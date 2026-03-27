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

    // Admin/IT can toggle to see hidden deliveries
    $showHidden = false;
    if ($request->filled('show_hidden') && auth()->user()->isAdminUser()) {
        $query->withHidden();
        $showHidden = true;
    }

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

    $perPage = in_array((int)$request->per_page, [25, 50, 100, 250, 500]) ? (int)$request->per_page : 25;
    $deliveries = $query->paginate($perPage)->withQueryString();

    return view('deliveries.index', compact('deliveries', 'showHidden'));
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

/**
 * ✅ Create Backload Entry as Receiving Report
 * (No AR correlation - purely logistics/warehouse tracking)
 */
private function createBackloadEntry(Request $request)
{
    try {
        // Generate RR Number
        $rrNumber = $this->generateRRNumber();
        
        // Normalize empty strings to nulls
        $data = $request->all();
        foreach (['customer_name', 'customer_code', 'branch', 'tin_no', 'sales_rep', 'sales_representative', 'sales_executive', 'po_number', 'plate_no', 'sales_invoice_no'] as $field) {
            if (isset($data[$field]) && $data[$field] === '') {
                $data[$field] = null;
            }
        }
        
        // Handle attachment
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time() . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());
            $uploadPath = public_path('receiving_report_attachments');
            if (!file_exists($uploadPath)) mkdir($uploadPath, 0755, true);
            $file->move($uploadPath, $filename);
            $attachmentPath = $filename;
        }
        
        // ✅ FIXED: Use sales_representative (not sales_rep)
        $salesRep = $data['sales_representative'] ?? $data['sales_rep'] ?? null;
        
        // Create Receiving Report
        $receivingReport = \App\Models\ReceivingReport::create([
            'rr_number' => $rrNumber,
            'sales_order_number' => $data['sales_order_number'],
            'customer_name' => $data['customer_name'] ?? null,
            'customer_code' => $data['customer_code'] ?? null,
            'tin_no' => $data['tin_no'] ?? null,
            'branch' => $data['branch'] ?? null,
            'sales_representative' => $salesRep, // ✅ FIXED
            'sales_executive' => $data['sales_executive'] ?? null,
            'po_number' => $data['po_number'] ?? null,
            'plate_no' => $data['plate_no'] ?? null,
            'sales_invoice_no' => $data['sales_invoice_no'] ?? null,
            'received_by' => $data['approved_by'] ?? auth()->user()->name ?? 'System',
            'delivery_batch' => $data['delivery_batch'] ?? null,
            'delivery_type' => $data['delivery_type'] ?? 'Full',
            'additional_instructions' => $data['additional_instructions'] ?? null,
            'request_delivery_date' => $data['request_delivery_date'] ?? null,
            'status' => 'Received',
            'received_date' => now(),
            'attachment' => $attachmentPath,
            'created_by' => auth()->user()->name ?? 'System',
        ]);
        
        // Fetch SO for item details
        $salesOrder = SalesOrder::with('items')->where('sales_order_number', $data['sales_order_number'])->first();
        $soItemsMap = collect();
        if ($salesOrder) {
            foreach ($salesOrder->items as $soItem) {
                $soItemsMap->put($soItem->item_code, $soItem);
            }
        }
        
        // Create items for the receiving report
        foreach ($request->items as $itemData) {
            $itemCode = $itemData['item_code'] ?? null;
            $soItem = $soItemsMap->get($itemCode);
            $itemRecord = !$soItem && $itemCode ? Item::where('item_code', $itemCode)->first() : null;
            
            \App\Models\ReceivingReportItem::create([
                'receiving_report_id' => $receivingReport->id,
                'item_id' => $soItem?->item_id ?? $itemRecord?->id ?? null,
                'sales_order_item_id' => $soItem?->id ?? null,
                'item_code' => $itemCode,
                'item_description' => $itemData['item_description'] ?? null,
                'brand' => $soItem?->brand ?? $itemRecord?->brand ?? null,
                'item_category' => $soItem?->item_category ?? $itemRecord?->item_category ?? null,
                'quantity' => $itemData['quantity'] ?? 0,
                'original_quantity' => $itemData['original_quantity'] ?? ($itemData['quantity'] ?? 0),
                'remaining_quantity' => $itemData['remaining_quantity'] ?? 0,
                'uom' => $itemData['uom'] ?? null,
                'unit_price' => $itemData['unit_price'] ?? 0,
                'total_amount' => $itemData['total_amount'] ?? 0,
                'notes' => $itemData['notes'] ?? null,
            ]);
        }
        
        // Create activity log
        Activity::create([
            'user_name' => auth()->user()->name ?? 'System',
            'action' => 'Created',
            'item' => $rrNumber . ' - ' . ($data['customer_name'] ?? 'N/A'),
            'target' => $data['sales_order_number'] ?? 'N/A',
            'type' => 'Receiving Report',
            'message' => "Created backload receiving report: {$rrNumber}",
        ]);
        
        return response()->json([
    'success' => true,
    'message' => "Backload created successfully! RR Number: {$rrNumber}",
    'rr_number' => $rrNumber,
    'items_count' => count($request->items), // ✅ Add this
    'redirect' => route('receiving-reports.index')
]);
        
    } catch (\Exception $e) {
        Log::error('💥 Backload creation failed', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to create backload: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * ✅ Generate unique RR Number (e.g., RR-2026-001)
 */
private function generateRRNumber()
{
    $year = date('Y');
    $lastRR = \App\Models\ReceivingReport::where('rr_number', 'like', "RR-{$year}-%")
        ->orderBy('rr_number', 'desc')
        ->first();
    
    if ($lastRR) {
        $lastNumber = (int) substr($lastRR->rr_number, -3);
        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
    } else {
        $newNumber = '001';
    }
    
    return "RR-{$year}-{$newNumber}";
}

public function update(Request $request, $id)
{
    try {
        $delivery = Deliveries::findOrFail($id);

        if ($delivery->is_locked) {
            return response()->json(['success' => false, 'message' => 'This Delivery is locked and cannot be edited.'], 403);
        }

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
            'items.*.sales_order_item_id' => 'nullable|integer',
            'items.*.item_code' => 'nullable|string|max:255',
            'items.*.item_description' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.original_quantity' => 'nullable|numeric|min:0',
            'items.*.remaining_quantity' => 'nullable|numeric|min:0',
            'items.*.uom' => 'nullable|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0', // ✅ REQUIRED
            'items.*.total_amount' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:2000',
        ]);

        // Normalize empty strings
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

        $salesOrder = SalesOrder::with('items')->where('sales_order_number', $validated['sales_order_number'])->first();
        if (!$salesOrder) {
            throw new \Exception('Sales Order not found');
        }

        $soItemsMap = collect();
        foreach ($salesOrder->items as $soItem) {
            // ✅ Use sales_order_item_id as key to handle duplicate item_codes
            $soItemsMap->put($soItem->id, $soItem);
        }

        $deliveredSums = DeliveryItem::whereHas('delivery', function($q) use ($validated, $delivery) {
                $q->where('sales_order_number', $validated['sales_order_number'])
                  ->where('status', 'Delivered');
            })
            ->where('delivery_id', '!=', $delivery->id)
            ->where('quantity', '>', 0)
            ->select('sales_order_item_id', DB::raw('SUM(quantity) as total_delivered'))
            ->groupBy('sales_order_item_id')
            ->get()
            ->keyBy('sales_order_item_id');

        DeliveryItem::where('delivery_id', $delivery->id)->delete();

        // ✅ CRITICAL: Recalculate totals
        foreach ($items as $item) {
            $salesOrderItemId = $item['sales_order_item_id'] ?? null;
            $itemCode = $item['item_code'] ?? null;
            $soItem = $salesOrderItemId ? $soItemsMap->get($salesOrderItemId) : null;

            if ($soItem && $soItem->quantity > 0) {
                $originalQty = $soItem->quantity;
            } else {
                $originalQty = $item['original_quantity'] ?? ($item['quantity'] ?? 0);
            }

            $previousDelivered = $salesOrderItemId ? ($deliveredSums->get($salesOrderItemId)?->total_delivered ?? 0) : 0;
            $deliveredQty = $item['quantity'] ?? 0;
            $newTotalDelivered = $previousDelivered + $deliveredQty;
            $remainingQty = max(0, $originalQty - $newTotalDelivered);

            $itemRecord = !$soItem && $itemCode ? Item::where('item_code', $itemCode)->first() : null;

            // ✅ ALWAYS recalculate - ignore client value
            $unitPrice = $item['unit_price'] ?? 0;
            $totalAmount = round($deliveredQty * $unitPrice, 2);

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
                'unit_price' => $unitPrice,
                'total_amount' => $totalAmount, // ✅ Server-calculated only
                'delivery_batch' => $validated['delivery_batch'] ?? null,
                'notes' => $item['notes'] ?? $soItem?->note ?? null,
            ]);
        }

        // ✅ ADDED: Verify all calculations
        $this->recalculateDeliveryItemTotals($delivery->id);

        $salesOrder->fresh()->checkAndClose();

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

public function fixExistingTotals()
{
    try {
        $deliveries = Deliveries::all();
        $fixedCount = 0;
        
        foreach ($deliveries as $delivery) {
            $items = DeliveryItem::where('delivery_id', $delivery->id)->get();
            
            foreach ($items as $item) {
                $correctTotal = round(($item->quantity ?? 0) * ($item->unit_price ?? 0), 2);
                
                if (abs($item->total_amount - $correctTotal) > 0.01) {
                    Log::info('🔧 Fixing total for item', [
                        'delivery_id' => $delivery->id,
                        'dr_no' => $delivery->dr_no,
                        'item_code' => $item->item_code,
                        'old_total' => $item->total_amount,
                        'new_total' => $correctTotal,
                    ]);
                    
                    $item->update(['total_amount' => $correctTotal]);
                    $fixedCount++;
                }
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => "Fixed {$fixedCount} item total(s)",
        ]);
        
    } catch (\Exception $e) {
        Log::error('Failed to fix totals', ['error' => $e->getMessage()]);
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

/**
 * ✅ FIXED: Store Delivery - Server-side total calculation
 */
public function store(Request $request)
{
    try {
        $status = $request->input('status', 'Delivered');
        
        $validationRules = [
            'sales_order_number' => 'required|string|max:255',
            'delivery_batch' => 'nullable|string|max:255',
            'delivery_type' => 'required|string|in:Full,Partial',
            'status' => 'required|string|in:Delivered,Cancelled,Backload',
            'customer_name' => 'nullable|string|max:255',
            'customer_code' => 'nullable|string|max:255',
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
            'items.*.sales_order_item_id' => 'nullable|integer',
            'items.*.item_code' => 'nullable|string|max:255',
            'items.*.item_description' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.original_quantity' => 'nullable|numeric|min:0',
            'items.*.remaining_quantity' => 'nullable|numeric|min:0',
            'items.*.uom' => 'nullable|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0', // ✅ MAKE REQUIRED
            'items.*.total_amount' => 'nullable|numeric|min:0', // ✅ Keep nullable - we recalculate
            'items.*.notes' => 'nullable|string|max:2000',
        ];

        if ($status !== 'Backload') {
            $validationRules['dr_no'] = ['required', 'string', 'max:255', 'unique:deliveries,dr_no'];
        } else {
            $validationRules['dr_no'] = 'nullable|string|max:255';
        }

        $validated = $request->validate($validationRules);

        if ($status === 'Backload') {
            return $this->createBackloadEntry($request);
        }

        $validated['approval_status'] = 'Pending';
        $validated['created_by'] = auth()->user()->name ?? 'System';

        // Normalize empty strings
        foreach (['customer_name', 'customer_code', 'branch', 'tin_no', 'sales_rep', 'sales_representative', 'sales_executive', 'po_number', 'plate_no', 'sales_invoice_no'] as $field) {
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

        // Check user permissions
        $isApprover = auth()->user()->canApproveDeliveries();
        $validated['created_by'] = auth()->user()->name ?? 'System';

        if ($isApprover) {
            $validated['status'] = 'Delivered';
            $validated['approval_status'] = 'Approved';
            $validated['approved_by_user'] = auth()->user()->name ?? 'System';
            $validated['approved_at'] = now();
        } else {
            $validated['status'] = 'Pending';
            $validated['approval_status'] = 'Pending';
        }

        // Count existing deliveries (including pending ones)
        $existingDeliveryCount = Deliveries::where('sales_order_number', $validated['sales_order_number'])
            ->where('status', '!=', 'Cancelled')  // Exclude cancelled deliveries
            ->whereHas('items', function($q) {
                $q->where('quantity', '>', 0);
            })
            ->count();

        if ($existingDeliveryCount === 0) {
            $validated['delivery_batch'] = ($validated['delivery_type'] === 'Partial') ? 'Batch 1' : 'Full Delivery';
        } else {
            $validated['delivery_batch'] = 'Batch ' . ($existingDeliveryCount + 1);
        }

        // Fetch SO
        $salesOrder = SalesOrder::with('items')->where('sales_order_number', $validated['sales_order_number'])->first();
        if (!$salesOrder) {
            throw new \Exception('Sales Order not found');
        }

        $soItemsMap = collect();
        foreach ($salesOrder->items as $soItem) {
            // ✅ Use sales_order_item_id as key to handle duplicate item_codes
            $soItemsMap->put($soItem->id, $soItem);
        }

        // Get delivered sums
        $deliveredSums = DeliveryItem::whereHas('delivery', function($q) use ($validated) {
                $q->where('sales_order_number', $validated['sales_order_number'])
                  ->where('approval_status', 'Approved')
                  ->where('status', 'Delivered');
            })
            ->where('quantity', '>', 0)
            ->select('sales_order_item_id', DB::raw('SUM(quantity) as total_delivered'))
            ->groupBy('sales_order_item_id')
            ->get()
            ->keyBy('sales_order_item_id');

        // Create delivery
        $delivery = Deliveries::create($validated);

        // Sync delivery date to SO
        if ($validated['request_delivery_date']) {
            $salesOrder = SalesOrder::where('sales_order_number', $validated['sales_order_number'])->first();
            
            if ($salesOrder && $salesOrder->request_delivery_date != $validated['request_delivery_date']) {
                $salesOrder->update(['request_delivery_date' => $validated['request_delivery_date']]);
                SalesOrderItem::where('sales_order_id', $salesOrder->id)
                    ->update(['request_delivery_date' => $validated['request_delivery_date']]);
            }
        }

        // ✅ CRITICAL: Always recalculate total_amount server-side
        foreach ($items as $item) {
            $salesOrderItemId = $item['sales_order_item_id'] ?? null;
            $itemCode = $item['item_code'] ?? null;
            $soItem = $salesOrderItemId ? $soItemsMap->get($salesOrderItemId) : null;

            $originalQty = $soItem ? $soItem->quantity : ($item['original_quantity'] ?? ($item['quantity'] ?? 0));
            $previousDelivered = $salesOrderItemId ? ($deliveredSums->get($salesOrderItemId)?->total_delivered ?? 0) : 0;
            $deliveredQty = $item['quantity'] ?? 0;
            $newTotalDelivered = $previousDelivered + $deliveredQty;
            $remainingQty = max(0, $originalQty - $newTotalDelivered);

            $itemRecord = !$soItem && $itemCode ? Item::where('item_code', $itemCode)->first() : null;

            // ✅ ALWAYS calculate server-side - IGNORE client value
            $unitPrice = $item['unit_price'] ?? 0;
            $totalAmount = round($deliveredQty * $unitPrice, 2); // ✅ Round to 2 decimals

            // ✅ Log if client sent different value
            if (isset($item['total_amount']) && abs($item['total_amount'] - $totalAmount) > 0.01) {
                Log::warning('⚠️ Client sent incorrect total_amount', [
                    'item_code' => $itemCode,
                    'client_sent' => $item['total_amount'],
                    'server_calculated' => $totalAmount,
                    'quantity' => $deliveredQty,
                    'unit_price' => $unitPrice,
                ]);
            }

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
                'unit_price' => $unitPrice,
                'total_amount' => $totalAmount, // ✅ Use ONLY server-calculated value
                'delivery_batch' => $validated['delivery_batch'],
                'notes' => $item['notes'] ?? $soItem?->note ?? null,      
            ]);
        }

        // ✅ ADDED: Double-check all totals after creation
        $this->recalculateDeliveryItemTotals($delivery->id);

        if ($isApprover) {
            $salesOrder->fresh()->checkAndClose();
        }

        Activity::create([
            'user_name' => auth()->user()->name ?? 'System',
            'action' => 'Created',
            'item' => $delivery->dr_no . ' - ' . ($delivery->customer_name ?? 'N/A'),
            'target' => $delivery->sales_order_number ?? 'N/A',
            'type' => 'Delivery',
            'message' => $isApprover 
                ? "Created and auto-approved delivery: {$delivery->dr_no} ({$delivery->delivery_batch})"
                : "Created delivery for approval: {$delivery->dr_no} ({$delivery->delivery_batch}) - Status: Pending Approval",
        ]);

        $message = $isApprover 
            ? "Delivery created and approved successfully! Batch: {$delivery->delivery_batch}"
            : "Delivery created successfully! Status: Pending Approval. Batch: {$delivery->delivery_batch}";

        return response()->json([
            'success' => true,
            'message' => $message,
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

public function quickUpdate(Request $request, $id)
{
    try {
        // ✅ Load delivery with fresh items
        $delivery = Deliveries::with('items')->findOrFail($id);

        if ($delivery->is_pulled_out) {
            return response()->json(['success' => false, 'message' => 'Cannot edit pulled out delivery'], 400);
        }

        if ($delivery->approval_status === 'Approved') {
            return response()->json([
                'success' => false, 
                'message' => 'Approved deliveries are locked and cannot be edited. Only pullout is available for approved deliveries.'
            ], 403);
        }

        $canApprove = auth()->user()->canApproveDeliveries();

        if (!$canApprove) {
            if (!$delivery->edit_approved) {
                return response()->json([
                    'success' => false,
                    'message' => 'You need edit approval to modify this pending delivery.'
                ], 403);
            }
        }

        // ✅ CRITICAL: Build validation rules - items are REQUIRED
        $validationRules = [
            'request_delivery_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.delivery_item_id' => 'nullable|integer',  // ✅ Primary identifier
            'items.*.sales_order_item_id' => 'nullable|integer',
            'items.*.item_code' => 'required|string',
            'items.*.item_description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.original_quantity' => 'required|numeric|min:0',
            'items.*.already_delivered' => 'nullable|numeric|min:0',
            'items.*.uom' => 'required|string',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string',
        ];

        if ($request->has('dr_no')) {
            $validationRules['dr_no'] = ['required', 'string', 'max:255', Rule::unique('deliveries', 'dr_no')->ignore($delivery->id)];
        }

        if ($request->has('po_number')) {
            $validationRules['po_number'] = 'nullable|string|max:255';
        }

        if ($request->has('plate_no')) {
            $validationRules['plate_no'] = 'nullable|string|max:255';
        }

        if ($request->has('sales_invoice_no')) {
            $salesInvoiceNo = $request->input('sales_invoice_no');
            if (!empty($salesInvoiceNo) && trim($salesInvoiceNo) !== '') {
                if ($salesInvoiceNo !== $delivery->sales_invoice_no) {
                    $validationRules['sales_invoice_no'] = [
                        'nullable', 
                        'string', 
                        'max:255', 
                        Rule::unique('deliveries', 'sales_invoice_no')->ignore($delivery->id)
                    ];
                }
            }
        }

        Log::info('🔍 Quick Update Request', [
            'delivery_id' => $id,
            'request_data' => $request->all(),
            'items_count' => count($request->input('items', [])),
        ]);

        try {
            $validated = $request->validate($validationRules);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ Validation Failed', [
                'errors' => $e->errors(),
                'request' => $request->all(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . implode(', ', array_map(fn($err) => implode(', ', $err), $e->errors())),
                'errors' => $e->errors()
            ], 422);
        }

        // ✅ Start database transaction
        DB::beginTransaction();

        try {
            // ✅ Update delivery fields
            $updateData = [
                'request_delivery_date' => $validated['request_delivery_date'],
            ];

            if (isset($validated['dr_no'])) {
                $updateData['dr_no'] = $validated['dr_no'];
            }

            if (isset($validated['po_number'])) {
                $updateData['po_number'] = $validated['po_number'];
            }

            if (isset($validated['plate_no'])) {
                $updateData['plate_no'] = $validated['plate_no'];
            }

            if (isset($validated['sales_invoice_no'])) {
                $updateData['sales_invoice_no'] = $validated['sales_invoice_no'];
            }

            Log::info('📝 Updating delivery', [
                'delivery_id' => $id,
                'update_data' => $updateData
            ]);

            $delivery->update($updateData);

            // ✅ Sync delivery date back to Sales Order
            if ($validated['request_delivery_date']) {
                $salesOrder = SalesOrder::where('sales_order_number', $delivery->sales_order_number)->first();
                
                if ($salesOrder) {
                    $salesOrder->update(['request_delivery_date' => $validated['request_delivery_date']]);
                    
                    SalesOrderItem::where('sales_order_id', $salesOrder->id)
                        ->update(['request_delivery_date' => $validated['request_delivery_date']]);
                    
                    Log::info('✅ Synced delivery date back to SO', [
                        'so_number' => $delivery->sales_order_number,
                        'new_date' => $validated['request_delivery_date'],
                    ]);
                }
            }

            // ✅ CRITICAL FIX: Update items with direct database query
            if (isset($validated['items'])) {
                $updatedCount = 0;

                foreach ($validated['items'] as $itemData) {
                    $quantity = $itemData['quantity'];
                    $unitPrice = $itemData['unit_price'];

                    // ✅ Calculate total amount server-side
                    $totalAmount = round($quantity * $unitPrice, 2);

                    $remaining = max(0, $itemData['original_quantity'] - ($itemData['already_delivered'] ?? 0) - $quantity);

                    // ✅ CRITICAL: Use delivery_item_id to identify the exact delivery item
                    $query = DeliveryItem::where('delivery_id', $delivery->id);

                    if (!empty($itemData['delivery_item_id'])) {
                        // Use delivery_item_id (primary key) for precise identification
                        $query->where('id', $itemData['delivery_item_id']);
                    } elseif (!empty($itemData['sales_order_item_id'])) {
                        // Fallback to sales_order_item_id (may update multiple items if duplicates exist)
                        $query->where('sales_order_item_id', $itemData['sales_order_item_id']);
                    } else {
                        // Last resort: item_code for custom items
                        $query->where('item_code', $itemData['item_code'])
                              ->whereNull('sales_order_item_id');
                    }

                    $updated = $query->update([
                        'quantity' => $quantity,
                        'remaining_quantity' => $remaining,
                        'total_amount' => $totalAmount,
                        'updated_at' => now(),
                    ]);

                    if ($updated > 0) {
                        $updatedCount++;
                        Log::info('📦 Updated delivery item', [
                            'delivery_item_id' => $itemData['delivery_item_id'] ?? 'N/A',
                            'sales_order_item_id' => $itemData['sales_order_item_id'] ?? 'N/A',
                            'item_code' => $itemData['item_code'],
                            'new_quantity' => $quantity,
                            'unit_price' => $unitPrice,
                            'calculated_total' => $totalAmount,
                            'rows_affected' => $updated,
                        ]);
                    } else {
                        Log::warning('⚠️ No rows updated for item', [
                            'delivery_id' => $delivery->id,
                            'sales_order_item_id' => $itemData['sales_order_item_id'] ?? 'N/A',
                            'item_code' => $itemData['item_code'],
                        ]);
                    }
                }
                
                Log::info('✅ Total items updated', [
                    'delivery_id' => $id,
                    'updated_count' => $updatedCount,
                    'total_items' => count($validated['items']),
                ]);
            }

            // ✅ Reset edit flags if this was an approved edit
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

            // ✅ Commit transaction
            DB::commit();

            Activity::create([
                'user_name' => auth()->user()->name ?? 'System',
                'action' => 'Updated',
                'item' => $delivery->dr_no,
                'target' => $delivery->sales_order_number,
                'type' => 'Delivery',
                'message' => "Updated delivery: {$delivery->dr_no}",
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Delivery updated successfully!'
            ]);

        } catch (\Exception $e) {
            // ✅ Rollback transaction on error
            DB::rollBack();
            throw $e;
        }

    } catch (\Exception $e) {
        Log::error('💥 Quick update failed', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'delivery_id' => $id ?? null,
            'request_data' => $request->all(),
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

    // ✅ Fetch all SO items (exclude only explicitly cancelled items, include NULL batch_status)
    $soItems = SalesOrderItem::where('sales_order_id', $soExists->id)
        ->where(function($q) {
            $q->where('batch_status', '!=', 'Cancelled')
              ->orWhereNull('batch_status');
        })
        ->with('item')
        ->get();

    if ($soItems->isEmpty()) {
        return response()->json(['error' => 'No items found in this Sales Order.'], 404);
    }

    $requestDeliveryDate = null;
    if ($soItems->first() && $soItems->first()->request_delivery_date) {
        try {
            $requestDeliveryDate = Carbon::parse($soItems->first()->request_delivery_date)->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning('Failed to parse SO item date', ['date' => $soItems->first()->request_delivery_date]);
        }
    } elseif ($soExists->request_delivery_date) {
        try {
            $requestDeliveryDate = Carbon::parse($soExists->request_delivery_date)->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning('Failed to parse SO date', ['date' => $soExists->request_delivery_date]);
        }
    }

    Log::info('📅 Delivery date formatting', [
        'so_number' => $soNumber,
        'raw_so_date' => $soExists->request_delivery_date,
        'raw_item_date' => $soItems->first()?->request_delivery_date,
        'formatted_date' => $requestDeliveryDate
    ]);

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

    // ✅ FIXED: Group by sales_order_item_id to handle duplicate item_codes correctly
    $deliveredSums = $deliveredQuery
        ->select('sales_order_item_id', DB::raw('SUM(quantity) as total_delivered'))
        ->groupBy('sales_order_item_id')
        ->get()
        ->keyBy('sales_order_item_id');

    // ✅ Build items list and check if anything remains to deliver
    $items = [];
    $hasRemainingItems = false;

    foreach ($soItems as $soItem) {
        $originalQty = $soItem->quantity ?? 0;
        // ✅ FIXED: Use sales_order_item_id to lookup delivered quantities (handles duplicate item_codes)
        $alreadyDelivered = $deliveredSums->get($soItem->id)?->total_delivered ?? 0;
        $remainingAvailable = $originalQty - $alreadyDelivered;

        // ✅ For edit mode: show ALL items including those with 0 quantity
        if ($isEditMode && $delivery) {
            // ✅ FIXED: Use sales_order_item_id to find the correct delivery item (handles duplicate item_codes)
            $existingDeliveryItem = $delivery->items->firstWhere('sales_order_item_id', $soItem->id);

            $deliveredQty = $existingDeliveryItem ? ($existingDeliveryItem->quantity ?? 0) : 0;
            $notes = $existingDeliveryItem ? ($existingDeliveryItem->notes ?? $soItem->note ?? null) : ($soItem->note ?? null);

            $items[] = [
                'sales_order_item_id' => $soItem->id, // ✅ CRITICAL: Include SO item ID to keep items unique
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
            // ✅ For new deliveries, ONLY show items with remaining quantity > 0
            // Skip fully delivered items (remaining = 0) or over-delivered items (remaining < 0)
            if ($remainingAvailable <= 0) {
                continue; // Skip this item - nothing left to deliver
            }

            // Item has remaining quantity, so mark that we have items to deliver
            $hasRemainingItems = true;

            $items[] = [
                'sales_order_item_id' => $soItem->id, // ✅ CRITICAL: Include SO item ID to keep items unique
                'item_code' => $soItem->item_code,
                'item_description' => $soItem->item_description ?? $soItem->item->item_description ?? '',
                'brand' => $soItem->brand ?? $soItem->item?->brand ?? '',
                'item_category' => $soItem->item_category ?? $soItem->item?->item_category ?? '',
                'quantity' => $remainingAvailable, // Default to remaining quantity for new delivery
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
        $willAutoApprove = auth()->user()->canApproveDeliveries();
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
        'will_auto_approve' => $willAutoApprove,
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

public function approve($id)
{
    try {
        if (!\App\Helpers\RoleHelper::canApproveDeliveries()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to approve deliveries'], 403);
        }

        $delivery = Deliveries::with('items')->findOrFail($id);

        if ($delivery->is_locked) {
            return response()->json(['success' => false, 'message' => 'This Delivery is locked and cannot be modified.'], 403);
        }

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

private function recalculateDeliveryItemTotals($deliveryId)
{
    $items = DeliveryItem::where('delivery_id', $deliveryId)->get();
    
    foreach ($items as $item) {
        $correctTotal = ($item->quantity ?? 0) * ($item->unit_price ?? 0);
        
        // Only update if there's a mismatch
        if ($item->total_amount != $correctTotal) {
            Log::warning('⚠️ Total amount mismatch detected', [
                'delivery_id' => $deliveryId,
                'item_code' => $item->item_code,
                'stored_total' => $item->total_amount,
                'correct_total' => $correctTotal,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
            ]);
            
            $item->update(['total_amount' => $correctTotal]);
        }
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

        if ($delivery->is_locked) {
            return response()->json(['success' => false, 'message' => 'This Delivery is locked and cannot be modified.'], 403);
        }

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
        $canApprove = auth()->user()->canApproveDeliveries();
        
        if (!$canApprove) {
            // Creators need edit approval even for pending deliveries
            if (!$delivery->edit_approved) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Please request edit permission first for this pending delivery.'
                ], 403);
            }
        }

        // Map SO items for comparison - ✅ Use sales_order_item_id as key
        $soItemsMap = collect();
        if ($delivery->salesOrder && $delivery->salesOrder->items) {
            foreach ($delivery->salesOrder->items as $soItem) {
                $soItemsMap->put($soItem->id, $soItem);
            }
        }

        // Get already delivered quantities (excluding this delivery) - ✅ Group by sales_order_item_id
        $deliveredSums = DeliveryItem::whereHas('delivery', function($q) use ($delivery) {
                $q->where('sales_order_number', $delivery->sales_order_number)
                  ->where('approval_status', 'Approved')
                  ->where('status', 'Delivered')
                  ->where('id', '!=', $delivery->id);
            })
            ->select('sales_order_item_id', DB::raw('SUM(quantity) as total_delivered'))
            ->groupBy('sales_order_item_id')
            ->get()
            ->keyBy('sales_order_item_id');

        // Prepare items data
        $items = [];
        foreach ($delivery->items as $item) {
            // ✅ Use sales_order_item_id to find the correct SO item
            $soItem = $item->sales_order_item_id ? $soItemsMap->get($item->sales_order_item_id) : null;
            $originalQty = $soItem ? $soItem->quantity : ($item->original_quantity ?? 0);
            $alreadyDelivered = $item->sales_order_item_id ? ($deliveredSums->get($item->sales_order_item_id)?->total_delivered ?? 0) : 0;

            $items[] = [
                'delivery_item_id' => $item->id,  // ✅ CRITICAL: Unique identifier for THIS delivery item
                'sales_order_item_id' => $item->sales_order_item_id,
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

        // ✅ FIX: Format the date properly - use the delivery's date, NOT the current date
        $requestDeliveryDate = null;
        if ($delivery->request_delivery_date) {
            // Ensure it's formatted as Y-m-d
            $requestDeliveryDate = Carbon::parse($delivery->request_delivery_date)->format('Y-m-d');
        } elseif ($delivery->salesOrder && $delivery->salesOrder->request_delivery_date) {
            // Fallback to SO date if delivery date is null
            $requestDeliveryDate = Carbon::parse($delivery->salesOrder->request_delivery_date)->format('Y-m-d');
        }

        Log::info('📅 getEditData - Date Information', [
            'delivery_id' => $id,
            'delivery_date' => $delivery->request_delivery_date,
            'formatted_date' => $requestDeliveryDate,
            'so_date' => $delivery->salesOrder?->request_delivery_date ?? 'N/A'
        ]);

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
            'request_delivery_date' => $requestDeliveryDate, // ✅ FIXED: Use the properly formatted date
            'po_image_url' => $poImageUrl,
            'po_image_name' => $poImageName,
            'items' => $items,
        ]);
    } catch (\Exception $e) {
        Log::error('Get edit data failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json(['success' => false, 'message' => 'Failed to load delivery data'], 500);
    }
}

/**
 * ✅ Request edit - Only for PENDING deliveries
 */
public function requestEdit($id)
{
    try {
        // Only users who can edit (but not approve) need to request edit
        if (auth()->user()->canApproveDeliveries()) {
            return response()->json([
                'success' => false,
                'message' => 'Approvers do not need to request edit permission.'
            ], 400);
        }
        if (!auth()->user()->canPerformInModule('can_edit', 'deliveries')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
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

        if ($delivery->is_locked) {
            return response()->json(['success' => false, 'message' => 'This Delivery is locked and cannot be modified.'], 403);
        }

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
        if (!auth()->user()->canApproveDeliveries()) {
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
        // Admin can view hidden deliveries too
        $query = auth()->user()->isAdminUser()
            ? Deliveries::withHidden()
            : Deliveries::query();

        $delivery = $query->with(['items','salesOrder'])->findOrFail($id);
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

                    // Map SO items by ID for comparison (handles duplicate item_codes)
                    $soItemsMap = collect();
                    if ($delivery->salesOrder && $delivery->salesOrder->items) {
                        $soItemsMap = $delivery->salesOrder->items->keyBy('id');
                    }

                    $deliveryTotal = 0;

                    if ($delivery->items && $delivery->items->count() > 0) {
                        foreach ($delivery->items as $item) {
                            $soItem = $soItemsMap->get($item->sales_order_item_id);
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

                    // Map SO items by ID (handles duplicate item_codes)
                    $soItemsMap = collect();
                    if ($delivery->salesOrder && $delivery->salesOrder->items) {
                        $soItemsMap = $delivery->salesOrder->items->keyBy('id');
                    }

                    $grandTotal = 0;

                    if ($delivery->items && $delivery->items->count() > 0) {
                        foreach ($delivery->items as $item) {
                            $soItem = $soItemsMap->get($item->sales_order_item_id);
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

/**
 * Recalculate all delivery item totals (fixes decimal truncation issues)
 * This method recalculates total_amount for all delivery items based on quantity * unit_price
 */
public function recalculateAllTotals(Request $request)
{
    try {
        // Check authorization - only admins and IT can run this
        if (!auth()->user()->isAdminUser()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only Admin and IT users can recalculate totals.'
            ], 403);
        }

        $deliveryId = $request->input('delivery_id');

        if ($deliveryId) {
            // Recalculate for a specific delivery
            $delivery = Deliveries::findOrFail($deliveryId);
            $updatedCount = $this->recalculateSingleDelivery($deliveryId);

            Log::info('🔄 Recalculated totals for single delivery', [
                'delivery_id' => $deliveryId,
                'dr_no' => $delivery->dr_no,
                'updated_items' => $updatedCount,
                'user' => auth()->user()->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully recalculated {$updatedCount} item(s) for delivery {$delivery->dr_no}",
                'updated_count' => $updatedCount,
            ]);
        } else {
            // Recalculate for ALL deliveries
            $allItems = DeliveryItem::with('delivery')->get();
            $updatedCount = 0;
            $deliveriesAffected = [];

            foreach ($allItems as $item) {
                $correctTotal = round(($item->quantity ?? 0) * ($item->unit_price ?? 0), 2);

                // Only update if there's a mismatch
                if (abs($item->total_amount - $correctTotal) > 0.01) {
                    $oldTotal = $item->total_amount;
                    $item->update(['total_amount' => $correctTotal]);
                    $updatedCount++;

                    if (!in_array($item->delivery_id, $deliveriesAffected)) {
                        $deliveriesAffected[] = $item->delivery_id;
                    }

                    Log::info('✅ Fixed total amount', [
                        'delivery_id' => $item->delivery_id,
                        'dr_no' => $item->delivery?->dr_no,
                        'item_code' => $item->item_code,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'old_total' => $oldTotal,
                        'new_total' => $correctTotal,
                    ]);
                }
            }

            Activity::create([
                'user_name' => auth()->user()->name,
                'action' => 'System Recalculation',
                'item' => 'All Deliveries',
                'target' => "{$updatedCount} items across " . count($deliveriesAffected) . " deliveries",
                'type' => 'Delivery',
                'message' => "Recalculated total amounts for {$updatedCount} delivery items",
            ]);

            Log::info('🔄 Bulk recalculation completed', [
                'total_items_updated' => $updatedCount,
                'deliveries_affected' => count($deliveriesAffected),
                'user' => auth()->user()->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully recalculated {$updatedCount} item(s) across " . count($deliveriesAffected) . " deliveries",
                'updated_count' => $updatedCount,
                'deliveries_affected' => count($deliveriesAffected),
            ]);
        }

    } catch (\Exception $e) {
        Log::error('💥 Recalculation failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to recalculate totals: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Helper method to recalculate a single delivery's totals
 */
private function recalculateSingleDelivery($deliveryId)
{
    $items = DeliveryItem::where('delivery_id', $deliveryId)->get();
    $updatedCount = 0;

    foreach ($items as $item) {
        $correctTotal = round(($item->quantity ?? 0) * ($item->unit_price ?? 0), 2);

        if (abs($item->total_amount - $correctTotal) > 0.01) {
            $item->update(['total_amount' => $correctTotal]);
            $updatedCount++;
        }
    }

    return $updatedCount;
}

/**
 * ✅ Repair delivery items that have duplicate item_codes in the same SO
 * This fixes the sales_order_item_id linkage for proper tracking
 */
public function repairDuplicateItemCodes(Request $request)
{
    if (!auth()->user()->isAdminUser()) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
    }

    $soNumber = $request->input('so_number');

    try {
        DB::beginTransaction();

        $query = Deliveries::with(['items', 'salesOrder.items']);

        if ($soNumber) {
            $query->where('sales_order_number', $soNumber);
        }

        $deliveries = $query->get();
        $fixedCount = 0;
        $details = [];

        foreach ($deliveries as $delivery) {
            if (!$delivery->salesOrder || !$delivery->salesOrder->items) {
                continue;
            }

            // Get SO items - check for duplicates
            $soItems = $delivery->salesOrder->items;
            $itemCodeGroups = $soItems->groupBy('item_code');

            // Find item_codes that appear multiple times in SO
            $duplicateCodes = $itemCodeGroups->filter(fn($group) => $group->count() > 1)->keys();

            if ($duplicateCodes->isEmpty()) {
                continue; // No duplicates in this SO
            }

            // Process delivery items that have duplicate item_codes
            foreach ($delivery->items as $deliveryItem) {
                if (!$duplicateCodes->contains($deliveryItem->item_code)) {
                    continue; // Not a duplicate code
                }

                // Get all SO items with this item_code
                $matchingSoItems = $soItems->where('item_code', $deliveryItem->item_code)->values();

                if ($matchingSoItems->count() <= 1) {
                    continue;
                }

                // If delivery item already has sales_order_item_id, check if it's valid
                if ($deliveryItem->sales_order_item_id) {
                    $validId = $matchingSoItems->pluck('id')->contains($deliveryItem->sales_order_item_id);
                    if ($validId) {
                        continue; // Already has valid linkage
                    }
                }

                // Try to match by quantity or position
                $matchedSoItem = null;

                // First try: exact quantity match
                foreach ($matchingSoItems as $soItem) {
                    if (abs($soItem->quantity - $deliveryItem->original_quantity) < 0.01) {
                        // Check if this SO item is already used by another delivery item
                        $alreadyUsed = $delivery->items
                            ->where('id', '!=', $deliveryItem->id)
                            ->where('sales_order_item_id', $soItem->id)
                            ->count() > 0;

                        if (!$alreadyUsed) {
                            $matchedSoItem = $soItem;
                            break;
                        }
                    }
                }

                // Second try: match by position in the list
                if (!$matchedSoItem) {
                    $deliveryItemsWithSameCode = $delivery->items
                        ->where('item_code', $deliveryItem->item_code)
                        ->values();

                    $position = $deliveryItemsWithSameCode->search(fn($item) => $item->id === $deliveryItem->id);

                    if ($position !== false && isset($matchingSoItems[$position])) {
                        $matchedSoItem = $matchingSoItems[$position];
                    }
                }

                // Update the delivery item
                if ($matchedSoItem) {
                    $oldId = $deliveryItem->sales_order_item_id;
                    $deliveryItem->update([
                        'sales_order_item_id' => $matchedSoItem->id,
                        'original_quantity' => $matchedSoItem->quantity,
                    ]);

                    $fixedCount++;
                    $details[] = [
                        'delivery_id' => $delivery->id,
                        'dr_no' => $delivery->dr_no,
                        'item_code' => $deliveryItem->item_code,
                        'old_so_item_id' => $oldId,
                        'new_so_item_id' => $matchedSoItem->id,
                        'so_item_qty' => $matchedSoItem->quantity,
                    ];

                    Log::info('✅ Fixed delivery item linkage', [
                        'delivery_id' => $delivery->id,
                        'delivery_item_id' => $deliveryItem->id,
                        'item_code' => $deliveryItem->item_code,
                        'old_so_item_id' => $oldId,
                        'new_so_item_id' => $matchedSoItem->id,
                    ]);
                }
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => "Fixed {$fixedCount} delivery item(s) with duplicate item codes",
            'fixed_count' => $fixedCount,
            'details' => $details,
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('❌ Failed to repair duplicate item codes', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to repair: ' . $e->getMessage(),
        ], 500);
    }
}

/**
 * ✅ Recalculate remaining quantities for all delivery items in an SO
 */
public function recalculateSODeliveries(Request $request)
{
    if (!auth()->user()->isAdminUser()) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
    }

    $soNumber = $request->input('so_number');
    if (!$soNumber) {
        return response()->json(['success' => false, 'message' => 'SO number required'], 400);
    }

    try {
        DB::beginTransaction();

        $salesOrder = SalesOrder::with('items')->where('sales_order_number', $soNumber)->first();
        if (!$salesOrder) {
            return response()->json(['success' => false, 'message' => 'SO not found'], 404);
        }

        // Create SO items map by ID
        $soItemsMap = $salesOrder->items->keyBy('id');

        // Get all deliveries for this SO
        $deliveries = Deliveries::with('items')
            ->where('sales_order_number', $soNumber)
            ->where('status', 'Delivered')
            ->orderBy('created_at', 'asc')
            ->get();

        // Track cumulative delivered per SO item
        $cumulativeDelivered = [];
        $updatedCount = 0;

        foreach ($deliveries as $delivery) {
            foreach ($delivery->items as $item) {
                $soItemId = $item->sales_order_item_id;

                if (!$soItemId) {
                    continue;
                }

                $soItem = $soItemsMap->get($soItemId);
                if (!$soItem) {
                    continue;
                }

                // Initialize cumulative tracking
                if (!isset($cumulativeDelivered[$soItemId])) {
                    $cumulativeDelivered[$soItemId] = 0;
                }

                // Calculate what this item's remaining should be
                $originalQty = $soItem->quantity;
                $previousDelivered = $cumulativeDelivered[$soItemId];
                $thisDeliveryQty = $item->quantity ?? 0;
                $newTotalDelivered = $previousDelivered + $thisDeliveryQty;
                $newRemaining = max(0, $originalQty - $newTotalDelivered);

                // Update if different
                if (abs($item->original_quantity - $originalQty) > 0.01 ||
                    abs($item->remaining_quantity - $newRemaining) > 0.01) {

                    $item->update([
                        'original_quantity' => $originalQty,
                        'remaining_quantity' => $newRemaining,
                    ]);
                    $updatedCount++;
                }

                // Track cumulative for next delivery
                $cumulativeDelivered[$soItemId] = $newTotalDelivered;
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => "Recalculated {$updatedCount} delivery item(s) for {$soNumber}",
            'updated_count' => $updatedCount,
            'cumulative_delivered' => $cumulativeDelivered,
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Failed: ' . $e->getMessage(),
        ], 500);
    }
}

    // ===================== HIDE / UNHIDE DR =====================

    public function hide(Request $request, $id)
    {
        if (!auth()->user()->isAdminUser()) {
            abort(403, 'Unauthorized');
        }

        $request->validate(['hidden_reason' => 'required|string|max:500']);

        $delivery = Deliveries::findOrFail($id);
        $delivery->update([
            'is_hidden'     => true,
            'hidden_by'     => auth()->user()->name,
            'hidden_at'     => now(),
            'hidden_reason' => $request->hidden_reason,
        ]);

        return redirect()->route('deliveries.index')->with('success', "DR {$delivery->dr_no} has been hidden.");
    }

    public function unhide($id)
    {
        if (!auth()->user()->isAdminUser()) {
            abort(403, 'Unauthorized');
        }

        $delivery = Deliveries::withHidden()->findOrFail($id);
        $delivery->update([
            'is_hidden'     => false,
            'hidden_by'     => null,
            'hidden_at'     => null,
            'hidden_reason' => null,
        ]);

        return redirect()->route('deliveries.index', ['show_hidden' => 1])->with('success', "DR {$delivery->dr_no} has been restored.");
    }

}