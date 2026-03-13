@extends('layouts.app')
@section('title', 'Edit Storage')
@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">EDIT STORAGE</h1>
            <a href="{{ route('storages.show', $storage->id) }}" class="bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>

        @if($errors->any())
            <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('storages.update', $storage->id) }}" method="POST">
            @csrf @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">STORAGE CODE:</label>
                    <input type="text" value="{{ $storage->storage_code }}" class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white cursor-not-allowed" readonly>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">STORAGE NAME: <span class="text-red-400">*</span></label>
                    <input type="text" name="storage_name" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('storage_name', $storage->storage_name) }}" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">WAREHOUSE:</label>
                    <select name="warehouse_id" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">-- Select Warehouse --</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ old('warehouse_id', $storage->warehouse_id) == $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->warehouse_name }} ({{ $warehouse->warehouse_code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">LOCATION:</label>
                    <input type="text" name="location" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('location', $storage->location) }}">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">TEMPERATURE CONTROLLED:</label>
                    <select name="temperature_controlled" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="No" {{ old('temperature_controlled', $storage->temperature_controlled) == 'No' ? 'selected' : '' }}>No</option>
                        <option value="Yes" {{ old('temperature_controlled', $storage->temperature_controlled) == 'Yes' ? 'selected' : '' }}>Yes</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">CAPACITY (Liters):</label>
                    <input type="number" name="capacity" step="0.01" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('capacity', $storage->capacity) }}">
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">STATUS:</label>
                    <select name="status" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="active" {{ old('status', $storage->status) == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $storage->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">DESCRIPTION:</label>
                <textarea name="description" rows="3" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('description', $storage->description) }}</textarea>
            </div>

            <div class="flex justify-end gap-4">
                <a href="{{ route('storages.show', $storage->id) }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-600 transition">Cancel</a>
                <button type="submit" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800">
                    <i class="fas fa-save mr-1"></i> Update Storage
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
