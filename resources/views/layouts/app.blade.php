<!DOCTYPE html>
<html lang="en">
<head>
    <!-- ✅ Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-sV1Q1vHbItg4pTo8x4i1Lr3b6eC5ayvJe7f9kM3qFms0tYgM1zMyxSm+kWjqT7wn3C1HrN6S3iX3UnyFwX9bOg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SOPOD')</title>
    <script src="https://cdn.tailwindcss.com"></script>

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

    </style>
</head>
<body class="flex bg-gray-900 text-white">

@auth
<!-- =================== SIDEBAR =================== -->
<div id="sidebar" class="sidebar bg-gray-900 text-white w-64 min-h-screen transition-all duration-300 ease-in-out">
    <div class="flex items-center justify-center p-4">
        <h2 class="text-lg font-bold sidebar-text">SOPOD</h2>
    </div>

    <nav class="mt-4 space-y-2">
        <!-- Dashboard -->
        <a href="{{ route('dashboard') }}" class="flex items-center space-x-2 px-4 py-2 hover:bg-gray-700">
            <span>📊</span>
            <span class="sidebar-text">Dashboard</span>
        </a>

        <!-- =================== SALES ORDERS =================== -->
        <div>
            <button class="flex items-center justify-between w-full px-4 py-2 hover:bg-gray-700">
                <span class="flex items-center space-x-2">
                    <span>📄</span>
                    <span class="sidebar-text">Sales Orders</span>
                </span>
                <span class="chevron">▼</span>
            </button>
            <div class="submenu ml-8 space-y-1 hidden">
                <a href="{{ route('sales_orders.create') }}" class="block hover:underline">Create Order</a>
                <a href="{{ route('sales_orders.index') }}" class="block hover:underline">Order List</a>
                <a href="{{ route('sales_orders.accepted') }}" class="block hover:underline">Accepted Orders</a>
            </div>
        </div>

        <!-- =================== CUSTOMERS =================== -->
        <div>
            <button class="flex items-center justify-between w-full px-4 py-2 hover:bg-gray-700">
                <span class="flex items-center space-x-2">
                    <span>👥</span>
                    <span class="sidebar-text">Customers</span>
                </span>
                <span class="chevron">▼</span>
            </button>
            <div class="submenu ml-8 space-y-1 hidden">
                <a href="{{ route('customers.create') }}" class="block hover:underline">Add Customer</a>
                <a href="{{ route('customers.index') }}" class="block hover:underline">Customer List</a>
            </div>
        </div>

        <!-- =================== ITEMS =================== -->
        <div>
            <button class="flex items-center justify-between w-full px-4 py-2 hover:bg-gray-700">
                <span class="flex items-center space-x-2">
                    <span>📦</span>
                    <span class="sidebar-text">Items</span>
                </span>
                <span class="chevron">▼</span>
            </button>
            <div class="submenu ml-8 space-y-1 hidden">
                <a href="{{ route('items.create') }}" class="block hover:underline">Add Item</a>
                <a href="{{ route('items.index') }}" class="block hover:underline">Item List</a>
            </div>
        </div>

        <!-- =================== DELIVERIES =================== -->
        <div>
            <button class="flex items-center justify-between w-full px-4 py-2 hover:bg-gray-700">
                <span class="flex items-center space-x-2">
                    <span>🚚</span>
                    <span class="sidebar-text">Deliveries</span>
                </span>
                <span class="chevron">▼</span>
            </button>
            <div class="submenu ml-8 space-y-1 hidden">
                <a href="{{ route('deliveries.deliveries') }}" class="block hover:underline">View Delivery</a>
                <a href="{{ route('deliveries.index') }}" class="block hover:underline">Delivery List</a>
            </div>
        </div>

    <a href="{{ route('records.index') }}" class="flex items-center space-x-2 px-4 py-2 hover:bg-gray-700">
        <span>📁</span>
        <span class="sidebar-text">Records</span>
    </a>

    <!-- Excel Import -->
    <a href="{{ route('excel.import') }}" class="flex items-center space-x-2 px-4 py-2 hover:bg-gray-700">
        <span>📊</span>
        <span class="sidebar-text">Excel Import</span>
    </a>
    </nav>
</div>

<!-- =================== MAIN CONTENT =================== -->
<div class="flex-1 min-h-screen bg-gray-100 flex flex-col">

    <!-- Top Bar -->
    <div class="bg-gray-800 shadow p-4 flex items-center justify-between text-white">
        <div class="flex items-center space-x-4">
            <button id="toggle-btn" class="text-white text-xl">☰</button>
            <h1 class="text-xl font-semibold">@yield('title', 'Dashboard')</h1>
        </div>

        <div class="flex items-center space-x-6 relative">
            <!-- Create New Dropdown -->
            <div class="relative">
                <button id="createNewButton" class="flex items-center space-x-1 hover:text-gray-300">
                    <span>Create New</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="createNewDropdown" class="hidden absolute right-0 mt-2 w-40 bg-white text-black border rounded shadow-lg z-50">
                    <a href="{{ route('sales_orders.create') }}" class="block px-4 py-2 hover:bg-gray-100">Sales Order</a>
                    <a href="{{ route('customers.create') }}" class="block px-4 py-2 hover:bg-gray-100">Customer</a>
                    <a href="{{ route('items.create') }}" class="block px-4 py-2 hover:bg-gray-100">Item</a>
                    <a href="{{ route('admin.users.create') }}" class="block px-4 py-2 hover:bg-gray-100">User</a>       
                </div>
            </div>

            <!-- User Dropdown -->
            <div class="relative">
                <button id="userDropdownButton" class="flex items-center space-x-2 focus:outline-none hover:text-gray-300">
                    <span>{{ Auth::user()->name }}</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="userDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white text-black border rounded shadow-lg z-50">
                    <div class="px-4 py-2 border-b bg-gray-50">
                        <p class="text-sm font-semibold">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-gray-600">{{ Auth::user()->role }}</p>
                    </div>
                    <div>
                       <a href="http://mtcresolveit.meatplus.ph/public/ticket/index.php?entity=1" target="_blank"> 
                        <button id="reports" class="w-full text-left px-4 py-2 hover:bg-gray-100">Reports</button></a>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2 hover:bg-gray-100">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Content -->
    <div class="bg-gray-900 p-6 flex-1 text-white">
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

    // ✅ Sidebar Open / Close Toggle
    toggleBtn.addEventListener("click", () => {
        sidebar.classList.toggle("collapsed"); // <-- NEW
        sidebar.classList.toggle("w-64");
        sidebar.classList.toggle("w-16");

        // Hide/Show Text
        sidebarTexts.forEach(text => text.classList.toggle("hidden"));
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

</script>

</body>
</html>