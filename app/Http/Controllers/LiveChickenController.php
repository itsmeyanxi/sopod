<?php

namespace App\Http\Controllers;

use App\Models\LiveChicken;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'supplier'               => 'required|string|max:255',
            'items'                  => 'required|string',
            'brand'                  => 'nullable|string|max:255',
            'price'                  => 'required|numeric|min:0',
            'actual_qty'             => 'required|numeric|min:0',
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

        $validated['created_by'] = auth()->id();

        if ($request->hasFile('docs_required_file')) {
            $validated['docs_required_file'] = $request->file('docs_required_file')->store('live_chicken_docs', 'public');
        }

        if ($request->hasFile('docs_transmitted_file')) {
            $validated['docs_transmitted_file'] = $request->file('docs_transmitted_file')->store('live_chicken_docs', 'public');
        }

        LiveChicken::create($validated);

        return redirect()->route('live_chickens.index')->with('success', 'Live chicken record created.');
    }

    public function show($id)
    {
        $record = LiveChicken::with(['creator', 'purchaseOrder.items'])->findOrFail($id);
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
            'supplier'               => 'required|string|max:255',
            'items'                  => 'required|string',
            'brand'                  => 'nullable|string|max:255',
            'price'                  => 'required|numeric|min:0',
            'actual_qty'             => 'required|numeric|min:0',
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

        if ($request->hasFile('docs_required_file')) {
            if ($record->docs_required_file) {
                Storage::disk('public')->delete($record->docs_required_file);
            }
            $validated['docs_required_file'] = $request->file('docs_required_file')->store('live_chicken_docs', 'public');
        }

        if ($request->hasFile('docs_transmitted_file')) {
            if ($record->docs_transmitted_file) {
                Storage::disk('public')->delete($record->docs_transmitted_file);
            }
            $validated['docs_transmitted_file'] = $request->file('docs_transmitted_file')->store('live_chicken_docs', 'public');
        }

        $record->update($validated);

        return redirect()->route('live_chickens.show', $record->id)->with('success', 'Record updated.');
    }

    public function destroy($id)
    {
        $record = LiveChicken::findOrFail($id);

        if ($record->docs_required_file) {
            Storage::disk('public')->delete($record->docs_required_file);
        }
        if ($record->docs_transmitted_file) {
            Storage::disk('public')->delete($record->docs_transmitted_file);
        }

        $record->delete();

        return redirect()->route('live_chickens.index')->with('success', 'Record deleted.');
    }

    public function searchPOs(Request $request)
    {
        $search = $request->input('search', '');

        $query = PurchaseOrder::with('items')->where('status', 'approved');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('po_no', 'LIKE', "%{$search}%")
                  ->orWhere('supplier', 'LIKE', "%{$search}%");
            });
        }

        $pos = $query->select('id', 'po_no', 'supplier', 'brand', 'order_date', 'lc_price')
            ->limit(15)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($po) {
                return [
                    'id'           => $po->id,
                    'po_no'        => $po->po_no,
                    'supplier'     => $po->supplier,
                    'brand'        => $po->brand,
                    'order_date'   => $po->order_date,
                    'po_qty'       => (float) $po->items->sum('qty'),
                    'items_desc'   => $po->items->pluck('description')->filter()->implode(', '),
                    'price'        => (float) ($po->lc_price ?? $po->items->first()?->unit_price ?? 0),
                ];
            });

        return response()->json($pos);
    }
}
