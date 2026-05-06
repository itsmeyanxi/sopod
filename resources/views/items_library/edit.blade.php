@extends('layouts.app')

@section('content')
<div class="p-6 bg-gray-900 min-h-screen text-gray-200">
    <h1 class="text-2xl font-bold mb-6">Edit Item</h1>

    <div class="bg-gray-800/90 border border-gray-700 p-6 rounded-xl shadow-lg max-w-3xl mx-auto">
        <form action="{{ route('items_library.update', $item->id) }}" method="POST">
            @csrf @method('PUT')

            @if($errors->any())
                <div class="bg-red-800 text-white p-3 rounded mb-4 text-sm">
                    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Item Code <span class="text-red-400">*</span></label>
                    <input type="text" name="item_code" value="{{ old('item_code', $item->item_code) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 text-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Type <span class="text-red-400">*</span></label>
                    <select name="type" class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 text-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="trade" {{ old('type', $item->type) === 'trade' ? 'selected' : '' }}>Trade</option>
                        <option value="non_trade" {{ old('type', $item->type) === 'non_trade' ? 'selected' : '' }}>Non-Trade</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-400 mb-1">Description</label>
                    <input type="text" name="item_description" value="{{ old('item_description', $item->item_description) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 text-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Category</label>
                    <input type="text" name="item_category" value="{{ old('item_category', $item->item_category) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 text-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Brand</label>
                    <input type="text" name="brand" value="{{ old('brand', $item->brand) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 text-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Unit</label>
                    <input type="text" name="unit" value="{{ old('unit', $item->unit) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 text-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('items_library.index') }}"
                   class="bg-gray-700 hover:bg-gray-600 text-gray-200 px-4 py-2 rounded-md transition">Cancel</a>
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md shadow transition">
                    Update Item
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
