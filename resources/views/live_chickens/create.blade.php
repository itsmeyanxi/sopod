@extends('layouts.app')

@section('title', 'New Live Chicken Record')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6 max-w-4xl mx-auto">

        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold">🐔 New Live Chicken Record</h1>
            <a href="{{ route('live_chickens.index') }}" class="text-gray-400 hover:text-white text-sm">← Back to List</a>
        </div>

        @if($errors->any())
            <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('live_chickens.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                {{-- Date --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Date <span class="text-red-400">*</span></label>
                    <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}"
                           class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                {{-- PO Number --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">PO Number</label>
                    <div class="relative">
                        <input type="text" id="po_search" placeholder="Search PO..."
                               class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none"
                               value="{{ old('po_no') }}" autocomplete="off">
                        <input type="hidden" name="po_no" id="po_no_val" value="{{ old('po_no') }}">
                        <div id="po_dropdown" class="absolute z-50 bg-gray-900 border border-gray-700 rounded w-full hidden max-h-48 overflow-y-auto shadow-lg"></div>
                    </div>
                    <div id="po_info" class="mt-1 text-xs text-yellow-300 hidden">
                        PO Qty: <span id="po_qty_display">0</span>
                    </div>
                </div>

                {{-- Supplier --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Supplier <span class="text-red-400">*</span></label>
                    <input type="text" name="supplier" value="{{ old('supplier') }}" id="supplier_field"
                           class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                {{-- Brand --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Brand</label>
                    <input type="text" name="brand" value="{{ old('brand') }}"
                           class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                {{-- Items (full width) --}}
                <div class="md:col-span-2">
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Item Description <span class="text-red-400">*</span></label>
                    <div class="relative">
                        <input type="text" id="items_search" name="items" value="{{ old('items') }}"
                               placeholder="Type to search item description..."
                               autocomplete="off"
                               class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                        <div id="items_dropdown" class="absolute z-50 bg-gray-900 border border-gray-700 rounded w-full hidden max-h-48 overflow-y-auto shadow-lg"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Auto-filled from PO when a PO is selected. You may also type to search.</p>
                </div>

                {{-- Price --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Price <span class="text-red-400">*</span></label>
                    <input type="number" name="price" value="{{ old('price', 0) }}" step="0.01" min="0" id="price_field"
                           class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                {{-- Actual Qty --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Actual Qty <span class="text-red-400">*</span></label>
                    <input type="number" name="actual_qty" value="{{ old('actual_qty', 0) }}" step="0.01" min="0" id="actual_qty_field"
                           class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                    <div id="qty_warning" class="mt-1 text-xs text-red-400 hidden">
                        ⚠ Actual Qty is less than PO Qty — this PO will be blocked from Receiving Reports.
                    </div>
                </div>

                {{-- Amount (auto-calc) --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Amount <span class="text-red-400">*</span></label>
                    <input type="number" name="amount" value="{{ old('amount', 0) }}" step="0.01" min="0" id="amount_field"
                           class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                {{-- Delivery Date --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Delivery Date</label>
                    <input type="date" name="delivery_date" value="{{ old('delivery_date') }}"
                           class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                {{-- Delivery Week No --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Delivery Week No.</label>
                    <input type="text" name="delivery_week_no" value="{{ old('delivery_week_no') }}" placeholder="e.g. WK01"
                           class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                {{-- Status --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Status <span class="text-red-400">*</span></label>
                    <select name="status"
                            class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                        @foreach(\App\Models\LiveChicken::STATUSES as $s)
                            <option value="{{ $s }}" @selected(old('status', 'Ongoing') === $s)>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>

            </div>

            {{-- Docs Section --}}
            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-5">

                {{-- Docs Required --}}
                <div class="bg-gray-900 rounded-lg p-4 border border-gray-700">
                    <label class="block text-gray-200 font-semibold mb-3">Docs Required</label>
                    <div class="flex gap-4 mb-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="docs_required_type" value="file" id="req_type_file"
                                   @checked(old('docs_required_type') === 'file') class="text-purple-500">
                            <span class="text-sm text-gray-300">File Attachment</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="docs_required_type" value="date" id="req_type_date"
                                   @checked(old('docs_required_type') === 'date') class="text-purple-500">
                            <span class="text-sm text-gray-300">Date</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="docs_required_type" value="" id="req_type_none"
                                   @checked(!old('docs_required_type')) class="text-purple-500">
                            <span class="text-sm text-gray-300">None</span>
                        </label>
                    </div>
                    <div id="req_file_input" class="hidden">
                        <input type="file" name="docs_required_file" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                               class="w-full text-gray-300 text-sm">
                    </div>
                    <div id="req_date_input" class="hidden">
                        <input type="date" name="docs_required_date" value="{{ old('docs_required_date') }}"
                               class="w-full bg-gray-800 border border-gray-600 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                    </div>
                </div>

                {{-- Docs Transmitted --}}
                <div class="bg-gray-900 rounded-lg p-4 border border-gray-700">
                    <label class="block text-gray-200 font-semibold mb-3">Docs Transmitted</label>
                    <div class="flex gap-4 mb-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="docs_transmitted_type" value="file" id="trans_type_file"
                                   @checked(old('docs_transmitted_type') === 'file') class="text-purple-500">
                            <span class="text-sm text-gray-300">File Attachment</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="docs_transmitted_type" value="date" id="trans_type_date"
                                   @checked(old('docs_transmitted_type') === 'date') class="text-purple-500">
                            <span class="text-sm text-gray-300">Date</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="docs_transmitted_type" value="" id="trans_type_none"
                                   @checked(!old('docs_transmitted_type')) class="text-purple-500">
                            <span class="text-sm text-gray-300">None</span>
                        </label>
                    </div>
                    <div id="trans_file_input" class="hidden">
                        <input type="file" name="docs_transmitted_file" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                               class="w-full text-gray-300 text-sm">
                    </div>
                    <div id="trans_date_input" class="hidden">
                        <input type="date" name="docs_transmitted_date" value="{{ old('docs_transmitted_date') }}"
                               class="w-full bg-gray-800 border border-gray-600 text-white rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                    </div>
                </div>

            </div>

            <div class="mt-6 flex gap-3">
                <button type="submit" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded font-semibold">
                    Save Record
                </button>
                <a href="{{ route('live_chickens.index') }}" class="px-6 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded">
                    Cancel
                </a>
            </div>

        </form>
    </div>
</div>

<script>
// Docs toggle
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

// Auto-calculate amount
let poQty = 0;
function recalcAmount() {
    const price = parseFloat(document.getElementById('price_field').value) || 0;
    const qty   = parseFloat(document.getElementById('actual_qty_field').value) || 0;
    document.getElementById('amount_field').value = (price * qty).toFixed(2);
    checkQtyWarning(qty);
}
function checkQtyWarning(qty) {
    const warn = document.getElementById('qty_warning');
    warn.classList.toggle('hidden', !(poQty > 0 && qty < poQty));
}
document.getElementById('price_field').addEventListener('input', recalcAmount);
document.getElementById('actual_qty_field').addEventListener('input', recalcAmount);

// PO search
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
                        supplierFld.value = po.supplier || '';
                        if (brandFld && po.brand) brandFld.value = po.brand;
                        if (itemsFld && po.items_desc) itemsFld.value = po.items_desc;
                        if (po.price) document.getElementById('price_field').value = parseFloat(po.price).toFixed(2);
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

// Items description autocomplete
const itemsDropdown = document.getElementById('items_dropdown');
let itemsDebounce;
itemsFld.addEventListener('input', function () {
    clearTimeout(itemsDebounce);
    const q = this.value.trim();
    if (!q || q.length < 2) { itemsDropdown.classList.add('hidden'); return; }
    itemsDebounce = setTimeout(() => {
        fetch(`/purchase_orders/search-items?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(data => {
                itemsDropdown.innerHTML = '';
                if (!data.length) { itemsDropdown.classList.add('hidden'); return; }
                data.slice(0, 10).forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'px-3 py-2 hover:bg-gray-700 cursor-pointer text-sm border-b border-gray-800';
                    div.innerHTML = `<span class="text-white">${item.item_description || item.description}</span>`
                        + (item.item_code ? `<span class="text-gray-500 ml-2 text-xs">${item.item_code}</span>` : '');
                    div.addEventListener('click', () => {
                        itemsFld.value = item.item_description || item.description || '';
                        if (brandFld && item.brand) brandFld.value = item.brand;
                        itemsDropdown.classList.add('hidden');
                    });
                    itemsDropdown.appendChild(div);
                });
                itemsDropdown.classList.remove('hidden');
            }).catch(() => itemsDropdown.classList.add('hidden'));
    }, 300);
});

document.addEventListener('click', e => {
    if (!itemsDropdown.contains(e.target) && e.target !== itemsFld) itemsDropdown.classList.add('hidden');
});
</script>
@endsection
