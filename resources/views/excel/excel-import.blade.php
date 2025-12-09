@extends('layouts.app')

@section('title', 'Excel Import')

@section('content')
<div class="max-w-4xl mx-auto">

    @php
        $user = auth()->user();

        $canImportItems = in_array($user->role, [
            'Admin',
            'IT',
            'Accounting_Creator',
            'Accounting_Approver'
        ]);

        $canImportCustomers = in_array($user->role, [
            'Admin',
            'IT',
            'CC_Approver',
            'CC_Creator',
            'Accounting_Creator',
            'Accounting_Approver'
        ]);

        $canImportMonthlySales = in_array($user->role, [
            'Admin',
            'IT',
            'Accounting_Creator',
            'Accounting_Approver'
        ]);
    @endphp

    <h1 class="text-3xl font-bold text-white mb-2">Import Data from Excel/CSV</h1>
    <p class="text-gray-400 mb-6">Upload Excel or CSV files to import items, customers, or monthly sales into your database</p>

    <!-- Tabs -->
    <div class="bg-gray-800 rounded-lg shadow-sm mb-6">
        <div class="border-b border-gray-700">
            <div class="flex">

                {{-- ITEMS TAB (show only if allowed) --}}
                @if($canImportItems)
                <button onclick="switchTab('items')" id="items-tab"
                    class="tab-button px-6 py-3 font-medium text-blue-400 border-b-2 border-blue-400">
                    Import Items
                </button>
                @endif

                {{-- CUSTOMERS TAB (show only if allowed) --}}
                @if($canImportCustomers)
                <button onclick="switchTab('customers')" id="customers-tab"
                    class="tab-button px-6 py-3 font-medium text-gray-400 hover:text-gray-300">
                    Import Customers
                </button>
                @endif

                {{-- MONTHLY SALES TAB (show only if allowed) --}}
                @if($canImportMonthlySales)
                <button onclick="switchTab('monthly_sales')" id="monthly_sales-tab"
                    class="tab-button px-6 py-3 font-medium text-gray-400 hover:text-gray-300">
                    Import Monthly Sales
                </button>
                @endif

            </div>
        </div>

        <div class="p-6">

            {{-- ITEMS TAB CONTENT — only if allowed --}}
            @if($canImportItems)
            <div id="items-content" class="tab-content">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-2 text-white">Items Import Requirements</h3>
                    <div class="bg-blue-900 bg-opacity-20 border border-blue-700 rounded-lg p-4">
                        <p class="text-sm text-gray-300 mb-2"><strong>Required columns (can use spaces or underscores):</strong></p>
                        <ul class="text-sm text-gray-400 space-y-1 ml-4">
                            <li>• <strong>item_code</strong> or <strong>Item Code</strong></li>
                            <li>• <strong>item_category</strong> or <strong>Item Category</strong></li>
                            <li>• <strong>item_description</strong> or <strong>Item Description</strong></li>
                            <li>• <strong>brand</strong> or <strong>Brand</strong></li>
                        </ul>
                        <p class="text-sm text-gray-300 mt-3 mb-2"><strong>Optional:</strong></p>
                        <ul class="text-sm text-gray-400 space-y-1 ml-4">
                            <li>• unit / Unit</li>
                        </ul>
                    </div>
                </div>

                <button onclick="downloadTemplate('items')" class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors mb-4">
                    <i class="fas fa-download"></i>
                    Download Template
                </button>

                <form action="{{ route('excel.import.items') }}" method="POST" enctype="multipart/form-data" id="items-form">
                    @csrf
                    <div class="border-2 border-dashed border-gray-600 rounded-lg p-8 text-center hover:border-blue-400 transition-colors">
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" id="items-file" class="hidden" onchange="handleFileSelect(this, 'items')" required>
                        <label for="items-file" class="cursor-pointer">
                            <i class="fas fa-file-excel text-5xl text-gray-500 mb-4"></i>
                            <p class="text-lg font-medium text-gray-300 mb-2">Click to upload Excel or CSV file</p>
                            <p id="items-filename" class="text-sm text-blue-400 mt-2"></p>
                        </label>
                    </div>
                    <button type="submit" class="mt-4 w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        Upload & Import Items
                    </button>
                </form>
            </div>
            @endif


            {{-- CUSTOMERS TAB CONTENT — only if allowed --}}
            @if($canImportCustomers)
            <div id="customers-content" class="tab-content {{ $canImportItems ? 'hidden' : '' }}">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-2 text-white">Customers Import Requirements</h3>
                    <div class="bg-blue-900 bg-opacity-20 border border-blue-700 rounded-lg p-4 max-h-96 overflow-y-auto">

                        <p class="text-sm text-gray-300 mb-2"><strong>Required columns:</strong></p>
                        <ul class="text-sm text-gray-400 space-y-1 ml-4">
                            <li>• customer_code / Customer Code</li>
                            <li>• customer_name / Customer Name</li>
                            <li>• billing_address / Billing Address</li>
                            <li>• sales_rep / Sales Rep</li>
                            <li>• collection_terms / Collection Terms</li>
                        </ul>

                        <p class="text-sm text-gray-300 mt-3 mb-2"><strong>Optional columns:</strong></p>
                        <div class="grid grid-cols-2 gap-x-4">
                            <ul class="text-sm text-gray-400 space-y-1 ml-4">
                                <li>• business_style</li>
                                <li>• branch</li>
                                <li>• customer_group</li>
                                <li>• customer_type</li>
                                <li>• currency</li>
                                <li>• telephone_1</li>
                                <li>• mobile</li>
                                <li>• email</li>
                                <li>• website</li>
                            </ul>
                            <ul class="text-sm text-gray-400 space-y-1 ml-4">
                                <li>• shipping_address</li>
                                <li>• whtcode</li>
                                <li>• whtrate</li>
                                <li>• require_si</li>
                                <li>• ar_type</li>
                                <li>• tin_no</li>
                                <li>• credit_limit</li>
                            </ul>
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
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" id="customers-file" class="hidden" onchange="handleFileSelect(this, 'customers')" required>
                        <label for="customers-file" class="cursor-pointer">
                            <i class="fas fa-file-excel text-5xl text-gray-500 mb-4"></i>
                            <p class="text-lg font-medium text-gray-300 mb-2">Click to upload Excel or CSV file</p>
                            <p id="customers-filename" class="text-sm text-blue-400 mt-2"></p>
                        </label>
                    </div>
                    <button type="submit" class="mt-4 w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        Upload & Import Customers
                    </button>
                </form>
            </div>
            @endif

            {{-- MONTHLY SALES TAB CONTENT — only if allowed --}}
            @if($canImportMonthlySales)
            <div id="monthly_sales-content" class="tab-content hidden">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-2 text-white">Monthly Sales Import Requirements</h3>
                    <div class="bg-blue-900 bg-opacity-20 border border-blue-700 rounded-lg p-4">
                        <p class="text-sm text-gray-300 mb-2"><strong>Required columns:</strong></p>
                        <ul class="text-sm text-gray-400 space-y-1 ml-4">
                            <li>• <strong>month</strong> or <strong>Month</strong> (e.g., January, February)</li>
                            <li>• <strong>qty</strong> or <strong>Qty</strong> (quantity sold)</li>
                            <li>• <strong>php</strong> or <strong>PHP</strong> (amount in Philippine Pesos)</li>
                        </ul>
                        <p class="text-sm text-gray-300 mt-3 mb-2"><strong>Note:</strong></p>
                        <ul class="text-sm text-gray-400 space-y-1 ml-4">
                            <li>• Numbers can include commas (e.g., 902,352.22)</li>
                            <li>• Existing data for the same month will be updated</li>
                        </ul>
                    </div>
                </div>

                <button onclick="downloadTemplate('monthly_sales')" class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors mb-4">
                    <i class="fas fa-download"></i>
                    Download Template
                </button>

                <form action="{{ route('excel.import.monthly_sales') }}" method="POST" enctype="multipart/form-data" id="monthly_sales-form">
                    @csrf
                    <div class="border-2 border-dashed border-gray-600 rounded-lg p-8 text-center hover:border-blue-400 transition-colors">
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" id="monthly_sales-file" class="hidden" onchange="handleFileSelect(this, 'monthly_sales')" required>
                        <label for="monthly_sales-file" class="cursor-pointer">
                            <i class="fas fa-file-excel text-5xl text-gray-500 mb-4"></i>
                            <p class="text-lg font-medium text-gray-300 mb-2">Click to upload Excel or CSV file</p>
                            <p id="monthly_sales-filename" class="text-sm text-blue-400 mt-2"></p>
                        </label>
                    </div>
                    <button type="submit" class="mt-4 w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        Upload & Import Monthly Sales
                    </button>
                </form>
            </div>
            @endif

        </div>
    </div>


    {{-- SUCCESS / ERROR HANDLERS --}}
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
                'Item Description': 'Laptop',
                'Brand': 'Dell',
                'Unit': 'pcs'
            }
        ];
        filename = 'items_template.xlsx';
    } else if (type === 'customers') {
        data = [
            {
                'Customer Code': 'CUST001',
                'Customer Name': 'ABC Corp'
            }
        ];
        filename = 'customers_template.xlsx';
    } else if (type === 'monthly_sales') {
        data = [
            {
                'Month': 'January',
                'Qty': '902352.22',
                'PHP': '214312824'
            },
            {
                'Month': 'February',
                'Qty': '1210814.63',
                'PHP': '284576739'
            },
            {
                'Month': 'March',
                'Qty': '1747477.00',
                'PHP': '415267198'
            }
        ];
        filename = 'monthly_sales_template.xlsx';
    }

    const ws = XLSX.utils.json_to_sheet(data);
    ws['!cols'] = Object.keys(data[0]).map(key => ({ wch: key.length + 10 }));

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Template');
    XLSX.writeFile(wb, filename);
}
</script>
@endsection