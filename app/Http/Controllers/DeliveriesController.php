<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Deliveries;
use App\Models\DeliveryItem;
use App\Models\SalesOrder;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Activity;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DeliveriesExport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule; 

class DeliveriesController extends Controller
{
    // INDEX 
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
                ->orWhere('customer_code', 'like', '%' . $request->search . '%')
                ->orWhere('customer_name', 'like', '%' . $request->search . '%')
                ->orWhereHas('salesOrder', function($sq) use ($request) {
                    $sq->where('customer_name', 'like', '%' . $request->search . '%');
                })
                ->orWhereHas('salesOrder.customer', function($cq) use ($request) {
                    $cq->where('customer_name', 'like', '%' . $request->search . '%');
                });
            });
        }

        $deliveries = $query->get(); // ✅ no groupBy needed

        return view('deliveries.index', compact('deliveries'));
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
            'salesOrder.items.item',  // ✅ Load SO items for qty comparison
            'items.item'              // ✅ Load delivery items with item details
        ])->findOrFail($id);
        
        return view('deliveries.print', compact('delivery'));
    }

    // 👁️ Show single delivery
    public function show($id)
    {
        $delivery = Deliveries::with(['items','salesOrder'])->findOrFail($id);
        return view('deliveries.show', compact('delivery'));
    }

    // ➕ Create form
    public function create()
    {
        $salesOrders = SalesOrder::where('status', 'Approved')->get();
        return view('deliveries.create', compact('salesOrders'));
    }

    // ✅ UPDATED STORE METHOD - Now saves item_id, brand, item_category
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'sales_order_number' => 'required|string|max:255',
                'dr_no' => 'nullable|string|max:255',
                'customer_name' => 'nullable|string|max:255',
                'tin' => 'nullable|string|max:255',
                'branch' => 'nullable|string|max:255',
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
                'items.*.uom' => 'nullable|string|max:50',
                'items.*.unit_price' => 'nullable|numeric|min:0',
                'items.*.total_amount' => 'required|numeric|min:0',
            ]);

            // Clean empty strings to null
            foreach (['customer_code', 'customer_name', 'branch', 'tin', 'sales_representative', 'sales_executive', 'po_number', 'plate_no', 'sales_invoice_no'] as $field) {
                if (isset($validated[$field]) && $validated[$field] === '') {
                    $validated[$field] = null;
                }
            }

            // Handle file upload
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $filename = time() . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());
                
                $uploadPath = public_path('delivery_images');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                
                $file->move($uploadPath, $filename);
                $validated['attachment'] = $filename;
            }

            $items = $validated['items'];
            unset($validated['items']);

            $delivery = Deliveries::create($validated);

            // ✅ Get sales order items for reference
            $salesOrder = SalesOrder::with('items.item')->where('sales_order_number', $validated['sales_order_number'])->first();
            $soItemsMap = $salesOrder ? $salesOrder->items->keyBy('item_code') : collect();

            // ✅ Create delivery items with full item details
            foreach ($items as $item) {
                $itemCode = $item['item_code'] ?? null;
                
                // ✅ Try to get item details from sales order items first
                $soItem = $soItemsMap->get($itemCode);
                
                // ✅ Fallback to items table if not in SO
                $itemRecord = null;
                if (!$soItem && $itemCode) {
                    $itemRecord = Item::where('item_code', $itemCode)->first();
                }

               DeliveryItem::create([
                    'delivery_id' => $delivery->id,
                    'item_id' => $soItem?->item_id ?? $itemRecord?->id ?? null,
                    'item_code' => $itemCode,
                    'item_description' => $item['item_description'] ?? null,
                    'brand' => $soItem?->brand ?? $itemRecord?->brand ?? null,
                    'item_category' => $soItem?->item_category ?? $itemRecord?->item_category ?? null,
                    'quantity' => $item['quantity'],
                    'uom' => $item['uom'] ?? null,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'total_amount' => $item['total_amount'],
                ]);
            }

            Log::info('Delivery created with items', [
                'delivery_id' => $delivery->id,
                'items_count' => count($items)
            ]);

            Activity::create([
                'user_name' => auth()->user()->name ?? 'System',
                'action' => 'Created',
                'item' => $delivery->dr_no . ' - ' . ($delivery->customer_name ?? 'N/A'),
                'target' => $delivery->sales_order_number ?? 'N/A',
                'type' => 'Delivery',
                'message' => 'Created delivery: ' . $delivery->dr_no . ' for SO: ' . $delivery->sales_order_number,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Delivery created successfully!',
                'delivery_id' => $delivery->id
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Delivery store failed', [
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

    // ✅ UPDATED UPDATE METHOD - Now saves item_id, brand, item_category
    public function update(Request $request, $id)
    {
        try {
            $delivery = Deliveries::findOrFail($id);

            Log::info('DELIVERY UPDATE START', [
                'id' => $id,
                'has_file' => $request->hasFile('attachment'),
            ]);

            $validated = $request->validate([
                'sales_order_number' => 'required|string|max:255',
                'dr_no' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('deliveries', 'dr_no')->ignore($id)
                ],
                'sales_invoice_no' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('deliveries', 'sales_invoice_no')->ignore($id)
                ],
                'customer_code' => 'nullable|string|max:255',
                'customer_name' => 'nullable|string|max:255',
                'tin' => 'nullable|string|max:255',
                'branch' => 'nullable|string|max:255',
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
                'items.*.uom' => 'nullable|string|max:50',
                'items.*.unit_price' => 'nullable|numeric|min:0',
                'items.*.total_amount' => 'required|numeric|min:0',
            ], [
                'dr_no.required' => 'DR Number is required.',
                'dr_no.unique' => 'This DR Number already exists. Please use a unique DR Number.',
                'sales_invoice_no.required' => 'Sales Invoice Number is required.',
                'sales_invoice_no.unique' => 'This Sales Invoice Number already exists. Please use a unique Sales Invoice Number.',
            ]);

            // Clean empty strings to null
            foreach (['customer_code', 'customer_name', 'branch', 'tin', 'sales_representative', 'sales_executive', 'po_number', 'plate_no'] as $field) {
                if (isset($validated[$field]) && $validated[$field] === '') {
                    $validated[$field] = null;
                }
            }

            // Handle file upload
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $filename = time() . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());
                
                $uploadPath = public_path('delivery_images');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                
                if ($delivery->attachment && file_exists(public_path('delivery_images/' . $delivery->attachment))) {
                    @unlink(public_path('delivery_images/' . $delivery->attachment));
                }
                
                $file->move($uploadPath, $filename);
                $validated['attachment'] = $filename;
            }

            $items = $validated['items'];
            unset($validated['items']);

            $delivery->update($validated);

            // Get sales order items for reference
            $salesOrder = SalesOrder::with('items.item')->where('sales_order_number', $validated['sales_order_number'])->first();
            $soItemsMap = $salesOrder ? $salesOrder->items->keyBy('item_code') : collect();

            // Delete old items and create new ones
            DeliveryItem::where('delivery_id', $delivery->id)->delete();
            
            foreach ($items as $item) {
                $itemCode = $item['item_code'] ?? null;
                
                $soItem = $soItemsMap->get($itemCode);
                
                $itemRecord = null;
                if (!$soItem && $itemCode) {
                    $itemRecord = Item::where('item_code', $itemCode)->first();
                }

                DeliveryItem::create([
                    'delivery_id' => $delivery->id,
                    'item_id' => $soItem?->item_id ?? $itemRecord?->id ?? null,
                    'item_code' => $itemCode,
                    'item_description' => $item['item_description'] ?? null,
                    'brand' => $soItem?->brand ?? $itemRecord?->brand ?? null,
                    'item_category' => $soItem?->item_category ?? $itemRecord?->item_category ?? null,
                    'quantity' => $item['quantity'],
                    'uom' => $item['uom'] ?? null,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'total_amount' => $item['total_amount'],
                ]);
            }

            Log::info('Delivery updated with items', [
                'delivery_id' => $delivery->id,
                'items_count' => count($items)
            ]);

            Activity::create([
                'user_name' => auth()->user()->name ?? 'System',
                'action' => 'Updated',
                'item' => $delivery->dr_no . ' - ' . ($delivery->customer_name ?? 'N/A'),
                'target' => $delivery->sales_order_number ?? 'N/A',
                'type' => 'Delivery',
                'message' => 'Updated delivery: ' . $delivery->dr_no . ' (Status: ' . $delivery->status . ')',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Delivery updated successfully!'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Delivery update failed', [
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

    // ✅ FIXED DESTROY METHOD - WITH ACTIVITY LOGGING
    public function destroy($id)
    {
        $delivery = Deliveries::findOrFail($id);
        
        Activity::create([
            'user_name' => auth()->user()->name ?? 'System',
            'action' => 'Deleted',
            'item' => $delivery->dr_no . ' - ' . ($delivery->customer_name ?? 'N/A'),
            'target' => $delivery->sales_order_number ?? 'N/A',
            'type' => 'Delivery',
            'message' => 'Deleted delivery: ' . $delivery->dr_no,
        ]);
        
        if ($delivery->attachment && file_exists(public_path('delivery_images/' . $delivery->attachment))) {
            @unlink(public_path('delivery_images/' . $delivery->attachment));
        }
        
        $delivery->delete();

        return redirect()->route('deliveries.index')->with('success', 'Delivery deleted successfully!');
    }

    // ✅  SEARCH METHOD
   public function search(Request $request)
{
    $soNumber = $request->input('so_number');
    
    if (!$soNumber) {
        return response()->json([
            'error' => 'Please provide a Sales Order number.'
        ], 400);
    }

    // ✅ First check if SO exists at all
    $soExists = SalesOrder::where('sales_order_number', $soNumber)->first();
    
    if (!$soExists) {
        return response()->json([
            'error' => 'Sales Order not found. Please check the SO number and try again.'
        ], 404);
    }

    // ✅ Then check if it's approved
    if ($soExists->status !== 'Approved') {
        return response()->json([
            'error' => "Sales Order {$soNumber} exists but has not been approved yet (Status: {$soExists->status}). Only approved sales orders can be delivered."
        ], 403);
    }

    // ✅ Fetch approved sales order with items AND their item details
    $salesOrder = SalesOrder::with(['items.item'])
        ->where('sales_order_number', $soNumber)
        ->where('status', 'Approved')
        ->first();

    $delivery = Deliveries::with('items')
        ->where('sales_order_number', $soNumber)
        ->first();

    $items = [];
    
    if ($delivery && $delivery->items->count() > 0) {
        foreach ($delivery->items as $deliveryItem) {
            $items[] = [
                'item_code' => $deliveryItem->item_code ?? '',
                'item_description' => $deliveryItem->item_description ?? '',
                'brand' => $deliveryItem->brand ?? '',
                'item_category' => $deliveryItem->item_category ?? '',
                'quantity' => $deliveryItem->quantity ?? 0,
                'uom' => $deliveryItem->uom ?? '',
                'unit_price' => $deliveryItem->unit_price ?? 0,
                'total_amount' => $deliveryItem->total_amount ?? 0,
            ];
        }
    } else {
        foreach ($salesOrder->items as $item) {
            $items[] = [
                'item_code' => $item->item_code ?? '',
                'item_description' => $item->item_description ?? $item->description ?? '',
                'quantity' => $item->quantity ?? 0,
                'uom' => $item->unit ?? $item->uom ?? '',
                'unit_price' => $item->unit_price ?? 0,
                'total_amount' => (($item->quantity ?? 0) * ($item->unit_price ?? 0)),
            ];
        }
    }

    return response()->json([
        'id' => $delivery->id ?? null,
        'sales_order_number' => $salesOrder->sales_order_number,
        'customer_code' => $salesOrder->customer->customer_code ?? '',
        'customer_name' => $salesOrder->customer->customer_name ?? '',
        'tin' => $salesOrder->customer->tin ?? '',
        'branch' => $salesOrder->branch,
        'sales_representative' => $salesOrder->sales_representative,
        'sales_executive' => $salesOrder->sales_executive,
        'po_number' => $salesOrder->po_number,
        'request_delivery_date' => $salesOrder->request_delivery_date,
        'approved_by' => auth()->user()->name,
        'plate_no' => $delivery->plate_no ?? '',
        'sales_invoice_no' => $delivery->sales_invoice_no ?? '',
        'dr_no' => $delivery->dr_no ?? '',
        'status' => $delivery->status ?? 'Delivered',
        'additional_instructions' => $salesOrder->additional_instructions,
        'attachment' => $delivery->attachment ?? null,
        'items' => $items,
    ]);
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
                            $delivery->salesOrder?->customer?->tin ?? $delivery->tin ?? '—',
                            $delivery->branch ?? $delivery->salesOrder?->branch ?? '—',
                            $delivery->sales_representative ?? $delivery->salesOrder?->sales_representative ?? '—',
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
                        $delivery->salesOrder?->customer?->tin ?? $delivery->tin ?? '—',
                        $delivery->branch ?? $delivery->salesOrder?->branch ?? '—',
                        $delivery->sales_representative ?? $delivery->salesOrder?->sales_representative ?? '—',
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