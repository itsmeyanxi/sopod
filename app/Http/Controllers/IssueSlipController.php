<?php

namespace App\Http\Controllers;

use App\Models\IssueSlip;
use App\Models\IssueSlipItem;
use App\Models\Customer;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class IssueSlipController extends Controller
{
    public function index(Request $request)
    {
        $query = IssueSlip::with(['salesOrder', 'creator'])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('issue_slip_number', 'like', "%{$s}%")
                  ->orWhere('sales_order_number', 'like', "%{$s}%")
                  ->orWhere('customer_name', 'like', "%{$s}%")
                  ->orWhere('origin', 'like', "%{$s}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $issueSlips = $query->paginate(20)->withQueryString();

        return view('issue_slips.index', compact('issueSlips'));
    }

    public function create()
    {
        return view('issue_slips.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'date'            => 'required|date',
            'origin'          => 'nullable|string|max:255',
            'customer_id'     => 'nullable|exists:customers,id',
            'destination'     => 'nullable|string|max:255',
            'sales_order_id'  => 'required|exists:sales_orders,id',
            'items'           => 'required|array|min:1',
            'items.*.sales_order_item_id' => 'nullable',
            'items.*.number_of_boxes'     => 'nullable|numeric|min:0',
            'items.*.net_weight'          => 'nullable|numeric|min:0',
            'items.*.actual_weight'       => 'nullable|numeric|min:0',
            'issued_by'                   => 'nullable|string|max:255',
            'transport'                   => 'nullable|string|max:255',
            'service_providers_checker'   => 'nullable|string|max:255',
            'received_by'                 => 'nullable|string|max:255',
        ]);

        // Check if this Sales Order already has an Issue Slip
        if (IssueSlip::where('sales_order_id', $request->sales_order_id)->exists()) {
            return back()->withInput()->with('error', 'This Sales Order has already been used to create an Issue Slip. Each Sales Order can only have one Issue Slip.');
        }

        DB::beginTransaction();
        try {
            do {
                $number = 'IS' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            } while (IssueSlip::where('issue_slip_number', $number)->exists());

            $salesOrder = SalesOrder::findOrFail($request->sales_order_id);

            $issueSlip = IssueSlip::create([
                'issue_slip_number'  => $number,
                'date'               => $request->date,
                'origin'             => $request->origin,
                'customer_id'        => $request->customer_id ?: null,
                'destination'        => $request->destination,
                'sales_order_id'     => $salesOrder->id,
                'sales_order_number' => $salesOrder->sales_order_number,
                'customer_name'      => $salesOrder->customer_name,
                'branch'             => $salesOrder->branch,
                'remarks'            => $request->remarks,
                'issued_by'          => $request->issued_by,
                'transport'          => $request->transport,
                'service_providers_checker' => $request->service_providers_checker,
                'received_by'        => $request->received_by,
                'created_by'         => Auth::id(),
            ]);

            foreach ($request->items as $item) {
                // Get the origin/notes from the SalesOrderItem
                $origin = null;
                if (!empty($item['sales_order_item_id'])) {
                    $soItem = \App\Models\SalesOrderItem::find($item['sales_order_item_id']);
                    if ($soItem) {
                        $origin = $soItem->note; // Get the note from SalesOrderItem
                    }
                }

                IssueSlipItem::create([
                    'issue_slip_id'       => $issueSlip->id,
                    'sales_order_item_id' => !empty($item['sales_order_item_id']) ? $item['sales_order_item_id'] : null,
                    'item_code'           => $item['item_code'] ?? null,
                    'item_description'    => $item['item_description'] ?? null,
                    'brand'               => $item['brand'] ?? null,
                    'item_category'       => $item['item_category'] ?? null,
                    'origin'              => $origin, // Auto-populated from SalesOrderItem note
                    'so_quantity'         => $item['so_quantity'] ?? 0,
                    'number_of_boxes'     => $item['number_of_boxes'] ?? 0,
                    'net_weight'          => $item['net_weight'] ?? 0,
                    'actual_weight'       => $item['actual_weight'] ?? 0,
                ]);
            }

            DB::commit();

            return redirect()->route('issue_slips.show', $issueSlip->id)
                ->with('success', 'Issue Slip ' . $number . ' created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create Issue Slip: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $issueSlip = IssueSlip::with(['items', 'salesOrder', 'creator'])->findOrFail($id);
        return view('issue_slips.show', compact('issueSlip'));
    }

    public function edit($id)
    {
        $issueSlip = IssueSlip::with(['items', 'salesOrder'])->findOrFail($id);
        return view('issue_slips.edit', compact('issueSlip'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'date'   => 'required|date',
            'origin' => 'nullable|string|max:255',
            'items'  => 'required|array|min:1',
            'items.*.number_of_boxes' => 'nullable|numeric|min:0',
            'items.*.net_weight'      => 'nullable|numeric|min:0',
            'items.*.actual_weight'   => 'nullable|numeric|min:0',
            'issued_by'                   => 'nullable|string|max:255',
            'transport'                   => 'nullable|string|max:255',
            'service_providers_checker'   => 'nullable|string|max:255',
            'received_by'                 => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $issueSlip = IssueSlip::findOrFail($id);
            $issueSlip->update([
                'date'        => $request->date,
                'origin'      => $request->origin,
                'customer_id' => $request->customer_id ?: null,
                'destination' => $request->destination,
                'remarks'     => $request->remarks,
                'issued_by'   => $request->issued_by,
                'transport'   => $request->transport,
                'service_providers_checker' => $request->service_providers_checker,
                'received_by' => $request->received_by,
            ]);

            $issueSlip->items()->delete();

            foreach ($request->items as $item) {
                // Get the origin/notes from the SalesOrderItem
                $origin = null;
                if (!empty($item['sales_order_item_id'])) {
                    $soItem = \App\Models\SalesOrderItem::find($item['sales_order_item_id']);
                    if ($soItem) {
                        $origin = $soItem->note; // Get the note from SalesOrderItem
                    }
                }

                IssueSlipItem::create([
                    'issue_slip_id'       => $issueSlip->id,
                    'sales_order_item_id' => !empty($item['sales_order_item_id']) ? $item['sales_order_item_id'] : null,
                    'item_code'           => $item['item_code'] ?? null,
                    'item_description'    => $item['item_description'] ?? null,
                    'brand'               => $item['brand'] ?? null,
                    'item_category'       => $item['item_category'] ?? null,
                    'origin'              => $origin, // Auto-populated from SalesOrderItem note
                    'so_quantity'         => $item['so_quantity'] ?? 0,
                    'number_of_boxes'     => $item['number_of_boxes'] ?? 0,
                    'net_weight'          => $item['net_weight'] ?? 0,
                    'actual_weight'       => $item['actual_weight'] ?? 0,
                ]);
            }

            DB::commit();

            return redirect()->route('issue_slips.show', $issueSlip->id)
                ->with('success', 'Issue Slip updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        // Check if user has permission to delete issue slips
        if (!auth()->user()->canDeleteIssueSlips()) {
            abort(403, 'Unauthorized to delete issue slips.');
        }

        $issueSlip = IssueSlip::findOrFail($id);
        $issueSlip->delete();

        return redirect()->route('issue_slips.index')
            ->with('success', 'Issue Slip deleted.');
    }

    public function print($id)
    {
        $issueSlip = IssueSlip::with(['items', 'salesOrder', 'creator'])->findOrFail($id);
        return view('issue_slips.print', compact('issueSlip'));
    }

    /**
     * Search sales orders for the SO lookup in create form.
     */
    public function searchSalesOrders(Request $request)
    {
        $q = $request->input('q', '');
        if (strlen($q) < 1) return response()->json([]);

        $results = SalesOrder::where('sales_order_number', 'like', "%{$q}%")
            ->orWhere('customer_name', 'like', "%{$q}%")
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'sales_order_number', 'customer_name', 'status', 'request_delivery_date']);

        // Mark which SOs are already used for Issue Slips
        $results->each(function ($so) {
            $so->has_issue_slip = IssueSlip::where('sales_order_id', $so->id)->exists();
        });

        return response()->json($results);
    }

    /**
     * Get items for a specific sales order (called when SO is selected).
     */
    public function getSalesOrderItems($soId)
    {
        $so = SalesOrder::with('items')->findOrFail($soId);

        $items = $so->items->map(function ($item) {
            return [
                'id'               => $item->id,
                'item_code'        => $item->item_code,
                'item_description' => $item->item_description,
                'brand'            => $item->brand,
                'item_category'    => $item->item_category,
                'quantity'         => $item->quantity,
                'unit'             => $item->unit,
                'note'             => $item->note, // Include note so origin can be displayed
            ];
        });

        return response()->json([
            'sales_order' => [
                'id'                   => $so->id,
                'sales_order_number'   => $so->sales_order_number,
                'customer_name'        => $so->customer_name,
                'customer_id'          => $so->customer_id,
                'request_delivery_date' => $so->request_delivery_date,
            ],
            'items' => $items,
        ]);
    }

    /**
     * Search customers for the destination lookup.
     */
    public function searchCustomers(Request $request)
    {
        $q = $request->input('q', '');
        if (strlen($q) < 1) return response()->json([]);

        $results = Customer::where('customer_name', 'like', "%{$q}%")
            ->orWhere('customer_code', 'like', "%{$q}%")
            ->orderBy('customer_name')
            ->limit(10)
            ->get(['id', 'customer_name', 'customer_code', 'shipping_address']);

        return response()->json($results);
    }
}
