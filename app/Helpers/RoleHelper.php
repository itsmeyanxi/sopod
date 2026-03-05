<?php

namespace App\Helpers;

class RoleHelper
{
    public static function canManageSalesOrders()
    {
        return auth()->check() && auth()->user()->canManageSalesOrders();
    }

    public static function canCreateSalesOrders()
    {
        return auth()->check() && auth()->user()->canCreateSalesOrders();
    }

    public static function canApproveSalesOrders()
    {
        return auth()->check() && auth()->user()->canApproveSalesOrders();
    }

    public static function canManageItems()
    {
        return auth()->check() && auth()->user()->canManageItems();
    }

    public static function canApproveItems()
    {
        return auth()->check() && auth()->user()->canApproveItems();
    }

    public static function canManageCustomers()
    {
        return auth()->check() && auth()->user()->canManageCustomers();
    }

    public static function canManageDeliveries()
    {
        return auth()->check() && auth()->user()->canManageDeliveries();
    }

    public static function canManageUsers()
    {
        return auth()->check() && auth()->user()->canManageUsers();
    }

    public static function isAdminOrIT()
    {
        return auth()->check() && auth()->user()->isAdminUser();
    }

    public static function unauthorized()
    {
        return response()->view('errors.noaccess', [], 403);
    }

    public static function canUpdateSalesOrderStatus()
    {
        return auth()->check() && auth()->user()->canApproveSalesOrders();
    }

    public static function canaccessexcelimport()
    {
        return auth()->check() && auth()->user()->canAccessExcelImport();
    }

    public static function canInitiateEdit()
    {
        return auth()->check() && auth()->user()->canInitiateEdit();
    }

    public static function canEditAfterCCApproval()
    {
        return auth()->check() && auth()->user()->canEditAfterCCApproval();
    }

    public static function canCreateDeliveries()
    {
        return auth()->check() && auth()->user()->canCreateDeliveries();
    }

    public static function canApproveDeliveries()
    {
        return auth()->check() && auth()->user()->canApproveDeliveries();
    }

    public static function canViewChangelog()
    {
        return auth()->check() && auth()->user()->canAccessChangelog();
    }
}
