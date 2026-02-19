<?php

namespace App\Http\Controllers;

use App\Models\AccountsPayableInvoice;
use App\Models\RequestForPayment;
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
        if ($request->has('rfp_id')) {
            $selectedRFP = RequestForPayment::with('purchaseOrder')->find($request->rfp_id);
        }

        return view('accounts_payable_invoices.create', compact('apvNo', 'selectedRFP'));
    }

    /**
     * Search approved RFPs (AJAX)
     */
    public function searchRFPs(Request $request)
    {
        $searchTerm = $request->input('search', '');

        $rfps = RequestForPayment::where('status', 'approved')
            ->where(function ($query) use ($searchTerm) {
                $query->where('rfp_no', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('payee', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('company', 'LIKE', "%{$searchTerm}%");
            })
            ->select('id', 'rfp_no', 'payee', 'company', 'date', 'amount')
            ->limit(10)
            ->get();

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
        $invoice = AccountsPayableInvoice::with(['creator', 'requestForPayment', 'approver'])
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

        DB::beginTransaction();
        try {
            $invoice = AccountsPayableInvoice::findOrFail($id);

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
            $invoice->delete();

            return redirect()
                ->route('accounts_payable_invoices.index')
                ->with('success', 'Invoice deleted successfully!');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Error deleting invoice: ' . $e->getMessage());
        }
    }

    /**
     * Approve an invoice
     */
    public function approve($id)
    {
        $invoice = AccountsPayableInvoice::findOrFail($id);

        $invoice->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Invoice approved successfully!');
    }

    /**
     * Reject an invoice
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $invoice = AccountsPayableInvoice::findOrFail($id);

        $invoice->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Invoice rejected.');
    }

    /**
     * Print accounts payable invoice
     */
    public function print($id)
    {
        $apv = AccountsPayableInvoice::findOrFail($id);
        return view('apv.print', ['apv' => $apv]);
    }
}
