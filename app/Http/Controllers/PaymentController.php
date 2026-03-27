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
            ->whereRaw('deliveries.dr_no = ?', [trim($search)])
            ->groupBy('deliveries.id', 'deliveries.customer_code', 'deliveries.customer_name', 'deliveries.request_delivery_date', 'deliveries.dr_no', 'deliveries.branch', 'deliveries.sales_executive', 'customers.whtrate')
            ->first();

        if ($exactDrMatch) {
            $customerData = $exactDrMatch;
            Log::info('✅ Found exact DR match in deliveries', [
                'dr_no' => trim($search),
                'delivery_amount' => $exactDrMatch->delivery_amount ?? 'NULL',
                'customer_name' => $exactDrMatch->client_name
            ]);
        } else {
            Log::warning('⚠️ Exact DR match NOT found in deliveries', [
                'search_term' => trim($search),
                'expected_status' => 'Delivered',
                'excluded_if' => 'is_pulled_out = 1'
            ]);
        }

        // If no exact DR match found, search in ar_aging table
        if (!$customerData) {
            // Search in ar_aging table - only sum net_ar_balance where > 0 (outstanding)
            // ✅ NEW: Join with customers table to get whtrate
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
                'customers.whtrate' // ✅ NEW: Get customer's tax rate
            )
            ->where(function($query) use ($search) {
                $query->whereRaw('TRIM(ar_aging.customer_code) = ?', [trim($search)])
                      ->orWhereRaw('TRIM(ar_aging.customer_code) LIKE ?', ['%' . trim($search) . '%'])
                      ->orWhereRaw('TRIM(ar_aging.client_name) LIKE ?', ['%' . trim($search) . '%'])
                      ->orWhereRaw('TRIM(ar_aging.dr_no) = ?', [trim($search)]) // ✅ NEW: Search by DR number
                      ->orWhereRaw('TRIM(ar_aging.dr_no) LIKE ?', ['%' . trim($search) . '%']); // ✅ NEW: Partial DR match
            })
            ->groupBy('ar_aging.customer_code', 'ar_aging.client_name', 'customers.whtrate')
            ->first();

            Log::info('Search result', ['found' => $customerData ? 'yes' : 'no']);
        }

        // ✅ NEW: If not found in either search, try deliveries table with broader criteria
        // ✅ FETCH AMOUNT FROM DELIVERY ITEMS (sum of item amounts), NOT SALES ORDER
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
                ->where('deliveries.status', 'Delivered')  // ✅ Only find delivered orders
                ->where('deliveries.is_pulled_out', '!=', 1)  // Exclude pulled out deliveries
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
                $customerData = $delivery;
                Log::info('Customer found in deliveries table (pending invoicing)', [
                    'customer_code' => $customerData->customer_code,
                    'delivery_date' => $customerData->request_delivery_date ?? 'N/A',
                    'search_term' => $search
                ]);
            } else {
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

        // ✅ If a delivery was found (pending invoicing), create a synthetic invoice entry for it
        $outstandingInvoices = collect();
        if (isset($customerData->dr_no) && $customerData->total_outstanding == 0) {
            // First check if ar_aging already has this DR — use its net_ar_balance if so
            // Try both the delivery's dr_no AND the original search term (handles format mismatches)
            $drNoFromDelivery = trim($customerData->dr_no);
            $drNoFromSearch = trim($search);

            $arAgingForDr = DB::table('ar_aging')
                ->where(function($q) use ($drNoFromDelivery, $drNoFromSearch) {
                    $q->whereRaw('TRIM(dr_no) = ?', [$drNoFromDelivery]);
                    if ($drNoFromSearch !== $drNoFromDelivery) {
                        $q->orWhereRaw('TRIM(dr_no) = ?', [$drNoFromSearch]);
                    }
                    // Also handle Excel-exported decimals like "136501.0"
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
                // Use actual AR aging data; resolve net_ar_balance for each record
                $arAgingForDr = $arAgingForDr->map(function($record) {
                    $netAr = (float)($record->net_ar_balance ?? 0);
                    if ($netAr <= 0) {
                        // Fallback 1: invoice_amount - settled_invoice_amount
                        $netAr = max(0,
                            (float)($record->invoice_amount ?? 0)
                            - (float)($record->settled_invoice_amount ?? 0)
                        );
                    }
                    if ($netAr <= 0) {
                        // Fallback 2: derive from net_of_cwt, check_amount, others_amount, ar_adjustments
                        // others_amount can be a credit (payment) or debit (additional charge):
                        //   - Credit: net_of_cwt - ar_adj - check - others  (when check < net_of_cwt)
                        //   - Debit:  net_of_cwt - ar_adj + others - check  (when check > net_of_cwt)
                        $netOfCwt  = (float)($record->net_of_cwt ?? 0);
                        $checkAmt  = (float)($record->check_amount ?? 0);
                        $othersAmt = (float)($record->others_amount ?? 0);
                        $adjAmt    = (float)($record->ar_adjustments ?? 0);
                        if ($netOfCwt > 0) {
                            $computed = $netOfCwt - $adjAmt - $checkAmt - $othersAmt;
                            if ($computed < 0) {
                                // others_amount is likely a debit/charge, not a payment
                                $computed = $netOfCwt - $adjAmt + $othersAmt - $checkAmt;
                            }
                            $netAr = max(0, $computed);
                        }
                    }
                    $record->net_ar_balance = $netAr;
                    return $record;
                });

                $outstandingInvoices = $arAgingForDr;
            } else {
                // No AR aging record yet — use full delivery amount as outstanding
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
            // Get outstanding invoices from ar_aging (include fallback columns)
            $arQuery = DB::table('ar_aging')
                ->select(
                    'dr_no', 'invoice_no', 'invoice_date', 'invoice_amount',
                    'settled_invoice_amount', 'net_ar_balance', 'gross_ar_balance',
                    'net_of_cwt', 'check_amount', 'others_amount', 'ar_adjustments',
                    'terms', 'age', 'due_date', 'status'
                )
                ->where('customer_code', $customerData->customer_code)
                ->where(function($q) {
                    $q->whereNull('status')
                      ->orWhere('status', '')
                      ->orWhere('status', '!=', 'Paid');
                });

            if (isset($customerData->dr_no)) {
                $arQuery->where('dr_no', $customerData->dr_no);
            }

            $outstandingInvoices = $arQuery
                ->orderBy('invoice_date', 'desc')
                ->get()
                ->map(function($record) {
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
                })
                ->filter(fn($r) => $r->net_ar_balance > 0)
                ->values();
        }

        // Subtract existing payments from outstanding balances
        $existingPayments = DB::table('payments')
            ->where(function($q) use ($customerData) {
                $q->where('customer_code', $customerData->customer_code);
                if (!empty($customerData->client_name)) {
                    $q->orWhereRaw('TRIM(LOWER(customer_name)) = ?', [trim(strtolower($customerData->client_name))]);
                }
            })
            ->select('dr_no', 'invoice_no', DB::raw('SUM(amount) as total_paid'))
            ->groupBy('dr_no', 'invoice_no')
            ->get()
            ->keyBy(function($p) { return trim($p->dr_no ?? '') . '|' . trim($p->invoice_no ?? ''); });

        $outstandingInvoices = $outstandingInvoices->map(function($record) use ($existingPayments) {
            $key = trim($record->dr_no ?? '') . '|' . trim($record->invoice_no ?? '');
            if ($existingPayments->has($key)) {
                $paid = (float)$existingPayments[$key]->total_paid;
                $record->net_ar_balance = max(0, $record->net_ar_balance - $paid);
                $record->total_paid = $paid;
                if ($record->net_ar_balance <= 0) {
                    $record->status = 'Paid';
                } else {
                    $record->status = 'Partial';
                }
            }
            return $record;
        })->filter(fn($r) => $r->net_ar_balance > 0)->values();

        Log::info('Outstanding invoices loaded', [
            'count' => $outstandingInvoices->count(),
            'sample_dr_numbers' => $outstandingInvoices->take(3)->pluck('dr_no')->toArray()
        ]);

        // Calculate actual outstanding balance from invoices (handles pending deliveries)
        $actualOutstandingBalance = $outstandingInvoices->sum('net_ar_balance');

        return response()->json([
            'success' => true,
            'customer' => [
                'code' => $customerData->customer_code,
                'name' => $customerData->client_name,
                'outstanding_balance' => number_format($actualOutstandingBalance, 2, '.', ''),
                'gross_balance' => number_format($customerData->gross_balance, 2, '.', ''),
                'total_invoice' => number_format($customerData->total_invoice, 2, '.', ''),
                'total_settled' => number_format($customerData->total_settled, 2, '.', ''),
                'branch' => $customerData->branch ?? 'N/A',
                'sales_executive' => $customerData->sales_executive ?? 'N/A',
                'terms' => $customerData->terms ?? 'N/A',
                'invoice_count' => $customerData->invoice_count,
                'outstanding_invoice_count' => $customerData->outstanding_invoice_count,
                'whtrate' => $customerData->whtrate ?? 0, // ✅ Tax rate for auto-calculation
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
    'customer_code' => 'required|string|max:255',
    'customer_name' => 'required|string|max:255',
    'dr_number' => 'required|string|max:255',
    'invoice_no' => 'nullable|string|max:255',
    'collection_receipt_number' => 'required|string|max:255',
    'collection_receipt_date' => 'required|date',
    'payment_posting_date' => 'required|date',
    'amount' => 'required|numeric|min:0.01',
    'tax' => 'nullable|numeric|min:0',
    'net' => 'nullable|numeric|min:0',
    'payment_means' => 'required|array',
    'payment_means.type' => 'required|in:check,bank_transfer,cash',
    'payment_means.gl_account' => 'nullable|string',
    'payment_means.gl_account_id' => 'nullable|integer',
    'payment_means.gl_account_name' => 'nullable|string',
    'payment_means.due_date' => 'nullable|date',
    'payment_means.amount' => 'nullable|numeric',
    'payment_means.bank_name' => 'nullable|string',
    'payment_means.check_number' => 'nullable|string',
    'payment_means.transfer_date' => 'nullable|date',
    'payment_means.reference' => 'nullable|string',
    'payment_notes' => 'nullable|string|max:1000',
    'invoice_outstanding' => 'nullable|numeric|min:0',
    'credit_applied' => 'nullable|numeric|min:0',
    'credit_from_payment_id' => 'nullable|integer',
    'credits' => 'nullable|array',
    'credits.*.credit_source_payment_id' => 'required_with:credits|integer',
    'credits.*.amount' => 'required_with:credits|numeric|min:0.01',
]);

        Log::info('Validation passed', ['validated_data' => $validated]);

        // Check for duplicate collection_receipt_number
        $exists = DB::table('payments')
            ->where('collection_receipt_number', $validated['collection_receipt_number'])
            ->exists();

        if ($exists) {
            Log::warning('Duplicate collection receipt number', [
                'receipt_number' => $validated['collection_receipt_number']
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Collection receipt number already exists.',
                'errors' => ['collection_receipt_number' => ['This receipt number has already been used.']]
            ], 422);
        }

        // ✅ Use direct DB insert with DR number
        $grossAmount = (float)$validated['amount'];
        $ewtAmount   = (float)($validated['tax'] ?? 0);
        $netAmount   = (float)($validated['net'] ?? ($grossAmount - $ewtAmount));
        $invoiceOutstanding = isset($validated['invoice_outstanding']) ? (float)$validated['invoice_outstanding'] : null;
        // Support multiple credits: sum from credits array, fallback to legacy single credit
        $creditsArray = $validated['credits'] ?? [];
        $creditApplied = 0;
        $creditFromId  = null;
        if (!empty($creditsArray)) {
            $creditApplied = array_sum(array_column($creditsArray, 'amount'));
            // Keep first credit source as legacy fallback
            $creditFromId = $creditsArray[0]['credit_source_payment_id'] ?? null;
        } else {
            $creditApplied = (float)($validated['credit_applied'] ?? 0);
            $creditFromId  = $validated['credit_from_payment_id'] ?? null;
        }

        // Calculate overpayment: if paid more than outstanding
        $overpayment = 0;
        if ($invoiceOutstanding !== null && $grossAmount > $invoiceOutstanding && $invoiceOutstanding > 0) {
            $overpayment = $grossAmount - $invoiceOutstanding;
        }

        $paymentId = DB::table('payments')->insertGetId([
    'customer_code' => $validated['customer_code'],
    'customer_name' => $validated['customer_name'],
    'dr_no' => $validated['dr_number'],
    'invoice_no' => $validated['invoice_no'],
    'collection_receipt_number' => $validated['collection_receipt_number'],
    'collection_receipt_date' => $validated['collection_receipt_date'],
    'deposit_date' => $validated['collection_receipt_date'],
    'payment_posting_date' => $validated['payment_posting_date'],
    'payment_date' => $validated['payment_posting_date'],
    'amount' => $grossAmount,
    'tax' => $ewtAmount,
    'net' => $netAmount,
    // Display columns (used by AR profile collections tab)
    'gross_amount' => $grossAmount,
    'ewt' => $ewtAmount,
    'check_amount' => $netAmount,
    'other_adjustment' => 0,
    'factoring' => 0,
    'status' => 'Posted',
    'signed_by' => auth()->user()->name ?? 'System',
    // Credit balance tracking
    'invoice_outstanding' => $invoiceOutstanding,
    'overpayment' => $overpayment,
    'credit_applied' => $creditApplied,
    'credit_from_payment_id' => $creditFromId,
    // Payment means
    'payment_method' => $validated['payment_means']['type'],
    'payment_means_data' => json_encode($validated['payment_means']),
    'payment_notes' => $validated['payment_notes'] ?? null,
    'created_by' => auth()->user()->name ?? 'System',
    'bank' => $validated['payment_means']['bank_name'] ?? null,
    'reference_no' => $validated['payment_means']['reference'] ?? $validated['payment_means']['check_number'] ?? null,
    'created_at' => now(),
]);

        // Insert individual credit application records
        if (!empty($creditsArray)) {
            foreach ($creditsArray as $credit) {
                DB::table('payment_credit_applications')->insert([
                    'payment_id' => $paymentId,
                    'credit_source_payment_id' => $credit['credit_source_payment_id'],
                    'amount' => $credit['amount'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } elseif ($creditFromId && $creditApplied > 0) {
            // Legacy single credit — also save to junction table
            DB::table('payment_credit_applications')->insert([
                'payment_id' => $paymentId,
                'credit_source_payment_id' => $creditFromId,
                'amount' => $creditApplied,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Log::info('Payment inserted successfully', [
            'payment_id' => $paymentId,
            'customer_code' => $validated['customer_code'],
            'dr_number' => $validated['dr_number'],
            'amount' => $validated['amount'],
            'credits_applied' => count($creditsArray),
        ]);

        // ✅ UPDATE AR AGING BALANCES BY DR NUMBER
        $this->updateArAgingBalanceByDR(
            $validated['customer_code'], 
            $validated['dr_number'], 
            $validated['amount']
        );

        $payment = DB::table('payments')->where('id', $paymentId)->first();

        // Create activity log
        if (class_exists('\App\Models\Activity')) {
            try {
                DB::table('activities')->insert([
                    'user_name' => auth()->user()->name ?? 'System',
                    'action' => 'Created',
                    'item' => 'Payment: ' . $validated['collection_receipt_number'],
                    'target' => $validated['customer_name'] . ' - DR: ' . $validated['dr_number'],
                    'type' => 'Payment',
                    'message' => "Created payment entry: {$validated['collection_receipt_number']} for DR: {$validated['dr_number']} - Amount: ₱" . number_format($validated['amount'], 2),
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
                'id' => $payment->id,
                'customer_code' => $payment->customer_code,
                'customer_name' => $payment->customer_name,
                'dr_no' => $payment->dr_no,
                'invoice_no' => $payment->invoice_no,
                'collection_receipt_number' => $payment->collection_receipt_number,
                'collection_receipt_date' => $payment->collection_receipt_date,
                'payment_posting_date' => $payment->payment_posting_date,
                'amount' => $payment->amount,
                'tax' => $payment->tax,
                'payment_means' => 'required|array',
                'payment_means.type' => 'required|in:check,bank_transfer,cash',
            ]
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('Validation failed', [
            'errors' => $e->errors()
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        Log::error('Payment store failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'request_data' => $request->all()
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Failed to save payment: ' . $e->getMessage()
        ], 500);
    }
}

// ✅ NEW: Update AR Aging by specific DR number
private function updateArAgingBalanceByDR($customerCode, $drNumber, $paymentAmount)
{
    try {
        Log::info('Updating AR Aging by DR', [
            'customer_code' => $customerCode,
            'dr_number' => $drNumber,
            'payment_amount' => $paymentAmount
        ]);

        // Find the specific invoice by DR number (flexible match)
        // Find unpaid invoice for this DR (handles multiple invoices per DR)
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
            // All invoices for this DR may already be paid
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
            Log::warning('Invoice not found in ar_aging', [
                'customer_code' => $customerCode,
                'dr_number' => $drNumber
            ]);
            return false;
        }

        // Compute actual outstanding: use net_ar_balance if > 0, else derive from invoice_amount
        $currentBalance = (float)($invoice->net_ar_balance ?? 0);
        if ($currentBalance <= 0) {
            $currentBalance = max(0,
                (float)($invoice->invoice_amount ?? 0)
                - (float)($invoice->cwt ?? 0)
                - (float)($invoice->settled_invoice_amount ?? 0)
                + (float)($invoice->ewt ?? 0)
            );
        }

        if ($currentBalance <= 0) {
            Log::info('Invoice already fully settled', ['invoice_id' => $invoice->id]);
            return false;
        }

        if ($paymentAmount >= $currentBalance) {
            // Full payment
            DB::table('ar_aging')
                ->where('id', $invoice->id)
                ->update([
                    'net_ar_balance' => 0,
                    'settled_invoice_amount' => DB::raw('COALESCE(settled_invoice_amount, 0) + ' . $currentBalance),
                    'status' => 'Paid',
                    'updated_at' => now()
                ]);

            Log::info('Invoice fully paid', [
                'invoice_id' => $invoice->id,
                'dr_number' => $drNumber,
                'amount_applied' => $currentBalance
            ]);
        } else {
            // Partial payment
            $newBalance = $currentBalance - $paymentAmount;
            DB::table('ar_aging')
                ->where('id', $invoice->id)
                ->update([
                    'net_ar_balance' => $newBalance,
                    'settled_invoice_amount' => DB::raw('COALESCE(settled_invoice_amount, 0) + ' . $paymentAmount),
                    'status' => 'Partial',
                    'updated_at' => now()
                ]);

            Log::info('Invoice partially paid', [
                'invoice_id' => $invoice->id,
                'dr_number' => $drNumber,
                'amount_applied' => $paymentAmount,
                'remaining_balance' => $newBalance
            ]);
        }

        return true;

    } catch (\Exception $e) {
        Log::error('Failed to update AR Aging by DR', [
            'error' => $e->getMessage(),
            'customer_code' => $customerCode,
            'dr_number' => $drNumber,
            'payment_amount' => $paymentAmount
        ]);
        return false;
    }
}
    public function collectionReport(Request $request)
    {
        try {
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
            $customerFilter = $request->input('customer', '');

            // Get actual collections from payments table
            $paymentsQuery = Payment::select(
                    'id',
                    'customer_code',
                    'customer_name',
                    'dr_no',
                    'invoice_no',
                    'collection_receipt_number',
                    'collection_receipt_date',
                    'payment_posting_date',
                    'payment_date',
                    'amount',
                    'gross_amount',
                    'tax',
                    'ewt',
                    'net',
                    'check_amount',
                    'payment_method',
                    'payment_option',
                    'payment_means_data',
                    'payment_notes',
                    'bank',
                    'reference_no',
                    'status',
                    'created_by',
                    'created_at'
                );

            if ($dateFrom) {
                $paymentsQuery->whereDate('payment_posting_date', '>=', $dateFrom);
            }

            if ($dateTo) {
                $paymentsQuery->whereDate('payment_posting_date', '<=', $dateTo);
            }

            if ($customerFilter) {
                $paymentsQuery->where(function($q) use ($customerFilter) {
                    $q->where('customer_code', 'LIKE', '%' . $customerFilter . '%')
                      ->orWhere('customer_name', 'LIKE', '%' . $customerFilter . '%');
                });
            }

            // ✅ Show only actual collections from payments table
            $payments = $paymentsQuery
                ->orderBy('payment_posting_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'payments' => $payments,
                'total_amount' => $payments->sum('amount'),
                'total_tax' => $payments->sum('tax'),
            ]);

        } catch (\Exception $e) {
            Log::error('Collection report failed', [
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load report'
            ], 500);
        }
    }

    public function export(Request $request)
    {
        try {
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
            $customerFilter = $request->input('customer', '');

            // ✅ Query 1: Manually entered payments
            $paymentsQuery = Payment::select(
                    DB::raw("'manual' as source"),
                    'customer_code',
                    'customer_name',
                    'dr_no',
                    'invoice_no',
                    'collection_receipt_number',
                    'collection_receipt_date',
                    'payment_posting_date',
                    'amount',
                    'tax',
                    'net'
                );

            if ($dateFrom) {
                $paymentsQuery->whereDate('payment_posting_date', '>=', $dateFrom);
            }

            if ($dateTo) {
                $paymentsQuery->whereDate('payment_posting_date', '<=', $dateTo);
            }

            if ($customerFilter) {
                $paymentsQuery->where(function($q) use ($customerFilter) {
                    $q->where('customer_code', 'LIKE', '%' . $customerFilter . '%')
                      ->orWhere('customer_name', 'LIKE', '%' . $customerFilter . '%');
                });
            }

            // ✅ Query 2: AR Aging records (invoiced items)
            $arAgingQuery = DB::table('ar_aging')
                ->select(
                    DB::raw("'invoiced' as source"),
                    'customer_code',
                    DB::raw("client_name as customer_name"),
                    'dr_no',
                    'invoice_no',
                    DB::raw("CONCAT('AR-', invoice_no) as collection_receipt_number"),
                    DB::raw("invoice_date as collection_receipt_date"),
                    DB::raw("invoice_date as payment_posting_date"),
                    'invoice_amount as amount',
                    DB::raw('0 as tax'),
                    DB::raw('NULL as net')
                );

            if ($dateFrom) {
                $arAgingQuery->whereDate('invoice_date', '>=', $dateFrom);
            }

            if ($dateTo) {
                $arAgingQuery->whereDate('invoice_date', '<=', $dateTo);
            }

            if ($customerFilter) {
                $arAgingQuery->where(function($q) use ($customerFilter) {
                    $q->where('customer_code', 'LIKE', '%' . $customerFilter . '%')
                      ->orWhere('client_name', 'LIKE', '%' . $customerFilter . '%');
                });
            }

            // ✅ Query 3: Delivered items pending invoicing
            $deliveriesQuery = DB::table('deliveries')
                ->select(
                    DB::raw("'pending_invoice' as source"),
                    'customer_code',
                    'customer_name',
                    'dr_no',
                    DB::raw("'Pending Invoice' as invoice_no"),
                    DB::raw("dr_no as collection_receipt_number"),
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
                        ->whereColumn('ar_aging.dr_no', '=', 'deliveries.dr_no');
                });

            if ($dateFrom) {
                $deliveriesQuery->whereDate('request_delivery_date', '>=', $dateFrom);
            }

            if ($dateTo) {
                $deliveriesQuery->whereDate('request_delivery_date', '<=', $dateTo);
            }

            if ($customerFilter) {
                $deliveriesQuery->where(function($q) use ($customerFilter) {
                    $q->where('customer_code', 'LIKE', '%' . $customerFilter . '%')
                      ->orWhere('customer_name', 'LIKE', '%' . $customerFilter . '%');
                });
            }

            // ✅ Combine all queries
            $payments = $paymentsQuery
                ->union($arAgingQuery)
                ->union($deliveriesQuery)
                ->orderBy('payment_posting_date', 'desc')
                ->get();

            $filename = 'collection_report_' . now()->format('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
                'Pragma' => 'public',
            ];

            $callback = function () use ($payments) {
                $file = fopen('php://output', 'w');

                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

                fputcsv($file, [
                    'Customer Code',
                    'Customer Name',
                    'Collection Receipt Number',
                    'Collection Receipt Date',
                    'Payment Posting Date',
                    'Amount',
                    'Tax',
                    'Net', // ✅ NEW: Add Net column
                    'Payment Option',
                    'Notes'
                ]);

                foreach ($payments as $payment) {
                    fputcsv($file, [
                        $payment->customer_code,
                        $payment->customer_name,
                        $payment->collection_receipt_number,
                        Carbon::parse($payment->collection_receipt_date)->format('m/d/Y'),
                        Carbon::parse($payment->payment_posting_date)->format('m/d/Y'),
                        number_format($payment->amount, 2, '.', ''),
                        number_format($payment->tax ?? 0, 2, '.', ''),
                        number_format($payment->net ?? 0, 2, '.', ''), // ✅ NEW: Include net
                        $payment->payment_option,
                        $payment->payment_notes ?? '',
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Payment export failed', [
                'error' => $e->getMessage()
            ]);
            abort(500, 'Failed to export: ' . $e->getMessage());
        }
    }

    private function updateArAgingBalance($customerCode, $paymentAmount)
    {
        try {
            // Get all outstanding records for this customer, ordered by oldest first
            $outstandingRecords = DB::table('ar_aging')
                ->where('customer_code', $customerCode)
                ->where('net_ar_balance', '>', 0)
                ->orderBy('invoice_date', 'asc')
                ->get();

            $remainingPayment = $paymentAmount;

            foreach ($outstandingRecords as $record) {
                if ($remainingPayment <= 0) {
                    break;
                }

                $currentBalance = $record->net_ar_balance;

                if ($remainingPayment >= $currentBalance) {
                    // Payment covers entire balance
                    DB::table('ar_aging')
                        ->where('id', $record->id)
                        ->update([
                            'net_ar_balance' => 0,
                            'settled_invoice_amount' => DB::raw('settled_invoice_amount + ' . $currentBalance),
                            'status' => 'Paid'
                        ]);

                    $remainingPayment -= $currentBalance;
                } else {
                    // Payment covers partial balance
                    DB::table('ar_aging')
                        ->where('id', $record->id)
                        ->update([
                            'net_ar_balance' => DB::raw('net_ar_balance - ' . $remainingPayment),
                            'settled_invoice_amount' => DB::raw('settled_invoice_amount + ' . $remainingPayment),
                            'status' => 'Partial'
                        ]);

                    $remainingPayment = 0;
                }
            }

            Log::info('AR Aging updated successfully', [
                'customer_code' => $customerCode,
                'payment_amount' => $paymentAmount,
                'remaining_payment' => $remainingPayment
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to update AR Aging', [
                'error' => $e->getMessage(),
                'customer_code' => $customerCode,
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
        $statusFilter = $request->input('status'); // 'outstanding' or null

        if (!$customerCode) {
            return response()->json([
                'success' => false,
                'message' => 'Customer code is required'
            ], 400);
        }

        Log::info('Getting customer history', [
            'customer_code' => $customerCode,
            'status_filter' => $statusFilter
        ]);

        // ✅ Get outstanding invoices from ar_aging table instead of payments table
        if ($statusFilter === 'outstanding') {
            $outstandingInvoices = DB::table('ar_aging')
                ->select(
                    'invoice_date as deposit_date',
                    'invoice_no',
                    'dr_no',
                    'customer_code',
                    'client_name as customer_name',
                    'branch',
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

            // ✅ Compute actual outstanding balance (handles net_ar_balance=0)
            $outstandingInvoices = $outstandingInvoices->map(function($record) {
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
                        if ($computed < 0) {
                            $computed = $netOfCwt - $adjAmt + $othersAmt - $checkAmt;
                        }
                        $netAr = max(0, $computed);
                    }
                }
                $record->check_amount = $netAr;
                $record->gross_amount = (float)($record->invoice_amount ?? $record->gross_amount);
                return $record;
            })->filter(fn($r) => $r->check_amount > 0)->values();

            // ✅ Subtract existing payments from outstanding balances (payment-aware)
            $existingPayments = DB::table('payments')
                ->where('customer_code', $customerCode)
                ->select('dr_no', 'invoice_no', DB::raw('SUM(amount) as total_paid'))
                ->groupBy('dr_no', 'invoice_no')
                ->get()
                ->keyBy(function($p) { return trim($p->dr_no ?? '') . '|' . trim($p->invoice_no ?? ''); });

            $outstandingInvoices = $outstandingInvoices->map(function($record) use ($existingPayments) {
                $key = trim($record->dr_no ?? '') . '|' . trim($record->invoice_no ?? '');
                if ($existingPayments->has($key)) {
                    $paid = (float)$existingPayments[$key]->total_paid;
                    $record->check_amount = max(0, $record->check_amount - $paid);
                    $record->total_paid = $paid;
                    if ($record->check_amount <= 0) {
                        $record->status = 'Paid';
                    } else {
                        $record->status = 'Partial';
                    }
                }
                return $record;
            })->filter(fn($r) => $r->check_amount > 0)->values();

            Log::info('Outstanding invoices found (payment-aware)', [
                'count' => $outstandingInvoices->count(),
                'total_amount' => $outstandingInvoices->sum('check_amount')
            ]);

            return response()->json([
                'success' => true,
                'payments' => $outstandingInvoices,
                'total_outstanding' => $outstandingInvoices->sum('check_amount')
            ]);
        }

        // ✅ Original behavior: Get all payment history (if no status filter)
        $customerName = DB::table('ar_aging')
            ->where('customer_code', $customerCode)
            ->value('client_name');

        if (!$customerName) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        $payments = Payment::where(function($query) use ($customerCode, $customerName) {
                $query->where('customer_code', $customerCode)
                      ->orWhere('customer_name', 'LIKE', '%' . $customerName . '%');
            })
            ->orderBy('payment_posting_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'payments' => $payments
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to get customer payment history', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
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
                // Show all records for a specific CR number
                $payments = Payment::where('collection_receipt_number', $crNumber)
                    ->orderBy('created_at', 'asc')
                    ->get();

                $title = "CR Number: {$crNumber} ({$payments->count()} records)";
            } else {
                // Show all CR numbers that have duplicates
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
            Log::error('Failed to view duplicate CR numbers', [
                'error' => $e->getMessage()
            ]);

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

        // Decode payment means data
        $paymentMeans = json_decode($payment->payment_means_data ?? '{}', true);

        // Get credit sources for this payment (from junction table, fallback to legacy)
        $creditSources = DB::table('payment_credit_applications')
            ->join('payments', 'payments.id', '=', 'payment_credit_applications.credit_source_payment_id')
            ->where('payment_credit_applications.payment_id', $payment->id)
            ->select('payments.id', 'payments.collection_receipt_number', 'payments.invoice_no', 'payments.dr_no', 'payment_credit_applications.amount as credit_amount')
            ->get();

        // Legacy fallback: if no junction records but legacy credit exists
        $creditSource = null;
        if ($creditSources->isEmpty() && $payment->credit_from_payment_id) {
            $creditSource = DB::table('payments')
                ->where('id', $payment->credit_from_payment_id)
                ->first();
        }

        // Get payments that used credit from this overpayment
        $creditApplications = collect();
        if ($payment->overpayment > 0) {
            // Check junction table first
            $junctionApps = DB::table('payment_credit_applications')
                ->join('payments', 'payments.id', '=', 'payment_credit_applications.payment_id')
                ->where('payment_credit_applications.credit_source_payment_id', $payment->id)
                ->select('payments.*', 'payment_credit_applications.amount as credit_applied_amount')
                ->get();

            if ($junctionApps->isNotEmpty()) {
                $creditApplications = $junctionApps;
            } else {
                $creditApplications = DB::table('payments')
                    ->where('credit_from_payment_id', $payment->id)
                    ->get();
            }
        }

        // Calculate remaining credit
        $remainingCredit = 0;
        if ($payment->overpayment > 0) {
            $usedJunction = (float)DB::table('payment_credit_applications')
                ->where('credit_source_payment_id', $payment->id)
                ->sum('amount');
            $usedLegacy = (float)DB::table('payments')
                ->where('credit_from_payment_id', $payment->id)
                ->sum('credit_applied');
            $usedCredit = max($usedJunction, $usedLegacy);
            $remainingCredit = (float)$payment->overpayment - $usedCredit;
        }

        return view('payments.show', compact('payment', 'paymentMeans', 'creditSource', 'creditSources', 'creditApplications', 'remainingCredit'));
    }

    /**
     * Get available credit balance for a customer
     */
    /**
     * Check if a DR number is already paid, partially paid, or still outstanding.
     */
    public function checkDRStatus(Request $request)
    {
        $customerCode = $request->input('customer_code');
        $drNo = $request->input('dr_no');

        if (!$customerCode || !$drNo) {
            return response()->json(['status' => 'not_found']);
        }

        // Check ar_aging for this DR
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

        if (!$arRecord) {
            return response()->json(['status' => 'not_found']);
        }

        // Compute the original outstanding from ar_aging
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

        // Sum all existing payments for this DR
        $totalPaid = (float)DB::table('payments')
            ->where(function($q) use ($customerCode) {
                $q->where('customer_code', $customerCode)
                  ->orWhereRaw('TRIM(customer_code) = ?', [trim($customerCode)]);
            })
            ->where(function($q) use ($drNo) {
                $q->where('dr_no', $drNo)
                  ->orWhereRaw('TRIM(dr_no) = ?', [trim($drNo)]);
            })
            ->sum('amount');

        $remaining = max(0, $originalOutstanding - $totalPaid);

        if ($remaining <= 0 && $totalPaid > 0) {
            return response()->json([
                'status' => 'paid',
                'total_paid' => $totalPaid,
                'invoice_no' => $arRecord->invoice_no ?? null,
            ]);
        } elseif ($totalPaid > 0 && $remaining > 0) {
            return response()->json([
                'status' => 'partial',
                'total_paid' => $totalPaid,
                'remaining' => $remaining,
                'invoice_no' => $arRecord->invoice_no ?? null,
            ]);
        }

        // If no payments and original outstanding is 0, it might be settled at import
        if ($originalOutstanding <= 0) {
            return response()->json([
                'status' => 'paid',
                'total_paid' => 0,
                'invoice_no' => $arRecord->invoice_no ?? null,
            ]);
        }

        return response()->json(['status' => 'not_found']);
    }

    public function getCustomerCredits(Request $request)
    {
        $customerCode = $request->input('customer_code');
        $customerName = $request->input('customer_name');

        if (!$customerCode && !$customerName) {
            return response()->json(['success' => false, 'credits' => [], 'total' => 0]);
        }

        // Find payments with unused overpayments for this customer
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

        $credits = [];
        $totalAvailable = 0;

        foreach ($overpayments as $op) {
            // Check both legacy single-credit and new multi-credit junction table
            $usedLegacy = (float)Payment::where('credit_from_payment_id', $op->id)
                ->sum('credit_applied');
            $usedJunction = (float)DB::table('payment_credit_applications')
                ->where('credit_source_payment_id', $op->id)
                ->sum('amount');
            $used = max($usedLegacy, $usedJunction); // Use the higher value to avoid double-counting
            $remaining = (float)$op->overpayment - $used;

            if ($remaining > 0) {
                $credits[] = [
                    'payment_id' => $op->id,
                    'collection_receipt_number' => $op->collection_receipt_number,
                    'date' => $op->collection_receipt_date,
                    'original_amount' => (float)$op->amount,
                    'overpayment' => (float)$op->overpayment,
                    'remaining_credit' => $remaining,
                    'invoice_no' => $op->invoice_no,
                    'dr_no' => $op->dr_no,
                ];
                $totalAvailable += $remaining;
            }
        }

        return response()->json([
            'success' => true,
            'credits' => $credits,
            'total' => $totalAvailable,
        ]);
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
            'collection_receipt_date' => 'required|date',
            'payment_posting_date' => 'required|date',
            'dr_no' => 'required|string|max:255',
            'invoice_no' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'net' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:check,bank_transfer,cash',
            'bank' => 'nullable|string|max:255',
            'reference_no' => 'nullable|string|max:255',
            'payment_notes' => 'nullable|string|max:1000',
        ]);

        // Check duplicate CR number (exclude self)
        $exists = DB::table('payments')
            ->where('collection_receipt_number', $validated['collection_receipt_number'])
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['collection_receipt_number' => 'This receipt number has already been used.'])->withInput();
        }

        $grossAmount = (float)$validated['amount'];
        $ewtAmount = (float)($validated['tax'] ?? 0);
        $netAmount = (float)($validated['net'] ?? ($grossAmount - $ewtAmount));

        DB::table('payments')->where('id', $id)->update([
            'collection_receipt_number' => $validated['collection_receipt_number'],
            'collection_receipt_date' => $validated['collection_receipt_date'],
            'deposit_date' => $validated['collection_receipt_date'],
            'payment_posting_date' => $validated['payment_posting_date'],
            'payment_date' => $validated['payment_posting_date'],
            'dr_no' => $validated['dr_no'],
            'invoice_no' => $validated['invoice_no'],
            'amount' => $grossAmount,
            'gross_amount' => $grossAmount,
            'tax' => $ewtAmount,
            'ewt' => $ewtAmount,
            'net' => $netAmount,
            'check_amount' => $netAmount,
            'payment_method' => $validated['payment_method'],
            'bank' => $validated['bank'],
            'reference_no' => $validated['reference_no'],
            'payment_notes' => $validated['payment_notes'],
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
            'collection_receipt_date' => 'required|date',
            'payment_posting_date' => 'required|date',
            'dr_no' => 'required|string|max:255',
            'invoice_no' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'net' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:check,bank_transfer,cash',
            'bank' => 'nullable|string|max:255',
            'reference_no' => 'nullable|string|max:255',
            'payment_notes' => 'nullable|string|max:1000',
            'edit_reason' => 'required|string|max:500',
            'attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx',
        ]);

        // Handle file upload
        $attachmentPath = null;
        $attachmentName = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentName = $file->getClientOriginalName();
            $attachmentPath = $file->store('payment-edit-attachments', 'public');
        }

        $originalData = [
            'collection_receipt_number' => $payment->collection_receipt_number,
            'collection_receipt_date' => $payment->collection_receipt_date,
            'payment_posting_date' => $payment->payment_posting_date,
            'dr_no' => $payment->dr_no,
            'invoice_no' => $payment->invoice_no,
            'amount' => $payment->amount,
            'tax' => $payment->tax,
            'net' => $payment->net,
            'payment_method' => $payment->payment_method,
            'bank' => $payment->bank,
            'reference_no' => $payment->reference_no,
            'payment_notes' => $payment->payment_notes,
        ];

        $proposedData = [
            'collection_receipt_number' => $validated['collection_receipt_number'],
            'collection_receipt_date' => $validated['collection_receipt_date'],
            'payment_posting_date' => $validated['payment_posting_date'],
            'dr_no' => $validated['dr_no'],
            'invoice_no' => $validated['invoice_no'],
            'amount' => $validated['amount'],
            'tax' => $validated['tax'] ?? 0,
            'net' => $validated['net'] ?? 0,
            'payment_method' => $validated['payment_method'],
            'bank' => $validated['bank'],
            'reference_no' => $validated['reference_no'],
            'payment_notes' => $validated['payment_notes'],
        ];

        DB::table('payment_edit_requests')->insert([
            'payment_id' => $id,
            'requested_by' => auth()->id(),
            'requested_by_name' => auth()->user()->name,
            'original_data' => json_encode($originalData),
            'proposed_data' => json_encode($proposedData),
            'reason' => $validated['edit_reason'],
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'status' => 'Pending',
            'created_at' => now(),
            'updated_at' => now(),
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

        // CC roles only see their own requests; Joey/IT sees all
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
        $grossAmount = (float)($proposedData['amount'] ?? 0);
        $ewtAmount = (float)($proposedData['tax'] ?? 0);
        $netAmount = (float)($proposedData['net'] ?? ($grossAmount - $ewtAmount));

        // Apply the edit
        DB::table('payments')->where('id', $editRequest->payment_id)->update([
            'collection_receipt_number' => $proposedData['collection_receipt_number'],
            'collection_receipt_date' => $proposedData['collection_receipt_date'],
            'deposit_date' => $proposedData['collection_receipt_date'],
            'payment_posting_date' => $proposedData['payment_posting_date'],
            'payment_date' => $proposedData['payment_posting_date'],
            'dr_no' => $proposedData['dr_no'],
            'invoice_no' => $proposedData['invoice_no'],
            'amount' => $grossAmount,
            'gross_amount' => $grossAmount,
            'tax' => $ewtAmount,
            'ewt' => $ewtAmount,
            'net' => $netAmount,
            'check_amount' => $netAmount,
            'payment_method' => $proposedData['payment_method'],
            'bank' => $proposedData['bank'] ?? null,
            'reference_no' => $proposedData['reference_no'] ?? null,
            'payment_notes' => $proposedData['payment_notes'] ?? null,
        ]);

        // Mark request as approved
        DB::table('payment_edit_requests')->where('id', $requestId)->update([
            'status' => 'Approved',
            'reviewed_by' => auth()->id(),
            'reviewed_by_name' => auth()->user()->name,
            'reviewed_at' => now(),
            'updated_at' => now(),
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
            'status' => 'Rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_by_name' => auth()->user()->name,
            'review_notes' => $request->input('review_notes'),
            'reviewed_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Edit request rejected.');
    }
}