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
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalesOrdersExport;

class SalesOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesOrder::with(['customer', 'preparer', 'approver', 'deliveries'])
            ->orderByDesc('created_at');

        // 📅 DATE FROM
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        // 📅 DATE TO
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // 🔍 ONE BAR SEARCH (search everything)
        if ($request->filled('search')) {
            $keyword = $request->search;

            $query->where(function ($q) use ($keyword) {
                $q->where('sales_order_number', 'LIKE', "%$keyword%")
                ->orWhere('status', 'LIKE', "%$keyword%")
                ->orWhere('po_number', 'LIKE', "%$keyword%")
                ->orWhere('sales_rep', 'LIKE', "%$keyword%")  // ✅ CHANGED
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
        $items = Item::where('approval_status', 'approved')->get(); // ✅ Only approved items
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
            'po_reference_no' => 'required|unique:sales_orders,po_number',
            'sales_rep' => 'required|string',  // ✅ CHANGED from sales_representative
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'additional_instructions' => 'nullable|string',
        ], [
            'po_reference_no.unique' => 'This PO Number has already been used in another sales order.',
        ]);

        DB::beginTransaction();

        try {
            $customer = Customer::where('customer_code', $request->customer_code)->firstOrFail();
            $salesOrderNumber = $this->generateSalesOrderNumber();

            $items = $request->items;
            $totalAmount = collect($items)->sum(fn($i) => $i['quantity'] * $i['price']);
            $firstItem = $items[0] ?? [];
            
            // ✅ Use the main request_delivery_date as default batch date
            $defaultDeliveryDate = $request->request_delivery_date;
            $deliveryBatch = $salesOrderNumber . '-' . date('Ymd', strtotime($defaultDeliveryDate));

            $salesOrder = SalesOrder::create([
                'sales_order_number' => $salesOrderNumber,
                'customer_id' => $customer->id,
                'prepared_by' => auth()->id(),
                'approved_by' => null, 
                'request_delivery_date' => $defaultDeliveryDate,
                'po_number' => $request->po_reference_no,
                'customer_name' => $request->customer_name ?? $customer->customer_name,
                'sales_rep' => $request->sales_rep,  // ✅ CHANGED
                'sales_executive' => $request->sales_executive ?? null,
                'branch' => $request->branch ?? null,
                'total_amount' => $totalAmount,
                'item_description' => $firstItem['item_description'] ?? null,
                'item_code' => $firstItem['item_code'] ?? null,
                'brand' => $firstItem['brand'] ?? null,
                'item_category' => $firstItem['item_category'] ?? null,
                'additional_instructions' => $request->additional_instructions,
            ]);

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
                    'delivery_batch' => $deliveryBatch,
                    'request_delivery_date' => $defaultDeliveryDate,
                ]);
            }

            \App\Models\Activity::create([
                'user_name' => auth()->user()->name ?? 'System',
                'action' => 'Created',
                'item' => $salesOrderNumber,
                'target' => $customer->customer_name ?? 'N/A',
                'type' => 'Sales Order',
                'message' => 'Created sales order: ' . $salesOrderNumber,
            ]);

            DB::commit();
            return redirect()->route('sales_orders.index')
                ->with('success', 'Sales order created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Sales Order Error:', ['message' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to save sales order: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $salesOrder = SalesOrder::with(['customer', 'items.item'])->findOrFail($id);
        return view('sales_orders.show', compact('salesOrder'));
    }

        // 🔥 FIXED: Load Item relationship and populate missing data
    public function edit($id)
    {
        $salesOrder = SalesOrder::with(['customer', 'items.item'])->findOrFail($id);
        $customers = Customer::all();
        $items = Item::where('approval_status', 'approved')->get();
        
        // 🔥 Populate missing item data from Item model
        foreach ($salesOrder->items as $orderItem) {
            if ($orderItem->item) {
                // Fill in missing description (using item_description from items table)
                if (empty($orderItem->item_description) && !empty($orderItem->item->item_description)) {
                    $orderItem->item_description = $orderItem->item->item_description;
                }
                
                // Fill in missing category (using item_category from items table)
                if (empty($orderItem->item_category) && !empty($orderItem->item->item_category)) {
                    $orderItem->item_category = $orderItem->item->item_category;
                }
                
                // Fill in missing brand
                if (empty($orderItem->brand) && !empty($orderItem->item->brand)) {
                    $orderItem->brand = $orderItem->item->brand;
                }
                
                // Fill in missing item_code
                if (empty($orderItem->item_code) && !empty($orderItem->item->item_code)) {
                    $orderItem->item_code = $orderItem->item->item_code;
                }
            }
        }
        
        return view('sales_orders.edit', compact('salesOrder', 'customers'));
    }

    // 🔥 FIXED: Populate data from Item model when updating
        public function update(Request $request, $id)
    {
        $salesOrder = SalesOrder::findOrFail($id);

        $salesOrder->update([
            'customer_id' => $request->customer_id,
            'status' => $request->status ?? $salesOrder->status,
            'order_date' => $request->order_date ?? $salesOrder->order_date,
            'remarks' => $request->remarks ?? $salesOrder->remarks,
            'sales_rep' => $request->sales_rep ?? $salesOrder->sales_rep,  // ✅ CHANGED
            'request_delivery_date' => $request->request_delivery_date ?? $salesOrder->request_delivery_date,
            'additional_instructions' => $request->additional_instructions,
            'sales_executive' => optional(Customer::find($request->customer_id))->sales_executive ?? $salesOrder->sales_executive,
        ]);

        // ✅ UPDATE DELIVERY RECORD IF IT EXISTS
        if ($request->filled('request_delivery_date')) {
            $delivery = Deliveries::where('sales_order_number', $salesOrder->sales_order_number)->first();
            if ($delivery) {
                $delivery->update([
                    'request_delivery_date' => $request->request_delivery_date
                ]);
            }
        }

        $salesOrder->items()->delete();

        $newTotalAmount = 0;

        if (!empty($request->items)) {
            foreach ($request->items as $itemData) {
                $quantity = (float) ($itemData['quantity'] ?? 0);
                $unitPrice = (float) ($itemData['unit_price'] ?? 0);
                $itemTotal = $quantity * $unitPrice;

                $item = Item::find($itemData['item_id']);

                $salesOrder->items()->create([
                    'item_id' => $itemData['item_id'] ?? null,
                    'item_code' => $itemData['item_code'] ?? $item->item_code ?? null,
                    'item_description' => $itemData['item_description'] ?? $item->item_description ?? null,
                    'brand' => $itemData['brand'] ?? $item->brand ?? null,
                    'item_category' => $itemData['item_category'] ?? $item->item_category ?? null,
                    'quantity' => $quantity,
                    'unit' => $itemData['unit'] ?? $item->unit ?? 'Kgs',
                    'unit_price' => $unitPrice,
                    'total_amount' => $itemTotal,
                ]);

                $newTotalAmount += $itemTotal;
            }

            $firstItem = $request->items[0] ?? [];
            $firstItemModel = Item::find($firstItem['item_id'] ?? null);
            
            $salesOrder->item_description = $firstItem['item_description'] ?? $firstItemModel->item_description ?? null;
            $salesOrder->item_code = $firstItem['item_code'] ?? $firstItemModel->item_code ?? null;
            $salesOrder->brand = $firstItem['brand'] ?? $firstItemModel->brand ?? null;
            $salesOrder->item_category = $firstItem['item_category'] ?? $firstItemModel->item_category ?? null;
        }

        $salesOrder->total_amount = $newTotalAmount;
        $salesOrder->save();

        \App\Models\Activity::create([
            'user_name' => auth()->user()->name ?? 'System',
            'action' => 'Updated',
            'item' => $salesOrder->sales_order_number,
            'target' => optional($salesOrder->customer)->customer_name ?? 'N/A',
            'type' => 'Sales Order',
            'message' => 'Updated sales order: ' . $salesOrder->sales_order_number,
        ]);

        return redirect()->route('sales_orders.index')->with('success', 'Sales Order updated successfully!');
    }

    public function destroy($id)
    {
        try {
            $salesOrder = SalesOrder::findOrFail($id);

            // Capture data before deleting
            $soNumber = $salesOrder->sales_order_number;
            $customer = optional($salesOrder->customer)->customer_name ?? 'N/A';

            // Delete related items first
            $salesOrder->items()->delete();
            $salesOrder->delete();

            // ✨ Log activity ✨
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

    public function approve($id)
    {
        $salesOrder = SalesOrder::findOrFail($id);

        // Determine approver
        $approverId = auth()->check() ? auth()->id() : 40; // 👈 Default to user ID 40 ("review")

        $salesOrder->update([
            'status' => 'Approved',
            'approved_by' => $approverId,
        ]);

        \App\Models\Activity::create([
            'user_name' => auth()->user()->name ?? 'review', // fallback for activity logs
            'action' => 'Approved',
            'item' => $salesOrder->sales_order_number,
            'target' => optional($salesOrder->customer)->customer_name ?? 'N/A',
            'type' => 'Sales Order',
            'message' => 'Approved sales order: ' . $salesOrder->sales_order_number,
        ]);

        return redirect()->route('sales_orders.index')->with('success', 'Sales order approved!');
    }

    public function updateStatus(Request $request, $id)
    {
        $salesOrder = SalesOrder::with(['customer', 'items'])->findOrFail($id);
        $newStatus = $request->status;

        $updateData = ['status' => $newStatus];

        // If approved, also mark approved_by
        if (strtolower($newStatus) === 'approved') {
            $updateData['approved_by'] = auth()->id();
        } else {
            $updateData['approved_by'] = null; // reset if declined/cancelled
        }

        $salesOrder->update($updateData);

        // Create Activity for ALL statuses
        $actionMap = [
            'approved' => 'Approved',
            'declined' => 'Declined',
            'cancelled' => 'Cancelled'
        ];

        $actionText = $actionMap[strtolower($newStatus)] ?? ucfirst($newStatus);

        \App\Models\Activity::create([
            'user_name' => auth()->user()->name ?? 'System',
            'action' => $actionText,
            'item' => $salesOrder->sales_order_number,
            'target' => optional($salesOrder->customer)->customer_name ?? 'N/A',
            'type' => 'Sales Order',
            'message' => "{$actionText} sales order: " . $salesOrder->sales_order_number,
        ]);

        // Optional: Create Delivery if approved
        if (strtolower($newStatus) === 'approved') {
            $existingDelivery = Deliveries::where('sales_order_number', $salesOrder->sales_order_number)->first();
            if (!$existingDelivery) {
                $customer = $salesOrder->customer;
                Deliveries::create([
                    'sales_order_number' => $salesOrder->sales_order_number,
                    'customer_code' => $customer->customer_code ?? null,
                    'client' => $customer->customer_name ?? null,
                    'branch' => $customer->branch ?? null,
                    'sales_representative' => $salesOrder->sales_rep?? null,
                    'sales_executive' => $customer->sales_executive ?? null,
                    'po_number' => $salesOrder->po_number ?? null,
                    'request_delivery_date' => $salesOrder->request_delivery_date ?: now()->toDateString(),
                    'status' => 'Pending',
                    'quantity' => $salesOrder->items->sum('quantity'),
                    'total_amount' => $salesOrder->total_amount ?? 0,
                    'item_code' => $salesOrder->item_code,
                    'item_description' => $salesOrder->item_description,
                    'approved_by' => auth()->user()->name,
                ]);
            }
        }

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

    //PRINTLIST
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

        // ✅ Exclude pending orders
        $query->where('status', '!=', 'Pending');

        $salesOrders = $query->orderByDesc('created_at')->get();

        // ✅ Check if there are any orders to print
        if ($salesOrders->isEmpty()) {
            return back()->with('error', 'No approved sales orders found to print.');
        }

        return view('sales_orders.printlist', compact('salesOrders', 'dateFrom', 'dateTo'));
    }

    public function print($id)
    {
        $salesOrder = SalesOrder::with(['customer', 'items', 'preparer', 'approver'])->findOrFail($id);

        // ✅ Block printing if status is Pending
        if ($salesOrder->status === 'Pending') {
            return back()->with('error', 'Cannot print: Sales order is still pending approval.');
        }

        return view('sales_orders.print', compact('salesOrder'));
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class, 'sales_order_id', 'id');
    }

        public function exportExcel(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $search = $request->input('search');

        $query = SalesOrder::with(['customer', 'preparer', 'approver']);

        // 📅 Date filtering
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // 🔍 Search filtering
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

        // ✅ Exclude pending orders
        $query->where('status', '!=', 'Pending');

        $salesOrders = $query->orderByDesc('created_at')->get();

        // ✅ Check if there are any orders to export
        if ($salesOrders->isEmpty()) {
            return back()->with('error', 'No approved sales orders found to export.');
        }

        // 🗂️ Filename
        $filename = 'sales_orders_' . now()->format('Y-m-d_H-i-s') . '.csv';

        // 🧾 Proper headers for browser download
        $headers = [
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Expires'             => '0',
            'Pragma'              => 'public',
        ];

        // 📤 Streamed CSV response
        $callback = function () use ($salesOrders) {
            $file = fopen('php://output', 'w');

            // ✅ BOM for Excel UTF-8 compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header row
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

            // Data rows
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

        // Show form to add items to existing approved SO
    public function addItemsForm($id)
    {
        $salesOrder = SalesOrder::with(['customer', 'items'])->findOrFail($id);
        
        // Only allow adding items to approved orders
        if ($salesOrder->status !== 'Approved') {
            return back()->with('error', 'Can only add items to approved sales orders.');
        }
        
        $customers = Customer::all();
        $items = Item::where('approval_status', 'approved')->get();
        
        return view('sales_orders.add_items', compact('salesOrder', 'customers', 'items'));
    }

    // ✅ Store additional items to existing SO
    public function storeAdditionalItems(Request $request, $id)
    {
        $salesOrder = SalesOrder::with(['customer', 'items'])->findOrFail($id);
        
        if ($salesOrder->status !== 'Approved') {
            return back()->with('error', 'Can only add items to approved sales orders.');
        }
        
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.request_delivery_date' => 'required|date',
        ]);

        DB::beginTransaction();

        try {
            $addedItems = [];
            $newTotalAmount = 0;

            foreach ($request->items as $itemData) {
                $item = Item::find($itemData['item_id']);
                $quantity = (float) $itemData['quantity'];
                $unitPrice = (float) $itemData['price'];
                $itemTotal = $quantity * $unitPrice;
                $deliveryDate = $itemData['request_delivery_date'];
                
                // Generate delivery batch identifier (SO_NUMBER-DATE)
                $deliveryBatch = $salesOrder->sales_order_number . '-' . date('Ymd', strtotime($deliveryDate));

                $newItem = SalesOrderItem::create([
                    'sales_order_id' => $salesOrder->id,
                    'item_id' => $itemData['item_id'],
                    'item_code' => $itemData['item_code'] ?? $item->item_code ?? null,
                    'item_description' => $itemData['item_description'] ?? $item->item_description ?? null,
                    'brand' => $itemData['brand'] ?? $item->brand ?? null,
                    'item_category' => $itemData['item_category'] ?? $item->item_category ?? null,
                    'quantity' => $quantity,
                    'unit' => $itemData['unit'] ?? $item->unit ?? 'Kgs',
                    'unit_price' => $unitPrice,
                    'total_amount' => $itemTotal,
                    'delivery_batch' => $deliveryBatch,              // ✅ NEW
                    'request_delivery_date' => $deliveryDate,         // ✅ NEW
                ]);

                $addedItems[] = $newItem;
                $newTotalAmount += $itemTotal;
                
                // ✅ Create or update delivery for this batch
                $this->createOrUpdateDeliveryBatch($salesOrder, $deliveryBatch, $deliveryDate);
            }

            // Update SO total amount
            $salesOrder->total_amount += $newTotalAmount;
            $salesOrder->save();

            // Log activity
            \App\Models\Activity::create([
                'user_name' => auth()->user()->name ?? 'System',
                'action' => 'Added Items',
                'item' => $salesOrder->sales_order_number,
                'target' => $salesOrder->customer->customer_name ?? 'N/A',
                'type' => 'Sales Order',
                'message' => 'Added ' . count($addedItems) . ' item(s) to sales order: ' . $salesOrder->sales_order_number,
            ]);

            DB::commit();
            return redirect()->route('sales_orders.show', $salesOrder->id)
                ->with('success', 'Items added successfully! Delivery batches updated.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Add Items Error:', ['message' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to add items: ' . $e->getMessage());
        }
    }

    // ✅ Create or update delivery batch
    private function createOrUpdateDeliveryBatch($salesOrder, $deliveryBatch, $deliveryDate)
    {
        // Get all items for this delivery batch
        $batchItems = SalesOrderItem::where('sales_order_id', $salesOrder->id)
            ->where('delivery_batch', $deliveryBatch)
            ->get();
        
        // Check if delivery already exists for this batch
        $delivery = Deliveries::where('sales_order_number', $salesOrder->sales_order_number)
            ->where('request_delivery_date', $deliveryDate)
            ->first();
        
        $batchTotal = $batchItems->sum('total_amount');
        $batchQuantity = $batchItems->sum('quantity');
        
        if (!$delivery) {
            // Create new delivery for this batch
            $delivery = Deliveries::create([
                'sales_order_number' => $salesOrder->sales_order_number,
                'customer_code' => $salesOrder->customer->customer_code ?? null,
                'customer_name' => $salesOrder->customer->customer_name ?? null,
                'branch' => $salesOrder->customer->branch ?? null,
                'sales_representative' => $salesOrder->sales_rep ?? null,
                'sales_executive' => $salesOrder->sales_executive ?? null,
                'po_number' => $salesOrder->po_number ?? null,
                'request_delivery_date' => $deliveryDate,
                'status' => 'Pending',
                'quantity' => $batchQuantity,
                'total_amount' => $batchTotal,
                'approved_by' => auth()->user()->name ?? 'System',
            ]);
        } else {
            // Update existing delivery totals
            $delivery->update([
                'quantity' => $batchQuantity,
                'total_amount' => $batchTotal,
            ]);
        }
        
        // Sync delivery items
        foreach ($batchItems as $soItem) {
            DeliveryItem::updateOrCreate(
                [
                    'delivery_id' => $delivery->id,
                    'sales_order_item_id' => $soItem->id,
                ],
                [
                    'item_code' => $soItem->item_code,
                    'item_description' => $soItem->item_description,
                    'brand' => $soItem->brand,
                    'item_category' => $soItem->item_category,
                    'quantity' => $soItem->quantity,
                    'uom' => $soItem->unit,
                    'unit_price' => $soItem->unit_price,
                    'total_amount' => $soItem->total_amount,
                    'delivery_batch' => $deliveryBatch,
                ]
            );
        }
    }

    // ✅ View all delivery batches for a sales order
    public function viewDeliveryBatches($id)
    {
        $salesOrder = SalesOrder::with(['customer', 'items'])->findOrFail($id);
        
        // Group items by delivery batch
        $deliveryBatches = $salesOrder->items()
            ->orderBy('request_delivery_date')
            ->get()
            ->groupBy('delivery_batch');
        
        // Get deliveries for this SO
        $deliveries = Deliveries::where('sales_order_number', $salesOrder->sales_order_number)
            ->orderBy('request_delivery_date')
            ->get();
        
        return view('sales_orders.delivery_batches', compact('salesOrder', 'deliveryBatches', 'deliveries'));
    }
}