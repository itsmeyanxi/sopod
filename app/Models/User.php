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
        return $this->hasRole(['Admin', 'IT', 'CSR', 'CC_Approver', 'CC_Creator', 'Accounting_Approver', 'Delivery_Approver', 'Delivery_Creator']);
    }

    public function canCreateSalesOrders()
    {
        return $this->hasRole(['Admin', 'IT', 'CSR']);
    }

    public function canApproveSalesOrders()
    {
        return $this->hasRole(['Admin', 'IT', 'CC_Approver', 'CC_Creator', 'Accounting_Approver']);
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
        return $this->hasRole(['Admin', 'IT', 'Purchasing', 'Accounting_Creator', 'Accounting_Approver', 'SCM']);
    }

    public function canDeleteSuppliers()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Approver']);
    }

    // ==================== SUPPLIER RECEIVING REPORTS ====================

    public function canManageSupplierReceivingReports()
    {
        return $this->hasRole(['Admin', 'IT', 'SCM']);
    }

    public function canApproveSupplierReceivingReports()
    {
        return $this->hasRole(['Admin', 'IT', 'SCM']);
    }

    // ==================== ISSUE SLIP ROLES ====================

    public function canManageIssueSlips()
    {
        return $this->hasRole(['Admin', 'IT', 'SCM', 'CSR', 'CC_Approver', 'CC_Creator', 'Delivery_Creator', 'Delivery_Approver']);
    }

    // ==================== PURCHASE REQUEST ROLES ====================

    public function canManagePurchaseRequests()
    {
        return $this->hasRole(['Admin', 'IT', 'Requisitioner', 'PR_Approver', 'Procurement_Approver', 'Department_Head', 'General_Manager', 'CFO', 'SCM']);
    }

    public function canCreatePurchaseRequests()
    {
        return $this->hasRole(['Admin', 'IT', 'Requisitioner', 'PR_Preparer', 'PR_Reviewer', 'Procurement_Approver', 'SCM']);
    }

    public function canApprovePurchaseRequests()
    {
        return $this->hasRole(['Admin', 'IT', 'PR_Viewer', 'PR_Approver', 'Procurement_Approver', 'Department_Head', 'General_Manager', 'CFO', 'SCM']);
    }

    public function canApprovePurchaseRequestsAsDH()
    {
        return $this->hasRole(['Admin', 'IT', 'Department_Head']);
    }

    public function canApprovePurchaseRequestsAsManagement()
    {
        return $this->hasRole(['Admin', 'IT', 'General_Manager', 'CFO']);
    }

    public function canApprovePurchaseRequestsAsExecutive()
    {
        return $this->hasRole(['Admin', 'IT', 'CFO']);
    }

    // ==================== PURCHASE ORDER ROLES ====================

    public function canManagePurchaseOrders()
    {
        return $this->hasRole(['Admin', 'IT', 'Purchasing', 'Procurement_Approver', 'Department_Head', 'General_Manager', 'CFO', 'SCM', 'PO_Preparer', 'PO_Reviewer', 'PO_Approver']);
    }

    public function canCreatePurchaseOrders()
    {
        return $this->hasRole(['Admin', 'IT', 'Purchasing', 'Procurement_Approver', 'SCM', 'PO_Preparer', 'PO_Reviewer']);
    }

    public function canApprovePurchaseOrders()
    {
        return $this->hasRole(['Admin', 'IT', 'Procurement_Approver', 'Department_Head', 'General_Manager', 'CFO', 'SCM', 'PO_Reviewer', 'PO_Approver']);
    }

    public function canApprovePurchaseOrdersAsDH()
    {
        return $this->hasRole(['Admin', 'IT', 'Department_Head']);
    }

    public function canApprovePurchaseOrdersAsManagement()
    {
        return $this->hasRole(['Admin', 'IT', 'General_Manager', 'CFO']);
    }

    public function canApprovePurchaseOrdersAsExecutive()
    {
        return $this->hasRole(['Admin', 'IT', 'CFO']);
    }

    // ==================== REQUEST FOR PAYMENT ROLES ====================

    public function canManageRequestForPayments()
    {
        return $this->hasRole(['Admin', 'IT', 'Purchasing', 'Procurement_Approver', 'SCM']);
    }

    public function canCreateRequestForPayments()
    {
        return $this->hasRole(['Admin', 'IT', 'Purchasing', 'Procurement_Approver', 'SCM', 'RFP_Preparer', 'RFP_Reviewer']);
    }

    public function canApproveRequestForPayments()
    {
        return $this->hasRole(['Admin', 'IT', 'Procurement_Approver', 'Department_Head', 'Accounting_Approver', 'CFO', 'SCM', 'RFP_Reviewer', 'RFP_Approver']);
    }

    public function canApproveRFPAsDH()
    {
        return $this->hasRole(['Admin', 'IT', 'Department_Head']);
    }

    public function canApproveRFPAsAccounting()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Approver']);
    }

    public function canApproveRFPAsExecutive()
    {
        return $this->hasRole(['Admin', 'IT', 'CFO']);
    }

    // APV permissions
    public function canApproveAPVAsDH()
    {
        return $this->hasRole(['Admin', 'IT', 'Department_Head']);
    }

    public function canApproveAPV()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Approver', 'APV_Approver']);
    }

    // CV permissions
    public function canApproveCVAsAccounting()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Approver']);
    }

    public function canApproveCV()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Approver']);
    }

    // ==================== OTHER PERMISSIONS ====================

    public function canInitiateEdit()
    {
        return $this->hasRole(['Admin', 'IT', 'CC_Approver']);
    }

    public function canEditAfterCCApproval()
    {
        return $this->hasRole(['Admin', 'IT', 'CSR']);
    }

    // ==================== REIMBURSEMENT FORM PERMISSIONS ====================

    /**
     * Check if user can view and manage reimbursement forms (full access)
     */
    public function canManageReimbursementForms()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Approver']);
    }

    /**
     * Check if user can create reimbursement forms
     * Reimbursement_Preparer role: can create and view only
     * Accounting roles and above: full access
     */
    public function canCreateReimbursementForms()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver', 'Reimbursement_Preparer']);
    }

    /**
     * Check if user can view reimbursement forms list
     */
    public function canAccessReimbursementForms()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver', 'Reimbursement_Preparer', 'Reimbursement_Reviewer']);
    }

    /**
     * Check if user can approve reimbursement forms
     * Reimbursement_Reviewer: can view and approve/reject forms
     * Reimbursement_Approver: final approval
     * Accounting_Approver and above: full approval access
     */
    public function canApproveReimbursementForms()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Approver', 'Reimbursement_Reviewer', 'Reimbursement_Approver']);
    }

    // ==================== CASH ADVANCE REQUEST PERMISSIONS ====================

    /**
     * Check if user can create cash advance requests
     * CAR_Preparer: can create and view only
     * Accounting roles and above: full access
     */
    public function canCreateCashAdvanceRequests()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver', 'CAR_Preparer']);
    }

    /**
     * Check if user can access cash advance requests list
     */
    public function canAccessCashAdvanceRequests()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver', 'CAR_Preparer', 'CAR_Reviewer']);
    }

    /**
     * Check if user can approve cash advance requests
     * CAR_Reviewer: can view and approve/reject requests
     * CAR_Approver: final approval
     * Accounting_Approver and above: full approval access
     */
    public function canApproveCashAdvanceRequests()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Approver', 'CAR_Reviewer', 'CAR_Approver']);
    }

    // ==================== ACCOUNTS PAYABLE INVOICE (APV) PERMISSIONS ====================

    /**
     * Check if user can create APV invoices
     * APV_Preparer: can create and view only
     * Accounting roles and above: full access
     */
    public function canCreateAPV()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver', 'APV_Preparer']);
    }

    /**
     * Check if user can access APV invoices list
     */
    public function canAccessAPV()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver', 'APV_Preparer', 'APV_Reviewer']);
    }

    /**
     * Check if user can approve APV invoices
     * APV_Reviewer: can view and approve/reject invoices
     * APV_Approver: final approval
     * Accounting_Approver and above: full approval access
     */
    public function canApproveAPVInvoices()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Approver', 'APV_Reviewer', 'APV_Approver']);
    }

    // ==================== LIQUIDATION FORM PERMISSIONS ====================

    /**
     * Check if user can create liquidation forms
     * LF_Preparer: can create and view only
     * Accounting roles and above: full access
     */
    public function canCreateLiquidationForms()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver', 'LF_Preparer']);
    }

    /**
     * Check if user can view liquidation forms list
     */
    public function canAccessLiquidationForms()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver', 'LF_Preparer', 'LF_Reviewer']);
    }

    /**
     * Check if user can approve liquidation forms
     * LF_Reviewer: can view and approve/reject forms
     * LF_Approver: final approval
     * Accounting_Approver and above: full approval access
     */
    public function canApproveLiquidationForms()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Approver', 'LF_Reviewer', 'LF_Approver']);
    }

    // ==================== CHECK VOUCHER (JOURNAL VOUCHER) PERMISSIONS ====================

    /**
     * Check if user can create check vouchers
     * CV_Preparer: can create and view only
     * Accounting roles and above: full access
     */
    public function canCreateCheckVouchers()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver', 'CV_Preparer']);
    }

    /**
     * Check if user can view check vouchers list
     */
    public function canAccessCheckVouchers()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver', 'CV_Preparer', 'CV_Reviewer']);
    }

    /**
     * Check if user can approve check vouchers
     * CV_Reviewer: can view and approve/reject vouchers
     * CV_Approver: final approval
     * Accounting_Approver and above: full approval access
     */
    public function canApproveCheckVouchers()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Approver', 'CV_Reviewer', 'CV_Approver']);
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

    // =================== MODULE ACCESS METHODS ===================

    /**
     * Check if user can access Payments/Collections module
     */
    public function canAccessPayments()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver', 'CC_Approver']);
    }

    /**
     * Check if user can access Aging Reports module
     * Only Accounting, IT, and Admin can access
     */
    public function canAccessAgingReports()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver']);
    }

    /**
     * Check if user can access AR Dashboard module
     * Only Accounting, IT, and Admin can access
     */
    public function canAccessARDashboard()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver']);
    }

    /**
     * Check if user can access Receiving Reports module
     * Only Delivery, IT, and Admin can access
     */
    public function canAccessReceivingReports()
    {
        return $this->hasRole(['Admin', 'IT', 'Delivery_Creator', 'Delivery_Approver']);
    }

    /**
     * Check if user can access Change Log module
     */
    public function canAccessChangelog()
    {
        return $this->hasRole(['Admin', 'IT', 'CC_Approver', 'CC_Creator']);
    }

    /**
     * Check if user can access Sales Analytics module
     */
    public function canAccessSalesAnalytics()
    {
        return $this->hasRole(['Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver']);
    }

    /**
     * Check if user can access Records module
     */
    public function canAccessRecords()
    {
        return $this->hasRole(['Admin', 'IT', 'CC_Approver', 'CC_Creator', 'Accounting_Creator', 'Accounting_Approver']);
    }

    /**
     * Check if user can access Excel Import module
     */
    public function canAccessExcelImport()
    {
        return $this->hasRole(['Admin', 'IT', 'CC_Approver', 'CC_Creator', 'Accounting_Creator', 'Accounting_Approver']);
    }

}