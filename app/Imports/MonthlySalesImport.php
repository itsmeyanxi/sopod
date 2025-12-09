<?php

namespace App\Imports;

use App\Models\MonthlySale;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MonthlySalesImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Handle both lowercase and mixed case headers
        $month = $row['month'] ?? $row['Month'] ?? '';
        $qty = $row['qty'] ?? $row['Qty'] ?? 0;
        $php = $row['php'] ?? $row['PHP'] ?? 0;

        // Remove commas from numbers
        $qty = $this->parseNumber($qty);
        $php = $this->parseNumber($php);

        return MonthlySale::updateOrCreate(
            ['month' => trim($month)],
            [
                'quantity' => $qty,
                'php_amount' => $php
            ]
        );
    }

    private function parseNumber($value)
    {
        // Remove commas and convert to float
        return floatval(str_replace(',', '', $value));
    }
}