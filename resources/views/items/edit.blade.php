@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex items-center space-x-4 mb-6">
        <a href="{{ route('items.index', $item->id) }}" class="text-blue-600 hover:text-blue-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <h1 class="text-3xl font-bold text-white">Edit Item</h1>
    </div>

    <!-- Success Message -->
    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <!-- Edit Form -->
    <div class="bg-gray-900 text-white rounded-lg shadow-lg p-8">
    <form action="{{ route('items.update', $item->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Item Code -->
            <div>
                <label for="item_code" class="block text-sm font-semibold text-gray-200 mb-2">
                    Item Code <span class="text-red-400">*</span>
                </label>
                <input type="text" 
                       name="item_code" 
                       id="item_code" 
                       value="{{ old('item_code', $item->item_code) }}"
                       class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white focus:ring-2 focus:ring-blue-400 focus:border-blue-400"
                       required>
            </div>

            <!-- Brand -->
            <div>
                <label for="brand" class="block text-sm font-semibold text-gray-200 mb-2">
                    Brand
                </label>
                <input type="text" 
                       name="brand" 
                       id="brand" 
                       value="{{ old('brand', $item->brand) }}"
                       class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
            </div>

            <!-- Category -->
            <div>
                <label for="item_category" class="block text-sm font-semibold text-gray-200 mb-2">
                    Category
                </label>
                <input type="text" 
                       name="item_category" 
                       id="item_category" 
                       value="{{ old('item_category', $item->item_category) }}"
                       class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
            </div>

            <!-- Description -->
            <div class="md:col-span-2">
                <label for="item_description" class="block text-sm font-semibold text-gray-200 mb-2">
                     Description
                </label>
                <textarea name="item_description" 
                          id="item_description" 
                          rows="4"
                          class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white focus:ring-2 focus:ring-blue-400 focus:border-blue-400">{{ old('item_description', $item->item_description) }}</textarea>
            </div>
        </div>

        <div class="flex justify-end space-x-4 mt-8">
            <a href="{{ route('items.index', $item->id) }}" class="bg-gray-700 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition">
                Cancel
            </a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition">
                Update Item
            </button>
        </div>

    </form>
</div>

@endsection