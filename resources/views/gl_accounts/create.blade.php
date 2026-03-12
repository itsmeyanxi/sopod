@extends('layouts.app')
@section('title', 'Create GL Account')

@section('content')
<div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
        <h1 class="text-2xl font-bold">CREATE GL ACCOUNT</h1>
    </div>

    {{-- Validation Errors --}}
    @if($errors->any())
        <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('gl_accounts.store') }}" method="POST">
        @csrf

        {{-- Account Code & Name --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block font-semibold text-gray-300 mb-2">
                    Account Code: <span class="text-red-400">*</span>
                </label>
                <input type="text" name="account_code"
                    class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    placeholder="e.g., 1000" value="{{ old('account_code') }}" required>
            </div>
            <div>
                <label class="block font-semibold text-gray-300 mb-2">
                    Account Name: <span class="text-red-400">*</span>
                </label>
                <input type="text" name="account_name"
                    class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    placeholder="e.g., Cash" value="{{ old('account_name') }}" required>
            </div>
        </div>

        {{-- FS Line Item & Notes --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block font-semibold text-gray-300 mb-2">FS Line Item:</label>
                <input type="text" name="fs_line_item"
                    class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    placeholder="e.g., Current Assets" value="{{ old('fs_line_item') }}">
            </div>
            <div>
                <label class="block font-semibold text-gray-300 mb-2">FS Notes:</label>
                <textarea name="fs_notes" rows="3"
                    class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    placeholder="Optional notes...">{{ old('fs_notes') }}</textarea>
            </div>
        </div>

        {{-- Info Callout --}}
        <div class="mb-6 p-4 bg-blue-900/20 border border-blue-700 rounded">
            <p class="text-blue-300 text-sm">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Note:</strong> Account Code must be unique. Use a consistent naming convention.
            </p>
        </div>

        {{-- Form Action Buttons --}}
        <div class="flex justify-end gap-4">
            <a href="{{ route('gl_accounts.index') }}"
                class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-600 transition">
                Cancel
            </a>
            <button type="submit"
                class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800">
                <i class="fas fa-save mr-1"></i> Create GL Account
            </button>
        </div>
    </form>
</div>
@endsection
