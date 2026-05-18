@extends('layouts.app')

@section('title', 'Edit Vendor')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">EDIT VENDOR</h1>
            <a href="{{ route('suppliers.index', ['tab' => 'vendors']) }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-500 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>

        @if($errors->any())
            <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form action="{{ route('suppliers.vendors.update', $vendor->id) }}" method="POST">
            @csrf @method('PUT')

            {{-- Core Vendor Fields --}}
            <h3 class="text-sm font-semibold text-gray-400 uppercase mb-3">Vendor Info</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1">VENDOR CODE <span class="text-red-500">*</span></label>
                    <input type="text" name="vendor_code" value="{{ old('vendor_code', $vendor->vendor_code) }}" required
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1">VENDOR NAME <span class="text-red-500">*</span></label>
                    <input type="text" name="vendor_name" value="{{ old('vendor_name', $vendor->vendor_name) }}" required
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1">2307 NAME</label>
                    <input type="text" name="name_2307" value="{{ old('name_2307', $vendor->name_2307) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1">COMPANY</label>
                    <input type="text" name="company" value="{{ old('company', $vendor->company) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1">CATEGORY</label>
                    <select name="category" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="">— Select —</option>
                        @foreach(['TRADE', 'NON TRADE', 'EMPLOYEES'] as $cat)
                            <option value="{{ $cat }}" {{ old('category', $vendor->category) === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1">GROUP</label>
                    <input type="text" name="group" value="{{ old('group', $vendor->group) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1">GL ACCOUNT</label>
                    <input type="text" name="gl_account" value="{{ old('gl_account', $vendor->gl_account) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1">STATUS <span class="text-red-500">*</span></label>
                    <select name="status" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="active" {{ old('status', $vendor->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $vendor->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>

            {{-- Billing Address --}}
            <h3 class="text-sm font-semibold text-gray-400 uppercase mb-3 border-t border-gray-700 pt-4">Billing Address</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-300 mb-1">STREET #/PO BOX</label>
                    <input type="text" name="billing_street" value="{{ old('billing_street', $vendor->billing_street) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1">BLOCK</label>
                    <input type="text" name="billing_block" value="{{ old('billing_block', $vendor->billing_block) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1">CITY</label>
                    <input type="text" name="billing_city" value="{{ old('billing_city', $vendor->billing_city) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1">ZIP CODE</label>
                    <input type="text" name="billing_zip" value="{{ old('billing_zip', $vendor->billing_zip) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1">COUNTRY</label>
                    <input type="text" name="billing_country" value="{{ old('billing_country', $vendor->billing_country) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
            </div>

            {{-- Shipping Address --}}
            <h3 class="text-sm font-semibold text-gray-400 uppercase mb-3 border-t border-gray-700 pt-4">Shipping Address</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-300 mb-1">STREET #/PO BOX</label>
                    <input type="text" name="shipping_street" value="{{ old('shipping_street', $vendor->shipping_street) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1">BLOCK</label>
                    <input type="text" name="shipping_block" value="{{ old('shipping_block', $vendor->shipping_block) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1">CITY</label>
                    <input type="text" name="shipping_city" value="{{ old('shipping_city', $vendor->shipping_city) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1">ZIP CODE</label>
                    <input type="text" name="shipping_zip" value="{{ old('shipping_zip', $vendor->shipping_zip) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1">COUNTRY</label>
                    <input type="text" name="shipping_country" value="{{ old('shipping_country', $vendor->shipping_country) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
            </div>

            {{-- Financial / Tax Info --}}
            <h3 class="text-sm font-semibold text-gray-400 uppercase mb-3 border-t border-gray-700 pt-4">Financial / Tax Info</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1">PAYMENT TERMS</label>
                    <input type="text" name="payment_terms" value="{{ old('payment_terms', $vendor->payment_terms) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1">SELLING PRICE LIST</label>
                    <input type="text" name="selling_price_list" value="{{ old('selling_price_list', $vendor->selling_price_list) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1">VAT</label>
                    <input type="text" name="vat" value="{{ old('vat', $vendor->vat) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1">WITHHOLDING</label>
                    <input type="text" name="withholding" value="{{ old('withholding', $vendor->withholding) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1">REGISTRATION</label>
                    <input type="text" name="registration" value="{{ old('registration', $vendor->registration) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1">TIN</label>
                    <input type="text" name="tin" value="{{ old('tin', $vendor->tin) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-300 mb-1">ACCOUNT</label>
                    <input type="text" name="account" value="{{ old('account', $vendor->account) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
            </div>

            {{-- Employee Fields --}}
            <h3 class="text-sm font-semibold text-gray-400 uppercase mb-3 border-t border-gray-700 pt-4">Employee Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1">EE ID</label>
                    <input type="text" name="ee_id" value="{{ old('ee_id', $vendor->ee_id) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1">LAST NAME</label>
                    <input type="text" name="last_name" value="{{ old('last_name', $vendor->last_name) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1">FIRST NAME</label>
                    <input type="text" name="first_name" value="{{ old('first_name', $vendor->first_name) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1">MIDDLE NAME</label>
                    <input type="text" name="middle_name" value="{{ old('middle_name', $vendor->middle_name) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1">POSITION</label>
                    <input type="text" name="position" value="{{ old('position', $vendor->position) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1">DEPARTMENT</label>
                    <input type="text" name="department" value="{{ old('department', $vendor->department) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1">LOCATION</label>
                    <input type="text" name="location" value="{{ old('location', $vendor->location) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1">OFFICE ADDRESS</label>
                    <input type="text" name="office_address" value="{{ old('office_address', $vendor->office_address) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1">DATE HIRED</label>
                    <input type="date" name="date_hired" value="{{ old('date_hired', $vendor->date_hired?->format('Y-m-d')) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
            </div>

            <div class="flex justify-end gap-4">
                <a href="{{ route('suppliers.index', ['tab' => 'vendors']) }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-600">Cancel</a>
                <button type="submit" class="bg-gradient-to-r from-orange-600 to-orange-700 text-white px-6 py-2 rounded hover:from-orange-700 hover:to-orange-800">
                    <i class="fas fa-save mr-1"></i> Update Vendor
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
