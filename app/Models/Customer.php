<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_code',
        'customer_name',
        'sales_executive',
        'business_style',
        'billing_address',
        'branch',
        'tin',
        'shipping_address',
        'status',
    ];
}

