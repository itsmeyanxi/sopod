<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\ArAdjustment;
use App\Models\ArAging;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ArAdjustmentController extends Controller
{
    /**
     * Display AR Adjustments page
     */
    public function index()
    {
        return view('ar_adjustments.index');
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('ar_adjustments.create');
    }

    /**
     * Search AR Aging records by customer name or DR number
     */
    public function searchArAging(Request $request)
    {
        try {
            $search = $request->get('search');

            if (empty($search)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search term is required'
                ], 400);
            }

            // Search in AR Aging table by customer name or DR number
            $records = ArAging::where(function($query) use ($search) {
                $query->where('client_name', 'LIKE', "%{$search}%")
                      ->orWhere('dr_no', 'LIKE', "%{$search}%")
                      ->orWhere('customer_code', 'LIKE', "%{$search}%");
            })
            ->where('net_ar_balance', '>', 0) // Only show records with outstanding balance
            ->orderBy('dr_no', 'desc')
            ->get();

            if ($records->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No matching records found'
                ]);
            }

            return response()->json([
                'success' => true,
                'records' => $records
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to search AR Aging', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to search: ' . $e->getMessage()
            ], 500);
        }
    }

public function getAdjustments(Request $request)
{
    try {
        $query = ArAdjustment::query();

        // Apply filters
        if ($request->filled('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->end_date);
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('reference_number', 'LIKE', "%{$search}%")
                  ->orWhere('customer_name', 'LIKE', "%{$search}%")
                  ->orWhere('invoice_number', 'LIKE', "%{$search}%")
                  ->orWhere('customer_code', 'LIKE', "%{$search}%");
            });
        }

        $adjustments = $query->orderBy('transaction_date', 'desc')->get();

        // Calculate summary
        $summary = [
            'total_count' => $adjustments->count(),
            'credit_total' => $adjustments->where('is_decrease', true)->sum(function($adj) {
                return abs($adj->amount);
            }),
            'debit_total' => $adjustments->where('is_decrease', false)->sum('amount'),
            'net_total' => $adjustments->sum('amount')
        ];

        // Format adjustments for frontend with delivery info
        $formattedAdjustments = $adjustments->map(function($adj) {
            // Get linked delivery info if available
            $delivery = null;
            $drStatus = null;
            if ($adj->dr_no) {
                $delivery = \App\Models\Deliveries::where('dr_no', $adj->dr_no)->first();
                if ($delivery) {
                    $drStatus = $delivery->status ?? 'pending';
                    // Handle approval status if set
                    if ($delivery->approval_status) {
                        $drStatus = $delivery->approval_status;
                    }
                }
            }

            return [
                'id' => $adj->id,
                'transaction_date' => Carbon::parse($adj->transaction_date)->format('Y-m-d'),
                'reference_number' => $adj->reference_number,
                'transaction_type' => $adj->transaction_type,
                'formatted_type' => $adj->formatted_type,
                'dr_no' => $adj->dr_no,
                'dr_status' => $drStatus,
                'invoice_number' => $adj->invoice_number,
                'customer_code' => $adj->customer_code,
                'customer_name' => $adj->customer_name,
                'branch' => $adj->branch,
                'amount' => $adj->amount,
                'absolute_amount' => abs($adj->amount),
                'is_decrease' => $adj->is_decrease,
                'gl_account' => $adj->gl_account,
                'remarks' => $adj->remarks,
                'signed_by' => $adj->signed_by,
                'created_by' => $adj->created_by,
                'created_at' => $adj->created_at ? $adj->created_at->format('Y-m-d H:i:s') : 'N/A',
            ];
        });

        return response()->json([
            'success' => true,
            'adjustments' => $formattedAdjustments,
            'summary' => $summary
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to get adjustments', [
            'error' => $e->getMessage(),
            'line' => $e->getLine()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to load adjustments: ' . $e->getMessage()
        ], 500);
    }
}

public function store(Request $request)
{
    try {
        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'reference_number' => 'required|string|max:255|unique:ar_adjustments,reference_number',
            'transaction_type' => 'required|in:atd,offset,credit_memo,debit_memo,adjustment,write_off',
            'dr_no' => 'nullable|string|max:255',
            'invoice_number' => 'nullable|string|max:255',
            'customer_code' => 'nullable|string|max:255',
            'customer_name' => 'required|string|max:255',
            'branch' => 'nullable|string|max:255',
            'amount' => 'required|string',
            'gl_account' => 'required|string|max:255',
            'remarks' => 'nullable|string',
            'signed_by' => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        // Parse amount
        $amountStr = $validated['amount'];
        $isDecrease = strpos($amountStr, '(') !== false || strpos($amountStr, '-') !== false;
        $amount = floatval(str_replace(['(', ')', ',', ' ', '-'], '', $amountStr));
        
        if ($isDecrease) {
            $amount = -abs($amount);
        } else {
            $amount = abs($amount);
        }

        // Create adjustment - MAKE SURE ALL FIELDS ARE INCLUDED
        $adjustment = ArAdjustment::create([
            'transaction_date' => $validated['transaction_date'],
            'reference_number' => $validated['reference_number'],
            'transaction_type' => $validated['transaction_type'],
            'dr_no' => $validated['dr_no'] ?? null,
            'invoice_number' => $validated['invoice_number'] ?? null,
            'customer_code' => $validated['customer_code'] ?? null,
            'customer_name' => $validated['customer_name'],
            'branch' => $validated['branch'] ?? null,  // ✅ MAKE SURE THIS IS HERE
            'amount' => $amount,
            'is_decrease' => $isDecrease,
            'gl_account' => $validated['gl_account'],
            'remarks' => $validated['remarks'] ?? null,
            'signed_by' => $validated['signed_by'],
            'created_by' => Auth::user()->name ?? 'System',
        ]);

        // Update AR Aging if invoice_number is provided
        if (!empty($validated['invoice_number']) && !empty($validated['customer_code'])) {
            $this->applyAdjustmentToAR(
                $validated['customer_code'],
                $amount,
                $validated['invoice_number'],
                $validated['transaction_date'],
                $validated['reference_number']
            );
        }

        DB::commit();

        // Check if request is AJAX or form submission
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'AR adjustment created successfully!',
                'adjustment' => $adjustment
            ]);
        }

        return redirect()->route('ar_adjustments.show', $adjustment->id)->with('success', 'AR adjustment created successfully!');

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Failed to create adjustment', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create adjustment: ' . $e->getMessage()
            ], 500);
        }

        return back()->withInput()->with('error', 'Failed to create adjustment: ' . $e->getMessage());
    }
}

    /**
     * Show single adjustment details
     */
    public function show($id)
    {
        $adjustment = ArAdjustment::findOrFail($id);
        return view('ar_adjustments.show', compact('adjustment'));
    }

    /**
     * Show edit form
     */
    public function editForm($id)
    {
        $adjustment = ArAdjustment::findOrFail($id);
        return view('ar_adjustments.edit', compact('adjustment'));
    }

    /**
     * Update an existing adjustment
     */
    public function update(Request $request, $id)
    {
        try {
            $adjustment = ArAdjustment::findOrFail($id);

            $validated = $request->validate([
                'transaction_date' => 'required|date',
                'reference_number' => 'required|string|max:255|unique:ar_adjustments,reference_number,' . $id,
                'transaction_type' => 'required|in:atd,offset,credit_memo,debit_memo,adjustment,write_off',
                'dr_no' => 'nullable|string|max:255',
                'invoice_number' => 'nullable|string|max:255',
                'customer_code' => 'nullable|string|max:255',
                'customer_name' => 'required|string|max:255',
                'amount' => 'required|string',
                'gl_account' => 'required|string|max:255',
                'remarks' => 'nullable|string',
                'signed_by' => 'required|string|max:255',
            ]);

            DB::beginTransaction();

            // Parse amount
            $amountStr = $validated['amount'];
            $isDecrease = strpos($amountStr, '(') !== false || strpos($amountStr, '-') !== false;
            $amount = floatval(str_replace(['(', ')', ',', ' ', '-'], '', $amountStr));
            
            if ($isDecrease) {
                $amount = -abs($amount);
            } else {
                $amount = abs($amount);
            }

            // Update adjustment
            $adjustment->update([
                'transaction_date' => $validated['transaction_date'],
                'reference_number' => $validated['reference_number'],
                'transaction_type' => $validated['transaction_type'],
                'dr_no' => $validated['dr_no'] ?? null,
                'invoice_number' => $validated['invoice_number'] ?? null,
                'customer_code' => $validated['customer_code'] ?? null,
                'customer_name' => $validated['customer_name'],
                'amount' => $amount,
                'gl_account' => $validated['gl_account'],
                'remarks' => $validated['remarks'] ?? null,
                'signed_by' => $validated['signed_by'],
            ]);

            DB::commit();

            // Check if request is AJAX or form submission
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'AR adjustment updated successfully!',
                    'adjustment' => $adjustment
                ]);
            }

            return redirect()->route('ar_adjustments.show', $adjustment->id)->with('success', 'AR adjustment updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update adjustment', [
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update adjustment: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()->with('error', 'Failed to update adjustment: ' . $e->getMessage());
        }
    }

    /**
     * Delete an adjustment
     */
    public function destroy($id)
    {
        try {
            $adjustment = ArAdjustment::findOrFail($id);
            
            DB::beginTransaction();

            // Reverse the adjustment in AR Aging if applicable
            if (!empty($adjustment->invoice_number) && !empty($adjustment->customer_code)) {
                $this->applyAdjustmentToAR(
                    $adjustment->customer_code,
                    -$adjustment->amount, // Reverse the amount
                    $adjustment->invoice_number,
                    now(),
                    'REVERSAL-' . $adjustment->reference_number
                );
            }

            $adjustment->delete();

            DB::commit();

            // Check if request is AJAX or form submission
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'AR adjustment deleted successfully!'
                ]);
            }

            return redirect()->route('ar_adjustments.index')->with('success', 'AR adjustment deleted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete adjustment', [
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete adjustment: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to delete adjustment: ' . $e->getMessage());
        }
    }

    /**
     * Export adjustments to CSV
     */
    public function export(Request $request)
    {
        try {
            $query = ArAdjustment::query();

            // Apply same filters as getAdjustments
            if ($request->filled('start_date')) {
                $query->whereDate('transaction_date', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $query->whereDate('transaction_date', '<=', $request->end_date);
            }

            if ($request->filled('transaction_type')) {
                $query->where('transaction_type', $request->transaction_type);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('reference_number', 'LIKE', "%{$search}%")
                      ->orWhere('customer_name', 'LIKE', "%{$search}%")
                      ->orWhere('invoice_number', 'LIKE', "%{$search}%");
                });
            }

            $adjustments = $query->orderBy('transaction_date', 'desc')->get();

            $filename = 'ar_adjustments_' . now()->format('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
                'Pragma' => 'public',
            ];

            $callback = function () use ($adjustments) {
                $file = fopen('php://output', 'w');
                
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                
                fputcsv($file, [
                    'Date',
                    'Reference No',
                    'Transaction Type',
                    'Customer Code',
                    'Customer Name',
                    'DR No',
                    'DR Status',
                    'Invoice No',
                    'Amount',
                    'Effect',
                    'GL Account',
                    'Branch',
                    'Remarks',
                    'Signed By',
                    'Created By',
                    'Created At'
                ]);

                foreach ($adjustments as $adj) {
                    // Get DR status if available
                    $drStatus = 'N/A';
                    if ($adj->dr_no) {
                        $delivery = \App\Models\Deliveries::where('dr_no', $adj->dr_no)->first();
                        if ($delivery) {
                            $drStatus = $delivery->approval_status ?? $delivery->status ?? 'pending';
                        }
                    }

                    fputcsv($file, [
                        Carbon::parse($adj->transaction_date)->format('Y-m-d'),
                        $adj->reference_number,
                        $adj->formatted_type,
                        $adj->customer_code ?? 'N/A',
                        $adj->customer_name,
                        $adj->dr_no ?? 'N/A',
                        $drStatus,
                        $adj->invoice_number ?? 'N/A',
                        number_format($adj->amount, 2),
                        $adj->is_decrease ? 'Decrease AR' : 'Increase AR',
                        $adj->gl_account,
                        $adj->branch ?? 'N/A',
                        $adj->remarks ?? 'N/A',
                        $adj->signed_by,
                        $adj->created_by,
                        $adj->created_at ? $adj->created_at->format('Y-m-d H:i:s') : 'N/A'
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Failed to export adjustments', [
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to export: ' . $e->getMessage());
        }
    }

    /**
     * Get dashboard statistics for AR adjustments
     */
    public function getDashboardStats()
    {
        try {
            $thirtyDaysAgo = Carbon::now()->subDays(30);
            $todayStart = Carbon::now()->startOfDay();
            $todayEnd = Carbon::now()->endOfDay();

            // Recent adjustments (last 7 days)
            $recentAdjustments = ArAdjustment::where('transaction_date', '>=', Carbon::now()->subDays(7))
                ->orderBy('transaction_date', 'desc')
                ->limit(5)
                ->get();

            // High-value adjustments (absolute value > 50000)
            $highValueAdjustments = ArAdjustment::where(DB::raw('ABS(amount)'), '>=', 50000)
                ->orderBy(DB::raw('ABS(amount)'), 'desc')
                ->limit(5)
                ->get();

            // Today's adjustments
            $todayCount = ArAdjustment::whereBetween('transaction_date', [$todayStart, $todayEnd])->count();
            $todayTotal = ArAdjustment::whereBetween('transaction_date', [$todayStart, $todayEnd])->sum('amount');

            // This month's adjustments
            $thisMonthCount = ArAdjustment::where('transaction_date', '>=', Carbon::now()->startOfMonth())->count();
            $thisMonthTotal = ArAdjustment::where('transaction_date', '>=', Carbon::now()->startOfMonth())->sum('amount');

            // Top customers by adjustment count
            $topCustomers = DB::table('ar_adjustments')
                ->select('customer_name', 'customer_code', DB::raw('COUNT(*) as adjustment_count'), DB::raw('SUM(amount) as total_amount'))
                ->groupBy('customer_code', 'customer_name')
                ->orderByDesc('adjustment_count')
                ->limit(5)
                ->get();

            // Adjustments by type
            $adjustmentsByType = DB::table('ar_adjustments')
                ->select('transaction_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
                ->groupBy('transaction_type')
                ->get();

            // Pending DR approvals (adjustments with DR but delivery not approved)
            $pendingApprovals = ArAdjustment::whereNotNull('dr_no')
                ->where(function($query) {
                    $query->whereHas('delivery', function($q) {
                        $q->where('approval_status', '!=', 'approved')
                          ->orWhere('approval_status', null);
                    })
                    ->orDoesntHave('delivery');
                })
                ->limit(5)
                ->get();

            return response()->json([
                'success' => true,
                'stats' => [
                    'today' => [
                        'count' => $todayCount,
                        'total' => (float)$todayTotal
                    ],
                    'this_month' => [
                        'count' => $thisMonthCount,
                        'total' => (float)$thisMonthTotal
                    ],
                    'last_30_days' => [
                        'count' => ArAdjustment::where('transaction_date', '>=', $thirtyDaysAgo)->count(),
                        'total' => (float)ArAdjustment::where('transaction_date', '>=', $thirtyDaysAgo)->sum('amount')
                    ]
                ],
                'recent_adjustments' => $recentAdjustments->map(function($adj) {
                    return [
                        'id' => $adj->id,
                        'reference_number' => $adj->reference_number,
                        'customer_name' => $adj->customer_name,
                        'amount' => $adj->amount,
                        'transaction_type' => $adj->formatted_type,
                        'transaction_date' => $adj->transaction_date->format('M d, Y')
                    ];
                }),
                'high_value_adjustments' => $highValueAdjustments->map(function($adj) {
                    return [
                        'id' => $adj->id,
                        'reference_number' => $adj->reference_number,
                        'customer_name' => $adj->customer_name,
                        'amount' => $adj->amount,
                        'transaction_type' => $adj->formatted_type,
                        'transaction_date' => $adj->transaction_date->format('M d, Y')
                    ];
                }),
                'top_customers' => $topCustomers,
                'adjustments_by_type' => $adjustmentsByType,
                'pending_approvals' => $pendingApprovals->map(function($adj) {
                    return [
                        'id' => $adj->id,
                        'reference_number' => $adj->reference_number,
                        'dr_no' => $adj->dr_no,
                        'customer_name' => $adj->customer_name,
                        'amount' => $adj->amount
                    ];
                })
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get dashboard stats', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard statistics'
            ], 500);
        }
    }

    /**
     * Display adjustments for a specific customer
     */
    public function byCustomer($customerCode)
    {
        try {
            // Get customer details
            $customer = \App\Models\Customer::where('customer_code', $customerCode)->first();

            if (!$customer) {
                return redirect()->route('ar_adjustments.index')->with('error', 'Customer not found');
            }

            // Get all adjustments for this customer
            $adjustments = ArAdjustment::where(function($query) use ($customerCode) {
                $query->where('customer_code', $customerCode)
                      ->orWhere('customer_name', $customer->customer_name);
            })
            ->orderBy('transaction_date', 'desc')
            ->get();

            // Calculate summary
            $summary = [
                'total_count' => $adjustments->count(),
                'credit_total' => $adjustments->where('is_decrease', true)->sum(function($adj) {
                    return abs($adj->amount);
                }),
                'debit_total' => $adjustments->where('is_decrease', false)->sum('amount'),
                'net_total' => $adjustments->sum('amount')
            ];

            // Get AR aging for this customer
            $arAging = \App\Models\ArAging::where(function($query) use ($customerCode, $customer) {
                if (!empty($customerCode) && $customerCode !== '#N/A' && $customerCode !== 'N/A') {
                    $query->where('customer_code', $customerCode);
                } else {
                    $query->where('client_name', $customer->customer_name);
                }
            })
            ->orderBy('invoice_date', 'desc')
            ->get();

            return view('ar_adjustments.by_customer', compact(
                'customer',
                'adjustments',
                'summary',
                'arAging'
            ));

        } catch (\Exception $e) {
            Log::error('Failed to load customer adjustments', [
                'error' => $e->getMessage(),
                'customer_code' => $customerCode
            ]);
            return redirect()->route('ar_adjustments.index')->with('error', 'Failed to load customer adjustments');
        }
    }

    /**
     * Apply adjustment to AR Aging records
     */
    private function applyAdjustmentToAR($customerCode, $adjustmentAmount, $invoiceNumber, $transactionDate, $referenceNumber)
    {
        $query = ArAging::where('customer_code', $customerCode);

        // If specific invoice, adjust that one
        if ($invoiceNumber) {
            $query->where('invoice_no', $invoiceNumber);
        } else {
            // Otherwise, adjust oldest outstanding invoice
            $query->where('net_ar_balance', '>', 0)
                  ->orderBy('invoice_date', 'asc')
                  ->limit(1);
        }

        $record = $query->first();

        if (!$record) {
            Log::warning('No matching AR record found for adjustment', [
                'customer_code' => $customerCode,
                'invoice_number' => $invoiceNumber
            ]);
            return;
        }

        // Update AR balances
        $record->ar_adjustments += $adjustmentAmount;
        $record->gross_ar_balance += $adjustmentAmount;
        $record->net_ar_balance += $adjustmentAmount;
        $record->net_ar += $adjustmentAmount;

        // Update status
        if ($record->net_ar_balance <= 0.01) {
            $record->status = 'Paid';
            $record->net_ar_balance = 0;
            $record->net_ar = 0;
        } elseif ($record->settled_invoice_amount > 0) {
            $record->status = 'Partial';
        } else {
            $record->status = 'Outstanding';
        }

        $record->save();

        // Log transaction
        DB::table('ar_transactions')->insert([
            'ar_aging_id' => $record->id,
            'transaction_type' => 'Adjustment',
            'amount' => $adjustmentAmount,
            'transaction_date' => $transactionDate,
            'reference_number' => $referenceNumber,
            'created_by' => Auth::user()->name ?? 'System',
            'created_at' => now(),
        ]);

        Log::info('AR Aging updated by adjustment', [
            'ar_aging_id' => $record->id,
            'adjustment_amount' => $adjustmentAmount,
            'new_balance' => $record->net_ar_balance
        ]);
    }

    /**
     * Get pending deliveries without AR adjustments
     */
    public function getPendingDeliveries()
    {
        try {
            // Get all deliveries that don't have matching AR adjustments
            // Use whereNotExists() for proper SQL subquery matching
            $deliveries = \App\Models\Deliveries::select(
                'id',
                'dr_no',
                'customer_code',
                'customer_name',
                'status',
                'approval_status',
                'request_delivery_date'
            )
            ->where('status', 'Delivered')  // Only delivered orders
            ->where('is_pulled_out', '!=', 1)  // Exclude pulled out deliveries
            ->whereNotExists(function ($query) {
                // Exclude deliveries that have a matching AR adjustment by DR number
                $query->select(DB::raw(1))
                    ->from('ar_adjustments')
                    ->whereColumn('ar_adjustments.dr_no', '=', 'deliveries.dr_no');
            })
            ->orderBy('id', 'desc')  // Show newest first
            ->limit(500)  // Limit to avoid loading too many
            ->get()
            ->map(function ($delivery) {
                $delivery->has_adjustment = false;  // All these don't have adjustments
                return $delivery;
            });

            return response()->json([
                'success' => true,
                'deliveries' => $deliveries
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load pending deliveries', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load deliveries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all pending deliveries for a specific customer
     */
    public function getCustomerDeliveries($customerCode)
    {
        try {
            // Get all deliveries for this customer that don't have AR adjustments
            $deliveries = \App\Models\Deliveries::select(
                'id',
                'dr_no',
                'sales_invoice_no',
                'customer_code',
                'customer_name',
                'sales_order_number',
                'po_number',
                'status',
                'approval_status',
                'request_delivery_date'
            )
            ->with('items')
            ->where('customer_code', $customerCode)
            ->where('status', 'Delivered')
            ->where('is_pulled_out', '!=', 1)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('ar_adjustments')
                    ->whereColumn('ar_adjustments.dr_no', '=', 'deliveries.dr_no');
            })
            ->orderBy('request_delivery_date', 'desc')
            ->get()
            ->map(function ($delivery) {
                // Add calculated fields
                $delivery->item_count = $delivery->items->count();
                $delivery->total_amount = $delivery->items->sum('total_amount');
                return $delivery;
            });

            return response()->json([
                'success' => true,
                'deliveries' => $deliveries
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load customer deliveries', [
                'customer_code' => $customerCode,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load deliveries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get delivery information by DR number
     */
    public function getDeliveryInfo($drNo)
    {
        try {
            $delivery = \App\Models\Deliveries::where('dr_no', $drNo)->first();

            if (!$delivery) {
                return response()->json([
                    'success' => false,
                    'message' => 'Delivery not found'
                ], 404);
            }

            // Check if adjustment already exists
            $hasAdjustment = ArAdjustment::where('dr_no', $drNo)->exists();

            if ($hasAdjustment) {
                return response()->json([
                    'success' => false,
                    'message' => 'This delivery already has an AR adjustment'
                ]);
            }

            // Get AR Aging record for this customer
            $arAging = ArAging::where('customer_code', $delivery->customer_code)->first();

            return response()->json([
                'success' => true,
                'delivery' => [
                    'dr_no' => $delivery->dr_no,
                    'customer_code' => $delivery->customer_code,
                    'customer_name' => $delivery->customer_name,
                    'delivery_date' => $delivery->delivery_date,
                    'status' => $delivery->status
                ],
                'ar_balance' => $arAging ? $arAging->net_ar_balance : 0
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get delivery information', [
                'error' => $e->getMessage(),
                'dr_no' => $drNo
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load delivery information'
            ], 500);
        }
    }
}