@extends('layouts.app')

@section('title', 'AR Management System')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-lg p-6">
        
        {{-- Header --}}
        <div class="flex justify-between items-center mb-6 border-b border-gray-200 pb-4">
            <div>
                <h2 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                    <i class="fas fa-chart-line text-blue-400"></i>
                    AR Management System
                </h2>
                <p class="text-gray-500 text-sm mt-1">Unified view of Aging Reports, Collections, and Adjustments</p>
            </div>
        </div>

        {{-- Filters Section --}}
        <div class="bg-gray-100 rounded-lg p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Filters</h3>
            </div>
            <form method="GET" action="{{ route('ar.unified') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">Record Date</label>
                    <input type="date" name="filter_date" value="{{ $filterDate }}"
                           class="w-full bg-gray-600 text-white border border-gray-500 rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">Include</label>
                    <select name="include" class="w-full bg-gray-600 text-white border border-gray-500 rounded px-3 py-2">
                        <option value="all" {{ $include === 'all' ? 'selected' : '' }}>All Records</option>
                        <option value="yes" {{ $include === 'yes' ? 'selected' : '' }}>Included Only</option>
                        <option value="no" {{ $include === 'no' ? 'selected' : '' }}>Excluded Only</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">View</label>
                    <select name="view" id="view_selector" class="w-full bg-gray-600 text-white border border-gray-500 rounded px-3 py-2">
                        <option value="list" {{ $view === 'list' ? 'selected' : '' }}>AR Aging List</option>
                        <option value="summary" {{ $view === 'summary' ? 'selected' : '' }}>Summary Report</option>
                        <option value="collections" {{ $view === 'collections' ? 'selected' : '' }}>Collections</option>
                        <option value="adjustments" {{ $view === 'adjustments' ? 'selected' : '' }}>Adjustments</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-medium">
                        <i class="fas fa-filter mr-2"></i>Apply Filters
                    </button>
                </div>
                <div class="flex items-end">
                    <button type="button" onclick="exportData()" class="w-full bg-emerald-600 hover:bg-emerald-700 text-gray-800 px-4 py-2 rounded font-medium">
                        <i class="fas fa-file-excel mr-2"></i>Export
                    </button>
                </div>
            </form>
        </div>

        {{-- Metrics Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-gradient-to-br from-blue-900/40 to-blue-800/30 border border-blue-700/50 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold text-blue-300">Total AR</h3>
                    <i class="fas fa-dollar-sign text-blue-400 text-xl"></i>
                </div>
                <p class="text-gray-800 text-2xl font-bold">₱{{ number_format($metrics['total_ar'], 2) }}</p>
                <p class="text-gray-500 text-xs mt-1">Outstanding balance</p>
            </div>

            <div class="bg-gradient-to-br from-green-900/40 to-green-800/30 border border-green-700/50 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold text-green-300">Total Collected</h3>
                    <i class="fas fa-check-circle text-green-400 text-xl"></i>
                </div>
                <p class="text-gray-800 text-2xl font-bold">₱{{ number_format($metrics['total_collected'], 2) }}</p>
                <p class="text-gray-500 text-xs mt-1">Payments received</p>
            </div>

            <div class="bg-gradient-to-br from-purple-900/40 to-purple-800/30 border border-purple-700/50 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold text-purple-300">Total Invoiced</h3>
                    <i class="fas fa-file-invoice text-purple-400 text-xl"></i>
                </div>
                <p class="text-gray-800 text-2xl font-bold">₱{{ number_format($metrics['total_invoiced'], 2) }}</p>
                <p class="text-gray-500 text-xs mt-1">All invoices</p>
            </div>

            <div class="bg-gradient-to-br from-orange-900/40 to-orange-800/30 border border-orange-700/50 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold text-orange-300">Outstanding</h3>
                    <i class="fas fa-hourglass-half text-orange-400 text-xl"></i>
                </div>
                <p class="text-gray-800 text-2xl font-bold">{{ $metrics['outstanding_count'] }}</p>
                <p class="text-gray-500 text-xs mt-1">Unpaid invoices</p>
            </div>
        </div>

        {{-- Quick Action Buttons --}}
        <div class="flex justify-end gap-3 mb-6">
            <button type="button" onclick="openCollectionModal()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded font-medium">
                <i class="fas fa-money-bill-wave mr-2"></i>Record Collection
            </button>
            <button type="button" onclick="openAdjustmentModal()" class="bg-purple-600 hover:bg-purple-700 text-gray-800 px-6 py-2 rounded font-medium">
                <i class="fas fa-edit mr-2"></i>Record Adjustment
            </button>
        </div>

        {{-- Content Based on View --}}
        @if($view === 'list')
            @include('ar.partials.aging_list', ['records' => $agingRecords])
        @elseif($view === 'summary')
            @include('ar.partials.aging_summary', ['records' => $agingRecords])
        @elseif($view === 'collections')
            @include('ar.partials.collections_list', ['collections' => $viewData])
        @elseif($view === 'adjustments')
            @include('ar.partials.adjustments_list', ['adjustments' => $viewData])
        @endif
    </div>
</div>

{{-- Collection Modal --}}
<div id="collection_modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold text-gray-800">Record Collection</h3>
            <button onclick="closeCollectionModal()" class="text-gray-400 hover:text-gray-800">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>

        <form id="collection_form" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-500 mb-2">Customer Code *</label>
                    <input type="text" name="customer_code" id="customer_code_input" required
                           class="w-full bg-gray-100 text-gray-800 border border-gray-300 rounded px-3 py-2">
                    <button type="button" onclick="searchCustomer()" class="mt-2 text-blue-400 text-sm hover:underline">
                        <i class="fas fa-search mr-1"></i>Search Customer
                    </button>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">Collection Receipt No. *</label>
                    <input type="text" name="collection_receipt_number" required
                           class="w-full bg-gray-100 text-gray-800 border border-gray-300 rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">Receipt Date *</label>
                    <input type="date" name="collection_receipt_date" value="{{ now()->format('Y-m-d') }}" required
                           class="w-full bg-gray-100 text-gray-800 border border-gray-300 rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">Posting Date *</label>
                    <input type="date" name="payment_posting_date" value="{{ now()->format('Y-m-d') }}" required
                           class="w-full bg-gray-100 text-gray-800 border border-gray-300 rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">Payment Option *</label>
                    <select name="payment_option" required
                            class="w-full bg-gray-100 text-gray-800 border border-gray-300 rounded px-3 py-2">
                        <option value="Full Payment">Full Payment</option>
                        <option value="Partial Payment">Partial Payment</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">Check Amount (Gross) *</label>
                    <input type="number" name="check_amount" step="0.01" min="0" required
                           oninput="calculateNetAmount()"
                           class="w-full bg-gray-100 text-gray-800 border border-gray-300 rounded px-3 py-2">
                    <p class="text-xs text-gray-400 mt-1">Invoice/Gross amount</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">EWT (Withholding Tax)</label>
                    <input type="number" name="ewt" step="0.01" min="0" value="0"
                           oninput="calculateNetAmount()"
                           class="w-full bg-gray-100 text-gray-800 border border-gray-300 rounded px-3 py-2">
                    <p class="text-xs text-gray-400 mt-1">Tax withheld by customer</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">Net Amount (After Tax)</label>
                    <input type="text" id="net_amount_display" readonly
                           class="w-full bg-gray-600 text-white border border-gray-500 rounded px-3 py-2 font-bold text-green-400">
                    <p class="text-xs text-gray-400 mt-1">Auto-calculated: Check Amount - EWT</p>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-500 mb-2">Notes</label>
                    <textarea name="payment_notes" rows="3"
                              class="w-full bg-gray-100 text-gray-800 border border-gray-300 rounded px-3 py-2"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-4 mt-6">
                <button type="button" onclick="closeCollectionModal()"
                        class="bg-gray-600 hover:bg-gray-100 text-gray-800 px-6 py-2 rounded font-medium">
                    Cancel
                </button>
                <button type="submit"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded font-medium">
                    <i class="fas fa-save mr-2"></i>Save Collection
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Adjustment Modal --}}
<div id="adjustment_modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold text-gray-800">Record AR Adjustment</h3>
            <button onclick="closeAdjustmentModal()" class="text-gray-400 hover:text-gray-800">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>

        <div class="bg-blue-900/30 border border-blue-700/50 rounded-lg p-4 mb-6">
            <p class="text-sm text-blue-300">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Formatting Guide:</strong> Enter amount with <code class="bg-gray-100 px-2 py-1 rounded">()</code> to decrease AR (e.g., <code class="bg-gray-100 px-2 py-1 rounded">(1000)</code>), 
                or without to increase AR (e.g., <code class="bg-gray-100 px-2 py-1 rounded">1000</code>)
            </p>
        </div>

        <form id="adjustment_form" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">Customer Code *</label>
                    <input type="text" name="customer_code" required
                           class="w-full bg-gray-100 text-gray-800 border border-gray-300 rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">Reference Number *</label>
                    <input type="text" name="reference_number" required
                           class="w-full bg-gray-100 text-gray-800 border border-gray-300 rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">Transaction Type *</label>
                    <select name="transaction_type" required
                            class="w-full bg-gray-100 text-gray-800 border border-gray-300 rounded px-3 py-2">
                        <option value="">Select Type</option>
                        <option value="Credit Memo">Credit Memo</option>
                        <option value="Debit Memo">Debit Memo</option>
                        <option value="Adjustment">Adjustment</option>
                        <option value="Write-off">Write-off</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">Transaction Date *</label>
                    <input type="date" name="transaction_date" value="{{ now()->format('Y-m-d') }}" required
                           class="w-full bg-gray-100 text-gray-800 border border-gray-300 rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">Amount *</label>
                    <input type="text" name="amount" placeholder="1000 or (1000)" required
                           class="w-full bg-gray-100 text-gray-800 border border-gray-300 rounded px-3 py-2">
                    <p class="text-xs text-gray-400 mt-1">Use () to decrease AR</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">Invoice Number</label>
                    <input type="text" name="invoice_number"
                           class="w-full bg-gray-100 text-gray-800 border border-gray-300 rounded px-3 py-2">
                    <p class="text-xs text-gray-400 mt-1">Optional - leave blank to apply to oldest</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">DR No.</label>
                    <input type="text" name="dr_no"
                           class="w-full bg-gray-100 text-gray-800 border border-gray-300 rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">GL Account *</label>
                    <div class="relative">
                        <input type="hidden" name="gl_account_id" id="arMgmtGlAccountId">
                        <input type="hidden" name="gl_account" id="arMgmtGlAccountCode">
                        <input type="text" id="arMgmtGlAccountSearch" placeholder="Search GL Account (code/name)"
                               class="w-full bg-gray-100 text-gray-800 border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500" required>
                        <div id="arMgmtGlAccountDropdown" class="absolute top-full left-0 right-0 bg-white border border-gray-300 rounded max-h-48 overflow-y-auto z-10 hidden mt-1"></div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">Signed By *</label>
                    <input type="text" name="signed_by" required
                           class="w-full bg-gray-100 text-gray-800 border border-gray-300 rounded px-3 py-2">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-500 mb-2">Remarks</label>
                    <textarea name="remarks" rows="3"
                              class="w-full bg-gray-100 text-gray-800 border border-gray-300 rounded px-3 py-2"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-4 mt-6">
                <button type="button" onclick="closeAdjustmentModal()"
                        class="bg-gray-600 hover:bg-gray-100 text-gray-800 px-6 py-2 rounded font-medium">
                    Cancel
                </button>
                <button type="submit"
                        class="bg-purple-600 hover:bg-purple-700 text-gray-800 px-6 py-2 rounded font-medium">
                    <i class="fas fa-save mr-2"></i>Save Adjustment
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal controls
function openCollectionModal() {
    document.getElementById('collection_modal').classList.remove('hidden');
}

function closeCollectionModal() {
    document.getElementById('collection_modal').classList.add('hidden');
    document.getElementById('collection_form').reset();
}

function openAdjustmentModal() {
    document.getElementById('adjustment_modal').classList.remove('hidden');
}

function closeAdjustmentModal() {
    document.getElementById('adjustment_modal').classList.add('hidden');
    document.getElementById('adjustment_form').reset();
}

// Calculate net amount
function calculateNetAmount() {
    const checkAmount = parseFloat(document.querySelector('[name="check_amount"]').value) || 0;
    const ewt = parseFloat(document.querySelector('[name="ewt"]').value) || 0;
    const netAmount = checkAmount - ewt;
    
    document.getElementById('net_amount_display').value = '₱' + netAmount.toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Collection form submission
document.getElementById('collection_form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    Swal.fire({
        title: 'Saving...',
        text: 'Recording collection',
        background: '#ffffff',
        color: '#1f2937',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });
    
    fetch('/ar/collection/store', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        Swal.close();
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message,
                background: '#ffffff',
                color: '#1f2937'
            }).then(() => {
                closeCollectionModal();
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message,
                background: '#ffffff',
                color: '#1f2937'
            });
        }
    })
    .catch(error => {
        Swal.close();
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to save collection',
            background: '#ffffff',
            color: '#1f2937'
        });
    });
});

// Adjustment form submission
document.getElementById('adjustment_form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    Swal.fire({
        title: 'Saving...',
        text: 'Recording adjustment',
        background: '#ffffff',
        color: '#1f2937',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });
    
    fetch('/ar/adjustment/store', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        Swal.close();
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message,
                background: '#ffffff',
                color: '#1f2937'
            }).then(() => {
                closeAdjustmentModal();
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message,
                background: '#ffffff',
                color: '#1f2937'
            });
        }
    })
    .catch(error => {
        Swal.close();
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to save adjustment',
            background: '#ffffff',
            color: '#1f2937'
        });
    });
});

// Export data
function exportData() {
    const params = new URLSearchParams(window.location.search);
    window.location.href = '/ar/export?' + params.toString();
}

// Search customer
function searchCustomer() {
    const customerCode = document.getElementById('customer_code_input').value;
    
    if (!customerCode) {
        Swal.fire({
            icon: 'warning',
            title: 'Empty Search',
            text: 'Please enter a customer code',
            background: '#ffffff',
            color: '#1f2937'
        });
        return;
    }
    
    // Implement customer search
    console.log('Searching for:', customerCode);
}

// GL Account search in AR Adjustment modal
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('arMgmtGlAccountSearch');
    const dropdown = document.getElementById('arMgmtGlAccountDropdown');
    const idInput = document.getElementById('arMgmtGlAccountId');
    const codeInput = document.getElementById('arMgmtGlAccountCode');
    let searchTimeout;

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length === 0) {
                dropdown.classList.add('hidden');
                return;
            }

            searchTimeout = setTimeout(async function() {
                try {
                    const response = await fetch(`/ar-adjustments/gl-accounts?search=${encodeURIComponent(query)}`);
                    const data = await response.json();

                    if (data.success && data.accounts.length > 0) {
                        dropdown.innerHTML = data.accounts.map(account => `
                            <div class="px-3 py-2 hover:bg-purple-700 cursor-pointer text-white" onclick="selectArMgmtGlAccount(${account.id}, '${account.display.replace(/'/g, "\\'")}', '${(account.code || '').replace(/'/g, "\\'")}')" >
                                <div class="font-semibold">${account.display}</div>
                                <div class="text-xs text-gray-400">${account.fs_line_item || 'No FS Item'}</div>
                            </div>
                        `).join('');
                        dropdown.classList.remove('hidden');
                    } else {
                        dropdown.innerHTML = '<div class="px-3 py-2 text-gray-400">No GL accounts found</div>';
                        dropdown.classList.remove('hidden');
                    }
                } catch (error) {
                    console.error('Error fetching GL accounts:', error);
                }
            }, 300);
        });

        // Hide dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target !== searchInput) {
                dropdown.classList.add('hidden');
            }
        });
    }
});

function selectArMgmtGlAccount(id, display, code) {
    document.getElementById('arMgmtGlAccountId').value = id;
    document.getElementById('arMgmtGlAccountCode').value = code;
    document.getElementById('arMgmtGlAccountSearch').value = display;
    document.getElementById('arMgmtGlAccountDropdown').classList.add('hidden');
}
</script>
@endsection