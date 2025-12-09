<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    CustomerController,
    UserController,
    SalesOrderController,
    ItemController,
    DashboardController,
    DeliveriesController,
    UserManagementController,
    RecordsController,
    ImportController

};

    // ===================== AUTH (Public Routes) =====================
    Route::get('/login', [UserController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [UserController::class, 'login'])->name('login.submit');
    Route::post('/logout', [UserController::class, 'logout'])->name('logout');

    // ===================== AUTHENTICATED ROUTES =====================
    Route::middleware(['auth'])->group(function () {

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

    // IMPORT ITEMS — only Admin, IT + Accounting roles
    Route::post('/excel-import/items', function () {
        $user = auth()->user();
        if (!$user || !$user->canImportItems()) { abort(403, 'Unauthorized');}
        return app(App\Http\Controllers\ExcelImportController::class)->importItems(request());
    })->name('excel.import.items');

// Add this route with your other import routes
Route::post('/excel/import/monthly-sales', [ImportController::class, 'importMonthlySales'])->name('excel.import.monthly_sales');

    // IMPORT CUSTOMERS — all the listed roles
    Route::post('/excel-import/customers', function () {
        $user = auth()->user();
        if (!$user || !$user->canImportCustomers()) { abort(403, 'Unauthorized'); }
        return app(App\Http\Controllers\ExcelImportController::class)->importCustomers(request());
    })->name('excel.import.customers');

    // ===================== DASHBOARD =====================
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/recent-activities', [DashboardController::class, 'viewAllActivities'])->name('recent_activities.index');

    // In routes/web.php
    Route::get('/sales-report', [DashboardController::class, 'salesReport'])->name('sales.report');

    // ===================== RECORDS =====================
    Route::get('/records', [App\Http\Controllers\RecordsController::class, 'index'])->name('records.index');

    //export excel
   Route::get('/records/export/excel', [RecordsController::class, 'exportExcel'])->name('records.export.excel');
    
    // Sales Order Records
    Route::get('/records/sales-order/{id}', [App\Http\Controllers\RecordsController::class, 'so_show'])->name('records.so_show');

    // Delivery Records
    Route::get('/records/delivery/{id}', [App\Http\Controllers\RecordsController::class, 'dshow'])->name('records.dshow');

    // ===================== SALES ORDERS =====================
    Route::prefix('sales_orders')->name('sales_orders.')->group(function () {

        Route::get('/sales_orders', [SalesOrderController::class, 'index'])->name('sales_orders.index');
        Route::get('/sales_orders/{id}', [SalesOrderController::class, 'show'])->name('sales_orders.show');

        // PRINT ROUTES
        Route::get('/print-list', [SalesOrderController::class, 'printList'])->name('printList');
        Route::get('/{id}/print', [SalesOrderController::class, 'print'])->name('print');
    
        // ✅ EXCEL EXPORT - Add this route
    Route::get('/export-excel', function () {
            $user = auth()->user();
            if (in_array($user->role, ['Admin', 'IT', 'CSR_Approver','CSR_Creator', 'Delivery_Creator', 'Delivery_Approver', 'CC_Creator', 'CC_Approver', 'Accounting_Creator', 'Accounting_Approver'])) {
                return app(SalesOrderController::class)->exportExcel(request());
            }
            return view('errors.noaccess');
        })->name('exportExcel');

        Route::get('sales-orders/{id}/delivery-batches', [SalesOrderController::class, 'deliveryBatches'])
        ->name('delivery_batches');

        
        // ✅ Index
        Route::get('/', function () {
            $user = auth()->user();
            if (in_array($user->role, ['Admin', 'IT', 'CSR_Approver','CSR_Creator', 'Delivery_Creator', 'Delivery_Approver', 'CC_Creator', 'CC_Approver', 'Accounting_Creator', 'Accounting_Approver'])) {
                return app(SalesOrderController::class)->index(request());
            }
            return view('errors.noaccess');
        })->name('index');

        // ✅ Create
        Route::get('/create', function () {
            $user = auth()->user();
            if (in_array($user->role, ['Admin', 'IT', 'CSR_Approver','CSR_Creator',])) {
                return app(SalesOrderController::class)->create();
            }
            return view('errors.noaccess');
        })->name('create');

        // ✅ Store
        Route::post('/', function () {
            $user = auth()->user();
            if (in_array($user->role, ['Admin', 'IT', 'CSR_Approver','CSR_Creator',])) {
                return app(SalesOrderController::class)->store(request());
            }
            return view('errors.noaccess');
        })->name('store');

        // ✅ Accepted
        Route::get('/accepted', function () {
            $user = auth()->user();
            if (in_array($user->role, ['Admin', 'IT', 'CSR_Approver','CSR_Creator', 'Delivery_Creator', 'Delivery_Approver', 'CC_Creator', 'CC_Approver', 'Accounting_Creator', 'Accounting_Approver'])) {
                return app(SalesOrderController::class)->accepted(request());
            }
            return view('errors.noaccess');
        })->name('accepted');

        // ✅ Search
        Route::get('/search', function () {
            $user = auth()->user();
            if (in_array($user->role, ['Admin', 'IT', 'CSR_Approver','CSR_Creator', 'Delivery_Creator', 'Delivery_Approver', 'CC_Creator', 'CC_Approver', 'Accounting_Creator', 'Accounting_Approver'])) {
                return app(SalesOrderController::class)->search(request());
            }
            return view('errors.noaccess');
        })->name('search');

        // ✅ Edit
        Route::get('/{id}/edit', function ($id) {
            $user = auth()->user();
            if (in_array($user->role, ['Admin', 'IT', 'CSR_Approver'])) {
                return app(SalesOrderController::class)->edit($id);
            }
            return view('errors.noaccess');
        })->name('edit');

        // ✅ Update
        Route::put('/{id}', function ($id) {
            $user = auth()->user();
            if (in_array($user->role, ['Admin', 'IT', 'CSR_Approver'])) {
                return app(SalesOrderController::class)->update(request(), $id);
            }
            return view('errors.noaccess');
        })->name('update');

        // ✅ Delete
        Route::delete('/{id}', function ($id) {
            $user = auth()->user();
            if (in_array($user->role, ['Admin', 'IT', 'CSR_Approver'])) {
                return app(SalesOrderController::class)->destroy($id);
            }
            return view('errors.noaccess');
        })->name('destroy');

        // ✅ Approve
        Route::post('/{id}/approve', function ($id) {
            $user = auth()->user();
            if (in_array($user->role, ['Admin', 'IT', 'CSR_Approver','CSR_Creator','Accounting_Approver'])) {
                return app(SalesOrderController::class)->approve($id);
            }
            return view('errors.noaccess');
        })->name('approve');

        // ✅ Update Status
        Route::patch('/{id}/update-status', function ($id) {
            $user = auth()->user();
            if (in_array($user->role, ['Admin', 'IT', 'CSR_Approver','Accounting_Approver'])) {
                return app(SalesOrderController::class)->updateStatus(request(), $id);
            }
            return view('errors.noaccess');
        })->name('updateStatus');

        // ✅ Mark Delivered
        Route::patch('/{id}/mark-delivered', function ($id) {
            $user = auth()->user();
            if (in_array($user->role, ['Admin', 'IT','CSR_Approver','Accounting_Approver'])) {
                return app(SalesOrderController::class)->markDelivered($id);
            }
            return view('errors.noaccess');
        })->name('markDelivered');

        // ✅ Show
        Route::get('/{id}', function ($id) {
            $user = auth()->user();
            if (in_array($user->role, ['Admin', 'IT', 'CSR_Approver','CSR_Creator', 'Delivery_Creator', 'Delivery_Approver', 'CC_Creator', 'CC_Approver', 'Accounting_Creator', 'Accounting_Approver'])) {
                return app(SalesOrderController::class)->show($id);
            }
            return view('errors.noaccess');
        })->name('show');

            // ✅ Add Items to Approved SO
    Route::get('/{id}/add-items', function ($id) {
        $user = auth()->user();
        if (in_array($user->role, ['Admin', 'IT', 'CSR_Approver', 'CSR_Creator'])) {
            return app(SalesOrderController::class)->addItemsForm($id);
        }
        return view('errors.noaccess');
    })->name('addItemsForm');
     });

    // ===================== ITEMS =====================
    Route::prefix('items')->name('items.')->group(function () {
        // ✅ BULK ACTIONS - Place at the top before parameterized routes
        Route::post('/bulk-approve', [ItemController::class, 'bulkApprove'])->name('bulk-approve');
        Route::post('/bulk-reject', [ItemController::class, 'bulkReject'])->name('bulk-reject');
        
        // TOGGLE ENABLE/DISABLE
        Route::post('/{item}/toggle', [ItemController::class, 'toggleStatus'])->name('toggle');
        
        // ✅ Index
        Route::get('/', function () {
            $user = auth()->user(); 
            if (in_array($user->role, [
                'Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver', 'CC_Creator', 'CC_Approver',
            ])) {
                return app(ItemController::class)->index(); 
            }
            return view('errors.noaccess');
        })->name('index');
        
        // ✅ Create
        Route::get('/create', function () {
            $user = auth()->user();
            if (in_array($user->role, [
                'Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver','CC_Creator', 'CC_Approver',
            ])) { 
                return app(ItemController::class)->create();
            } 
            return view('errors.noaccess');
        })->name('create');
        
        // ✅ Store
        Route::post('/', function () {
            $user = auth()->user();
            if (in_array($user->role, [
                'Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver', 
            ])) {
                return app(ItemController::class)->store(request()); 
            } 
            return view('errors.noaccess');
        })->name('store');
        
        // Item Approval Routes
        Route::post('/{id}/approve', [ItemController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [ItemController::class, 'reject'])->name('reject');
        Route::get('/pending', [ItemController::class, 'pending'])->name('pending');
        
        // ✅ Edit
        Route::get('/{id}/edit', function ($id) {
            $user = auth()->user();
            if (in_array($user->role, [
                'Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver', 
            ])) {  
                return app(ItemController::class)->edit($id);
            }
            return view('errors.noaccess');
        })->name('edit');
        
        // ✅ Update
        Route::put('/{id}', function ($id) {
            $user = auth()->user();
            if (in_array($user->role, [
                'Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver', 
            ])) { 
                return app(ItemController::class)->update(request(), $id); 
            }
            return view('errors.noaccess');
        })->name('update');
        
        // ✅ Delete
        Route::delete('/{id}', function ($id) {
            $user = auth()->user();
            if (in_array($user->role, ['Admin', 'IT','Accounting_Approver'])) { 
                return app(ItemController::class)->destroy($id); 
            }
            return view('errors.noaccess');
        })->name('destroy');
        
        // ✅ Show - Must be LAST to avoid conflicts
        Route::get('/{id}', function ($id) {
            $user = auth()->user();
            if (in_array($user->role, [
                'Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver',  'CC_Creator', 'CC_Approver'
            ])) {
                return app(ItemController::class)->show($id);
            }
            return view('errors.noaccess');
        })->name('show');
    });


   // ===================== CUSTOMERS =====================
Route::prefix('customers')->name('customers.')->group(function () {

    // ✅ Index
    Route::get('/', function () {
        $user = auth()->user();
        if (in_array($user->role, ['Admin', 'IT', 'CC_Creator', 'CC_Approver', 'Accounting_Creator', 'Accounting_Approver'])) {
            return app(CustomerController::class)->index();
        }
        return view('errors.noaccess');
    })->name('index');

    // ✅ Create
    Route::get('/create', function () {
        $user = auth()->user();
        if (in_array($user->role, ['Admin', 'IT', 'CC_Creator', 'CC_Approver'])) {
            return app(CustomerController::class)->create();
        }
        return view('errors.noaccess');
    })->name('create');

    // ✅ Store
    Route::post('/', function () {
        $user = auth()->user();
        if (in_array($user->role, ['Admin', 'IT', 'CC_Creator', 'CC_Approver'])) {
            return app(CustomerController::class)->store(request());
        }
        return view('errors.noaccess');
    })->name('store');

    // ✅ **ADD THIS NEW ROUTE** - Get customer by code (for AJAX autofill)
    Route::get('/get/{code}', function ($code) {
        $user = auth()->user();
        if (in_array($user->role, ['Admin', 'IT', 'CSR_Approver', 'CSR_Creator', 'CC_Creator', 'CC_Approver'])) {
            return app(CustomerController::class)->getByCode($code);
        }
        return response()->json(['error' => 'Access denied'], 403);
    })->name('getByCode');

    // ✅ Edit
    Route::get('/{id}/edit', function ($id) {
        $user = auth()->user();
        if (in_array($user->role, ['Admin', 'IT', 'CC_Creator', 'CC_Approver'])) {
            return app(CustomerController::class)->edit($id);
        }
        return view('errors.noaccess');
    })->name('edit');

    // ✅ Update
    Route::put('/{id}', function ($id) {
        $user = auth()->user();
        if (in_array($user->role, ['Admin', 'IT', 'CC_Creator', 'CC_Approver'])) {
            return app(CustomerController::class)->update(request(), $id);
        }
        return view('errors.noaccess');
    })->name('update');

    // ✅ Delete
    Route::delete('/{id}', function ($id) {
        $user = auth()->user();
        if (in_array($user->role, ['Admin', 'IT'])) { // Only admin/IT can delete
            return app(CustomerController::class)->destroy($id);
        }
        return view('errors.noaccess');
    })->name('destroy');

    // Inside Route::prefix('customers')->name('customers.')->group(function () {

    // ✅ Toggle Status 
    Route::patch('/{id}/toggle-status', function ($id) {
        $user = auth()->user();
        if (in_array($user->role, ['Admin', 'IT', 'CC_Creator', 'CC_Approver'])) {
            return app(CustomerController::class)->toggleStatus($id);
        }
        return view('errors.noaccess');
    })->name('toggleStatus');

    // ✅ Show (MUST BE LAST because it catches any /{id})
    Route::get('/{id}', function ($id) {
        $user = auth()->user();
        if (in_array($user->role, ['Admin', 'IT', 'CC_Creator', 'CC_Approver', 'Accounting_Creator', 'Accounting_Approver'])) {
            return app(CustomerController::class)->show($id);
        }
        return view('errors.noaccess');
    })->name('show');
});

    // ===================== DELIVERIES =====================
    Route::prefix('deliveries')->name('deliveries.')->group(function () {
        
        // PRINT ROUTES (must be first to avoid conflicts)
        Route::get('/print-list', function() {
            if (in_array(auth()->user()->role, ['Admin', 'IT', 'Delivery_Creator', 'Delivery_Approver', 'CC_Creator', 'CC_Approver', 'Accounting_Creator', 'Accounting_Approver'])) {
                return app(DeliveriesController::class)->printList(request());
            }
            return view('errors.noaccess');
        })->name('printList');

        // EXCEL EXPORT (LIST)
        Route::get('/export-excel', function() {
            if (in_array(auth()->user()->role, ['Admin', 'IT', 'Delivery_Creator', 'Delivery_Approver', 'CC_Creator', 'CC_Approver', 'Accounting_Creator', 'Accounting_Approver'])) {
                return app(DeliveriesController::class)->exportExcel(request());
            }
            return view('errors.noaccess');
        })->name('exportExcel');

        // ✅ FIXED: EXCEL EXPORT (SINGLE DELIVERY WITH ITEMS)
        Route::get('/export-items', function() {
            if (in_array(auth()->user()->role, ['Admin', 'IT', 'Delivery_Creator', 'Delivery_Approver', 'CC_Creator', 'CC_Approver', 'Accounting_Creator', 'Accounting_Approver'])) {
                return app(DeliveriesController::class)->exportDeliveryItemsExcel(request());
            }
            return view('errors.noaccess');
        })->name('exportDeliveryItemsExcel');
        
        // DELIVERIES LIST PAGE (for deliveries.deliveries view)
        Route::get('/list', function() {
            if (in_array(auth()->user()->role, ['Admin', 'IT', 'Delivery_Creator', 'Delivery_Approver', 'CC_Creator', 'CC_Approver', 'Accounting_Creator', 'Accounting_Approver'])) {
                return app(DeliveriesController::class)->deliveriesList(request());
            }
            return view('errors.noaccess');
        })->name('deliveries');
        
        // SEARCH
        Route::get('/search', [DeliveriesController::class, 'search'])->name('search');
        
        // CREATE
        Route::get('/create', function() {
            if (in_array(auth()->user()->role, ['Admin', 'IT',  'Delivery_Creator', 'Delivery_Approver'])) {
                return app(DeliveriesController::class)->create();
            }
            return view('errors.noaccess');
        })->name('create');
        
        // STORE
        Route::post('/store', function() {
            if (in_array(auth()->user()->role, ['Admin', 'IT',  'Delivery_Creator', 'Delivery_Approver'])) {
                return app(DeliveriesController::class)->store(request());
            }
            return view('errors.noaccess');
        })->name('store');
        
        // INDEX (main list page with date filter)
        Route::get('/', function() {
            if (in_array(auth()->user()->role, ['Admin', 'IT', 'Delivery_Creator', 'Delivery_Approver', 'CC_Creator', 'CC_Approver', 'Accounting_Creator', 'Accounting_Approver'])) {
                return app(DeliveriesController::class)->index(request());
            }
            return view('errors.noaccess');
        })->name('index');
        
        // SINGLE DELIVERY PRINT (must come before /{id} to avoid conflict)
        Route::get('/{id}/print', function($id) {
            if (in_array(auth()->user()->role, ['Admin', 'IT', 'Delivery_Creator', 'Delivery_Approver', 'CC_Creator', 'CC_Approver', 'Accounting_Creator', 'Accounting_Approver'])) {
                return app(DeliveriesController::class)->print($id);
            }
            return view('errors.noaccess');
        })->name('print');
        
        // EDIT
        Route::get('/{id}/edit', function($id) {
            if (in_array(auth()->user()->role, ['Admin', 'IT',  'Delivery_Creator', 'Delivery_Approver'])) {
                return app(DeliveriesController::class)->edit($id);
            }
            return view('errors.noaccess');
        })->name('edit');
        
        // UPDATE
        Route::put('/{id}', function($id) {
            if (in_array(auth()->user()->role, ['Admin', 'IT',  'Delivery_Creator', 'Delivery_Approver'])) {
                return app(DeliveriesController::class)->update(request(), $id);
            }
            return view('errors.noaccess');
        })->name('update');
        
        // SHOW (must be last because it catches any /{id})
        Route::get('/{id}', function($id) {
            if (in_array(auth()->user()->role, ['Admin', 'IT', 'Delivery_Creator', 'Delivery_Approver', 'CC_Creator', 'CC_Approver', 'Accounting_Creator', 'Accounting_Approver'])) {
                return app(DeliveriesController::class)->show($id);
            }
            return view('errors.noaccess');
        })->name('show');
    });

    // ===================== USER MANAGEMENT =====================
Route::prefix('admin/users')->name('admin.users.')->group(function () {
    Route::get('/', fn() => in_array(auth()->user()->role, ['Admin', 'IT']) ? app(UserManagementController::class)->index() : view('errors.noaccess'))->name('index');
    Route::get('/create', fn() => in_array(auth()->user()->role, ['Admin', 'IT']) ? app(UserManagementController::class)->create() : view('errors.noaccess'))->name('create');
    Route::post('/', fn() => in_array(auth()->user()->role, ['Admin', 'IT']) ? app(UserManagementController::class)->store(request()) : view('errors.noaccess'))->name('store');
    Route::get('/{id}/edit', fn($id) => in_array(auth()->user()->role, ['Admin', 'IT']) ? app(UserManagementController::class)->edit($id) : view('errors.noaccess'))->name('edit');
    Route::put('/{id}', fn($id) => in_array(auth()->user()->role, ['Admin', 'IT']) ? app(UserManagementController::class)->update(request(), $id) : view('errors.noaccess'))->name('update');
    Route::delete('/{id}', fn($id) => in_array(auth()->user()->role, ['Admin', 'IT']) ? app(UserManagementController::class)->destroy($id) : view('errors.noaccess'))->name('destroy');
});

// ===================== USER PROFILE (All authenticated users) =====================
Route::get('/profile', [UserController::class, 'profile'])->name('profile');
Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');

    }); 

// ===================== ROOT REDIRECT =====================
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Session check route for inactivity timeout
// Route::get('/check-session', [UserController::class, 'checkSession'])->name('check.session');

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

