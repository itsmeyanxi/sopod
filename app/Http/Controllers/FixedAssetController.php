<?php

namespace App\Http\Controllers;

use App\Models\AssetClass;
use App\Models\FixedAsset;
use App\Models\GlAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FixedAssetController extends Controller
{
    public function index(Request $request)
    {
        $query = FixedAsset::query();

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

        if ($request->filled('status')) {
            if ($request->status === 'Active') {
                $query->active();
            } elseif ($request->status === 'Disposed') {
                $query->disposed();
            } elseif ($request->status === 'Fully Depreciated') {
                $query->where('remaining_life_months', 0)->whereNull('disposal_date');
            }
        }

        if ($request->filled('asset_class')) {
            $query->where('asset_class', $request->asset_class);
        }

        $assets = $query->orderBy('asset_group')->orderBy('asset_code')->paginate(50);

        $assetGroups  = FixedAsset::withDisposed()->select('asset_group')->distinct()->orderBy('asset_group')->pluck('asset_group');
        $assetClasses = FixedAsset::withDisposed()->select('asset_class')->distinct()->whereNotNull('asset_class')->orderBy('asset_class')->pluck('asset_class');

        $summary = [
            'total_assets'    => FixedAsset::withDisposed()->count(),
            'total_cost'      => FixedAsset::withDisposed()->sum('cost'),
            'total_nbv'       => FixedAsset::withDisposed()->sum('net_book_value'),
            'total_accum_dep' => FixedAsset::withDisposed()->sum('accumulated_depreciation'),
            'active_count'    => FixedAsset::active()->count(),
            'disposed_count'  => FixedAsset::disposed()->count(),
            'fully_dep_count' => FixedAsset::where('remaining_life_months', 0)->whereNull('disposal_date')->count(),
        ];

        return view('fixed_assets.index', compact('assets', 'assetGroups', 'assetClasses', 'summary'));
    }

    public function show($id)
    {
        $asset = FixedAsset::withDisposed()->findOrFail($id);

        $glAccounts = GlAccount::orderBy('account_code')
            ->get(['id', 'account_code', 'account_name'])
            ->map(fn($a) => [
                'code'   => $a->account_code,
                'name'   => $a->account_name,
                'search' => strtolower($a->account_code . ' ' . $a->account_name),
            ])->toArray();

        return view('fixed_assets.show', compact('asset', 'glAccounts'));
    }

    public function create()
    {
        $assetGroups = [
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

        $glAccounts = GlAccount::orderBy('account_code')
            ->get(['id', 'account_code', 'account_name'])
            ->map(function ($account) {
                return [
                    'id'     => $account->id,
                    'code'   => $account->account_code,
                    'name'   => $account->account_name,
                    'search' => strtolower($account->account_code . ' ' . $account->account_name),
                ];
            });

        // Approved POs for the PO selector
        $approvedPos = DB::table('purchase_orders')
            ->where('status', 'Approved')
            ->orderBy('order_date', 'desc')
            ->get(['id', 'po_no', 'supplier', 'order_date']);

        // Asset classes for auto-fill on select
        $assetClasses = AssetClass::active()
            ->orderBy('asset_group')
            ->orderBy('asset_class')
            ->get(['id', 'asset_group', 'asset_class', 'useful_life_months', 'gl_account', 'depreciation_account', 'dep_type']);

        return view('fixed_assets.create', compact('assetGroups', 'glAccounts', 'approvedPos', 'assetClasses'));
    }

    /**
     * Return PO items as JSON for the PO selector AJAX call.
     */
    public function getPoItems(int $poId)
    {
        $items = DB::table('purchase_order_items')
            ->where('purchase_order_id', $poId)
            ->get(['id', 'item_no', 'description', 'qty', 'unit_price', 'total']);

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_group'       => 'required|string',
            'asset_description' => 'required|string',
            'cost'              => 'required|numeric|min:0',
            'acquisition_date'  => 'nullable|date',
            'useful_life_years' => 'required|numeric|min:0',
        ]);

        $data = $request->all();
        $data['created_by'] = Auth::user()->name ?? 'System';

        $usefulLifeYears = (float)($data['useful_life_years'] ?? 0);
        $cost            = (float)($data['cost'] ?? 0);
        $salvage         = (float)($data['salvage_value'] ?? 0);

        // If useful_life_months was submitted directly (from asset class auto-fill), use it; otherwise derive from years
        $usefulLifeMonths = isset($data['useful_life_months']) && (int)$data['useful_life_months'] > 0
            ? (int)$data['useful_life_months']
            : (int)round($usefulLifeYears * 12);

        $data['useful_life_months']   = $usefulLifeMonths;
        $data['useful_life_years']    = $usefulLifeMonths > 0 ? round($usefulLifeMonths / 12, 2) : $usefulLifeYears;
        $data['remaining_life_months']= $usefulLifeMonths;
        $data['yearly_depreciation']  = $data['useful_life_years'] > 0 ? round(($cost - $salvage) / $data['useful_life_years'], 2) : 0;
        $data['monthly_depreciation'] = $usefulLifeMonths > 0 ? round(($cost - $salvage) / $usefulLifeMonths, 2) : 0;
        $data['net_book_value']       = $cost - ($data['accumulated_depreciation'] ?? 0) - ($data['disposal_amount'] ?? 0);
        $data['status']               = 'Active';

        // Auto-calculate dep_end_date if dep_start_date and useful_life_months are set
        if (!empty($data['dep_start_date']) && $usefulLifeMonths > 0 && empty($data['dep_end_date'])) {
            $data['dep_end_date'] = \Carbon\Carbon::parse($data['dep_start_date'])
                ->addMonths($usefulLifeMonths)
                ->format('Y-m-d');
        }

        // Clean up PO fields
        $data['purchase_order_id']      = $request->integer('purchase_order_id') ?: null;
        $data['purchase_order_item_id'] = $request->integer('purchase_order_item_id') ?: null;

        FixedAsset::create($data);

        return redirect()->route('fixed_assets.index')->with('success', 'Fixed asset created successfully.');
    }

    public function edit($id)
    {
        $asset = FixedAsset::findOrFail($id);

        $assetGroups = [
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

        $glAccounts = GlAccount::orderBy('account_code')
            ->get(['id', 'account_code', 'account_name'])
            ->map(function ($account) {
                return [
                    'id'     => $account->id,
                    'code'   => $account->account_code,
                    'name'   => $account->account_name,
                    'search' => strtolower($account->account_code . ' ' . $account->account_name),
                ];
            });

        $assetClasses = AssetClass::active()
            ->orderBy('asset_group')
            ->orderBy('asset_class')
            ->get(['id', 'asset_group', 'asset_class', 'useful_life_months', 'gl_account', 'depreciation_account', 'dep_type']);

        return view('fixed_assets.edit', compact('asset', 'assetGroups', 'glAccounts', 'assetClasses'));
    }

    public function update(Request $request, $id)
    {
        $asset = FixedAsset::findOrFail($id);

        $validated = $request->validate([
            'asset_group'       => 'required|string',
            'asset_description' => 'required|string',
            'cost'              => 'required|numeric|min:0',
            'useful_life_months'=> 'nullable|integer|min:0',
        ]);

        $data = $request->all();

        $cost            = (float)($data['cost'] ?? 0);
        $salvage         = (float)($data['salvage_value'] ?? 0);
        $usefulLifeMonths = isset($data['useful_life_months']) && (int)$data['useful_life_months'] > 0
            ? (int)$data['useful_life_months']
            : $asset->useful_life_months;

        $data['useful_life_months']   = $usefulLifeMonths;
        $data['useful_life_years']    = $usefulLifeMonths > 0 ? round($usefulLifeMonths / 12, 2) : 0;
        $data['yearly_depreciation']  = $data['useful_life_years'] > 0 ? round(($cost - $salvage) / $data['useful_life_years'], 2) : 0;
        $data['monthly_depreciation'] = $usefulLifeMonths > 0 ? round(($cost - $salvage) / $usefulLifeMonths, 2) : 0;
        $data['net_book_value']       = $cost - (float)($data['accumulated_depreciation'] ?? 0) - (float)($data['disposal_amount'] ?? 0);

        // Auto-calculate dep_end_date if dep_start_date and useful_life_months are set and dep_end_date is empty
        if (!empty($data['dep_start_date']) && $usefulLifeMonths > 0 && empty($data['dep_end_date'])) {
            $data['dep_end_date'] = \Carbon\Carbon::parse($data['dep_start_date'])
                ->addMonths($usefulLifeMonths)
                ->format('Y-m-d');
        }

        $asset->update($data);

        return redirect()->route('fixed_assets.show', $asset->id)->with('success', 'Fixed asset updated successfully.');
    }

    public function destroy($id)
    {
        $asset = FixedAsset::findOrFail($id);
        $asset->delete();

        return redirect()->route('fixed_assets.index')->with('success', 'Fixed asset deleted successfully.');
    }

    public function dispose(Request $request, $id)
    {
        $asset = FixedAsset::withDisposed()->findOrFail($id);

        if ($asset->disposal_date) {
            return redirect()->back()->with('error', 'This asset is already disposed.');
        }

        $validated = $request->validate([
            'disposal_date' => 'required|date',
            'disposal_amount' => 'required|numeric|min:0',
            'disposal_reason' => 'required|string|max:1000',
            'gain_loss_gl_account' => 'nullable|string',
        ]);

        // Recalculate NBV at time of disposal
        $nbv = $asset->cost - $asset->accumulated_depreciation;
        $gainLoss = $validated['disposal_amount'] - $nbv;

        $asset->update([
            'disposal_date' => $validated['disposal_date'],
            'disposal_amount' => $validated['disposal_amount'],
            'disposal_reason' => $validated['disposal_reason'],
            'gain_loss_gl_account' => $validated['gain_loss_gl_account'] ?? null,
            'disposed_by' => Auth::user()->name ?? 'System',
            'net_book_value' => 0,
            'status' => 'Disposed',
        ]);

        return redirect()->route('fixed_assets.show', $asset->id)
            ->with('success', 'Asset disposed successfully.');
    }

    public function summary()
    {
        $groups = [
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

        $summaryData = [];
        foreach ($groups as $group) {
            $summaryData[$group] = [
                'cost'                     => FixedAsset::withDisposed()->where('asset_group', $group)->sum('cost'),
                'additions'                => FixedAsset::withDisposed()->where('asset_group', $group)->sum('additions'),
                'disposals'                => FixedAsset::withDisposed()->where('asset_group', $group)->sum('disposal_amount'),
                'accumulated_depreciation' => FixedAsset::withDisposed()->where('asset_group', $group)->sum('accumulated_depreciation'),
                'monthly_depreciation'     => FixedAsset::withDisposed()->where('asset_group', $group)->sum('monthly_depreciation'),
                'net_book_value'           => FixedAsset::withDisposed()->where('asset_group', $group)->sum('net_book_value'),
                'asset_count'              => FixedAsset::withDisposed()->where('asset_group', $group)->count(),
            ];
        }

        $totals = [
            'cost'                     => FixedAsset::withDisposed()->sum('cost'),
            'additions'                => FixedAsset::withDisposed()->sum('additions'),
            'disposals'                => FixedAsset::withDisposed()->sum('disposal_amount'),
            'accumulated_depreciation' => FixedAsset::withDisposed()->sum('accumulated_depreciation'),
            'monthly_depreciation'     => FixedAsset::withDisposed()->sum('monthly_depreciation'),
            'net_book_value'           => FixedAsset::withDisposed()->sum('net_book_value'),
            'asset_count'              => FixedAsset::withDisposed()->count(),
        ];

        return view('fixed_assets.summary', compact('summaryData', 'totals', 'groups'));
    }

    /**
     * Report: Depreciation per month / year / cost center
     */
    public function reportDepreciation(Request $request)
    {
        $year  = $request->input('year', date('Y'));
        $costCenter = $request->input('cost_center');

        $query = FixedAsset::withDisposed()
            ->whereNotNull('dep_start_date')
            ->where('monthly_depreciation', '>', 0);

        if ($costCenter) {
            $query->where('cost_center_name', $costCenter);
        }

        $assets = $query->orderBy('asset_group')->orderBy('asset_code')->get();

        // Build monthly depreciation grid
        $depData = [];
        foreach ($assets as $asset) {
            $start = $asset->dep_start_date;
            $end   = $asset->dep_end_date ?? $start->copy()->addMonths($asset->useful_life_months);

            $row = [
                'asset'   => $asset,
                'months'  => [],
                'total'   => 0,
            ];

            for ($m = 1; $m <= 12; $m++) {
                $monthDate = \Carbon\Carbon::createFromDate($year, $m, 1);
                $isActive  = $monthDate->gte($start->startOfMonth()) && $monthDate->lte($end->startOfMonth());
                $amount    = $isActive ? (float)$asset->monthly_depreciation : 0;
                $row['months'][$m] = $amount;
                $row['total']     += $amount;
            }

            if ($row['total'] > 0) {
                $depData[] = $row;
            }
        }

        $costCenters = FixedAsset::withDisposed()
            ->select('cost_center_name')
            ->distinct()
            ->whereNotNull('cost_center_name')
            ->where('cost_center_name', '!=', '')
            ->orderBy('cost_center_name')
            ->pluck('cost_center_name');

        return view('fixed_assets.report_depreciation', compact('depData', 'year', 'costCenter', 'costCenters'));
    }

    /**
     * Report: Asset Transaction Summary
     */
    public function reportTransactions(Request $request)
    {
        $query = FixedAsset::withDisposed();

        if ($request->filled('asset_group')) {
            $query->where('asset_group', $request->asset_group);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('acquisition_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('acquisition_date', '<=', $request->date_to);
        }

        $assets = $query->orderBy('asset_group')->orderBy('acquisition_date', 'desc')->paginate(100)->withQueryString();

        $groups = [
            'Leasehold Improvement', 'Transportation Equipment', 'Machineries And Equipment',
            'Office Equipment', 'Furniture And Fixtures', 'Tools', 'Building',
            'Right of Use Asset', 'Fixed Asset Clearing',
        ];

        $summary = [
            'total_cost'       => $query->sum('cost'),
            'total_accum_dep'  => $query->sum('accumulated_depreciation'),
            'total_disposal'   => $query->sum('disposal_amount'),
            'total_nbv'        => $query->sum('net_book_value'),
            'total_assets'     => $query->count(),
        ];

        return view('fixed_assets.report_transactions', compact('assets', 'groups', 'summary'));
    }

    /**
     * Report: Assets per Cost Center
     */
    public function reportCostCenter(Request $request)
    {
        $selectedCenter = $request->input('cost_center');

        $costCenters = FixedAsset::withDisposed()
            ->select('cost_center_name')
            ->selectRaw('COUNT(*) as asset_count')
            ->selectRaw('SUM(cost) as total_cost')
            ->selectRaw('SUM(accumulated_depreciation) as total_accum_dep')
            ->selectRaw('SUM(net_book_value) as total_nbv')
            ->selectRaw('SUM(monthly_depreciation) as total_monthly_dep')
            ->groupBy('cost_center_name')
            ->orderBy('cost_center_name')
            ->get();

        $assets = null;
        if ($selectedCenter) {
            $assets = FixedAsset::withDisposed()
                ->where('cost_center_name', $selectedCenter)
                ->orderBy('asset_group')
                ->orderBy('asset_code')
                ->get();
        }

        return view('fixed_assets.report_cost_center', compact('costCenters', 'assets', 'selectedCenter'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:20480',
        ]);

        try {
            $file        = $request->file('file');
            $reader      = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load($file->getPathname());

            $sheet = $spreadsheet->getSheetByName('Monthly Depreciation Sched');
            if (!$sheet) {
                $sheet = $spreadsheet->getActiveSheet();
            }

            $highestRow = $sheet->getHighestRow();
            $imported   = 0;
            $skipped    = 0;

            for ($row = 5; $row <= $highestRow; $row++) {
                $assetGroup = trim($sheet->getCell('B' . $row)->getCalculatedValue() ?? '');
                $assetDesc  = trim($sheet->getCell('S' . $row)->getCalculatedValue() ?? '');

                if (empty($assetGroup) || empty($assetDesc)) { $skipped++; continue; }

                $cost = (float)($sheet->getCell('AK' . $row)->getCalculatedValue() ?? 0);
                if ($cost <= 0) { $skipped++; continue; }

                $datePosted      = $this->parseExcelDate($sheet->getCell('C' . $row)->getCalculatedValue());
                $acquisitionDate = $this->parseExcelDate($sheet->getCell('E' . $row)->getCalculatedValue());
                $depStartDate    = $this->parseExcelDate($sheet->getCell('F' . $row)->getCalculatedValue());
                $depEndDate      = $this->parseExcelDate($sheet->getCell('G' . $row)->getCalculatedValue());
                $disposalDate    = $this->parseExcelDate($sheet->getCell('H' . $row)->getCalculatedValue());

                $usefulLifeYears  = (float)($sheet->getCell('AP' . $row)->getCalculatedValue() ?? 0);
                $usefulLifeMonths = (int)($sheet->getCell('AQ' . $row)->getCalculatedValue() ?? 0);
                $remainingMonths  = max(0, (int)($sheet->getCell('AR' . $row)->getCalculatedValue() ?? 0));
                $salvageValue     = (float)($sheet->getCell('AS' . $row)->getCalculatedValue() ?? 0);
                $yearlyDep        = (float)($sheet->getCell('AT' . $row)->getCalculatedValue() ?? 0);
                $monthlyDep       = (float)($sheet->getCell('AU' . $row)->getCalculatedValue() ?? 0);
                $additions        = (float)($sheet->getCell('AW' . $row)->getCalculatedValue() ?? 0);
                $reclass          = (float)($sheet->getCell('AX' . $row)->getCalculatedValue() ?? 0);
                $disposal         = (float)($sheet->getCell('AY' . $row)->getCalculatedValue() ?? 0);

                $monthsElapsed = $usefulLifeMonths - $remainingMonths;
                $accumDep      = $monthsElapsed * $monthlyDep;
                $nbv           = $cost - $accumDep;

                $status = 'Active';
                if ($disposalDate)          $status = 'Disposed';
                elseif ($remainingMonths <= 0) $status = 'Fully Depreciated';

                FixedAsset::create([
                    'serial_engine_no'         => trim($sheet->getCell('A'  . $row)->getCalculatedValue() ?? ''),
                    'asset_group'              => $assetGroup,
                    'date_posted'              => $datePosted,
                    'acquisition_date'         => $acquisitionDate,
                    'dep_start_date'           => $depStartDate,
                    'dep_end_date'             => $depEndDate,
                    'disposal_date'            => $disposalDate,
                    'dep_type'                 => trim($sheet->getCell('J'  . $row)->getCalculatedValue() ?? 'Straight Line'),
                    'reference_apv_jv'         => trim($sheet->getCell('K'  . $row)->getCalculatedValue() ?? ''),
                    'reference_reversal'       => trim($sheet->getCell('L'  . $row)->getCalculatedValue() ?? ''),
                    'vendor_name'              => trim($sheet->getCell('M'  . $row)->getCalculatedValue() ?? ''),
                    'plate_no'                 => trim($sheet->getCell('N'  . $row)->getCalculatedValue() ?? ''),
                    'assigned_person'          => trim($sheet->getCell('P'  . $row)->getCalculatedValue() ?? ''),
                    'employee_name'            => trim($sheet->getCell('Q'  . $row)->getCalculatedValue() ?? ''),
                    'quantity'                 => max(1, (int)($sheet->getCell('R' . $row)->getCalculatedValue() ?? 1)),
                    'asset_description'        => $assetDesc,
                    'asset_code'               => trim($sheet->getCell('AA' . $row)->getCalculatedValue() ?? ''),
                    'gl_account'               => trim($sheet->getCell('AD' . $row)->getCalculatedValue() ?? ''),
                    'asset_class'              => trim($sheet->getCell('AE' . $row)->getCalculatedValue() ?? ''),
                    'cost_center_name'         => trim($sheet->getCell('AF' . $row)->getCalculatedValue() ?? ''),
                    'cost_center_code'         => trim($sheet->getCell('AI' . $row)->getCalculatedValue() ?? ''),
                    'depreciation_account'     => trim($sheet->getCell('AJ' . $row)->getCalculatedValue() ?? ''),
                    'cost'                     => $cost,
                    'year_in_service'          => (int)($sheet->getCell('AN' . $row)->getCalculatedValue() ?? 0) ?: null,
                    'year_end_service'         => (int)($sheet->getCell('AO' . $row)->getCalculatedValue() ?? 0) ?: null,
                    'useful_life_years'        => $usefulLifeYears,
                    'useful_life_months'       => $usefulLifeMonths,
                    'remaining_life_months'    => $remainingMonths,
                    'salvage_value'            => $salvageValue,
                    'yearly_depreciation'      => $yearlyDep,
                    'monthly_depreciation'     => $monthlyDep,
                    'additions'                => $additions,
                    'reclass_affiliates'       => $reclass,
                    'disposal_amount'          => $disposal,
                    'accumulated_depreciation' => round($accumDep, 2),
                    'net_book_value'           => round(max(0, $nbv), 2),
                    'status'                   => $status,
                    'created_by'               => Auth::user()->name ?? 'Import',
                ]);

                $imported++;
            }

            return redirect()->route('fixed_assets.index')->with('success', "Import complete: {$imported} assets imported, {$skipped} rows skipped.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    private function parseExcelDate($value)
    {
        if (empty($value)) return null;
        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}