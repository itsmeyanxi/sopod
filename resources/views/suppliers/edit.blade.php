@extends('layouts.app')

@section('title', 'Edit Supplier')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">EDIT SUPPLIER</h1>
            <a href="{{ route('suppliers.show', $supplier->id) }}" class="bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-700 transition">
                <i class="fas fa-arrow-left mr-1"></i> Back to Details
            </a>
        </div>

        @if($errors->any())
            <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('suppliers.update', $supplier->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Supplier Code -->
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">SUPPLIER CODE: <span class="text-red-700">*</span></label>
                    <input type="text" name="supplier_code" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('supplier_code', $supplier->supplier_code) }}" required>
                </div>

                <!-- Supplier Name -->
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">SUPPLIER NAME: <span class="text-red-700">*</span></label>
                    <input type="text" name="supplier_name" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('supplier_name', $supplier->supplier_name) }}" required>
                </div>
            </div>

            <!-- Address -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">ADDRESS:</label>
                <textarea name="address" rows="3" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('address', $supplier->address) }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Payment Terms -->
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">PAYMENT TERMS:</label>
                    <input type="text" name="terms" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('terms', $supplier->terms) }}" placeholder="e.g. Net 30, COD, 60 days">
                </div>

                <!-- Contact Person -->
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">CONTACT PERSON:</label>
                    <input type="text" name="contact_person" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('contact_person', $supplier->contact_person) }}">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Email -->
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">EMAIL ADDRESSES:</label>
                    <input type="text" name="email" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('email', $supplier->email) }}" placeholder="Separate multiple with comma">
                </div>

                <!-- Contact Number -->
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">CONTACT NUMBER:</label>
                    <input type="text" name="contact_number" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('contact_number', $supplier->contact_number) }}">
                </div>
            </div>

            <!-- TIN -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">TIN (Tax Identification Number):</label>
                <input type="text" name="tin" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('tin', $supplier->tin) }}">
            </div>

            <!-- Storage / Warehouse -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">STORAGE / WAREHOUSE:</label>
                <select name="storage" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">-- Select Warehouse / Storage --</option>
                    @foreach([
                        'Crystal Cold Chain Corp.',
                        'Glacier South Refrigeration Services Corp.',
                        'Icy Point Storage and Processing Corp.',
                        'One Stop Warehousing Solutions, Inc.',
                        'Benson Industrial Cold Storage, Inc.',
                        'Apex Cold Storage Inc.',
                        'Titan Transnational Corporation',
                    ] as $warehouse)
                        <option value="{{ $warehouse }}" {{ old('storage', $supplier->storage) == $warehouse ? 'selected' : '' }}>{{ $warehouse }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Bank Information 1 -->
            <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
                <h3 class="font-semibold text-white mb-4">BANK INFORMATION (Primary)</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">BANK:</label>
                        <input type="text" name="bank" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('bank', $supplier->bank) }}" placeholder="e.g., BPI, BDO, Metrobank">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">ACCOUNT NAME:</label>
                        <input type="text" name="account_name" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('account_name', $supplier->account_name) }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">ACCOUNT NUMBER:</label>
                        <input type="text" name="account_number" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('account_number', $supplier->account_number) }}">
                    </div>
                </div>
            </div>

            <!-- Bank Information 2 -->
            <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
                <h3 class="font-semibold text-white mb-4">BANK INFORMATION (Secondary)</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">BANK:</label>
                        <input type="text" name="bank2" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('bank2', $supplier->bank2) }}" placeholder="e.g., BPI, BDO, Metrobank">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">ACCOUNT NAME:</label>
                        <input type="text" name="account_name2" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('account_name2', $supplier->account_name2) }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-300 mb-2">ACCOUNT NUMBER:</label>
                        <input type="text" name="account_number2" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('account_number2', $supplier->account_number2) }}">
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('suppliers.show', $supplier->id) }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-700 transition">
                    Cancel
                </a>
                <button type="submit" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800">
                    <i class="fas fa-save mr-1"></i> Update Supplier
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
