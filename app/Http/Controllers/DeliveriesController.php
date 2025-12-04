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

class DeliveriesController extends Controller
{
        public function index(Request $request)
    {
        $query = Deliveries::with(['salesOrder.customer'])
            ->withSum('items as quantity', 'quantity')
            ->withSum('items as total_amount', 'total_amount')
            ->orderByDesc('created_at');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('dr_no', 'like', '%' . $request->search . '%')
                ->orWhere('sales_order_number', 'like', '%' . $request->search . '%')  // ✅ ADDED
                ->orWhere('customer_code', 'like', '%' . $request->search . '%')
                ->orWhere('customer_name', 'like', '%' . $request->search . '%')
                ->orWhere('plate_no', 'like', '%' . $request->search . '%')  // ✅ BONUS: search by plate
                ->orWhereHas('salesOrder', function ($sq) use ($request) {
                    $sq->where('customer_name', 'like', '%' . $request->search . '%')
                        ->orWhere('sales_order_number', 'like', '%' . $request->search . '%');  // ✅ BONUS
                })
                ->orWhereHas('salesOrder.customer', function ($cq) use ($request) {
                    $cq->where('customer_name', 'like', '%' . $request->search . '%')
                        ->orWhere('customer_code', 'like', '%' . $request->search . '%');  // ✅ BONUS
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
   // Replace your update() method in DeliveriesController.php with this:

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

        // Compute previous delivered sums for the SO, EXCLUDING this delivery
        $deliveredSums = DeliveryItem::whereHas('delivery', function($q) use ($validated, $delivery) {
                $q->where('sales_order_number', $validated['sales_order_number'])
                  ->where('status', 'Delivered');
            })
            ->where('delivery_id', '!=', $delivery->id)
            ->select('item_code', DB::raw('SUM(quantity) as total_delivered'))
            ->groupBy('item_code')
            ->get()
            ->keyBy('item_code');

        // Delete existing delivery items for a clean replace
        DeliveryItem::where('delivery_id', $delivery->id)->delete();

        // Create updated delivery items
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
                'quantity' => $deliveredQty,
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

    if ($soExists->status !== 'Approved') {
        return response()->json(['error' => "Sales Order {$soNumber} exists but has not been approved yet (Status: {$soExists->status}). Only approved sales orders can be delivered."], 403);
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

   // ✅ Check existing deliveries for THIS SPECIFIC SO ONLY (exclude Pending AND auto-created ones)
    $existingDeliveries = Deliveries::where('sales_order_number', $soNumber)
        ->where('status', '!=', 'Pending') // ✅ Ignore auto-created pending deliveries
        ->whereNotNull('dr_no') // ✅ ADDED: Ensure it has a real DR number (not auto-generated temp)
        ->whereHas('items') // ✅ ADDED: Only count deliveries that have actual items
        ->orderBy('created_at', 'asc')
        ->get();

    $deliveryCount = $existingDeliveries->count();
    $hasPartialDelivery = $existingDeliveries->where('status', 'Partial')->count() > 0;

    \Log::info('🔍 Delivery check', [
        'so_number' => $soNumber,
        'delivery_count' => $deliveryCount,
        'has_partial' => $hasPartialDelivery,
        'existing_deliveries' => $existingDeliveries->pluck('dr_no', 'status')->toArray()
    ]);

    // ✅ Check if we're editing an existing delivery
    $delivery = null;
    $isEditMode = false;
    
    if ($deliveryBatch && $deliveryBatch !== 'new') {
        // User selected existing batch to edit
        $delivery = Deliveries::where('sales_order_number', $soNumber)
            ->where('delivery_batch', $deliveryBatch)
            ->with('items')
            ->first();
        
        $isEditMode = $delivery ? true : false;
    }

    // ✅ Calculate delivered quantities per item (exclude current editing delivery)
    $deliveredQuery = DeliveryItem::whereHas('delivery', function($q) use ($soNumber) {
            $q->where('sales_order_number', $soNumber);
        });

    if ($delivery && $isEditMode) {
        $deliveredQuery->where('delivery_id', '!=', $delivery->id);
    }

    $deliveredSums = $deliveredQuery
        ->select('item_code', DB::raw('SUM(quantity) as total_delivered'))
        ->groupBy('item_code')
        ->get()
        ->keyBy('item_code');

    // ✅ NEW: Determine batch name for new delivery (per SO)
    $newBatchName = null;
    if (!$isEditMode) {
        if ($deliveryCount === 0) {
            // First delivery - batch name pending until we know if it's full or partial
            $newBatchName = 'Pending';
        } else {
            // Subsequent delivery - it's a continuation batch
            $newBatchName = 'Batch ' . ($deliveryCount + 1);
        }
    }

   $items = [];
$allItemsFullyDelivered = true;

foreach ($soItems as $soItem) {
    $originalQty = $soItem->quantity ?? 0;
    $alreadyDelivered = $deliveredSums->get($soItem->item_code)?->total_delivered ?? 0;
    $remainingAvailable = $originalQty - $alreadyDelivered;

    // ✅ For edit mode: show the item's current delivery quantities
    if ($isEditMode && $delivery) {
        $existingDeliveryItem = $delivery->items->firstWhere('item_code', $soItem->item_code);
        if ($existingDeliveryItem) {
            $deliveredQty = $existingDeliveryItem->quantity ?? 0;
            $notes = $existingDeliveryItem->notes ?? $soItem->note ?? null; // ✅ FIXED: Fallback to SO note
            
            $items[] = [
                'item_code' => $soItem->item_code,
                'item_description' => $soItem->item_description ?? $soItem->item->item_description ?? '',
                'brand' => $soItem->brand ?? $soItem->item?->brand ?? '',
                'item_category' => $soItem->item_category ?? $soItem->item?->item_category ?? '',
                'quantity' => $deliveredQty,
                'original_quantity' => $originalQty,
                'remaining_quantity' => $remainingAvailable,
                'uom' => $soItem->unit ?? 'Kgs',
                'unit_price' => $soItem->unit_price ?? 0,
                'total_amount' => ($deliveredQty * ($soItem->unit_price ?? 0)),
                'notes' => $notes,
            ];
        }
    } else {
        // ✅ NEW DELIVERY: Only show items that still have remaining quantity
        if ($remainingAvailable > 0) {
            $allItemsFullyDelivered = false;
            
            $items[] = [
                'item_code' => $soItem->item_code,
                'item_description' => $soItem->item_description ?? $soItem->item->item_description ?? '',
                'brand' => $soItem->brand ?? $soItem->item?->brand ?? '',
                'item_category' => $soItem->item_category ?? $soItem->item?->item_category ?? '',
                'quantity' => $remainingAvailable,
                'original_quantity' => $originalQty,
                'remaining_quantity' => 0,
                'uom' => $soItem->unit ?? 'Kgs',
                'unit_price' => $soItem->unit_price ?? 0,
                'total_amount' => ($remainingAvailable * ($soItem->unit_price ?? 0)),
                'notes' => $soItem->note ?? null, // ✅ FIXED: Get note from SO item
            ];
        }
    }
}

// ✅ Check if all items are fully delivered
if (!$isEditMode && $allItemsFullyDelivered) {
    return response()->json([
        'error' => 'All items in this Sales Order have been fully delivered. No remaining items to deliver.'
    ], 400);
}
    

    // ✅ Check if all items are fully delivered
    if (!$isEditMode && $allItemsFullyDelivered) {
        return response()->json([
            'error' => 'All items in this Sales Order have been fully delivered. No remaining items to deliver.'
        ], 400);
    }

    // ✅ If no items remaining for new delivery
    if (!$isEditMode && empty($items)) {
        return response()->json([
            'error' => 'No items with remaining quantities found for delivery.'
        ], 400);
    }

    // Build response
    $attachmentUrl = null;
    $attachmentName = null;
    if ($delivery && $delivery->attachment) {
        $attachmentUrl = asset('delivery_images/' . $delivery->attachment);
        $attachmentName = $delivery->attachment;
    }

    // ✅ FIXED: Info message ONLY shows for 2nd+ batch when previous delivery was partial
    $infoMessage = null;
    $showPartialAlert = false;
    
    // Only show the partial delivery history alert if ALL these conditions are met:
    // 1. NOT in edit mode
    // 2. There's at least 1 previous delivery (deliveryCount >= 1) 
    // 3. At least one of those previous deliveries had status "Partial"
    // 4. Currently creating a 2nd+ batch (which means deliveryCount >= 1)
    if (!$isEditMode && $deliveryCount >= 1 && $hasPartialDelivery) {
        // Show alert when creating 2nd+ batch ONLY if there was a previous partial delivery
        $showPartialAlert = true;
        $fullyDeliveredCount = $soItems->count() - count($items);
        $infoMessage = "This SO has {$deliveryCount} previous partial delivery(ies). ";
        if ($fullyDeliveredCount > 0) {
            $infoMessage .= "{$fullyDeliveredCount} item(s) already fully delivered. ";
        }
        $infoMessage .= "Showing " . count($items) . " item(s) with remaining quantities.";
    }

    return response()->json([
        'success' => true,
        'id' => $isEditMode && $delivery ? $delivery->id : null,
        'is_edit_mode' => $isEditMode,
        'has_partial_delivery' => $hasPartialDelivery,
        'show_partial_alert' => $showPartialAlert, // ✅ NEW FLAG
        'info_message' => $infoMessage,
        'sales_order_number' => $soExists->sales_order_number,
        'delivery_batch' => $isEditMode ? $delivery->delivery_batch : $newBatchName,
        'customer_code' => $soExists->customer->customer_code ?? '',
        'customer_name' => $soExists->customer->customer_name ?? '',
        'tin_no' => $soExists->customer->tin_no ?? '',
        'branch' => $soExists->branch ?? '',
        'sales_representative' => $soExists->sales_rep ?? '',
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
            'status' => 'required|string|in:Delivered,Cancelled',
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

        // Count existing deliveries (exclude Pending)
        $existingDeliveryCount = Deliveries::where('sales_order_number', $validated['sales_order_number'])
            ->where('status', '!=', 'Pending')
            ->whereHas('items')
            ->count();
        
        // Determine batch name
        if ($existingDeliveryCount === 0) {
            $validated['delivery_batch'] = ($validated['delivery_type'] === 'Partial') ? 'Batch 1' : 'Full Delivery';
        } else {
            $validated['delivery_batch'] = 'Batch ' . ($existingDeliveryCount + 1);
        }

        Log::info('📦 Creating delivery', [
            'so_number' => $validated['sales_order_number'],
            'batch_name' => $validated['delivery_batch'],
            'delivery_type' => $validated['delivery_type'],
            'status' => $validated['status'],
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

        // Get delivered sums
        $deliveredSums = DeliveryItem::whereHas('delivery', function($q) use ($validated) {
                $q->where('sales_order_number', $validated['sales_order_number'])
                  ->where('status', 'Delivered');
            })
            ->select('item_code', DB::raw('SUM(quantity) as total_delivered'))
            ->groupBy('item_code')
            ->get()
            ->keyBy('item_code');

        // Create delivery
        $delivery = Deliveries::create($validated);

        // Create delivery items
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
                'quantity' => $deliveredQty,
                'original_quantity' => $originalQty,
                'remaining_quantity' => $remainingQty,
                'uom' => $item['uom'] ?? null,
                'unit_price' => $item['unit_price'] ?? 0,
                'total_amount' => $item['total_amount'] ?? 0,
                'delivery_batch' => $validated['delivery_batch'],
                'notes' => $item['notes'] ?? $soItem?->note ?? null,      
            ]);
        }

        // ✅ Check if SO should be closed (AFTER all items are created)
        $salesOrder->fresh()->checkAndClose();

        // Create activity log
        Activity::create([
            'user_name' => auth()->user()->name ?? 'System',
            'action' => 'Created',
            'item' => $delivery->dr_no . ' - ' . ($delivery->customer_name ?? 'N/A'),
            'target' => $delivery->sales_order_number ?? 'N/A',
            'type' => 'Delivery',
            'message' => "Created delivery: {$delivery->dr_no} ({$delivery->delivery_batch}) - Type: {$delivery->delivery_type}",
        ]);

        return response()->json([
            'success' => true,
            'message' => "Delivery created successfully! Type: {$delivery->delivery_type}, Batch: {$delivery->delivery_batch}",
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

    // Print filtered list
    public function printList(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $search = $request->input('search');

        // ✅ Eager load all necessary relationships
        $query = Deliveries::with([
            'salesOrder.customer',
            'salesOrder.items.item',  // ✅ Load sales order items with item details
            'items.item'  // ✅ Load delivery items with item details
        ]);

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
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

        $deliveries = $query->orderByDesc('created_at')->get();

        return view('deliveries.printlist', compact('deliveries', 'dateFrom', 'dateTo'));
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

    // Export Excel
    public function exportExcel(Request $request)
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        try {
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
            $search = $request->input('search');

            Log::info('Export deliveries started', [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'search' => $search
            ]);

            // ✅ Build query with filters
            $query = Deliveries::with(['items', 'salesOrder.customer', 'salesOrder.items']);

            if ($dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
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

            $deliveries = $query->orderByDesc('created_at')->get();

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

                    // ✅ UTF-8 BOM
                    fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

                    // Column headers
                    fputcsv($file, [
                        'DR No',
                        'Sales Order No',
                        'Customer Code',
                        'Customer Name',
                        'TIN',
                        'Branch',
                        'Sales Representative',
                        'Sales Executive',
                        'Plate No',
                        'Sales Invoice No',
                        'PO Number',
                        'Request Delivery Date',
                        'Status',
                        'Approved By',
                        'Additional Instructions',
                        'Date Created',
                        'Item Code',
                        'Item Category',
                        'Brand',
                        'Item Description',
                        'UOM',
                        'SO Qty',
                        'DR Qty',
                        'Variance',
                        'Unit Price',
                        'Total Amount'
                    ]);

                    $overallGrandTotal = 0;

                    foreach ($deliveries as $delivery) {
                        // Prepare delivery-level info
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

                        $deliveryInfo = [
                            $delivery->dr_no ?? '—',
                            $delivery->sales_order_number ?? '—',
                            $delivery->customer_code ?? $delivery->salesOrder?->customer_code ?? '—',
                            $delivery->customer_name ?? $delivery->salesOrder?->customer?->customer_name ?? $delivery->salesOrder?->client_name ?? '—',
                            $delivery->salesOrder?->customer?->tin_no ?? $delivery->tin_no ?? '—',
                            $delivery->branch ?? $delivery->salesOrder?->branch ?? '—',
                            $delivery->sales_rep ?? $delivery->salesOrder?->sales_rep ?? '—',
                            $delivery->sales_executive ?? $delivery->salesOrder?->sales_executive ?? '—',
                            $delivery->plate_no ?? '—',
                            $delivery->sales_invoice_no ?? '—',
                            $delivery->po_number ?? $delivery->salesOrder?->po_number ?? '—',
                            $deliveryDate,
                            $delivery->status ?? '—',
                            $delivery->approved_by ?? $delivery->salesOrder?->approved_by ?? '—',
                            $delivery->additional_instructions ?? $delivery->salesOrder?->additional_instructions ?? '—',
                            $delivery->created_at ? $delivery->created_at->format('m/d/Y h:i A') : '—'
                        ];

                        // Map SO items by item_code for comparison
                        $soItemsMap = collect();
                        if ($delivery->salesOrder && $delivery->salesOrder->items) {
                            $soItemsMap = $delivery->salesOrder->items->keyBy('item_code');
                        }

                        $deliveryTotal = 0;

                        if ($delivery->items && $delivery->items->count() > 0) {
                            foreach ($delivery->items as $item) {
                                $soItem = $soItemsMap->get($item->item_code);
                                $soQty = $soItem?->quantity ?? 0;
                                $drQty = $item->quantity ?? 0;
                                $variance = $drQty - $soQty;

                                fputcsv($file, array_merge($deliveryInfo, [
                                    $item->item_code ?? '—',
                                    $item->item_category ?? '—',
                                    $item->brand ?? '—',
                                    $item->item_description ?? '—',
                                    $item->uom ?? '—',
                                    number_format($soQty, 2),
                                    number_format($drQty, 2),
                                    ($variance > 0 ? '+' : '') . number_format($variance, 2),
                                    number_format($item->unit_price ?? 0, 2),
                                    number_format($item->total_amount ?? 0, 2),
                                ]));

                                $deliveryTotal += $item->total_amount ?? 0;
                            }

                            $overallGrandTotal += $deliveryTotal;

                            // Add subtotal row for this delivery
                            $subtotalRow = array_fill(0, 24, '');
                            $subtotalRow[24] = 'SUBTOTAL:';
                            $subtotalRow[25] = number_format($deliveryTotal, 2);
                            fputcsv($file, $subtotalRow);

                            // Add blank row between deliveries
                            fputcsv($file, []);
                        } else {
                            // No items - just output delivery info with empty item columns
                            fputcsv($file, array_merge($deliveryInfo, array_fill(0, 10, '—')));
                            fputcsv($file, []);
                        }
                    }

                    // ✅ Add overall grand total at the end
                    fputcsv($file, []);
                    $grandTotalRow = array_fill(0, 24, '');
                    $grandTotalRow[24] = '>>> GRAND TOTAL <<<';
                    $grandTotalRow[25] = number_format($overallGrandTotal, 2);
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

    public function exportDeliveryItemsExcel(Request $request)
    {
        // Enable error display for debugging
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        try {
            $deliveryId = $request->query('delivery_id');

            // Log the request
            Log::info('Export delivery items started', ['delivery_id' => $deliveryId]);

            if (!$deliveryId) {
                Log::error('No delivery ID provided');
                abort(400, 'Delivery ID is required');
            }

            // ✅ Find delivery with related items and sales order
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

                    // ✅ UTF-8 BOM
                    fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

                    // Column headers
                    fputcsv($file, [
                        'DR No',
                        'Sales Order No',
                        'Customer Code',
                        'Customer Name',
                        'TIN',
                        'Branch',
                        'Sales Representative',
                        'Sales Executive',
                        'Plate No',
                        'Sales Invoice No',
                        'PO Number',
                        'Request Delivery Date',
                        'Status',
                        'Approved By',
                        'Additional Instructions',
                        'Date Created',
                        'Item Code',
                        'Item Category',
                        'Brand',
                        'Item Description',
                        'UOM',
                        'SO Qty',
                        'DR Qty',
                        'Variance',
                        'Unit Price',
                        'Total Amount'
                    ]);

                    // Prepare delivery-level info
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

                    $deliveryInfo = [
                        $delivery->dr_no ?? '—',
                        $delivery->sales_order_number ?? '—',
                        $delivery->customer_code ?? $delivery->salesOrder?->customer_code ?? '—',
                        $delivery->customer_name ?? $delivery->salesOrder?->customer?->customer_name ?? $delivery->salesOrder?->client_name ?? '—',
                        $delivery->salesOrder?->customer?->tin_no ?? $delivery->tin_no ?? '—',
                        $delivery->branch ?? $delivery->salesOrder?->branch ?? '—',
                        $delivery->sales_rep ?? $delivery->salesOrder?->sales_rep ?? '—',
                        $delivery->sales_executive ?? $delivery->salesOrder?->sales_executive ?? '—',
                        $delivery->plate_no ?? '—',
                        $delivery->sales_invoice_no ?? '—',
                        $delivery->po_number ?? $delivery->salesOrder?->po_number ?? '—',
                        $deliveryDate,
                        $delivery->status ?? '—',
                        $delivery->approved_by ?? $delivery->salesOrder?->approved_by ?? '—',
                        $delivery->additional_instructions ?? $delivery->salesOrder?->additional_instructions ?? '—',
                        $delivery->created_at ? $delivery->created_at->format('m/d/Y h:i A') : '—'
                    ];

                    // Map SO items by item_code for comparison
                    $soItemsMap = collect();
                    if ($delivery->salesOrder && $delivery->salesOrder->items) {
                        $soItemsMap = $delivery->salesOrder->items->keyBy('item_code');
                    }

                    $grandTotal = 0;

                    if ($delivery->items && $delivery->items->count() > 0) {
                        foreach ($delivery->items as $item) {
                            $soItem = $soItemsMap->get($item->item_code);
                            $soQty = $soItem?->quantity ?? 0;
                            $drQty = $item->quantity ?? 0;
                            $variance = $drQty - $soQty;

                            fputcsv($file, array_merge($deliveryInfo, [
                                $item->item_code ?? '—',
                                $item->item_category ?? '—',
                                $item->brand ?? '—',
                                $item->item_description ?? '—',
                                $item->uom ?? '—',
                                number_format($soQty, 2),
                                number_format($drQty, 2),
                                ($variance > 0 ? '+' : '') . number_format($variance, 2),
                                number_format($item->unit_price ?? 0, 2),
                                number_format($item->total_amount ?? 0, 2),
                            ]));

                            $grandTotal += $item->total_amount ?? 0;
                        }

                        // Add grand total row - Fixed column count
                        $emptyColumns = array_fill(0, 25, ''); // 16 delivery info + 9 item columns before total
                        $emptyColumns[24] = 'GRAND TOTAL:';
                        $emptyColumns[25] = number_format($grandTotal, 2);
                        fputcsv($file, $emptyColumns);
                    } else {
                        // No items - just output delivery info with empty item columns
                        fputcsv($file, array_merge($deliveryInfo, array_fill(0, 10, '—')));
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
            
            // Return a proper error response instead of JSON for easier debugging
            abort(500, 'Failed to export: ' . $e->getMessage());
        }
    }                                                              
}