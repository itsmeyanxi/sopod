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
        
        if (!$user->canApproveItems() && !RoleHelper::canManageItems()) {
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

    // store
    public function store(Request $request)
    {
        if (!RoleHelper::canManageItems()) {
            return RoleHelper::unauthorized();
        }

        $validatedData = $request->validate([
            'item_description' => 'nullable|string',
            'item_code'        => 'required|string|max:255',
            'item_category'    => 'nullable|string|max:255',
            'brand'            => 'nullable|string|max:255',
            'is_enabled'       => 'sometimes|boolean',
        ]);

        $validatedData['is_enabled'] = $request->has('is_enabled') 
            ? $request->boolean('is_enabled') 
            : true;

        $validatedData['approval_status'] = 'pending';

        $item = Item::create($validatedData);

        // ✅ ADD DEBUG LOGGING
        \Log::info('🔥 ITEM CREATED', [
            'item_id' => $item->id,
            'item_code' => $item->item_code,
            'about_to_send_email' => true
        ]);

        // ✅ Send email notification with error handling
        /*
        try {
            $emailSent = app(\App\Services\NotificationService::class)->notifyNewItem($item);
            \Log::info('🔥 ITEM EMAIL SENT', ['success' => $emailSent]);
        } catch (\Exception $e) {
            \Log::error('🔥 ITEM EMAIL FAILED', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        */
        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action'    => 'Created',
            'item'      => $item->item_code . ' - ' . $item->item_description,
            'target'    => $item->brand ?? 'N/A',
            'type'      => 'Item',
            'message'   => 'Added new item (Pending Approval): ' . $item->item_description,
        ]);

        return redirect()->route('items.index')->with('success', 'Item created and sent for approval!');
    }

    /**
 * Export items to CSV/Excel
 */
public function export(Request $request)
{
    $query = Item::with(['addedBy', 'approvedBy']);

    // Apply filters if provided
    if ($request->has('status') && $request->status != 'all') {
        $query->where('approval_status', $request->status);
    }

    if ($request->has('enabled') && $request->enabled != 'all') {
        $query->where('is_enabled', $request->enabled == 'true' ? 1 : 0);
    }

    if ($request->has('category') && $request->category) {
        $query->where('item_category', 'LIKE', '%' . $request->category . '%');
    }

    if ($request->has('brand') && $request->brand) {
        $query->where('brand', 'LIKE', '%' . $request->brand . '%');
    }

    // Get items ordered by item code
    $items = $query->orderBy('item_code')->get();

    // Define CSV headers for download
    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="items_export_' . date('Y-m-d_His') . '.csv"',
        'Pragma' => 'no-cache',
        'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
        'Expires' => '0'
    ];

    // Create callback for streaming CSV
    $callback = function() use ($items) {
        $file = fopen('php://output', 'w');

        // Add UTF-8 BOM for proper Excel encoding
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

        // Add CSV column headers
        fputcsv($file, [
            'Item Code',
            'Item Description',
            'Brand',
            'Category',
            'Unit',
            'Approval Status',
            'Visibility Status',
            'Added By',
            'Added Date',
            'Approved By',
            'Approved Date',
            'Rejection Reason',
            'Last Updated'
        ]);

        // Add data rows
        foreach ($items as $item) {
            fputcsv($file, [
                $item->item_code ?? '',
                $item->item_description ?? '',
                $item->brand ?? 'N/A',
                $item->item_category ?? 'N/A',
                $item->unit ?? 'N/A',
                ucfirst($item->approval_status ?? 'pending'),
                $item->is_enabled ? 'Enabled' : 'Disabled',
                optional($item->addedBy)->name ?? 'N/A',
                $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : 'N/A',
                optional($item->approvedBy)->name ?? 'N/A',
                $item->approved_at ? date('Y-m-d H:i:s', strtotime($item->approved_at)) : 'N/A',
                $item->rejection_reason ?? 'N/A',
                $item->updated_at ? $item->updated_at->format('Y-m-d H:i:s') : 'N/A'
            ]);
        }

        fclose($file);
    };

    // Log the export activity
    Activity::create([
        'user_name' => Auth::user()->name ?? 'System',
        'action' => 'Exported',
        'item' => $items->count() . ' items',
        'target' => 'CSV Export',
        'type' => 'Item',
        'message' => 'Exported ' . $items->count() . ' items to CSV',
    ]);

    return response()->stream($callback, 200, $headers);
}

    // Approve item
    public function approve($id)
    {
        $user = Auth::user();
        
        if (!$user->canApproveItems() && !RoleHelper::canManageItems()) {
            return redirect()->route('items.index')->with('error', 'Unauthorized access.');
        }

        $item = Item::findOrFail($id);
        
        $item->update([
            'approval_status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        // app(\App\Services\NotificationService::class)->notifyItemStatusChange($item, 'approved');

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
        
        if (!$user->canApproveItems() && !RoleHelper::canManageItems()) {
            return redirect()->route('items.index')->with('error', 'Unauthorized access.');
        }

        $item = Item::findOrFail($id);
        
        $item->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $request->rejection_reason ?? null,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

       // app(\App\Services\NotificationService::class)->notifyItemStatusChange($item, 'rejected');

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
            !Auth::user()->canApproveItems() &&
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

        if ($item->is_locked) {
            return redirect()->route('items.show', $id)
                ->with('error', 'This Item is locked and cannot be edited.');
        }

        return view('items.edit', compact('item'));
    }

    // Update item
    public function update(Request $request, $id)
    {
        if (!RoleHelper::canManageItems()) {
            return RoleHelper::unauthorized();
        }

        $item = Item::findOrFail($id);

        if ($item->is_locked) {
            return redirect()->route('items.show', $id)
                ->with('error', 'This Item is locked and cannot be edited.');
        }

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

        if ($item->is_locked) {
            return redirect()->back()
                ->with('error', 'This Item is locked and cannot be deleted.');
        }

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

        if ($item->is_locked) {
            return redirect()->back()
                ->with('error', 'This Item is locked and cannot be modified.');
        }

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

   // ✅ FIXED: ItemController::bulkReject() method
public function bulkReject(Request $request)
{
    try {
        $itemIds = json_decode($request->input('item_ids'), true);
        $rejectionReason = $request->input('rejection_reason');
        
        if (empty($itemIds)) {
            return redirect()->back()->with('error', 'No items selected');
        }

        // ✅ FETCH items BEFORE updating
        $items = Item::whereIn('id', $itemIds)->get();

        // Update items to rejected status
        Item::whereIn('id', $itemIds)->update([
            'approval_status' => 'rejected',  
            'rejection_reason' => $rejectionReason,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // ✅ Send notifications for each item
        foreach ($items as $item) {
            // Refresh item to get updated data
            $item->fresh();
            $item->rejection_reason = $rejectionReason; // Ensure reason is set
            //app(\App\Services\NotificationService::class)->notifyItemStatusChange($item, 'rejected');
        }

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

// ✅ FIXED: ItemController::bulkApprove() method
public function bulkApprove(Request $request)
{
    try {
        $itemIds = json_decode($request->input('item_ids'), true);
        
        if (empty($itemIds)) {
            return redirect()->back()->with('error', 'No items selected');
        }

        // ✅ FETCH items BEFORE updating
        $items = Item::whereIn('id', $itemIds)->get();

        // Update items to approved status
        Item::whereIn('id', $itemIds)->update([
            'approval_status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        // Send notifications for each item (disabled — method not implemented yet)
        // foreach ($items as $item) {
        //     app(\App\Services\NotificationService::class)->notifyItemStatusChange($item->fresh(), 'approved');
        // }

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