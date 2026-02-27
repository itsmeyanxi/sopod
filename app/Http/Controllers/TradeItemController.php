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
        // Abbreviate item name: first letter of each word (letters only)
        $cleanDesc = preg_replace('/[^a-zA-Z\s]/', '', $description);
        $words = preg_split('/\s+/', trim($cleanDesc));
        $words = array_filter($words, fn($w) => strlen($w) > 0);
        if (count($words) === 1) {
            $abbr = strtoupper(substr(reset($words), 0, 2));
        } else {
            $abbr = strtoupper(implode('', array_map(fn($w) => substr($w, 0, 1), $words)));
        }

        // Get supplier abbreviation from supplier name (first letter of each word)
        $supplierCode = 'GEN';
        if ($supplierId) {
            $supplier = Supplier::find($supplierId);
            if ($supplier && $supplier->supplier_name) {
                $sWords = preg_split('/\s+/', trim($supplier->supplier_name));
                $supplierCode = strtoupper(implode('', array_map(fn($w) => substr($w, 0, 1), $sWords)));
            }
        }

        // Get next global sequence number
        $pattern = '/^[A-Z]+-(\d+)-[A-Z]+$/';
        $allCodes = array_merge(
            \App\Models\PurchaseOrderItem::whereNotNull('item_code')->pluck('item_code')->toArray(),
            \App\Models\PurchaseRequestItem::whereNotNull('item_code')->pluck('item_code')->toArray(),
            \App\Models\NonTradeItem::whereNotNull('item_code')->pluck('item_code')->toArray(),
            TradeItem::whereNotNull('item_code')->pluck('item_code')->toArray()
        );
        $maxSeq = 0;
        foreach ($allCodes as $code) {
            if (preg_match($pattern, $code, $matches)) {
                $maxSeq = max($maxSeq, (int) $matches[1]);
            }
        }

        $seq = str_pad($maxSeq + 1, 3, '0', STR_PAD_LEFT);

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
            'vendor_code' => 'nullable|string|max:100',
            'account' => 'nullable|string|max:100',
            'local_or_import' => 'nullable|in:Local,Import',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'item_code' => 'nullable|string|max:100',
        ]);

        $exists = TradeItem::where('name', $request->name)
            ->where('supplier_id', $request->supplier_id ?: null)
            ->exists();

        if ($exists) {
            return redirect()->route('trade_items.index')
                ->with('error', 'This item already exists for the selected supplier.');
        }

        $itemCode = $request->item_code ?: $this->generateItemCode($request->name, $request->supplier_id ?: null);

        TradeItem::create([
            'name' => $request->name,
            'vendor_code' => $request->vendor_code ?: null,
            'account' => $request->account ?: null,
            'local_or_import' => $request->local_or_import ?: null,
            'supplier_id' => $request->supplier_id ?: null,
            'item_code' => $itemCode,
        ]);

        return redirect()->route('trade_items.index')
            ->with('success', 'Trade item added successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'item_code' => 'nullable|string|max:100',
        ]);

        $item = TradeItem::findOrFail($id);
        $item->update(['item_code' => $request->item_code ?: null]);

        return redirect()->route('trade_items.index')
            ->with('success', 'Item code updated successfully.');
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
        $updated = 0;
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

            // Check for exact match: same name AND same supplier
            $existing = TradeItem::where('name', $itemName)
                ->where('supplier_id', $supplierId)
                ->first();

            if ($existing) {
                // Update existing record with new data
                $updateData = [
                    'supplier_id' => $supplierId,
                ];
                if (!empty($account)) $updateData['account'] = $account;
                if (!empty($vendorCode)) $updateData['vendor_code'] = $vendorCode;
                if (!empty($localOrImport)) $updateData['local_or_import'] = $localOrImport;
                if (!$existing->item_code) {
                    $updateData['item_code'] = $this->generateItemCode($itemName, $supplierId);
                }
                $existing->update($updateData);
                $updated++;
            } else {
                $newItem = TradeItem::create([
                    'name' => $itemName,
                    'account' => $account ?: null,
                    'vendor_code' => $vendorCode ?: null,
                    'local_or_import' => $localOrImport ?: null,
                    'supplier_id' => $supplierId,
                ]);

                if (!$newItem->item_code) {
                    $itemCode = $this->generateItemCode($itemName, $supplierId);
                    $newItem->update(['item_code' => $itemCode]);
                }

                $imported++;
            }
        }

        $message = "{$imported} item(s) imported";
        if ($updated > 0) $message .= ", {$updated} updated";
        if ($skipped > 0) $message .= ", {$skipped} skipped (empty)";

        return redirect()->route('trade_items.index')
            ->with('success', $message . '.');
    }
}
