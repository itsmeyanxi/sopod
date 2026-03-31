@extends('layouts.app')
@section('title', 'Add Fixed Asset')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6 max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
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

        <form action="{{ route('fixed_assets.store') }}" method="POST" id="fa-form">
            @csrf

            {{-- PO Link --}}
            <h3 class="text-lg font-semibold text-gray-200 mb-3">Purchase Order (Optional)</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 p-4 bg-blue-50 rounded-lg border border-blue-100">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Select Approved PO</label>
                    <select id="po_select" class="w-full border border-gray-600 rounded px-3 py-2 text-sm bg-gray-800">
                        <option value="">— Select PO —</option>
                        @foreach($approvedPos as $po)
                            <option value="{{ $po->id }}" data-supplier="{{ $po->supplier }}" data-date="{{ $po->order_date }}">
                                {{ $po->po_no }} — {{ $po->supplier }}
                            </option>
                        @endforeach
                    </select>
                    <input type="hidden" name="purchase_order_id" id="purchase_order_id_hidden">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">PO Line Item</label>
                    <select id="po_item_select" class="w-full border border-gray-600 rounded px-3 py-2 text-sm bg-gray-800" disabled>
                        <option value="">— Select PO first —</option>
                    </select>
                    <input type="hidden" name="purchase_order_item_id" id="purchase_order_item_id_hidden">
                </div>
            </div>

            {{-- Asset Class Auto-fill --}}
            <h3 class="text-lg font-semibold text-gray-200 mb-3">Asset Identification</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Asset Group <span class="text-red-700">*</span></label>
                    <select name="asset_group" id="asset_group" required class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select --</option>
                        @foreach($assetGroups as $group)
                            <option value="{{ $group }}" {{ old('asset_group') == $group ? 'selected' : '' }}>{{ $group }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Asset Class</label>
                    <select name="asset_class" id="asset_class_select" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                        <option value="">-- Select asset group first --</option>
                        @foreach($assetClasses as $ac)
                            <option value="{{ $ac->asset_class }}"
                                data-group="{{ $ac->asset_group }}"
                                data-months="{{ $ac->useful_life_months }}"
                                data-gl="{{ $ac->gl_account }}"
                                data-dep="{{ $ac->depreciation_account }}"
                                data-deptype="{{ $ac->dep_type }}"
                                {{ old('asset_class') == $ac->asset_class ? 'selected' : '' }}>
                                {{ $ac->asset_class }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Asset Code</label>
                    <input type="text" name="asset_code" value="{{ old('asset_code') }}" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm" placeholder="e.g. TRA220100001-1">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-400 mb-1">Asset Description <span class="text-red-700">*</span></label>
                <textarea name="asset_description" id="asset_description" required rows="2" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">{{ old('asset_description') }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Serial / Engine No.</label>
                    <input type="text" name="serial_engine_no" value="{{ old('serial_engine_no') }}" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Plate No.</label>
                    <input type="text" name="plate_no" value="{{ old('plate_no') }}" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Quantity</label>
                    <input type="number" name="quantity" value="{{ old('quantity', 1) }}" min="1" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
            </div>

            <!-- Dates -->
            <h3 class="text-lg font-semibold text-gray-200 mb-3">Dates</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Acquisition Date</label>
                    <input type="date" name="acquisition_date" value="{{ old('acquisition_date') }}" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Date Posted</label>
                    <input type="date" name="date_posted" value="{{ old('date_posted') }}" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Dep. Start Date</label>
                    <input type="date" name="dep_start_date" id="dep_start_date" value="{{ old('dep_start_date') }}" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Dep. End Date <span class="text-xs text-blue-500">(auto)</span></label>
                    <input type="date" name="dep_end_date" id="dep_end_date" value="{{ old('dep_end_date') }}" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
            </div>

            <!-- Financial -->
            <h3 class="text-lg font-semibold text-gray-200 mb-3">Financial Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Cost <span class="text-red-700">*</span></label>
                    <input type="number" name="cost" id="cost" value="{{ old('cost', 0) }}" step="0.01" min="0" required class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Salvage Value</label>
                    <input type="number" name="salvage_value" value="{{ old('salvage_value', 0) }}" step="0.01" min="0" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Useful Life (Months) <span class="text-red-700">*</span> <span class="text-xs text-blue-500">(from asset class)</span></label>
                    <input type="number" name="useful_life_months" id="useful_life_months" value="{{ old('useful_life_months', 0) }}" min="0" required class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                    <input type="hidden" name="useful_life_years" id="useful_life_years" value="{{ old('useful_life_years', 0) }}">
                </div>
            </div>

            <!-- Accounting -->
            <h3 class="text-lg font-semibold text-gray-200 mb-3">Accounting</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                @include('partials.gl_account_selector', ['field' => 'gl_account', 'label' => 'GL Account', 'uid' => 'fa_gl_account', 'value' => old('gl_account'), 'glAccounts' => $glAccounts])
                @include('partials.gl_account_selector', ['field' => 'depreciation_account', 'label' => 'Depreciation Account', 'uid' => 'fa_dep_account', 'value' => old('depreciation_account'), 'glAccounts' => $glAccounts])
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Cost Center</label>
                    <input type="text" name="cost_center_name" value="{{ old('cost_center_name') }}" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
            </div>

            <!-- Assignment -->
            <h3 class="text-lg font-semibold text-gray-200 mb-3">Assignment</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Assigned Person</label>
                    <input type="text" name="assigned_person" value="{{ old('assigned_person') }}" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Employee Name</label>
                    <input type="text" name="employee_name" value="{{ old('employee_name') }}" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Vendor</label>
                    <input type="text" name="vendor_name" value="{{ old('vendor_name') }}" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
            </div>

            <!-- References -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Reference APV/JV</label>
                    <input type="text" name="reference_apv_jv" value="{{ old('reference_apv_jv') }}" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Dep. Type <span class="text-xs text-blue-500">(from asset class)</span></label>
                    <select name="dep_type" id="dep_type" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                        <option value="Straight Line" {{ old('dep_type') == 'Straight Line' ? 'selected' : '' }}>Straight Line</option>
                        <option value="Declining Balance" {{ old('dep_type') == 'Declining Balance' ? 'selected' : '' }}>Declining Balance</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-4">
                <a href="{{ route('fixed_assets.index') }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-600 transition">Cancel</a>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
                    <i class="fas fa-save mr-1"></i> Save Asset
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    // ── PO Selector ──────────────────────────────────────────────────────
    const poSelect       = document.getElementById('po_select');
    const poItemSelect   = document.getElementById('po_item_select');
    const poIdHidden     = document.getElementById('purchase_order_id_hidden');
    const poItemIdHidden = document.getElementById('purchase_order_item_id_hidden');
    const descField      = document.getElementById('asset_description');
    const costField      = document.getElementById('cost');

    poSelect.addEventListener('change', function () {
        const poId = this.value;
        poIdHidden.value = poId;
        poItemSelect.innerHTML = '<option value="">Loading...</option>';
        poItemSelect.disabled = true;

        if (!poId) {
            poItemSelect.innerHTML = '<option value="">— Select PO first —</option>';
            return;
        }

        fetch('{{ route("fixed_assets.po_items", ":id") }}'.replace(':id', poId))
            .then(r => r.json())
            .then(items => {
                poItemSelect.innerHTML = '<option value="">— Select line item —</option>';
                items.forEach(item => {
                    const opt = document.createElement('option');
                    opt.value = item.id;
                    opt.dataset.description = item.description ?? '';
                    opt.dataset.total = item.total ?? '';
                    opt.textContent = `#${item.item_no} — ${item.description} (${item.qty})`;
                    poItemSelect.appendChild(opt);
                });
                poItemSelect.disabled = false;
            })
            .catch(() => {
                poItemSelect.innerHTML = '<option value="">Error loading items</option>';
            });
    });

    poItemSelect.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        poItemIdHidden.value = this.value;

        if (this.value) {
            if (opt.dataset.description && !descField.value.trim()) {
                descField.value = opt.dataset.description;
            }
            if (opt.dataset.total && parseFloat(costField.value) === 0) {
                costField.value = parseFloat(opt.dataset.total).toFixed(2);
            }
        }
    });

    // ── Asset Group → filter Asset Class options ──────────────────────────
    const assetGroupSel  = document.getElementById('asset_group');
    const assetClassSel  = document.getElementById('asset_class_select');
    const allClassOptions = Array.from(assetClassSel.options);

    function filterClasses(group) {
        const current = assetClassSel.value;
        assetClassSel.innerHTML = '';
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = group ? '-- Select class --' : '-- Select asset group first --';
        assetClassSel.appendChild(placeholder);

        allClassOptions.forEach(opt => {
            if (!opt.value) return;
            if (!group || opt.dataset.group === group) {
                assetClassSel.appendChild(opt.cloneNode(true));
            }
        });

        assetClassSel.value = current;
    }

    assetGroupSel.addEventListener('change', function () {
        filterClasses(this.value);
    });

    // ── Asset Class → auto-fill useful life, GL accounts, dep type ───────
    const usefulLifeMonthsField = document.getElementById('useful_life_months');
    const usefulLifeYearsField  = document.getElementById('useful_life_years');
    const depEndDateField       = document.getElementById('dep_end_date');
    const depStartDateField     = document.getElementById('dep_start_date');
    const depTypeField          = document.getElementById('dep_type');

    assetClassSel.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        if (!this.value) return;

        const months = parseInt(opt.dataset.months || '0');
        usefulLifeMonthsField.value = months;
        usefulLifeYearsField.value  = months > 0 ? (months / 12).toFixed(2) : 0;

        if (opt.dataset.deptype) {
            depTypeField.value = opt.dataset.deptype;
        }

        // Trigger GL account selectors to select by code
        if (opt.dataset.gl) {
            window.setGlSelectorValue && window.setGlSelectorValue('fa_gl_account', opt.dataset.gl);
        }
        if (opt.dataset.dep) {
            window.setGlSelectorValue && window.setGlSelectorValue('fa_dep_account', opt.dataset.dep);
        }

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
        const y = start.getFullYear();
        const m = String(start.getMonth() + 1).padStart(2, '0');
        const d = String(start.getDate()).padStart(2, '0');
        depEndDateField.value = `${y}-${m}-${d}`;
    }

    // Init filter on load
    filterClasses(assetGroupSel.value);
})();
</script>
@include('partials.gl_account_selector_js')
@endsection
