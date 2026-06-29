<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Deliveries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class StatementOfAccountController extends Controller
{
    /**
     * Calculate total paid for a delivery, handling EWT recalculation and missing EWT detection
     */
    private function calculatePaid(float $invoiceAmount, ?object $paidData): float
    {
        if (!$paidData) return 0;

        $sumAmount = (float) $paidData->sum_amount;
        $sumDiscount = (float) $paidData->sum_discount;
        $sumEwt = (float) $paidData->sum_ewt;

        // Recalculate EWT based on invoice amount (gross) instead of payment amount
        if ($sumEwt > 0 && $sumAmount > 0) {
            $ewtRate = $sumEwt / $sumAmount;
            $correctEwt = $invoiceAmount * $ewtRate;
            $paid = $sumAmount + $sumDiscount + $correctEwt;
        } else {
            $paid = $sumAmount + $sumDiscount + $sumEwt;
        }

        // Detect missing/unrecorded EWT/CWT and recalculation residuals.
        // Standard withholding rates: CWT 1%, EWT 1%, 2%, 2.5%, 5%.
        // Widen upper bound to 5.5% to cover all standard rates plus rounding.
        if ($sumAmount > 0 && $invoiceAmount > 0) {
            $gap = $invoiceAmount - $paid;
            if ($gap > 0) {
                $gapPercent = ($gap / $invoiceAmount) * 100;

                if ($sumEwt == 0) {
                    // EWT/CWT not recorded — gap matches any standard withholding rate
                    if ($gapPercent >= 0.9 && $gapPercent <= 5.5) {
                        $paid = $invoiceAmount;
                    }
                } else {
                    // EWT was recorded but recalculation leaves a residual under 3%
                    if ($gapPercent <= 3.0) {
                        $paid = $invoiceAmount;
                    }
                }
            }
        }

        return $paid;
    }

    /**
     * List all customers with outstanding balances (deliveries + ar_aging)
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $perPage = 25;
        $page = (int) $request->input('page', 1);

        // ── Source 1: customers from delivered deliveries ──────────────────
        $deliveryQuery = DB::table('deliveries')
            ->select(
                DB::raw('TRIM(deliveries.customer_name) as client_name'),
                DB::raw('GROUP_CONCAT(DISTINCT cust.branch SEPARATOR \', \') as branch'),
                DB::raw('MAX(deliveries.sales_executive) as sales_executive'),
                DB::raw('MAX(cust.billing_address) as billing_address'),
                DB::raw('MAX(so.collection_terms) as terms'),
                DB::raw('COUNT(DISTINCT deliveries.id) as invoice_count'),
                DB::raw('COUNT(DISTINCT deliveries.customer_code) as branch_count'),
                DB::raw('SUM(
                    CASE WHEN (
                        COALESCE(NULLIF(CAST(di.items_total AS DECIMAL(15,2)), 0), CAST(deliveries.total_amount AS DECIMAL(15,2)))
                        - COALESCE(paid.sum_amount + paid.sum_discount + CASE WHEN paid.sum_ewt > 0 AND paid.sum_amount > 0 THEN COALESCE(NULLIF(CAST(di.items_total AS DECIMAL(15,2)), 0), CAST(deliveries.total_amount AS DECIMAL(15,2))) * (paid.sum_ewt / paid.sum_amount) ELSE paid.sum_ewt END, 0)
                        + COALESCE(adj.net_adjustment, 0)
                    ) > COALESCE(NULLIF(CAST(di.items_total AS DECIMAL(15,2)), 0), CAST(deliveries.total_amount AS DECIMAL(15,2))) * 0.03 THEN (
                        COALESCE(NULLIF(CAST(di.items_total AS DECIMAL(15,2)), 0), CAST(deliveries.total_amount AS DECIMAL(15,2)))
                        - COALESCE(paid.sum_amount + paid.sum_discount + CASE WHEN paid.sum_ewt > 0 AND paid.sum_amount > 0 THEN COALESCE(NULLIF(CAST(di.items_total AS DECIMAL(15,2)), 0), CAST(deliveries.total_amount AS DECIMAL(15,2))) * (paid.sum_ewt / paid.sum_amount) ELSE paid.sum_ewt END, 0)
                        + COALESCE(adj.net_adjustment, 0)
                    ) ELSE 0 END
                ) as outstanding_balance')
            )
            ->leftJoin('sales_orders as so', DB::raw('deliveries.sales_order_number COLLATE utf8mb4_unicode_ci'), '=', DB::raw('so.sales_order_number COLLATE utf8mb4_unicode_ci'))
            ->leftJoin('customers as cust', DB::raw('CAST(deliveries.customer_code AS CHAR) COLLATE utf8mb4_unicode_ci'), '=', DB::raw('CAST(cust.customer_code AS CHAR) COLLATE utf8mb4_unicode_ci'))
            ->leftJoin(DB::raw('(SELECT delivery_id, SUM(total_amount) as items_total FROM delivery_items GROUP BY delivery_id) as di'), 'di.delivery_id', '=', 'deliveries.id')
            ->leftJoin(DB::raw('(SELECT TRIM(dr_no) as dr_no, SUM(amount) as sum_amount, SUM(COALESCE(discount_amount, 0)) as sum_discount, SUM(COALESCE(ewt, 0)) as sum_ewt FROM payments WHERE (status IS NULL OR (status != \'Voided\' AND status != \'Bounced\')) GROUP BY TRIM(dr_no)) as paid'), function ($join) {
                $join->on(DB::raw('TRIM(deliveries.dr_no) COLLATE utf8mb4_unicode_ci'), '=', DB::raw('paid.dr_no COLLATE utf8mb4_unicode_ci'));
            })
            ->leftJoin(DB::raw('(SELECT dr_no, SUM(CASE WHEN is_decrease = 1 THEN -ABS(amount) ELSE ABS(amount) END) as net_adjustment FROM ar_adjustments WHERE dr_no IS NOT NULL AND dr_no != \'\' GROUP BY dr_no) as adj'), function ($join) {
                $join->on(DB::raw('TRIM(deliveries.dr_no) COLLATE utf8mb4_unicode_ci'), '=', DB::raw('TRIM(adj.dr_no) COLLATE utf8mb4_unicode_ci'));
            })
            ->where('deliveries.status', 'Delivered')
            ->where('deliveries.is_locked', '!=', 1)
            ->where('deliveries.is_pulled_out', '!=', 1)
            ->whereNotNull('deliveries.customer_code')
            ->where('deliveries.customer_code', '!=', '')
            ->groupBy('deliveries.customer_name')
            ->havingRaw('outstanding_balance > 150');

        if (!empty($search)) {
            $deliveryQuery->where('deliveries.customer_name', 'LIKE', "%{$search}%");
        }

        $deliveryCustomers = $deliveryQuery->get();

        // Names already covered by deliveries (for exclusion below)
        $coveredNames = $deliveryCustomers->map(fn($c) => strtolower(trim($c->client_name)))->toArray();

        // ── Source 2: customers ONLY in ar_aging (delivery not Delivered / missing) ─
        //
        // The outstanding_balance uses the same logic as show()/calculatePaid():
        //   base  = GREATEST(net_ar_balance, invoice_amount - settled_invoice_amount)
        //   paid  = SUM of payments from the payments table (amount + discount + ewt)
        //   gap % = (base - paid) / base
        //   If gap <= 3% of base (standard EWT rates are 1–2%), treat as fully paid.
        //   Otherwise outstanding = base - paid.
        //
        // This ensures the index never shows a customer as having outstanding balance
        // when show() would calculate 0 (preventing "empty detail view" confusion).
        $arAgingQuery = DB::table('ar_aging')
            ->select(
                DB::raw('TRIM(ar_aging.client_name) as client_name'),
                DB::raw('MAX(ar_aging.branch) as branch'),
                DB::raw('MAX(ar_aging.sales_executive) as sales_executive'),
                DB::raw('NULL as billing_address'),
                DB::raw('MAX(ar_aging.collection_terms) as terms'),
                DB::raw('COUNT(*) as invoice_count'),
                DB::raw('1 as branch_count'),
                DB::raw('SUM(
                    CASE
                        WHEN COALESCE(ar_aging.invoice_amount, 0) <= 0 THEN 0
                        ELSE GREATEST(0,
                            -- base remaining balance (respects pre-import partial settlements)
                            CASE
                                WHEN COALESCE(pmts.total_paid, 0) = 0
                                    THEN GREATEST(
                                            COALESCE(ar_aging.net_ar_balance, 0),
                                            COALESCE(ar_aging.invoice_amount, 0) - COALESCE(ar_aging.settled_invoice_amount, 0)
                                         )
                                -- gap <= 3% of base → EWT withholding, treat as fully paid
                                WHEN (GREATEST(
                                            COALESCE(ar_aging.net_ar_balance, 0),
                                            COALESCE(ar_aging.invoice_amount, 0) - COALESCE(ar_aging.settled_invoice_amount, 0)
                                        ) - COALESCE(pmts.total_paid, 0))
                                     / NULLIF(GREATEST(
                                            COALESCE(ar_aging.net_ar_balance, 0),
                                            COALESCE(ar_aging.invoice_amount, 0) - COALESCE(ar_aging.settled_invoice_amount, 0)
                                        ), 0) <= 0.03
                                    THEN 0
                                ELSE GREATEST(
                                        COALESCE(ar_aging.net_ar_balance, 0),
                                        COALESCE(ar_aging.invoice_amount, 0) - COALESCE(ar_aging.settled_invoice_amount, 0)
                                     ) - COALESCE(pmts.total_paid, 0)
                            END
                        )
                    END
                ) as outstanding_balance')
            )
            ->leftJoin(
                DB::raw('(SELECT TRIM(dr_no) AS dr_no,
                              SUM(amount + COALESCE(discount_amount, 0) + COALESCE(ewt, 0)) AS total_paid
                          FROM payments
                          WHERE dr_no IS NOT NULL AND dr_no != \'\'
                            AND (status IS NULL OR (status != \'Voided\' AND status != \'Bounced\'))
                          GROUP BY TRIM(dr_no)) AS pmts'),
                DB::raw('TRIM(ar_aging.dr_no)'), '=', 'pmts.dr_no'
            )
            ->where(function($q) {
                $q->whereNull('ar_aging.status')->orWhere('ar_aging.status', '')->orWhere('ar_aging.status', '!=', 'Paid');
            })
            ->where('ar_aging.invoice_amount', '>', 0)
            ->whereNotNull('ar_aging.client_name')
            ->where('ar_aging.client_name', '!=', '')
            // Exclude DRs that already have a matching delivered delivery
            ->whereNotIn(DB::raw('TRIM(ar_aging.dr_no) COLLATE utf8mb4_unicode_ci'), function($sub) {
                $sub->select(DB::raw('TRIM(dr_no) COLLATE utf8mb4_unicode_ci'))
                    ->from('deliveries')
                    ->where('status', 'Delivered')
                    ->where('is_pulled_out', '!=', 1);
            })
            // Exclude locked DRs (cross-check deliveries table)
            ->whereNotIn(DB::raw('TRIM(ar_aging.dr_no) COLLATE utf8mb4_unicode_ci'), function($sub) {
                $sub->select(DB::raw('TRIM(dr_no) COLLATE utf8mb4_unicode_ci'))
                    ->from('deliveries')
                    ->where('is_locked', 1);
            })
            ->groupBy('ar_aging.client_name')
            ->havingRaw('outstanding_balance > 10');

        if (!empty($search)) {
            $arAgingQuery->where('client_name', 'LIKE', "%{$search}%");
        }

        $arAgingCustomers = $arAgingQuery->get()
            ->filter(fn($c) => !in_array(strtolower(trim($c->client_name)), $coveredNames));

        // ── Merge both sources, sort by outstanding balance descending ────
        $all = $deliveryCustomers->concat($arAgingCustomers)
            ->sortByDesc('outstanding_balance')
            ->values();

        $total = $all->count();
        $items = $all->slice(($page - 1) * $perPage, $perPage)->values();

        $customers = new \Illuminate\Pagination\LengthAwarePaginator(
            $items, $total, $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('soa.index', compact('customers', 'search'));
    }

    /**
     * Show SOA detail for a specific customer (from deliveries)
     */
    public function show($customerCode)
    {
        $today = Carbon::now();
        $customerCode = urldecode($customerCode);

        // Resolve all customer codes by customer name — from deliveries first, then ar_aging
        $relatedCodes = DB::table('deliveries')
            ->whereRaw('TRIM(customer_name) = TRIM(?)', [$customerCode])
            ->distinct()
            ->pluck('customer_code')
            ->toArray();

        if (empty($relatedCodes)) {
            // Fallback: try ar_aging client_name
            $relatedCodes = DB::table('ar_aging')
                ->whereRaw('TRIM(client_name) = TRIM(?)', [$customerCode])
                ->distinct()
                ->pluck('customer_code')
                ->filter()
                ->toArray();
        }

        if (empty($relatedCodes)) {
            $relatedCodes = [$customerCode];
        }

        // Get all delivered deliveries for this customer and all its branches
        $deliveries = DB::table('deliveries')
            ->leftJoin('sales_orders as so', DB::raw('deliveries.sales_order_number COLLATE utf8mb4_unicode_ci'), '=', DB::raw('so.sales_order_number COLLATE utf8mb4_unicode_ci'))
            ->leftJoin('customers as cust', DB::raw('CAST(deliveries.customer_code AS CHAR) COLLATE utf8mb4_unicode_ci'), '=', DB::raw('CAST(cust.customer_code AS CHAR) COLLATE utf8mb4_unicode_ci'))
            ->whereIn('deliveries.customer_code', $relatedCodes)
            ->where('deliveries.status', 'Delivered')
            ->where('deliveries.is_locked', '!=', 1)
            ->where('deliveries.is_pulled_out', '!=', 1)
            ->select(
                'deliveries.id',
                'deliveries.dr_no',
                'deliveries.sales_invoice_no',
                'deliveries.customer_code',
                'deliveries.customer_name',
                DB::raw('COALESCE(cust.branch, deliveries.branch) as branch'),
                'deliveries.sales_executive',
                'deliveries.total_amount',
                'deliveries.request_delivery_date',
                'deliveries.counter_date',
                DB::raw('COALESCE(so.collection_terms, 30) as terms')
            )
            ->orderBy('deliveries.request_delivery_date', 'asc')
            ->get();

        // Try ar_aging if no delivered deliveries found — might be ar_aging-only customer
        $arAgingFallback = null;
        if ($deliveries->isEmpty()) {
            $arAgingFallback = DB::table('ar_aging')
                ->whereIn('customer_code', $relatedCodes)
                ->where('invoice_amount', '>', 0)
                ->orderBy('invoice_date', 'desc')
                ->first();

            if (!$arAgingFallback) {
                return redirect()->route('soa.index')->with('error', 'No records found for this customer.');
            }
        }

        $firstRecord   = $deliveries->first();
        $customerName  = $firstRecord?->customer_name  ?? $arAgingFallback?->client_name;
        $branch        = $firstRecord?->branch         ?? $arAgingFallback?->branch;
        $terms         = $firstRecord?->terms          ?? $arAgingFallback?->collection_terms ?? 30;
        $salesExec     = $firstRecord?->sales_executive ?? $arAgingFallback?->sales_executive;

        // Get billing address from customers table for display
        $customerRecord = DB::table('customers')
            ->where('customer_code', $customerCode)
            ->select('billing_address')
            ->first();
        $billingAddress = $customerRecord->billing_address ?? null;

        // Get payment breakdown per DR (amount, discount, ewt separately) — across all branch codes
        $paidPerDr = Payment::select(
                DB::raw('TRIM(dr_no) as dr_no'),
                DB::raw('SUM(amount) as sum_amount'),
                DB::raw('SUM(COALESCE(discount_amount, 0)) as sum_discount'),
                DB::raw('SUM(COALESCE(ewt, 0)) as sum_ewt')
            )
            ->where(function ($q) use ($relatedCodes, $customerName) {
                $q->whereIn('customer_code', $relatedCodes)
                  ->orWhere('customer_name', 'LIKE', '%' . $customerName . '%');
            })
            ->where(function ($q) {
                $q->whereNull('status')->orWhere(function ($q2) { $q2->where('status', '!=', 'Voided')->where('status', '!=', 'Bounced'); });
            })
            ->groupBy(DB::raw('TRIM(dr_no)'))
            ->get()
            ->keyBy('dr_no');

        // Get AR adjustments per DR for this customer — across all branch codes
        $adjustmentsPerDr = DB::table('ar_adjustments')
            ->whereIn('customer_code', $relatedCodes)
            ->whereNotNull('dr_no')
            ->where('dr_no', '!=', '')
            ->select('dr_no', DB::raw('SUM(CASE WHEN is_decrease = 1 THEN -ABS(amount) ELSE ABS(amount) END) as net_adjustment'))
            ->groupBy('dr_no')
            ->get()
            ->keyBy(function($a) { return trim($a->dr_no); });

        // Pre-fetch delivery_items totals for all deliveries at once (avoid N+1 queries).
        // delivery_items is the authoritative source — deliveries.total_amount can be stale.
        $deliveryIds = $deliveries->pluck('id')->toArray();
        $itemsTotals = DB::table('delivery_items')
            ->whereIn('delivery_id', $deliveryIds)
            ->select('delivery_id', DB::raw('SUM(total_amount) as items_total'))
            ->groupBy('delivery_id')
            ->get()
            ->keyBy('delivery_id');

        // Pre-fetch ar_aging paid status per DR — if ar_aging marks a DR as Paid
        // and the collection module has recorded payment, skip it from SOA entirely.
        $drNosForStatus = $deliveries->pluck('dr_no')->map(fn($d) => trim($d))->filter()->toArray();
        $arPaidStatus = collect();
        if (!empty($drNosForStatus)) {
            $arPaidStatus = DB::table('ar_aging')
                ->whereIn(DB::raw('TRIM(dr_no)'), $drNosForStatus)
                ->select(DB::raw('TRIM(dr_no) as dr_no'), 'net_ar_balance', 'status')
                ->get()
                ->keyBy(fn($r) => trim($r->dr_no));
        }

        // Build detail rows
        $totalCurrent = 0;
        $totalPastDue = 0;
        $detailRows = [];

        foreach ($deliveries as $delivery) {
            $drNo = trim($delivery->dr_no);

            // Skip DRs that ar_aging has already marked Paid AND a payment exists
            $arStatus = $arPaidStatus[$drNo] ?? null;
            if ($arStatus && isset($paidPerDr[$drNo]) &&
                ((float)$arStatus->net_ar_balance <= 0 || strtolower(trim($arStatus->status ?? '')) === 'paid')) {
                continue;
            }

            // Use delivery_items total as authoritative amount; fall back to total_amount
            $itemsTotal = (float)($itemsTotals[$delivery->id]->items_total ?? 0);
            $invoiceAmount = $itemsTotal > 0 ? $itemsTotal : (float) $delivery->total_amount;

            $paidData = $paidPerDr[$drNo] ?? null;

            $paid = $this->calculatePaid($invoiceAmount, $paidData);

            // Apply AR adjustments for this DR (negative = decrease AR, positive = increase AR)
            $outstanding = $invoiceAmount - $paid;
            if (isset($adjustmentsPerDr[$drNo])) {
                $outstanding += (float) $adjustmentsPerDr[$drNo]->net_adjustment;
            }
            $outstanding = max(0, $outstanding);
            // ₱150 leeway — absorbs EWT/CWT rounding differences across standard rates
            if ($outstanding <= 150) continue;

            // Use counter_date for aging if available, otherwise fall back to delivery date
            $baseDate = $delivery->counter_date
                ? Carbon::parse($delivery->counter_date)
                : ($delivery->request_delivery_date ? Carbon::parse($delivery->request_delivery_date) : null);
            $termsDays = (int) ($delivery->terms ?? 30);
            $dueDate = $baseDate ? $baseDate->copy()->addDays($termsDays) : null;

            $daysOutstanding = 0;
            if ($dueDate) {
                $daysOutstanding = (int) $dueDate->diffInDays($today, false);
            }

            $current = 0;
            $pastDue = 0;
            if ($daysOutstanding <= 0) {
                $current = $outstanding;
            } else {
                $pastDue = $outstanding;
            }

            $totalCurrent += $current;
            $totalPastDue += $pastDue;

            $deliveryDate = $delivery->request_delivery_date;
            $counterDate = $delivery->counter_date ?? $deliveryDate; // fallback to delivery date if no counter date

            $detailRows[] = (object) [
                'invoice_date' => $deliveryDate,
                'counter_date' => $counterDate,
                'due_date' => $dueDate ? $dueDate->format('Y-m-d') : null,
                'soa_date' => $today->format('Y-m-d'),
                'dr_no' => $delivery->dr_no,
                'invoice_no' => $delivery->sales_invoice_no,
                'branch' => $delivery->branch,
                'customer_code' => $delivery->customer_code,
                'days_outstanding' => $daysOutstanding,
                'current' => $current,
                'past_due' => $pastDue,
                'outstanding' => $outstanding,
            ];
        }

        // ── Supplement with ar_aging rows not already covered by deliveries ──
        $coveredDrNos = collect($detailRows)->map(fn($r) => trim($r->dr_no))->filter()->toArray();

        $lockedDrNos = DB::table('deliveries')->where('is_locked', 1)
            ->pluck('dr_no')->map(fn($d) => trim($d))->filter()->toArray();

        $arAgingExtra = DB::table('ar_aging')
            ->whereIn('customer_code', $relatedCodes)
            ->where(function($q) {
                $q->whereNull('status')->orWhere('status', '')->orWhere('status', '!=', 'Paid');
            })
            ->where('invoice_amount', '>', 0)
            ->when(!empty($coveredDrNos), fn($q) => $q->whereNotIn(DB::raw('TRIM(dr_no) COLLATE utf8mb4_unicode_ci'), $coveredDrNos))
            ->when(!empty($lockedDrNos), fn($q) => $q->whereNotIn(DB::raw('TRIM(dr_no) COLLATE utf8mb4_unicode_ci'), $lockedDrNos))
            ->get();

        foreach ($arAgingExtra as $arRow) {
            $drNo = trim($arRow->dr_no ?? '');
            $invoiceAmount = (float) ($arRow->invoice_amount ?? 0);
            if ($invoiceAmount <= 0) continue;

            $paidData = $paidPerDr[$drNo] ?? null;

            $paid = $this->calculatePaid($invoiceAmount, $paidData);

            $outstanding = $invoiceAmount - $paid;
            if (isset($adjustmentsPerDr[$drNo])) {
                $outstanding += (float) $adjustmentsPerDr[$drNo]->net_adjustment;
            }
            $outstanding = max(0, $outstanding);
            if ($outstanding <= 10) continue;

            $deliveryDate = $arRow->invoice_date;
            $counterDate  = $arRow->counter_date ?? $deliveryDate;
            $baseDate     = $counterDate ? Carbon::parse($counterDate) : ($deliveryDate ? Carbon::parse($deliveryDate) : null);
            $termsDays    = (int) ($arRow->collection_terms ?? $arRow->terms ?? 30);
            $dueDate      = $baseDate ? $baseDate->copy()->addDays($termsDays) : null;
            $daysOutstanding = $dueDate ? (int) $dueDate->diffInDays($today, false) : 0;

            $current = $daysOutstanding <= 0 ? $outstanding : 0;
            $pastDue = $daysOutstanding >  0 ? $outstanding : 0;

            $totalCurrent  += $current;
            $totalPastDue  += $pastDue;

            $detailRows[] = (object) [
                'invoice_date'    => $deliveryDate,
                'counter_date'    => $counterDate,
                'due_date'        => $dueDate ? $dueDate->format('Y-m-d') : null,
                'soa_date'        => $today->format('Y-m-d'),
                'dr_no'           => $arRow->dr_no,
                'invoice_no'      => $arRow->invoice_no,
                'branch'          => $arRow->branch,
                'customer_code'   => $arRow->customer_code,
                'days_outstanding'=> $daysOutstanding,
                'current'         => $current,
                'past_due'        => $pastDue,
                'outstanding'     => $outstanding,
            ];
        }

        // Re-sort all rows by delivery date ascending
        usort($detailRows, fn($a, $b) => strcmp($a->invoice_date ?? '', $b->invoice_date ?? ''));

        // AR Adjustments (only those NOT linked to a specific DR — per-DR ones already applied above)
        $netAdjustments = (float) DB::table('ar_adjustments')
            ->whereIn('customer_code', $relatedCodes)
            ->where(function($q) {
                $q->whereNull('dr_no')->orWhere('dr_no', '');
            })
            ->select(DB::raw('SUM(CASE WHEN is_decrease = 1 THEN -ABS(amount) ELSE ABS(amount) END) as total'))
            ->value('total') ?? 0;

        $totalBalance = $totalCurrent + $totalPastDue + $netAdjustments;

        // Credit balance (overpayments minus credits already applied)
        $overpayments = Payment::where('overpayment', '>', 0)
            ->where(function ($q) use ($relatedCodes, $customerName) {
                $q->whereIn('customer_code', $relatedCodes)
                  ->orWhere('customer_name', 'LIKE', '%' . $customerName . '%');
            })
            ->get();

        $creditBalance = 0;
        foreach ($overpayments as $op) {
            $usedLegacy = (float) Payment::where('credit_from_payment_id', $op->id)
                ->sum('credit_applied');
            $usedJunction = (float) DB::table('payment_credit_applications')
                ->where('credit_source_payment_id', $op->id)
                ->sum('amount');
            $used = max($usedLegacy, $usedJunction);
            $remaining = (float) $op->overpayment - $used;
            if ($remaining > 0) {
                $creditBalance += $remaining;
            }
        }

        // No longer need collectionsToDeduct since we subtract per-DR
        $collectionsToDeduct = 0;

        return view('soa.show', compact(
            'customerCode', 'customerName', 'branch', 'terms', 'salesExec',
            'billingAddress', 'detailRows', 'totalCurrent', 'totalPastDue', 'totalBalance', 'today',
            'collectionsToDeduct', 'netAdjustments', 'creditBalance'
        ));
    }

    /**
     * Export SOA as Excel
     */
    public function export($customerCode)
    {
        $today = Carbon::now();
        $customerCode = urldecode($customerCode);

        // Resolve customer codes by name — deliveries first, ar_aging fallback
        $relatedCodes = DB::table('deliveries')
            ->whereRaw('TRIM(customer_name) = TRIM(?)', [$customerCode])
            ->distinct()->pluck('customer_code')->toArray();
        if (empty($relatedCodes)) {
            $relatedCodes = DB::table('ar_aging')
                ->whereRaw('TRIM(client_name) = TRIM(?)', [$customerCode])
                ->distinct()->pluck('customer_code')->filter()->toArray();
        }
        if (empty($relatedCodes)) {
            $relatedCodes = [$customerCode];
        }

        // Get all delivered deliveries for this customer and all its branches
        $deliveries = DB::table('deliveries')
            ->leftJoin('sales_orders as so', DB::raw('deliveries.sales_order_number COLLATE utf8mb4_unicode_ci'), '=', DB::raw('so.sales_order_number COLLATE utf8mb4_unicode_ci'))
            ->leftJoin('customers as cust', DB::raw('CAST(deliveries.customer_code AS CHAR) COLLATE utf8mb4_unicode_ci'), '=', DB::raw('CAST(cust.customer_code AS CHAR) COLLATE utf8mb4_unicode_ci'))
            ->whereIn('deliveries.customer_code', $relatedCodes)
            ->where('deliveries.status', 'Delivered')
            ->where('deliveries.is_locked', '!=', 1)
            ->where('deliveries.is_pulled_out', '!=', 1)
            ->select(
                'deliveries.id',
                'deliveries.dr_no',
                'deliveries.sales_invoice_no',
                'deliveries.customer_name',
                DB::raw('COALESCE(cust.branch, deliveries.branch) as branch'),
                'deliveries.total_amount',
                'deliveries.request_delivery_date',
                'deliveries.counter_date',
                DB::raw('COALESCE(so.collection_terms, 30) as terms')
            )
            ->orderBy('deliveries.request_delivery_date', 'asc')
            ->get();

        // Determine customer name — deliveries first, ar_aging fallback
        $customerName = $deliveries->isNotEmpty()
            ? $deliveries->first()->customer_name
            : (DB::table('ar_aging')->whereIn('customer_code', $relatedCodes)->value('client_name') ?? $customerCode);

        if ($deliveries->isEmpty()) {
            // Check ar_aging has records before proceeding
            $hasArAging = DB::table('ar_aging')->whereIn('customer_code', $relatedCodes)->where('invoice_amount', '>', 0)->exists();
            if (!$hasArAging) {
                return back()->with('error', 'No outstanding records found.');
            }
        }

        // Get payment breakdown per DR — across all branch codes
        $paidPerDr = Payment::select(
                DB::raw('TRIM(dr_no) as dr_no'),
                DB::raw('SUM(amount) as sum_amount'),
                DB::raw('SUM(COALESCE(discount_amount, 0)) as sum_discount'),
                DB::raw('SUM(COALESCE(ewt, 0)) as sum_ewt')
            )
            ->where(function ($q) use ($relatedCodes, $customerName) {
                $q->whereIn('customer_code', $relatedCodes)
                  ->orWhere('customer_name', 'LIKE', '%' . $customerName . '%');
            })
            ->where(function ($q) {
                $q->whereNull('status')->orWhere(function ($q2) { $q2->where('status', '!=', 'Voided')->where('status', '!=', 'Bounced'); });
            })
            ->groupBy(DB::raw('TRIM(dr_no)'))
            ->get()
            ->keyBy('dr_no');

        // Get AR adjustments per DR — across all branch codes
        $adjustmentsPerDr = DB::table('ar_adjustments')
            ->whereIn('customer_code', $relatedCodes)
            ->whereNotNull('dr_no')
            ->where('dr_no', '!=', '')
            ->select('dr_no', DB::raw('SUM(CASE WHEN is_decrease = 1 THEN -ABS(amount) ELSE ABS(amount) END) as net_adjustment'))
            ->groupBy('dr_no')
            ->get()
            ->keyBy(function($a) { return trim($a->dr_no); });

        // Build detail rows
        $detailRows = [];
        $totalCurrent = 0;
        $totalPastDue = 0;

        foreach ($deliveries as $delivery) {
            $invoiceAmount = (float) $delivery->total_amount;
            if ($invoiceAmount <= 0) {
                $invoiceAmount = (float) DB::table('delivery_items')
                    ->where('delivery_id', $delivery->id)
                    ->sum('total_amount');
            }
            $drNo = trim($delivery->dr_no);

            $paidData = $paidPerDr[$drNo] ?? null;

            $paid = $this->calculatePaid($invoiceAmount, $paidData);

            // Apply AR adjustments for this DR (negative = decrease AR, positive = increase AR)
            $outstanding = $invoiceAmount - $paid;
            if (isset($adjustmentsPerDr[$drNo])) {
                $outstanding += (float) $adjustmentsPerDr[$drNo]->net_adjustment;
            }
            $outstanding = max(0, $outstanding);
            if ($outstanding <= 10) continue; // ₱10 leeway — treat small balances as fully paid

            // Use counter_date for aging if available, otherwise fall back to delivery date
            $baseDate = $delivery->counter_date
                ? Carbon::parse($delivery->counter_date)
                : ($delivery->request_delivery_date ? Carbon::parse($delivery->request_delivery_date) : null);
            $termsDays = (int) ($delivery->terms ?? 30);
            $dueDate = $baseDate ? $baseDate->copy()->addDays($termsDays) : null;
            $daysOutstanding = $dueDate ? (int) $dueDate->diffInDays($today, false) : 0;

            $current = 0;
            $pastDue = 0;
            if ($daysOutstanding <= 0) {
                $current = $outstanding;
            } else {
                $pastDue = $outstanding;
            }

            $totalCurrent += $current;
            $totalPastDue += $pastDue;

            $detailRows[] = [
                'delivery_date' => $delivery->request_delivery_date,
                'counter_date' => $delivery->counter_date ?? $delivery->request_delivery_date,
                'due_date' => $dueDate ? $dueDate->format('n/j/Y') : '',
                'dr_no' => $delivery->dr_no,
                'invoice_no' => $delivery->sales_invoice_no,
                'days_outstanding' => $daysOutstanding,
                'current' => $current,
                'past_due' => $pastDue,
            ];
        }

        // ── Supplement with ar_aging rows not already covered by deliveries ──
        $coveredExportDrNos = array_column($detailRows, 'dr_no');
        $coveredExportDrNos = array_map('trim', array_filter($coveredExportDrNos));

        $lockedExportDrNos = DB::table('deliveries')->where('is_locked', 1)
            ->pluck('dr_no')->map(fn($d) => trim($d))->filter()->toArray();

        $arAgingExport = DB::table('ar_aging')
            ->whereIn('customer_code', $relatedCodes)
            ->where(function($q) {
                $q->whereNull('status')->orWhere('status', '')->orWhere('status', '!=', 'Paid');
            })
            ->where('invoice_amount', '>', 0)
            ->when(!empty($coveredExportDrNos), fn($q) => $q->whereNotIn(DB::raw('TRIM(dr_no) COLLATE utf8mb4_unicode_ci'), $coveredExportDrNos))
            ->when(!empty($lockedExportDrNos), fn($q) => $q->whereNotIn(DB::raw('TRIM(dr_no) COLLATE utf8mb4_unicode_ci'), $lockedExportDrNos))
            ->get();

        foreach ($arAgingExport as $arRow) {
            $drNo = trim($arRow->dr_no ?? '');
            $invoiceAmount = (float) ($arRow->invoice_amount ?? 0);
            if ($invoiceAmount <= 0) continue;

            $paidData = $paidPerDr[$drNo] ?? null;
            $paid = $this->calculatePaid($invoiceAmount, $paidData);

            $outstanding = $invoiceAmount - $paid;
            if (isset($adjustmentsPerDr[$drNo])) {
                $outstanding += (float) $adjustmentsPerDr[$drNo]->net_adjustment;
            }
            $outstanding = max(0, $outstanding);
            if ($outstanding <= 10) continue;

            $deliveryDate = $arRow->invoice_date;
            $counterDate  = $arRow->counter_date ?? $deliveryDate;
            $baseDate     = $counterDate ? Carbon::parse($counterDate) : ($deliveryDate ? Carbon::parse($deliveryDate) : null);
            $termsDays    = (int) ($arRow->collection_terms ?? $arRow->terms ?? 30);
            $dueDate      = $baseDate ? $baseDate->copy()->addDays($termsDays) : null;
            $daysOutstanding = $dueDate ? (int) $dueDate->diffInDays($today, false) : 0;

            $current = $daysOutstanding <= 0 ? $outstanding : 0;
            $pastDue = $daysOutstanding >  0 ? $outstanding : 0;
            $totalCurrent += $current;
            $totalPastDue += $pastDue;

            $detailRows[] = [
                'delivery_date'    => $deliveryDate,
                'counter_date'     => $counterDate,
                'due_date'         => $dueDate ? $dueDate->format('n/j/Y') : '',
                'dr_no'            => $arRow->dr_no,
                'invoice_no'       => $arRow->invoice_no,
                'days_outstanding' => $daysOutstanding,
                'current'          => $current,
                'past_due'         => $pastDue,
            ];
        }

        // Sort by delivery date ascending
        usort($detailRows, fn($a, $b) => strcmp($a['delivery_date'] ?? '', $b['delivery_date'] ?? ''));

        // AR Adjustments (only those NOT linked to a specific DR — per-DR ones already applied above)
        $netAdjustments = (float) DB::table('ar_adjustments')
            ->whereRaw('TRIM(customer_code) = ?', [trim($customerCode)])
            ->where(function($q) {
                $q->whereNull('dr_no')->orWhere('dr_no', '');
            })
            ->select(DB::raw('SUM(CASE WHEN is_decrease = 1 THEN -ABS(amount) ELSE ABS(amount) END) as total'))
            ->value('total') ?? 0;

        $totalBalance = $totalCurrent + $totalPastDue + $netAdjustments;

        // Credit balance
        $creditBalance = 0;
        $exportOverpayments = Payment::where('overpayment', '>', 0)
            ->where(function ($q) use ($customerCode, $customerName) {
                $q->whereRaw('TRIM(customer_code) = ?', [trim($customerCode)])
                  ->orWhere('customer_name', 'LIKE', '%' . $customerName . '%');
            })
            ->get();
        foreach ($exportOverpayments as $op) {
            $usedLegacy = (float) Payment::where('credit_from_payment_id', $op->id)->sum('credit_applied');
            $usedJunction = (float) DB::table('payment_credit_applications')->where('credit_source_payment_id', $op->id)->sum('amount');
            $remaining = (float) $op->overpayment - max($usedLegacy, $usedJunction);
            if ($remaining > 0) $creditBalance += $remaining;
        }

        // Build Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Statement of Account');

        // Colors
        $darkRed = '8B0000';
        $white = 'FFFFFF';

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(24);
        $sheet->getColumnDimension('G')->setWidth(16);
        $sheet->getColumnDimension('H')->setWidth(16);

        // ====== LOGO ======
        $logoPath = public_path('images/meatplus-logo.png');
        if (file_exists($logoPath)) {
            $drawing = new Drawing();
            $drawing->setName('Logo');
            $drawing->setDescription('Meatplus Logo');
            $drawing->setPath($logoPath);
            $drawing->setHeight(50);
            $drawing->setCoordinates('A1');
            $drawing->setWorksheet($sheet);
            $sheet->getRowDimension(1)->setRowHeight(40);
        }

        // ====== HEADER SECTION ======
        $sheet->setCellValue('C1', 'MEATPLUS TRADING CORPORATION');
        $sheet->mergeCells('C1:E1');
        $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(14)->setColor(new Color($darkRed));
        $sheet->getStyle('C1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('G1', 'Statement of Account');
        $sheet->mergeCells('G1:H1');
        $sheet->getStyle('G1:H1')->getFont()->setBold(true)->setSize(14)->setColor(new Color($darkRed))->setItalic(true);
        $sheet->getStyle('G1:H1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->setCellValue('A3', 'Bill to');
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(9);
        $sheet->setCellValue('B3', $customerName);
        $sheet->mergeCells('B3:D3');
        $sheet->getStyle('B3:D3')->getFont()->setBold(true)->setSize(11);

        $sheet->setCellValue('G3', 'Statement Date');
        $sheet->setCellValue('H3', $today->format('F d, Y'));
        $sheet->getStyle('G3')->getFont()->setBold(true)->setSize(9);
        $sheet->getStyle('H3')->getFont()->setSize(9);
        $sheet->getStyle('H3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->setCellValue('A4', 'Receivable as of ' . $today->format('M d, Y'));
        $sheet->mergeCells('A4:B4');
        $sheet->getStyle('A4')->getFont()->setBold(true)->setSize(8);
        $sheet->setCellValue('C4', $totalBalance);
        $sheet->getStyle('C4')->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('C4')->getFont()->setBold(true)->setSize(10);

        $sheet->setCellValue('G4', 'Statement Period');
        $sheet->setCellValue('H4', $today->format('F Y'));
        $sheet->getStyle('G4')->getFont()->setBold(true)->setSize(9);
        $sheet->getStyle('H4')->getFont()->setSize(9);
        $sheet->getStyle('H4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // ====== TABLE HEADER ======
        $headerRow = 6;
        $headers = ['Delivery Date', 'Counter Date', 'Due Date', 'DR No.', 'SI No.', 'No. Of Days Outstanding', 'Current Due', 'Past Due'];

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'] as $i => $col) {
            $sheet->setCellValue("{$col}{$headerRow}", $headers[$i]);
        }

        $sheet->getStyle("A{$headerRow}:H{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => $white], 'size' => 9],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $darkRed]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $darkRed]]],
        ]);

        // ====== DATA ROWS ======
        $dataRow = $headerRow + 1;
        foreach ($detailRows as $idx => $row) {
            $r = $dataRow + $idx;
            $sheet->setCellValue("A{$r}", $row['delivery_date'] ? Carbon::parse($row['delivery_date'])->format('n/j/Y') : '');
            $sheet->setCellValue("B{$r}", $row['counter_date'] ? Carbon::parse($row['counter_date'])->format('n/j/Y') : '');
            $sheet->setCellValue("C{$r}", $row['due_date']);
            $sheet->setCellValue("D{$r}", $row['dr_no']);
            $sheet->setCellValue("E{$r}", $row['invoice_no']);
            $sheet->setCellValue("F{$r}", $row['days_outstanding']);
            $sheet->setCellValue("G{$r}", $row['current'] > 0 ? $row['current'] : '');
            $sheet->setCellValue("H{$r}", $row['past_due'] > 0 ? $row['past_due'] : '');

            $sheet->getStyle("A{$r}:F{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$r}:H{$r}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("G{$r}:H{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            if ($idx % 2 === 1) {
                $sheet->getStyle("A{$r}:H{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF5F5');
            }

            $sheet->getStyle("A{$r}:H{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('DDDDDD');
        }

        // ====== NOTHING FOLLOWS ======
        $nfRow = $dataRow + count($detailRows);
        $sheet->setCellValue("A{$nfRow}", '—Nothing Follows—');
        $sheet->mergeCells("A{$nfRow}:H{$nfRow}");
        $sheet->getStyle("A{$nfRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$nfRow}")->getFont()->setItalic(true)->setSize(9);

        // ====== CURRENT BALANCE ROW ======
        $totRow = $nfRow + 1;
        $sheet->setCellValue("A{$totRow}", 'Current Balance:');
        $sheet->mergeCells("A{$totRow}:F{$totRow}");
        $sheet->getStyle("A{$totRow}")->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle("A{$totRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue("G{$totRow}", $totalCurrent);
        $sheet->setCellValue("H{$totRow}", $totalPastDue);
        $sheet->getStyle("G{$totRow}:H{$totRow}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("A{$totRow}:H{$totRow}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $darkRed]],
            'font' => ['bold' => true, 'color' => ['rgb' => $white]],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $darkRed]]],
        ]);
        $sheet->getStyle("G{$totRow}:H{$totRow}")->getFont()->setColor(new Color($white));

        // ====== FOOTER ======
        $footRow = $totRow + 2;
        $sheet->setCellValue("A{$footRow}", 'For any comments and questions about this statement of account, please contact');
        $sheet->mergeCells("A{$footRow}:H{$footRow}");
        $sheet->getStyle("A{$footRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$footRow}")->getFont()->setSize(9);

        $footRow++;
        $sheet->setCellValue("A{$footRow}", 'Meatplus Trading Corporation');
        $sheet->mergeCells("A{$footRow}:H{$footRow}");
        $sheet->getStyle("A{$footRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$footRow}")->getFont()->setBold(true)->setSize(9);

        $footRow++;
        $sheet->setCellValue("A{$footRow}", 'Telephone: 244-4618 / 244-4619');
        $sheet->mergeCells("A{$footRow}:H{$footRow}");
        $sheet->getStyle("A{$footRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$footRow}")->getFont()->setSize(9);

        $footRow++;
        $sheet->setCellValue("A{$footRow}", 'Email: treasury@meatplus.ph');
        $sheet->mergeCells("A{$footRow}:H{$footRow}");
        $sheet->getStyle("A{$footRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$footRow}")->getFont()->setSize(9);

        $footRow++;
        $sheet->setCellValue("A{$footRow}", 'Suite 1207 Victoria Building, 429 U.N. Avenue, Ermita, Manila');
        $sheet->mergeCells("A{$footRow}:H{$footRow}");
        $sheet->getStyle("A{$footRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$footRow}")->getFont()->setSize(9);

        $footRow += 2;
        $sheet->setCellValue("A{$footRow}", 'If we do not hear from you within three (3) days from receipt of this statement, we will assume that you are in agreement with the outstanding balance.');
        $sheet->mergeCells("A{$footRow}:H{$footRow}");
        $sheet->getStyle("A{$footRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);
        $sheet->getStyle("A{$footRow}")->getFont()->setSize(8)->setItalic(true);

        $footRow += 2;
        $sheet->setCellValue("A{$footRow}", 'Prepared by:');
        $sheet->getStyle("A{$footRow}")->getFont()->setBold(true)->setSize(9);
        $sheet->setCellValue("G{$footRow}", 'Noted By:');
        $sheet->getStyle("G{$footRow}")->getFont()->setBold(true)->setSize(9);

        $footRow++;
        $sheet->setCellValue("A{$footRow}", auth()->user()->name ?? 'Treasury');
        $sheet->getStyle("A{$footRow}")->getFont()->setItalic(true)->setSize(9);

        // Print setup
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setFitToWidth(1);

        $safeCode = preg_replace('/[^a-zA-Z0-9_-]/', '_', $customerCode);
        $filename = 'SOA_' . $safeCode . '_' . $today->format('Y-m-d') . '.xlsx';
        $tempPath = storage_path('app/' . $filename);

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
