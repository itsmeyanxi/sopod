@extends('layouts.app')

@section('title', 'Create Cash Advance Request')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">CASH ADVANCE REQUEST</h1>
            <div class="text-right">
                <label class="font-semibold text-gray-300 block mb-1">CAR NO:</label>
                <input type="text" name="car_no" value="{{ old('car_no', $carNo) }}"
                    class="px-3 py-1 bg-gray-900 border border-gray-600 text-white rounded focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm w-48">
            </div>
        </div>

        @if($errors->any())
            <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-700 text-white px-4 py-3 rounded mb-4">{{ session('error') }}</div>
        @endif

        <form action="{{ route('cash_advance_requests.store') }}" method="POST" enctype="multipart/form-data" id="carForm">
            @csrf

            <!-- File Attachment -->
            <div class="mb-6 p-4 bg-gray-900 border border-gray-700 rounded">
                <label class="block font-semibold text-gray-300 mb-2"><i class="fas fa-paperclip mr-1"></i> ATTACH FILE <span class="text-xs text-gray-400 font-normal">(PDF, image, Excel, Word — max 10MB). If attached, all other fields become optional.</span></label>
                <input type="file" name="attachment" id="attachmentInput" accept=".pdf,.jpg,.jpeg,.png,.xlsx,.xls,.doc,.docx"
                    class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <!-- Main Form Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">PAYEE: <span class="text-red-700">*</span></label>
                    <div class="relative">
                        <input type="text" id="payeeSearch" autocomplete="off"
                            class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                            placeholder="Search employee..." value="{{ old('payee') }}" required>
                        <input type="hidden" name="payee" id="payeeValue" value="{{ old('payee') }}">
                        <div id="payeeDropdown" class="hidden absolute z-20 left-0 right-0 bg-gray-800 border border-gray-600 rounded shadow-lg max-h-48 overflow-y-auto" style="top:100%"></div>
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">DEPARTMENT: <span class="text-red-700">*</span></label>
                    <div class="relative">
                        <input type="text" name="department" id="deptInput" autocomplete="off"
                            class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                            value="{{ old('department') }}" required placeholder="Type or search department...">
                        <div id="deptDropdown" class="hidden absolute z-20 left-0 right-0 bg-gray-800 border border-gray-600 rounded shadow-lg max-h-48 overflow-y-auto" style="top:100%"></div>
                    </div>
                </div>
            </div>

            <!-- Purpose -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">PURPOSE: <span class="text-red-700">*</span></label>
                <textarea name="purpose" rows="4" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Enter purpose of cash advance..." required>{{ old('purpose') }}</textarea>
            </div>

            <!-- Dates and Amount -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">DATE REQUESTED: <span class="text-red-700">*</span></label>
                    <input type="date" name="date_requested" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('date_requested', date('Y-m-d')) }}" required>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">DATE NEEDED: <span class="text-red-700">*</span></label>
                    <input type="date" name="date_needed" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('date_needed') }}" required>
                </div>
            </div>

            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">AMOUNT ADVANCED: <span class="text-red-700">*</span></label>
                <div class="relative">
                    <span class="absolute left-3 top-2.5 text-gray-300">&#8369;</span>
                    <input type="text" id="amount_display" inputmode="decimal"
                        class="w-full bg-gray-900 border border-gray-700 rounded pl-8 pr-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="0.00" autocomplete="off" required>
                    <input type="hidden" name="amount_advanced" id="amount_advanced" value="{{ old('amount_advanced') }}">
                </div>
            </div>

            <!-- Signature Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">REQUESTED BY:</label>
                    <input type="text" name="requested_by" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('requested_by') }}">
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">CHECKED BY (Department Head):</label>
                    <input type="text" name="checked_by" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('checked_by') }}">
                </div>
            </div>

            <!-- Remarks -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">REMARKS:</label>
                <textarea name="remarks" rows="3" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Optional remarks...">{{ old('remarks') }}</textarea>
            </div>

            <!-- Fine Print -->
            <div class="mb-6 p-4 bg-gray-900 border border-gray-700 rounded">
                <p class="text-gray-300 text-sm italic">
                    I hereby acknowledge receipt of the above sum of money and hereby agree to liquidate in 5 calendar days after the cash advance serve its purpose and provide receipts to document the expenditures.
                </p>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('cash_advance_requests.index') }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-700 transition">
                    Cancel
                </a>
                <button type="submit" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800">
                    <i class="fas fa-save mr-1"></i> Create Cash Advance Request
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const searchUrl = '{{ route("suppliers.vendors.employees_search") }}';
    const deptUrl   = '{{ route("suppliers.vendors.departments_search") }}';
    const input     = document.getElementById('payeeSearch');
    const hidden    = document.getElementById('payeeValue');
    const dropdown  = document.getElementById('payeeDropdown');
    const deptInput = document.getElementById('deptInput');
    const deptDD    = document.getElementById('deptDropdown');
    let t, td;

    // Payee search
    input.addEventListener('input', function () {
        hidden.value = this.value;
        clearTimeout(t);
        if (!this.value.length) { dropdown.classList.add('hidden'); return; }
        t = setTimeout(async () => {
            const items = await fetch(searchUrl + '?q=' + encodeURIComponent(this.value)).then(r => r.json());
            if (!items.length) { dropdown.classList.add('hidden'); return; }
            dropdown.innerHTML = items.map(e =>
                `<div class="px-3 py-2 hover:bg-gray-700 cursor-pointer text-sm text-gray-200 border-b border-gray-700 payee-opt"
                      data-name="${e.vendor_name}" data-dept="${e.department || ''}">${e.vendor_name} <span class="text-xs text-gray-400">${e.vendor_code}</span></div>`
            ).join('');
            dropdown.classList.remove('hidden');
            dropdown.querySelectorAll('.payee-opt').forEach(opt => {
                opt.addEventListener('mousedown', e => {
                    e.preventDefault();
                    input.value = hidden.value = opt.dataset.name;
                    if (opt.dataset.dept && deptInput) deptInput.value = opt.dataset.dept;
                    dropdown.classList.add('hidden');
                });
            });
        }, 250);
    });
    input.addEventListener('focus', function () { if (this.value) this.dispatchEvent(new Event('input')); });
    input.addEventListener('blur', () => setTimeout(() => dropdown.classList.add('hidden'), 200));

    // Department autocomplete
    deptInput.addEventListener('input', function () {
        clearTimeout(td);
        if (!this.value.length) { deptDD.classList.add('hidden'); return; }
        td = setTimeout(async () => {
            const items = await fetch(deptUrl + '?q=' + encodeURIComponent(this.value)).then(r => r.json());
            if (!items.length) { deptDD.classList.add('hidden'); return; }
            deptDD.innerHTML = items.map(d =>
                `<div class="px-3 py-2 hover:bg-gray-700 cursor-pointer text-sm text-gray-200 border-b border-gray-700 dept-opt">${d}</div>`
            ).join('');
            deptDD.classList.remove('hidden');
            deptDD.querySelectorAll('.dept-opt').forEach(opt => {
                opt.addEventListener('mousedown', e => {
                    e.preventDefault();
                    deptInput.value = opt.textContent;
                    deptDD.classList.add('hidden');
                });
            });
        }, 250);
    });
    deptInput.addEventListener('blur', () => setTimeout(() => deptDD.classList.add('hidden'), 200));

    // Toggle required when file is attached
    const attachInput = document.getElementById('attachmentInput');
    const requiredFields = document.querySelectorAll('#carForm [required]');
    attachInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            requiredFields.forEach(f => f.removeAttribute('required'));
        } else {
            requiredFields.forEach(f => f.setAttribute('required', ''));
        }
    });
})();

// Thousands separator for amount field
(function() {
    const display = document.getElementById('amount_display');
    const hidden  = document.getElementById('amount_advanced');

    function formatNumber(val) {
        val = val.replace(/[^0-9.]/g, '');
        const parts = val.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        if (parts.length > 2) parts.splice(2);
        if (parts[1] !== undefined) parts[1] = parts[1].slice(0, 2);
        return parts.join('.');
    }

    function rawNumber(val) {
        return val.replace(/,/g, '');
    }

    display.addEventListener('input', function() {
        const raw = rawNumber(this.value);
        this.value = formatNumber(this.value);
        hidden.value = raw;
    });

    display.addEventListener('blur', function() {
        const raw = parseFloat(rawNumber(this.value));
        if (!isNaN(raw)) {
            this.value = raw.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            hidden.value = raw.toFixed(2);
        }
    });

    // Pre-fill if old value exists
    if (hidden.value) {
        const raw = parseFloat(hidden.value);
        if (!isNaN(raw)) display.value = raw.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
})();
</script>
@endsection
