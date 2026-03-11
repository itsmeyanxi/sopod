<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deliveries extends Model
{
    protected $fillable = [
        'sales_order_id',
        'sales_order_number',
        'delivery_batch',
        'delivery_type',
        'dr_no',
        'sales_invoice_no',
        'customer_code',
        'customer_name',
        'tin_no',
        'branch',
        'sales_rep',
        'sales_representative',
        'sales_executive',
        'po_number',
        'request_delivery_date',
        'status',
        'plate_no',
        'approved_by',
        'additional_instructions',
        'attachment',
        // ✅ NEW APPROVAL FIELDS
        'approval_status',
        'approved_by_user',
        'rejection_reason',
        'approved_at',
        // ✅ EDIT REQUEST FIELDS
        'edit_requested',
        'edit_requested_by',
        'edit_requested_at',
        'edit_approved',
        'edit_approved_by',
        'edit_approved_at',
        // ✅ PULLOUT FIELDS
        'is_pulled_out',
        'pulled_out_by',
        'pulled_out_at',
        'pullout_reason',
        'created_by',
        'is_locked',
    ];

    protected $casts = [
        'request_delivery_date' => 'date',
        'approved_at' => 'datetime',
        'edit_requested_at' => 'datetime',
        'edit_approved_at' => 'datetime',
        'pulled_out_at' => 'datetime',
        'edit_requested' => 'boolean',
        'edit_approved' => 'boolean',
        'is_pulled_out' => 'boolean',
        'is_locked' => 'boolean',
    ];

    // Relationships
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_number', 'sales_order_number');
    }

    public function items()
    {
        return $this->hasMany(DeliveryItem::class, 'delivery_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_code', 'customer_code');
    }

    public function arAdjustments()
    {
        return $this->hasMany(ArAdjustment::class, 'dr_no', 'dr_no');
    }

    // ✅ Check if user can edit this delivery
    public function canBeEdited()
    {
        // Cannot edit if locked
        if ($this->is_locked) {
            return false;
        }

        // Cannot edit if pulled out
        if ($this->is_pulled_out) {
            return false;
        }

        // Admin/IT can always edit
        if (\App\Helpers\RoleHelper::isAdminOrIT()) {
            return true;
        }

        // Delivery_Approver can always edit
        if (\App\Helpers\RoleHelper::canApproveDeliveries()) {
            return true;
        }

        // Delivery_Creator can edit only if edit is approved
        if (\App\Helpers\RoleHelper::canCreateDeliveries() && $this->edit_approved) {
            return true;
        }

        return false;
    }

    // ✅ Get batch items (existing method)
    public function getBatchItems($batch)
    {
        return $this->where('delivery_batch', $batch);
    }

    // ✅ Get delivery batch display name (existing method)
    public function getBatchNameAttribute()
    {
        if (!$this->delivery_batch) return 'Single Delivery';

        $parts = explode('-', $this->delivery_batch);
        if (count($parts) >= 3) {
            $dateStr = end($parts);
            try {
                return 'Batch ' . \Carbon\Carbon::parse($dateStr)->format('M d, Y');
            } catch (\Exception $e) {
                return $this->delivery_batch;
            }
        }
        return $this->delivery_batch;
    }

    // ✅ Check if SO has multiple batches (existing method)
    public static function hasMultipleBatches($soNumber)
    {
        return self::where('sales_order_number', $soNumber)
            ->distinct('delivery_batch')
            ->count('delivery_batch') > 1;
    }
}