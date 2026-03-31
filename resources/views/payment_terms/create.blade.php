@extends('layouts.app')

@section('title', 'Create Payment Term')

@section('content')
<div class="container mx-auto max-w-2xl">
    <div class="bg-gray-800 rounded-lg shadow-lg p-6">

        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">Create Payment Term</h1>
            <a href="{{ route('payment_terms.index') }}" class="text-gray-300 hover:text-gray-200 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>

        @if($errors->any())
            <div class="bg-red-100 text-red-800 px-4 py-3 rounded mb-4">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('payment_terms.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-300 text-sm mb-1 font-medium">Code <span class="text-red-500">*</span></label>
                    <input type="text" name="code" value="{{ old('code') }}" class="w-full border border-gray-600 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" placeholder="e.g., NET30" required>
                </div>
                <div>
                    <label class="block text-gray-300 text-sm mb-1 font-medium">Description <span class="text-red-500">*</span></label>
                    <input type="text" name="description" value="{{ old('description') }}" class="w-full border border-gray-600 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" placeholder="e.g., Net 30" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-gray-300 text-sm mb-1 font-medium">Net Days <span class="text-red-500">*</span></label>
                    <input type="number" name="net_days" value="{{ old('net_days') }}" min="1" class="w-full border border-gray-600 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" placeholder="30" required>
                </div>
                <div>
                    <label class="block text-gray-300 text-sm mb-1 font-medium">Discount %</label>
                    <input type="number" name="discount_pct" value="{{ old('discount_pct', 0) }}" step="0.01" min="0" max="100" class="w-full border border-gray-600 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
                <div>
                    <label class="block text-gray-300 text-sm mb-1 font-medium">Discount Days</label>
                    <input type="number" name="discount_days" value="{{ old('discount_days', 0) }}" min="0" class="w-full border border-gray-600 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition text-sm font-medium">
                    <i class="fas fa-save mr-1"></i> Save Payment Term
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
