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

        /* Active state styling for sidebar links - matches gray-700 hover */
        .sidebar nav a.active {
            background-color: #374151;
            border-left: 3px solid #60a5fa;
        }

        .sidebar nav .submenu a.active {
            background-color: #4b5563;
            border-left: 3px solid #93c5fd;
            font-weight: 500;
        }

        .sidebar nav button.parent-active {
            background-color: #374151;
        }

        /* Hover states */
        .sidebar nav a:hover:not(.active) {
            background-color: #374151;
        }

        .sidebar nav .submenu a:hover:not(.active) {
            background-color: #4b5563;
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
<div id="sidebar" class="sidebar bg-gray-900 text-white w-64 min-h-screen transition-all duration-300 ease-in-out md:relative">
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
        @if(auth()->user()->canManagePurchaseRequests() || auth()->user()->canManagePurchaseOrders() || auth()->user()->canManageRequestForPayments() || auth()->user()->hasRole(['Accounting_Creator', 'Accounting_Approver']))
        <a href="{{ route('po_dashboard') }}" class="flex items-center space-x-2 px-4 py-2 hover:bg-gray-700">
            <span>💰</span>
            <span class="sidebar-text">PO Dashboard</span>
        </a>
        @endif

        @if(auth()->user()->canManagePurchaseRequests() || auth()->user()->canManagePurchaseOrders() || auth()->user()->canManageRequestForPayments() || auth()->user()->hasRole(['Accounting_Creator', 'Accounting_Approver']))
        <a href="{{ route('po_summary') }}" class="flex items-center space-x-2 px-4 py-2 hover:bg-gray-700">
            <span>📋</span>
            <span class="sidebar-text">PO Summary</span>
        </a>
        @endif

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
                    @if(auth()->user()->canCreateSalesOrders())
                        <a href="{{ route('sales_orders.create') }}" class="block hover:underline">Create Order</a>
                    @endif
                    @if(!in_array(auth()->user()->role, ['Delivery_Approver', 'Delivery_Creator']))
                        <a href="{{ route('sales_orders.index') }}" class="block hover:underline">Order List</a>
                    @endif
                    <a href="{{ route('sales_orders.accepted') }}" class="block hover:underline">Accepted Orders</a>
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
                    @if(auth()->user()->canAddCustomers())
                        <a href="{{ route('customers.create') }}" class="block hover:underline">Add Customer</a>
                    @endif
                    <a href="{{ route('customers.index') }}" class="block hover:underline">Customer List</a>
                </div>
            </div>
        @endif

        <!-- =================== SUPPLIERS =================== -->
        @if(auth()->user()->canManageSuppliers())
            <div>
                <button class="flex items-center justify-between w-full px-4 py-2 hover:bg-gray-700">
                    <span class="flex items-center space-x-2">
                        <span>🏢</span>
                        <span class="sidebar-text">Supply Chain</span>
                    </span>
                    <span class="chevron">▼</span>
                </button>
                <div class="submenu ml-8 space-y-1 hidden">
                    <a href="{{ route('suppliers.create') }}" class="block hover:underline">Add Supplier</a>
                    <a href="{{ route('suppliers.index') }}" class="block hover:underline">Supplier List</a>
                    @if(auth()->user()->canManageSupplierReceivingReports())
                        <a href="{{ route('supplier_receiving_reports.index') }}" class="block hover:underline">Receiving Reports</a>
                    @endif
                    @if(auth()->user()->canManageIssueSlips())
                        <a href="{{ route('issue_slips.index') }}" class="block hover:underline">Issue Slips</a>
                    @endif
                    <!-- SCM Role - Supply Chain Module Links -->
                    @if(auth()->user()->hasRole(['SCM']))
                        <hr class="my-2 border-gray-600">
                        @if(auth()->user()->canManagePurchaseRequests())
                            <a href="{{ route('purchase_requests.index') }}" class="block hover:underline">Purchase Request (PR)</a>
                        @endif
                        @if(auth()->user()->canManagePurchaseOrders())
                            <a href="{{ route('purchase_orders.index') }}" class="block hover:underline">Purchase Order (PO)</a>
                        @endif
                        @if(auth()->user()->canManageRequestForPayments())
                            <a href="{{ route('request_for_payments.index') }}" class="block hover:underline">Request For Payment (RFP)</a>
                        @endif
                        <a href="{{ route('non_trade_items.index') }}" class="block hover:underline">Non-Trade Items Library</a>
                        <a href="{{ route('trade_items.index') }}" class="block hover:underline">Trade Items Library</a>
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
                    @if(auth()->user()->canAddItems())
                        <a href="{{ route('items.create') }}" class="block hover:underline">Add Item</a>
                    @endif
                    <a href="{{ route('items.index') }}" class="block hover:underline">Item List</a>
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
                    @if(auth()->user()->canCreateDeliveries())
                        <a href="{{ route('deliveries.deliveries') }}" class="block hover:underline">View Delivery</a>
                    @endif
                    <a href="{{ route('deliveries.index') }}" class="block hover:underline">Delivery List</a>
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
        @if((auth()->user()->canManagePurchaseRequests() || auth()->user()->canManagePurchaseOrders() || auth()->user()->canManageRequestForPayments() || auth()->user()->hasRole(['Accounting_Creator', 'Accounting_Approver'])) && !auth()->user()->hasRole(['SCM']))
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

                    @if(auth()->user()->hasRole(['Admin', 'IT', 'Purchasing', 'SCM']))
                        <a href="{{ route('non_trade_items.index') }}" class="block hover:underline">Non-Trade Items Library</a>
                        <a href="{{ route('trade_items.index') }}" class="block hover:underline">Trade Items Library</a>
                    @endif

                    @if(auth()->user()->canManageRequestForPayments())
                        <a href="{{ route('request_for_payments.index') }}" class="block hover:underline">Request For Payment (RFP)</a>
                    @endif

                    @if(auth()->user()->hasRole(['Admin', 'IT', 'Accounting_Creator', 'Accounting_Approver']))
                        <a href="{{ route('accounts_payable_invoices.index') }}" class="block hover:underline">Account Payable Invoice (APV)</a>
                        <a href="{{ route('check_vouchers.index') }}" class="block hover:underline">Check Voucher (CV)</a>
                    @endif

                    <a href="{{ route('cash_advance_requests.index') }}" class="block hover:underline">Cash Advance Request (CAR)</a>
                    <a href="{{ route('liquidation_forms.index') }}" class="block hover:underline">Liquidation Form (LIQ)</a>
                    @if(auth()->user()->canAccessReimbursementForms())
                        <a href="{{ route('reimbursement_forms.index') }}" class="block hover:underline">Reimbursement Form (RI)</a>
                    @endif

                    <a href="{{ route('po_records.index') }}" class="block hover:underline">PO Records</a>

                    @if(auth()->user()->hasRole(['Admin', 'IT', 'Purchasing', 'Procurement_Approver']))
                        <a href="{{ route('currencies.index') }}" class="block hover:underline">Currency Rates</a>
                    @endif
                </div>
            </div>
        @endif

        <!-- =================== AGING REPORT =================== -->
        @if(auth()->user()->canAccessAgingReports())
            <div>
                <button class="flex items-center justify-between w-full px-4 py-2 hover:bg-gray-700">
                    <span class="flex items-center space-x-2">
                        <span>📅</span>
                        <span class="sidebar-text">Aging Report</span>
                    </span>
                    <span class="chevron">▼</span>
                </button>
                <div class="submenu ml-8 space-y-1 hidden">
                    <a href="{{ route('aging_reports.view') }}" class="block hover:underline">Aging Reports View</a>
                    @if(auth()->user()->canAccessARDashboard())
                        <a href="{{ route('invoices.screen') }}" class="block hover:underline">AR Dashboard</a>
                    @endif
                    @if(auth()->user()->canAccessPayments())
                        <a href="{{ route('payments.entry') }}" class="block hover:underline">Collection</a>
                    @endif
                    <a href="{{ route('ar_adjustments.index') }}" class="block hover:underline">AR Adjustments</a>
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
        @if(in_array(auth()->user()->role, ['Admin', 'IT']))
            <a href="{{ route('lock.index') }}" class="flex items-center space-x-2 px-4 py-2 hover:bg-gray-700">
                <span>🔒</span>
                <span class="sidebar-text">Record Lock</span>
            </a>
        @endif
    </nav>
</div>

<!-- =================== MAIN CONTENT =================== -->
<div class="flex-1 min-h-screen bg-gray-100 flex flex-col w-full md:w-auto">

    <!-- Top Bar -->
    <div class="bg-gray-800 shadow p-4 flex items-center justify-between text-white">
        <div class="flex items-center space-x-2 md:space-x-4">
            <button id="toggle-btn" class="text-white text-xl">☰</button>
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
                    <div id="createNewDropdown" class="hidden absolute right-0 mt-2 w-40 bg-white text-black border rounded shadow-lg z-50">
                        @if(auth()->user()->canCreateSalesOrders())
                            <a href="{{ route('sales_orders.create') }}" class="block px-4 py-2 hover:bg-gray-100">Sales Order</a>
                        @endif
                        @if(auth()->user()->canAddCustomers())
                            <a href="{{ route('customers.create') }}" class="block px-4 py-2 hover:bg-gray-100">Customer</a>
                        @endif
                        @if(auth()->user()->canAddItems())
                            <a href="{{ route('items.create') }}" class="block px-4 py-2 hover:bg-gray-100">Item</a>
                        @endif
                        @if(auth()->user()->canManageUsers())
                            <a href="{{ route('admin.users.create') }}" class="block px-4 py-2 hover:bg-gray-100">User</a>
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
                <div id="userDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white text-black border rounded shadow-lg z-50">
                    <!-- User Info - Now Clickable -->
                    <a href="{{ route('profile') }}" class="block px-4 py-2 border-b bg-gray-50 hover:bg-gray-100">
                        <p class="text-sm font-semibold">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-gray-600">{{ Auth::user()->roles_display }}</p>
                    </a>

                    <!-- User List (IT Only) -->
                    @if(Auth::user()->canManageUsers())
                    <a href="{{ route('admin.users.index') }}" class="block px-4 py-2 hover:bg-gray-100">
                        <i class="fas fa-users mr-2"></i>User List
                    </a>
                    @endif

                    <!-- Reports -->
                    <a href="http://mtcresolveit.meatplus.ph/public/ticket/index.php?entity=1" target="_blank" class="block px-4 py-2 hover:bg-gray-100">
                        <i class="fas fa-file-alt mr-2"></i>Reports
                    </a>

                    <!-- Logout -->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2 hover:bg-gray-100">
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