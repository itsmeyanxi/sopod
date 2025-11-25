@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8 text-gray-100">
    <!-- Header -->
    <div class="flex items-center space-x-4 mb-6">
        <a href="{{ route('customers.show', $customer->id) }}" class="text-blue-400 hover:text-blue-500">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <h1 class="text-3xl font-bold text-white">Edit Customer</h1>
    </div>

    <!-- Success Message -->
    @if (session('success'))
        <div class="bg-green-700 border border-green-500 text-white px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <!-- Edit Form -->
    <div class="bg-gray-800 rounded-lg shadow-lg p-8">
        <form action="{{ route('customers.update', $customer->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Customer Code -->
                <div>
                    <label for="customer_code" class="block text-sm font-semibold text-gray-300 mb-2">
                        Customer Code <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="customer_code"
                           id="customer_code"
                           value="{{ old('customer_code', $customer->customer_code) }}"
                           class="w-full px-4 py-2 border border-gray-600 bg-gray-900 text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('customer_code') border-red-500 @enderror"
                           required>
                    @error('customer_code')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Customer Name -->
                <div>
                    <label for="customer_name" class="block text-sm font-semibold text-gray-300 mb-2">
                        Customer Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="customer_name"
                           id="customer_name"
                           value="{{ old('customer_name', $customer->customer_name) }}"
                           class="w-full px-4 py-2 border border-gray-600 bg-gray-900 text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('customer_name') border-red-500 @enderror"
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
                           class="w-full px-4 py-2 border border-gray-600 bg-gray-900 text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    @error('business_style')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
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
                           class="w-full px-4 py-2 border border-gray-600 bg-gray-900 text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    @error('tin')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Billing Address -->
                <div class="md:col-span-2">
                    <label for="billing_address" class="block text-sm font-semibold text-gray-300 mb-2">
                        Billing Address
                    </label>
                    <textarea name="billing_address"
                              id="billing_address"
                              rows="3"
                              class="w-full px-4 py-2 border border-gray-600 bg-gray-900 text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ old('billing_address', $customer->billing_address) }}</textarea>
                    @error('billing_address')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Shipping Address -->
                <div class="md:col-span-2">
                    <label for="shipping_address" class="block text-sm font-semibold text-gray-300 mb-2">
                        Shipping Address
                    </label>
                    <textarea name="shipping_address"
                              id="shipping_address"
                              rows="3"
                              class="w-full px-4 py-2 border border-gray-600 bg-gray-900 text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ old('shipping_address', $customer->shipping_address) }}</textarea>
                    @error('shipping_address')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

            </div>

            <!-- Buttons -->
            <div class="flex justify-end space-x-4 mt-8">
                <a href="{{ route('customers.index', $customer->id) }}"
                   class="bg-gray-700 hover:bg-gray-600 text-gray-200 px-6 py-2 rounded-lg transition">
                    Cancel
                </a>
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition">
                    Update Customer
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
