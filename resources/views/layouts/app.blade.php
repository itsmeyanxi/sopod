<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SOPOD')</title>

    <!-- ✅ Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- ✅ Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .sidebar {
            transition: width 0.3s ease;
            overflow: hidden;
        }

        button.active .chevron {
            transform: rotate(180deg);
            transition: transform 0.3s ease;
        }

        .submenu {
            transition: all 0.3s ease;
        }

        .sidebar.collapsed .sidebar-text,
        .sidebar.collapsed .submenu {
            display: none !important;
        }

        .sidebar.collapsed .chevron {
            opacity: 0;
            width: 0;
            overflow: hidden;
        }

        /* Hide ALL content when sidebar is collapsed - show nothing */
        .sidebar.collapsed nav {
            display: none !important;
        }

        /* Also hide the header content */
        .sidebar.collapsed .sidebar-header {
            display: none !important;
        }

        /* Active state styling for sidebar links */
        .sidebar nav a.active {
            background-color: #1e3a5f;
            border-left: 3px solid #3b82f6;
            color: #93c5fd;
        }

        .sidebar nav .submenu a.active {
            background-color: #1e3a5f;
            border-left: 3px solid #60a5fa;
            color: #93c5fd;
            font-weight: 500;
        }

        .sidebar nav button.parent-active {
            background-color: #1f2937;
        }

        /* Hover states */
        .sidebar nav a:hover:not(.active) {
            background-color: #1f2937;
        }

        .sidebar nav .submenu a:hover:not(.active) {
            background-color: #1f2937;
        }

        /* Fix collapsed sidebar - make it completely invisible/minimal */
        .sidebar.collapsed {
            width: 0 !important;
            min-width: 0 !important;
            overflow: hidden;
            padding: 0;
        }

        /* Ensure everything inside is hidden when collapsed */
        .sidebar.collapsed * {
            display: none !important;
        }

        /* Mobile responsive - hide sidebar by default on small screens */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                left: -16rem;
                z-index: 1000;
            }

            .sidebar.mobile-open {
                left: 0;
            }

            .sidebar.collapsed {
                left: -4rem;
            }

            .sidebar.collapsed.mobile-open {
                left: 0;
            }

            /* Add overlay when sidebar is open on mobile */
            body.sidebar-overlay::before {
                content: '';
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 999;
            }
        }

    </style>
</head>
<body class="flex bg-gray-900 text-white overflow-x-hidden">

@auth
<!-- =================== SIDEBAR =================== -->
<div id="sidebar" class="sidebar bg-gray-800 text-gray-200 w-64 min-h-screen border-r border-gray-700 transition-all duration-300 ease-in-out md:relative">
    <div class="flex items-center justify-center p-4 sidebar-header">
        <h2 class="text-lg font-bold sidebar-text">NOMSUITE</h2>
        <span class="text-2xl hidden collapsed-icon">☰</span>
    </div>

    <nav class="mt-4 space-y-2">
        <!-- Dashboard -->
        <a href="{{ route('dashboard') }}" class="flex items-center space-x-2 px-4 py-2 hover:bg-gray-700">
            <span>📊</span>
            <span class="sidebar-text">Dashboard</span>
        </a>

        <!-- PO Dashboard -->
        @if(auth()->user()->navAccess('po_dashboard', fn() => auth()->user()->canAccessModule('po_dashboard')))
        <a href="{{ route('po_dashboard') }}" class="flex items-center space-x-2 px-4 py-2 hover:bg-gray-700">
            <span>💰</span>
            <span class="sidebar-text">PO Dashboard</span>
        </a>
        @endif

        @if(auth()->user()->navAccess('po_summary', fn() => auth()->user()->canAccessModule('po_dashboard')))
        <a href="{{ route('po_summary') }}" class="flex items-center space-x-2 px-4 py-2 hover:bg-gray-700">
            <span>📋</span>
            <span class="sidebar-text">PO Summary</span>
        </a>
        @endif

        <!-- AP Dashboard -->
        <a href="{{ route('ap_dashboard') }}" class="flex items-center space-x-2 px-4 py-2 hover:bg-gray-700">
            <span>📑</span>
            <span class="sidebar-text">AP Dashboard</span>
        </a>

        <!-- =================== RESTRICTED: Hide everything below for President/VP =================== -->
        @if(!auth()->user()->isPresidentOrVicePresident())

        <!-- =================== PO CREATOR ROLE (LIMITED ACCESS) =================== -->
        @if(auth()->user()->isPOCreatorRole())
            <div>
                <button class="flex items-center justify-between w-full px-4 py-2 hover:bg-gray-700">
                    <span class="flex items-center space-x-2">
                        <span>📦</span>
                        <span class="sidebar-text">Purchase Order</span>
                    </span>
                    <span class="chevron">▼</span>
                </button>
                <div class="submenu ml-8 space-y-1 hidden">
                    <a href="{{ route('purchase_orders.create') }}" class="block hover:underline">Create Purchase Order</a>
                </div>
            </div>
        @else
        <!-- =================== SALES ORDERS =================== -->
        @if(auth()->user()->canManageSalesOrders())
            <div>
                <button class="flex items-center justify-between w-full px-4 py-2 hover:bg-gray-700">
                    <span class="flex items-center space-x-2">
                        <span>📄</span>
                        <span class="sidebar-text">Sales Orders</span>
                    </span>
                    <span class="chevron">▼</span>
                </button>
                <div class="submenu ml-8 space-y-1 hidden">
                    @if(auth()->user()->navAccess('sales_orders.create', fn() => auth()->user()->canCreateSalesOrders()))
                        <a href="{{ route('sales_orders.create') }}" class="block hover:underline">Create Order</a>
                    @endif
                    @if(auth()->user()->navAccess('sales_orders.list', fn() => auth()->user()->canAccessModule('sales_orders')))
                        <a href="{{ route('sales_orders.index') }}" class="block hover:underline">Order List</a>
                    @endif
                    @if(auth()->user()->navAccess('sales_orders.accepted', fn() => true))
                        <a href="{{ route('sales_orders.accepted') }}" class="block hover:underline">Accepted Orders</a>
                    @endif
                </div>
            </div>
        @endif

        <!-- =================== CUSTOMERS =================== -->
        @if(auth()->user()->canManageCustomers())
            <div>
                <button class="flex items-center justify-between w-full px-4 py-2 hover:bg-gray-700">
                    <span class="flex items-center space-x-2">
                        <span>👥</span>
                        <span class="sidebar-text">Customers</span>
                    </span>
                    <span class="chevron">▼</span>
                </button>
                <div class="submenu ml-8 space-y-1 hidden">
                    @if(auth()->user()->navAccess('customers.create', fn() => auth()->user()->canAddCustomers()))
                        <a href="{{ route('customers.create') }}" class="block hover:underline">Add Customer</a>
                    @endif
                    @if(auth()->user()->navAccess('customers.list', fn() => true))
                        <a href="{{ route('customers.index') }}" class="block hover:underline">Customer List</a>
                    @endif
                </div>
            </div>
        @endif

        <!-- =================== SUPPLIERS =================== -->
        @php
            $canSeeSupplyChain = !auth()->user()->isCCRole() && (
                auth()->user()->canManageSuppliers()
                || auth()->user()->canManageSupplierReceivingReports()
                || auth()->user()->canManageIssueSlips()
                || auth()->user()->canManagePurchaseRequests()
                || auth()->user()->canManagePurchaseOrders()
                || auth()->user()->canManageRequestForPayments()
            );
        @endphp
        @if($canSeeSupplyChain)
            <div>
                <button class="flex items-center justify-between w-full px-4 py-2 hover:bg-gray-700">
                    <span class="flex items-center space-x-2">
                        <span>🏢</span>
                        <span class="sidebar-text">Supply Chain</span>
                    </span>
                    <span class="chevron">▼</span>
                </button>
                <div class="submenu ml-8 space-y-1 hidden">
                    @if(auth()->user()->canManageSuppliers())
                        @if(auth()->user()->navAccess('supply_chain.add_supplier', fn() => true))
                            <a href="{{ route('suppliers.create') }}" class="block hover:underline">Add Supplier</a>
                        @endif
                        @if(auth()->user()->navAccess('supply_chain.supplier_list', fn() => true))
                            <a href="{{ route('suppliers.index') }}" class="block hover:underline">Supplier List</a>
                        @endif
                    @endif
                    @if(auth()->user()->canManageSupplierReceivingReports())
                        @if(auth()->user()->navAccess('supply_chain.receiving_reports', fn() => true))
                            <a href="{{ route('supplier_receiving_reports.index') }}" class="block hover:underline">Receiving Reports</a>
                        @endif
                    @endif
                    @if(auth()->user()->canManageIssueSlips())
                        @if(auth()->user()->navAccess('supply_chain.issue_slips', fn() => true))
                            <a href="{{ route('issue_slips.index') }}" class="block hover:underline">Issue Slips</a>
                        @endif
                    @endif
                    <!-- SCM Role - Supply Chain Module Links -->
                    @if(auth()->user()->hasRole(['SCM']))
                        <hr class="my-2 border-gray-600">
                    @endif
                    @if(auth()->user()->canManagePurchaseRequests())
                        @if(auth()->user()->navAccess('supply_chain.purchase_requests', fn() => true))
                            <a href="{{ route('purchase_requests.index') }}" class="block hover:underline">Purchase Request (PR)</a>
                        @endif
                    @endif
                    @if(auth()->user()->canManagePurchaseOrders())
                        @if(auth()->user()->navAccess('supply_chain.purchase_orders', fn() => true))
                            <a href="{{ route('purchase_orders.index') }}" class="block hover:underline">Purchase Order (PO)</a>
                        @endif
                    @endif
                    @if(auth()->user()->canManageRequestForPayments())
                        @if(auth()->user()->navAccess('supply_chain.rfp', fn() => true))
                            <a href="{{ route('request_for_payments.index') }}" class="block hover:underline">Request For Payment (RFP)</a>
                        @endif
                    @endif
                    @if(auth()->user()->canAccessModule('non_trade_items'))
                        @if(auth()->user()->navAccess('supply_chain.non_trade_items', fn() => true))
                            <a href="{{ route('non_trade_items.index') }}" class="block hover:underline">Non-Trade Items Library</a>
                        @endif
                    @endif
                    @if(auth()->user()->canAccessModule('trade_items'))
                        @if(auth()->user()->navAccess('supply_chain.trade_items', fn() => true))
                            <a href="{{ route('trade_items.index') }}" class="block hover:underline">Trade Items Library</a>
                        @endif
                    @endif
                </div>
            </div>
        @endif

        <!-- =================== STORAGE / WAREHOUSE =================== -->
        @if(auth()->user()->canAccessModule('warehouse'))
        <div>
            <button class="flex items-center justify-between w-full px-4 py-2 hover:bg-gray-700">
                <span class="flex items-center space-x-2">
                    <span>🏭</span>
                    <span class="sidebar-text">Storage / Warehouse</span>
                </span>
                <span class="chevron">▼</span>
            </button>
            <div class="submenu ml-8 space-y-1 hidden">
                <a href="{{ route('warehouses.create') }}" class="block hover:underline">Add Warehouse</a>
                <a href="{{ route('warehouses.index') }}" class="block hover:underline">Warehouse List</a>
                <hr class="my-2 border-gray-600">
                <a href="{{ route('storages.create') }}" class="block hover:underline text-sm">Add Storage</a>
                <a href="{{ route('storages.index') }}" class="block hover:underline text-sm">Storage List</a>
            </div>
        </div>
        @endif

        <!-- =================== BOM =================== -->
        @if(auth()->user()->canAccessModule('inhouse_bom') || auth()->user()->canAccessModule('daily_feed_usage'))
        <div>
            <button class="flex items-center justify-between w-full px-4 py-2 hover:bg-gray-700">
                <span class="flex items-center space-x-2">
                    <span>🐔</span>
                    <span class="sidebar-text">In-House BOM</span>
                </span>
                <span class="chevron">▼</span>
            </button>
            <div class="submenu ml-8 space-y-1 hidden">
                @if(auth()->user()->canAccessModule('inhouse_bom'))
                    <a href="{{ route('inhouse_bom.index') }}" class="block hover:underline">BOM List</a>
                    <a href="{{ route('inhouse_bom.create') }}" class="block hover:underline">New BOM</a>
                @endif
                @if(auth()->user()->canAccessModule('daily_feed_usage'))
                    <a href="{{ route('daily_feed_usage.index') }}" class="block hover:underline">Daily Feed Usage</a>
                @endif
            </div>
        </div>
        @endif

        <!-- =================== BANKING =================== -->
        @if(auth()->user()->canAccessModule('treasury') || auth()->user()->canAccessModule('cv') || auth()->user()->canAccessCollections() || auth()->user()->canAccessModule('loans'))
        <div>
            <button class="flex items-center justify-between w-full px-4 py-2 hover:bg-gray-700">
                <span class="flex items-center space-x-2">
                    <span>🏦</span>
                    <span class="sidebar-text">Treasury</span>
                </span>
                <span class="chevron">▼</span>
            </button>
            <div class="submenu ml-8 space-y-1 hidden">
                @if(auth()->user()->canAccessCollections())
                    <a href="{{ route('payments.entry') }}" class="block hover:underline">Cash Receipts</a>
                @endif
                @if(auth()->user()->canAccessModule('cv'))
                    <a href="{{ route('check_vouchers.index') }}" class="block hover:underline">Cash Disbursements</a>
                @endif
                @if(auth()->user()->canAccessModule('loans'))
                    <a href="{{ route('loans.index') }}" class="block hover:underline">Loans</a>
                @endif
                @if(auth()->user()->canAccessModule('treasury'))
                    <a href="{{ route('treasury.confirmation') }}" class="block hover:underline">Payment Confirmation</a>
                    <a href="{{ route('treasury.summary') }}" class="block hover:underline">Bank</a>
                    <a href="{{ route('treasury.banks', 'peso') }}" class="block hover:underline">Peso Accounts</a>
                    <a href="{{ route('treasury.banks', 'dollar') }}" class="block hover:underline">Dollar Accounts</a>
                @endif
            </div>
        </div>
        @endif

        <!-- =================== ITEMS =================== -->
        @if(auth()->user()->canManageItems())
            <div>
                <button class="flex items-center justify-between w-full px-4 py-2 hover:bg-gray-700">
                    <span class="flex items-center space-x-2">
                        <span>📦</span>
                        <span class="sidebar-text">Items</span>
                    </span>
                    <span class="chevron">▼</span>
                </button>
                <div class="submenu ml-8 space-y-1 hidden">
                    @if(auth()->user()->navAccess('items.create', fn() => auth()->user()->canAddItems()))
                        <a href="{{ route('items.create') }}" class="block hover:underline">Add Item</a>
                    @endif
                    @if(auth()->user()->navAccess('items.list', fn() => true))
                        <a href="{{ route('items.index') }}" class="block hover:underline">Item List</a>
                    @endif
                </div>
            </div>
        @endif

        <!-- =================== DELIVERIES =================== -->
        @if(auth()->user()->canManageDeliveries())
            <div>
                <button class="flex items-center justify-between w-full px-4 py-2 hover:bg-gray-700">
                    <span class="flex items-center space-x-2">
                        <span>🚚</span>
                        <span class="sidebar-text">Deliveries</span>
                    </span>
                    <span class="chevron">▼</span>
                </button>
                <div class="submenu ml-8 space-y-1 hidden">
                    @if(auth()->user()->navAccess('deliveries.view', fn() => auth()->user()->canCreateDeliveries()))
                        <a href="{{ route('deliveries.deliveries') }}" class="block hover:underline">View Delivery</a>
                    @endif
                    @if(auth()->user()->navAccess('deliveries.list', fn() => true))
                        <a href="{{ route('deliveries.index') }}" class="block hover:underline">Delivery List</a>
                    @endif
                </div>
            </div>
        @endif

        <!-- =================== RECEIVING REPORTS (BACKLOAD) =================== -->
        @if(auth()->user()->canAccessReceivingReports())
            <div>
                <button class="flex items-center justify-between w-full px-4 py-2 hover:bg-gray-700">
                    <span class="flex items-center space-x-2">
                        <span>🔄</span>
                        <span class="sidebar-text">Receiving Reports</span>
                    </span>
                    <span class="chevron">▼</span>
                </button>
                <div class="submenu ml-8 space-y-1 hidden">
                    <a href="{{ route('receiving-reports.index') }}" class="block hover:underline">RR List</a>
                </div>
            </div>
        @endif

        <!-- =================== PURCHASE ORDER =================== -->
        @php
            $canSeeFinance = !auth()->user()->hasRole(['SCM']) && (
                auth()->user()->canManagePurchaseRequests()
                || auth()->user()->canManagePurchaseOrders()
                || auth()->user()->canManageRequestForPayments()
                || auth()->user()->canAccessModule('apv')
                || auth()->user()->canAccessModule('cv')
                || auth()->user()->canAccessModule('cash_advance')
                || auth()->user()->canAccessModule('liquidation')
                || auth()->user()->canAccessModule('reimbursement')
                || auth()->user()->canAccessModule('non_trade_items')
                || auth()->user()->canAccessModule('trade_items')
                || auth()->user()->canAccessModule('currency_rates')
            );
        @endphp
        @if($canSeeFinance)
            <div>
                <button class="flex items-center justify-between w-full px-4 py-2 hover:bg-gray-700">
                    <span class="flex items-center space-x-2">
                        <span>💰</span>
                        <span class="sidebar-text">Finance</span>
                    </span>
                    <span class="chevron">▼</span>
                </button>
                <div class="submenu ml-8 space-y-1 hidden">
                    @if(auth()->user()->canManagePurchaseRequests())
                        <a href="{{ route('purchase_requests.index') }}" class="block hover:underline">Purchase Request (PR)</a>
                    @endif
                    @if(auth()->user()->canManagePurchaseOrders())
                        <a href="{{ route('purchase_orders.index') }}" class="block hover:underline">Purchase Order (PO)</a>
                    @endif
                    @if(auth()->user()->canAccessModule('non_trade_items'))
                        <a href="{{ route('non_trade_items.index') }}" class="block hover:underline">Non-Trade Items Library</a>
                    @endif
                    @if(auth()->user()->canAccessModule('trade_items'))
                        <a href="{{ route('trade_items.index') }}" class="block hover:underline">Trade Items Library</a>
                    @endif
                    @if(auth()->user()->canManageRequestForPayments())
                        <a href="{{ route('request_for_payments.index') }}" class="block hover:underline">Request For Payment (RFP)</a>
                    @endif
                    @if(auth()->user()->canAccessModule('apv'))
                        <a href="{{ route('accounts_payable_invoices.index') }}" class="block hover:underline">Account Payable Invoice (APV)</a>
                    @endif
                    <hr class="my-2 border-gray-600">

                    @if(auth()->user()->canAccessModule('cash_advance'))
                        <a href="{{ route('cash_advance_requests.index') }}" class="block hover:underline">Cash Advance Request (CAR)</a>
                    @endif
                    @if(auth()->user()->canAccessModule('liquidation'))
                        <a href="{{ route('liquidation_forms.index') }}" class="block hover:underline">Liquidation Form (LIQ)</a>
                    @endif
                    @if(auth()->user()->canAccessReimbursementForms())
                        <a href="{{ route('reimbursement_forms.index') }}" class="block hover:underline">Reimbursement Form (RI)</a>
                    @endif
                    @if(auth()->user()->canManagePurchaseOrders())
                        <a href="{{ route('po_records.index') }}" class="block hover:underline">PO Records</a>
                    @endif
                    @if(auth()->user()->canAccessModule('currency_rates'))
                        <a href="{{ route('currencies.index') }}" class="block hover:underline">Currency Rates</a>
                    @endif
                </div>
            </div>
        @endif
        @endif <!-- Close PO Creator role check -->

        <!-- =================== CREDITS & COLLECTION =================== -->
        @if(auth()->user()->canAccessAgingReports() || auth()->user()->canAccessCollections() || auth()->user()->canAccessModule('soa') || auth()->user()->canAccessModule('delivery_counter_dates') || auth()->user()->canAccessModule('counter_date_approvals'))
            <div>
                <button class="flex items-center justify-between w-full px-4 py-2 hover:bg-gray-700">
                    <span class="flex items-center space-x-2">
                        <span>📅</span>
                        <span class="sidebar-text">Credits & Collection</span>
                    </span>
                    <span class="chevron">▼</span>
                </button>
                <div class="submenu ml-8 space-y-1 hidden">
                    @if(auth()->user()->canAccessAgingReports() && auth()->user()->navAccess('aging.view', fn() => true))
                        <a href="{{ route('aging_reports.view') }}" class="block hover:underline">Aging Reports View</a>
                    @endif
                    @if(auth()->user()->canAccessARDashboard())
                        <a href="{{ route('invoices.screen') }}" class="block hover:underline">AR Dashboard</a>
                    @endif
                    @if(auth()->user()->canAccessCollections() && auth()->user()->navAccess('payments', fn() => true))
                        <a href="{{ route('payments.entry') }}" class="block hover:underline">Collection</a>
                    @endif
                    @if(auth()->user()->canApprovePaymentEditRequests() || auth()->user()->canRequestPaymentEdit())
                        <a href="{{ route('payments.editRequests') }}" class="block hover:underline">Edit Requests</a>
                    @endif
                    @if(auth()->user()->canAccessModule('soa'))
                        <a href="{{ route('soa.index') }}" class="block hover:underline">Statement of Accounts</a>
                    @endif
                    @if(auth()->user()->canAccessModule('delivery_counter_dates'))
                        <a href="{{ route('delivery_counter_dates.index') }}" class="block hover:underline">Delivery Counter Dates</a>
                    @endif
                    @if(auth()->user()->canAccessModule('counter_date_approvals'))
                        <a href="{{ route('counter_date_approvals.index') }}" class="block hover:underline">Counter Date Approval</a>
                    @endif
                    @if(auth()->user()->canAccessAgingReports() && auth()->user()->navAccess('aging.adjustments', fn() => true))
                        <a href="{{ route('ar_adjustments.index') }}" class="block hover:underline">AR Adjustments</a>
                    @endif
                </div>
            </div>
        @endif

        <!-- =================== ACCOUNTING =================== -->
        @if(auth()->user()->canAccessModule('gl_accounts') || auth()->user()->canAccessModule('fixed_assets') || auth()->user()->canAccessModule('journal_vouchers') || auth()->user()->canAccessModule('depreciation_runs') || auth()->user()->canAccessModule('asset_classes'))
            <div>
                <button class="flex items-center justify-between w-full px-4 py-2 hover:bg-gray-700">
                    <span class="flex items-center space-x-2">
                        <span>📒</span>
                        <span class="sidebar-text">Accounting</span>
                    </span>
                    <span class="chevron">▼</span>
                </button>
                <div class="submenu ml-8 space-y-1 hidden">
                    @if(auth()->user()->canAccessModule('gl_accounts'))
                        <a href="{{ route('gl_accounts.index') }}" class="block hover:underline">Chart of Accounts</a>
                    @endif
                    @if(auth()->user()->canAccessModule('asset_classes'))
                        <a href="{{ route('asset_classes.index') }}" class="block hover:underline">Asset Classes</a>
                    @endif
                    @if(auth()->user()->canAccessModule('fixed_assets'))
                        <a href="{{ route('fixed_assets.index') }}" class="block hover:underline">Fixed Asset Capitalization</a>
                        <a href="{{ route('fixed_assets.summary') }}" class="block hover:underline">Lapsing Schedule</a>
                        <a href="{{ route('disposals.index') }}" class="block hover:underline">Disposals</a>
                        <a href="{{ route('fixed_assets.report_depreciation') }}" class="block hover:underline text-xs pl-2">Depreciation Report</a>
                        <a href="{{ route('fixed_assets.report_transactions') }}" class="block hover:underline text-xs pl-2">Asset Transaction Summary</a>
                        <a href="{{ route('fixed_assets.report_cost_center') }}" class="block hover:underline text-xs pl-2">Assets by Cost Center</a>
                    @endif
                    @if(auth()->user()->canAccessModule('depreciation_runs'))
                        <a href="{{ route('depreciation_runs.index') }}" class="block hover:underline">Depreciation Run</a>
                    @endif
                    @if(auth()->user()->canAccessModule('journal_vouchers'))
                        <a href="{{ route('journal_vouchers.index') }}" class="block hover:underline">Journal Vouchers</a>
                    @endif
                </div>
            </div>
        @endif

        <!-- =================== CHANGE LOG =================== -->
        @if(auth()->user()->canAccessChangelog())
            <div>
                <button class="flex items-center justify-between w-full px-4 py-2 hover:bg-gray-700">
                    <span class="flex items-center space-x-2">
                        <span>📝</span>
                        <span class="sidebar-text">Change Log</span>
                    </span>
                    <span class="chevron">▼</span>
                </button>
                <div class="submenu ml-8 space-y-1 hidden">
                    <a href="{{ route('changelog.index') }}" class="block hover:underline">View Changes</a>
                </div>
            </div>
        @endif

        <!-- =================== SALES ANALYTICS =================== -->
        @if(auth()->user()->canAccessSalesAnalytics())
            <a href="{{ route('sales.dashboard') }}" class="flex items-center space-x-2 px-4 py-2 hover:bg-gray-700">
                <span>📈</span>
                <span class="sidebar-text">Sales Analytics</span>
            </a>
        @endif

        <!-- =================== RECORDS =================== -->
        @if(auth()->user()->canAccessRecords())
            <a href="{{ route('records.index') }}" class="flex items-center space-x-2 px-4 py-2 hover:bg-gray-700">
                <span>📁</span>
                <span class="sidebar-text">Records</span>
            </a>
        @endif

        <!-- =================== EXCEL IMPORT =================== -->
        @if(auth()->user()->canAccessExcelImport())
            <a href="{{ route('excel.import') }}" class="flex items-center space-x-2 px-4 py-2 hover:bg-gray-700">
                <span>📊</span>
                <span class="sidebar-text">Excel Import</span>
            </a>
        @endif

        <!-- =================== RECORD LOCK =================== -->
        @if(auth()->user()->canAccessModule('record_lock'))
            <a href="{{ route('lock.index') }}" class="flex items-center space-x-2 px-4 py-2 hover:bg-gray-700">
                <span>🔒</span>
                <span class="sidebar-text">Record Lock</span>
            </a>
        @endif
        @endif <!-- Close President/VP restriction -->
    </nav>
</div>

<!-- =================== MAIN CONTENT =================== -->
<div class="flex-1 min-h-screen bg-gray-700 flex flex-col w-full md:w-auto">

    <!-- Top Bar -->
    <div class="bg-gray-800 shadow border-b border-gray-700 p-4 flex items-center justify-between text-white">
        <div class="flex items-center space-x-2 md:space-x-4">
            <button id="toggle-btn" class="text-gray-300 text-xl">☰</button>
            <h1 class="text-lg md:text-xl font-semibold truncate">@yield('title', 'Dashboard')</h1>
        </div>

        <div class="flex items-center space-x-2 md:space-x-6 relative">
            <!-- Create New Dropdown -->
            @if(auth()->user()->canCreateSalesOrders() || auth()->user()->canAddCustomers() || auth()->user()->canAddItems() || auth()->user()->canManageUsers())
                <div class="relative hidden md:block">
                    <button id="createNewButton" class="flex items-center space-x-1 hover:text-gray-300">
                        <span class="text-sm md:text-base">Create New</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div id="createNewDropdown" class="hidden absolute right-0 mt-2 w-40 bg-gray-800 text-black border rounded shadow-lg z-50">
                        @if(auth()->user()->canCreateSalesOrders())
                            <a href="{{ route('sales_orders.create') }}" class="block px-4 py-2 hover:bg-gray-700">Sales Order</a>
                        @endif
                        @if(auth()->user()->canAddCustomers())
                            <a href="{{ route('customers.create') }}" class="block px-4 py-2 hover:bg-gray-700">Customer</a>
                        @endif
                        @if(auth()->user()->canAddItems())
                            <a href="{{ route('items.create') }}" class="block px-4 py-2 hover:bg-gray-700">Item</a>
                        @endif
                        @if(auth()->user()->canManageUsers())
                            <a href="{{ route('rbac.index') }}" class="block px-4 py-2 hover:bg-gray-700">User</a>
                        @endif
                    </div>
                </div>
            @endif

        <!-- User Dropdown -->
            <div class="relative">
                <button id="userDropdownButton" class="flex items-center space-x-1 md:space-x-2 focus:outline-none hover:text-gray-300">
                    <span class="text-sm md:text-base truncate max-w-[100px] md:max-w-none">{{ Auth::user()->name }}</span>
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="userDropdown" class="hidden absolute right-0 mt-2 w-48 bg-gray-800 text-black border rounded shadow-lg z-50">
                    <!-- User Info - Now Clickable -->
                    <a href="{{ route('profile') }}" class="block px-4 py-2 border-b bg-gray-900 hover:bg-gray-700">
                        <p class="text-sm font-semibold">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-gray-300">{{ Auth::user()->roles_display }}</p>
                    </a>

                    <!-- User & Access Management (IT Only) -->
                    @if(Auth::user()->canManageUsers())
                    <a href="{{ route('admin.users.index') }}" class="block px-4 py-2 hover:bg-gray-700">
                        <i class="fas fa-users mr-2"></i>User Management
                    </a>
                    <a href="{{ route('rbac.index') }}" class="block px-4 py-2 hover:bg-gray-700">
                        <i class="fas fa-users-gear mr-2"></i>RBAC Management
                    </a>
                    @endif

                    <!-- Reports -->
                    <a href="http://mtcresolveit.meatplus.ph/public/ticket/index.php?entity=1" target="_blank" class="block px-4 py-2 hover:bg-gray-700">
                        <i class="fas fa-file-alt mr-2"></i>Reports
                    </a>

                    <!-- Logout -->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2 hover:bg-gray-700">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Content -->
    <div class="bg-gray-900 p-4 md:p-6 flex-1 text-white overflow-x-auto">
        @yield('content')
    </div>
</div>
@endauth

@guest
<!-- Simple Layout for Login/Register -->
<div class="flex-1 flex items-center justify-center bg-gray-900 text-white min-h-screen">
    @yield('content')
</div>
@endguest

<!-- =================== JS =================== -->
<script>
    const toggleBtn = document.getElementById("toggle-btn");
    const sidebar = document.getElementById("sidebar");
    const submenuButtons = document.querySelectorAll("#sidebar button");
    const sidebarTexts = document.querySelectorAll(".sidebar-text");
    const currentUrl = window.location.pathname;

    // ✅ Sidebar Open / Close Toggle with Mobile Support
    toggleBtn.addEventListener("click", () => {
        const isMobile = window.innerWidth <= 768;

        if (isMobile) {
            sidebar.classList.toggle("mobile-open");
            document.body.classList.toggle("sidebar-overlay");
        } else {
            sidebar.classList.toggle("collapsed");
            sidebar.classList.toggle("w-64");
            sidebar.classList.toggle("w-16");
            // Hide/Show Text
            sidebarTexts.forEach(text => text.classList.toggle("hidden"));
        }
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener("click", (e) => {
        const isMobile = window.innerWidth <= 768;
        if (isMobile && sidebar.classList.contains("mobile-open")) {
            if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                sidebar.classList.remove("mobile-open");
                document.body.classList.remove("sidebar-overlay");
            }
        }
    });

    // ✅ Dropdown Toggle
    submenuButtons.forEach(button => {
        button.addEventListener("click", () => {
            const submenu = button.nextElementSibling;
            submenu.classList.toggle("hidden");
            button.classList.toggle("active");
        });
    });

    // ✅ Create New & User Dropdowns
    const dropdowns = [
        { btn: "userDropdownButton", menu: "userDropdown" },
        { btn: "createNewButton", menu: "createNewDropdown" }
    ];

    dropdowns.forEach(({ btn, menu }) => {
        const button = document.getElementById(btn);
        const dropdown = document.getElementById(menu);

        if (button && dropdown) {
            button.addEventListener("click", (e) => {
                e.stopPropagation();

                // CLOSE ALL DROPDOWNS FIRST ✅
                dropdowns.forEach(d => {
                    const otherMenu = document.getElementById(d.menu);
                    if (otherMenu && otherMenu !== dropdown) {
                        otherMenu.classList.add("hidden");
                    }
                });

                // THEN TOGGLE THE CLICKED ONE ✅
                dropdown.classList.toggle("hidden");
            });

            document.addEventListener("click", (e) => {
                if (!dropdown.contains(e.target) && !button.contains(e.target)) {
                    dropdown.classList.add("hidden");
                }
            });
        }
    });

    // ✅ HIGHLIGHT ACTIVE PAGE IN SIDEBAR
    document.addEventListener("DOMContentLoaded", () => {
        // Get all sidebar links (excluding submenu toggle buttons)
        const sidebarLinks = document.querySelectorAll("#sidebar nav a");
        let activeFound = false;
        let bestMatch = null;
        let bestMatchLength = 0;

        sidebarLinks.forEach(link => {
            const linkUrl = new URL(link.href).pathname;

            // Exact match gets highest priority
            if (linkUrl === currentUrl) {
                if (bestMatch) {
                    bestMatch.classList.remove("active");
                }
                bestMatch = link;
                bestMatchLength = linkUrl.length;
                activeFound = true;
            }
            // For partial matches, prefer the longest/most specific match
            else if (currentUrl.startsWith(linkUrl) && linkUrl !== '/' && linkUrl !== '/dashboard') {
                if (linkUrl.length > bestMatchLength) {
                    if (bestMatch) {
                        bestMatch.classList.remove("active");
                    }
                    bestMatch = link;
                    bestMatchLength = linkUrl.length;
                    activeFound = true;
                }
            }
        });

        // Apply the best match
        if (bestMatch) {
            bestMatch.classList.add("active");

            // If this link is inside a submenu, expand the parent and mark it
            const parentSubmenu = bestMatch.closest(".submenu");
            if (parentSubmenu) {
                parentSubmenu.classList.remove("hidden");
                const parentButton = parentSubmenu.previousElementSibling;
                if (parentButton && parentButton.tagName === "BUTTON") {
                    parentButton.classList.add("active", "parent-active");
                }
            }
        }

        // If no active link found and we're on dashboard
        if (!activeFound && (currentUrl === '/dashboard' || currentUrl === '/')) {
            const dashboardLink = document.querySelector('a[href*="dashboard"]');
            if (dashboardLink) {
                dashboardLink.classList.add("active");
            }
        }
    });

    // Handle window resize
    window.addEventListener("resize", () => {
        const isMobile = window.innerWidth <= 768;
        if (!isMobile) {
            sidebar.classList.remove("mobile-open");
            document.body.classList.remove("sidebar-overlay");
        }
    });

</script>

</body>
</html>