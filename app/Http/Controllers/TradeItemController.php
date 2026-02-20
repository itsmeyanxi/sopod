<?php

namespace App\Http\Controllers;

use App\Models\TradeItem;
use App\Models\Supplier;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TradeItemController extends Controller
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
        $ntiMax = \App\Models\NonTradeItem::whereNotNull('item_code')
            ->pluck('item_code')
            ->filter(fn($c) => preg_match($pattern, $c, $m))
            ->map(fn($c) => (int) explode('-', $c)[1])
            ->max() ?? 0;
        $tiMax = TradeItem::whereNotNull('item_code')
            ->pluck('item_code')
            ->filter(fn($c) => preg_match($pattern, $c, $m))
            ->map(fn($c) => (int) explode('-', $c)[1])
            ->max() ?? 0;

        $seq = str_pad(max($poMax, $prMax, $ntiMax, $tiMax) + 1, 3, '0', STR_PAD_LEFT);

        return "{$abbr}-{$seq}-{$supplierCode}";
    }

    public function index(Request $request)
    {
        $query = TradeItem::with('supplier')->orderBy('name');

        if ($request->filled('search')) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        if ($request->filled('account')) {
            $query->where('account', $request->account);
        }

        $items = $query->paginate(50)->appends($request->query());
        $suppliers = Supplier::where('status', 'active')->orderBy('supplier_name')->get();

        // Get unique accounts for filter
        $accounts = TradeItem::select('account')->distinct()->orderBy('account')->pluck('account');

        return view('trade_items.index', compact('items', 'suppliers', 'accounts'));
    }

    public function search(Request $request)
    {
        $term = $request->input('q', '');
        $supplierId = $request->input('supplier_id');

        $query = TradeItem::with('supplier')->where('name', 'LIKE', "%{$term}%");

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
            'vendor_code' => 'nullable|string|max:100',
            'account' => 'nullable|string|max:100',
            'local_or_import' => 'nullable|in:Local,Import',
            'supplier_id' => 'nullable|exists:suppliers,id',
        ]);

        $exists = TradeItem::where('name', $request->name)
            ->where('supplier_id', $request->supplier_id ?: null)
            ->exists();

        if ($exists) {
            return redirect()->route('trade_items.index')
                ->with('error', 'This item already exists for the selected supplier.');
        }

        TradeItem::create([
            'name' => $request->name,
            'vendor_code' => $request->vendor_code ?: null,
            'account' => $request->account ?: null,
            'local_or_import' => $request->local_or_import ?: null,
            'supplier_id' => $request->supplier_id ?: null,
        ]);

        return redirect()->route('trade_items.index')
            ->with('success', 'Trade item added successfully.');
    }

    public function destroy($id)
    {
        TradeItem::findOrFail($id)->delete();

        return redirect()->route('trade_items.index')
            ->with('success', 'Trade item removed from library.');
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
            $localOrImport = trim($row[4] ?? '');

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

            // Check for exact match: same name AND same supplier
            $existing = TradeItem::where('name', $itemName)
                ->where('supplier_id', $supplierId)
                ->exists();

            if ($existing) {
                // Same item + same supplier already in DB → skip
                $skipped++;
            } else {
                // New item, or same item name but different supplier → create new record
                $newItem = TradeItem::create([
                    'name' => $itemName,
                    'account' => $account ?: null,
                    'vendor_code' => $vendorCode ?: null,
                    'local_or_import' => $localOrImport ?: null,
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

        $message = "{$imported} item(s) imported";
        if ($skipped > 0) $message .= ", {$skipped} already existed (same item + supplier)";

        return redirect()->route('trade_items.index')
            ->with('success', $message . '.');
    }
}
