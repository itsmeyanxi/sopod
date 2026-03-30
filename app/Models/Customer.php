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
        'collection_terms',
        'sales_rep',
        'credit_limit',
        'assigned_bank',
        'is_flagged',
        'is_locked',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
        'is_flagged' => 'boolean',
    ];

    public function deliveries()
    {
        return $this->hasMany(Deliveries::class, 'customer_code', 'customer_code');
    }

    public function arAdjustments()
    {
        return $this->hasMany(ArAdjustment::class, 'customer_code', 'customer_code');
    }

    public function arAging()
    {
        return $this->hasMany(ArAging::class, 'customer_code', 'customer_code');
    }

    /**
     * Get the terms change history for this customer
     */
    public function termsHistory()
    {
        return $this->hasMany(CustomerTermsHistory::class, 'customer_code', 'customer_code')->orderByDesc('created_at');
    }
}

