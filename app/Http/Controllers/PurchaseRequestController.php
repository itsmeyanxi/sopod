<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\NonTradeItem;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseRequestController extends Controller
{
    /**
     * Display a listing of purchase requests
     */
    public function index()
    {
        $purchaseRequests = PurchaseRequest::with(['creator', 'items'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('purchase_requests.index', compact('purchaseRequests'));
    }

    /**
     * Show the form for creating a new purchase request
     */
    public function create()
    {
        // Generate PR number
        $prNo = 'PR-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // Define companies
        $companies = [
            'North Breeders Corporation',
            'Pacific Agro Resources Inc.',
            'Pacific Magalang Agriventures Inc.',
            'Pacific Agrisolutions Enterprises Inc.',
        ];

        // Get active suppliers for dropdown
        $suppliers = Supplier::where('status', 'active')
            ->orderBy('supplier_name')
            ->get();

        return view('purchase_requests.create', compact('prNo', 'companies', 'suppliers'));
    }

    /**
     * Store a newly created purchase request
     */
    public function store(Request $request)
    {
        $request->validate([
            'company' => 'required|string',
            'requisitioner' => 'required|string',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'date_of_request' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.qty' => 'required|numeric|min:0',
            'items.*.uom' => 'required|string',
            'items.*.description' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            // Generate PR number
            $prNo = 'PR-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // Create purchase request
            $purchaseRequest = PurchaseRequest::create([
                'pr_no' => $prNo,
                'company' => $request->company,
                'requisitioner' => $request->requisitioner,
                'department' => $request->department,
                'supplier' => $request->supplier,
                'terms' => $request->terms,
                'address' => $request->address,
                'delivery_address' => $request->delivery_address,
                'contact_person' => $request->contact_person,
                'date_of_request' => $request->date_of_request,
                'date_needed' => $request->date_needed,
                'type_of_request' => $request->type_of_request,
                'with_budget' => $request->with_budget,
                'charge_to' => $request->charge_to,
                'contact_number' => $request->contact_number,
                'reason_for_requisition' => $request->reason_for_requisition,
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            // Create items
            foreach ($request->items as $index => $item) {
                PurchaseRequestItem::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'item_no' => $index + 1,
                    'qty' => $item['qty'],
                    'uom' => $item['uom'],
                    'description' => $item['description'],
                    'unit_price' => $item['unit_price'] ?? null,
                    'amount' => $item['amount'] ?? null,
                    'remarks' => $item['remarks'] ?? null,
                ]);

                // Auto-save item description to non-trade items library
                if (!empty($item['description'])) {
                    NonTradeItem::firstOrCreate(
                        ['name' => trim($item['description'])],
                        ['unit' => $item['uom'] ?? null]
                    );
                }
            }

            DB::commit();

            return redirect()
                ->route('purchase_requests.show', $purchaseRequest->id)
                ->with('success', 'Purchase Request created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error creating Purchase Request: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified purchase request
     */
    public function show($id)
    {
        $purchaseRequest = PurchaseRequest::with(['items', 'creator', 'approver'])
            ->findOrFail($id);

        return view('purchase_requests.show', compact('purchaseRequest'));
    }

    public function print($id)
    {
        $purchaseRequest = PurchaseRequest::with(['items', 'creator', 'approver'])
            ->findOrFail($id);

        return view('purchase_requests.print', compact('purchaseRequest'));
    }

    /**
     * Show the form for editing the specified purchase request
     */
    public function edit($id)
    {
        $purchaseRequest = PurchaseRequest::with('items')->findOrFail($id);

        $companies = [
            'North Breeders Corporation',
            'Pacific Agro Resources Inc.',
            'Pacific Magalang Agriventures Inc.',
            'Pacific Agrisolutions Enterprises Inc.',
        ];

        $suppliers = Supplier::where('status', 'active')
            ->orderBy('supplier_name')
            ->get();

        return view('purchase_requests.edit', compact('purchaseRequest', 'companies', 'suppliers'));
    }

    /**
     * Update the specified purchase request
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'company' => 'required|string',
            'requisitioner' => 'required|string',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'date_of_request' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.qty' => 'required|numeric|min:0',
            'items.*.uom' => 'required|string',
            'items.*.description' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $purchaseRequest = PurchaseRequest::findOrFail($id);

            // Update purchase request
            $purchaseRequest->update([
                'company' => $request->company,
                'requisitioner' => $request->requisitioner,
                'department' => $request->department,
                'supplier_id' => $request->supplier_id,
                'supplier' => $request->supplier,
                'terms' => $request->terms,
                'address' => $request->address,
                'delivery_address' => $request->delivery_address,
                'contact_person' => $request->contact_person,
                'date_of_request' => $request->date_of_request,
                'date_needed' => $request->date_needed,
                'type_of_request' => $request->type_of_request,
                'with_budget' => $request->with_budget,
                'charge_to' => $request->charge_to,
                'contact_number' => $request->contact_number,
                'reason_for_requisition' => $request->reason_for_requisition,
            ]);

            // Delete existing items
            $purchaseRequest->items()->delete();

            // Create new items
            foreach ($request->items as $index => $item) {
                PurchaseRequestItem::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'item_no' => $index + 1,
                    'qty' => $item['qty'],
                    'uom' => $item['uom'],
                    'description' => $item['description'],
                    'unit_price' => $item['unit_price'] ?? null,
                    'amount' => $item['amount'] ?? null,
                    'remarks' => $item['remarks'] ?? null,
                ]);

                // Auto-save item description to non-trade items library
                if (!empty($item['description'])) {
                    NonTradeItem::firstOrCreate(
                        ['name' => trim($item['description'])],
                        ['unit' => $item['uom'] ?? null]
                    );
                }
            }

            DB::commit();

            return redirect()
                ->route('purchase_requests.show', $purchaseRequest->id)
                ->with('success', 'Purchase Request updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error updating Purchase Request: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified purchase request
     */
    public function destroy($id)
    {
        try {
            $purchaseRequest = PurchaseRequest::findOrFail($id);
            $purchaseRequest->delete();

            return redirect()
                ->route('purchase_requests.index')
                ->with('success', 'Purchase Request deleted successfully!');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Error deleting Purchase Request: ' . $e->getMessage());
        }
    }

    /**
     * Approve a purchase request
     */
    public function approve($id)
    {
        $user = Auth::user();

        if (!$user->canApprovePurchaseRequests()) {
            return redirect()->route('purchase_requests.index')
                ->with('error', 'Unauthorized to approve purchase requests.');
        }

        $purchaseRequest = PurchaseRequest::findOrFail($id);

        $purchaseRequest->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Purchase Request approved successfully!');
    }

    /**
     * Reject a purchase request
     */
    public function reject(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->canApprovePurchaseRequests()) {
            return redirect()->route('purchase_requests.index')
                ->with('error', 'Unauthorized to reject purchase requests.');
        }

        $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $purchaseRequest = PurchaseRequest::findOrFail($id);

        $purchaseRequest->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Purchase Request rejected.');
    }

    /**
     * Bulk approve multiple purchase requests
     */
    public function bulkApprove(Request $request)
    {
        $user = Auth::user();

        if (!$user->canApprovePurchaseRequests()) {
            return redirect()->route('purchase_requests.index')
                ->with('error', 'Unauthorized to approve purchase requests.');
        }

        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->route('purchase_requests.index')
                ->with('error', 'No purchase requests selected.');
        }

        $approved = PurchaseRequest::whereIn('id', $ids)
            ->where('status', 'pending')
            ->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'rejection_reason' => null,
            ]);

        $total = count($ids);

        return redirect()->route('purchase_requests.index')
            ->with('success', "{$approved} of {$total} Purchase Request(s) approved successfully.");
    }
}
