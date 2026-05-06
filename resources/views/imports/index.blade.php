@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto bg-gray-800 p-6 rounded-lg text-white">
    <h2 class="text-xl font-bold mb-4">📥 Import Data</h2>

    @if(session('success'))
        <div class="bg-green-600 p-3 rounded mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-600 p-3 rounded mb-4">{{ session('error') }}</div>
    @endif

    <form action="{{ route('import.upload') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <label class="block mb-2 font-semibold">Select Import Type</label>
        <select name="import_type" id="import_type" required class="w-full mb-4 bg-gray-700 text-gray-200 border border-gray-600 px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">-- Select --</option>

            @if($context === 'vendors')
                <option value="vendors" selected>Vendors / Suppliers (Trade, Non-Trade, Employees)</option>
            @else
                <option value="customers">Customers</option>
                <option value="items">Items</option>
                <option value="monthly_sales">Monthly Sales</option>
                <option value="asset_classes">Asset Classes</option>
                <option value="fixed_assets">Fixed Assets</option>
                <option value="vendors">Vendors / Suppliers</option>
            @endif
        </select>

        {{-- Info panels --}}
        <div id="vendors_info" class="{{ $context === 'vendors' ? '' : 'hidden' }} bg-blue-900 border border-blue-700 p-3 rounded mb-4 text-sm text-gray-200">
            <strong>Vendor / Supplier Import</strong><br>
            Upload a <code>.xlsx</code>, <code>.xls</code>, or <code>.csv</code> file with a <strong>header row</strong>.<br>
            Supported headers (all optional except <code>Vendor Code</code> or <code>Vendor Name</code>):<br>
            <code class="text-xs leading-loose">
                Vendor Code, Vendor Name, Category, Group, GL Account, Status,<br>
                Company, EE ID, Last Name, First Name, Middle Name,<br>
                Position, Department, Location, Office Address, Date Hired
            </code><br>
            <span class="text-gray-400 text-xs">Category values: <strong>TRADE</strong>, <strong>NON TRADE</strong>, <strong>EMPLOYEES</strong>. Existing records matched by Vendor Code will be updated.</span>
        </div>

        <div id="monthly_sales_info" class="hidden bg-blue-900 border border-blue-700 p-3 rounded mb-4 text-sm text-gray-200">
            <strong>Expected format:</strong> Excel/CSV with columns: <code>month, qty, php</code><br>
            <a href="{{ route('import.monthly_sales.template') }}" class="text-blue-400 underline mt-2 inline-block">Download Template</a>
        </div>

        <div id="fixed_assets_info" class="hidden bg-blue-900 border border-blue-700 p-3 rounded mb-4 text-sm text-gray-200">
            <strong>Upload the Fixed Asset Masterdata.xlsx</strong> file.<br>
            Expected sheet: <code>Monthly Depreciation Sched</code>. Data starts from row 5.
        </div>

        <div id="asset_classes_info" class="hidden bg-blue-900 border border-blue-700 p-3 rounded mb-4 text-sm text-gray-200">
            <strong>Upload the FA Asset Class List sheet.</strong><br>
            Expected columns: <code>Code, Name, Acquisition GL Code, Acquisition GL Account, Depreciation GL Code, Depreciation Type, Useful Life</code>
        </div>

        <label class="block mb-2 font-semibold">Upload File (.xlsx, .xls, .csv)</label>
        <input type="file" name="file" required accept=".xlsx,.xls,.csv" class="w-full mb-4 text-gray-200">

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded w-full">
            Upload
        </button>
    </form>
</div>

<script>
document.getElementById('import_type').addEventListener('change', function () {
    ['vendors_info','monthly_sales_info','fixed_assets_info','asset_classes_info'].forEach(id =>
        document.getElementById(id).classList.add('hidden')
    );
    const map = { vendors: 'vendors_info', monthly_sales: 'monthly_sales_info', fixed_assets: 'fixed_assets_info', asset_classes: 'asset_classes_info' };
    if (map[this.value]) document.getElementById(map[this.value]).classList.remove('hidden');
});
</script>
@endsection
