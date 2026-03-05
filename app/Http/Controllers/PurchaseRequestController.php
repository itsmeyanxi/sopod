<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\PurchaseOrderItem;
use App\Models\NonTradeItem;
use App\Models\TradeItem;
use App\Models\Supplier;
use App\Models\Activity;
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
        $user = Auth::user();
        $query = PurchaseRequest::with(['creator', 'items']);

        if ($user->isAdminUser()) {
            // Admin/IT see all — no filter
        } elseif ($user->canApprovePurchaseRequestsAsExecutive()) {
            $query->where(function($q) use ($user) {
                $q->whereIn('approval_stage', ['pending_dh', 'pending_management', 'pending_executive', 'approved'])
                  ->orWhere('created_by', $user->id);
            });
        } elseif ($user->canApprovePurchaseRequestsAsManagement()) {
            $query->where(function($q) use ($user) {
                $q->whereIn('approval_stage', ['pending_management', 'pending_executive', 'approved'])
                  ->orWhere('created_by', $user->id);
            });
        } elseif ($user->canApprovePurchaseRequestsAsDH()) {
            $query->where(function($q) use ($user) {
                $q->where('approval_stage', 'pending_dh')
                  ->orWhere('created_by', $user->id);
            });
        }

        $purchaseRequests = $query->orderByDesc('created_at')->paginate(20);

        return view('purchase_requests.index', compact('purchaseRequests'));
    }

    /**
     * Show the form for creating a new purchase request
     */
    public function create()
    {
        $prNo = 'PR-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $companies = [
            'North Breeders Corporation',
            'Pacific Agro Resources Inc.',
            'Pacific Magalang Agriventures Inc.',
            'Pacific Agrisolutions Enterprises Inc.',
        ];

        return view('purchase_requests.create', compact('prNo', 'companies'));
    }

    /**
     * Search suppliers for autocomplete (AJAX)
     *
     * When a `description` is provided, only return suppliers who have previously
     * supplied an item with that description. Falls back to all active suppliers if
     * no description-matched suppliers exist.
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
            $confirmedIds = NonTradeItem::where('name', $description)
                ->whereNotNull('supplier_id')
                ->pluck('supplier_id')
                ->unique()->filter()->values();

            if ($confirmedIds->isNotEmpty()) {
                $suppliers = (clone $baseQuery)
                    ->whereIn('id', $confirmedIds)
                    ->orderBy('supplier_name')
                    ->limit(15)
                    ->get(['id', 'supplier_name', 'supplier_code', 'address']);

                // If the current supplier is set but NOT in the confirmed list, prepend them
                if ($currentSupplierId && !$confirmedIds->contains($currentSupplierId)) {
                    $currentSupplier = Supplier::where('status', 'active')
                        ->find($currentSupplierId, ['id', 'supplier_name', 'supplier_code', 'address']);

                    if ($currentSupplier) {
                        $currentSupplier->carries_item = false;
                        $currentSupplier->is_current   = true;
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
     * Search items from both Trade and Non-Trade libraries (AJAX)
     */
    public function searchItems(Request $request)
    {
        $term = $request->input('q', '');
        $supplierId = $request->input('supplier_id');

        if (strlen($term) < 2 && !$supplierId) {
            return response()->json([]);
        }

        // Search Non-Trade Items
        $ntQuery = NonTradeItem::with('supplier')->where('name', 'LIKE', "%{$term}%");
        if ($supplierId) {
            $ntQuery->where('supplier_id', $supplierId);
        }
        $nonTradeItems = $ntQuery->orderBy('name')->limit(25)->get()->map(function ($item) {
            $displayName = $item->name;
            if ($item->supplier) {
                $displayName .= ' (' . $item->supplier->supplier_name . ')';
            }
            return [
                'display_name' => $displayName,
                'name' => $item->name,
                'item_code' => $item->item_code,
                'supplier_id' => $item->supplier_id,
                'supplier_name' => $item->supplier->supplier_name ?? null,
                'type' => 'non_trade',
            ];
        });

        // Search Trade Items
        $tQuery = TradeItem::with('supplier')->where('name', 'LIKE', "%{$term}%");
        if ($supplierId) {
            $tQuery->where('supplier_id', $supplierId);
        }
        $tradeItems = $tQuery->orderBy('name')->limit(25)->get()->map(function ($item) {
            $displayName = $item->name;
            if ($item->supplier) {
                $displayName .= ' (' . $item->supplier->supplier_name . ')';
            }
            return [
                'display_name' => $displayName,
                'name' => $item->name,
                'item_code' => $item->item_code,
                'supplier_id' => $item->supplier_id,
                'supplier_name' => $item->supplier->supplier_name ?? null,
                'type' => 'trade',
            ];
        });

        // Merge, deduplicate by name+supplier, and limit
        $combined = $nonTradeItems->merge($tradeItems)
            ->unique(fn($item) => $item['name'] . '|' . $item['supplier_id'])
            ->sortBy('name')
            ->take(50)
            ->values();

        return response()->json($combined);
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
            $prNo = 'PR-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

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

            foreach ($request->items as $index => $item) {
                PurchaseRequestItem::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'supplier_id' => $item['supplier_id'] ?? null,
                    'supplier_name' => $item['supplier_name'] ?? null,
                    'item_no' => $index + 1,
                    'item_code' => $item['item_code'] ?? null,
                    'date_needed' => $item['date_needed'] ?? null,
                    'qty' => $item['qty'],
                    'uom' => $item['uom'],
                    'description' => $item['description'],
                    'unit_price' => $item['unit_price'] ?? null,
                    'amount' => $item['amount'] ?? null,
                    'remarks' => $item['remarks'] ?? null,
                    'note' => $item['note'] ?? null,
                ]);

                if (!empty($item['description'])) {
                    NonTradeItem::firstOrCreate(
                        ['name' => trim($item['description'])],
                        ['unit' => $item['uom'] ?? null]
                    );
                }
            }

            DB::commit();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'Created',
                'item' => $purchaseRequest->pr_no,
                'target' => $purchaseRequest->company,
                'type' => 'Purchase Request',
                'message' => 'Created Purchase Request ' . $purchaseRequest->pr_no . ' for ' . $purchaseRequest->company,
            ]);

            return redirect()
                ->route('purchase_requests.show', $purchaseRequest->id)
                ->with('success', 'Purchase Request created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('PR Creation Error: ' . $e->getMessage(), ['exception' => $e]);
            return back()
                ->withInput()
                ->with('error', 'Error creating Purchase Request: ' . $e->getMessage())
                ->with('debug', true);
        }
    }

    /**
     * Display the specified purchase request
     */
    public function show($id)
    {
        $purchaseRequest = PurchaseRequest::with(['items', 'creator', 'approver', 'departmentHeadApprover'])
            ->findOrFail($id);

        return view('purchase_requests.show', compact('purchaseRequest'));
    }

    public function print($id)
    {
        $purchaseRequest = PurchaseRequest::with(['items', 'creator', 'approver', 'departmentHeadApprover'])
            ->findOrFail($id);

        return view('purchase_requests.print', compact('purchaseRequest'));
    }

    /**
     * Show the form for editing the specified purchase request
     */
    public function edit($id)
    {
        $purchaseRequest = PurchaseRequest::with('items')->findOrFail($id);

        $notesOnly = false;
        if (in_array($purchaseRequest->status, ['approved']) && $purchaseRequest->approved_at !== null) {
            $notesOnly = true;
        }

        if ($purchaseRequest->status === 'rejected' && $purchaseRequest->approved_at !== null) {
            return redirect()
                ->route('purchase_requests.show', $purchaseRequest->id)
                ->with('error', 'Cannot edit a rejected Purchase Request.');
        }

        $companies = [
            'North Breeders Corporation',
            'Pacific Agro Resources Inc.',
            'Pacific Magalang Agriventures Inc.',
            'Pacific Agrisolutions Enterprises Inc.',
        ];

        return view('purchase_requests.edit', compact('purchaseRequest', 'companies', 'notesOnly'));
    }

    /**
     * Update only the notes on PR items (works for approved PRs)
     */
    public function updateNotes(Request $request, $id)
    {
        $purchaseRequest = PurchaseRequest::with('items')->findOrFail($id);

        DB::beginTransaction();
        try {
            if ($request->has('item_notes')) {
                foreach ($request->item_notes as $itemId => $note) {
                    PurchaseRequestItem::where('id', $itemId)
                        ->where('purchase_request_id', $purchaseRequest->id)
                        ->update(['note' => $note]);
                }
            }

            DB::commit();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'Updated Notes',
                'item' => $purchaseRequest->pr_no,
                'target' => $purchaseRequest->company,
                'type' => 'Purchase Request',
                'message' => 'Updated notes on Purchase Request ' . $purchaseRequest->pr_no,
            ]);

            return redirect()
                ->route('purchase_requests.show', $purchaseRequest->id)
                ->with('success', 'Notes updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error updating notes: ' . $e->getMessage());
        }
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

            $purchaseRequest->items()->delete();

            foreach ($request->items as $index => $item) {
                PurchaseRequestItem::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'supplier_id' => $item['supplier_id'] ?? null,
                    'supplier_name' => $item['supplier_name'] ?? null,
                    'item_no' => $index + 1,
                    'item_code' => $item['item_code'] ?? null,
                    'date_needed' => $item['date_needed'] ?? null,
                    'qty' => $item['qty'],
                    'uom' => $item['uom'],
                    'description' => $item['description'],
                    'unit_price' => $item['unit_price'] ?? null,
                    'amount' => $item['amount'] ?? null,
                    'remarks' => $item['remarks'] ?? null,
                    'note' => $item['note'] ?? null,
                ]);

                if (!empty($item['description'])) {
                    NonTradeItem::firstOrCreate(
                        ['name' => trim($item['description'])],
                        ['unit' => $item['uom'] ?? null]
                    );
                }
            }

            DB::commit();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'Updated',
                'item' => $purchaseRequest->pr_no,
                'target' => $purchaseRequest->company,
                'type' => 'Purchase Request',
                'message' => 'Updated Purchase Request ' . $purchaseRequest->pr_no,
            ]);

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

            if ($purchaseRequest->created_by !== Auth::id() && !Auth::user()->isAdminUser() && !Auth::user()->canApprovePurchaseRequestsAsDH()) {
                return back()->with('error', 'You can only delete your own Purchase Requests.');
            }

            $prNo = $purchaseRequest->pr_no;
            $purchaseRequest->delete();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'Deleted',
                'item' => $prNo,
                'target' => 'N/A',
                'type' => 'Purchase Request',
                'message' => 'Deleted Purchase Request ' . $prNo,
            ]);

            return redirect()
                ->route('purchase_requests.index')
                ->with('success', 'Purchase Request deleted successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Error deleting Purchase Request: ' . $e->getMessage());
        }
    }

    /**
     * Approve as Department Head (Level 1)
     */
    public function approveDH(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->canApprovePurchaseRequestsAsDH()) {
            return redirect()->route('purchase_requests.index')
                ->with('error', 'Unauthorized to approve as Department Head.');
        }

        $purchaseRequest = PurchaseRequest::find($id);

        if (!$purchaseRequest) {
            return redirect()->route('purchase_requests.index')
                ->with('error', 'Purchase Request not found.');
        }

        if ($purchaseRequest->approval_stage !== 'pending_dh') {
            return redirect()->route('purchase_requests.show', $id)
                ->with('error', 'This PR is not pending Department Head approval. Current stage: ' . $purchaseRequest->approval_stage);
        }

        $purchaseRequest->update([
            'approval_stage' => 'pending_management',
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
            'item' => $purchaseRequest->pr_no,
            'target' => $purchaseRequest->company,
            'type' => 'Purchase Request',
            'message' => 'Department Head noted Purchase Request ' . $purchaseRequest->pr_no,
        ]);

        return redirect()->back()->with('success', 'PR noted by Department Head. Forwarded to Management.');
    }

    /**
     * Approve as Management (Level 2 - GM/CFO)
     */
    public function approveManagement(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->canApprovePurchaseRequestsAsManagement()) {
            return redirect()->route('purchase_requests.index')
                ->with('error', 'Unauthorized to approve as Management.');
        }

        $purchaseRequest = PurchaseRequest::find($id);

        if (!$purchaseRequest) {
            return redirect()->route('purchase_requests.index')
                ->with('error', 'Purchase Request not found.');
        }

        if ($purchaseRequest->approval_stage !== 'pending_management') {
            return redirect()->route('purchase_requests.show', $id)
                ->with('error', 'This PR is not pending Management approval. Current stage: ' . $purchaseRequest->approval_stage);
        }

        if ($this->isPMAI($purchaseRequest->company) && !$user->isAdminUser() && !$user->canApprovePurchaseRequestsAsManagement()) {
            return redirect()->route('purchase_requests.show', $id)
                ->with('error', 'Only CFO can approve PRs for this company.');
        }

        $purchaseRequest->update([
            'approval_stage' => 'pending_executive',
            'management_approved_by' => Auth::id(),
            'management_approved_at' => now(),
            'management_approved_latitude' => $request->input('latitude'),
            'management_approved_longitude' => $request->input('longitude'),
            'management_approved_location' => $request->input('location'),
            'rejection_reason' => null,
        ]);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Approved (Management)',
            'item' => $purchaseRequest->pr_no,
            'target' => $purchaseRequest->company,
            'type' => 'Purchase Request',
            'message' => 'Management approved Purchase Request ' . $purchaseRequest->pr_no,
        ]);

        return redirect()->back()->with('success', 'PR approved by Management. Forwarded to Executive.');
    }

    /**
     * Approve as Executive (Level 3 - President/VP) - Final approval
     */
    public function approve(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->canApprovePurchaseRequestsAsExecutive()) {
            return redirect()->route('purchase_requests.index')
                ->with('error', 'Unauthorized to approve as Executive.');
        }

        $purchaseRequest = PurchaseRequest::find($id);

        if (!$purchaseRequest) {
            return redirect()->route('purchase_requests.index')
                ->with('error', 'Purchase Request not found.');
        }

        if ($purchaseRequest->approval_stage !== 'pending_executive') {
            return redirect()->route('purchase_requests.show', $id)
                ->with('error', 'This PR is not pending Executive approval. Current stage: ' . $purchaseRequest->approval_stage);
        }

        $purchaseRequest->update([
            'approval_stage' => 'approved',
            'status' => 'approved',
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
            'item' => $purchaseRequest->pr_no,
            'target' => $purchaseRequest->company,
            'type' => 'Purchase Request',
            'message' => 'Approved Purchase Request ' . $purchaseRequest->pr_no,
        ]);

        return redirect()->back()->with('success', 'Purchase Request approved successfully!');
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

        $request->validate(['rejection_reason' => 'nullable|string|max:500']);

        $purchaseRequest = PurchaseRequest::findOrFail($id);

        $purchaseRequest->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Rejected',
            'item' => $purchaseRequest->pr_no,
            'target' => $purchaseRequest->company,
            'type' => 'Purchase Request',
            'message' => 'Rejected Purchase Request ' . $purchaseRequest->pr_no,
        ]);

        return redirect()->back()->with('success', 'Purchase Request rejected.');
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

    private function isTwoLevelApproval(?string $company): bool
    {
        if (!$company) return false;
        $c = strtolower($company);
        return str_contains($c, 'magalang') || str_contains($c, 'agrisolutions');
    }

    private function isPMAI(?string $company): bool
    {
        if (!$company) return false;
        return str_contains(strtolower($company), 'meatplus');
    }
}