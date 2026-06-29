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

        <div class="mb-4 p-3 bg-blue-900 border border-blue-600 rounded text-sm text-blue-200">
            Only <strong>item quantities</strong> can be changed on this form. All other fields are locked.
        </div>

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

                {{-- Date (readonly) --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Date</label>
                    <input type="date" name="date" value="{{ $record->date->format('Y-m-d') }}"
                           readonly class="w-full bg-gray-700 border border-gray-600 text-gray-300 rounded px-3 py-2 cursor-not-allowed">
                </div>

                {{-- PO Number (readonly) --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">PO Number</label>
                    <input type="text" name="po_no" value="{{ $record->po_no }}"
                           readonly class="w-full bg-gray-700 border border-gray-600 text-gray-300 rounded px-3 py-2 cursor-not-allowed">
                </div>

                {{-- RR Number (readonly) --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">RR Number</label>
                    <input type="text" name="srr_code" value="{{ $record->srr_code }}"
                           readonly class="w-full bg-gray-700 border border-gray-600 text-gray-300 rounded px-3 py-2 cursor-not-allowed">
                </div>

                {{-- RFP Number (readonly) --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">RFP Number</label>
                    <input type="text" name="rfp_no" value="{{ $record->rfp_no }}"
                           readonly class="w-full bg-gray-700 border border-gray-600 text-gray-300 rounded px-3 py-2 cursor-not-allowed">
                </div>

                {{-- Reference Number (readonly) --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Reference Number</label>
                    <input type="text" name="reference_number" value="{{ $record->reference_number }}"
                           readonly class="w-full bg-gray-700 border border-gray-600 text-gray-300 rounded px-3 py-2 cursor-not-allowed">
                </div>

                {{-- Supplier (readonly) --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Supplier</label>
                    <input type="text" name="supplier" value="{{ $record->supplier }}"
                           readonly class="w-full bg-gray-700 border border-gray-600 text-gray-300 rounded px-3 py-2 cursor-not-allowed">
                </div>

                {{-- Brand (readonly) --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Brand</label>
                    <input type="text" name="brand" value="{{ $record->brand }}"
                           readonly class="w-full bg-gray-700 border border-gray-600 text-gray-300 rounded px-3 py-2 cursor-not-allowed">
                </div>

                {{-- Items Table (qty only editable) --}}
                <div class="md:col-span-2">
                    <label class="block text-gray-300 text-sm font-semibold mb-1">
                        Items <span class="text-blue-300 text-xs font-normal ml-1">(only Qty can be changed)</span>
                    </label>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs bg-gray-900 rounded border border-gray-700" id="items_table">
                            <thead class="bg-gray-700 text-gray-300">
                                <tr>
                                    <th class="px-2 py-2 text-left">Description</th>
                                    <th class="px-2 py-2 text-left w-28">Brand</th>
                                    <th class="px-2 py-2 text-left w-20">Qty</th>
                                    <th class="px-2 py-2 text-left w-20">UOM</th>
                                    <th class="px-2 py-2 text-left w-24">Unit Price</th>
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
                                    <td class="px-1 py-1"><input type="text" value="{{ $ei['description']??'' }}" readonly class="w-full bg-gray-700 text-gray-300 rounded px-2 py-1 text-xs item-desc cursor-not-allowed"></td>
                                    <td class="px-1 py-1"><input type="text" value="{{ $ei['brand']??'' }}" readonly class="w-full bg-gray-700 text-gray-300 rounded px-2 py-1 text-xs item-brand cursor-not-allowed"></td>
                                    <td class="px-1 py-1"><input type="number" value="{{ $ei['qty']??0 }}" step="0.01" class="w-full bg-gray-800 border border-purple-600 text-white rounded px-2 py-1 text-xs item-qty focus:ring-2 focus:ring-purple-500 focus:outline-none"></td>
                                    <td class="px-1 py-1"><input type="text" value="{{ $ei['uom']??'' }}" readonly class="w-full bg-gray-700 text-gray-300 rounded px-2 py-1 text-xs item-uom cursor-not-allowed"></td>
                                    <td class="px-1 py-1"><input type="number" value="{{ $ei['unit_price']??0 }}" step="0.01" readonly class="w-full bg-gray-700 text-gray-300 rounded px-2 py-1 text-xs item-price cursor-not-allowed"></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <input type="hidden" name="items" id="items_hidden">
                    <input type="hidden" name="items_data" id="items_data_hidden">
                </div>

                {{-- Price (readonly) --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Price</label>
                    <input type="number" name="price" value="{{ $record->price }}" step="0.01" id="price_field"
                           readonly class="w-full bg-gray-700 border border-gray-600 text-gray-300 rounded px-3 py-2 cursor-not-allowed">
                </div>

                <input type="hidden" name="actual_qty" id="actual_qty_field" value="{{ $record->actual_qty }}">

                {{-- Amount (auto-recalc) --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Amount <span class="text-xs text-blue-300 font-normal">(auto-calculated)</span></label>
                    <input type="number" name="amount" value="{{ $record->amount }}" step="0.01" id="amount_field"
                           readonly class="w-full bg-gray-700 border border-gray-600 text-gray-300 rounded px-3 py-2 cursor-not-allowed">
                </div>

                {{-- Delivery Date (readonly) --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Delivery Date</label>
                    <input type="date" name="delivery_date" value="{{ $record->delivery_date?->format('Y-m-d') }}"
                           readonly class="w-full bg-gray-700 border border-gray-600 text-gray-300 rounded px-3 py-2 cursor-not-allowed">
                </div>

                {{-- Delivery Week No (readonly) --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Delivery Week No.</label>
                    <input type="text" name="delivery_week_no" value="{{ $record->delivery_week_no }}"
                           readonly class="w-full bg-gray-700 border border-gray-600 text-gray-300 rounded px-3 py-2 cursor-not-allowed">
                </div>

                {{-- Status (readonly) --}}
                <div>
                    <label class="block text-gray-300 mb-1 text-sm font-semibold">Status</label>
                    <input type="text" value="{{ $record->status }}"
                           readonly class="w-full bg-gray-700 border border-gray-600 text-gray-300 rounded px-3 py-2 cursor-not-allowed">
                    <input type="hidden" name="status" value="{{ $record->status }}">
                </div>

            </div>

            {{-- Docs Section --}}
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

function getItemQtySum() {
    let total = 0;
    document.querySelectorAll('#items_tbody .item-qty').forEach(i => { total += parseFloat(i.value) || 0; });
    return total;
}
function recalcAmount() {
    const price = parseFloat(document.getElementById('price_field').value) || 0;
    const qty   = getItemQtySum();
    document.getElementById('actual_qty_field').value = qty.toFixed(2);
    document.getElementById('amount_field').value = (price * qty).toFixed(2);
}
document.getElementById('items_tbody').addEventListener('input', function(e) {
    if (e.target.classList.contains('item-qty')) recalcAmount();
});

document.querySelector('form').addEventListener('submit', function() {
    const rows = document.querySelectorAll('#items_tbody tr');
    const data = [];
    rows.forEach(tr => {
        const desc = tr.querySelector('.item-desc')?.value?.trim();
        if (desc) data.push({
            description: desc,
            brand: tr.querySelector('.item-brand')?.value?.trim() || '',
            qty: parseFloat(tr.querySelector('.item-qty')?.value) || 0,
            uom: tr.querySelector('.item-uom')?.value?.trim() || '',
            unit_price: parseFloat(tr.querySelector('.item-price')?.value) || 0,
        });
    });
    document.getElementById('items_hidden').value = data.map(d => d.description).join('\n');
    document.getElementById('items_data_hidden').value = JSON.stringify(data);
});
</script>
@endsection
