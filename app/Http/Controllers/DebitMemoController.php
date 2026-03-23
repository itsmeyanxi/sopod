<?php

namespace App\Http\Controllers;

use App\Models\DebitMemo;
use App\Models\Supplier;
use App\Models\AccountsPayableInvoice;
use App\Models\ApLedgerEntry;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DebitMemoController extends Controller
{
    public function index()
    {
        $memos = DebitMemo::with(['supplier', 'creator'])
            ->orderByDesc('memo_date')
            ->paginate(20);

        return view('debit_memos.index', compact('memos'));
    }

    public function create()
    {
        $dmNo = 'DM-' . date('Ymd') . '-' . str_pad(DebitMemo::count() + 1, 4, '0', STR_PAD_LEFT);
        $suppliers = Supplier::where('status', 'active')->orderBy('supplier_name')->get();
        $invoices = AccountsPayableInvoice::where('status', '!=', 'rejected')
            ->orderByDesc('created_at')
            ->get();

        return view('debit_memos.create', compact('dmNo', 'suppliers', 'invoices'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'dm_no' => 'required|string|max:80|unique:debit_memos,dm_no',
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_id' => 'nullable|exists:accounts_payable_invoices,id',
            'memo_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:200',
            'notes' => 'nullable|string',
        ]);

        $supplier = Supplier::findOrFail($validated['supplier_id']);
        $validated['supplier_name'] = $supplier->supplier_name;
        $validated['created_by'] = Auth::id();

        if (!empty($validated['invoice_id'])) {
            $invoice = AccountsPayableInvoice::find($validated['invoice_id']);
            $validated['invoice_no'] = $invoice->apv_no ?? null;
        }

        $memo = DebitMemo::create($validated);

        ApLedgerEntry::addEntry(
            'Debit Memo',
            $memo->dm_no,
            $memo->supplier_name,
            $memo->reason ?? 'Debit Memo',
            0,
            (float) $memo->amount
        );

        Activity::create([
            'user_name' => Auth::user()->name,
            'action' => 'Created',
            'item' => $memo->dm_no,
            'target' => $memo->supplier_name,
            'message' => 'Created debit memo ' . $memo->dm_no . ' for ' . $memo->supplier_name,
        ]);

        return redirect()->route('debit_memos.index')
            ->with('success', 'Debit memo created successfully.');
    }

    public function show(DebitMemo $debit_memo)
    {
        $debit_memo->load(['supplier', 'invoice', 'creator']);
        return view('debit_memos.show', compact('debit_memo'));
    }

    public function edit(DebitMemo $debit_memo)
    {
        $suppliers = Supplier::where('status', 'active')->orderBy('supplier_name')->get();
        $invoices = AccountsPayableInvoice::where('status', '!=', 'rejected')
            ->orderByDesc('created_at')
            ->get();

        return view('debit_memos.edit', compact('debit_memo', 'suppliers', 'invoices'));
    }

    public function update(Request $request, DebitMemo $debit_memo)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_id' => 'nullable|exists:accounts_payable_invoices,id',
            'memo_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:200',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:Open,Applied,Voided',
        ]);

        $supplier = Supplier::findOrFail($validated['supplier_id']);
        $validated['supplier_name'] = $supplier->supplier_name;

        if (!empty($validated['invoice_id'])) {
            $invoice = AccountsPayableInvoice::find($validated['invoice_id']);
            $validated['invoice_no'] = $invoice->apv_no ?? null;
        } else {
            $validated['invoice_no'] = null;
        }

        $debit_memo->update($validated);

        Activity::create([
            'user_name' => Auth::user()->name,
            'action' => 'Updated',
            'item' => $debit_memo->dm_no,
            'target' => $debit_memo->supplier_name,
            'message' => 'Updated debit memo ' . $debit_memo->dm_no,
        ]);

        return redirect()->route('debit_memos.index')
            ->with('success', 'Debit memo updated successfully.');
    }

    public function destroy(DebitMemo $debit_memo)
    {
        $dmNo = $debit_memo->dm_no;
        $supplierName = $debit_memo->supplier_name;
        $debit_memo->delete();

        Activity::create([
            'user_name' => Auth::user()->name,
            'action' => 'Deleted',
            'item' => $dmNo,
            'target' => $supplierName,
            'message' => 'Deleted debit memo ' . $dmNo,
        ]);

        return redirect()->route('debit_memos.index')
            ->with('success', 'Debit memo deleted successfully.');
    }
}
