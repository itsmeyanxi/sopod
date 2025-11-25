@extends('layouts.app')

@section('content')
<div class="p-6 bg-gray-900 min-h-screen text-gray-200">
    <h1 class="text-2xl font-bold mb-6">Create Customer</h1>

    {{-- ✅ Success message --}}
    @if(session('success'))
        <div class="bg-green-600 text-white p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    {{-- ❌ Validation errors --}}
    @if ($errors->any())
        <div class="bg-red-700 text-white p-3 rounded mb-4">
            <ul class="list-disc ml-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- 💠 Form Container -->
    <div class="bg-gray-800/90 border border-gray-700 p-6 rounded-xl shadow-lg max-w-3xl mx-auto">
        <form action="{{ route('customers.store') }}" method="POST">
            @csrf

            {{-- 🔹 Customer Code (manual) --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-300 mb-1">Customer Code <span class="text-red-400">*</span></label>
                <input 
                    type="text" 
                    name="customer_code"
                    class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 text-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    placeholder="Enter Customer Code (must be unique)"
                    required
                >
            </div>

            {{-- 🔹 Customer Name --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-300 mb-1">Customer Name <span class="text-red-400">*</span></label>
                <input 
                    type="text" 
                    name="customer_name" 
                    class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent" 
                    placeholder="Enter Customer Name" 
                    required
                >
            </div>

            {{-- 🔹Branch --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-300 mb-1">Branch <span class="text-red-400">*</span></label>
                <input 
                    type="text" 
                    name="branch" 
                    class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent" 
                    placeholder="Enter Branch" 
                    required
                >
            </div>

            {{-- 🔹 Sales Executive --}}
           <div class="mb-5">
                <label class="block text-sm font-medium text-gray-300 mb-1">Sales Executive</label>
                <input 
                    type="text" 
                    name="sales_executive" 
                    class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 text-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent" 
                    placeholder="Enter Sales Executive Name"
                >
            </div>

            {{-- 🔹 Business Style --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-300 mb-1">Business Style</label>
                <input 
                    type="text" 
                    name="business_style" 
                    class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent" 
                    placeholder="Enter Business Style"
                >
            </div>

            {{-- 🔹 Billing Address --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-300 mb-1">Billing Address</label>
                <textarea 
                    name="billing_address" 
                    rows="2"
                    class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent" 
                    placeholder="Enter Billing Address"
                ></textarea>
            </div>

            {{-- 🔹 TIN --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-300 mb-1">TIN</label>
                <input 
                    type="text" 
                    name="tin" 
                    class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent" 
                    placeholder="Enter TIN"
                >
            </div>

            {{-- 🔹 Shipping Address --}}
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-300 mb-1">Shipping Address</label>
                <textarea 
                    name="shipping_address" 
                    rows="2"
                    class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent" 
                    placeholder="Enter Shipping Address"
                ></textarea>
            </div>

            {{-- 🔘 Buttons --}}
            <div class="flex justify-end gap-3">
                <a href="{{ route('customers.index') }}" 
                   class="bg-gray-700 hover:bg-gray-600 text-gray-200 px-4 py-2 rounded-md transition">
                    Cancel
                </a>

                <button type="submit" 
                        class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-md shadow transition">
                    Save Customer
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
