<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $fillable = [
        'vendor_code', 'vendor_name', 'name_2307', 'category', 'group', 'gl_account', 'status',
        'company', 'ee_id', 'last_name', 'first_name', 'middle_name',
        'position', 'department', 'location', 'office_address', 'date_hired',
        'billing_street', 'billing_block', 'billing_city', 'billing_zip', 'billing_country',
        'shipping_street', 'shipping_block', 'shipping_city', 'shipping_zip', 'shipping_country',
        'payment_terms', 'selling_price_list', 'vat', 'withholding', 'registration', 'tin', 'account',
    ];

    protected $casts = ['date_hired' => 'date'];
}
