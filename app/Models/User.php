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
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
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

    // ✅ FIXED: Now matches RoleHelper
    public function canManageSalesOrders()
    {
        return in_array($this->role, ['Admin', 'IT', 'CSR_Approver', 'CSR_Creator']);
    }

    // ✅ FIXED: Added CSR_Creator
    public function canCreateSalesOrders()
    {
        return in_array($this->role, ['Admin', 'IT', 'CSR_Approver', 'CSR_Creator']);
    }

    public function canApproveSalesOrders()
    {
        return in_array($this->role, ['Admin', 'IT', 'CSR_Approver']);
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

    // ✅ FIXED: Added Accounting roles to match RoleHelper
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

    // ✅ FIXED: Changed from 'Delivery' to proper delivery roles
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

}