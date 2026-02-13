<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_code',
        'supplier_name',
        'address',
        'email',
        'contact_number',
        'tin',
        'bank',
        'account_name',
        'account_number',
        'status',
        'created_by',
        'contact_person',  // ✅ ADD THIS - for storing contact person from Excel
        'terms',           // ✅ ADD THIS - for storing payment/delivery terms from Excel
    ];

    /**
     * Relationship with User (creator)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Purchase Requests using this supplier
     */
    public function purchaseRequests()
    {
        return $this->hasMany(PurchaseRequest::class, 'supplier_id');
    }

    /**
     * Purchase Orders using this supplier
     */
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'supplier_id');
    }

    /**
     * Supplier documents
     */
    public function documents()
    {
        return $this->hasMany(SupplierDocument::class);
    }

    /**
     * Check if supplier is active
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Get the supplier's full contact information
     * (Optional helper method for display purposes)
     */
    public function getFullContactAttribute()
    {
        $contact = [];
        
        if ($this->contact_person) {
            $contact[] = $this->contact_person;
        }
        
        if ($this->email) {
            $contact[] = $this->email;
        }
        
        if ($this->contact_number) {
            $contact[] = $this->contact_number;
        }
        
        return implode(' | ', $contact);
    }

    /**
     * Get the supplier's bank details
     * (Optional helper method for display purposes)
     */
    public function getBankDetailsAttribute()
    {
        if (!$this->bank && !$this->account_name && !$this->account_number) {
            return null;
        }
        
        $details = [];
        
        if ($this->bank) {
            $details[] = $this->bank;
        }
        
        if ($this->account_name) {
            $details[] = $this->account_name;
        }
        
        if ($this->account_number) {
            $details[] = $this->account_number;
        }
        
        return implode(' - ', $details);
    }
}