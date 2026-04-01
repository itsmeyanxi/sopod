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
        
        {{-- Dropdown to select type --}}
        <label class="block mb-2 font-semibold">Select Import Type</label>
        <select name="import_type" id="import_type" required class="w-full mb-4 bg-gray-700 text-gray-200 border border-gray-600 px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">-- Select --</option>
            <option value="customers">Customers</option>
            <option value="items">Items</option>
            <option value="monthly_sales">Monthly Sales</option>
            <option value="asset_classes">Asset Classes</option>
        </select>

        {{-- Info text for monthly sales --}}
        <div id="monthly_sales_info" class="hidden bg-blue-900 border border-blue-700 p-3 rounded mb-4 text-sm text-gray-200">
            <strong>Expected format:</strong> Excel/CSV file with columns: <code>month, qty, php</code><br>
            Example: January | 902352.22 | 214312824<br>
            <a href="{{ route('import.monthly_sales.template') }}" class="text-blue-400 underline mt-2 inline-block">Download Template</a>
        </div>

        {{-- Info text for asset classes --}}
        <div id="asset_classes_info" class="hidden bg-blue-900 border border-blue-700 p-3 rounded mb-4 text-sm text-gray-200">
            <strong>Upload the FA Asset Class List sheet</strong> from your <em>Fixed Asset Masterdata.xlsx</em>.<br>
            Expected columns: <code>Code, Name, Acquisition GL Code, Acquisition GL Account, Deprciation GL Code, Depreciaion Type, Useful Life</code><br>
            <span class="text-gray-400 text-xs">Rows without a Code will be skipped. Existing records matched by Code will be updated.</span>
        </div>

        {{-- File input --}}
        <label class="block mb-2 font-semibold">Upload File (.xlsx, .xls, .csv)</label>
        <input type="file" name="file" required accept=".xlsx,.xls,.csv" class="w-full mb-4 text-gray-200">

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded w-full">
            Upload
        </button>
    </form>
</div>

<script>
    document.getElementById('import_type').addEventListener('change', function() {
        document.getElementById('monthly_sales_info').classList.add('hidden');
        document.getElementById('asset_classes_info').classList.add('hidden');
        if (this.value === 'monthly_sales') {
            document.getElementById('monthly_sales_info').classList.remove('hidden');
        } else if (this.value === 'asset_classes') {
            document.getElementById('asset_classes_info').classList.remove('hidden');
        }
    });
</script>
@endsection