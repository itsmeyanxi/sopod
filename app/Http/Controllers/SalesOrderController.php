<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Customer;
use App\Models\DeliveryItem;
use App\Models\Deliveries;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SalesOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesOrder::with(['customer', 'preparer', 'approver', 'deliveries'])
            ->orderByDesc('created_at');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $keyword = $request->search;

            $query->where(function ($q) use ($keyword) {
                $q->where('sales_order_number', 'LIKE', "%$keyword%")
                    ->orWhere('status', 'LIKE', "%$keyword%")
                    ->orWhere('po_number', 'LIKE', "%$keyword%")
                    ->orWhere('sales_rep', 'LIKE', "%$keyword%")
                    ->orWhereHas('customer', function ($c) use ($keyword) {
                        $c->where('customer_name', 'LIKE', "%$keyword%");
                    })
                    ->orWhereHas('preparer', function ($p) use ($keyword) {
                        $p->where('name', 'LIKE', "%$keyword%");
                    })
                    ->orWhereHas('items', function ($i) use ($keyword) {
                        $i->where('item_code', 'LIKE', "%$keyword%")
                          ->orWhere('item_description', 'LIKE', "%$keyword%")
                          ->orWhere('brand', 'LIKE', "%$keyword%")
                          ->orWhere('item_category', 'LIKE', "%$keyword%");
                    });
            });
        }

        $salesOrders = $query->get();

        return view('sales_orders.index', compact('salesOrders'));
    }

    public function create()
    {
        $customers = Customer::all();
        $items = Item::where('approval_status', 'approved')->get();
        $nextNumber = $this->generateNextSalesOrderNumber();

        return view('sales_orders.create', compact('customers', 'items', 'nextNumber'));
    }

    private function generateSalesOrderNumber()
    {
        $latestOrder = SalesOrder::orderBy('id', 'desc')->first();
        $nextNumber = $latestOrder ? intval(Str::after($latestOrder->sales_order_number, 'SO-')) + 1 : 1;
        return 'SO-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function store(Request $request)
    {
        \Log::info('Form Data Received:', $request->all());

        $request->validate([
            'customer_code' => 'required|exists:customers,customer_code',
            'request_delivery_date' => 'required|date',
            'po_reference_no' => 'nullable|string|max:255',
            'po_image' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'sales_rep' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'additional_instructions' => 'nullable|string',
        ], [
            'po_image.mimes' => 'PO proof must be a JPG, PNG, or PDF file.',
            'po_image.max' => 'PO proof file size must not exceed 4MB.',
        ]);

        // ✅ Additional validation: Either PO number or PO image is required
        if (!$request->po_reference_no && !$request->hasFile('po_image')) {
            return back()->withInput()->with('error', 'Please provide either a PO Number or upload proof of customer order.');
        }

        DB::beginTransaction();

        try {
            $customer = Customer::where('customer_code', $request->customer_code)->firstOrFail();
            $salesOrderNumber = $this->generateSalesOrderNumber();

            $items = $request->items;
            $totalAmount = collect($items)->sum(fn($i) => $i['quantity'] * $i['price']);
            $firstItem = $items[0] ?? [];

            // ✅ Handle PO Image Upload
            $poImageFilename = null;
            if ($request->hasFile('po_image')) {
                $file = $request->file('po_image');
                $poImageFilename = time() . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());
                
                // Create directory if it doesn't exist
                $uploadPath = public_path('po_images');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                
                // Move file to public/po_images
                $file->move($uploadPath, $poImageFilename);
                
                \Log::info('✅ PO Image uploaded', [
                    'filename' => $poImageFilename,
                    'path' => $uploadPath . '/' . $poImageFilename
                ]);
            }

            // ✅ CREATE SALES ORDER (with po_image)
            $salesOrder = SalesOrder::create([
                'sales_order_number' => $salesOrderNumber,
                'customer_id' => $customer->id,
                'prepared_by' => auth()->id(),
                'approved_by' => null,
                'request_delivery_date' => $request->request_delivery_date,
                'po_number' => $request->po_reference_no,
                'po_image' => $poImageFilename, // ✅ Save PO image filename
                'customer_name' => $request->customer_name ?? $customer->customer_name,
                'shipping_address' => $request->shipping_address,
                'sales_rep' => $request->sales_rep,
                'sales_executive' => $request->sales_executive ?? null,
                'branch' => $request->branch ?? null,
                'total_amount' => $totalAmount,
                'item_description' => $firstItem['item_description'] ?? null,
                'item_code' => $firstItem['item_code'] ?? null,
                'brand' => $firstItem['brand'] ?? null,
                'item_category' => $firstItem['item_category'] ?? null,
                'additional_instructions' => $request->additional_instructions,
                'status' => $request->status ?? 'Pending',
            ]);

            // ✅ CREATE SALES ORDER ITEMS
            foreach ($request->items as $index => $itemData) {
                $item = Item::find($itemData['item_id']);

                SalesOrderItem::create([
                    'sales_order_id' => $salesOrder->id,
                    'item_id' => $itemData['item_id'],
                    'item_code' => $itemData['item_code'] ?? $item->item_code ?? null,
                    'item_description' => $itemData['item_description'] ?? $item->item_description ?? null,
                    'brand' => $itemData['brand'] ?? $item->brand ?? null,
                    'item_category' => $itemData['item_category'] ?? $item->item_category ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'] ?? $item->unit ?? 'Kgs',
                    'unit_price' => $itemData['price'],
                    'total_amount' => $itemData['amount'] ?? ($itemData['quantity'] * $itemData['price']),
                    'note' => $itemData['note'] ?? null,
                ]);
            }

            // ✅ UPDATE CUSTOMER'S SHIPPING ADDRESS (if provided)
            if ($request->filled('shipping_address')) {
                $customer->update([
                    'shipping_address' => $request->shipping_address
                ]);
                
                \App\Models\Activity::create([
                    'user_name' => auth()->user()->name ?? 'System',
                    'action' => 'Updated',
                    'item' => $customer->customer_code . ' - ' . $customer->customer_name,
                    'target' => 'Shipping Address',
                    'type' => 'Customer',
                    'message' => 'Updated shipping address via Sales Order: ' . $salesOrderNumber,
                ]);
            }

            // ✅ LOG SALES ORDER CREATION
            \App\Models\Activity::create([
                'user_name' => auth()->user()->name ?? 'System',
                'action' => 'Created',
                'item' => $salesOrderNumber,
                'target' => $customer->customer_name ?? 'N/A',
                'type' => 'Sales Order',
                'message' => 'Created sales order: ' . $salesOrderNumber . ($poImageFilename ? ' (with PO proof uploaded)' : ''),
            ]);

            DB::commit();
            return redirect()->route('sales_orders.index')
                ->with('success', 'Sales order created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            // ✅ Delete uploaded file if transaction failed
            if (isset($poImageFilename) && file_exists(public_path('po_images/' . $poImageFilename))) {
                @unlink(public_path('po_images/' . $poImageFilename));
            }
            
            \Log::error('Sales Order Error:', ['message' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to save sales order: ' . $e->getMessage());
        }
    }

/**
 * ✅ Sync unit prices from SO items to related delivery items
 */
private function syncDeliveryPrices(SalesOrder $salesOrder)
{
    try {
        // Get all deliveries for this SO
        $deliveries = Deliveries::where('sales_order_number', $salesOrder->sales_order_number)->get();
        
        if ($deliveries->isEmpty()) {
            return; // No deliveries to update
        }

        // Create a map of SO items by item_code for quick lookup
        $soItemsMap = $salesOrder->items->keyBy('item_code');
        
        foreach ($deliveries as $delivery) {
            $deliveryUpdated = false;
            
            foreach ($delivery->items as $deliveryItem) {
                // Find matching SO item
                $soItem = $soItemsMap->get($deliveryItem->item_code);
                
                if ($soItem && $soItem->unit_price != $deliveryItem->unit_price) {
                    // Update unit price and recalculate total
                    $newTotalAmount = $deliveryItem->quantity * $soItem->unit_price;
                    
                    $deliveryItem->update([
                        'unit_price' => $soItem->unit_price,
                        'total_amount' => $newTotalAmount,
                    ]);
                    
                    $deliveryUpdated = true;
                    
                    \Log::info("✅ Updated delivery item price", [
                        'delivery_id' => $delivery->id,
                        'dr_no' => $delivery->dr_no,
                        'item_code' => $deliveryItem->item_code,
                        'old_price' => $deliveryItem->unit_price,
                        'new_price' => $soItem->unit_price,
                    ]);
                }
            }
            
            if ($deliveryUpdated) {
                \Log::info("✅ Delivery prices synced", [
                    'delivery_id' => $delivery->id,
                    'dr_no' => $delivery->dr_no,
                    'so_number' => $salesOrder->sales_order_number,
                ]);
            }
        }
        
    } catch (\Exception $e) {
        \Log::error('❌ Failed to sync delivery prices', [
            'so_number' => $salesOrder->sales_order_number,
            'error' => $e->getMessage(),
        ]);
        // Don't throw - let SO update succeed even if delivery sync fails
    }
}

    public function show($id)
    {
        $salesOrder = SalesOrder::with(['customer', 'items.item'])->findOrFail($id);
        return view('sales_orders.show', compact('salesOrder'));
    }

 public function edit($id)
    {
        $salesOrder = SalesOrder::with(['customer', 'items.item', 'deliveries'])->findOrFail($id);
        
        // ✅ Check if SO is closed OR fully delivered
        $isFullyDelivered = $salesOrder->is_closed;
        
        if ($salesOrder->deliveries) {
            $delivery = $salesOrder->deliveries;
            // Check if delivery is marked as Delivered (Full)
            if ($delivery->status === 'Delivered' || 
                ($delivery->delivery_type === 'Full' && $delivery->status === 'Delivered')) {
                $isFullyDelivered = true;
            }
        }
        
        if ($isFullyDelivered) {
            return redirect()->route('sales_orders.show', $id)
                ->with('error', 'This Sales Order has been fully delivered and can no longer be edited.');
        }
        
        // ✅ Check edit permissions based on role and approval status
        $user = auth()->user();
        $canEdit = false;
        $needsCCApproval = false;
        
        if ($user->canInitiateEdit()) {
            // CC_Approver, Admin, IT can always edit
            $canEdit = true;
        } elseif ($user->canEditAfterCCApproval()) {
            // CSR roles can only edit if CC has approved editing
            if ($salesOrder->isEditApprovedByCC()) {
                $canEdit = true;
            } else {
                $needsCCApproval = true;
            }
        }
        
        if (!$canEdit) {
            $message = $needsCCApproval 
                ? 'This Sales Order must be approved for editing by CC_Approver first.'
                : 'You do not have permission to edit this Sales Order.';
            
            return redirect()->route('sales_orders.show', $id)
                ->with('error', $message);
        }
        
        $customers = Customer::all();
        $items = Item::where('approval_status', 'approved')->get();

        // Fill in missing item data from master item
        foreach ($salesOrder->items as $orderItem) {
            if ($orderItem->item) {
                if (empty($orderItem->item_description)) {
                    $orderItem->item_description = $orderItem->item->item_description;
                }
                if (empty($orderItem->item_category)) {
                    $orderItem->item_category = $orderItem->item->item_category;
                }
                if (empty($orderItem->brand)) {
                    $orderItem->brand = $orderItem->item->brand;
                }
                if (empty($orderItem->item_code)) {
                    $orderItem->item_code = $orderItem->item->item_code;
                }
            }
        }

        return view('sales_orders.edit', compact('salesOrder', 'customers', 'items'));
    }

    public function update(Request $request, $id)
    {
        $salesOrder = SalesOrder::with('deliveries')->findOrFail($id);
        
        // ✅ Check if SO is closed OR fully delivered
        $isFullyDelivered = $salesOrder->is_closed;
        
        if ($salesOrder->deliveries) {
            $delivery = $salesOrder->deliveries;
            // Check if delivery is marked as Delivered (Full)
            if ($delivery->status === 'Delivered' || 
                ($delivery->delivery_type === 'Full' && $delivery->status === 'Delivered')) {
                $isFullyDelivered = true;
            }
        }
        
        if ($isFullyDelivered) {
            return redirect()->route('sales_orders.show', $id)
                ->with('error', 'This Sales Order has been fully delivered and can no longer be edited.');
        }
        
        // ✅ Verify edit permissions
        $user = auth()->user();
        if (!$user->canInitiateEdit() && !($user->canEditAfterCCApproval() && $salesOrder->isEditApprovedByCC())) {
            return redirect()->route('sales_orders.show', $id)
                ->with('error', 'You do not have permission to edit this Sales Order.');
        }

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'request_delivery_date' => 'required|date',
            'po_number' => 'nullable|string|max:255',
            'po_image' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ], [
            'po_image.mimes' => 'PO proof must be a JPG, PNG, or PDF file.',
            'po_image.max' => 'PO proof file size must not exceed 4MB.',
        ]);

        DB::beginTransaction();
        try {
            // ✅ Handle PO Image Upload
            $poImageFilename = $salesOrder->po_image; // Keep existing by default
            
            // Check if user wants to remove current PO image
            if ($request->has('remove_po_image') && $request->remove_po_image == '1') {
                if ($salesOrder->po_image && file_exists(public_path('po_images/' . $salesOrder->po_image))) {
                    @unlink(public_path('po_images/' . $salesOrder->po_image));
                }
                $poImageFilename = null;
            }
            
            // Upload new PO image if provided
            if ($request->hasFile('po_image')) {
                // Delete old file if exists
                if ($salesOrder->po_image && file_exists(public_path('po_images/' . $salesOrder->po_image))) {
                    @unlink(public_path('po_images/' . $salesOrder->po_image));
                }
                
                $file = $request->file('po_image');
                $poImageFilename = time() . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());
                
                // Create directory if it doesn't exist
                $uploadPath = public_path('po_images');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                
                // Move file to public/po_images
                $file->move($uploadPath, $poImageFilename);
                
                \Log::info('✅ PO Image uploaded during update', [
                    'so_number' => $salesOrder->sales_order_number,
                    'filename' => $poImageFilename
                ]);
            }

            // Update SO header
            $salesOrder->update([
                'customer_id' => $request->customer_id,
                'request_delivery_date' => $request->request_delivery_date,
                'po_number' => $request->po_number, // ✅ Now editable
                'po_image' => $poImageFilename, // ✅ Update PO image
                'sales_rep' => $request->sales_rep ?? $salesOrder->sales_rep,
                'shipping_address' => $request->shipping_address ?? $salesOrder->shipping_address,
                'additional_instructions' => $request->additional_instructions,
                'sales_executive' => optional(Customer::find($request->customer_id))->sales_executive ?? $salesOrder->sales_executive,
            ]);

            // Delete existing items and recreate
            $salesOrder->items()->delete();

            $newTotalAmount = 0;

            foreach ($request->items as $itemData) {
                $quantity = (float) ($itemData['quantity'] ?? 0);
                $unitPrice = (float) ($itemData['unit_price'] ?? 0);
                $itemTotal = $quantity * $unitPrice;

                $item = Item::find($itemData['item_id']);

                $salesOrder->items()->create([
                    'item_id' => $itemData['item_id'],
                    'item_code' => $itemData['item_code'] ?? $item->item_code ?? null,
                    'item_description' => $itemData['item_description'] ?? $item->item_description ?? null,
                    'brand' => $itemData['brand'] ?? $item->brand ?? null,
                    'item_category' => $itemData['item_category'] ?? $item->item_category ?? null,
                    'quantity' => $quantity,
                    'unit' => $itemData['unit'] ?? $item->unit ?? 'Kgs',
                    'unit_price' => $unitPrice,
                    'total_amount' => $itemTotal,
                    'note' => $itemData['note'] ?? null,
                ]);

                $newTotalAmount += $itemTotal;
            }

            // Update first item reference fields
            $firstItem = $request->items[0] ?? [];
            $firstItemModel = Item::find($firstItem['item_id'] ?? null);

            $salesOrder->item_description = $firstItem['item_description'] ?? $firstItemModel->item_description ?? null;
            $salesOrder->item_code = $firstItem['item_code'] ?? $firstItemModel->item_code ?? null;
            $salesOrder->brand = $firstItem['brand'] ?? $firstItemModel->brand ?? null;
            $salesOrder->item_category = $firstItem['item_category'] ?? $firstItemModel->item_category ?? null;
            $salesOrder->total_amount = $newTotalAmount;
            $salesOrder->save();

            // Sync prices to related deliveries
            $this->syncDeliveryPrices($salesOrder);

            // Log activity
            \App\Models\Activity::create([
                'user_name' => auth()->user()->name ?? 'System',
                'action' => 'Updated',
                'item' => $salesOrder->sales_order_number,
                'target' => optional($salesOrder->customer)->customer_name ?? 'N/A',
                'type' => 'Sales Order',
                'message' => 'Updated sales order: ' . $salesOrder->sales_order_number . 
                            ($request->hasFile('po_image') ? ' (PO proof updated)' : '') .
                            ($request->has('remove_po_image') ? ' (PO proof removed)' : ''),
            ]);

            DB::commit();
            return redirect()->route('sales_orders.show', $salesOrder->id)
                ->with('success', 'Sales Order updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            // ✅ Delete uploaded file if transaction failed
            if (isset($poImageFilename) && $poImageFilename !== $salesOrder->po_image && file_exists(public_path('po_images/' . $poImageFilename))) {
                @unlink(public_path('po_images/' . $poImageFilename));
            }
            
            \Log::error('SO Update Error:', ['message' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to update: ' . $e->getMessage());
        }
    }

    /**
     * ✅ NEW: CC Approver approves SO for editing by CSR roles
     */
    public function approveForEdit($id)
    {
        $salesOrder = SalesOrder::findOrFail($id);
        
        if (!auth()->user()->canInitiateEdit()) {
            return redirect()->back()->with('error', 'Only CC_Approver can approve Sales Orders for editing.');
        }
        
        if ($salesOrder->isEditApprovedByCC()) {
            return redirect()->back()->with('info', 'This Sales Order is already approved for editing.');
        }
        
        $salesOrder->markEditApprovedByCC();
        
        \App\Models\Activity::create([
            'user_name' => auth()->user()->name ?? 'System',
            'action' => 'Approved for Edit',
            'item' => $salesOrder->sales_order_number,
            'target' => optional($salesOrder->customer)->customer_name ?? 'N/A',
            'type' => 'Sales Order',
            'message' => 'Approved Sales Order for editing by CSR roles',
        ]);
        
        return redirect()->back()->with('success', 'Sales Order approved for editing by CSR team.');
    }
    
    public function destroy($id)
    {
        try {
            $salesOrder = SalesOrder::findOrFail($id);

            $soNumber = $salesOrder->sales_order_number;
            $customer = optional($salesOrder->customer)->customer_name ?? 'N/A';

            $salesOrder->items()->delete();
            $salesOrder->delete();

            \App\Models\Activity::create([
                'user_name' => auth()->user()->name ?? 'System',
                'action' => 'Deleted',
                'item' => $soNumber,
                'target' => $customer,
                'type' => 'Sales Order',
                'message' => 'Deleted sales order: ' . $soNumber,
            ]);

            return redirect()->route('sales_orders.index')->with('success', 'Sales order deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete sales order: ' . $e->getMessage());
        }
    }

      private function generateTempDRNumber()
    {
        $lastDelivery = Deliveries::orderBy('id', 'desc')->first();
        
        if (!$lastDelivery || !$lastDelivery->dr_no || !preg_match('/DR-(\d+)/', $lastDelivery->dr_no, $matches)) {
            return 'DR-0001';
        }
        
        $lastNumber = intval($matches[1]);
        return 'DR-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    public function approve($id)
    {
        $salesOrder = SalesOrder::findOrFail($id);

        $approverId = auth()->check() ? auth()->id() : 40;

        $salesOrder->update([
            'status' => 'Approved',
            'approved_by' => $approverId,
        ]);

        \App\Models\Activity::create([
            'user_name' => auth()->user()->name ?? 'review',
            'action' => 'Approved',
            'item' => $salesOrder->sales_order_number,
            'target' => optional($salesOrder->customer)->customer_name ?? 'N/A',
            'type' => 'Sales Order',
            'message' => 'Approved sales order: ' . $salesOrder->sales_order_number,
        ]);

        // ❌ REMOVE THIS - Don't auto-create delivery anymore
        // The delivery will be created when user actually creates it in Delivery Module

        return redirect()->route('sales_orders.index')->with('success', 'Sales order approved!');
    }

    public function updateStatus(Request $request, $id)
{
    $request->validate([
        'status' => 'required|in:Approved,Declined,Cancelled',
        'notes' => 'nullable|string', // Add validation for notes
    ]);

    $salesOrder = SalesOrder::with(['customer', 'items'])->findOrFail($id);
    $newStatus = $request->status;

    $updateData = ['status' => $newStatus];

    if (strtolower($newStatus) === 'approved') {
        $updateData['approved_by'] = auth()->id();
    } else {
        $updateData['approved_by'] = null;
    }

    // 🔥 ADD THIS - Save the notes if provided
    if ($request->filled('notes')) {
        $updateData['notes'] = $request->notes;
    }

    $salesOrder->update($updateData);

    $actionMap = [
        'approved' => 'Approved',
        'declined' => 'Declined',
        'cancelled' => 'Cancelled'
    ];

    $actionText = $actionMap[strtolower($newStatus)] ?? ucfirst($newStatus);

    // 🔥 OPTIONAL - Include notes in activity log
    $activityMessage = "{$actionText} sales order: " . $salesOrder->sales_order_number;
    if ($request->filled('notes')) {
        $activityMessage .= " - Reason: " . $request->notes;
    }

    \App\Models\Activity::create([
        'user_name' => auth()->user()->name ?? 'System',
        'action' => $actionText,
        'item' => $salesOrder->sales_order_number,
        'target' => optional($salesOrder->customer)->customer_name ?? 'N/A',
        'type' => 'Sales Order',
        'message' => $activityMessage,
    ]);

    return redirect()->route('sales_orders.index')
        ->with('success', "Sales order status updated to {$newStatus}!");
}

    public function accepted(Request $request)
    {
        $query = SalesOrder::with(['customer', 'approver'])
            ->whereIn('status', ['Approved', 'Accepted'])
            ->orderByDesc('created_at');

        if ($request->filled('so_number')) {
            $query->where('sales_order_number', 'LIKE', '%' . $request->so_number . '%');
        }

        $salesOrders = $query->get();
        return view('sales_orders.accepted', compact('salesOrders'));
    }

    public function markDelivered($id)
    {
        $order = SalesOrder::with(['customer', 'items'])->findOrFail($id);
        $order->update(['status' => 'Delivered']);

        Deliveries::firstOrCreate(
            ['sales_order_number' => $order->sales_order_number],
            [
                'customer_code' => $order->customer->customer_code ?? null,
                'client' => $order->customer->customer_name ?? null,
                'branch' => $order->customer->branch ?? null,
                'status' => 'Completed',
                'quantity' => $order->items->sum('quantity'),
                'total_amount' => $order->total_amount ?? 0,
                'item_code' => $order->item_code,
                'item_description' => $order->item_description,
            ]
        );

        return redirect()->route('deliveries.index')->with('success', 'Order marked as delivered!');
    }

    public function search(Request $request)
    {
        $soNumber = $request->input('so_number');

        $salesOrders = SalesOrder::whereIn('status', ['Approved', 'Accepted'])
            ->where('sales_order_number', 'LIKE', "%{$soNumber}%")
            ->orderByDesc('created_at')
            ->get();

        return view('sales_orders.accepted', compact('salesOrders'));
    }

    private function generateNextSalesOrderNumber()
    {
        $lastOrder = SalesOrder::orderBy('id', 'desc')->first();

        if (!$lastOrder || !$lastOrder->sales_order_number) {
            return 'SO-0001';
        }

        $lastNumber = intval(substr($lastOrder->sales_order_number, 3));
        return 'SO-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    public function printList(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $search = $request->input('search');

        $query = SalesOrder::with(['customer', 'preparer', 'approver']);

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($search) {
            $keyword = $search;
            $query->where(function ($q) use ($keyword) {
                $q->where('sales_order_number', 'LIKE', "%$keyword%")
                    ->orWhere('status', 'LIKE', "%$keyword%")
                    ->orWhereHas('customer', function ($c) use ($keyword) {
                        $c->where('customer_name', 'LIKE', "%$keyword%");
                    });
            });
        }

        $query->where('status', '!=', 'Pending');

        $salesOrders = $query->orderByDesc('created_at')->get();

        if ($salesOrders->isEmpty()) {
            return back()->with('error', 'No approved sales orders found to print.');
        }

        return view('sales_orders.printlist', compact('salesOrders', 'dateFrom', 'dateTo'));
    }

    public function print($id, Request $request)
    {
        $salesOrder = SalesOrder::with(['customer', 'items', 'preparer', 'approver'])->findOrFail($id);

        if ($salesOrder->status === 'Pending') {
            return back()->with('error', 'Cannot print: Sales order is still pending approval.');
        }
        
        return view('sales_orders.print', compact('salesOrder'));
    }

    public function exportExcel(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $search = $request->input('search');

        $query = SalesOrder::with(['customer', 'preparer', 'approver']);

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($search) {
            $keyword = $search;
            $query->where(function ($q) use ($keyword) {
                $q->where('sales_order_number', 'LIKE', "%$keyword%")
                    ->orWhere('status', 'LIKE', "%$keyword%")
                    ->orWhereHas('customer', function ($c) use ($keyword) {
                        $c->where('customer_name', 'LIKE', "%$keyword%");
                    });
            });
        }

        $query->where('status', '!=', 'Pending');

        $salesOrders = $query->orderByDesc('created_at')->get();

        if ($salesOrders->isEmpty()) {
            return back()->with('error', 'No approved sales orders found to export.');
        }

        $filename = 'sales_orders_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Expires'             => '0',
            'Pragma'              => 'public',
        ];

        $callback = function () use ($salesOrders) {
            $file = fopen('php://output', 'w');

            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'SO Number',
                'Customer',
                'Date',
                'Total Amount',
                'Status',
                'Prepared By',
                'Approved By',
                'Sales Representative',
                'Branch',
                'PO Number'
            ]);

            foreach ($salesOrders as $order) {
                fputcsv($file, [
                    $order->sales_order_number,
                    $order->customer->customer_name ?? 'N/A',
                    $order->created_at ? $order->created_at->format('Y-m-d') : '',
                    $order->total_amount,
                    $order->status,
                    $order->preparer->name ?? '—',
                    $order->approver->name ?? '—',
                    $order->sales_rep ?? '',
                    $order->branch ?? '',
                    $order->po_number ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function deliveryBatches($id)
    {
        $salesOrder = SalesOrder::with(['customer', 'items.item'])->findOrFail($id);
        
        // Get all deliveries for this SO to check if there are multiple batches
        $deliveries = Deliveries::where('sales_order_number', $salesOrder->sales_order_number)
            ->with('items')
            ->whereHas('items') // Only deliveries with items
            ->orderBy('created_at', 'asc')
            ->get();
        
        // If there are no deliveries or only one delivery, redirect back
        if ($deliveries->count() < 2) {
            return redirect()->route('sales_orders.show', $id)
                ->with('error', 'This sales order does not have multiple delivery batches yet.');
        }
        
        return view('sales_orders.delivery_batches', compact('salesOrder', 'deliveries'));
    }

    public function manualClose($id)
    {
        try {
            // ✅ Check permissions (only Admin, IT, CC_Approver can manually close)
            $user = auth()->user();
            if (!in_array($user->role, ['Admin', 'IT', 'CC_Approver'])) {
                return redirect()->back()->with('error', 'Unauthorized: Only Admin, IT, or CC_Approver can manually close Sales Orders.');
            }

            $salesOrder = SalesOrder::with('items', 'deliveries')->findOrFail($id);

            // ✅ Check if already closed
            if ($salesOrder->is_closed) {
                return redirect()->back()->with('info', 'This Sales Order is already closed.');
            }

            DB::beginTransaction();

            // ✅ Close the Sales Order
            $salesOrder->update(['is_closed' => true]);

            // ✅ Update all related deliveries to "Delivered" status
            $deliveries = Deliveries::where('sales_order_number', $salesOrder->sales_order_number)
                ->where('status', '!=', 'Delivered')
                ->get();

            $deliveryCount = 0;
            foreach ($deliveries as $delivery) {
                $delivery->update([
                    'status' => 'Delivered',
                    'approval_status' => 'Approved', // Also approve if pending
                ]);
                $deliveryCount++;
            }

            // ✅ Log activity
            \App\Models\Activity::create([
                'user_name' => auth()->user()->name ?? 'System',
                'action' => 'Manually Closed',
                'item' => $salesOrder->sales_order_number,
                'target' => optional($salesOrder->customer)->customer_name ?? 'N/A',
                'type' => 'Sales Order',
                'message' => "Manually closed Sales Order: {$salesOrder->sales_order_number}. {$deliveryCount} delivery(ies) marked as delivered.",
            ]);

            DB::commit();

            return redirect()->route('sales_orders.show', $id)
                ->with('success', "✅ Sales Order closed successfully! {$deliveryCount} delivery(ies) marked as delivered.");

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Manual SO Close Error:', ['message' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to close Sales Order: ' . $e->getMessage());
        }
    }
}
