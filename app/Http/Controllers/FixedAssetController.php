<?php

namespace App\Http\Controllers;

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
        $asset = FixedAsset::findOrFail($id);
        return view('fixed_assets.show', compact('asset'));
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
                    'id'   => $account->id,
                    'code' => $account->account_code,
                    'name' => $account->account_name,
                ];
            });

        return view('fixed_assets.create', compact('assetGroups', 'glAccounts'));
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

        $data['useful_life_months']   = (int)round($usefulLifeYears * 12);
        $data['remaining_life_months']= $data['useful_life_months'];
        $data['yearly_depreciation']  = $usefulLifeYears > 0 ? round(($cost - $salvage) / $usefulLifeYears, 2) : 0;
        $data['monthly_depreciation'] = $data['useful_life_months'] > 0 ? round(($cost - $salvage) / $data['useful_life_months'], 2) : 0;
        $data['net_book_value']       = $cost - ($data['accumulated_depreciation'] ?? 0);
        $data['status']               = 'Active';

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
                    'id'   => $account->id,
                    'code' => $account->account_code,
                    'name' => $account->account_name,
                ];
            });

        return view('fixed_assets.edit', compact('asset', 'assetGroups', 'glAccounts'));
    }

    public function update(Request $request, $id)
    {
        $asset = FixedAsset::findOrFail($id);

        $validated = $request->validate([
            'asset_group'       => 'required|string',
            'asset_description' => 'required|string',
            'cost'              => 'required|numeric|min:0',
            'useful_life_years' => 'required|numeric|min:0',
        ]);

        $data = $request->all();

        $usefulLifeYears = (float)($data['useful_life_years'] ?? 0);
        $cost            = (float)($data['cost'] ?? 0);
        $salvage         = (float)($data['salvage_value'] ?? 0);

        $data['useful_life_months']   = (int)round($usefulLifeYears * 12);
        $data['yearly_depreciation']  = $usefulLifeYears > 0 ? round(($cost - $salvage) / $usefulLifeYears, 2) : 0;
        $data['monthly_depreciation'] = $data['useful_life_months'] > 0 ? round(($cost - $salvage) / $data['useful_life_months'], 2) : 0;
        $data['net_book_value']       = $cost - (float)($data['accumulated_depreciation'] ?? 0);

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
        ]);

        $asset->update([
            'disposal_date' => $validated['disposal_date'],
            'disposal_amount' => $validated['disposal_amount'],
            'disposal_reason' => $validated['disposal_reason'],
            'disposed_by' => Auth::user()->name ?? 'System',
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