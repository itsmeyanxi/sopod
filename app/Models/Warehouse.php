<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_code',
        'warehouse_name',
        'address',
        'email',
        'contact_number',
        'tin',
        'bank',
        'account_name',
        'account_number',
        'status',
        'documents',
        'created_by',
    ];

    protected $casts = [
        'documents' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActive(): bool { return $this->status === 'active'; }
}