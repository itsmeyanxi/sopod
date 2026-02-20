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
        // Abbreviate item name: single word takes first 2 chars, multi-word takes first letter of each word
        $words = preg_split('/\s+/', trim($description));
        if (count($words) === 1) {
            $abbr = strtoupper(substr($words[0], 0, 2));
        } else {
            $abbr = strtoupper(implode('', array_map(fn($w) => substr($w, 0, 1), $words)));
        }

        // Get supplier code
        $supplierCode = 'GEN';
        if ($supplierId) {
            $supplier = Supplier::find($supplierId);
            if ($supplier && $supplier->supplier_code) {
                $supplierCode = strtoupper($supplier->supplier_code);
            }
        }

        // Get next global sequence number
        $pattern = '/^[A-Z]+-(\d+)-[A-Z]+$/';
        $poMax = \App\Models\PurchaseOrderItem::whereNotNull('item_code')
            ->pluck('item_code')
            ->filter(fn($c) => preg_match($pattern, $c, $m))
            ->map(fn($c) => (int) explode('-', $c)[1])
            ->max() ?? 0;
        $prMax = \App\Models\PurchaseRequestItem::whereNotNull('item_code')
            ->pluck('item_code')
            ->filter(fn($c) => preg_match($pattern, $c, $m))
            ->map(fn($c) => (int) explode('-', $c)[1])
            ->max() ?? 0;
        $ntiMax = NonTradeItem::whereNotNull('item_code')
            ->pluck('item_code')
            ->filter(fn($c) => preg_match($pattern, $c, $m))
            ->map(fn($c) => (int) explode('-', $c)[1])
            ->max() ?? 0;

        $seq = str_pad(max($poMax, $prMax, $ntiMax) + 1, 3, '0', STR_PAD_LEFT);

        return "{$abbr}-{$seq}-{$supplierCode}";
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

        // Return items with supplier name in parenthesis
        $results = $items->map(function($item) {
            $displayName = $item->name;
            if ($item->supplier) {
                $displayName .= ' (' . $item->supplier->supplier_name . ')';
            }
            return $displayName;
        })->values();

        return response()->json($results);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:500',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'unit' => 'nullable|string|max:100',
        ]);

        $exists = NonTradeItem::where('name', $request->name)
            ->where('supplier_id', $request->supplier_id ?: null)
            ->exists();

        if ($exists) {
            return redirect()->route('non_trade_items.index')
                ->with('error', 'This item already exists for the selected supplier.');
        }

        NonTradeItem::create([
            'name' => $request->name,
            'supplier_id' => $request->supplier_id ?: null,
            'unit' => $request->unit ?: null,
        ]);

        return redirect()->route('non_trade_items.index')
            ->with('success', 'Item added successfully.');
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

        if (in_array($extension, ['xlsx', 'xls'])) {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $data = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            // Skip header row if first cell looks like a header
            if (!empty($data)) {
                $col0 = strtolower(trim($data[0][0] ?? ''));
                if (in_array($col0, ['supplier', 'name', 'item', 'description'])) {
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
                    if (in_array($col0, ['supplier', 'name', 'item', 'description'])) {
                        continue;
                    }
                }
                $rows[] = $row;
            }
            fclose($handle);
        }

        $imported = 0;
        $skipped = 0;
        $supplierCache = [];

        foreach ($rows as $row) {
            $supplierName = trim($row[0] ?? '');
            $itemName = trim($row[1] ?? '');
            $account = trim($row[2] ?? '');
            $vendorCode = trim($row[3] ?? '');

            if (empty($itemName)) {
                $skipped++;
                continue;
            }

            // Resolve supplier_id by name
            $supplierId = null;
            if (!empty($supplierName)) {
                if (!isset($supplierCache[$supplierName])) {
                    $supplier = Supplier::where('supplier_name', $supplierName)->first();
                    $supplierCache[$supplierName] = $supplier ? $supplier->id : null;
                }
                $supplierId = $supplierCache[$supplierName];
            }

            // Check if this is a nontrade item based on account field
            $isNontrade = stripos($account, 'nontrade') !== false;

            if ($isNontrade) {
                // Import to NonTradeItem
                $existing = NonTradeItem::where('name', $itemName)
                    ->where('supplier_id', $supplierId)
                    ->exists();

                if ($existing) {
                    $skipped++;
                } else {
                    $newItem = NonTradeItem::create([
                        'name' => $itemName,
                        'supplier_id' => $supplierId,
                        'account' => $account ?: null,
                        'vendor_code' => $vendorCode ?: null,
                    ]);

                    // Auto-generate item code if supplier is set and item doesn't have a code
                    if ($supplierId && !$newItem->item_code) {
                        $itemCode = $this->generateItemCode($itemName, $supplierId);
                        $newItem->update(['item_code' => $itemCode]);
                    }

                    $imported++;
                }
            } else {
                // Import to TradeItem
                $existing = \App\Models\TradeItem::where('name', $itemName)
                    ->where('supplier_id', $supplierId)
                    ->exists();

                if ($existing) {
                    $skipped++;
                } else {
                    $newItem = \App\Models\TradeItem::create([
                        'name' => $itemName,
                        'account' => $account ?: null,
                        'vendor_code' => $vendorCode ?: null,
                        'local_or_import' => (stripos($account, 'import') !== false) ? 'Import' : 'Local',
                        'supplier_id' => $supplierId,
                    ]);

                    // Auto-generate item code if supplier is set and item doesn't have a code
                    if ($supplierId && !$newItem->item_code) {
                        $itemCode = $this->generateItemCode($itemName, $supplierId);
                        $newItem->update(['item_code' => $itemCode]);
                    }

                    $imported++;
                }
            }
        }

        $message = "{$imported} item(s) imported";
        if ($skipped > 0) $message .= ", {$skipped} already existed (same item + supplier)";

        return redirect()->route('non_trade_items.index')
            ->with('success', $message . '.');
    }
}
