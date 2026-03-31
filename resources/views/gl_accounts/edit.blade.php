@extends('layouts.app')
@section('title', 'Edit GL Account')

@section('content')
<div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
        <h1 class="text-2xl font-bold">EDIT GL ACCOUNT</h1>
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

    <form action="{{ route('gl_accounts.update', $account->id) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- Account Code & Name --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block font-semibold text-gray-500 mb-2">
                    Account Code: <span class="text-red-700">*</span>
                </label>
                <input type="text" name="account_code"
                    class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    placeholder="e.g., 1000" value="{{ $account->account_code }}" required>
            </div>
            <div>
                <label class="block font-semibold text-gray-500 mb-2">
                    Account Name: <span class="text-red-700">*</span>
                </label>
                <input type="text" name="account_name"
                    class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    placeholder="e.g., Cash" value="{{ $account->account_name }}" required>
            </div>
        </div>

        {{-- FS Line Item & Notes --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block font-semibold text-gray-500 mb-2">FS Line Item:</label>
                <input type="text" name="fs_line_item"
                    class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    placeholder="e.g., Current Assets" value="{{ $account->fs_line_item }}">
            </div>
            <div>
                <label class="block font-semibold text-gray-500 mb-2">FS Notes:</label>
                <textarea name="fs_notes" rows="3"
                    class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    placeholder="Optional notes...">{{ $account->fs_notes }}</textarea>
            </div>
        </div>

        {{-- Audit Information --}}
        <div class="bg-gray-700 rounded-lg p-4 mb-6">
            <h3 class="font-semibold text-gray-500 mb-3">Audit Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-500">
                <div>
                    <label class="block font-semibold text-gray-500 mb-1">Created By:</label>
                    <p>{{ $account->created_by ?? '-' }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-1">Created At:</label>
                    <p>{{ $account->created_at->format('F d, Y h:i A') }}</p>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-1">Last Updated:</label>
                    <p>{{ $account->updated_at->format('F d, Y h:i A') }}</p>
                </div>
            </div>
        </div>

        {{-- Form Action Buttons --}}
        <div class="flex justify-end gap-4">
            <a href="{{ route('gl_accounts.show', $account->id) }}"
                class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-200 transition">
                Cancel
            </a>
            <button type="submit"
                class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800">
                <i class="fas fa-save mr-1"></i> Update GL Account
            </button>
        </div>
    </form>
</div>
@endsection
