@extends('layouts.app')
@section('title', 'Add Asset Class')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6 max-w-2xl mx-auto">
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold">ADD ASSET CLASS</h1>
            <a href="{{ route('asset_classes.index') }}" class="bg-gray-600 text-gray-200 px-4 py-2 rounded hover:bg-gray-600 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>

        @if($errors->any())
            <div class="bg-red-100 text-red-800 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside text-sm">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('asset_classes.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Asset Group <span class="text-red-600">*</span></label>
                    <select name="asset_group" required class="w-full border border-gray-600 rounded px-3 py-2 text-sm">
                        <option value="">— Select —</option>
                        @foreach($assetGroups as $g)
                            <option value="{{ $g }}" {{ old('asset_group') == $g ? 'selected' : '' }}>{{ $g }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Asset Class <span class="text-red-600">*</span></label>
                    <input type="text" name="asset_class" value="{{ old('asset_class') }}" required
                           class="w-full border border-gray-600 rounded px-3 py-2 text-sm" placeholder="e.g. Delivery Trucks">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Useful Life (Months) <span class="text-red-600">*</span></label>
                    <input type="number" name="useful_life_months" value="{{ old('useful_life_months', 0) }}" required min="0"
                           class="w-full border border-gray-600 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Depreciation Type <span class="text-red-600">*</span></label>
                    <select name="dep_type" required class="w-full border border-gray-600 rounded px-3 py-2 text-sm">
                        <option value="Straight Line" {{ old('dep_type') == 'Straight Line' ? 'selected' : '' }}>Straight Line</option>
                        <option value="Declining Balance" {{ old('dep_type') == 'Declining Balance' ? 'selected' : '' }}>Declining Balance</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                @include('partials.gl_account_selector', ['field' => 'gl_account', 'label' => 'GL Account', 'uid' => 'ac_gl', 'value' => old('gl_account'), 'glAccounts' => $glAccounts])
                @include('partials.gl_account_selector', ['field' => 'depreciation_account', 'label' => 'Depreciation Account', 'uid' => 'ac_dep', 'value' => old('depreciation_account'), 'glAccounts' => $glAccounts])
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('asset_classes.index') }}" class="bg-gray-600 text-gray-200 px-6 py-2 rounded hover:bg-gray-600 text-sm">Cancel</a>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 text-sm">
                    <i class="fas fa-save mr-1"></i> Save
                </button>
            </div>
        </form>
    </div>
</div>
@include('partials.gl_account_selector_js')
@endsection
