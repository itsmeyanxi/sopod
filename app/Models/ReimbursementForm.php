<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReimbursementForm extends Model
{
    use HasFactory;

    protected $fillable = [
        'ri_no', 'department', 'date_applied',
        'total_amount_spent', 'amount_to_be_reimbursed',
        'submitted_by', 'checked_by', 'approved_by_name', 'remarks', 'proof_documents',
        'status', 'approval_stage',
        'dh_approved_by', 'dh_approved_at', 'dh_approved_latitude', 'dh_approved_longitude', 'dh_approved_location',
        'executive_approved_by', 'executive_approved_at', 'executive_approved_latitude', 'executive_approved_longitude', 'executive_approved_location',
        'rejection_reason', 'created_by',
    ];

    protected $casts = [
        'date_applied' => 'datetime',
        'total_amount_spent' => 'decimal:2',
        'amount_to_be_reimbursed' => 'decimal:2',
        'dh_approved_at' => 'datetime',
        'executive_approved_at' => 'datetime',
        'proof_documents' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(ReimbursementFormItem::class);
    }

    public function dhApprover()
    {
        return $this->belongsTo(User::class, 'dh_approved_by');
    }

    public function executiveApprover()
    {
        return $this->belongsTo(User::class, 'executive_approved_by');
    }
}
