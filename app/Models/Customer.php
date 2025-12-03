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
        'business_style',
        'billing_address',
        'branch',
        'tin_no',
        'shipping_address',
        'status',
        'customer_group', 
        'customer_type',
        'currency', 
        'telephone_1',
        'telephone_2', 
        'mobile', 
        'email', 
        'website', 
        'name_of_contact',
        'whtrate', 
        'whtcode',
        'require_si', 
        'ar_type',
        'tin_no', 
        'collection_terms',
        'sales_rep', 
        'credit_limit', 
        'assigned_bank'
    ];
}

