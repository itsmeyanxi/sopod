@extends('layouts.app')
@section('title', 'Create Journal Voucher')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold">CREATE JOURNAL VOUCHER</h1>
            <span class="bg-gray-700 text-gray-500 px-3 py-1 rounded text-sm font-mono">{{ $nextJvNumber }}</span>
        </div>

        @if($errors->any())
            <div class="bg-red-100 text-red-800 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form action="{{ route('journal_vouchers.store') }}" method="POST" enctype="multipart/form-data" id="jvForm">
            @csrf

            <!-- Header Info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">JV Date <span class="text-red-700">*</span></label>
                    <input type="date" name="jv_date" value="{{ old('jv_date', date('Y-m-d')) }}" required class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Transaction Type <span class="text-red-700">*</span></label>
                    <select name="transaction_type" required class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                        <option value="">-- Select Type --</option>
                        <option value="bank_interest" {{ old('transaction_type') == 'bank_interest' ? 'selected' : '' }}>Bank Interest</option>
                        <option value="bank_charges" {{ old('transaction_type') == 'bank_charges' ? 'selected' : '' }}>Bank Charges</option>
                        <option value="reclassification" {{ old('transaction_type') == 'reclassification' ? 'selected' : '' }}>Reclassification</option>
                        <option value="adjustment" {{ old('transaction_type') == 'adjustment' ? 'selected' : '' }}>Adjustment</option>
                        <option value="correction" {{ old('transaction_type') == 'correction' ? 'selected' : '' }}>Correction</option>
                        <option value="accrual" {{ old('transaction_type') == 'accrual' ? 'selected' : '' }}>Accrual</option>
                        <option value="reversal" {{ old('transaction_type') == 'reversal' ? 'selected' : '' }}>Reversal</option>
                        <option value="depreciation" {{ old('transaction_type') == 'depreciation' ? 'selected' : '' }}>Depreciation</option>
                        <option value="tax_adjustment" {{ old('transaction_type') == 'tax_adjustment' ? 'selected' : '' }}>Tax Adjustment</option>
                        <option value="intercompany" {{ old('transaction_type') == 'intercompany' ? 'selected' : '' }}>Intercompany</option>
                        <option value="other" {{ old('transaction_type') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Reference No.</label>
                    <input type="text" name="reference_no" value="{{ old('reference_no') }}" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm" placeholder="e.g. Bank Ref, APV No.">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Description <span class="text-red-700">*</span></label>
                    <textarea name="description" rows="2" required class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm" placeholder="Describe the journal entry...">{{ old('description') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Remarks</label>
                    <textarea name="remarks" rows="2" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm" placeholder="Additional notes...">{{ old('remarks') }}</textarea>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Prepared By</label>
                    <input type="text" name="prepared_by" value="{{ old('prepared_by', auth()->user()->name) }}" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Checked By</label>
                    <input type="text" name="checked_by" value="{{ old('checked_by') }}" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Approved By</label>
                    <input type="text" name="approved_by" value="{{ old('approved_by') }}" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                </div>
            </div>

            <!-- Journal Lines -->
            <div class="mb-6">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-lg font-semibold text-gray-200">Journal Entry Lines</h3>
                    <button type="button" onclick="addLine()" class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">
                        <i class="fas fa-plus mr-1"></i> Add Line
                    </button>
                </div>

                <table class="w-full text-sm border-collapse" id="linesTable">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="border border-gray-700 px-3 py-2 text-left" style="min-width:250px;">Account Code</th>
                            <th class="border border-gray-700 px-3 py-2 text-left" style="min-width:180px;">Account Name</th>
                            <th class="border border-gray-700 px-3 py-2 text-left">Description</th>
                            <th class="border border-gray-700 px-3 py-2 text-left" style="min-width:120px;">Cost Center</th>
                            <th class="border border-gray-700 px-3 py-2 text-right" style="min-width:130px;">Debit</th>
                            <th class="border border-gray-700 px-3 py-2 text-right" style="min-width:130px;">Credit</th>
                            <th class="border border-gray-700 px-3 py-2 text-center" style="width:40px;"></th>
                        </tr>
                    </thead>
                    <tbody id="linesBody">
                        <tr class="jv-line">
                            <td class="border border-gray-700 px-2 py-2" style="position:relative; overflow:visible;">
                                <div class="relative acct-search-container" style="position:relative;">
                                    <input type="text" class="acct-search w-full bg-gray-800 border-2 border-gray-600 rounded-lg px-3 py-2 pr-10 text-white placeholder-gray-500 focus:border-blue-500 focus:outline-none transition-colors" placeholder="Type to search accounts..." autocomplete="off">
                                    <svg class="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                    <div class="acct-dropdown absolute z-[9999] w-full bg-gray-800 border-2 border-gray-600 rounded-lg mt-1 shadow-2xl hidden max-h-60 overflow-y-auto" style="position:absolute; left:0;">
                                        <div class="sticky top-0 bg-gray-700 px-3 py-2 text-xs text-gray-300 font-semibold border-b border-gray-600">Select an account</div>
                                        @foreach($glAccounts as $acct)
                                        <div class="acct-option px-4 py-2 hover:bg-blue-600 hover:text-white cursor-pointer text-white border-b border-gray-700 last:border-b-0 transition-colors"
                                            data-code="{{ $acct['code'] }}"
                                            data-name="{{ $acct['name'] }}"
                                            data-search="{{ strtolower($acct['code'] . ' ' . $acct['name']) }}">
                                            <div class="font-semibold text-sm font-mono">{{ $acct['code'] }}</div>
                                            <div class="text-xs text-gray-500">{{ $acct['name'] }}</div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                <input type="hidden" name="lines[0][account_code]" class="acct-code-hidden" required>
                            </td>
                            <td class="border border-gray-700 px-1 py-1">
                                <input type="text" name="lines[0][account_name]" class="acct-name w-full border-0 px-2 py-1 text-sm bg-gray-900 rounded" readonly>
                            </td>
                            <td class="border border-gray-700 px-1 py-1">
                                <input type="text" name="lines[0][line_description]" class="w-full border-0 px-2 py-1 text-sm bg-transparent focus:ring-1 focus:ring-blue-500 rounded" placeholder="Line description">
                            </td>
                            <td class="border border-gray-700 px-1 py-1">
                                <input type="text" name="lines[0][cost_center]" class="w-full border-0 px-2 py-1 text-sm bg-transparent focus:ring-1 focus:ring-blue-500 rounded" placeholder="Cost center">
                            </td>
                            <td class="border border-gray-700 px-1 py-1">
                                <input type="number" name="lines[0][debit]" step="0.01" min="0" class="w-full border-0 px-2 py-1 text-sm bg-transparent text-right focus:ring-1 focus:ring-blue-500 rounded debit-input" placeholder="0.00" oninput="recalcTotals()">
                            </td>
                            <td class="border border-gray-700 px-1 py-1">
                                <input type="number" name="lines[0][credit]" step="0.01" min="0" class="w-full border-0 px-2 py-1 text-sm bg-transparent text-right focus:ring-1 focus:ring-blue-500 rounded credit-input" placeholder="0.00" oninput="recalcTotals()">
                            </td>
                            <td class="border border-gray-700 px-1 py-1 text-center">
                                <button type="button" onclick="removeLine(this)" class="text-red-500 hover:text-red-700"><i class="fas fa-trash-alt"></i></button>
                            </td>
                        </tr>
                        <tr class="jv-line">
                            <td class="border border-gray-700 px-2 py-2" style="position:relative; overflow:visible;">
                                <div class="relative acct-search-container" style="position:relative;">
                                    <input type="text" class="acct-search w-full bg-gray-800 border-2 border-gray-600 rounded-lg px-3 py-2 pr-10 text-white placeholder-gray-500 focus:border-blue-500 focus:outline-none transition-colors" placeholder="Type to search accounts..." autocomplete="off">
                                    <svg class="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                    <div class="acct-dropdown absolute z-[9999] w-full bg-gray-800 border-2 border-gray-600 rounded-lg mt-1 shadow-2xl hidden max-h-60 overflow-y-auto" style="position:absolute; left:0;">
                                        <div class="sticky top-0 bg-gray-700 px-3 py-2 text-xs text-gray-300 font-semibold border-b border-gray-600">Select an account</div>
                                        @foreach($glAccounts as $acct)
                                        <div class="acct-option px-4 py-2 hover:bg-blue-600 hover:text-white cursor-pointer text-white border-b border-gray-700 last:border-b-0 transition-colors"
                                            data-code="{{ $acct['code'] }}"
                                            data-name="{{ $acct['name'] }}"
                                            data-search="{{ strtolower($acct['code'] . ' ' . $acct['name']) }}">
                                            <div class="font-semibold text-sm font-mono">{{ $acct['code'] }}</div>
                                            <div class="text-xs text-gray-500">{{ $acct['name'] }}</div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                <input type="hidden" name="lines[1][account_code]" class="acct-code-hidden" required>
                            </td>
                            <td class="border border-gray-700 px-1 py-1">
                                <input type="text" name="lines[1][account_name]" class="acct-name w-full border-0 px-2 py-1 text-sm bg-gray-900 rounded" readonly>
                            </td>
                            <td class="border border-gray-700 px-1 py-1">
                                <input type="text" name="lines[1][line_description]" class="w-full border-0 px-2 py-1 text-sm bg-transparent focus:ring-1 focus:ring-blue-500 rounded" placeholder="Line description">
                            </td>
                            <td class="border border-gray-700 px-1 py-1">
                                <input type="text" name="lines[1][cost_center]" class="w-full border-0 px-2 py-1 text-sm bg-transparent focus:ring-1 focus:ring-blue-500 rounded" placeholder="Cost center">
                            </td>
                            <td class="border border-gray-700 px-1 py-1">
                                <input type="number" name="lines[1][debit]" step="0.01" min="0" class="w-full border-0 px-2 py-1 text-sm bg-transparent text-right focus:ring-1 focus:ring-blue-500 rounded debit-input" placeholder="0.00" oninput="recalcTotals()">
                            </td>
                            <td class="border border-gray-700 px-1 py-1">
                                <input type="number" name="lines[1][credit]" step="0.01" min="0" class="w-full border-0 px-2 py-1 text-sm bg-transparent text-right focus:ring-1 focus:ring-blue-500 rounded credit-input" placeholder="0.00" oninput="recalcTotals()">
                            </td>
                            <td class="border border-gray-700 px-1 py-1 text-center">
                                <button type="button" onclick="removeLine(this)" class="text-red-500 hover:text-red-700"><i class="fas fa-trash-alt"></i></button>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-700 font-bold">
                            <td colspan="4" class="border border-gray-700 px-3 py-2 text-right">TOTALS:</td>
                            <td class="border border-gray-700 px-3 py-2 text-right" id="totalDebit">0.00</td>
                            <td class="border border-gray-700 px-3 py-2 text-right" id="totalCredit">0.00</td>
                            <td class="border border-gray-700"></td>
                        </tr>
                        <tr id="differenceRow" class="hidden">
                            <td colspan="4" class="border border-gray-700 px-3 py-2 text-right text-red-700 font-semibold">DIFFERENCE:</td>
                            <td colspan="2" class="border border-gray-700 px-3 py-2 text-right text-red-700 font-bold" id="difference">0.00</td>
                            <td class="border border-gray-700"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Attachment -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-400 mb-1">Attachment (optional)</label>
                <input type="file" name="attachment" accept=".pdf,.png,.jpg,.jpeg" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-sm">
                <p class="text-xs text-gray-400 mt-1">PDF, PNG, JPG (max 5MB)</p>
            </div>

            <div class="flex justify-end gap-4">
                <a href="{{ route('journal_vouchers.index') }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-600 transition">Cancel</a>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
                    <i class="fas fa-save mr-1"></i> Save as Draft
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Hidden template for dropdown options used by addLine() --}}
<template id="acctOptionsTemplate">
    <div class="sticky top-0 bg-gray-700 px-3 py-2 text-xs text-gray-300 font-semibold border-b border-gray-600">Select an account</div>
    @foreach($glAccounts as $acct)
    <div class="acct-option px-4 py-2 hover:bg-blue-600 hover:text-white cursor-pointer text-white border-b border-gray-700 last:border-b-0 transition-colors"
        data-code="{{ $acct['code'] }}"
        data-name="{{ $acct['name'] }}"
        data-search="{{ strtolower($acct['code'] . ' ' . $acct['name']) }}">
        <div class="font-semibold text-sm font-mono">{{ $acct['code'] }}</div>
        <div class="text-xs text-gray-500">{{ $acct['name'] }}</div>
    </div>
    @endforeach
</template>

<script>
let lineIndex = 2;

// ================= SEARCHABLE ACCOUNT DROPDOWN =================
function initAccountSearch(row) {
    const searchInput = row.querySelector('.acct-search');
    const dropdown = row.querySelector('.acct-dropdown');
    const codeHidden = row.querySelector('.acct-code-hidden');
    const nameInput = row.querySelector('.acct-name');
    const originalDropdownHTML = dropdown.innerHTML;

    searchInput.addEventListener('focus', function() {
        dropdown.innerHTML = originalDropdownHTML;
        rebindOptionClicks();
        filterOptions(this.value.toLowerCase());
        dropdown.classList.remove('hidden');
    });

    searchInput.addEventListener('input', function() {
        dropdown.innerHTML = originalDropdownHTML;
        rebindOptionClicks();
        filterOptions(this.value.toLowerCase());
        dropdown.classList.remove('hidden');
    });

    function filterOptions(searchTerm) {
        if (searchTerm === '') {
            dropdown.querySelectorAll('.acct-option').forEach(opt => opt.style.display = 'block');
            return;
        }
        let visibleCount = 0;
        dropdown.querySelectorAll('.acct-option').forEach(option => {
            const searchText = option.getAttribute('data-search');
            if (searchText.includes(searchTerm)) {
                option.style.display = 'block';
                visibleCount++;
            } else {
                option.style.display = 'none';
            }
        });
        if (visibleCount === 0) {
            const noResults = document.createElement('div');
            noResults.className = 'px-4 py-6 text-center text-gray-400';
            noResults.innerHTML = '<div class="font-medium text-sm">No accounts found</div><div class="text-xs mt-1">Try a different search term</div>';
            dropdown.appendChild(noResults);
        }
    }

    function rebindOptionClicks() {
        dropdown.querySelectorAll('.acct-option').forEach(option => {
            option.addEventListener('click', handleOptionClick);
        });
    }

    function handleOptionClick() {
        const code = this.getAttribute('data-code');
        const name = this.getAttribute('data-name');
        searchInput.value = code + ' — ' + name;
        codeHidden.value = code;
        nameInput.value = name;
        dropdown.classList.add('hidden');
    }

    rebindOptionClicks();

    document.addEventListener('click', function(e) {
        if (!row.querySelector('.acct-search-container').contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
}

function addLine() {
    const tbody = document.getElementById('linesBody');
    const tr = document.createElement('tr');
    tr.className = 'jv-line';
    const optionsHTML = document.getElementById('acctOptionsTemplate').innerHTML;
    tr.innerHTML = `
        <td class="border border-gray-700 px-2 py-2" style="position:relative; overflow:visible;">
            <div class="relative acct-search-container" style="position:relative;">
                <input type="text" class="acct-search w-full bg-gray-800 border-2 border-gray-600 rounded-lg px-3 py-2 pr-10 text-white placeholder-gray-500 focus:border-blue-500 focus:outline-none transition-colors" placeholder="Type to search accounts..." autocomplete="off">
                <svg class="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <div class="acct-dropdown absolute z-[9999] w-full bg-gray-800 border-2 border-gray-600 rounded-lg mt-1 shadow-2xl hidden max-h-60 overflow-y-auto" style="position:absolute; left:0;">${optionsHTML}</div>
            </div>
            <input type="hidden" name="lines[${lineIndex}][account_code]" class="acct-code-hidden" required>
        </td>
        <td class="border border-gray-700 px-1 py-1">
            <input type="text" name="lines[${lineIndex}][account_name]" class="acct-name w-full border-0 px-2 py-1 text-sm bg-gray-900 rounded" readonly>
        </td>
        <td class="border border-gray-700 px-1 py-1">
            <input type="text" name="lines[${lineIndex}][line_description]" class="w-full border-0 px-2 py-1 text-sm bg-transparent focus:ring-1 focus:ring-blue-500 rounded" placeholder="Line description">
        </td>
        <td class="border border-gray-700 px-1 py-1">
            <input type="text" name="lines[${lineIndex}][cost_center]" class="w-full border-0 px-2 py-1 text-sm bg-transparent focus:ring-1 focus:ring-blue-500 rounded" placeholder="Cost center">
        </td>
        <td class="border border-gray-700 px-1 py-1">
            <input type="number" name="lines[${lineIndex}][debit]" step="0.01" min="0" class="w-full border-0 px-2 py-1 text-sm bg-transparent text-right focus:ring-1 focus:ring-blue-500 rounded debit-input" placeholder="0.00" oninput="recalcTotals()">
        </td>
        <td class="border border-gray-700 px-1 py-1">
            <input type="number" name="lines[${lineIndex}][credit]" step="0.01" min="0" class="w-full border-0 px-2 py-1 text-sm bg-transparent text-right focus:ring-1 focus:ring-blue-500 rounded credit-input" placeholder="0.00" oninput="recalcTotals()">
        </td>
        <td class="border border-gray-700 px-1 py-1 text-center">
            <button type="button" onclick="removeLine(this)" class="text-red-500 hover:text-red-700"><i class="fas fa-trash-alt"></i></button>
        </td>
    `;
    tbody.appendChild(tr);
    initAccountSearch(tr);
    lineIndex++;
}

function removeLine(btn) {
    if (document.querySelectorAll('.jv-line').length <= 2) { alert('Minimum 2 lines required.'); return; }
    btn.closest('tr').remove();
    recalcTotals();
}

function recalcTotals() {
    let totalDebit = 0, totalCredit = 0;
    document.querySelectorAll('.debit-input').forEach(el => totalDebit += parseFloat(el.value || 0));
    document.querySelectorAll('.credit-input').forEach(el => totalCredit += parseFloat(el.value || 0));
    document.getElementById('totalDebit').textContent = totalDebit.toLocaleString('en', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('totalCredit').textContent = totalCredit.toLocaleString('en', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    const diff = Math.abs(totalDebit - totalCredit);
    if (diff > 0.01) {
        document.getElementById('differenceRow').classList.remove('hidden');
        document.getElementById('difference').textContent = diff.toLocaleString('en', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    } else {
        document.getElementById('differenceRow').classList.add('hidden');
    }
}

// Init all existing rows
document.querySelectorAll('.jv-line').forEach(row => initAccountSearch(row));
</script>
@endsection
