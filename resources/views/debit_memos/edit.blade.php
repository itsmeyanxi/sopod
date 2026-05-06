@extends('layouts.app')

@section('title', 'Edit Debit Memo')

@section('content')
<div class="container mx-auto max-w-3xl">
    <div class="bg-gray-800 rounded-lg shadow-lg p-6">

        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">Edit Debit Memo: {{ $debit_memo->dm_no }}</h1>
            <a href="{{ route('debit_memos.index') }}" class="text-gray-300 hover:text-gray-200 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>

        @if($errors->any())
            <div class="bg-red-100 text-red-800 px-4 py-3 rounded mb-4">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('debit_memos.update', $debit_memo) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-300 text-sm mb-1 font-medium">DM No.</label>
                    <input type="text" value="{{ $debit_memo->dm_no }}" class="w-full border border-gray-700 rounded px-3 py-2 text-gray-300 bg-gray-900 text-sm" disabled>
                </div>
                <div>
                    <label class="block text-gray-300 text-sm mb-1 font-medium">Memo Date <span class="text-red-500">*</span></label>
                    <input type="date" name="memo_date" value="{{ old('memo_date', $debit_memo->memo_date->format('Y-m-d')) }}" class="w-full border border-gray-600 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="relative">
                    <label class="block text-gray-300 text-sm mb-1 font-medium">Supplier <span class="text-red-500">*</span></label>
                    <input type="hidden" name="supplier_id" id="supplier_id" value="{{ old('supplier_id', $debit_memo->supplier_id) }}" required>
                    <input type="text" id="supplier_search_input" autocomplete="off"
                           placeholder="Type supplier name..."
                           value="{{ old('supplier_id') ? ($suppliers->firstWhere('id', old('supplier_id'))?->supplier_name ?? '') : ($debit_memo->supplier?->supplier_name ?? '') }}"
                           class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <div id="supplier_search_dropdown" class="hidden absolute z-50 left-0 right-0 bg-gray-800 border border-gray-600 rounded shadow-lg max-h-52 overflow-y-auto" style="top:100%;margin-top:2px;"></div>
                </div>
                <div>
                    <label class="block text-gray-300 text-sm mb-1 font-medium">Invoice (APV)</label>
                    <select name="invoice_id" class="w-full border border-gray-600 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">-- None --</option>
                        @foreach($invoices as $invoice)
                            <option value="{{ $invoice->id }}" {{ old('invoice_id', $debit_memo->invoice_id) == $invoice->id ? 'selected' : '' }}>
                                {{ $invoice->apv_no }} - {{ $invoice->vendor_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-300 text-sm mb-1 font-medium">Amount <span class="text-red-500">*</span></label>
                    <input type="number" name="amount" value="{{ old('amount', $debit_memo->amount) }}" step="0.01" min="0.01" class="w-full border border-gray-600 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" required>
                </div>
                <div>
                    <label class="block text-gray-300 text-sm mb-1 font-medium">Status</label>
                    <select name="status" class="w-full border border-gray-600 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="Open" {{ old('status', $debit_memo->status) === 'Open' ? 'selected' : '' }}>Open</option>
                        <option value="Applied" {{ old('status', $debit_memo->status) === 'Applied' ? 'selected' : '' }}>Applied</option>
                        <option value="Voided" {{ old('status', $debit_memo->status) === 'Voided' ? 'selected' : '' }}>Voided</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-300 text-sm mb-1 font-medium">Reason</label>
                <input type="text" name="reason" value="{{ old('reason', $debit_memo->reason) }}" class="w-full border border-gray-600 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>

            <div class="mb-6">
                <label class="block text-gray-300 text-sm mb-1 font-medium">Notes</label>
                <textarea name="notes" rows="3" class="w-full border border-gray-600 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">{{ old('notes', $debit_memo->notes) }}</textarea>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition text-sm font-medium">
                    <i class="fas fa-save mr-1"></i> Update Debit Memo
                </button>
            </div>
        </form>
    </div>
</div>
<script>
(function() {
    const SUPPLIER_QUICK_URL = '{{ route("suppliers.search_quick") }}';
    const searchInput  = document.getElementById('supplier_search_input');
    const hiddenId     = document.getElementById('supplier_id');
    const dropdown     = document.getElementById('supplier_search_dropdown');
    let debounce;

    searchInput.addEventListener('input', function() {
        hiddenId.value = '';
        clearTimeout(debounce);
        const q = this.value.trim();
        if (q.length < 1) { dropdown.classList.add('hidden'); return; }
        debounce = setTimeout(async () => {
            try {
                const res   = await fetch(`${SUPPLIER_QUICK_URL}?q=${encodeURIComponent(q)}`);
                const items = await res.json();
                if (!items.length) { dropdown.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">No suppliers found</div>'; dropdown.classList.remove('hidden'); return; }
                dropdown.innerHTML = items.map(s =>
                    `<div class="px-3 py-2 hover:bg-gray-700 cursor-pointer text-sm text-gray-200 supplier-opt"
                          data-id="${s.id}"
                          data-name="${s.supplier_name.replace(/"/g,'&quot;')}">
                        <span class="font-semibold">${s.supplier_name}</span>
                        <span class="text-gray-400 ml-2 text-xs">${s.supplier_code||''}</span>
                    </div>`
                ).join('');
                dropdown.classList.remove('hidden');
                dropdown.querySelectorAll('.supplier-opt').forEach(opt => {
                    opt.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        hiddenId.value    = this.dataset.id;
                        searchInput.value = this.dataset.name;
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
