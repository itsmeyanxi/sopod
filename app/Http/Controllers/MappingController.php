<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class MappingController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseOrder::with([
            'purchaseRequest',
            'rfps' => fn($q) => $q->where('status', 'approved'),
            'rfps.apvs' => fn($q) => $q->where('status', 'approved'),
            'srrs',
            'supplierModel',
        ])->where('status', 'approved')->orderByDesc('created_at');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('po_no', 'LIKE', "%{$s}%")
                  ->orWhere('pr_no', 'LIKE', "%{$s}%")
                  ->orWhere('supplier', 'LIKE', "%{$s}%")
                  ->orWhereHas('rfps', fn($r) => $r->where('rfp_no', 'LIKE', "%{$s}%"))
                  ->orWhereHas('rfps.apvs', fn($a) => $a->where('apv_no', 'LIKE', "%{$s}%"))
                  ->orWhereHas('srrs', fn($r) => $r->where('srr_code', 'LIKE', "%{$s}%"));
            });
        }

        $pos = $query->paginate(25)->appends($request->query());

        return view('mapping.index', compact('pos'));
    }
}
