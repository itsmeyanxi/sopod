@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-6 py-10 bg-gray-900 min-h-screen text-gray-100">
    <!-- Header with Back Button -->
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center space-x-4">
            <h1 class="text-3xl font-bold text-white">Customer Details</h1>
        </div>

        <!-- Action Buttons -->
        @if(auth()->user()->canManageCustomers())
        <div class="flex gap-2">
            <a href="{{ route('customers.edit', $customer->id) }}" 
               class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit
            </a>

            <form action="{{ route('customers.destroy', $customer->id) }}" 
                  method="POST" 
                  class="inline-block"
                  onsubmit="return confirm('Are you sure you want to delete this customer?');">
                @csrf
                @method('DELETE')
                <button type="submit" 
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete
                </button>
            </form>
        </div>
        @endif
    </div>

    <!-- Status Badge -->
    <div class="mb-6">
        <span class="px-4 py-2 rounded-lg text-sm font-semibold {{ $customer->status === 'enabled' ? 'bg-green-600 text-white' : 'bg-red-600 text-white' }}">
            {{ ucfirst($customer->status) }}
        </span>
    </div>

    <!-- Customer Details Card -->
    <div class="bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        
        <!-- Basic Information Section -->
        <div class="p-6 border-b border-gray-700">
            <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Basic Information
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <p class="text-xs text-gray-400 mb-1">Customer Code</p>
                    <p class="text-gray-100 font-semibold">{{ $customer->customer_code }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">Customer Name</p>
                    <p class="text-gray-100 font-semibold">{{ $customer->customer_name }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">Business Style</p>
                    <p class="text-gray-100">{{ $customer->business_style ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">Branch</p>
                    <p class="text-gray-100">{{ $customer->branch ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">Customer Group</p>
                    <p class="text-gray-100">{{ $customer->customer_group ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">Customer Type</p>
                    <p class="text-gray-100">{{ $customer->customer_type ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">Currency</p>
                    <p class="text-gray-100">{{ $customer->currency ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <!-- Contact Information Section -->
        <div class="p-6 border-b border-gray-700">
            <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                Contact Information
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <p class="text-xs text-gray-400 mb-1">Telephone 1</p>
                    <p class="text-gray-100">{{ $customer->telephone_1 ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">Telephone 2</p>
                    <p class="text-gray-100">{{ $customer->telephone_2 ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">Mobile</p>
                    <p class="text-gray-100">{{ $customer->mobile ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">Email</p>
                    <p class="text-gray-100">
                        @if($customer->email)
                            <a href="mailto:{{ $customer->email }}" class="text-blue-400 hover:text-blue-300">{{ $customer->email }}</a>
                        @else
                            N/A
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">Website</p>
                    <p class="text-gray-100">
                        @if($customer->website)
                            <a href="http://{{ $customer->website }}" target="_blank" class="text-blue-400 hover:text-blue-300">{{ $customer->website }}</a>
                        @else
                            N/A
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">Contact Person</p>
                    <p class="text-gray-100">{{ $customer->name_of_contact ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <!-- Address Information Section -->
        <div class="p-6 border-b border-gray-700">
            <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Address Information
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-xs text-gray-400 mb-1">Billing Address</p>
                    <p class="text-gray-100 whitespace-pre-line">{{ $customer->billing_address ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">Shipping Address</p>
                    <p class="text-gray-100 whitespace-pre-line">{{ $customer->shipping_address ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <!-- Financial Information Section -->
        <div class="p-6">
            <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Financial Information
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <p class="text-xs text-gray-400 mb-1">WHT Rate</p>
                    <p class="text-gray-100">{{ $customer->whtrate ?? '0.00' }}%</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">WHT Code</p>
                    <p class="text-gray-100">{{ $customer->whtcode ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">Require SI</p>
                    <p class="text-gray-100">{{ ucfirst($customer->require_si ?? 'no') }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">AR Type</p>
                    <p class="text-gray-100">{{ $customer->ar_type ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">TIN No</p>
                    <p class="text-gray-100">{{ $customer->tin_no ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">Collection Terms</p>
                    <p class="text-gray-100">{{ $customer->collection_terms ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">Sales Representative</p>
                    <p class="text-gray-100">{{ $customer->sales_rep ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">Credit Limit</p>
                    <p class="text-gray-100">{{ $customer->credit_limit ? '₱ ' . number_format($customer->credit_limit, 2) : 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">Assigned Bank</p>
                    <p class="text-gray-100">{{ $customer->assigned_bank ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <!-- Timestamps Section -->
        <div class="p-6 bg-gray-700/30">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                <div>
                    <p class="text-xs text-gray-400 mb-1">Created At</p>
                    <p class="text-gray-100">{{ $customer->created_at->format('F d, Y h:i A') }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">Last Updated</p>
                    <p class="text-gray-100">{{ $customer->updated_at->format('F d, Y h:i A') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Back Button (Bottom) -->
    <div class="mt-6">
        <a href="{{ route('customers.index') }}" 
           class="inline-block bg-gray-700 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition">
            ← Back to Customers List
        </a>
    </div>
</div>
@endsection