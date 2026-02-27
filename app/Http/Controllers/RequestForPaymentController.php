<?php

namespace App\Http\Controllers;

use App\Models\RequestForPayment;
use App\Models\PurchaseOrder;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class RequestForPaymentController extends Controller
{
    /**
     * Display a listing of request for payments
     */
    public function index()
    {
        $rfps = RequestForPayment::with(['creator', 'purchaseOrder'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('request_for_payments.index', compact('rfps'));
    }

    /**
     * Show the form for creating a new request for payment
     */
    public function create(Request $request)
    {
        // Generate RFP number
        $rfpNo = 'RFP-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // Define companies
        $companies = [
            'North Breeders Corporation',
            'Pacific Agro Resources Inc.',
            'Pacific Magalang Agriventures Inc.',
            'Pacific Agrisolutions Enterprises Inc.',
        ];

        // Check if a PO was selected
        $selectedPO = null;
        $poAmount = 0;
        if ($request->has('po_id')) {
    $selectedPO = PurchaseOrder::with(['items.supplierModel', 'supplierModel'])->find($request->po_id);
    if ($selectedPO) {
        $poAmount = $selectedPO->items->sum('total') ?: (float) $selectedPO->lc_price;
    }
}

        return view('request_for_payments.create', compact('rfpNo', 'companies', 'selectedPO', 'poAmount'));
    }

    /**
     * Search approved POs (AJAX)
     */
    public function searchPOs(Request $request)
{
    $searchTerm = $request->input('search', '');

    try {
        $pos = PurchaseOrder::with(['items.supplierModel', 'supplierModel'])
            ->where('status', 'approved')
            ->where(function ($query) use ($searchTerm) {
                $query->where('po_no', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('supplier', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('company', 'LIKE', "%{$searchTerm}%")
                    ->orWhereHas('supplierModel', function($q) use ($searchTerm) {
                        $q->where('supplier_name', 'LIKE', "%{$searchTerm}%");
                    });
            })
            ->limit(10)
            ->get()
            ->map(function ($po) {
                $poAmount = $po->items->sum('total') ?: (float) $po->lc_price;

                $itemSupplierName = $po->items
                    ->map(fn($item) => $item->supplierModel->supplier_name ?? $item->supplier_name ?? null)
                    ->filter()
                    ->unique()
                    ->implode(' / ');

                $supplierName = $itemSupplierName
                    ?: $po->supplierModel->supplier_name
                    ?? $po->supplier
                    ?? 'N/A';

                return [
                    'id'               => $po->id,
                    'po_no'            => $po->po_no,
                    'supplier'         => $supplierName,
                    'company'          => $po->company,
                    'order_date'       => $po->order_date ? $po->order_date->format('Y-m-d') : null,
                    'amount'           => round($poAmount, 2),
                    'currency'         => $po->currency ?? 'PHP',
                    'supplier_address' => $po->supplierModel->address ?? $po->supplier_address ?? '',
                    'supplier_tin'     => $po->supplierModel->tin ?? '',
                ];
            });

        return response()->json($pos);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'line'  => $e->getLine(),
            'file'  => $e->getFile(),
        ], 500);
    }
}

    /**
     * Store a newly created request for payment
     */
    public function store(Request $request)
    {
        $request->validate([
            'company' => 'required|string',
            'date' => 'required|date',
            'payee' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'payment_methods' => 'array',
        ]);

        // Validate amount does not exceed linked PO total
        if ($request->purchase_order_id) {
            $po = PurchaseOrder::with('items')->find($request->purchase_order_id);
            if ($po) {
                $poAmount = $po->items->sum('total') ?: (float) $po->lc_price;
                if ($poAmount > 0 && (float) $request->amount > $poAmount) {
                    return back()->withInput()->with('error', 'Amount (₱' . number_format($request->amount, 2) . ') exceeds PO total (₱' . number_format($poAmount, 2) . ').');
                }
            }
        }

        DB::beginTransaction();
        try {
            // Generate RFP number
            $rfpNo = 'RFP-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // Create request for payment
            $rfp = RequestForPayment::create([
                'rfp_no' => $rfpNo,
                'purchase_order_id' => $request->purchase_order_id,
                'company' => $request->company,
                'payment_methods' => json_encode($request->payment_methods ?? []),
                'date' => $request->date,
                'due_date' => $request->due_date,
                'payee' => $request->payee,
                'amount' => $request->amount,
                'particulars' => $request->particulars,
                'bank' => $request->bank,
                'apv_no' => $request->apv_no,
                'cv_no' => $request->cv_no,
                'requested_by' => $request->requested_by,
                'checked_by' => $request->checked_by,
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'Created',
                'item' => $rfp->rfp_no,
                'target' => $rfp->payee,
                'type' => 'Request For Payment',
                'message' => 'Created Request for Payment ' . $rfp->rfp_no . ' for ' . $rfp->payee,
            ]);

            return redirect()
                ->route('request_for_payments.show', $rfp->id)
                ->with('success', 'Request for Payment created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error creating Request for Payment: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified request for payment
     */
    public function show($id)
    {
        $rfp = RequestForPayment::with(['creator', 'purchaseOrder', 'approver', 'departmentHeadApprover', 'accountingApprover'])
            ->findOrFail($id);

        return view('request_for_payments.show', compact('rfp'));
    }

    /**
     * Show the form for editing the specified request for payment
     */
    public function edit($id)
    {
        $rfp = RequestForPayment::findOrFail($id);

        $companies = [
            'North Breeders Corporation',
            'Pacific Agro Resources Inc.',
            'Pacific Magalang Agriventures Inc.',
            'Pacific Agrisolutions Enterprises Inc.',
        ];

        $purchaseOrders = PurchaseOrder::where('status', 'approved')
            ->orderByDesc('created_at')
            ->get();

        return view('request_for_payments.edit', compact('rfp', 'companies', 'purchaseOrders'));
    }

    /**
     * Update the specified request for payment
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'company' => 'required|string',
            'date' => 'required|date',
            'payee' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'payment_methods' => 'array',
        ]);

        // Validate amount does not exceed linked PO total
        $poId = $request->purchase_order_id ?: RequestForPayment::find($id)?->purchase_order_id;
        if ($poId) {
            $po = PurchaseOrder::with('items')->find($poId);
            if ($po) {
                $poAmount = $po->items->sum('total') ?: (float) $po->lc_price;
                if ($poAmount > 0 && (float) $request->amount > $poAmount) {
                    return back()->withInput()->with('error', 'Amount (₱' . number_format($request->amount, 2) . ') exceeds PO total (₱' . number_format($poAmount, 2) . ').');
                }
            }
        }

        DB::beginTransaction();
        try {
            $rfp = RequestForPayment::findOrFail($id);

            // Update request for payment
            $rfp->update([
                'purchase_order_id' => $request->purchase_order_id,
                'company' => $request->company,
                'payment_methods' => json_encode($request->payment_methods ?? []),
                'date' => $request->date,
                'due_date' => $request->due_date,
                'payee' => $request->payee,
                'amount' => $request->amount,
                'particulars' => $request->particulars,
                'bank' => $request->bank,
                'apv_no' => $request->apv_no,
                'cv_no' => $request->cv_no,
                'requested_by' => $request->requested_by,
                'checked_by' => $request->checked_by,
            ]);

            DB::commit();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'Updated',
                'item' => $rfp->rfp_no,
                'target' => $rfp->payee,
                'type' => 'Request For Payment',
                'message' => 'Updated Request for Payment ' . $rfp->rfp_no,
            ]);

            return redirect()
                ->route('request_for_payments.show', $rfp->id)
                ->with('success', 'Request for Payment updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error updating Request for Payment: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified request for payment
     */
    public function destroy($id)
    {
        try {
            $rfp = RequestForPayment::findOrFail($id);
            $rfpNo = $rfp->rfp_no;
            $rfp->delete();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'Deleted',
                'item' => $rfpNo,
                'target' => 'N/A',
                'type' => 'Request For Payment',
                'message' => 'Deleted Request for Payment ' . $rfpNo,
            ]);

            return redirect()
                ->route('request_for_payments.index')
                ->with('success', 'Request for Payment deleted successfully!');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Error deleting Request for Payment: ' . $e->getMessage());
        }
    }

    /**
     * Approve as Department Head (Level 1)
     */
    public function approveDH(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->canApproveRFPAsDH()) {
            return redirect()->route('request_for_payments.index')->with('error', 'Unauthorized.');
        }

        $rfp = RequestForPayment::where('approval_stage', 'pending_dh')->findOrFail($id);
        $rfp->update([
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
            'action' => 'Approved (DH)',
            'item' => $rfp->rfp_no,
            'target' => $rfp->payee,
            'type' => 'Request For Payment',
            'message' => 'Department Head checked Request for Payment ' . $rfp->rfp_no,
        ]);

        return redirect()->back()->with('success', 'RFP checked by Department Head. Forwarded to Accounting.');
    }

    /**
     * Approve as Accounting (Level 2)
     */
    public function approveAccounting(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->canApproveRFPAsAccounting()) {
            return redirect()->route('request_for_payments.index')->with('error', 'Unauthorized.');
        }

        $rfp = RequestForPayment::where('approval_stage', 'pending_accounting')->findOrFail($id);
        $rfp->update([
            'approval_stage' => 'pending_executive',
            'accounting_approved_by' => Auth::id(),
            'accounting_approved_at' => now(),
            'accounting_approved_latitude' => $request->input('latitude'),
            'accounting_approved_longitude' => $request->input('longitude'),
            'accounting_approved_location' => $request->input('location'),
            'rejection_reason' => null,
        ]);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Approved (Accounting)',
            'item' => $rfp->rfp_no,
            'target' => $rfp->payee,
            'type' => 'Request For Payment',
            'message' => 'Accounting checked Request for Payment ' . $rfp->rfp_no,
        ]);

        return redirect()->back()->with('success', 'RFP checked by Accounting. Forwarded to CFO/President.');
    }

    /**
     * Approve as Executive - CFO/President (Level 3 - Final)
     */
    public function approve(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->canApproveRFPAsExecutive()) {
            return redirect()->route('request_for_payments.index')->with('error', 'Unauthorized.');
        }

        $rfp = RequestForPayment::where('approval_stage', 'pending_executive')->findOrFail($id);
        $rfp->update([
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
            'item' => $rfp->rfp_no,
            'target' => $rfp->payee,
            'type' => 'Request For Payment',
            'message' => 'Approved Request for Payment ' . $rfp->rfp_no,
        ]);

        return redirect()->back()->with('success', 'Request for Payment approved!');
    }

    /**
     * Reject a request for payment (any stage)
     */
    public function reject(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->canApproveRequestForPayments()) {
            return redirect()->route('request_for_payments.index')->with('error', 'Unauthorized.');
        }

        $request->validate(['rejection_reason' => 'nullable|string|max:500']);
        $rfp = RequestForPayment::findOrFail($id);

        $rfp->update([
            'status' => 'rejected',
            'approval_stage' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Rejected',
            'item' => $rfp->rfp_no,
            'target' => $rfp->payee,
            'type' => 'Request For Payment',
            'message' => 'Rejected Request for Payment ' . $rfp->rfp_no,
        ]);

        return redirect()->back()->with('success', 'Request for Payment rejected!');
    }

    /**
     * Print request for payment
     */
    public function print($id)
    {
        $rfp = RequestForPayment::with(['creator', 'purchaseOrder', 'approver', 'departmentHeadApprover', 'accountingApprover'])->findOrFail($id);
        return view('request_for_payments.print', ['rfp' => $rfp]);
    }
}
