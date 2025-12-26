<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Deliveries;
use App\Models\DeliveryItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Activity;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DeliveriesExport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Traits\LogsControllerActions;

class DeliveriesController extends Controller
{
       public function index(Request $request)
{
    $query = Deliveries::with(['salesOrder.customer'])
        ->withSum('items as quantity', 'quantity')
        ->withSum('items as total_amount', 'total_amount')
        ->orderBy('request_delivery_date', 'desc'); 

    if ($request->filled('delivery_date_from')) {
        $query->whereDate('request_delivery_date', '>=', $request->delivery_date_from);
    }

    if ($request->filled('delivery_date_to')) {
        $query->whereDate('request_delivery_date', '<=', $request->delivery_date_to);
    }

    // Status filters
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    if ($request->filled('approval_status')) {
        $query->where('approval_status', $request->approval_status);
    }

    // Search filter
    if ($request->search) {
        $query->where(function ($q) use ($request) {
            $q->where('dr_no', 'like', '%' . $request->search . '%')
            ->orWhere('sales_order_number', 'like', '%' . $request->search . '%')  
            ->orWhere('customer_code', 'like', '%' . $request->search . '%')
            ->orWhere('customer_name', 'like', '%' . $request->search . '%')
            ->orWhere('plate_no', 'like', '%' . $request->search . '%') 
            ->orWhereHas('salesOrder', function ($sq) use ($request) {
                $sq->where('customer_name', 'like', '%' . $request->search . '%')
                    ->orWhere('sales_order_number', 'like', '%' . $request->search . '%');  
            })
            ->orWhereHas('salesOrder.customer', function ($cq) use ($request) {
                $cq->where('customer_name', 'like', '%' . $request->search . '%')
                    ->orWhere('customer_code', 'like', '%' . $request->search . '%'); 
            });
        });
    }

    $deliveries = $query->get();

    return view('deliveries.index', compact('deliveries'));
}

    // Create form
    public function create()
    {
        $salesOrders = SalesOrder::where('status', 'Approved')->get();
        return view('deliveries.create', compact('salesOrders'));
    }

    /**
     * UPDATE 
     */

public function update(Request $request, $id)
{
    try {
        $delivery = Deliveries::findOrFail($id);

        $validated = $request->validate([
            'sales_order_number' => 'required|string|max:255',
            'delivery_type' => 'required|string|in:Full,Partial',
            'dr_no' => ['required', 'string', 'max:255', Rule::unique('deliveries', 'dr_no')->ignore($delivery->id)],
            'sales_invoice_no' => ['nullable', 'string', 'max:255', Rule::unique('deliveries', 'sales_invoice_no')->ignore($delivery->id)],
            'customer_code' => 'nullable|string|max:255',
            'customer_name' => 'nullable|string|max:255',
            'tin_no' => 'nullable|string|max:255',
            'branch' => 'nullable|string|max:255',
            'sales_rep' => 'nullable|string|max:255',
            'sales_representative' => 'nullable|string|max:255',
            'sales_executive' => 'nullable|string|max:255',
            'po_number' => 'nullable|string|max:255',
            'request_delivery_date' => 'nullable|date',
            'status' => 'required|string|in:Delivered,Cancelled',
            'plate_no' => 'nullable|string|max:255',
            'approved_by' => 'required|string|max:255',
            'additional_instructions' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'items' => 'required|array|min:1',
            'items.*.item_code' => 'nullable|string|max:255',
            'items.*.item_description' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.original_quantity' => 'nullable|numeric|min:0',
            'items.*.remaining_quantity' => 'nullable|numeric|min:0',
            'items.*.uom' => 'nullable|string|max:50',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.total_amount' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:2000',
        ]);

        // Normalize empty strings to nulls
        foreach (['customer_code', 'customer_name', 'branch', 'tin_no', 'sales_rep', 'sales_representative', 'sales_executive', 'po_number', 'plate_no'] as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        // Handle attachment
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time() . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());
            $uploadPath = public_path('delivery_images');
            if (!file_exists($uploadPath)) mkdir($uploadPath, 0755, true);
            if ($delivery->attachment && file_exists(public_path('delivery_images/' . $delivery->attachment))) {
                @unlink(public_path('delivery_images/' . $delivery->attachment));
            }
            $file->move($uploadPath, $filename);
            $validated['attachment'] = $filename;
        }

        $items = $validated['items'];
        unset($validated['items']);
        $delivery->update($validated);

        // Fetch SO for reference
        $salesOrder = SalesOrder::with('items')->where('sales_order_number', $validated['sales_order_number'])->first();
        if (!$salesOrder) {
            throw new \Exception('Sales Order not found');
        }

        $soItemsMap = collect();
        foreach ($salesOrder->items as $soItem) {
            $soItemsMap->put($soItem->item_code, $soItem);
        }

        // ✅ Compute previous delivered sums, EXCLUDING this delivery and qty=0 items
        $deliveredSums = DeliveryItem::whereHas('delivery', function($q) use ($validated, $delivery) {
                $q->where('sales_order_number', $validated['sales_order_number'])
                  ->where('status', 'Delivered');
            })
            ->where('delivery_id', '!=', $delivery->id)
            ->where('quantity', '>', 0) // ✅ Only count actual deliveries
            ->select('item_code', DB::raw('SUM(quantity) as total_delivered'))
            ->groupBy('item_code')
            ->get()
            ->keyBy('item_code');

        // Delete existing delivery items for a clean replace
        DeliveryItem::where('delivery_id', $delivery->id)->delete();

        // ✅ Create updated delivery items (INCLUDING qty=0 for tracking)
        foreach ($items as $item) {
            $itemCode = $item['item_code'] ?? null;
            $soItem = $soItemsMap->get($itemCode);

            if ($soItem && $soItem->quantity > 0) {
                $originalQty = $soItem->quantity;
            } else {
                $originalQty = $item['original_quantity'] ?? ($item['quantity'] ?? 0);
            }

            $previousDelivered = $deliveredSums->get($itemCode)?->total_delivered ?? 0;
            $deliveredQty = $item['quantity'] ?? 0;
            $newTotalDelivered = $previousDelivered + $deliveredQty;
            $remainingQty = max(0, $originalQty - $newTotalDelivered);

            $itemRecord = !$soItem && $itemCode ? Item::where('item_code', $itemCode)->first() : null;

            DeliveryItem::create([
                'delivery_id' => $delivery->id,
                'item_id' => $soItem?->item_id ?? $itemRecord?->id ?? null,
                'sales_order_item_id' => $soItem?->id ?? null,
                'item_code' => $itemCode,
                'item_description' => $item['item_description'] ?? null,
                'brand' => $soItem?->brand ?? $itemRecord?->brand ?? null,
                'item_category' => $soItem?->item_category ?? $itemRecord?->item_category ?? null,
                'quantity' => $deliveredQty, // ✅ Can be 0 if temporarily removed
                'original_quantity' => $originalQty,
                'remaining_quantity' => $remainingQty,
                'uom' => $item['uom'] ?? null,
                'unit_price' => $item['unit_price'] ?? 0,
                'total_amount' => $item['total_amount'] ?? 0,
                'delivery_batch' => $validated['delivery_batch'] ?? null,
                'notes' => $item['notes'] ?? $soItem?->note ?? null,
            ]);
        }

        // ✅ Check if SO should be closed (AFTER all items are updated)
        $salesOrder->fresh()->checkAndClose();

        // app(\App\Services\NotificationService::class)->notifySalesOrderUpdated($salesOrder);

        // Create activity log
        Activity::create([
            'user_name' => auth()->user()->name ?? 'System',
            'action' => 'Updated',
            'item' => $delivery->dr_no . ' - ' . ($delivery->customer_name ?? 'N/A'),
            'target' => $delivery->sales_order_number ?? 'N/A',
            'type' => 'Delivery',
            'message' => 'Updated delivery: ' . $delivery->dr_no,
        ]);

        return response()->json(['success' => true, 'message' => 'Delivery updated successfully!']);
    } catch (\Exception $e) {
        Log::error('💥 Delivery update failed', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to update delivery: ' . $e->getMessage()
        ], 500);
    }
}

public function search(Request $request)
{
    $soNumber = $request->input('so_number');
    $deliveryBatch = $request->input('delivery_batch');

    \Log::info('🔍 Delivery search request:', [
        'so_number' => $soNumber,
        'delivery_batch' => $deliveryBatch
    ]);

    if (!$soNumber) {
        return response()->json(['error' => 'Please provide a Sales Order number.'], 400);
    }

    $soExists = SalesOrder::where('sales_order_number', $soNumber)->first();

    if (!$soExists) {
        return response()->json(['error' => 'Sales Order not found. Please check the SO number and try again.'], 404);
    }

    // ✅ Block declined/cancelled/pending Sales Orders - ONLY allow Approved
    if ($soExists->status === 'Declined') {
        return response()->json([
            'error' => 'This Sales Order has been declined and cannot be used for deliveries.',
            'error_type' => 'declined',
            'show_alert' => true
        ], 403);
    }

    if ($soExists->status === 'Cancelled') {
        return response()->json([
            'error' => 'This Sales Order has been cancelled and cannot be used for deliveries.',
            'error_type' => 'cancelled',
            'show_alert' => true
        ], 403);
    }

    if ($soExists->status === 'Pending') {
        return response()->json([
            'error' => 'This Sales Order is still pending approval. Only approved Sales Orders can be used for deliveries.',
            'error_type' => 'pending',
            'show_alert' => true
        ], 403);
    }

    if ($soExists->status !== 'Approved') {
        return response()->json([
            'error' => "This Sales Order has status '{$soExists->status}'. Only approved Sales Orders can be used for deliveries.",
            'error_type' => 'not_approved',
            'show_alert' => true
        ], 403);
    }

    // ✅ Fetch all SO items
    $soItems = SalesOrderItem::where('sales_order_id', $soExists->id)
        ->where('batch_status', 'Active')
        ->with('item')
        ->get();

    if ($soItems->isEmpty()) {
        return response()->json(['error' => 'No items found in this Sales Order.'], 404);
    }

    // ✅ Get request delivery date
    $requestDeliveryDate = $soItems->first()->request_delivery_date ?? $soExists->request_delivery_date;

    // ✅ Check existing deliveries for THIS SPECIFIC SO ONLY
    $existingDeliveries = Deliveries::where('sales_order_number', $soNumber)
        ->where(function($q) {
            $q->where('status', '!=', 'Pending')
              ->orWhereHas('items');
        })
        ->orderBy('created_at', 'asc')
        ->get();

    $deliveryCount = $existingDeliveries->count();
    $hasPartialDelivery = $existingDeliveries->where('delivery_type', 'Partial')->count() > 0;
    
    $hasPulledOut = $existingDeliveries->where('is_pulled_out', true)->count() > 0;
    $hasCancelled = $existingDeliveries->where('status', 'Cancelled')->count() > 0;
    $hasRejected = $existingDeliveries->where('approval_status', 'Rejected')->count() > 0;

    // ✅ Check if we're editing an existing delivery
    $delivery = null;
    $isEditMode = false;
    
    if ($deliveryBatch && $deliveryBatch !== 'new') {
        $delivery = Deliveries::where('sales_order_number', $soNumber)
            ->where('delivery_batch', $deliveryBatch)
            ->with('items')
            ->first();
        
        $isEditMode = $delivery ? true : false;
    }

    // ✅ Check delivery statuses
    $hasRejectedDelivery = $existingDeliveries->where('approval_status', 'Rejected')->count() > 0;
    $hasCancelledDelivery = $existingDeliveries->where('status', 'Cancelled')->count() > 0;

    // ✅ Block search if delivery was rejected/cancelled (but allow edit mode)
    if ($hasRejectedDelivery && !$isEditMode) {
        return response()->json([
            'error' => 'This Sales Order has been rejected for delivery and cannot be searched. It is view-only.',
            'error_type' => 'rejected',
            'show_alert' => true
        ], 403);
    }

    if ($hasCancelledDelivery && !$isEditMode) {
        return response()->json([
            'error' => 'This Sales Order has a cancelled delivery and cannot be searched. It is view-only.',
            'error_type' => 'cancelled',
            'show_alert' => true
        ], 403);
    }

    \Log::info('🔍 Delivery check', [
        'so_number' => $soNumber,
        'so_status' => $soExists->status,
        'is_closed' => $soExists->is_closed,
        'delivery_count' => $deliveryCount,
        'has_partial' => $hasPartialDelivery,
        'is_edit_mode' => $isEditMode,
    ]);

    // ✅ FIXED: Calculate ACTUAL delivered quantities (excluding qty=0 items)
    $deliveredQuery = DeliveryItem::whereHas('delivery', function($q) use ($soNumber) {
            $q->where('sales_order_number', $soNumber)
              ->where('approval_status', 'Approved')
              ->where('status', 'Delivered');
        })
        ->where('quantity', '>', 0); // ✅ CRITICAL: Only count items that were actually delivered

    if ($delivery && $isEditMode) {
        $deliveredQuery->where('delivery_id', '!=', $delivery->id);
    }

    $deliveredSums = $deliveredQuery
        ->select('item_code', DB::raw('SUM(quantity) as total_delivered'))
        ->groupBy('item_code')
        ->get()
        ->keyBy('item_code');

    // ✅ Build items list and check if anything remains to deliver
    $items = [];
    $hasRemainingItems = false;

    foreach ($soItems as $soItem) {
        $originalQty = $soItem->quantity ?? 0;
        $alreadyDelivered = $deliveredSums->get($soItem->item_code)?->total_delivered ?? 0;
        $remainingAvailable = $originalQty - $alreadyDelivered;

        // ✅ For edit mode: show ALL items including those with 0 quantity
        if ($isEditMode && $delivery) {
            $existingDeliveryItem = $delivery->items->firstWhere('item_code', $soItem->item_code);
            
            $deliveredQty = $existingDeliveryItem ? ($existingDeliveryItem->quantity ?? 0) : 0;
            $notes = $existingDeliveryItem ? ($existingDeliveryItem->notes ?? $soItem->note ?? null) : ($soItem->note ?? null);
            
            $items[] = [
                'item_code' => $soItem->item_code,
                'item_description' => $soItem->item_description ?? $soItem->item->item_description ?? '',
                'brand' => $soItem->brand ?? $soItem->item?->brand ?? '',
                'item_category' => $soItem->item_category ?? $soItem->item?->item_category ?? '',
                'quantity' => $deliveredQty,
                'original_quantity' => $originalQty,
                'remaining_quantity' => $remainingAvailable,
                'already_delivered' => $alreadyDelivered,
                'uom' => $soItem->unit ?? 'Kgs',
                'unit_price' => $soItem->unit_price ?? 0,
                'total_amount' => ($deliveredQty * ($soItem->unit_price ?? 0)),
                'notes' => $notes,
                'is_hidden' => $deliveredQty == 0,
            ];
            
            // Edit mode always has items to show
            $hasRemainingItems = true;
        } else {
            // ✅ NEW: For new deliveries, SKIP fully delivered items
            if ($remainingAvailable <= 0) {
                continue; // Don't add fully delivered items
            }
            
            $hasRemainingItems = true; // ✅ Found an item that needs delivery
            
            $items[] = [
                'item_code' => $soItem->item_code,
                'item_description' => $soItem->item_description ?? $soItem->item->item_description ?? '',
                'brand' => $soItem->brand ?? $soItem->item?->brand ?? '',
                'item_category' => $soItem->item_category ?? $soItem->item?->item_category ?? '',
                'quantity' => $remainingAvailable, // ✅ Default to remaining quantity
                'original_quantity' => $originalQty,
                'remaining_quantity' => $remainingAvailable,
                'already_delivered' => $alreadyDelivered,
                'uom' => $soItem->unit ?? 'Kgs',
                'unit_price' => $soItem->unit_price ?? 0,
                'total_amount' => ($remainingAvailable * ($soItem->unit_price ?? 0)),
                'notes' => $soItem->note ?? null,
                'is_hidden' => false,
            ];
        }
    }

    // ✅ CRITICAL: Block search if NO remaining items (all fully delivered)
    if (!$isEditMode && !$hasRemainingItems) {
        return response()->json([
            'error' => 'All items in this Sales Order have been fully delivered. No items available for new delivery.',
            'error_type' => 'delivered',
            'show_alert' => true
        ], 403);
    }

    // ✅ Determine batch name for new delivery
    $newBatchName = null;
    $canCreateNewDelivery = $hasRemainingItems;
    
    if (!$isEditMode && $canCreateNewDelivery) {
        if ($deliveryCount === 0) {
            $newBatchName = 'Pending';
        } else {
            $newBatchName = 'Batch ' . ($deliveryCount + 1);
        }
    } elseif (!$isEditMode && !$canCreateNewDelivery) {
        $newBatchName = 'View Only';
    }

    // Build response
    $attachmentUrl = null;
    $attachmentName = null;
    if ($delivery && $delivery->attachment) {
        $attachmentUrl = asset('delivery_images/' . $delivery->attachment);
        $attachmentName = $delivery->attachment;
    }

    // ✅ Enhanced info message
    $infoMessage = null;
    $showPartialAlert = false;
    $isViewOnly = !$hasRemainingItems && !$isEditMode;
    
    if (!$isEditMode && $deliveryCount > 0 && $hasRemainingItems) {
        $showPartialAlert = true;
        $fullyDeliveredCount = $soItems->count() - count($items);
        $infoMessage = "This SO has {$deliveryCount} previous delivery(ies). ";
        if ($fullyDeliveredCount > 0) {
            $infoMessage .= "{$fullyDeliveredCount} item(s) already fully delivered. ";
        }
        $infoMessage .= "Showing " . count($items) . " item(s) with remaining quantities.";
    }
    
    // ✅ Add delivery history info
    if ($hasPulledOut || $hasCancelled || $hasRejected) {
        $historyInfo = [];
        if ($hasPulledOut) $historyInfo[] = "Pulled Out";
        if ($hasCancelled) $historyInfo[] = "Cancelled";
        if ($hasRejected) $historyInfo[] = "Rejected";
        
        if ($infoMessage) {
            $infoMessage .= " | Delivery History: " . implode(", ", $historyInfo);
        } else {
            $infoMessage = "Delivery History: " . implode(", ", $historyInfo);
            $showPartialAlert = true;
        }
    }

    return response()->json([
        'success' => true,
        'id' => $isEditMode && $delivery ? $delivery->id : null,
        'is_edit_mode' => $isEditMode,
        'is_view_only' => $isViewOnly,
        'can_create_new_delivery' => $canCreateNewDelivery,
        'so_status' => $soExists->status,
        'is_closed' => $soExists->is_closed,
        'has_partial_delivery' => $hasPartialDelivery,
        'show_partial_alert' => $showPartialAlert,
        'info_message' => $infoMessage,
        'sales_order_number' => $soExists->sales_order_number,
        'delivery_batch' => $isEditMode ? $delivery->delivery_batch : $newBatchName,
        'customer_code' => $soExists->customer->customer_code ?? '',
        'customer_name' => $soExists->customer->customer_name ?? '',
        'tin_no' => $soExists->customer->tin_no ?? '',
        'branch' => $soExists->branch ?? '',
        'sales_rep' => $soExists->sales_rep ?? '',
        'sales_executive' => $soExists->sales_executive ?? '',
        'po_number' => $soExists->po_number ?? '',
        'request_delivery_date' => $requestDeliveryDate,
        'delivery_type' => $soExists->delivery_type ?? 'Full',
        'approved_by' => auth()->user()->name ?? 'System',
        'plate_no' => $isEditMode ? ($delivery->plate_no ?? '') : '',
        'sales_invoice_no' => $isEditMode ? ($delivery->sales_invoice_no ?? '') : '',
        'dr_no' => $isEditMode ? ($delivery->dr_no ?? '') : '',
        'status' => $isEditMode ? ($delivery->status ?? 'Delivered') : 'Delivered',
        'additional_instructions' => $soExists->additional_instructions ?? '',
        'attachment' => $delivery->attachment ?? null,
        'attachment_url' => $attachmentUrl,
        'attachment_name' => $attachmentName,
        'items' => $items,
        'delivery_count' => $deliveryCount,
        'items_count' => count($items),
        'delivery_history' => [
            'pulled_out' => $hasPulledOut,
            'cancelled' => $hasCancelled,
            'rejected' => $hasRejected,
            'total_deliveries' => $deliveryCount,
        ],
    ]);
}

public function store(Request $request)
{
    try {
        $validated = $request->validate([
            'sales_order_number' => 'required|string|max:255',
            'delivery_batch' => 'nullable|string|max:255',
            'delivery_type' => 'required|string|in:Full,Partial',
            'dr_no' => ['required', 'string', 'max:255', 'unique:deliveries,dr_no'],
            'customer_name' => 'nullable|string|max:255',
            'tin_no' => 'nullable|string|max:255',
            'branch' => 'nullable|string|max:255',
            'sales_rep' => 'nullable|string|max:255',
            'sales_representative' => 'nullable|string|max:255',
            'sales_executive' => 'nullable|string|max:255',
            'po_number' => 'nullable|string|max:255',
            'request_delivery_date' => 'nullable|date',
            'plate_no' => 'nullable|string|max:255',
            'sales_invoice_no' => 'nullable|string|max:255',
            'approved_by' => 'required|string|max:255',
            'additional_instructions' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'items' => 'required|array|min:1',
            'items.*.item_code' => 'nullable|string|max:255',
            'items.*.item_description' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.original_quantity' => 'nullable|numeric|min:0',
            'items.*.remaining_quantity' => 'nullable|numeric|min:0',
            'items.*.uom' => 'nullable|string|max:50',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.total_amount' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:2000',
        ]);

        $validated['approval_status'] = 'Pending';
        $validated['created_by'] = auth()->user()->name ?? 'System';

        \Log::info('✅ Validated items:', [
            'items' => $validated['items']
        ]);

        // Normalize empty strings to nulls
        foreach (['customer_name', 'branch', 'tin_no', 'sales_rep', 'sales_representative', 'sales_executive', 'po_number', 'plate_no', 'sales_invoice_no'] as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        // Handle attachment
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time() . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());
            $uploadPath = public_path('delivery_images');
            if (!file_exists($uploadPath)) mkdir($uploadPath, 0755, true);
            $file->move($uploadPath, $filename);
            $validated['attachment'] = $filename;
        }

        $items = $validated['items'];
        unset($validated['items']);

        // ✅ Set initial status and approval tracking
        $validated['status'] = 'Pending';
        $validated['approval_status'] = 'Pending';
        $validated['created_by'] = auth()->user()->name ?? 'System';

        // ✅ Count existing APPROVED deliveries only (with actual delivered items)
        $existingDeliveryCount = Deliveries::where('sales_order_number', $validated['sales_order_number'])
            ->where('approval_status', 'Approved')
            ->whereHas('items', function($q) {
                $q->where('quantity', '>', 0); // ✅ Only count deliveries with actual items
            })
            ->count();
        
        // Determine batch name
        if ($existingDeliveryCount === 0) {
            $validated['delivery_batch'] = ($validated['delivery_type'] === 'Partial') ? 'Batch 1' : 'Full Delivery';
        } else {
            $validated['delivery_batch'] = 'Batch ' . ($existingDeliveryCount + 1);
        }

        Log::info('📦 Creating delivery for approval', [
            'so_number' => $validated['sales_order_number'],
            'batch_name' => $validated['delivery_batch'],
            'created_by' => $validated['created_by'],
            'approval_status' => 'Pending',
        ]);

        // Fetch SO
        $salesOrder = SalesOrder::with('items')->where('sales_order_number', $validated['sales_order_number'])->first();
        if (!$salesOrder) {
            throw new \Exception('Sales Order not found');
        }

        $soItemsMap = collect();
        foreach ($salesOrder->items as $soItem) {
            $soItemsMap->put($soItem->item_code, $soItem);
        }

        // ✅ Get delivered sums (only approved deliveries with qty > 0)
        $deliveredSums = DeliveryItem::whereHas('delivery', function($q) use ($validated) {
                $q->where('sales_order_number', $validated['sales_order_number'])
                  ->where('approval_status', 'Approved')
                  ->where('status', 'Delivered');
            })
            ->where('quantity', '>', 0) // ✅ Only count actual deliveries
            ->select('item_code', DB::raw('SUM(quantity) as total_delivered'))
            ->groupBy('item_code')
            ->get()
            ->keyBy('item_code');

        // Create delivery
        $delivery = Deliveries::create($validated);

        // ✅ Create delivery items (INCLUDING items with qty=0 for tracking)
        foreach ($items as $item) {
            $itemCode = $item['item_code'] ?? null;
            $soItem = $soItemsMap->get($itemCode);

            $originalQty = $soItem ? $soItem->quantity : ($item['original_quantity'] ?? ($item['quantity'] ?? 0));
            $previousDelivered = $deliveredSums->get($itemCode)?->total_delivered ?? 0;
            $deliveredQty = $item['quantity'] ?? 0;
            $newTotalDelivered = $previousDelivered + $deliveredQty;
            $remainingQty = max(0, $originalQty - $newTotalDelivered);

            $itemRecord = !$soItem && $itemCode ? Item::where('item_code', $itemCode)->first() : null;

            DeliveryItem::create([
                'delivery_id' => $delivery->id,
                'item_id' => $soItem?->item_id ?? $itemRecord?->id ?? null,
                'sales_order_item_id' => $soItem?->id ?? null,
                'item_code' => $itemCode,
                'item_description' => $item['item_description'] ?? null,
                'brand' => $soItem?->brand ?? $itemRecord?->brand ?? null,
                'item_category' => $soItem?->item_category ?? $itemRecord?->item_category ?? null,
                'quantity' => $deliveredQty, // ✅ Can be 0 if temporarily removed
                'original_quantity' => $originalQty,
                'remaining_quantity' => $remainingQty,
                'uom' => $item['uom'] ?? null,
                'unit_price' => $item['unit_price'] ?? 0,
                'total_amount' => $item['total_amount'] ?? 0,
                'delivery_batch' => $validated['delivery_batch'],
                'notes' => $item['notes'] ?? $soItem?->note ?? null,      
            ]);
        }

        // \Log::info('🔥 DELIVERY CREATED', [
        //     'delivery_id' => $delivery->id,
        //     'dr_no' => $delivery->dr_no,
        //     'about_to_send_email' => true
        // ]);

        // try {
        //     $emailSent = app(\App\Services\NotificationService::class)->notifyNewDelivery($delivery);
        //     \Log::info('🔥 DELIVERY EMAIL SENT', ['success' => $emailSent]);
        // } catch (\Exception $emailError) {
        //     \Log::error('🔥 DELIVERY EMAIL FAILED', [
        //         'error' => $emailError->getMessage(),
        //         'trace' => $emailError->getTraceAsString()
        //     ]);
        //     // Don't throw - let delivery creation succeed even if email fails
        // }

        // Create activity log AFTER email attempt
        Activity::create([
            'user_name' => auth()->user()->name ?? 'System',
            'action' => 'Created',
            'item' => $delivery->dr_no . ' - ' . ($delivery->customer_name ?? 'N/A'),
            'target' => $delivery->sales_order_number ?? 'N/A',
            'type' => 'Delivery',
            'message' => "Created delivery for approval: {$delivery->dr_no} ({$delivery->delivery_batch}) - Status: Pending Approval",
        ]);

        return response()->json([
            'success' => true,
            'message' => "Delivery created successfully! Status: Pending Approval. Batch: {$delivery->delivery_batch}",
        ]);
    } catch (\Exception $e) {
        Log::error('💥 Delivery store failed', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to create delivery: ' . $e->getMessage()
        ], 500);
    }
}

public function approve($id)
{
    try {
        if (!\App\Helpers\RoleHelper::canApproveDeliveries()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to approve deliveries'], 403);
        }

        $delivery = Deliveries::with('items')->findOrFail($id);

        if ($delivery->approval_status !== 'Pending') {
            return response()->json(['success' => false, 'message' => 'Delivery is not pending approval'], 400);
        }

        $delivery->update([
            'approval_status' => 'Approved',
            'status' => 'Delivered',
            'approved_by_user' => auth()->user()->name,
            'approved_at' => now(),
        ]);

        // ✅ NOW check if SO should be closed after approval
        $salesOrder = SalesOrder::where('sales_order_number', $delivery->sales_order_number)->first();
        if ($salesOrder) {
            $salesOrder->fresh()->checkAndClose();
        }

        // app(\App\Services\NotificationService::class)->notifyDeliveryStatusChange($delivery, 'approved');

        Activity::create([
            'user_name' => auth()->user()->name ?? 'System',
            'action' => 'Approved',
            'item' => $delivery->dr_no . ' - ' . ($delivery->customer_name ?? 'N/A'),
            'target' => $delivery->sales_order_number ?? 'N/A',
            'type' => 'Delivery',
            'message' => "Approved delivery: {$delivery->dr_no}",
        ]);

        return response()->json(['success' => true, 'message' => 'Delivery approved successfully!']);
    } catch (\Exception $e) {
        Log::error('Delivery approval failed', ['error' => $e->getMessage()]);
        return response()->json(['success' => false, 'message' => 'Failed to approve delivery'], 500);
    }
}

public function reject(Request $request, $id)
{
    try {
        if (!\App\Helpers\RoleHelper::canApproveDeliveries()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to reject deliveries'], 403);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $delivery = Deliveries::findOrFail($id);

        if ($delivery->approval_status !== 'Pending') {
            return response()->json(['success' => false, 'message' => 'Delivery is not pending approval'], 400);
        }

        $delivery->update([
            'approval_status' => 'Rejected',
            'status' => 'Cancelled',
            'approved_by_user' => auth()->user()->name,
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        // app(\App\Services\NotificationService::class)->notifyDeliveryStatusChange($delivery, 'rejected');

        Activity::create([
            'user_name' => auth()->user()->name ?? 'System',
            'action' => 'Rejected',
            'item' => $delivery->dr_no . ' - ' . ($delivery->customer_name ?? 'N/A'),
            'target' => $delivery->sales_order_number ?? 'N/A',
            'type' => 'Delivery',
            'message' => "Rejected delivery: {$delivery->dr_no}. Reason: {$validated['rejection_reason']}",
        ]);

        return response()->json(['success' => true, 'message' => 'Delivery rejected successfully!']);
    } catch (\Exception $e) {
        Log::error('Delivery rejection failed', ['error' => $e->getMessage()]);
        return response()->json(['success' => false, 'message' => 'Failed to reject delivery'], 500);
    }
}


public function pullout(Request $request, $id)
{
    try {
        if (!\App\Helpers\RoleHelper::canApproveDeliveries()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'pullout_reason' => 'required|string|max:1000',
        ]);

        $delivery = Deliveries::findOrFail($id);

        if ($delivery->is_pulled_out) {
            return response()->json(['success' => false, 'message' => 'Already pulled out'], 400);
        }

        $delivery->update([
            'is_pulled_out' => true,
            'pulled_out_by' => auth()->user()->name,
            'pulled_out_at' => now(),
            'pullout_reason' => $validated['pullout_reason'],
            'status' => 'Cancelled',
        ]);

        Activity::create([
            'user_name' => auth()->user()->name,
            'action' => 'Pulled Out',
            'item' => $delivery->dr_no,
            'target' => $delivery->sales_order_number,
            'type' => 'Delivery',
            'message' => "Pulled out: {$delivery->dr_no}. Reason: {$validated['pullout_reason']}",
        ]);

        return response()->json(['success' => true, 'message' => 'Delivery pulled out successfully!']);
    } catch (\Exception $e) {
        Log::error('Pullout failed', ['error' => $e->getMessage()]);
        return response()->json(['success' => false, 'message' => 'Failed to pullout delivery'], 500);
    }
}

/**
 * ✅ UPDATED: Get edit data - Only PENDING deliveries can be edited
 */
public function getEditData($id)
{
    try {
        $delivery = Deliveries::with(['items', 'salesOrder.items'])->findOrFail($id);

        // ✅ Check if delivery is pulled out
        if ($delivery->is_pulled_out) {
            return response()->json(['success' => false, 'message' => 'Cannot edit pulled out delivery'], 400);
        }

        // ✅ CRITICAL: APPROVED DELIVERIES CANNOT BE EDITED BY ANYONE
        if ($delivery->approval_status === 'Approved') {
            return response()->json([
                'success' => false, 
                'message' => 'Approved deliveries are locked and cannot be edited. Only pullout is available for approved deliveries.'
            ], 403);
        }

        // ✅ For PENDING deliveries: Check permissions
        $userRole = auth()->user()->role;
        $canApprove = in_array($userRole, ['Admin', 'IT', 'Delivery_Approver']);
        
        if (!$canApprove) {
            // Creators need edit approval even for pending deliveries
            if (!$delivery->edit_approved) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Please request edit permission first for this pending delivery.'
                ], 403);
            }
        }

        // Map SO items for comparison
        $soItemsMap = collect();
        if ($delivery->salesOrder && $delivery->salesOrder->items) {
            foreach ($delivery->salesOrder->items as $soItem) {
                $soItemsMap->put($soItem->item_code, $soItem);
            }
        }

        // Get already delivered quantities (excluding this delivery)
        $deliveredSums = DeliveryItem::whereHas('delivery', function($q) use ($delivery) {
                $q->where('sales_order_number', $delivery->sales_order_number)
                  ->where('approval_status', 'Approved')
                  ->where('status', 'Delivered')
                  ->where('id', '!=', $delivery->id);
            })
            ->select('item_code', DB::raw('SUM(quantity) as total_delivered'))
            ->groupBy('item_code')
            ->get()
            ->keyBy('item_code');

        // Prepare items data
        $items = [];
        foreach ($delivery->items as $item) {
            $soItem = $soItemsMap->get($item->item_code);
            $originalQty = $soItem ? $soItem->quantity : ($item->original_quantity ?? 0);
            $alreadyDelivered = $deliveredSums->get($item->item_code)?->total_delivered ?? 0;

            $items[] = [
                'item_code' => $item->item_code,
                'item_description' => $item->item_description,
                'brand' => $item->brand,
                'item_category' => $item->item_category,
                'quantity' => $item->quantity ?? 0,
                'original_quantity' => $originalQty,
                'already_delivered' => $alreadyDelivered,
                'uom' => $item->uom,
                'unit_price' => $item->unit_price ?? 0,
                'total_amount' => $item->total_amount ?? 0,
                'notes' => $item->notes,
            ];
        }

        // ✅ Get PO Image from Sales Order
        $poImageUrl = null;
        $poImageName = null;
        if ($delivery->salesOrder && $delivery->salesOrder->po_image) {
            $poImagePath = public_path('po_images/' . $delivery->salesOrder->po_image);
            if (file_exists($poImagePath)) {
                $poImageUrl = asset('po_images/' . $delivery->salesOrder->po_image);
                $poImageName = $delivery->salesOrder->po_image;
            }
        }

        return response()->json([
            'success' => true,
            'id' => $delivery->id,
            'approval_status' => $delivery->approval_status,
            'sales_order_number' => $delivery->sales_order_number,
            'customer_name' => $delivery->customer_name ?? $delivery->salesOrder?->customer?->customer_name ?? 'N/A',
            'delivery_batch' => $delivery->delivery_batch,
            'dr_no' => $delivery->dr_no,
            'sales_invoice_no' => $delivery->sales_invoice_no,
            'po_number' => $delivery->po_number,
            'plate_no' => $delivery->plate_no,
            'po_image_url' => $poImageUrl,
            'po_image_name' => $poImageName,
            'items' => $items,
        ]);
    } catch (\Exception $e) {
        Log::error('Get edit data failed', ['error' => $e->getMessage()]);
        return response()->json(['success' => false, 'message' => 'Failed to load delivery data'], 500);
    }
}

/**
 * ✅ UPDATED: Quick update - Only PENDING deliveries can be updated
 */
public function quickUpdate(Request $request, $id)
{
    try {
        $delivery = Deliveries::with('items')->findOrFail($id);

        if ($delivery->is_pulled_out) {
            return response()->json(['success' => false, 'message' => 'Cannot edit pulled out delivery'], 400);
        }

        // ✅ CRITICAL: APPROVED DELIVERIES CANNOT BE EDITED BY ANYONE
        if ($delivery->approval_status === 'Approved') {
            return response()->json([
                'success' => false, 
                'message' => 'Approved deliveries are locked and cannot be edited. Only pullout is available for approved deliveries.'
            ], 403);
        }

        // ✅ For PENDING deliveries: Check permissions
        $userRole = auth()->user()->role;
        $canApprove = in_array($userRole, ['Admin', 'IT', 'Delivery_Approver']);
        
        if (!$canApprove) {
            // Creators need edit approval even for pending deliveries
            if (!$delivery->edit_approved) {
                return response()->json([
                    'success' => false, 
                    'message' => 'You need edit approval to modify this pending delivery.'
                ], 403);
            }
        }

        // Validate only editable fields
        $validated = $request->validate([
            'dr_no' => ['required', 'string', 'max:255', Rule::unique('deliveries', 'dr_no')->ignore($delivery->id)],
            'sales_invoice_no' => ['nullable', 'string', 'max:255', Rule::unique('deliveries', 'sales_invoice_no')->ignore($delivery->id)],
            'po_number' => 'nullable|string|max:255',
            'plate_no' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.item_code' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.original_quantity' => 'required|numeric|min:0',
            'items.*.already_delivered' => 'nullable|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.total_amount' => 'required|numeric|min:0',
        ]);

        // Update delivery
        $delivery->update([
            'dr_no' => $validated['dr_no'],
            'sales_invoice_no' => $validated['sales_invoice_no'],
            'po_number' => $validated['po_number'],
            'plate_no' => $validated['plate_no'],
        ]);

        // ✅ If this was an approved edit, reset the edit flags
        if ($delivery->edit_approved) {
            $delivery->update([
                'edit_requested' => false,
                'edit_approved' => false,
                'edit_requested_by' => null,
                'edit_requested_at' => null,
                'edit_approved_by' => null,
                'edit_approved_at' => null,
            ]);
        }

        // Update delivery items
        foreach ($validated['items'] as $itemData) {
            $deliveryItem = $delivery->items->firstWhere('item_code', $itemData['item_code']);
            
            if ($deliveryItem) {
                $remaining = max(0, $itemData['original_quantity'] - ($itemData['already_delivered'] ?? 0) - $itemData['quantity']);
                
                $deliveryItem->update([
                    'quantity' => $itemData['quantity'],
                    'remaining_quantity' => $remaining,
                    'total_amount' => $itemData['total_amount'],
                ]);
            }
        }
        
        // app(\App\Services\NotificationService::class)->notifyDeliveryUpdated($delivery->fresh());

        // Create activity log
        Activity::create([
            'user_name' => auth()->user()->name ?? 'System',
            'action' => 'Updated',
            'item' => $delivery->dr_no,
            'target' => $delivery->sales_order_number,
            'type' => 'Delivery',
            'message' => "Updated pending delivery: {$delivery->dr_no}",
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Delivery updated successfully!'
        ]);
    } catch (\Exception $e) {
        Log::error('Quick update failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to update delivery: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * ✅ Request edit - Only for PENDING deliveries
 */
public function requestEdit($id)
{
    try {
        $userRole = auth()->user()->role;
        
        // ✅ Only Delivery_Creator needs to request edit
        if (!in_array($userRole, ['Delivery_Creator'])) {
            return response()->json([
                'success' => false, 
                'message' => 'Unauthorized - Only Delivery Creators need to request edit permission.'
            ], 403);
        }

        $delivery = Deliveries::findOrFail($id);

        if ($delivery->is_pulled_out) {
            return response()->json(['success' => false, 'message' => 'Cannot request edit for pulled out delivery'], 400);
        }

        // ✅ CRITICAL: Only PENDING deliveries can be edited
        if ($delivery->approval_status !== 'Pending') {
            return response()->json([
                'success' => false, 
                'message' => 'Edit requests are only available for pending deliveries. Approved deliveries are locked and cannot be edited.'
            ], 400);
        }

        if ($delivery->edit_requested && !$delivery->edit_approved) {
            return response()->json(['success' => false, 'message' => 'Edit request already pending approval'], 400);
        }

        $delivery->update([
            'edit_requested' => true,
            'edit_requested_by' => auth()->user()->name,
            'edit_requested_at' => now(),
            'edit_approved' => false,
        ]);

        Activity::create([
            'user_name' => auth()->user()->name,
            'action' => 'Edit Requested',
            'item' => $delivery->dr_no,
            'target' => $delivery->sales_order_number,
            'type' => 'Delivery',
            'message' => "Requested edit permission for pending delivery: {$delivery->dr_no}",
        ]);

        return response()->json(['success' => true, 'message' => 'Edit request submitted successfully! Waiting for approver decision.']);
    } catch (\Exception $e) {
        Log::error('Edit request failed', ['error' => $e->getMessage()]);
        return response()->json(['success' => false, 'message' => 'Failed to request edit'], 500);
    }
}

/**
 * ✅ Approve edit request - Only for PENDING deliveries
 */
public function approveEdit($id)
{
    try {
        if (!\App\Helpers\RoleHelper::canApproveDeliveries()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $delivery = Deliveries::findOrFail($id);

        if (!$delivery->edit_requested) {
            return response()->json(['success' => false, 'message' => 'No edit request found'], 400);
        }

        if ($delivery->approval_status !== 'Pending') {
            return response()->json(['success' => false, 'message' => 'Can only approve edit requests for pending deliveries'], 400);
        }

        $delivery->update([
            'edit_approved' => true,
            'edit_approved_by' => auth()->user()->name,
            'edit_approved_at' => now(),
        ]);

        Activity::create([
            'user_name' => auth()->user()->name,
            'action' => 'Edit Approved',
            'item' => $delivery->dr_no,
            'target' => $delivery->sales_order_number,
            'type' => 'Delivery',
            'message' => "Approved edit request for: {$delivery->dr_no}",
        ]);

        return response()->json(['success' => true, 'message' => 'Edit request approved!']);
    } catch (\Exception $e) {
        Log::error('Edit approval failed', ['error' => $e->getMessage()]);
        return response()->json(['success' => false, 'message' => 'Failed to approve edit'], 500);
    }
}

/**
 * ✅ Reject edit request
 */
public function rejectEdit(Request $request, $id)
{
    try {
        $userRole = auth()->user()->role;
        
        if (!in_array($userRole, ['Admin', 'IT', 'Delivery_Approver'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $delivery = Deliveries::findOrFail($id);

        if (!$delivery->edit_requested) {
            return response()->json(['success' => false, 'message' => 'No edit request found'], 400);
        }

        if ($delivery->edit_approved) {
            return response()->json(['success' => false, 'message' => 'Edit request already approved'], 400);
        }

        $delivery->update([
            'edit_requested' => false,
            'edit_approved' => false,
            'edit_rejection_reason' => $validated['rejection_reason'],
            'edit_rejected_by' => auth()->user()->name,
            'edit_rejected_at' => now(),
        ]);

        Activity::create([
            'user_name' => auth()->user()->name,
            'action' => 'Edit Rejected',
            'item' => $delivery->dr_no,
            'target' => $delivery->sales_order_number,
            'type' => 'Delivery',
            'message' => "Rejected edit request for: {$delivery->dr_no}. Reason: {$validated['rejection_reason']}",
        ]);

        return response()->json(['success' => true, 'message' => 'Edit request rejected successfully!']);
    } catch (\Exception $e) {
        Log::error('Edit rejection failed', ['error' => $e->getMessage()]);
        return response()->json(['success' => false, 'message' => 'Failed to reject edit request'], 500);
    }
}


    /**
     * Helper function to generate ordinal names
     */
    private function getOrdinalName($number)
    {
        $ordinals = [
            1 => '1st',
            2 => '2nd',
            3 => '3rd',
        ];
        
        if (isset($ordinals[$number])) {
            return $ordinals[$number];
        }
        
        return $number . 'th';
    }

    // 📋 DELIVERIES LIST
    public function deliveriesList(Request $request)
    {
        $query = Deliveries::query()->orderByDesc('created_at');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $deliveries = $query->get();
        
        return view('deliveries.deliveries', compact('deliveries'));
    }

   public function printList(Request $request)
{
    $deliveryDateFrom = $request->input('delivery_date_from');
    $deliveryDateTo = $request->input('delivery_date_to');
    $search = $request->input('search');
    $status = $request->input('status');
    $approvalStatus = $request->input('approval_status');

    $query = Deliveries::with([
        'salesOrder.customer',
        'salesOrder.items.item',
        'items.item'
    ])
    ->withSum('items as quantity', 'quantity')
    ->withSum('items as total_amount', 'total_amount');

    if ($deliveryDateFrom) {
        $query->whereDate('request_delivery_date', '>=', $deliveryDateFrom);
    }

    if ($deliveryDateTo) {
        $query->whereDate('request_delivery_date', '<=', $deliveryDateTo);
    }

    if ($status) {
        $query->where('status', $status);
    }

    if ($approvalStatus) {
        $query->where('approval_status', $approvalStatus);
    }

    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('dr_no', 'like', '%' . $search . '%')
            ->orWhere('customer_code', 'like', '%' . $search . '%')
            ->orWhere('customer_name', 'like', '%' . $search . '%')
            ->orWhereHas('salesOrder', function($sq) use ($search) {
                $sq->where('customer_name', 'like', '%' . $search . '%');
            })
            ->orWhereHas('salesOrder.customer', function($cq) use ($search) {
                $cq->where('customer_name', 'like', '%' . $search . '%');
            });
        });
    }

    $deliveries = $query->orderBy('request_delivery_date', 'desc')->get();

    // ✅ ADD THESE ALIAS VARIABLES FOR THE VIEW
    $dateFrom = $deliveryDateFrom;
    $dateTo = $deliveryDateTo;

    return view('deliveries.printlist', compact(
        'deliveries', 
        'deliveryDateFrom', 
        'deliveryDateTo',
        'dateFrom',      
        'dateTo',        
        'status', 
        'approvalStatus'
    ));
}

    // 🖨️ Print single delivery
    public function print($id)
    {
        // ✅ Load delivery with items AND sales order with items for comparison
        $delivery = Deliveries::with([
            'salesOrder.items.item',  
            'items.item'              
        ])->findOrFail($id);
        
        return view('deliveries.print', compact('delivery'));
    }

    // 👁️ Show single delivery
    public function show($id)
    {
        $delivery = Deliveries::with(['items','salesOrder'])->findOrFail($id);
        return view('deliveries.show', compact('delivery'));
    }

    public function exportExcel(Request $request)
{
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    try {
        // ✅ REMOVED: date_from and date_to (created date)
        // ✅ NEW: Only delivery date filters
        $deliveryDateFrom = $request->input('delivery_date_from');
        $deliveryDateTo = $request->input('delivery_date_to');
        $search = $request->input('search');
        $status = $request->input('status');
        $approvalStatus = $request->input('approval_status');

        Log::info('Export deliveries started', [
            'delivery_date_from' => $deliveryDateFrom,
            'delivery_date_to' => $deliveryDateTo,
            'search' => $search,
            'status' => $status,
            'approval_status' => $approvalStatus
        ]);

        $query = Deliveries::with(['items', 'salesOrder.customer', 'salesOrder.items']);

        // ✅ REMOVED: created_at filters
        // ✅ NEW: Only filter by request_delivery_date
        if ($deliveryDateFrom) {
            $query->whereDate('request_delivery_date', '>=', $deliveryDateFrom);
        }

        if ($deliveryDateTo) {
            $query->whereDate('request_delivery_date', '<=', $deliveryDateTo);
        }

        // Status filters
        if ($status) {
            $query->where('status', $status);
        }

        if ($approvalStatus) {
            $query->where('approval_status', $approvalStatus);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('dr_no', 'like', '%' . $search . '%')
                ->orWhere('customer_code', 'like', '%' . $search . '%')
                ->orWhere('customer_name', 'like', '%' . $search . '%')
                ->orWhereHas('salesOrder', function ($sq) use ($search) {
                    $sq->where('customer_name', 'like', '%' . $search . '%');
                })
                ->orWhereHas('salesOrder.customer', function ($cq) use ($search) {
                    $cq->where('customer_name', 'like', '%' . $search . '%');
                });
            });
        }

        // ✅ Sort by delivery date instead of created_at
        $deliveries = $query->orderBy('request_delivery_date', 'desc')->get();

        Log::info('Deliveries found', ['count' => $deliveries->count()]);

        $filename = 'deliveries_items_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
            'Pragma' => 'public',
        ];

        $callback = function () use ($deliveries) {
            try {
                $file = fopen('php://output', 'w');

                if ($file === false) {
                    Log::error('Failed to open output stream');
                    return;
                }

                // UTF-8 BOM
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

                // Column headers
                fputcsv($file, [
                    'Sales Rep',
                    'PO No.',
                    'Sales Order',
                    'Customer',
                    'Branch',
                    'Item Code',
                    'Item Category',
                    'Brand',
                    'Item',
                    'SO Quantity',
                    'Price',
                    'UOM',
                    'Delivery Date',
                    'Plate No.',
                    'Status',
                    'DR No.',
                    'SI No.',
                    'DR Weight',
                    'Total Amount'
                ]);

                $overallGrandTotal = 0;

                foreach ($deliveries as $delivery) {
                    // Prepare delivery date
                    $deliveryDate = '—';
                    try {
                        if ($delivery->request_delivery_date) {
                            $deliveryDate = \Carbon\Carbon::parse($delivery->request_delivery_date)->format('m/d/Y');
                        } elseif ($delivery->salesOrder?->request_delivery_date) {
                            $deliveryDate = \Carbon\Carbon::parse($delivery->salesOrder->request_delivery_date)->format('m/d/Y');
                        }
                    } catch (\Exception $e) {
                        Log::warning('Date parsing failed', ['error' => $e->getMessage()]);
                    }

                    // Map SO items by item_code for comparison
                    $soItemsMap = collect();
                    if ($delivery->salesOrder && $delivery->salesOrder->items) {
                        $soItemsMap = $delivery->salesOrder->items->keyBy('item_code');
                    }

                    $deliveryTotal = 0;

                    if ($delivery->items && $delivery->items->count() > 0) {
                        foreach ($delivery->items as $item) {
                            $soItem = $soItemsMap->get($item->item_code);
                            $soQty = $soItem?->quantity ?? $item->original_quantity ?? 0;
                            $drQty = $item->quantity ?? 0;

                            fputcsv($file, [
                                $delivery->sales_rep ?? $delivery->salesOrder?->sales_rep ?? '—',
                                $delivery->po_number ?? $delivery->salesOrder?->po_number ?? '—',
                                $delivery->sales_order_number ?? '—',
                                $delivery->customer_name ?? $delivery->salesOrder?->customer?->customer_name ?? '—',
                                $delivery->branch ?? $delivery->salesOrder?->branch ?? '—',
                                $item->item_code ?? '—',
                                $item->item_category ?? '—',
                                $item->brand ?? '—',
                                $item->item_description ?? '—',
                                number_format($soQty, 2),
                                number_format($item->unit_price ?? 0, 2),
                                $item->uom ?? '—',
                                $deliveryDate,
                                $delivery->plate_no ?? '—',
                                $delivery->status ?? '—',
                                $delivery->dr_no ?? '—',
                                $delivery->sales_invoice_no ?? '—',
                                number_format($drQty, 2),
                                number_format($item->total_amount ?? 0, 2),
                            ]);

                            $deliveryTotal += $item->total_amount ?? 0;
                        }

                        $overallGrandTotal += $deliveryTotal;

                        // Add subtotal row
                        $subtotalRow = array_fill(0, 18, '');
                        $subtotalRow[17] = 'SUBTOTAL:';
                        $subtotalRow[18] = number_format($deliveryTotal, 2);
                        fputcsv($file, $subtotalRow);

                        // Blank row
                        fputcsv($file, []);
                    } else {
                        // No items
                        fputcsv($file, [
                            $delivery->sales_rep ?? $delivery->salesOrder?->sales_rep ?? '—',
                            $delivery->po_number ?? $delivery->salesOrder?->po_number ?? '—',
                            $delivery->sales_order_number ?? '—',
                            $delivery->customer_name ?? $delivery->salesOrder?->customer?->customer_name ?? '—',
                            $delivery->branch ?? $delivery->salesOrder?->branch ?? '—',
                            '—', '—', '—', '—', '—', '—', '—',
                            $deliveryDate,
                            $delivery->plate_no ?? '—',
                            $delivery->status ?? '—',
                            $delivery->dr_no ?? '—',
                            $delivery->sales_invoice_no ?? '—',
                            '—', '—'
                        ]);
                        fputcsv($file, []);
                    }
                }

                // Grand total
                fputcsv($file, []);
                $grandTotalRow = array_fill(0, 18, '');
                $grandTotalRow[17] = '>>> GRAND TOTAL <<<';
                $grandTotalRow[18] = number_format($overallGrandTotal, 2);
                fputcsv($file, $grandTotalRow);

                fclose($file);
                
            } catch (\Exception $e) {
                Log::error('Error in export callback', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
                throw $e;
            }
        };

        return response()->stream($callback, 200, $headers);

    } catch (\Exception $e) {
        Log::error('Export deliveries failed', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
        
        abort(500, 'Failed to export: ' . $e->getMessage());
    }
}

    // Export Single Delivery Excel - NEW FORMAT
    public function exportDeliveryItemsExcel(Request $request)
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        try {
            $deliveryId = $request->query('delivery_id');

            Log::info('Export delivery items started', ['delivery_id' => $deliveryId]);

            if (!$deliveryId) {
                Log::error('No delivery ID provided');
                abort(400, 'Delivery ID is required');
            }

            $delivery = Deliveries::with(['items', 'salesOrder.customer', 'salesOrder.items'])
                ->find($deliveryId);

            if (!$delivery) {
                Log::error('Delivery not found', ['delivery_id' => $deliveryId]);
                abort(404, 'Delivery not found');
            }

            Log::info('Delivery found', [
                'delivery_id' => $deliveryId,
                'items_count' => $delivery->items->count(),
                'has_sales_order' => $delivery->salesOrder ? 'yes' : 'no'
            ]);

            $filename = 'delivery_' . ($delivery->dr_no ?? 'export') . '_' . now()->format('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
                'Pragma' => 'public',
            ];

            $callback = function () use ($delivery) {
                try {
                    $file = fopen('php://output', 'w');

                    if ($file === false) {
                        Log::error('Failed to open output stream');
                        return;
                    }

                    // UTF-8 BOM
                    fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

                    // NEW COLUMN ORDER
                    fputcsv($file, [
                        'Sales Rep',
                        'PO No.',
                        'Sales Order',
                        'Customer',
                        'Branch',
                        'Item Code',
                        'Item Category',
                        'Brand',
                        'Item',
                        'SO Quantity',
                        'Price',
                        'UOM',
                        'Delivery Date',
                        'Plate No.',
                        'Status',
                        'DR No.',
                        'SI No.',
                        'DR Weight',
                        'Total Amount'
                    ]);

                    // Prepare delivery date
                    $deliveryDate = '—';
                    try {
                        if ($delivery->request_delivery_date) {
                            $deliveryDate = \Carbon\Carbon::parse($delivery->request_delivery_date)->format('m/d/Y');
                        } elseif ($delivery->salesOrder?->request_delivery_date) {
                            $deliveryDate = \Carbon\Carbon::parse($delivery->salesOrder->request_delivery_date)->format('m/d/Y');
                        }
                    } catch (\Exception $e) {
                        Log::warning('Date parsing failed', ['error' => $e->getMessage()]);
                    }

                    // Map SO items
                    $soItemsMap = collect();
                    if ($delivery->salesOrder && $delivery->salesOrder->items) {
                        $soItemsMap = $delivery->salesOrder->items->keyBy('item_code');
                    }

                    $grandTotal = 0;

                    if ($delivery->items && $delivery->items->count() > 0) {
                        foreach ($delivery->items as $item) {
                            $soItem = $soItemsMap->get($item->item_code);
                            $soQty = $soItem?->quantity ?? $item->original_quantity ?? 0;
                            $drQty = $item->quantity ?? 0;

                            // NEW ROW ORDER
                            fputcsv($file, [
                                $delivery->sales_rep ?? $delivery->salesOrder?->sales_rep ?? '—',
                                $delivery->po_number ?? $delivery->salesOrder?->po_number ?? '—',
                                $delivery->sales_order_number ?? '—',
                                $delivery->customer_name ?? $delivery->salesOrder?->customer?->customer_name ?? '—',
                                $delivery->branch ?? $delivery->salesOrder?->branch ?? '—',
                                $item->item_code ?? '—',
                                $item->item_category ?? '—',
                                $item->brand ?? '—',
                                $item->item_description ?? '—',
                                number_format($soQty, 2),
                                number_format($item->unit_price ?? 0, 2),
                                $item->uom ?? '—',
                                $deliveryDate,
                                $delivery->plate_no ?? '—',
                                $delivery->status ?? '—',
                                $delivery->dr_no ?? '—',
                                $delivery->sales_invoice_no ?? '—',
                                number_format($drQty, 2), // DR Weight
                                number_format($item->total_amount ?? 0, 2),
                            ]);

                            $grandTotal += $item->total_amount ?? 0;
                        }

                        // Grand total
                        $emptyColumns = array_fill(0, 18, '');
                        $emptyColumns[17] = 'GRAND TOTAL:';
                        $emptyColumns[18] = number_format($grandTotal, 2);
                        fputcsv($file, $emptyColumns);
                    } else {
                        // No items
                        fputcsv($file, [
                            $delivery->sales_rep ?? $delivery->salesOrder?->sales_rep ?? '—',
                            $delivery->po_number ?? $delivery->salesOrder?->po_number ?? '—',
                            $delivery->sales_order_number ?? '—',
                            $delivery->customer_name ?? $delivery->salesOrder?->customer?->customer_name ?? '—',
                            $delivery->branch ?? $delivery->salesOrder?->branch ?? '—',
                            '—', '—', '—', '—', '—', '—', '—',
                            $deliveryDate,
                            $delivery->plate_no ?? '—',
                            $delivery->status ?? '—',
                            $delivery->dr_no ?? '—',
                            $delivery->sales_invoice_no ?? '—',
                            '—', '—'
                        ]);
                    }

                    fclose($file);
                    
                } catch (\Exception $e) {
                    Log::error('Error in export callback', [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ]);
                    throw $e;
                }
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Export delivery items failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'delivery_id' => $deliveryId ?? null
            ]);
            
            abort(500, 'Failed to export: ' . $e->getMessage());
        }
    }

    // Add these methods to your DeliveriesController.php

/**
 * ✅ Batch Approve Multiple Deliveries
 */
public function batchApprove(Request $request)
{
    try {
        if (!\App\Helpers\RoleHelper::canApproveDeliveries()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to approve deliveries'], 403);
        }

        $validated = $request->validate([
            'delivery_ids' => 'required|array|min:1',
            'delivery_ids.*' => 'required|integer|exists:deliveries,id',
        ]);

        $deliveryIds = $validated['delivery_ids'];
        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($deliveryIds as $id) {
            try {
                $delivery = Deliveries::with('items')->findOrFail($id);

                // Check if delivery is pending
                if ($delivery->approval_status !== 'Pending') {
                    $failedCount++;
                    $errors[] = "DR {$delivery->dr_no} is not pending approval";
                    continue;
                }

                // Check if pulled out
                if ($delivery->is_pulled_out) {
                    $failedCount++;
                    $errors[] = "DR {$delivery->dr_no} is pulled out";
                    continue;
                }

                // Approve delivery
                $delivery->update([
                    'approval_status' => 'Approved',
                    'status' => 'Delivered',
                    'approved_by_user' => auth()->user()->name,
                    'approved_at' => now(),
                ]);

                // Check if SO should be closed
                $salesOrder = SalesOrder::where('sales_order_number', $delivery->sales_order_number)->first();
                if ($salesOrder) {
                    $salesOrder->fresh()->checkAndClose();
                }

                // Create activity log
                Activity::create([
                    'user_name' => auth()->user()->name ?? 'System',
                    'action' => 'Batch Approved',
                    'item' => $delivery->dr_no . ' - ' . ($delivery->customer_name ?? 'N/A'),
                    'target' => $delivery->sales_order_number ?? 'N/A',
                    'type' => 'Delivery',
                    'message' => "Batch approved delivery: {$delivery->dr_no}",
                ]);

                $successCount++;
            } catch (\Exception $e) {
                $failedCount++;
                $errors[] = "Failed to approve delivery ID {$id}: " . $e->getMessage();
                Log::error('Batch approve failed for delivery', ['id' => $id, 'error' => $e->getMessage()]);
            }
        }

        $message = "Successfully approved {$successCount} delivery(ies).";
        if ($failedCount > 0) {
            $message .= " {$failedCount} failed.";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'errors' => $errors,
        ]);
    } catch (\Exception $e) {
        Log::error('Batch approval failed', ['error' => $e->getMessage()]);
        return response()->json(['success' => false, 'message' => 'Batch approval failed: ' . $e->getMessage()], 500);
    }
}

/**
 * ✅ Batch Reject Multiple Deliveries
 */
public function batchReject(Request $request)
{
    try {
        if (!\App\Helpers\RoleHelper::canApproveDeliveries()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to reject deliveries'], 403);
        }

        $validated = $request->validate([
            'delivery_ids' => 'required|array|min:1',
            'delivery_ids.*' => 'required|integer|exists:deliveries,id',
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $deliveryIds = $validated['delivery_ids'];
        $rejectionReason = $validated['rejection_reason'];
        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($deliveryIds as $id) {
            try {
                $delivery = Deliveries::findOrFail($id);

                // Check if delivery is pending
                if ($delivery->approval_status !== 'Pending') {
                    $failedCount++;
                    $errors[] = "DR {$delivery->dr_no} is not pending approval";
                    continue;
                }

                // Check if pulled out
                if ($delivery->is_pulled_out) {
                    $failedCount++;
                    $errors[] = "DR {$delivery->dr_no} is pulled out";
                    continue;
                }

                // Reject delivery
                $delivery->update([
                    'approval_status' => 'Rejected',
                    'status' => 'Cancelled',
                    'approved_by_user' => auth()->user()->name,
                    'rejection_reason' => $rejectionReason,
                ]);

                // Create activity log
                Activity::create([
                    'user_name' => auth()->user()->name ?? 'System',
                    'action' => 'Batch Rejected',
                    'item' => $delivery->dr_no . ' - ' . ($delivery->customer_name ?? 'N/A'),
                    'target' => $delivery->sales_order_number ?? 'N/A',
                    'type' => 'Delivery',
                    'message' => "Batch rejected delivery: {$delivery->dr_no}. Reason: {$rejectionReason}",
                ]);

                $successCount++;
            } catch (\Exception $e) {
                $failedCount++;
                $errors[] = "Failed to reject delivery ID {$id}: " . $e->getMessage();
                Log::error('Batch reject failed for delivery', ['id' => $id, 'error' => $e->getMessage()]);
            }
        }

        $message = "Successfully rejected {$successCount} delivery(ies).";
        if ($failedCount > 0) {
            $message .= " {$failedCount} failed.";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'errors' => $errors,
        ]);
    } catch (\Exception $e) {
        Log::error('Batch rejection failed', ['error' => $e->getMessage()]);
        return response()->json(['success' => false, 'message' => 'Batch rejection failed: ' . $e->getMessage()], 500);
    }
}

                                                             
}