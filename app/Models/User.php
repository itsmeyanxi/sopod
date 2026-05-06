<?php

namespace App\Models;

use App\Models\UserModuleOverride;
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
        'roles',
        'is_locked',
        'locked_at',
        'locked_by',
        'full_aging_access',
        'esignature',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_locked'         => 'boolean',
        'locked_at'         => 'datetime',
        'roles'             => 'array',
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
            if (empty($user->roles)) {
                $user->roles = ['User'];
            }
            if (empty($user->role) && !empty($user->roles)) {
                $user->role = $user->roles[0];
            }
        });
    }

    /**
     * Fallback to single 'role' if 'roles' is empty (backward compatibility).
     */
    public function getRolesAttribute($value)
    {
        $roles = json_decode($value, true);

        if (empty($roles) && !empty($this->attributes['role'])) {
            return [$this->attributes['role']];
        }

        return $roles ?? [];
    }

    /**
     * Check if user has ANY of the specified roles.
     */
    public function hasRole($role)
    {
        if (is_array($role)) {
            return !empty(array_intersect($this->roles ?? [], $role));
        }
        return in_array($role, $this->roles ?? []);
    }

    /**
     * Check if user has ALL of the specified roles.
     */
    public function hasAllRoles(array $roles)
    {
        return count(array_intersect($this->roles ?? [], $roles)) === count($roles);
    }

    /**
     * Get display name for roles (comma-separated).
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
        if ($this->isDeliveryOnlyRole()) {
            return false;
        }
        return $this->canAccessModule('items');
    }

    public function canAddItems()
    {
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
        if ($this->isDeliveryOnlyRole()) {
            return false;
        }
        if ($this->isAdminUser()) return true;
        return $this->canAccessModule('customers');
    }

    public function canAddCustomers()
    {
        if ($this->isAdminUser()) return true;
        if ($this->isJoeyFernandez()) return true;
        return $this->canPerformInModule('can_create', 'customers');
    }

    public function canEditCustomers()
    {
        if ($this->isAdminUser()) return true;
        if ($this->isJoeyFernandez()) return true;
        return $this->canPerformInModule('can_edit', 'customers');
    }

    public function canRequestCustomerEdit()
    {
        return $this->isCCRole() && !$this->isJoeyFernandez();
    }

    public function canApproveCustomerEditRequests()
    {
        return $this->isAdminUser() || $this->isJoeyFernandez();
    }

    public function canDeleteCustomers()
    {
        if ($this->isAdminUser()) return true;
        if ($this->isJoeyFernandez()) return true;
        return $this->canPerformInModule('can_delete', 'customers');
    }

    // ==================== PAYMENT EDIT PERMISSIONS ====================

    public function canEditPayments()
    {
        // Joey and IT can edit freely
        return $this->isAdminUser() || $this->isJoeyFernandez();
    }

    public function canRequestPaymentEdit()
    {
        // Other CC roles must request edit approval
        return $this->isCCRole() && !$this->isJoeyFernandez();
    }

    public function canApprovePaymentEditRequests()
    {
        return $this->isAdminUser() || $this->isJoeyFernandez();
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
        return $this->isAdminUser() || $this->canAccessModule('user_management');
    }

    // ==================== IMPORT PERMISSIONS ====================

    public function canImportItems()
    {
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
        if ($this->hasRole(['Credit & Collection', 'Delivery'])) {
            return false;
        }
        return $this->canAccessModule('suppliers');
    }

    public function canDeleteSuppliers()
    {
        return $this->canPerformInModule('can_delete', 'suppliers');
    }

    // ==================== CC ROLE HELPERS ====================

    public function isCCRole()
    {
        return in_array($this->role, ['CC_Approver', 'CC_Creator'])
            || $this->userRoles()->where('sub_department_id', 15)->exists();
    }

    public function isJoeyFernandez()
    {
        return $this->id === 28;
    }

    // ==================== DELIVERY ROLE RESTRICTIONS ====================

    public function isDeliveryOnlyRole()
    {
        // IT/Admin users are never restricted, even if they also have Delivery role
        if ($this->isAdminUser()) {
            return false;
        }
        return $this->hasRole('Delivery');
    }

    public function isPOCreatorRole()
    {
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

    // ==================== LIVE CHICKEN ====================

    public function canManageLiveChicken()
    {
        return $this->canAccessModule('live_chicken');
    }

    public function canDeleteIssueSlips()
    {
        return $this->canPerformInModule('can_delete', 'issue_slips');
    }

    // ==================== PURCHASE REQUEST ROLES ====================

    public function canManagePurchaseRequests()
    {
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
        return $this->hasPermission('can_approve', 16);
    }

    public function canApprovePurchaseRequestsAsManagement()
    {
        return $this->userRoles()->whereIn('sub_department_id', [17, 18])->where('can_approve', true)->exists()
            || $this->isAdminUser();
    }

    public function canApprovePurchaseRequestsAsExecutive()
    {
        return $this->userRoles()->whereIn('sub_department_id', [19, 20])->where('can_approve', true)->exists()
            || $this->isAdminUser();
    }

    // ==================== PURCHASE ORDER ROLES ====================

    public function canManagePurchaseOrders()
    {
        if ($this->isDeliveryOnlyRole()) {
            return false;
        }
        if ($this->isPOCreatorRole()) {
            return true;
        }
        return $this->canAccessModule('purchase_orders');
    }

    public function canCreatePurchaseOrders()
    {
        if ($this->isPOCreatorRole()) {
            return true;
        }
        return $this->canPerformInModule('can_create', 'purchase_orders');
    }

    public function canApprovePurchaseOrders()
    {
        if ($this->isPOCreatorRole()) {
            return false;
        }
        return $this->canPerformInModule('can_approve', 'purchase_orders');
    }

    public function canApprovePurchaseOrdersAsDH()
    {
        if ($this->isPOCreatorRole()) {
            return false;
        }
        return $this->hasPermission('can_approve', 16);
    }

    public function canApprovePurchaseOrdersAsManagement()
    {
        if ($this->isPOCreatorRole()) {
            return false;
        }
        return $this->userRoles()->whereIn('sub_department_id', [17, 18])->where('can_approve', true)->exists()
            || $this->isAdminUser();
    }

    public function canApprovePurchaseOrdersAsExecutive()
    {
        if ($this->isPOCreatorRole()) {
            return false;
        }
        return $this->userRoles()->whereIn('sub_department_id', [19, 20])->where('can_approve', true)->exists()
            || $this->isAdminUser();
    }

    // ==================== REQUEST FOR PAYMENT ROLES ====================

    public function canManageRequestForPayments()
    {
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
        return $this->canPerformInModule('can_approve', 'rfp')
            || $this->canApproveRFPAsDH()
            || $this->canApproveRFPAsAccounting()
            || $this->canApproveRFPAsExecutive();
    }

    public function canApproveRFPAsDH()
    {
        return $this->hasPermission('can_approve', 16);
    }

    public function canApproveRFPAsAccounting()
    {
        return $this->hasPermission('can_approve', 26);
    }

    public function canApproveRFPAsExecutive()
    {
        return $this->userRoles()->whereIn('sub_department_id', [18, 19, 20])->where('can_approve', true)->exists()
            || $this->isAdminUser();
    }

    // APV permissions
    public function canApproveAPVAsDH()
    {
        return $this->hasPermission('can_approve', 16);
    }

    public function canApproveAPV()
    {
        return $this->hasPermission('can_approve', 26);
    }

    public function canApproveAPVInvoices()
    {
        return $this->canApproveAPV();
    }

    // CV permissions
    public function canApproveCVAsAccounting()
    {
        return $this->hasPermission('can_approve', 26);
    }

    public function canApproveCV()
    {
        return $this->hasPermission('can_approve', 26);
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

    public function canApproveCustomerChange()
    {
        return $this->hasRole(['IT', 'Admin', 'Information System'])
            || in_array($this->role, ['IT', 'Admin', 'Information System']);
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

    const ADMIN_SUB_DEPARTMENTS = [5, 12];

    const MODULE_SUB_DEPARTMENTS = [
        'sales_orders'       => [13, 15, 26],                    // Sales, Credit & Collection, Accounting
        'customers'          => [15, 26, 12],                  // Credit & Collection, Accounting, IT Operations
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
        'gl_accounts'        => [26, 5, 12],                   // Accounting, General Administration, IT Operations
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
        'debit_memos'        => [22, 26, 24],                  // FAS Trade, Accounting, Treasury
        'payment_terms'      => [22, 26, 24],                  // FAS Trade, Accounting, Treasury
        'ap_ledger'          => [22, 26, 24],                  // FAS Trade, Accounting, Treasury
        'ap_dashboard'       => [22, 26, 24, 5, 6, 16, 17, 18, 19, 20], // FAS Trade, Accounting, Treasury, Admin, Purchasing, Executive
        'ap_reports'         => [22, 26, 24, 16, 17, 18, 19, 20],       // FAS Trade, Accounting, Treasury, Executive
        'inhouse_bom'        => [30],                                     // Operations NBC
        'asset_classes'      => [26, 5, 12],                              // Accounting, General Administration, IT Operations
        'fixed_assets'       => [26, 5, 12],                              // Accounting, General Administration, IT Operations
        'disposals'          => [26, 5, 12],                              // Accounting, General Administration, IT Operations
        'depreciation_runs'  => [26, 5, 12],                              // Accounting, General Administration, IT Operations
        'loans'              => [26, 24, 5, 12],                          // Accounting, Treasury, General Administration, IT Operations
        'journal_vouchers'   => [26, 5, 12],                              // Accounting, General Administration, IT Operations
        'treasury'           => [24, 26, 5, 12],                          // Treasury, Accounting, General Administration, IT Operations
        'soa'                => [15, 26, 5, 12],                          // Credit & Collection, Accounting, General Administration, IT Operations
        'delivery_counter_dates' => [15, 25, 26, 5, 12],                  // Credit & Collection, Delivery, Accounting, General Administration, IT Operations
        'counter_date_approvals' => [15, 26, 5, 12],                      // Credit & Collection, Accounting, General Administration, IT Operations
        'daily_feed_usage'   => [30, 5, 12],                              // Operations NBC, General Administration, IT Operations
        'storages'           => [2, 5, 12],                               // SC - Logistics, General Administration, IT Operations
        'asset_classes'      => [26, 5, 12],                              // Accounting, General Administration, IT Operations
        'disposals'          => [26, 5, 12],                              // Accounting, General Administration, IT Operations
        'depreciation_runs'  => [26, 5, 12],                              // Accounting, General Administration, IT Operations
        'loans'              => [26, 24, 5, 12],                          // Accounting, Treasury, General Administration, IT Operations
        'live_chicken'       => [1, 2, 3, 4, 6, 5, 12],                  // Supply Chain, Purchasing, General Administration, IT Operations
    ];

    const MODULE_ACTION_DEPARTMENTS = [
        'sales_orders' => [13, 15, 26],
        'deliveries'   => [15, 25],
    ];

    // ==================== MODULE OVERRIDE RELATIONSHIP ====================

    public function moduleOverrides()
    {
        return $this->hasMany(UserModuleOverride::class);
    }

    // ==================== RBAC HELPER METHODS ====================

    /**
     * Check if user is IT/Admin — these users bypass ALL restrictions.
     */
    public function isAdminUser(): bool
    {
        // Anyone in the "Information System" department has full unrestricted access
        $inInfoSystemDept = $this->userRoles()
            ->whereHas('subDepartment', function ($q) {
                $q->whereHas('department', function ($q2) {
                    $q2->where('name', 'Information System');
                });
            })
            ->exists();

        if ($inInfoSystemDept) return true;

        // Fallback: hardcoded admin sub-departments or legacy IT/Admin role
        return $this->userRoles()
                ->whereIn('sub_department_id', self::ADMIN_SUB_DEPARTMENTS)
                ->exists()
            || $this->hasRole(['IT', 'Admin', 'Information System'])
            || in_array($this->role, ['IT', 'Admin', 'Information System']);
    }

    /**
     * Check a specific nav item override (dot-notation key).
     * Returns true/false if override exists, or calls $default closure if no override.
     * NOTE: Admin users bypass this entirely.
     */
    public function navAccess(string $key, callable $default): bool
    {
        // IT/Admin always has access — overrides cannot block them
        if ($this->isAdminUser()) {
            return true;
        }

        $override = $this->moduleOverrides()->where('module', $key)->first();
        if ($override !== null) {
            return (bool) $override->allowed;
        }

        return $default();
    }

    /**
     * Check if user can access a module.
     *
     * Priority order:
     * 1. IT/Admin → always full access, nothing blocks this
     * 2. Sub-department match (primary OR secondary) → auto access
     * 3. Explicit grant override by IT/Admin → extra access on top
     * 4. No match → denied
     */
    public function canAccessModule(string $module): bool
    {
        // 1. IT/Admin unconditionally passes — overrides cannot block admins
        if ($this->isAdminUser()) {
            return true;
        }

        $subDeptIds = self::MODULE_SUB_DEPARTMENTS[$module] ?? [];

        // Empty array = all users can access (cash_advance, reimbursement, etc.)
        if (empty($subDeptIds)) {
            return true;
        }

        // 2. Sub-department assignment (primary + secondary both count)
        if ($this->userRoles()->whereIn('sub_department_id', $subDeptIds)->exists()) {
            return true;
        }

        // 3. Explicit grant override (adds extra access on top of sub-dept)
        return $this->moduleOverrides()
            ->where('module', $module)
            ->where('allowed', true)
            ->exists();
    }

    /**
     * Check if user has a specific permission flag in a module.
     *
     * IT/Admin always passes.
     * Others need the permission flag set in their sub-department role assignment.
     */
    public function canPerformInModule(string $flag, string $module): bool
    {
        // 1. IT/Admin always passes
        if ($this->isAdminUser()) {
            return true;
        }

        $subDeptIds = self::MODULE_ACTION_DEPARTMENTS[$module]
            ?? self::MODULE_SUB_DEPARTMENTS[$module]
            ?? [];

        // Empty = all users can perform this action
        if (empty($subDeptIds)) {
            return true;
        }

        // 2. Check sub-department has this permission flag enabled
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

    // =================== MODULE ACCESS METHODS ===================

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
        // IT/Admin always wins — nothing blocks them
        if ($this->isAdminUser()) return true;

        // Per-user override takes priority (even over CC role block)
        $override = $this->moduleOverrides()->where('module', 'aging_reports')->first();
        if ($override !== null) {
            return (bool) $override->allowed;
        }

        // CC roles cannot access AR Aging reports by default — only collections
        if ($this->isCCRole()) {
            // Unless explicitly granted by IT/Admin
            return $this->moduleOverrides()
                ->where('module', 'aging_reports')
                ->where('allowed', true)
                ->exists();
        }

        return $this->canAccessModule('aging_reports');
    }

    public function canAccessCollections()
    {
        return $this->isAdminUser()
            || $this->isCCRole()
            || $this->canAccessModule('payments')
            || in_array($this->role, ['Accounting_Approver', 'Accounting_Creator']);
    }

    public function canAccessARDashboard()
    {
        // IT/Admin always wins
        if ($this->isAdminUser()) return true;

        // Per-user override takes priority
        $override = $this->moduleOverrides()->where('module', 'ar_dashboard')->first();
        if ($override !== null) {
            return (bool) $override->allowed;
        }

        // CC cannot access the AR dashboard by default
        if ($this->isCCRole()) {
            return $this->moduleOverrides()
                ->where('module', 'ar_dashboard')
                ->where('allowed', true)
                ->exists();
        }

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
        return $this->hasRole(['President', 'Vice President']);
    }
}