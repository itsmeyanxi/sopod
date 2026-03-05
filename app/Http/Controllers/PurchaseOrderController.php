<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\Currency;
use App\Models\Activity;
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
        $user = Auth::user();
        $query = PurchaseOrder::with(['creator', 'items', 'purchaseRequest']);

        if ($user->isAdminUser()) {
            // Admin/IT see all
        } elseif ($user->canApprovePurchaseOrdersAsExecutive()) {
            $query->where(function($q) use ($user) {
                $q->whereIn('approval_stage', ['pending_dh', 'pending_management', 'pending_executive', 'approved'])
                  ->orWhere('created_by', $user->id);
            });
        } elseif ($user->canApprovePurchaseOrdersAsManagement()) {
            $query->where(function($q) use ($user) {
                $q->whereIn('approval_stage', ['pending_management', 'pending_executive', 'approved'])
                  ->orWhere('created_by', $user->id);
            });
        } elseif ($user->canApprovePurchaseOrdersAsDH()) {
            $query->where(function($q) use ($user) {
                $q->where('approval_stage', 'pending_dh')
                  ->orWhere('created_by', $user->id);
            });
        }

        $purchaseOrders = $query->orderByDesc('created_at')->paginate(20);

        return view('purchase_orders.index', compact('purchaseOrders'));
    }

    /**
     * Show the form for creating a new purchase order
     */
    public function create(Request $request)
    {
        $poNo = 'PO-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $companies = [
            'North Breeders Corporation',
            'Pacific Agro Resources Inc.',
            'Pacific Magalang Agriventures Inc.',
            'Pacific Agrisolutions Enterprises Inc.',
        ];

        $purchaseRequests = PurchaseRequest::where('status', 'approved')
            ->with(['items', 'supplier'])
            ->orderByDesc('created_at')
            ->get();

        $purchaseRequests = $purchaseRequests->filter(function ($pr) {
            $hasRemaining = false;
            foreach ($pr->items as $item) {
                $orderedQty = PurchaseOrderItem::where('purchase_request_item_id', $item->id)->sum('qty');
                if ($orderedQty < $item->qty) { $hasRemaining = true; break; }
            }
            return $hasRemaining || $pr->items->isEmpty();
        })->map(function ($pr) {
            $hasOrdered = false;
            foreach ($pr->items as $item) {
                $orderedQty = PurchaseOrderItem::where('purchase_request_item_id', $item->id)->sum('qty');
                if ($orderedQty > 0) { $hasOrdered = true; break; }
            }
            $pr->is_partially_ordered = $hasOrdered;
            return $pr;
        })->values();

        $suppliers  = Supplier::where('status', 'active')->orderBy('supplier_name')->get();
        $currencies = Currency::orderByRaw("FIELD(code,'PHP','USD','AUD','GBP','EUR')")->get();

        $selectedPR = null;
        if ($request->has('pr_id')) {
            $selectedPR = PurchaseRequest::with(['items', 'supplier'])
                ->where('status', 'approved')
                ->find($request->pr_id);

            if ($selectedPR) {
                $selectedPR->filteredItems = $selectedPR->items->map(function ($item) {
                    $orderedQty          = PurchaseOrderItem::where('purchase_request_item_id', $item->id)->sum('qty');
                    $item->ordered_qty   = $orderedQty;
                    $item->remaining_qty = max(0, $item->qty - $orderedQty);
                    return $item;
                })->filter(fn($item) => $item->remaining_qty > 0)->values();
            }
        }

        return view('purchase_orders.create', compact(
            'poNo', 'companies', 'purchaseRequests', 'suppliers', 'currencies', 'selectedPR'
        ));
    }

    /**
     * Search suppliers for autocomplete (AJAX)
     *
     * When a `description` is provided, only return suppliers who have previously
     * supplied an item with that description (from PO items, PR items, NonTradeItems,
     * TradeItems). If no description-matched suppliers exist, fall back to all active
     * suppliers matching the name/code query — so the user is never left with an
     * empty list.
     */
    public function searchSuppliers(Request $request)
    {
        $q                 = $request->input('q', '');
        $description       = trim($request->input('description', ''));
        $currentSupplierId = $request->input('current_supplier_id');

        // Base query: active suppliers matching the typed name/code search
        $baseQuery = Supplier::where('status', 'active')
            ->where(function ($query) use ($q) {
                $query->where('supplier_name', 'LIKE', "%{$q}%")
                      ->orWhere('supplier_code', 'LIKE', "%{$q}%");
            });

        if ($description !== '') {
            // Find supplier IDs confirmed in the Non-Trade Items library for this item
            $confirmedIds = \App\Models\NonTradeItem::where('name', $description)
                ->whereNotNull('supplier_id')
                ->pluck('supplier_id')
                ->unique()->filter()->values();

            if ($confirmedIds->isNotEmpty()) {
                // Return confirmed suppliers, always ensuring current supplier is included
                $suppliers = (clone $baseQuery)
                    ->whereIn('id', $confirmedIds)
                    ->orderBy('supplier_name')
                    ->limit(15)
                    ->get(['id', 'supplier_name', 'supplier_code', 'address']);

                // If the current supplier is set but NOT in the confirmed list, append them
                if ($currentSupplierId && !$confirmedIds->contains($currentSupplierId)) {
                    $currentSupplier = Supplier::where('status', 'active')
                        ->where(function ($q2) use ($q) {
                            $q2->where('supplier_name', 'LIKE', "%{$q}%")
                               ->orWhere('supplier_code', 'LIKE', "%{$q}%")
                               ->orWhereRaw('1=1'); // always fetch regardless of name search
                        })
                        ->find($currentSupplierId, ['id', 'supplier_name', 'supplier_code', 'address']);

                    if ($currentSupplier) {
                        // Tag it as the original/current supplier but unconfirmed for this item
                        $currentSupplier->carries_item   = false;
                        $currentSupplier->is_current     = true;
                        // Prepend so it appears first
                        $suppliers = collect([$currentSupplier])->merge(
                            $suppliers->map(function ($s) {
                                $s->carries_item = true;
                                return $s;
                            })
                        );
                        return response()->json($suppliers->values());
                    }
                }

                if ($suppliers->isNotEmpty()) {
                    return response()->json($suppliers->map(function ($s) {
                        $s->carries_item = true;
                        return $s;
                    }));
                }
            }

            // Item not in library — show all suppliers; mark current supplier specially
            $suppliers = $baseQuery->orderBy('supplier_name')->limit(15)
                ->get(['id', 'supplier_name', 'supplier_code', 'address']);

            return response()->json($suppliers->map(function ($s) use ($currentSupplierId) {
                $s->carries_item = false;
                $s->is_fallback  = true;
                $s->is_current   = ($currentSupplierId && $s->id == $currentSupplierId);
                return $s;
            }));
        }

        // No description context — plain supplier search, no item filtering
        $suppliers = $baseQuery->orderBy('supplier_name')->limit(15)
            ->get(['id', 'supplier_name', 'supplier_code', 'address']);

        return response()->json($suppliers->map(function ($s) use ($currentSupplierId) {
            $s->is_current = ($currentSupplierId && $s->id == $currentSupplierId);
            return $s;
        }));
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
     * Get PR details with items (AJAX)
     */
    public function getPRDetails(Request $request)
    {
        $prId = $request->input('pr_id');

        if (!$prId) {
            return response()->json(['error' => 'PR ID is required'], 400);
        }

        $pr = PurchaseRequest::with(['items', 'supplier'])
            ->where('status', 'approved')
            ->findOrFail($prId);

        return response()->json([
            'id'                     => $pr->id,
            'pr_no'                  => $pr->pr_no,
            'company'                => $pr->company,
            'requisitioner'          => $pr->requisitioner,
            'supplier'               => $pr->supplier,
            'supplier_id'            => $pr->supplier_id,
            'address'                => $pr->address,
            'delivery_address'       => $pr->delivery_address,
            'terms'                  => $pr->terms,
            'date_needed'            => $pr->date_needed,
            'reason_for_requisition' => $pr->reason_for_requisition,
            'items' => $pr->items->map(function ($item) {
                $orderedQty   = PurchaseOrderItem::where('purchase_request_item_id', $item->id)->sum('qty');
                $remainingQty = max(0, $item->qty - $orderedQty);
                return [
                    'id'            => $item->id,
                    'item_no'       => $item->item_no,
                    'item_code'     => $item->item_code,
                    'description'   => $item->description,
                    'qty'           => $item->qty,
                    'ordered_qty'   => $orderedQty,
                    'remaining_qty' => $remainingQty,
                    'uom'           => $item->uom,
                    'unit_price'    => $item->unit_price,
                    'amount'        => $item->amount,
                    'remarks'       => $item->remarks,
                    'supplier_id'   => $item->supplier_id,
                    'supplier_name' => $item->supplier_name,
                    'date_needed'   => $item->date_needed,
                ];
            })->filter(fn($item) => $item['remaining_qty'] > 0)->values(),
        ]);
    }

    public function store(Request $request)
    {
        // ── Validate ────────────────────────────────────────────────────────
        $validated = $request->validate([
            'company'               => 'required|string',
            'order_date'            => 'required|date',
            'items'                 => 'required|array|min:1',
            'items.*.qty'           => 'required|numeric|min:0',
            'items.*.description'   => 'required|string',
            'items.*.supplier_id'   => 'nullable',
            'items.*.supplier_name' => 'nullable|string',
            'items.*.note'          => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            do {
                $poNo = 'PO-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            } while (PurchaseOrder::where('po_no', $poNo)->exists());

            $quotationPath = null;
            if ($request->hasFile('quotation')) {
                $quotationPath = $request->file('quotation')->store('quotations', 'public');
            }

            $purchaseOrder = PurchaseOrder::create([
                'po_no'                  => $poNo,
                'purchase_request_id'    => $request->purchase_request_id ?: null,
                'company'                => $request->company,
                'supplier_id'            => $request->supplier_id ?: null,
                'supplier'               => $request->supplier ?: null,
                'supplier_address'       => $request->supplier_address ?: null,
                'consignee'              => $request->consignee ?: null,
                'consignee_address'      => $request->consignee_address ?: null,
                'delivery_address'       => $request->delivery_address ?: null,
                'order_date'             => $request->order_date,
                'expected_delivery_date' => $request->expected_delivery_date ?: null,
                'payment_terms'          => $request->payment_terms ?: null,
                'location'               => $request->location ?: null,
                'house'                  => $request->house ?: null,
                'pr_no'                  => $request->pr_no ?: null,
                'lc_price'               => $request->lc_price ?: null,
                'remarks'                => $request->remarks ?: null,
                'quotation'              => $quotationPath,
                'currency'               => $request->currency ?? 'PHP',
                'exchange_rate'          => $request->exchange_rate ?? 1,
                'status'                 => 'pending',
                'approval_stage'         => 'pending_dh',
                'created_by'             => Auth::id(),
            ]);

            foreach ($request->items as $index => $item) {
                $supplierId = isset($item['supplier_id']) && is_numeric($item['supplier_id']) && $item['supplier_id'] > 0
                    ? (int) $item['supplier_id']
                    : null;

                PurchaseOrderItem::create([
                    'purchase_order_id'        => $purchaseOrder->id,
                    'purchase_request_item_id' => !empty($item['purchase_request_item_id']) ? $item['purchase_request_item_id'] : null,
                    'supplier_id'              => $supplierId,
                    'supplier_name'            => !empty($item['supplier_name']) ? $item['supplier_name'] : null,
                    'item_no'                  => $index + 1,
                    'item_code'                => !empty($item['item_code']) ? $item['item_code'] : null,
                    'date_needed'              => !empty($item['date_needed']) ? $item['date_needed'] : null,
                    'qty'                      => $item['qty'],
                    'uom'                      => !empty($item['uom']) ? $item['uom'] : null,
                    'description'              => $item['description'],
                    'unit_price'               => !empty($item['unit_price']) ? $item['unit_price'] : null,
                    'tax'                      => isset($item['tax']) && is_numeric($item['tax']) ? $item['tax'] : 0,
                    'total'                    => !empty($item['total']) ? $item['total'] : null,
                    'note'                     => !empty($item['note']) ? $item['note'] : null,
                ]);
            }

            DB::commit();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action'    => 'Created',
                'item'      => $purchaseOrder->po_no,
                'target'    => $purchaseOrder->company,
                'type'      => 'Purchase Order',
                'message'   => 'Created Purchase Order ' . $purchaseOrder->po_no . ' for ' . $purchaseOrder->company,
            ]);

            return redirect()
                ->route('purchase_orders.show', $purchaseOrder->id)
                ->with('success', 'Purchase Order created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('PO Creation Error: ' . $e->getMessage(), [
                'trace'   => $e->getTraceAsString(),
                'request' => $request->except(['_token']),
            ]);
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
        $purchaseOrder = PurchaseOrder::with(['items', 'creator', 'purchaseRequest', 'approver', 'departmentHeadApprover'])
            ->findOrFail($id);

        return view('purchase_orders.show', compact('purchaseOrder'));
    }

    public function print($id)
    {
        $purchaseOrder = PurchaseOrder::with(['items', 'creator', 'purchaseRequest', 'approver', 'departmentHeadApprover'])
            ->findOrFail($id);

        return view('purchase_orders.print', compact('purchaseOrder'));
    }

    /**
     * Show the form for editing the specified purchase order
     */
    public function edit($id)
    {
        $purchaseOrder = PurchaseOrder::with('items')->findOrFail($id);

        $notesOnly = false;
        if ($purchaseOrder->status === 'approved' && $purchaseOrder->approved_at !== null) {
            $notesOnly = true;
        }

        $companies = [
            'North Breeders Corporation',
            'Pacific Agro Resources Inc.',
            'Pacific Magalang Agriventures Inc.',
            'Pacific Agrisolutions Enterprises Inc.',
        ];

        $purchaseRequests = PurchaseRequest::where('status', 'approved')
            ->orderByDesc('created_at')
            ->get();

        $suppliers  = Supplier::where('status', 'active')->orderBy('supplier_name')->get();
        $currencies = Currency::orderByRaw("FIELD(code,'PHP','USD','AUD','GBP','EUR')")->get();

        return view('purchase_orders.edit', compact(
            'purchaseOrder', 'companies', 'purchaseRequests', 'suppliers', 'currencies', 'notesOnly'
        ));
    }

    /**
     * Update the specified purchase order
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'company'                  => 'required|string',
            'order_date'               => 'required|date',
            'items'                    => 'required|array|min:1',
            'items.*.qty'              => 'required|numeric|min:0',
            'items.*.description'      => 'required|string',
            'items.*.supplier_id'      => 'nullable|exists:suppliers,id',
            'items.*.supplier_name'    => 'nullable|string',
            'items.*.note'             => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $purchaseOrder = PurchaseOrder::findOrFail($id);

            $updateData = [
                'purchase_request_id'    => $request->purchase_request_id ?: null,
                'company'                => $request->company,
                'supplier_id'            => $request->supplier_id ?: null,
                'supplier'               => $request->supplier ?: null,
                'supplier_address'       => $request->supplier_address ?: null,
                'consignee'              => $request->consignee ?: null,
                'consignee_address'      => $request->consignee_address ?: null,
                'delivery_address'       => $request->delivery_address ?: null,
                'order_date'             => $request->order_date,
                'expected_delivery_date' => $request->expected_delivery_date ?: null,
                'payment_terms'          => $request->payment_terms ?: null,
                'location'               => $request->location ?: null,
                'house'                  => $request->house ?: null,
                'pr_no'                  => $request->pr_no ?: null,
                'lc_price'               => $request->lc_price ?: null,
                'remarks'                => $request->remarks ?: null,
                'currency'               => $request->currency ?? 'PHP',
                'exchange_rate'          => $request->exchange_rate ?? 1,
            ];

            if ($request->hasFile('quotation')) {
                if ($purchaseOrder->quotation && \Storage::disk('public')->exists($purchaseOrder->quotation)) {
                    \Storage::disk('public')->delete($purchaseOrder->quotation);
                }
                $updateData['quotation'] = $request->file('quotation')->store('quotations', 'public');
            }

            $purchaseOrder->update($updateData);
            $purchaseOrder->items()->delete();

            foreach ($request->items as $index => $item) {
                $supplierId = isset($item['supplier_id']) && is_numeric($item['supplier_id']) && $item['supplier_id'] > 0
                    ? (int) $item['supplier_id']
                    : null;

                PurchaseOrderItem::create([
                    'purchase_order_id'        => $purchaseOrder->id,
                    'purchase_request_item_id' => !empty($item['purchase_request_item_id']) ? $item['purchase_request_item_id'] : null,
                    'supplier_id'              => $supplierId,
                    'supplier_name'            => !empty($item['supplier_name']) ? $item['supplier_name'] : null,
                    'item_no'                  => $index + 1,
                    'item_code'                => $item['item_code'] ?: null,
                    'date_needed'              => $item['date_needed'] ?: null,
                    'qty'                      => $item['qty'],
                    'uom'                      => $item['uom'] ?? null,
                    'description'              => $item['description'],
                    'unit_price'               => $item['unit_price'] ?: null,
                    'tax'                      => $item['tax'] ?? 0,
                    'total'                    => $item['total'] ?: null,
                    'note'                     => $item['note'] ?: null,
                ]);
            }

            DB::commit();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action'    => 'Updated',
                'item'      => $purchaseOrder->po_no,
                'target'    => $purchaseOrder->company,
                'type'      => 'Purchase Order',
                'message'   => 'Updated Purchase Order ' . $purchaseOrder->po_no,
            ]);

            return redirect()
                ->route('purchase_orders.show', $purchaseOrder->id)
                ->with('success', 'Purchase Order updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('PO Update Error: ' . $e->getMessage(), ['exception' => $e]);
            return back()
                ->withInput()
                ->with('error', 'Error updating Purchase Order: ' . $e->getMessage());
        }
    }

    /**
     * Update notes on PO items (works on approved POs too)
     */
    public function updateNotes(Request $request, $id)
    {
        $purchaseOrder = PurchaseOrder::with('items')->findOrFail($id);

        $request->validate([
            'notes'   => 'required|array',
            'notes.*' => 'nullable|string',
        ]);

        foreach ($request->notes as $itemId => $note) {
            PurchaseOrderItem::where('id', $itemId)
                ->where('purchase_order_id', $purchaseOrder->id)
                ->update(['note' => $note]);
        }

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action'    => 'Updated Notes',
            'item'      => $purchaseOrder->po_no,
            'target'    => $purchaseOrder->company,
            'type'      => 'Purchase Order',
            'message'   => 'Updated notes on Purchase Order ' . $purchaseOrder->po_no,
        ]);

        return redirect()
            ->route('purchase_orders.show', $purchaseOrder->id)
            ->with('success', 'Notes updated successfully!');
    }

    /**
     * Remove the specified purchase order
     */
    public function destroy($id)
    {
        try {
            $purchaseOrder = PurchaseOrder::findOrFail($id);
            $poNo          = $purchaseOrder->po_no;
            $purchaseOrder->delete();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action'    => 'Deleted',
                'item'      => $poNo,
                'target'    => 'N/A',
                'type'      => 'Purchase Order',
                'message'   => 'Deleted Purchase Order ' . $poNo,
            ]);

            return redirect()
                ->route('purchase_orders.index')
                ->with('success', 'Purchase Order deleted successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Error deleting Purchase Order: ' . $e->getMessage());
        }
    }

    /**
     * Approve as Department Head (Level 1)
     */
    public function approveDH(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->canApprovePurchaseOrdersAsDH()) {
            return redirect()->route('purchase_orders.index')
                ->with('error', 'Unauthorized to approve as Department Head.');
        }

        $purchaseOrder = PurchaseOrder::where('approval_stage', 'pending_dh')->findOrFail($id);

        $purchaseOrder->update([
            'approval_stage'                    => 'pending_management',
            'department_head_approved_by'       => Auth::id(),
            'department_head_approved_at'       => now(),
            'department_head_approved_latitude'  => $request->input('latitude'),
            'department_head_approved_longitude' => $request->input('longitude'),
            'department_head_approved_location'  => $request->input('location'),
            'rejection_reason'                  => null,
        ]);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action'    => 'Approved (DH)',
            'item'      => $purchaseOrder->po_no,
            'target'    => $purchaseOrder->company,
            'type'      => 'Purchase Order',
            'message'   => 'Department Head approved Purchase Order ' . $purchaseOrder->po_no . '. Forwarded to Management.',
        ]);

        return redirect()->back()->with('success', 'PO noted by Department Head. Forwarded to Management.');
    }

    /**
     * Approve as Management (Level 2 - GM/CFO)
     */
    public function approveManagement(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->canApprovePurchaseOrdersAsManagement()) {
            return redirect()->route('purchase_orders.index')
                ->with('error', 'Unauthorized to approve as Management.');
        }

        $purchaseOrder = PurchaseOrder::where('approval_stage', 'pending_management')->findOrFail($id);

        if ($this->isPMAI($purchaseOrder->company) && !$user->isAdminUser() && !$user->canApprovePurchaseOrdersAsManagement()) {
            return redirect()->route('purchase_orders.show', $id)
                ->with('error', 'Only CFO can approve POs for this company.');
        }

        $purchaseOrder->update([
            'approval_stage'                   => 'pending_executive',
            'management_approved_by'           => Auth::id(),
            'management_approved_at'           => now(),
            'management_approved_latitude'      => $request->input('latitude'),
            'management_approved_longitude'     => $request->input('longitude'),
            'management_approved_location'      => $request->input('location'),
            'rejection_reason'                 => null,
        ]);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action'    => 'Approved (Management)',
            'item'      => $purchaseOrder->po_no,
            'target'    => $purchaseOrder->company,
            'type'      => 'Purchase Order',
            'message'   => 'Management approved Purchase Order ' . $purchaseOrder->po_no . '. Forwarded to Executive.',
        ]);

        return redirect()->back()->with('success', 'PO approved by Management. Forwarded to Executive.');
    }

    /**
     * Approve as Executive (Level 3 - President/VP) - Final approval
     */
    public function approve(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->canApprovePurchaseOrdersAsExecutive()) {
            return redirect()->route('purchase_orders.index')
                ->with('error', 'Unauthorized to approve as Executive.');
        }

        $purchaseOrder = PurchaseOrder::where('approval_stage', 'pending_executive')->findOrFail($id);

        $purchaseOrder->update([
            'approval_stage'    => 'approved',
            'status'            => 'approved',
            'approved_by'       => Auth::id(),
            'approved_at'       => now(),
            'approved_latitude'  => $request->input('latitude'),
            'approved_longitude' => $request->input('longitude'),
            'approved_location'  => $request->input('location'),
            'rejection_reason'  => null,
        ]);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action'    => 'Approved',
            'item'      => $purchaseOrder->po_no,
            'target'    => $purchaseOrder->company,
            'type'      => 'Purchase Order',
            'message'   => 'Purchase Order ' . $purchaseOrder->po_no . ' fully approved.',
        ]);

        return redirect()->back()->with('success', 'Purchase Order approved successfully!');
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

        $request->validate(['rejection_reason' => 'nullable|string|max:500']);

        $purchaseOrder = PurchaseOrder::findOrFail($id);

        $purchaseOrder->update([
            'status'           => 'rejected',
            'approved_by'      => Auth::id(),
            'approved_at'      => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action'    => 'Rejected',
            'item'      => $purchaseOrder->po_no,
            'target'    => $purchaseOrder->company,
            'type'      => 'Purchase Order',
            'message'   => 'Rejected Purchase Order ' . $purchaseOrder->po_no . ($request->rejection_reason ? ': ' . $request->rejection_reason : ''),
        ]);

        return redirect()->back()->with('success', 'Purchase Order rejected!');
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
                'status'           => 'approved',
                'approved_by'      => Auth::id(),
                'approved_at'      => now(),
                'rejection_reason' => null,
            ]);

        return redirect()->route('purchase_orders.index')
            ->with('success', "{$approved} of " . count($ids) . " Purchase Order(s) approved successfully.");
    }

    /**
     * Generate item code based on description and supplier
     */
    public function generateItemCode(Request $request)
    {
        $description = $request->input('description', '');
        $supplierId  = $request->input('supplier_id');

        $query = \App\Models\NonTradeItem::where('name', $description)->whereNotNull('item_code');
        if ($supplierId) $query->where('supplier_id', $supplierId);
        $existing = $query->first();
        if ($existing) {
            return response()->json(['item_code' => $existing->item_code]);
        }

        $query2 = \App\Models\TradeItem::where('name', $description)->whereNotNull('item_code');
        if ($supplierId) $query2->where('supplier_id', $supplierId);
        $existing2 = $query2->first();
        if ($existing2) {
            return response()->json(['item_code' => $existing2->item_code]);
        }

        $cleanDesc = preg_replace('/[^a-zA-Z\s]/', '', $description);
        $words = preg_split('/\s+/', trim($cleanDesc));
        $words = array_filter($words, fn($w) => strlen($w) > 0);
        $abbr  = count($words) === 1
            ? strtoupper(substr(reset($words), 0, 2))
            : strtoupper(implode('', array_map(fn($w) => substr($w, 0, 1), $words)));

        $supplierCode = 'GEN';
        if ($supplierId) {
            $supplier = Supplier::find($supplierId);
            if ($supplier && $supplier->supplier_name) {
                $sWords = preg_split('/\s+/', trim($supplier->supplier_name));
                $supplierCode = strtoupper(implode('', array_map(fn($w) => substr($w, 0, 1), $sWords)));
            }
        }

        $pattern = '/^[A-Z]+-(\d+)-[A-Z]+$/';
        $allCodes = array_merge(
            PurchaseOrderItem::whereNotNull('item_code')->pluck('item_code')->toArray(),
            \App\Models\PurchaseRequestItem::whereNotNull('item_code')->pluck('item_code')->toArray(),
            \App\Models\NonTradeItem::whereNotNull('item_code')->pluck('item_code')->toArray(),
            \App\Models\TradeItem::whereNotNull('item_code')->pluck('item_code')->toArray()
        );

        $maxSeq = 0;
        foreach ($allCodes as $code) {
            if (preg_match($pattern, $code, $matches)) {
                $maxSeq = max($maxSeq, (int) $matches[1]);
            }
        }

        $seq = str_pad($maxSeq + 1, 3, '0', STR_PAD_LEFT);

        return response()->json(['item_code' => "{$abbr}-{$seq}-{$supplierCode}"]);
    }

    /**
     * Search item by item code
     */
    public function searchByItemCode(Request $request)
    {
        $itemCode = $request->input('item_code', '');
        if (empty($itemCode)) {
            return response()->json([]);
        }

        $poItem = PurchaseOrderItem::where('item_code', $itemCode)
            ->whereNotNull('description')
            ->orderByDesc('id')
            ->first();

        if ($poItem) {
            return response()->json([
                'description'   => $poItem->description,
                'supplier_id'   => $poItem->supplier_id,
                'supplier_name' => $poItem->supplier_name,
                'unit_price'    => $poItem->unit_price,
                'uom'           => $poItem->uom,
            ]);
        }

        $prItem = \App\Models\PurchaseRequestItem::where('item_code', $itemCode)
            ->whereNotNull('description')
            ->orderByDesc('id')
            ->first();

        if ($prItem) {
            return response()->json([
                'description'   => $prItem->description,
                'supplier_id'   => $prItem->supplier_id,
                'supplier_name' => $prItem->supplier_name,
                'unit_price'    => $prItem->unit_price,
                'uom'           => $prItem->uom,
            ]);
        }

        $nti = \App\Models\NonTradeItem::where('item_code', $itemCode)->first();
        if ($nti) {
            return response()->json([
                'description'   => $nti->name,
                'supplier_id'   => $nti->supplier_id,
                'supplier_name' => $nti->supplier ? $nti->supplier->supplier_name : null,
                'unit_price'    => null,
                'uom'           => null,
            ]);
        }

        $ti = \App\Models\TradeItem::where('item_code', $itemCode)->first();
        if ($ti) {
            return response()->json([
                'description'   => $ti->name,
                'supplier_id'   => $ti->supplier_id,
                'supplier_name' => $ti->supplier ? $ti->supplier->supplier_name : null,
                'unit_price'    => null,
                'uom'           => null,
            ]);
        }

        return response()->json([]);
    }

    /**
     * Check if company is SOPOD (MeatPlus)
     */
    private function isPMAI(?string $company): bool
    {
        if (!$company) return false;
        return str_contains(strtolower($company), 'meatplus');
    }
}