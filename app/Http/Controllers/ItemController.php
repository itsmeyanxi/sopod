<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use App\Models\Activity;
use Illuminate\Support\Facades\Auth;
use App\Helpers\RoleHelper;

class ItemController extends Controller
{
    // Show all items (only approved items for regular users)
        public function index()
    {
        $user = Auth::user();
        
        // Users who can manage items see all items (including disabled ones)
        if ($user->canManageItems()) {
            $items = Item::all();
        }
        // Regular users only see approved AND enabled items
        else {
            $items = Item::approved()
                        ->where('is_enabled', 1)
                        ->get();
        }
        
        return view('items.index', compact('items'));
    }

    // Show pending items (for accounting approvers, admin, and IT)
    public function pending()
    {
        $user = Auth::user();
        
        if ($user->role !== 'accounting_approver' && !RoleHelper::canManageItems()) {
            return redirect()->route('items.index')->with('error', 'Unauthorized access.');
        }

        $items = Item::pending()->get();
        return view('items.pending', compact('items'));
    }

    // Show create form
    public function create()
    {
        if (!RoleHelper::canManageItems()) {
            return RoleHelper::unauthorized();
        }

        return view('items.create');
    }

    // Save to DB (status = pending)
    public function store(Request $request)
    {
        if (!RoleHelper::canManageItems()) {
            return RoleHelper::unauthorized();
        }

        $validatedData = $request->validate([
            'item_description' => 'nullable|string',
            'item_code' => 'required|string|max:255',
            'item_category' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'is_enabled' => 1, 

        ]);

        // Set initial status as pending
        $validatedData['approval_status'] = 'pending';

        $item = Item::create($validatedData);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Created',
            'item' => $item->item_code . ' - ' . $item->item_description,
            'target' => $item->brand ?? 'N/A',
            'type' => 'Item',
            'message' => 'Added new item (Pending Approval): ' . $item->item_description,
        ]);

        return redirect()->route('items.index')->with('success', 'Item created and sent for approval!');
    }

    // Approve item
    public function approve($id)
    {
        $user = Auth::user();
        
        if ($user->role !== 'accounting_approver' && !RoleHelper::canManageItems()) {
            return redirect()->route('items.index')->with('error', 'Unauthorized access.');
        }

        $item = Item::findOrFail($id);
        
        $item->update([
            'approval_status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Approved',
            'item' => $item->item_code . ' - ' . $item->item_description,
            'target' => $item->brand ?? 'N/A',
            'type' => 'Item',
            'message' => 'Approved item: ' . $item->item_description,
        ]);

        return redirect()->back()->with('success', 'Item approved successfully!');
    }

    // Reject item
    public function reject(Request $request, $id)
    {
        $user = Auth::user();
        
        if ($user->role !== 'accounting_approver' && !RoleHelper::canManageItems()) {
            return redirect()->route('items.index')->with('error', 'Unauthorized access.');
        }

        $item = Item::findOrFail($id);
        
        $item->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $request->rejection_reason ?? null,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Rejected',
            'item' => $item->item_code . ' - ' . $item->item_description,
            'target' => $item->brand ?? 'N/A',
            'type' => 'Item',
            'message' => 'Rejected item: ' . $item->item_description . ($request->rejection_reason ? ' | Reason: ' . $request->rejection_reason : ''),
        ]);

        return redirect()->back()->with('success', 'Item rejected successfully!');
    }

    // Show single item details
    public function show($id)
    {
        $item = Item::findOrFail($id);
        
        // Check if user can view this item
        if (!$item->isApproved() && 
            Auth::user()->role !== 'accounting_approver' && 
            !RoleHelper::canManageItems()) {
            return redirect()->route('items.index')->with('error', 'Item not found or pending approval.');
        }
        
        return view('items.show', compact('item'));
    }

    // Show edit form
    public function edit($id)
    {
        if (!RoleHelper::canManageItems()) {
            return RoleHelper::unauthorized();
        }

        $item = Item::findOrFail($id);
        return view('items.edit', compact('item'));
    }

    // Update item
    public function update(Request $request, $id)
    {
        if (!RoleHelper::canManageItems()) {
            return RoleHelper::unauthorized();
        }

        $item = Item::findOrFail($id);

        $validatedData = $request->validate([
            'item_description' => 'nullable|string',
            'item_code' => 'required|string|max:255',
            'item_category' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
        ]);

        $item->update($validatedData);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Updated',
            'item' => $item->item_code . ' - ' . $item->item_description,
            'target' => $item->brand ?? 'N/A',
            'type' => 'Item',
            'message' => 'Updated item: ' . $item->item_description,
        ]);

        return redirect()->route('items.index')->with('success', 'Item updated successfully!');
    }

    // Delete item
    public function destroy($id)
    {
        if (!RoleHelper::canManageItems()) {
            return RoleHelper::unauthorized();
        }

        $item = Item::findOrFail($id);
        
        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Deleted',
            'item' => $item->item_code . ' - ' . $item->item_description,
            'target' => $item->brand ?? 'N/A',
            'type' => 'Item',
            'message' => 'Deleted item: ' . $item->item_description,
        ]);

        $item->delete();

        return redirect()->route('items.index')->with('success', 'Item deleted successfully!');
    }

   // Toggle item status (Enable/Disable)
    public function toggleStatus($id)
    {
        // Only Admin and IT can toggle status
        if (!auth()->user()->canEditItems()) {
            return redirect()->route('items.index')
                ->with('error', 'You do not have permission to toggle item status.');
        }

        $item = Item::findOrFail($id);

        // Toggle the status
        $item->is_enabled = !$item->is_enabled;
        $item->save();

        $status = $item->is_enabled ? 'enabled' : 'disabled';

        // Log the activity
        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Status Changed',
            'item' => $item->item_code . ' - ' . $item->item_description,
            'target' => $status,
            'type' => 'Item',
            'message' => 'Changed item status to: ' . $status,
        ]);

        return redirect()->back()->with('success', "Item '{$item->item_code}' has been {$status}.");
    }

    public function bulkReject(Request $request)
    {
        try {
            $itemIds = json_decode($request->input('item_ids'), true);
            $rejectionReason = $request->input('rejection_reason');
            
            if (empty($itemIds)) {
                return redirect()->back()->with('error', 'No items selected');
            }

            // ✅ Update items to rejected status (correct column name)
            Item::whereIn('id', $itemIds)->update([
                'approval_status' => 'rejected',  // ✅ Changed from 'status' to 'approval_status'
                'rejection_reason' => $rejectionReason,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // Log activity
            Activity::create([
                'user_name' => auth()->user()->name ?? 'System',
                'action' => 'Bulk Rejected',
                'item' => count($itemIds) . ' items',
                'target' => 'Items',
                'type' => 'Item',
                'message' => 'Bulk rejected ' . count($itemIds) . ' items' . ($rejectionReason ? ' | Reason: ' . $rejectionReason : ''),
            ]);

            return redirect()->back()->with('success', count($itemIds) . ' item(s) rejected successfully!');

        } catch (\Exception $e) {
            \Log::error('Bulk reject failed', ['error' => $e->getMessage()]);
            
            return redirect()->back()->with('error', 'Failed to reject items: ' . $e->getMessage());
        }
    }

    // ✅ Add this bulk approve method too
    public function bulkApprove(Request $request)
    {
        try {
            $itemIds = json_decode($request->input('item_ids'), true);
            
            if (empty($itemIds)) {
                return redirect()->back()->with('error', 'No items selected');
            }

            // Update items to approved status
            Item::whereIn('id', $itemIds)->update([
                'approval_status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'rejection_reason' => null,
            ]);

            // Log activity
            Activity::create([
                'user_name' => auth()->user()->name ?? 'System',
                'action' => 'Bulk Approved',
                'item' => count($itemIds) . ' items',
                'target' => 'Items',
                'type' => 'Item',
                'message' => 'Bulk approved ' . count($itemIds) . ' items',
            ]);

            return redirect()->back()->with('success', count($itemIds) . ' item(s) approved successfully!');

        } catch (\Exception $e) {
            \Log::error('Bulk approve failed', ['error' => $e->getMessage()]);
            
            return redirect()->back()->with('error', 'Failed to approve items: ' . $e->getMessage());
        }
    }
}