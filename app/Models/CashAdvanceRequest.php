<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashAdvanceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_no', 'payee', 'department', 'purpose',
        'date_requested', 'date_needed', 'amount_advanced',
        'requested_by', 'checked_by', 'approved_by_name', 'remarks',
        'attachment_path', 'attachment_name',
        'status', 'approval_stage',
        'dh_approved_by', 'dh_approved_at', 'dh_approved_latitude', 'dh_approved_longitude', 'dh_approved_location',
        'executive_approved_by', 'executive_approved_at', 'executive_approved_latitude', 'executive_approved_longitude', 'executive_approved_location',
        'rejection_reason', 'created_by',
    ];

    protected $casts = [
        'date_requested' => 'datetime',
        'date_needed' => 'datetime',
        'amount_advanced' => 'decimal:2',
        'dh_approved_at' => 'datetime',
        'executive_approved_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dhApprover()
    {
        return $this->belongsTo(User::class, 'dh_approved_by');
    }

    public function executiveApprover()
    {
        return $this->belongsTo(User::class, 'executive_approved_by');
    }

    public function liquidationForms()
    {
        return $this->hasMany(LiquidationForm::class, 'cash_advance_request_id');
    }
}
