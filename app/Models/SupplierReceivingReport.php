<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierReceivingReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'srr_code',
        'report_date',
        'supplier_id',
        'supplier_name',
        'cv_no',
        'po_no',
        'reference_number',
        'storage',
        'report_type',
        'note',
        'prepared_by',
        'checked_by',
        'received_by',
        'verified_by',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'report_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(SupplierReceivingReportItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isSaved()
    {
        return $this->status === 'saved';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isDraft()
    {
        return $this->status === 'draft';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }
}
