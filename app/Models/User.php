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
        return $this->canAccessModule('sales_orders');
    }

    public function canCreateSalesOrders()
    {
        // CC and Accounting cannot create sales orders
        if ($this->hasRole(['Credit & Collection', 'Accounting'])) {
            return false;
        }
        return $this->canPerformInModule('can_create', 'sales_orders');
    }

    public function canApproveSalesOrders()
    {
        return $this->canPerformInModule('can_approve', 'sales_orders');
    }

    // ==================== ITEM PERMISSIONS ====================

    public function canManageItems()
    {
        // Delivery cannot access items
        if ($this->isDeliveryOnlyRole()) {
            return false;
        }
        return $this->canAccessModule('items');
    }

    public function canAddItems()
    {
        // CC cannot create items
        if ($this->hasRole('Credit & Collection')) {
            return false;
        }
        return $this->canPerformInModule('can_create', 'items');
    }

    public function canEditItems()
    {
        return $this->canPerformInModule('can_edit', 'items');
    }

    public function canDeleteItems()
    {
        return $this->canPerformInModule('can_delete', 'items');
    }

    public function canApproveItems()
    {
        return $this->canPerformInModule('can_approve', 'items');
    }

    // ==================== CUSTOMER PERMISSIONS ====================

    public function canManageCustomers()
    {
        // Delivery cannot access customers
        if ($this->isDeliveryOnlyRole()) {
            return false;
        }
        return $this->canAccessModule('customers');
    }

    public function canAddCustomers()
    {
        // Accounting cannot create customers
        if ($this->hasRole('Accounting')) {
            return false;
        }
        return $this->canPerformInModule('can_create', 'customers');
    }

    public function canEditCustomers()
    {
        return $this->canPerformInModule('can_edit', 'customers');
    }

    public function canDeleteCustomers()
    {
        return $this->canPerformInModule('can_delete', 'customers');
    }

    // ==================== DELIVERY PERMISSIONS ====================

    public function canManageDeliveries()
    {
        return $this->canAccessModule('deliveries');
    }

    public function canApproveDeliveries()
    {
        return $this->canPerformInModule('can_approve', 'deliveries');
    }

    public function canCreateDeliveries()
    {
        return $this->canPerformInModule('can_create', 'deliveries');
    }

    // ==================== REIMBURSEMENT PERMISSIONS ====================

    public function canAccessReimbursementForms()
    {
        // Delivery cannot access reimbursement forms
        if ($this->isDeliveryOnlyRole()) {
            return false;
        }
        return $this->canAccessModule('reimbursement');
    }

    public function canCreateReimbursementForms()
    {
        return $this->canPerformInModule('can_create', 'reimbursement');
    }

    public function canApproveReimbursementForms()
    {
        return $this->canPerformInModule('can_approve', 'reimbursement');
    }

    // ==================== CASH ADVANCE PERMISSIONS ====================

    public function canAccessCashAdvanceRequests()
    {
        // Delivery cannot access cash advance requests
        if ($this->isDeliveryOnlyRole()) {
            return false;
        }
        return $this->canAccessModule('cash_advance');
    }

    public function canCreateCashAdvanceRequests()
    {
        return $this->canPerformInModule('can_create', 'cash_advance');
    }

    public function canApproveCashAdvanceRequests()
    {
        return $this->canPerformInModule('can_approve', 'cash_advance');
    }

    // ==================== USER MANAGEMENT ====================

    public function canManageUsers()
    {
        // Check new RBAC system OR legacy IT role for backward compatibility
        return $this->canAccessModule('user_management') || $this->hasRole('IT') || $this->role === 'IT';
    }

    // ==================== IMPORT PERMISSIONS ====================

    public function canImportItems()
    {
        // CC cannot import items
        if ($this->hasRole('Credit & Collection')) {
            return false;
        }
        return $this->canPerformInModule('can_create', 'items');
    }

    public function canImportCustomers()
    {
        return $this->canPerformInModule('can_create', 'customers');
    }

    // ==================== SUPPLIER MANAGEMENT ====================

    public function canManageSuppliers()
    {
        // CC and Delivery cannot manage suppliers
        if ($this->hasRole(['Credit & Collection', 'Delivery'])) {
            return false;
        }
        return $this->canAccessModule('suppliers');
    }

    public function canDeleteSuppliers()
    {
        return $this->canPerformInModule('can_delete', 'suppliers');
    }

    // ==================== DELIVERY ROLE RESTRICTIONS ====================

    public function isDeliveryOnlyRole()
    {
        // Check if user is Delivery role - they have restricted access
        return $this->hasRole('Delivery');
    }

    public function isPOCreatorRole()
    {
        // Check if user is PO Creator sub-department - they can only create POs
        return $this->userRoles()
            ->whereHas('subDepartment', function ($query) {
                $query->where('name', 'PO Creator');
            })
            ->exists();
    }

    // ==================== SUPPLIER RECEIVING REPORTS ====================

    public function canManageSupplierReceivingReports()
    {
        return $this->canAccessModule('supplier_rr');
    }

    public function canApproveSupplierReceivingReports()
    {
        return $this->canPerformInModule('can_approve', 'supplier_rr');
    }

    // ==================== ISSUE SLIP ROLES ====================

    public function canManageIssueSlips()
    {
        return $this->canAccessModule('issue_slips');
    }

    public function canDeleteIssueSlips()
    {
        return $this->canPerformInModule('can_delete', 'issue_slips');
    }

    // ==================== PURCHASE REQUEST ROLES ====================

    public function canManagePurchaseRequests()
    {
        // Delivery cannot access purchase requests
        if ($this->isDeliveryOnlyRole()) {
            return false;
        }
        return $this->canAccessModule('purchase_requests');
    }

    public function canCreatePurchaseRequests()
    {
        return $this->canPerformInModule('can_create', 'purchase_requests');
    }

    public function canApprovePurchaseRequests()
    {
        return $this->canPerformInModule('can_approve', 'purchase_requests');
    }

    public function canApprovePurchaseRequestsAsDH()
    {
        return $this->hasPermission('can_approve', 16); // Department Head sub-dept
    }

    public function canApprovePurchaseRequestsAsManagement()
    {
        return $this->userRoles()->whereIn('sub_department_id', [17, 18])->where('can_approve', true)->exists() || $this->isAdminUser();
    }

    public function canApprovePurchaseRequestsAsExecutive()
    {
        return $this->userRoles()->whereIn('sub_department_id', [19, 20])->where('can_approve', true)->exists() || $this->isAdminUser();
    }

    // ==================== PURCHASE ORDER ROLES ====================

    public function canManagePurchaseOrders()
    {
        // Delivery cannot access purchase orders
        if ($this->isDeliveryOnlyRole()) {
            return false;
        }
        // PO Creator can only create, allow access to the module
        if ($this->isPOCreatorRole()) {
            return true;
        }
        return $this->canAccessModule('purchase_orders');
    }

    public function canCreatePurchaseOrders()
    {
        // PO Creator can create
        if ($this->isPOCreatorRole()) {
            return true;
        }
        return $this->canPerformInModule('can_create', 'purchase_orders');
    }

    public function canApprovePurchaseOrders()
    {
        // PO Creator cannot approve
        if ($this->isPOCreatorRole()) {
            return false;
        }
        return $this->canPerformInModule('can_approve', 'purchase_orders');
    }

    public function canApprovePurchaseOrdersAsDH()
    {
        // PO Creator cannot approve
        if ($this->isPOCreatorRole()) {
            return false;
        }
        return $this->hasPermission('can_approve', 16); // Department Head sub-dept
    }

    public function canApprovePurchaseOrdersAsManagement()
    {
        // PO Creator cannot approve
        if ($this->isPOCreatorRole()) {
            return false;
        }
        return $this->userRoles()->whereIn('sub_department_id', [17, 18])->where('can_approve', true)->exists() || $this->isAdminUser();
    }

    public function canApprovePurchaseOrdersAsExecutive()
    {
        // PO Creator cannot approve
        if ($this->isPOCreatorRole()) {
            return false;
        }
        return $this->userRoles()->whereIn('sub_department_id', [19, 20])->where('can_approve', true)->exists() || $this->isAdminUser();
    }

    // ==================== REQUEST FOR PAYMENT ROLES ====================

    public function canManageRequestForPayments()
    {
        // Delivery cannot access request for payments
        if ($this->isDeliveryOnlyRole()) {
            return false;
        }
        return $this->canAccessModule('rfp');
    }

    public function canCreateRequestForPayments()
    {
        return $this->canPerformInModule('can_create', 'rfp');
    }

    public function canApproveRequestForPayments()
    {
        return $this->canPerformInModule('can_approve', 'rfp') || $this->canApproveRFPAsDH() || $this->canApproveRFPAsAccounting() || $this->canApproveRFPAsExecutive();
    }

    public function canApproveRFPAsDH()
    {
        return $this->hasPermission('can_approve', 16); // Department Head sub-dept
    }

    public function canApproveRFPAsAccounting()
    {
        return $this->hasPermission('can_approve', 26); // Accounting sub-dept
    }

    public function canApproveRFPAsExecutive()
    {
        return $this->userRoles()->whereIn('sub_department_id', [18, 19, 20])->where('can_approve', true)->exists() || $this->isAdminUser();
    }

    // APV permissions
    public function canApproveAPVAsDH()
    {
        return $this->hasPermission('can_approve', 16); // Department Head sub-dept
    }

    public function canApproveAPV()
    {
        return $this->hasPermission('can_approve', 26); // Accounting sub-dept
    }

    public function canApproveAPVInvoices()
    {
        return $this->canApproveAPV();
    }

    // CV permissions
    public function canApproveCVAsAccounting()
    {
        return $this->hasPermission('can_approve', 26); // Accounting sub-dept
    }

    public function canApproveCV()
    {
        return $this->hasPermission('can_approve', 26); // Accounting sub-dept
    }

    // ==================== OTHER PERMISSIONS ====================

    public function canInitiateEdit()
    {
        return $this->canPerformInModule('can_manage', 'customers');
    }

    public function canEditAfterCCApproval()
    {
        return $this->canPerformInModule('can_edit', 'sales_orders');
    }

    // ==================== RELATIONSHIPS ====================

    public function userRoles()
    {
        return $this->hasMany(UserRole::class);
    }

    public function lockedBy()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function isLocked()
    {
        return $this->is_locked;
    }

    // ==================== MODULE ↔ SUB-DEPARTMENT MAPPING ====================

    // Admin/IT sub-departments — users here get access to ALL modules
    const ADMIN_SUB_DEPARTMENTS = [5, 12]; // General Administration, IT Operations

    // Each module maps to sub-department IDs that grant access
    const MODULE_SUB_DEPARTMENTS = [
        'sales_orders'       => [13, 15, 26],                    // Sales, Credit & Collection, Accounting
        'customers'          => [15, 26],                      // Credit & Collection, Accounting
        'suppliers'          => [6, 1, 2, 3, 4, 26],          // Purchasing, Supply Chain subs, Accounting
        'items'              => [15, 26],                      // Credit & Collection, Accounting
        'deliveries'         => [15, 25],                      // Credit & Collection, Delivery
        'receiving_reports'  => [25],                          // Delivery
        'issue_slips'        => [1, 2, 3, 4, 14, 15, 25],     // Supply Chain, Customer Service, Credit & Collection, Delivery
        'supplier_rr'        => [1, 2, 3, 4],                  // Supply Chain subs
        'purchase_requests'  => [1, 5, 27, 28, 29, 16, 17, 18, 19, 20], // SC-Category, Administration, Procurement, Executive
        'purchase_orders'    => [5, 6, 27, 28, 29, 16, 17, 18, 19, 20], // Administration, Purchasing, Procurement, Executive
        'rfp'                => [1, 2, 3, 4, 5, 6],           // Supply Chain subs, Administration, Purchasing
        'apv'                => [22],                          // FAS Trade Local/Imports
        'jv'                 => [22],                          // FAS Trade Local/Imports
        'cv'                 => [24],                          // Treasury
        'non_trade_items'    => [6, 1, 2, 3, 4],              // Purchasing, Supply Chain subs
        'trade_items'        => [6, 1, 2, 3, 4],              // Purchasing, Supply Chain subs
        'currency_rates'     => [6, 28, 29],                   // Purchasing, PR Approver, Procurement Approver
        'aging_reports'      => [26],                          // Accounting
        'ar_dashboard'       => [26],                          // Accounting
        'gl_accounts'        => [26, 5],                       // Accounting, General Administration
        'payments'           => [26, 15],                      // Accounting, Credit & Collection
        'change_log'         => [15],                          // Credit & Collection
        'sales_analytics'    => [26, 16, 17, 18, 19, 20],     // Accounting, Executive
        'records'            => [15, 26],                       // Credit & Collection, Accounting
        'excel_import'       => [15, 26],                      // Credit & Collection, Accounting
        'record_lock'        => [12],                          // IT Operations
        'user_management'    => [5, 12],                       // General Administration, IT Operations
        'po_dashboard'       => [5, 6, 26, 27, 28, 29, 16, 17, 18, 19, 20, 21, 22, 23, 24], // Administration, Purchasing, Accounting, Procurement, Executive, Finance
        'warehouse'          => [2],                              // SC - Logistics only
        'cash_advance'       => [],                            // All users
        'liquidation'        => [],                            // All users
        'reimbursement'      => [],                            // All users
    ];

    // Sub-departments that can perform actions (create/edit/etc.) in a module.
    // If a module is NOT listed here, all its MODULE_SUB_DEPARTMENTS can perform actions.
    // If listed, only these sub-departments are checked for action permissions.
    const MODULE_ACTION_DEPARTMENTS = [
        'sales_orders' => [13, 15, 26],       // Sales, Credit & Collection, Accounting (NOT Delivery)
        'deliveries'   => [15, 25],              // Credit & Collection, Delivery
    ];

    // ==================== RBAC HELPER METHODS ====================

    /**
     * Check if user is in an Admin/IT sub-department (full access).
     */
    public function isAdminUser(): bool
    {
        // Check new RBAC system OR legacy IT/Admin role
        return $this->userRoles()
            ->whereIn('sub_department_id', self::ADMIN_SUB_DEPARTMENTS)
            ->exists() || $this->hasRole(['IT', 'Admin']) || in_array($this->role, ['IT', 'Admin']);
    }

    /**
     * Check if user can access a module based on their sub-department assignments.
     */
    public function canAccessModule(string $module): bool
    {
        if ($this->isAdminUser()) {
            return true;
        }

        $subDeptIds = self::MODULE_SUB_DEPARTMENTS[$module] ?? [];

        // Empty array means all users can access
        if (empty($subDeptIds)) {
            return true;
        }

        return $this->userRoles()
            ->whereIn('sub_department_id', $subDeptIds)
            ->exists();
    }

    /**
     * Check if user has a specific permission flag in ANY sub-department
     * that maps to the given module. Uses MODULE_ACTION_DEPARTMENTS if defined
     * for the module, so view-only departments are excluded from action checks.
     */
    public function canPerformInModule(string $flag, string $module): bool
    {
        if ($this->isAdminUser()) {
            return true;
        }

        // Use action-specific departments if defined, otherwise fall back to all module departments
        $subDeptIds = self::MODULE_ACTION_DEPARTMENTS[$module]
            ?? self::MODULE_SUB_DEPARTMENTS[$module]
            ?? [];

        if (empty($subDeptIds)) {
            return true;
        }

        return $this->userRoles()
            ->whereIn('sub_department_id', $subDeptIds)
            ->where($flag, true)
            ->exists();
    }

    /**
     * Check if user has a specific permission flag in a given sub-department.
     */
    public function hasPermission(string $flag, int $subDepartmentId): bool
    {
        if ($this->isAdminUser()) {
            return true;
        }

        return $this->userRoles()
            ->where('sub_department_id', $subDepartmentId)
            ->where($flag, true)
            ->exists();
    }

    /**
     * Check if user has ANY role in a given sub-department.
     */
    public function hasAccessTo(int $subDepartmentId): bool
    {
        if ($this->isAdminUser()) {
            return true;
        }

        return $this->userRoles()
            ->where('sub_department_id', $subDepartmentId)
            ->exists();
    }

    /**
     * Check if user has a specific permission in ANY sub-department
     * whose name matches the given array.
     */
    public function hasPermissionInSubDepartments(string $flag, array $subDeptNames): bool
    {
        if ($this->isAdminUser()) {
            return true;
        }

        return $this->userRoles()
            ->whereHas('subDepartment', function ($q) use ($subDeptNames) {
                $q->whereIn('name', $subDeptNames);
            })
            ->where($flag, true)
            ->exists();
    }

    // =================== MODULE ACCESS METHODS (delegate to RBAC) ===================

    public function canAccessPayments()
    {
        return $this->canAccessModule('payments');
    }

    public function canAccessAPV()
    {
        return $this->canAccessModule('apv');
    }

    public function canCreateAPV()
    {
        return $this->canPerformInModule('can_create', 'apv');
    }

    public function canAccessAgingReports()
    {
        return $this->canAccessModule('aging_reports');
    }

    public function canAccessARDashboard()
    {
        return $this->canAccessModule('ar_dashboard');
    }

    public function canAccessReceivingReports()
    {
        return $this->canAccessModule('receiving_reports');
    }

    public function canAccessChangelog()
    {
        return $this->canAccessModule('change_log');
    }

    public function canAccessSalesAnalytics()
    {
        // Delivery cannot access sales analytics
        if ($this->isDeliveryOnlyRole()) {
            return false;
        }
        return $this->canAccessModule('sales_analytics');
    }

    public function canAccessRecords()
    {
        return $this->canAccessModule('records');
    }

    public function canAccessExcelImport()
    {
        return $this->canAccessModule('excel_import');
    }

    // ==================== PRESIDENT/VP ROLE RESTRICTIONS ====================

    public function isPresidentOrVicePresident()
    {
        // Check if user has President or Vice President role
        return $this->hasRole(['President', 'Vice President']);
    }

}