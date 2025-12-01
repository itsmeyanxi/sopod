@extends('layouts.app')

@section('title', 'Excel Import')

@section('content')
<div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold text-white mb-2">Import Data from Excel</h1>
    <p class="text-gray-400 mb-6">Upload Excel files to import items or customers into your database</p>

    <!-- Tabs -->
    <div class="bg-gray-800 rounded-lg shadow-sm mb-6">
        <div class="border-b border-gray-700">
            <div class="flex">
                <button onclick="switchTab('items')" id="items-tab" class="tab-button px-6 py-3 font-medium text-blue-400 border-b-2 border-blue-400">
                    Import Items
                </button>
                <button onclick="switchTab('customers')" id="customers-tab" class="tab-button px-6 py-3 font-medium text-gray-400 hover:text-gray-300">
                    Import Customers
                </button>
            </div>
        </div>

        <div class="p-6">
            <!-- ITEMS TAB -->
            <div id="items-content" class="tab-content">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-2 text-white">Items Import Requirements</h3>
                    <div class="bg-blue-900 bg-opacity-20 border border-blue-700 rounded-lg p-4">
                        <p class="text-sm text-gray-300 mb-2"><strong>Required columns (can use spaces or underscores):</strong></p>
                        <ul class="text-sm text-gray-400 space-y-1 ml-4">
                            <li>• <strong>item_code</strong> or <strong>Item Code</strong> - Unique item identifier</li>
                            <li>• <strong>item_category</strong> or <strong>Item Category</strong> - Category of the item</li>
                            <li>• <strong>item_description</strong> or <strong>Item Description</strong> - Description of the item</li>
                            <li>• <strong>brand</strong> or <strong>Brand</strong> - Brand name</li>
                        </ul>
                        <p class="text-sm text-gray-300 mt-3 mb-2"><strong>Optional columns:</strong></p>
                        <ul class="text-sm text-gray-400 space-y-1 ml-4">
                            <li>• <strong>unit</strong> or <strong>Unit</strong> - Unit of measurement (e.g., pcs, box, kg)</li>
                        </ul>
                        <div class="mt-3 p-3 bg-yellow-900 bg-opacity-20 border border-yellow-700 rounded">
                            <p class="text-xs text-yellow-300">💡 <strong>Tip:</strong> Column names are flexible! You can use "Item Code" or "item_code", both will work.</p>
                        </div>
                    </div>
                </div>

                <button onclick="downloadTemplate('items')" class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors mb-4">
                    <i class="fas fa-download"></i>
                    Download Template
                </button>

                <form action="{{ route('excel.import.items') }}" method="POST" enctype="multipart/form-data" id="items-form">
                    @csrf
                    <div class="border-2 border-dashed border-gray-600 rounded-lg p-8 text-center hover:border-blue-400 transition-colors">
                        <input type="file" name="file" accept=".xlsx, .xls" id="items-file" class="hidden" onchange="handleFileSelect(this, 'items')" required>
                        <label for="items-file" class="cursor-pointer">
                            <i class="fas fa-file-excel text-5xl text-gray-500 mb-4"></i>
                            <p class="text-lg font-medium text-gray-300 mb-2">Click to upload Excel file</p>
                            <p class="text-sm text-gray-500">Supports .xlsx and .xls files (max 10MB)</p>
                            <p id="items-filename" class="text-sm text-blue-400 mt-2"></p>
                        </label>
                    </div>
                    <button type="submit" class="mt-4 w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        Upload & Import Items
                    </button>
                </form>
            </div>

            <!-- CUSTOMERS TAB -->
            <div id="customers-content" class="tab-content hidden">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-2 text-white">Customers Import Requirements</h3>
                    <div class="bg-blue-900 bg-opacity-20 border border-blue-700 rounded-lg p-4 max-h-96 overflow-y-auto">
                        <p class="text-sm text-gray-300 mb-2"><strong>Required columns (can use spaces or underscores):</strong></p>
                        <ul class="text-sm text-gray-400 space-y-1 ml-4">
                            <li>• <strong>customer_code</strong> or <strong>Customer Code</strong> - Unique customer identifier</li>
                            <li>• <strong>customer_name</strong> or <strong>Customer Name</strong> - Full name of the customer</li>
                        </ul>
                        <p class="text-sm text-gray-300 mt-3 mb-2"><strong>Optional columns:</strong></p>
                        <div class="grid grid-cols-2 gap-x-4">
                            <ul class="text-sm text-gray-400 space-y-1 ml-4">
                                <li>• business_style / Business Style</li>
                                <li>• branch / Branch</li>
                                <li>• customer_group / Customer Group</li>
                                <li>• customer_type / Customer Type</li>
                                <li>• currency / Currency</li>
                                <li>• telephone_1 / Telephone 1</li>
                                <li>• telephone_2 / Telephone 2</li>
                                <li>• mobile / Mobile</li>
                                <li>• email / Email</li>
                                <li>• website / Website</li>
                                <li>• name_of_contact / Name of Contact</li>
                            </ul>
                            <ul class="text-sm text-gray-400 space-y-1 ml-4">
                                <li>• billing_address / Billing Address</li>
                                <li>• shipping_address / Shipping Address</li>
                                <li>• whtrate / WHT Rate</li>
                                <li>• whtcode / WHT Code</li>
                                <li>• require_si / Require SI (yes/no)</li>
                                <li>• ar_type / AR Type</li>
                                <li>• tin_no / TIN No</li>
                                <li>• collection_terms / Collection Terms</li>
                                <li>• sales_rep / Sales Rep</li>
                                <li>• credit_limit / Credit Limit</li>
                                <li>• assigned_bank / Assigned Bank</li>
                            </ul>
                        </div>
                        <div class="mt-3 p-3 bg-yellow-900 bg-opacity-20 border border-yellow-700 rounded">
                            <p class="text-xs text-yellow-300">💡 <strong>Tip:</strong> Column names are flexible! You can use "Customer Code" or "customer_code", both will work.</p>
                        </div>
                    </div>
                </div>

                <button onclick="downloadTemplate('customers')" class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors mb-4">
                    <i class="fas fa-download"></i>
                    Download Template
                </button>

                <form action="{{ route('excel.import.customers') }}" method="POST" enctype="multipart/form-data" id="customers-form">
                    @csrf
                    <div class="border-2 border-dashed border-gray-600 rounded-lg p-8 text-center hover:border-blue-400 transition-colors">
                        <input type="file" name="file" accept=".xlsx, .xls" id="customers-file" class="hidden" onchange="handleFileSelect(this, 'customers')" required>
                        <label for="customers-file" class="cursor-pointer">
                            <i class="fas fa-file-excel text-5xl text-gray-500 mb-4"></i>
                            <p class="text-lg font-medium text-gray-300 mb-2">Click to upload Excel file</p>
                            <p class="text-sm text-gray-500">Supports .xlsx and .xls files (max 10MB)</p>
                            <p id="customers-filename" class="text-sm text-blue-400 mt-2"></p>
                        </label>
                    </div>
                    <button type="submit" class="mt-4 w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        Upload & Import Customers
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
    <div class="bg-green-900 bg-opacity-30 border border-green-600 rounded-lg p-4 mb-4">
        <div class="flex items-start gap-3">
            <i class="fas fa-check-circle text-green-400 mt-0.5"></i>
            <div>
                <h4 class="font-semibold text-green-300">Import Successful!</h4>
                <p class="text-sm text-green-400 mt-1 whitespace-pre-line">{{ session('success') }}</p>
            </div>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-900 bg-opacity-30 border border-red-600 rounded-lg p-4 mb-4">
        <div class="flex items-start gap-3">
            <i class="fas fa-times-circle text-red-400 mt-0.5"></i>
            <div class="flex-1">
                <h4 class="font-semibold text-red-300">Import Failed</h4>
                <p class="text-sm text-red-400 mt-1 whitespace-pre-line">{{ session('error') }}</p>
            </div>
        </div>
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-900 bg-opacity-30 border border-red-600 rounded-lg p-4 mb-4">
        <div class="flex items-start gap-3">
            <i class="fas fa-times-circle text-red-400 mt-0.5"></i>
            <div class="flex-1">
                <h4 class="font-semibold text-red-300">Validation Errors</h4>
                <ul class="text-sm text-red-400 mt-1 space-y-1">
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
function switchTab(tab) {
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('text-blue-400', 'border-b-2', 'border-blue-400');
        btn.classList.add('text-gray-400');
    });
    
    const activeTab = document.getElementById(tab + '-tab');
    activeTab.classList.remove('text-gray-400');
    activeTab.classList.add('text-blue-400', 'border-b-2', 'border-blue-400');
    
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    document.getElementById(tab + '-content').classList.remove('hidden');
}

function handleFileSelect(input, type) {
    const filename = input.files[0]?.name;
    if (filename) {
        document.getElementById(type + '-filename').textContent = 'Selected: ' + filename;
    }
}

function downloadTemplate(type) {
    let data, filename;
    
    if (type === 'items') {
        data = [
            {
                'Item Code': 'ITEM001',
                'Item Category': 'Electronics',
                'Item Description': 'Sample Laptop Computer',
                'Brand': 'Dell',
                'Unit': 'pcs'
            },
            {
                'Item Code': 'ITEM002',
                'Item Category': 'Furniture',
                'Item Description': 'Office Chair Ergonomic',
                'Brand': 'ErgoComfort',
                'Unit': 'pcs'
            }
        ];
        filename = 'items_template.xlsx';
    } else {
        data = [
            {
                'Customer Code': 'CUST001',
                'Customer Name': 'ABC Corporation',
                'Business Style': 'Retail',
                'Branch': 'Main Branch',
                'Customer Group': 'VIP',
                'Customer Type': 'Corporate',
                'Currency': 'PHP',
                'Telephone 1': '02-1234-5678',
                'Telephone 2': '02-8765-4321',
                'Mobile': '0917-123-4567',
                'Email': 'contact@abc.com',
                'Website': 'www.abc.com',
                'Name of Contact': 'John Doe',
                'Billing Address': '123 Main St, Makati City',
                'Shipping Address': '123 Main St, Makati City',
                'WHT Rate': 5.00,
                'WHT Code': 'WHT001',
                'Require SI': 'yes',
                'AR Type': 'Regular',
                'TIN No': '123-456-789-000',
                'Collection Terms': 'Net 30',
                'Sales Rep': 'Jane Smith',
                'Credit Limit': 100000.00,
                'Assigned Bank': 'BDO'
            },
            {
                'Customer Code': 'CUST002',
                'Customer Name': 'XYZ Enterprises',
                'Business Style': 'Wholesale',
                'Branch': 'Branch 2',
                'Customer Group': 'Regular',
                'Customer Type': 'SME',
                'Currency': 'PHP',
                'Telephone 1': '02-9876-5432',
                'Telephone 2': '',
                'Mobile': '0918-765-4321',
                'Email': 'info@xyz.com',
                'Website': 'www.xyz.com',
                'Name of Contact': 'Maria Garcia',
                'Billing Address': '456 Business Ave, Quezon City',
                'Shipping Address': '456 Business Ave, Quezon City',
                'WHT Rate': 2.50,
                'WHT Code': 'WHT002',
                'Require SI': 'no',
                'AR Type': 'COD',
                'TIN No': '987-654-321-000',
                'Collection Terms': 'Net 15',
                'Sales Rep': 'Bob Johnson',
                'Credit Limit': 50000.00,
                'Assigned Bank': 'BPI'
            }
        ];
        filename = 'customers_template.xlsx';
    }
    
    const ws = XLSX.utils.json_to_sheet(data);
    
    // Auto-size columns
    const colWidths = [];
    const headers = Object.keys(data[0]);
    headers.forEach(header => {
        const maxLength = Math.max(
            header.length,
            ...data.map(row => String(row[header] || '').length)
        );
        colWidths.push({ wch: Math.min(maxLength + 2, 50) });
    });
    ws['!cols'] = colWidths;
    
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Template');
    XLSX.writeFile(wb, filename);
}
</script>
@endsection