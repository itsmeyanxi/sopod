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
                        <p class="text-sm text-gray-300 mb-2"><strong>Required columns:</strong></p>
                        <ul class="text-sm text-gray-400 space-y-1 ml-4">
                            <li>• <strong>item_code</strong> - Unique item identifier</li>
                            <li>• <strong>item_category</strong> - Category of the item</li>
                            <li>• <strong>item_description</strong> - Description of the item</li>
                            <li>• <strong>brand</strong> - Brand name</li>
                        </ul>
                        <p class="text-sm text-gray-400 mt-2 ml-4">• <strong>unit</strong> - Unit of measurement (optional)</p>
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
                            <p class="text-sm text-gray-500">Supports .xlsx and .xls files</p>
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
                    <div class="bg-blue-900 bg-opacity-20 border border-blue-700 rounded-lg p-4">
                        <p class="text-sm text-gray-300 mb-2"><strong>Required columns:</strong></p>
                        <ul class="text-sm text-gray-400 space-y-1 ml-4">
                            <li>• <strong>customer_code</strong> - Unique customer identifier</li>
                            <li>• <strong>customer_name</strong> - Full name of the customer</li>
                        </ul>
                        <p class="text-sm text-gray-400 mt-2"><strong>Optional columns:</strong></p>
                        <ul class="text-sm text-gray-400 space-y-1 ml-4">
                            <li>• <strong>sales_executive</strong> - Sales executive name</li>
                            <li>• <strong>business_style</strong> - Business style</li>
                            <li>• <strong>billing_address</strong> - Billing address</li>
                            <li>• <strong>branch</strong> - Branch location</li>
                            <li>• <strong>tin</strong> - Tax identification number</li>
                            <li>• <strong>shipping_address</strong> - Shipping address</li>
                        </ul>
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
                            <p class="text-sm text-gray-500">Supports .xlsx and .xls files</p>
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
                <p class="text-sm text-green-400 mt-1">{{ session('success') }}</p>
            </div>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-900 bg-opacity-30 border border-red-600 rounded-lg p-4 mb-4">
        <div class="flex items-start gap-3">
            <i class="fas fa-times-circle text-red-400 mt-0.5"></i>
            <div>
                <h4 class="font-semibold text-red-300">Import Failed</h4>
                <p class="text-sm text-red-400 mt-1">{{ session('error') }}</p>
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
    // Update tab buttons
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('text-blue-400', 'border-b-2', 'border-blue-400');
        btn.classList.add('text-gray-400');
    });
    
    const activeTab = document.getElementById(tab + '-tab');
    activeTab.classList.remove('text-gray-400');
    activeTab.classList.add('text-blue-400', 'border-b-2', 'border-blue-400');
    
    // Update content
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
                item_code: 'ITEM001',
                item_category: 'Electronics',
                item_description: 'Sample Item Description',
                brand: 'Sample Brand',
                unit: 'pcs'
            },
            {
                item_code: 'ITEM002',
                item_category: 'Furniture',
                item_description: 'Office Chair',
                brand: 'ErgoComfort',
                unit: 'pcs'
            }
        ];
        filename = 'items_template.xlsx';
    } else {
        data = [
            {
                customer_code: 'CUST001',
                customer_name: 'John Doe',
                sales_executive: 'Alice Johnson',
                business_style: 'Retail',
                billing_address: '123 Main St, City',
                branch: 'Main Branch',
                tin: '123-456-789',
                shipping_address: '123 Main St, City'
            },
            {
                customer_code: 'CUST002',
                customer_name: 'Jane Smith',
                sales_executive: 'Bob Williams',
                business_style: 'Wholesale',
                billing_address: '456 Oak Ave, Town',
                branch: 'Branch 2',
                tin: '987-654-321',
                shipping_address: '456 Oak Ave, Town'
            }
        ];
        filename = 'customers_template.xlsx';
    }
    
    const ws = XLSX.utils.json_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Template');
    XLSX.writeFile(wb, filename);
}
</script>
@endsection