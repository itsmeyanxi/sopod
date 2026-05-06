<?php

namespace App\Http\Controllers;

use App\Models\CostCenter;
use App\Models\GlAccount;
use App\Models\Activity;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CostCenterController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $query = CostCenter::with('creator')->orderBy('cost_center_code');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('cost_center_code', 'LIKE', "%{$search}%")
                  ->orWhere('cost_center_name', 'LIKE', "%{$search}%")
                  ->orWhere('division', 'LIKE', "%{$search}%")
                  ->orWhere('department', 'LIKE', "%{$search}%");
            });
        }

        $costCenters = $query->paginate(20)->withQueryString();
        return view('cost_centers.index', compact('costCenters', 'search'));
    }

    public function create()
    {
        $glAccounts = GlAccount::orderBy('account_code')->get(['id', 'account_code', 'account_name']);
        return view('cost_centers.create', compact('glAccounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cost_center_code'  => 'required|string|max:50|unique:cost_centers,cost_center_code',
            'cost_center_name'  => 'required|string|max:255',
            'dimension'         => 'nullable|string|max:100',
            'cost_center_owner' => 'nullable|string|max:100',
            'division'          => 'nullable|string|max:100',
            'department'        => 'nullable|string|max:100',
            'general_cc'        => 'nullable|string|max:100',
            'cc'                => 'nullable|string|max:100',
            'gl_accounts'       => 'nullable|array',
            'lookup'            => 'nullable|string',
            'opex_mapping'      => 'nullable|string',
        ]);

        $glAccounts = [];
        foreach ($request->gl_accounts ?? [] as $gl) {
            $decoded = is_string($gl) ? json_decode($gl, true) : $gl;
            if (is_array($decoded)) $glAccounts[] = $decoded;
        }

        $costCenter = CostCenter::create([
            'cost_center_code'  => strtoupper(trim($request->cost_center_code)),
            'cost_center_name'  => $request->cost_center_name,
            'dimension'         => $request->dimension,
            'cost_center_owner' => $request->cost_center_owner,
            'division'          => $request->division,
            'department'        => $request->department,
            'general_cc'        => $request->general_cc,
            'cc'                => $request->cc,
            'gl_accounts'       => $glAccounts,
            'lookup'            => $request->lookup,
            'opex_mapping'      => $request->opex_mapping,
            'created_by'        => Auth::id(),
        ]);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action'    => 'Created',
            'item'      => $costCenter->cost_center_code,
            'target'    => $costCenter->cost_center_name,
            'type'      => 'Cost Center',
            'message'   => 'Created Cost Center ' . $costCenter->cost_center_code,
        ]);

        return redirect()->route('cost_centers.index')->with('success', 'Cost Center created successfully.');
    }

    public function show($id)
    {
        $costCenter = CostCenter::with('creator')->findOrFail($id);
        $glAccounts = GlAccount::orderBy('account_code')->get(['id', 'account_code', 'account_name']);
        return view('cost_centers.show', compact('costCenter', 'glAccounts'));
    }

    public function edit($id)
    {
        $costCenter = CostCenter::findOrFail($id);
        $glAccounts = GlAccount::orderBy('account_code')->get(['id', 'account_code', 'account_name']);
        return view('cost_centers.edit', compact('costCenter', 'glAccounts'));
    }

    public function update(Request $request, $id)
    {
        $costCenter = CostCenter::findOrFail($id);

        $request->validate([
            'cost_center_code'  => 'required|string|max:50|unique:cost_centers,cost_center_code,' . $id,
            'cost_center_name'  => 'required|string|max:255',
            'dimension'         => 'nullable|string|max:100',
            'cost_center_owner' => 'nullable|string|max:100',
            'division'          => 'nullable|string|max:100',
            'department'        => 'nullable|string|max:100',
            'general_cc'        => 'nullable|string|max:100',
            'cc'                => 'nullable|string|max:100',
            'gl_accounts'       => 'nullable|array',
            'lookup'            => 'nullable|string',
            'opex_mapping'      => 'nullable|string',
        ]);

        $glAccounts = [];
        foreach ($request->gl_accounts ?? [] as $gl) {
            $decoded = is_string($gl) ? json_decode($gl, true) : $gl;
            if (is_array($decoded)) $glAccounts[] = $decoded;
        }

        $costCenter->update([
            'cost_center_code'  => strtoupper(trim($request->cost_center_code)),
            'cost_center_name'  => $request->cost_center_name,
            'dimension'         => $request->dimension,
            'cost_center_owner' => $request->cost_center_owner,
            'division'          => $request->division,
            'department'        => $request->department,
            'general_cc'        => $request->general_cc,
            'cc'                => $request->cc,
            'gl_accounts'       => $glAccounts,
            'lookup'            => $request->lookup,
            'opex_mapping'      => $request->opex_mapping,
        ]);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action'    => 'Updated',
            'item'      => $costCenter->cost_center_code,
            'target'    => $costCenter->cost_center_name,
            'type'      => 'Cost Center',
            'message'   => 'Updated Cost Center ' . $costCenter->cost_center_code,
        ]);

        return redirect()->route('cost_centers.show', $costCenter->id)->with('success', 'Cost Center updated successfully.');
    }

    public function destroy($id)
    {
        $costCenter = CostCenter::findOrFail($id);
        $code = $costCenter->cost_center_code;
        $costCenter->delete();

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action'    => 'Deleted',
            'item'      => $code,
            'target'    => 'N/A',
            'type'      => 'Cost Center',
            'message'   => 'Deleted Cost Center ' . $code,
        ]);

        return redirect()->route('cost_centers.index')->with('success', 'Cost Center deleted.');
    }

    public function search(Request $request)
    {
        $q = $request->input('q', '');
        $results = CostCenter::where('cost_center_code', 'LIKE', "%{$q}%")
            ->orWhere('cost_center_name', 'LIKE', "%{$q}%")
            ->limit(15)
            ->get(['id', 'cost_center_code', 'cost_center_name', 'dimension', 'department']);
        return response()->json($results);
    }

   public function importCostCenters(Request $request)
{
    $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv|max:10240']);

    try {
        $spreadsheet = IOFactory::load($request->file('file')->getPathname());
        $sheet       = $spreadsheet->getActiveSheet();

        // Unmerge all cells before reading — merged cells cause toArray() to
        // repeat the value across all merged columns, which corrupts header detection.
       foreach (array_keys($sheet->getMergeCells()) as $mergeRange) {
            $sheet->unmergeCells($mergeRange);
        }

        $rows = $sheet->toArray(null, true, true, true); // keyed by column letter

        // ── Column name → model field mapping ───────────────────────────
        $columnMap = [
            'cost center code'   => 'cost_center_code',
            'cc code'            => 'cost_center_code',
            'cost center name'   => 'cost_center_name',
            'cost center  name'  => 'cost_center_name',
            'cc name'            => 'cost_center_name',
            'dimension'          => 'dimension',
            'cost center owner'  => 'cost_center_owner',
            'owner'              => 'cost_center_owner',
            'division'           => 'division',
            'department'         => 'department',
            'general cc'         => 'general_cc',
            'generalcc'          => 'general_cc',
            'cc'                 => 'cc',
            'gl account names'   => 'gl_accounts_raw',
            'gl accounts'        => 'gl_accounts_raw',
            'gl account'         => 'gl_accounts_raw',
            'lookup'             => 'lookup',
            'opex mapping'       => 'opex_mapping',
            'opex'               => 'opex_mapping',
        ];

        $requiredFields  = ['cost_center_code', 'cost_center_name'];
        $rowKeys         = array_keys($rows);
        $bestHeaderIndex = null;
        $mappedHeaders   = [];
        $bestScore       = 0;

        foreach ($rowKeys as $idx => $i) {
            // Skip NOTE/comment rows during header detection
            $firstCellVal = strtolower(trim((string)($rows[$i][array_key_first($rows[$i])] ?? '')));
            if (str_starts_with($firstCellVal, 'note:') || str_contains($firstCellVal, 'gl account names are matched')) {
                continue;
            }

            // Merge with next row to handle split headers, skip if next row is a NOTE row
            $combinedRow = $rows[$i];
            $nextIdx = $rowKeys[$idx + 1] ?? null;
            if ($nextIdx) {
                $nextFirstCell = strtolower(trim((string)($rows[$nextIdx][array_key_first($rows[$nextIdx])] ?? '')));
                $isNoteRow = str_starts_with($nextFirstCell, 'note:') || str_contains($nextFirstCell, 'gl account names are matched');
                if (!$isNoteRow) {
                    foreach ($rows[$nextIdx] as $letter => $cell) {
                        if (!empty(trim((string)$cell)) && empty(trim((string)($combinedRow[$letter] ?? '')))) {
                            $combinedRow[$letter] = $cell;
                        }
                    }
                }
            }

            $tmp        = [];
            $seenFields = [];
            foreach ($combinedRow as $letter => $cell) {
                $h = preg_replace('/\s+/', ' ', strtolower(trim((string)$cell)));
                foreach ($columnMap as $key => $field) {
                    if ($h === $key && !in_array($field, $seenFields)) {
                        $tmp[$letter] = $field;
                        $seenFields[] = $field;
                        break;
                    }
                }
            }

            $score = count(array_intersect($requiredFields, array_values($tmp)));
            if ($score > $bestScore) {
                $bestScore       = $score;
                $bestHeaderIndex = $i;
                $mappedHeaders   = $tmp;
            }
        }

        if (!$mappedHeaders || $bestScore < count($requiredFields)) {
            return response()->json([
                'success' => false,
                'message' => 'Header row not found. Expected columns: Cost Center Code, Cost Center Name, Dimension, Cost Center Owner, Division, Department, General CC, CC, GL Account Names, OPEX Mapping',
            ], 422);
        }

        // ── Build GL lookup: account_name/account_code (lower) → record ──
        $glByName = [];
        $glByCode = [];
        GlAccount::all(['id', 'account_code', 'account_name'])->each(function ($gl) use (&$glByName, &$glByCode) {
            $glByName[strtolower(trim($gl->account_name))] = $gl;
            $glByCode[strtolower(trim($gl->account_code))] = $gl;
        });

        $created      = 0;
        $updated      = 0;
        $skipped      = 0;
        $passedHeader = false;

        foreach ($rows as $i => $row) {
            if (!$passedHeader) {
                if ($i == $bestHeaderIndex) $passedHeader = true;
                continue;
            }

            // Skip sub-header rows
            $firstVals = array_map(fn($v) => strtolower(trim((string)$v)), array_values($row));
            if (in_array('division', $firstVals) && in_array('department', $firstVals)) {
                continue;
            }

            // Skip NOTE rows
            $firstCell = strtolower(trim((string)($row[array_key_first($row)] ?? '')));
            if (str_starts_with($firstCell, 'note:') || str_contains($firstCell, 'gl account names are matched')) {
                continue;
            }

            $data = [];
            foreach ($mappedHeaders as $letter => $field) {
                $data[$field] = trim((string)($row[$letter] ?? ''));
            }

            $code = strtoupper($data['cost_center_code'] ?? '');
            $name = $data['cost_center_name'] ?? '';

            if (!$code) { $skipped++; continue; }
            if (!$name) $name = $code;

            // ── Resolve GL accounts ──
            $glAccounts = [];
            if (!empty($data['gl_accounts_raw'])) {
                foreach (array_map('trim', explode(',', $data['gl_accounts_raw'])) as $entry) {
                    if (!$entry) continue;
                    $entryLower = strtolower($entry);
                    $gl = $glByName[$entryLower] ?? $glByCode[$entryLower] ?? null;
                    if ($gl) {
                        $glAccounts[] = [
                            'id'           => $gl->id,
                            'account_code' => $gl->account_code,
                            'account_name' => $gl->account_name,
                        ];
                    }
                }
            }

            $payload = [
                'cost_center_name'  => $name,
                'dimension'         => $data['dimension']         ?? null ?: null,
                'cost_center_owner' => $data['cost_center_owner'] ?? null ?: null,
                'division'          => $data['division']          ?? null ?: null,
                'department'        => $data['department']        ?? null ?: null,
                'general_cc'        => $data['general_cc']        ?? null ?: null,
                'cc'                => $data['cc']                ?? null ?: null,
                'gl_accounts'       => $glAccounts,
                'lookup'            => $data['lookup']            ?? null ?: null,
                'opex_mapping'      => $data['opex_mapping']      ?? null ?: null,
                'created_by'        => auth()->id(),
            ];

            try {
                $existing = CostCenter::where('cost_center_code', $code)->first();
                if ($existing) {
                    unset($payload['created_by']);
                    $existing->update($payload);
                    $updated++;
                } else {
                    CostCenter::create(array_merge(['cost_center_code' => $code], $payload));
                    $created++;
                }
            } catch (\Exception $rowEx) {
                $skipped++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Import complete: {$created} created, {$updated} updated, {$skipped} skipped.",
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
        ]);

    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'Import failed: ' . $e->getMessage()], 500);
    }
}

public function debugImport(Request $request)
{
    $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv|max:10240']);

    $spreadsheet = IOFactory::load($request->file('file')->getPathname());
    $sheet = $spreadsheet->getActiveSheet();

    $merges = $sheet->getMergeCells();

    // Try unmerge
    foreach (array_keys($merges) as $range) {
        $sheet->unmergeCells($range);
    }

    $rows = $sheet->toArray(null, true, true, true);

    $firstRows = array_slice($rows, 0, 4, true);

    return response()->json([
        'merge_cells'     => $merges,
        'merge_keys'      => array_keys($merges),
        'total_rows'      => count($rows),
        'first_rows'      => $firstRows,
        'row_keys'        => array_keys($rows),
    ]);
}
}