<?php

namespace App\Http\Controllers;

use App\Models\RequestForPayment;
use App\Models\PurchaseOrder;
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
        if ($request->has('po_id')) {
            $selectedPO = PurchaseOrder::find($request->po_id);
        }

        return view('request_for_payments.create', compact('rfpNo', 'companies', 'selectedPO'));
    }

    /**
     * Search approved POs (AJAX)
     */
    public function searchPOs(Request $request)
    {
        $searchTerm = $request->input('search', '');

        $pos = PurchaseOrder::where('status', 'approved')
            ->where(function ($query) use ($searchTerm) {
                $query->where('po_no', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('supplier', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('company', 'LIKE', "%{$searchTerm}%");
            })
            ->select('id', 'po_no', 'supplier', 'company', 'order_date')
            ->limit(10)
            ->get();

        return response()->json($pos);
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
        $rfp = RequestForPayment::with(['creator', 'purchaseOrder', 'approver'])
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
            $rfp->delete();

            return redirect()
                ->route('request_for_payments.index')
                ->with('success', 'Request for Payment deleted successfully!');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Error deleting Request for Payment: ' . $e->getMessage());
        }
    }

    /**
     * Approve a request for payment
     */
    public function approve($id)
    {
        $user = Auth::user();

        if (!$user->canApproveRequestForPayments()) {
            return redirect()->route('request_for_payments.index')
                ->with('error', 'Unauthorized to approve request for payments.');
        }

        $rfp = RequestForPayment::findOrFail($id);

        $rfp->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Request for Payment approved successfully!');
    }

    /**
     * Reject a request for payment
     */
    public function reject(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->canApproveRequestForPayments()) {
            return redirect()->route('request_for_payments.index')
                ->with('error', 'Unauthorized to reject request for payments.');
        }

        $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $rfp = RequestForPayment::findOrFail($id);

        $rfp->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Request for Payment rejected!');
    }

    /**
     * Print request for payment
     */
    public function print($id)
    {
        $rfp = RequestForPayment::with(['creator', 'purchaseOrder'])->findOrFail($id);

        if ($rfp->status !== 'approved') {
            return redirect()
                ->route('request_for_payments.show', $id)
                ->with('error', 'Request for Payment must be approved before printing.');
        }

        return view('request_for_payments.print', ['rfp' => $rfp]);
    }
}
