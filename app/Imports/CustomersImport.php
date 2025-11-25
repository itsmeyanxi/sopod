<?php

namespace App\Imports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Str;

class CustomersImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new Customer([
            'customer_code'    => $row['customer_code'] ?? 'CUST-' . strtoupper(Str::random(8)),
            'customer_name'    => $row['customer_name'] ?? null,
            'branch'           => $row['branch'] ?? null,
            'business_style'   => $row['business_style'] ?? null,
            'billing_address'  => $row['billing_address'] ?? null,
            'tin'              => $row['tin'] ?? null,
            'shipping_address' => $row['shipping_address'] ?? null,
            'sales_executive'  => $row['sales_executive'] ?? null,
            'status'           => 'enabled',
        ]);
    }
    
    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'customer_code' => 'nullable|string|max:50|unique:customers,customer_code',
            'customer_name' => 'nullable|string|max:255',
            'branch' => 'nullable|string|max:255',
            'business_style' => 'nullable|string|max:255',
            'billing_address' => 'nullable|string',
            'tin' => 'nullable|string|max:50',
            'shipping_address' => 'nullable|string',
            'sales_executive' => 'nullable|string|max:255',
        ];
    }
    
    /**
     * Custom error messages
     */
    public function customValidationMessages()
    {
        return [
            'customer_code.unique' => 'Customer code already exists',
            'customer_name.max' => 'Customer name is too long',
        ];
    }
}