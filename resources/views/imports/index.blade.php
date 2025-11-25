@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto bg-gray-800 p-6 rounded-lg text-white">
    <h2 class="text-xl font-bold mb-4">📥 Import Data</h2>

    @if(session('success'))
        <div class="bg-green-600 p-3 rounded mb-4">{{ session('success') }}</div>
    @endif

    <form action="{{ route('import.upload') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        {{-- Dropdown to select type --}}
        <label class="block mb-2 font-semibold">Select Import Type</label>
        <select name="import_type" required class="w-full mb-4 text-black px-2 py-1 rounded">
            <option value="">-- Select --</option>
            <option value="customers">Customers</option>
            <option value="items">Items</option>
        </select>

        {{-- File input --}}
        <label class="block mb-2 font-semibold">Upload Excel File (.xlsx)</label>
        <input type="file" name="file" required class="w-full mb-4 text-black">

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded w-full">
            Upload
        </button>
    </form>
</div>
@endsection
