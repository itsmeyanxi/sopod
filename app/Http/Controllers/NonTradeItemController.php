<?php

namespace App\Http\Controllers;

use App\Models\NonTradeItem;
use App\Models\Supplier;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

class NonTradeItemController extends Controller
{
    private function generateItemCode($description, $supplierId = null)
    {
        // Format: description-number-supplier
        // e.g., "Office Chair-001-BPI Trading"
        $descPart = trim($description);
        if (strlen($descPart) > 50) {
            $descPart = substr($descPart, 0, 50);
        }

        // Get supplier name
        $supplierName = 'GEN';
        if ($supplierId) {
            $supplier = Supplier::find($supplierId);
            if ($supplier && $supplier->supplier_name) {
                $supplierName = $supplier->supplier_name;
            }
        }

        // Get next sequence number - search across all item code tables
        $pattern = '/-(\d{3,})-/';
        $allCodes = array_merge(
            \App\Models\PurchaseOrderItem::whereNotNull('item_code')->pluck('item_code')->toArray(),
            \App\Models\PurchaseRequestItem::whereNotNull('item_code')->pluck('item_code')->toArray(),
            NonTradeItem::whereNotNull('item_code')->pluck('item_code')->toArray(),
            \App\Models\TradeItem::whereNotNull('item_code')->pluck('item_code')->toArray()
        );
        $maxSeq = 0;
        foreach ($allCodes as $code) {
            if (preg_match($pattern, $code, $matches)) {
                $maxSeq = max($maxSeq, (int) $matches[1]);
            }
        }

        $seq = str_pad($maxSeq + 1, 3, '0', STR_PAD_LEFT);

        return "{$descPart}-{$seq}-{$supplierName}";
    }

    public function index(Request $request)
    {
        $query = NonTradeItem::with('supplier')->orderBy('name');

        if ($request->filled('search')) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        $items = $query->paginate(50)->appends($request->query());
        $suppliers = \App\Models\Supplier::where('status', 'active')->orderBy('supplier_name')->get();

        return view('non_trade_items.index', compact('items', 'suppliers'));
    }

    public function search(Request $request)
    {
        $term = $request->input('q', '');
        $supplierId = $request->input('supplier_id');

        $query = NonTradeItem::with('supplier')->where('name', 'LIKE', "%{$term}%");

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        $items = $query->orderBy('name')->limit(50)->get();

        $results = $items->map(function($item) {
            $displayName = $item->name;
            if ($item->supplier) {
                $displayName .= ' (' . $item->supplier->supplier_name . ')';
            }
            return [
                'display_name' => $displayName,
                'name' => $item->name,
                'item_code' => $item->item_code,
                'supplier_id' => $item->supplier_id,
                'supplier_name' => $item->supplier->supplier_name ?? null,
            ];
        })->values();

        return response()->json($results);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:500',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'unit' => 'nullable|string|max:100',
            'item_code' => 'nullable|string|max:100',
            'group' => 'nullable|string|max:200',
            'brand' => 'nullable|string|max:200',
            'trading_uom' => 'nullable|string|max:100',
            'conversion' => 'nullable|string|max:100',
            'status' => 'nullable|string|max:50',
        ]);

        $exists = NonTradeItem::where('name', $request->name)
            ->where('supplier_id', $request->supplier_id ?: null)
            ->exists();

        if ($exists) {
            return redirect()->route('non_trade_items.index')
                ->with('error', 'This item already exists for the selected supplier.');
        }

        $itemCode = $request->item_code ?: $this->generateItemCode($request->name, $request->supplier_id ?: null);

        NonTradeItem::create([
            'name' => $request->name,
            'supplier_id' => $request->supplier_id ?: null,
            'unit' => $request->unit ?: null,
            'item_code' => $itemCode,
            'group' => $request->group ?: null,
            'brand' => $request->brand ?: null,
            'trading_uom' => $request->trading_uom ?: null,
            'conversion' => $request->conversion ?: null,
            'status' => $request->status ?: 'active',
        ]);

        return redirect()->route('non_trade_items.index')
            ->with('success', 'Item added successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'item_code' => 'nullable|string|max:100',
        ]);

        $item = NonTradeItem::findOrFail($id);
        $item->update(['item_code' => $request->item_code ?: null]);

        return redirect()->route('non_trade_items.index')
            ->with('success', 'Item code updated successfully.');
    }

    public function destroy($id)
    {
        NonTradeItem::findOrFail($id)->delete();

        return redirect()->route('non_trade_items.index')
            ->with('success', 'Item removed from library.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt,xlsx,xls',
        ]);

        $file = $request->file('csv_file');
        $extension = strtolower($file->getClientOriginalExtension());

        // Read rows from file
        $rows = [];
        $headers = [];

        if (in_array($extension, ['xlsx', 'xls'])) {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $data = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            if (!empty($data)) {
                // Detect header row
                $col0 = strtolower(trim($data[0][0] ?? ''));
                if (in_array($col0, ['supplier', 'name', 'item', 'description', 'item code', 'item_code'])) {
                    $headers = array_map(fn($h) => strtolower(trim($h ?? '')), $data[0]);
                    array_shift($data);
                }
            }
            $rows = $data;
        } else {
            $handle = fopen($file->getRealPath(), 'r');
            $firstRow = true;
            while (($row = fgetcsv($handle)) !== false) {
                if ($firstRow) {
                    $firstRow = false;
                    $col0 = strtolower(trim($row[0] ?? ''));
                    if (in_array($col0, ['supplier', 'name', 'item', 'description', 'item code', 'item_code'])) {
                        $headers = array_map(fn($h) => strtolower(trim($h ?? '')), $row);
                        continue;
                    }
                }
                $rows[] = $row;
            }
            fclose($handle);
        }

        // Map header columns to field names
        $colMap = $this->mapImportColumns($headers);

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $supplierCache = [];

        foreach ($rows as $row) {
            // Use column map if headers detected, otherwise fallback to old format (col0=supplier, col1=item)
            if (!empty($colMap)) {
                $itemCode    = trim($row[$colMap['item_code'] ?? -1] ?? '');
                $itemName    = trim($row[$colMap['description'] ?? -1] ?? '');
                $group       = trim($row[$colMap['group'] ?? -1] ?? '');
                $brand       = trim($row[$colMap['brand'] ?? -1] ?? '');
                $uom         = trim($row[$colMap['uom'] ?? -1] ?? '');
                $tradingUom  = trim($row[$colMap['trading_uom'] ?? -1] ?? '');
                $conversion  = trim($row[$colMap['conversion'] ?? -1] ?? '');
                $status      = trim($row[$colMap['status'] ?? -1] ?? '');
                $supplierName = trim($row[$colMap['supplier'] ?? -1] ?? '');
            } else {
                // Legacy format: Column A = Supplier, Column B = Item Description
                $supplierName = trim($row[0] ?? '');
                $itemName     = trim($row[1] ?? '');
                $itemCode = $group = $brand = $uom = $tradingUom = $conversion = $status = '';
            }

            if (empty($itemName) && empty($itemCode)) {
                $skipped++;
                continue;
            }

            // If no item name but has item code, use item code as name
            if (empty($itemName) && !empty($itemCode)) {
                $itemName = $itemCode;
            }

            // Resolve supplier_id by name — auto-create if not found
            $supplierId = null;
            if (!empty($supplierName)) {
                if (!isset($supplierCache[$supplierName])) {
                    $supplier = Supplier::whereRaw('LOWER(supplier_name) = ?', [strtolower($supplierName)])->first();
                    if (!$supplier) {
                        $initials = strtoupper(collect(explode(' ', $supplierName))
                            ->map(fn($w) => substr($w, 0, 1))
                            ->take(3)->implode(''));
                        $code = $initials . '-' . str_pad(Supplier::count() + 1, 3, '0', STR_PAD_LEFT);
                        while (Supplier::where('supplier_code', $code)->exists()) {
                            $code = $initials . '-' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
                        }
                        $supplier = Supplier::create([
                            'supplier_name' => $supplierName,
                            'supplier_code' => $code,
                            'status'        => 'active',
                            'created_by'    => auth()->id(),
                        ]);
                    }
                    $supplierCache[$supplierName] = $supplier->id;
                }
                $supplierId = $supplierCache[$supplierName];
            }

            // Check if item already exists (same item_code or same name + supplier)
            $existing = null;
            if (!empty($itemCode)) {
                $existing = NonTradeItem::where('item_code', $itemCode)->first();
            }
            if (!$existing) {
                $existing = NonTradeItem::where('name', $itemName)
                    ->where('supplier_id', $supplierId)
                    ->first();
            }

            $itemData = array_filter([
                'name'        => $itemName,
                'supplier_id' => $supplierId,
                'unit'        => $uom ?: null,
                'group'       => $group ?: null,
                'brand'       => $brand ?: null,
                'trading_uom' => $tradingUom ?: null,
                'conversion'  => $conversion ?: null,
                'status'      => $status ?: 'active',
            ], fn($v) => $v !== null);

            if ($existing) {
                if (!$existing->item_code && !empty($itemCode)) {
                    $itemData['item_code'] = $itemCode;
                } elseif (!$existing->item_code) {
                    $itemData['item_code'] = $this->generateItemCode($itemName, $supplierId);
                }
                $existing->update($itemData);
                $updated++;
            } else {
                $itemData['item_code'] = $itemCode ?: $this->generateItemCode($itemName, $supplierId);
                NonTradeItem::create($itemData);
                $imported++;
            }
        }

        $message = "{$imported} item(s) imported";
        if ($updated > 0) $message .= ", {$updated} updated";
        if ($skipped > 0) $message .= ", {$skipped} skipped (empty)";

        return redirect()->route('non_trade_items.index')
            ->with('success', $message . '.');
    }

    private function mapImportColumns(array $headers): array
    {
        if (empty($headers)) return [];

        $map = [];
        $mappings = [
            'item_code'   => ['item code', 'item_code', 'code', 'itemcode'],
            'description' => ['item description', 'item_description', 'description', 'name', 'item name', 'item'],
            'group'       => ['group', 'item group', 'category'],
            'brand'       => ['brand', 'item brand'],
            'uom'         => ['uom', 'unit', 'unit of measure'],
            'trading_uom' => ['trading uom', 'trading_uom', 'trading unit'],
            'conversion'  => ['conversion', 'conv', 'conversion factor'],
            'status'      => ['status', 'item status'],
            'supplier'    => ['supplier', 'supplier name', 'supplier_name'],
        ];

        foreach ($headers as $index => $header) {
            if (empty($header)) continue;
            foreach ($mappings as $field => $variations) {
                if (in_array($header, $variations) && !isset($map[$field])) {
                    $map[$field] = $index;
                    break;
                }
            }
        }

        return $map;
    }
}
