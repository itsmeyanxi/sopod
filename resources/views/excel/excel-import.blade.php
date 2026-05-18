@extends('layouts.app')

@section('title', 'Excel Import')

@section('content')
<div class="max-w-4xl mx-auto">

    @php
        $user = auth()->user();

        $canImportItems = $user->canPerformInModule('can_create', 'items');
        $canImportCustomers = $user->canPerformInModule('can_create', 'customers');
        $canImportMonthlySales = $user->canAccessModule('sales_analytics');
        $canImportSuppliers = $user->canPerformInModule('can_create', 'suppliers');
        $canImportARAging = $user->canAccessModule('aging_reports');
        $canImportCollections = $user->canAccessModule('payments');
        $canImportARAdjustments = $user->canAccessModule('aging_reports');
        $canImportBomMaterials = $user->canAccessModule('inhouse_bom');
        $canImportAssetClasses = $user->canAccessModule('asset_classes');
        $canImportFixedAssets = $user->canAccessModule('fixed_assets');
    @endphp

    <h1 class="text-3xl font-bold text-white mb-2">Import Data from Excel/CSV</h1>
    <p class="text-gray-300 mb-6">Upload Excel or CSV files to import items, customers, suppliers, or monthly sales into your database</p>

    <!-- Tabs -->
    <div class="bg-gray-800 rounded-lg shadow-sm mb-6">
        <div class="border-b border-gray-700">
            <div class="flex flex-wrap">

                @if($canImportItems)
                <button onclick="switchTab('items')" id="items-tab"
                    class="tab-button px-6 py-3 font-medium text-blue-700 border-b-2 border-blue-400">
                    Import Items
                </button>
                @endif

                @if($canImportCustomers)
                <button onclick="switchTab('customers')" id="customers-tab"
                    class="tab-button px-6 py-3 font-medium text-gray-300 hover:text-gray-300">
                    Import Customers
                </button>
                @endif

                @if($canImportSuppliers)
                <button onclick="switchTab('suppliers')" id="suppliers-tab"
                    class="tab-button px-6 py-3 font-medium text-gray-300 hover:text-gray-300">
                    Import Suppliers
                </button>
                <button onclick="switchTab('vendors_trade')" id="vendors_trade-tab"
                    class="tab-button px-6 py-3 font-medium text-gray-300 hover:text-gray-300">
                    Trade Vendors
                </button>
                <button onclick="switchTab('vendors_nontrade')" id="vendors_nontrade-tab"
                    class="tab-button px-6 py-3 font-medium text-gray-300 hover:text-gray-300">
                    Non-Trade Vendors
                </button>
                <button onclick="switchTab('vendors_employees')" id="vendors_employees-tab"
                    class="tab-button px-6 py-3 font-medium text-gray-300 hover:text-gray-300">
                    Employees
                </button>
                <button onclick="switchTab('vendors_replace')" id="vendors_replace-tab"
                    class="tab-button px-6 py-3 font-medium text-red-300 hover:text-red-200">
                    🔄 Replace All Vendors
                </button>
                @endif

                @if($canImportMonthlySales)
                <button onclick="switchTab('monthly_sales')" id="monthly_sales-tab"
                    class="tab-button px-6 py-3 font-medium text-gray-300 hover:text-gray-300">
                    Import Monthly Sales
                </button>
                @endif

                @if($canImportARAging)
                <button onclick="switchTab('ar_aging')" id="ar_aging-tab"
                    class="tab-button px-6 py-3 font-medium text-gray-300 hover:text-gray-300">
                    Import AR Aging
                </button>
                @endif

                @if($canImportCollections)
                <button onclick="switchTab('collections')" id="collections-tab"
                    class="tab-button px-6 py-3 font-medium text-gray-300 hover:text-gray-300">
                    Import Collections
                </button>
                @endif

                @if($canImportARAdjustments)
                <button onclick="switchTab('ar_adjustments')" id="ar_adjustments-tab"
                    class="tab-button px-6 py-3 font-medium text-gray-300 hover:text-gray-300">
                    Import AR Adjustments
                </button>
                @endif

                {{-- ✅ NEW: BOM MATERIALS TAB --}}
                @if($canImportBomMaterials)
                <button onclick="switchTab('bom_materials')" id="bom_materials-tab"
                    class="tab-button px-6 py-3 font-medium text-gray-300 hover:text-gray-300">
                    🐔 BOM Materials
                </button>
                @endif

                @if($canImportAssetClasses)
                <button onclick="switchTab('asset_classes')" id="asset_classes-tab"
                    class="tab-button px-6 py-3 font-medium text-gray-300 hover:text-gray-300">
                    Asset Classes
                </button>
                @endif

                @if($canImportFixedAssets)
                <button onclick="switchTab('fixed_assets')" id="fixed_assets-tab"
                    class="tab-button px-6 py-3 font-medium text-gray-300 hover:text-gray-300">
                    FA Masterdata (Fixed Assets)
                </button>
                @endif

            </div>
        </div>

        <div class="p-6">

            {{-- ITEMS TAB CONTENT --}}
            @if($canImportItems)
            <div id="items-content" class="tab-content">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-2 text-white">Items Import Requirements</h3>
                    <div class="bg-blue-100 bg-opacity-20 border border-blue-700 rounded-lg p-4">
                        <p class="text-sm text-gray-300 mb-2"><strong>✅ All fields are OPTIONAL:</strong></p>
                        <p class="text-xs text-gray-300 mb-3">Only item_code is checked. Rows without item_code will be skipped. All other fields are optional.</p>
                        <ul class="text-sm text-gray-300 space-y-1 ml-4">
                            <li>• <strong>item_code</strong> or <strong>Item Code</strong></li>
                            <li>• item_category / Item Category</li>
                            <li>• item_description / Item Description</li>
                            <li>• brand / Brand</li>
                            <li>• unit / Unit</li>
                        </ul>
                    </div>
                </div>
                <button onclick="downloadTemplate('items')" class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors mb-4">
                    <i class="fas fa-download"></i> Download Template
                </button>
                <form action="{{ route('excel.import.items') }}" method="POST" enctype="multipart/form-data" id="items-form">
                    @csrf
                    <div class="border-2 border-dashed border-gray-600 rounded-lg p-8 text-center hover:border-blue-400 transition-colors">
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" id="items-file" class="hidden" onchange="handleFileSelect(this, 'items')" required>
                        <label for="items-file" class="cursor-pointer">
                            <i class="fas fa-file-excel text-5xl text-gray-300 mb-4"></i>
                            <p class="text-lg font-medium text-gray-300 mb-2">Click to upload Excel or CSV file</p>
                            <p id="items-filename" class="text-sm text-blue-700 mt-2"></p>
                        </label>
                    </div>
                    <button type="submit" class="mt-4 w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        Upload & Import Items
                    </button>
                </form>
            </div>
            @endif

            {{-- CUSTOMERS TAB CONTENT --}}
            @if($canImportCustomers)
            <div id="customers-content" class="tab-content {{ $canImportItems ? 'hidden' : '' }}">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-2 text-white">Customers Import Requirements</h3>
                    <div class="bg-blue-100 bg-opacity-20 border border-blue-700 rounded-lg p-4 max-h-96 overflow-y-auto">
                        <p class="text-sm text-gray-300 mb-2"><strong>✅ Required columns:</strong></p>
                        <ul class="text-sm text-gray-300 space-y-1 ml-4 mb-4">
                            <li>• <strong class="text-red-700">customer_code</strong> / Customer Code</li>
                            <li>• <strong class="text-red-700">customer_name</strong> / Customer Name</li>
                            <li>• <strong class="text-red-700">billing_address</strong> / Billing Address</li>
                            <li>• <strong class="text-red-700">sales_rep</strong> / Sales Rep</li>
                            <li>• <strong class="text-red-700">collection_terms</strong> / Collection Terms</li>
                            <li>• <strong class="text-yellow-700">flag_status</strong> / Flag Status
                                <span class="text-xs text-gray-300">(must be either "Flagged" or "Unflagged")</span>
                            </li>
                        </ul>
                        <div class="bg-yellow-100/20 border border-yellow-600 rounded p-3 mb-4">
                            <p class="text-xs text-yellow-700 font-semibold mb-2">📋 Flag Status Examples:</p>
                            <div class="text-xs text-gray-300 space-y-1">
                                <p>• Use <strong class="text-yellow-700">"Flagged"</strong> for customers requiring approval</p>
                                <p>• Use <strong class="text-green-700">"Unflagged"</strong> for customers with auto-approval</p>
                                <p class="text-red-700 mt-2">⚠️ Case-insensitive: "flagged", "FLAGGED", or "Flagged" all work</p>
                            </div>
                        </div>
                        <p class="text-sm text-gray-300 mt-3 mb-2"><strong>Optional columns:</strong></p>
                        <div class="grid grid-cols-2 gap-x-4">
                            <ul class="text-sm text-gray-300 space-y-1 ml-4">
                                <li>• business_style</li><li>• branch</li><li>• customer_group</li>
                                <li>• customer_type</li><li>• currency</li><li>• telephone_1</li>
                                <li>• mobile</li><li>• email</li><li>• website</li>
                            </ul>
                            <ul class="text-sm text-gray-300 space-y-1 ml-4">
                                <li>• shipping_address</li><li>• whtcode</li><li>• whtrate</li>
                                <li>• require_si</li><li>• ar_type</li><li>• tin_no</li><li>• credit_limit</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <button onclick="downloadTemplate('customers')" class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors mb-4">
                    <i class="fas fa-download"></i> Download Template
                </button>
                <form action="{{ route('excel.import.customers') }}" method="POST" enctype="multipart/form-data" id="customers-form">
                    @csrf
                    <div class="border-2 border-dashed border-gray-600 rounded-lg p-8 text-center hover:border-blue-400 transition-colors">
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" id="customers-file" class="hidden" onchange="handleFileSelect(this, 'customers')" required>
                        <label for="customers-file" class="cursor-pointer">
                            <i class="fas fa-file-excel text-5xl text-gray-300 mb-4"></i>
                            <p class="text-lg font-medium text-gray-300 mb-2">Click to upload Excel or CSV file</p>
                            <p id="customers-filename" class="text-sm text-blue-700 mt-2"></p>
                        </label>
                    </div>
                    <button type="submit" class="mt-4 w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        Upload & Import Customers
                    </button>
                </form>
            </div>
            @endif

            {{-- SUPPLIERS TAB CONTENT --}}
            @if($canImportSuppliers)
            <div id="suppliers-content" class="tab-content hidden">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-2 text-white">Suppliers Import Requirements</h3>
                    <div class="bg-blue-100 bg-opacity-20 border border-blue-700 rounded-lg p-4 max-h-96 overflow-y-auto">
                        <p class="text-sm text-gray-300 mb-2"><strong>✅ All fields are OPTIONAL:</strong></p>
                        <p class="text-xs text-gray-300 mb-4">If supplier_code is missing, it will be auto-generated.</p>
                        <div class="grid grid-cols-2 gap-x-4">
                            <ul class="text-sm text-gray-300 space-y-1 ml-4">
                                <li>• supplier_code / Supplier Code</li><li>• supplier / Supplier Name</li>
                                <li>• contact_person / Contact Person</li><li>• email_address / Email Address</li>
                                <li>• company_address / Address</li><li>• contact_number / Contact Number</li>
                            </ul>
                            <ul class="text-sm text-gray-300 space-y-1 ml-4">
                                <li>• terms / Terms</li><li>• tin / TIN</li><li>• bank / Bank</li>
                                <li>• account_name / Account Name</li><li>• account_number / Account Number</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <button onclick="downloadTemplate('suppliers')" class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors mb-4">
                    <i class="fas fa-download"></i> Download Template
                </button>
                <form action="{{ route('excel.import.suppliers') }}" method="POST" enctype="multipart/form-data" id="suppliers-form">
                    @csrf
                    <div class="border-2 border-dashed border-gray-600 rounded-lg p-8 text-center hover:border-blue-400 transition-colors">
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" id="suppliers-file" class="hidden" onchange="handleFileSelect(this, 'suppliers')" required>
                        <label for="suppliers-file" class="cursor-pointer">
                            <i class="fas fa-file-excel text-5xl text-gray-300 mb-4"></i>
                            <p class="text-lg font-medium text-gray-300 mb-2">Click to upload Excel or CSV file</p>
                            <p id="suppliers-filename" class="text-sm text-blue-700 mt-2"></p>
                        </label>
                    </div>
                    <button type="submit" class="mt-4 w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        Upload & Import Suppliers
                    </button>
                </form>
            </div>
            @endif

            {{-- TRADE VENDORS TAB --}}
            @if($canImportSuppliers)
            <div id="vendors_trade-content" class="tab-content hidden">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-2 text-white">Trade Vendors Import</h3>
                    <div class="bg-green-900/30 border border-green-700 rounded-lg p-4">
                        <p class="text-sm text-green-400 font-semibold mb-2">✅ Category will be automatically set to <strong>TRADE</strong> for all rows.</p>
                        <p class="text-sm text-gray-300 mb-2">All fields optional except <strong>Vendor Code</strong> or <strong>Vendor Name</strong>. Supported headers:</p>
                        <p class="text-xs text-gray-400 font-mono">Vendor Code, Vendor Name, Group, GL Account, Status, Company, EE ID, Last Name, First Name, Middle Name, Position, Department, Location, Office Address, Date Hired</p>
                    </div>
                </div>
                <form action="{{ route('excel.import.vendors') }}" method="POST" enctype="multipart/form-data" id="vendors_trade-form">
                    @csrf
                    <input type="hidden" name="forced_category" value="TRADE">
                    <div class="border-2 border-dashed border-gray-600 rounded-lg p-8 text-center hover:border-green-400 transition-colors">
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" id="vendors_trade-file" class="hidden" onchange="handleFileSelect(this, 'vendors_trade')" required>
                        <label for="vendors_trade-file" class="cursor-pointer">
                            <i class="fas fa-file-excel text-5xl text-gray-300 mb-4"></i>
                            <p class="text-lg font-medium text-gray-300 mb-2">Click to upload Excel or CSV file</p>
                            <p id="vendors_trade-filename" class="text-sm text-green-400 mt-2"></p>
                        </label>
                    </div>
                    <button type="submit" class="mt-4 w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                        <i class="fas fa-upload mr-2"></i> Upload & Import Trade Vendors
                    </button>
                </form>
            </div>

            {{-- NON-TRADE VENDORS TAB --}}
            <div id="vendors_nontrade-content" class="tab-content hidden">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-2 text-white">Non-Trade Vendors Import</h3>
                    <div class="bg-yellow-900/30 border border-yellow-700 rounded-lg p-4">
                        <p class="text-sm text-yellow-400 font-semibold mb-2">✅ Category will be automatically set to <strong>NON TRADE</strong> for all rows.</p>
                        <p class="text-sm text-gray-300 mb-2">All fields optional except <strong>Vendor Code</strong> or <strong>Vendor Name</strong>. Supported headers:</p>
                        <p class="text-xs text-gray-400 font-mono">Vendor Code, Vendor Name, Group, GL Account, Status, Company, EE ID, Last Name, First Name, Middle Name, Position, Department, Location, Office Address, Date Hired</p>
                    </div>
                </div>
                <form action="{{ route('excel.import.vendors') }}" method="POST" enctype="multipart/form-data" id="vendors_nontrade-form">
                    @csrf
                    <input type="hidden" name="forced_category" value="NON TRADE">
                    <div class="border-2 border-dashed border-gray-600 rounded-lg p-8 text-center hover:border-yellow-400 transition-colors">
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" id="vendors_nontrade-file" class="hidden" onchange="handleFileSelect(this, 'vendors_nontrade')" required>
                        <label for="vendors_nontrade-file" class="cursor-pointer">
                            <i class="fas fa-file-excel text-5xl text-gray-300 mb-4"></i>
                            <p class="text-lg font-medium text-gray-300 mb-2">Click to upload Excel or CSV file</p>
                            <p id="vendors_nontrade-filename" class="text-sm text-yellow-400 mt-2"></p>
                        </label>
                    </div>
                    <button type="submit" class="mt-4 w-full px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors font-medium">
                        <i class="fas fa-upload mr-2"></i> Upload & Import Non-Trade Vendors
                    </button>
                </form>
            </div>

            {{-- EMPLOYEES TAB --}}
            <div id="vendors_employees-content" class="tab-content hidden">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-2 text-white">Employees Import</h3>
                    <div class="bg-purple-900/30 border border-purple-700 rounded-lg p-4">
                        <p class="text-sm text-purple-400 font-semibold mb-2">✅ Category will be automatically set to <strong>EMPLOYEES</strong> for all rows.</p>
                        <p class="text-sm text-gray-300 mb-2">All fields optional except <strong>Vendor Code</strong> or <strong>Vendor Name</strong>. Supported headers:</p>
                        <p class="text-xs text-gray-400 font-mono">Vendor Code, Vendor Name, Group, GL Account, Status, Company, EE ID, Last Name, First Name, Middle Name, Position, Department, Location, Office Address, Date Hired</p>
                    </div>
                </div>
                <form action="{{ route('excel.import.vendors') }}" method="POST" enctype="multipart/form-data" id="vendors_employees-form">
                    @csrf
                    <input type="hidden" name="forced_category" value="EMPLOYEES">
                    <div class="border-2 border-dashed border-gray-600 rounded-lg p-8 text-center hover:border-purple-400 transition-colors">
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" id="vendors_employees-file" class="hidden" onchange="handleFileSelect(this, 'vendors_employees')" required>
                        <label for="vendors_employees-file" class="cursor-pointer">
                            <i class="fas fa-file-excel text-5xl text-gray-300 mb-4"></i>
                            <p class="text-lg font-medium text-gray-300 mb-2">Click to upload Excel or CSV file</p>
                            <p id="vendors_employees-filename" class="text-sm text-purple-400 mt-2"></p>
                        </label>
                    </div>
                    <button type="submit" class="mt-4 w-full px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-medium">
                        <i class="fas fa-upload mr-2"></i> Upload & Import Employees
                    </button>
                </form>
            </div>

            {{-- REPLACE ALL VENDORS TAB --}}
            <div id="vendors_replace-content" class="tab-content hidden">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-2 text-white">Replace All Vendors</h3>
                    <div class="bg-red-900/40 border border-red-600 rounded-lg p-4">
                        <p class="text-sm text-red-400 font-bold mb-2">⚠️ WARNING: This will DELETE all existing vendors and replace with the uploaded file.</p>
                        <p class="text-sm text-gray-300">Upload a CSV/Excel with all categories (TRADE, NON TRADE, EMPLOYEES) in one file. The CATEGORY column in the file will be used.</p>
                    </div>
                </div>
                <form action="{{ route('excel.import.vendors') }}" method="POST" enctype="multipart/form-data" id="vendors_replace-form">
                    @csrf
                    <input type="hidden" name="replace_all" value="1">
                    <div class="border-2 border-dashed border-red-700 rounded-lg p-8 text-center hover:border-red-400 transition-colors">
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" id="vendors_replace-file" class="hidden" onchange="handleFileSelect(this, 'vendors_replace')" required>
                        <label for="vendors_replace-file" class="cursor-pointer">
                            <i class="fas fa-file-excel text-5xl text-gray-300 mb-4"></i>
                            <p class="text-lg font-medium text-gray-300 mb-2">Click to upload CSV/Excel file</p>
                            <p id="vendors_replace-filename" class="text-sm text-red-400 mt-2"></p>
                        </label>
                    </div>
                    <button type="submit" onclick="return confirm('This will DELETE ALL existing vendors and replace them. Are you sure?')"
                        class="mt-4 w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium">
                        <i class="fas fa-sync-alt mr-2"></i> Delete All & Re-Import Vendors
                    </button>
                </form>
            </div>
            @endif

            {{-- MONTHLY SALES TAB CONTENT --}}
            @if($canImportMonthlySales)
            <div id="monthly_sales-content" class="tab-content hidden">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-2 text-white">Monthly Sales Import Requirements</h3>
                    <div class="bg-blue-100 bg-opacity-20 border border-blue-700 rounded-lg p-4">
                        <p class="text-sm text-gray-300 mb-2"><strong>Required columns:</strong></p>
                        <ul class="text-sm text-gray-300 space-y-1 ml-4">
                            <li>• <strong>month</strong> or <strong>Month</strong> (e.g., January, February)</li>
                            <li>• <strong>qty</strong> or <strong>Qty</strong> (quantity sold)</li>
                            <li>• <strong>php</strong> or <strong>PHP</strong> (amount in Philippine Pesos)</li>
                        </ul>
                        <p class="text-sm text-gray-300 mt-3 mb-2"><strong>Note:</strong></p>
                        <ul class="text-sm text-gray-300 space-y-1 ml-4">
                            <li>• Numbers can include commas (e.g., 902,352.22)</li>
                            <li>• Existing data for the same month will be updated</li>
                        </ul>
                    </div>
                </div>
                <button onclick="downloadTemplate('monthly_sales')" class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors mb-4">
                    <i class="fas fa-download"></i> Download Template
                </button>
                <form action="{{ route('excel.import.monthly_sales') }}" method="POST" enctype="multipart/form-data" id="monthly_sales-form">
                    @csrf
                    <div class="border-2 border-dashed border-gray-600 rounded-lg p-8 text-center hover:border-blue-400 transition-colors">
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" id="monthly_sales-file" class="hidden" onchange="handleFileSelect(this, 'monthly_sales')" required>
                        <label for="monthly_sales-file" class="cursor-pointer">
                            <i class="fas fa-file-excel text-5xl text-gray-300 mb-4"></i>
                            <p class="text-lg font-medium text-gray-300 mb-2">Click to upload Excel or CSV file</p>
                            <p id="monthly_sales-filename" class="text-sm text-blue-700 mt-2"></p>
                        </label>
                    </div>
                    <button type="submit" class="mt-4 w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        Upload & Import Monthly Sales
                    </button>
                </form>
            </div>
            @endif

            {{-- AR AGING TAB CONTENT --}}
            @if($canImportARAging)
            <div id="ar_aging-content" class="tab-content hidden">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-2 text-white">AR Aging Import Requirements</h3>
                    <div class="bg-blue-100 bg-opacity-20 border border-blue-700 rounded-lg p-4 max-h-96 overflow-y-auto">
                        <p class="text-sm text-gray-300 mb-2"><strong>Available columns:</strong></p>
                        <div class="grid grid-cols-2 gap-x-4">
                            <ul class="text-sm text-gray-300 space-y-1 ml-4">
                                <li>• customer_code</li><li>• invoice_no</li><li>• invoice_date</li>
                                <li>• net_ar</li><li>• aging_date</li><li>• counter_date</li>
                                <li>• record_date</li><li>• due_date</li><li>• po_no</li>
                                <li>• dr_no</li><li>• client_name</li><li>• branch</li><li>• sales_executive</li>
                            </ul>
                            <ul class="text-sm text-gray-300 space-y-1 ml-4">
                                <li>• se2</li><li>• terms</li><li>• sales_week_no</li><li>• age</li>
                                <li>• age_category</li><li>• invoice_amount</li><li>• ar_adjustments</li>
                                <li>• settled_invoice_amount</li><li>• gross_ar_balance</li><li>• cwt</li>
                                <li>• net_of_cwt</li><li>• net_ar_balance</li><li>• factored_ar_amount</li>
                                <li>• status</li><li>• include_flag</li><li>• ar_class</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <button onclick="downloadTemplate('ar_aging')" class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors mb-4">
                    <i class="fas fa-download"></i> Download Template
                </button>
                <form action="{{ route('excel.import.ar_aging') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="border-2 border-dashed border-gray-600 rounded-lg p-8 text-center hover:border-blue-400 transition-colors">
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" id="ar-aging-file" class="hidden" onchange="handleFileSelect(this, 'ar_aging')" required>
                        <label for="ar-aging-file" class="cursor-pointer">
                            <i class="fas fa-file-excel text-5xl text-gray-300 mb-4"></i>
                            <p class="text-lg font-medium text-gray-300 mb-2">Click to upload Excel or CSV file</p>
                            <p id="ar_aging-filename" class="text-sm text-blue-700 mt-2"></p>
                        </label>
                    </div>
                    <button type="submit" class="mt-4 w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        Upload & Import AR Aging
                    </button>
                </form>
            </div>
            @endif

            {{-- COLLECTIONS TAB CONTENT --}}
            @if($canImportCollections)
            <div id="collections-content" class="tab-content hidden">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-2 text-white">Collections (Payments) Import Requirements</h3>
                    <div class="bg-blue-100 bg-opacity-20 border border-blue-700 rounded-lg p-4 max-h-96 overflow-y-auto">
                        <p class="text-sm text-gray-300 mb-2"><strong>Available columns (all optional):</strong></p>
                        <div class="grid grid-cols-2 gap-x-4">
                            <ul class="text-sm text-gray-300 space-y-1 ml-4">
                                <li>• customer_code</li><li>• customer_name</li><li>• collection_receipt_number</li>
                                <li>• collection_receipt_date</li><li>• payment_posting_date</li><li>• payment_date</li>
                                <li>• amount</li><li>• payment_option</li><li>• tax</li><li>• ewt</li>
                                <li>• net_of_cwt</li><li>• payment_notes</li><li>• created_by</li><li>• payment_method</li>
                            </ul>
                            <ul class="text-sm text-gray-300 space-y-1 ml-4">
                                <li>• bank</li><li>• reference_no</li><li>• remarks</li><li>• invoice_no</li>
                                <li>• dr_no</li><li>• branch</li><li>• status</li><li>• signed_by</li>
                                <li>• other_adjustment</li><li>• factoring</li><li>• check_amount</li>
                                <li>• checking_si</li><li>• week_no</li><li>• ar_class</li><li>• data_check</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <button onclick="downloadTemplate('collections')" class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors mb-4">
                    <i class="fas fa-download"></i> Download Template
                </button>
                <form action="{{ route('excel.import.collections') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="border-2 border-dashed border-gray-600 rounded-lg p-8 text-center hover:border-blue-400 transition-colors">
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" id="collections-file" class="hidden" onchange="handleFileSelect(this, 'collections')" required>
                        <label for="collections-file" class="cursor-pointer">
                            <i class="fas fa-file-excel text-5xl text-gray-300 mb-4"></i>
                            <p class="text-lg font-medium text-gray-300 mb-2">Click to upload Excel or CSV file</p>
                            <p id="collections-filename" class="text-sm text-blue-700 mt-2"></p>
                        </label>
                    </div>
                    <button type="submit" class="mt-4 w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        Upload & Import Collections
                    </button>
                </form>
            </div>
            @endif

            {{-- AR ADJUSTMENTS TAB CONTENT --}}
            @if($canImportARAdjustments)
            <div id="ar_adjustments-content" class="tab-content hidden">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-2 text-white">AR Adjustments Import Requirements</h3>
                    <div class="bg-blue-100 bg-opacity-20 border border-blue-700 rounded-lg p-4 max-h-96 overflow-y-auto">
                        <p class="text-sm text-gray-300 mb-2"><strong>Available columns (all optional):</strong></p>
                        <div class="grid grid-cols-2 gap-x-4">
                            <ul class="text-sm text-gray-300 space-y-1 ml-4">
                                <li>• customer_code</li><li>• customer_name</li><li>• reference_number</li>
                                <li>• transaction_type</li><li>• transaction_date</li><li>• amount</li>
                                <li>• is_decrease</li><li>• invoice_number</li>
                            </ul>
                            <ul class="text-sm text-gray-300 space-y-1 ml-4">
                                <li>• dr_no</li><li>• gl_account</li><li>• remarks</li>
                                <li>• signed_by</li><li>• created_by</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <button onclick="downloadTemplate('ar_adjustments')" class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors mb-4">
                    <i class="fas fa-download"></i> Download Template
                </button>
                <form action="{{ route('excel.import.ar_adjustments') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="border-2 border-dashed border-gray-600 rounded-lg p-8 text-center hover:border-blue-400 transition-colors">
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" id="ar-adjustments-file" class="hidden" onchange="handleFileSelect(this, 'ar_adjustments')" required>
                        <label for="ar-adjustments-file" class="cursor-pointer">
                            <i class="fas fa-file-excel text-5xl text-gray-300 mb-4"></i>
                            <p class="text-lg font-medium text-gray-300 mb-2">Click to upload Excel or CSV file</p>
                            <p id="ar_adjustments-filename" class="text-sm text-blue-700 mt-2"></p>
                        </label>
                    </div>
                    <button type="submit" class="mt-4 w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        Upload & Import AR Adjustments
                    </button>
                </form>
            </div>
            @endif

            {{-- ✅ NEW: BOM MATERIALS TAB CONTENT --}}
            @if($canImportBomMaterials)
            <div id="bom_materials-content" class="tab-content hidden">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-2 text-white">BOM Materials Library Import</h3>
                    <p class="text-sm text-gray-300 mb-4">
                        Import your materials masterlist to pre-populate the In-House BOM material library.
                        Imported items will appear as searchable/selectable options when building a BOM.
                    </p>

                    {{-- Column requirements --}}
                    <div class="bg-blue-100 bg-opacity-20 border border-blue-700 rounded-lg p-4 mb-4">
                        <p class="text-sm text-gray-300 font-semibold mb-3">📋 Supported Columns</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {{-- Required --}}
                            <div>
                                <p class="text-xs font-semibold text-red-700 uppercase tracking-wide mb-2">Required</p>
                                <ul class="text-sm text-gray-300 space-y-1 ml-2">
                                    <li>• <strong class="text-white">item_code</strong> / Item Code / ITEM CODE</li>
                                    <li>• <strong class="text-white">item_description</strong> / Item Description / ITEM DESCRIPTION</li>
                                </ul>
                            </div>
                            {{-- Optional --}}
                            <div>
                                <p class="text-xs font-semibold text-green-700 uppercase tracking-wide mb-2">Optional</p>
                                <ul class="text-sm text-gray-300 space-y-1 ml-2">
                                    <li>• <strong>uom</strong> / UOM (unit of measure)</li>
                                    <li>• <strong>category</strong> / Category</li>
                                    <li>• <strong>default_cost</strong> / Default Cost</li>
                                    <li>• <strong>notes</strong> / Notes</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {{-- Category mapping info --}}
                    <div class="bg-gray-800 bg-opacity-30 border border-gray-600 rounded-lg p-4 mb-4">
                        <p class="text-xs font-semibold text-yellow-700 uppercase tracking-wide mb-2">🗂️ Category Values (for BOM grouping)</p>
                        <p class="text-xs text-gray-300 mb-3">
                            Use one of these exact values in the <strong class="text-white">category</strong> column so items are grouped correctly in the BOM form.
                            If left blank, items will be placed under <strong class="text-white">Other</strong>.
                        </p>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                            <span class="text-xs bg-blue-100/40 border border-blue-700 rounded px-2 py-1 text-blue-700">feed</span>
                            <span class="text-xs bg-indigo-100 border border-indigo-700 rounded px-2 py-1 text-indigo-700">supplement</span>
                            <span class="text-xs bg-teal-900/40 border border-teal-700 rounded px-2 py-1 text-teal-300">vaccine</span>
                            <span class="text-xs bg-amber-900/40 border border-amber-700 rounded px-2 py-1 text-amber-300">cleaning</span>
                            <span class="text-xs bg-rose-900/40 border border-rose-700 rounded px-2 py-1 text-rose-300">supply</span>
                            <span class="text-xs bg-orange-100/40 border border-orange-700 rounded px-2 py-1 text-orange-700">labor</span>
                            <span class="text-xs bg-gray-700/60 border border-gray-600 rounded px-2 py-1 text-gray-300">overhead</span>
                        </div>
                        <p class="text-xs text-gray-300 mt-3">
                            💡 Tip: The category column is also auto-detected from your Excel sheet's section headers
                            (e.g. a row containing only "FEEDS" or "VACCINE & MEDICATION" will set the category for all rows below it).
                        </p>
                    </div>

                    {{-- Tips --}}
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <p class="text-xs font-semibold text-green-700 uppercase tracking-wide mb-2">💡 Import Tips</p>
                        <ul class="text-xs text-gray-300 space-y-1.5 ml-2">
                            <li>• Matches your existing <strong class="text-white">NBC Masterlist</strong> format (Item Code · Item Description · UOM)</li>
                            <li>• If <strong>item_code</strong> already exists, the record will be <strong class="text-yellow-700">updated</strong> (not duplicated)</li>
                            <li>• Empty rows and section-header rows (e.g. "FEEDS", "SUPPLEMENTS") are automatically skipped</li>
                            <li>• <strong>default_cost</strong> is optional — costs can still be edited per BOM</li>
                            <li>• Supports both <strong>.xlsx</strong> and <strong>.csv</strong> formats</li>
                        </ul>
                    </div>
                </div>

                {{-- Preview section (shown after file selection) --}}
                <div id="bom-preview-container" class="hidden mb-4">
                    <div class="border border-gray-700 rounded-lg overflow-hidden">
                        <div class="bg-gray-900 px-4 py-2 border-b border-gray-700 flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-200">📄 File Preview <span id="bom-preview-count" class="text-gray-300 font-normal"></span></span>
                            <button type="button" onclick="clearBomFile()" class="text-xs text-red-500 hover:text-red-700">✕ Clear</button>
                        </div>
                        <div class="overflow-x-auto max-h-64">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="bg-gray-700">
                                        <th class="px-3 py-2 text-left text-gray-300 font-semibold">Item Code</th>
                                        <th class="px-3 py-2 text-left text-gray-300 font-semibold">Item Description</th>
                                        <th class="px-3 py-2 text-left text-gray-300 font-semibold">UOM</th>
                                        <th class="px-3 py-2 text-left text-gray-300 font-semibold">Category</th>
                                        <th class="px-3 py-2 text-right text-gray-300 font-semibold">Default Cost</th>
                                    </tr>
                                </thead>
                                <tbody id="bom-preview-tbody" class="divide-y divide-gray-700"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <button onclick="downloadTemplate('bom_materials')" class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors mb-4">
                    <i class="fas fa-download"></i> Download Masterlist Template
                </button>

                <form action="{{ route('excel.import.bom_materials') }}" method="POST" enctype="multipart/form-data" id="bom_materials-form">
                    @csrf
                    <div id="bom-dropzone" class="border-2 border-dashed border-gray-600 rounded-lg p-8 text-center hover:border-blue-400 transition-colors">
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" id="bom_materials-file"
                            class="hidden" onchange="handleBomFileSelect(this)" required>
                        <label for="bom_materials-file" class="cursor-pointer">
                            <i class="fas fa-file-excel text-5xl text-gray-300 mb-4"></i>
                            <p class="text-lg font-medium text-gray-300 mb-2">Click to upload Excel or CSV file</p>
                            <p class="text-xs text-gray-300">Supports NBC Masterlist format: Item Code · Item Description · UOM</p>
                            <p id="bom_materials-filename" class="text-sm text-blue-700 mt-2"></p>
                        </label>
                    </div>

                    {{-- Import mode --}}
                    <div class="mt-4 flex items-center gap-4 text-sm text-gray-300">
                        <span class="font-medium">On duplicate item code:</span>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="duplicate_mode" value="update" checked class="accent-blue-600">
                            <span>Update existing</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="duplicate_mode" value="skip" class="accent-blue-600">
                            <span>Skip duplicates</span>
                        </label>
                    </div>

                    <button type="submit" class="mt-4 w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        <i class="fas fa-upload mr-2"></i> Upload & Import BOM Materials
                    </button>
                </form>
            </div>
            @endif

            {{-- ASSET CLASSES TAB CONTENT --}}
            @if($canImportAssetClasses)
            <div id="asset_classes-content" class="tab-content hidden">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-2 text-white">Asset Classes Import</h3>
                    <div class="bg-blue-100 bg-opacity-20 border border-blue-700 rounded-lg p-4">
                        <p class="text-sm text-gray-300 mb-2"><strong>Upload the FA Asset Class List sheet</strong> from your Fixed Asset Masterdata.xlsx — or any file with these columns:</p>
                        <ul class="text-sm text-gray-300 space-y-1 ml-4">
                            <li>• <strong>Code</strong> — Asset code (e.g. ACS01)</li>
                            <li>• <strong>Name</strong> — Asset class name (e.g. AIRCON)</li>
                            <li>• <strong>Acquisition GL Code</strong> — GL account code</li>
                            <li>• <strong>Acquisition GL Account</strong> — Used to map asset group</li>
                            <li>• <strong>Deprciation GL Code</strong> — Depreciation account code</li>
                            <li>• <strong>Depreciaion Type</strong> — e.g. Straight line</li>
                            <li>• <strong>Useful Life</strong> — In months (e.g. 36)</li>
                        </ul>
                        <p class="text-xs text-gray-400 mt-3">Rows without a Code are skipped. Existing records matched by Code will be updated or skipped depending on your choice below.</p>
                    </div>
                </div>

                <form action="{{ route('excel.import.asset_classes') }}" method="POST" enctype="multipart/form-data" id="asset_classes-form">
                    @csrf
                    <div class="border-2 border-dashed border-gray-600 rounded-lg p-8 text-center hover:border-blue-400 transition-colors">
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" id="asset_classes-file" class="hidden" onchange="handleFileSelect(this, 'asset_classes')" required>
                        <label for="asset_classes-file" class="cursor-pointer">
                            <i class="fas fa-file-excel text-5xl text-gray-300 mb-4"></i>
                            <p class="text-lg font-medium text-gray-300 mb-2">Click to upload Excel or CSV file</p>
                            <p id="asset_classes-filename" class="text-sm text-blue-400 mt-2"></p>
                        </label>
                    </div>
                    <div class="mt-4 flex items-center gap-4 text-sm text-gray-300">
                        <span class="font-medium">On duplicate asset code:</span>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="duplicate_mode" value="update" checked class="accent-blue-600">
                            <span>Update existing</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="duplicate_mode" value="skip" class="accent-blue-600">
                            <span>Skip duplicates</span>
                        </label>
                    </div>
                    <button type="submit" class="mt-4 w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        <i class="fas fa-upload mr-2"></i> Upload & Import Asset Classes
                    </button>
                </form>
            </div>
            @endif

            {{-- FA MASTERDATA (FIXED ASSETS) TAB CONTENT --}}
            @if($canImportFixedAssets)
            <div id="fixed_assets-content" class="tab-content hidden">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-2 text-white">FA Masterdata (Fixed Assets) Import</h3>
                    <div class="bg-blue-100 bg-opacity-20 border border-blue-700 rounded-lg p-4">
                        <p class="text-sm text-gray-300 mb-2"><strong>Upload your Fixed Asset Masterdata.xlsx</strong> file.</p>
                        <ul class="text-sm text-gray-300 space-y-1 ml-4">
                            <li>• Sheet name: <strong>FA Master Data</strong></li>
                            <li>• Data starts from <strong>row 4</strong> (row 3 is the header)</li>
                            <li>• Columns: <code>B</code> = Item Code, <code>C</code> = Item Name, <code>D</code> = Asset Class, <code>E</code> = Cost, <code>F</code> = Accum Dep, <code>G</code> = NBV</li>
                            <li>• Asset Group, GL Accounts, and depreciation rates are filled automatically from the Asset Classes table</li>
                        </ul>
                        <p class="text-xs text-gray-400 mt-3">Only completely blank rows (no Item Code and no Item Name) are skipped.</p>
                    </div>
                </div>

                <form action="{{ route('excel.import.fixed_assets') }}" method="POST" enctype="multipart/form-data" id="fixed_assets-form">
                    @csrf
                    <div class="border-2 border-dashed border-gray-600 rounded-lg p-8 text-center hover:border-blue-400 transition-colors">
                        <input type="file" name="file" accept=".xlsx,.xls" id="fixed_assets-file" class="hidden" onchange="handleFileSelect(this, 'fixed_assets')" required>
                        <label for="fixed_assets-file" class="cursor-pointer">
                            <i class="fas fa-file-excel text-5xl text-gray-300 mb-4"></i>
                            <p class="text-lg font-medium text-gray-300 mb-2">Click to upload Excel file</p>
                            <p class="text-sm text-gray-400">Supports .xlsx and .xls only</p>
                            <p id="fixed_assets-filename" class="text-sm text-blue-400 mt-2"></p>
                        </label>
                    </div>
                    <button type="submit" class="mt-4 w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        <i class="fas fa-upload mr-2"></i> Upload & Import Fixed Assets
                    </button>
                </form>
            </div>
            @endif

        </div>
    </div>

    {{-- SUCCESS / ERROR HANDLERS --}}
    @if(session('success'))
    <div class="bg-green-100 bg-opacity-30 border border-green-600 rounded-lg p-4 mb-4">
        <div class="flex items-start gap-3">
            <i class="fas fa-check-circle text-green-700 mt-0.5"></i>
            <div>
                <h4 class="font-semibold text-green-700">Import Successful!</h4>
                <p class="text-sm text-green-700 mt-1 whitespace-pre-line">{{ session('success') }}</p>
            </div>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-100 bg-opacity-30 border border-red-600 rounded-lg p-4 mb-4">
        <div class="flex items-start gap-3">
            <i class="fas fa-times-circle text-red-700 mt-0.5"></i>
            <div class="flex-1">
                <h4 class="font-semibold text-red-700">Import Failed</h4>
                <p class="text-sm text-red-700 mt-1 whitespace-pre-line">{{ session('error') }}</p>
            </div>
        </div>
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-100 bg-opacity-30 border border-red-600 rounded-lg p-4 mb-4">
        <div class="flex items-start gap-3">
            <i class="fas fa-times-circle text-red-700 mt-0.5"></i>
            <div class="flex-1">
                <h4 class="font-semibold text-red-700">Validation Errors</h4>
                <ul class="text-sm text-red-700 mt-1 space-y-1">
                    @foreach($errors->all() as $error)
                    <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
// ── Tab switching ─────────────────────────────────────────────────────────────
function switchTab(tab) {
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('text-blue-700', 'border-b-2', 'border-blue-400');
        btn.classList.add('text-gray-300');
    });
    const activeTab = document.getElementById(tab + '-tab');
    if (activeTab) {
        activeTab.classList.remove('text-gray-300');
        activeTab.classList.add('text-blue-700', 'border-b-2', 'border-blue-400');
    }
    document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
    const content = document.getElementById(tab + '-content');
    if (content) content.classList.remove('hidden');
}

function handleFileSelect(input, type) {
    const filename = input.files[0]?.name;
    if (filename) document.getElementById(type + '-filename').textContent = 'Selected: ' + filename;
}

// ── BOM Materials file select + preview ──────────────────────────────────────

// Category detection from section-header rows (matches NBC Masterlist format)
const SECTION_HEADERS = {
    'feeds': 'feed',
    'feed': 'feed',
    'vaccine & medication': 'vaccine',
    'vaccine': 'vaccine',
    'medication': 'vaccine',
    'supplements': 'supplement',
    'supplement': 'supplement',
    'disinfectant': 'cleaning',
    'cleaning and disinfection': 'cleaning',
    'cleaning': 'cleaning',
    'poultry lifter': 'supply',
    'supplies': 'supply',
    'supply': 'supply',
    'operating supplies': 'supply',
    'labor': 'labor',
    'overhead': 'overhead',
};

function detectCategory(val) {
    if (!val) return null;
    return SECTION_HEADERS[String(val).toLowerCase().trim()] || null;
}

function handleBomFileSelect(input) {
    const file = input.files[0];
    if (!file) return;
    document.getElementById('bom_materials-filename').textContent = 'Selected: ' + file.name;

    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const data    = new Uint8Array(e.target.result);
            const wb      = XLSX.read(data, { type: 'array' });
            const ws      = wb.Sheets[wb.SheetNames[0]];
            const rows    = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '' });
            renderBomPreview(rows);
        } catch(err) {
            console.error('Preview error:', err);
        }
    };
    reader.readAsArrayBuffer(file);
}

function renderBomPreview(rows) {
    if (!rows.length) return;

    // Find header row (contains item_code / Item Code / ITEM CODE)
    let headerRowIdx = -1;
    let colMap = {};

    for (let i = 0; i < Math.min(rows.length, 10); i++) {
        const row = rows[i].map(c => String(c).toLowerCase().trim());
        const codeIdx = row.findIndex(c => c === 'item_code' || c === 'item code');
        if (codeIdx >= 0) {
            headerRowIdx = i;
            colMap.code  = codeIdx;
            colMap.desc  = row.findIndex(c => c === 'item_description' || c === 'item description');
            colMap.uom   = row.findIndex(c => c === 'uom');
            colMap.cat   = row.findIndex(c => c === 'category');
            colMap.cost  = row.findIndex(c => c === 'default_cost' || c === 'default cost' || c === 'cost');
            break;
        }
    }

    // If no header row found, assume first row is header for NBC Masterlist (ITEM CODE | ITEM DESCRIPTION | UOM)
    if (headerRowIdx < 0) {
        // Try to find by content — NBC masterlist has "ITEM CODE" in first data row
        for (let i = 0; i < Math.min(rows.length, 5); i++) {
            const r = rows[i].map(c => String(c).toUpperCase().trim());
            if (r.includes('ITEM CODE')) {
                headerRowIdx = i;
                colMap.code  = r.indexOf('ITEM CODE');
                colMap.desc  = r.indexOf('ITEM DESCRIPTION');
                colMap.uom   = r.indexOf('UOM');
                break;
            }
        }
    }

    const tbody   = document.getElementById('bom-preview-tbody');
    tbody.innerHTML = '';

    let currentCat = '';
    let count      = 0;
    const MAX_PREVIEW = 20;

    for (let i = (headerRowIdx >= 0 ? headerRowIdx + 1 : 1); i < rows.length; i++) {
        const row  = rows[i];
        const code = colMap.code >= 0 ? String(row[colMap.code] || '').trim() : '';
        const desc = colMap.desc >= 0 ? String(row[colMap.desc] || '').trim() : String(row[1] || '').trim();
        const uom  = colMap.uom  >= 0 ? String(row[colMap.uom]  || '').trim() : String(row[2] || '').trim();
        const cost = colMap.cost >= 0 ? String(row[colMap.cost] || '').trim() : '';

        // Detect section-header rows (NBC Masterlist style)
        const autocat = detectCategory(desc) || detectCategory(code);
        if (!code && autocat) { currentCat = autocat; continue; }
        if (!code && !desc)   continue; // blank row

        const cat = (colMap.cat >= 0 ? String(row[colMap.cat] || '') : '') || currentCat || '—';
        if (autocat && !code) { currentCat = autocat; continue; }

        if (count >= MAX_PREVIEW) break;

        const catColors = {
            feed:'bg-blue-50 text-blue-700', supplement:'bg-indigo-50 text-indigo-700',
            vaccine:'bg-teal-50 text-teal-700', cleaning:'bg-amber-50 text-amber-700',
            supply:'bg-rose-50 text-rose-700', labor:'bg-orange-50 text-orange-700',
            overhead:'bg-gray-700 text-gray-300'
        };
        const catClass = catColors[cat] || 'bg-gray-900 text-gray-300';

        tbody.innerHTML += `<tr class="hover:bg-gray-900">
            <td class="px-3 py-1.5 font-mono text-xs text-gray-200">${code || '—'}</td>
            <td class="px-3 py-1.5 text-white">${desc || '—'}</td>
            <td class="px-3 py-1.5 text-gray-300">${uom || '—'}</td>
            <td class="px-3 py-1.5"><span class="text-xs px-1.5 py-0.5 rounded ${catClass}">${cat}</span></td>
            <td class="px-3 py-1.5 text-right text-gray-300">${cost || '—'}</td>
        </tr>`;
        count++;
    }

    document.getElementById('bom-preview-count').textContent = `(showing first ${count} rows)`;
    document.getElementById('bom-preview-container').classList.remove('hidden');
}

function clearBomFile() {
    document.getElementById('bom_materials-file').value = '';
    document.getElementById('bom_materials-filename').textContent = '';
    document.getElementById('bom-preview-container').classList.add('hidden');
    document.getElementById('bom-preview-tbody').innerHTML = '';
}

// ── Template downloads ────────────────────────────────────────────────────────
function downloadTemplate(type) {
    let data, filename;

    if (type === 'bom_materials') {
        // NBC Masterlist format — matches the actual masterlist PDF structure
        data = [
            { 'ITEM CODE': '',            'ITEM DESCRIPTION': 'FEEDS',                     'UOM': '',  'CATEGORY': '',      'DEFAULT COST': '' },
            { 'ITEM CODE': 'FDS-001-PIL', 'ITEM DESCRIPTION': 'Chick Starter 1 Crumble - Pilmico 50kg/Sack', 'UOM': 'Kg', 'CATEGORY': 'feed', 'DEFAULT COST': '' },
            { 'ITEM CODE': 'FDS-003-PIL', 'ITEM DESCRIPTION': 'Chick Grower Crumble - Pilmico 50kg/Sack',     'UOM': 'Kg', 'CATEGORY': 'feed', 'DEFAULT COST': '' },
            { 'ITEM CODE': '',            'ITEM DESCRIPTION': 'VACCINE & MEDICATION',       'UOM': '',  'CATEGORY': '',      'DEFAULT COST': '' },
            { 'ITEM CODE': 'VAC-023-ZOE', 'ITEM DESCRIPTION': 'V.A. ChickVac with sterile diluent - Zoetis (1000 dose)', 'UOM': 'Vial', 'CATEGORY': 'vaccine', 'DEFAULT COST': '' },
            { 'ITEM CODE': 'VAC-025-PHI', 'ITEM DESCRIPTION': 'MB1 - Phibro (2000 dose)',  'UOM': 'Vial', 'CATEGORY': 'vaccine', 'DEFAULT COST': '' },
            { 'ITEM CODE': '',            'ITEM DESCRIPTION': 'SUPPLEMENTS',                'UOM': '',  'CATEGORY': '',      'DEFAULT COST': '' },
            { 'ITEM CODE': 'SPS-002-GEN', 'ITEM DESCRIPTION': 'Multi Vitamins + Amino Acids - (1 liter/bottle)', 'UOM': 'liter', 'CATEGORY': 'supplement', 'DEFAULT COST': 725.00 },
            { 'ITEM CODE': 'SPS-003-GEN', 'ITEM DESCRIPTION': 'Electrolytes - JCS (1 liter/bottle)',             'UOM': 'liter', 'CATEGORY': 'supplement', 'DEFAULT COST': 195.00 },
            { 'ITEM CODE': '',            'ITEM DESCRIPTION': 'DISINFECTANT',               'UOM': '',  'CATEGORY': '',      'DEFAULT COST': '' },
            { 'ITEM CODE': 'DFT-006-GEN', 'ITEM DESCRIPTION': 'Lasto-zide - JCS (5liters/gallon)',    'UOM': 'Gallon', 'CATEGORY': 'cleaning', 'DEFAULT COST': 1300.00 },
            { 'ITEM CODE': 'DFT-016-KAL', 'ITEM DESCRIPTION': 'Linol (All purpose liquid detergent)', 'UOM': 'Carboy', 'CATEGORY': 'cleaning', 'DEFAULT COST': '' },
            { 'ITEM CODE': '',            'ITEM DESCRIPTION': 'POULTRY LIFTER / SUPPLIES',  'UOM': '',  'CATEGORY': '',      'DEFAULT COST': '' },
            { 'ITEM CODE': 'PLR-004-GEN', 'ITEM DESCRIPTION': 'Ricehull',                  'UOM': 'Kg',   'CATEGORY': 'supply', 'DEFAULT COST': 6.00   },
            { 'ITEM CODE': 'PLR-001-SOL', 'ITEM DESCRIPTION': 'Liquid Petroleum Gas - Solane (50Kg/Tank)', 'UOM': 'Tank', 'CATEGORY': 'supply', 'DEFAULT COST': 3291.00 },
        ];
        filename = 'bom_materials_template.xlsx';

    } else if (type === 'ar_aging') {
        data = [{
            'customer_code': 'CUST001', 'invoice_no': 'INV-2024-001', 'invoice_date': '2024-01-15',
            'net_ar': '50000.00', 'client_name': 'ABC Corporation', 'sales_executive': 'John Doe',
            'age': '45', 'age_category': '31-60 Days', 'status': 'Outstanding',
            'due_date': '2024-02-15', 'branch': 'Main Branch', 'terms': '30 Days',
            'invoice_amount': '53000.00', 'settled_invoice_amount': '3000.00', 'include_flag': 'yes', 'ar_class': 'Regular'
        }];
        filename = 'ar_aging_template.xlsx';
    } else if (type === 'customers') {
        data = [{
            'customer_code': 'CUST001', 'customer_name': 'ABC Corporation',
            'billing_address': '123 Business St, Manila', 'sales_rep': 'John Doe',
            'collection_terms': '30 Days', 'flag_status': 'Unflagged'
        }];
        filename = 'customers_template.xlsx';
    } else if (type === 'collections') {
        data = [{
            'customer_code': 'CUST001', 'customer_name': 'ABC Corporation',
            'collection_receipt_number': 'CR-2024-001', 'collection_receipt_date': '2024-01-15',
            'amount': '50000.00', 'payment_method': 'Check', 'bank': 'BDO',
            'reference_no': 'CHK-12345', 'invoice_no': 'INV-001', 'status': 'Cleared'
        }];
        filename = 'collections_template.xlsx';
    } else if (type === 'suppliers') {
        data = [{
            'Supplier Code': 'SUP001', 'Supplier': 'Sure Good Foods Ltd.',
            'Contact Person': 'Penny Tsang', 'Email Address': 'ptsang@suregoodfoods.com',
            'Company Address': '2333 North Sheridan Way, Mississauga', 'Contact Number': '+1-905-123-4567',
            'Terms': '30 Days From Arrival Date', 'TIN': '123-456-789', 'Bank': 'BPI',
            'Account Name': 'Sure Good Foods Ltd.', 'Account Number': '1234567890'
        }];
        filename = 'suppliers_template.xlsx';
    } else if (type === 'monthly_sales') {
        data = [{ 'Month': 'January', 'Qty': 1000, 'PHP': 500000 }];
        filename = 'monthly_sales_template.xlsx';
    } else if (type === 'ar_adjustments') {
        data = [{
            'customer_code': 'CUST001', 'customer_name': 'ABC Corporation',
            'reference_number': 'ADJ-2024-001', 'transaction_type': 'Credit Memo',
            'transaction_date': '2024-01-15', 'amount': '5000.00', 'is_decrease': 'yes',
            'invoice_number': 'INV-001', 'remarks': 'Return of damaged goods'
        }];
        filename = 'ar_adjustments_template.xlsx';
    } else {
        data = [{ 'item_code': '', 'item_description': '', 'brand': '', 'unit': '' }];
        filename = 'items_template.xlsx';
    }

    const ws = XLSX.utils.json_to_sheet(data);
    const colWidths = Object.keys(data[0]).map(key => ({
        wch: Math.min(Math.max(key.length, ...data.map(r => String(r[key] || '').length)) + 4, 55)
    }));
    ws['!cols'] = colWidths;
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Template');
    XLSX.writeFile(wb, filename);
}
</script>
@endsection