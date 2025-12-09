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
        <select name="import_type" id="import_type" required class="w-full mb-4 text-black px-2 py-1 rounded">
            <option value="">-- Select --</option>
            <option value="customers">Customers</option>
            <option value="items">Items</option>
            <option value="monthly_sales">Monthly Sales</option>
        </select>

        {{-- Info text for monthly sales --}}
        <div id="monthly_sales_info" class="hidden bg-blue-900 p-3 rounded mb-4 text-sm">
            <strong>Expected format:</strong> Excel/CSV file with columns: <code>month, qty, php</code><br>
            Example: January | 902352.22 | 214312824<br>
            <a href="{{ route('import.monthly_sales.template') }}" class="text-blue-300 underline mt-2 inline-block">Download Template</a>
        </div>

        {{-- File input --}}
        <label class="block mb-2 font-semibold">Upload File (.xlsx, .xls, .csv)</label>
        <input type="file" name="file" required class="w-full mb-4 text-white">

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded w-full">
            Upload
        </button>
    </form>
</div>

<script>
    document.getElementById('import_type').addEventListener('change', function() {
        const infoDiv = document.getElementById('monthly_sales_info');
        if (this.value === 'monthly_sales') {
            infoDiv.classList.remove('hidden');
        } else {
            infoDiv.classList.add('hidden');
        }
    });
</script>
@endsection