@extends('layouts.app')

@section('title', 'AR Adjustments')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-gray-800 rounded-lg shadow-lg p-6">
        
        {{-- Header --}}
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-3xl font-bold text-white flex items-center gap-3">
                    <i class="fas fa-edit text-purple-400"></i>
                    AR Adjustments
                </h2>
                <p class="text-gray-300 text-sm mt-1">Manage accounts receivable adjustments and transactions</p>
            </div>
            <button type="button" id="add_adjustment_btn" 
                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded font-medium transition flex items-center gap-2">
                <i class="fas fa-plus"></i>
                New Adjustment
            </button>
        </div>

        {{-- Filters Section --}}
        <div class="bg-gray-700 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-white mb-4">Filters</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="filter_start_date" class="block text-sm font-medium text-gray-300 mb-2">
                        Start Date
                    </label>
                    <input type="date" id="filter_start_date" 
                           class="w-full bg-gray-600 text-white border border-gray-500 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="filter_end_date" class="block text-sm font-medium text-gray-300 mb-2">
                        End Date
                    </label>
                    <input type="date" id="filter_end_date" 
                           class="w-full bg-gray-600 text-white border border-gray-500 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="filter_transaction_type" class="block text-sm font-medium text-gray-300 mb-2">
                        Transaction Type
                    </label>
                    <select id="filter_transaction_type" 
                            class="w-full bg-gray-600 text-white border border-gray-500 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Types</option>
                        <option value="credit_memo">Credit Memo</option>
                        <option value="debit_memo">Debit Memo</option>
                        <option value="adjustment">Adjustment</option>
                        <option value="write_off">Write-off</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="button" id="apply_filters_btn" 
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-medium transition">
                        <i class="fas fa-filter mr-2"></i>Apply Filters
                    </button>
                </div>
            </div>
        </div>

        {{-- Search Bar --}}
        <div class="bg-gray-700 rounded-lg p-4 mb-6">
            <div class="flex items-center space-x-4">
                <div class="flex-1">
                    <input type="text" id="search_input" 
                           placeholder="Search by reference number, client name, invoice number..." 
                           class="w-full bg-gray-600 text-white border border-gray-500 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="button" id="search_btn" 
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded font-medium transition flex items-center gap-2">
                    <i class="fas fa-search"></i>
                    Search
                </button>
                <button type="button" id="export_btn" 
                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2 rounded font-medium transition flex items-center gap-2">
                    <i class="fas fa-file-excel"></i>
                    Export
                </button>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-gradient-to-br from-blue-900/40 to-blue-800/30 border border-blue-700/50 rounded-lg p-4">
                <p class="text-gray-300 text-xs mb-1">Total Adjustments</p>
                <p class="text-white text-2xl font-bold" id="total_count">0</p>
            </div>
            <div class="bg-gradient-to-br from-green-900/40 to-green-800/30 border border-green-700/50 rounded-lg p-4">
                <p class="text-gray-300 text-xs mb-1">Credit Memos</p>
                <p class="text-white text-2xl font-bold" id="credit_total">₱0.00</p>
            </div>
            <div class="bg-gradient-to-br from-red-900/40 to-red-800/30 border border-red-700/50 rounded-lg p-4">
                <p class="text-gray-300 text-xs mb-1">Debit Memos</p>
                <p class="text-white text-2xl font-bold" id="debit_total">₱0.00</p>
            </div>
            <div class="bg-gradient-to-br from-purple-900/40 to-purple-800/30 border border-purple-700/50 rounded-lg p-4">
                <p class="text-gray-300 text-xs mb-1">Net Adjustment</p>
                <p class="text-white text-2xl font-bold" id="net_total">₱0.00</p>
            </div>
        </div>

        {{-- AR Adjustments Table --}}
        <div class="bg-gray-700 rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                    <i class="fas fa-table text-blue-400"></i>
                    AR Adjustment Records
                </h3>
                <span class="text-gray-300 text-sm">
                    <i class="fas fa-info-circle mr-1"></i>
                    Showing <strong id="record_count">0</strong> record(s)
                </span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full bg-gray-800 rounded-lg text-sm">
                    <thead>
                        <tr class="bg-gray-900 text-gray-200">
                            <th class="px-3 py-3 text-left">Date</th>
                            <th class="px-3 py-3 text-left">Reference No.</th>
                            <th class="px-3 py-3 text-left">Transaction Type</th>
                            <th class="px-3 py-3 text-left">DR No.</th>
                            <th class="px-3 py-3 text-left">Invoice No.</th>
                            <th class="px-3 py-3 text-left">Customer Code</th>
                            <th class="px-3 py-3 text-left">Customer Name</th>
                            <th class="px-3 py-3 text-left">Branch</th>
                            <th class="px-3 py-3 text-right">Amount</th>
                            <th class="px-3 py-3 text-left">GL Account</th>
                            <th class="px-3 py-3 text-left">Remarks</th>
                            <th class="px-3 py-3 text-left">Signed By</th>
                            <th class="px-3 py-3 text-left">Date & Time</th>
                            <th class="px-3 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="adjustments_tbody" class="text-gray-200">
s                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal for New/Edit Adjustment --}}
<div id="adjustment_modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold text-white" id="modal_title">New AR Adjustment</h3>
            <button id="close_modal_btn" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>

        <form id="adjustment_form" class="space-y-4">
            <input type="hidden" id="adjustment_id" name="adjustment_id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Transaction Date *</label>
                    <input type="date" name="transaction_date" id="transaction_date" required
                           class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Reference Number *</label>
                    <input type="text" name="reference_number" id="reference_number" required
                           placeholder="e.g., ADJ-2025-001"
                           class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Transaction Type *</label>
                    <select name="transaction_type" id="transaction_type" required
                            class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Type</option>
                        <option value="credit_memo">Credit Memo (Decrease AR)</option>
                        <option value="debit_memo">Debit Memo (Increase AR)</option>
                        <option value="adjustment">Adjustment</option>
                        <option value="write_off">Write-off</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">DR No.</label>
                    <input type="text" name="dr_no" id="dr_no"
                           placeholder="Optional"
                           class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Invoice Number</label>
                    <input type="text" name="invoice_number" id="invoice_number"
                           placeholder="e.g., INV-2025-0001"
                           class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Customer Code</label>
                    <input type="text" name="customer_code" id="customer_code"
                           placeholder="e.g., CUST-001"
                           class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Customer Name *</label>
                    <input type="text" name="customer_name" id="customer_name" required
                           placeholder="e.g., ABC Corporation"
                           class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Branch</label>
                    <input type="text" name="branch" id="branch"
                           placeholder="e.g., Manila Branch"
                           class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Amount *</label>
                    <input type="text" name="amount" id="amount" required
                           placeholder="For credit memo: (1000) or -1000"
                           class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-400 mt-1">Use parentheses (1000) or minus -1000 for credits</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">GL Account *</label>
                    <input type="text" name="gl_account" id="gl_account" required
                           placeholder="e.g., 1200-AR"
                           class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Signed By *</label>
                    <input type="text" name="signed_by" id="signed_by" required
                           placeholder="e.g., John Doe"
                           class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Remarks</label>
                    <textarea name="remarks" id="remarks" rows="3"
                              placeholder="Optional remarks or notes"
                              class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-4 mt-6">
                <button type="button" id="cancel_modal_btn"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded font-medium transition">
                    Cancel
                </button>
                <button type="submit"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded font-medium transition">
                    <i class="fas fa-save mr-2"></i>Save Adjustment
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// ✅ Load adjustments on page load
document.addEventListener('DOMContentLoaded', function() {
    loadAdjustments();
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('transaction_date').value = today;
});

function loadAdjustments(filters = {}) {
    const params = new URLSearchParams({
        start_date: filters.start_date || '',
        end_date: filters.end_date || '',
        transaction_type: filters.transaction_type || '',
        search: filters.search || ''
    });

    fetch(`{{ route('ar_adjustments.get') }}?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderAdjustments(data.adjustments || []);
                updateSummary(data.summary || {
                    total_count: 0,
                    credit_total: 0,
                    debit_total: 0,
                    net_total: 0
                });
            } else {
                console.error('Error:', data.message);
                showError('Failed to load adjustments: ' + data.message);
                // Show empty state
                renderAdjustments([]);
                updateSummary({
                    total_count: 0,
                    credit_total: 0,
                    debit_total: 0,
                    net_total: 0
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Failed to load adjustments. Please check your connection.');
            // Show empty state
            renderAdjustments([]);
            updateSummary({
                total_count: 0,
                credit_total: 0,
                debit_total: 0,
                net_total: 0
            });
        });
}

// ✅ Render adjustments table
function renderAdjustments(adjustments) {
    const tbody = document.getElementById('adjustments_tbody');
    const recordCount = document.getElementById('record_count');

    if (!tbody) {
        console.error('adjustments_tbody element not found');
        return;
    }

    // Clear existing content
    tbody.innerHTML = '';

    if (!adjustments || adjustments.length === 0) {
        // Show empty state
        tbody.innerHTML = `
            <tr>
                <td colspan="14" class="px-4 py-8 text-center text-gray-400">
                    <i class="fas fa-inbox text-4xl mb-2"></i>
                    <p>No AR adjustments found.</p>
                    <p class="text-sm mt-2">Click "New Adjustment" to create one.</p>
                </td>
            </tr>
        `;
        recordCount.textContent = '0';
        return;
    }

    recordCount.textContent = adjustments.length;

    adjustments.forEach(adj => {
        const typeColor = {
            'credit_memo': 'green',
            'debit_memo': 'red',
            'adjustment': 'yellow',
            'write_off': 'purple'
        }[adj.transaction_type] || 'gray';

        const amountColor = adj.is_decrease ? 'text-red-400' : 'text-green-400';
        const amountSign = adj.is_decrease ? '-' : '+';

        const row = document.createElement('tr');
        row.className = 'border-b border-gray-700 hover:bg-gray-750 transition';
        row.innerHTML = `
            <td class="px-3 py-3">${adj.transaction_date || 'N/A'}</td>
            <td class="px-3 py-3">
                <span class="bg-blue-900/30 border border-blue-700/50 px-2 py-1 rounded text-xs font-mono">
                    ${adj.reference_number || 'N/A'}
                </span>
            </td>
            <td class="px-3 py-3">
                <span class="bg-${typeColor}-600 px-2 py-1 rounded text-xs font-medium">
                    ${adj.formatted_type || 'N/A'}
                </span>
            </td>
            <td class="px-3 py-3">${adj.dr_no || 'N/A'}</td>
            <td class="px-3 py-3">
                <span class="bg-gray-700 px-2 py-1 rounded text-xs">
                    ${adj.invoice_number || 'N/A'}
                </span>
            </td>
            <td class="px-3 py-3">${adj.customer_code || 'N/A'}</td>
            <td class="px-3 py-3">${adj.customer_name || 'N/A'}</td>
            <td class="px-3 py-3">${adj.branch || 'N/A'}</td>
            <td class="px-3 py-3 text-right font-semibold ${amountColor}">
                ${amountSign}₱${(adj.absolute_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
            </td>
            <td class="px-3 py-3">
                <span class="text-xs bg-purple-900/30 border border-purple-700/50 px-2 py-1 rounded">
                    ${adj.gl_account || 'N/A'}
                </span>
            </td>
            <td class="px-3 py-3 text-xs">${adj.remarks || '-'}</td>
            <td class="px-3 py-3">${adj.signed_by || 'N/A'}</td>
            <td class="px-3 py-3 text-xs">
                <div>${adj.created_at ? adj.created_at.split(' ')[0] : 'N/A'}</div>
                <div class="text-gray-400">${adj.created_at ? adj.created_at.split(' ')[1] : ''}</div>
            </td>
            <td class="px-3 py-3 text-center">
                <div class="flex items-center justify-center gap-2">
                    <button onclick="viewAdjustment(${adj.id})" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-xs transition" title="View">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button onclick="deleteAdjustment(${adj.id})" class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-xs transition" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// ✅ Update summary cards
function updateSummary(summary) {
    document.getElementById('total_count').textContent = summary.total_count || 0;
    document.getElementById('credit_total').textContent = '₱' + (summary.credit_total || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('debit_total').textContent = '₱' + (summary.debit_total || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    
    const netTotal = summary.net_total || 0;
    const netElement = document.getElementById('net_total');
    netElement.textContent = (netTotal >= 0 ? '+' : '') + '₱' + Math.abs(netTotal).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

// Modal controls
const modal = document.getElementById('adjustment_modal');
const addBtn = document.getElementById('add_adjustment_btn');
const closeBtn = document.getElementById('close_modal_btn');
const cancelBtn = document.getElementById('cancel_modal_btn');

addBtn.addEventListener('click', () => {
    document.getElementById('adjustment_form').reset();
    document.getElementById('adjustment_id').value = '';
    document.getElementById('modal_title').textContent = 'New AR Adjustment';
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('transaction_date').value = today;
    modal.classList.remove('hidden');
});

closeBtn.addEventListener('click', () => {
    modal.classList.add('hidden');
});

cancelBtn.addEventListener('click', () => {
    modal.classList.add('hidden');
});

// ✅ Apply filters
document.getElementById('apply_filters_btn').addEventListener('click', function() {
    const filters = {
        start_date: document.getElementById('filter_start_date').value,
        end_date: document.getElementById('filter_end_date').value,
        transaction_type: document.getElementById('filter_transaction_type').value
    };
    loadAdjustments(filters);
});

// ✅ Search functionality
document.getElementById('search_btn').addEventListener('click', function() {
    const searchValue = document.getElementById('search_input').value.trim();
    loadAdjustments({ search: searchValue });
});

// Allow Enter key in search
document.getElementById('search_input').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('search_btn').click();
    }
});

// ✅ Export functionality
document.getElementById('export_btn').addEventListener('click', function() {
    const filters = {
        start_date: document.getElementById('filter_start_date').value,
        end_date: document.getElementById('filter_end_date').value,
        transaction_type: document.getElementById('filter_transaction_type').value,
        search: document.getElementById('search_input').value
    };

    const params = new URLSearchParams(filters);
    window.location.href = `{{ route('ar_adjustments.export') }}?${params}`;
    
    showSuccess('Your file will be downloaded shortly');
});

// ✅ Form submission
document.getElementById('adjustment_form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {};
    
    // Convert FormData to object
    formData.forEach((value, key) => {
        data[key] = value;
    });

    Swal.fire({
        title: 'Saving...',
        text: 'Creating AR adjustment',
        background: '#1f2937',
        color: '#fff',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch('{{ route("ar_adjustments.store") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        Swal.close();
        
        if (data.success) {
            modal.classList.add('hidden');
            showSuccess(data.message);
            loadAdjustments();
        } else {
            showError(data.message || 'Failed to save adjustment');
        }
    })
    .catch(error => {
        Swal.close();
        console.error('Error:', error);
        showError('Failed to save adjustment');
    });
});

// ✅ View Adjustment Details
function viewAdjustment(id) {
    Swal.fire({
        title: 'Loading...',
        text: 'Fetching adjustment details',
        background: '#1f2937',
        color: '#fff',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch(`/ar-adjustments/${id}`)
        .then(response => response.json())
        .then(data => {
            Swal.close();
            
            if (data.success) {
                const adj = data.adjustment;
                const amountColor = adj.is_decrease ? 'text-red-400' : 'text-green-400';
                const amountSign = adj.is_decrease ? '-' : '+';
                
                Swal.fire({
                    title: '<strong>AR Adjustment Details</strong>',
                    html: `
                        <div class="text-left space-y-3" style="color: #fff;">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-gray-400 text-xs">Reference Number</p>
                                    <p class="font-semibold">${adj.reference_number}</p>
                                </div>
                                <div>
                                    <p class="text-gray-400 text-xs">Date</p>
                                    <p class="font-semibold">${adj.transaction_date}</p>
                                </div>
                                <div>
                                    <p class="text-gray-400 text-xs">Transaction Type</p>
                                    <p class="font-semibold">${adj.formatted_type}</p>
                                </div>
                                <div>
                                    <p class="text-gray-400 text-xs">Amount</p>
                                    <p class="font-semibold ${amountColor}">${amountSign}₱${adj.absolute_amount.toLocaleString('en-US', {minimumFractionDigits: 2})}</p>
                                </div>
                                <div>
                                    <p class="text-gray-400 text-xs">DR No.</p>
                                    <p class="font-semibold">${adj.dr_no || 'N/A'}</p>
                                </div>
                                <div>
                                    <p class="text-gray-400 text-xs">Invoice No.</p>
                                    <p class="font-semibold">${adj.invoice_number || 'N/A'}</p>
                                </div>
                                <div>
                                    <p class="text-gray-400 text-xs">Customer Code</p>
                                    <p class="font-semibold">${adj.customer_code || 'N/A'}</p>
                                </div>
                                <div>
                                    <p class="text-gray-400 text-xs">Customer Name</p>
                                    <p class="font-semibold">${adj.customer_name}</p>
                                </div>
                                <div>
                                    <p class="text-gray-400 text-xs">Branch</p>
                                    <p class="font-semibold">${adj.branch || 'N/A'}</p>
                                </div>
                                <div>
                                    <p class="text-gray-400 text-xs">GL Account</p>
                                    <p class="font-semibold">${adj.gl_account}</p>
                                </div>
                                <div>
                                    <p class="text-gray-400 text-xs">Signed By</p>
                                    <p class="font-semibold">${adj.signed_by}</p>
                                </div>
                                <div>
                                    <p class="text-gray-400 text-xs">Created By</p>
                                    <p class="font-semibold">${adj.created_by}</p>
                                </div>
                                <div class="col-span-2">
                                    <p class="text-gray-400 text-xs">Remarks</p>
                                    <p class="font-semibold">${adj.remarks || 'N/A'}</p>
                                </div>
                                <div class="col-span-2">
                                    <p class="text-gray-400 text-xs">Created At</p>
                                    <p class="font-semibold">${adj.created_at}</p>
                                </div>
                            </div>
                        </div>
                    `,
                    width: 700,
                    background: '#1f2937',
                    color: '#fff',
                    confirmButtonText: 'Close',
                    confirmButtonColor: '#3b82f6'
                });
            }
        })
        .catch(error => {
            Swal.close();
            showError('Failed to load adjustment details');
        });
}

// ✅ Delete Adjustment
function deleteAdjustment(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'This will reverse the adjustment in AR records!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        background: '#1f2937',
        color: '#fff'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/ar-adjustments/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess(data.message);
                    loadAdjustments();
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                showError('Failed to delete adjustment');
            });
        }
    });
}

// ✅ Helper functions
function showSuccess(message) {
    Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: message,
        background: '#1f2937',
        color: '#fff',
        timer: 2000,
        showConfirmButton: false
    });
}

function showError(message) {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: message,
        background: '#1f2937',
        color: '#fff'
    });
}
</script>
@endsection