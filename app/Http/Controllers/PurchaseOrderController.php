<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of purchase orders
     */
    public function index()
    {
        $purchaseOrders = PurchaseOrder::with(['creator', 'items', 'purchaseRequest'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('purchase_orders.index', compact('purchaseOrders'));
    }

    /**
     * Show the form for creating a new purchase order
     */
    public function create(Request $request)
    {
        // Generate PO number
        $poNo = 'PO-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // Define companies
        $companies = [
            'North Breeders Corporation',
            'Pacific Agro Resources Inc.',
            'Pacific Magalang Agriventures Inc.',
            'Pacific Agrisolutions Enterprises Inc.',
        ];

        // Get approved purchase requests for dropdown
        $purchaseRequests = PurchaseRequest::where('status', 'approved')
            ->with(['items', 'supplier'])
            ->orderByDesc('created_at')
            ->get();

        // Get active suppliers for dropdown
        $suppliers = Supplier::where('status', 'active')
            ->orderBy('supplier_name')
            ->get();

        // Check if a PR was selected
        $selectedPR = null;
        if ($request->has('pr_id')) {
            $selectedPR = PurchaseRequest::with(['items', 'supplier'])->find($request->pr_id);
        }

        $currencies = Currency::orderByRaw("FIELD(code,'PHP','USD','AUD','GBP','EUR')")->get();

        return view('purchase_orders.create', compact('poNo', 'companies', 'purchaseRequests', 'selectedPR', 'suppliers', 'currencies'));
    }

    /**
     * Search approved PRs (AJAX)
     */
    public function searchPRs(Request $request)
    {
        $searchTerm = $request->input('search', '');

        $prs = PurchaseRequest::where('status', 'approved')
            ->where(function ($query) use ($searchTerm) {
                $query->where('pr_no', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('requisitioner', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('company', 'LIKE', "%{$searchTerm}%");
            })
            ->select('id', 'pr_no', 'requisitioner', 'company', 'date_of_request')
            ->limit(10)
            ->get();

        return response()->json($prs);
    }

    /**
     * Store a newly created purchase order
     */
    public function store(Request $request)
    {
        $request->validate([
            'company' => 'required|string',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'order_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.qty' => 'required|numeric|min:0',
            'items.*.uom' => 'required|string',
            'items.*.description' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            // Generate PO number
            $poNo = 'PO-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // Handle quotation file upload
            $quotationPath = null;
            if ($request->hasFile('quotation')) {
                $quotationPath = $request->file('quotation')->store('quotations', 'public');
            }

            // Create purchase order
            $purchaseOrder = PurchaseOrder::create([
                'po_no' => $poNo,
                'purchase_request_id' => $request->purchase_request_id,
                'company' => $request->company,
                'supplier_id' => $request->supplier_id,
                'supplier' => $request->supplier,
                'supplier_address' => $request->supplier_address,
                'consignee' => $request->consignee,
                'consignee_address' => $request->consignee_address,
                'delivery_address' => $request->delivery_address,
                'order_date' => $request->order_date,
                'expected_delivery_date' => $request->expected_delivery_date,
                'payment_terms' => $request->payment_terms,
                'location' => $request->location,
                'house' => $request->house,
                'pr_no' => $request->pr_no,
                'lc_price' => $request->lc_price,
                'remarks' => $request->remarks,
                'quotation' => $quotationPath,
                'currency' => $request->currency ?? 'PHP',
                'exchange_rate' => $request->exchange_rate ?? 1,
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            // Create items
            foreach ($request->items as $index => $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'item_no' => $index + 1,
                    'item_code' => $item['item_code'] ?? null,
                    'qty' => $item['qty'],
                    'uom' => $item['uom'],
                    'description' => $item['description'],
                    'unit_price' => $item['unit_price'] ?? null,
                    'tax' => $item['tax'] ?? 0,
                    'total' => $item['total'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('purchase_orders.show', $purchaseOrder->id)
                ->with('success', 'Purchase Order created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error creating Purchase Order: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified purchase order
     */
    public function show($id)
    {
        $purchaseOrder = PurchaseOrder::with(['items', 'creator', 'purchaseRequest', 'approver'])
            ->findOrFail($id);

        return view('purchase_orders.show', compact('purchaseOrder'));
    }

    public function print($id)
    {
        $purchaseOrder = PurchaseOrder::with(['items', 'creator', 'purchaseRequest', 'approver'])
            ->findOrFail($id);

        if ($purchaseOrder->status !== 'approved') {
            return redirect()
                ->route('purchase_orders.show', $id)
                ->with('error', 'Purchase Order must be approved before printing.');
        }

        return view('purchase_orders.print', compact('purchaseOrder'));
    }

    /**
     * Show the form for editing the specified purchase order
     */
    public function edit($id)
    {
        $purchaseOrder = PurchaseOrder::with('items')->findOrFail($id);

        $companies = [
            'North Breeders Corporation',
            'Pacific Agro Resources Inc.',
            'Pacific Magalang Agriventures Inc.',
            'Pacific Agrisolutions Enterprises Inc.',
        ];

        $purchaseRequests = PurchaseRequest::where('status', 'approved')
            ->orderByDesc('created_at')
            ->get();

        // Get active suppliers for dropdown
        $suppliers = Supplier::where('status', 'active')
            ->orderBy('supplier_name')
            ->get();

        $currencies = Currency::orderByRaw("FIELD(code,'PHP','USD','AUD','GBP','EUR')")->get();

        return view('purchase_orders.edit', compact('purchaseOrder', 'companies', 'purchaseRequests', 'suppliers', 'currencies'));
    }

    /**
     * Update the specified purchase order
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'company' => 'required|string',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'order_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.qty' => 'required|numeric|min:0',
            'items.*.uom' => 'required|string',
            'items.*.description' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $purchaseOrder = PurchaseOrder::findOrFail($id);

            // Handle quotation file upload
            $updateData = [
                'purchase_request_id' => $request->purchase_request_id,
                'company' => $request->company,
                'supplier_id' => $request->supplier_id,
                'supplier' => $request->supplier,
                'supplier_address' => $request->supplier_address,
                'consignee' => $request->consignee,
                'consignee_address' => $request->consignee_address,
                'delivery_address' => $request->delivery_address,
                'order_date' => $request->order_date,
                'expected_delivery_date' => $request->expected_delivery_date,
                'payment_terms' => $request->payment_terms,
                'location' => $request->location,
                'house' => $request->house,
                'pr_no' => $request->pr_no,
                'lc_price' => $request->lc_price,
                'remarks' => $request->remarks,
                'currency' => $request->currency ?? 'PHP',
                'exchange_rate' => $request->exchange_rate ?? 1,
            ];

            if ($request->hasFile('quotation')) {
                // Delete old file if exists
                if ($purchaseOrder->quotation && \Storage::disk('public')->exists($purchaseOrder->quotation)) {
                    \Storage::disk('public')->delete($purchaseOrder->quotation);
                }
                $updateData['quotation'] = $request->file('quotation')->store('quotations', 'public');
            }

            // Update purchase order
            $purchaseOrder->update($updateData);

            // Delete existing items
            $purchaseOrder->items()->delete();

            // Create new items
            foreach ($request->items as $index => $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'item_no' => $index + 1,
                    'item_code' => $item['item_code'] ?? null,
                    'qty' => $item['qty'],
                    'uom' => $item['uom'],
                    'description' => $item['description'],
                    'unit_price' => $item['unit_price'] ?? null,
                    'tax' => $item['tax'] ?? 0,
                    'total' => $item['total'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('purchase_orders.show', $purchaseOrder->id)
                ->with('success', 'Purchase Order updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error updating Purchase Order: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified purchase order
     */
    public function destroy($id)
    {
        try {
            $purchaseOrder = PurchaseOrder::findOrFail($id);
            $purchaseOrder->delete();

            return redirect()
                ->route('purchase_orders.index')
                ->with('success', 'Purchase Order deleted successfully!');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Error deleting Purchase Order: ' . $e->getMessage());
        }
    }

    /**
     * Approve a purchase order
     */
    public function approve($id)
    {
        $user = Auth::user();

        if (!$user->canApprovePurchaseOrders()) {
            return redirect()->route('purchase_orders.index')
                ->with('error', 'Unauthorized to approve purchase orders.');
        }

        $purchaseOrder = PurchaseOrder::findOrFail($id);

        $purchaseOrder->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Purchase Order approved successfully!');
    }

    /**
     * Reject a purchase order
     */
    public function reject(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->canApprovePurchaseOrders()) {
            return redirect()->route('purchase_orders.index')
                ->with('error', 'Unauthorized to reject purchase orders.');
        }

        $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $purchaseOrder = PurchaseOrder::findOrFail($id);

        $purchaseOrder->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Purchase Order rejected!');
    }

    /**
     * Bulk approve multiple purchase orders
     */
    public function bulkApprove(Request $request)
    {
        $user = Auth::user();

        if (!$user->canApprovePurchaseOrders()) {
            return redirect()->route('purchase_orders.index')
                ->with('error', 'Unauthorized to approve purchase orders.');
        }

        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->route('purchase_orders.index')
                ->with('error', 'No purchase orders selected.');
        }

        $approved = PurchaseOrder::whereIn('id', $ids)
            ->where('status', 'pending')
            ->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'rejection_reason' => null,
            ]);

        $total = count($ids);

        return redirect()->route('purchase_orders.index')
            ->with('success', "{$approved} of {$total} Purchase Order(s) approved successfully.");
    }
}
