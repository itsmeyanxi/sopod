@extends('layouts.app')
@section('title', 'GL Account Details')

@section('content')
<div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
        <h1 class="text-2xl font-bold">GL ACCOUNT DETAILS</h1>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="bg-green-600 text-white px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    {{-- Account Code & Name --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div>
            <label class="block font-semibold text-gray-300 mb-1">Account Code:</label>
            <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200 font-semibold">
                {{ $account->account_code }}
            </p>
        </div>
        <div>
            <label class="block font-semibold text-gray-300 mb-1">Account Name:</label>
            <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200 font-semibold">
                {{ $account->account_name }}
            </p>
        </div>
    </div>

    {{-- FS Line Item & Notes --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div>
            <label class="block font-semibold text-gray-300 mb-1">FS Line Item:</label>
            <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">
                {{ $account->fs_line_item ?? '-' }}
            </p>
        </div>
    </div>

    {{-- FS Notes (full width) --}}
    <div class="mb-6">
        <label class="block font-semibold text-gray-300 mb-1">FS Notes:</label>
        <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200 whitespace-pre-wrap">
            {{ $account->fs_notes ?? '-' }}
        </p>
    </div>

    {{-- Audit Information --}}
    <div class="bg-gray-700 rounded-lg p-4 mb-6">
        <h3 class="font-semibold text-gray-300 mb-3">Audit Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-300">
            <div>
                <label class="block font-semibold text-gray-400 mb-1">Created By:</label>
                <p>{{ $account->created_by ?? '-' }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-400 mb-1">Created At:</label>
                <p>{{ $account->created_at->format('F d, Y h:i A') }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-400 mb-1">Last Updated:</label>
                <p>{{ $account->updated_at->format('F d, Y h:i A') }}</p>
            </div>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="flex justify-end gap-4">
        <a href="{{ route('gl_accounts.index') }}"
            class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-600 transition">
            <i class="fas fa-arrow-left mr-1"></i> Back to List
        </a>
        <a href="{{ route('gl_accounts.edit', $account->id) }}"
            class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
            <i class="fas fa-edit mr-1"></i> Edit
        </a>
        <form action="{{ route('gl_accounts.destroy', $account->id) }}" method="POST"
            style="display: inline;"
            onsubmit="return confirm('Are you sure you want to delete this GL account?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 transition">
                <i class="fas fa-trash mr-1"></i> Delete
            </button>
        </form>
    </div>
</div>
@endsection
