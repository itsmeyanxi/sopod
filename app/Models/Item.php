<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_description',
        'item_code',
        'item_category',
        'brand',
        'approval_status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'is_enabled',
    ];
    
    protected $attributes = [
        'is_enabled' => 1,
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    // Relationship to the user who approved
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes for filtering items
    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('approval_status', 'pending');
    }

    public function scopeRejected($query)
    {
        return $query->where('approval_status', 'rejected');
    }

    // Helper methods
    public function isApproved()
    {
        return $this->approval_status === 'approved';
    }

    public function isPending()
    {
        return $this->approval_status === 'pending';
    }

    public function isRejected()
    {
        return $this->approval_status === 'rejected';
    }
}