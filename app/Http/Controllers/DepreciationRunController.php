<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\DepreciationRun;
use App\Models\FixedAsset;
use App\Models\GlAccount;
use App\Models\JournalVoucher;
use App\Models\JournalVoucherLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DepreciationRunController extends Controller
{
    public function index(Request $request)
    {
        $query = DepreciationRun::query();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('run_number', 'LIKE', "%{$s}%")
                  ->orWhere('description', 'LIKE', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('period')) {
            $query->where('period', $request->period);
        }

        $runs = $query->orderByDesc('period')->orderByDesc('id')->paginate(50);

        $summary = [
            'total'  => DepreciationRun::count(),
            'draft'  => DepreciationRun::draft()->count(),
            'posted' => DepreciationRun::posted()->count(),
            'void'   => DepreciationRun::where('status', 'Void')->count(),
        ];

        return view('depreciation_runs.index', compact('runs', 'summary'));
    }

    public function create()
    {
        $assetGroups = FixedAsset::withoutGlobalScope('not_disposed')
            ->select('asset_group')
            ->distinct()
            ->orderBy('asset_group')
            ->pluck('asset_group');

        $nextRunNumber = $this->generateNextRunNumber();

        return view('depreciation_runs.create', compact('assetGroups', 'nextRunNumber'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'period'       => 'required|string|regex:/^\d{4}-\d{2}$/',
            'description'  => 'required|string|max:500',
            'asset_groups' => 'required|array|min:1',
        ]);

        // Prevent duplicate run for same period
        $existing = DepreciationRun::where('period', $request->period)
            ->whereIn('status', ['Draft', 'Posted'])
            ->first();
        if ($existing) {
            return back()->withInput()->with('error', "A depreciation run for {$request->period} already exists ({$existing->run_number}).");
        }

        // Fetch active assets in the selected groups with remaining life
        $assets = FixedAsset::active()
            ->whereIn('asset_group', $request->asset_groups)
            ->where('remaining_life_months', '>', 0)
            ->get();

        if ($assets->isEmpty()) {
            return back()->withInput()->with('error', 'No active assets with remaining life found for the selected groups.');
        }

        $entries = [];
        $totalAmount = 0;

        foreach ($assets as $asset) {
            $monthly = (float) $asset->monthly_depreciation;
            if ($monthly <= 0) continue;

            $entries[] = [
                'asset_id'              => $asset->id,
                'asset_code'            => $asset->asset_code,
                'asset_description'     => $asset->asset_description,
                'asset_group'           => $asset->asset_group,
                'depreciation_account'  => $asset->depreciation_account,
                'monthly_depreciation'  => $monthly,
                'accumulated_before'    => (float) $asset->accumulated_depreciation,
                'remaining_before'      => $asset->remaining_life_months,
            ];

            $totalAmount += $monthly;
        }

        if (empty($entries)) {
            return back()->withInput()->with('error', 'No valid depreciation amounts found for the selected assets.');
        }

        DB::beginTransaction();
        try {
            $run = DepreciationRun::create([
                'run_number'           => $this->generateNextRunNumber(),
                'period'               => $request->period,
                'description'          => $request->description,
                'asset_groups'         => $request->asset_groups,
                'depreciation_entries' => $entries,
                'total_debit'          => $totalAmount,
                'total_credit'         => $totalAmount,
                'status'               => 'Draft',
                'prepared_by'          => Auth::user()->name,
                'created_by'           => Auth::id(),
            ]);

            DB::commit();

            Activity::create([
                'user_name' => Auth::user()->name,
                'action'    => 'Created',
                'item'      => $run->run_number,
                'target'    => $run->period,
                'type'      => 'Depreciation Run',
                'message'   => "Created Depreciation Run {$run->run_number} for {$run->period}",
            ]);

            return redirect()->route('depreciation_runs.show', $run->id)
                ->with('success', 'Depreciation Run created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error creating run: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $run = DepreciationRun::findOrFail($id);
        return view('depreciation_runs.show', compact('run'));
    }

    public function post(Request $request, $id)
    {
        $run = DepreciationRun::findOrFail($id);

        if ($run->status !== 'Draft') {
            return back()->with('error', 'Only Draft runs can be posted.');
        }

        // Prevent duplicate posting for same period
        $alreadyPosted = DepreciationRun::where('period', $run->period)
            ->where('status', 'Posted')
            ->where('id', '!=', $run->id)
            ->first();
        if ($alreadyPosted) {
            return back()->with('error', "Period {$run->period} already has a posted run: {$alreadyPosted->run_number}.");
        }

        DB::beginTransaction();
        try {
            $entries = $run->depreciation_entries ?? [];
            $jvNumber = $this->generateNextJvNumber();

            // Create the Journal Voucher
            $jv = JournalVoucher::create([
                'jv_number'        => $jvNumber,
                'jv_date'          => now()->toDateString(),
                'transaction_type' => 'depreciation',
                'description'      => "Depreciation Run: {$run->run_number} | Period: {$run->period}",
                'reference_no'     => $run->run_number,
                'department'       => 'Accounting',
                'total_debit'      => $run->total_debit,
                'total_credit'     => $run->total_credit,
                'status'           => 'Posted',
                'posted_at'        => now(),
                'prepared_by'      => $run->prepared_by,
                'approved_by'      => $request->approved_by ?? Auth::user()->name,
                'created_by'       => Auth::user()->name,
            ]);

            // One debit line per asset (Depreciation Expense)
            foreach ($entries as $i => $entry) {
                JournalVoucherLine::create([
                    'journal_voucher_id' => $jv->id,
                    'account_code'       => $entry['depreciation_account'] ?? 'DEP-EXP',
                    'account_name'       => 'Depreciation Expense',
                    'line_description'   => $entry['asset_code'] . ' — ' . $entry['asset_description'],
                    'debit'              => $entry['monthly_depreciation'],
                    'credit'             => 0,
                    'sort_order'         => $i,
                ]);
            }

            // Single credit line — Accumulated Depreciation
            JournalVoucherLine::create([
                'journal_voucher_id' => $jv->id,
                'account_code'       => 'ACCUM-DEP',
                'account_name'       => 'Accumulated Depreciation',
                'line_description'   => "Monthly depreciation — {$run->period}",
                'debit'              => 0,
                'credit'             => $run->total_credit,
                'sort_order'         => count($entries),
            ]);

            // Update each fixed asset
            foreach ($entries as $entry) {
                $asset = FixedAsset::withoutGlobalScope('not_disposed')->find($entry['asset_id']);
                if (!$asset) continue;

                $newAccum   = (float) $asset->accumulated_depreciation + $entry['monthly_depreciation'];
                $newRemain  = max(0, $asset->remaining_life_months - 1);
                $newNBV     = max(0, (float) $asset->cost - $newAccum);

                $asset->update([
                    'accumulated_depreciation' => $newAccum,
                    'remaining_life_months'    => $newRemain,
                    'net_book_value'           => $newNBV,
                ]);
            }

            // Mark the run as posted
            $run->update([
                'status'      => 'Posted',
                'jv_number'   => $jvNumber,
                'posted_at'   => now(),
                'approved_by' => $request->approved_by ?? Auth::user()->name,
            ]);

            DB::commit();

            Activity::create([
                'user_name' => Auth::user()->name,
                'action'    => 'Posted',
                'item'      => $run->run_number,
                'target'    => $jvNumber,
                'type'      => 'Depreciation Run',
                'message'   => "Posted Depreciation Run {$run->run_number} → JV {$jvNumber}",
            ]);

            return redirect()->route('depreciation_runs.show', $run->id)
                ->with('success', "Depreciation Run posted. Journal Voucher {$jvNumber} created.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error posting run: ' . $e->getMessage());
        }
    }

    public function void($id)
    {
        $run = DepreciationRun::findOrFail($id);

        if ($run->status === 'Void') {
            return back()->with('error', 'This run is already voided.');
        }

        if ($run->status === 'Posted') {
            return back()->with('error', 'Posted runs cannot be voided here. Please reverse the Journal Voucher manually.');
        }

        $run->update(['status' => 'Void']);

        Activity::create([
            'user_name' => Auth::user()->name,
            'action'    => 'Voided',
            'item'      => $run->run_number,
            'type'      => 'Depreciation Run',
            'message'   => "Voided Depreciation Run {$run->run_number}",
        ]);

        return back()->with('success', 'Depreciation Run voided.');
    }

    public function destroy($id)
    {
        $run = DepreciationRun::findOrFail($id);

        if ($run->status === 'Posted') {
            return back()->with('error', 'Cannot delete a posted run.');
        }

        $run->delete();
        return redirect()->route('depreciation_runs.index')->with('success', 'Depreciation Run deleted.');
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function generateNextRunNumber(): string
    {
        $prefix = 'DR-' . date('Ym') . '-';
        $last   = DepreciationRun::where('run_number', 'LIKE', "{$prefix}%")
            ->orderByDesc('run_number')
            ->value('run_number');

        $next = 1;
        if ($last) {
            $next = (int) substr($last, -4) + 1;
        }

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    private function generateNextJvNumber(): string
    {
        $prefix = 'JV-' . date('Ym') . '-';
        $last   = JournalVoucher::where('jv_number', 'LIKE', "{$prefix}%")
            ->orderByDesc('jv_number')
            ->value('jv_number');

        $next = 1;
        if ($last) {
            $next = (int) substr($last, -4) + 1;
        }

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
