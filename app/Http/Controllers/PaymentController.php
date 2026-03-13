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

        // ✅ NEW: If not found in ar_aging, try deliveries table (for pending invoicing)
        if (!$customerData) {
            $delivery = DB::table('deliveries')
                ->leftJoin('customers', DB::raw("CAST(deliveries.customer_code AS CHAR) COLLATE utf8mb4_unicode_ci"), '=', DB::raw("CAST(customers.customer_code AS CHAR) COLLATE utf8mb4_unicode_ci"))
                ->select(
                    'deliveries.customer_code',
                    'deliveries.customer_name as client_name',
                    DB::raw('0 as total_outstanding'),
                    DB::raw('0 as gross_balance'),
                    DB::raw('0 as total_invoice'),
                    DB::raw('0 as total_settled'),
                    DB::raw("COALESCE(deliveries.branch, 'N/A') as branch"),
                    DB::raw("COALESCE(deliveries.sales_executive, 'N/A') as sales_executive"),
                    DB::raw("NULL as terms"),
                    DB::raw('0 as invoice_count'),
                    DB::raw('0 as outstanding_invoice_count'),
                    'customers.whtrate'
                )
                ->where(function($query) use ($search) {
                    $query->whereRaw('TRIM(deliveries.customer_code) = ?', [trim($search)])
                          ->orWhereRaw('TRIM(deliveries.customer_code) LIKE ?', ['%' . trim($search) . '%'])
                          ->orWhereRaw('TRIM(deliveries.customer_name) LIKE ?', ['%' . trim($search) . '%'])
                          ->orWhereRaw('TRIM(deliveries.dr_no) = ?', [trim($search)])
                          ->orWhereRaw('TRIM(deliveries.dr_no) LIKE ?', ['%' . trim($search) . '%']);
                })
                ->first();

            if ($delivery) {
                $customerData = $delivery;
                Log::info('Customer found in deliveries table (pending invoicing)', [
                    'customer_code' => $customerData->customer_code,
                    'delivery_date' => $delivery->request_delivery_date,
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

        // ✅ FIX: Get outstanding invoices WITH dr_no field
        $outstandingInvoices = DB::table('ar_aging')
            ->select(
                'dr_no',           // ✅ Added this!
                'invoice_no',
                'invoice_date',
                'invoice_amount',
                'settled_invoice_amount',
                'net_ar_balance',
                'gross_ar_balance',
                'terms',
                'age',
                'due_date',
                'status'
            )
            ->where('customer_code', $customerData->customer_code)
            ->where('net_ar_balance', '>', 0)
            ->orderBy('invoice_date', 'desc')
            ->limit(100)  // Increased limit to get more outstanding invoices
            ->get();

        Log::info('Outstanding invoices loaded', [
            'count' => $outstandingInvoices->count(),
            'sample_dr_numbers' => $outstandingInvoices->take(3)->pluck('dr_no')->toArray()
        ]);

        return response()->json([
            'success' => true,
            'customer' => [
                'code' => $customerData->customer_code,
                'name' => $customerData->client_name,
                'outstanding_balance' => number_format($customerData->total_outstanding, 2, '.', ''),
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
    'net' => 'nullable|numeric|min:0', // ✅ NEW: Accept net amount
    'payment_means' => 'required|array', // ✅ NEW: Accept payment means data
    'payment_means.type' => 'required|in:check,bank_transfer,cash', // ✅ NEW
    'payment_notes' => 'nullable|string|max:1000',
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
        $paymentId = DB::table('payments')->insertGetId([
    'customer_code' => $validated['customer_code'],
    'customer_name' => $validated['customer_name'],
    'dr_no' => $validated['dr_number'],
    'invoice_no' => $validated['invoice_no'],
    'collection_receipt_number' => $validated['collection_receipt_number'],
    'collection_receipt_date' => $validated['collection_receipt_date'],
    'payment_posting_date' => $validated['payment_posting_date'],
    'payment_date' => $validated['payment_posting_date'],
    'amount' => $validated['amount'],
    'tax' => $validated['tax'] ?? 0,
    'net' => $validated['net'] ?? null, // ✅ NEW: Save net amount
    // ✅ NEW: Store payment means data as JSON or separate fields
    'payment_method' => $validated['payment_means']['type'], // check, bank_transfer, or cash
    'payment_means_data' => json_encode($validated['payment_means']), // Store full data as JSON
    'payment_notes' => $validated['payment_notes'] ?? null,
    'created_by' => auth()->user()->name ?? 'System',
    'bank' => $validated['payment_means']['bank_name'] ?? null,
    'reference_no' => $validated['payment_means']['reference'] ?? $validated['payment_means']['check_number'] ?? null,
    'created_at' => now(),
]);

        Log::info('Payment inserted successfully', [
            'payment_id' => $paymentId,
            'customer_code' => $validated['customer_code'],
            'dr_number' => $validated['dr_number'],
            'amount' => $validated['amount']
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

        // Find the specific invoice by DR number
        $invoice = DB::table('ar_aging')
            ->where('customer_code', $customerCode)
            ->where('dr_no', $drNumber)
            ->where('net_ar_balance', '>', 0)
            ->first();

        if (!$invoice) {
            Log::warning('Invoice not found or already paid', [
                'customer_code' => $customerCode,
                'dr_number' => $drNumber
            ]);
            return false;
        }

        $currentBalance = $invoice->net_ar_balance;

        if ($paymentAmount >= $currentBalance) {
            // Payment covers entire balance
            DB::table('ar_aging')
                ->where('id', $invoice->id)
                ->update([
                    'net_ar_balance' => 0,
                    'settled_invoice_amount' => DB::raw('settled_invoice_amount + ' . $currentBalance),
                    'status' => 'Paid',
                    'updated_at' => now()
                ]);

            Log::info('Invoice fully paid', [
                'invoice_id' => $invoice->id,
                'dr_number' => $drNumber,
                'amount_applied' => $currentBalance
            ]);
        } else {
            // Payment covers partial balance
            DB::table('ar_aging')
                ->where('id', $invoice->id)
                ->update([
                    'net_ar_balance' => DB::raw('net_ar_balance - ' . $paymentAmount),
                    'settled_invoice_amount' => DB::raw('settled_invoice_amount + ' . $paymentAmount),
                    'status' => 'Partial',
                    'updated_at' => now()
                ]);

            Log::info('Invoice partially paid', [
                'invoice_id' => $invoice->id,
                'dr_number' => $drNumber,
                'amount_applied' => $paymentAmount,
                'remaining_balance' => $currentBalance - $paymentAmount
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

            // Query 1: Get manually entered payments from payments table
            $paymentsQuery = DB::table('payments')
                ->select(
                    DB::raw("'manual' as source"),
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
                    'tax',
                    'net', // ✅ NEW: Include net field
                    'payment_method',
                    'payment_option',
                    'payment_means_data',
                    'payment_notes',
                    'bank',
                    'reference_no',
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

            // Query 2: Get original data from ar_aging table (invoices/payments)
            $arAgingQuery = DB::table('ar_aging')
                ->select(
                    DB::raw("'ar_aging' as source"),
                    DB::raw('NULL as id'),
                    'customer_code',
                    'client_name as customer_name',
                    'dr_no',
                    'invoice_no',
                    DB::raw("CONCAT('AR-', invoice_no) as collection_receipt_number"),
                    'invoice_date as collection_receipt_date',
                    'invoice_date as payment_posting_date',
                    'invoice_date as payment_date',
                    'invoice_amount as amount',
                    DB::raw('0 as tax'),
                    DB::raw('NULL as net'), // ✅ NEW: Net not applicable for ar_aging
                    DB::raw('NULL as payment_method'),
                    DB::raw('NULL as payment_option'),
                    DB::raw('NULL as payment_means_data'),
                    'terms as payment_notes',
                    DB::raw('NULL as bank'),
                    DB::raw('NULL as reference_no'),
                    DB::raw("'System' as created_by"),
                    'created_at'
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

            // ✅ Query 3: Get delivered items that don't have invoices yet
            $deliveriesQuery = DB::table('deliveries')
                ->select(
                    DB::raw("'delivery' as source"),
                    'id',
                    'customer_code',
                    'customer_name',
                    'dr_no',
                    DB::raw("'Pending Invoice' as invoice_no"),
                    DB::raw("dr_no as collection_receipt_number"),
                    DB::raw("request_delivery_date as collection_receipt_date"),
                    DB::raw("request_delivery_date as payment_posting_date"),
                    DB::raw("request_delivery_date as payment_date"),
                    DB::raw('0 as amount'),
                    DB::raw('0 as tax'),
                    DB::raw('NULL as net'),
                    DB::raw('NULL as payment_method'),
                    DB::raw('NULL as payment_option'),
                    DB::raw('NULL as payment_means_data'),
                    DB::raw("status as payment_notes"),
                    DB::raw('NULL as bank'),
                    DB::raw('NULL as reference_no'),
                    DB::raw("'System' as created_by"),
                    'created_at'
                )
                ->where('status', 'Delivered')
                ->where('is_pulled_out', '!=', 1)
                ->whereNotExists(function ($query) {
                    // Exclude deliveries that already have invoices
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

            // ✅ Combine all three queries using UNION
            $payments = $paymentsQuery
                ->union($arAgingQuery)
                ->union($deliveriesQuery)
                ->orderBy('payment_posting_date', 'desc')
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
            $paymentsQuery = DB::table('payments')
                ->select(
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
                    DB::raw('CONCAT("INV-", invoice_no) as collection_receipt_number')
                )
                ->where('customer_code', $customerCode)
                ->where('net_ar_balance', '>', 0) // ✅ Only outstanding balances
                ->orderBy('invoice_date', 'desc')
                ->limit(100)
                ->get();

            Log::info('Outstanding invoices found', [
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
}