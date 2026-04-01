<?php

namespace App\Http\Controllers;

use App\Models\AssetClass;
use App\Models\GlAccount;
use Illuminate\Http\Request;

class AssetClassController extends Controller
{
    private function getGlAccounts(): array
    {
        return GlAccount::orderBy('account_code')
            ->get(['account_code', 'account_name'])
            ->map(fn($a) => [
                'code'   => $a->account_code,
                'name'   => $a->account_name,
                'search' => strtolower($a->account_code . ' ' . $a->account_name),
            ])->toArray();
    }

    private const ASSET_GROUPS = [
        'Leasehold Improvement',
        'Transportation Equipment',
        'Machineries And Equipment',
        'Office Equipment',
        'Furniture And Fixtures',
        'Tools',
        'Building',
        'Right of Use Asset',
        'Fixed Asset Clearing',
    ];

    public function index(Request $request)
    {
        $query = AssetClass::query();

        if ($request->filled('asset_group')) {
            $query->where('asset_group', $request->asset_group);
        }
        if ($request->filled('search')) {
            $query->where('asset_class', 'like', '%' . $request->search . '%');
        }

        $classes    = $query->orderBy('asset_group')->orderBy('asset_class')->paginate(50)->withQueryString();
        $assetGroups = self::ASSET_GROUPS;

        return view('asset_classes.index', compact('classes', 'assetGroups'));
    }

    public function create()
    {
        $assetGroups = self::ASSET_GROUPS;
        $glAccounts  = $this->getGlAccounts();
        return view('asset_classes.create', compact('assetGroups', 'glAccounts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'asset_group'          => 'required|string',
            'asset_class'          => 'required|string|max:255',
            'useful_life_months'   => 'required|integer|min:0',
            'gl_account'           => 'nullable|string',
            'depreciation_account' => 'nullable|string',
            'dep_type'             => 'required|in:Straight Line,Declining Balance',
        ]);

        $data['created_by'] = auth()->user()->name ?? auth()->id();

        AssetClass::create($data);

        return redirect()->route('asset_classes.index')->with('success', 'Asset class created.');
    }

    public function edit(AssetClass $assetClass)
    {
        $assetGroups = self::ASSET_GROUPS;
        $glAccounts  = $this->getGlAccounts();
        return view('asset_classes.edit', compact('assetClass', 'assetGroups', 'glAccounts'));
    }

    public function update(Request $request, AssetClass $assetClass)
    {
        $data = $request->validate([
            'asset_group'          => 'required|string',
            'asset_class'          => 'required|string|max:255',
            'useful_life_months'   => 'required|integer|min:0',
            'gl_account'           => 'nullable|string',
            'depreciation_account' => 'nullable|string',
            'dep_type'             => 'required|in:Straight Line,Declining Balance',
            'is_active'            => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $assetClass->update($data);

        return redirect()->route('asset_classes.index')->with('success', 'Asset class updated.');
    }

    public function destroy(AssetClass $assetClass)
    {
        $assetClass->delete();
        return redirect()->route('asset_classes.index')->with('success', 'Asset class deleted.');
    }
}
