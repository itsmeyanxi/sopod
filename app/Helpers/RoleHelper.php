<?php

namespace App\Helpers;

class RoleHelper
{
    public static function canManageSalesOrders()
    {
        if (!auth()->check()) {
        return false;
    }

        $role = auth()->user()->role ?? null;
        return in_array($role, ['Admin', 'IT', 'CSR_Approver','CSR_Creator']);
    }

    public static function canCreateSalesOrders()
    {
        if (!auth()->check()) {
        return false;
    }

        $role = auth()->user()->role ?? null;
        return in_array($role, ['Admin', 'IT', 'CSR_Approver','CSR_Creator',]);
    }

    public static function canApproveSalesOrders()
    {
        if (!auth()->check()) {
        return false;
    }

        $role = auth()->user()->role ?? null;
        return in_array($role, ['Admin', 'IT', 'CSR_Approver']);
    }

    public static function canManageItems()
    {
        if (!auth()->check()) {
        return false;
    }

        $role = auth()->user()->role ?? null;
        return in_array($role, ['Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver','CC_Creator', 'CC_Approver']);
    }

        public static function canApproveItems()
    {
        return auth()->check() && auth()->user()->canApproveItems();
    }

    public static function canManageCustomers()
    {
        if (!auth()->check()) {
        return false;
    }

        $role = auth()->user()->role ?? null;
        return in_array($role, ['Admin', 'IT', 'CC_Approver','CC_Creator','Accounting_Creator', 'Accounting_Approver']);
    }

    public static function canManageDeliveries()
    {
        if (!auth()->check()) {
        return false;
    }

        $role = auth()->user()->role ?? null;
        return in_array($role, [ 'Delivery_Creator', 'Delivery_Approver', 'Admin', 'IT', 'CC_Approver']);
    }

    public static function canManageUsers()
    {
        if (!auth()->check()) {
        return false;
    }

        $role = auth()->user()->role ?? null;
        return in_array($role, ['Admin', 'IT']);
    }

    public static function isAdminOrIT()
    {
        if (!auth()->check()) {
        return false;
    }
    
        $role = auth()->user()->role ?? null;
        return in_array($role, ['Admin', 'IT']);
    }

    public static function unauthorized()
    {
        return response()->view('errors.noaccess', [], 403);
    }

        public static function canUpdateSalesOrderStatus()
    {

        if (!auth()->check()) {
            return false;
        }
        
        $role = auth()->user()->role ?? null;
        return in_array($role, ['Admin', 'IT', 'CSR_Approver']);
    }

     public static function canaccessexcelimport()
    {

        if (!auth()->check()) {
            return false;
        }
        
        $role = auth()->user()->role ?? null;
        return in_array($role, ['Admin', 'IT', 'CC_Approver', 'CC_Creator','Accounting_Creator', 'Accounting_Approver']);
    }
        
}
