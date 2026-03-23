<?php

namespace App\Http\Controllers;

use App\Models\ApLedgerEntry;
use Illuminate\Http\Request;

class ApLedgerController extends Controller
{
    public function index(Request $request)
    {
        $query = ApLedgerEntry::query();

        if ($request->filled('supplier')) {
            $query->where('supplier_name', 'LIKE', '%' . $request->supplier . '%');
        }

        if ($request->filled('from')) {
            $query->where('txn_date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->where('txn_date', '<=', $request->to);
        }

        $entries = $query->orderBy('id', 'asc')->paginate(50);

        $suppliers = ApLedgerEntry::select('supplier_name')
            ->distinct()
            ->orderBy('supplier_name')
            ->pluck('supplier_name');

        return view('ap_ledger.index', compact('entries', 'suppliers'));
    }
}
