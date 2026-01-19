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
            $customerData = DB::table('ar_aging')
                ->select(
                    'customer_code',
                    'client_name',
                    DB::raw('SUM(CASE WHEN COALESCE(net_ar_balance, 0) > 0 THEN COALESCE(net_ar_balance, 0) ELSE 0 END) as total_outstanding'),
                    DB::raw('SUM(COALESCE(gross_ar_balance, 0)) as gross_balance'),
                    DB::raw('SUM(COALESCE(invoice_amount, 0)) as total_invoice'),
                    DB::raw('SUM(COALESCE(settled_invoice_amount, 0)) as total_settled'),
                    DB::raw('MAX(branch) as branch'),
                    DB::raw('COALESCE(MAX(sales_executive), MAX(se2)) as sales_executive'),
                    DB::raw('MAX(terms) as terms'),
                    DB::raw('COUNT(*) as invoice_count'),
                    DB::raw('COUNT(CASE WHEN COALESCE(net_ar_balance, 0) > 0 THEN 1 END) as outstanding_invoice_count')
                )
                ->where(function($query) use ($search) {
                    $query->whereRaw('TRIM(customer_code) = ?', [trim($search)])
                          ->orWhereRaw('TRIM(customer_code) LIKE ?', ['%' . trim($search) . '%'])
                          ->orWhereRaw('TRIM(client_name) LIKE ?', ['%' . trim($search) . '%']);
                })
                ->groupBy('customer_code', 'client_name')
                ->first();

            Log::info('Search result', ['found' => $customerData ? 'yes' : 'no']);

            if (!$customerData) {
                Log::warning('Customer not found in ar_aging', [
                    'search_term' => $search,
                    'trimmed' => trim($search)
                ]);
                
                return response()->json([
                    'success' => false, 
                    'message' => 'Customer not found'
                ], 404);
            }

            // Get outstanding invoices for this customer
            $outstandingInvoices = DB::table('ar_aging')
                ->select(
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
                ->limit(10)
                ->get();

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
            // Log incoming request
            Log::info('Payment store request received', [
                'data' => $request->all()
            ]);

            $validated = $request->validate([
                'customer_code' => 'required|string|max:255',
                'customer_name' => 'required|string|max:255',
                'collection_receipt_number' => 'required|string|max:255',
                'collection_receipt_date' => 'required|date',
                'payment_posting_date' => 'required|date',
                'amount' => 'required|numeric|min:0.01',
                'tax' => 'nullable|numeric|min:0',
                'payment_option' => 'required|in:Full Payment,Partial Payment',
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

            // Use direct DB insert matching exact table structure
            $paymentId = DB::table('payments')->insertGetId([
                'customer_code' => $validated['customer_code'],
                'customer_name' => $validated['customer_name'],
                'collection_receipt_number' => $validated['collection_receipt_number'],
                'collection_receipt_date' => $validated['collection_receipt_date'],
                'payment_posting_date' => $validated['payment_posting_date'],
                'payment_date' => $validated['payment_posting_date'],
                'amount' => $validated['amount'],
                'tax' => $validated['tax'] ?? 0,
                'payment_option' => $validated['payment_option'],
                'payment_notes' => $validated['payment_notes'] ?? null,
                'created_by' => auth()->user()->name ?? 'System',
                'payment_method' => null,
                'bank' => null,
                'reference_no' => null,
                'remarks' => null,
                'created_at' => now(),
            ]);

            Log::info('Payment inserted successfully', [
                'payment_id' => $paymentId,
                'customer_code' => $validated['customer_code'],
                'amount' => $validated['amount']
            ]);

            // ✅ UPDATE AR AGING BALANCES
            $this->updateArAgingBalance($validated['customer_code'], $validated['amount']);

            // Get the inserted payment
            $payment = DB::table('payments')->where('id', $paymentId)->first();

            // Create activity log if Activity model exists
            if (class_exists('\App\Models\Activity')) {
                try {
                    DB::table('activities')->insert([
                        'user_name' => auth()->user()->name ?? 'System',
                        'action' => 'Created',
                        'item' => 'Payment: ' . $validated['collection_receipt_number'],
                        'target' => $validated['customer_name'],
                        'type' => 'Payment',
                        'message' => "Created payment entry: {$validated['collection_receipt_number']} for {$validated['customer_name']} - Amount: ₱" . number_format($validated['amount'], 2),
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
                    'collection_receipt_number' => $payment->collection_receipt_number,
                    'collection_receipt_date' => $payment->collection_receipt_date,
                    'payment_posting_date' => $payment->payment_posting_date,
                    'amount' => $payment->amount,
                    'tax' => $payment->tax,
                    'payment_option' => $payment->payment_option,
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

    public function collectionReport(Request $request)
    {
        try {
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
            $customerFilter = $request->input('customer', '');

            $query = DB::table('payments');

            if ($dateFrom) {
                $query->whereDate('payment_posting_date', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->whereDate('payment_posting_date', '<=', $dateTo);
            }

            if ($customerFilter) {
                $query->where(function($q) use ($customerFilter) {
                    $q->where('customer_code', 'LIKE', '%' . $customerFilter . '%')
                      ->orWhere('customer_name', 'LIKE', '%' . $customerFilter . '%');
                });
            }

            $payments = $query->orderBy('payment_posting_date', 'desc')->get();

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

            $query = DB::table('payments');

            if ($dateFrom) {
                $query->whereDate('payment_posting_date', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->whereDate('payment_posting_date', '<=', $dateTo);
            }

            if ($customerFilter) {
                $query->where(function($q) use ($customerFilter) {
                    $q->where('customer_code', 'LIKE', '%' . $customerFilter . '%')
                      ->orWhere('customer_name', 'LIKE', '%' . $customerFilter . '%');
                });
            }

            $payments = $query->orderBy('payment_posting_date', 'desc')->get();

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

            if (!$customerCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer code is required'
                ], 400);
            }

            // First, get the customer name from ar_aging
            $customerName = DB::table('ar_aging')
                ->where('customer_code', $customerCode)
                ->value('client_name');

            if (!$customerName) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found'
                ], 404);
            }

            // Fetch all payments for this customer by name (since customer_code is NULL in most payments)
            // Also check by customer_code in case some have it
            $payments = Payment::where(function($query) use ($customerCode, $customerName) {
                    $query->where('customer_code', $customerCode)
                          ->orWhere('customer_name', 'LIKE', '%' . $customerName . '%');
                })
                ->orderBy('payment_posting_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit(50) // Limit to last 50 payments
                ->get();

            return response()->json([
                'success' => true,
                'payments' => $payments // Return ALL payment database fields
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get customer payment history', [
                'error' => $e->getMessage(),
                'customer_code' => $request->input('customer_code')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load payment history'
            ], 500);
        }
    }
}