@extends('layouts.app')

@section('title', 'Edit Live Chicken Record')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6 max-w-4xl mx-auto">

        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold">🐔 Edit Live Chicken Record</h1>
            <a href="{{ route('live_chickens.show', $record->id) }}" class="text-gray-400 hover:text-white text-sm">← Back</a>
        </div>

        @if($errors->any())
            <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('live_chickens.update', $record->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- GRPO Number (read-only) --}}
            @if($record->grpo_no)
            <div class="mb-4 p-3 bg-gray-900 border border-purple-700 rounded">
                <span class="text-gray-400 text-sm font-semibold">GRPO #:</span>
                <span class="text-purple-300 font-mono text-lg ml-2">{{ $record->grpo_no }}</span>
            </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Date <span class="text-red-400">*</span></label>
                    <input type="date" name="date" value="{{ old('date', $record->date->format('Y-m-d')) }}"
                           class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">PO Number</label>
                    <div class="relative">
                        <input type="text" id="po_search" placeholder="Search PO..."
                               class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none"
                               value="{{ old('po_no', $record->po_no) }}" autocomplete="off">
                        <input type="hidden" name="po_no" id="po_no_val" value="{{ old('po_no', $record->po_no) }}">
                        <div id="po_dropdown" class="absolute z-50 bg-gray-900 border border-gray-700 rounded w-full hidden max-h-48 overflow-y-auto shadow-lg"></div>
                    </div>
                    <div id="po_info" class="mt-1 text-xs text-yellow-300 {{ $record->po_no ? '' : 'hidden' }}">
                        PO Qty: <span id="po_qty_display">{{ $record->getPoQty() }}</span>
                    </div>
                </div>

                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Reference Number</label>
                    <input type="text" name="reference_number" value="{{ old('reference_number', $record->reference_number) }}"
                           placeholder="Auto-filled from PO or enter manually"
                           class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Supplier <span class="text-red-400">*</span></label>
                    <input type="text" name="supplier" value="{{ old('supplier', $record->supplier) }}" id="supplier_field"
                           class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Brand</label>
                    <input type="text" name="brand" value="{{ old('brand', $record->brand) }}"
                           class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                <div class="md:col-span-2">
                    <div class="flex justify-between items-center mb-1">
                        <label class="block text-gray-300 text-sm font-semibold">Items <span class="text-red-400">*</span></label>
                        <button type="button" onclick="addItemRow()" class="bg-purple-600 hover:bg-purple-700 text-white text-xs px-2 py-1 rounded">+ Add Row</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs bg-gray-900 rounded border border-gray-700" id="items_table">
                            <thead class="bg-gray-700 text-gray-300">
                                <tr>
                                    <th class="px-2 py-2 text-left">Description</th>
                                    <th class="px-2 py-2 text-left w-28">Brand</th>
                                    <th class="px-2 py-2 text-left w-20">Qty</th>
                                    <th class="px-2 py-2 text-left w-20">UOM</th>
                                    <th class="px-2 py-2 text-left w-24">Unit Price</th>
                                    <th class="px-2 py-2 w-8"></th>
                                </tr>
                            </thead>
                            <tbody id="items_tbody">
                                @php
                                    $existingItems = [];
                                    if ($record->items_data) {
                                        $existingItems = is_array($record->items_data) ? $record->items_data : json_decode($record->items_data, true) ?? [];
                                    } elseif ($record->items) {
                                        foreach (explode("\n", $record->items) as $line) {
                                            if (trim($line)) $existingItems[] = ['description'=>trim($line),'brand'=>'','qty'=>0,'uom'=>'','unit_price'=>0];
                                        }
                                    }
                                @endphp
                                @foreach($existingItems as $ei)
                                <tr class="border-b border-gray-700">
                                    <td class="px-1 py-1"><input type="text" value="{{ $ei['description']??'' }}" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-2 py-1 text-xs item-desc"></td>
                                    <td class="px-1 py-1"><input type="text" value="{{ $ei['brand']??'' }}" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-2 py-1 text-xs item-brand"></td>
                                    <td class="px-1 py-1"><input type="number" value="{{ $ei['qty']??0 }}" step="0.01" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-2 py-1 text-xs item-qty"></td>
                                    <td class="px-1 py-1"><input type="text" value="{{ $ei['uom']??'' }}" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-2 py-1 text-xs item-uom"></td>
                                    <td class="px-1 py-1"><input type="number" value="{{ $ei['unit_price']??0 }}" step="0.01" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-2 py-1 text-xs item-price"></td>
                                    <td class="px-1 py-1 text-center"><button type="button" onclick="this.closest('tr').remove()" class="text-red-400 hover:text-red-300 text-xs">✕</button></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <input type="hidden" name="items" id="items_hidden">
                    <input type="hidden" name="items_data" id="items_data_hidden">
                    <p class="text-xs text-gray-500 mt-1">Auto-filled from PO when a PO is selected. You may also type to search.</p>
                </div>

                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Price <span class="text-red-400">*</span></label>
                    <input type="number" name="price" value="{{ old('price', $record->price) }}" step="0.01" min="0" id="price_field"
                           class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Actual Qty <span class="text-red-400">*</span></label>
                    <input type="number" name="actual_qty" value="{{ old('actual_qty', $record->actual_qty) }}" step="0.01" min="0" id="actual_qty_field"
                           class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                    <div id="qty_warning" class="mt-1 text-xs text-red-400 {{ !$record->isPoQtyMet() ? '' : 'hidden' }}">
                        ⚠ Actual Qty is less than PO Qty — this PO will be blocked from Receiving Reports.
                    </div>
                </div>

                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Amount <span class="text-red-400">*</span></label>
                    <input type="number" name="amount" value="{{ old('amount', $record->amount) }}" step="0.01" min="0" id="amount_field"
                           class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Delivery Date</label>
                    <input type="date" name="delivery_date" value="{{ old('delivery_date', $record->delivery_date?->format('Y-m-d')) }}"
                           class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Delivery Week No.</label>
                    <input type="text" name="delivery_week_no" value="{{ old('delivery_week_no', $record->delivery_week_no) }}"
                           class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Status <span class="text-red-400">*</span></label>
                    <select name="status"
                            class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                        @foreach(\App\Models\LiveChicken::STATUSES as $s)
                            <option value="{{ $s }}" @selected(old('status', $record->status) === $s)>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>

            </div>

            {{-- Docs --}}
            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-5">

                <div class="bg-gray-900 rounded-lg p-4 border border-gray-700">
                    <label class="block text-gray-200 font-semibold mb-3">Docs Required</label>
                    <div class="flex gap-4 mb-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="docs_required_type" value="file" id="req_type_file"
                                   @checked(old('docs_required_type', $record->docs_required_type) === 'file')>
                            <span class="text-sm text-gray-300">File</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="docs_required_type" value="date" id="req_type_date"
                                   @checked(old('docs_required_type', $record->docs_required_type) === 'date')>
                            <span class="text-sm text-gray-300">Date</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="docs_required_type" value="" id="req_type_none"
                                   @checked(!old('docs_required_type', $record->docs_required_type))>
                            <span class="text-sm text-gray-300">None</span>
                        </label>
                    </div>
                    <div id="req_file_input" class="hidden">
                        @if($record->docs_required_file)
                            <div class="text-xs text-green-400 mb-1">
                                Current: <a href="{{ Storage::url($record->docs_required_file) }}" target="_blank" class="underline">View file</a>
                            </div>
                        @endif
                        <input type="file" name="docs_required_file" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                               class="w-full text-gray-300 text-sm">
                    </div>
                    <div id="req_date_input" class="hidden">
                        <input type="date" name="docs_required_date" value="{{ old('docs_required_date', $record->docs_required_date?->format('Y-m-d')) }}"
                               class="w-full bg-gray-800 border border-gray-600 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                    </div>
                </div>

                <div class="bg-gray-900 rounded-lg p-4 border border-gray-700">
                    <label class="block text-gray-200 font-semibold mb-3">Docs Transmitted</label>
                    <div class="flex gap-4 mb-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="docs_transmitted_type" value="file" id="trans_type_file"
                                   @checked(old('docs_transmitted_type', $record->docs_transmitted_type) === 'file')>
                            <span class="text-sm text-gray-300">File</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="docs_transmitted_type" value="date" id="trans_type_date"
                                   @checked(old('docs_transmitted_type', $record->docs_transmitted_type) === 'date')>
                            <span class="text-sm text-gray-300">Date</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="docs_transmitted_type" value="" id="trans_type_none"
                                   @checked(!old('docs_transmitted_type', $record->docs_transmitted_type))>
                            <span class="text-sm text-gray-300">None</span>
                        </label>
                    </div>
                    <div id="trans_file_input" class="hidden">
                        @if($record->docs_transmitted_file)
                            <div class="text-xs text-green-400 mb-1">
                                Current: <a href="{{ Storage::url($record->docs_transmitted_file) }}" target="_blank" class="underline">View file</a>
                            </div>
                        @endif
                        <input type="file" name="docs_transmitted_file" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                               class="w-full text-gray-300 text-sm">
                    </div>
                    <div id="trans_date_input" class="hidden">
                        <input type="date" name="docs_transmitted_date" value="{{ old('docs_transmitted_date', $record->docs_transmitted_date?->format('Y-m-d')) }}"
                               class="w-full bg-gray-800 border border-gray-600 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                    </div>
                </div>

            </div>

            <div class="mt-6 flex gap-3">
                <button type="submit" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded font-semibold">
                    Update Record
                </button>
                <a href="{{ route('live_chickens.show', $record->id) }}" class="px-6 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded">
                    Cancel
                </a>
            </div>

        </form>
    </div>
</div>

<script>
function setupDocToggle(radioName, fileDiv, dateDiv) {
    const radios = document.querySelectorAll(`input[name="${radioName}"]`);
    function toggle() {
        const val = document.querySelector(`input[name="${radioName}"]:checked`)?.value;
        document.getElementById(fileDiv).classList.toggle('hidden', val !== 'file');
        document.getElementById(dateDiv).classList.toggle('hidden', val !== 'date');
    }
    radios.forEach(r => r.addEventListener('change', toggle));
    toggle();
}
setupDocToggle('docs_required_type', 'req_file_input', 'req_date_input');
setupDocToggle('docs_transmitted_type', 'trans_file_input', 'trans_date_input');

let poQty = {{ $record->getPoQty() }};
function checkQtyWarning(qty) {
    const warn = document.getElementById('qty_warning');
    warn.classList.toggle('hidden', !(poQty > 0 && qty < poQty));
}
function recalcAmount() {
    const price = parseFloat(document.getElementById('price_field').value) || 0;
    const qty   = parseFloat(document.getElementById('actual_qty_field').value) || 0;
    document.getElementById('amount_field').value = (price * qty).toFixed(2);
    checkQtyWarning(qty);
}
document.getElementById('price_field').addEventListener('input', recalcAmount);
document.getElementById('actual_qty_field').addEventListener('input', recalcAmount);

const poSearch    = document.getElementById('po_search');
const poValInput  = document.getElementById('po_no_val');
const poDropdown  = document.getElementById('po_dropdown');
const poInfo      = document.getElementById('po_info');
const poQtyDisp   = document.getElementById('po_qty_display');
const supplierFld = document.getElementById('supplier_field');
const brandFld    = document.querySelector('input[name="brand"]');
const itemsFld    = document.getElementById('items_search');

let debounce;
poSearch.addEventListener('input', function () {
    clearTimeout(debounce);
    const q = this.value.trim();
    if (!q) { poDropdown.classList.add('hidden'); return; }
    debounce = setTimeout(() => {
        fetch(`{{ route('live_chickens.searchPOs') }}?search=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(data => {
                poDropdown.innerHTML = '';
                if (!data.length) { poDropdown.classList.add('hidden'); return; }
                data.forEach(po => {
                    const div = document.createElement('div');
                    div.className = 'px-3 py-2 hover:bg-gray-700 cursor-pointer text-sm border-b border-gray-800';
                    div.innerHTML = `<span class="text-purple-300 font-mono">${po.po_no}</span>`
                        + `<span class="text-gray-400 ml-2">${po.supplier || ''}</span>`
                        + (po.brand ? `<span class="text-yellow-400 ml-2 text-xs">${po.brand}</span>` : '')
                        + `<span class="text-gray-500 ml-2 text-xs">Qty: ${po.po_qty}</span>`;
                    div.addEventListener('click', () => {
                        poSearch.value    = po.po_no;
                        poValInput.value  = po.po_no;
                        const refFld = document.querySelector('input[name="reference_number"]');
                        if (refFld && po.reference_number) refFld.value = po.reference_number;
                        supplierFld.value = po.supplier || '';
                        if (brandFld && po.brand) brandFld.value = po.brand;
                        if (po.price) document.getElementById('price_field').value = parseFloat(po.price).toFixed(2);
                        if (po.po_items && po.po_items.length) {
                            document.getElementById('items_tbody').innerHTML = '';
                            po.po_items.forEach(item => addItemRow(item));
                        }
                        poQty = po.po_qty;
                        poQtyDisp.textContent = po.po_qty;
                        poInfo.classList.remove('hidden');
                        recalcAmount();
                        poDropdown.classList.add('hidden');
                    });
                    poDropdown.appendChild(div);
                });
                poDropdown.classList.remove('hidden');
            });
    }, 300);
});
document.addEventListener('click', e => {
    if (!poDropdown.contains(e.target) && e.target !== poSearch) poDropdown.classList.add('hidden');
});

// Items table
function addItemRow(data = {}) {
    const tr = document.createElement('tr');
    tr.className = 'border-b border-gray-700';
    tr.innerHTML = `
        <td class="px-1 py-1"><input type="text" placeholder="Description" value="${data.description||''}" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-2 py-1 text-xs item-desc"></td>
        <td class="px-1 py-1"><input type="text" placeholder="Brand" value="${data.brand||''}" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-2 py-1 text-xs item-brand"></td>
        <td class="px-1 py-1"><input type="number" placeholder="0" value="${data.qty||''}" step="0.01" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-2 py-1 text-xs item-qty"></td>
        <td class="px-1 py-1"><input type="text" placeholder="kg" value="${data.uom||''}" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-2 py-1 text-xs item-uom"></td>
        <td class="px-1 py-1"><input type="number" placeholder="0" value="${data.unit_price||''}" step="0.01" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-2 py-1 text-xs item-price"></td>
        <td class="px-1 py-1 text-center"><button type="button" onclick="this.closest('tr').remove()" class="text-red-400 hover:text-red-300 text-xs">✕</button></td>`;
    document.getElementById('items_tbody').appendChild(tr);
}
document.querySelector('form').addEventListener('submit', function() {
    const rows = document.querySelectorAll('#items_tbody tr');
    const data = [];
    rows.forEach(tr => {
        const desc = tr.querySelector('.item-desc')?.value?.trim();
        if (desc) data.push({
            description: desc,
            brand: tr.querySelector('.item-brand')?.value?.trim()||'',
            qty: parseFloat(tr.querySelector('.item-qty')?.value)||0,
            uom: tr.querySelector('.item-uom')?.value?.trim()||'',
            unit_price: parseFloat(tr.querySelector('.item-price')?.value)||0,
        });
    });
    document.getElementById('items_hidden').value = data.map(d=>d.description).join('\n');
    document.getElementById('items_data_hidden').value = JSON.stringify(data);
});
</script>
@endsection
