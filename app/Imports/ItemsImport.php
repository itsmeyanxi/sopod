<?php

namespace App\Imports;

use App\Models\Item;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ItemsImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        Log::info('Import Row Received:', $row);

        $itemCode = $row['item_code'] ?? null;
        $description = $row['item_description'] ?? null;
        $brand = $row['brand'] ?? null;
        $unit = $row['unit'] ?? null;
        $status = $row['status'] ?? null;

        // Only generate item_code if missing
        if (empty($itemCode)) {
            Log::warning('Missing item_code in CSV row');
            $itemCode = 'ITEM-' . strtoupper(Str::random(8));
        }

        Log::info('Mapped Data:', [
            'item_code'        => $itemCode,
            'item_description' => $description,
            'item_category'    => $itemGroup,
            'brand'            => $brand,
            'unit'             => $unit,
            'unit_price'       => null,
            'status'           => $status,
        ]);

        return new Item([
            'item_code'        => $itemCode,
            'item_description' => $description,
            'item_category'    => $itemGroup,
            'brand'            => $brand,
            'unit'             => $unit,
            'unit_price'       => null,
            'approval_status'  => 'pending',
        ]);
    }
}