@extends('layouts.app')
@section('title', 'Add Fixed Asset')

@section('content')
<div class="container mx-auto">
    <div class="bg-white text-gray-800 rounded-lg shadow-lg p-6 max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6 border-b border-gray-200 pb-4">
            <h1 class="text-2xl font-bold">ADD FIXED ASSET</h1>
        </div>

        @if($errors->any())
            <div class="bg-red-100 text-red-800 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('fixed_assets.store') }}" method="POST">
            @csrf

            <!-- Asset Identification -->
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Asset Identification</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Asset Group <span class="text-red-700">*</span></label>
                    <select name="asset_group" required class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select --</option>
                        @foreach($assetGroups as $group)
                            <option value="{{ $group }}" {{ old('asset_group') == $group ? 'selected' : '' }}>{{ $group }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Asset Code</label>
                    <input type="text" name="asset_code" value="{{ old('asset_code') }}" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-sm" placeholder="e.g. TRA220100001-1">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Asset Class</label>
                    <input type="text" name="asset_class" value="{{ old('asset_class') }}" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-sm" placeholder="e.g. Delivery Trucks">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-500 mb-1">Asset Description <span class="text-red-700">*</span></label>
                <textarea name="asset_description" required rows="2" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-sm">{{ old('asset_description') }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Serial / Engine No.</label>
                    <input type="text" name="serial_engine_no" value="{{ old('serial_engine_no') }}" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Plate No.</label>
                    <input type="text" name="plate_no" value="{{ old('plate_no') }}" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Quantity</label>
                    <input type="number" name="quantity" value="{{ old('quantity', 1) }}" min="1" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-sm">
                </div>
            </div>

            <!-- Dates -->
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Dates</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Acquisition Date</label>
                    <input type="date" name="acquisition_date" value="{{ old('acquisition_date') }}" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Date Posted</label>
                    <input type="date" name="date_posted" value="{{ old('date_posted') }}" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Dep. Start Date</label>
                    <input type="date" name="dep_start_date" value="{{ old('dep_start_date') }}" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Dep. End Date</label>
                    <input type="date" name="dep_end_date" value="{{ old('dep_end_date') }}" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-sm">
                </div>
            </div>

            <!-- Financial -->
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Financial Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Cost <span class="text-red-700">*</span></label>
                    <input type="number" name="cost" value="{{ old('cost', 0) }}" step="0.01" min="0" required class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Salvage Value</label>
                    <input type="number" name="salvage_value" value="{{ old('salvage_value', 0) }}" step="0.01" min="0" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Useful Life (Years) <span class="text-red-700">*</span></label>
                    <input type="number" name="useful_life_years" value="{{ old('useful_life_years', 0) }}" step="0.01" min="0" required class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-sm">
                </div>
            </div>

            <!-- Accounting -->
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Accounting</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

                {{-- GL Account — searchable dropdown (same pattern as Account Code in Journal Voucher) --}}
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">GL Account</label>
                    <div class="relative acct-search-container" style="position: relative;">
                        <input
                            type="text"
                            id="gl_account_search"
                            class="acct-search w-full bg-white border-2 border-gray-300 rounded-lg px-3 py-2 pr-10 text-gray-800 placeholder-gray-500 focus:border-blue-500 focus:outline-none transition-colors text-sm"
                            placeholder="Type to search GL accounts..."
                            autocomplete="off"
                            value="{{ old('gl_account') ? old('gl_account') . ' — ' . '' : '' }}"
                        >
                        <svg class="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <div
                            id="gl_account_dropdown"
                            class="acct-dropdown absolute z-[9999] w-full bg-white border-2 border-gray-300 rounded-lg mt-1 shadow-2xl hidden max-h-60 overflow-y-auto"
                            style="position: absolute; left: 0;"
                        >
                            <div class="sticky top-0 bg-gray-100 px-3 py-2 text-xs text-gray-500 font-semibold border-b border-gray-300">Select a GL account</div>
                            @foreach($glAccounts as $acct)
                                <div
                                    class="acct-option px-4 py-2 hover:bg-blue-600 hover:text-white cursor-pointer text-gray-800 border-b border-gray-200 last:border-b-0 transition-colors"
                                    data-code="{{ $acct['code'] }}"
                                    data-name="{{ $acct['name'] }}"
                                    data-search="{{ strtolower($acct['code'] . ' ' . $acct['name']) }}"
                                >
                                    <div class="font-semibold text-sm font-mono">{{ $acct['code'] }}</div>
                                    <div class="text-xs text-gray-500">{{ $acct['name'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    {{-- Hidden input carries the selected code as the actual form value --}}
                    <input type="hidden" name="gl_account" id="gl_account_hidden" value="{{ old('gl_account') }}">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Depreciation Account</label>
                    <input type="text" name="depreciation_account" value="{{ old('depreciation_account') }}" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Cost Center</label>
                    <input type="text" name="cost_center_name" value="{{ old('cost_center_name') }}" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-sm">
                </div>
            </div>

            <!-- Assignment -->
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Assignment</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Assigned Person</label>
                    <input type="text" name="assigned_person" value="{{ old('assigned_person') }}" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Employee Name</label>
                    <input type="text" name="employee_name" value="{{ old('employee_name') }}" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Vendor</label>
                    <input type="text" name="vendor_name" value="{{ old('vendor_name') }}" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-sm">
                </div>
            </div>

            <!-- References -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Reference APV/JV</label>
                    <input type="text" name="reference_apv_jv" value="{{ old('reference_apv_jv') }}" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Dep. Type</label>
                    <select name="dep_type" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-sm">
                        <option value="Straight Line" {{ old('dep_type') == 'Straight Line' ? 'selected' : '' }}>Straight Line</option>
                        <option value="Declining Balance" {{ old('dep_type') == 'Declining Balance' ? 'selected' : '' }}>Declining Balance</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-4">
                <a href="{{ route('fixed_assets.index') }}" class="bg-gray-100 text-gray-800 px-6 py-2 rounded hover:bg-gray-200 transition">Cancel</a>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
                    <i class="fas fa-save mr-1"></i> Save Asset
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const searchInput  = document.getElementById('gl_account_search');
    const dropdown     = document.getElementById('gl_account_dropdown');
    const hiddenInput  = document.getElementById('gl_account_hidden');

    // Cache original options HTML so filtering can reset cleanly
    const originalHTML = dropdown.innerHTML;

    function filterOptions(term) {
        // Reset to full list before filtering
        dropdown.innerHTML = originalHTML;
        bindOptionClicks();

        if (term === '') {
            dropdown.querySelectorAll('.acct-option').forEach(o => o.style.display = 'block');
            return;
        }

        let visible = 0;
        dropdown.querySelectorAll('.acct-option').forEach(function (opt) {
            if (opt.getAttribute('data-search').includes(term)) {
                opt.style.display = 'block';
                visible++;
            } else {
                opt.style.display = 'none';
            }
        });

        if (visible === 0) {
            const empty = document.createElement('div');
            empty.className = 'px-4 py-6 text-center text-gray-400';
            empty.innerHTML = '<div class="font-medium text-sm">No accounts found</div><div class="text-xs mt-1">Try a different search term</div>';
            dropdown.appendChild(empty);
        }
    }

    function bindOptionClicks() {
        dropdown.querySelectorAll('.acct-option').forEach(function (opt) {
            opt.addEventListener('click', function () {
                const code = this.getAttribute('data-code');
                const name = this.getAttribute('data-name');
                searchInput.value  = code + ' — ' + name;
                hiddenInput.value  = code;
                dropdown.classList.add('hidden');
            });
        });
    }

    // Show dropdown on focus
    searchInput.addEventListener('focus', function () {
        filterOptions(this.value.toLowerCase());
        dropdown.classList.remove('hidden');
    });

    // Filter while typing
    searchInput.addEventListener('input', function () {
        filterOptions(this.value.toLowerCase());
        dropdown.classList.remove('hidden');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function (e) {
        if (!searchInput.closest('.acct-search-container').contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });

    // Initial bind
    bindOptionClicks();
})();
</script>
@endsection