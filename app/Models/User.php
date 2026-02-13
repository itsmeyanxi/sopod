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
        'role', // Keep for backward compatibility
        'roles', // New multi-role field
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
        'roles' => 'array', // Cast JSON to array
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
            // If roles array is empty, default to 'User'
            if (empty($user->roles)) {
                $user->roles = ['User'];
            }
            // Set legacy role field to first role
            if (empty($user->role) && !empty($user->roles)) {
                $user->role = $user->roles[0];
            }
        });
    }

    /**
     * 🔧 FIX: Fallback to single 'role' if 'roles' is empty
     * This ensures backward compatibility
     */
    public function getRolesAttribute($value)
    {
        $roles = json_decode($value, true);
        
        // Fallback: if roles is empty/null, use legacy 'role' field
        if (empty($roles) && !empty($this->attributes['role'])) {
            return [$this->attributes['role']];
        }
        
        return $roles ?? [];
    }

    /**
     * Check if user has ANY of the specified roles
     */
    public function hasRole($role)
    {
        if (is_array($role)) {
            return !empty(array_intersect($this->roles ?? [], $role));
        }
        return in_array($role, $this->roles ?? []);
    }

    /**
     * Check if user has ALL of the specified roles
     */
    public function hasAllRoles(array $roles)
    {
        return count(array_intersect($this->roles ?? [], $roles)) === count($roles);
    }

    /**
     * Get display name for roles (comma-separated)
     */
    public function getRolesDisplayAttribute()
    {
        return implode(', ', $this->roles ?? []);
    }

    // ==================== SALES ORDER PERMISSIONS ====================
    
    public function canManageSalesOrders()
    {
        return $this->hasRole(['Admin', 'IT', 'CSR_Approver', 'CSR_Creator']);
    }

    public function canCreateSalesOrders()
    {
        return $this->hasRole(['Admin', 'IT', 'CSR_Approver', 'CSR_Creator']);
    }

    public function canApproveSalesOrders()
    {
        return $this->hasRole(['Admin', 'IT', 'CC_Approver', 'Accounting_Approver']);
    }

    // ==================== ITEM PERMISSIONS ====================
    
    public function canManageItems()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver', 'CC_Creator', 'CC_Approver']);
    }
    
    public function canAddItems()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver']);
    }

    public function canEditItems()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver']);
    }

    public function canDeleteItems()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Approver']);
    }

    public function canApproveItems()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Approver']);
    }

    // ==================== CUSTOMER PERMISSIONS ====================
    
    public function canManageCustomers()
    {
        return $this->hasRole(['Admin', 'IT', 'CC_Creator', 'CC_Approver', 'Accounting_Creator', 'Accounting_Approver']);
    }

    public function canAddCustomers()
    {
        return $this->hasRole(['Admin', 'IT', 'CC_Creator', 'CC_Approver']);
    }

    public function canEditCustomers()
    {
        return $this->hasRole(['Admin', 'IT', 'CC_Creator', 'CC_Approver']);
    }

    public function canDeleteCustomers()
    {
        return $this->hasRole(['Admin', 'IT', 'CC_Approver']); 
    }

    // ==================== DELIVERY PERMISSIONS ====================
    
    public function canManageDeliveries()
    {
        return $this->hasRole(['Delivery_Creator', 'Delivery_Approver', 'Admin', 'IT', 'CC_Approver']);
    }

    public function canApproveDeliveries()
    {
        return $this->hasRole(['Admin', 'IT', 'Delivery_Approver']);
    }

    public function canCreateDeliveries()
    {
        return $this->hasRole(['Admin', 'IT', 'Delivery_Creator', 'Delivery_Approver']);
    }

    // ==================== USER MANAGEMENT ====================
    
    public function canManageUsers()
    {
        return $this->hasRole(['Admin', 'IT']);
    }

    // ==================== IMPORT PERMISSIONS ====================
    
    public function canImportItems()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver']);
    }

    public function canImportCustomers()
    {
        return $this->hasRole(['Admin', 'IT', 'CC_Approver', 'CC_Creator', 'Accounting_Creator', 'Accounting_Approver']);
    }

    // ==================== SUPPLIER MANAGEMENT ====================

    public function canManageSuppliers()
    {
        return $this->hasRole(['Admin', 'IT', 'PO_Creator', 'RFP_Creator', 'Accounting_Creator', 'Accounting_Approver']);
    }

    public function canDeleteSuppliers()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Approver']);
    }

    // ==================== PURCHASE REQUEST ROLES ====================

    public function canManagePurchaseRequests()
    {
        return $this->hasRole(['Admin', 'IT', 'PR_Creator', 'PR_Approver']);
    }

    public function canCreatePurchaseRequests()
    {
        return $this->hasRole(['Admin', 'IT', 'PR_Creator', 'PR_Approver']);
    }

    public function canApprovePurchaseRequests()
    {
        return $this->hasRole(['Admin', 'IT', 'PR_Approver']);
    }

    // ==================== PURCHASE ORDER ROLES ====================

    public function canManagePurchaseOrders()
    {
        return $this->hasRole(['Admin', 'IT', 'PO_Creator', 'PO_Approver']);
    }

    public function canCreatePurchaseOrders()
    {
        return $this->hasRole(['Admin', 'IT', 'PO_Creator', 'PO_Approver']);
    }

    public function canApprovePurchaseOrders()
    {
        return $this->hasRole(['Admin', 'IT', 'PO_Approver']);
    }

    // ==================== REQUEST FOR PAYMENT ROLES ====================

    public function canManageRequestForPayments()
    {
        return $this->hasRole(['Admin', 'IT', 'RFP_Creator', 'RFP_Approver']);
    }

    public function canCreateRequestForPayments()
    {
        return $this->hasRole(['Admin', 'IT', 'RFP_Creator', 'RFP_Approver']);
    }

    public function canApproveRequestForPayments()
    {
        return $this->hasRole(['Admin', 'IT', 'RFP_Approver']);
    }

    // ==================== OTHER PERMISSIONS ====================

    public static function canaccessexcelimport()
    {
        if (!auth()->check()) {
            return false;
        }
        
        $user = auth()->user();
        return $user->hasRole(['Admin', 'IT', 'CC_Approver', 'CC_Creator', 'Accounting_Creator', 'Accounting_Approver']);
    }

    public function canaccesssalesanalytics()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver']);
    }

    public function canInitiateEdit()
    {
        return $this->hasRole(['Admin', 'IT', 'CC_Approver']);
    }

    public function canEditAfterCCApproval()
    {
        return $this->hasRole(['Admin', 'IT', 'CSR_Approver', 'CSR_Creator']);
    }

    // ==================== RELATIONSHIPS ====================
    
    public function lockedBy()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function isLocked()
    {
        return $this->is_locked;
    }
}