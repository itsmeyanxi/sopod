<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\ArAging;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PaymentController extends Controller
{
    /**
     * Display the payment entry screen
     */
    public function entry()
    {
        return view('payments.entry');
    }

    /**
     * ✅ DEBUG: Detailed search debugging - shows what each query finds
     */
    public function debugSearch($search)
    {
        // Check ar_aging
        $arAgingResult = DB::table('ar_aging')
            ->select('customer_code', 'client_name', 'dr_no')
            ->where(function($query) use ($search) {
                $query->whereRaw('TRIM(ar_aging.customer_code) = ?', [trim($search)])
                      ->orWhereRaw('TRIM(ar_aging.customer_code) LIKE ?', ['%' . trim($search) . '%'])
                      ->orWhereRaw('TRIM(ar_aging.client_name) LIKE ?', ['%' . trim($search) . '%'])
                      ->orWhereRaw('TRIM(ar_aging.dr_no) = ?', [trim($search)])
                      ->orWhereRaw('TRIM(ar_aging.dr_no) LIKE ?', ['%' . trim($search) . '%']);
            })
            ->first();

        // Check deliveries
        $deliveryResult = DB::table('deliveries')
            ->select('customer_code', 'customer_name', 'dr_no', 'status', 'is_pulled_out')
            ->where('status', 'Delivered')
            ->where('is_pulled_out', '!=', 1)
            ->where(function($query) use ($search) {
                $query->whereRaw('TRIM(deliveries.customer_code) = ?', [trim($search)])
                      ->orWhereRaw('TRIM(deliveries.customer_code) LIKE ?', ['%' . trim($search) . '%'])
                      ->orWhereRaw('TRIM(deliveries.customer_name) LIKE ?', ['%' . trim($search) . '%'])
                      ->orWhereRaw('TRIM(deliveries.dr_no) = ?', [trim($search)])
                      ->orWhereRaw('TRIM(deliveries.dr_no) LIKE ?', ['%' . trim($search) . '%']);
            })
            ->first();

        return response()->json([
            'search_term' => $search,
            'trimmed' => trim($search),
            'found_in_ar_aging' => $arAgingResult ? true : false,
            'ar_aging_result' => $arAgingResult,
            'found_in_deliveries' => $deliveryResult ? true : false,
            'deliveries_result' => $deliveryResult,
            'which_would_be_used' => $arAgingResult ? 'ar_aging' : ($deliveryResult ? 'deliveries' : 'neither')
        ]);
    }

    /**
     * ✅ DEBUG: Check why a delivery is not showing in search results
     */
    public function debugDeliverySearch($drNo)
    {
        $delivery = DB::table('deliveries')
            ->select('dr_no', 'customer_code', 'customer_name', 'status', 'is_pulled_out', 'created_at')
            ->where('dr_no', $drNo)
            ->first();

        if (!$delivery) {
            return response()->json([
                'found' => false,
                'message' => "DR {$drNo} not found in deliveries table"
            ]);
        }

        // Check why it might not be returned
        $reasons = [];
        if ($delivery->status !== 'Delivered') {
            $reasons[] = "Status is '{$delivery->status}', not 'Delivered'";
        }
        if ($delivery->is_pulled_out == 1) {
            $reasons[] = "is_pulled_out = 1 (excluded)";
        }
        if (!$delivery->customer_code) {
            $reasons[] = "No customer_code";
        }

        // Check if it's in ar_aging
        $inArAging = DB::table('ar_aging')->where('dr_no', $drNo)->first();

        return response()->json([
            'found' => true,
            'delivery' => $delivery,
            'in_ar_aging' => $inArAging ? true : false,
            'reasons_not_shown' => $reasons,
            'would_show' => $delivery->status === 'Delivered' && $delivery->is_pulled_out != 1,
            'debug_info' => "If status=Delivered, not pulled out, and customer_code exists, it should show in search"
        ]);
    }

    /**
     * Search for a customer in ar_aging table
     */
    public function searchCustomer(Request $request)
    {
        try {
            $search = $request->input('search');

            if (!$search) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please provide a search term'
                ], 400);
            }

            Log::info('Searching for customer', ['search_term' => $search]);

            $customerData = null;
            $fullyPaidDrInfo = null;

            // Check if the search term matches a locked delivery — return early with a clear message
            if (is_numeric($search)) {
                $lockedDelivery = DB::table('deliveries')
                    ->where('dr_no', trim($search))
                    ->where('is_locked', 1)
                    ->first();

                if ($lockedDelivery) {
                    return response()->json([
                        'success' => false,
                        'locked' => true,
                        'dr_no' => $lockedDelivery->dr_no,
                        'customer_name' => $lockedDelivery->customer_name,
                        'message' => 'This delivery (DR ' . $lockedDelivery->dr_no . ') is locked and cannot be collected.'
                    ], 200);
                }
            }

            // ✅ CRITICAL FIX: Always try exact DR match FIRST before any other search
            // ✅ FETCH AMOUNT FROM DELIVERY ITEMS (sum of item amounts), NOT SALES ORDER
            $exactDrMatch = DB::table('deliveries')
                ->leftJoin('customers', DB::raw("CAST(deliveries.customer_code AS CHAR) COLLATE utf8mb4_unicode_ci"), '=', DB::raw("CAST(customers.customer_code AS CHAR) COLLATE utf8mb4_unicode_ci"))
                ->leftJoin('delivery_items', 'deliveries.id', '=', 'delivery_items.delivery_id')
                ->select(
                    'deliveries.id',
                    'deliveries.customer_code',
                    'deliveries.customer_name as client_name',
                    'deliveries.request_delivery_date',
                    'deliveries.dr_no',
                    DB::raw('0 as total_outstanding'),
                    DB::raw('0 as gross_balance'),
                    DB::raw('0 as total_invoice'),
                    DB::raw('0 as total_settled'),
                    DB::raw("COALESCE(deliveries.branch, 'N/A') as branch"),
                    DB::raw("COALESCE(deliveries.sales_executive, 'N/A') as sales_executive"),
                    DB::raw("NULL as terms"),
                    DB::raw('0 as invoice_count'),
                    DB::raw('0 as outstanding_invoice_count'),
                    DB::raw("COALESCE(customers.whtrate, 0) as whtrate"),
                    DB::raw("SUM(COALESCE(delivery_items.total_amount, 0)) as delivery_amount")
                )
                ->where('deliveries.status', 'Delivered')
                ->where('deliveries.is_pulled_out', '!=', 1)
                ->where('deliveries.is_locked', '!=', 1)
                ->whereRaw('deliveries.dr_no = ?', [trim($search)])
                ->groupBy('deliveries.id', 'deliveries.customer_code', 'deliveries.customer_name', 'deliveries.request_delivery_date', 'deliveries.dr_no', 'deliveries.branch', 'deliveries.sales_executive', 'customers.whtrate')
                ->first();

            if ($exactDrMatch) {
                // Check if this DR is already fully paid before accepting the match
                $drDeliveryAmount = (float)($exactDrMatch->delivery_amount ?? 0);
                $drTotalPaid = (float)(DB::table('payments')
                    ->where(function($q) use ($search) {
                        $q->where('dr_no', trim($search))
                          ->orWhereRaw('TRIM(dr_no) = ?', [trim($search)]);
                    })
                    ->where(function($q) use ($exactDrMatch) {
                        $q->where('customer_code', $exactDrMatch->customer_code)
                          ->orWhereRaw('TRIM(customer_code) = ?', [trim($exactDrMatch->customer_code)]);
                    })
                    ->where(function($q) { $q->whereNull('status') ->orWhere(function($inner) { $inner->whereNotNull('status')->whereNotIn('status', ['Voided', 'Bounced']); });})
                    ->selectRaw('SUM(amount + COALESCE(discount_amount, 0) + COALESCE(ewt, 0)) as total')
                    ->value('total') ?? 0);

                if ($drDeliveryAmount > 0 && ($drDeliveryAmount - $drTotalPaid) < 2) {
                    Log::info('⚠️ Exact DR match found but already fully paid', [
                        'dr_no' => trim($search),
                        'delivery_amount' => $drDeliveryAmount,
                        'total_paid' => $drTotalPaid,
                    ]);
                    $fullyPaidDrInfo = [
                        'dr_no' => trim($search),
                        'customer_name' => $exactDrMatch->client_name,
                        'customer_code' => $exactDrMatch->customer_code,
                        'total_paid' => $drTotalPaid,
                    ];
                } else {
                    $customerData = $exactDrMatch;
                    Log::info('✅ Found exact DR match in deliveries', [
                        'dr_no' => trim($search),
                        'delivery_amount' => $exactDrMatch->delivery_amount ?? 'NULL',
                        'customer_name' => $exactDrMatch->client_name
                    ]);
                }
            } else {
                Log::warning('⚠️ Exact DR match NOT found in deliveries', [
                    'search_term' => trim($search),
                    'expected_status' => 'Delivered',
                    'excluded_if' => 'is_pulled_out = 1'
                ]);
            }

            // If no exact DR match found, search in ar_aging table
            if (!$customerData) {
                // Get locked DR numbers to exclude from ar_aging results
                $lockedDrNos = DB::table('deliveries')
                    ->where('is_locked', 1)
                    ->pluck('dr_no')
                    ->map(fn($d) => trim($d))
                    ->filter()
                    ->toArray();

                $customerData = DB::table('ar_aging')
                    ->leftJoin('customers', DB::raw("CAST(ar_aging.customer_code AS CHAR) COLLATE utf8mb4_unicode_ci"), '=', DB::raw("CAST(customers.customer_code AS CHAR) COLLATE utf8mb4_unicode_ci"))
                    ->select(
                        'ar_aging.customer_code',
                        'ar_aging.client_name',
                        DB::raw('SUM(CASE WHEN COALESCE(ar_aging.net_ar_balance, 0) > 0 THEN COALESCE(ar_aging.net_ar_balance, 0) ELSE 0 END) as total_outstanding'),
                        DB::raw('SUM(COALESCE(ar_aging.gross_ar_balance, 0)) as gross_balance'),
                        DB::raw('SUM(COALESCE(ar_aging.invoice_amount, 0)) as total_invoice'),
                        DB::raw('SUM(COALESCE(ar_aging.settled_invoice_amount, 0)) as total_settled'),
                        DB::raw('MAX(ar_aging.branch) as branch'),
                        DB::raw('COALESCE(MAX(ar_aging.sales_executive), MAX(ar_aging.se2)) as sales_executive'),
                        DB::raw('MAX(ar_aging.terms) as terms'),
                        DB::raw('COUNT(*) as invoice_count'),
                        DB::raw('COUNT(CASE WHEN COALESCE(ar_aging.net_ar_balance, 0) > 0 THEN 1 END) as outstanding_invoice_count'),
                        'customers.whtrate'
                    )
                    ->where(function($query) use ($search) {
                        $query->whereRaw('TRIM(ar_aging.customer_code) = ?', [trim($search)])
                              ->orWhereRaw('TRIM(ar_aging.customer_code) LIKE ?', ['%' . trim($search) . '%'])
                              ->orWhereRaw('TRIM(ar_aging.client_name) LIKE ?', ['%' . trim($search) . '%'])
                              ->orWhereRaw('TRIM(ar_aging.dr_no) = ?', [trim($search)])
                              ->orWhereRaw('TRIM(ar_aging.dr_no) LIKE ?', ['%' . trim($search) . '%']);
                    })
                    ->when(!empty($lockedDrNos), function($q) use ($lockedDrNos) {
                        $q->whereNotIn(DB::raw('TRIM(ar_aging.dr_no)'), $lockedDrNos);
                    })
                    ->groupBy('ar_aging.customer_code', 'ar_aging.client_name', 'customers.whtrate')
                    ->first();

                Log::info('Search result', ['found' => $customerData ? 'yes' : 'no']);
            }

            // ✅ If not found in either search, try deliveries table with broader criteria
            if (!$customerData) {
                $delivery = DB::table('deliveries')
                    ->leftJoin('customers', DB::raw("CAST(deliveries.customer_code AS CHAR) COLLATE utf8mb4_unicode_ci"), '=', DB::raw("CAST(customers.customer_code AS CHAR) COLLATE utf8mb4_unicode_ci"))
                    ->leftJoin('delivery_items', 'deliveries.id', '=', 'delivery_items.delivery_id')
                    ->select(
                        'deliveries.id',
                        'deliveries.customer_code',
                        'deliveries.customer_name as client_name',
                        'deliveries.request_delivery_date',
                        'deliveries.dr_no',
                        DB::raw('0 as total_outstanding'),
                        DB::raw('0 as gross_balance'),
                        DB::raw('0 as total_invoice'),
                        DB::raw('0 as total_settled'),
                        DB::raw("COALESCE(deliveries.branch, 'N/A') as branch"),
                        DB::raw("COALESCE(deliveries.sales_executive, 'N/A') as sales_executive"),
                        DB::raw("NULL as terms"),
                        DB::raw('0 as invoice_count'),
                        DB::raw('0 as outstanding_invoice_count'),
                        DB::raw("COALESCE(customers.whtrate, 0) as whtrate"),
                        DB::raw("SUM(COALESCE(delivery_items.total_amount, 0)) as delivery_amount")
                    )
                    ->where('deliveries.status', 'Delivered')
                    ->where('deliveries.is_pulled_out', '!=', 1)
                    ->where('deliveries.is_locked', '!=', 1)
                    ->where(function($query) use ($search) {
                        $query->whereRaw('TRIM(deliveries.customer_code) = ?', [trim($search)])
                              ->orWhereRaw('TRIM(deliveries.customer_code) LIKE ?', ['%' . trim($search) . '%'])
                              ->orWhereRaw('TRIM(deliveries.customer_name) LIKE ?', ['%' . trim($search) . '%'])
                              ->orWhereRaw('TRIM(deliveries.dr_no) = ?', [trim($search)])
                              ->orWhereRaw('TRIM(deliveries.dr_no) LIKE ?', ['%' . trim($search) . '%']);
                    })
                    ->groupBy('deliveries.id', 'deliveries.customer_code', 'deliveries.customer_name', 'deliveries.request_delivery_date', 'deliveries.dr_no', 'deliveries.branch', 'deliveries.sales_executive', 'customers.whtrate')
                    ->first();

                if ($delivery) {
                    // Check if this DR is already fully paid
                    $broadDeliveryAmount = (float)($delivery->delivery_amount ?? 0);
                    $broadTotalPaid = 0;
                    if ($broadDeliveryAmount > 0 && !empty($delivery->dr_no)) {
                        $broadTotalPaid = (float)(DB::table('payments')
                            ->where(function($q) use ($delivery) {
                                $q->where('dr_no', $delivery->dr_no)
                                  ->orWhereRaw('TRIM(dr_no) = ?', [trim($delivery->dr_no)]);
                            })
                            ->where(function($q) use ($delivery) {
                                $q->where('customer_code', $delivery->customer_code)
                                  ->orWhereRaw('TRIM(customer_code) = ?', [trim($delivery->customer_code)]);
                            })
                            ->where(function($q) { $q->whereNull('status') ->orWhere(function($inner) { $inner->whereNotNull('status')->whereNotIn('status', ['Voided', 'Bounced']); });})
                            ->selectRaw('SUM(amount + COALESCE(discount_amount, 0) + COALESCE(ewt, 0)) as total')
                            ->value('total') ?? 0);
                    }

                    if ($broadDeliveryAmount > 0 && ($broadDeliveryAmount - $broadTotalPaid) < 2) {
                        Log::info('⚠️ Broad delivery match found but already fully paid', [
                            'dr_no' => $delivery->dr_no,
                            'delivery_amount' => $broadDeliveryAmount,
                            'total_paid' => $broadTotalPaid,
                        ]);
                        $fullyPaidDrInfo = $fullyPaidDrInfo ?? [
                            'dr_no' => $delivery->dr_no,
                            'customer_name' => $delivery->client_name,
                            'customer_code' => $delivery->customer_code,
                            'total_paid' => $broadTotalPaid,
                        ];
                    } else {
                        $customerData = $delivery;
                        Log::info('Customer found in deliveries table (pending invoicing)', [
                            'customer_code' => $customerData->customer_code,
                            'delivery_date' => $customerData->request_delivery_date ?? 'N/A',
                            'search_term' => $search
                        ]);
                    }
                } else {
                    if ($fullyPaidDrInfo) {
                        return response()->json([
                            'success' => false,
                            'fully_paid' => true,
                            'dr_no' => $fullyPaidDrInfo['dr_no'],
                            'customer_name' => $fullyPaidDrInfo['customer_name'],
                            'total_paid' => $fullyPaidDrInfo['total_paid'],
                            'message' => 'This DR has already been fully paid.'
                        ], 200);
                    }

                    Log::warning('Customer not found in ar_aging or deliveries', [
                        'search_term' => $search,
                        'trimmed' => trim($search)
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Customer or delivery not found'
                    ], 404);
                }
            }

            // If no customer data found after all searches, check if we found a fully-paid DR
            if (!$customerData && $fullyPaidDrInfo) {
                return response()->json([
                    'success' => false,
                    'fully_paid' => true,
                    'dr_no' => $fullyPaidDrInfo['dr_no'],
                    'customer_name' => $fullyPaidDrInfo['customer_name'],
                    'total_paid' => $fullyPaidDrInfo['total_paid'],
                    'message' => 'This DR has already been fully paid.'
                ], 200);
            }

            if (!$customerData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer or delivery not found'
                ], 404);
            }

            // ✅ If a delivery was found (pending invoicing), create a synthetic invoice entry for it
            $outstandingInvoices = collect();
            if (isset($customerData->dr_no) && $customerData->total_outstanding == 0) {
                $drNoFromDelivery = trim($customerData->dr_no);
                $drNoFromSearch = trim($search);

                $arAgingForDr = DB::table('ar_aging')
                    ->where(function($q) use ($drNoFromDelivery, $drNoFromSearch) {
                        $q->whereRaw('TRIM(dr_no) = ?', [$drNoFromDelivery]);
                        if ($drNoFromSearch !== $drNoFromDelivery) {
                            $q->orWhereRaw('TRIM(dr_no) = ?', [$drNoFromSearch]);
                        }
                        if (is_numeric($drNoFromSearch)) {
                            $q->orWhereRaw('CAST(TRIM(dr_no) AS UNSIGNED) = ?', [(int)$drNoFromSearch]);
                        }
                    })
                    ->select('dr_no', 'invoice_no', 'invoice_date', 'invoice_amount', 'settled_invoice_amount',
                             'net_ar_balance', 'gross_ar_balance', 'net_of_cwt', 'cwt',
                             'check_amount', 'others_amount', 'ar_adjustments', 'terms', 'age', 'due_date', 'status')
                    ->get();

                Log::info('AR Aging DR lookup', [
                    'dr_from_delivery' => $drNoFromDelivery,
                    'dr_from_search' => $drNoFromSearch,
                    'records_found' => $arAgingForDr->count(),
                    'net_ar_values' => $arAgingForDr->pluck('net_ar_balance')->toArray(),
                    'invoice_amounts' => $arAgingForDr->pluck('invoice_amount')->toArray(),
                ]);

                if ($arAgingForDr->isNotEmpty()) {
                    $arAgingForDr = $arAgingForDr->map(function($record) {
                        // ✅ Skip recalculation for records already marked as Paid
                        if (strtolower(trim($record->status ?? '')) === 'paid') {
                            $record->net_ar_balance = 0;
                            return $record;
                        }

                        $netAr = (float)($record->net_ar_balance ?? 0);
                        if ($netAr <= 0) {
                            $netAr = max(0,
                                (float)($record->invoice_amount ?? 0)
                                - (float)($record->settled_invoice_amount ?? 0)
                            );
                        }
                        if ($netAr <= 0) {
                            $netOfCwt  = (float)($record->net_of_cwt ?? 0);
                            $checkAmt  = (float)($record->check_amount ?? 0);
                            $othersAmt = (float)($record->others_amount ?? 0);
                            $adjAmt    = (float)($record->ar_adjustments ?? 0);
                            if ($netOfCwt > 0) {
                                $computed = $netOfCwt - $adjAmt - $checkAmt - $othersAmt;
                                if ($computed < 0) {
                                    $computed = $netOfCwt - $adjAmt + $othersAmt - $checkAmt;
                                }
                                $netAr = max(0, $computed);
                            }
                        }
                        $record->net_ar_balance = $netAr;
                        return $record;
                    });

                    // ✅ Filter out fully paid records (net_ar_balance = 0 after recalculation)
                    $outstandingInvoices = $arAgingForDr->filter(fn($r) => $r->net_ar_balance >= 2)->values();
                } else {
                    $deliveryAmount = isset($customerData->delivery_amount) ? floatval($customerData->delivery_amount) : 0;
                    $outstandingInvoices->push((object)[
                        'dr_no' => $customerData->dr_no,
                        'invoice_no' => 'PENDING',
                        'invoice_date' => $customerData->request_delivery_date,
                        'invoice_amount' => $deliveryAmount,
                        'settled_invoice_amount' => 0,
                        'net_ar_balance' => $deliveryAmount,
                        'gross_ar_balance' => $deliveryAmount,
                        'terms' => 'TBD',
                        'age' => 0,
                        'due_date' => null,
                        'status' => 'Pending Invoice'
                    ]);
                }
            } else {
                // Get locked DR numbers to exclude from outstanding list
                $lockedDrNos = DB::table('deliveries')
                    ->where('is_locked', 1)
                    ->where('customer_code', $customerData->customer_code)
                    ->pluck('dr_no')
                    ->map(fn($d) => trim($d))
                    ->filter()
                    ->toArray();

                $arQuery = DB::table('ar_aging')
                    ->select(
                        'dr_no', 'invoice_no', 'invoice_date', 'invoice_amount',
                        'settled_invoice_amount', 'net_ar_balance', 'gross_ar_balance',
                        'net_of_cwt', 'check_amount', 'others_amount', 'ar_adjustments',
                        'terms', 'age', 'due_date', 'status'
                    )
                    ->where('customer_code', $customerData->customer_code);

                if (isset($customerData->dr_no)) {
                    $arQuery->where('dr_no', $customerData->dr_no);
                }

                // Pre-fetch actual payments per DR so we can compute true gross remaining
                $arInvoices = $arQuery->orderBy('invoice_date', 'desc')->get();
                $arDrList = $arInvoices->pluck('dr_no')->map(fn($d) => trim($d))->filter()->values()->toArray();
                $paymentsPerDrMap = collect();
                if (!empty($arDrList)) {
                    $paymentsPerDrMap = DB::table('payments')
                        ->where('customer_code', $customerData->customer_code)
                        ->where(function($q) { $q->whereNull('status') ->orWhere(function($inner) { $inner->whereNotNull('status')->whereNotIn('status', ['Voided', 'Bounced']); });})
                        ->selectRaw('TRIM(dr_no) as dr_no, SUM(amount + COALESCE(discount_amount,0) + COALESCE(ewt,0)) as total_paid')
                        ->groupByRaw('TRIM(dr_no)')
                        ->get()->keyBy('dr_no');
                }

                $outstandingInvoices = $arInvoices
                    ->map(function($record) use ($lockedDrNos, $paymentsPerDrMap) {
                        $drKey = trim($record->dr_no ?? '');

                        // Compute true gross remaining = invoice_amount − settled (pre-system) − actual db payments
                        $invoiceAmt   = (float)($record->invoice_amount ?? 0);
                        $settled      = (float)($record->settled_invoice_amount ?? 0);
                        $actualPaid   = (float)($paymentsPerDrMap[$drKey]->total_paid ?? 0);
                        $grossRemaining = max(0, $invoiceAmt - $settled - $actualPaid);

                        // Fallback net_ar_balance (existing logic)
                        $netAr = (float)($record->net_ar_balance ?? 0);
                        if ($netAr <= 0) {
                            $netAr = max(0, $invoiceAmt - $settled);
                        }
                        if ($netAr <= 0) {
                            $netOfCwt  = (float)($record->net_of_cwt ?? 0);
                            $checkAmt  = (float)($record->check_amount ?? 0);
                            $othersAmt = (float)($record->others_amount ?? 0);
                            $adjAmt    = (float)($record->ar_adjustments ?? 0);
                            if ($netOfCwt > 0) {
                                $computed = $netOfCwt - $adjAmt - $checkAmt - $othersAmt;
                                if ($computed < 0) {
                                    $computed = $netOfCwt - $adjAmt + $othersAmt - $checkAmt;
                                }
                                $netAr = max(0, $computed);
                            }
                        }
                        $record->net_ar_balance = $netAr;
                        // gross_remaining is the definitive pre-EWT outstanding to use for payment entry
                        $record->gross_remaining = $grossRemaining > 0 ? $grossRemaining : $netAr;
                        $record->is_locked = in_array($drKey, $lockedDrNos);
                        return $record;
                    })
                    ->filter(fn($r) => $r->net_ar_balance >= 2 && !($r->is_locked ?? false))
                    ->values();

                // Also include outstanding deliveries NOT in ar_aging (e.g. pending invoicing)
                $arAgingDrNos = DB::table('ar_aging')
                    ->where('customer_code', $customerData->customer_code)
                    ->pluck('dr_no')
                    ->map(fn($d) => trim($d))
                    ->filter()
                    ->toArray();

                $deliveriesNotInAging = DB::table('deliveries')
                    ->leftJoin('delivery_items', 'deliveries.id', '=', 'delivery_items.delivery_id')
                    ->where('deliveries.customer_code', $customerData->customer_code)
                    ->where('deliveries.status', 'Delivered')
                    ->where('deliveries.is_pulled_out', '!=', 1)
                    ->where('deliveries.is_locked', '!=', 1)
                    ->when(!empty($arAgingDrNos), function($q) use ($arAgingDrNos) {
                        $q->whereNotIn(DB::raw('TRIM(deliveries.dr_no)'), $arAgingDrNos);
                    })
                    ->select(
                        'deliveries.dr_no',
                        'deliveries.is_locked',
                        'deliveries.request_delivery_date as invoice_date',
                        DB::raw('SUM(COALESCE(delivery_items.total_amount, 0)) as invoice_amount')
                    )
                    ->groupBy('deliveries.dr_no', 'deliveries.is_locked', 'deliveries.request_delivery_date')
                    ->having('invoice_amount', '>', 0)
                    ->get();

                foreach ($deliveriesNotInAging as $del) {
                    $outstandingInvoices->push((object)[
                        'dr_no' => $del->dr_no,
                        'invoice_no' => 'PENDING',
                        'invoice_date' => $del->invoice_date,
                        'invoice_amount' => (float)$del->invoice_amount,
                        'settled_invoice_amount' => 0,
                        'net_ar_balance' => (float)$del->invoice_amount,
                        'gross_ar_balance' => (float)$del->invoice_amount,
                        'terms' => 'TBD',
                        'age' => 0,
                        'due_date' => null,
                        'status' => 'Pending Invoice',
                        'is_locked' => (bool)$del->is_locked,
                    ]);
                }

                Log::info('Deliveries not in ar_aging added to collection', [
                    'customer_code' => $customerData->customer_code,
                    'count' => $deliveriesNotInAging->count(),
                    'dr_nos' => $deliveriesNotInAging->pluck('dr_no')->toArray(),
                ]);
            }

            // Subtract existing payments from outstanding balances
            $existingPayments = DB::table('payments')
                ->where(function($q) use ($customerData) {
                    $q->where('customer_code', $customerData->customer_code);
                    if (!empty($customerData->client_name)) {
                        $q->orWhereRaw('TRIM(LOWER(customer_name)) = ?', [trim(strtolower($customerData->client_name))]);
                    }
                })
                ->where(function($q) { $q->whereNull('status') ->orWhere(function($inner) { $inner->whereNotNull('status')->whereNotIn('status', ['Voided', 'Bounced']); });})
                ->select('dr_no', 'invoice_no', DB::raw('SUM(amount + COALESCE(discount_amount, 0) + COALESCE(ewt, 0)) as total_paid'))
                ->groupBy('dr_no', 'invoice_no')
                ->get()
                ->keyBy(function($p) { return trim($p->dr_no ?? '') . '|' . trim($p->invoice_no ?? ''); });

            // Get AR adjustments per DR for this customer
            // Use is_decrease flag as authoritative: decrease = subtract from AR, increase = add to AR
            $arAdjustments = DB::table('ar_adjustments')
                ->where(function($q) use ($customerData) {
                    $q->whereRaw('TRIM(customer_code) = ?', [trim($customerData->customer_code)]);
                })
                ->whereNotNull('dr_no')
                ->where('dr_no', '!=', '')
                ->select('dr_no', DB::raw('SUM(CASE WHEN is_decrease = 1 THEN -ABS(amount) ELSE ABS(amount) END) as net_adjustment'))
                ->groupBy('dr_no')
                ->get()
                ->keyBy(function($a) { return trim($a->dr_no); });

            $outstandingInvoices = $outstandingInvoices->map(function($record) use ($existingPayments, $arAdjustments) {
                $drKey  = trim($record->dr_no ?? '');
                $invKey = trim($record->invoice_no ?? '');
                $key    = $drKey . '|' . $invKey;

                // Primary: match by dr_no + invoice_no
                if ($existingPayments->has($key)) {
                    $paid = (float)$existingPayments[$key]->total_paid;
                } else {
                    // Fallback: match by dr_no only (handles PENDING invoice_no mismatch)
                    $paid = (float)$existingPayments
                        ->filter(fn($p) => trim($p->dr_no ?? '') === $drKey)
                        ->sum('total_paid');
                }

                if ($paid > 0) {
                    $record->net_ar_balance = max(0, $record->net_ar_balance - $paid);
                    $record->total_paid = $paid;
                    $record->status = $record->net_ar_balance <= 0 ? 'Paid' : 'Partial';
                }

                // Apply AR adjustments (negative amount = decrease AR, positive = increase AR)
                if ($arAdjustments->has($drKey)) {
                    $adjustment = (float)$arAdjustments[$drKey]->net_adjustment;
                    $record->net_ar_balance = max(0, $record->net_ar_balance + $adjustment);
                    $record->ar_adjustment_applied = $adjustment;
                }

                return $record;
            })->filter(fn($r) => $r->net_ar_balance >= 2)->values();

            Log::info('Outstanding invoices loaded', [
                'count' => $outstandingInvoices->count(),
                'sample_dr_numbers' => $outstandingInvoices->take(3)->pluck('dr_no')->toArray()
            ]);

            $actualOutstandingBalance = $outstandingInvoices->sum('net_ar_balance');

            // If searching by DR number and all invoices are fully paid, show fully paid message
            if ($outstandingInvoices->isEmpty() && $fullyPaidDrInfo) {
                return response()->json([
                    'success' => false,
                    'fully_paid' => true,
                    'dr_no' => $fullyPaidDrInfo['dr_no'],
                    'customer_name' => $fullyPaidDrInfo['customer_name'],
                    'total_paid' => $fullyPaidDrInfo['total_paid'],
                    'message' => 'This DR has already been fully paid.'
                ], 200);
            }

            // Get billing address from customers table for display
            $customerInfo = DB::table('customers')
                ->where('customer_code', $customerData->customer_code)
                ->select('billing_address', 'shipping_address', 'branch')
                ->first();

            return response()->json([
                'success' => true,
                'customer' => [
                    'code' => $customerData->customer_code,
                    'name' => $customerData->client_name,
                    'outstanding_balance' => number_format($actualOutstandingBalance, 2, '.', ''),
                    'gross_balance' => number_format($customerData->gross_balance, 2, '.', ''),
                    'total_invoice' => number_format($customerData->total_invoice, 2, '.', ''),
                    'total_settled' => number_format($customerData->total_settled, 2, '.', ''),
                    'branch' => $customerInfo->branch ?? $customerData->branch ?? 'N/A',
                    'billing_address' => $customerInfo->billing_address ?? null,
                    'sales_executive' => $customerData->sales_executive ?? 'N/A',
                    'terms' => $customerData->terms ?? 'N/A',
                    'invoice_count' => $customerData->invoice_count,
                    'outstanding_invoice_count' => $customerData->outstanding_invoice_count,
                    'whtrate' => $customerData->whtrate ?? 0,
                ],
                'outstanding_invoices' => $outstandingInvoices
            ]);

        } catch (\Exception $e) {
            Log::error('Customer search failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('Payment store request received', [
                'data' => $request->all()
            ]);

            $validated = $request->validate([
                'customer_code'                        => 'required|string|max:255',
                'customer_name'                        => 'required|string|max:255',
                'dr_number'                            => 'required|string|max:255',
                'invoice_no'                           => 'nullable|string|max:255',
                'collection_receipt_number'            => 'required|string|max:255',
                'collection_receipt_date'              => 'required|date|before_or_equal:9999-12-31',
                'payment_posting_date'                 => 'required|date|before_or_equal:9999-12-31',
                'amount'                               => 'required|numeric|min:0.01',
                'tax'                                  => 'nullable|numeric|min:0',
                'net'                                  => 'nullable|numeric|min:0',
                'payment_means'                        => 'required|array',
                'payment_means.type'                   => 'required|in:check,bank_transfer,cash',
                'payment_means.gl_account'             => 'nullable|string',
                'payment_means.gl_account_id'          => 'nullable|integer',
                'payment_means.gl_account_name'        => 'nullable|string',
                'payment_means.due_date'               => 'nullable|date|before_or_equal:9999-12-31',
                'payment_means.amount'                 => 'nullable|numeric',
                'payment_means.bank_name'              => 'nullable|string',
                'payment_means.check_number'           => 'nullable|string',
                'payment_means.transfer_date'          => 'nullable|date|before_or_equal:9999-12-31',
                'payment_means.deposit_date'           => 'nullable|date|before_or_equal:9999-12-31',
                'payment_means.reference'              => 'nullable|string',
                'payment_notes'                        => 'nullable|string|max:1000',
                'invoice_outstanding'                  => 'nullable|numeric|min:0',
                'credit_applied'                       => 'nullable|numeric|min:0',
                'credit_from_payment_id'               => 'nullable|integer',
                'credits'                              => 'nullable|array',
                'credits.*.credit_source_payment_id'   => 'required_with:credits|integer',
                'credits.*.amount'                     => 'required_with:credits|numeric|min:0.01',
                'discount_rate'                        => 'nullable|numeric|min:0|max:100',
                'discount_amount'                      => 'nullable|numeric|min:0',
                'is_short_payment'                     => 'nullable|boolean',
            ]);

            Log::info('Validation passed', ['validated_data' => $validated]);

            // ─── Check for duplicate CR + DR combination ─────────────────────
            $existingPayment = DB::table('payments')
                ->where('collection_receipt_number', $validated['collection_receipt_number'])
                ->where(function($q) use ($validated) {
                    $q->where('dr_no', $validated['dr_number'])
                      ->orWhereRaw('TRIM(dr_no) = ?', [trim($validated['dr_number'])]);
                })
                ->first();

            if ($existingPayment) {
                return response()->json([
                    'success' => false,
                    'duplicate' => true,
                    'message' => 'IT HAS BEEN PAID'
                ], 422);
            }

            // ─── Check if DR is already fully paid ───────────────────────────
            $drNo = $validated['dr_number'];
            $custCode = $validated['customer_code'];

            // Get the original outstanding amount for this DR
            $drOutstanding = 0;

            // Check ar_aging first
            $arRecord = DB::table('ar_aging')
                ->where(function($q) use ($custCode) {
                    $q->where('customer_code', $custCode)
                      ->orWhereRaw('TRIM(customer_code) = ?', [trim($custCode)]);
                })
                ->where(function($q) use ($drNo) {
                    $q->where('dr_no', $drNo)
                      ->orWhereRaw('TRIM(dr_no) = ?', [trim($drNo)]);
                })
                ->first();

            if ($arRecord) {
                $drOutstanding = (float)($arRecord->net_ar_balance ?? 0);
                if ($drOutstanding <= 0) {
                    $drOutstanding = max(0, (float)($arRecord->invoice_amount ?? 0) - (float)($arRecord->settled_invoice_amount ?? 0));
                }
            }

            // Fallback to delivery items total
            if ($drOutstanding <= 0) {
                $drOutstanding = (float)DB::table('delivery_items')
                    ->join('deliveries', 'deliveries.id', '=', 'delivery_items.delivery_id')
                    ->where(function($q) use ($drNo) {
                        $q->where('deliveries.dr_no', $drNo)
                          ->orWhereRaw('TRIM(deliveries.dr_no) = ?', [trim($drNo)]);
                    })
                    ->sum('delivery_items.total_amount');
            }

            if ($drOutstanding > 0) {
                $alreadyPaid = (float)(DB::table('payments')
                    ->where(function($q) use ($custCode) {
                        $q->where('customer_code', $custCode)
                          ->orWhereRaw('TRIM(customer_code) = ?', [trim($custCode)]);
                    })
                    ->where(function($q) use ($drNo) {
                        $q->where('dr_no', $drNo)
                          ->orWhereRaw('TRIM(dr_no) = ?', [trim($drNo)]);
                    })
                    ->where(function($q) { $q->whereNull('status') ->orWhere(function($inner) { $inner->whereNotNull('status')->whereNotIn('status', ['Voided', 'Bounced']); });})
                    ->selectRaw('SUM(amount + COALESCE(discount_amount, 0) + COALESCE(ewt, 0)) as total')
                    ->value('total') ?? 0);

                $remainingBalance = max(0, $drOutstanding - $alreadyPaid);

                if ($remainingBalance < 2) {
                    return response()->json([
                        'success' => false,
                        'message' => "DR \"{$drNo}\" is already fully paid. Total paid: ₱" . number_format($alreadyPaid, 2)
                    ], 422);
                }
            }

            // ─── Amount fields ────────────────────────────────────────────────
            // Frontend sends:
            //   amount  = the payment means (check/transfer/cash) amount  ← what the customer physically hands over
            //   tax     = EWT amount (already computed: afterDiscount * ewtRate / 100)
            //   net     = amount after discount AND EWT  (outstanding - discount - EWT)
            //   discount_rate / discount_amount = discount breakdown
            //
            // Overpayment = meansAmount - netCollectible
            //   where netCollectible = outstanding - discountAmount - ewtAmount
            //   This is already in `validated['net']` sent from the frontend.
            // ─────────────────────────────────────────────────────────────────

            $meansAmount    = (float)$validated['amount'];                    // what was physically paid (check/cash/transfer)
            $discountRate   = (float)($validated['discount_rate']   ?? 0);
            $discountAmount = (float)($validated['discount_amount'] ?? 0);
            $ewtAmount      = (float)($validated['tax']             ?? 0);
            $netAmount      = (float)($validated['net'] ?? ($meansAmount - $ewtAmount));

            // gross_amount = the outstanding balance of the DR (what was owed)
            $invoiceOutstanding = isset($validated['invoice_outstanding'])
                ? (float)$validated['invoice_outstanding']
                : null;
            $grossAmount = $invoiceOutstanding ?? $meansAmount;

            // Credits
            $creditsArray  = $validated['credits'] ?? [];
            $creditApplied = 0;
            $creditFromId  = null;
            if (!empty($creditsArray)) {
                $creditApplied = array_sum(array_column($creditsArray, 'amount'));
                $creditFromId  = $creditsArray[0]['credit_source_payment_id'] ?? null;
            } else {
                $creditApplied = (float)($validated['credit_applied'] ?? 0);
                $creditFromId  = $validated['credit_from_payment_id'] ?? null;
            }

            // Overpayment = means amount - outstanding balance (excess over what was owed)
            // ₱10 leeway — small differences are not counted as overpayment
            $overpayment = 0;
            if ($meansAmount > $grossAmount && $grossAmount > 0) {
                $diff = round($meansAmount - $grossAmount, 2);
                $overpayment = $diff > 10 ? $diff : 0;
            }

            $paymentId = DB::table('payments')->insertGetId([
                'customer_code'              => $validated['customer_code'],
                'customer_name'              => $validated['customer_name'],
                'dr_no'                      => $validated['dr_number'],
                'invoice_no'                 => $validated['invoice_no'],
                'collection_receipt_number'  => $validated['collection_receipt_number'],
                'collection_receipt_date'    => $validated['collection_receipt_date'],
                'deposit_date'               => $validated['collection_receipt_date'],
                'payment_posting_date'       => $validated['payment_posting_date'],
                'payment_date'               => $validated['payment_posting_date'],
                'amount'                     => $meansAmount,       // what was physically paid
                'tax'                        => $ewtAmount,
                'net'                        => $netAmount,         // outstanding - EWT
                // Display columns
                'gross_amount'               => $grossAmount,       // outstanding balance of DR
                'ewt'                        => $ewtAmount,
                'discount_rate'              => $discountRate,
                'discount_amount'            => $discountAmount,
                'check_amount'               => $netAmount,          // net collectible (outstanding - EWT)
                'other_adjustment'           => 0,
                'factoring'                  => 0,
                'status'                     => 'Clearing',
                'signed_by'                  => auth()->user()->name ?? 'System',
                // Credit balance tracking
                'invoice_outstanding'        => $invoiceOutstanding,
                // ✅ overpayment is now meansAmount - netCollectible (discount-aware)
                'overpayment'                => $overpayment,
                'credit_applied'             => $creditApplied,
                'credit_from_payment_id'     => $creditFromId,
                // Payment means
                'payment_method'             => $validated['payment_means']['type'],
                'payment_means_data'         => json_encode($validated['payment_means']),
                'payment_notes'              => $validated['payment_notes'] ?? null,
                'is_short_payment'           => !empty($validated['is_short_payment']),
                'created_by'                 => auth()->user()->name ?? 'System',
                'bank'                       => $validated['payment_means']['bank_name'] ?? null,
                'reference_no'               => $validated['payment_means']['reference'] ?? $validated['payment_means']['check_number'] ?? null,
                'created_at'                 => now(),
            ]);

            // Insert individual credit application records
            if (!empty($creditsArray)) {
                foreach ($creditsArray as $credit) {
                    DB::table('payment_credit_applications')->insert([
                        'payment_id'               => $paymentId,
                        'credit_source_payment_id' => $credit['credit_source_payment_id'],
                        'amount'                   => $credit['amount'],
                        'created_at'               => now(),
                        'updated_at'               => now(),
                    ]);
                }
            } elseif ($creditFromId && $creditApplied > 0) {
                DB::table('payment_credit_applications')->insert([
                    'payment_id'               => $paymentId,
                    'credit_source_payment_id' => $creditFromId,
                    'amount'                   => $creditApplied,
                    'created_at'               => now(),
                    'updated_at'               => now(),
                ]);
            }

            Log::info('Payment inserted successfully', [
                'payment_id'      => $paymentId,
                'customer_code'   => $validated['customer_code'],
                'dr_number'       => $validated['dr_number'],
                'means_amount'    => $meansAmount,
                'net_collectible' => $netAmount,
                'overpayment'     => $overpayment,
                'credits_applied' => count($creditsArray),
            ]);

            // ✅ UPDATE AR AGING BALANCES BY DR NUMBER
            // Use the actual amount applied to the invoice:
            //   means amount + discount (withheld) + EWT (withheld) = total AR reduction
            // Do NOT use invoice_outstanding here — for partial payments that would
            // incorrectly mark the full invoice as paid.
            $arUpdateAmount = $meansAmount + $discountAmount + $ewtAmount;
            $this->updateArAgingBalanceByDR(
                $validated['customer_code'],
                $validated['dr_number'],
                $arUpdateAmount
            );

            $payment = DB::table('payments')->where('id', $paymentId)->first();

            // Create activity log
            if (class_exists('\App\Models\Activity')) {
                try {
                    DB::table('activities')->insert([
                        'user_name'  => auth()->user()->name ?? 'System',
                        'action'     => 'Created',
                        'item'       => 'Payment: ' . $validated['collection_receipt_number'],
                        'target'     => $validated['customer_name'] . ' - DR: ' . $validated['dr_number'],
                        'type'       => 'Payment',
                        'message'    => "Created payment entry: {$validated['collection_receipt_number']} for DR: {$validated['dr_number']} - Amount: ₱" . number_format($validated['amount'], 2),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Activity log creation failed', ['error' => $e->getMessage()]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment entry saved successfully!',
                'payment' => [
                    'id'                        => $payment->id,
                    'customer_code'             => $payment->customer_code,
                    'customer_name'             => $payment->customer_name,
                    'dr_no'                     => $payment->dr_no,
                    'invoice_no'                => $payment->invoice_no,
                    'collection_receipt_number' => $payment->collection_receipt_number,
                    'collection_receipt_date'   => $payment->collection_receipt_date,
                    'payment_posting_date'      => $payment->payment_posting_date,
                    'amount'                    => $payment->amount,
                    'tax'                       => $payment->tax,
                    'net'                       => $payment->net,
                    'overpayment'               => $payment->overpayment,
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Payment store failed', [
                'error'        => $e->getMessage(),
                'trace'        => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to save payment: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ Update AR Aging by specific DR number
    private function updateArAgingBalanceByDR($customerCode, $drNumber, $paymentAmount)
    {
        try {
            Log::info('Updating AR Aging by DR', [
                'customer_code'  => $customerCode,
                'dr_number'      => $drNumber,
                'payment_amount' => $paymentAmount
            ]);

            $invoice = DB::table('ar_aging')
                ->where(function($q) use ($customerCode) {
                    $q->where('customer_code', $customerCode)
                      ->orWhereRaw('TRIM(customer_code) = ?', [trim($customerCode)]);
                })
                ->where(function($q) use ($drNumber) {
                    $q->where('dr_no', $drNumber)
                      ->orWhereRaw('TRIM(dr_no) = ?', [trim($drNumber)]);
                })
                ->where(function($q) {
                    $q->whereNull('status')
                      ->orWhere('status', '')
                      ->orWhereNotIn('status', ['Paid']);
                })
                ->first();

            if (!$invoice) {
                $invoice = DB::table('ar_aging')
                    ->where(function($q) use ($customerCode) {
                        $q->where('customer_code', $customerCode)
                          ->orWhereRaw('TRIM(customer_code) = ?', [trim($customerCode)]);
                    })
                    ->where(function($q) use ($drNumber) {
                        $q->where('dr_no', $drNumber)
                          ->orWhereRaw('TRIM(dr_no) = ?', [trim($drNumber)]);
                    })
                    ->first();
            }

            if (!$invoice) {
                // ── Auto-create ar_aging row from delivery data ───────────────
                $delivery = DB::table('deliveries')
                    ->whereRaw('TRIM(dr_no) = ?', [trim($drNumber)])
                    ->where('status', 'Delivered')
                    ->first();

                if (!$delivery || (float)($delivery->total_amount ?? 0) <= 0) {
                    Log::warning('Invoice not found in ar_aging and no delivery to create from', [
                        'customer_code' => $customerCode,
                        'dr_number'     => $drNumber
                    ]);
                    return false;
                }

                $invoiceAmount = (float)$delivery->total_amount;

                DB::table('ar_aging')->insert([
                    'dr_no'          => trim($drNumber),
                    'invoice_no'     => $delivery->sales_invoice_no ?? null,
                    'po_no'          => $delivery->po_number ?? null,
                    'customer_code'  => $delivery->customer_code ?? $customerCode,
                    'client_name'    => $delivery->customer_name ?? null,
                    'branch'         => $delivery->branch ?? null,
                    'sales_executive'=> $delivery->sales_executive ?? null,
                    'invoice_amount' => $invoiceAmount,
                    'net_ar_balance' => $invoiceAmount,
                    'invoice_date'   => $delivery->request_delivery_date ?? now()->toDateString(),
                    'record_date'    => now()->toDateString(),
                    'aging_date'     => now()->toDateString(),
                    'status'         => '',
                    'include_flag'   => 1,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);

                $invoice = DB::table('ar_aging')
                    ->whereRaw('TRIM(dr_no) = ?', [trim($drNumber)])
                    ->orderBy('id', 'desc')
                    ->first();

                Log::info('Auto-created ar_aging row from delivery', [
                    'dr_number'      => $drNumber,
                    'invoice_amount' => $invoiceAmount,
                    'customer_code'  => $customerCode,
                ]);
            }

            // Use the ORIGINAL net_ar_balance from the import as the baseline.
            // Do NOT reduce net_ar_balance for partial payments — that would cause
            // double-deduction since searchCustomer/getCustomerHistory already
            // subtract payments dynamically from this same field.
            $originalBalance = (float)($invoice->net_ar_balance ?? 0);
            if ($originalBalance <= 0) {
                $originalBalance = max(0,
                    (float)($invoice->invoice_amount ?? 0)
                    - (float)($invoice->cwt ?? 0)
                    - (float)($invoice->settled_invoice_amount ?? 0)
                    + (float)($invoice->ewt ?? 0)
                );
            }

            if ($originalBalance <= 0) {
                Log::info('Invoice already fully settled', ['invoice_id' => $invoice->id]);
                return false;
            }

            // Compute total payments applied to this DR (including the current one just inserted)
            $totalPaid = (float)DB::table('payments')
                ->where(function($q) use ($customerCode) {
                    $q->whereRaw('TRIM(customer_code) = ?', [trim($customerCode)]);
                })
                ->where(function($q) use ($drNumber) {
                    $q->whereRaw('TRIM(dr_no) = ?', [trim($drNumber)]);
                })
                ->sum(DB::raw('amount + COALESCE(discount_amount, 0) + COALESCE(ewt, 0)'));

            $remaining = max(0, $originalBalance - $totalPaid);

            // ₱10 leeway — if remaining is within ₱10, treat as fully paid
            if ($remaining <= 10) {
                // Fully paid: zero out net_ar_balance so it gets filtered from outstanding
                DB::table('ar_aging')
                    ->where('id', $invoice->id)
                    ->update([
                        'net_ar_balance' => 0,
                        'status'         => 'Paid',
                        'updated_at'     => now()
                    ]);

                Log::info('Invoice fully paid', [
                    'invoice_id'    => $invoice->id,
                    'dr_number'     => $drNumber,
                    'total_paid'    => $totalPaid,
                    'original_bal'  => $originalBalance,
                ]);
            } else {
                // Partial payment: only update status — leave net_ar_balance untouched
                // so dynamic deduction in searchCustomer works from the original baseline
                DB::table('ar_aging')
                    ->where('id', $invoice->id)
                    ->update([
                        'status'     => 'Partial',
                        'updated_at' => now()
                    ]);

                Log::info('Invoice partially paid', [
                    'invoice_id'  => $invoice->id,
                    'dr_number'   => $drNumber,
                    'total_paid'  => $totalPaid,
                    'remaining'   => $remaining,
                ]);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to update AR Aging by DR', [
                'error'          => $e->getMessage(),
                'customer_code'  => $customerCode,
                'dr_number'      => $drNumber,
                'payment_amount' => $paymentAmount
            ]);
            return false;
        }
    }

    public function collectionReport(Request $request)
    {
        try {
            $dateFrom       = $request->input('date_from');
            $dateTo         = $request->input('date_to');
            $customerFilter = $request->input('customer', '');
            $drFilter       = $request->input('dr_no', '');

            $paymentsQuery = Payment::select(
                'id', 'customer_code', 'customer_name', 'dr_no', 'invoice_no',
                'collection_receipt_number', 'collection_receipt_date', 'payment_posting_date',
                'payment_date', 'amount', 'gross_amount', 'tax', 'ewt', 'net', 'check_amount',
                'payment_method', 'payment_option', 'payment_means_data', 'payment_notes',
                'bank', 'reference_no', 'status', 'is_short_payment', 'created_by', 'created_at'
            );

            if ($dateFrom) $paymentsQuery->whereDate('payment_posting_date', '>=', $dateFrom);
            if ($dateTo)   $paymentsQuery->whereDate('payment_posting_date', '<=', $dateTo);
            if ($customerFilter) {
                $paymentsQuery->where(function($q) use ($customerFilter) {
                    $q->where('customer_code', 'LIKE', '%' . $customerFilter . '%')
                      ->orWhere('customer_name', 'LIKE', '%' . $customerFilter . '%');
                });
            }
            if ($drFilter) {
                $paymentsQuery->whereRaw('TRIM(dr_no) LIKE ?', ['%' . trim($drFilter) . '%']);
            }

            $payments = $paymentsQuery
                ->where(function($q) { $q->whereNull('status')->orWhereNotIn('status', ['Bounced']); })
                ->orderBy('payment_posting_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success'      => true,
                'payments'     => $payments,
                'total_amount' => $payments->sum('amount'),
                'total_tax'    => $payments->sum('tax'),
            ]);

        } catch (\Exception $e) {
            Log::error('Collection report failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to load report'], 500);
        }
    }

    public function export(Request $request)
    {
        try {
            $dateFrom       = $request->input('date_from');
            $dateTo         = $request->input('date_to');
            $customerFilter = $request->input('customer', '');
            $drFilter       = $request->input('dr_no', '');

            $paymentsQuery = Payment::select(
                DB::raw("'manual' as source"),
                'customer_code', 'customer_name', 'dr_no', 'invoice_no',
                'collection_receipt_number', 'collection_receipt_date',
                'payment_posting_date', 'amount', 'tax', 'net'
            );

            if ($dateFrom) $paymentsQuery->whereDate('payment_posting_date', '>=', $dateFrom);
            if ($dateTo)   $paymentsQuery->whereDate('payment_posting_date', '<=', $dateTo);
            if ($customerFilter) {
                $paymentsQuery->where(function($q) use ($customerFilter) {
                    $q->where('customer_code', 'LIKE', '%' . $customerFilter . '%')
                      ->orWhere('customer_name', 'LIKE', '%' . $customerFilter . '%');
                });
            }
            if ($drFilter) {
                $paymentsQuery->whereRaw('TRIM(dr_no) LIKE ?', ['%' . trim($drFilter) . '%']);
            }

            $arAgingQuery = DB::table('ar_aging')
                ->select(
                    DB::raw("'invoiced' as source"),
                    'customer_code',
                    DB::raw("client_name as customer_name"),
                    'dr_no', 'invoice_no',
                    DB::raw("CONCAT('AR-', invoice_no) as collection_receipt_number"),
                    DB::raw("invoice_date as collection_receipt_date"),
                    DB::raw("invoice_date as payment_posting_date"),
                    'invoice_amount as amount',
                    DB::raw('0 as tax'),
                    DB::raw('NULL as net')
                );

            if ($dateFrom) $arAgingQuery->whereDate('invoice_date', '>=', $dateFrom);
            if ($dateTo)   $arAgingQuery->whereDate('invoice_date', '<=', $dateTo);
            if ($customerFilter) {
                $arAgingQuery->where(function($q) use ($customerFilter) {
                    $q->where('customer_code', 'LIKE', '%' . $customerFilter . '%')
                      ->orWhere('client_name', 'LIKE', '%' . $customerFilter . '%');
                });
            }

            $deliveriesQuery = DB::table('deliveries')
                ->select(
                    DB::raw("'pending_invoice' as source"),
                    DB::raw("customer_code COLLATE utf8mb4_general_ci as customer_code"),
                    DB::raw("customer_name COLLATE utf8mb4_general_ci as customer_name"),
                    DB::raw("dr_no COLLATE utf8mb4_general_ci as dr_no"),
                    DB::raw("'Pending Invoice' COLLATE utf8mb4_general_ci as invoice_no"),
                    DB::raw("dr_no COLLATE utf8mb4_general_ci as collection_receipt_number"),
                    DB::raw("request_delivery_date as collection_receipt_date"),
                    DB::raw("request_delivery_date as payment_posting_date"),
                    DB::raw('0 as amount'),
                    DB::raw('0 as tax'),
                    DB::raw('NULL as net')
                )
                ->where('status', 'Delivered')
                ->where('is_pulled_out', '!=', 1)
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('ar_aging')
                        ->whereRaw('ar_aging.dr_no COLLATE utf8mb4_general_ci = deliveries.dr_no COLLATE utf8mb4_general_ci');
                });

            if ($dateFrom) $deliveriesQuery->whereDate('request_delivery_date', '>=', $dateFrom);
            if ($dateTo)   $deliveriesQuery->whereDate('request_delivery_date', '<=', $dateTo);
            if ($customerFilter) {
                $deliveriesQuery->where(function($q) use ($customerFilter) {
                    $q->where('customer_code', 'LIKE', '%' . $customerFilter . '%')
                      ->orWhere('customer_name', 'LIKE', '%' . $customerFilter . '%');
                });
            }

            $paymentsQuery->where(function($q) { $q->whereNull('status')->orWhereNotIn('status', ['Bounced']); });

            $payments = $paymentsQuery
                ->union($arAgingQuery)
                ->union($deliveriesQuery)
                ->orderBy('payment_posting_date', 'desc')
                ->get();

            $filename = 'collection_report_' . now()->format('Y-m-d_H-i-s') . '.csv';
            $headers  = [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
                'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
                'Expires'             => '0',
                'Pragma'              => 'public',
            ];

            $callback = function () use ($payments) {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                fputcsv($file, [
                    'Customer Code', 'Customer Name', 'DR No', 'Collection Receipt Number',
                    'Collection Receipt Date', 'Payment Posting Date',
                    'Amount', 'Tax', 'Net', 'Payment Option', 'Notes'
                ]);
                foreach ($payments as $payment) {
                    fputcsv($file, [
                        $payment->customer_code,
                        $payment->customer_name,
                        $payment->dr_no ?? '',
                        $payment->collection_receipt_number,
                        Carbon::parse($payment->collection_receipt_date)->format('m/d/Y'),
                        Carbon::parse($payment->payment_posting_date)->format('m/d/Y'),
                        number_format($payment->amount, 2, '.', ''),
                        number_format($payment->tax ?? 0, 2, '.', ''),
                        number_format($payment->net ?? 0, 2, '.', ''),
                        $payment->payment_option ?? '',
                        $payment->payment_notes ?? '',
                    ]);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Payment export failed', ['error' => $e->getMessage()]);
            abort(500, 'Failed to export: ' . $e->getMessage());
        }
    }

    private function updateArAgingBalance($customerCode, $paymentAmount)
    {
        try {
            $outstandingRecords = DB::table('ar_aging')
                ->where('customer_code', $customerCode)
                ->where('net_ar_balance', '>', 0)
                ->orderBy('invoice_date', 'asc')
                ->get();

            $remainingPayment = $paymentAmount;

            foreach ($outstandingRecords as $record) {
                if ($remainingPayment <= 0) break;

                $currentBalance = $record->net_ar_balance;

                if ($remainingPayment >= $currentBalance) {
                    DB::table('ar_aging')->where('id', $record->id)->update([
                        'net_ar_balance'         => 0,
                        'settled_invoice_amount' => DB::raw('settled_invoice_amount + ' . $currentBalance),
                        'status'                 => 'Paid'
                    ]);
                    $remainingPayment -= $currentBalance;
                } else {
                    DB::table('ar_aging')->where('id', $record->id)->update([
                        'net_ar_balance'         => DB::raw('net_ar_balance - ' . $remainingPayment),
                        'settled_invoice_amount' => DB::raw('settled_invoice_amount + ' . $remainingPayment),
                        'status'                 => 'Partial'
                    ]);
                    $remainingPayment = 0;
                }
            }

            Log::info('AR Aging updated successfully', [
                'customer_code'    => $customerCode,
                'payment_amount'   => $paymentAmount,
                'remaining_payment'=> $remainingPayment
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to update AR Aging', [
                'error'          => $e->getMessage(),
                'customer_code'  => $customerCode,
                'payment_amount' => $paymentAmount
            ]);
            return false;
        }
    }

    /**
     * Get payment history for a specific customer
     */
    public function getCustomerHistory(Request $request)
    {
        try {
            $customerCode = $request->input('customer_code');
            $statusFilter = $request->input('status');

            if (!$customerCode) {
                return response()->json(['success' => false, 'message' => 'Customer code is required'], 400);
            }

            Log::info('Getting customer history', [
                'customer_code' => $customerCode,
                'status_filter' => $statusFilter
            ]);

            if ($statusFilter === 'outstanding') {
                // Get locked DR numbers to exclude from outstanding list
                $lockedDrNos = DB::table('deliveries')
                    ->where('is_locked', 1)
                    ->where('customer_code', $customerCode)
                    ->pluck('dr_no')
                    ->map(fn($d) => trim($d))
                    ->filter()
                    ->toArray();

                $outstandingInvoices = DB::table('ar_aging')
                    ->select(
                        'invoice_date as deposit_date', 'invoice_no', 'dr_no',
                        'customer_code', 'client_name as customer_name', 'branch',
                        'invoice_amount as gross_amount',
                        DB::raw('0 as ewt'),
                        DB::raw('0 as other_adjustment'),
                        DB::raw('0 as factoring'),
                        'net_ar_balance as check_amount',
                        DB::raw('0 as net_of_cwt'),
                        DB::raw('NULL as week_no'),
                        DB::raw('NULL as ar_class'),
                        DB::raw('NULL as bank'),
                        DB::raw('NULL as checking_si'),
                        'status',
                        'terms as remarks',
                        DB::raw('CONCAT("INV-", invoice_no) as collection_receipt_number'),
                        'invoice_amount',
                        'settled_invoice_amount',
                        'net_of_cwt as raw_net_of_cwt',
                        'ar_adjustments',
                        'others_amount'
                    )
                    ->where('customer_code', $customerCode)
                    ->where(function($q) {
                        $q->whereNull('status')
                          ->orWhere('status', '')
                          ->orWhere('status', '!=', 'Paid');
                    })
                    ->orderBy('invoice_date', 'desc')
                    ->get();

                $outstandingInvoices = $outstandingInvoices->map(function($record) use ($lockedDrNos) {
                    $netAr = (float)($record->check_amount ?? 0);
                    if ($netAr <= 0) {
                        $netAr = max(0,
                            (float)($record->invoice_amount ?? 0)
                            - (float)($record->settled_invoice_amount ?? 0)
                        );
                    }
                    if ($netAr <= 0) {
                        $netOfCwt  = (float)($record->raw_net_of_cwt ?? 0);
                        $checkAmt  = (float)($record->check_amount ?? 0);
                        $othersAmt = (float)($record->others_amount ?? 0);
                        $adjAmt    = (float)($record->ar_adjustments ?? 0);
                        if ($netOfCwt > 0) {
                            $computed = $netOfCwt - $adjAmt - $checkAmt - $othersAmt;
                            if ($computed < 0) $computed = $netOfCwt - $adjAmt + $othersAmt - $checkAmt;
                            $netAr = max(0, $computed);
                        }
                    }
                    $record->check_amount = $netAr;
                    $record->gross_amount = (float)($record->invoice_amount ?? $record->gross_amount);

                    // Cross-check with delivery total — ar_aging may have wrong amount
                    $deliveryTotal = (float)DB::table('delivery_items')
                        ->join('deliveries', 'deliveries.id', '=', 'delivery_items.delivery_id')
                        ->where('deliveries.dr_no', $record->dr_no)
                        ->sum('delivery_items.total_amount');

                    if ($deliveryTotal > 0 && $deliveryTotal > $record->gross_amount) {
                        $record->gross_amount = $deliveryTotal;
                        // Also update check_amount if delivery total is higher
                        if ($deliveryTotal > $netAr) {
                            $record->check_amount = $deliveryTotal;
                        }
                    }

                    $record->is_locked = in_array(trim($record->dr_no), $lockedDrNos);

                    return $record;
                })->filter(fn($r) => $r->check_amount > 0 && !($r->is_locked ?? false))->values();

                // Total settled = means paid + discount + EWT (all reduce the outstanding)
                $existingPayments = DB::table('payments')
                    ->where('customer_code', $customerCode)
                    ->select('dr_no', 'invoice_no', DB::raw('SUM(amount + COALESCE(discount_amount, 0) + COALESCE(ewt, 0)) as total_paid'))
                    ->groupBy('dr_no', 'invoice_no')
                    ->get()
                    ->keyBy(function($p) { return trim($p->dr_no ?? '') . '|' . trim($p->invoice_no ?? ''); });

                $outstandingInvoices = $outstandingInvoices->map(function($record) use ($existingPayments) {
                    $drKey  = trim($record->dr_no ?? '');
                    $key    = $drKey . '|' . trim($record->invoice_no ?? '');

                    // Primary: match by dr_no + invoice_no; fallback: dr_no only (handles PENDING mismatch)
                    if ($existingPayments->has($key)) {
                        $paid = (float)$existingPayments[$key]->total_paid;
                    } else {
                        $paid = (float)$existingPayments
                            ->filter(fn($p) => trim($p->dr_no ?? '') === $drKey)
                            ->sum('total_paid');
                    }

                    if ($paid > 0) {
                        $record->check_amount = max(0, $record->check_amount - $paid);
                        $record->gross_amount = max(0, $record->gross_amount - $paid);
                        $record->total_paid   = $paid;
                        $record->status = $record->check_amount <= 0 ? 'Paid' : 'Partial';
                    }
                    return $record;
                })->filter(fn($r) => $r->check_amount > 0 && !($r->is_locked ?? false))->values();

                // Apply AR adjustments from ar_adjustments table
                $arAdjustments = DB::table('ar_adjustments')
                    ->whereRaw('TRIM(customer_code) = ?', [trim($customerCode)])
                    ->whereNotNull('dr_no')
                    ->where('dr_no', '!=', '')
                    ->select(
                        'dr_no',
                        DB::raw('SUM(CASE WHEN is_decrease = 1 THEN -ABS(amount) ELSE ABS(amount) END) as net_adjustment'),
                        DB::raw('GROUP_CONCAT(CONCAT(transaction_type, ": ", IF(is_decrease=1,"-","+"), FORMAT(ABS(amount),2)) ORDER BY created_at SEPARATOR " | ") as adj_detail')
                    )
                    ->groupBy('dr_no')
                    ->get()
                    ->keyBy(fn($a) => trim($a->dr_no));

                $outstandingInvoices = $outstandingInvoices->map(function($record) use ($arAdjustments) {
                    $drKey = trim($record->dr_no ?? '');
                    if ($arAdjustments->has($drKey)) {
                        $adj = $arAdjustments[$drKey];
                        $adjustment = (float)$adj->net_adjustment;
                        $record->check_amount = max(0, $record->check_amount + $adjustment);
                        $record->ar_adjustment_applied = $adjustment;
                        $record->ar_adjustment_detail  = $adj->adj_detail;
                    }
                    return $record;
                })->filter(fn($r) => $r->check_amount > 0 && !($r->is_locked ?? false))->values();

                // Also include outstanding deliveries NOT in ar_aging (pending invoicing)
                $arAgingDrNos = DB::table('ar_aging')
                    ->where('customer_code', $customerCode)
                    ->pluck('dr_no')
                    ->map(fn($d) => trim($d))
                    ->filter()
                    ->toArray();

                $deliveriesNotInAging = DB::table('deliveries')
                    ->leftJoin('delivery_items', 'deliveries.id', '=', 'delivery_items.delivery_id')
                    ->where('deliveries.customer_code', $customerCode)
                    ->where('deliveries.status', 'Delivered')
                    ->where('deliveries.is_pulled_out', '!=', 1)
                    ->where('deliveries.is_locked', '!=', 1)
                    ->when(!empty($arAgingDrNos), function($q) use ($arAgingDrNos) {
                        $q->whereNotIn(DB::raw('TRIM(deliveries.dr_no)'), $arAgingDrNos);
                    })
                    ->select(
                        'deliveries.dr_no',
                        'deliveries.is_locked',
                        'deliveries.customer_code',
                        'deliveries.customer_name',
                        'deliveries.branch',
                        'deliveries.request_delivery_date',
                        DB::raw('SUM(COALESCE(delivery_items.total_amount, 0)) as delivery_total')
                    )
                    ->groupBy('deliveries.dr_no', 'deliveries.is_locked', 'deliveries.customer_code', 'deliveries.customer_name', 'deliveries.branch', 'deliveries.request_delivery_date')
                    ->having('delivery_total', '>', 0)
                    ->get();

                foreach ($deliveriesNotInAging as $del) {
                    $delTotal = (float)$del->delivery_total;

                    // Check if already paid
                    $delKey = trim($del->dr_no) . '|PENDING';
                    $paidAmount = 0;
                    if ($existingPayments->has($delKey)) {
                        $paidAmount = (float)$existingPayments[$delKey]->total_paid;
                    }
                    // Also check without invoice_no
                    $existingPayments->each(function($p) use ($del, &$paidAmount) {
                        if (trim($p->dr_no) === trim($del->dr_no) && $paidAmount === 0) {
                            $paidAmount = (float)$p->total_paid;
                        }
                    });

                    $remaining = max(0, $delTotal - $paidAmount);
                    if ($remaining > 0) {
                        $outstandingInvoices->push((object)[
                            'deposit_date' => $del->request_delivery_date,
                            'invoice_no' => 'PENDING',
                            'dr_no' => $del->dr_no,
                            'customer_code' => $del->customer_code,
                            'customer_name' => $del->customer_name,
                            'branch' => $del->branch,
                            'gross_amount' => $delTotal,
                            'ewt' => 0,
                            'other_adjustment' => 0,
                            'factoring' => 0,
                            'check_amount' => $remaining,
                            'net_of_cwt' => 0,
                            'week_no' => null,
                            'ar_class' => null,
                            'bank' => null,
                            'checking_si' => null,
                            'status' => $paidAmount > 0 ? 'Partial' : 'Outstanding',
                            'remarks' => 'TBD',
                            'collection_receipt_number' => 'PENDING',
                            'invoice_amount' => $delTotal,
                            'settled_invoice_amount' => 0,
                            'raw_net_of_cwt' => 0,
                            'ar_adjustments' => 0,
                            'others_amount' => 0,
                            'is_locked' => (bool)$del->is_locked,
                        ]);
                    }
                }

                Log::info('Outstanding invoices found (payment-aware)', [
                    'count'        => $outstandingInvoices->count(),
                    'total_amount' => $outstandingInvoices->sum('check_amount')
                ]);

                return response()->json([
                    'success'           => true,
                    'payments'          => $outstandingInvoices,
                    'total_outstanding' => $outstandingInvoices->sum('check_amount')
                ]);
            }

            $customerName = DB::table('ar_aging')
                ->where('customer_code', $customerCode)
                ->value('client_name');

            if (!$customerName) {
                return response()->json(['success' => false, 'message' => 'Customer not found'], 404);
            }

            $payments = Payment::where(function($query) use ($customerCode, $customerName) {
                    $query->where('customer_code', $customerCode)
                          ->orWhere('customer_name', 'LIKE', '%' . $customerName . '%');
                })
                ->orderBy('payment_posting_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            return response()->json(['success' => true, 'payments' => $payments]);

        } catch (\Exception $e) {
            Log::error('Failed to get customer payment history', [
                'error'         => $e->getMessage(),
                'trace'         => $e->getTraceAsString(),
                'customer_code' => $request->input('customer_code')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load payment history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * View all payments with duplicate CR numbers
     */
    public function viewDuplicateCR(Request $request)
    {
        try {
            $crNumber = $request->input('cr_number');

            if ($crNumber) {
                $payments = Payment::where('collection_receipt_number', $crNumber)
                    ->orderBy('created_at', 'asc')
                    ->get();
                $title = "CR Number: {$crNumber} ({$payments->count()} records)";
            } else {
                $duplicates = DB::table('payments')
                    ->select('collection_receipt_number', DB::raw('COUNT(*) as count'))
                    ->whereNotNull('collection_receipt_number')
                    ->where('collection_receipt_number', '!=', '')
                    ->groupBy('collection_receipt_number')
                    ->having('count', '>', 1)
                    ->orderBy('count', 'desc')
                    ->get();

                return view('payments.duplicate-cr-list', compact('duplicates'));
            }

            return view('payments.duplicate-cr-details', compact('payments', 'title', 'crNumber'));

        } catch (\Exception $e) {
            Log::error('Failed to view duplicate CR numbers', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to load duplicate CR data');
        }
    }

    /**
     * Show individual payment details
     */
    public function show($id)
    {
        $payment = DB::table('payments')->where('id', $id)->first();

        if (!$payment) {
            return redirect()->route('payments.entry')->with('error', 'Payment not found.');
        }

        // Look up branch from customers table
        $payment->branch = DB::table('customers')
            ->where('customer_code', $payment->customer_code)
            ->value('branch');

        $paymentMeans = json_decode($payment->payment_means_data ?? '{}', true);

        $creditSources = DB::table('payment_credit_applications')
            ->join('payments', 'payments.id', '=', 'payment_credit_applications.credit_source_payment_id')
            ->where('payment_credit_applications.payment_id', $payment->id)
            ->select('payments.id', 'payments.collection_receipt_number', 'payments.invoice_no', 'payments.dr_no', 'payment_credit_applications.amount as credit_amount')
            ->get();

        $creditSource = null;
        if ($creditSources->isEmpty() && $payment->credit_from_payment_id) {
            $creditSource = DB::table('payments')->where('id', $payment->credit_from_payment_id)->first();
        }

        $creditApplications = collect();
        if ($payment->overpayment > 0) {
            $junctionApps = DB::table('payment_credit_applications')
                ->join('payments', 'payments.id', '=', 'payment_credit_applications.payment_id')
                ->where('payment_credit_applications.credit_source_payment_id', $payment->id)
                ->select('payments.*', 'payment_credit_applications.amount as credit_applied_amount')
                ->get();

            $creditApplications = $junctionApps->isNotEmpty()
                ? $junctionApps
                : DB::table('payments')->where('credit_from_payment_id', $payment->id)->get();
        }

        $remainingCredit = 0;
        if ($payment->overpayment > 0) {
            $usedJunction = (float)DB::table('payment_credit_applications')
                ->where('credit_source_payment_id', $payment->id)
                ->sum('amount');
            $usedLegacy = (float)DB::table('payments')
                ->where('credit_from_payment_id', $payment->id)
                ->sum('credit_applied');
            $usedCredit      = max($usedJunction, $usedLegacy);
            $remainingCredit = (float)$payment->overpayment - $usedCredit;
        }

        // Get AR adjustments for this DR
        $arAdjustments = collect();
        if (!empty($payment->dr_no)) {
            $arAdjustments = DB::table('ar_adjustments')
                ->whereRaw('TRIM(dr_no) = ?', [trim($payment->dr_no)])
                ->select('transaction_type', 'amount', 'is_decrease', 'transaction_date', 'reference_number', 'remarks', 'created_by')
                ->orderBy('transaction_date')
                ->get();
        }

        return view('payments.show', compact('payment', 'paymentMeans', 'creditSource', 'creditSources', 'creditApplications', 'remainingCredit', 'arAdjustments'));
    }

    /**
     * Check if a DR number is already paid, partially paid, or still outstanding.
     */
    public function checkDRStatus(Request $request)
    {
        $customerCode = $request->input('customer_code');
        $drNo         = $request->input('dr_no');

        if (!$customerCode || !$drNo) {
            return response()->json(['status' => 'not_found']);
        }

        // Check if delivery is locked — locked DRs should not be collectible
        $lockedCheck = DB::table('deliveries')
            ->where(function($q) use ($drNo) {
                $q->where('dr_no', $drNo)->orWhereRaw('TRIM(dr_no) = ?', [trim($drNo)]);
            })
            ->where('is_locked', 1)
            ->first();

        if ($lockedCheck) {
            return response()->json(['status' => 'paid', 'total_paid' => 0, 'invoice_no' => null, 'locked' => true]);
        }

        $arRecord = DB::table('ar_aging')
            ->where(function($q) use ($customerCode) {
                $q->where('customer_code', $customerCode)
                  ->orWhereRaw('TRIM(customer_code) = ?', [trim($customerCode)]);
            })
            ->where(function($q) use ($drNo) {
                $q->where('dr_no', $drNo)
                  ->orWhereRaw('TRIM(dr_no) = ?', [trim($drNo)]);
            })
            ->first();

        // Helper: get net AR adjustment for this DR (decrease = negative, increase = positive)
        $arAdjustmentNet = (float)(DB::table('ar_adjustments')
            ->where(function($q) use ($drNo) {
                $q->where('dr_no', $drNo)->orWhereRaw('TRIM(dr_no) = ?', [trim($drNo)]);
            })
            ->selectRaw('SUM(CASE WHEN is_decrease = 1 THEN -ABS(amount) ELSE ABS(amount) END) as net')
            ->value('net') ?? 0);

        if (!$arRecord) {
            $delivery = DB::table('deliveries')
                ->leftJoin('delivery_items', 'deliveries.id', '=', 'delivery_items.delivery_id')
                ->where('deliveries.status', 'Delivered')
                ->where('deliveries.is_pulled_out', '!=', 1)
                ->where(function($q) use ($drNo) {
                    $q->where('deliveries.dr_no', $drNo)
                      ->orWhereRaw('TRIM(deliveries.dr_no) = ?', [trim($drNo)]);
                })
                ->where(function($q) use ($customerCode) {
                    $q->where('deliveries.customer_code', $customerCode)
                      ->orWhereRaw('TRIM(deliveries.customer_code) = ?', [trim($customerCode)]);
                })
                ->selectRaw('deliveries.dr_no, SUM(COALESCE(delivery_items.total_amount, 0)) as delivery_amount')
                ->groupBy('deliveries.dr_no')
                ->first();

            if ($delivery && $delivery->delivery_amount > 0) {
                // Total settled = means + discount + EWT (all reduce outstanding)
                $totalPaid = (float)DB::table('payments')
                    ->where(function($q) use ($drNo) {
                        $q->where('dr_no', $drNo)->orWhereRaw('TRIM(dr_no) = ?', [trim($drNo)]);
                    })
                    ->where(function($q) use ($customerCode) {
                        $q->where('customer_code', $customerCode)->orWhereRaw('TRIM(customer_code) = ?', [trim($customerCode)]);
                    })
                    ->where(function($q) { $q->whereNull('status') ->orWhere(function($inner) { $inner->whereNotNull('status')->whereNotIn('status', ['Voided', 'Bounced']); });})
                    ->selectRaw('SUM(amount + COALESCE(discount_amount, 0) + COALESCE(ewt, 0)) as total')
                    ->value('total') ?? 0;

                $remaining = max(0, (float)$delivery->delivery_amount - $totalPaid + $arAdjustmentNet);

                if ($remaining < 2 && $totalPaid > 0) {
                    return response()->json(['status' => 'paid', 'total_paid' => $totalPaid, 'invoice_no' => 'PENDING']);
                }

                return response()->json([
                    'status'         => $totalPaid > 0 ? 'partial' : 'outstanding',
                    'total_paid'     => $totalPaid,
                    'remaining'      => $remaining,
                    'invoice_amount' => (float)$delivery->delivery_amount,
                    'invoice_no'     => 'PENDING',
                ]);
            }

            // DR not found for this customer — check if it belongs to a different customer
            $otherOwner = DB::table('deliveries')
                ->where('dr_no', $drNo)
                ->select('customer_code', 'customer_name')
                ->first();

            if (!$otherOwner) {
                $otherOwner = DB::table('ar_aging')
                    ->where('dr_no', $drNo)
                    ->select('customer_code', DB::raw('client_name as customer_name'))
                    ->first();
            }

            if ($otherOwner) {
                return response()->json([
                    'status'        => 'wrong_customer',
                    'actual_customer_code' => $otherOwner->customer_code,
                    'actual_customer_name' => $otherOwner->customer_name,
                ]);
            }

            return response()->json(['status' => 'not_found']);
        }

        $originalOutstanding = (float)($arRecord->net_ar_balance ?? 0);
        if ($originalOutstanding <= 0) {
            $originalOutstanding = max(0,
                (float)($arRecord->invoice_amount ?? 0)
                - (float)($arRecord->settled_invoice_amount ?? 0)
            );
        }
        if ($originalOutstanding <= 0) {
            $netOfCwt  = (float)($arRecord->net_of_cwt ?? 0);
            $checkAmt  = (float)($arRecord->check_amount ?? 0);
            $othersAmt = (float)($arRecord->others_amount ?? 0);
            $adjAmt    = (float)($arRecord->ar_adjustments ?? 0);
            if ($netOfCwt > 0) {
                $computed = $netOfCwt - $adjAmt - $checkAmt - $othersAmt;
                if ($computed < 0) $computed = $netOfCwt - $adjAmt + $othersAmt - $checkAmt;
                $originalOutstanding = max(0, $computed);
            }
        }

        // Cross-check with delivery total — ar_aging may have incomplete/wrong amount
        $deliveryTotal = (float)DB::table('delivery_items')
            ->join('deliveries', 'deliveries.id', '=', 'delivery_items.delivery_id')
            ->where('deliveries.dr_no', $drNo)
            ->sum('delivery_items.total_amount');

        if ($deliveryTotal > 0 && $deliveryTotal > $originalOutstanding) {
            $originalOutstanding = $deliveryTotal;
        }

        // Total settled = means + discount + EWT (all reduce outstanding)
        $totalPaid = (float)(DB::table('payments')
            ->where(function($q) use ($customerCode) {
                $q->where('customer_code', $customerCode)->orWhereRaw('TRIM(customer_code) = ?', [trim($customerCode)]);
            })
            ->where(function($q) use ($drNo) {
                $q->where('dr_no', $drNo)->orWhereRaw('TRIM(dr_no) = ?', [trim($drNo)]);
            })
            ->where(function($q) { $q->whereNull('status') ->orWhere(function($inner) { $inner->whereNotNull('status')->whereNotIn('status', ['Voided', 'Bounced']); });})
            ->selectRaw('SUM(amount + COALESCE(discount_amount, 0) + COALESCE(ewt, 0)) as total')
            ->value('total') ?? 0);

        $remaining = max(0, $originalOutstanding - $totalPaid + $arAdjustmentNet);

        if ($remaining < 2 && $totalPaid > 0) {
            return response()->json(['status' => 'paid', 'total_paid' => $totalPaid, 'invoice_no' => $arRecord->invoice_no ?? null]);
        } elseif ($totalPaid > 0 && $remaining >= 2) {
            return response()->json([
                'status'         => 'partial',
                'total_paid'     => $totalPaid,
                'remaining'      => $remaining,
                'invoice_amount' => (float)($arRecord->invoice_amount ?? $originalOutstanding),
                'invoice_no'     => $arRecord->invoice_no ?? null,
            ]);
        }

        if ($originalOutstanding <= 0) {
            return response()->json(['status' => 'paid', 'total_paid' => 0, 'invoice_no' => $arRecord->invoice_no ?? null]);
        }

        // Outstanding balance exists but no payments made yet — also factor in adjustments
        $adjustedOutstanding = max(0, $originalOutstanding + $arAdjustmentNet);
        return response()->json([
            'status'         => $adjustedOutstanding < 2 ? 'paid' : 'outstanding',
            'total_paid'     => 0,
            'remaining'      => $adjustedOutstanding,
            'invoice_amount' => (float)($arRecord->invoice_amount ?? $originalOutstanding),
            'invoice_no'     => $arRecord->invoice_no ?? null,
        ]);
    }

    public function getCustomerCredits(Request $request)
    {
        $customerCode = $request->input('customer_code');
        $customerName = $request->input('customer_name');

        if (!$customerCode && !$customerName) {
            return response()->json(['success' => false, 'credits' => [], 'total' => 0]);
        }

        $query = Payment::where('overpayment', '>', 0);

        if (!empty($customerName)) {
            $query->where(function($q) use ($customerCode, $customerName) {
                $q->whereRaw('TRIM(LOWER(customer_name)) = ?', [trim(strtolower($customerName))]);
                if (!empty($customerCode) && $customerCode !== '#N/A' && $customerCode !== 'N/A') {
                    $q->orWhere('customer_code', $customerCode);
                }
            });
        } else {
            $query->where('customer_code', $customerCode);
        }

        $overpayments = $query->get();
        $credits       = [];
        $totalAvailable = 0;

        foreach ($overpayments as $op) {
            $usedLegacy  = (float)Payment::where('credit_from_payment_id', $op->id)->sum('credit_applied');
            $usedJunction = (float)DB::table('payment_credit_applications')->where('credit_source_payment_id', $op->id)->sum('amount');
            $used      = max($usedLegacy, $usedJunction);
            $remaining = (float)$op->overpayment - $used;

            if ($remaining > 0) {
                $credits[] = [
                    'payment_id'               => $op->id,
                    'collection_receipt_number' => $op->collection_receipt_number,
                    'date'                     => $op->collection_receipt_date,
                    'original_amount'          => (float)$op->amount,
                    'overpayment'              => (float)$op->overpayment,
                    'remaining_credit'         => $remaining,
                    'invoice_no'               => $op->invoice_no,
                    'dr_no'                    => $op->dr_no,
                ];
                $totalAvailable += $remaining;
            }
        }

        return response()->json(['success' => true, 'credits' => $credits, 'total' => $totalAvailable]);
    }

    /**
     * Show edit form for a payment
     */
    public function edit($id)
    {
        $payment = DB::table('payments')->where('id', $id)->first();
        if (!$payment) {
            return redirect()->route('payments.entry')->with('error', 'Payment not found.');
        }

        $paymentMeans = json_decode($payment->payment_means_data ?? '{}', true);
        return view('payments.edit', compact('payment', 'paymentMeans'));
    }

    /**
     * Update a payment directly (Joey / IT only)
     */
    public function update(Request $request, $id)
    {
        $payment = DB::table('payments')->where('id', $id)->first();
        if (!$payment) {
            return redirect()->route('payments.entry')->with('error', 'Payment not found.');
        }

        $validated = $request->validate([
            'collection_receipt_number' => 'required|string|max:255',
            'collection_receipt_date'   => 'required|date',
            'payment_posting_date'      => 'required|date',
            'dr_no'                     => 'required|string|max:255',
            'invoice_no'                => 'nullable|string|max:255',
            'amount'                    => 'required|numeric|min:0',
            'tax'                       => 'nullable|numeric|min:0',
            'net'                       => 'nullable|numeric|min:0',
            'payment_method'            => 'required|in:check,bank_transfer,cash',
            'bank'                      => 'nullable|string|max:255',
            'reference_no'              => 'nullable|string|max:255',
            'payment_notes'             => 'nullable|string|max:1000',
        ]);

        $exists = DB::table('payments')
            ->where('collection_receipt_number', $validated['collection_receipt_number'])
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['collection_receipt_number' => 'This receipt number has already been used.'])->withInput();
        }

        $grossAmount = (float)$validated['amount'];
        $ewtAmount   = (float)($validated['tax'] ?? 0);
        $netAmount   = (float)($validated['net'] ?? ($grossAmount - $ewtAmount));
        $overpayment = ($grossAmount > $netAmount && $netAmount > 0)
            ? round($grossAmount - $netAmount, 2)
            : 0;

        DB::table('payments')->where('id', $id)->update([
            'collection_receipt_number' => $validated['collection_receipt_number'],
            'collection_receipt_date'   => $validated['collection_receipt_date'],
            'deposit_date'              => $validated['collection_receipt_date'],
            'payment_posting_date'      => $validated['payment_posting_date'],
            'payment_date'              => $validated['payment_posting_date'],
            'dr_no'                     => $validated['dr_no'],
            'invoice_no'                => $validated['invoice_no'],
            'amount'                    => $grossAmount,
            'gross_amount'              => $grossAmount,
            'tax'                       => $ewtAmount,
            'ewt'                       => $ewtAmount,
            'net'                       => $netAmount,
            'check_amount'              => $netAmount,
            'payment_method'            => $validated['payment_method'],
            'bank'                      => $validated['bank'],
            'reference_no'              => $validated['reference_no'],
            'payment_notes'             => $validated['payment_notes'],
        ]);

        return redirect()->route('payments.show', $id)->with('success', 'Payment updated successfully.');
    }

    /**
     * Submit an edit request (for non-Joey CC roles)
     */
    public function submitEditRequest(Request $request, $id)
    {
        $payment = DB::table('payments')->where('id', $id)->first();
        if (!$payment) {
            return redirect()->route('payments.entry')->with('error', 'Payment not found.');
        }

        $validated = $request->validate([
            'collection_receipt_number' => 'required|string|max:255',
            'collection_receipt_date'   => 'required|date',
            'payment_posting_date'      => 'required|date',
            'dr_no'                     => 'required|string|max:255',
            'invoice_no'                => 'nullable|string|max:255',
            'amount'                    => 'required|numeric|min:0',
            'tax'                       => 'nullable|numeric|min:0',
            'net'                       => 'nullable|numeric|min:0',
            'payment_method'            => 'required|in:check,bank_transfer,cash',
            'bank'                      => 'nullable|string|max:255',
            'reference_no'              => 'nullable|string|max:255',
            'payment_notes'             => 'nullable|string|max:1000',
            'edit_reason'               => 'required|string|max:500',
            'attachment'                => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx',
        ]);

        $attachmentPath = null;
        $attachmentName = null;
        if ($request->hasFile('attachment')) {
            $file           = $request->file('attachment');
            $attachmentName = $file->getClientOriginalName();
            $attachmentPath = $file->store('payment-edit-attachments', 'public');
        }

        $originalData = [
            'collection_receipt_number' => $payment->collection_receipt_number,
            'collection_receipt_date'   => $payment->collection_receipt_date,
            'payment_posting_date'      => $payment->payment_posting_date,
            'dr_no'                     => $payment->dr_no,
            'invoice_no'                => $payment->invoice_no,
            'amount'                    => $payment->amount,
            'tax'                       => $payment->tax,
            'net'                       => $payment->net,
            'payment_method'            => $payment->payment_method,
            'bank'                      => $payment->bank,
            'reference_no'              => $payment->reference_no,
            'payment_notes'             => $payment->payment_notes,
        ];

        $proposedData = [
            'collection_receipt_number' => $validated['collection_receipt_number'],
            'collection_receipt_date'   => $validated['collection_receipt_date'],
            'payment_posting_date'      => $validated['payment_posting_date'],
            'dr_no'                     => $validated['dr_no'],
            'invoice_no'                => $validated['invoice_no'],
            'amount'                    => $validated['amount'],
            'tax'                       => $validated['tax'] ?? 0,
            'net'                       => $validated['net'] ?? 0,
            'payment_method'            => $validated['payment_method'],
            'bank'                      => $validated['bank'],
            'reference_no'              => $validated['reference_no'],
            'payment_notes'             => $validated['payment_notes'],
        ];

        DB::table('payment_edit_requests')->insert([
            'payment_id'       => $id,
            'requested_by'     => auth()->id(),
            'requested_by_name'=> auth()->user()->name,
            'original_data'    => json_encode($originalData),
            'proposed_data'    => json_encode($proposedData),
            'reason'           => $validated['edit_reason'],
            'attachment_path'  => $attachmentPath,
            'attachment_name'  => $attachmentName,
            'status'           => 'Pending',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return redirect()->route('payments.show', $id)->with('success', 'Edit request submitted. Awaiting approval from Joey Fernandez.');
    }

    /**
     * List pending edit requests (for Joey / IT)
     */
    public function editRequests()
    {
        $query = DB::table('payment_edit_requests')
            ->join('payments', 'payments.id', '=', 'payment_edit_requests.payment_id')
            ->select(
                'payment_edit_requests.*',
                'payments.customer_name',
                'payments.collection_receipt_number as cr_number',
                'payments.dr_no'
            );

        $user = auth()->user();
        if (!$user->canApprovePaymentEditRequests()) {
            $query->where('payment_edit_requests.requested_by', $user->id);
        }

        $requests = $query
            ->orderByRaw("CASE WHEN payment_edit_requests.status = 'Pending' THEN 0 ELSE 1 END")
            ->orderBy('payment_edit_requests.created_at', 'desc')
            ->get();

        return view('payments.edit-requests', compact('requests'));
    }

    /**
     * Approve an edit request
     */
    public function approveEditRequest($requestId)
    {
        $editRequest = DB::table('payment_edit_requests')->where('id', $requestId)->first();
        if (!$editRequest || $editRequest->status !== 'Pending') {
            return back()->with('error', 'Edit request not found or already processed.');
        }

        $proposedData = json_decode($editRequest->proposed_data, true);
        $grossAmount  = (float)($proposedData['amount'] ?? 0);
        $ewtAmount    = (float)($proposedData['tax'] ?? 0);
        $netAmount    = (float)($proposedData['net'] ?? ($grossAmount - $ewtAmount));
        $overpayment  = ($grossAmount > $netAmount && $netAmount > 0)
            ? round($grossAmount - $netAmount, 2)
            : 0;

        DB::table('payments')->where('id', $editRequest->payment_id)->update([
            'collection_receipt_number' => $proposedData['collection_receipt_number'],
            'collection_receipt_date'   => $proposedData['collection_receipt_date'],
            'deposit_date'              => $proposedData['collection_receipt_date'],
            'payment_posting_date'      => $proposedData['payment_posting_date'],
            'payment_date'              => $proposedData['payment_posting_date'],
            'dr_no'                     => $proposedData['dr_no'],
            'invoice_no'                => $proposedData['invoice_no'],
            'amount'                    => $grossAmount,
            'gross_amount'              => $grossAmount,
            'tax'                       => $ewtAmount,
            'ewt'                       => $ewtAmount,
            'net'                       => $netAmount,
            'check_amount'              => $netAmount,
            'payment_method'            => $proposedData['payment_method'],
            'bank'                      => $proposedData['bank'] ?? null,
            'reference_no'              => $proposedData['reference_no'] ?? null,
            'payment_notes'             => $proposedData['payment_notes'] ?? null,
        ]);

        DB::table('payment_edit_requests')->where('id', $requestId)->update([
            'status'           => 'Approved',
            'reviewed_by'      => auth()->id(),
            'reviewed_by_name' => auth()->user()->name,
            'reviewed_at'      => now(),
            'updated_at'       => now(),
        ]);

        return back()->with('success', 'Edit request approved and payment updated.');
    }

    /**
     * Reject an edit request
     */
    public function rejectEditRequest(Request $request, $requestId)
    {
        $editRequest = DB::table('payment_edit_requests')->where('id', $requestId)->first();
        if (!$editRequest || $editRequest->status !== 'Pending') {
            return back()->with('error', 'Edit request not found or already processed.');
        }

        DB::table('payment_edit_requests')->where('id', $requestId)->update([
            'status'           => 'Rejected',
            'reviewed_by'      => auth()->id(),
            'reviewed_by_name' => auth()->user()->name,
            'review_notes'     => $request->input('review_notes'),
            'reviewed_at'      => now(),
            'updated_at'       => now(),
        ]);

        return back()->with('success', 'Edit request rejected.');
    }

    // ─── IT-only: Manage Payments ─────────────────────────────────────────────

    public function managePayments(Request $request)
    {
        if (!auth()->user()->isAdminUser()) {
            abort(403, 'Unauthorized: Only IT can access payment management.');
        }

        $dateFrom    = $request->input('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo      = $request->input('date_to',   now()->format('Y-m-d'));
        $customer    = $request->input('customer');
        $drFilter    = $request->input('dr_no');
        $crFilter    = $request->input('cr_no');
        $statusFilter= $request->input('status');
        $perPage     = 50;

        $query = DB::table('payments')
            ->whereBetween('payment_posting_date', [$dateFrom, $dateTo . ' 23:59:59'])
            ->orderBy('payment_posting_date', 'desc')
            ->orderBy('id', 'desc');

        if ($customer) {
            $query->where(function($q) use ($customer) {
                $q->where('customer_name', 'LIKE', "%{$customer}%")
                  ->orWhere('customer_code', 'LIKE', "%{$customer}%");
            });
        }
        if ($drFilter)  $query->whereRaw('TRIM(dr_no) LIKE ?', ["%{$drFilter}%"]);
        if ($crFilter)  $query->where('collection_receipt_number', 'LIKE', "%{$crFilter}%");
        if ($statusFilter) $query->where('status', $statusFilter);

        $payments = $query->paginate($perPage)->withQueryString();

        return view('payments.manage', compact(
            'payments', 'dateFrom', 'dateTo', 'customer', 'drFilter', 'crFilter', 'statusFilter'
        ));
    }

    public function destroy(Request $request, $id)
    {
        if (!auth()->user()->isAdminUser()) {
            abort(403, 'Unauthorized: Only IT can delete payments.');
        }

        $request->validate(['delete_reason' => 'required|string|max:500']);

        $payment = DB::table('payments')->where('id', $id)->first();
        if (!$payment) {
            return redirect()->route('payments.manage')->with('error', 'Payment not found.');
        }

        // Delete the payment first so the remaining-payments query reflects reality
        DB::table('payments')->where('id', $id)->delete();

        // Re-sync ar_aging status after deletion
        $arRecord = DB::table('ar_aging')
            ->where(function($q) use ($payment) {
                $q->whereRaw('TRIM(customer_code) = ?', [trim($payment->customer_code)]);
            })
            ->where(function($q) use ($payment) {
                $q->whereRaw('TRIM(dr_no) = ?', [trim($payment->dr_no)]);
            })
            ->first();

        if ($arRecord) {
            $originalBalance = (float)($arRecord->invoice_amount ?? $arRecord->net_ar_balance ?? 0);

            $remainingPaid = (float)DB::table('payments')
                ->whereRaw('TRIM(customer_code) = ?', [trim($payment->customer_code)])
                ->whereRaw('TRIM(dr_no) = ?', [trim($payment->dr_no)])
                ->sum(DB::raw('amount + COALESCE(discount_amount, 0) + COALESCE(ewt, 0)'));

            $remaining = max(0, $originalBalance - $remainingPaid);

            if ($remainingPaid <= 0) {
                // No payments left — fully restore
                DB::table('ar_aging')->where('id', $arRecord->id)->update([
                    'net_ar_balance' => $originalBalance,
                    'status'         => null,
                    'updated_at'     => now(),
                ]);
            } elseif ($remaining <= 10) {
                // Still fully paid by other payments
                DB::table('ar_aging')->where('id', $arRecord->id)->update([
                    'net_ar_balance' => 0,
                    'status'         => 'Paid',
                    'updated_at'     => now(),
                ]);
            } else {
                // Still partially paid
                DB::table('ar_aging')->where('id', $arRecord->id)->update([
                    'net_ar_balance' => $originalBalance,
                    'status'         => 'Partial',
                    'updated_at'     => now(),
                ]);
            }
        }

        // Log the deletion
        try {
            DB::table('activity_log')->insert([
                'user_id'     => auth()->id(),
                'user_name'   => auth()->user()->name,
                'action'      => 'delete_payment',
                'description' => "Deleted payment CR#{$payment->collection_receipt_number} DR#{$payment->dr_no} (₱" . number_format((float)$payment->amount, 2) . ") — Reason: " . $request->delete_reason,
                'created_at'  => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Activity log failed on payment delete', ['error' => $e->getMessage()]);
        }

        Log::info('Payment permanently deleted', [
            'payment_id' => $id,
            'cr_no'      => $payment->collection_receipt_number,
            'dr_no'      => $payment->dr_no,
            'amount'     => $payment->amount,
            'deleted_by' => auth()->user()->name,
            'reason'     => $request->delete_reason,
        ]);

        $redirect = $request->input('redirect_back', route('payments.manage'));
        return redirect($redirect)->with('success', "Payment CR#{$payment->collection_receipt_number} has been permanently deleted.");
    }
}