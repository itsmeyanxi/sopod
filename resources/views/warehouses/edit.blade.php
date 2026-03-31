@extends('layouts.app')
@section('title', 'Edit Warehouse')
@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold">EDIT WAREHOUSE</h1>
            <a href="{{ route('warehouses.index') }}" class="bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-700 transition">
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

        <form action="{{ route('warehouses.update', $warehouse->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">WAREHOUSE CODE: <span class="text-red-700">*</span></label>
                    <input type="text" name="warehouse_code" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('warehouse_code', $warehouse->warehouse_code) }}" required>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">WAREHOUSE NAME: <span class="text-red-700">*</span></label>
                    <input type="text" name="warehouse_name" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('warehouse_name', $warehouse->warehouse_name) }}" required>
                </div>
            </div>

            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">ADDRESS:</label>
                <textarea name="address" rows="3" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('address', $warehouse->address) }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">EMAIL ADDRESS:</label>
                    <input type="email" name="email" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('email', $warehouse->email) }}">
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">CONTACT NUMBER:</label>
                    <input type="text" name="contact_number" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('contact_number', $warehouse->contact_number) }}">
                </div>
            </div>

            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">TIN (Tax Identification Number):</label>
                <input type="text" name="tin" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('tin', $warehouse->tin) }}">
            </div>

            <!-- Status -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">STATUS:</label>
                <select name="status" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="active" {{ old('status', $warehouse->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $warehouse->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <!-- Bank Information -->
            <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
                <h3 class="font-semibold text-white mb-4">BANK INFORMATION</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">BANK:</label>
                        <input type="text" name="bank" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('bank', $warehouse->bank) }}" placeholder="e.g., BPI, BDO, Metrobank">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">ACCOUNT NAME:</label>
                        <input type="text" name="account_name" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('account_name', $warehouse->account_name) }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">ACCOUNT NUMBER:</label>
                        <input type="text" name="account_number" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('account_number', $warehouse->account_number) }}">
                    </div>
                </div>
            </div>

            <!-- Existing Documents -->
            @if($warehouse->documents && count($warehouse->documents) > 0)
            <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
                <h3 class="font-semibold text-white mb-4"><i class="fas fa-folder-open mr-2"></i>EXISTING DOCUMENTS</h3>
                <div class="space-y-2">
                    @foreach($warehouse->documents as $doc)
                    <div class="flex items-center justify-between bg-gray-800 rounded px-3 py-2">
                        <span class="text-gray-300"><i class="fas fa-file mr-2 text-purple-700"></i>{{ $doc['name'] }}</span>
                        <a href="{{ Storage::url($doc['path']) }}" target="_blank" class="text-blue-700 hover:text-blue-700 text-sm"><i class="fas fa-download mr-1"></i>View</a>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Add New Documents -->
            <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
                <h3 class="font-semibold text-white mb-4"><i class="fas fa-plus-circle mr-2"></i>ADD NEW DOCUMENTS</h3>
                <div id="fileInputs">
                    <div class="file-row mb-3 bg-gray-800 border border-gray-700 rounded p-3">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-gray-300 text-sm mb-1">Document Label:</label>
                                <input type="text" name="document_names[]" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="e.g., Business Permit, Contract...">
                            </div>
                            <div>
                                <label class="block text-gray-300 text-sm mb-1">File:</label>
                                <input type="file" name="documents[]" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm" accept=".png,.jpg,.jpeg,.gif,.pdf,.doc,.docx,.xls,.xlsx">
                            </div>
                        </div>
                        <p class="text-gray-300 text-xs mt-1">Accepted: PNG, JPG, PDF, DOC, DOCX, XLS, XLSX (max 10MB)</p>
                    </div>
                </div>
                <button type="button" onclick="addFileInput()" class="text-blue-700 hover:text-blue-700 text-sm">
                    <i class="fas fa-plus mr-1"></i> Add Another Document
                </button>
            </div>

            <div class="flex justify-end gap-4">
                <a href="{{ route('warehouses.index') }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-700 transition">Cancel</a>
                <button type="submit" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800">
                    <i class="fas fa-save mr-1"></i> Update Warehouse
                </button>
            </div>
        </form>
    </div>
</div>
<script>
function addFileInput() {
    const container = document.getElementById('fileInputs');
    const div = document.createElement('div');
    div.className = 'file-row mb-3 bg-gray-800 border border-gray-700 rounded p-3 relative';
    div.innerHTML = `
        <button type="button" onclick="this.parentElement.remove()" class="absolute top-2 right-2 text-red-700 hover:text-red-700 text-sm"><i class="fas fa-times"></i></button>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
                <label class="block text-gray-300 text-sm mb-1">Document Label:</label>
                <input type="text" name="document_names[]" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm" placeholder="e.g., Business Permit...">
            </div>
            <div>
                <label class="block text-gray-300 text-sm mb-1">File:</label>
                <input type="file" name="documents[]" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm" accept=".png,.jpg,.jpeg,.gif,.pdf,.doc,.docx,.xls,.xlsx">
            </div>
        </div>
        <p class="text-gray-300 text-xs mt-1">Accepted: PNG, JPG, PDF, DOC, DOCX, XLS, XLSX (max 10MB)</p>
    `;
    container.appendChild(div);
}
</script>
@endsection