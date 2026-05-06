@extends('layouts.app')
@section('title', 'Edit Fixed Asset')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6 max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold">EDIT FIXED ASSET</h1>
            <span class="bg-gray-700 text-gray-300 px-3 py-1 rounded text-sm font-mono">{{ $asset->asset_code }}</span>
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

        <form action="{{ route('fixed_assets.update', $asset->id) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Asset Identification -->
            <h3 class="text-lg font-semibold text-gray-200 mb-3">Asset Identification</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Asset Group <span class="text-red-700">*</span></label>
                    <select name="asset_group" id="asset_group" required class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                        @foreach($assetGroups as $group)
                            <option value="{{ $group }}" {{ old('asset_group', $asset->asset_group) == $group ? 'selected' : '' }}>{{ $group }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Asset Class</label>
                    <select name="asset_class" id="asset_class_select" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                        <option value="">-- Select --</option>
                        @foreach($assetClasses as $ac)
                            <option value="{{ $ac->asset_class }}"
                                data-group="{{ $ac->asset_group }}"
                                data-months="{{ $ac->useful_life_months }}"
                                data-gl="{{ $ac->gl_account }}"
                                data-dep="{{ $ac->depreciation_account }}"
                                data-deptype="{{ $ac->dep_type }}"
                                {{ old('asset_class', $asset->asset_class) == $ac->asset_class ? 'selected' : '' }}>
                                {{ $ac->asset_class }}
                            </option>
                        @endforeach
                        @if($asset->asset_class && !$assetClasses->contains('asset_class', $asset->asset_class))
                            <option value="{{ $asset->asset_class }}" selected>{{ $asset->asset_class }}</option>
                        @endif
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Asset Code</label>
                    <input type="text" name="asset_code" value="{{ old('asset_code', $asset->asset_code) }}" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-400 mb-1">Asset Description <span class="text-red-700">*</span></label>
                <textarea name="asset_description" required rows="2" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">{{ old('asset_description', $asset->asset_description) }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Serial / Engine No.</label>
                    <input type="text" name="serial_engine_no" value="{{ old('serial_engine_no', $asset->serial_engine_no) }}" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Plate No.</label>
                    <input type="text" name="plate_no" value="{{ old('plate_no', $asset->plate_no) }}" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Quantity</label>
                    <input type="number" name="quantity" value="{{ old('quantity', $asset->quantity) }}" min="1" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
            </div>

            <!-- Dates -->
            <h3 class="text-lg font-semibold text-gray-200 mb-3">Dates</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Acquisition Date</label>
                    <input type="date" name="acquisition_date" value="{{ old('acquisition_date', $asset->acquisition_date?->format('Y-m-d')) }}" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Date Posted</label>
                    <input type="date" name="date_posted" value="{{ old('date_posted', $asset->date_posted?->format('Y-m-d')) }}" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Dep. Start Date</label>
                    <input type="date" name="dep_start_date" id="dep_start_date" value="{{ old('dep_start_date', $asset->dep_start_date?->format('Y-m-d')) }}" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Dep. End Date <span class="text-xs text-blue-500">(auto)</span></label>
                    <input type="date" name="dep_end_date" id="dep_end_date" value="{{ old('dep_end_date', $asset->dep_end_date?->format('Y-m-d')) }}" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Disposal Date</label>
                    <input type="date" name="disposal_date" value="{{ old('disposal_date', $asset->disposal_date?->format('Y-m-d')) }}" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Disposal Amount</label>
                    <input type="number" name="disposal_amount" value="{{ old('disposal_amount', $asset->disposal_amount) }}" step="0.01" min="0" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
            </div>

            <!-- Financial -->
            <h3 class="text-lg font-semibold text-gray-200 mb-3">Financial Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Cost <span class="text-red-700">*</span></label>
                    <input type="number" name="cost" value="{{ old('cost', $asset->cost) }}" step="0.01" min="0" required class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Salvage Value</label>
                    <input type="number" name="salvage_value" value="{{ old('salvage_value', $asset->salvage_value) }}" step="0.01" min="0" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Useful Life (Months) <span class="text-red-700">*</span></label>
                    <input type="number" name="useful_life_months" id="useful_life_months" value="{{ old('useful_life_months', $asset->useful_life_months) }}" min="0" required class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Remaining Life (Months)</label>
                    <input type="number" name="remaining_life_months" value="{{ old('remaining_life_months', $asset->remaining_life_months) }}" min="0" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Accumulated Depreciation</label>
                    <input type="number" name="accumulated_depreciation" value="{{ old('accumulated_depreciation', $asset->accumulated_depreciation) }}" step="0.01" min="0" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Status</label>
                    <select name="status" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                        <option value="Active" {{ old('status', $asset->status) == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Disposed" {{ old('status', $asset->status) == 'Disposed' ? 'selected' : '' }}>Disposed</option>
                        <option value="Fully Depreciated" {{ old('status', $asset->status) == 'Fully Depreciated' ? 'selected' : '' }}>Fully Depreciated</option>
                    </select>
                </div>
            </div>

            <!-- Accounting -->
            <h3 class="text-lg font-semibold text-gray-200 mb-3">Accounting</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                @include('partials.gl_account_selector', ['field' => 'gl_account',           'label' => 'GL Account',           'uid' => 'fa_gl_account',  'value' => old('gl_account',           $asset->gl_account),           'glAccounts' => $glAccounts])
                @include('partials.gl_account_selector', ['field' => 'depreciation_account', 'label' => 'Depreciation Account', 'uid' => 'fa_dep_account', 'value' => old('depreciation_account', $asset->depreciation_account), 'glAccounts' => $glAccounts])
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Cost Center</label>
                    <input type="text" name="cost_center_name" value="{{ old('cost_center_name', $asset->cost_center_name) }}" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
            </div>

            <!-- Assignment -->
            <h3 class="text-lg font-semibold text-gray-200 mb-3">Assignment</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Assigned Person</label>
                    <input type="text" name="assigned_person" value="{{ old('assigned_person', $asset->assigned_person) }}" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Employee Name</label>
                    <input type="text" name="employee_name" value="{{ old('employee_name', $asset->employee_name) }}" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-400 mb-1">Vendor</label>
                    <input type="text" id="fa_vendor_search" autocomplete="off"
                           placeholder="Type supplier name..."
                           value="{{ old('vendor_name', $asset->vendor_name) }}"
                           class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm text-white">
                    <input type="hidden" name="vendor_name" id="fa_vendor_name" value="{{ old('vendor_name', $asset->vendor_name) }}">
                    <div id="fa_vendor_dropdown" class="hidden absolute z-50 left-0 right-0 bg-gray-800 border border-gray-600 rounded shadow-lg max-h-52 overflow-y-auto" style="top:100%;margin-top:2px;"></div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Reference APV/JV</label>
                    <input type="text" name="reference_apv_jv" value="{{ old('reference_apv_jv', $asset->reference_apv_jv) }}" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Dep. Type</label>
                    <select name="dep_type" id="dep_type" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                        <option value="Straight Line" {{ old('dep_type', $asset->dep_type) == 'Straight Line' ? 'selected' : '' }}>Straight Line</option>
                        <option value="Declining Balance" {{ old('dep_type', $asset->dep_type) == 'Declining Balance' ? 'selected' : '' }}>Declining Balance</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-4">
                <a href="{{ route('fixed_assets.show', $asset->id) }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-600 transition">Cancel</a>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
                    <i class="fas fa-save mr-1"></i> Update Asset
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const assetGroupSel  = document.getElementById('asset_group');
    const assetClassSel  = document.getElementById('asset_class_select');
    const allClassOptions = Array.from(assetClassSel.options);
    const usefulLifeMonthsField = document.getElementById('useful_life_months');
    const depEndDateField       = document.getElementById('dep_end_date');
    const depStartDateField     = document.getElementById('dep_start_date');
    const depTypeField          = document.getElementById('dep_type');

    function filterClasses(group) {
        const current = assetClassSel.value;
        assetClassSel.innerHTML = '';
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = '-- Select --';
        assetClassSel.appendChild(placeholder);
        allClassOptions.forEach(opt => {
            if (!opt.value) return;
            if (!group || opt.dataset.group === group) {
                assetClassSel.appendChild(opt.cloneNode(true));
            }
        });
        assetClassSel.value = current;
    }

    assetGroupSel.addEventListener('change', function () { filterClasses(this.value); });

    assetClassSel.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        if (!this.value || !opt.dataset.months) return;
        const months = parseInt(opt.dataset.months || '0');
        usefulLifeMonthsField.value = months;
        if (opt.dataset.deptype) depTypeField.value = opt.dataset.deptype;
        if (opt.dataset.gl) window.setGlSelectorValue && window.setGlSelectorValue('fa_gl_account', opt.dataset.gl);
        if (opt.dataset.dep) window.setGlSelectorValue && window.setGlSelectorValue('fa_dep_account', opt.dataset.dep);
        recalcDepEndDate();
    });

    depStartDateField.addEventListener('change', recalcDepEndDate);
    usefulLifeMonthsField.addEventListener('input', recalcDepEndDate);

    function recalcDepEndDate() {
        const startVal = depStartDateField.value;
        const months   = parseInt(usefulLifeMonthsField.value || '0');
        if (!startVal || months <= 0) return;
        const start = new Date(startVal);
        start.setMonth(start.getMonth() + months);
        depEndDateField.value = start.toISOString().slice(0, 10);
    }

    filterClasses(assetGroupSel.value);
})();
</script>
@include('partials.gl_account_selector_js')
<script>
(function() {
    const SUPPLIER_QUICK_URL = '{{ route("suppliers.search_quick") }}';
    const searchInput = document.getElementById('fa_vendor_search');
    const hiddenInput = document.getElementById('fa_vendor_name');
    const dropdown    = document.getElementById('fa_vendor_dropdown');
    let debounce;

    searchInput.addEventListener('input', function() {
        hiddenInput.value = this.value;
        clearTimeout(debounce);
        const q = this.value.trim();
        if (q.length < 1) { dropdown.classList.add('hidden'); return; }
        debounce = setTimeout(async () => {
            try {
                const res   = await fetch(`${SUPPLIER_QUICK_URL}?q=${encodeURIComponent(q)}`);
                const items = await res.json();
                if (!items.length) {
                    dropdown.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">No suppliers found</div>';
                    dropdown.classList.remove('hidden');
                    return;
                }
                dropdown.innerHTML = items.map(s =>
                    `<div class="px-3 py-2 hover:bg-gray-700 cursor-pointer text-sm text-gray-200 fa-vendor-opt"
                          data-name="${s.supplier_name.replace(/"/g,'&quot;')}">
                        <span class="font-semibold">${s.supplier_name}</span>
                        <span class="text-gray-400 ml-2 text-xs">${s.supplier_code || ''}</span>
                    </div>`
                ).join('');
                dropdown.classList.remove('hidden');
                dropdown.querySelectorAll('.fa-vendor-opt').forEach(opt => {
                    opt.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        searchInput.value = this.dataset.name;
                        hiddenInput.value = this.dataset.name;
                        dropdown.classList.add('hidden');
                    });
                });
            } catch(e) { dropdown.classList.add('hidden'); }
        }, 250);
    });

    searchInput.addEventListener('blur', () => setTimeout(() => dropdown.classList.add('hidden'), 200));
})();
</script>
@endsection
