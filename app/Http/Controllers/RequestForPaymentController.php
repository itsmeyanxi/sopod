<?php

namespace App\Http\Controllers;

use App\Models\RequestForPayment;
use App\Models\PurchaseOrder;
use App\Models\Activity;
use App\Models\Vendor;
use App\Models\Supplier;
use App\Models\LiveChicken;
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
        $rfpNo = 'Auto-assigned on save';

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

        $payeeOptions = collect();
        $payeeOptions = $payeeOptions->merge(
            Vendor::orderBy('vendor_name')->get(['id', 'vendor_name'])->map(fn($v) => ['label' => $v->vendor_name, 'type' => 'Vendor'])
        )->merge(
            Supplier::where('status', 'active')->orderBy('supplier_name')->get(['id', 'supplier_name'])->map(fn($s) => ['label' => $s->supplier_name, 'type' => 'Supplier'])
        )->sortBy('label')->values();

        $currencyRates = \App\Models\Currency::whereNotNull('rate_to_php')->pluck('rate_to_php', 'code');

        return view('request_for_payments.create', compact('rfpNo', 'companies', 'selectedPO', 'poAmount', 'payeeOptions', 'currencyRates'));
    }

    /**
     * Search approved POs (AJAX)
     */
    public function searchPOs(Request $request)
{
    $searchTerm = $request->input('search', '');

    try {
        // Also find POs linked to GRPOs matching the search term
        $grpoPoNos = LiveChicken::where('grpo_no', 'LIKE', "%{$searchTerm}%")
            ->whereNotNull('po_no')
            ->pluck('po_no')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $pos = PurchaseOrder::with(['items.supplierModel', 'supplierModel'])
            ->where('status', 'approved')
            ->where('is_closed', false)
            ->where(function ($query) use ($searchTerm, $grpoPoNos) {
                $query->where('po_no', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('supplier', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('company', 'LIKE', "%{$searchTerm}%")
                    ->orWhereHas('supplierModel', function($q) use ($searchTerm) {
                        $q->where('supplier_name', 'LIKE', "%{$searchTerm}%");
                    });
                if (!empty($grpoPoNos)) {
                    $query->orWhereIn('po_no', $grpoPoNos);
                }
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

                $paidTotal = \DB::table('request_for_payments')
                    ->where('purchase_order_id', $po->id)
                    ->whereNotIn('status', ['invalidated', 'rejected'])
                    ->sum('amount');
                $fullyPaid = $poAmount > 0 && (float)$paidTotal >= (float)$poAmount;

                return [
                    'id'               => $po->id,
                    'po_no'            => $po->po_no,
                    'supplier'         => $supplierName,
                    'company'          => $po->company,
                    'order_date'       => $po->order_date ? $po->order_date->format('Y-m-d') : null,
                    'amount'           => round($poAmount, 2),
                    'paid_total'       => round((float)$paidTotal, 2),
                    'fully_paid'       => $fullyPaid,
                    'currency'         => $po->currency ?? 'PHP',
                    'supplier_address' => $po->supplierModel->address ?? $po->supplier_address ?? '',
                    'supplier_tin'     => $po->supplierModel->tin ?? '',
                    'payment_terms'    => $po->payment_terms ?? '',
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
     * Get PO items (AJAX)
     */
    public function getPoItems($poId)
    {
        $po = PurchaseOrder::with(['items', 'purchaseRequest'])->find($poId);
        if (!$po) {
            return response()->json(['po_type' => 'items', 'items' => [], 'service_description' => null]);
        }

        // Build remarks hint from PO, PR, and GRPO notes
        $remarksParts = array_filter([
            $po->remarks ? 'PO: ' . $po->remarks : null,
            $po->purchaseRequest?->remarks ? 'PR: ' . $po->purchaseRequest->remarks : null,
        ]);

        // Try to get items from GRPO (live_chickens) linked to this PO
        $grpos = LiveChicken::where('po_no', $po->po_no)->orderBy('date')->get();
        if ($grpos->isNotEmpty()) {
            foreach ($grpos as $g) {
                if ($g->remarks ?? null) $remarksParts[] = 'GRPO ' . $g->grpo_no . ': ' . $g->remarks;
            }
            // Aggregate items by GRPO (each GRPO is one item line)
            $items = $grpos->map(fn($g, $i) => [
                'item_no'    => $i + 1,
                'item_code'  => $g->grpo_no,
                'description'=> $g->items ?? $po->items->first()?->description ?? '',
                'brand'      => $g->brand ?? '',
                'qty'        => $g->actual_qty,
                'uom'        => 'KG',
                'unit_price' => $g->price ?? ($po->lc_price ?? 0),
                'total'      => ($g->actual_qty ?? 0) * ($g->price ?? $po->lc_price ?? 0),
            ]);
            return response()->json([
                'po_type'             => $po->po_type ?? 'items',
                'service_description' => $po->service_description,
                'items'               => $items,
                'terms'               => $po->payment_terms ?? '',
                'remarks_hint'        => implode("\n", $remarksParts),
                'source'              => 'grpo',
                'pr_no'               => $po->pr_no ?? '',
                'po_no'               => $po->po_no ?? '',
                'exchange_rate'       => $po->exchange_rate ?? null,
            ]);
        }

        // Fallback: PO items
        $items = $po->items->map(fn($item, $i) => [
            'item_no'    => $item->item_no ?? $i + 1,
            'item_code'  => $item->item_code ?? '',
            'description'=> $item->description ?? '',
            'brand'      => $item->brand ?? '',
            'qty'        => $item->qty,
            'uom'        => $item->uom ?? '',
            'unit_price' => $item->unit_price,
            'total'      => $item->total,
        ]);
        return response()->json([
            'po_type'             => $po->po_type ?? 'items',
            'service_description' => $po->service_description,
            'items'               => $items,
            'terms'               => $po->payment_terms ?? '',
            'remarks_hint'        => implode("\n", $remarksParts),
            'source'              => 'po',
            'pr_no'               => $po->pr_no ?? '',
            'po_no'               => $po->po_no ?? '',
            'exchange_rate'       => $po->exchange_rate ?? null,
        ]);
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

        DB::beginTransaction();
        try {
            $rfp = RequestForPayment::create([
                'rfp_no' => '',
                'purchase_order_id' => $request->purchase_order_id ?: null,
                'srr_id' => $request->srr_id ?: null,
                'live_chicken_id' => $request->live_chicken_id ?: null,
                'payment_type' => $request->payment_type,
                'company' => $request->company,
                'payment_methods' => json_encode($request->payment_methods ?? []),
                'date' => $request->date,
                'due_date' => $request->due_date,
                'payee' => $request->payee,
                'payment_terms' => $request->payment_terms,
                'amount' => $request->amount,
                'currency' => $request->currency ?? 'PHP',
                'particulars' => $request->particulars,
                'bank' => $request->bank,
                'apv_no' => $request->apv_no,
                'cv_no' => $request->cv_no,
                'requested_by' => $request->requested_by,
                'checked_by' => $request->checked_by,
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);
            $rfp->rfp_no = 'RFP-' . date('Ym') . '-' . $rfp->id;
            $rfp->save();

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
        $rfp = RequestForPayment::with(['creator', 'purchaseOrder.items', 'approver', 'departmentHeadApprover', 'accountingApprover', 'grpo'])
            ->findOrFail($id);

        // Resolve PO — direct link first, fallback via GRPO's po_no
        $po = $rfp->purchaseOrder;
        if (!$po && $rfp->grpo && $rfp->grpo->po_no) {
            $po = PurchaseOrder::with('items')->where('po_no', $rfp->grpo->po_no)->first();
        }

        if ($rfp->grpo) {
            $grpos = collect([$rfp->grpo]);
        } elseif ($po) {
            $grpos = LiveChicken::where('po_no', $po->po_no)->orderBy('date')->get();
        } else {
            $grpos = collect();
        }

        return view('request_for_payments.show', compact('rfp', 'grpos', 'po'));
    }

    /**
     * Show the form for editing the specified request for payment
     */
    public function edit($id)
    {
        $rfp = RequestForPayment::with(['purchaseOrder.items'])->findOrFail($id);


        $companies = [
            'North Breeders Corporation',
            'Pacific Agro Resources Inc.',
            'Pacific Magalang Agriventures Inc.',
            'Pacific Agrisolutions Enterprises Inc.',
        ];

        $purchaseOrders = PurchaseOrder::where('status', 'approved')
            ->where('is_closed', false)
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

        DB::beginTransaction();
        try {
            $rfp = RequestForPayment::findOrFail($id);

            $wasApproved = $rfp->status === 'approved';

            // Update request for payment
            $rfp->update([
                'purchase_order_id' => $request->purchase_order_id ?: null,
                'srr_id' => $request->srr_id ?: null,
                'live_chicken_id' => $request->live_chicken_id ?: null,
                'payment_type' => $request->payment_type,
                'company' => $request->company,
                'payment_methods' => json_encode($request->payment_methods ?? []),
                'date' => $request->date,
                'due_date' => $request->due_date,
                'payee' => $request->payee,
                'payment_terms' => $request->payment_terms,
                'amount' => $request->amount,
                'currency' => $request->currency ?? 'PHP',
                'particulars' => $request->particulars,
                'bank' => $request->bank,
                'apv_no' => $request->apv_no,
                'cv_no' => $request->cv_no,
                'requested_by' => $request->requested_by,
                'checked_by' => $request->checked_by,
            ]);

            if ($wasApproved) {
                DB::table('request_for_payments')->where('id', $rfp->id)->update([
                    'status'                            => 'pending',
                    'approval_stage'                    => 'pending',
                    'approved_by'                       => null,
                    'approved_at'                       => null,
                    'department_head_approved_by'       => null,
                    'department_head_approved_at'       => null,
                    'department_head_approved_latitude' => null,
                    'department_head_approved_longitude'=> null,
                    'department_head_approved_location' => null,
                    'accounting_approved_by'            => null,
                    'accounting_approved_at'            => null,
                    'accounting_approved_latitude'      => null,
                    'accounting_approved_longitude'     => null,
                    'accounting_approved_location'      => null,
                ]);
                // Invalidate downstream APVs (and their CVs)
                $apvIds = \App\Models\AccountsPayableInvoice::where('request_for_payment_id', $rfp->id)->pluck('id');
                \App\Models\AccountsPayableInvoice::whereIn('id', $apvIds)->update(['status' => 'invalidated']);
                if ($apvIds->isNotEmpty()) {
                    \App\Models\CheckVoucher::whereIn('accounts_payable_invoice_id', $apvIds)->update(['status' => 'invalidated']);
                }
            }

            DB::commit();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'Updated',
                'item' => $rfp->rfp_no,
                'target' => $rfp->payee,
                'type' => 'Request For Payment',
                'message' => 'Updated Request for Payment ' . $rfp->rfp_no . ($wasApproved ? ' (reset to pending for re-approval)' : ''),
            ]);

            $msg = $wasApproved
                ? 'Request for Payment updated and reset to pending for re-approval.'
                : 'Request for Payment updated successfully!';

            return redirect()
                ->route('request_for_payments.show', $rfp->id)
                ->with('success', $msg);

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

            if ($rfp->accountsPayableInvoices()->exists()) {
                return back()->with('error', 'Cannot delete: this RFP has linked APVs.');
            }

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

        $rfp = RequestForPayment::findOrFail($id);
        if ($rfp->approval_stage !== 'pending_dh') {
            return redirect()->back()->with('error', 'This RFP is not awaiting Department Head approval.');
        }
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

        $rfp = RequestForPayment::findOrFail($id);
        if ($rfp->approval_stage !== 'pending_accounting') {
            return redirect()->back()->with('error', 'This RFP is not awaiting Accounting approval.');
        }
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

        $rfp = RequestForPayment::findOrFail($id);
        if ($rfp->approval_stage !== 'pending_executive') {
            return redirect()->back()->with('error', 'This RFP is not awaiting Executive approval.');
        }
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
        $rfp = RequestForPayment::with(['creator', 'purchaseOrder.items', 'approver', 'departmentHeadApprover', 'accountingApprover', 'grpo'])->findOrFail($id);

        // Resolve PO — direct link first, fallback via GRPO's po_no
        $po = $rfp->purchaseOrder;
        if (!$po && $rfp->grpo && $rfp->grpo->po_no) {
            $po = PurchaseOrder::with('items')->where('po_no', $rfp->grpo->po_no)->first();
        }

        if ($rfp->grpo) {
            $grpos = collect([$rfp->grpo]);
        } elseif ($po) {
            $grpos = LiveChicken::where('po_no', $po->po_no)->orderBy('date')->get();
        } else {
            $grpos = collect();
        }
        // Payment percentage stats
        $poTotal = 0;
        if ($po) {
            $poTotal = (float)($po->items->sum('total') ?: $po->lc_price ?? 0);
        }
        $allPaid = \DB::table('request_for_payments')
            ->where(function ($q) use ($rfp, $po) {
                if ($po) $q->where('purchase_order_id', $po->id);
                if ($rfp->live_chicken_id) $q->orWhere('live_chicken_id', $rfp->live_chicken_id);
            })
            ->whereNotIn('status', ['invalidated', 'rejected'])
            ->sum('amount');
        $thisAmount = (float)$rfp->amount;
        $paidBefore = max(0, (float)$allPaid - $thisAmount);
        $remaining  = max(0, $poTotal - (float)$allPaid);
        $paidPct    = $poTotal > 0 ? round(($allPaid / $poTotal) * 100, 1) : null;
        $remainPct  = $poTotal > 0 ? round(($remaining / $poTotal) * 100, 1) : null;

        return view('request_for_payments.print', compact('rfp', 'grpos', 'po', 'poTotal', 'paidBefore', 'remaining', 'paidPct', 'remainPct', 'thisAmount'));
    }
}
