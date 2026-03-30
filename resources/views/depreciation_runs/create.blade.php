@extends('layouts.app')
@section('title', 'New Depreciation Run')

@section('content')
<div class="container mx-auto">
    <div class="bg-white text-gray-800 rounded-lg shadow-lg p-6">

        <div class="flex justify-between items-center mb-6 border-b border-gray-200 pb-4">
            <h1 class="text-2xl font-bold text-gray-800">NEW DEPRECIATION RUN</h1>
            <a href="{{ route('depreciation_runs.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>

        @if(session('error'))
            <div class="bg-red-100 text-red-800 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('depreciation_runs.store') }}">
            @csrf

            <!-- Run Info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Run Number</label>
                    <input type="text" value="{{ $nextRunNumber }}" readonly class="w-full bg-gray-100 border border-gray-300 rounded px-3 py-2 text-sm font-mono text-gray-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Period <span class="text-red-500">*</span></label>
                    <input type="month" name="period" value="{{ old('period', date('Y-m')) }}" required
                           class="w-full bg-white border border-gray-300 rounded px-3 py-2 text-sm @error('period') border-red-400 @enderror">
                    @error('period')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Prepared By</label>
                    <input type="text" value="{{ auth()->user()->name }}" readonly class="w-full bg-gray-100 border border-gray-300 rounded px-3 py-2 text-sm text-gray-500">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-600 mb-1">Description <span class="text-red-500">*</span></label>
                <textarea name="description" rows="2" required
                          class="w-full bg-white border border-gray-300 rounded px-3 py-2 text-sm @error('description') border-red-400 @enderror"
                          placeholder="e.g. Monthly depreciation run for March 2026">{{ old('description') }}</textarea>
                @error('description')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <!-- Asset Groups -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-600 mb-2">
                    Asset Groups to Include <span class="text-red-500">*</span>
                </label>
                @error('asset_groups')<p class="text-red-500 text-xs mb-2">{{ $message }}</p>@enderror
                @if($assetGroups->isEmpty())
                    <p class="text-gray-400 text-sm italic">No asset groups found.</p>
                @else
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        @foreach($assetGroups as $group)
                        <label class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded px-3 py-2 cursor-pointer hover:bg-blue-50 hover:border-blue-300 text-sm">
                            <input type="checkbox" name="asset_groups[]" value="{{ $group }}"
                                   {{ in_array($group, old('asset_groups', [])) ? 'checked' : '' }}
                                   class="accent-blue-600">
                            <span>{{ $group }}</span>
                        </label>
                        @endforeach
                    </div>
                    <div class="mt-2 flex gap-2">
                        <button type="button" onclick="document.querySelectorAll('[name=\'asset_groups[]\']').forEach(c=>c.checked=true)"
                                class="text-xs text-blue-600 hover:underline">Select All</button>
                        <span class="text-gray-300">|</span>
                        <button type="button" onclick="document.querySelectorAll('[name=\'asset_groups[]\']').forEach(c=>c.checked=false)"
                                class="text-xs text-gray-500 hover:underline">Clear All</button>
                    </div>
                @endif
            </div>

            <!-- Info note -->
            <div class="bg-blue-50 border border-blue-200 rounded p-3 mb-6 text-sm text-blue-700">
                <i class="fas fa-info-circle mr-1"></i>
                This will compute monthly depreciation for all <strong>active assets</strong> in the selected groups that still have remaining useful life.
                The run is saved as <strong>Draft</strong> — review it before posting.
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 text-sm">
                    <i class="fas fa-calculator mr-1"></i> Compute & Save Draft
                </button>
                <a href="{{ route('depreciation_runs.index') }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded hover:bg-gray-300 text-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
