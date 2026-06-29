<?php

namespace App\Http\Controllers;

use App\Models\LiveChicken;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class LiveChickenController extends Controller
{
    public function index(Request $request)
    {
        $query = LiveChicken::with('creator')->orderByDesc('date');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('po_no', 'LIKE', "%{$s}%")
                  ->orWhere('grpo_no', 'LIKE', "%{$s}%")
                  ->orWhere('supplier', 'LIKE', "%{$s}%")
                  ->orWhere('items', 'LIKE', "%{$s}%")
                  ->orWhere('delivery_week_no', 'LIKE', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $records = $query->paginate(20)->appends($request->query());

        return view('live_chickens.index', compact('records'));
    }

    public function create()
    {
        return view('live_chickens.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date'                   => 'required|date',
            'po_no'                  => 'nullable|string|max:100',
            'rfp_no'                 => 'nullable|string|max:100',
            'reference_number'       => 'nullable|string|max:255',
            'items_data'             => 'nullable|string',
            'container_no'           => 'nullable|string|max:100',
            'pallet_no'              => 'nullable|string|max:100',
            'storage_name'           => 'nullable|string|max:255',
            'storage_reference_no'   => 'nullable|string|max:100',
            'shipping_type'          => 'nullable|string|max:100',
            'supplier'               => 'required|string|max:255',
            'items'                  => 'required|string',
            'brand'                  => 'nullable|string|max:255',
            'price'                  => 'required|numeric|min:0',
            'delivery_date'          => 'nullable|date',
            'docs_required_type'     => 'nullable|in:file,date',
            'docs_required_file'     => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
            'docs_required_date'     => 'nullable|date',
            'docs_transmitted_type'  => 'nullable|in:file,date',
            'docs_transmitted_file'  => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
            'docs_transmitted_date'  => 'nullable|date',
            'amount'                 => 'required|numeric|min:0',
            'status'                 => 'required|in:Paid,Ongoing,UN Office,No Documents',
            'delivery_week_no'       => 'nullable|string|max:50',
        ]);

        $itemsData = json_decode($validated['items_data'] ?? '[]', true) ?: [];
        $validated['actual_qty'] = array_sum(array_column($itemsData, 'qty'));

        $validated['created_by'] = auth()->id();
        $validated['grpo_no'] = '';

        if ($request->hasFile('docs_required_file')) {
            $validated['docs_required_file'] = $request->file('docs_required_file')->store('live_chicken_docs', 'public');
        }

        if ($request->hasFile('docs_transmitted_file')) {
            $validated['docs_transmitted_file'] = $request->file('docs_transmitted_file')->store('live_chicken_docs', 'public');
        }

        $record = LiveChicken::create($validated);
        $record->grpo_no = LiveChicken::generateGrpoNo($record->id);
        $record->save();

        return redirect()->route('live_chickens.show', $record->id)->with('success', 'GRPO record created.');
    }

    public function show($id)
    {
        $record = LiveChicken::with(['creator', 'purchaseOrder.items', 'receivedBy', 'grpoApprovedBy'])->findOrFail($id);
        return view('live_chickens.show', compact('record'));
    }

    public function edit($id)
    {
        $record = LiveChicken::findOrFail($id);
        return view('live_chickens.edit', compact('record'));
    }

    public function update(Request $request, $id)
    {
        $record = LiveChicken::findOrFail($id);

        $validated = $request->validate([
            'date'                   => 'required|date',
            'po_no'                  => 'nullable|string|max:100',
            'rfp_no'                 => 'nullable|string|max:100',
            'reference_number'       => 'nullable|string|max:255',
            'items_data'             => 'nullable|string',
            'container_no'           => 'nullable|string|max:100',
            'pallet_no'              => 'nullable|string|max:100',
            'storage_name'           => 'nullable|string|max:255',
            'storage_reference_no'   => 'nullable|string|max:100',
            'shipping_type'          => 'nullable|string|max:100',
            'supplier'               => 'required|string|max:255',
            'items'                  => 'required|string',
            'brand'                  => 'nullable|string|max:255',
            'price'                  => 'required|numeric|min:0',
            'delivery_date'          => 'nullable|date',
            'docs_required_type'     => 'nullable|in:file,date',
            'docs_required_file'     => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
            'docs_required_date'     => 'nullable|date',
            'docs_transmitted_type'  => 'nullable|in:file,date',
            'docs_transmitted_file'  => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
            'docs_transmitted_date'  => 'nullable|date',
            'amount'                 => 'required|numeric|min:0',
            'status'                 => 'required|in:Paid,Ongoing,UN Office,No Documents',
            'delivery_week_no'       => 'nullable|string|max:50',
        ]);

        $itemsData = json_decode($validated['items_data'] ?? '[]', true) ?: [];
        $validated['actual_qty'] = array_sum(array_column($itemsData, 'qty'));

        if ($request->hasFile('docs_required_file')) {
            if ($record->docs_required_file) Storage::disk('public')->delete($record->docs_required_file);
            $validated['docs_required_file'] = $request->file('docs_required_file')->store('live_chicken_docs', 'public');
        }

        if ($request->hasFile('docs_transmitted_file')) {
            if ($record->docs_transmitted_file) Storage::disk('public')->delete($record->docs_transmitted_file);
            $validated['docs_transmitted_file'] = $request->file('docs_transmitted_file')->store('live_chicken_docs', 'public');
        }

        $record->update($validated);

        return redirect()->route('live_chickens.show', $record->id)->with('success', 'Record updated.');
    }

    public function destroy($id)
    {
        if (!Auth::user()->canManageRequestForPayments()) {
            abort(403, 'Unauthorized action.');
        }

        $record = LiveChicken::findOrFail($id);

        if ($record->docs_required_file) Storage::disk('public')->delete($record->docs_required_file);
        if ($record->docs_transmitted_file) Storage::disk('public')->delete($record->docs_transmitted_file);

        $record->delete();

        return redirect()->route('live_chickens.index')->with('success', 'Record deleted.');
    }

    public function receive(Request $request, $id)
    {
        $record = LiveChicken::findOrFail($id);

        $record->update([
            'received_by_user_id'  => Auth::id(),
            'received_at'          => now(),
            'received_latitude'    => $request->input('latitude'),
            'received_longitude'   => $request->input('longitude'),
            'received_location'    => $request->input('location'),
        ]);

        return redirect()->back()->with('success', 'Signed as Received.');
    }

    public function grpoApprove(Request $request, $id)
    {
        $record = LiveChicken::findOrFail($id);

        $record->update([
            'grpo_approved_by_user_id' => Auth::id(),
            'grpo_approved_at'         => now(),
            'grpo_approved_latitude'   => $request->input('latitude'),
            'grpo_approved_longitude'  => $request->input('longitude'),
            'grpo_approved_location'   => $request->input('location'),
        ]);

        return redirect()->back()->with('success', 'Signed as Approved (Warehouse Supervisor).');
    }

    public function print($id)
    {
        $record = LiveChicken::with(['creator', 'purchaseOrder.items', 'receivedBy', 'grpoApprovedBy'])->findOrFail($id);
        return view('live_chickens.print', compact('record'));
    }

    public function searchSRRs(Request $request)
    {
        $search = $request->input('search', '');

        $query = \App\Models\SupplierReceivingReport::with('items')
            ->whereNotIn('status', ['invalidated']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('srr_code', 'LIKE', "%{$search}%")
                  ->orWhere('supplier_name', 'LIKE', "%{$search}%")
                  ->orWhere('po_no', 'LIKE', "%{$search}%")
                  ->orWhere('reference_number', 'LIKE', "%{$search}%");
            });
        }

        $srrs = $query->orderByDesc('created_at')->limit(15)->get()
            ->map(fn($srr) => [
                'id'               => $srr->id,
                'srr_code'         => $srr->srr_code,
                'po_no'            => $srr->po_no,
                'supplier_name'    => $srr->supplier_name,
                'reference_number' => $srr->reference_number,
                'report_date'      => $srr->report_date?->format('Y-m-d'),
                'srr_items'        => $srr->items->map(fn($i) => [
                    'description' => $i->item_description,
                    'brand'       => $i->brand ?? '',
                    'qty'         => (float) $i->no_of_boxes,
                    'uom'         => 'kg',
                    'unit_price'  => 0,
                ])->values(),
            ]);

        return response()->json($srrs);
    }

    public function searchRFPs(Request $request)
    {
        $search = $request->input('search', '');

        $query = \App\Models\RequestForPayment::with(['purchaseOrder.items'])
            ->whereNotIn('status', ['invalidated', 'rejected']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('rfp_no', 'LIKE', "%{$search}%")
                  ->orWhere('payee', 'LIKE', "%{$search}%");
            });
        }

        $rfps = $query->orderByDesc('created_at')->limit(15)->get()
            ->map(function ($rfp) {
                $po = $rfp->purchaseOrder;
                $poItems = $po ? $po->items->map(fn($i) => [
                    'description' => $i->description,
                    'brand'       => $i->brand ?? '',
                    'qty'         => (float) $i->qty,
                    'uom'         => $i->uom ?? 'kg',
                    'unit_price'  => (float) $i->unit_price,
                ])->values()->all() : [];

                return [
                    'id'               => $rfp->id,
                    'rfp_no'           => $rfp->rfp_no,
                    'payee'            => $rfp->payee,
                    'particulars'      => $rfp->particulars,
                    'amount'           => (float) $rfp->amount,
                    'po_no'            => $po?->po_no,
                    'reference_number' => $po?->reference_number,
                    'brand'            => $po?->brand ?: ($po?->items->first()?->brand),
                    'price'            => (float) ($po?->lc_price ?? $po?->items->first()?->unit_price ?? 0),
                    'rfp_items'        => $poItems,
                ];
            });

        return response()->json($rfps);
    }

    public function searchPOs(Request $request)
    {
        $search = $request->input('search', '');

        $query = PurchaseOrder::with('items')->where('status', 'approved');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('po_no', 'LIKE', "%{$search}%")
                  ->orWhere('supplier', 'LIKE', "%{$search}%")
                  ->orWhere('reference_number', 'LIKE', "%{$search}%");
            });
        }

        $pos = $query->select('id', 'po_no', 'reference_number', 'supplier', 'brand', 'order_date', 'lc_price')
            ->limit(15)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($po) {
                return [
                    'id'              => $po->id,
                    'po_no'           => $po->po_no,
                    'reference_number'=> $po->reference_number,
                    'supplier'        => $po->supplier,
                    'brand'        => $po->brand ?: $po->items->first()?->brand,
                    'order_date'   => $po->order_date,
                    'po_qty'       => (float) $po->items->sum('qty'),
                    'items_desc'   => $po->items->pluck('description')->filter()->implode("\n"),
                    'po_items'     => $po->items->map(fn($i) => ['description'=>$i->description,'brand'=>$i->brand,'qty'=>(float)$i->qty,'uom'=>$i->uom??'','unit_price'=>(float)$i->unit_price])->values(),
                    'price'        => (float) ($po->lc_price ?? $po->items->first()?->unit_price ?? 0),
                    'payment_terms' => $po->payment_terms,
                ];
            });

        return response()->json($pos);
    }
}
