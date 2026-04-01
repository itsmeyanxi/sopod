<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\{
    CustomerController,
    UserController,
    SalesOrderController,
    ItemController,
    DashboardController,
    DeliveriesController,
    UserManagementController,
    RecordsController,
    ImportController,
    ChangeLogController,
    SalesDashboardController,
    PurchaseRequestController,
    PurchaseOrderController,
    RequestForPaymentController,
    AccountsPayableInvoiceController,
    CheckVoucherController,
    SuppliersController,
    PurchaseOrderRecordsController,
    SupplierImportController,
    AgingReportController,
    InvoiceController,
    ExcelImportController,
    PaymentController,
    ReceivingReportsController,
    ArAdjustmentController,
    ARDashboardController,
    LockController,
    SupplierReceivingReportController,
    NonTradeItemController,
    TradeItemController,
    CurrencyController,
    POSummaryController,
    CashAdvanceRequestController,
    LiquidationFormController,
    ReimbursementFormController,
    RoleController,
    WarehouseController,
    StorageController,
    CustomerARProfileController,
    GlAccountController,
    PaymentTermController,
    DebitMemoController,
    ApLedgerController,
    ApDashboardController,
    ApReportController,
    InHouseBomController,
    StatementOfAccountController,
    DeliveryCounterDateController,
    CounterDateApprovalController,
    DailyFeedUsageController,
    PaymentConfirmationController,
    TreasurySummaryController,
    TreasuryBankController,
    AssetClassController,
    FixedAssetController,
    DisposalController,
    JournalVoucherController,
    DepreciationRunController,
    LoanController
};

Route::post('/users/reset-login-attempts', [UserController::class, 'resetLoginAttempts'])
    ->name('users.reset-attempts')
    ->middleware('auth');

    // ===================== SUPPLIER IMPORT =====================
    Route::post('/excel/import/suppliers', [SupplierImportController::class, 'importSuppliers'])
    ->name('excel.import.suppliers');

    // ===================== Warehouse =====================
    Route::resource('warehouses', WarehouseController::class)->middleware('auth');

    // ===================== Storage =====================
    Route::resource('storages', StorageController::class)->middleware('auth');

    // ===================== AUTH (Public Routes) =====================
    Route::get('/login', [UserController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [UserController::class, 'login'])->name('login.submit');
    Route::match(['get', 'post'], '/logout', [UserController::class, 'logout'])->name('logout');

    // ===================== AUTHENTICATED ROUTES =====================
    Route::middleware(['auth'])->group(function () {

    // ===================== PO SUMMARY ROUTES =====================
    Route::get('/po-summary', [POSummaryController::class, 'index'])->name('po_summary');
    Route::get('/po-summary/api-data', [POSummaryController::class, 'apiData'])->name('po_summary.api_data');

    // ===================== PAYMENTS ENTRY =====================
    Route::prefix('payments')->name('payments.')->group(function () {
    Route::get('/entry', [PaymentController::class, 'entry'])->name('entry');
    Route::get('/search-customer', [PaymentController::class, 'searchCustomer'])->name('searchCustomer');
    Route::get('/customer-history', [PaymentController::class, 'getCustomerHistory'])->name('customerHistory');
    Route::post('/store', [PaymentController::class, 'store'])->name('store');
    Route::get('/collection-report', [PaymentController::class, 'collectionReport'])->name('collectionReport');
    Route::get('/export', [PaymentController::class, 'export'])->name('export');
    Route::get('/duplicate-cr', [PaymentController::class, 'viewDuplicateCR'])->name('duplicateCR');
    Route::get('/customer-credits', [PaymentController::class, 'getCustomerCredits'])->name('customerCredits');
    Route::get('/check-dr-status', [PaymentController::class, 'checkDRStatus'])->name('checkDRStatus');
    Route::get('/debug-delivery/{drNo}', [PaymentController::class, 'debugDeliverySearch'])->name('debugDelivery');
    Route::get('/debug-search/{search}', [PaymentController::class, 'debugSearch'])->name('debugSearch');

    // Edit requests management (must be before /{id} routes)
    Route::get('/edit-requests', [PaymentController::class, 'editRequests'])->name('editRequests');
    Route::post('/edit-requests/{requestId}/approve', [PaymentController::class, 'approveEditRequest'])->name('approveEditRequest');
    Route::post('/edit-requests/{requestId}/reject', [PaymentController::class, 'rejectEditRequest'])->name('rejectEditRequest');

    // Edit payment (must be before /{id} show route)
    Route::get('/{id}/edit', [PaymentController::class, 'edit'])->name('edit')->where('id', '[0-9]+');
    Route::put('/{id}', [PaymentController::class, 'update'])->name('update')->where('id', '[0-9]+');
    Route::post('/{id}/edit-request', [PaymentController::class, 'submitEditRequest'])->name('submitEditRequest')->where('id', '[0-9]+');

    Route::get('/{id}', [PaymentController::class, 'show'])->name('show')->where('id', '[0-9]+');
});

    // ===================== STATEMENT OF ACCOUNTS =====================
    Route::prefix('soa')->name('soa.')->group(function () {
        Route::get('/', [StatementOfAccountController::class, 'index'])->name('index');
        Route::get('/export/{customerCode}', [StatementOfAccountController::class, 'export'])->name('export');
        Route::get('/{customerCode}', [StatementOfAccountController::class, 'show'])->name('show');
    });

    // ===================== DELIVERY COUNTER DATES =====================
    Route::prefix('delivery-counter-dates')->name('delivery_counter_dates.')->group(function () {
        Route::get('/', [DeliveryCounterDateController::class, 'index'])->name('index');
        Route::put('/{id}', [DeliveryCounterDateController::class, 'update'])->name('update');
        Route::post('/bulk-update', [DeliveryCounterDateController::class, 'bulkUpdate'])->name('bulkUpdate');
        Route::post('/{id}/clear', [DeliveryCounterDateController::class, 'clear'])->name('clear');
    });

    // ===================== COUNTER DATE APPROVAL =====================
    Route::prefix('counter-date-approvals')->name('counter_date_approvals.')->group(function () {
        Route::get('/', [CounterDateApprovalController::class, 'index'])->name('index');
        Route::get('/{id}', [CounterDateApprovalController::class, 'show'])->name('show');
        Route::post('/{id}/approve', [CounterDateApprovalController::class, 'approve'])->name('approve');
        Route::post('/{id}/upload', [CounterDateApprovalController::class, 'uploadAttachment'])->name('upload');
        Route::post('/bulk-approve', [CounterDateApprovalController::class, 'bulkApprove'])->name('bulkApprove');
    });

    // ===================== CHANGE LOG & NOTIFICATIONS =====================
    Route::get('/changelog', [ChangeLogController::class, 'index'])->name('changelog.index');
    Route::get('/changelog/sales-order/{id}', [ChangeLogController::class, 'salesOrderChanges'])->name('changelog.sales_order');
    Route::get('/changelog/export', [ChangeLogController::class, 'export'])->name('changelog.export');
    
    Route::get('/notifications', [ChangeLogController::class, 'notifications'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [ChangeLogController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [ChangeLogController::class, 'markAllAsRead'])->name('notifications.read_all');
    Route::get('/notifications/unread-count', [ChangeLogController::class, 'unreadCount'])->name('notifications.unread_count');

// ===================== AGING REPORTS =====================
Route::prefix('aging-reports')->name('aging_reports.')->group(function () {
    // Main aging reports view
    Route::get('/', [AgingReportController::class, 'view'])->name('view');
    
    // Search aging reports
    Route::get('/search', [AgingReportController::class, 'search'])->name('search');
    
    // AR Aging summary (pivot table)
    Route::get('/summary', [AgingReportController::class, 'summary'])->name('summary');
    
    // AR Aging data (for AJAX)
    Route::get('/ar-aging', [AgingReportController::class, 'arAging'])->name('ar_aging');
    
    // Export aging reports list
    Route::get('/export', [AgingReportController::class, 'export'])->name('export');
    
    // Export AR Aging summary
    Route::get('/export-ar-aging', [AgingReportController::class, 'exportArAging'])->name('export_ar_aging');
    
    // ✅ Customer AR Summary - Show all aging buckets for a customer
    Route::get('/customer/{customer_code}', [AgingReportController::class, 'customerSummary'])->name('customer_summary');

    // ✅ Detail view - Show all invoices for customer in an aging bucket
    Route::get('/detail/{customer_code}/{bucket}', [AgingReportController::class, 'detail'])->name('detail');

    // ✅ AR PROFILE - Export single customer profile (must be before {id} wildcard)
    Route::get('/ar-profile/export', [AgingReportController::class, 'exportARProfile'])->name('ar_profile.export');

    // ✅ AR PROFILE - View single customer profile
    Route::get('/ar-profile/{id}', [AgingReportController::class, 'showARProfile'])->name('ar_profile');
});

// ===================== AR ADJUSTMENTS =====================
Route::prefix('ar-adjustments')->name('ar_adjustments.')->group(function () {
    // Main view
    Route::get('/', [ArAdjustmentController::class, 'index'])->name('index');

    // Create view
    Route::get('/create', [ArAdjustmentController::class, 'create'])->name('create');

    // Search AR Aging records
    Route::get('/search-ar', [ArAdjustmentController::class, 'searchArAging'])->name('search_ar');

    // Get adjustments data (for AJAX)
    Route::get('/get', [ArAdjustmentController::class, 'getAdjustments'])->name('get');

    // Get dashboard statistics
    Route::get('/stats/dashboard', [ArAdjustmentController::class, 'getDashboardStats'])->name('stats.dashboard');

    // Export adjustments
    Route::get('/export/csv', [ArAdjustmentController::class, 'export'])->name('export');

    // ✅ NEW: Get pending deliveries without adjustments
    Route::get('/deliveries/pending', [ArAdjustmentController::class, 'getPendingDeliveries'])->name('deliveries.pending');

    // ✅ NEW: Get delivery information by DR number
    Route::get('/delivery/{drNo}', [ArAdjustmentController::class, 'getDeliveryInfo'])->name('delivery.info');

    // ✅ NEW: Get all pending deliveries for a customer
    Route::get('/customer/{customerCode}/deliveries', [ArAdjustmentController::class, 'getCustomerDeliveries'])->name('customer.deliveries');

    // ✅ NEW: Get GL accounts for dropdown (searchable)
    Route::get('/gl-accounts', [ArAdjustmentController::class, 'getGlAccounts'])->name('gl_accounts');

    // Search receiving reports for linking
    Route::get('/search-rr', [ArAdjustmentController::class, 'searchReceivingReports'])->name('search_rr');

    // ✅ NEW: View adjustments by customer (must be before /{id} route)
    Route::get('/customer/{customerCode}', [ArAdjustmentController::class, 'byCustomer'])->name('by_customer');

    // Store new adjustment
    Route::post('/', [ArAdjustmentController::class, 'store'])->name('store');

    // Print adjustment (credit/debit memo)
    Route::get('/{id}/print', [ArAdjustmentController::class, 'printDoc'])->name('print');

    // Show single adjustment
    Route::get('/{id}', [ArAdjustmentController::class, 'show'])->name('show');

    // Print Memo (Debit or Credit)
    Route::get('/{id}/print-memo', [ArAdjustmentController::class, 'printMemo'])->name('print_memo');

    // Edit form
    Route::get('/{id}/edit', [ArAdjustmentController::class, 'editForm'])->name('edit');

    // Update adjustment
    Route::put('/{id}', [ArAdjustmentController::class, 'update'])->name('update');

    // Delete adjustment
    Route::delete('/{id}', [ArAdjustmentController::class, 'destroy'])->name('destroy');
});

    // ===================== CUSTOMER AR PROFILE (Cross-Module) =====================
    Route::get('/ar/customer/{customerCode}', [CustomerARProfileController::class, 'show'])
        ->name('ar.customer_profile');

// ===================== RECEIVING REPORTS =====================
Route::prefix('receiving-reports')->name('receiving-reports.')->group(function () {
    Route::get('/', [ReceivingReportsController::class, 'index'])->name('index');
    Route::get('/{id}', [ReceivingReportsController::class, 'show'])->name('show');
    Route::put('/{id}/update-dr', [ReceivingReportsController::class, 'updateDrNo'])->name('updateDr');
    Route::get('/{id}/print', [ReceivingReportsController::class, 'print'])->name('print');
    Route::get('/export/excel', [ReceivingReportsController::class, 'exportExcel'])->name('export');
});

// ===================== RECORD LOCK MANAGEMENT =====================
// Only Admin and IT roles can access this module
Route::prefix('lock')->name('lock.')->group(function () {
    Route::get('/', function () {
        if (!auth()->check() || !auth()->user()->canAccessModule('record_lock')) {
            return view('errors.noaccess');
        }
        return app(LockController::class)->index(request());
    })->name('index');

    Route::get('/details', function () {
        if (!auth()->check() || !auth()->user()->canAccessModule('record_lock')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        return app(LockController::class)->getMonthDetails(request());
    })->name('details');

    Route::post('/lock', function () {
        if (!auth()->check() || !auth()->user()->canAccessModule('record_lock')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        return app(LockController::class)->lock(request());
    })->name('lock');

    Route::post('/unlock', function () {
        if (!auth()->check() || !auth()->user()->canAccessModule('record_lock')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        return app(LockController::class)->unlock(request());
    })->name('unlock');
});

    // =================== INVOICE ROUTES ===================
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/screen', [InvoiceController::class, 'screen'])->name('screen');
    });




    //===================== SALES ANALYTICS =====================
        Route::get('/sales/dashboard', function () {
            $user = auth()->user();
            
            if (!$user || !$user->canaccesssalesanalytics()) {
                return view('errors.noaccess');
            }
            
            // If authorized, call the controller
            return app(\App\Http\Controllers\SalesDashboardController::class)->index(request());
        })->name('sales.dashboard')->middleware('auth');

            Route::get('/sales/metrics', function () {
            $user = auth()->user();
            
            if (!$user || !$user->canaccesssalesanalytics()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            
            return app(\App\Http\Controllers\SalesDashboardController::class)->getMetrics(request());
        })->name('sales.metrics')->middleware('auth');
        
        // Customer Export
        Route::get('/customers/export', [CustomerController::class, 'export'])
        ->name('customers.export')
        ->middleware('auth');

    // ===================== IN HOUSE BOM =====================
    Route::middleware('auth')->prefix('inhouse-bom')->name('inhouse_bom.')->group(function () {
        Route::get('/', function () {
            if (!auth()->user()->canAccessModule('inhouse_bom')) return view('errors.noaccess');
            return app(InHouseBomController::class)->index(request());
        })->name('index');
        Route::get('/create', function () {
            if (!auth()->user()->canAccessModule('inhouse_bom')) return view('errors.noaccess');
            return app(InHouseBomController::class)->create();
        })->name('create');
        Route::post('/', function () {
            if (!auth()->user()->canAccessModule('inhouse_bom')) return view('errors.noaccess');
            return app(InHouseBomController::class)->store(request());
        })->name('store');
        Route::get('/{inhouseBom}', function ($inhouseBom) {
            if (!auth()->user()->canAccessModule('inhouse_bom')) return view('errors.noaccess');
            return app(InHouseBomController::class)->show(\App\Models\InHouseBom::findOrFail($inhouseBom));
        })->name('show');
        Route::get('/{inhouseBom}/edit', function ($inhouseBom) {
            if (!auth()->user()->canAccessModule('inhouse_bom')) return view('errors.noaccess');
            return app(InHouseBomController::class)->edit(\App\Models\InHouseBom::findOrFail($inhouseBom));
        })->name('edit');
        Route::put('/{inhouseBom}', function ($inhouseBom) {
            if (!auth()->user()->canAccessModule('inhouse_bom')) return view('errors.noaccess');
            return app(InHouseBomController::class)->update(request(), \App\Models\InHouseBom::findOrFail($inhouseBom));
        })->name('update');
        Route::delete('/{inhouseBom}', function ($inhouseBom) {
            if (!auth()->user()->canAccessModule('inhouse_bom')) return view('errors.noaccess');
            return app(InHouseBomController::class)->destroy(\App\Models\InHouseBom::findOrFail($inhouseBom));
        })->name('destroy');
        Route::patch('/{inhouseBom}/status', function ($inhouseBom) {
            if (!auth()->user()->canAccessModule('inhouse_bom')) return view('errors.noaccess');
            return app(InHouseBomController::class)->updateStatus(request(), \App\Models\InHouseBom::findOrFail($inhouseBom));
        })->name('updateStatus');
        Route::get('/{inhouseBom}/export', function ($inhouseBom) {
            if (!auth()->user()->canAccessModule('inhouse_bom')) return view('errors.noaccess');
            return app(InHouseBomController::class)->exportExcel(\App\Models\InHouseBom::findOrFail($inhouseBom));
        })->name('export');
        Route::get('/{inhouseBom}/extend', function ($inhouseBom) {
            if (!auth()->user()->canAccessModule('inhouse_bom')) return view('errors.noaccess');
            return app(InHouseBomController::class)->extend(\App\Models\InHouseBom::findOrFail($inhouseBom));
        })->name('extend');
        Route::post('/{inhouseBom}/approve', function ($inhouseBom) {
            if (!auth()->user()->canAccessModule('inhouse_bom')) return view('errors.noaccess');
            return app(InHouseBomController::class)->approve(\App\Models\InHouseBom::findOrFail($inhouseBom));
        })->name('approve');
        Route::post('/{inhouseBom}/unapprove', function ($inhouseBom) {
            if (!auth()->user()->canAccessModule('inhouse_bom')) return view('errors.noaccess');
            return app(InHouseBomController::class)->unapprove(\App\Models\InHouseBom::findOrFail($inhouseBom));
        })->name('unapprove');
    });

    // ===================== DAILY FEED USAGE =====================
    Route::middleware('auth')->prefix('daily-feed-usage')->name('daily_feed_usage.')->group(function () {
        Route::get('/', function () {
            if (!auth()->user()->canAccessModule('inhouse_bom')) return view('errors.noaccess');
            return app(DailyFeedUsageController::class)->index(request());
        })->name('index');
        Route::get('/create', function () {
            if (!auth()->user()->canAccessModule('inhouse_bom')) return view('errors.noaccess');
            return app(DailyFeedUsageController::class)->create(request());
        })->name('create');
        Route::post('/', function () {
            if (!auth()->user()->canAccessModule('inhouse_bom')) return view('errors.noaccess');
            return app(DailyFeedUsageController::class)->store(request());
        })->name('store');
        Route::get('/bom-houses', function () {
            return app(DailyFeedUsageController::class)->getBomHouses(request());
        })->name('bomHouses')->middleware('auth');
        Route::get('/cumulative', function () {
            return app(DailyFeedUsageController::class)->getCumulativeUsage(request());
        })->name('cumulative')->middleware('auth');
        Route::get('/{dailyFeedUsage}', function ($id) {
            if (!auth()->user()->canAccessModule('inhouse_bom')) return view('errors.noaccess');
            return app(DailyFeedUsageController::class)->show(\App\Models\DailyFeedUsage::findOrFail($id));
        })->name('show');
        Route::delete('/{dailyFeedUsage}', function ($id) {
            if (!auth()->user()->canAccessModule('inhouse_bom')) return view('errors.noaccess');
            return app(DailyFeedUsageController::class)->destroy(\App\Models\DailyFeedUsage::findOrFail($id));
        })->name('destroy');
    });

    // ===================== TREASURY =====================
    Route::middleware('auth')->prefix('treasury')->name('treasury.')->group(function () {
        Route::get('/confirmation', [PaymentConfirmationController::class, 'index'])->name('confirmation');
        Route::post('/confirm/{id}', [PaymentConfirmationController::class, 'confirm'])->name('confirm');
        Route::post('/bulk-confirm', [PaymentConfirmationController::class, 'bulkConfirm'])->name('bulkConfirm');
        Route::post('/unconfirm/{id}', [PaymentConfirmationController::class, 'unconfirm'])->name('unconfirm');
        Route::get('/summary', [TreasurySummaryController::class, 'index'])->name('summary');
        Route::get('/banks/{currency}', [TreasuryBankController::class, 'banks'])->name('banks')->where('currency', 'peso|dollar');
        Route::get('/bank/{id}', [TreasuryBankController::class, 'show'])->name('bank.show')->where('id', '[0-9]+');
        Route::post('/bank/{id}/transaction', [TreasuryBankController::class, 'addTransaction'])->name('bank.addTransaction')->where('id', '[0-9]+');
        Route::patch('/bank/{id}/balance', [TreasuryBankController::class, 'updateBalance'])->name('bank.updateBalance')->where('id', '[0-9]+');
    });

    // ===================== BATCH PRINT =====================
    Route::post('/records/sales-orders/batch-print',
    [RecordsController::class, 'batchPrintSalesOrders']
    )->name('records.batchPrintSO');

    // ===================== IMPORTS =======================
    Route::get('/excel-import', function () {
        $user = auth()->user();
        
        if (!$user || !$user->canImportCustomers()) {
            return view('errors.noaccess');
        }
        
        return view('excel.excel-import'); 
    })->name('excel.import');

    Route::post('/excel/import/collections', [ExcelImportController::class, 'importCollections'])
    ->name('excel.import.collections')
    ->middleware(['auth']);

Route::post('/excel/import/bom-materials', [ExcelImportController::class, 'importBomMaterials'])->name('excel.import.bom_materials');
Route::post('/excel/import/asset-classes', [ExcelImportController::class, 'importAssetClasses'])->name('excel.import.asset_classes');

// ── Imports Module ──────────────────────────────────────────────────────────
Route::middleware(['auth'])->group(function () {
    Route::get('/imports', function () {
        return view('imports.index');
    })->name('imports.index');

    Route::post('/imports/upload', function (\Illuminate\Http\Request $request) {
        $type = $request->input('import_type');
        $ec   = app(\App\Http\Controllers\ExcelImportController::class);
        return match ($type) {
            'customers'     => $ec->importCustomers($request),
            'items'         => $ec->importItems($request),
            'monthly_sales' => $ec->importMonthlySales($request),
            'asset_classes' => $ec->importAssetClasses($request),
            default         => redirect()->back()->with('error', 'Unknown import type.'),
        };
    })->name('import.upload');

    Route::get('/imports/template/monthly-sales', [ImportController::class, 'downloadMonthlySalesTemplate'])
        ->name('import.monthly_sales.template');
});

// ✅ NEW: AR Adjustments Import Route
Route::post('/excel/import/ar-adjustments', [ExcelImportController::class, 'importArAdjustments'])
    ->name('excel.import.ar_adjustments')
    ->middleware(['auth']);

// ===================== GL ACCOUNTS / CHART OF ACCOUNTS =====================
Route::prefix('accounting/gl-accounts')->name('gl_accounts.')->middleware('auth')->group(function () {
    Route::get('/',             [GlAccountController::class, 'index'])->name('index');
    Route::get('/create',       [GlAccountController::class, 'create'])->name('create');
    Route::get('/get',          [GlAccountController::class, 'getAccounts'])->name('get');
    Route::get('/export',       [GlAccountController::class, 'export'])->name('export');
    Route::post('/',            [GlAccountController::class, 'store'])->name('store');
    Route::get('/{id}',         [GlAccountController::class, 'show'])->name('show');
    Route::get('/{id}/edit',    [GlAccountController::class, 'editForm'])->name('edit');
    Route::put('/{id}',         [GlAccountController::class, 'update'])->name('update');
    Route::delete('/{id}',      [GlAccountController::class, 'destroy'])->name('destroy');
});

Route::post('/excel/import/gl-accounts', [ExcelImportController::class, 'importGlAccounts'])
    ->name('excel.import.gl_accounts')
    ->middleware(['auth']);

// ===================== ASSET CLASSES MASTERDATA =====================
Route::prefix('accounting/asset-classes')->name('asset_classes.')->middleware(['auth', 'module:asset_classes'])->group(function () {
    Route::get('/',           [AssetClassController::class, 'index'])->name('index');
    Route::get('/create',     [AssetClassController::class, 'create'])->name('create');
    Route::post('/',          [AssetClassController::class, 'store'])->name('store');
    Route::get('/{assetClass}/edit', [AssetClassController::class, 'edit'])->name('edit');
    Route::put('/{assetClass}',      [AssetClassController::class, 'update'])->name('update');
    Route::delete('/{assetClass}',   [AssetClassController::class, 'destroy'])->name('destroy');
});

// ===================== FIXED ASSET CAPITALIZATION =====================
Route::prefix('accounting/fixed-assets')->name('fixed_assets.')->middleware(['auth', 'module:fixed_assets'])->group(function () {
    Route::get('/',             [FixedAssetController::class, 'index'])->name('index');
    Route::get('/summary',      [FixedAssetController::class, 'summary'])->name('summary');
    Route::get('/report/depreciation',  [FixedAssetController::class, 'reportDepreciation'])->name('report_depreciation');
    Route::get('/report/transactions',  [FixedAssetController::class, 'reportTransactions'])->name('report_transactions');
    Route::get('/report/cost-center',   [FixedAssetController::class, 'reportCostCenter'])->name('report_cost_center');
    Route::get('/create',       [FixedAssetController::class, 'create'])->name('create');
    Route::post('/',            [FixedAssetController::class, 'store'])->name('store');
    Route::post('/import',      [FixedAssetController::class, 'import'])->name('import');
    Route::get('/po-items/{poId}', [FixedAssetController::class, 'getPoItems'])->name('po_items');
    Route::post('/{id}/dispose', [FixedAssetController::class, 'dispose'])->name('dispose');
    Route::get('/{id}',         [FixedAssetController::class, 'show'])->name('show');
    Route::get('/{id}/edit',    [FixedAssetController::class, 'edit'])->name('edit');
    Route::put('/{id}',         [FixedAssetController::class, 'update'])->name('update');
    Route::delete('/{id}',      [FixedAssetController::class, 'destroy'])->name('destroy');
});

// ===================== DISPOSAL MODULE =====================
Route::prefix('accounting/disposals')->name('disposals.')->middleware(['auth', 'module:disposals'])->group(function () {
    Route::get('/',      [DisposalController::class, 'index'])->name('index');
    Route::get('/{id}',  [DisposalController::class, 'show'])->name('show');
});

// ===================== DEPRECIATION RUNS =====================
Route::prefix('accounting/depreciation-runs')->name('depreciation_runs.')->middleware(['auth', 'module:depreciation_runs'])->group(function () {
    Route::get('/',             [DepreciationRunController::class, 'index'])->name('index');
    Route::get('/create',       [DepreciationRunController::class, 'create'])->name('create');
    Route::post('/',            [DepreciationRunController::class, 'store'])->name('store');
    Route::get('/{id}',         [DepreciationRunController::class, 'show'])->name('show');
    Route::post('/{id}/post',   [DepreciationRunController::class, 'post'])->name('post');
    Route::post('/{id}/void',   [DepreciationRunController::class, 'void'])->name('void');
    Route::delete('/{id}',      [DepreciationRunController::class, 'destroy'])->name('destroy');
});

// ===================== LOANS =====================
Route::prefix('banking/loans')->name('loans.')->middleware(['auth', 'module:loans'])->group(function () {
    Route::get('/',                 [LoanController::class, 'index'])->name('index');
    Route::get('/create',           [LoanController::class, 'create'])->name('create');
    Route::post('/',                [LoanController::class, 'store'])->name('store');
    Route::get('/{id}',             [LoanController::class, 'show'])->name('show');
    Route::get('/{id}/edit',        [LoanController::class, 'edit'])->name('edit');
    Route::put('/{id}',             [LoanController::class, 'update'])->name('update');
    Route::post('/{id}/payment',    [LoanController::class, 'storePayment'])->name('payment');
    Route::post('/{id}/void',       [LoanController::class, 'markVoid'])->name('void');
});

// ===================== JOURNAL VOUCHERS =====================
Route::prefix('accounting/journal-vouchers')->name('journal_vouchers.')->middleware(['auth', 'module:journal_vouchers'])->group(function () {
    Route::get('/',             [JournalVoucherController::class, 'index'])->name('index');
    Route::get('/create',       [JournalVoucherController::class, 'create'])->name('create');
    Route::post('/',            [JournalVoucherController::class, 'store'])->name('store');
    Route::get('/{id}',         [JournalVoucherController::class, 'show'])->name('show');
    Route::get('/{id}/edit',    [JournalVoucherController::class, 'edit'])->name('edit');
    Route::put('/{id}',         [JournalVoucherController::class, 'update'])->name('update');
    Route::delete('/{id}',      [JournalVoucherController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/post',   [JournalVoucherController::class, 'post'])->name('post');
    Route::post('/{id}/void',   [JournalVoucherController::class, 'void'])->name('void');
    Route::get('/{id}/print',   [JournalVoucherController::class, 'print'])->name('print');
});

    // IMPORT ITEMS — only Admin, IT + Accounting roles
    Route::post('/excel-import/items', function () {
        $user = auth()->user();
        if (!$user || !$user->canImportItems()) { 
            abort(403, 'Unauthorized');
        }
        return app(App\Http\Controllers\ExcelImportController::class)->importItems(request());
    })->name('excel.import.items');

    // IMPORT MONTHLY SALES
    Route::post('/excel-import/monthly-sales', function () {
        $user = auth()->user();
        if (!$user || !$user->canAccessModule('apv')) {
            abort(403, 'Unauthorized');
        }
        return app(App\Http\Controllers\ExcelImportController::class)->importMonthlySales(request());
    })->name('excel.import.monthly_sales');

    // IMPORT CUSTOMERS — all the listed roles
    Route::post('/excel-import/customers', function () {
        $user = auth()->user();
        if (!$user || !$user->canImportCustomers()) { 
            abort(403, 'Unauthorized'); 
        }
        return app(App\Http\Controllers\ExcelImportController::class)->importCustomers(request());
    })->name('excel.import.customers');

    // IMPORT AR AGING — Admin, IT + Accounting roles
    Route::post('/excel-import/ar-aging', function () {
        $user = auth()->user();
        if (!$user || !$user->canAccessModule('apv')) {
            abort(403, 'Unauthorized');
        }
        return app(App\Http\Controllers\ExcelImportController::class)->importARAging(request());
    })->name('excel.import.ar_aging');

    // ===================== DASHBOARD =====================
    Route::match(['get', 'post'], '/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/po-dashboard', [DashboardController::class, 'poDashboard'])->name('po_dashboard');
    Route::get('/recent-activities', [DashboardController::class, 'viewAllActivities'])->name('recent_activities.index');

    // In routes/web.php
    Route::get('/sales-report', [DashboardController::class, 'salesReport'])->name('sales.report');

    // AR Dashboard
    Route::get('/ar-dashboard', [ARDashboardController::class, 'index'])->name('ar_dashboard.index');
    Route::get('/ar-dashboard/export-summary', [ARDashboardController::class, 'exportSummary'])->name('ar_dashboard.export_summary');
    Route::get('/ar-dashboard/export-details', [ARDashboardController::class, 'exportDetails'])->name('ar_dashboard.export_details');

    // ===================== RECORDS =====================
    Route::get('/records', [App\Http\Controllers\RecordsController::class, 'index'])->name('records.index');

    //export excel
   Route::get('/records/export/excel', [RecordsController::class, 'exportExcel'])->name('records.export.excel');
    
    // Sales Order Records
    Route::get('/records/sales-order/{id}', [App\Http\Controllers\RecordsController::class, 'so_show'])->name('records.so_show');
// Add this to your routes/web.php temporarily for debugging

Route::get('/debug-ar-adjustments/{customerCode}', function($customerCode) {
    
    // Get AR Aging record for this customer
    $arRecord = DB::table('ar_aging')
        ->where('customer_code', $customerCode)
        ->first();
    
    if (!$arRecord) {
        return response()->json([
            'error' => 'Customer not found in ar_aging',
            'customer_code_searched' => $customerCode
        ]);
    }
    
    // Get all adjustments
    $allAdjustments = DB::table('ar_adjustments')
        ->select('id', 'customer_code', 'customer_name', 'reference_number', 'amount', 'created_at', 'created_by')
        ->orderBy('id', 'desc')
        ->limit(20)
        ->get();
    
    // Try exact match
    $exactMatch = DB::table('ar_adjustments')
        ->where('customer_code', $customerCode)
        ->get();
    
    // Try trimmed match
    $trimmedMatch = DB::table('ar_adjustments')
        ->whereRaw('TRIM(customer_code) = ?', [trim($customerCode)])
        ->get();
    
    // Get invoice numbers for this customer
    $invoiceNumbers = DB::table('ar_aging')
        ->where('customer_code', $customerCode)
        ->pluck('invoice_no')
        ->filter()
        ->toArray();
    
    // Try invoice match
    $invoiceMatch = DB::table('ar_adjustments')
        ->whereIn('invoice_number', $invoiceNumbers)
        ->get();
    
    // Check if customer_code has special characters
    $customerCodeHex = bin2hex($customerCode);
    $customerCodeLength = strlen($customerCode);
    
    return response()->json([
        'customer_info' => [
            'code' => $customerCode,
            'code_hex' => $customerCodeHex,
            'code_length' => $customerCodeLength,
            'name' => $arRecord->client_name ?? 'N/A',
            'invoice_count' => count($invoiceNumbers),
            'sample_invoices' => array_slice($invoiceNumbers, 0, 5),
        ],
        'adjustments_found' => [
            'exact_match' => [
                'count' => $exactMatch->count(),
                'data' => $exactMatch
            ],
            'trimmed_match' => [
                'count' => $trimmedMatch->count(),
                'data' => $trimmedMatch
            ],
            'invoice_match' => [
                'count' => $invoiceMatch->count(),
                'data' => $invoiceMatch
            ],
        ],
        'recent_adjustments_all' => $allAdjustments,
        'diagnosis' => [
            'has_exact_match' => $exactMatch->count() > 0 ? '✅ YES' : '❌ NO',
            'has_trimmed_match' => $trimmedMatch->count() > 0 ? '✅ YES' : '❌ NO',
            'has_invoice_match' => $invoiceMatch->count() > 0 ? '✅ YES' : '❌ NO',
        ]
    ]);
})->where('customerCode', '.*');
    // Delivery Records
    Route::get('/records/delivery/{id}', [App\Http\Controllers\RecordsController::class, 'dshow'])->name('records.dshow');
// AR Adjustments Fix Routes
Route::get('/diagnostic-adjustments', [CustomerController::class, 'diagnosticAdjustments']);
Route::get('/diagnostic-ar-aging-match', [CustomerController::class, 'diagnosticArAgingMatch']);
Route::get('/fix-adjustments-via-ar-aging', [CustomerController::class, 'fixAdjustmentsViaArAging']);
Route::post('/bulk-assign-adjustments', [CustomerController::class, 'bulkAssignCustomer']);
     // ===================== SALES ORDERS =====================
Route::prefix('sales_orders')->name('sales_orders.')->group(function () {

    // =================== SPECIFIC ACTION ROUTES (MUST BE BEFORE {id} ROUTES) ===================
    Route::post('/bulk-approve', function () {
        if (auth()->user()->canApproveSalesOrders()) {
            return app(SalesOrderController::class)->bulkApprove(request());
        }
        return view('errors.noaccess');
    })->name('bulkApprove');

    Route::post('/bulk-decline', function () {
        if (auth()->user()->canApproveSalesOrders()) {
            return app(SalesOrderController::class)->bulkDecline(request());
        }
        return view('errors.noaccess');
    })->name('bulkDecline');

    // Manual Close Sales Order
    Route::patch('/{id}/close', function ($id) {
        if (auth()->user()->canPerformInModule('can_manage', 'sales_orders')) {
            return app(SalesOrderController::class)->manualClose($id);
        }
        return view('errors.noaccess');
    })->name('manualClose');

    // Print Routes
    Route::get('/print-list', [SalesOrderController::class, 'printList'])->name('printList');
    Route::get('/{id}/print', [SalesOrderController::class, 'print'])->name('print');

    // Excel Export
    Route::get('/export-excel', function () {
        if (auth()->user()->canPerformInModule('can_export', 'sales_orders')) {
            return app(SalesOrderController::class)->exportExcel(request());
        }
        return view('errors.noaccess');
    })->name('exportExcel');

    // Delivery Batches
    Route::get('/{id}/delivery-batches', [SalesOrderController::class, 'deliveryBatches'])
        ->name('delivery_batches');

    // Create
    Route::get('/create', function () {
        if (auth()->user()->canCreateSalesOrders()) {
            return app(SalesOrderController::class)->create();
        }
        return view('errors.noaccess');
    })->name('create');

    // Accepted
    Route::get('/accepted', function () {
        if (auth()->user()->canManageSalesOrders()) {
            return app(SalesOrderController::class)->accepted(request());
        }
        return view('errors.noaccess');
    })->name('accepted');

    // Search
    Route::get('/search', function () {
        if (auth()->user()->canManageSalesOrders()) {
            return app(SalesOrderController::class)->search(request());
        }
        return view('errors.noaccess');
    })->name('search');

    // Edit
    Route::get('/{id}/edit', function ($id) {
        if (auth()->user()->canPerformInModule('can_edit', 'sales_orders')) {
            return app(SalesOrderController::class)->edit($id);
        }
        return view('errors.noaccess');
    })->name('edit');

    // Add Items to Approved SO
    Route::get('/{id}/add-items', function ($id) {
        if (auth()->user()->canCreateSalesOrders()) {
            return app(SalesOrderController::class)->addItemsForm($id);
        }
        return view('errors.noaccess');
    })->name('addItemsForm');

    // =================== POST/PUT/PATCH/DELETE ROUTES ===================

    // Store
    Route::post('/', function () {
        if (auth()->user()->canCreateSalesOrders()) {
            return app(SalesOrderController::class)->store(request());
        }
        return view('errors.noaccess');
    })->name('store');

    // Approve
    Route::post('/{id}/approve', function ($id) {
        if (auth()->user()->canApproveSalesOrders()) {
            return app(SalesOrderController::class)->approve($id);
        }
        return view('errors.noaccess');
    })->name('approve');

    // Approve for Editing
    Route::post('/{id}/approve-edit', function ($id) {
        if (auth()->user()->canPerformInModule('can_manage', 'sales_orders')) {
            return app(SalesOrderController::class)->approveForEdit($id);
        }
        return view('errors.noaccess');
    })->name('approveForEdit');

    // Update
    Route::put('/{id}', function ($id) {
        if (auth()->user()->canPerformInModule('can_edit', 'sales_orders')) {
            return app(SalesOrderController::class)->update(request(), $id);
        }
        return view('errors.noaccess');
    })->name('update');

    // Update Status
    Route::patch('/{id}/update-status', function ($id) {
        if (auth()->user()->canApproveSalesOrders()) {
            return app(SalesOrderController::class)->updateStatus(request(), $id);
        }
        return view('errors.noaccess');
    })->name('updateStatus');

    // Mark Delivered
    Route::patch('/{id}/mark-delivered', function ($id) {
        if (auth()->user()->canPerformInModule('can_manage', 'sales_orders')) {
            return app(SalesOrderController::class)->markDelivered($id);
        }
        return view('errors.noaccess');
    })->name('markDelivered');

    // Delete
    Route::delete('/{id}', function ($id) {
        if (auth()->user()->canPerformInModule('can_delete', 'sales_orders')) {
            return app(SalesOrderController::class)->destroy($id);
        }
        return view('errors.noaccess');
    })->name('destroy');

    // =================== GENERIC ROUTES (MUST BE LAST) ===================

    // Index
    Route::get('/', function () {
        if (auth()->user()->canManageSalesOrders()) {
            return app(SalesOrderController::class)->index(request());
        }
        return view('errors.noaccess');
    })->name('index');

    // Show (MUST BE LAST - catches all remaining /{id} routes)
    Route::get('/{id}', function ($id) {
        if (auth()->user()->canManageSalesOrders()) {
            return app(SalesOrderController::class)->show($id);
        }
        return view('errors.noaccess');
    })->name('show');
});

    // ===================== ITEMS =====================
Route::prefix('items')->name('items.')->group(function () {

    // ✅ FIXED: Remove the duplicate '/items' prefix - just use '/export'
    Route::get('/export', [ItemController::class, 'export'])->name('export');

    // ✅ BULK ACTIONS - Place at the top before parameterized routes
    Route::post('/bulk-approve', [ItemController::class, 'bulkApprove'])->name('bulk-approve');
    Route::post('/bulk-reject', [ItemController::class, 'bulkReject'])->name('bulk-reject');
    
    // Item Approval Routes (must be before {id} routes)
    Route::get('/pending', [ItemController::class, 'pending'])->name('pending');
    Route::post('/{id}/approve', [ItemController::class, 'approve'])->name('approve');
    Route::post('/{id}/reject', [ItemController::class, 'reject'])->name('reject');
    
    // TOGGLE ENABLE/DISABLE
    Route::post('/{item}/toggle', [ItemController::class, 'toggleStatus'])->name('toggle');
    
    // ✅ Index
    Route::get('/', function () {
        if (auth()->user()->canManageItems()) {
            return app(ItemController::class)->index();
        }
        return view('errors.noaccess');
    })->name('index');

    // ✅ Create
    Route::get('/create', function () {
        if (auth()->user()->canAddItems()) {
            return app(ItemController::class)->create();
        }
        return view('errors.noaccess');
    })->name('create');

    // ✅ Store
    Route::post('/', function () {
        if (auth()->user()->canAddItems()) {
            return app(ItemController::class)->store(request());
        }
        return view('errors.noaccess');
    })->name('store');

    // ✅ Edit
    Route::get('/{id}/edit', function ($id) {
        if (auth()->user()->canEditItems()) {
            return app(ItemController::class)->edit($id);
        }
        return view('errors.noaccess');
    })->name('edit');

    // ✅ Update
    Route::put('/{id}', function ($id) {
        if (auth()->user()->canEditItems()) {
            return app(ItemController::class)->update(request(), $id);
        }
        return view('errors.noaccess');
    })->name('update');

    // ✅ Delete
    Route::delete('/{id}', function ($id) {
        if (auth()->user()->canDeleteItems()) {
            return app(ItemController::class)->destroy($id);
        }
        return view('errors.noaccess');
    })->name('destroy');

    // ✅ Show - Must be LAST to avoid conflicts
    Route::get('/{id}', function ($id) {
        if (auth()->user()->canManageItems()) {
            return app(ItemController::class)->show($id);
        }
        return view('errors.noaccess');
    })->name('show');
});

    // ===================== CUSTOMERS =====================
    Route::prefix('customers')->name('customers.')->group(function () {

        // ✅ Index
        Route::get('/', function () {
            if (auth()->user()->canManageCustomers()) {
                return app(CustomerController::class)->index();
            }
            return view('errors.noaccess');
        })->name('index');

        // ✅ Export
        Route::get('/export', function () {
            if (auth()->user()->canPerformInModule('can_export', 'customers')) {
                return app(CustomerController::class)->export();
            }
            return view('errors.noaccess');
        })->name('export');

        // ✅ Next Code (for auto-generating customer code)
        Route::get('/next-code', function () {
            if (auth()->user()->canAddCustomers()) {
                return app(CustomerController::class)->getNextCode();
            }
            return response()->json(['error' => 'Access denied'], 403);
        })->name('nextCode');

        // ✅ Create
        Route::get('/create', function () {
            if (auth()->user()->canAddCustomers()) {
                return app(CustomerController::class)->create();
            }
            return view('errors.noaccess');
        })->name('create');

        // ✅ Store
        Route::post('/', function () {
            if (auth()->user()->canAddCustomers()) {
                return app(CustomerController::class)->store(request());
            }
            return view('errors.noaccess');
        })->name('store');

        // ✅ Get customer by code (for AJAX autofill)
        Route::get('/get/{code}', function ($code) {
            if (auth()->user()->canManageCustomers() || auth()->user()->canManageSalesOrders()) {
                return app(CustomerController::class)->getByCode($code);
            }
            return response()->json(['error' => 'Access denied'], 403);
        })->name('getByCode');

        // ✅ Edit (MUST come before generic /{id})
        Route::get('/{id}/edit', [CustomerController::class, 'edit'])->where('id', '[0-9]+')->name('edit');

        // ✅ Toggle Status (MUST come before generic /{id})
        Route::patch('/{id}/toggle-status', function ($id) {
            if (auth()->user()->canEditCustomers()) {
                return app(CustomerController::class)->toggleStatus($id);
            }
            return view('errors.noaccess');
        })->where('id', '[0-9]+')->name('toggleStatus');

        // ✅ Toggle Flag (MUST come before generic /{id})
        Route::patch('/{id}/toggle-flag', function ($id) {
            if (auth()->user()->canPerformInModule('can_manage', 'customers')) {
                return app(CustomerController::class)->toggleFlag($id);
            }
            return view('errors.noaccess');
        })->where('id', '[0-9]+')->name('toggleFlag');

        // ✅ Update
        Route::put('/{id}', function ($id) {
            if (auth()->user()->canEditCustomers()) {
                return app(CustomerController::class)->update(request(), $id);
            }
            return view('errors.noaccess');
        })->where('id', '[0-9]+')->name('update');

        // ✅ Delete
        Route::delete('/{id}', function ($id) {
            if (auth()->user()->canDeleteCustomers()) {
                return app(CustomerController::class)->destroy($id);
            }
            return view('errors.noaccess');
        })->where('id', '[0-9]+')->name('destroy');

        // ✅ Show (MUST BE LAST because it catches any GET /{id})
        // Use direct controller method for better route matching
        Route::get('/{id}', [CustomerController::class, 'show'])->where('id', '[0-9]+')->name('show');
    });

    // ===================== SUPPLIERS =====================
    Route::prefix('suppliers')->name('suppliers.')->group(function () {

        // Index
        Route::get('/', function () {
            $user = auth()->user();
            if ($user->canManageSuppliers()) {
                return app(SuppliersController::class)->index();
            }
            return view('errors.noaccess');
        })->name('index');

        // Export
        Route::get('/export', function () {
            $user = auth()->user();
            if ($user->canManageSuppliers()) {
                return app(SuppliersController::class)->export();
            }
            return view('errors.noaccess');
        })->name('export');

        // Create
        Route::get('/create', function () {
            $user = auth()->user();
            if ($user->canManageSuppliers()) {
                return app(SuppliersController::class)->create();
            }
            return view('errors.noaccess');
        })->name('create');

        // Store
        Route::post('/', function () {
            $user = auth()->user();
            if ($user->canManageSuppliers()) {
                return app(SuppliersController::class)->store(request());
            }
            return view('errors.noaccess');
        })->name('store');

        // Edit
        Route::get('/{id}/edit', function ($id) {
            $user = auth()->user();
            if ($user->canManageSuppliers()) {
                return app(SuppliersController::class)->edit($id);
            }
            return view('errors.noaccess');
        })->name('edit');

        // Update
        Route::put('/{id}', function ($id) {
            $user = auth()->user();
            if ($user->canManageSuppliers()) {
                return app(SuppliersController::class)->update(request(), $id);
            }
            return view('errors.noaccess');
        })->name('update');

        // Toggle Status
        Route::patch('/{id}/toggle-status', function ($id) {
            $user = auth()->user();
            if ($user->canManageSuppliers()) {
                return app(SuppliersController::class)->toggleStatus($id);
            }
            return view('errors.noaccess');
        })->name('toggleStatus');

        // Delete
        Route::delete('/{id}', function ($id) {
            $user = auth()->user();
            if ($user->canDeleteSuppliers()) {
                return app(SuppliersController::class)->destroy($id);
            }
            return view('errors.noaccess');
        })->name('destroy');

        // Upload Documents
        Route::post('/{id}/documents', function ($id) {
            $user = auth()->user();
            if ($user->canManageSuppliers()) {
                return app(SuppliersController::class)->uploadDocuments(request(), $id);
            }
            return view('errors.noaccess');
        })->name('uploadDocuments');

        // Download Document
        Route::get('/{id}/documents/{documentId}/download', function ($id, $documentId) {
            $user = auth()->user();
            if ($user->canManageSuppliers()) {
                return app(SuppliersController::class)->downloadDocument($id, $documentId);
            }
            return view('errors.noaccess');
        })->name('downloadDocument');

        // View Document
        Route::get('/{id}/documents/{documentId}/view', function ($id, $documentId) {
            $user = auth()->user();
            if ($user->canManageSuppliers()) {
                return app(SuppliersController::class)->viewDocument($id, $documentId);
            }
            return view('errors.noaccess');
        })->name('viewDocument');

        // Delete Document
        Route::delete('/{id}/documents/{documentId}', function ($id, $documentId) {
            $user = auth()->user();
            if ($user->canManageSuppliers()) {
                return app(SuppliersController::class)->deleteDocument($id, $documentId);
            }
            return view('errors.noaccess');
        })->name('deleteDocument');

        // Show (must be last)
        Route::get('/{id}', function ($id) {
            $user = auth()->user();
            if ($user->canManageSuppliers()) {
                return app(SuppliersController::class)->show($id);
            }
            return view('errors.noaccess');
        })->name('show');
    });

    // ===================== SUPPLIER RECEIVING REPORTS =====================
    Route::prefix('supplier-receiving-reports')->name('supplier_receiving_reports.')->group(function () {

        // Index
        Route::get('/', function () {
            if (auth()->user()->canManageSupplierReceivingReports()) {
                return app(SupplierReceivingReportController::class)->index(request());
            }
            return view('errors.noaccess');
        })->name('index');

        // Export Excel
        Route::get('/export-excel', function () {
            if (auth()->user()->canManageSupplierReceivingReports()) {
                return app(SupplierReceivingReportController::class)->exportExcel(request());
            }
            return view('errors.noaccess');
        })->name('exportExcel');

        // Search Purchase Orders (AJAX)
        Route::get('/search-purchase-orders', function () {
            if (auth()->user()->canManageSupplierReceivingReports()) {
                return app(SupplierReceivingReportController::class)->searchPurchaseOrders(request());
            }
            return response()->json([]);
        })->name('searchPurchaseOrders');

        // Create
        Route::get('/create', function () {
            if (auth()->user()->canManageSupplierReceivingReports()) {
                return app(SupplierReceivingReportController::class)->create();
            }
            return view('errors.noaccess');
        })->name('create');

        // Store
        Route::post('/', function () {
            if (auth()->user()->canManageSupplierReceivingReports()) {
                return app(SupplierReceivingReportController::class)->store(request());
            }
            return view('errors.noaccess');
        })->name('store');

        // Print
        Route::get('/{id}/print', function ($id) {
            if (auth()->user()->canManageSupplierReceivingReports()) {
                return app(SupplierReceivingReportController::class)->print($id);
            }
            return view('errors.noaccess');
        })->name('print');

        // Approve
        Route::post('/{id}/approve', function ($id) {
            if (auth()->user()->canApproveSupplierReceivingReports()) {
                return app(SupplierReceivingReportController::class)->approve($id);
            }
            return view('errors.noaccess');
        })->name('approve');

        // Reject
        Route::post('/{id}/reject', function ($id) {
            if (auth()->user()->canApproveSupplierReceivingReports()) {
                return app(SupplierReceivingReportController::class)->reject(request(), $id);
            }
            return view('errors.noaccess');
        })->name('reject');

        // Edit
        Route::get('/{id}/edit', function ($id) {
            if (auth()->user()->canManageSupplierReceivingReports()) {
                return app(SupplierReceivingReportController::class)->edit($id);
            }
            return view('errors.noaccess');
        })->name('edit');

        // Update
        Route::put('/{id}', function ($id) {
            if (auth()->user()->canManageSupplierReceivingReports()) {
                return app(SupplierReceivingReportController::class)->update(request(), $id);
            }
            return view('errors.noaccess');
        })->name('update');

        // Delete
        Route::delete('/{id}', function ($id) {
            if (auth()->user()->canManageSupplierReceivingReports()) {
                return app(SupplierReceivingReportController::class)->destroy($id);
            }
            return view('errors.noaccess');
        })->name('destroy');

        // Show (must be last)
        Route::get('/{id}', function ($id) {
            if (auth()->user()->canManageSupplierReceivingReports()) {
                return app(SupplierReceivingReportController::class)->show($id);
            }
            return view('errors.noaccess');
        })->name('show');
    });

    // ===================== ISSUE SLIPS =====================
    Route::prefix('issue-slips')->name('issue_slips.')->group(function () {

        Route::get('/', function () {
            if (auth()->user()->canManageIssueSlips()) {
                return app(\App\Http\Controllers\IssueSlipController::class)->index(request());
            }
            return view('errors.noaccess');
        })->name('index');

        Route::get('/create', function () {
            if (auth()->user()->canManageIssueSlips()) {
                return app(\App\Http\Controllers\IssueSlipController::class)->create();
            }
            return view('errors.noaccess');
        })->name('create');

        Route::post('/', function () {
            if (auth()->user()->canManageIssueSlips()) {
                return app(\App\Http\Controllers\IssueSlipController::class)->store(request());
            }
            return view('errors.noaccess');
        })->name('store');

        // Search Sales Orders (AJAX)
        Route::get('/search-sales-orders', function () {
            if (auth()->user()->canManageIssueSlips()) {
                return app(\App\Http\Controllers\IssueSlipController::class)->searchSalesOrders(request());
            }
            return response()->json([]);
        })->name('search_sales_orders');

        // Search Customers (AJAX for destination)
        Route::get('/search-customers', function () {
            if (auth()->user()->canManageIssueSlips()) {
                return app(\App\Http\Controllers\IssueSlipController::class)->searchCustomers(request());
            }
            return response()->json([]);
        })->name('search_customers');

        // Get SO items (AJAX)
        Route::get('/sales-order-items/{soId}', function ($soId) {
            if (auth()->user()->canManageIssueSlips()) {
                return app(\App\Http\Controllers\IssueSlipController::class)->getSalesOrderItems($soId);
            }
            return response()->json([]);
        })->name('get_so_items');

        Route::get('/{id}/print', function ($id) {
            if (auth()->user()->canManageIssueSlips()) {
                return app(\App\Http\Controllers\IssueSlipController::class)->print($id);
            }
            return view('errors.noaccess');
        })->name('print');

        Route::get('/{id}/edit', function ($id) {
            if (auth()->user()->canManageIssueSlips()) {
                return app(\App\Http\Controllers\IssueSlipController::class)->edit($id);
            }
            return view('errors.noaccess');
        })->name('edit');

        Route::put('/{id}', function ($id) {
            if (auth()->user()->canManageIssueSlips()) {
                return app(\App\Http\Controllers\IssueSlipController::class)->update(request(), $id);
            }
            return view('errors.noaccess');
        })->name('update');

        Route::delete('/{id}', function ($id) {
            if (auth()->user()->canManageIssueSlips()) {
                return app(\App\Http\Controllers\IssueSlipController::class)->destroy($id);
            }
            return view('errors.noaccess');
        })->name('destroy');

        // Show (must be last)
        Route::get('/{id}', function ($id) {
            if (auth()->user()->canManageIssueSlips()) {
                return app(\App\Http\Controllers\IssueSlipController::class)->show($id);
            }
            return view('errors.noaccess');
        })->name('show');
    });

    // ===================== DELIVERIES =====================
    Route::prefix('deliveries')->name('deliveries.')->group(function () {
        
        // PRINT ROUTES (must be first to avoid conflicts)
        Route::get('/print-list', function() {
            if (auth()->user()->canManageDeliveries()) {
                return app(DeliveriesController::class)->printList(request());
            }
            return view('errors.noaccess');
        })->name('printList');

        // EXCEL EXPORT (LIST)
        Route::get('/export-excel', function() {
            if (auth()->user()->canPerformInModule('can_export', 'deliveries')) {
                return app(DeliveriesController::class)->exportExcel(request());
            }
            return view('errors.noaccess');
        })->name('exportExcel');

        // EXCEL EXPORT (SINGLE DELIVERY WITH ITEMS)
        Route::get('/export-items', function() {
            if (auth()->user()->canPerformInModule('can_export', 'deliveries')) {
                return app(DeliveriesController::class)->exportDeliveryItemsExcel(request());
            }
            return view('errors.noaccess');
        })->name('exportDeliveryItemsExcel');

        // DELIVERIES LIST PAGE
        Route::get('/list', function() {
            if (auth()->user()->canManageDeliveries()) {
                return app(DeliveriesController::class)->deliveriesList(request());
            }
            return view('errors.noaccess');
        })->name('deliveries');

        // SEARCH
        Route::get('/search', [DeliveriesController::class, 'search'])->name('search');

        // CREATE
        Route::get('/create', function() {
            if (auth()->user()->canCreateDeliveries()) {
                return app(DeliveriesController::class)->create();
            }
            return view('errors.noaccess');
        })->name('create');

        // STORE
        Route::post('/store', function() {
            if (auth()->user()->canCreateDeliveries()) {
                return app(DeliveriesController::class)->store(request());
            }
            return view('errors.noaccess');
        })->name('store');

        // INDEX (main list page with date filter)
        Route::get('/', function() {
            if (auth()->user()->canManageDeliveries()) {
                return app(DeliveriesController::class)->index(request());
            }
            return view('errors.noaccess');
        })->name('index');

        // SINGLE DELIVERY PRINT
        Route::get('/{id}/print', function($id) {
            if (auth()->user()->canManageDeliveries()) {
                return app(DeliveriesController::class)->print($id);
            }
            return view('errors.noaccess');
        })->name('print');

        // EDIT
        Route::get('/{id}/edit', function($id) {
            if (auth()->user()->canPerformInModule('can_edit', 'deliveries')) {
                return app(DeliveriesController::class)->edit($id);
            }
            return view('errors.noaccess');
        })->name('edit');

        // UPDATE
        Route::put('/{id}', function($id) {
            if (auth()->user()->canPerformInModule('can_edit', 'deliveries')) {
                return app(DeliveriesController::class)->update(request(), $id);
            }
            return view('errors.noaccess');
        })->name('update');

        Route::post('/{id}/reject-edit', function($id) {
            if (auth()->user()->canApproveDeliveries()) {
                return app(DeliveriesController::class)->rejectEdit(request(), $id);
            }
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        })->name('rejectEdit');

        // Get delivery data for editing
        Route::get('/{id}/edit-data', function($id) {
            if (auth()->user()->canPerformInModule('can_edit', 'deliveries')) {
                return app(DeliveriesController::class)->getEditData($id);
            }
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        })->name('getEditData');

        // Quick update delivery
        Route::post('/{id}/quick-update', function($id) {
            if (auth()->user()->canPerformInModule('can_edit', 'deliveries')) {
                return app(DeliveriesController::class)->quickUpdate(request(), $id);
            }
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        })->name('quickUpdate');

        // Delivery approval routes
        Route::post('/{id}/approve', function($id) {
            if (auth()->user()->canApproveDeliveries()) {
                return app(DeliveriesController::class)->approve($id);
            }
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        })->name('approve');

        Route::post('/{id}/reject', function($id) {
            if (auth()->user()->canApproveDeliveries()) {
                return app(DeliveriesController::class)->reject(request(), $id);
            }
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        })->name('reject');

        Route::post('/batch-approve', [DeliveriesController::class, 'batchApprove'])->name('batch-approve');
        Route::post('/batch-reject', [DeliveriesController::class, 'batchReject'])->name('batch-reject');

        // Repair routes
        Route::post('/repair-duplicate-items', [DeliveriesController::class, 'repairDuplicateItemCodes'])->name('repair-duplicate-items');
        Route::post('/recalculate-so-deliveries', [DeliveriesController::class, 'recalculateSODeliveries'])->name('recalculate-so-deliveries');

        // Request Edit
        Route::post('/{id}/request-edit', function($id) {
            if (auth()->user()->canPerformInModule('can_edit', 'deliveries')) {
                return app(DeliveriesController::class)->requestEdit($id);
            }
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        })->name('requestEdit');

        Route::post('/{id}/approve-edit', function($id) {
            if (auth()->user()->canApproveDeliveries()) {
                return app(DeliveriesController::class)->approveEdit($id);
            }
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        })->name('approveEdit');

        Route::post('/{id}/pullout', function($id) {
            if (auth()->user()->canApproveDeliveries()) {
                return app(DeliveriesController::class)->pullout(request(), $id);
            }
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        })->name('pullout');

        // Recalculate totals (Admin/IT only)
        Route::post('/recalculate-totals', function() {
            if (auth()->user()->canPerformInModule('can_manage', 'deliveries')) {
                return app(DeliveriesController::class)->recalculateAllTotals(request());
            }
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        })->name('recalculateTotals');

        // HIDE / UNHIDE DR (Admin/IT only)
        Route::post('/{id}/hide', function($id) {
            if (!auth()->user()->isAdminUser()) { abort(403); }
            return app(DeliveriesController::class)->hide(request(), $id);
        })->name('hide');
        Route::post('/{id}/unhide', function($id) {
            if (!auth()->user()->isAdminUser()) { abort(403); }
            return app(DeliveriesController::class)->unhide($id);
        })->name('unhide');

        // SHOW (must be last because it catches any /{id})
        Route::get('/{id}', function($id) {
            if (auth()->user()->canManageDeliveries()) {
                return app(DeliveriesController::class)->show($id);
            }
            return view('errors.noaccess');
        })->name('show');
    });

    // ===================== PURCHASE REQUESTS =====================
    Route::prefix('purchase_requests')->name('purchase_requests.')->group(function () {

        // Search Suppliers (AJAX) for per-item supplier — uses controller method with description filtering
        Route::get('/search-suppliers', function () {
            $user = auth()->user();
            if ($user->canManagePurchaseRequests()) {
                return app(PurchaseRequestController::class)->searchSuppliers(request());
            }
            return response()->json([]);
        })->name('search_suppliers');

        // Search Items (AJAX) — combined Trade + Non-Trade item search
        Route::get('/search-items', function () {
            $user = auth()->user();
            if ($user->canManagePurchaseRequests()) {
                return app(PurchaseRequestController::class)->searchItems(request());
            }
            return response()->json([]);
        })->name('search_items');

        // Go to PO (smart redirect: existing PO view or PO creation)
        Route::get('/{id}/go-to-po', function ($id) {
            $user = auth()->user();
            if ($user->canManagePurchaseRequests()) {
                return app(PurchaseRequestController::class)->goToPO($id);
            }
            return view('errors.noaccess');
        })->name('go_to_po');

        // Index
        Route::get('/', function () {
            $user = auth()->user();
            if ($user->canManagePurchaseRequests()) {
                return app(PurchaseRequestController::class)->index();
            }
            return view('errors.noaccess');
        })->name('index');

        // Create
        Route::get('/create', function () {
            $user = auth()->user();
            if ($user->canCreatePurchaseRequests()) {
                return app(PurchaseRequestController::class)->create();
            }
            return view('errors.noaccess');
        })->name('create');

        // Store
        Route::post('/', function () {
            $user = auth()->user();
            if ($user->canCreatePurchaseRequests()) {
                return app(PurchaseRequestController::class)->store(request());
            }
            return view('errors.noaccess');
        })->name('store');

        // Approve as Department Head
        Route::post('/{id}/approve-dh', function ($id) {
            $user = auth()->user();
            if ($user->canApprovePurchaseRequestsAsDH()) {
                return app(PurchaseRequestController::class)->approveDH(request(), $id);
            }
            return view('errors.noaccess');
        })->name('approve_dh');

        // Approve as Management
        Route::post('/{id}/approve-management', function ($id) {
            $user = auth()->user();
            if ($user->canApprovePurchaseRequestsAsManagement()) {
                return app(PurchaseRequestController::class)->approveManagement(request(), $id);
            }
            return view('errors.noaccess');
        })->name('approve_management');

        // Approve as Executive
        Route::post('/{id}/approve', function ($id) {
            $user = auth()->user();
            if ($user->canApprovePurchaseRequestsAsExecutive()) {
                return app(PurchaseRequestController::class)->approve(request(), $id);
            }
            return view('errors.noaccess');
        })->name('approve');

        // Reject
        Route::post('/{id}/reject', function ($id) {
            $user = auth()->user();
            if ($user->canApprovePurchaseRequests()) {
                return app(PurchaseRequestController::class)->reject(request(), $id);
            }
            return view('errors.noaccess');
        })->name('reject');

        // Edit
        Route::get('/{id}/edit', function ($id) {
            $user = auth()->user();
            if ($user->canManagePurchaseRequests()) {
                return app(PurchaseRequestController::class)->edit($id);
            }
            return view('errors.noaccess');
        })->name('edit');

        // Update
        Route::put('/{id}', function ($id) {
            $user = auth()->user();
            if ($user->canManagePurchaseRequests()) {
                return app(PurchaseRequestController::class)->update(request(), $id);
            }
            return view('errors.noaccess');
        })->name('update');

        // Update Notes (for approved PRs)
        Route::put('/{id}/update-notes', function ($id) {
            $user = auth()->user();
            if ($user->canManagePurchaseRequests()) {
                return app(PurchaseRequestController::class)->updateNotes(request(), $id);
            }
            return view('errors.noaccess');
        })->name('update_notes');

        // Delete
        Route::delete('/{id}', function ($id) {
            $user = auth()->user();
            if ($user->canManagePurchaseRequests()) {
                return app(PurchaseRequestController::class)->destroy($id);
            }
            return view('errors.noaccess');
        })->name('destroy');

        // Bulk Approve
        Route::post('/bulk-approve', function () {
            $user = auth()->user();
            if ($user->canApprovePurchaseRequests()) {
                return app(PurchaseRequestController::class)->bulkApprove(request());
            }
            return view('errors.noaccess');
        })->name('bulk_approve');

        // Print
        Route::get('/{id}/print', function ($id) {
            $user = auth()->user();
            if ($user->canManagePurchaseRequests()) {
                return app(PurchaseRequestController::class)->print($id);
            }
            return view('errors.noaccess');
        })->name('print');

        // Show (must be last)
        Route::get('/{id}', function ($id) {
            $user = auth()->user();
            if ($user->canManagePurchaseRequests()) {
                return app(PurchaseRequestController::class)->show($id);
            }
            return view('errors.noaccess');
        })->name('show');
    });

    // ===================== PURCHASE ORDERS =====================
    Route::prefix('purchase_orders')->name('purchase_orders.')->group(function () {

        // Search PRs (AJAX)
        Route::get('/search-prs', function () {
            $user = auth()->user();
            if ($user->canManagePurchaseOrders()) {
                return app(PurchaseOrderController::class)->searchPRs(request());
            }
            return response()->json([]);
        })->name('search_prs');

            // ===================== GENERATE ITEM CODE (Global) =====================
    Route::get('/generate-item-code', function () {
        $user = auth()->user();
        // Allow both PO and PR users to generate item codes
        if ($user->canManagePurchaseOrders() || $user->canManagePurchaseRequests()) {
            return app(\App\Http\Controllers\PurchaseOrderController::class)->generateItemCode(request());
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    })->name('generate_item_code');

    // ===================== SEARCH BY ITEM CODE (Global) =====================
    Route::get('/search-by-item-code', function () {
        $user = auth()->user();
        if ($user->canManagePurchaseOrders() || $user->canManagePurchaseRequests()) {
            return app(\App\Http\Controllers\PurchaseOrderController::class)->searchByItemCode(request());
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    })->name('search_by_item_code');

        // Search Suppliers (AJAX) — uses controller method with description filtering
        Route::get('/search-suppliers', function () {
            $user = auth()->user();
            if ($user->canManagePurchaseOrders()) {
                return app(PurchaseOrderController::class)->searchSuppliers(request());
            }
            return response()->json([]);
        })->name('search_suppliers');

        // Search Items (AJAX) — combined Trade + Non-Trade item search
        Route::get('/search-items', function () {
            $user = auth()->user();
            if ($user->canManagePurchaseOrders() || $user->canManagePurchaseRequests()) {
                return app(PurchaseRequestController::class)->searchItems(request());
            }
            return response()->json([]);
        })->name('search_items');

        // Get PR Details (AJAX) - Allow reuse across multiple POs
        Route::get('/get-pr-details', function () {
            $user = auth()->user();
            if ($user->canManagePurchaseOrders()) {
                return app(PurchaseOrderController::class)->getPRDetails(request());
            }
            return response()->json(['error' => 'Unauthorized'], 403);
        })->name('get_pr_details');

        // Index
        Route::get('/', function () {
            $user = auth()->user();
            if ($user->canManagePurchaseOrders()) {
                return app(PurchaseOrderController::class)->index();
            }
            return view('errors.noaccess');
        })->name('index');

        // Create
        Route::get('/create', function () {
            $user = auth()->user();
            if ($user->canCreatePurchaseOrders()) {
                return app(PurchaseOrderController::class)->create(request());
            }
            return view('errors.noaccess');
        })->name('create');

        // Store
        Route::post('/', function () {
            $user = auth()->user();
            if ($user->canCreatePurchaseOrders()) {
                return app(PurchaseOrderController::class)->store(request());
            }
            return view('errors.noaccess');
        })->name('store');

        // Approve as Department Head
        Route::post('/{id}/approve-dh', function ($id) {
            $user = auth()->user();
            if ($user->canApprovePurchaseOrdersAsDH()) {
                return app(PurchaseOrderController::class)->approveDH(request(), $id);
            }
            return view('errors.noaccess');
        })->name('approve_dh');

        // Approve as Management
        Route::post('/{id}/approve-management', function ($id) {
            $user = auth()->user();
            if ($user->canApprovePurchaseOrdersAsManagement()) {
                return app(PurchaseOrderController::class)->approveManagement(request(), $id);
            }
            return view('errors.noaccess');
        })->name('approve_management');

        // Approve as Executive
        Route::post('/{id}/approve', function ($id) {
            $user = auth()->user();
            if ($user->canApprovePurchaseOrdersAsExecutive()) {
                return app(PurchaseOrderController::class)->approve(request(), $id);
            }
            return view('errors.noaccess');
        })->name('approve');

        // Reject
        Route::post('/{id}/reject', function ($id) {
            $user = auth()->user();
            if ($user->canApprovePurchaseOrders()) {
                return app(PurchaseOrderController::class)->reject(request(), $id);
            }
            return view('errors.noaccess');
        })->name('reject');

        // Edit
        Route::get('/{id}/edit', function ($id) {
            $user = auth()->user();
            if ($user->canManagePurchaseOrders()) {
                return app(PurchaseOrderController::class)->edit($id);
            }
            return view('errors.noaccess');
        })->name('edit');

        // Update
        Route::put('/{id}', function ($id) {
            $user = auth()->user();
            if ($user->canManagePurchaseOrders()) {
                return app(PurchaseOrderController::class)->update(request(), $id);
            }
            return view('errors.noaccess');
        })->name('update');

        // Delete
        Route::delete('/{id}', function ($id) {
            $user = auth()->user();
            if ($user->canApprovePurchaseOrders()) {
                return app(PurchaseOrderController::class)->destroy($id);
            }
            return view('errors.noaccess');
        })->name('destroy');

        // Bulk Approve
        Route::post('/bulk-approve', function () {
            $user = auth()->user();
            if ($user->canApprovePurchaseOrders()) {
                return app(PurchaseOrderController::class)->bulkApprove(request());
            }
            return view('errors.noaccess');
        })->name('bulk_approve');

        // Print
        Route::get('/{id}/print', function ($id) {
            $user = auth()->user();
            if ($user->canManagePurchaseOrders()) {
                return app(PurchaseOrderController::class)->print($id);
            }
            return view('errors.noaccess');
        })->name('print');

        // Update Notes (works on approved POs too)
        Route::put('/{id}/update-notes', function ($id) {
            $user = auth()->user();
            if ($user->canManagePurchaseOrders()) {
                return app(PurchaseOrderController::class)->updateNotes(request(), $id);
            }
            return view('errors.noaccess');
        })->name('update_notes');

        // Show (must be last)
        Route::get('/{id}', function ($id) {
            $user = auth()->user();
            if ($user->canManagePurchaseOrders()) {
                return app(PurchaseOrderController::class)->show($id);
            }
            return view('errors.noaccess');
        })->name('show');
    });

    // ===================== NON-TRADE ITEMS LIBRARY =====================
    Route::prefix('non-trade-items')->name('non_trade_items.')->middleware('auth')->group(function () {
        Route::get('/', function () {
            $user = auth()->user();
            if ($user->canAccessModule('non_trade_items')) {
                return app(NonTradeItemController::class)->index(request());
            }
            return view('errors.noaccess');
        })->name('index');

        Route::get('/search', function () {
            return app(NonTradeItemController::class)->search(request());
        })->name('search');

        Route::post('/store', function () {
            $user = auth()->user();
            if ($user->canAccessModule('non_trade_items')) {
                return app(NonTradeItemController::class)->store(request());
            }
            return view('errors.noaccess');
        })->name('store');

        Route::post('/import', function () {
            $user = auth()->user();
            if ($user->canAccessModule('non_trade_items')) {
                return app(NonTradeItemController::class)->import(request());
            }
            return view('errors.noaccess');
        })->name('import');

        Route::put('/{id}', function ($id) {
            $user = auth()->user();
            if ($user->canAccessModule('non_trade_items')) {
                return app(NonTradeItemController::class)->update(request(), $id);
            }
            return view('errors.noaccess');
        })->name('update');

        Route::delete('/{id}', function ($id) {
            $user = auth()->user();
            if ($user->canAccessModule('non_trade_items')) {
                return app(NonTradeItemController::class)->destroy($id);
            }
            return view('errors.noaccess');
        })->name('destroy');
    });

    // ===================== TRADE ITEMS LIBRARY =====================
    Route::prefix('trade-items')->name('trade_items.')->middleware('auth')->group(function () {
        Route::get('/', function () {
            $user = auth()->user();
            if ($user->canAccessModule('trade_items')) {
                return app(TradeItemController::class)->index(request());
            }
            return view('errors.noaccess');
        })->name('index');

        Route::get('/search', function () {
            return app(TradeItemController::class)->search(request());
        })->name('search');

        Route::post('/store', function () {
            $user = auth()->user();
            if ($user->canAccessModule('trade_items')) {
                return app(TradeItemController::class)->store(request());
            }
            return view('errors.noaccess');
        })->name('store');

        Route::post('/import', function () {
            $user = auth()->user();
            if ($user->canAccessModule('trade_items')) {
                return app(TradeItemController::class)->import(request());
            }
            return view('errors.noaccess');
        })->name('import');

        Route::put('/{id}', function ($id) {
            $user = auth()->user();
            if ($user->canAccessModule('trade_items')) {
                return app(TradeItemController::class)->update(request(), $id);
            }
            return view('errors.noaccess');
        })->name('update');

        Route::delete('/{id}', function ($id) {
            $user = auth()->user();
            if ($user->canAccessModule('trade_items')) {
                return app(TradeItemController::class)->destroy($id);
            }
            return view('errors.noaccess');
        })->name('destroy');
    });

    // ===================== REQUEST FOR PAYMENTS =====================
    Route::prefix('request_for_payments')->name('request_for_payments.')->group(function () {

        // Search POs (AJAX)
        Route::get('/search-pos', function () {
            $user = auth()->user();
            if ($user->canManageRequestForPayments()) {
                return app(RequestForPaymentController::class)->searchPOs(request());
            }
            return response()->json([]);
        })->name('search_pos');

        // Index
        Route::get('/', function () {
            $user = auth()->user();
            if ($user->canManageRequestForPayments()) {
                return app(RequestForPaymentController::class)->index();
            }
            return view('errors.noaccess');
        })->name('index');

        // Create
        Route::get('/create', function () {
            $user = auth()->user();
            if ($user->canCreateRequestForPayments()) {
                return app(RequestForPaymentController::class)->create(request());
            }
            return view('errors.noaccess');
        })->name('create');

        // Store
        Route::post('/', function () {
            $user = auth()->user();
            if ($user->canCreateRequestForPayments()) {
                return app(RequestForPaymentController::class)->store(request());
            }
            return view('errors.noaccess');
        })->name('store');

        // Approve DH (Level 1)
        Route::post('/{id}/approve-dh', function ($id) {
            $user = auth()->user();
            if ($user->canApproveRFPAsDH()) {
                return app(RequestForPaymentController::class)->approveDH(request(), $id);
            }
            return view('errors.noaccess');
        })->name('approve_dh');

        // Approve Accounting (Level 2)
        Route::post('/{id}/approve-accounting', function ($id) {
            $user = auth()->user();
            if ($user->canApproveRFPAsAccounting()) {
                return app(RequestForPaymentController::class)->approveAccounting(request(), $id);
            }
            return view('errors.noaccess');
        })->name('approve_accounting');

        // Approve Executive (Level 3 - Final)
        Route::post('/{id}/approve', function ($id) {
            $user = auth()->user();
            if ($user->canApproveRFPAsExecutive()) {
                return app(RequestForPaymentController::class)->approve(request(), $id);
            }
            return view('errors.noaccess');
        })->name('approve');

        // Reject
        Route::post('/{id}/reject', function ($id) {
            $user = auth()->user();
            if ($user->canApproveRequestForPayments()) {
                return app(RequestForPaymentController::class)->reject(request(), $id);
            }
            return view('errors.noaccess');
        })->name('reject');

        // Edit
        Route::get('/{id}/edit', function ($id) {
            $user = auth()->user();
            if ($user->canManageRequestForPayments()) {
                return app(RequestForPaymentController::class)->edit($id);
            }
            return view('errors.noaccess');
        })->name('edit');

        // Update
        Route::put('/{id}', function ($id) {
            $user = auth()->user();
            if ($user->canManageRequestForPayments()) {
                return app(RequestForPaymentController::class)->update(request(), $id);
            }
            return view('errors.noaccess');
        })->name('update');

        // Delete
        Route::delete('/{id}', function ($id) {
            $user = auth()->user();
            if ($user->canApproveRequestForPayments()) {
                return app(RequestForPaymentController::class)->destroy($id);
            }
            return view('errors.noaccess');
        })->name('destroy');

        // Print
        Route::get('/{id}/print', function ($id) {
            $user = auth()->user();
            if ($user->canManageRequestForPayments()) {
                return app(RequestForPaymentController::class)->print($id);
            }
            return view('errors.noaccess');
        })->name('print');

        // Show (must be last)
        Route::get('/{id}', function ($id) {
            $user = auth()->user();
            if ($user->canManageRequestForPayments()) {
                return app(RequestForPaymentController::class)->show($id);
            }
            return view('errors.noaccess');
        })->name('show');
    });

    // ===================== ACCOUNTS PAYABLE INVOICES =====================
    Route::prefix('accounts_payable_invoices')->name('accounts_payable_invoices.')->group(function () {

        // Search RFPs (AJAX)
        Route::get('/search-rfps', function () {
            $user = auth()->user();
            if ($user->canAccessModule('apv')) {
                return app(AccountsPayableInvoiceController::class)->searchRFPs(request());
            }
            return response()->json([]);
        })->name('search_rfps');

        // Search CARs (AJAX)
        Route::get('/search-cars', function () {
            $user = auth()->user();
            if ($user->canAccessModule('apv')) {
                return app(AccountsPayableInvoiceController::class)->searchCARs(request());
            }
            return response()->json([]);
        })->name('search_cars');

        // Search Reimbursements (AJAX)
        Route::get('/search-reimbursements', function () {
            $user = auth()->user();
            if ($user->canAccessModule('apv')) {
                return app(AccountsPayableInvoiceController::class)->searchReimbursements(request());
            }
            return response()->json([]);
        })->name('search_reimbursements');

        // Index
        Route::get('/', function () {
            $user = auth()->user();
            if ($user->canAccessModule('apv')) {
                return app(AccountsPayableInvoiceController::class)->index(request());
            }
            return view('errors.noaccess');
        })->name('index');

        // Export Excel
        Route::get('/export', function () {
            $user = auth()->user();
            if ($user->canAccessModule('apv')) {
                return app(AccountsPayableInvoiceController::class)->exportExcel(request());
            }
            return view('errors.noaccess');
        })->name('export');

        // Create
        Route::get('/create', function () {
            $user = auth()->user();
            if ($user->canAccessModule('apv')) {
                return app(AccountsPayableInvoiceController::class)->create(request());
            }
            return view('errors.noaccess');
        })->name('create');

        // Store
        Route::post('/', function () {
            $user = auth()->user();
            if ($user->canAccessModule('apv')) {
                return app(AccountsPayableInvoiceController::class)->store(request());
            }
            return view('errors.noaccess');
        })->name('store');

        // Approve DH (Level 1)
        Route::post('/{id}/approve-dh', function ($id) {
            $user = auth()->user();
            if ($user->canApproveAPVAsDH()) {
                return app(AccountsPayableInvoiceController::class)->approveDH(request(), $id);
            }
            return view('errors.noaccess');
        })->name('approve_dh');

        // Approve (Level 2 - Final)
        Route::post('/{id}/approve', function ($id) {
            $user = auth()->user();
            if ($user->canApproveAPV()) {
                return app(AccountsPayableInvoiceController::class)->approve(request(), $id);
            }
            return view('errors.noaccess');
        })->name('approve');

        // Reject
        Route::post('/{id}/reject', function ($id) {
            $user = auth()->user();
            if ($user->canApproveAPV() || $user->canApproveAPVAsDH()) {
                return app(AccountsPayableInvoiceController::class)->reject(request(), $id);
            }
            return view('errors.noaccess');
        })->name('reject');

        // Edit
        Route::get('/{id}/edit', function ($id) {
            $user = auth()->user();
            if ($user->canAccessModule('apv')) {
                return app(AccountsPayableInvoiceController::class)->edit($id);
            }
            return view('errors.noaccess');
        })->name('edit');

        // Update
        Route::put('/{id}', function ($id) {
            $user = auth()->user();
            if ($user->canAccessModule('apv')) {
                return app(AccountsPayableInvoiceController::class)->update(request(), $id);
            }
            return view('errors.noaccess');
        })->name('update');

        // Delete
        Route::delete('/{id}', function ($id) {
            $user = auth()->user();
            if ($user->canApproveAPVAsDH()) {
                return app(AccountsPayableInvoiceController::class)->destroy($id);
            }
            return view('errors.noaccess');
        })->name('destroy');

        // Print
        Route::get('/{id}/print', function ($id) {
            $user = auth()->user();
            if ($user->canAccessModule('apv')) {
                return app(AccountsPayableInvoiceController::class)->print($id);
            }
            return view('errors.noaccess');
        })->name('print');

        // Show (must be last)
        Route::get('/{id}', function ($id) {
            $user = auth()->user();
            if ($user->canAccessModule('apv')) {
                return app(AccountsPayableInvoiceController::class)->show($id);
            }
            return view('errors.noaccess');
        })->name('show');
    });

    // ===================== CHECK VOUCHERS =====================
    Route::prefix('check_vouchers')->name('check_vouchers.')->group(function () {

        // Search APVs (AJAX)
        Route::get('/search-apvs', function () {
            $user = auth()->user();
            if ($user->canAccessModule('cv')) {
                return app(CheckVoucherController::class)->searchAPVs(request());
            }
            return response()->json([]);
        })->name('search_apvs');

        // Index
        Route::get('/', function () {
            $user = auth()->user();
            if ($user->canAccessModule('cv')) {
                return app(CheckVoucherController::class)->index();
            }
            return view('errors.noaccess');
        })->name('index');

        // Create
        Route::get('/create', function () {
            $user = auth()->user();
            if ($user->canAccessModule('cv')) {
                return app(CheckVoucherController::class)->create(request());
            }
            return view('errors.noaccess');
        })->name('create');

        // Store
        Route::post('/', function () {
            $user = auth()->user();
            if ($user->canAccessModule('cv')) {
                return app(CheckVoucherController::class)->store(request());
            }
            return view('errors.noaccess');
        })->name('store');

        // Approve Accounting (Level 1)
        Route::post('/{id}/approve-accounting', function ($id) {
            $user = auth()->user();
            if ($user->canApproveCVAsAccounting()) {
                return app(CheckVoucherController::class)->approveAccounting(request(), $id);
            }
            return view('errors.noaccess');
        })->name('approve_accounting');

        // Approve ODM/FDM (Level 2 - Final)
        Route::post('/{id}/approve', function ($id) {
            $user = auth()->user();
            if ($user->canApproveCV()) {
                return app(CheckVoucherController::class)->approve(request(), $id);
            }
            return view('errors.noaccess');
        })->name('approve');

        // Reject
        Route::post('/{id}/reject', function ($id) {
            $user = auth()->user();
            if ($user->canPerformInModule('can_approve', 'cv')) {
                return app(CheckVoucherController::class)->reject(request(), $id);
            }
            return view('errors.noaccess');
        })->name('reject');

        // Edit
        Route::get('/{id}/edit', function ($id) {
            $user = auth()->user();
            if ($user->canAccessModule('cv')) {
                return app(CheckVoucherController::class)->edit($id);
            }
            return view('errors.noaccess');
        })->name('edit');

        // Update
        Route::put('/{id}', function ($id) {
            $user = auth()->user();
            if ($user->canAccessModule('cv')) {
                return app(CheckVoucherController::class)->update(request(), $id);
            }
            return view('errors.noaccess');
        })->name('update');

        // Delete
        Route::delete('/{id}', function ($id) {
            $user = auth()->user();
            if ($user->canApproveAPVAsDH()) {
                return app(CheckVoucherController::class)->destroy($id);
            }
            return view('errors.noaccess');
        })->name('destroy');

        // Print
        Route::get('/{id}/print', function ($id) {
            $user = auth()->user();
            if ($user->canAccessModule('cv')) {
                return app(CheckVoucherController::class)->print($id);
            }
            return view('errors.noaccess');
        })->name('print');

        // Show (must be last)
        Route::get('/{id}', function ($id) {
            $user = auth()->user();
            if ($user->canAccessModule('cv')) {
                return app(CheckVoucherController::class)->show($id);
            }
            return view('errors.noaccess');
        })->name('show');
    });

    // ===================== CASH ADVANCE REQUESTS =====================
    Route::prefix('cash_advance_requests')->name('cash_advance_requests.')->group(function () {

        Route::get('/', function () {
            return app(CashAdvanceRequestController::class)->index();
        })->name('index');

        Route::get('/create', function () {
            return app(CashAdvanceRequestController::class)->create();
        })->name('create');

        Route::post('/', function () {
            return app(CashAdvanceRequestController::class)->store(request());
        })->name('store');

        Route::post('/{id}/approve-dh', function ($id) {
            $user = auth()->user();
            if ($user->canApproveRFPAsDH() || $user->canPerformInModule('can_approve', 'rfp')) {
                return app(CashAdvanceRequestController::class)->approveDH(request(), $id);
            }
            return view('errors.noaccess');
        })->name('approve_dh');

        Route::post('/{id}/approve', function ($id) {
            $user = auth()->user();
            if ($user->canApproveRFPAsExecutive()) {
                return app(CashAdvanceRequestController::class)->approve(request(), $id);
            }
            return view('errors.noaccess');
        })->name('approve');

        Route::post('/{id}/reject', function ($id) {
            $user = auth()->user();
            if ($user->canApproveRequestForPayments()) {
                return app(CashAdvanceRequestController::class)->reject(request(), $id);
            }
            return view('errors.noaccess');
        })->name('reject');

        Route::get('/{id}/edit', function ($id) {
            return app(CashAdvanceRequestController::class)->edit($id);
        })->name('edit');

        Route::put('/{id}', function ($id) {
            return app(CashAdvanceRequestController::class)->update(request(), $id);
        })->name('update');

        Route::delete('/{id}', function ($id) {
            $user = auth()->user();
            if ($user->isAdminUser()) {
                return app(CashAdvanceRequestController::class)->destroy($id);
            }
            return view('errors.noaccess');
        })->name('destroy');

        Route::get('/{id}/print', function ($id) {
            return app(CashAdvanceRequestController::class)->print($id);
        })->name('print');

        Route::get('/{id}', function ($id) {
            return app(CashAdvanceRequestController::class)->show($id);
        })->name('show');
    });

    // ===================== LIQUIDATION FORMS =====================
    Route::prefix('liquidation_forms')->name('liquidation_forms.')->group(function () {

        Route::get('/search-cars', function () {
            return app(LiquidationFormController::class)->searchCARs(request());
        })->name('search_cars');

        Route::get('/', function () {
            return app(LiquidationFormController::class)->index();
        })->name('index');

        Route::get('/create', function () {
            return app(LiquidationFormController::class)->create(request());
        })->name('create');

        Route::post('/', function () {
            return app(LiquidationFormController::class)->store(request());
        })->name('store');

        Route::post('/{id}/approve-dh', function ($id) {
            $user = auth()->user();
            if ($user->canApproveRFPAsDH() || $user->canPerformInModule('can_approve', 'rfp')) {
                return app(LiquidationFormController::class)->approveDH(request(), $id);
            }
            return view('errors.noaccess');
        })->name('approve_dh');

        Route::post('/{id}/approve', function ($id) {
            $user = auth()->user();
            if ($user->canApproveRFPAsExecutive()) {
                return app(LiquidationFormController::class)->approve(request(), $id);
            }
            return view('errors.noaccess');
        })->name('approve');

        Route::post('/{id}/reject', function ($id) {
            $user = auth()->user();
            if ($user->canApproveRequestForPayments()) {
                return app(LiquidationFormController::class)->reject(request(), $id);
            }
            return view('errors.noaccess');
        })->name('reject');

        Route::get('/{id}/edit', function ($id) {
            return app(LiquidationFormController::class)->edit($id);
        })->name('edit');

        Route::put('/{id}', function ($id) {
            return app(LiquidationFormController::class)->update(request(), $id);
        })->name('update');

        Route::delete('/{id}', function ($id) {
            $user = auth()->user();
            if ($user->isAdminUser()) {
                return app(LiquidationFormController::class)->destroy($id);
            }
            return view('errors.noaccess');
        })->name('destroy');

        Route::get('/{id}/print', function ($id) {
            return app(LiquidationFormController::class)->print($id);
        })->name('print');

        Route::get('/{id}', function ($id) {
            return app(LiquidationFormController::class)->show($id);
        })->name('show');
    });

    // ===================== REIMBURSEMENT FORMS =====================
    Route::prefix('reimbursement_forms')->name('reimbursement_forms.')->group(function () {

        Route::get('/', function () {
            return app(ReimbursementFormController::class)->index();
        })->name('index');

        Route::get('/create', function () {
            return app(ReimbursementFormController::class)->create();
        })->name('create');

        Route::post('/', function () {
            return app(ReimbursementFormController::class)->store(request());
        })->name('store');

        Route::post('/{id}/approve-dh', function ($id) {
            $user = auth()->user();
            if ($user->canApproveRFPAsDH() || $user->canPerformInModule('can_approve', 'rfp')) {
                return app(ReimbursementFormController::class)->approveDH(request(), $id);
            }
            return view('errors.noaccess');
        })->name('approve_dh');

        Route::post('/{id}/approve', function ($id) {
            $user = auth()->user();
            if ($user->canApproveRFPAsExecutive()) {
                return app(ReimbursementFormController::class)->approve(request(), $id);
            }
            return view('errors.noaccess');
        })->name('approve');

        Route::post('/{id}/reject', function ($id) {
            $user = auth()->user();
            if ($user->canApproveRequestForPayments()) {
                return app(ReimbursementFormController::class)->reject(request(), $id);
            }
            return view('errors.noaccess');
        })->name('reject');

        Route::get('/{id}/edit', function ($id) {
            return app(ReimbursementFormController::class)->edit($id);
        })->name('edit');

        Route::put('/{id}', function ($id) {
            return app(ReimbursementFormController::class)->update(request(), $id);
        })->name('update');

        Route::delete('/{id}', function ($id) {
            $user = auth()->user();
            if ($user->isAdminUser()) {
                return app(ReimbursementFormController::class)->destroy($id);
            }
            return view('errors.noaccess');
        })->name('destroy');

        Route::get('/{id}/print', function ($id) {
            return app(ReimbursementFormController::class)->print($id);
        })->name('print');

        Route::get('/{id}', function ($id) {
            return app(ReimbursementFormController::class)->show($id);
        })->name('show');
    });

    // ===================== PO RECORDS =====================
    Route::middleware('auth')->prefix('po-records')->name('po_records.')->group(function () {
        Route::get('/', function () {
            $user = auth()->user();
            if ($user->canAccessModule('po_dashboard')) {
                return app(PurchaseOrderRecordsController::class)->index(request());
            }
            return view('errors.noaccess');
        })->name('index');
        

        Route::get('/export', function () {
            $user = auth()->user();
            if ($user->canAccessModule('po_dashboard')) {
                return app(PurchaseOrderRecordsController::class)->exportExcel(request());
            }
            return view('errors.noaccess');
        })->name('export');
    });

    //====================== CHANGE LOG CONTROLLER =====================
    Route::middleware('auth')->prefix('changelog')->name('changelog.')->group(function () {
        
        // Main changelog index
        Route::get('/', function () {
            $user = auth()->user();
            if ($user->canAccessChangelog()) {
                return app(App\Http\Controllers\ChangeLogController::class)->index(request());
            }
            return view('errors.noaccess');
        })->name('index');
        
        // View changes for specific sales order
        Route::get('/sales-order/{id}', function ($id) {
            $user = auth()->user();
            if ($user->canAccessChangelog()) {
                return app(App\Http\Controllers\ChangeLogController::class)->salesOrderChanges($id);
            }
            return view('errors.noaccess');
        })->name('sales_order');
        
        // Export changelog to CSV
        Route::get('/export', function () {
            $user = auth()->user();
            if ($user->canAccessChangelog()) {
                return app(App\Http\Controllers\ChangeLogController::class)->export(request());
            }
            return view('errors.noaccess');
        })->name('export');
        
        // Notifications
        Route::get('/notifications', function () {
            $user = auth()->user();
            if ($user->canAccessChangelog()) {
                return app(App\Http\Controllers\ChangeLogController::class)->notifications();
            }
            return view('errors.noaccess');
        })->name('notifications');
        
        // Mark notification as read
        Route::post('/notifications/{id}/read', function ($id) {
            $user = auth()->user();
            if ($user->canAccessChangelog()) {
                return app(App\Http\Controllers\ChangeLogController::class)->markAsRead($id);
            }
            return redirect()->back()->with('error', 'Access denied');
        })->name('notifications.read');
        
        // Mark all notifications as read
        Route::post('/notifications/read-all', function () {
            $user = auth()->user();
            if ($user->canAccessChangelog()) {
                return app(App\Http\Controllers\ChangeLogController::class)->markAllAsRead();
            }
            return redirect()->back()->with('error', 'Access denied');
        })->name('notifications.read_all');
        
        // Get unread notification count (for AJAX)
        Route::get('/notifications/unread-count', function () {
            $user = auth()->user();
            if ($user->canAccessChangelog()) {
                return app(App\Http\Controllers\ChangeLogController::class)->unreadCount();
            }
            return response()->json(['count' => 0]);
        })->name('notifications.unread_count');
    });

    // ===================== USER MANAGEMENT =====================
Route::prefix('admin/users')->name('admin.users.')->group(function () {
    Route::get('/', fn() => auth()->user()->canManageUsers() ? app(UserManagementController::class)->index() : view('errors.noaccess'))->name('index');
    Route::get('/create', fn() => auth()->user()->canManageUsers() ? app(UserManagementController::class)->create() : view('errors.noaccess'))->name('create');
    Route::post('/', fn() => auth()->user()->canManageUsers() ? app(UserManagementController::class)->store(request()) : view('errors.noaccess'))->name('store');
    Route::post('/{id}/toggle-lock', fn($id) => auth()->user()->isAdminUser() ? app(UserManagementController::class)->toggleLock($id) : view('errors.noaccess'))->name('toggleLock');
    Route::post('/{id}/module-overrides', fn($id) => auth()->user()->isAdminUser() ? app(UserManagementController::class)->updateModuleOverride(request(), $id) : response()->json(['error' => 'Unauthorized'], 403))->name('module-overrides');
    Route::get('/{id}/edit', fn($id) => auth()->user()->canManageUsers() ? app(UserManagementController::class)->edit($id) : view('errors.noaccess'))->name('edit');
    Route::put('/{id}', fn($id) => auth()->user()->canManageUsers() ? app(UserManagementController::class)->update(request(), $id) : view('errors.noaccess'))->name('update');
    Route::delete('/{id}', fn($id) => auth()->user()->canManageUsers() ? app(UserManagementController::class)->destroy($id) : view('errors.noaccess'))->name('destroy');
});

// ===================== RBAC MANAGEMENT (IT ONLY) =====================
Route::prefix('rbac')->name('rbac.')->middleware('auth')->group(function () {
    // Middleware-style closure to check IT role for all RBAC routes
    $itOnlyMiddleware = function ($callback) {
        return function () use ($callback) {
            if (!auth()->user()->hasRole('IT') && auth()->user()->role !== 'IT') {
                return view('errors.noaccess');
            }
            return $callback(...func_get_args());
        };
    };

    Route::get('/', $itOnlyMiddleware(fn() => app(RoleController::class)->index()))->name('index');
    Route::post('/toggle-permission', $itOnlyMiddleware(fn() => app(RoleController::class)->togglePermission(request())))->name('toggle');
    Route::post('/change-level', $itOnlyMiddleware(fn() => app(RoleController::class)->changeLevel(request())))->name('level');
    Route::post('/add-role', $itOnlyMiddleware(fn() => app(RoleController::class)->addRole(request())))->name('addRole');
    Route::delete('/remove-role/{id}', $itOnlyMiddleware(fn($id) => app(RoleController::class)->removeRole(request(), $id)))->name('removeRole');
    Route::post('/change-role-type/{id}', $itOnlyMiddleware(fn($id) => app(RoleController::class)->changeRoleType(request(), $id)))->name('changeRoleType');

    // User Management (IT ONLY)
    Route::post('/user', $itOnlyMiddleware(fn() => app(RoleController::class)->storeUser(request())))->name('storeUser');
    Route::put('/user/{id}', $itOnlyMiddleware(fn($id) => app(RoleController::class)->updateUser(request(), $id)))->name('updateUser');
    Route::delete('/user/{id}', $itOnlyMiddleware(fn($id) => app(RoleController::class)->destroyUser(request(), $id)))->name('destroyUser');
    Route::post('/user/{id}/toggle-lock', $itOnlyMiddleware(fn($id) => app(RoleController::class)->toggleLockUser(request(), $id)))->name('toggleLock');
    Route::post('/user/{id}/reset-attempts', $itOnlyMiddleware(fn($id) => app(RoleController::class)->resetLoginAttempts(request(), $id)))->name('resetAttempts');
});

// ===================== CURRENCIES =====================
Route::prefix('currencies')->name('currencies.')->group(function () {
    Route::get('/', function () {
        return app(CurrencyController::class)->index();
    })->name('index');

    Route::put('/{id}', function ($id) {
        $user = auth()->user();
        if ($user->canAccessModule('currency_rates')) {
            return app(CurrencyController::class)->update(request(), $id);
        }
        return view('errors.noaccess');
    })->name('update');

    Route::get('/rate/{code}', function ($code) {
        return app(CurrencyController::class)->rate($code);
    })->name('rate');
});

// ===================== USER PROFILE (All authenticated users) =====================
Route::get('/profile', [UserController::class, 'profile'])->name('profile');
Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');

    }); 

// ===================== ROOT REDIRECT =====================
Route::get('/', function () {
    return redirect()->route('login');
});

// In routes/web.php
Route::get('/fix-sales-order-totals', [SalesOrderController::class, 'fixExistingSalesOrders']);

// In routes/web.php
Route::get('/fix-delivery-totals', [DeliveriesController::class, 'fixExistingTotals']);

// ✅ Fix duplicate item codes for a specific SO (run once to repair data)
Route::get('/fix-duplicate-items/{so_number?}', function($soNumber = null) {
    if (!auth()->check() || !auth()->user()->isAdminUser()) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $request = request();
    $request->merge(['so_number' => $soNumber]);

    return app(DeliveriesController::class)->repairDuplicateItemCodes($request);
})->middleware('auth');

Route::get('/recalculate-deliveries/{so_number}', function($soNumber) {
    if (!auth()->check() || !auth()->user()->isAdminUser()) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $request = request();
    $request->merge(['so_number' => $soNumber]);

    return app(DeliveriesController::class)->recalculateSODeliveries($request);
})->middleware('auth');

Route::get('/debug-delivery', function() {
    $delivery = \App\Models\Deliveries::with(['salesOrder.customer'])->first();
    
    if (!$delivery) {
        return 'No deliveries found';
    }
    
    return [
        'delivery_info' => [
            'dr_no' => $delivery->dr_no,
            'sales_order_number' => $delivery->sales_order_number,
            'customer_code' => $delivery->customer_code,
        ],
        'salesOrder_exists' => $delivery->salesOrder ? 'YES' : 'NO',
        'salesOrder_data' => $delivery->salesOrder ? [
            'id' => $delivery->salesOrder->id,
            'sales_order_number' => $delivery->salesOrder->sales_order_number,
            'customer_id' => $delivery->salesOrder->customer_id,
            'client_name' => $delivery->salesOrder->client_name,
        ] : null,
        'customer_exists' => ($delivery->salesOrder && $delivery->salesOrder->customer) ? 'YES' : 'NO',
        'customer_data' => ($delivery->salesOrder && $delivery->salesOrder->customer) ? [
            'id' => $delivery->salesOrder->customer->id,
            'customer_code' => $delivery->salesOrder->customer->customer_code,
            'customer_name' => $delivery->salesOrder->customer->customer_name,
        ] : null,
    ];
});

Route::get('/debug-delivery-specific', function() {
    $delivery = \App\Models\Deliveries::with(['salesOrder.customer'])
        ->where('dr_no', 'DR-5566774324')
        ->first();
    
    if (!$delivery) {
        return 'Delivery DR-5566774324 not found';
    }
    
    return [
        'delivery_info' => [
            'dr_no' => $delivery->dr_no,
            'sales_order_number' => $delivery->sales_order_number,
            'customer_code' => $delivery->customer_code,
        ],
        'salesOrder_exists' => $delivery->salesOrder ? 'YES' : 'NO',
        'salesOrder_data' => $delivery->salesOrder,
        'customer_exists' => ($delivery->salesOrder && $delivery->salesOrder->customer) ? 'YES' : 'NO',
        'customer_data' => ($delivery->salesOrder && $delivery->salesOrder->customer) ? $delivery->salesOrder->customer : null,
    ];
});

// Add this to your web.php routes file temporarily for testing

Route::get('/test-ar-data/{customerCode}', function($customerCode) {
    
    // Get customer info from ar_aging
    $arRecord = DB::table('ar_aging')
        ->where('customer_code', $customerCode)
        ->first();
    
    if (!$arRecord) {
        return response()->json([
            'error' => 'Customer not found in ar_aging',
            'customer_code' => $customerCode
        ]);
    }

    // Test payments
    $paymentsExact = DB::table('payments')
        ->where('customer_code', $customerCode)
        ->get();

    $paymentsTrimmed = DB::table('payments')
        ->whereRaw('TRIM(customer_code) = ?', [trim($customerCode)])
        ->get();

    $paymentsAll = DB::table('payments')
        ->select('customer_code')
        ->distinct()
        ->get();

    // Test adjustments
    $adjustmentsExact = DB::table('ar_adjustments')
        ->where('customer_code', $customerCode)
        ->get();

    $adjustmentsTrimmed = DB::table('ar_adjustments')
        ->whereRaw('TRIM(customer_code) = ?', [trim($customerCode)])
        ->get();

    $adjustmentsAll = DB::table('ar_adjustments')
        ->select('customer_code')
        ->distinct()
        ->get();

    return response()->json([
        'customer_code_searched' => $customerCode,
        'customer_code_length' => strlen($customerCode),
        'customer_code_trimmed' => trim($customerCode),
        
        'ar_aging' => [
            'found' => true,
            'customer_code' => $arRecord->customer_code,
            'customer_name' => $arRecord->client_name ?? 'N/A'
        ],
        
        'payments' => [
            'total_in_db' => DB::table('payments')->count(),
            'found_exact_match' => $paymentsExact->count(),
            'found_trimmed_match' => $paymentsTrimmed->count(),
            'sample_customer_codes' => $paymentsAll->take(10),
            'data' => $paymentsExact->take(5)
        ],
        
        'adjustments' => [
            'total_in_db' => DB::table('ar_adjustments')->count(),
            'found_exact_match' => $adjustmentsExact->count(),
            'found_trimmed_match' => $adjustmentsTrimmed->count(),
            'sample_customer_codes' => $adjustmentsAll->take(10),
            'data' => $adjustmentsExact->take(5)
        ]
    ]);
});

// ===================== PAYMENT TERMS =====================
Route::middleware('auth')->group(function () {
    Route::resource('payment_terms', PaymentTermController::class)->except(['show']);
});

// ===================== DEBIT MEMOS =====================
Route::middleware('auth')->group(function () {
    Route::resource('debit_memos', DebitMemoController::class);
});

// ===================== AP LEDGER =====================
Route::middleware('auth')->group(function () {
    Route::get('/ap-ledger', [ApLedgerController::class, 'index'])->name('ap_ledger.index');
});

// ===================== AP DASHBOARD =====================
Route::middleware('auth')->group(function () {
    Route::get('/ap-dashboard', [ApDashboardController::class, 'index'])->name('ap_dashboard');
});

// ===================== AP REPORTS =====================
Route::middleware('auth')->prefix('ap-reports')->name('ap_reports.')->group(function () {
    Route::get('/aging', [ApReportController::class, 'aging'])->name('aging');
    Route::get('/cash-forecast', [ApReportController::class, 'cashForecast'])->name('cash_forecast');
    Route::get('/spend-analysis', [ApReportController::class, 'spendAnalysis'])->name('spend_analysis');
});

