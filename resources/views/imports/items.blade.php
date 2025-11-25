@extends('layouts.app')

@section('title', 'Import Items')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-gray-800 rounded-lg shadow-lg p-6">
        <h2 class="text-2xl font-bold mb-6">Import Items from Excel</h2>

        @if(session('success'))
            <div class="bg-green-600 text-white p-4 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-600 text-white p-4 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-600 text-white p-4 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Upload Form -->
        <form action="{{ route('import.items.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            
            <div>
                <label class="block text-sm font-medium mb-2">Select Excel File</label>
                <input 
                    type="file" 
                    name="file" 
                    accept=".xlsx,.xls,.csv"
                    required
                    class="block w-full text-sm text-gray-300
                        file:mr-4 file:py-2 file:px-4
                        file:rounded file:border-0
                        file:text-sm file:font-semibold
                        file:bg-blue-600 file:text-white
                        hover:file:bg-blue-700
                        cursor-pointer"
                >
                <p class="text-sm text-gray-400 mt-2">Accepted formats: .xlsx, .xls, .csv</p>
            </div>

            <div class="flex items-center justify-between">
                <button 
                    type="submit" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded font-semibold transition"
                >
                    Upload & Import
                </button>
                
                <a 
                    href="{{ route('items.index') }}" 
                    class="text-gray-400 hover:text-white transition"
                >
                    Cancel
                </a>
            </div>
        </form>

        <!-- Instructions -->
        <div class="mt-8 bg-gray-700 p-4 rounded">
            <h3 class="font-semibold mb-3">📋 Excel File Format Guidelines</h3>
            <p class="text-sm text-gray-300 mb-2">Your Excel file can have the following columns (all optional):</p>
            <ul class="text-sm text-gray-300 space-y-1 list-disc list-inside">
                <li><strong>item_code</strong> - Item code/SKU</li>
                <li><strong>item_description</strong> - Item description</li>
                <li><strong>item_group</strong> - Item category/group</li>
                <li><strong>brand</strong> - Brand name</li>
                <li><strong>uom</strong> - Unit of measurement</li>
                <li><strong>unit_price</strong> - Price per unit</li>
            </ul>
            
            <div class="mt-4 pt-4 border-t border-gray-600">
                <a 
                    href="{{ route('import.items.template') }}" 
                    class="inline-flex items-center text-blue-400 hover:text-blue-300 text-sm"
                >
                    📥 Download Sample Template
                </a>
            </div>
        </div>
    </div>
</div>
@endsection