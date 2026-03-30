<?php

namespace App\Http\Controllers;

use App\Models\AccountsPayableInvoice;
use App\Models\RequestForPayment;
use App\Models\CashAdvanceRequest;
use App\Models\ReimbursementForm;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AccountsPayableInvoiceController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::user()->canAccessAPV()) {
            abort(403, 'Unauthorized action.');
        }

        $query = AccountsPayableInvoice::with(['creator', 'requestForPayment']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('apv_no', 'like', "%{$s}%")
                  ->orWhere('vendor_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('apv_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('apv_date', '<=', $request->date_to);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->orderByDesc('created_at')->paginate(20);

        return view('accounts_payable_invoices.index', compact('invoices'));
    }

    public function exportExcel(Request $request)
    {
        if (!Auth::user()->canAccessAPV()) {
            abort(403, 'Unauthorized action.');
        }

        $query = AccountsPayableInvoice::with(['creator', 'requestForPayment']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('apv_no', 'like', "%{$s}%")
                  ->orWhere('vendor_name', 'like', "%{$s}%");
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('apv_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('apv_date', '<=', $request->date_to);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->orderByDesc('created_at')->get();

        $filename = 'AP_Invoices_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($invoices) {
            $file = fopen('php://output', 'w');
            // UTF-8 BOM for Excel
            fwrite($file, "\xEF\xBB\xBF");

            fputcsv($file, [
                'APV No', 'APV Date', 'RFP No', 'Vendor Name', 'Payment Type',
                'Currency', 'Grand Total', 'Status', 'Due Date', 'Created By', 'Created At',
            ]);

            foreach ($invoices as $inv) {
                fputcsv($file, [
                    $inv->apv_no,
                    $inv->apv_date ? $inv->apv_date->format('Y-m-d') : '',
                    $inv->requestForPayment->rfp_no ?? 'N/A',
                    $inv->vendor_name,
                    $inv->payment_type === 'downpayment' ? 'Downpayment' : 'Full Payment',
                    $inv->currency,
                    number_format($inv->grand_total, 2),
                    ucfirst($inv->status),
                    $inv->due_date ? $inv->due_date->format('Y-m-d') : '',
                    $inv->creator->name ?? 'N/A',
                    $inv->created_at ? $inv->created_at->format('Y-m-d H:i') : '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function create(Request $request)
    {
        // Check if user can create APV invoices
        if (!Auth::user()->canCreateAPV()) {
            abort(403, 'Unauthorized action.');
        }

        // Generate APV number
        $apvNo = 'APV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // Check if an RFP was selected
        $selectedRFP = null;
        $supplierInfo = null;
        if ($request->has('rfp_id')) {
            $selectedRFP = RequestForPayment::with(['purchaseOrder.supplierModel'])->find($request->rfp_id);
            if ($selectedRFP && $selectedRFP->purchaseOrder) {
                $supplier = $selectedRFP->purchaseOrder->supplierModel;
                $supplierInfo = [
                    'address' => $supplier->address ?? $selectedRFP->purchaseOrder->supplier_address ?? '',
                    'tin' => $supplier->tin ?? '',
                    'code' => $supplier->supplier_code ?? '',
                ];
            }
        }

        $glAccounts = $this->getGlAccounts();

        return view('accounts_payable_invoices.create', compact('apvNo', 'selectedRFP', 'supplierInfo', 'glAccounts'));
    }

    /**
     * Search approved RFPs (AJAX)
     */
    public function searchRFPs(Request $request)
    {
        $searchTerm = $request->input('search', '');

        $rfps = RequestForPayment::with(['purchaseOrder.supplierModel'])
            ->where('status', 'approved')
            ->where(function ($query) use ($searchTerm) {
                $query->where('rfp_no', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('payee', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('company', 'LIKE', "%{$searchTerm}%");
            })
            ->limit(10)
            ->get()
            ->map(function ($rfp) {
                $supplier = $rfp->purchaseOrder->supplierModel ?? null;
                return [
                    'id' => $rfp->id,
                    'rfp_no' => $rfp->rfp_no,
                    'payee' => $rfp->payee,
                    'company' => $rfp->company,
                    'date' => $rfp->date,
                    'amount' => (float) $rfp->amount,
                    'particulars' => $rfp->particulars,
                    'purchase_order_no' => $rfp->purchaseOrder->po_no ?? '',
                    'vendor_address' => $supplier->address ?? $rfp->purchaseOrder->supplier_address ?? '',
                    'vendor_tin' => $supplier->tin ?? '',
                    'vendor_code' => $supplier->supplier_code ?? '',
                ];
            });

        return response()->json($rfps);
    }

    /**
     * Search approved Cash Advance Requests (AJAX)
     */
    public function searchCARs(Request $request)
    {
        $searchTerm = $request->input('search', '');

        $cars = CashAdvanceRequest::where('status', 'approved')
            ->where(function ($query) use ($searchTerm) {
                $query->where('car_no', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('payee', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('department', 'LIKE', "%{$searchTerm}%");
            })
            ->limit(10)
            ->get()
            ->map(function ($car) {
                return [
                    'id' => $car->id,
                    'car_no' => $car->car_no,
                    'payee' => $car->payee,
                    'department' => $car->department,
                    'amount' => (float) $car->amount_advanced,
                    'purpose' => $car->purpose,
                    'date_requested' => $car->date_requested?->format('Y-m-d'),
                ];
            });

        return response()->json($cars);
    }

    /**
     * Search approved Reimbursement Forms (AJAX)
     */
    public function searchReimbursements(Request $request)
    {
        $searchTerm = $request->input('search', '');

        $reimbursements = ReimbursementForm::where('status', 'approved')
            ->where(function ($query) use ($searchTerm) {
                $query->where('ri_no', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('department', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('submitted_by', 'LIKE', "%{$searchTerm}%");
            })
            ->limit(10)
            ->get()
            ->map(function ($ri) {
                return [
                    'id' => $ri->id,
                    'ri_no' => $ri->ri_no,
                    'department' => $ri->department,
                    'submitted_by' => $ri->submitted_by,
                    'amount' => (float) $ri->amount_to_be_reimbursed,
                    'total_spent' => (float) $ri->total_amount_spent,
                    'date_applied' => $ri->date_applied?->format('Y-m-d'),
                ];
            });

        return response()->json($reimbursements);
    }

    /**
     * Store a newly created invoice
     */
    public function store(Request $request)
    {
        // Check if user can create APV invoices
        if (!Auth::user()->canCreateAPV()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'apv_date' => 'required|date',
            'payment_type' => 'required|in:full_payment,downpayment',
            'vendor_name' => 'required|string',
            'document_date' => 'required|date',
            'particulars' => 'required|string',
            'total' => 'required|numeric|min:0',
            'currency' => 'required|string',
        ]);

        // Validate total does not exceed linked RFP amount
        if ($request->request_for_payment_id) {
            $rfp = RequestForPayment::find($request->request_for_payment_id);
            if ($rfp && (float) $request->total > (float) $rfp->amount) {
                return back()->withInput()->with('error', 'Total amount (₱' . number_format($request->total, 2) . ') exceeds RFP amount (₱' . number_format($rfp->amount, 2) . ').');
            }
        }

        DB::beginTransaction();
        try {
            // Generate APV number
            $apvNo = 'APV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // Calculate totals
            $total = $request->total;
            $downpaymentAmount = $request->downpayment_amount ?? 0;
            $vatAmount = $request->vat_amount ?? 0;
            $wTaxAmount = $request->w_tax_amount ?? 0;

            // Calculate based on payment type
            $totalBeforeVat = $request->payment_type === 'downpayment' ? $downpaymentAmount : $total;
            $totalAfterVat = $totalBeforeVat + $vatAmount;
            $grandTotal = $totalAfterVat - $wTaxAmount;

            // Create invoice
            $invoice = AccountsPayableInvoice::create([
                'apv_no' => $apvNo,
                'request_for_payment_id' => $request->request_for_payment_id,
                'cash_advance_request_id' => $request->cash_advance_request_id,
                'reimbursement_form_id' => $request->reimbursement_form_id,
                'reference_type' => $request->reference_type,
                'apv_date' => $request->apv_date,
                'payment_type' => $request->payment_type,
                'vendor_code' => $request->vendor_code,
                'vendor_name' => $request->vendor_name,
                'vendor_address' => $request->vendor_address,
                'vendor_tin' => $request->vendor_tin,
                'document_date' => $request->document_date,
                'payment_terms' => $request->payment_terms,
                'due_date' => $request->due_date,
                'reference_no' => $request->reference_no,
                'purchase_order_no' => $request->purchase_order_no,
                'currency' => $request->currency,
                'forex_rate' => $request->forex_rate,
                'particulars' => $request->particulars,
                'item_code' => $request->item_code,
                'cost_center' => $request->cost_center,
                'account_code' => $request->account_code,
                'account_name' => $request->account_name,
                'total' => $total,
                'downpayment_amount' => $downpaymentAmount,
                'total_before_vat' => $totalBeforeVat,
                'vat_amount' => $vatAmount,
                'total_after_vat' => $totalAfterVat,
                'w_tax_amount' => $wTaxAmount,
                'grand_total' => $grandTotal,
                'prepared_by' => $request->prepared_by,
                'reviewed_by' => $request->reviewed_by,
                'remarks' => $request->remarks,
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'Created',
                'item' => $invoice->apv_no,
                'target' => $invoice->vendor_name,
                'type' => 'Accounts Payable Invoice',
                'message' => 'Created Accounts Payable Invoice ' . $invoice->apv_no . ' for ' . $invoice->vendor_name,
            ]);

            return redirect()
                ->route('accounts_payable_invoices.show', $invoice->id)
                ->with('success', 'Accounts Payable Invoice created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error creating invoice: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified invoice
     */
    public function show($id)
    {
        $invoice = AccountsPayableInvoice::with(['creator', 'requestForPayment', 'approver', 'departmentHeadApprover'])
            ->findOrFail($id);

        return view('accounts_payable_invoices.show', compact('invoice'));
    }

    /**
     * Show the form for editing the specified invoice
     */
    public function edit($id)
    {
        // Check if user can create/edit APV invoices
        if (!Auth::user()->canCreateAPV()) {
            abort(403, 'Unauthorized action.');
        }

        $invoice    = AccountsPayableInvoice::findOrFail($id);
        $glAccounts = $this->getGlAccounts();

        return view('accounts_payable_invoices.edit', compact('invoice', 'glAccounts'));
    }

    /**
     * Update the specified invoice
     */
    public function update(Request $request, $id)
    {
        // Check if user can create/edit APV invoices
        if (!Auth::user()->canCreateAPV()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'apv_date' => 'required|date',
            'payment_type' => 'required|in:full_payment,downpayment',
            'vendor_name' => 'required|string',
            'document_date' => 'required|date',
            'particulars' => 'required|string',
            'total' => 'required|numeric|min:0',
            'currency' => 'required|string',
        ]);

        // Validate total does not exceed linked RFP amount
        $invoice = AccountsPayableInvoice::findOrFail($id);
        $rfpId = $request->request_for_payment_id ?: $invoice->request_for_payment_id;
        if ($rfpId) {
            $rfp = RequestForPayment::find($rfpId);
            if ($rfp && (float) $request->total > (float) $rfp->amount) {
                return back()->withInput()->with('error', 'Total amount (₱' . number_format($request->total, 2) . ') exceeds RFP amount (₱' . number_format($rfp->amount, 2) . ').');
            }
        }

        DB::beginTransaction();
        try {
            // Calculate totals
            $total = $request->total;
            $downpaymentAmount = $request->downpayment_amount ?? 0;
            $vatAmount = $request->vat_amount ?? 0;
            $wTaxAmount = $request->w_tax_amount ?? 0;

            // Calculate based on payment type
            $totalBeforeVat = $request->payment_type === 'downpayment' ? $downpaymentAmount : $total;
            $totalAfterVat = $totalBeforeVat + $vatAmount;
            $grandTotal = $totalAfterVat - $wTaxAmount;

            // Update invoice
            $invoice->update([
                'apv_date' => $request->apv_date,
                'payment_type' => $request->payment_type,
                'vendor_code' => $request->vendor_code,
                'vendor_name' => $request->vendor_name,
                'vendor_address' => $request->vendor_address,
                'vendor_tin' => $request->vendor_tin,
                'document_date' => $request->document_date,
                'payment_terms' => $request->payment_terms,
                'due_date' => $request->due_date,
                'reference_no' => $request->reference_no,
                'purchase_order_no' => $request->purchase_order_no,
                'currency' => $request->currency,
                'forex_rate' => $request->forex_rate,
                'particulars' => $request->particulars,
                'item_code' => $request->item_code,
                'cost_center' => $request->cost_center,
                'account_code' => $request->account_code,
                'account_name' => $request->account_name,
                'total' => $total,
                'downpayment_amount' => $downpaymentAmount,
                'total_before_vat' => $totalBeforeVat,
                'vat_amount' => $vatAmount,
                'total_after_vat' => $totalAfterVat,
                'w_tax_amount' => $wTaxAmount,
                'grand_total' => $grandTotal,
                'prepared_by' => $request->prepared_by,
                'reviewed_by' => $request->reviewed_by,
                'remarks' => $request->remarks,
            ]);

            DB::commit();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'Updated',
                'item' => $invoice->apv_no,
                'target' => $invoice->vendor_name,
                'type' => 'Accounts Payable Invoice',
                'message' => 'Updated Accounts Payable Invoice ' . $invoice->apv_no,
            ]);

            return redirect()
                ->route('accounts_payable_invoices.show', $invoice->id)
                ->with('success', 'Invoice updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error updating invoice: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified invoice
     */
    public function destroy($id)
    {
        try {
            $invoice = AccountsPayableInvoice::findOrFail($id);
            $apvNo = $invoice->apv_no;
            $invoice->delete();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'Deleted',
                'item' => $apvNo,
                'target' => 'N/A',
                'type' => 'Accounts Payable Invoice',
                'message' => 'Deleted Accounts Payable Invoice ' . $apvNo,
            ]);

            return redirect()
                ->route('accounts_payable_invoices.index')
                ->with('success', 'Invoice deleted successfully!');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Error deleting invoice: ' . $e->getMessage());
        }
    }

    /**
     * Approve as Department Head (Level 1 - Review)
     */
    public function approveDH(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->canApproveAPVInvoices()) {
            return redirect()->route('accounts_payable_invoices.index')->with('error', 'Unauthorized.');
        }

        $invoice = AccountsPayableInvoice::where('approval_stage', 'pending_dh')->findOrFail($id);
        $invoice->update([
            'approval_stage' => 'pending_accounting',
            'department_head_approved_by' => Auth::id(),
            'department_head_approved_at' => now(),
            'department_head_approved_latitude' => $request->input('latitude'),
            'department_head_approved_longitude' => $request->input('longitude'),
            'department_head_approved_location' => $request->input('location'),
            'rejection_reason' => null,
        ]);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Reviewed (DH)',
            'item' => $invoice->apv_no,
            'target' => $invoice->vendor_name,
            'type' => 'Accounts Payable Invoice',
            'message' => 'Department Head reviewed Accounts Payable Invoice ' . $invoice->apv_no,
        ]);

        return redirect()->back()->with('success', 'APV reviewed by Department Head. Forwarded to Accounting Manager.');
    }

    /**
     * Approve as Accounting Manager (Level 2 - Final)
     */
    public function approve(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->canApproveAPV()) {
            return redirect()->route('accounts_payable_invoices.index')->with('error', 'Unauthorized.');
        }

        $invoice = AccountsPayableInvoice::where('approval_stage', 'pending_accounting')->findOrFail($id);
        $invoice->update([
            'status' => 'approved',
            'approval_stage' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'approved_latitude' => $request->input('latitude'),
            'approved_longitude' => $request->input('longitude'),
            'approved_location' => $request->input('location'),
            'rejection_reason' => null,
        ]);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Approved',
            'item' => $invoice->apv_no,
            'target' => $invoice->vendor_name,
            'type' => 'Accounts Payable Invoice',
            'message' => 'Approved Accounts Payable Invoice ' . $invoice->apv_no,
        ]);

        return redirect()->back()->with('success', 'Invoice approved!');
    }

    /**
     * Reject an invoice (any stage)
     */
    public function reject(Request $request, $id)
    {
        // Check if user can approve/reject APV invoices
        if (!Auth::user()->canApproveAPVInvoices()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate(['rejection_reason' => 'nullable|string|max:500']);
        $invoice = AccountsPayableInvoice::findOrFail($id);

        $invoice->update([
            'status' => 'rejected',
            'approval_stage' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Rejected',
            'item' => $invoice->apv_no,
            'target' => $invoice->vendor_name,
            'type' => 'Accounts Payable Invoice',
            'message' => 'Rejected Accounts Payable Invoice ' . $invoice->apv_no,
        ]);

        return redirect()->back()->with('success', 'Invoice rejected.');
    }

    /**
     * Print accounts payable invoice
     */
    public function print($id)
    {
        $apv = AccountsPayableInvoice::with(['creator', 'approver', 'departmentHeadApprover'])->findOrFail($id);
        return view('accounts_payable_invoices.print', ['apv' => $apv]);
    }

    private function getGlAccounts(): array
    {
        return \App\Models\GlAccount::orderBy('account_code')
            ->get(['account_code', 'account_name'])
            ->map(fn($a) => [
                'code'    => $a->account_code,
                'name'    => $a->account_name,
                'display' => $a->account_code . ' — ' . $a->account_name,
                'search'  => strtolower($a->account_code . ' ' . $a->account_name),
            ])->toArray();
    }
}
