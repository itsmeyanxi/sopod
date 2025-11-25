@extends('layouts.app')

@section('content')
<div class="p-6 bg-gray-900 min-h-screen text-gray-200">
    <h1 class="text-2xl font-bold mb-6">Add Item</h1>

    {{-- 💠 Form Container --}}
    <div class="bg-gray-800/90 border border-gray-700 p-6 rounded-xl shadow-lg max-w-3xl mx-auto">
        <form action="{{ route('items.store') }}" method="POST">
            @csrf

            {{-- 🔹 Item Description --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-300 mb-1">Item Description</label>
                <input 
                    type="text" 
                    name="item_description" 
                    class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 text-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent" 
                    placeholder="Enter Item Description"
                >
            </div>

            {{-- 🔹 Item Code --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-300 mb-1">Item Code</label>
                <input 
                    type="text" 
                    name="item_code" 
                    class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 text-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent" 
                    placeholder="Enter Item Code"
                >
            </div>

            {{-- 🔹 Category --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-300 mb-1">Category</label>
                <input 
                    type="text" 
                    name="item_category" 
                    class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 text-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent" 
                    placeholder="Enter Category"
                >
            </div>

            {{-- 🔹 Brand --}}
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-300 mb-1">Brand</label>
                <input 
                    type="text" 
                    name="brand" 
                    class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 text-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent" 
                    placeholder="Enter Brand"
                >
            </div>

            {{-- 🔘 Buttons --}}
            <div class="flex justify-end gap-3">
                <a href="{{ route('items.index') }}" 
                   class="bg-gray-700 hover:bg-gray-600 text-gray-200 px-4 py-2 rounded-md transition">
                    Cancel
                </a>

                <button 
                    type="submit" 
                    class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-md shadow transition">
                    Save Item
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
