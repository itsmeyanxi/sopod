<?php

namespace App\Http\Controllers;

use App\Models\AccountsPayableInvoice;
use App\Models\RequestForPayment;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AccountsPayableInvoiceController extends Controller
{
    /**
     * Display a listing of invoices
     */
    public function index()
    {
        $invoices = AccountsPayableInvoice::with(['creator', 'requestForPayment'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('accounts_payable_invoices.index', compact('invoices'));
    }

    /**
     * Show the form for creating a new invoice
     */
    public function create(Request $request)
    {
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

        return view('accounts_payable_invoices.create', compact('apvNo', 'selectedRFP', 'supplierInfo'));
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
     * Store a newly created invoice
     */
    public function store(Request $request)
    {
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
        $invoice = AccountsPayableInvoice::findOrFail($id);

        return view('accounts_payable_invoices.edit', compact('invoice'));
    }

    /**
     * Update the specified invoice
     */
    public function update(Request $request, $id)
    {
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
        if (!$user->canApproveAPVAsDH()) {
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
}
