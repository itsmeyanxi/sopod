@extends('layouts.app')
@section('title', 'Create Storage')
@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">CREATE NEW STORAGE</h1>
            <a href="{{ route('storages.index') }}" class="bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-200 transition">
                <i class="fas fa-arrow-left mr-1"></i> Back to List
            </a>
        </div>

        @if($errors->any())
            <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('storages.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">STORAGE CODE: <span class="text-blue-700 text-xs">(Auto-Generated)</span></label>
                    <div class="flex items-center gap-2">
                        <input type="text" id="storage_code_display" class="flex-1 bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white focus:outline-none cursor-not-allowed" placeholder="Will be auto-generated" readonly>
                        <input type="hidden" name="storage_code" id="storage_code_input">
                    </div>
                    <p class="text-gray-500 text-xs mt-1">Code will be automatically generated on save</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">STORAGE NAME: <span class="text-red-700">*</span></label>
                    <input type="text" name="storage_name" id="storage_name" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('storage_name') }}" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">WAREHOUSE:</label>
                    <select name="warehouse_id" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">-- Select Warehouse --</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ old('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->warehouse_name }} ({{ $warehouse->warehouse_code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">LOCATION:</label>
                    <input type="text" name="location" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('location') }}" placeholder="e.g., Building A, Floor 2">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">TEMPERATURE CONTROLLED:</label>
                    <select name="temperature_controlled" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="No" {{ old('temperature_controlled', 'No') == 'No' ? 'selected' : '' }}>No</option>
                        <option value="Yes" {{ old('temperature_controlled') == 'Yes' ? 'selected' : '' }}>Yes</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">CAPACITY (Liters):</label>
                    <input type="number" name="capacity" step="0.01" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('capacity') }}" placeholder="e.g., 10000.00">
                </div>
            </div>

            <div class="mb-6">
                <label class="block font-semibold text-gray-500 mb-2">DESCRIPTION:</label>
                <textarea name="description" rows="3" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('description') }}</textarea>
            </div>

            <div class="flex justify-end gap-4">
                <a href="{{ route('storages.index') }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-200 transition">Cancel</a>
                <button type="submit" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800">
                    <i class="fas fa-save mr-1"></i> Create Storage
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// ✅ Auto-generate storage code on form submission
document.querySelector('form').addEventListener('submit', function(e) {
    const codeInput = document.getElementById('storage_code_input');
    const codeDisplay = document.getElementById('storage_code_display');

    if (!codeInput.value || codeInput.value.trim() === '') {
        const storageName = document.getElementById('storage_name').value;
        if (storageName.trim()) {
            const abbr = storageName.split(' ')
                .map(word => word.charAt(0).toUpperCase())
                .join('')
                .substring(0, 3);

            const timestamp = Date.now().toString().slice(-6);
            const generatedCode = `ST-${abbr}-${timestamp}`;

            codeInput.value = generatedCode;
            codeDisplay.value = generatedCode;
        } else {
            alert('Please enter a storage name first');
            e.preventDefault();
            return false;
        }
    }
});

// ✅ Show preview of code format when name changes
document.getElementById('storage_name').addEventListener('change', function() {
    const name = this.value.trim();
    if (name && !document.getElementById('storage_code_input').value) {
        const abbr = name.split(' ')
            .map(word => word.charAt(0).toUpperCase())
            .join('')
            .substring(0, 3);
        const preview = `ST-${abbr}-XXXXXX (will be finalized on save)`;
        document.getElementById('storage_code_display').placeholder = preview;
    }
});
</script>
@endsection
