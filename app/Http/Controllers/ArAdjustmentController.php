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

        // Format adjustments for frontend
        $formattedAdjustments = $adjustments->map(function($adj) {
            return [
                'id' => $adj->id,
                'transaction_date' => Carbon::parse($adj->transaction_date)->format('Y-m-d'),
                'reference_number' => $adj->reference_number,
                'transaction_type' => $adj->transaction_type,
                'formatted_type' => $adj->formatted_type,
                'dr_no' => $adj->dr_no,
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
            'transaction_type' => 'required|in:credit_memo,debit_memo,adjustment,write_off',
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
                'transaction_type' => 'required|in:credit_memo,debit_memo,adjustment,write_off',
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
                    'DR No',
                    'Invoice No',
                    'Customer Code',
                    'Customer Name',
                    'Amount',
                    'Effect',
                    'GL Account',
                    'Remarks',
                    'Signed By',
                    'Created By',
                    'Created At'
                ]);

                foreach ($adjustments as $adj) {
                    fputcsv($file, [
                        Carbon::parse($adj->transaction_date)->format('Y-m-d'),
                        $adj->reference_number,
                        $adj->formatted_type,
                        $adj->dr_no ?? 'N/A',
                        $adj->invoice_number ?? 'N/A',
                        $adj->customer_code ?? 'N/A',
                        $adj->customer_name,
                        number_format($adj->amount, 2),
                        $adj->is_decrease ? 'Decrease AR' : 'Increase AR',
                        $adj->gl_account,
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
}