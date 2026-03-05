@extends('layouts.app')
@section('title', 'Create Warehouse')
@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">CREATE NEW WAREHOUSE</h1>
            <a href="{{ route('warehouses.index') }}" class="bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
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

        <form action="{{ route('warehouses.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">WAREHOUSE CODE: <span class="text-red-400">*</span></label>
                    <input type="text" name="warehouse_code" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('warehouse_code') }}" required>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">WAREHOUSE NAME: <span class="text-red-400">*</span></label>
                    <input type="text" name="warehouse_name" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('warehouse_name') }}" required>
                </div>
            </div>

            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">ADDRESS:</label>
                <textarea name="address" rows="3" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('address') }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">EMAIL ADDRESS:</label>
                    <input type="email" name="email" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('email') }}">
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">CONTACT NUMBER:</label>
                    <input type="text" name="contact_number" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('contact_number') }}">
                </div>
            </div>

            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">TIN (Tax Identification Number):</label>
                <input type="text" name="tin" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('tin') }}">
            </div>

            <!-- Bank Information -->
            <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
                <h3 class="font-semibold text-white mb-4">BANK INFORMATION</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">BANK:</label>
                        <input type="text" name="bank" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('bank') }}" placeholder="e.g., BPI, BDO, Metrobank">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">ACCOUNT NAME:</label>
                        <input type="text" name="account_name" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('account_name') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">ACCOUNT NUMBER:</label>
                        <input type="text" name="account_number" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('account_number') }}">
                    </div>
                </div>
            </div>

            <!-- Business Documents -->
            <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
                <h3 class="font-semibold text-white mb-4"><i class="fas fa-folder-open mr-2"></i>BUSINESS DOCUMENTS</h3>
                <p class="text-gray-400 text-sm mb-4">Upload permits, contracts, and other required documents.</p>
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
                        <p class="text-gray-500 text-xs mt-1">Accepted: PNG, JPG, PDF, DOC, DOCX, XLS, XLSX (max 10MB)</p>
                    </div>
                </div>
                <button type="button" onclick="addFileInput()" class="text-blue-400 hover:text-blue-300 text-sm">
                    <i class="fas fa-plus mr-1"></i> Add Another Document
                </button>
            </div>

            <div class="flex justify-end gap-4">
                <a href="{{ route('warehouses.index') }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-600 transition">Cancel</a>
                <button type="submit" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800">
                    <i class="fas fa-save mr-1"></i> Create Warehouse
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
        <button type="button" onclick="this.parentElement.remove()" class="absolute top-2 right-2 text-red-400 hover:text-red-300 text-sm"><i class="fas fa-times"></i></button>
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
        <p class="text-gray-500 text-xs mt-1">Accepted: PNG, JPG, PDF, DOC, DOCX, XLS, XLSX (max 10MB)</p>
    `;
    container.appendChild(div);
}
</script>
@endsection