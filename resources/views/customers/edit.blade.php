@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-6 py-10 bg-gray-900 min-h-screen text-gray-100">
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

                <!-- Sales Executive -->
               <div>
                    <label for="sales_executive" class="block text-sm font-semibold text-gray-300 mb-2">
                        Sales Executive
                    </label>
                    <input type="text" 
                        name="sales_executive" 
                        id="sales_executive" 
                        value="{{ old('sales_executive', $customer->sales_executive) }}"
                        class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
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

                <!-- TIN -->
                <div>
                    <label for="tin" class="block text-sm font-semibold text-gray-300 mb-2">
                        TIN
                    </label>
                    <input type="text" 
                           name="tin" 
                           id="tin" 
                           value="{{ old('tin', $customer->tin) }}"
                           class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>

                <!-- Billing Address -->
                <div class="md:col-span-2">
                    <label for="billing_address" class="block text-sm font-semibold text-gray-300 mb-2">
                        Billing Address
                    </label>
                    <textarea name="billing_address" 
                              id="billing_address" 
                              rows="3"
                              class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">{{ old('billing_address', $customer->billing_address) }}</textarea>
                </div>

                <!-- Shipping Address -->
                <div class="md:col-span-2">
                    <label for="shipping_address" class="block text-sm font-semibold text-gray-300 mb-2">
                        Shipping Address
                    </label>
                    <textarea name="shipping_address" 
                              id="shipping_address" 
                              rows="3"
                              class="w-full px-4 py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">{{ old('shipping_address', $customer->shipping_address) }}</textarea>
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
