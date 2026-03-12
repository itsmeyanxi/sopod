<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerTermsHistory extends Model
{
    protected $table = 'customer_terms_history';

    protected $fillable = [
        'customer_code',
        'old_terms',
        'new_terms',
        'changed_by',
    ];

    /**
     * Get the customer this history record belongs to
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_code', 'customer_code');
    }
}
