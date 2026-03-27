@extends('layouts.app')

@section('title', 'Create AR Adjustment')

@section('content')
<div class="container mx-auto">
    <div class="bg-white text-gray-800 rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-200 pb-4">
            <h1 class="text-2xl font-bold">CREATE AR ADJUSTMENT</h1>
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

        <form action="{{ route('ar_adjustments.store') }}" method="POST" id="adjustmentForm" enctype="multipart/form-data">
            @csrf

            <!-- Transaction Date & Reference Number -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">Transaction Date: <span class="text-red-700">*</span></label>
                    <input type="date" name="transaction_date" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('transaction_date', date('Y-m-d')) }}" required>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">Reference Number:</label>
                    <input type="text" name="reference_number" class="w-full bg-gray-100 border border-gray-200 rounded px-3 py-2 text-gray-500 cursor-not-allowed" value="{{ $nextRefNumber }}" readonly>
                    <p class="text-xs text-gray-400 mt-1">Auto-generated on save</p>
                </div>
            </div>

            <!-- Transaction Type & Customer Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">Transaction Type: <span class="text-red-700">*</span></label>
                    <select name="transaction_type" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                        <option value="">-- Select Transaction Type --</option>
                        <option value="debit_memo" {{ old('transaction_type') == 'debit_memo' ? 'selected' : '' }}>Debit Memo</option>
                        <option value="credit_memo" {{ old('transaction_type') == 'credit_memo' ? 'selected' : '' }}>Credit Memo</option>
                        <option value="sales_return_allowances" {{ old('transaction_type') == 'sales_return_allowances' ? 'selected' : '' }}>Sales Return and Allowances</option>
                        <option value="price_adjustment" {{ old('transaction_type') == 'price_adjustment' ? 'selected' : '' }}>Price Adjustment</option>
                        <option value="rebates" {{ old('transaction_type') == 'rebates' ? 'selected' : '' }}>Rebates</option>
                        <option value="distribution_fees" {{ old('transaction_type') == 'distribution_fees' ? 'selected' : '' }}>Distribution Fees</option>
                        <option value="penalty" {{ old('transaction_type') == 'penalty' ? 'selected' : '' }}>Penalty</option>
                        <option value="promotional_expenses" {{ old('transaction_type') == 'promotional_expenses' ? 'selected' : '' }}>Promotional Expenses</option>
                        <option value="small_balance_adjustment" {{ old('transaction_type') == 'small_balance_adjustment' ? 'selected' : '' }}>Small balance adjustment</option>
                        <option value="atd" {{ old('transaction_type') == 'atd' ? 'selected' : '' }}>ATD</option>
                        <option value="offset" {{ old('transaction_type') == 'offset' ? 'selected' : '' }}>Offset</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">Customer Name: <span class="text-red-700">*</span></label>
                    <input type="text" name="customer_name" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Enter customer name" value="{{ old('customer_name') }}" required>
                </div>
            </div>

            <!-- Link Receiving Report -->
            <div class="mb-6 p-4 bg-purple-50 border border-purple-200 rounded-lg">
                <label class="block font-semibold text-purple-700 mb-2"><i class="fas fa-link mr-1"></i> Link Receiving Report (optional)</label>
                <div class="relative">
                    <input type="text" id="rrSearchInput" autocomplete="off"
                        class="w-full bg-white border border-purple-300 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="Search by RR number, DR number, customer name, or SO number...">
                    <div id="rrDropdown" class="absolute z-50 w-full bg-white border border-gray-200 rounded mt-1 shadow-lg hidden max-h-72 overflow-y-auto"></div>
                </div>
                <!-- Selected RR preview -->
                <div id="rrPreview" class="hidden mt-3 p-3 bg-white rounded border border-purple-200">
                    <div class="flex justify-between items-start">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm flex-1">
                            <div>
                                <p class="text-xs text-gray-500">RR Number</p>
                                <p class="font-semibold text-purple-700" id="rrPreviewRR"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">DR / Delivery Batch</p>
                                <p class="font-semibold text-gray-800" id="rrPreviewDR"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Customer</p>
                                <p class="font-semibold text-gray-800" id="rrPreviewCustomer"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">RR Total Amount</p>
                                <p class="font-semibold text-green-700" id="rrPreviewAmount"></p>
                            </div>
                        </div>
                        <button type="button" id="rrClearBtn" class="text-red-500 hover:text-red-700 ml-3 text-sm">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                </div>
                <input type="hidden" name="receiving_report_id" id="receivingReportId">
            </div>

            <!-- Customer Code & Branch -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">Customer Code:</label>
                    <input type="text" name="customer_code" id="customerCodeInput" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Customer code (optional)" value="{{ old('customer_code') }}">
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">Branch:</label>
                    <input type="text" name="branch" id="branchInput" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Branch (optional)" value="{{ old('branch') }}">
                </div>
            </div>

            <!-- DR & Invoice Numbers -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">DR Number:</label>
                    <input type="text" name="dr_no" id="drNoInput" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Delivery Report number (optional)" value="{{ old('dr_no') }}">
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">Invoice Number:</label>
                    <input type="text" name="invoice_number" id="invoiceNumberInput" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Invoice number (optional)" value="{{ old('invoice_number') }}">
                </div>
            </div>

            <!-- Amount & GL Account -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div>
        <label class="block font-semibold text-gray-500 mb-2">Amount: <span class="text-red-700">*</span></label>
        <div class="relative">
            <span class="absolute left-3 top-2 text-gray-500 text-lg">₱</span>
            <input type="text" name="amount" id="amountInput"
                class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 pl-8 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500"
                placeholder="0.00 (use - or () for decrease)"
                value="{{ old('amount') }}" required>
        </div>
        <p class="text-xs text-gray-500 mt-1">Enter negative/() to decrease AR, positive to increase AR</p>
    </div>

    <div>
    <label class="block font-semibold text-gray-500 mb-2">GL Account: <span class="text-red-700">*</span></label>

    <input type="hidden" name="gl_account_id" id="glAccountId" value="{{ old('gl_account_id') }}">
    <input type="hidden" name="gl_account"    id="glAccountCode" value="{{ old('gl_account') }}">

    <div class="relative gl-search-container">

        {{-- Search input --}}
        <div class="relative">
            <input
                type="text"
                id="gl_search_input"
                class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 pr-10 text-gray-800 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-purple-500"
                placeholder="Search by code or name..."
                autocomplete="off">
            <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>

        {{-- Dropdown: all options pre-rendered by Blade, filtered by JS --}}
        <div id="gl_dropdown"
            class="absolute z-50 w-full bg-white border border-gray-200 rounded mt-1 shadow-lg hidden max-h-64 overflow-y-auto">

            <div class="sticky top-0 bg-gray-100 px-3 py-2 text-xs text-gray-500 font-semibold border-b border-gray-200">
                Select a GL Account
            </div>

            @foreach($glAccounts as $account)
                <div
                    class="gl-option px-4 py-3 hover:bg-purple-600 hover:text-white cursor-pointer text-gray-800 border-b border-gray-100 last:border-b-0 transition-colors"
                    data-id="{{ $account['id'] }}"
                    data-code="{{ $account['code'] }}"
                    data-name="{{ $account['name'] }}"
                    data-display="{{ $account['display'] }}"
                    data-search="{{ strtolower($account['code'] . ' ' . $account['name'] . ' ' . ($account['fs_line_item'] ?? '')) }}">
                    <div class="font-semibold text-sm">{{ $account['code'] }}
                        <span class="font-normal text-gray-600"> — {{ $account['name'] }}</span>
                    </div>
                    <div class="text-xs text-gray-400">{{ $account['fs_line_item'] ?? 'No FS Item' }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>

            <!-- Signed By -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-500 mb-2">Signed By: <span class="text-red-700">*</span></label>
                <input type="text" name="signed_by" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Name of person who signed" value="{{ old('signed_by') }}" required>
            </div>

            <!-- Remarks & Attachment -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">Remarks:</label>
                    <textarea name="remarks" rows="4" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Enter any remarks...">{{ old('remarks') }}</textarea>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">Supporting Document:</label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-purple-400 transition" id="dropZone">
                        <input type="file" name="attachment" id="attachmentInput" accept=".pdf,.png,.jpg,.jpeg" class="hidden">
                        <div id="uploadPlaceholder">
                            <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                            <p class="text-sm text-gray-500">Click or drag & drop to upload</p>
                            <p class="text-xs text-gray-400 mt-1">PDF, PNG, JPG (max 5MB)</p>
                        </div>
                        <div id="filePreview" class="hidden">
                            <i class="fas fa-file text-2xl text-purple-600 mb-1"></i>
                            <p class="text-sm font-medium text-gray-700" id="fileName"></p>
                            <p class="text-xs text-gray-400" id="fileSize"></p>
                            <button type="button" id="removeFile" class="text-red-500 text-xs mt-1 hover:underline">
                                <i class="fas fa-times mr-1"></i>Remove
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Box -->
            <div class="mb-6 p-4 bg-blue-50 border border-blue-700 rounded">
                <p class="text-blue-700 text-sm"><i class="fas fa-info-circle mr-2"></i><strong>Amount Format:</strong> Enter amount as positive number. Use negative sign (-) or parentheses () prefix to mark as decrease (credit) to AR balance.</p>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('ar_adjustments.index') }}" class="bg-gray-100 text-gray-800 px-6 py-2 rounded hover:bg-gray-200 transition">
                    Cancel
                </a>
                <button type="submit" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800">
                    <i class="fas fa-save mr-1"></i> Create Adjustment
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ================= GL ACCOUNT SEARCHABLE DROPDOWN =================
    const glSearchInput = document.getElementById('gl_search_input');
    const glDropdown    = document.getElementById('gl_dropdown');
    const glAccountId   = document.getElementById('glAccountId');
    const glAccountCode = document.getElementById('glAccountCode');

    // Cache original HTML once on load — same technique as Sales Order item search
    const originalGlHTML = glDropdown.innerHTML;

    function filterGlOptions(searchTerm) {
        const options = glDropdown.querySelectorAll('.gl-option');

        if (searchTerm === '') {
            options.forEach(opt => opt.style.display = 'block');
            return;
        }

        let visible = 0;
        options.forEach(opt => {
            const match = opt.getAttribute('data-search').includes(searchTerm);
            opt.style.display = match ? 'block' : 'none';
            if (match) visible++;
        });

        if (visible === 0) {
            // Append no-results instead of wiping the cached options
            const msg = document.createElement('div');
            msg.className = 'gl-no-results px-4 py-6 text-center text-gray-400 text-sm';
            msg.innerHTML = `
                <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="font-medium">No GL accounts found</div>
                <div class="text-xs mt-1">Try a different search term</div>
            `;
            glDropdown.appendChild(msg);
        }
    }

    function rebindGlClicks() {
        glDropdown.querySelectorAll('.gl-option').forEach(opt => {
            opt.addEventListener('click', handleGlSelect);
        });
    }

    function handleGlSelect() {
        glAccountId.value   = this.getAttribute('data-id');
        glAccountCode.value = this.getAttribute('data-code');
        glSearchInput.value = this.getAttribute('data-display');
        glDropdown.classList.add('hidden');
    }

    // Show + reset dropdown on focus
    glSearchInput.addEventListener('focus', function () {
        glDropdown.innerHTML = originalGlHTML;
        rebindGlClicks();
        filterGlOptions(this.value.toLowerCase());
        glDropdown.classList.remove('hidden');
    });

    // Filter on every keystroke
    glSearchInput.addEventListener('input', function () {
        // Remove any leftover no-results message without touching the cached options
        glDropdown.querySelectorAll('.gl-no-results').forEach(el => el.remove());
        filterGlOptions(this.value.toLowerCase());
        glDropdown.classList.remove('hidden');
    });

    // Enter key also triggers filter
    glSearchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            glDropdown.querySelectorAll('.gl-no-results').forEach(el => el.remove());
            filterGlOptions(this.value.toLowerCase());
            glDropdown.classList.remove('hidden');
        }
        // Clear hidden values if user wipes the input
        if ((e.key === 'Backspace' || e.key === 'Delete') && this.value.length <= 1) {
            glAccountId.value   = '';
            glAccountCode.value = '';
        }
    });

    // Close on outside click
    document.addEventListener('click', function (e) {
        if (!document.querySelector('.gl-search-container').contains(e.target)) {
            glDropdown.classList.add('hidden');
        }
    });

    // Initial click binding
    rebindGlClicks();

    // Restore old() value after a validation error
    const oldCode = '{{ old("gl_account") }}';
    if (oldCode) {
        const match = glDropdown.querySelector(`.gl-option[data-code="${CSS.escape(oldCode)}"]`);
        if (match) match.click();
    }

    // ================= RECEIVING REPORT SEARCH =================
    const rrSearchInput = document.getElementById('rrSearchInput');
    const rrDropdown = document.getElementById('rrDropdown');
    const rrPreview = document.getElementById('rrPreview');
    const rrClearBtn = document.getElementById('rrClearBtn');
    const receivingReportId = document.getElementById('receivingReportId');
    let rrSearchTimeout = null;

    rrSearchInput.addEventListener('input', function() {
        clearTimeout(rrSearchTimeout);
        const val = this.value.trim();
        if (val.length < 2) {
            rrDropdown.classList.add('hidden');
            return;
        }
        rrSearchTimeout = setTimeout(() => fetchRRResults(val), 300);
    });

    async function fetchRRResults(search) {
        try {
            const res = await fetch(`{{ route('ar_adjustments.search_rr') }}?search=${encodeURIComponent(search)}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            });
            const data = await res.json();
            renderRRDropdown(data.results || []);
        } catch (e) {
            console.error('RR search failed:', e);
        }
    }

    function renderRRDropdown(results) {
        if (!results.length) {
            rrDropdown.innerHTML = '<div class="px-4 py-3 text-center text-gray-400 text-sm">No receiving reports found</div>';
            rrDropdown.classList.remove('hidden');
            return;
        }

        rrDropdown.innerHTML = results.map(rr => `
            <div class="rr-option px-4 py-3 hover:bg-purple-50 cursor-pointer border-b border-gray-100 last:border-0 transition"
                data-rr='${JSON.stringify(rr).replace(/'/g, "&#39;")}'>
                <div class="flex justify-between items-center">
                    <div>
                        <span class="font-semibold text-purple-700 text-sm">${rr.rr_number}</span>
                        <span class="text-gray-400 mx-1">|</span>
                        <span class="text-gray-600 text-sm">DR: ${rr.delivery_batch || '—'}</span>
                    </div>
                    <span class="text-green-700 font-semibold text-sm">₱${Number(rr.total_amount).toLocaleString('en', {minimumFractionDigits: 2})}</span>
                </div>
                <div class="text-xs text-gray-500 mt-1">
                    ${rr.customer_name || '—'} ${rr.customer_code ? '(' + rr.customer_code + ')' : ''}
                    <span class="text-gray-400 ml-1">${rr.received_date || ''}</span>
                    <span class="ml-1 px-1.5 py-0.5 rounded text-xs ${rr.status === 'Received' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'}">${rr.status}</span>
                </div>
            </div>
        `).join('');

        rrDropdown.querySelectorAll('.rr-option').forEach(opt => {
            opt.addEventListener('click', function() {
                const rr = JSON.parse(this.dataset.rr);
                selectRR(rr);
            });
        });

        rrDropdown.classList.remove('hidden');
    }

    function selectRR(rr) {
        // Fill hidden field
        receivingReportId.value = rr.id;

        // Fill form fields
        document.querySelector('[name="customer_name"]').value = rr.customer_name || '';
        document.getElementById('customerCodeInput').value = rr.customer_code || '';
        document.getElementById('branchInput').value = rr.branch || '';
        document.getElementById('drNoInput').value = rr.delivery_batch || '';
        document.getElementById('invoiceNumberInput').value = rr.sales_invoice_no || '';

        // Fill amount
        const amountInput = document.getElementById('amountInput');
        if (rr.total_amount > 0) {
            amountInput.value = Number(rr.total_amount).toLocaleString('en', {minimumFractionDigits: 2});
        }

        // Show preview
        document.getElementById('rrPreviewRR').textContent = rr.rr_number;
        document.getElementById('rrPreviewDR').textContent = rr.delivery_batch || '—';
        document.getElementById('rrPreviewCustomer').textContent = `${rr.customer_name || '—'} (${rr.customer_code || '—'})`;
        document.getElementById('rrPreviewAmount').textContent = '₱' + Number(rr.total_amount).toLocaleString('en', {minimumFractionDigits: 2});

        rrPreview.classList.remove('hidden');
        rrDropdown.classList.add('hidden');
        rrSearchInput.value = rr.rr_number + ' — ' + (rr.delivery_batch || '');
    }

    rrClearBtn.addEventListener('click', function() {
        receivingReportId.value = '';
        rrSearchInput.value = '';
        rrPreview.classList.add('hidden');
    });

    // Close RR dropdown on outside click
    document.addEventListener('click', function(e) {
        if (!rrSearchInput.contains(e.target) && !rrDropdown.contains(e.target)) {
            rrDropdown.classList.add('hidden');
        }
    });

    // ================= FILE UPLOAD =================
    const dropZone = document.getElementById('dropZone');
    const attachmentInput = document.getElementById('attachmentInput');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');
    const filePreview = document.getElementById('filePreview');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const removeFile = document.getElementById('removeFile');

    dropZone.addEventListener('click', () => attachmentInput.click());

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('border-purple-500', 'bg-purple-50');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('border-purple-500', 'bg-purple-50');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-purple-500', 'bg-purple-50');
        if (e.dataTransfer.files.length) {
            attachmentInput.files = e.dataTransfer.files;
            showFilePreview(e.dataTransfer.files[0]);
        }
    });

    attachmentInput.addEventListener('change', function() {
        if (this.files.length) showFilePreview(this.files[0]);
    });

    function showFilePreview(file) {
        const allowed = ['application/pdf', 'image/png', 'image/jpeg'];
        if (!allowed.includes(file.type)) {
            Swal.fire('Invalid File', 'Only PDF, PNG, and JPG files are allowed.', 'error');
            attachmentInput.value = '';
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            Swal.fire('File Too Large', 'Maximum file size is 5MB.', 'error');
            attachmentInput.value = '';
            return;
        }
        fileName.textContent = file.name;
        fileSize.textContent = (file.size / 1024).toFixed(1) + ' KB';
        uploadPlaceholder.classList.add('hidden');
        filePreview.classList.remove('hidden');
    }

    removeFile.addEventListener('click', (e) => {
        e.stopPropagation();
        attachmentInput.value = '';
        filePreview.classList.add('hidden');
        uploadPlaceholder.classList.remove('hidden');
    });

    // ================= URL PRE-FILL =================
    const params = new URLSearchParams(window.location.search);
    if (params.get('dr_no'))            document.querySelector('[name="dr_no"]').value            = params.get('dr_no');
    if (params.get('customer_code'))    document.querySelector('[name="customer_code"]').value    = params.get('customer_code');
    if (params.get('customer_name'))    document.querySelector('[name="customer_name"]').value    = params.get('customer_name');
    if (params.get('sales_invoice_no')) document.querySelector('[name="invoice_number"]').value   = params.get('sales_invoice_no');

});
</script>
@endsection
