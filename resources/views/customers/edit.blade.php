@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-6 py-10 bg-gray-900 min-h-screen text-gray-100">
    <!-- Header -->
    <div class="flex items-center space-x-4 mb-8">
        <a href="{{ route('customers.index') }}" class="text-gray-300 hover:text-white transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <h1 class="text-3xl font-bold text-white">Edit Customer</h1>
    </div>

    <!-- Success Message -->
    @if (session('success'))
        <div class="bg-green-800 border border-green-600 text-green-100 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <!-- Edit Form -->
    <div class="bg-gray-800 rounded-xl shadow-lg p-8">
        <form action="{{ route('customers.update', $customer->id) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Basic Information Section -->
            <div class="mb-8">
                <h2 class="text-xl font-bold text-white mb-4 border-b border-gray-700 pb-2">Basic Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Customer Code -->
                    <div>
                        <label for="customer_code" class="block text-sm font-semibold text-gray-300 mb-2">
                            Customer Code <span class="text-red-400">*</span>
                        </label>
                        <input type="text" 
                               name="customer_code" 
                               id="customer_code" 
                               value="{{ old('customer_code', $customer->customer_code) }}"
                               class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('customer_code') border-red-500 @enderror"
                               required>
                        @error('customer_code')
                            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Customer Name -->
                    <div>
                        <label for="customer_name" class="block text-sm font-semibold text-gray-300 mb-2">
                            Customer Name <span class="text-red-400">*</span>
                        </label>
                        <input type="text" 
                               name="customer_name" 
                               id="customer_name" 
                               value="{{ old('customer_name', $customer->customer_name) }}"
                               class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('customer_name') border-red-500 @enderror"
                               required>
                        @error('customer_name')
                            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Business Style -->
                    <div>
                        <label for="business_style" class="block text-sm font-semibold text-gray-300 mb-2">
                            Business Style
                        </label>
                        <input type="text" 
                               name="business_style" 
                               id="business_style" 
                               value="{{ old('business_style', $customer->business_style) }}"
                               class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>

                    <!-- Branch -->
                    <div>
                        <label for="branch" class="block text-sm font-semibold text-gray-300 mb-2">
                            Branch
                        </label>
                        <input type="text" 
                               name="branch" 
                               id="branch" 
                               value="{{ old('branch', $customer->branch) }}"
                               class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>

                    <!-- Customer Group -->
                    <div>
                        <label for="customer_group" class="block text-sm font-semibold text-gray-300 mb-2">
                            Customer Group
                        </label>
                        <input type="text" 
                               name="customer_group" 
                               id="customer_group" 
                               value="{{ old('customer_group', $customer->customer_group) }}"
                               class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>

                    <!-- Customer Type -->
                    <div>
                        <label for="customer_type" class="block text-sm font-semibold text-gray-300 mb-2">
                            Customer Type
                        </label>
                        <input type="text" 
                               name="customer_type" 
                               id="customer_type" 
                               value="{{ old('customer_type', $customer->customer_type) }}"
                               class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>

                    <!-- Currency -->
                    <div>
                        <label for="currency" class="block text-sm font-semibold text-gray-300 mb-2">
                            Currency
                        </label>
                        <input type="text" 
                               name="currency" 
                               id="currency" 
                               value="{{ old('currency', $customer->currency) }}"
                               class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               placeholder="e.g., PHP, USD">
                    </div>
                </div>
            </div>

            <!-- Contact Information Section -->
            <div class="mb-8">
                <h2 class="text-xl font-bold text-white mb-4 border-b border-gray-700 pb-2">Contact Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Telephone 1 -->
                    <div>
                        <label for="telephone_1" class="block text-sm font-semibold text-gray-300 mb-2">
                            Telephone 1
                        </label>
                        <input type="text" 
                               name="telephone_1" 
                               id="telephone_1" 
                               value="{{ old('telephone_1', $customer->telephone_1) }}"
                               class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>

                    <!-- Telephone 2 -->
                    <div>
                        <label for="telephone_2" class="block text-sm font-semibold text-gray-300 mb-2">
                            Telephone 2
                        </label>
                        <input type="text" 
                               name="telephone_2" 
                               id="telephone_2" 
                               value="{{ old('telephone_2', $customer->telephone_2) }}"
                               class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>

                    <!-- Mobile -->
                    <div>
                        <label for="mobile" class="block text-sm font-semibold text-gray-300 mb-2">
                            Mobile
                        </label>
                        <input type="text" 
                               name="mobile" 
                               id="mobile" 
                               value="{{ old('mobile', $customer->mobile) }}"
                               class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-300 mb-2">
                            Email
                        </label>
                        <input type="email" 
                               name="email" 
                               id="email" 
                               value="{{ old('email', $customer->email) }}"
                               class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>

                    <!-- Website -->
                    <div>
                        <label for="website" class="block text-sm font-semibold text-gray-300 mb-2">
                            Website
                        </label>
                        <input type="text" 
                               name="website" 
                               id="website" 
                               value="{{ old('website', $customer->website) }}"
                               class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>

                    <!-- Name of Contact -->
                    <div>
                        <label for="name_of_contact" class="block text-sm font-semibold text-gray-300 mb-2">
                            Name of Contact
                        </label>
                        <input type="text" 
                               name="name_of_contact" 
                               id="name_of_contact" 
                               value="{{ old('name_of_contact', $customer->name_of_contact) }}"
                               class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                </div>
            </div>

            <!-- Address Information Section -->
            <div class="mb-8">
                <h2 class="text-xl font-bold text-white mb-4 border-b border-gray-700 pb-2">Address Information</h2>
                <div class="grid grid-cols-1 gap-6">
                    
                    <!-- Billing Address -->
                    <div>
                        <label for="billing_address" class="block text-sm font-semibold text-gray-300 mb-2">
                            Billing Address
                        </label>
                        <textarea name="billing_address" 
                                  id="billing_address" 
                                  rows="3"
                                  class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">{{ old('billing_address', $customer->billing_address) }}</textarea>
                    </div>

                    <!-- Shipping Address -->
                    <div>
                        <label for="shipping_address" class="block text-sm font-semibold text-gray-300 mb-2">
                            Shipping Address
                        </label>
                        <textarea name="shipping_address" 
                                  id="shipping_address" 
                                  rows="3"
                                  class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">{{ old('shipping_address', $customer->shipping_address) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Financial Information Section -->
            <div class="mb-8">
                <h2 class="text-xl font-bold text-white mb-4 border-b border-gray-700 pb-2">Financial Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- WHT Rate -->
                    <div>
                        <label for="whtrate" class="block text-sm font-semibold text-gray-300 mb-2">
                            WHT Rate (%)
                        </label>
                        <input type="number" 
                               name="whtrate" 
                               id="whtrate" 
                               step="0.01"
                               value="{{ old('whtrate', $customer->whtrate) }}"
                               class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>

                    <!-- WHT Code -->
                    <div>
                        <label for="whtcode" class="block text-sm font-semibold text-gray-300 mb-2">
                            WHT Code
                        </label>
                        <input type="text" 
                               name="whtcode" 
                               id="whtcode" 
                               value="{{ old('whtcode', $customer->whtcode) }}"
                               class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>

                    <!-- Require SI -->
                    <div>
                        <label for="require_si" class="block text-sm font-semibold text-gray-300 mb-2">
                            Require SI
                        </label>
                        <select name="require_si" 
                                id="require_si" 
                                class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="no" {{ old('require_si', $customer->require_si) == 'no' ? 'selected' : '' }}>No</option>
                            <option value="yes" {{ old('require_si', $customer->require_si) == 'yes' ? 'selected' : '' }}>Yes</option>
                        </select>
                    </div>

                    <!-- AR Type -->
                    <div>
                        <label for="ar_type" class="block text-sm font-semibold text-gray-300 mb-2">
                            AR Type
                        </label>
                        <input type="text" 
                               name="ar_type" 
                               id="ar_type" 
                               value="{{ old('ar_type', $customer->ar_type) }}"
                               class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>

                    <!-- TIN No -->
                    <div>
                        <label for="tin_no" class="block text-sm font-semibold text-gray-300 mb-2">
                            TIN No
                        </label>
                        <input type="text" 
                               name="tin_no" 
                               id="tin_no" 
                               value="{{ old('tin_no', $customer->tin_no) }}"
                               class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>

                    <!-- Collection Terms -->
                    <div>
                        <label for="collection_terms" class="block text-sm font-semibold text-gray-300 mb-2">
                            Collection Terms
                        </label>
                        <input type="text" 
                               name="collection_terms" 
                               id="collection_terms" 
                               value="{{ old('collection_terms', $customer->collection_terms) }}"
                               class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               placeholder="e.g., Net 30">
                    </div>

                    <!-- Sales Rep -->
                    <div>
                        <label for="sales_rep" class="block text-sm font-semibold text-gray-300 mb-2">
                            Sales Rep
                        </label>
                        <input type="text" 
                               name="sales_rep" 
                               id="sales_rep" 
                               value="{{ old('sales_rep', $customer->sales_rep) }}"
                               class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>

                    <!-- Credit Limit -->
                    <div>
                        <label for="credit_limit" class="block text-sm font-semibold text-gray-300 mb-2">
                            Credit Limit
                        </label>
                        <input type="number" 
                               name="credit_limit" 
                               id="credit_limit" 
                               step="0.01"
                               value="{{ old('credit_limit', $customer->credit_limit) }}"
                               class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>

                    <!-- Assigned Bank -->
                    <div>
                        <label for="assigned_bank" class="block text-sm font-semibold text-gray-300 mb-2">
                            Assigned Bank
                        </label>
                        <input type="text" 
                               name="assigned_bank" 
                               id="assigned_bank" 
                               value="{{ old('assigned_bank', $customer->assigned_bank) }}"
                               class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-4 mt-8">
                <a href="{{ route('customers.index') }}" class="bg-gray-600 hover:bg-gray-500 text-white px-6 py-2 rounded-lg transition">
                    Cancel
                </a>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg transition">
                    Update Customer
                </button>
            </div>
        </form>
    </div>
</div>
@endsection