<?php

namespace App\Http\Controllers;

use App\Models\InHouseBom;
use App\Models\InHouseBomHouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class InHouseBomController extends Controller
{
    public function index()
    {
        $boms = InHouseBom::with('houses')
            ->withCount('houses')
            ->latest()
            ->paginate(20);

        return view('inhouse_bom.index', compact('boms'));
    }

    public function create()
    {
        $nextRef = InHouseBom::nextCycleRef();
        return view('inhouse_bom.create', compact('nextRef'));
    }

    public function store(Request $request)
    {
        $request->validate(['bom_payload' => 'required|string']);

        $payload = json_decode($request->input('bom_payload'), true);

        if (!$payload) {
            return back()->withErrors(['bom_payload' => 'Invalid BOM data.'])->withInput();
        }

        if (empty($payload['cycle_ref'])) {
            return back()->withErrors(['cycle_ref' => 'Cycle reference is required.'])->withInput();
        }

        if (InHouseBom::where('cycle_ref', $payload['cycle_ref'])->exists()) {
            return back()->withErrors([
                'cycle_ref' => 'Cycle reference "' . $payload['cycle_ref'] . '" already exists.'
            ])->withInput();
        }

        DB::transaction(function () use ($payload) {
            $createData = [
                'cycle_ref'  => trim($payload['cycle_ref']),
                'cycle_date' => $payload['cycle_date'],
                'grower'     => $payload['grower']     ?? null,
                'notes'      => $payload['notes']      ?? null,
                'num_houses' => (int) ($payload['num_houses'] ?? 1),
                'status'     => 'draft',
                'created_by' => auth()->id(),
            ];

            // If this is an extension, link to parent
            if (!empty($payload['parent_bom_id'])) {
                $parent = InHouseBom::find($payload['parent_bom_id']);
                if ($parent) {
                    $rootId = $parent->isExtension() ? $parent->parent_bom_id : $parent->id;
                    $maxExt = InHouseBom::where('parent_bom_id', $rootId)->max('extension_number') ?? 0;
                    $createData['parent_bom_id'] = $rootId;
                    $createData['extension_number'] = $maxExt + 1;
                }
            }

            $bom = InHouseBom::create($createData);

            foreach ($payload['houses'] ?? [] as $houseNum => $h) {
                if (empty($h['loading']) && empty($h['name'])) continue;
                $this->saveHouse($bom->id, (int) $houseNum, $h);
            }
        });

        return redirect()->route('inhouse_bom.index')
            ->with('success', 'In-House BOM "' . $payload['cycle_ref'] . '" saved successfully.');
    }

    public function show(InHouseBom $inhouseBom)
    {
        $inhouseBom->load('houses', 'creator', 'extensions.houses', 'parentBom');
        return view('inhouse_bom.show', ['bom' => $inhouseBom]);
    }

    public function edit(InHouseBom $inhouseBom)
    {
        if ($inhouseBom->approved) {
            return redirect()->route('inhouse_bom.show', $inhouseBom)
                ->with('error', 'This BOM has been approved and cannot be edited.');
        }

        $inhouseBom->load('houses');
        return view('inhouse_bom.edit', ['bom' => $inhouseBom]);
    }

    public function update(Request $request, InHouseBom $inhouseBom)
    {
        if ($inhouseBom->approved) {
            return redirect()->route('inhouse_bom.show', $inhouseBom)
                ->with('error', 'This BOM has been approved and cannot be edited.');
        }

        $request->validate(['bom_payload' => 'required|string']);

        $payload = json_decode($request->input('bom_payload'), true);
        if (!$payload) return back()->withErrors(['bom_payload' => 'Invalid BOM data.'])->withInput();

        DB::transaction(function () use ($payload, $inhouseBom) {
            $inhouseBom->update([
                'cycle_ref'  => trim($payload['cycle_ref']),
                'cycle_date' => $payload['cycle_date'],
                'grower'     => $payload['grower']  ?? null,
                'notes'      => $payload['notes']   ?? null,
                'num_houses' => (int) ($payload['num_houses'] ?? 1),
            ]);

            $inhouseBom->houses()->delete();

            foreach ($payload['houses'] ?? [] as $houseNum => $h) {
                if (empty($h['loading']) && empty($h['name'])) continue;
                $this->saveHouse($inhouseBom->id, (int) $houseNum, $h);
            }
        });

        return redirect()->route('inhouse_bom.index')
            ->with('success', 'BOM "' . $payload['cycle_ref'] . '" updated successfully.');
    }

    public function destroy(InHouseBom $inhouseBom)
    {
        if ($inhouseBom->approved) {
            return redirect()->route('inhouse_bom.show', $inhouseBom)
                ->with('error', 'This BOM has been approved and cannot be deleted.');
        }

        $ref = $inhouseBom->cycle_ref;
        $inhouseBom->delete();
        return redirect()->route('inhouse_bom.index')
            ->with('success', 'BOM "' . $ref . '" deleted.');
    }

    public function updateStatus(Request $request, InHouseBom $inhouseBom)
    {
        $request->validate(['status' => 'required|in:draft,active,complete,archived']);
        $inhouseBom->update(['status' => $request->status]);
        return response()->json(['success' => true, 'status' => $inhouseBom->status]);
    }

    /**
     * Show create form pre-filled with parent BOM data for extension
     */
    public function extend(InHouseBom $inhouseBom)
    {
        if (!$inhouseBom->approved) {
            return redirect()->route('inhouse_bom.show', $inhouseBom)
                ->with('error', 'Only approved BOMs can be extended.');
        }

        $inhouseBom->load('houses');

        $nextRef = InHouseBom::nextExtensionRef($inhouseBom);

        // Build parent house data in the format the create form JS expects
        $parentHouses = [];
        foreach ($inhouseBom->houses as $house) {
            $mats = [];
            foreach ($house->materials ?? [] as $m) {
                $cat = $m['category'] ?? 'feed';
                if (!isset($mats[$cat])) $mats[$cat] = [];
                $mats[$cat][] = [
                    'name'     => $m['name'] ?? '',
                    'days'     => $m['days'] ?? '',
                    'qty_kg'   => $m['qty_kg'] ?? 0,
                    'qty_bags' => $m['qty_bags'] ?? '',
                    'uom'      => $m['uom'] ?? '',
                    'cost'     => $m['cost'] ?? 0,
                    'labor_op' => $m['labor_op'] ?? '',
                    'divisor'  => $m['divisor'] ?? '',
                ];
            }
            $parentHouses[$house->house_number] = [
                'name'    => $house->house_name ?? '',
                'loading' => $house->loading_qty,
                'liv'     => $house->livability,
                'alw'     => $house->alw,
                'fcr'     => $house->fcr,
                'age'     => $house->age_days,
                'bpi'     => $house->bpi,
                'docCost' => $house->doc_cost ?? 24,
                'mats'    => $mats,
            ];
        }

        $parentBom = $inhouseBom;

        return view('inhouse_bom.create', compact('nextRef', 'parentBom', 'parentHouses'));
    }

    public function approve(InHouseBom $inhouseBom)
    {
        $inhouseBom->update([
            'approved'    => true,
            'approved_by' => Auth::user()->name ?? 'System',
            'approved_at' => Carbon::now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'BOM "' . $inhouseBom->cycle_ref . '" has been approved.',
            'approved_by' => $inhouseBom->approved_by,
            'approved_at' => $inhouseBom->approved_at->format('M d, Y h:i A'),
        ]);
    }

    public function unapprove(InHouseBom $inhouseBom)
    {
        $inhouseBom->update([
            'approved'    => false,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'BOM "' . $inhouseBom->cycle_ref . '" approval has been revoked.',
        ]);
    }

    // ── Export to Excel (.xlsx) — matches BOM Bacolorv2026.xlsx format ────────

    public function exportExcel(InHouseBom $inhouseBom)
    {
        $inhouseBom->load('houses');
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $cats = [
            'feed'              => 'Feeds',
            'supplement'        => 'Supplements',
            'vaccine'           => 'Vaccine',
            'disinfectant'      => 'Disinfectant',
            'cleaning_material' => 'Cleaning Material',
            'supply'            => 'Supplies',
            'labor'             => 'Labor',
            'overhead'          => 'Overhead',
        ];

        $cdmCats = ['disinfectant', 'cleaning_material', 'supply'];

        // Category header styles (exact Excel colors)
        $catStyleMap = [
            'feed'              => ['bg' => 'DCEAF7', 'fc' => '000000'],
            'supplement'        => ['bg' => 'DCEAF7', 'fc' => '000000'],
            'vaccine'           => ['bg' => 'DCEAF7', 'fc' => '000000'],
            'disinfectant'      => ['bg' => 'DCEAF7', 'fc' => '000000'],
            'cleaning_material' => ['bg' => 'DCEAF7', 'fc' => '000000'],
            'supply'            => ['bg' => 'E8E8E8', 'fc' => '000000'],
            'labor'             => ['bg' => '156082', 'fc' => 'FFFFFF'],
            'overhead'          => ['bg' => '156082', 'fc' => 'FFFFFF'],
        ];

        foreach ($inhouseBom->houses as $house) {
            $sheetName = $house->house_name ?: ('House ' . $house->house_number);
            // Sheet names max 31 chars, no special chars
            $sheetName = substr(preg_replace('/[\\\\\/\?\*\[\]:]+/', '', $sheetName), 0, 31);
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($sheetName);

            $mats       = collect($house->materials ?? []);
            $loading    = $house->loading_qty ?? 0;
            $livability = $house->livability ? $house->livability / 100 : 0;
            $alw        = (float) ($house->alw ?? 0);
            $harvest    = $house->harvest_qty ?? 0;
            $totalKg    = (float) ($house->total_kg ?? 0);
            $fcr        = (float) ($house->fcr ?? 0);
            $feedReq    = (float) ($house->feed_req_kg ?? 0);
            $docCost    = (float) ($house->doc_cost ?? 24);
            $docAmt     = $loading * $docCost;
            $age        = $house->age_days ?? 0;
            $grandTotal = (float) ($house->total_cost ?? 0);
            $grandCpk   = $totalKg > 0 ? $grandTotal / $totalKg : 0;
            $bpi        = $house->bpi ?? 0;

            // ── Column widths (match Excel) ──
            $sheet->getColumnDimension('A')->setWidth(1.57);
            $sheet->getColumnDimension('B')->setWidth(25.86);
            $sheet->getColumnDimension('C')->setWidth(11);
            $sheet->getColumnDimension('D')->setWidth(11);
            $sheet->getColumnDimension('E')->setWidth(11);
            $sheet->getColumnDimension('F')->setWidth(11);
            $sheet->getColumnDimension('G')->setWidth(12.43);
            $sheet->getColumnDimension('H')->setWidth(15);
            $sheet->getColumnDimension('I')->setWidth(13.86);

            // Row height
            for ($r = 1; $r <= 70; $r++) {
                $sheet->getRowDimension($r)->setRowHeight(14.1);
            }

            // ── Header parameters (rows 2-12) ──
            $headers = [
                [2, 'Loading Quantity',   $loading,   '#,##0'],
                [4, 'Livability',         $livability, '0.00%'],
                [5, 'Harvest Qty, Heads', $harvest,   '#,##0'],
                [6, 'ALW',               $alw,       '#,##0.00'],
                [7, 'Total kg',          $totalKg,   '#,##0.00'],
                [8, 'FCR',               $fcr,       '#,##0.000'],
                [9, 'Feed Requirement',   $feedReq,   '#,##0'],
                [10, 'COST',             $grandCpk,  '#,##0.00'],
                [11, 'AGE',              $age,       '#,##0.00'],
                [12, 'BPI',              $bpi,       '#,##0'],
            ];

            foreach ($headers as [$row, $label, $value, $fmt]) {
                $sheet->setCellValue("B{$row}", $label);
                $sheet->setCellValue("I{$row}", $value);
                $sheet->getStyle("I{$row}")->getNumberFormat()->setFormatCode($fmt);
                $sheet->getStyle("I{$row}")->getFont()->setBold(false);
                $sheet->getStyle("I{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
            // Bold labels for COST, AGE, BPI
            foreach ([10, 11, 12] as $r) {
                $sheet->getStyle("B{$r}")->getFont()->setBold(true);
            }

            // ── House label (row 13, merged G-I) ──
            $sheet->mergeCells('G13:I13');
            $sheet->setCellValue('G13', $sheetName);
            $sheet->getStyle('G13')->getFont()->setBold(true);
            $sheet->getStyle('G13')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // ── Column headers (row 14) ──
            $colHeaders = ['E' => 'QTY', 'F' => 'UOM', 'G' => 'COST', 'H' => 'AMOUNT', 'I' => 'COST/KG'];
            foreach ($colHeaders as $col => $hdr) {
                $sheet->setCellValue("{$col}14", $hdr);
                $sheet->getStyle("{$col}14")->getFont()->setBold(true);
                $sheet->getStyle("{$col}14")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            // ── TOTAL COST (row 15) — gray bg, bold — matches view layout (total first) ──
            $sheet->setCellValue('B15', 'TOTAL COST');
            $sheet->setCellValue('H15', $grandTotal);
            $sheet->setCellValue('I15', $grandCpk);
            $this->applyRowStyle($sheet, 15, 'B', 'I', 'D0D0D0', '000000');
            $sheet->getStyle('B15')->getFont()->setBold(true);
            $sheet->getStyle('H15')->getFont()->setBold(true);
            $sheet->getStyle('I15')->getFont()->setBold(true);
            $sheet->getStyle('H15')->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('I15')->getNumberFormat()->setFormatCode('#,##0.00');

            // ── DOC row (row 16) ──
            $docCpk = $totalKg > 0 ? $docAmt / $totalKg : 0;
            $sheet->setCellValue('B16', 'Day Old Chick');
            $sheet->setCellValue('E16', $loading);
            $sheet->setCellValue('F16', 'Hds');
            $sheet->setCellValue('G16', $docCost);
            $sheet->setCellValue('H16', $docAmt);
            $sheet->setCellValue('I16', $docCpk);
            $sheet->getStyle('E16')->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('G16')->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('H16')->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('I16')->getNumberFormat()->setFormatCode('#,##0.00');

            // ── Total Materials (row 17) — blue bg, white text ──
            $matTotal = $grandTotal - $docAmt;
            $matCpk   = $totalKg > 0 ? $matTotal / $totalKg : 0;
            $sheet->setCellValue('B17', 'Total Materials');
            $sheet->setCellValue('H17', $matTotal);
            $sheet->setCellValue('I17', $matCpk);
            $this->applyRowStyle($sheet, 17, 'B', 'I', '156082', 'FFFFFF');
            $sheet->getStyle('H17')->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('I17')->getNumberFormat()->setFormatCode('#,##0.00');

            // ── Pre-calculate CDM sub-category amounts for parent header ──
            $cdmSubAmts = [];
            foreach ($cdmCats as $cdmKey) {
                $cdmSubAmts[$cdmKey] = $mats->where('category', $cdmKey)->values()
                    ->sum(fn($r) => (float)($r['qty_kg'] ?? 0) * (float)($r['cost'] ?? 0));
            }
            $cdmTotalAmt = ($cdmSubAmts['cleaning_material'] ?? 0) + ($cdmSubAmts['supply'] ?? 0) - ($cdmSubAmts['disinfectant'] ?? 0);
            $cdmTotalCpk = $totalKg > 0 ? $cdmTotalAmt / $totalKg : 0;

            // ── Material rows ──
            $row = 18;
            foreach ($cats as $catKey => $catLabel) {
                $catRows = $mats->where('category', $catKey)->values();
                $catAmt  = 0;
                $feedKg  = 0;
                $feedBags = 0;
                foreach ($catRows as $_r) {
                    if ($catKey === 'feed') {
                        $catAmt += (float)($_r['qty_bags'] ?? 0) * (float)($_r['cost'] ?? 0);
                    } elseif ($catKey === 'labor') {
                        $q=(float)($_r['qty_kg']??0); $c=(float)($_r['cost']??0); $dy=(float)($_r['days']??0); $dv=(float)($_r['divisor']??0); $op=$_r['labor_op']??'';
                        $a=$q*$c; if($dy){$a=$op==='divide'?($q*$c)/$dy:$q*$c*$dy;} if($dv)$a=$a/$dv;
                        $catAmt += $a;
                    } else {
                        $q=(float)($_r['qty_kg']??0); $c=(float)($_r['cost']??0);
                        $catAmt += ($catKey==='overhead' && strtolower($_r['uom']??'')==='houses' && $q) ? $c/$q : $q*$c;
                    }
                    $feedKg  += (float) ($_r['qty_kg'] ?? 0);
                    $feedBags += (float) ($_r['qty_bags'] ?? 0);
                }
                $catCpk = $totalKg > 0 ? $catAmt / $totalKg : 0;

                // CDM parent header row (before disinfectant)
                if ($catKey === 'disinfectant') {
                    $sheet->setCellValue("B{$row}", 'Cleaning and Disinfection Mat');
                    $sheet->setCellValue("H{$row}", $cdmTotalAmt);
                    $sheet->setCellValue("I{$row}", $cdmTotalCpk);
                    $this->applyRowStyle($sheet, $row, 'B', 'I', '156082', 'FFFFFF');
                    $sheet->getStyle("B{$row}")->getFont()->setBold(true);
                    $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
                    $sheet->getStyle("I{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
                    $row++;
                }

                // Category header row
                $cs = $catStyleMap[$catKey];
                $isCdmSub = in_array($catKey, $cdmCats);
                $sheet->setCellValue("B{$row}", ($isCdmSub ? '   ' : '') . $catLabel);
                if ($catKey === 'feed') {
                    $sheet->setCellValue("C{$row}", 'Rec. Bags/Hds');
                    $sheet->getStyle("C{$row}")->getFont()->setSize(8);
                    $sheet->setCellValue("D{$row}", 'KG');
                    $sheet->getStyle("D{$row}")->getFont()->setSize(8);
                    $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->setCellValue("E{$row}", 'QTY');
                    $sheet->getStyle("E{$row}")->getFont()->setSize(8);
                    $sheet->setCellValue("F{$row}", 'UOM');
                    $sheet->getStyle("F{$row}")->getFont()->setSize(8);
                } elseif ($catKey === 'labor') {
                    $sheet->setCellValue("C{$row}", 'Days');
                    $sheet->getStyle("C{$row}")->getFont()->setSize(8);
                    $sheet->setCellValue("E{$row}", 'QTY');
                    $sheet->getStyle("E{$row}")->getFont()->setSize(8);
                    $sheet->setCellValue("F{$row}", 'UOM');
                    $sheet->getStyle("F{$row}")->getFont()->setSize(8);
                    $sheet->setCellValue("G{$row}", 'Cost');
                    $sheet->getStyle("G{$row}")->getFont()->setSize(8);
                } else {
                    $sheet->setCellValue("E{$row}", 'QTY');
                    $sheet->getStyle("E{$row}")->getFont()->setSize(8);
                    $sheet->setCellValue("F{$row}", 'UOM');
                    $sheet->getStyle("F{$row}")->getFont()->setSize(8);
                    $sheet->setCellValue("G{$row}", 'Cost');
                    $sheet->getStyle("G{$row}")->getFont()->setSize(8);
                }
                $sheet->setCellValue("H{$row}", $catAmt);
                $sheet->setCellValue("I{$row}", $catCpk);
                $this->applyRowStyle($sheet, $row, 'B', 'I', $cs['bg'], $cs['fc']);
                $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle("I{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
                $row++;

                // Item rows
                foreach ($catRows as $item) {
                    $qty  = (float) ($item['qty_kg'] ?? 0);
                    $qtyBags = (float) ($item['qty_bags'] ?? 0);
                    $cost = (float) ($item['cost'] ?? 0);
                    if ($catKey === 'feed') {
                        $amt = $qtyBags * $cost;
                    } elseif ($catKey === 'labor') {
                        $dy = (float)($item['days'] ?? 0);
                        $dv = (float)($item['divisor'] ?? 0);
                        $op = $item['labor_op'] ?? '';
                        $amt = $qty * $cost;
                        if ($dy) { $amt = $op === 'divide' ? ($qty * $cost) / $dy : $qty * $cost * $dy; }
                        if ($dv) { $amt = $amt / $dv; }
                    } else {
                        $amt = ($catKey==='overhead' && strtolower($item['uom']??'')==='houses' && $qty) ? $cost/$qty : $qty*$cost;
                    }
                    $cpk  = $totalKg > 0 && $amt > 0 ? $amt / $totalKg : 0;

                    $itemName = $item['name'] ?? '';
                    if ($isCdmSub) $itemName = '      ' . $itemName;
                    $sheet->setCellValue("B{$row}", $itemName);
                    if (($catKey === 'feed' || $catKey === 'labor') && !empty($item['days'])) {
                        $sheet->setCellValue("C{$row}", (float) $item['days']);
                    }
                    if ($catKey === 'feed') {
                        $sheet->setCellValue("D{$row}", $qty);
                        $sheet->setCellValue("E{$row}", (float) ($item['qty_bags'] ?? 0));
                        $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0');
                    } else {
                        $sheet->setCellValue("E{$row}", $qty ?: '');
                    }
                    $sheet->setCellValue("F{$row}", $item['uom'] ?? '');
                    $sheet->setCellValue("G{$row}", $cost);
                    $sheet->setCellValue("H{$row}", $amt);
                    if ($catKey !== 'feed') {
                        $sheet->setCellValue("I{$row}", $cpk);
                    }

                    $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
                    $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
                    $sheet->getStyle("I{$row}")->getNumberFormat()->setFormatCode('#,##0.00');

                    // Right-align numeric columns
                    foreach (['C', 'D', 'E', 'G', 'H', 'I'] as $c) {
                        $sheet->getStyle("{$c}{$row}")->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }
                    $row++;
                }

                // Feed subtotal: Total Kg Conversion row
                if ($catKey === 'feed' && $feedKg) {
                    $sheet->setCellValue("B{$row}", 'Total Kg Conversion');
                    $sheet->setCellValue("C{$row}", 'Bags × 50');
                    $sheet->getStyle("C{$row}")->getFont()->setSize(8)->setColor(
                        new \PhpOffice\PhpSpreadsheet\Style\Color('FF666666')
                    );
                    $sheet->setCellValue("D{$row}", $feedKg);
                    $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle("D{$row}")->getFont()->setBold(true);
                    $sheet->getStyle("B{$row}")->getFont()->setBold(true)->setSize(9);
                    $this->applyRowStyle($sheet, $row, 'B', 'I', 'FFEAF3FB', 'FF156082');
                    foreach (['C', 'D'] as $c) {
                        $sheet->getStyle("{$c}{$row}")->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }
                    $row++;
                }
            }

            // ── TOTAL COST (grand total row) ──
            $sheet->setCellValue("B{$row}", 'TOTAL COST');
            $sheet->setCellValue("H{$row}", $grandTotal);
            $sheet->setCellValue("I{$row}", $grandCpk);
            $this->applyRowStyle($sheet, $row, 'B', 'I', 'D0D0D0', '000000');
            $sheet->getStyle("B{$row}")->getFont()->setBold(true);
            $sheet->getStyle("H{$row}")->getFont()->setBold(true);
            $sheet->getStyle("I{$row}")->getFont()->setBold(true);
            $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("I{$row}")->getNumberFormat()->setFormatCode('#,##0.00');

            // Thin borders on the BOM table (rows 14 to $row)
            $sheet->getStyle("B14:I{$row}")->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
        }

        // Activate first sheet
        $spreadsheet->setActiveSheetIndex(0);

        // Stream download
        $filename = $inhouseBom->cycle_ref . '.xlsx';
        $temp = tempnam(sys_get_temp_dir(), 'bom');
        $writer = new Xlsx($spreadsheet);
        $writer->save($temp);

        return response()->download($temp, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function applyRowStyle($sheet, int $row, string $fromCol, string $toCol, string $bg, string $fc): void
    {
        $range = "{$fromCol}{$row}:{$toCol}{$row}";
        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB($bg);
        $sheet->getStyle($range)->getFont()->getColor()->setRGB($fc);
    }

    // ── Shared house-save logic ───────────────────────────────────────────────

    private function saveHouse(int $bomId, int $houseNum, array $h): void
    {
        $loading    = (float) ($h['loading']  ?? 0);
        $livability = (float) ($h['liv']      ?? 0);
        $alw        = (float) ($h['alw']      ?? 0);
        $fcr        = (float) ($h['fcr']      ?? 0);
        $docCost    = (float) ($h['docCost']  ?? 24.00);

        $age       = (float) ($h['age'] ?? 0);

        $harvest   = ($loading && $livability) ? round($loading * $livability / 100) : 0;
        $totalKg   = $harvest * $alw;
        $feedReqKg = $totalKg * $fcr;

        // BPI: use manual override if provided, otherwise auto-calculate
        // Formula: (Livability × ALW) / (Age × FCR) × 100
        $bpi = !empty($h['bpi'])
            ? (int) ceil((float) $h['bpi'])
            : (($livability && $alw && $age && $fcr)
                ? (int) ceil(($livability * $alw) / ($age * $fcr) * 100)
                : null);

        // Flatten category→rows structure into a flat array with 'category' field
        $materials = [];
        foreach ($h['mats'] ?? [] as $cat => $rows) {
            foreach ($rows as $row) {
                $materials[] = array_merge($row, ['category' => $cat]);
            }
        }

        $docAmount = $loading * $docCost;
        // Total = DOC + Feed + Supplement + Vaccine + CDM (CM + Supply - Disinfectant)
        $matSums = [];
        foreach (['feed','supplement','vaccine','disinfectant','cleaning_material','supply'] as $mc) {
            $matSums[$mc] = 0;
        }
        foreach ($materials as $m) {
            $cat = $m['category'] ?? '';
            if (!isset($matSums[$cat])) continue;
            $cost = (float)($m['cost'] ?? 0);
            $matSums[$cat] += $cat === 'feed'
                ? (float)($m['qty_bags'] ?? 0) * $cost
                : (float)($m['qty_kg'] ?? 0) * $cost;
        }
        $cdmAmt = $matSums['cleaning_material'] + $matSums['supply'] - $matSums['disinfectant'];
        $matTotal = $matSums['feed'] + $matSums['supplement'] + $matSums['vaccine'] + $cdmAmt;
        $totalCost = $docAmount + $matTotal;
        $costPerKg = $totalKg > 0 ? $totalCost / $totalKg : 0;

        InHouseBomHouse::create([
            'bom_id'       => $bomId,
            'house_number' => $houseNum,
            'house_name'   => $h['name']   ?? null,
            'loading_qty'  => $loading   ?: null,
            'livability'   => $livability ?: null,
            'alw'          => $alw        ?: null,
            'fcr'          => $fcr        ?: null,
            'age_days'     => $age ?: null,
            'bpi'          => $bpi,
            'harvest_qty'  => $harvest   ?: null,
            'total_kg'     => $totalKg   ?: null,
            'feed_req_kg'  => $feedReqKg ?: null,
            'doc_cost'     => $docCost,
            'materials'    => $materials,
            'total_cost'   => $totalCost,
            'cost_per_kg'  => $costPerKg,
        ]);
    }
}