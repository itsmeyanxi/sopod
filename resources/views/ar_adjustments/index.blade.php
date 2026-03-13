@extends('layouts.app')

@section('title', 'AR Adjustments')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 rounded-lg shadow-lg p-6">

        {{-- Header --}}
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-white">AR Adjustments</h2>

            {{-- Action Buttons --}}
            <div class="flex items-center gap-3">
                <button type="button" id="import_modal_btn" class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-2 rounded font-medium transition flex items-center space-x-2">
                    <i class="fas fa-upload"></i>
                    <span>Import Bulk</span>
                </button>
                <a href="{{ route('ar_adjustments.create') }}" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded font-medium transition flex items-center space-x-2">
                    <i class="fas fa-plus"></i>
                    <span>New Adjustment</span>
                </a>
                <button type="button" id="toggle_adjustments_btn" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded font-medium transition flex items-center space-x-2">
                    <i class="fas fa-list"></i>
                    <span>Adjustments</span>
                </button>
                <button type="button" id="toggle_deliveries_btn" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded font-medium transition flex items-center space-x-2">
                    <i class="fas fa-truck"></i>
                    <span>Fulfilled Orders</span>
                </button>
            </div>
        </div>

        {{-- Quick Stats Dashboard --}}
        <div id="stats_dashboard" class="bg-gray-700 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                <i class="fas fa-chart-bar mr-2"></i> Quick Statistics
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-blue-900 rounded p-4">
                    <p class="text-gray-400 text-sm">Today's Adjustments</p>
                    <p class="text-2xl font-bold text-blue-300" id="stat_today_count">—</p>
                    <p class="text-gray-500 text-xs" id="stat_today_total">₱0.00</p>
                </div>
                <div class="bg-green-900 rounded p-4">
                    <p class="text-gray-400 text-sm">This Month</p>
                    <p class="text-2xl font-bold text-green-300" id="stat_month_count">—</p>
                    <p class="text-gray-500 text-xs" id="stat_month_total">₱0.00</p>
                </div>
                <div class="bg-purple-900 rounded p-4">
                    <p class="text-gray-400 text-sm">Last 30 Days</p>
                    <p class="text-2xl font-bold text-purple-300" id="stat_30day_count">—</p>
                    <p class="text-gray-500 text-xs" id="stat_30day_total">₱0.00</p>
                </div>
                <div class="bg-orange-900 rounded p-4">
                    <p class="text-gray-400 text-sm">Pending Approvals</p>
                    <p class="text-2xl font-bold text-orange-300" id="stat_pending_count">—</p>
                    <p class="text-gray-500 text-xs">Needs DR approval</p>
                </div>
            </div>
        </div>

        {{-- Search Section --}}
        <div class="bg-gray-700 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-white mb-4">Search Customer / DR Number</h3>
            <div class="flex items-center space-x-4">
                <div class="flex-1">
                    <input type="text" id="customer_search" placeholder="Enter Customer Name or DR Number"
                           class="w-full bg-gray-600 text-white border border-gray-500 rounded px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="button" id="search_customer_btn" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded font-medium transition flex items-center space-x-2">
                    <i class="fas fa-search"></i>
                    <span>Search</span>
                </button>
            </div>
        </div>

        {{-- Adjustment List View (shown initially) --}}
        <div id="adjustment_list_view" class="bg-gray-700 rounded-lg p-4">
            <div class="flex justify-between items-center mb-4">
                <h4 class="text-lg font-semibold text-white">Adjustment List</h4>
                <button type="button" onclick="exportAdjustmentList()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm flex items-center space-x-2">
                    <i class="fas fa-file-excel"></i>
                    <span>Export to Excel</span>
                </button>
            </div>

            {{-- Filter Section --}}
            <div class="bg-gray-800 rounded-lg p-4 mb-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Date From</label>
                        <input type="date" id="filter_start_date" class="w-full bg-gray-600 text-white border border-gray-500 rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Date To</label>
                        <input type="date" id="filter_end_date" class="w-full bg-gray-600 text-white border border-gray-500 rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Transaction Type</label>
                        <select id="filter_transaction_type" class="w-full bg-gray-600 text-white border border-gray-500 rounded px-3 py-2">
                            <option value="">All Types</option>
                            <option value="atd">ATD (Authority to Debit)</option>
                            <option value="offset">Offset</option>
                            <option value="credit_memo">Credit Memo</option>
                            <option value="debit_memo">Debit Memo</option>
                            <option value="adjustment">Adjustment</option>
                            <option value="write_off">Write-off</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="button" onclick="filterAdjustmentList()" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                            Apply Filter
                        </button>
                    </div>
                </div>
            </div>

            {{-- Adjustment List Table --}}
            <div class="overflow-x-auto">
                <table class="min-w-full bg-gray-800 rounded-lg text-sm">
                    <thead>
                        <tr class="bg-gray-900 text-gray-300 text-xs">
                            <th class="px-3 py-3 text-left">Date</th>
                            <th class="px-3 py-3 text-left">Ref No.</th>
                            <th class="px-3 py-3 text-left">Type</th>
                            <th class="px-3 py-3 text-left">Customer</th>
                            <th class="px-3 py-3 text-left">DR No.</th>
                            <th class="px-3 py-3 text-left">DR Status</th>
                            <th class="px-3 py-3 text-left">Invoice</th>
                            <th class="px-3 py-3 text-right">Amount</th>
                            <th class="px-3 py-3 text-left">GL Account</th>
                            <th class="px-3 py-3 text-left">Remarks</th>
                            <th class="px-3 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="adjustment_list_tbody" class="text-gray-300">
                        <tr>
                            <td colspan="15" class="px-4 py-8 text-center text-gray-400">
                                <i class="fas fa-file-invoice text-4xl mb-2"></i>
                                <p>No adjustment data available</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Adjustment Entries View (shown after search) --}}
        <div id="adjustment_entries_view" class="hidden">
            {{-- DR Selection --}}
            <div id="dr_selection_container" class="bg-gray-700 rounded-lg p-4 mb-4">
                <h4 class="text-sm font-semibold text-white mb-3 flex items-center">
                    <i class="fas fa-file-invoice mr-2"></i>
                    Select DR Number
                </h4>
                <div id="dr_numbers_list" class="space-y-2">
                    <!-- DR numbers will be populated here -->
                </div>
            </div>

            {{-- Customer & DR Information --}}
            <div id="customer_info_container" class="bg-gray-700 rounded-lg p-4 mb-4">
                <h4 class="text-sm font-semibold text-white mb-3 flex items-center">
                    <i class="fas fa-user mr-2"></i>
                    Customer & DR Information
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
                    <div>
                        <label class="block text-gray-400 text-xs mb-1">Customer Name</label>
                        <p class="text-white font-semibold" id="display_customer_name">—</p>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-xs mb-1">DR Number</label>
                        <p class="text-white font-semibold" id="display_dr_no">—</p>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-xs mb-1">Invoice Number</label>
                        <p class="text-white font-semibold" id="display_invoice_no">—</p>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-xs mb-1">Current AR Balance</label>
                        <p class="text-red-400 font-bold text-lg" id="display_ar_balance">₱0.00</p>
                    </div>
                </div>
            </div>

            {{-- Adjustment Entry Form --}}
            <div id="adjustment_table_container" class="bg-gray-700 rounded-lg p-4">
                <div class="flex justify-between items-center mb-3">
                    <h4 class="text-sm font-semibold text-white">New AR Adjustment</h4>
                    <button type="button" onclick="saveAdjustment()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm flex items-center space-x-1">
                        <i class="fas fa-save"></i>
                        <span>Save Adjustment</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Transaction Date *</label>
                        <input type="date" id="transaction_date" required
                               class="w-full bg-gray-600 text-white border border-gray-500 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Reference Number *</label>
                        <input type="text" id="reference_number" required
                               placeholder="e.g., ADJ-2026-001"
                               class="w-full bg-gray-600 text-white border border-gray-500 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Transaction Type *</label>
                        <select id="transaction_type" required
                                class="w-full bg-gray-600 text-white border border-gray-500 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Type</option>
                            <option value="credit_memo">Credit Memo (Decrease AR)</option>
                            <option value="debit_memo">Debit Memo (Increase AR)</option>
                            <option value="adjustment">Adjustment</option>
                            <option value="write_off">Write-off</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Amount *</label>
                        <input type="text" id="amount" required
                               placeholder="Enter amount (e.g., -100 to decrease AR by 100)"
                               class="w-full bg-gray-600 text-white border border-gray-500 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-400 mt-1">Use negative value (e.g., -100) to decrease AR</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">GL Account *</label>
                        <input type="text" id="gl_account" required
                               placeholder="e.g., 1200-AR"
                               class="w-full bg-gray-600 text-white border border-gray-500 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Signed By *</label>
                        <input type="text" id="signed_by" required
                               placeholder="e.g., John Doe"
                               class="w-full bg-gray-600 text-white border border-gray-500 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Remarks</label>
                        <textarea id="remarks" rows="3"
                                  placeholder="Optional remarks or notes"
                                  class="w-full bg-gray-600 text-white border border-gray-500 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- No Results Message --}}
        <div id="no_results_message" class="hidden bg-gray-700 rounded-lg p-8 text-center">
            <i class="fas fa-user-slash text-5xl text-gray-500 mb-4"></i>
            <h3 class="text-xl font-semibold text-white mb-2">No Results Found</h3>
            <p class="text-gray-400">The customer or DR number you searched for does not exist. Please check and try again.</p>
        </div>

        {{-- Delivery List View --}}
        <div id="delivery_list_view" class="hidden bg-gray-700 rounded-lg p-4">
            <div class="flex justify-between items-center mb-4">
                <h4 class="text-lg font-semibold text-white">Fulfilled Orders Awaiting AR Adjustment</h4>
                <button type="button" onclick="reloadDeliveryList()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm flex items-center space-x-2">
                    <i class="fas fa-sync-alt"></i>
                    <span>Refresh</span>
                </button>
            </div>

            {{-- ✅ NEW: Filter Section for Deliveries --}}
            <div class="bg-gray-800 rounded-lg p-4 mb-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Date From</label>
                        <input type="date" id="delivery_filter_start_date" class="w-full bg-gray-600 text-white border border-gray-500 rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Date To</label>
                        <input type="date" id="delivery_filter_end_date" class="w-full bg-gray-600 text-white border border-gray-500 rounded px-3 py-2">
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="button" onclick="filterDeliveryList()" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                            Apply Filter
                        </button>
                        <button type="button" onclick="viewAllDeliveries()" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded">
                            View ALL
                        </button>
                    </div>
                </div>
                <p class="text-gray-400 text-xs mt-2 px-2">💡 Leave dates empty to show all records, or set specific date range to filter.</p>
            </div>

            {{-- Delivery List Table --}}
            <div class="overflow-x-auto">
                <table class="min-w-full bg-gray-800 rounded-lg text-sm">
                    <thead>
                        <tr class="bg-gray-900 text-gray-300 text-xs">
                            <th class="px-3 py-3 text-left">DR No.</th>
                            <th class="px-3 py-3 text-left">Customer</th>
                            <th class="px-3 py-3 text-left">Delivery Date</th>
                            <th class="px-3 py-3 text-left">Delivery Status</th>
                            <th class="px-3 py-3 text-left">AR Status</th>
                            <th class="px-3 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="delivery_list_tbody" class="text-gray-300">
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                                <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                                <p>Loading deliveries...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Import Modal --}}
        <div id="import_modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-gray-800 rounded-lg shadow-xl p-8 max-w-2xl w-full mx-4">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold text-white">Import AR Adjustments</h3>
                    <button type="button" onclick="closeImportModal()" class="text-gray-400 hover:text-white text-2xl">×</button>
                </div>

                {{-- Import Info --}}
                <div class="bg-blue-900/30 border border-blue-700 rounded-lg p-4 mb-6">
                    <p class="text-blue-300 text-sm">
                        <i class="fas fa-info-circle mr-2"></i>
                        Upload a CSV or Excel file with AR adjustment records. Required columns: <strong>Customer Code</strong>, <strong>Customer Name</strong>, <strong>Transaction Type</strong>, <strong>Amount</strong>, <strong>Transaction Date</strong>.
                    </p>
                </div>

                <form id="import_form" class="space-y-4">
                    @csrf

                    {{-- File Upload with Drag & Drop --}}
                    <div class="border-2 border-dashed border-gray-600 rounded-lg p-8 bg-gray-700/50 hover:bg-gray-700/70 transition cursor-pointer" id="drop_zone">
                        <div class="text-center">
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                            <p class="text-white font-semibold mb-1">Drag and drop your file here</p>
                            <p class="text-gray-400 text-sm">or click to select a CSV/Excel file</p>
                            <input type="file" id="import_file" name="file" accept=".csv,.xlsx,.xls" class="hidden" required>
                        </div>
                    </div>

                    {{-- Selected File Display --}}
                    <div id="file_info" class="hidden bg-green-900/30 border border-green-700 rounded-lg p-3">
                        <p class="text-green-300 text-sm">
                            <i class="fas fa-check-circle mr-2"></i>
                            <span id="file_name">File selected</span>
                        </p>
                    </div>

                    {{-- Import Options --}}
                    <div class="bg-gray-700 rounded-lg p-4 space-y-4">
                        <h4 class="font-semibold text-white text-sm">Import Options</h4>

                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" id="merge_dr" checked class="w-4 h-4 bg-gray-600 border border-gray-500 rounded">
                            <span class="text-gray-300 text-sm">
                                <strong>Merge existing DRs:</strong> Combine adjustments if DR already has records
                            </span>
                        </label>

                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" id="auto_approve" checked class="w-4 h-4 bg-gray-600 border border-gray-500 rounded">
                            <span class="text-gray-300 text-sm">
                                <strong>Auto-approve deliveries:</strong> Mark linked deliveries as approved
                            </span>
                        </label>

                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" id="create_rr_link" class="w-4 h-4 bg-gray-600 border border-gray-500 rounded">
                            <span class="text-gray-300 text-sm">
                                <strong>Link to RR:</strong> Auto-link adjustments to Receiving Reports if available
                            </span>
                        </label>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex gap-3 pt-4 border-t border-gray-700">
                        <button type="button" onclick="closeImportModal()" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded font-medium transition">
                            Cancel
                        </button>
                        <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-medium transition flex items-center justify-center space-x-2">
                            <i class="fas fa-upload"></i>
                            <span>Import Now</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
let currentView = 'adjustment_list';
let selectedArAging = null;
let currentCustomer = null;

// ✅ Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadAdjustmentList();
    loadDashboardStats();
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('transaction_date').value = today;
    setupImportModal();
});

// ✅ Toggle between views
document.getElementById('toggle_adjustments_btn').addEventListener('click', function() {
    switchToAdjustmentList();
});

document.getElementById('toggle_deliveries_btn').addEventListener('click', function() {
    switchToDeliveryList();
});

function switchToAdjustmentEntries() {
    document.getElementById('adjustment_list_view').classList.add('hidden');
    document.getElementById('adjustment_entries_view').classList.remove('hidden');
    document.getElementById('delivery_list_view').classList.add('hidden');
    document.getElementById('no_results_message').classList.add('hidden');
    currentView = 'adjustment_entries';
}

function switchToAdjustmentList() {
    document.getElementById('adjustment_list_view').classList.remove('hidden');
    document.getElementById('adjustment_entries_view').classList.add('hidden');
    document.getElementById('delivery_list_view').classList.add('hidden');
    document.getElementById('no_results_message').classList.add('hidden');
    currentView = 'adjustment_list';

    // Reload adjustment list
    loadAdjustmentList();
}

function switchToDeliveryList() {
    document.getElementById('adjustment_list_view').classList.add('hidden');
    document.getElementById('adjustment_entries_view').classList.add('hidden');
    document.getElementById('delivery_list_view').classList.remove('hidden');
    document.getElementById('no_results_message').classList.add('hidden');
    currentView = 'delivery_list';

    // Load delivery list
    loadDeliveryList();
}

// ✅ Search customer or DR number
document.getElementById('search_customer_btn').addEventListener('click', function() {
    const searchValue = document.getElementById('customer_search').value.trim();

    if (searchValue === '') {
        Swal.fire({
            icon: 'warning',
            title: 'Empty Search',
            text: 'Please enter a customer name or DR number.',
            background: '#1f2937',
            color: '#fff'
        });
        return;
    }

    Swal.fire({
        title: 'Searching...',
        text: 'Looking up customer information',
        background: '#1f2937',
        color: '#fff',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    fetch(`/ar-adjustments/search-ar?search=${encodeURIComponent(searchValue)}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        Swal.close();

        if (data.success && data.records && data.records.length > 0) {
            // Display DR numbers for selection
            displayDRSelection(data.records);
            switchToAdjustmentEntries();
        } else {
            // Show no results message
            document.getElementById('adjustment_list_view').classList.add('hidden');
            document.getElementById('adjustment_entries_view').classList.add('hidden');
            document.getElementById('no_results_message').classList.remove('hidden');
        }
    })
    .catch(error => {
        Swal.close();
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to search. Please try again.',
            background: '#1f2937',
            color: '#fff'
        });
    });
});

// Allow Enter key to trigger search
document.getElementById('customer_search').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('search_customer_btn').click();
    }
});

// ✅ Display DR selection
function displayDRSelection(records) {
    const container = document.getElementById('dr_numbers_list');
    container.innerHTML = '';

    if (records.length === 1) {
        // Auto-select if only one record
        selectDRRecord(records[0]);
        document.getElementById('dr_selection_container').classList.add('hidden');
    } else {
        // Show selection buttons
        document.getElementById('dr_selection_container').classList.remove('hidden');

        records.forEach(record => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'w-full bg-gray-600 hover:bg-gray-500 text-white px-4 py-3 rounded text-left transition flex justify-between items-center';

            // ✅ Determine status badge color and icon
            const isFullyPaid = parseFloat(record.net_ar_balance || 0) <= 0;
            const statusBadgeClass = isFullyPaid ? 'bg-green-900/30 text-green-400' : 'bg-orange-900/30 text-orange-400';
            const statusIcon = isFullyPaid ? 'fa-check-circle' : 'fa-exclamation-circle';
            const statusText = isFullyPaid ? 'Fully Paid' : 'Outstanding';

            button.innerHTML = `
                <div>
                    <div class="font-semibold flex items-center gap-2">
                        ${record.dr_no || 'N/A'}
                        <span class="text-xs px-2 py-1 rounded ${statusBadgeClass}">
                            <i class="fas ${statusIcon} mr-1"></i>${statusText}
                        </span>
                    </div>
                    <div class="text-xs text-gray-300 mt-1">
                        ${record.client_name} | Invoice: ${record.invoice_no || 'N/A'} | Balance: ₱${parseFloat(record.net_ar_balance || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}
                    </div>
                </div>
                <i class="fas fa-chevron-right"></i>
            `;
            button.onclick = () => selectDRRecord(record);
            container.appendChild(button);
        });
    }
}

// ✅ Select DR record
function selectDRRecord(record) {
    selectedArAging = record;
    currentCustomer = {
        name: record.client_name,
        code: record.customer_code
    };

    // Hide DR selection
    document.getElementById('dr_selection_container').classList.add('hidden');

    // Update display
    document.getElementById('display_customer_name').textContent = record.client_name || '—';
    document.getElementById('display_dr_no').textContent = record.dr_no || '—';
    document.getElementById('display_invoice_no').textContent = record.invoice_no || '—';
    document.getElementById('display_ar_balance').textContent = '₱' + parseFloat(record.net_ar_balance || 0).toLocaleString('en-PH', {minimumFractionDigits: 2});

    // Show customer info and adjustment form
    document.getElementById('customer_info_container').classList.remove('hidden');
    document.getElementById('adjustment_table_container').classList.remove('hidden');
}

// ✅ Save adjustment
function saveAdjustment() {
    if (!selectedArAging) {
        Swal.fire({
            icon: 'error',
            title: 'No DR Selected',
            text: 'Please search and select a DR number first.',
            background: '#1f2937',
            color: '#fff'
        });
        return;
    }

    const transaction_date = document.getElementById('transaction_date').value;
    const reference_number = document.getElementById('reference_number').value;
    const transaction_type = document.getElementById('transaction_type').value;
    const amount = document.getElementById('amount').value;
    const gl_account = document.getElementById('gl_account').value;
    const signed_by = document.getElementById('signed_by').value;
    const remarks = document.getElementById('remarks').value;

    if (!transaction_date || !reference_number || !transaction_type || !amount || !gl_account || !signed_by) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please fill in all required fields.',
            background: '#1f2937',
            color: '#fff'
        });
        return;
    }

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

    const data = {
        transaction_date: transaction_date,
        reference_number: reference_number,
        transaction_type: transaction_type,
        dr_no: selectedArAging.dr_no,
        invoice_number: selectedArAging.invoice_no,
        customer_code: selectedArAging.customer_code,
        customer_name: selectedArAging.client_name,
        branch: selectedArAging.branch,
        amount: amount,
        gl_account: gl_account,
        signed_by: signed_by,
        remarks: remarks
    };

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    fetch('/ar-adjustments', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
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
                background: '#1f2937',
                color: '#fff'
            }).then(() => {
                // Clear form
                document.getElementById('reference_number').value = '';
                document.getElementById('transaction_type').value = '';
                document.getElementById('amount').value = '';
                document.getElementById('gl_account').value = '';
                document.getElementById('signed_by').value = '';
                document.getElementById('remarks').value = '';

                // Switch back to list view
                switchToAdjustmentList();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to save adjustment',
                background: '#1f2937',
                color: '#fff'
            });
        }
    })
    .catch(error => {
        Swal.close();
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to save adjustment. Please try again.',
            background: '#1f2937',
            color: '#fff'
        });
    });
}

// ✅ Load dashboard statistics
function loadDashboardStats() {
    fetch('{{ route("ar_adjustments.stats.dashboard") }}', {
        method: 'GET',
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.stats) {
            const stats = data.stats;

            // Update today's stats
            document.getElementById('stat_today_count').textContent = stats.today.count;
            document.getElementById('stat_today_total').textContent = '₱' + parseFloat(stats.today.total).toLocaleString('en-PH', {minimumFractionDigits: 2});

            // Update this month stats
            document.getElementById('stat_month_count').textContent = stats.this_month.count;
            document.getElementById('stat_month_total').textContent = '₱' + parseFloat(stats.this_month.total).toLocaleString('en-PH', {minimumFractionDigits: 2});

            // Update last 30 days stats
            document.getElementById('stat_30day_count').textContent = stats.last_30_days.count;
            document.getElementById('stat_30day_total').textContent = '₱' + parseFloat(stats.last_30_days.total).toLocaleString('en-PH', {minimumFractionDigits: 2});

            // Update pending approvals
            if (data.pending_approvals && data.pending_approvals.length > 0) {
                document.getElementById('stat_pending_count').textContent = data.pending_approvals.length;
            } else {
                document.getElementById('stat_pending_count').textContent = '0';
            }
        }
    })
    .catch(error => {
        console.error('Failed to load dashboard stats:', error);
    });
}

// ✅ Load adjustment list
function loadAdjustmentList() {
    const today = new Date();
    const thirtyDaysAgo = new Date(today);
    thirtyDaysAgo.setDate(today.getDate() - 30);

    // Set default dates if not already set
    if (!document.getElementById('filter_start_date').value) {
        document.getElementById('filter_start_date').valueAsDate = thirtyDaysAgo;
    }
    if (!document.getElementById('filter_end_date').value) {
        document.getElementById('filter_end_date').valueAsDate = today;
    }

    filterAdjustmentList();
}

// ✅ Filter adjustment list
function filterAdjustmentList() {
    const dateFrom = document.getElementById('filter_start_date').value;
    const dateTo = document.getElementById('filter_end_date').value;
    const transactionType = document.getElementById('filter_transaction_type').value;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    fetch(`/ar-adjustments/get?start_date=${dateFrom}&end_date=${dateTo}&transaction_type=${transactionType}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const tbody = document.getElementById('adjustment_list_tbody');
            tbody.innerHTML = '';

            if (!data.adjustments || data.adjustments.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="15" class="px-4 py-8 text-center text-gray-400">
                            <i class="fas fa-file-invoice text-4xl mb-2"></i>
                            <p>No adjustment data available</p>
                        </td>
                    </tr>
                `;
                return;
            }

            data.adjustments.forEach(adj => {
                const row = document.createElement('tr');
                row.className = 'border-b border-gray-700 hover:bg-gray-750';

                const typeColor = {
                    'atd': 'blue',
                    'offset': 'indigo',
                    'credit_memo': 'green',
                    'debit_memo': 'red',
                    'adjustment': 'yellow',
                    'write_off': 'purple'
                }[adj.transaction_type] || 'gray';

                const amountColor = adj.amount < 0 ? 'text-red-400' : 'text-green-400';
                const amountSign = adj.amount < 0 ? '-' : '+';
                const amountDisplay = `${amountSign}₱${Math.abs(adj.amount).toLocaleString('en-PH', {minimumFractionDigits: 2})}`;

                // DR Status badge
                const drStatusColor = {
                    'approved': 'green',
                    'pending': 'yellow',
                    'rejected': 'red',
                    'completed': 'blue',
                    'partial': 'orange'
                }[adj.dr_status] || 'gray';

                const drStatusDisplay = adj.dr_status
                    ? `<span class="bg-${drStatusColor}-900/30 border border-${drStatusColor}-700/50 px-2 py-1 rounded text-xs">${adj.dr_status}</span>`
                    : '<span class="text-gray-500 text-xs">—</span>';

                // Customer display
                const customerDisplay = adj.customer_name ? `<div class="font-semibold">${adj.customer_name}</div><div class="text-xs text-gray-400">${adj.customer_code || 'N/A'}</div>` : 'N/A';

                row.innerHTML = `
                    <td class="px-3 py-3 text-sm">${new Date(adj.transaction_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
                    <td class="px-3 py-3">
                        <span class="bg-blue-900/30 border border-blue-700/50 px-2 py-1 rounded text-xs font-mono">
                            ${adj.reference_number}
                        </span>
                    </td>
                    <td class="px-3 py-3">
                        <span class="bg-${typeColor}-600 px-2 py-1 rounded text-xs font-medium">
                            ${adj.formatted_type || adj.transaction_type}
                        </span>
                    </td>
                    <td class="px-3 py-3 text-sm">${customerDisplay}</td>
                    <td class="px-3 py-3">
                        ${adj.dr_no ? `<a href="javascript:void(0)" onclick="viewDeliveryByDrNo('${adj.dr_no}')" class="text-blue-400 hover:text-blue-300 font-mono text-sm cursor-pointer">${adj.dr_no}</a>` : '<span class="text-gray-500">N/A</span>'}
                    </td>
                    <td class="px-3 py-3">${drStatusDisplay}</td>
                    <td class="px-3 py-3">
                        <span class="bg-gray-700 px-2 py-1 rounded text-xs">
                            ${adj.invoice_number || 'N/A'}
                        </span>
                    </td>
                    <td class="px-3 py-3 text-right font-semibold ${amountColor}">${amountDisplay}</td>
                    <td class="px-3 py-3">
                        <span class="text-xs bg-purple-900/30 border border-purple-700/50 px-2 py-1 rounded">
                            ${adj.gl_account}
                        </span>
                    </td>
                    <td class="px-3 py-3 text-xs max-w-xs truncate" title="${adj.remarks || ''}">${adj.remarks || '—'}</td>
                    <td class="px-3 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            ${adj.customer_code ? `<a href="/ar/customer/${adj.customer_code}" class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1 rounded text-xs transition inline-block" title="View AR Profile">
                                <i class="fas fa-user-circle"></i>
                            </a>` : ''}
                            <button onclick="viewAdjustment(${adj.id})" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs transition" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                            <a href="/ar-adjustments/${adj.id}/edit" class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded text-xs transition inline-block" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button onclick="deleteAdjustment(${adj.id})" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs transition" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// ✅ Export adjustment list
function exportAdjustmentList() {
    const dateFrom = document.getElementById('filter_start_date').value;
    const dateTo = document.getElementById('filter_end_date').value;
    const transactionType = document.getElementById('filter_transaction_type').value;

    Swal.fire({
        icon: 'info',
        title: 'Export Started',
        text: 'Adjustment list will be downloaded shortly.',
        background: '#1f2937',
        color: '#fff',
        timer: 2000,
        showConfirmButton: false
    });

    window.location.href = `/ar-adjustments/export/csv?start_date=${dateFrom}&end_date=${dateTo}&transaction_type=${transactionType}`;
}

// ✅ View Adjustment Details
function viewAdjustment(id) {
    window.location.href = `/ar-adjustments/${id}`;
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
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            fetch(`/ar-adjustments/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: data.message,
                        background: '#1f2937',
                        color: '#fff',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    // Reload the list
                    loadAdjustmentList();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message,
                        background: '#1f2937',
                        color: '#fff'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to delete adjustment',
                    background: '#1f2937',
                    color: '#fff'
                });
            });
        }
    });
}

// ✅ Import Modal Functions
function setupImportModal() {
    const importModalBtn = document.getElementById('import_modal_btn');
    const importModal = document.getElementById('import_modal');
    const importForm = document.getElementById('import_form');
    const dropZone = document.getElementById('drop_zone');
    const importFile = document.getElementById('import_file');
    const fileInfo = document.getElementById('file_info');

    // Open modal
    importModalBtn.addEventListener('click', () => {
        importModal.classList.remove('hidden');
    });

    // Close modal on background click
    importModal.addEventListener('click', (e) => {
        if (e.target === importModal) {
            closeImportModal();
        }
    });

    // Drag & drop
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('border-blue-500', 'bg-blue-900/20');
    });

    dropZone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-blue-500', 'bg-blue-900/20');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-blue-500', 'bg-blue-900/20');
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            importFile.files = files;
            displayFileName(files[0]);
        }
    });

    // Click to browse
    dropZone.addEventListener('click', () => {
        importFile.click();
    });

    importFile.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            displayFileName(e.target.files[0]);
        }
    });

    // Handle form submit
    importForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const file = importFile.files[0];
        if (!file) {
            Swal.fire({
                icon: 'error',
                title: 'No File Selected',
                text: 'Please select a file to import.',
                background: '#1f2937',
                color: '#fff'
            });
            return;
        }

        const formData = new FormData();
        formData.append('file', file);
        formData.append('merge_dr', document.getElementById('merge_dr').checked ? 1 : 0);
        formData.append('auto_approve', document.getElementById('auto_approve').checked ? 1 : 0);
        formData.append('create_rr_link', document.getElementById('create_rr_link').checked ? 1 : 0);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

        Swal.fire({
            title: 'Importing...',
            text: 'Processing your file',
            background: '#1f2937',
            color: '#fff',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {
            const response = await fetch('{{ route("excel.import.ar_adjustments") }}', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            Swal.close();

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Import Successful!',
                    html: data.message.replace(/\n/g, '<br>'),
                    background: '#1f2937',
                    color: '#fff'
                }).then(() => {
                    closeImportModal();
                    importFile.value = '';
                    fileInfo.classList.add('hidden');
                    loadAdjustmentList();
                });
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Import Completed with Errors',
                    html: data.message.replace(/\n/g, '<br>'),
                    background: '#1f2937',
                    color: '#fff'
                });
            }
        } catch (error) {
            Swal.close();
            console.error('Import error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Import Failed',
                text: error.message || 'An error occurred during import.',
                background: '#1f2937',
                color: '#fff'
            });
        }
    });
}

function displayFileName(file) {
    const fileInfo = document.getElementById('file_info');
    const fileName = document.getElementById('file_name');
    fileName.textContent = `✓ ${file.name} (${(file.size / 1024).toFixed(2)} KB)`;
    fileInfo.classList.remove('hidden');
}

function closeImportModal() {
    document.getElementById('import_modal').classList.add('hidden');
    document.getElementById('import_file').value = '';
    document.getElementById('file_info').classList.add('hidden');
}

// ✅ View delivery by DR number
function viewDeliveryByDrNo(drNo) {
    Swal.fire({
        title: 'Loading...',
        text: 'Finding delivery record',
        background: '#1f2937',
        color: '#fff',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    fetch(`/deliveries/search?dr_no=${encodeURIComponent(drNo)}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        Swal.close();
        if (data.delivery && data.delivery.id) {
            window.location.href = `/deliveries/${data.delivery.id}`;
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Delivery Not Found',
                text: `Could not find delivery with DR number: ${drNo}`,
                background: '#1f2937',
                color: '#fff'
            });
        }
    })
    .catch(error => {
        Swal.close();
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to find delivery record',
            background: '#1f2937',
            color: '#fff'
        });
    });
}

// ✅ Load delivery list
function loadDeliveryList() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    fetch('/ar-adjustments/deliveries/pending', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        const tbody = document.getElementById('delivery_list_tbody');
        tbody.innerHTML = '';

        if (!data.deliveries || data.deliveries.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>No pending deliveries or all deliveries have AR adjustments</p>
                    </td>
                </tr>
            `;
            return;
        }

        data.deliveries.forEach(delivery => {
            const row = document.createElement('tr');
            row.className = 'border-b border-gray-700 hover:bg-gray-750';

            const deliveryStatusColor = {
                'approved': 'green',
                'pending': 'yellow',
                'rejected': 'red',
                'completed': 'blue',
                'partial': 'orange'
            }[delivery.status] || 'gray';

            const arStatusDisplay = delivery.has_adjustment
                ? '<span class="bg-green-900/30 border border-green-700/50 px-2 py-1 rounded text-xs">Has Adjustment</span>'
                : '<span class="bg-yellow-900/30 border border-yellow-700/50 px-2 py-1 rounded text-xs">Pending Adjustment</span>';

            row.innerHTML = `
                <td class="px-3 py-3 font-mono text-sm">${delivery.dr_no || 'N/A'}</td>
                <td class="px-3 py-3 text-sm">
                    <div class="font-semibold">${delivery.customer_name || 'N/A'}</div>
                    <div class="text-xs text-gray-400">${delivery.customer_code || 'N/A'}</div>
                </td>
                <td class="px-3 py-3 text-sm">${delivery.request_delivery_date ? new Date(delivery.request_delivery_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A'}</td>
                <td class="px-3 py-3">
                    <span class="bg-${deliveryStatusColor}-900/30 border border-${deliveryStatusColor}-700/50 px-2 py-1 rounded text-xs">
                        ${delivery.status || 'N/A'}
                    </span>
                </td>
                <td class="px-3 py-3">
                    ${arStatusDisplay}
                </td>
                <td class="px-3 py-3 text-center">
                    ${delivery.has_adjustment
                        ? '<span class="text-gray-500 text-sm">—</span>'
                        : `<button onclick="createAdjustmentFromDelivery('${(delivery.dr_no || '').replace(/'/g, "\\'")}', '${(delivery.customer_code || '').replace(/'/g, "\\'")}', '${(delivery.customer_name || '').replace(/'/g, "\\'")}')" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs transition" title="Create Adjustment">
                            <i class="fas fa-plus"></i> Create
                        </button>`
                    }
                </td>
            `;
            tbody.appendChild(row);
        });
    })
    .catch(error => {
        console.error('Error loading deliveries:', error);
        const tbody = document.getElementById('delivery_list_tbody');
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-4 py-8 text-center text-red-400">
                    <i class="fas fa-exclamation-circle text-2xl mb-2"></i>
                    <p>Failed to load deliveries</p>
                </td>
            </tr>
        `;
    });
}

// ✅ Reload delivery list
function reloadDeliveryList() {
    loadDeliveryList();
}

// ✅ NEW: Filter deliveries by date range
function filterDeliveryList() {
    const startDate = document.getElementById('delivery_filter_start_date').value;
    const endDate = document.getElementById('delivery_filter_end_date').value;

    if (!startDate || !endDate) {
        Swal.fire({
            icon: 'warning',
            title: 'Missing Dates',
            text: 'Please enter both start and end dates to filter.',
            background: '#1f2937',
            color: '#fff'
        });
        return;
    }

    filterDeliveriesByDate(startDate, endDate);
}

// ✅ NEW: View all deliveries without date filter
function viewAllDeliveries() {
    document.getElementById('delivery_filter_start_date').value = '';
    document.getElementById('delivery_filter_end_date').value = '';
    loadDeliveryList();
}

// ✅ NEW: Apply date filter to deliveries
function filterDeliveriesByDate(startDate, endDate) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    fetch(`/ar-adjustments/deliveries/pending?date_from=${startDate}&date_to=${endDate}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        const tbody = document.getElementById('delivery_list_tbody');
        tbody.innerHTML = '';

        if (!data.deliveries || data.deliveries.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>No deliveries found for the selected date range</p>
                    </td>
                </tr>
            `;
            return;
        }

        data.deliveries.forEach(delivery => {
            const row = document.createElement('tr');
            row.className = 'border-b border-gray-700 hover:bg-gray-750';

            const deliveryStatusColor = {
                'approved': 'green',
                'pending': 'yellow',
                'rejected': 'red',
                'completed': 'blue',
                'partial': 'orange'
            }[delivery.status] || 'gray';

            const arStatusDisplay = delivery.has_adjustment
                ? '<span class="bg-green-900/30 border border-green-700/50 px-2 py-1 rounded text-xs">Has Adjustment</span>'
                : '<span class="bg-yellow-900/30 border border-yellow-700/50 px-2 py-1 rounded text-xs">Pending Adjustment</span>';

            row.innerHTML = `
                <td class="px-3 py-3 font-mono text-sm">${delivery.dr_no || 'N/A'}</td>
                <td class="px-3 py-3 text-sm">
                    <div class="font-semibold">${delivery.customer_name || 'N/A'}</div>
                    <div class="text-xs text-gray-400">${delivery.customer_code || 'N/A'}</div>
                </td>
                <td class="px-3 py-3 text-sm">${delivery.request_delivery_date ? new Date(delivery.request_delivery_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A'}</td>
                <td class="px-3 py-3">
                    <span class="bg-${deliveryStatusColor}-900/30 border border-${deliveryStatusColor}-700/50 px-2 py-1 rounded text-xs">
                        ${delivery.status || 'N/A'}
                    </span>
                </td>
                <td class="px-3 py-3">
                    ${arStatusDisplay}
                </td>
                <td class="px-3 py-3 text-center">
                    ${delivery.has_adjustment
                        ? '<span class="text-gray-500 text-sm">—</span>'
                        : `<button onclick="createAdjustmentFromDelivery('${(delivery.dr_no || '').replace(/'/g, "\\'")}', '${(delivery.customer_code || '').replace(/'/g, "\\'")}', '${(delivery.customer_name || '').replace(/'/g, "\\'")}')" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs transition" title="Create Adjustment">
                            <i class="fas fa-plus"></i> Create
                        </button>`
                    }
                </td>
            `;
            tbody.appendChild(row);
        });
    })
    .catch(error => {
        console.error('Error filtering deliveries:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to filter deliveries.',
            background: '#1f2937',
            color: '#fff'
        });
    });
}

// ✅ Create adjustment from delivery - show list of customer's deliveries to choose from
function createAdjustmentFromDelivery(drNo, customerCode, customerName) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    // Show loading dialog
    Swal.fire({
        title: 'Loading...',
        text: 'Fetching deliveries',
        background: '#1f2937',
        color: '#fff',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Fetch all pending deliveries for this customer
    fetch(`/ar-adjustments/customer/${customerCode}/deliveries`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        Swal.close();

        if (data.success && data.deliveries && data.deliveries.length > 0) {
            // Build HTML for delivery selection modal
            let deliveryHTML = '<div style="text-align: left; max-height: 400px; overflow-y: auto;">';
            deliveryHTML += '<p style="margin-bottom: 15px; color: #9CA3AF; font-size: 14px;">Select a delivery to create an adjustment for:</p>';
            deliveryHTML += '<div style="display: flex; flex-direction: column; gap: 10px;">';

            data.deliveries.forEach(delivery => {
                const deliveryDate = new Date(delivery.request_delivery_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                const totalAmount = parseFloat(delivery.total_amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const itemCount = delivery.item_count || 0;
                const poNumber = delivery.po_number ? `PO: ${delivery.po_number}` : '';
                const siNo = delivery.sales_invoice_no ? `SI: ${delivery.sales_invoice_no}` : '';

                deliveryHTML += `
                    <button class="delivery-select-btn" data-dr-no="${delivery.dr_no}" data-customer-code="${customerCode}" data-customer-name="${customerName}" data-sales-invoice="${delivery.sales_invoice_no || ''}"
                            style="padding: 12px; background: #374151; border: 1px solid #4B5563; border-radius: 6px; color: #E5E7EB; cursor: pointer; text-align: left; transition: background 0.2s; width: 100%;"
                            onmouseover="this.style.background='#4B5563'"
                            onmouseout="this.style.background='#374151'">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <div>
                                <strong style="font-size: 15px;">${delivery.dr_no}</strong>
                                ${siNo ? `<div style="font-size: 12px; color: #9CA3AF; margin-top: 2px;">${siNo}</div>` : ''}
                            </div>
                            <span style="font-size: 13px; color: #9CA3AF;">₱${totalAmount}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 12px; color: #9CA3AF;">
                            <span>${deliveryDate} • ${itemCount} item${itemCount !== 1 ? 's' : ''}</span>
                            ${poNumber ? `<span>${poNumber}</span>` : ''}
                        </div>
                    </button>
                `;
            });

            deliveryHTML += '</div>';
            deliveryHTML += '</div>';

            // Show modal with delivery list
            Swal.fire({
                title: `${customerName}`,
                html: deliveryHTML,
                background: '#1f2937',
                color: '#fff',
                confirmButtonText: 'Cancel',
                confirmButtonColor: '#6B7280',
                showConfirmButton: true,
                allowOutsideClick: true,
                didOpen: () => {
                    // Attach click handlers to delivery buttons
                    document.querySelectorAll('.delivery-select-btn').forEach(btn => {
                        btn.addEventListener('click', function(e) {
                            e.preventDefault();
                            const drNo = this.dataset.drNo;
                            const customerCode = this.dataset.customerCode;
                            const customerName = this.dataset.customerName;
                            const salesInvoice = this.dataset.salesInvoice;
                            selectDeliveryAndRedirect(drNo, customerCode, customerName, salesInvoice);
                        });
                    });
                }
            });
        } else if (data.deliveries && data.deliveries.length === 0) {
            Swal.fire({
                icon: 'info',
                title: 'No Deliveries',
                text: 'No pending deliveries found for this customer.',
                background: '#1f2937',
                color: '#fff'
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to load deliveries',
                background: '#1f2937',
                color: '#fff'
            });
        }
    })
    .catch(error => {
        Swal.close();
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to load deliveries',
            background: '#1f2937',
            color: '#fff'
        });
    });
}

// ✅ Helper function to redirect after selecting a delivery
function selectDeliveryAndRedirect(selectedDrNo, customerCode, customerName, salesInvoiceNo) {
    const params = new URLSearchParams({
        dr_no: selectedDrNo,
        customer_code: customerCode,
        customer_name: customerName
    });
    if (salesInvoiceNo) {
        params.append('sales_invoice_no', salesInvoiceNo);
    }
    window.location.href = `/ar-adjustments/create?${params.toString()}`;
}
</script>
@endsection
