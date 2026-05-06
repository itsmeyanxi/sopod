<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\ArAdjustment;
use App\Models\ArAging;
use App\Models\Customer;
use App\Models\Deliveries;
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
    $glAccounts = \App\Models\GlAccount::orderBy('account_code')
        ->get(['id', 'account_code', 'account_name', 'fs_line_item'])
        ->map(function($account) {
            return [
                'id'          => $account->id,
                'code'        => $account->account_code,
                'name'        => $account->account_name,
                'display'     => $account->account_code . ' - ' . $account->account_name,
                'search'      => strtolower($account->account_code . ' ' . $account->account_name),
                'fs_line_item'=> $account->fs_line_item,
            ];
        });

    // Generate next reference number preview
    $year = date('Y');
    $lastAdj = ArAdjustment::where('reference_number', 'LIKE', "ADJ-{$year}-%")
        ->orderByRaw('CAST(SUBSTRING(reference_number, ' . (strlen("ADJ-{$year}-") + 1) . ') AS UNSIGNED) DESC')
        ->first();
    $nextNum = $lastAdj ? (int)explode('-', $lastAdj->reference_number)[2] + 1 : 1;
    $nextRefNumber = "ADJ-{$year}-" . str_pad($nextNum, 5, '0', STR_PAD_LEFT);

    return view('ar_adjustments.create', compact('glAccounts', 'nextRefNumber'));
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

            // ✅ Search in AR Aging table by customer name or DR number
            $arAgingRecords = ArAging::where(function($query) use ($search) {
                $query->where('client_name', 'LIKE', "%{$search}%")
                      ->orWhere('dr_no', 'LIKE', "%{$search}%")
                      ->orWhere('customer_code', 'LIKE', "%{$search}%");
            })
            ->select('id', 'dr_no', 'invoice_no', DB::raw("client_name as customer_name"), 'customer_code', 'invoice_amount', 'net_ar_balance')
            ->orderBy('dr_no', 'desc')
            ->get()
            ->map(function($record) {
                $record->source = 'invoiced';
                // ✅ Add payment status indicator
                $record->payment_status = ($record->net_ar_balance > 0) ? 'Outstanding' : 'Fully Paid';
                return $record;
            });

            // ✅ NEW: Also search in Deliveries table (pending invoicing)
            $deliveryRecords = Deliveries::leftJoin('customers', function($join) {
                    $join->whereRaw('LOWER(TRIM(customers.customer_name)) = LOWER(TRIM(deliveries.customer_name))');
                })
            ->where(function($query) use ($search) {
                $query->where('deliveries.customer_name', 'LIKE', "%{$search}%")
                      ->orWhere('deliveries.dr_no', 'LIKE', "%{$search}%")
                      ->orWhere('deliveries.customer_code', 'LIKE', "%{$search}%")
                      ->orWhere('customers.customer_code', 'LIKE', "%{$search}%");
            })
            ->where('deliveries.status', 'Delivered')
            ->where('deliveries.is_pulled_out', '!=', 1)
            ->where('deliveries.is_locked', '!=', 1)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('ar_aging')
                    ->whereRaw('CAST(ar_aging.dr_no AS CHAR) COLLATE utf8mb4_unicode_ci = CAST(deliveries.dr_no AS CHAR) COLLATE utf8mb4_unicode_ci');
            })
            ->select('deliveries.id', 'deliveries.dr_no', DB::raw("'Pending Invoice' as invoice_no"), 'deliveries.customer_name',
                     DB::raw("TRIM(IFNULL(NULLIF(deliveries.customer_code,''), customers.customer_code)) as customer_code"),
                     DB::raw('0 as invoice_amount'), DB::raw('0 as net_ar_balance'))
            ->orderBy('deliveries.dr_no', 'desc')
            ->get()
            ->map(function($record) {
                $record->source = 'pending_invoice';
                return $record;
            });

            // ✅ Combine both results
            $records = $arAgingRecords->concat($deliveryRecords);

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

    /**
     * Search receiving reports for linking to AR adjustment
     */
    public function searchReceivingReports(Request $request)
    {
        $search = $request->get('search', '');

        if (strlen($search) < 2) {
            return response()->json(['results' => []]);
        }

        $results = \App\Models\ReceivingReport::with('items')
            ->where(function($q) use ($search) {
                $q->where('rr_number', 'LIKE', "%{$search}%")
                  ->orWhere('delivery_batch', 'LIKE', "%{$search}%")
                  ->orWhere('customer_name', 'LIKE', "%{$search}%")
                  ->orWhere('customer_code', 'LIKE', "%{$search}%")
                  ->orWhere('sales_order_number', 'LIKE', "%{$search}%");
            })
            ->orderBy('received_date', 'desc')
            ->limit(20)
            ->get()
            ->map(function($rr) {
                return [
                    'id' => $rr->id,
                    'rr_number' => $rr->rr_number,
                    'delivery_batch' => $rr->delivery_batch,
                    'sales_order_number' => $rr->sales_order_number,
                    'customer_name' => $rr->customer_name,
                    'customer_code' => $rr->customer_code,
                    'branch' => $rr->branch,
                    'sales_invoice_no' => $rr->sales_invoice_no,
                    'received_date' => $rr->received_date ? $rr->received_date->format('M d, Y') : null,
                    'status' => $rr->status,
                    'total_amount' => $rr->items->sum('total_amount'),
                    'item_count' => $rr->items->count(),
                ];
            });

        return response()->json(['results' => $results]);
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
                  ->orWhere('customer_code', 'LIKE', "%{$search}%")
                  ->orWhere('dr_no', 'LIKE', "%{$search}%");
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

/**
     * Parse factoring XLSX file and return rows as JSON
     */
    public function parseFactoringFile(Request $request)
    {
        try {
            $request->validate([
                'factoring_file' => 'required|file|max:51200',
            ]);

            $file = $request->file('factoring_file');
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file->getPathname());
            $reader->setReadDataOnly(true);

            // Only read first 5000 rows to avoid memory issues
            $reader->setReadFilter(new class implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter {
                public function readCell($columnAddress, $row, $worksheetName = '') {
                    return $row <= 5000;
                }
            });

            $spreadsheet = $reader->load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();

            $rows = [];
            $highRow = min(5000, $sheet->getHighestDataRow());

            for ($row = 2; $row <= $highRow; $row++) {
                $drNo = $sheet->getCell('A' . $row)->getValue();
                if ($drNo === null || $drNo === '') continue;

                // Convert Excel serial dates to readable strings
                $depositedDate = $sheet->getCell('C' . $row)->getValue();
                $depositedDateRaw = '';
                if ($depositedDate && is_numeric($depositedDate)) {
                    try {
                        $dtObj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($depositedDate);
                        $depositedDateRaw = $dtObj->format('Y-m-d');
                        $depositedDate = $dtObj->format('M d, Y');
                    } catch (\Exception $e) { /* keep raw */ }
                }

                $crOr = $sheet->getCell('D' . $row)->getValue();
                if ($crOr && is_numeric($crOr)) {
                    try {
                        $crOr = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($crOr)->format('M d, Y');
                    } catch (\Exception $e) { /* keep raw */ }
                }

                // Calculate total collection by summing all amount columns (F through N)
                // Column O contains a formula which won't resolve with ReadDataOnly
                $totalCollection = (float)($sheet->getCell('F' . $row)->getValue() ?? 0)
                    + (float)($sheet->getCell('G' . $row)->getValue() ?? 0)
                    + (float)($sheet->getCell('H' . $row)->getValue() ?? 0)
                    + (float)($sheet->getCell('I' . $row)->getValue() ?? 0)
                    + (float)($sheet->getCell('J' . $row)->getValue() ?? 0)
                    + (float)($sheet->getCell('K' . $row)->getValue() ?? 0)
                    + (float)($sheet->getCell('L' . $row)->getValue() ?? 0)
                    + (float)($sheet->getCell('M' . $row)->getValue() ?? 0)
                    + (float)($sheet->getCell('N' . $row)->getValue() ?? 0);

                $rows[] = [
                    'dr_no'            => $drNo,
                    'customer'         => $sheet->getCell('B' . $row)->getValue() ?? '',
                    'deposited_date'   => $depositedDate ?? '',
                    'deposited_date_raw' => $depositedDateRaw,
                    'cr_or'            => $crOr ?? '',
                    'bank'             => $sheet->getCell('E' . $row)->getValue() ?? '',
                    'check_amount'     => $sheet->getCell('F' . $row)->getValue() ?? 0,
                    'ewt'              => $sheet->getCell('G' . $row)->getValue() ?? 0,
                    'rebate'           => $sheet->getCell('H' . $row)->getValue() ?? 0,
                    'overpayment'      => $sheet->getCell('I' . $row)->getValue() ?? 0,
                    'short_payment'    => $sheet->getCell('J' . $row)->getValue() ?? 0,
                    'factoring'        => $sheet->getCell('K' . $row)->getValue() ?? 0,
                    'refund'           => $sheet->getCell('L' . $row)->getValue() ?? 0,
                    'small_balance'    => $sheet->getCell('M' . $row)->getValue() ?? 0,
                    'others'           => $sheet->getCell('N' . $row)->getValue() ?? 0,
                    'total_collection' => $totalCollection,
                    'remarks'          => $sheet->getCell('P' . $row)->getValue() ?? '',
                    'week'             => $sheet->getCell('Q' . $row)->getValue() ?? '',
                ];
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            return response()->json([
                'success' => true,
                'rows' => $rows,
                'message' => count($rows) . ' rows parsed successfully.',
            ]);

        } catch (\Exception $e) {
            Log::error('Factoring file parse failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to parse file: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
{
    try {
        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'reference_number' => 'nullable|string|max:255',
            'transaction_type' => 'required|in:atd,offset,credit_memo,debit_memo,adjustment,write_off,sales_return_allowances,price_adjustment,rebates,distribution_fees,penalty,promotional_expenses,small_balance_adjustment,quantity_variants,short_payment,factoring',
            'dr_no' => 'nullable|string|max:255',
            'invoice_number' => 'nullable|string|max:255',
            'customer_code' => 'nullable|string|max:255',
            'customer_name' => 'required|string|max:255',
            'branch' => 'nullable|string|max:255',
            'amount' => 'nullable|string',
            'factoring_amount' => 'nullable|numeric',
            'gl_account' => 'nullable|string|max:255',
            'gl_account_id' => 'nullable|exists:gl_accounts,id',
            'remarks' => 'nullable|string',
            'attachment' => 'nullable|file|max:5120|mimes:pdf,png,jpg,jpeg',
            'receiving_report_id' => 'nullable|exists:receiving_reports,id',
            'delivery_id' => 'nullable|exists:deliveries,id',
            'signed_by' => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        // Auto-generate reference number if not provided
        if (empty($validated['reference_number'])) {
            $year = date('Y');
            $lastAdj = ArAdjustment::where('reference_number', 'LIKE', "ADJ-{$year}-%")
                ->orderByRaw('CAST(SUBSTRING(reference_number, ' . (strlen("ADJ-{$year}-") + 1) . ') AS UNSIGNED) DESC')
                ->first();
            $nextNum = 1;
            if ($lastAdj) {
                $parts = explode('-', $lastAdj->reference_number);
                $nextNum = (int)end($parts) + 1;
            }
            $validated['reference_number'] = "ADJ-{$year}-" . str_pad($nextNum, 5, '0', STR_PAD_LEFT);
        }

        // For factoring, use factoring_amount if amount is empty
        if (($validated['transaction_type'] ?? '') === 'factoring' && empty($validated['amount'])) {
            $factoringAmt = floatval($validated['factoring_amount'] ?? 0);
            if ($factoringAmt <= 0) {
                return back()->withInput()->withErrors(['factoring_amount' => 'The factoring amount is required.']);
            }
            $validated['amount'] = (string) $factoringAmt;
        }

        if (empty($validated['amount'])) {
            return back()->withInput()->withErrors(['amount' => 'The amount field is required.']);
        }

        // Parse amount — positive input = decrease AR, negative/() input = increase AR
        $amountStr = $validated['amount'];
        $isNegativeInput = strpos($amountStr, '(') !== false || strpos($amountStr, '-') !== false;
        $amount = floatval(str_replace(['(', ')', ',', ' ', '-'], '', $amountStr));

        // Flip: positive input → negative (decrease AR), negative input → positive (increase AR)
        if ($isNegativeInput) {
            $amount = abs($amount);
            $isDecrease = false;
        } else {
            $amount = -abs($amount);
            $isDecrease = true;
        }

        // Get GL account code if ID is provided
        $glAccountCode = null;
        if (!empty($validated['gl_account_id'])) {
            $glAccountRecord = \App\Models\GlAccount::find($validated['gl_account_id']);
            if ($glAccountRecord) {
                $glAccountCode = $glAccountRecord->account_code;
            }
        }

        // Handle file upload
        $attachmentPath = null;
        $attachmentName = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentName = $file->getClientOriginalName();
            $attachmentPath = $file->store('ar-adjustment-attachments', 'public');
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
            'branch' => $validated['branch'] ?? null,
            'amount' => $amount,
            'is_decrease' => $isDecrease,
            'gl_account' => $glAccountCode ?? $validated['gl_account'] ?? null,
            'gl_account_id' => $validated['gl_account_id'] ?? null,
            'receiving_report_id' => $validated['receiving_report_id'] ?? null,
            'delivery_id' => $validated['delivery_id'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'signed_by' => $validated['signed_by'],
            'created_by' => Auth::user()->name ?? 'System',
        ]);

        // ✅ Update AR Aging by invoice_number OR dr_no
        if (!empty($validated['customer_code']) && (!empty($validated['invoice_number']) || !empty($validated['dr_no']))) {
            $this->applyAdjustmentToAR(
                $validated['customer_code'],
                $amount,
                $validated['invoice_number'] ?? null,
                $validated['dr_no'] ?? null,
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
        $adjustment = ArAdjustment::with(['receivingReport.items', 'delivery', 'glAccount'])->findOrFail($id);
        return view('ar_adjustments.show', compact('adjustment'));
    }

    /**
     * Print Debit or Credit Memo
     */
    public function printMemo($id, Request $request)
    {
        $adjustment = ArAdjustment::with(['receivingReport.items', 'delivery', 'glAccount'])->findOrFail($id);
        // Derive memo type from transaction_type, fallback to query param
        $memoType = $adjustment->transaction_type === 'debit_memo' ? 'debit' : ($adjustment->transaction_type === 'credit_memo' ? 'credit' : $request->query('type', 'credit'));

        // Get customer record for TIN and Business Style
        $customer = Customer::where('customer_code', $adjustment->customer_code)->first();

        // Generate memo number from reference number
        $memoNumber = str_replace('ADJ-', ($memoType === 'debit' ? 'DM-' : 'CM-'), $adjustment->reference_number);

        // Format customer code to C0000000001-000 format
        $rawCode = preg_replace('/[^0-9\-]/', '', $adjustment->customer_code ?? '');
        $parts = explode('-', $rawCode);
        $mainPart = str_pad($parts[0] ?? '0', 10, '0', STR_PAD_LEFT);
        $subPart = str_pad($parts[1] ?? '0', 3, '0', STR_PAD_LEFT);
        $formattedCustomerCode = 'C' . $mainPart . '-' . $subPart;

        return view('ar_adjustments.print_memo', compact('adjustment', 'memoType', 'memoNumber', 'customer', 'formattedCustomerCode'));
    }

    /**
     * Show edit form
     */
    public function editForm($id)
{
    $adjustment = ArAdjustment::findOrFail($id);

    $glAccounts = \App\Models\GlAccount::orderBy('account_code')
        ->get(['id', 'account_code', 'account_name', 'fs_line_item'])
        ->map(function($account) {
            return [
                'id'          => $account->id,
                'code'        => $account->account_code,
                'name'        => $account->account_name,
                'display'     => $account->account_code . ' - ' . $account->account_name,
                'search'      => strtolower($account->account_code . ' ' . $account->account_name),
                'fs_line_item'=> $account->fs_line_item,
            ];
        });

    return view('ar_adjustments.edit', compact('adjustment', 'glAccounts'));
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
                'transaction_type' => 'required|in:atd,offset,credit_memo,debit_memo,adjustment,write_off,sales_return_allowances,price_adjustment,rebates,distribution_fees,penalty,promotional_expenses,small_balance_adjustment',
                'dr_no' => 'nullable|string|max:255',
                'invoice_number' => 'nullable|string|max:255',
                'customer_code' => 'nullable|string|max:255',
                'customer_name' => 'required|string|max:255',
                'amount' => 'required|string',
                'gl_account' => 'nullable|string|max:255',
                'gl_account_id' => 'nullable|exists:gl_accounts,id',
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

            // Get GL account code if ID is provided
            $glAccountCode = null;
            if (!empty($validated['gl_account_id'])) {
                $glAccountRecord = \App\Models\GlAccount::find($validated['gl_account_id']);
                if ($glAccountRecord) {
                    $glAccountCode = $glAccountRecord->account_code;
                }
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
                'gl_account' => $glAccountCode ?? $validated['gl_account'] ?? null,
                'gl_account_id' => $validated['gl_account_id'] ?? null,
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
     * Print a single adjustment as a credit/debit memo
     */
    public function printDoc($id)
    {
        $adjustment = ArAdjustment::with(['delivery', 'receivingReport', 'glAccount'])->findOrFail($id);
        return view('ar_adjustments.print', compact('adjustment'));
    }

    /**
     * Delete an adjustment
     */
    public function destroy(Request $request, $id)
    {
        try {
            $adjustment = ArAdjustment::findOrFail($id);
            
            DB::beginTransaction();

            // ✅ Reverse the adjustment in AR Aging if applicable
            if (!empty($adjustment->customer_code) && (!empty($adjustment->invoice_number) || !empty($adjustment->dr_no))) {
                $this->applyAdjustmentToAR(
                    $adjustment->customer_code,
                    -$adjustment->amount, // Reverse the amount
                    $adjustment->invoice_number ?? null,
                    $adjustment->dr_no ?? null,
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
    private function applyAdjustmentToAR($customerCode, $adjustmentAmount, $invoiceNumber = null, $drNo = null, $transactionDate, $referenceNumber)
    {
        $query = ArAging::where('customer_code', $customerCode);

        // ✅ If specific invoice or DR, adjust that one
        if ($invoiceNumber) {
            $query->where('invoice_no', $invoiceNumber);
        } elseif ($drNo) {
            // Search by DR number if invoice number not provided
            $query->where('dr_no', $drNo);
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
                'invoice_number' => $invoiceNumber,
                'dr_no' => $drNo
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
            'customer_code' => $customerCode,
            'transaction_type' => 'Adjustment',
            'gross_amount' => $adjustmentAmount,
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
    public function getPendingDeliveries(Request $request)
    {
        try {
            $query = \App\Models\Deliveries::select(
                'id',
                'dr_no',
                DB::raw("TRIM(IFNULL(customer_code, '')) as customer_code"),
                'customer_name',
                'status',
                'approval_status',
                'request_delivery_date',
                'created_at'
            )
            ->where('status', 'Delivered')  // Only delivered orders
            ->where('is_pulled_out', '!=', 1)  // Exclude pulled out deliveries
            ->where('is_locked', '!=', 1)  // Exclude locked deliveries
            ->whereNotExists(function ($query) {
                // Exclude deliveries that have a matching AR adjustment by DR number
                $query->select(DB::raw(1))
                    ->from('ar_adjustments')
                    ->whereColumn('ar_adjustments.dr_no', '=', 'deliveries.dr_no');
            });

            // ✅ Apply optional date filtering
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $deliveries = $query->orderBy('created_at', 'desc')  // Show newest first by creation date
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
            ->where(function ($query) use ($customerCode) {
                // Match by customer_code OR customer_name (trimmed)
                $query->where('customer_code', $customerCode)
                    ->orWhereRaw('TRIM(customer_name) = ?', [trim($customerCode)]);
            })
            ->where('status', 'Delivered')
            ->where('is_pulled_out', '!=', 1)
            ->where('is_locked', '!=', 1)
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
    /**
     * Search deliveries for linking in AR Adjustments
     */
    public function searchDeliveries(Request $request)
    {
        try {
            $search = trim($request->input('search', ''));
            if (strlen($search) < 2) {
                return response()->json(['results' => []]);
            }

            $deliveries = Deliveries::where(function($q) use ($search) {
                    $q->where('dr_no', 'LIKE', "%{$search}%")
                      ->orWhere('customer_name', 'LIKE', "%{$search}%")
                      ->orWhere('customer_code', 'LIKE', "%{$search}%")
                      ->orWhere('sales_order_number', 'LIKE', "%{$search}%");
                })
                ->select('id', 'dr_no', 'customer_code', 'customer_name', 'branch',
                         'sales_order_number', 'sales_invoice_no', 'status',
                         'request_delivery_date')
                ->orderBy('request_delivery_date', 'desc')
                ->limit(20)
                ->get()
                ->map(function($d) {
                    // Get delivery items total
                    $totalAmount = \App\Models\DeliveryItem::where('delivery_id', $d->id)->sum('total_amount');
                    return [
                        'id' => $d->id,
                        'dr_no' => $d->dr_no,
                        'customer_code' => $d->customer_code,
                        'customer_name' => $d->customer_name,
                        'branch' => $d->branch,
                        'sales_order_number' => $d->sales_order_number,
                        'sales_invoice_no' => $d->sales_invoice_no,
                        'status' => $d->status,
                        'delivery_date' => $d->request_delivery_date,
                        'total_amount' => $totalAmount,
                    ];
                });

            return response()->json(['results' => $deliveries]);
        } catch (\Exception $e) {
            Log::error('Delivery search failed', ['error' => $e->getMessage()]);
            return response()->json(['results' => []], 500);
        }
    }

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

    /**
     * Get GL accounts for dropdown with search
     */
    public function getGlAccounts(Request $request)
    {
        try {
            $search = $request->get('search', '');

            $query = \App\Models\GlAccount::query();

            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('account_code', 'LIKE', "%{$search}%")
                      ->orWhere('account_name', 'LIKE', "%{$search}%")
                      ->orWhere('fs_line_item', 'LIKE', "%{$search}%");
                });
            }

            $accounts = $query->orderBy('account_code')->take(50)->get();

            return response()->json([
                'success' => true,
                'accounts' => $accounts->map(function($account) {
                    return [
                        'id' => $account->id,
                        'code' => $account->account_code,
                        'name' => $account->account_name,
                        'display' => $account->account_code ? $account->account_code . ' - ' . $account->account_name : $account->account_name,
                        'fs_line_item' => $account->fs_line_item
                    ];
                })
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch GL accounts', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load GL accounts'
            ], 500);
        }
    }
}