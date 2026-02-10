<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_locked',
        'locked_at',
        'locked_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['password'] = Hash::make($value);
        }
    }

    protected static function booted()
    {
        static::creating(function ($user) {
            if (empty($user->role)) {
                $user->role = 'User';
            }
        });
    }

    public function hasRole($role)
    {
        if (is_array($role)) {
            return in_array($this->role, $role);
        }
        return $this->role === $role;
    }

    public function canManageSalesOrders()
    {
        return in_array($this->role, ['Admin', 'IT', 'CSR_Approver', 'CSR_Creator', 'CC_Approver', 'CC_Creator', 'Accounting_Approver', 'Delivery_Approver', 'Delivery_Creator']);
    }

    public function canCreateSalesOrders()
    {
        return in_array($this->role, ['Admin', 'IT', 'CSR_Approver', 'CSR_Creator']);
    }

    public function canApproveSalesOrders()
    {
        return in_array($this->role, ['Admin', 'IT', 'CC_Approver', 'CC_Creator', 'Accounting_Approver']);
    }

    // ✅ Already correct
    public function canManageItems()
    {
        return in_array($this->role, ['Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver', 'CC_Creator', 'CC_Approver']);
    }
    
    public function canAddItems()
    {
        return in_array($this->role, ['Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver']);
    }

    public function canEditItems()
    {
        return in_array($this->role, ['Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver']);
    }

    public function canDeleteItems()
    {
        return in_array($this->role, ['Admin', 'IT', 'Accounting_Approver']);
    }

        public function canApproveItems()
    {
        return in_array($this->role, ['Admin', 'IT', 'Accounting_Approver']);
    }

    public function canManageCustomers()
    {
        return in_array($this->role, ['Admin', 'IT', 'CC_Creator', 'CC_Approver', 'Accounting_Creator', 'Accounting_Approver']);
    }

    public function canAddCustomers()
    {
        return in_array($this->role, ['Admin', 'IT', 'CC_Creator', 'CC_Approver']);
    }

    public function canEditCustomers()
    {
        return in_array($this->role, ['Admin', 'IT', 'CC_Creator', 'CC_Approver']);
    }

    public function canDeleteCustomers()
    {
        return in_array($this->role, ['Admin', 'IT', 'CC_Approver']); 
    }

    public function canManageDeliveries()
    {
        return in_array($this->role, ['Delivery_Creator', 'Delivery_Approver', 'Admin', 'IT', 'CC_Approver']);
    }

    public function canManageUsers()
    {
        return in_array($this->role, ['Admin', 'IT']);
    }

        public function canImportItems()
    {
        return in_array($this->role, [
            'Admin',
            'IT',
            'Accounting_Creator',
            'Accounting_Approver'
        ]);
    }

    public function canApproveDeliveries()
    {
        return in_array($this->role, ['Admin', 'IT', 'Delivery_Approver']);
    }

    public function canCreateDeliveries()
    {
        return in_array($this->role, ['Admin', 'IT', 'Delivery_Creator', 'Delivery_Approver']);
    }

    public function canImportCustomers()
    {
        return in_array($this->role, [
            'Admin',
            'IT',
            'CC_Approver',
            'CC_Creator',
            'Accounting_Creator',
            'Accounting_Approver'
        ]);
    }

    public function canInitiateEdit()
    {
        return in_array($this->role, ['Admin', 'IT', 'CC_Approver']);
    }

    public function canEditAfterCCApproval()
    {
        return in_array($this->role, ['Admin', 'IT', 'CSR_Approver', 'CSR_Creator']);
    }

        public function lockedBy()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    // Check if account is locked
    public function isLocked()
    {
        return $this->is_locked;
    }

    // =================== MODULE ACCESS METHODS ===================

    /**
     * Check if user can access Payments/Collections module
     */
    public function canAccessPayments()
    {
        return in_array($this->role, ['Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver', 'CC_Approver']);
    }

    /**
     * Check if user can access Aging Reports module
     * Only Accounting, IT, and Admin can access
     */
    public function canAccessAgingReports()
    {
        return in_array($this->role, ['Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver']);
    }

    /**
     * Check if user can access AR Dashboard module
     * Only Accounting, IT, and Admin can access
     */
    public function canAccessARDashboard()
    {
        return in_array($this->role, ['Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver']);
    }

    /**
     * Check if user can access Receiving Reports module
     * Only Delivery, IT, and Admin can access
     */
    public function canAccessReceivingReports()
    {
        return in_array($this->role, ['Admin', 'IT', 'Delivery_Creator', 'Delivery_Approver']);
    }

    /**
     * Check if user can access Change Log module
     */
    public function canAccessChangelog()
    {
        return in_array($this->role, ['Admin', 'IT', 'CC_Approver', 'CC_Creator']);
    }

    /**
     * Check if user can access Sales Analytics module
     */
    public function canAccessSalesAnalytics()
    {
        return in_array($this->role, ['Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver']);
    }

    /**
     * Check if user can access Records module
     */
    public function canAccessRecords()
    {
        return in_array($this->role, ['Admin', 'IT', 'CC_Approver', 'CC_Creator', 'Accounting_Creator', 'Accounting_Approver']);
    }

    /**
     * Check if user can access Excel Import module
     */
    public function canAccessExcelImport()
    {
        return in_array($this->role, ['Admin', 'IT', 'CC_Approver', 'CC_Creator', 'Accounting_Creator', 'Accounting_Approver']);
    }

}