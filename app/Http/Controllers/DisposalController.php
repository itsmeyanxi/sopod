<?php

namespace App\Http\Controllers;

use App\Models\FixedAsset;
use Illuminate\Http\Request;

class DisposalController extends Controller
{
    /**
     * Display list of all disposed assets
     */
    public function index(Request $request)
    {
        $query = FixedAsset::disposed();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('asset_description', 'LIKE', "%{$search}%")
                  ->orWhere('asset_code', 'LIKE', "%{$search}%")
                  ->orWhere('serial_engine_no', 'LIKE', "%{$search}%")
                  ->orWhere('plate_no', 'LIKE', "%{$search}%")
                  ->orWhere('assigned_person', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('asset_group')) {
            $query->where('asset_group', $request->asset_group);
        }

        if ($request->filled('asset_class')) {
            $query->where('asset_class', $request->asset_class);
        }

        if ($request->filled('disposal_date_from')) {
            $query->whereDate('disposal_date', '>=', $request->disposal_date_from);
        }

        if ($request->filled('disposal_date_to')) {
            $query->whereDate('disposal_date', '<=', $request->disposal_date_to);
        }

        $assets = $query->orderBy('disposal_date', 'desc')->orderBy('asset_code')->paginate(50);

        $assetGroups  = FixedAsset::withDisposed()->select('asset_group')->distinct()->orderBy('asset_group')->pluck('asset_group');
        $assetClasses = FixedAsset::withDisposed()->select('asset_class')->distinct()->whereNotNull('asset_class')->orderBy('asset_class')->pluck('asset_class');

        $summary = [
            'total_disposed'   => FixedAsset::disposed()->count(),
            'total_cost'       => FixedAsset::disposed()->sum('cost'),
            'total_disposal'   => FixedAsset::disposed()->sum('disposal_amount'),
            'total_accum_dep'  => FixedAsset::disposed()->sum('accumulated_depreciation'),
        ];

        return view('disposals.index', compact('assets', 'assetGroups', 'assetClasses', 'summary'));
    }

    /**
     * Display details of a disposed asset
     */
    public function show($id)
    {
        $asset = FixedAsset::disposed()->findOrFail($id);
        return view('disposals.show', compact('asset'));
    }
}
