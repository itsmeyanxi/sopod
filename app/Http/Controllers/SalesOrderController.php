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
use App\Traits\LogsControllerActions;


class SalesOrderController extends Controller
{

    use LogsControllerActions;

   
   protected function getLogChannel(): string
   {
       return 'sales_orders';
   }

   public function index(Request $request)
{
    $query = SalesOrder::with(['customer', 'preparer', 'approver', 'deliveries'])
        ->orderByDesc('created_at');

    // This allows filtering even for SOs without deliveries yet
    if ($request->filled('date_from')) {
        $query->whereDate('request_delivery_date', '>=', $request->date_from);
    }

    if ($request->filled('date_to')) {
        $query->whereDate('request_delivery_date', '<=', $request->date_to);
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

private function recalculateSalesOrderTotals($salesOrderId)
{
    try {
        $salesOrder = SalesOrder::with('items')->findOrFail($salesOrderId);
        
        $correctTotal = 0;
        $itemsFixed = 0;
        
        foreach ($salesOrder->items as $item) {
            $quantity = (float) ($item->quantity ?? 0);
            $unitPrice = (float) ($item->unit_price ?? 0);
            $correctItemTotal = round($quantity * $unitPrice, 2);
            
            // Check if item total is wrong
            if (abs($item->total_amount - $correctItemTotal) > 0.01) {
                \Log::warning('⚠️ SO Item total mismatch', [
                    'so_id' => $salesOrderId,
                    'item_code' => $item->item_code,
                    'stored_total' => $item->total_amount,
                    'correct_total' => $correctItemTotal,
                ]);
                
                $item->update(['total_amount' => $correctItemTotal]);
                $itemsFixed++;
            }
            
            $correctTotal += $correctItemTotal;
        }
        
        // Check if SO total is wrong
        if (abs($salesOrder->total_amount - $correctTotal) > 0.01) {
            \Log::warning('⚠️ SO Grand total mismatch', [
                'so_id' => $salesOrderId,
                'so_number' => $salesOrder->sales_order_number,
                'stored_total' => $salesOrder->total_amount,
                'correct_total' => $correctTotal,
            ]);
            
            $salesOrder->update(['total_amount' => $correctTotal]);
        }
        
        if ($itemsFixed > 0) {
            \Log::info("✅ Fixed {$itemsFixed} SO item(s) for SO #{$salesOrderId}");
        }
        
    } catch (\Exception $e) {
        \Log::error('Failed to recalculate SO totals', [
            'so_id' => $salesOrderId,
            'error' => $e->getMessage()
        ]);
    }
}


/**
 * ✅ Bulk approve multiple sales orders
 */
public function bulkApprove(Request $request)
{
    try {
        $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'exists:sales_orders,id',
        ]);

        $orderIds = $request->order_ids;
        $approverId = auth()->check() ? auth()->id() : 40;
        
        DB::beginTransaction();

        $approvedCount = 0;
        $errors = [];

        foreach ($orderIds as $orderId) {
            try {
                $salesOrder = SalesOrder::findOrFail($orderId);
                
                // Skip if already approved
                if ($salesOrder->status === 'Approved') {
                    continue;
                }
                
                $oldStatus = $salesOrder->status;

                $salesOrder->update([
                    'status' => 'Approved',
                    'approved_by' => $approverId,
                ]);

                // // Try to send email notification
                // app(\App\Services\NotificationService::class)->notifySalesOrderStatusChange(
                //     $salesOrder->fresh(),
                //     $oldStatus,
                //     'Approved'
                // );

                // Log activity
                \App\Models\Activity::create([
                    'user_name' => auth()->user()->name ?? 'review',
                    'action' => 'Bulk Approved',
                    'item' => $salesOrder->sales_order_number,
                    'target' => optional($salesOrder->customer)->customer_name ?? 'N/A',
                    'type' => 'Sales Order',
                    'message' => 'Bulk approved sales order: ' . $salesOrder->sales_order_number,
                ]);

                $approvedCount++;

            } catch (\Exception $e) {
                $errors[] = "Failed to approve SO #{$orderId}: " . $e->getMessage();
                \Log::error('Bulk approval error for SO #' . $orderId, [
                    'error' => $e->getMessage()
                ]);
            }
        }

        DB::commit();

        $message = "Successfully approved {$approvedCount} sales order(s)!";
        if (count($errors) > 0) {
            $message .= " " . count($errors) . " failed. Check logs for details.";
        }

        return redirect()->route('sales_orders.index')
            ->with('success', $message);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Bulk approval failed', [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
        ]);
        
        return redirect()->back()
            ->with('error', 'Bulk approval failed: ' . $e->getMessage());
    }
}

/**
 * ✅ Bulk decline multiple sales orders
 */
public function bulkDecline(Request $request)
{
    try {
        $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'exists:sales_orders,id',
            'decline_reason' => 'required|string|min:5',
        ]);

        $orderIds = $request->order_ids;
        $reason = $request->decline_reason;
        
        DB::beginTransaction();

        $declinedCount = 0;
        $errors = [];

        foreach ($orderIds as $orderId) {
            try {
                $salesOrder = SalesOrder::findOrFail($orderId);
                
                // Skip if already declined
                if ($salesOrder->status === 'Declined') {
                    continue;
                }
                
                $oldStatus = $salesOrder->status;

                $salesOrder->update([
                    'status' => 'Declined',
                    'approved_by' => null,
                    'notes' => $reason,
                ]);

                // Try to send email notification
                // app(\App\Services\NotificationService::class)->notifySalesOrderStatusChange(
                //     $salesOrder->fresh(),
                //     $oldStatus,
                //     'Declined'
                // );

                // Log activity
                \App\Models\Activity::create([
                    'user_name' => auth()->user()->name ?? 'System',
                    'action' => 'Bulk Declined',
                    'item' => $salesOrder->sales_order_number,
                    'target' => optional($salesOrder->customer)->customer_name ?? 'N/A',
                    'type' => 'Sales Order',
                    'message' => 'Bulk declined sales order: ' . $salesOrder->sales_order_number . ' - Reason: ' . $reason,
                ]);

                $declinedCount++;

            } catch (\Exception $e) {
                $errors[] = "Failed to decline SO #{$orderId}: " . $e->getMessage();
                \Log::error('Bulk decline error for SO #' . $orderId, [
                    'error' => $e->getMessage()
                ]);
            }
        }

        DB::commit();

        $message = "Successfully declined {$declinedCount} sales order(s)!";
        if (count($errors) > 0) {
            $message .= " " . count($errors) . " failed. Check logs for details.";
        }

        return redirect()->route('sales_orders.index')
            ->with('success', $message);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Bulk decline failed', [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
        ]);
        
        return redirect()->back()
            ->with('error', 'Bulk decline failed: ' . $e->getMessage());
    }
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
        'items.*.amount' => 'nullable|numeric|min:0', // ✅ Made nullable
        'additional_instructions' => 'nullable|string',
    ], [
        'po_image.mimes' => 'PO proof must be a JPG, PNG, or PDF file.',
        'po_image.max' => 'PO proof file size must not exceed 4MB.',
    ]);

    if (!$request->po_reference_no && !$request->hasFile('po_image')) {
        return back()->withInput()->with('error', 'Please provide either a PO Number or upload proof of customer order.');
    }

    DB::beginTransaction();

    try {
        $customer = Customer::where('customer_code', $request->customer_code)->firstOrFail();
        $salesOrderNumber = $this->generateSalesOrderNumber();

        $items = $request->items;
        
        // ✅ CRITICAL: ALWAYS calculate server-side, ignore client values
        $totalAmount = 0;
        foreach ($items as $item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['price'] ?? 0);
            $itemTotal = round($qty * $price, 2);
            $totalAmount += $itemTotal;
            
            // ✅ Log if client sent wrong amount
            if (isset($item['amount']) && abs($item['amount'] - $itemTotal) > 0.01) {
                \Log::warning('⚠️ Client sent incorrect item amount', [
                    'item_code' => $item['item_code'] ?? 'unknown',
                    'client_sent' => $item['amount'],
                    'server_calculated' => $itemTotal,
                ]);
            }
        }
        
        $totalAmount = round($totalAmount, 2); // ✅ Round grand total
        
        $firstItem = $items[0] ?? [];

        // Handle PO Image Upload
        $poImageFilename = null;
        if ($request->hasFile('po_image')) {
            $file = $request->file('po_image');
            $poImageFilename = time() . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());
            
            $uploadPath = public_path('po_images');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            $file->move($uploadPath, $poImageFilename);
            
            \Log::info('✅ PO Image uploaded', [
                'filename' => $poImageFilename,
                'path' => $uploadPath . '/' . $poImageFilename
            ]);
        }

        $initialStatus = $customer->is_flagged ? 'Pending' : 'Approved';
        $approvedBy = $customer->is_flagged ? null : auth()->id();

        // ✅ CREATE SALES ORDER with CALCULATED total
        $salesOrder = SalesOrder::create([
            'sales_order_number' => $salesOrderNumber,
            'customer_id' => $customer->id,
            'prepared_by' => auth()->id(),
            'approved_by' => $approvedBy,
            'request_delivery_date' => $request->request_delivery_date,
            'po_number' => $request->po_reference_no,
            'po_image' => $poImageFilename,
            'customer_name' => $request->customer_name ?? $customer->customer_name,
            'shipping_address' => $request->shipping_address,
            'sales_rep' => $request->sales_rep,
            'sales_executive' => $request->sales_executive ?? null,
            'branch' => $request->branch ?? null,
            'total_amount' => $totalAmount, // ✅ Use server-calculated total
            'item_description' => $firstItem['item_description'] ?? null,
            'item_code' => $firstItem['item_code'] ?? null,
            'brand' => $firstItem['brand'] ?? null,
            'item_category' => $firstItem['item_category'] ?? null,
            'additional_instructions' => $request->additional_instructions,
            'status' => $initialStatus,
        ]);

        // ✅ CREATE SALES ORDER ITEMS with CALCULATED totals
        foreach ($request->items as $index => $itemData) {
            $item = Item::find($itemData['item_id']);
            
            $quantity = (float) ($itemData['quantity'] ?? 0);
            $unitPrice = (float) ($itemData['price'] ?? 0);
            $itemTotal = round($quantity * $unitPrice, 2); // ✅ Always calculate

            SalesOrderItem::create([
                'sales_order_id' => $salesOrder->id,
                'item_id' => $itemData['item_id'],
                'item_code' => $itemData['item_code'] ?? $item->item_code ?? null,
                'item_description' => $itemData['item_description'] ?? $item->item_description ?? null,
                'brand' => $itemData['brand'] ?? $item->brand ?? null,
                'item_category' => $itemData['item_category'] ?? $item->item_category ?? null,
                'quantity' => $quantity,
                'unit' => $itemData['unit'] ?? $item->unit ?? 'Kgs',
                'unit_price' => $unitPrice,
                'total_amount' => $itemTotal, // ✅ Use calculated value ONLY
                'note' => $itemData['note'] ?? null,
            ]);
        }

        // ✅ VERIFY calculations after creation
        $this->recalculateSalesOrderTotals($salesOrder->id);

        // Update customer shipping address if provided
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

        $statusMessage = $customer->is_flagged 
            ? 'Created sales order (Pending - Customer Flagged)' 
            : 'Created sales order (Auto-Approved - Customer Unflagged)';

        \App\Models\Activity::create([
            'user_name' => auth()->user()->name ?? 'System',
            'action' => 'Created',
            'item' => $salesOrderNumber,
            'target' => $customer->customer_name ?? 'N/A',
            'type' => 'Sales Order',
            'message' => $statusMessage . ': ' . $salesOrderNumber . ($poImageFilename ? ' (with PO proof uploaded)' : ''),
        ]);

        DB::commit();

        $successMessage = $customer->is_flagged 
            ? 'Sales order created successfully! (Pending approval - Customer is flagged)' 
            : 'Sales order created and auto-approved! (Customer is unflagged)';

        return redirect()->route('sales_orders.index')
            ->with('success', $successMessage);

    } catch (\Exception $e) {
        DB::rollBack();
        
        if (isset($poImageFilename) && file_exists(public_path('po_images/' . $poImageFilename))) {
            @unlink(public_path('po_images/' . $poImageFilename));
        }
        
        \Log::error('Sales Order Error:', ['message' => $e->getMessage()]);
        return back()->withInput()->with('error', 'Failed to save sales order: ' . $e->getMessage());
    }
}

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
    
    // ✅ NEW LOGIC: Check if delivery exists and is delivered
    $hasDelivery = $salesOrder->deliveries !== null;
    $isDelivered = false;
    
    if ($hasDelivery) {
        $delivery = $salesOrder->deliveries;
        // Check if delivery is marked as Delivered
        if ($delivery->status === 'Delivered') {
            $isDelivered = true;
        }
    }
    
    // ✅ NEW LOGIC: Permission check based on delivery status
    $user = auth()->user();
    $canEdit = false;
    $needsCCApproval = false;
    
    if (!$isDelivered) {
        // NOT delivered yet - Both CC_Approver and CSR can edit freely
        if ($user->canInitiateEdit() || $user->canEditAfterCCApproval()) {
            $canEdit = true;
        }
    } else {
        // DELIVERED - Need permission system
        if ($user->canInitiateEdit()) {
            // CC_Approver can always edit
            $canEdit = true;
        } elseif ($user->canEditAfterCCApproval()) {
            // CSR roles need CC approval to edit delivered orders
            if ($salesOrder->isEditApprovedByCC()) {
                $canEdit = true;
            } else {
                $needsCCApproval = true;
            }
        }
    }
    
    if (!$canEdit) {
        $message = $needsCCApproval 
            ? 'This Sales Order has been delivered and requires CC_Approver permission to edit.'
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
    
    $hasDelivery = $salesOrder->deliveries !== null;
    $isDelivered = false;
    
    if ($hasDelivery) {
        $delivery = $salesOrder->deliveries;
        if ($delivery->status === 'Delivered') {
            $isDelivered = true;
        }
    }
    
    $user = auth()->user();
    $canEdit = false;
    
    if (!$isDelivered) {
        if ($user->canInitiateEdit() || $user->canEditAfterCCApproval()) {
            $canEdit = true;
        }
    } else {
        if ($user->canInitiateEdit()) {
            $canEdit = true;
        } elseif ($user->canEditAfterCCApproval() && $salesOrder->isEditApprovedByCC()) {
            $canEdit = true;
        }
    }
    
    if (!$canEdit) {
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
        'items.*.total_amount' => 'nullable|numeric|min:0', // ✅ Made nullable
    ], [
        'po_image.mimes' => 'PO proof must be a JPG, PNG, or PDF file.',
        'po_image.max' => 'PO proof file size must not exceed 4MB.',
    ]);

    DB::beginTransaction();
    try {
        // Track changes before updating
        $originalItems = $salesOrder->items->keyBy('item_code')->map(function($item) {
            return [
                'item_code' => $item->item_code,
                'item_description' => $item->item_description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'unit' => $item->unit,
                'brand' => $item->brand,
            ];
        })->toArray();

        $originalData = [
            'customer_id' => $salesOrder->customer_id,
            'po_number' => $salesOrder->po_number,
            'request_delivery_date' => $salesOrder->request_delivery_date,
            'shipping_address' => $salesOrder->shipping_address,
            'sales_rep' => $salesOrder->sales_rep,
            'total_amount' => $salesOrder->total_amount,
        ];

        // Handle PO Image
        $poImageFilename = $salesOrder->po_image;
        
        if ($request->has('remove_po_image') && $request->remove_po_image == '1') {
            if ($salesOrder->po_image && file_exists(public_path('po_images/' . $salesOrder->po_image))) {
                @unlink(public_path('po_images/' . $salesOrder->po_image));
            }
            $poImageFilename = null;
        }
        
        if ($request->hasFile('po_image')) {
            if ($salesOrder->po_image && file_exists(public_path('po_images/' . $salesOrder->po_image))) {
                @unlink(public_path('po_images/' . $salesOrder->po_image));
            }
            
            $file = $request->file('po_image');
            $poImageFilename = time() . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());
            
            $uploadPath = public_path('po_images');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
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
            'po_number' => $request->po_number,
            'po_image' => $poImageFilename,
            'sales_rep' => $request->sales_rep ?? $salesOrder->sales_rep,
            'shipping_address' => $request->shipping_address ?? $salesOrder->shipping_address,
            'additional_instructions' => $request->additional_instructions,
            'sales_executive' => optional(Customer::find($request->customer_id))->sales_executive ?? $salesOrder->sales_executive,
        ]);

        // Delete existing items
        $salesOrder->items()->delete();

        // ✅ CRITICAL: Calculate totals server-side ONLY
        $newTotalAmount = 0;

        foreach ($request->items as $itemData) {
            $quantity = (float) ($itemData['quantity'] ?? 0);
            $unitPrice = (float) ($itemData['unit_price'] ?? 0);
            $itemTotal = round($quantity * $unitPrice, 2); // ✅ Always calculate

            // ✅ Log if client sent wrong total
            if (isset($itemData['total_amount']) && abs($itemData['total_amount'] - $itemTotal) > 0.01) {
                \Log::warning('⚠️ Client sent incorrect item total during update', [
                    'item_code' => $itemData['item_code'] ?? 'unknown',
                    'client_sent' => $itemData['total_amount'],
                    'server_calculated' => $itemTotal,
                ]);
            }

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
                'total_amount' => $itemTotal, // ✅ Use ONLY calculated value
                'note' => $itemData['note'] ?? null,
            ]);

            $newTotalAmount += $itemTotal;
        }

        $newTotalAmount = round($newTotalAmount, 2); // ✅ Round grand total

        // Update first item reference
        $firstItem = $request->items[0] ?? [];
        $firstItemModel = Item::find($firstItem['item_id'] ?? null);

        $salesOrder->item_description = $firstItem['item_description'] ?? $firstItemModel->item_description ?? null;
        $salesOrder->item_code = $firstItem['item_code'] ?? $firstItemModel->item_code ?? null;
        $salesOrder->brand = $firstItem['brand'] ?? $firstItemModel->brand ?? null;
        $salesOrder->item_category = $firstItem['item_category'] ?? $firstItemModel->item_category ?? null;
        $salesOrder->total_amount = $newTotalAmount; // ✅ Use calculated total
        $salesOrder->save();

        // ✅ VERIFY calculations
        $this->recalculateSalesOrderTotals($salesOrder->id);

        // Sync delivery dates
        if ($request->filled('request_delivery_date')) {
            $updatedDeliveriesCount = Deliveries::where('sales_order_number', $salesOrder->sales_order_number)
                ->update(['request_delivery_date' => $request->request_delivery_date]);
            
            SalesOrderItem::where('sales_order_id', $salesOrder->id)
                ->update(['request_delivery_date' => $request->request_delivery_date]);
            
            \Log::info('✅ Synced delivery dates', [
                'so_number' => $salesOrder->sales_order_number,
                'new_date' => $request->request_delivery_date,
                'deliveries_updated' => $updatedDeliveriesCount,
                'so_items_updated' => SalesOrderItem::where('sales_order_id', $salesOrder->id)->count()
            ]);
        }

        // Track changes (existing code...)
        $userId = auth()->id();
        $trackableFields = ['customer_id', 'po_number', 'request_delivery_date', 'shipping_address', 'sales_rep', 'total_amount'];

        foreach ($trackableFields as $field) {
            $oldValue = $originalData[$field] ?? null;
            $newValue = $salesOrder->$field ?? null;

            if ($oldValue != $newValue) {
                $oldDisplay = $this->formatChangeValue($field, $oldValue);
                $newDisplay = $this->formatChangeValue($field, $newValue);

                \App\Models\SalesOrderChange::create([
                    'sales_order_id' => $salesOrder->id,
                    'user_id' => $userId,
                    'field_changed' => $field,
                    'old_value' => $oldDisplay,
                    'new_value' => $newDisplay,
                    'change_type' => 'update',
                ]);
            }
        }

        // Track item changes (rest of existing code...)

        $this->syncDeliveryPrices($salesOrder);
        $this->syncDeliveryItems($salesOrder);

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
        
        if (isset($poImageFilename) && $poImageFilename !== $salesOrder->po_image && file_exists(public_path('po_images/' . $poImageFilename))) {
            @unlink(public_path('po_images/' . $poImageFilename));
        }
        
        \Log::error('SO Update Error:', ['message' => $e->getMessage()]);
        return back()->withInput()->with('error', 'Failed to update: ' . $e->getMessage());
    }
}

public function fixExistingSalesOrders()
{
    try {
        $salesOrders = SalesOrder::with('items')->get();
        $fixedCount = 0;
        
        foreach ($salesOrders as $so) {
            $correctTotal = 0;
            $itemsFixed = 0;
            
            foreach ($so->items as $item) {
                $qty = (float) ($item->quantity ?? 0);
                $price = (float) ($item->unit_price ?? 0);
                $correctItemTotal = round($qty * $price, 2);
                
                if (abs($item->total_amount - $correctItemTotal) > 0.01) {
                    \Log::info('🔧 Fixing SO item total', [
                        'so_number' => $so->sales_order_number,
                        'item_code' => $item->item_code,
                        'old_total' => $item->total_amount,
                        'new_total' => $correctItemTotal,
                    ]);
                    
                    $item->update(['total_amount' => $correctItemTotal]);
                    $itemsFixed++;
                }
                
                $correctTotal += $correctItemTotal;
            }
            
            $correctTotal = round($correctTotal, 2);
            
            if (abs($so->total_amount - $correctTotal) > 0.01) {
                \Log::info('🔧 Fixing SO grand total', [
                    'so_number' => $so->sales_order_number,
                    'old_total' => $so->total_amount,
                    'new_total' => $correctTotal,
                ]);
                
                $so->update(['total_amount' => $correctTotal]);
                $fixedCount++;
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => "Fixed {$fixedCount} sales order(s)",
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Failed to fix SO totals', ['error' => $e->getMessage()]);
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}


private function syncDeliveryItems(SalesOrder $salesOrder)
{
    try {
        // Get all PENDING deliveries for this SO (approved deliveries should not be auto-updated)
        $deliveries = Deliveries::where('sales_order_number', $salesOrder->sales_order_number)
            ->where('approval_status', 'Pending')
            ->get();
        
        if ($deliveries->isEmpty()) {
            \Log::info('No pending deliveries to sync for SO', [
                'so_number' => $salesOrder->sales_order_number
            ]);
            return;
        }

        // Create a map of SO items by item_code
        $soItemsMap = $salesOrder->items->keyBy('item_code');
        
        foreach ($deliveries as $delivery) {
            $deliveryUpdated = false;
            
            // Get already delivered quantities (excluding current delivery)
            $deliveredSums = DeliveryItem::whereHas('delivery', function($q) use ($salesOrder, $delivery) {
                    $q->where('sales_order_number', $salesOrder->sales_order_number)
                      ->where('approval_status', 'Approved')
                      ->where('status', 'Delivered')
                      ->where('id', '!=', $delivery->id);
                })
                ->where('quantity', '>', 0)
                ->select('item_code', DB::raw('SUM(quantity) as total_delivered'))
                ->groupBy('item_code')
                ->get()
                ->keyBy('item_code');
            
            // ✅ STEP 1: Update existing delivery items with new prices/quantities
            foreach ($delivery->items as $deliveryItem) {
                $soItem = $soItemsMap->get($deliveryItem->item_code);
                
                if ($soItem) {
                    // Update price if changed
                    if ($soItem->unit_price != $deliveryItem->unit_price) {
                        $newTotalAmount = $deliveryItem->quantity * $soItem->unit_price;
                        
                        $deliveryItem->update([
                            'unit_price' => $soItem->unit_price,
                            'total_amount' => $newTotalAmount,
                        ]);
                        
                        $deliveryUpdated = true;
                        
                        \Log::info("✅ Updated delivery item price", [
                            'delivery_id' => $delivery->id,
                            'item_code' => $deliveryItem->item_code,
                            'new_price' => $soItem->unit_price,
                        ]);
                    }
                    
                    // Update quantities and recalculate remaining
                    $originalQty = $soItem->quantity;
                    $alreadyDelivered = $deliveredSums->get($deliveryItem->item_code)?->total_delivered ?? 0;
                    $remainingQty = max(0, $originalQty - $alreadyDelivered);
                    
                    $deliveryItem->update([
                        'original_quantity' => $originalQty,
                        'remaining_quantity' => $remainingQty,
                        'item_description' => $soItem->item_description,
                        'brand' => $soItem->brand,
                        'item_category' => $soItem->item_category,
                        'uom' => $soItem->unit,
                    ]);
                    
                    $deliveryUpdated = true;
                }
            }
            
            // ✅ STEP 2: Add NEW items that exist in SO but not in delivery
            $deliveryItemCodes = $delivery->items->pluck('item_code')->toArray();
            
            foreach ($soItemsMap as $itemCode => $soItem) {
                if (!in_array($itemCode, $deliveryItemCodes)) {
                    // This is a NEW item added to the SO
                    $alreadyDelivered = $deliveredSums->get($itemCode)?->total_delivered ?? 0;
                    $remainingQty = max(0, $soItem->quantity - $alreadyDelivered);
                    
                    // Only add if there's remaining quantity
                    if ($remainingQty > 0) {
                        DeliveryItem::create([
                            'delivery_id' => $delivery->id,
                            'item_id' => $soItem->item_id,
                            'sales_order_item_id' => $soItem->id,
                            'item_code' => $itemCode,
                            'item_description' => $soItem->item_description,
                            'brand' => $soItem->brand,
                            'item_category' => $soItem->item_category,
                            'quantity' => $remainingQty, // Default to remaining quantity
                            'original_quantity' => $soItem->quantity,
                            'remaining_quantity' => 0, // Will be 0 since we're delivering the rest
                            'uom' => $soItem->unit,
                            'unit_price' => $soItem->unit_price,
                            'total_amount' => $remainingQty * $soItem->unit_price,
                            'delivery_batch' => $delivery->delivery_batch,
                            'notes' => $soItem->note,
                        ]);
                        
                        $deliveryUpdated = true;
                        
                        \Log::info("✅ Added new item to delivery", [
                            'delivery_id' => $delivery->id,
                            'item_code' => $itemCode,
                            'quantity' => $remainingQty,
                        ]);
                    }
                }
            }
            
            // ✅ STEP 3: Mark removed items with quantity = 0 (don't delete, for tracking)
            foreach ($delivery->items as $deliveryItem) {
                if (!$soItemsMap->has($deliveryItem->item_code)) {
                    // Item was removed from SO
                    $deliveryItem->update([
                        'quantity' => 0,
                        'total_amount' => 0,
                        'notes' => ($deliveryItem->notes ?? '') . ' [Item removed from SO]',
                    ]);
                    
                    $deliveryUpdated = true;
                    
                    \Log::info("✅ Marked delivery item as removed", [
                        'delivery_id' => $delivery->id,
                        'item_code' => $deliveryItem->item_code,
                    ]);
                }
            }
            
            if ($deliveryUpdated) {
                \Log::info("✅ Delivery items synced", [
                    'delivery_id' => $delivery->id,
                    'dr_no' => $delivery->dr_no,
                    'so_number' => $salesOrder->sales_order_number,
                ]);
            }
        }
        
    } catch (\Exception $e) {
        \Log::error('❌ Failed to sync delivery items', [
            'so_number' => $salesOrder->sales_order_number,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        // Don't throw - let SO update succeed even if delivery sync fails
    }
}

// Add this helper method to your SalesOrderController class
private function formatChangeValue($field, $value)
{
    if ($value === null) return 'None';
    
    switch ($field) {
        case 'customer_id':
            $customer = \App\Models\Customer::find($value);
            return $customer ? $customer->customer_name : 'Unknown';
        case 'total_amount':
            return '₱' . number_format($value, 2);
        case 'request_delivery_date':
            return date('M d, Y', strtotime($value));
        default:
            return $value;
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
        try {
            $salesOrder = SalesOrder::findOrFail($id);
            $oldStatus = $salesOrder->status;

            $approverId = auth()->check() ? auth()->id() : 40;

            $salesOrder->update([
                'status' => 'Approved',
                'approved_by' => $approverId,
            ]);

            \Log::info('📧 About to send approval email', [
                'so_number' => $salesOrder->sales_order_number,
                'old_status' => $oldStatus,
                'new_status' => 'Approved'
            ]);

            // $emailSent = app(\App\Services\NotificationService::class)->notifySalesOrderStatusChange(
            //     $salesOrder->fresh(),
            //     $oldStatus,
            //     'Approved'
            // );

            \Log::info('📧 Email send result', ['success' => $emailSent]);

            \App\Models\Activity::create([
                'user_name' => auth()->user()->name ?? 'review',
                'action' => 'Approved',
                'item' => $salesOrder->sales_order_number,
                'target' => optional($salesOrder->customer)->customer_name ?? 'N/A',
                'type' => 'Sales Order',
                'message' => 'Approved sales order: ' . $salesOrder->sales_order_number,
            ]);

            // $message = 'Sales order approved!';
            // if (!$emailSent) {
            //     $message .= ' (Note: Email notification may have failed - check logs)';
            // }

            return redirect()->route('sales_orders.index')->with('success', $message);
            
        } catch (\Exception $e) {
            \Log::error('❌ SO Approval failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Failed to approve: ' . $e->getMessage());
        }
    }

public function updateStatus(Request $request, $id)
{
    $request->validate([
        'status' => 'required|in:Approved,Declined,Cancelled',
        'notes' => 'nullable|string',
    ]);

    $salesOrder = SalesOrder::with(['customer', 'items'])->findOrFail($id);
    
    $oldStatus = $salesOrder->status;
    $newStatus = $request->status;

    $updateData = ['status' => $newStatus];

    if (strtolower($newStatus) === 'approved') {
        $updateData['approved_by'] = auth()->id();
        
        // ✅ Log if approving a flagged customer's order
        if ($salesOrder->customer && $salesOrder->customer->is_flagged) {
            \Log::warning('⚠️ FLAGGED CUSTOMER APPROVED', [
                'so_number' => $salesOrder->sales_order_number,
                'customer_code' => $salesOrder->customer->customer_code,
                'customer_name' => $salesOrder->customer->customer_name,
                'approved_by' => auth()->user()->name ?? 'Unknown',
                'approved_by_id' => auth()->id(),
                'timestamp' => now()
            ]);
        }
    } else {
        $updateData['approved_by'] = null;
    }

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

    $activityMessage = "{$actionText} sales order: " . $salesOrder->sales_order_number;
    
    // ✅ Add flagged customer note to activity log
    if ($newStatus === 'Approved' && $salesOrder->customer && $salesOrder->customer->is_flagged) {
        $activityMessage .= " ⚠️ (Flagged Customer: {$salesOrder->customer->customer_name})";
    }
    
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

    $successMessage = "Sales order status updated to {$newStatus}!";
    
    // ✅ Add warning message if flagged customer was approved
    if ($newStatus === 'Approved' && $salesOrder->customer && $salesOrder->customer->is_flagged) {
        $successMessage .= " ⚠️ Note: This customer is currently flagged.";
    }

    return redirect()->route('sales_orders.index')
        ->with('success', $successMessage);
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
                    'approval_status' => 'Approved', 
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
