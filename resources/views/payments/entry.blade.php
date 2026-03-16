@extends('layouts.app')

@section('title', 'Payment Entry Screen')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-white">Payment Entry Screen</h2>
            
            <!-- ✅ UPDATED: Dynamic button that switches based on current view -->
            <button type="button" id="toggle_view_btn" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded font-medium transition flex items-center space-x-2">
                <i class="fas fa-table"></i>
                <span id="toggle_btn_text">Payment List</span>
            </button>
        </div>

        <!-- Search Section -->
        <div class="bg-gray-700 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-white mb-4">Search Customer</h3>
            <p class="text-gray-400 text-sm mb-3">✅ Search by: Customer Name, Customer Code, or DR Number</p>
            <div class="flex items-center space-x-4">
                <div class="flex-1">
                    <input type="text" id="customer_search" placeholder="Enter Customer Name, Code, or DR Number"
                           class="w-full bg-gray-600 text-white border border-gray-500 rounded px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="button" id="search_customer_btn" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded font-medium transition flex items-center space-x-2">
                    <i class="fas fa-search"></i>
                    <span>Search</span>
                </button>
            </div>
        </div>

        <!-- ✅ Payment List View (shown initially) -->
        <div id="payment_list_view" class="bg-gray-700 rounded-lg p-4">
            <div class="flex justify-between items-center mb-4">
                <h4 class="text-lg font-semibold text-white">Payment List</h4>
                <button type="button" onclick="exportPaymentList()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm flex items-center space-x-2">
                    <i class="fas fa-file-excel"></i>
                    <span>Export to Excel</span>
                </button>
            </div>

            <!-- Filter Section -->
            <div class="bg-gray-800 rounded-lg p-4 mb-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Date From</label>
                        <input type="date" id="report_date_from" class="w-full bg-gray-600 text-white border border-gray-500 rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Date To</label>
                        <input type="date" id="report_date_to" class="w-full bg-gray-600 text-white border border-gray-500 rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Customer</label>
                        <input type="text" id="report_customer_filter" placeholder="Filter by customer" class="w-full bg-gray-600 text-white border border-gray-500 rounded px-3 py-2">
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="button" onclick="filterPaymentList()" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                            Apply Filter
                        </button>
                        <button type="button" onclick="viewAllPayments()" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded">
                            View ALL Records
                        </button>
                    </div>
                </div>
                <p class="text-gray-400 text-xs mt-2 px-2">💡 Leave dates empty to show all records, or set specific date range to filter.</p>
            </div>

            <!-- Payment List Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full bg-gray-800 rounded-lg text-sm">
                    <thead>
                        <tr class="bg-gray-900 text-gray-300">
                            <th class="px-4 py-3 text-left">Customer Name</th>
                            <th class="px-4 py-3 text-left">Collection Receipt Number</th>
                            <th class="px-4 py-3 text-left">Collection Receipt Date</th>
                            <th class="px-4 py-3 text-left">Payment Posting Date</th>
                            <th class="px-4 py-3 text-left">Payment Means</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                            <th class="px-4 py-3 text-right">Tax</th>
                        </tr>
                    </thead>
                    <tbody id="payment_list_tbody" class="text-gray-300">
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-400">
                                <i class="fas fa-file-invoice text-4xl mb-2"></i>
                                <p>No payment data available</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ✅ Payment Entries View (shown after search) -->
        <div id="payment_entries_view" class="hidden">
            <!-- ✅ Customer Payment History with CHECKBOXES -->
<div id="customer_payment_history" class="bg-gray-700 rounded-lg p-4 mb-4">
    <div class="flex justify-between items-center mb-3">
        <h4 class="text-sm font-semibold text-white flex items-center">
            <i class="fas fa-file-invoice-dollar mr-2 text-orange-400"></i>
            Outstanding Payments for <span id="history_customer_name" class="ml-2 text-blue-300"></span>
        </h4>
        <div class="flex items-center space-x-2">
            <!-- ✅ NEW: Add Selected Button -->
            <button type="button" id="add_selected_btn" onclick="addSelectedToPaymentTable()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm flex items-center space-x-2 hidden transition">
                <i class="fas fa-plus"></i>
                <span>Add Selected (<span id="selected_count">0</span>)</span>
            </button>
            <button type="button" onclick="togglePaymentHistory()" class="text-gray-400 hover:text-white text-xs">
                <i class="fas fa-chevron-down" id="history_toggle_icon"></i>
            </button>
        </div>
    </div>

    <div id="payment_history_content" class="overflow-x-auto" style="max-height: 300px;">
        <table class="min-w-full bg-gray-800 rounded-lg text-xs">
            <thead class="bg-gray-900 text-gray-300 sticky top-0">
                <tr>
                    <!-- ✅ NEW: Select All Checkbox -->
                    <th class="px-2 py-2 text-left w-12">
                        <input type="checkbox" 
                               id="select_all_checkbox" 
                               onchange="toggleSelectAll()" 
                               class="w-4 h-4 rounded bg-gray-700 border-gray-600 text-blue-600 focus:ring-blue-500 cursor-pointer"
                               title="Select All">
                    </th>
                    <th class="px-2 py-2 text-left">Deposit Date</th>
                    <th class="px-2 py-2 text-left">CR Number</th>
                    <th class="px-2 py-2 text-left">Invoice No</th>
                    <th class="px-2 py-2 text-left">DR No</th>
                    <th class="px-2 py-2 text-left">Customer Name</th>
                    <th class="px-2 py-2 text-left">Branch</th>
                    <th class="px-2 py-2 text-right">Gross Amount</th>
                    <th class="px-2 py-2 text-right">EWT</th>
                    <th class="px-2 py-2 text-right">Other Adj.</th>
                    <th class="px-2 py-2 text-right">Factoring</th>
                    <th class="px-2 py-2 text-right">Check Amount</th>
                    <th class="px-2 py-2 text-right">Net of CWT</th>
                    <th class="px-2 py-2 text-left">Week No</th>
                    <th class="px-2 py-2 text-left">AR Class</th>
                    <th class="px-2 py-2 text-left">Bank</th>
                    <th class="px-2 py-2 text-left">Checking SI</th>
                    <th class="px-2 py-2 text-left">Status</th>
                    <th class="px-2 py-2 text-left">Remarks</th>
                </tr>
            </thead>
            <tbody id="customer_payment_history_tbody" class="text-gray-300">
                <tr>
                    <td colspan="19" class="px-4 py-8 text-center text-gray-400">
                        <i class="fas fa-search text-3xl mb-2"></i>
                        <p>Search for a customer to view payment history</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

            <!-- Customer Information -->
            <div id="customer_info_container" class="bg-gray-700 rounded-lg p-4 mb-4">
                <div class="flex justify-between items-start mb-3">
                    <h4 class="text-sm font-semibold text-white flex items-center">
                        <i class="fas fa-user mr-2"></i>
                        Customer Information
                    </h4>
                    <a href="#" id="customerProfileLink" class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1 rounded text-xs flex items-center gap-2 hidden" target="_blank">
                        <i class="fas fa-user-circle"></i>View AR Profile
                    </a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                    <div>
                        <label class="block text-gray-400 text-xs mb-1">Customer Name</label>
                        <p class="text-white font-semibold" id="display_customer_name">—</p>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-xs mb-1">Outstanding Balance</label>
                        <p class="text-red-400 font-bold text-lg" id="display_outstanding_balance">₱0.00</p>
                    </div>
                </div>
            </div>

            <!-- Excel-like Payment Entry Table -->
<div id="payment_table_container" class="bg-gray-700 rounded-lg p-4">
    <div class="flex justify-between items-center mb-3">
        <h4 class="text-sm font-semibold text-white">Payment Entries</h4>
        <div class="flex space-x-2">
            <button type="button" onclick="addPaymentRow()" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded text-xs flex items-center space-x-1">
                <i class="fas fa-plus"></i>
                <span>Add Row</span>
            </button>
            <button type="button" onclick="saveAllPayments()" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-xs flex items-center space-x-1">
                <i class="fas fa-save"></i>
                <span>Save All</span>
            </button>
        </div>
    </div>

    <div class="overflow-x-auto" style="max-height: 400px;">
        <table class="min-w-full bg-gray-800 rounded-lg text-xs">
            <thead class="bg-gray-900 text-gray-300 sticky top-0">
                <tr>
                    <th class="px-2 py-2 text-left w-8">#</th>
                    <th class="px-2 py-2 text-left">DR Number *</th>
                    <th class="px-2 py-2 text-left">Invoice No</th>
                    <th class="px-2 py-2 text-right">Outstanding Balance</th>
                    <th class="px-2 py-2 text-left">Collection Receipt Number *</th>
                    <th class="px-2 py-2 text-left">Collection Receipt Date *</th>
                    <th class="px-2 py-2 text-left">Payment Posting Date *</th>
                    <th class="px-2 py-2 text-left">Payment Option *</th>
                    <th class="px-2 py-2 text-right">Amount *</th>
                    <th class="px-2 py-2 text-right">Tax</th>
                    <th class="px-2 py-2 text-right">Net</th>
                    <th class="px-2 py-2 text-left">Notes</th>
                    <th class="px-2 py-2 text-center w-12">Action</th>
                </tr>
            </thead>
            <tbody id="payment_entries_tbody" class="text-white">
                <!-- Rows will be added here dynamically -->
            </tbody>
        </table>
    </div>
</div>
        </div>

        <!-- No Results Message -->
        <div id="no_results_message" class="hidden bg-gray-700 rounded-lg p-8 text-center">
            <i class="fas fa-user-slash text-5xl text-gray-500 mb-4"></i>
            <h3 class="text-xl font-semibold text-white mb-2">Customer Not Found</h3>
            <p class="text-gray-400">The customer you searched for does not exist. Please check and try again.</p>
        </div>
    </div>
</div>

<!-- ✅ SIDE PANEL for Payment Means Details -->
<div id="payment_means_panel" class="fixed top-0 right-0 h-full w-96 bg-gray-800 shadow-2xl transform translate-x-full transition-transform duration-300 ease-in-out z-50 overflow-y-auto">
    <div class="p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h3 class="text-lg font-bold text-white flex items-center">
                <i class="fas fa-credit-card mr-2 text-blue-400"></i>
                Payment Means Details
            </h3>
            <button type="button" onclick="closePaymentMeansPanel()" class="text-gray-400 hover:text-white transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <!-- Row Info -->
        <div class="bg-gray-900 rounded-lg p-3 mb-4">
            <p class="text-sm text-gray-400">Editing Row: <span id="panel_row_number" class="text-white font-semibold">#1</span></p>
            <p class="text-xs text-gray-500 mt-1">DR: <span id="panel_dr_number" class="text-gray-300">—</span></p>
        </div>

        <!-- Payment Type Selection -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-300 mb-2">Payment Means Type *</label>
            <select id="panel_payment_type" onchange="updatePaymentMeansFields()" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
                <option value="">Select Payment Type</option>
                <option value="check">Check</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="cash">Cash</option>
            </select>
        </div>

        <!-- CHECK Fields -->
        <div id="check_fields" class="hidden space-y-4">
            <h4 class="text-sm font-semibold text-blue-300 mb-3 flex items-center">
                <i class="fas fa-money-check mr-2"></i>
                Check Details
            </h4>
            
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">G/L Account *</label>
                <div class="relative">
                    <input type="hidden" id="check_gl_account_id">
                    <input type="text" id="check_gl_account_search" placeholder="Search GL Account (code/name)" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <div id="check_gl_account_dropdown" class="absolute top-full left-0 right-0 bg-gray-800 border border-gray-600 rounded max-h-48 overflow-y-auto z-10 hidden mt-1"></div>
                </div>
                <input type="hidden" id="check_gl_account">
                <p class="text-xs text-gray-500 mt-1">For PDC, use Clearing Account - PDC</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Due Date *</label>
                <input type="date" id="check_due_date" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Amount *</label>
                <input type="number" step="0.01" id="check_amount" placeholder="0.00" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
                <button type="button" onclick="copyBalanceDueToCheck()" class="text-xs text-blue-400 hover:text-blue-300 mt-1">
                    <i class="fas fa-copy mr-1"></i>Copy Balance Due
                </button>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Bank Name *</label>
                <select id="check_bank_name" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">Select Bank</option>
                    <option value="BDO">BDO</option>
                    <option value="BPI">BPI</option>
                    <option value="Metrobank">Metrobank</option>
                    <option value="Security Bank">Security Bank</option>
                    <option value="UnionBank">UnionBank</option>
                    <option value="RCBC">RCBC</option>
                    <option value="PNB">PNB</option>
                    <option value="Landbank">Landbank</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Check Number *</label>
                <input type="text" id="check_number" placeholder="Check #" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <!-- BANK TRANSFER Fields -->
        <div id="bank_transfer_fields" class="hidden space-y-4">
            <h4 class="text-sm font-semibold text-green-300 mb-3 flex items-center">
                <i class="fas fa-exchange-alt mr-2"></i>
                Bank Transfer Details
            </h4>
            
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">G/L Account *</label>
                <div class="relative">
                    <input type="hidden" id="transfer_gl_account_id">
                    <input type="text" id="transfer_gl_account_search" placeholder="Search GL Account (code/name)" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <div id="transfer_gl_account_dropdown" class="absolute top-full left-0 right-0 bg-gray-800 border border-gray-600 rounded max-h-48 overflow-y-auto z-10 hidden mt-1"></div>
                </div>
                <input type="hidden" id="transfer_gl_account">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Transfer Date *</label>
                <input type="date" id="transfer_date" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Reference Number</label>
                <input type="text" id="transfer_reference" placeholder="Reference #" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Total Amount *</label>
                <input type="number" step="0.01" id="transfer_amount" placeholder="0.00" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
                <button type="button" onclick="copyBalanceDueToTransfer()" class="text-xs text-blue-400 hover:text-blue-300 mt-1">
                    <i class="fas fa-copy mr-1"></i>Copy Balance Due
                </button>
            </div>
        </div>

        <!-- CASH Fields -->
        <div id="cash_fields" class="hidden space-y-4">
            <h4 class="text-sm font-semibold text-yellow-300 mb-3 flex items-center">
                <i class="fas fa-money-bill-wave mr-2"></i>
                Cash Payment Details
            </h4>
            
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">G/L Account *</label>
                <div class="relative">
                    <input type="hidden" id="cash_gl_account_id">
                    <input type="text" id="cash_gl_account_search" placeholder="Search GL Account (code/name)" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <div id="cash_gl_account_dropdown" class="absolute top-full left-0 right-0 bg-gray-800 border border-gray-600 rounded max-h-48 overflow-y-auto z-10 hidden mt-1"></div>
                </div>
                <input type="hidden" id="cash_gl_account">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Total Amount *</label>
                <input type="number" step="0.01" id="cash_amount" placeholder="0.00" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
                <button type="button" onclick="copyBalanceDueToCash()" class="text-xs text-blue-400 hover:text-blue-300 mt-1">
                    <i class="fas fa-copy mr-1"></i>Copy Balance Due
                </button>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex space-x-3 mt-6 pt-4 border-t border-gray-700">
            <button type="button" onclick="savePaymentMeans()" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-medium transition">
                <i class="fas fa-check mr-2"></i>Save
            </button>
            <button type="button" onclick="closePaymentMeansPanel()" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded font-medium transition">
                Cancel
            </button>
        </div>
    </div>
</div>

<!-- Overlay for Side Panel -->
<div id="panel_overlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-40" onclick="closePaymentMeansPanel()"></div>

<script>
let currentCustomer = null;
let paymentRowCounter = 0;
let originalOutstandingBalance = 0;
let currentView = 'payment_list';
let outstandingInvoices = {}; // Store outstanding invoices by DR number
let selectedOutstandingPayments = new Set(); // Track selected outstanding payments
let outstandingPaymentsData = []; // Store full payment data
let currentEditingRowId = null; // 
let paymentMeansData = {};

function toggleOutstandingPayment(drNo) {
    if (selectedOutstandingPayments.has(drNo)) {
        selectedOutstandingPayments.delete(drNo);
    } else {
        selectedOutstandingPayments.add(drNo);
    }
    updateSelectedCount();
    updateCheckboxStates();
}

// ✅ NEW: Toggle select all
function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('select_all_checkbox');
    
    if (selectAllCheckbox.checked) {
        outstandingPaymentsData.forEach(payment => {
            selectedOutstandingPayments.add(payment.dr_no);
        });
    } else {
        selectedOutstandingPayments.clear();
    }
    
    updateSelectedCount();
    updateCheckboxStates();
}

// ✅ NEW: Update selected count display
function updateSelectedCount() {
    const count = selectedOutstandingPayments.size;
    document.getElementById('selected_count').textContent = count;
    
    const addSelectedBtn = document.getElementById('add_selected_btn');
    if (count > 0) {
        addSelectedBtn.classList.remove('hidden');
    } else {
        addSelectedBtn.classList.add('hidden');
    }
    
    const selectAllCheckbox = document.getElementById('select_all_checkbox');
    if (outstandingPaymentsData.length > 0) {
        selectAllCheckbox.checked = selectedOutstandingPayments.size === outstandingPaymentsData.length;
        selectAllCheckbox.indeterminate = selectedOutstandingPayments.size > 0 && selectedOutstandingPayments.size < outstandingPaymentsData.length;
    }
}

// ✅ NEW: Update checkbox states in table
function updateCheckboxStates() {
    const checkboxes = document.querySelectorAll('[data-payment-checkbox]');
    checkboxes.forEach(checkbox => {
        const drNo = checkbox.dataset.drNo;
        checkbox.checked = selectedOutstandingPayments.has(drNo);
        
        const row = checkbox.closest('tr');
        if (checkbox.checked) {
            row.classList.add('bg-blue-900', 'bg-opacity-20');
        } else {
            row.classList.remove('bg-blue-900', 'bg-opacity-20');
        }
    });
}

// ✅ NEW: Add selected outstanding payments to payment table
function addSelectedToPaymentTable() {
    if (selectedOutstandingPayments.size === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Selection',
            text: 'Please select at least one outstanding payment.',
            background: '#1f2937',
            color: '#fff'
        });
        return;
    }

    let addedCount = 0;
    
    outstandingPaymentsData.forEach(payment => {
        if (selectedOutstandingPayments.has(payment.dr_no)) {
            addPaymentRowWithData(payment);
            addedCount++;
        }
    });

    selectedOutstandingPayments.clear();
    updateSelectedCount();
    updateCheckboxStates();

    Swal.fire({
        icon: 'success',
        title: 'Added!',
        text: `${addedCount} payment(s) added to the entry table.`,
        background: '#1f2937',
        color: '#fff',
        timer: 2000,
        showConfirmButton: false
    });
}

// ✅ NEW: Add payment row with pre-filled data
    function addPaymentRowWithData(paymentData) {
    paymentRowCounter++;
    const tbody = document.getElementById('payment_entries_tbody');
    const today = new Date().toISOString().split('T')[0];
    
    const row = document.createElement('tr');
    row.className = 'border-b border-gray-700 bg-green-900 bg-opacity-10';
    row.id = `payment_row_${paymentRowCounter}`;
    row.innerHTML = `
        <td class="px-2 py-1.5 text-xs">${paymentRowCounter}</td>
        <td class="px-2 py-1.5">
            <input type="text" 
                   value="${paymentData.dr_no || ''}"
                   class="w-full bg-gray-700 text-white border border-gray-600 rounded px-2 py-1 text-xs" 
                   data-field="dr_number"
                   readonly>
        </td>
        <td class="px-2 py-1.5">
            <input type="text" 
                   value="${paymentData.invoice_no || ''}"
                   class="w-full bg-gray-600 text-gray-400 border border-gray-600 rounded px-2 py-1 text-xs" 
                   data-field="invoice_no"
                   readonly>
        </td>
        <td class="px-2 py-1.5">
            <input type="text" 
                   value="₱${parseFloat(paymentData.check_amount || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })}"
                   class="w-full bg-gray-600 text-orange-400 border border-gray-600 rounded px-2 py-1 text-xs text-right font-semibold" 
                   data-field="outstanding_balance"
                   readonly>
        </td>
        <td class="px-2 py-1.5">
            <input type="text" 
                   class="w-full bg-gray-700 text-white border border-gray-600 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500" 
                   placeholder="Receipt No." 
                   data-field="receipt_number">
        </td>
        <td class="px-2 py-1.5">
            <input type="date" 
                   value="${today}"
                   class="w-full bg-gray-700 text-white border border-gray-600 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500" 
                   data-field="receipt_date">
        </td>
        <td class="px-2 py-1.5">
            <input type="date" 
                   value="${today}"
                   class="w-full bg-gray-700 text-white border border-gray-600 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500" 
                   data-field="posting_date">
        </td>
        <td class="px-2 py-1.5">
            <button type="button" 
                    onclick="openPaymentMeansPanel(${paymentRowCounter})"
                    class="w-full bg-purple-600 hover:bg-purple-700 text-white px-2 py-1 rounded text-xs transition flex items-center justify-center space-x-1"
                    id="payment_means_btn_${paymentRowCounter}">
                <i class="fas fa-credit-card"></i>
                <span id="payment_means_label_${paymentRowCounter}">Set Payment Means</span>
            </button>
        </td>
        <td class="px-2 py-1.5">
            <input type="text"
                   value="₱${parseFloat(paymentData.check_amount || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })}"
                   class="w-full bg-gray-600 text-orange-400 border border-gray-600 rounded px-2 py-1 text-xs text-right focus:ring-1 focus:ring-blue-500 font-semibold"
                   data-field="amount"
                   data-row-id="${paymentRowCounter}"
                   readonly>
        </td>
        <td class="px-2 py-1.5">
            <input type="number"
                   step="0.01"
                   min="0"
                   class="w-full bg-gray-700 text-white border border-gray-600 rounded px-2 py-1 text-xs text-right focus:ring-1 focus:ring-blue-500"
                   placeholder="0.00"
                   data-field="tax">
        </td>
        <td class="px-2 py-1.5">
            <input type="text"
                   class="w-full bg-gray-600 text-green-400 border border-gray-600 rounded px-2 py-1 text-xs text-right font-semibold"
                   placeholder="₱0.00"
                   data-field="net"
                   readonly>
        </td>
        <td class="px-2 py-1.5">
            <input type="text"
                   class="w-full bg-gray-700 text-white border border-gray-600 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500"
                   placeholder="Notes"
                   data-field="notes">
        </td>
        <td class="px-2 py-1.5 text-center">
            <button type="button" 
                    onclick="deletePaymentRow(${paymentRowCounter})" 
                    class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-xs transition flex items-center space-x-1 mx-auto">
                <i class="fas fa-trash"></i>
                <span>Delete</span>
            </button>
        </td>
    `;

    tbody.appendChild(row);

    // ✅ Calculate net for pre-filled data if invoice number exists
    if (paymentData.invoice_no) {
        const grossAmount = parseFloat(paymentData.check_amount || 0);
        fetchCustomerTaxAndCalculateNet(paymentRowCounter, grossAmount);
    }

    updateOutstandingBalance();
}

// ✅ Toggle between views
document.getElementById('toggle_view_btn').addEventListener('click', function() {
    if (currentView === 'payment_list') {
        switchToPaymentEntries();
    } else {
        switchToPaymentList();
    }
});

function switchToPaymentEntries() {
    document.getElementById('payment_list_view').classList.add('hidden');
    document.getElementById('payment_entries_view').classList.remove('hidden');
    document.getElementById('no_results_message').classList.add('hidden');
    document.getElementById('toggle_btn_text').textContent = 'Payment List';
    currentView = 'payment_entries';
}

function switchToPaymentList() {
    document.getElementById('payment_list_view').classList.remove('hidden');
    document.getElementById('payment_entries_view').classList.add('hidden');
    document.getElementById('no_results_message').classList.add('hidden');
    document.getElementById('toggle_btn_text').textContent = 'Payment Entries';
    currentView = 'payment_list';
    loadPaymentList();
}

// Update outstanding balance display
function updateOutstandingBalance() {
    if (!currentCustomer) return;
    
    const rows = document.querySelectorAll('#payment_entries_tbody tr');
    let totalPayments = 0;
    
    rows.forEach(row => {
        const amountInput = row.querySelector('[data-field="amount"]');
        if (amountInput && amountInput.value) {
            // Remove ₱ symbol and commas before parsing
            const cleanAmount = amountInput.value.replace(/₱|,/g, '');
            totalPayments += parseFloat(cleanAmount) || 0;
        }
    });
    
    const newBalance = originalOutstandingBalance - totalPayments;
    const balanceElement = document.getElementById('display_outstanding_balance');
    
    balanceElement.textContent = '₱' + newBalance.toLocaleString('en-PH', { minimumFractionDigits: 2 });
    
    if (newBalance < 0) {
        balanceElement.classList.remove('text-red-400', 'text-yellow-400');
        balanceElement.classList.add('text-green-400');
    } else if (newBalance === 0) {
        balanceElement.classList.remove('text-red-400', 'text-green-400');
        balanceElement.classList.add('text-yellow-400');
    } else {
        balanceElement.classList.remove('text-green-400', 'text-yellow-400');
        balanceElement.classList.add('text-red-400');
    }
}

// ✅ Search customer - automatically switch to payment entries view
document.getElementById('search_customer_btn').addEventListener('click', function() {
    const searchValue = document.getElementById('customer_search').value.trim();
    
    if (searchValue === '') {
        Swal.fire({
            icon: 'warning',
            title: 'Empty Search',
            text: 'Please enter a customer name.',
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

    fetch(`/payments/search-customer?search=${encodeURIComponent(searchValue)}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        Swal.close();
        
        if (data.success) {
            currentCustomer = data.customer;
            originalOutstandingBalance = parseFloat(currentCustomer.outstanding_balance);
            
            // ✅ Store outstanding invoices for quick lookup
            outstandingInvoices = {};
            if (data.outstanding_invoices) {
                data.outstanding_invoices.forEach(invoice => {
                    if (invoice.dr_no) {
                        outstandingInvoices[invoice.dr_no.trim()] = {
                            invoice_no: invoice.invoice_no,
                            outstanding_balance: parseFloat(invoice.net_ar_balance || 0),
                            invoice_date: invoice.invoice_date,
                            due_date: invoice.due_date
                        };
                    }
                });
            }
            
            console.log('Outstanding invoices loaded:', outstandingInvoices);

            document.getElementById('display_customer_name').textContent = currentCustomer.name;
            document.getElementById('display_outstanding_balance').textContent = '₱' + parseFloat(currentCustomer.outstanding_balance).toLocaleString('en-PH', { minimumFractionDigits: 2 });

            const balanceElement = document.getElementById('display_outstanding_balance');
            balanceElement.classList.remove('text-green-400', 'text-yellow-400');
            balanceElement.classList.add('text-red-400');

            // Update customer profile link
            const profileLink = document.getElementById('customerProfileLink');
            if (currentCustomer.code) {
                profileLink.href = `/ar/customer/${currentCustomer.code}`;
                profileLink.classList.remove('hidden');
            } else {
                profileLink.classList.add('hidden');
            }

            loadCustomerPaymentHistory(currentCustomer.code, currentCustomer.name);
            switchToPaymentEntries();

            document.getElementById('payment_entries_tbody').innerHTML = '';
            paymentRowCounter = 0;
            addPaymentRow();

            // ✅ If search was for a specific DR number, auto-fill first payment row with that DR
            const searchValue = document.getElementById('customer_search').value.trim();
            if (searchValue && /^\d+$/.test(searchValue)) {
                console.log('🔍 Numeric search detected:', searchValue);
                console.log('📋 Available DRs in outstandingInvoices:', Object.keys(outstandingInvoices));

                if (outstandingInvoices[searchValue]) {
                    // Search was for a DR number and we found it - auto-fill the first row
                    const drInput = document.querySelector('[data-field="dr_number"]');
                    if (drInput) {
                        console.log('✅ Auto-filling DR field with:', searchValue);
                        drInput.value = searchValue;
                        drInput.dispatchEvent(new Event('change'));
                    }
                } else {
                    console.warn('⚠️ DR number searched but not in outstanding invoices:', searchValue);
                }
            }
        } else {
            document.getElementById('payment_list_view').classList.add('hidden');
            document.getElementById('payment_entries_view').classList.add('hidden');
            document.getElementById('no_results_message').classList.remove('hidden');
        }
    })
    .catch(error => {
        Swal.close();
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to search customer. Please try again.',
            background: '#1f2937',
            color: '#fff'
        });
    });
});

document.getElementById('customer_search').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('search_customer_btn').click();
    }
});

// ✅ UPDATED: Add DR number field and auto-populate invoice info
// ✅ IMPROVED: Add payment row with better delete button
function addPaymentRow() {
    paymentRowCounter++;
    const tbody = document.getElementById('payment_entries_tbody');
    const today = new Date().toISOString().split('T')[0];
    
    const row = document.createElement('tr');
    row.className = 'border-b border-gray-700 hover:bg-gray-750 transition';
    row.id = `payment_row_${paymentRowCounter}`;
    row.innerHTML = `
        <td class="px-2 py-1.5 text-xs text-gray-400">${paymentRowCounter}</td>
        <td class="px-2 py-1.5">
            <input type="text" 
                   class="w-full bg-gray-700 text-white border border-gray-600 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500" 
                   placeholder="DR Number" 
                   data-field="dr_number"
                   data-row-id="${paymentRowCounter}"
                   onchange="handleDRNumberChange(this)">
        </td>
        <td class="px-2 py-1.5">
            <input type="text" 
                   class="w-full bg-gray-600 text-gray-400 border border-gray-600 rounded px-2 py-1 text-xs" 
                   placeholder="Auto-filled" 
                   data-field="invoice_no"
                   readonly>
        </td>
        <td class="px-2 py-1.5">
            <input type="text" 
                   class="w-full bg-gray-600 text-orange-400 border border-gray-600 rounded px-2 py-1 text-xs text-right font-semibold" 
                   placeholder="₱0.00" 
                   data-field="outstanding_balance"
                   readonly>
        </td>
        <td class="px-2 py-1.5">
            <input type="text" 
                   class="w-full bg-gray-700 text-white border border-gray-600 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500" 
                   placeholder="Receipt No." 
                   data-field="receipt_number">
        </td>
        <td class="px-2 py-1.5">
            <input type="date" 
                   value="${today}"
                   class="w-full bg-gray-700 text-white border border-gray-600 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500" 
                   data-field="receipt_date">
        </td>
        <td class="px-2 py-1.5">
            <input type="date" 
                   value="${today}"
                   class="w-full bg-gray-700 text-white border border-gray-600 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500" 
                   data-field="posting_date">
        </td>
        <td class="px-2 py-1.5">
    <button type="button" 
            onclick="openPaymentMeansPanel(${paymentRowCounter})"
            class="w-full bg-purple-600 hover:bg-purple-700 text-white px-2 py-1 rounded text-xs transition flex items-center justify-center space-x-1"
            id="payment_means_btn_${paymentRowCounter}">
        <i class="fas fa-credit-card"></i>
        <span id="payment_means_label_${paymentRowCounter}">Set Payment Means</span>
    </button>
</td>
        <td class="px-2 py-1.5">
            <input type="text"
                   class="w-full bg-gray-600 text-orange-400 border border-gray-600 rounded px-2 py-1 text-xs text-right focus:ring-1 focus:ring-blue-500 font-semibold"
                   placeholder="₱0.00"
                   data-field="amount"
                   data-row-id="${paymentRowCounter}"
                   readonly>
        </td>
        <td class="px-2 py-1.5">
            <input type="number"
                   step="0.01"
                   min="0"
                   class="w-full bg-gray-700 text-white border border-gray-600 rounded px-2 py-1 text-xs text-right focus:ring-1 focus:ring-blue-500"
                   placeholder="0.00"
                   data-field="tax">
        </td>
        <td class="px-2 py-1.5">
            <input type="text"
                   class="w-full bg-gray-600 text-green-400 border border-gray-600 rounded px-2 py-1 text-xs text-right font-semibold"
                   placeholder="₱0.00"
                   data-field="net"
                   readonly>
        </td>
        <td class="px-2 py-1.5">
            <input type="text"
                   class="w-full bg-gray-700 text-white border border-gray-600 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500"
                   placeholder="Notes"
                   data-field="notes">
        </td>
        <td class="px-2 py-1.5 text-center">
            <button type="button" 
                    onclick="deletePaymentRow(${paymentRowCounter})" 
                    class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-xs transition flex items-center space-x-1 mx-auto"
                    title="Delete this row">
                <i class="fas fa-trash"></i>
                <span>Delete</span>
            </button>
        </td>
    `;
    
    tbody.appendChild(row);
}

// ✅ IMPROVED: Delete payment row with confirmation for multiple rows
// ✅ IMPROVED: Delete payment row with confirmation
function deletePaymentRow(rowId) {
    const row = document.getElementById(`payment_row_${rowId}`);
    if (!row) return;
    
    const rowCount = document.querySelectorAll('#payment_entries_tbody tr').length;
    
    // If this is the last row, show a warning
    if (rowCount === 1) {
        Swal.fire({
            icon: 'warning',
            title: 'Cannot Delete',
            text: 'You must have at least one payment row. Add another row before deleting this one.',
            background: '#1f2937',
            color: '#fff'
        });
        return;
    }
    
    // For multiple rows, confirm deletion
    Swal.fire({
        icon: 'question',
        title: 'Delete Row?',
        text: 'Are you sure you want to delete this payment entry?',
        background: '#1f2937',
        color: '#fff',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            row.remove();
            updateOutstandingBalance();
            
            Swal.fire({
                icon: 'success',
                title: 'Deleted!',
                text: 'Payment row has been removed.',
                background: '#1f2937',
                color: '#fff',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });
}

// ✅ NEW: Handle DR number input change
function handleDRNumberChange(input) {
    const rowId = input.dataset.rowId;
    const row = document.getElementById(`payment_row_${rowId}`);
    const drNumber = input.value.trim();
    
    if (!drNumber) {
        // Clear auto-filled fields
        row.querySelector('[data-field="invoice_no"]').value = '';
        row.querySelector('[data-field="outstanding_balance"]').value = '';
        row.querySelector('[data-field="amount"]').value = ''; // ✅ Clear amount
        row.querySelector('[data-field="tax"]').value = ''; // ✅ Clear auto-populated tax
        row.querySelector('[data-field="net"]').value = '';
        return;
    }
    
    console.log('DR Number entered:', drNumber);
    console.log('Looking up in:', outstandingInvoices);
    
    // Look up invoice info
    const invoiceInfo = outstandingInvoices[drNumber];
    
    if (invoiceInfo) {
        // Auto-fill invoice number and outstanding balance
        row.querySelector('[data-field="invoice_no"]').value = invoiceInfo.invoice_no || '';
        row.querySelector('[data-field="outstanding_balance"]').value = '₱' + invoiceInfo.outstanding_balance.toLocaleString('en-PH', { minimumFractionDigits: 2 });

        // Auto-fill amount with outstanding balance (formatted with currency symbol)
        const amountInput = row.querySelector('[data-field="amount"]');
        amountInput.value = '₱' + invoiceInfo.outstanding_balance.toLocaleString('en-PH', { minimumFractionDigits: 2 });

        // ✅ NEW: Fetch customer tax rate and calculate net
        fetchCustomerTaxAndCalculateNet(rowId, invoiceInfo.outstanding_balance);

        // Highlight row as valid
        row.classList.remove('bg-red-900', 'bg-opacity-20');
        row.classList.add('bg-green-900', 'bg-opacity-10');

        updateOutstandingBalance();
        
        Swal.fire({
            icon: 'success',
            title: 'DR Found!',
            html: `<div class="text-left">
                <p><strong>Invoice:</strong> ${invoiceInfo.invoice_no}</p>
                <p><strong>Outstanding:</strong> ₱${invoiceInfo.outstanding_balance.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</p>
            </div>`,
            background: '#1f2937',
            color: '#fff',
            timer: 3000,
            showConfirmButton: false
        });
    } else {
        // DR not found
        row.querySelector('[data-field="invoice_no"]').value = '';
        row.querySelector('[data-field="outstanding_balance"]').value = '';
        row.classList.remove('bg-green-900', 'bg-opacity-10');
        row.classList.add('bg-red-900', 'bg-opacity-20');
        
        Swal.fire({
            icon: 'warning',
            title: 'DR Not Found',
            text: `DR Number "${drNumber}" not found in outstanding invoices for this customer.`,
            background: '#1f2937',
            color: '#fff'
        });
    }
}

// ✅ NEW: Handle payment amount change with validation
function handlePaymentAmountChange(input) {
    const rowId = input.dataset.rowId;
    const row = document.getElementById(`payment_row_${rowId}`);
    const amount = parseFloat(input.value) || 0;
    
    // Get outstanding balance from the row
    const outstandingText = row.querySelector('[data-field="outstanding_balance"]').value;
    const outstanding = parseFloat(outstandingText.replace(/[₱,]/g, '')) || 0;
    
    if (outstanding > 0 && amount > outstanding) {
        Swal.fire({
            icon: 'warning',
            title: 'Amount Exceeds Outstanding',
            text: `Payment amount (₱${amount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}) exceeds outstanding balance (₱${outstanding.toLocaleString('en-PH', { minimumFractionDigits: 2 })})`,
            background: '#1f2937',
            color: '#fff'
        });
        input.classList.add('border-orange-500');
    } else {
        input.classList.remove('border-orange-500');
    }
    
    updateOutstandingBalance();
}

function deletePaymentRow(rowId) {
    const row = document.getElementById(`payment_row_${rowId}`);
    if (row) {
        row.remove();
        updateOutstandingBalance();
    }
}

// ✅ UPDATED: Save all payments with DR number
function saveAllPayments() {
    if (!currentCustomer) {
        Swal.fire({
            icon: 'error',
            title: 'No Customer Selected',
            text: 'Please search for a customer first.',
            background: '#1f2937',
            color: '#fff'
        });
        return;
    }

    const rows = document.querySelectorAll('#payment_entries_tbody tr');
    const payments = [];
    let hasErrors = false;

    // ✅ STEP 1: Validate ALL rows first (don't collect data yet)
    rows.forEach((row, index) => {
        const dr_number = row.querySelector('[data-field="dr_number"]')?.value;
        const receipt_number = row.querySelector('[data-field="receipt_number"]')?.value;
        const receipt_date = row.querySelector('[data-field="receipt_date"]')?.value;
        const posting_date = row.querySelector('[data-field="posting_date"]')?.value;
        const amount = row.querySelector('[data-field="amount"]')?.value;

        // Get the actual row ID from the row element
        const rowId = parseInt(row.id.replace('payment_row_', ''));

        // Validate required fields
        // Remove ₱ symbol and commas before parsing
        const cleanAmount = amount ? parseFloat(amount.toString().replace(/₱|,/g, '')) : 0;
        if (!dr_number || !receipt_number || !receipt_date || !posting_date || !amount || cleanAmount <= 0) {
            hasErrors = true;
            row.classList.add('bg-red-900', 'bg-opacity-20');
        } else if (!paymentMeansData[rowId]) {
            // Check if payment means is set
            hasErrors = true;
            row.classList.add('bg-red-900', 'bg-opacity-20');
        } else {
            // If validation passed, remove error styling
            row.classList.remove('bg-red-900', 'bg-opacity-20');
        }
    });

    // ✅ If ANY errors found, stop here and show error
    if (hasErrors) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please fill in all required fields and set payment means for all rows (highlighted in red).',
            background: '#1f2937',
            color: '#fff'
        });
        return;
    }

    // ✅ STEP 2: If ALL rows are valid, collect the payment data
    rows.forEach((row, index) => {
        const dr_number = row.querySelector('[data-field="dr_number"]')?.value;
        const invoice_no = row.querySelector('[data-field="invoice_no"]')?.value;
        const receipt_number = row.querySelector('[data-field="receipt_number"]')?.value;
        const receipt_date = row.querySelector('[data-field="receipt_date"]')?.value;
        const posting_date = row.querySelector('[data-field="posting_date"]')?.value;
        const amount = row.querySelector('[data-field="amount"]')?.value;
        const tax = row.querySelector('[data-field="tax"]')?.value;
        const net = row.querySelector('[data-field="net"]')?.value; // ✅ NEW: Get net value
        const notes = row.querySelector('[data-field="notes"]')?.value;

        // Get the actual row ID from the row element
        const rowId = parseInt(row.id.replace('payment_row_', ''));

        // Add to payments array (we already know all rows are valid)
        // Remove ₱ symbol and commas before parsing
        const cleanAmountValue = parseFloat(amount.toString().replace(/₱|,/g, '')) || 0;
        const cleanTaxValue = tax ? parseFloat(tax.toString().replace(/₱|,/g, '')) : 0;
        const cleanNetValue = net ? parseFloat(net.toString().replace(/₱|,/g, '')) : 0;

        payments.push({
            customer_code: currentCustomer.code,
            customer_name: currentCustomer.name,
            dr_number: dr_number,
            invoice_no: invoice_no,
            collection_receipt_number: receipt_number,
            collection_receipt_date: receipt_date,
            payment_posting_date: posting_date,
            payment_means: paymentMeansData[rowId],
            amount: cleanAmountValue,
            tax: cleanTaxValue,
            net: cleanNetValue, // ✅ NEW: Include net amount
            payment_notes: notes
        });
    });

    if (payments.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Payments',
            text: 'Please add at least one payment entry.',
            background: '#1f2937',
            color: '#fff'
        });
        return;
    }

    Swal.fire({
        title: 'Saving...',
        text: 'Processing payment entries',
        background: '#1f2937',
        color: '#fff',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    Promise.all(payments.map(payment => 
        fetch('/payments/store', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(payment)
        }).then(response => response.json())
    ))
    .then(results => {
        const successCount = results.filter(r => r.success).length;
        const failCount = results.length - successCount;

        if (failCount === 0) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: `${successCount} payment(s) saved successfully. Outstanding balances updated!`,
                background: '#1f2937',
                color: '#fff'
            }).then(() => {
                // Refresh the page to show updated balances
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Partial Success',
                text: `${successCount} succeeded, ${failCount} failed. Please check and try again.`,
                background: '#1f2937',
                color: '#fff'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to save payments. Please try again.',
            background: '#1f2937',
            color: '#fff'
        });
    });
}

// Load payment list
function loadPaymentList() {
    const dateFrom = document.getElementById('report_date_from').value;
    const dateTo = document.getElementById('report_date_to').value;
    const customerFilter = document.getElementById('report_customer_filter').value;
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    
    fetch(`/payments/collection-report?date_from=${dateFrom}&date_to=${dateTo}&customer=${customerFilter}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const tbody = document.getElementById('payment_list_tbody');
            tbody.innerHTML = '';
            
            if (!data.payments || data.payments.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-400">
                            <i class="fas fa-file-invoice text-4xl mb-2"></i>
                            <p>No payment data available</p>
                        </td>
                    </tr>
                `;
                return;
            }
            
            data.payments.forEach(payment => {
    const row = document.createElement('tr');
    row.className = 'border-b border-gray-700 hover:bg-gray-750';
    
    // ✅ Handle BOTH old (payment_option) and new (payment_method) data
    let paymentMeansDisplay = '—';
    let paymentMeansBadgeClass = 'bg-gray-600 text-gray-300';
    
    // Check for new payment_method field first
    if (payment.payment_method) {
        const typeLabels = {
            'check': 'Check',
            'bank_transfer': 'Bank Transfer',
            'cash': 'Cash'
        };
        paymentMeansDisplay = typeLabels[payment.payment_method] || payment.payment_method;
        
        if (payment.payment_method === 'check') {
            paymentMeansBadgeClass = 'bg-blue-900/30 text-blue-400 border border-blue-700';
        } else if (payment.payment_method === 'bank_transfer') {
            paymentMeansBadgeClass = 'bg-green-900/30 text-green-400 border border-green-700';
        } else if (payment.payment_method === 'cash') {
            paymentMeansBadgeClass = 'bg-yellow-900/30 text-yellow-400 border border-yellow-700';
        }
    } 
    // Fallback to old payment_option field for legacy data
    else if (payment.payment_option) {
        paymentMeansDisplay = payment.payment_option;
        paymentMeansBadgeClass = 'bg-purple-900/30 text-purple-400 border border-purple-700';
    }
    
    row.innerHTML = `
        <td class="px-4 py-3">${payment.customer_name || '—'}</td>
        <td class="px-4 py-3">${payment.collection_receipt_number || '—'}</td>
        <td class="px-4 py-3">${payment.collection_receipt_date ? new Date(payment.collection_receipt_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'}</td>
        <td class="px-4 py-3">${payment.payment_posting_date ? new Date(payment.payment_posting_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'}</td>
        <td class="px-4 py-3">
            <span class="px-2 py-1 rounded text-xs font-semibold ${paymentMeansBadgeClass}">
                ${paymentMeansDisplay}
            </span>
        </td>
        <td class="px-4 py-3 text-right font-semibold">₱${(payment.amount && !isNaN(payment.amount)) ? parseFloat(payment.amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) : '0.00'}</td>
        <td class="px-4 py-3 text-right">${(payment.tax && !isNaN(payment.tax)) ? '₱' + parseFloat(payment.tax).toLocaleString('en-PH', { minimumFractionDigits: 2 }) : '—'}</td>
    `;
    tbody.appendChild(row);
});
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function filterPaymentList() {
    loadPaymentList();
}

function viewAllPayments() {
    document.getElementById('report_date_from').value = '';
    document.getElementById('report_date_to').value = '';
    document.getElementById('report_customer_filter').value = '';
    loadPaymentList();
}

function exportPaymentList() {
    const dateFrom = document.getElementById('report_date_from').value;
    const dateTo = document.getElementById('report_date_to').value;
    const customerFilter = document.getElementById('report_customer_filter').value;
    
    Swal.fire({
        icon: 'info',
        title: 'Export Started',
        text: 'Payment list will be downloaded shortly.',
        background: '#1f2937',
        color: '#fff',
        timer: 2000,
        showConfirmButton: false
    });
    
    window.location.href = `/payments/export?date_from=${dateFrom}&date_to=${dateTo}&customer=${customerFilter}`;
}

// ✅ Load OUTSTANDING payments for a specific customer
function loadCustomerPaymentHistory(customerCode, customerName) {
    document.getElementById('history_customer_name').textContent = customerName;

    // Reset selections
    selectedOutstandingPayments.clear();
    outstandingPaymentsData = [];
    updateSelectedCount();

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    fetch(`/payments/customer-history?customer_code=${encodeURIComponent(customerCode)}&status=outstanding`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        console.log('API Response:', data); // ✅ ADD THIS
    console.log('First payment:', data.payments[0]); // ✅ ADD THIS
        const tbody = document.getElementById('customer_payment_history_tbody');

        if (!data.success || !data.payments || data.payments.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="19" class="px-4 py-6 text-center text-gray-400">
                        <i class="fas fa-check-circle text-3xl mb-2 text-green-500"></i>
                        <p>No outstanding payments found for this customer</p>
                        <p class="text-xs mt-1">All invoices have been paid!</p>
                    </td>
                </tr>
            `;
            return;
        }

        // ✅ Store payment data for checkbox functionality
        outstandingPaymentsData = data.payments;

        tbody.innerHTML = '';
        
        let totalOutstanding = 0;
        
        data.payments.forEach(payment => {
            const row = document.createElement('tr');
            row.className = 'border-b border-gray-700 hover:bg-gray-750 cursor-pointer transition';
            
            // ✅ Add click handler to row for checkbox toggle
            row.onclick = (e) => {
                if (e.target.type !== 'checkbox') {
                    toggleOutstandingPayment(payment.dr_no);
                }
            };

            const depositDate = payment.deposit_date ? new Date(payment.deposit_date).toLocaleDateString('en-US') : 'N/A';
            const grossAmount = parseFloat(payment.gross_amount || 0);
            const ewt = parseFloat(payment.ewt || 0);
            const otherAdj = parseFloat(payment.other_adjustment || 0);
            const factoring = parseFloat(payment.factoring || 0);
            const checkAmount = parseFloat(payment.check_amount || 0);
            const netOfCwt = parseFloat(payment.net_of_cwt || 0);
            
            totalOutstanding += checkAmount;

            let otherAdjDisplay = '—';
            let otherAdjClass = 'text-yellow-400';
            if (otherAdj !== 0) {
                otherAdjClass = otherAdj > 0 ? 'text-green-400' : 'text-red-400';
                otherAdjDisplay = (otherAdj > 0 ? '+' : '') + '₱' + Math.abs(otherAdj).toLocaleString('en-PH', { minimumFractionDigits: 2 });
            }

            row.innerHTML = `
                <td class="px-2 py-2" onclick="event.stopPropagation()">
                    <input type="checkbox" 
                           data-payment-checkbox 
                           data-dr-no="${payment.dr_no}"
                           onchange="toggleOutstandingPayment('${payment.dr_no}')"
                           class="w-4 h-4 rounded bg-gray-700 border-gray-600 text-blue-600 focus:ring-blue-500 cursor-pointer">
                </td>
                <td class="px-2 py-2">${depositDate}</td>
                <td class="px-2 py-2"><span class="bg-orange-900/30 border border-orange-700/50 px-2 py-1 rounded font-mono text-xs">${payment.collection_receipt_number || '—'}</span></td>
                <td class="px-2 py-2">${payment.invoice_no || '—'}</td>
                <td class="px-2 py-2 font-semibold text-blue-300">${payment.dr_no || '—'}</td>
                <td class="px-2 py-2 text-xs">${payment.customer_name || '—'}</td>
                <td class="px-2 py-2 text-xs">${payment.branch || 'N/A'}</td>
                <td class="px-2 py-2 text-right font-semibold text-orange-400">₱${grossAmount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                <td class="px-2 py-2 text-right text-orange-400">₱${ewt.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                <td class="px-2 py-2 text-right ${otherAdjClass}">${otherAdjDisplay}</td>
                <td class="px-2 py-2 text-right ${factoring !== 0 ? 'text-purple-400' : ''}">—</td>
                <td class="px-2 py-2 text-right font-semibold text-green-400">₱${checkAmount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                <td class="px-2 py-2 text-right">—</td>
                <td class="px-2 py-2">${payment.week_no || '—'}</td>
                <td class="px-2 py-2">${payment.ar_class || '—'}</td>
                <td class="px-2 py-2">${payment.bank || 'BDO'}</td>
                <td class="px-2 py-2">${payment.checking_si || 'OK'}</td>
                <td class="px-2 py-2"><span class="bg-orange-600/30 text-orange-300 px-2 py-1 rounded text-xs">${payment.status || 'Outstanding'}</span></td>
                <td class="px-2 py-2 text-xs">${payment.remarks || 'N/A'}</td>
            `;
            tbody.appendChild(row);
        });
        
        // Add total outstanding row
        const totalRow = document.createElement('tr');
        totalRow.className = 'bg-orange-900/20 border-t-2 border-orange-600 font-bold';
        totalRow.innerHTML = `
            <td></td>
            <td colspan="10" class="px-2 py-3 text-right text-white">TOTAL OUTSTANDING:</td>
            <td class="px-2 py-3 text-right text-orange-400 font-bold text-base">₱${totalOutstanding.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
            <td colspan="7"></td>
        `;
        tbody.appendChild(totalRow);
    })
    .catch(error => {
        console.error('Error loading outstanding payments:', error);
        const tbody = document.getElementById('customer_payment_history_tbody');
        tbody.innerHTML = `
            <tr>
                <td colspan="19" class="px-4 py-6 text-center text-red-400">
                    <i class="fas fa-exclamation-triangle text-3xl mb-2"></i>
                    <p>Failed to load outstanding payments</p>
                </td>
            </tr>
        `;
    });
}

function togglePaymentHistory() {
    const content = document.getElementById('payment_history_content');
    const icon = document.getElementById('history_toggle_icon');

    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.classList.remove('fa-chevron-right');
        icon.classList.add('fa-chevron-down');
    } else {
        content.style.display = 'none';
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-right');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    loadPaymentList();
});

function openPaymentMeansPanel(rowId) {
    currentEditingRowId = rowId;
    const row = document.getElementById(`payment_row_${rowId}`);
    
    if (!row) return;
    
    // Update panel header info
    document.getElementById('panel_row_number').textContent = `#${rowId}`;
    const drNumber = row.querySelector('[data-field="dr_number"]').value;
    document.getElementById('panel_dr_number').textContent = drNumber || '—';
    
    // Load existing payment means data if available
    if (paymentMeansData[rowId]) {
        const data = paymentMeansData[rowId];
        document.getElementById('panel_payment_type').value = data.type;
        updatePaymentMeansFields();
        
        if (data.type === 'check') {
            document.getElementById('check_gl_account').value = data.gl_account || '';
            document.getElementById('check_due_date').value = data.due_date || '';
            document.getElementById('check_amount').value = data.amount || '';
            document.getElementById('check_bank_name').value = data.bank_name || '';
            document.getElementById('check_number').value = data.check_number || '';
        } else if (data.type === 'bank_transfer') {
            document.getElementById('transfer_gl_account').value = data.gl_account || '';
            document.getElementById('transfer_date').value = data.transfer_date || '';
            document.getElementById('transfer_reference').value = data.reference || '';
            document.getElementById('transfer_amount').value = data.amount || '';
        } else if (data.type === 'cash') {
            document.getElementById('cash_gl_account').value = data.gl_account || '';
            document.getElementById('cash_amount').value = data.amount || '';
        }
    } else {
        // Reset form
        document.getElementById('panel_payment_type').value = '';
        updatePaymentMeansFields();
    }
    
    // Show panel and overlay
    document.getElementById('payment_means_panel').classList.remove('translate-x-full');
    document.getElementById('panel_overlay').classList.remove('hidden');
}

// ✅ NEW: Close payment means panel
function closePaymentMeansPanel() {
    document.getElementById('payment_means_panel').classList.add('translate-x-full');
    document.getElementById('panel_overlay').classList.add('hidden');
    currentEditingRowId = null;
}

// ✅ NEW: Update payment means fields based on type
function updatePaymentMeansFields() {
    const paymentType = document.getElementById('panel_payment_type').value;
    
    // Hide all field groups
    document.getElementById('check_fields').classList.add('hidden');
    document.getElementById('bank_transfer_fields').classList.add('hidden');
    document.getElementById('cash_fields').classList.add('hidden');
    
    // Show selected field group
    if (paymentType === 'check') {
        document.getElementById('check_fields').classList.remove('hidden');
        // Set default due date to today
        if (!document.getElementById('check_due_date').value) {
            document.getElementById('check_due_date').value = new Date().toISOString().split('T')[0];
        }
    } else if (paymentType === 'bank_transfer') {
        document.getElementById('bank_transfer_fields').classList.remove('hidden');
        // Set default transfer date to today
        if (!document.getElementById('transfer_date').value) {
            document.getElementById('transfer_date').value = new Date().toISOString().split('T')[0];
        }
    } else if (paymentType === 'cash') {
        document.getElementById('cash_fields').classList.remove('hidden');
    }
}

// ✅ NEW: Save payment means data
function savePaymentMeans() {
    if (!currentEditingRowId) return;
    
    const paymentType = document.getElementById('panel_payment_type').value;
    
    if (!paymentType) {
        Swal.fire({
            icon: 'warning',
            title: 'Payment Type Required',
            text: 'Please select a payment means type.',
            background: '#1f2937',
            color: '#fff'
        });
        return;
    }
    
    let paymentData = { type: paymentType };
    let isValid = true;
    let errorMessage = '';
    
    // Validate and collect data based on payment type
    if (paymentType === 'check') {
        paymentData.gl_account = document.getElementById('check_gl_account').value;
        paymentData.due_date = document.getElementById('check_due_date').value;
        paymentData.amount = document.getElementById('check_amount').value;
        paymentData.bank_name = document.getElementById('check_bank_name').value;
        paymentData.check_number = document.getElementById('check_number').value;
        
        if (!paymentData.gl_account || !paymentData.due_date || !paymentData.amount || !paymentData.bank_name || !paymentData.check_number) {
            isValid = false;
            errorMessage = 'Please fill in all required check fields.';
        }
    } else if (paymentType === 'bank_transfer') {
        paymentData.gl_account = document.getElementById('transfer_gl_account').value;
        paymentData.transfer_date = document.getElementById('transfer_date').value;
        paymentData.reference = document.getElementById('transfer_reference').value;
        paymentData.amount = document.getElementById('transfer_amount').value;
        
        if (!paymentData.gl_account || !paymentData.transfer_date || !paymentData.amount) {
            isValid = false;
            errorMessage = 'Please fill in all required bank transfer fields.';
        }
    } else if (paymentType === 'cash') {
        paymentData.gl_account = document.getElementById('cash_gl_account').value;
        paymentData.amount = document.getElementById('cash_amount').value;
        
        if (!paymentData.gl_account || !paymentData.amount) {
            isValid = false;
            errorMessage = 'Please fill in all required cash fields.';
        }
    }
    
    if (!isValid) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: errorMessage,
            background: '#1f2937',
            color: '#fff'
        });
        return;
    }
    
    // Store payment means data
    paymentMeansData[currentEditingRowId] = paymentData;
    
    // Update button label to show payment type
    const typeLabels = {
        'check': 'Check',
        'bank_transfer': 'Bank Transfer',
        'cash': 'Cash'
    };
    
    const label = document.getElementById(`payment_means_label_${currentEditingRowId}`);
    const btn = document.getElementById(`payment_means_btn_${currentEditingRowId}`);
    
    if (label && btn) {
        label.textContent = typeLabels[paymentType];
        btn.classList.remove('bg-purple-600', 'hover:bg-purple-700');
        btn.classList.add('bg-green-600', 'hover:bg-green-700');
    }
    
    // Update amount field in row if needed
    const row = document.getElementById(`payment_row_${currentEditingRowId}`);
    const amountInput = row.querySelector('[data-field="amount"]');
    if (amountInput && !amountInput.value) {
        // Format with currency symbol and thousands separator
        const formattedAmount = '₱' + parseFloat(paymentData.amount).toLocaleString('en-PH', { minimumFractionDigits: 2 });
        amountInput.value = formattedAmount;
        updateOutstandingBalance();
    }
    
    closePaymentMeansPanel();
    
    Swal.fire({
        icon: 'success',
        title: 'Saved!',
        text: 'Payment means details have been saved.',
        background: '#1f2937',
        color: '#fff',
        timer: 1500,
        showConfirmButton: false
    });
}

// ✅ NEW: Copy balance due helpers
function copyBalanceDueToCheck() {
    if (!currentEditingRowId) return;
    const row = document.getElementById(`payment_row_${currentEditingRowId}`);
    const balanceText = row.querySelector('[data-field="outstanding_balance"]').value;
    const balance = parseFloat(balanceText.replace(/[₱,]/g, '')) || 0;
    document.getElementById('check_amount').value = balance.toFixed(2);
}

function copyBalanceDueToTransfer() {
    if (!currentEditingRowId) return;
    const row = document.getElementById(`payment_row_${currentEditingRowId}`);
    const balanceText = row.querySelector('[data-field="outstanding_balance"]').value;
    const balance = parseFloat(balanceText.replace(/[₱,]/g, '')) || 0;
    document.getElementById('transfer_amount').value = balance.toFixed(2);
}

function copyBalanceDueToCash() {
    if (!currentEditingRowId) return;
    const row = document.getElementById(`payment_row_${currentEditingRowId}`);
    const balanceText = row.querySelector('[data-field="outstanding_balance"]').value;
    const balance = parseFloat(balanceText.replace(/[₱,]/g, '')) || 0;
    document.getElementById('cash_amount').value = balance.toFixed(2);
}

// ✅ NEW: Auto-populate tax and calculate net amount based on customer's tax rate (whtrate)
function fetchCustomerTaxAndCalculateNet(rowId, grossAmount) {
    const row = document.getElementById(`payment_row_${rowId}`);
    const invoiceNo = row.querySelector('[data-field="invoice_no"]').value;

    // Only calculate if there's an invoice number
    if (!invoiceNo) {
        row.querySelector('[data-field="tax"]').value = '';
        row.querySelector('[data-field="net"]').value = '';
        return;
    }

    // Use customer WHT rate from currentCustomer global variable
    if (currentCustomer && currentCustomer.whtrate) {
        const whtrate = parseFloat(currentCustomer.whtrate) || 0;
        const netAmount = grossAmount - (grossAmount * (whtrate / 100));

        // ✅ Auto-populate the Tax field with customer's WHT rate percentage (from customers table)
        row.querySelector('[data-field="tax"]').value = whtrate.toFixed(2);

        // ✅ Calculate and set Net = Gross - (Gross × Tax Rate %) - formatted with currency symbol
        row.querySelector('[data-field="net"]').value = '₱' + netAmount.toLocaleString('en-PH', { minimumFractionDigits: 2 });

        console.log(`✅ Tax and Net auto-calculated: Gross=₱${grossAmount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}, Tax Rate=${whtrate}%, Net=₱${netAmount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}`);
    } else {
        console.warn('Customer data not available or no tax rate set');
        row.querySelector('[data-field="tax"]').value = '';
        row.querySelector('[data-field="net"]').value = '₱' + grossAmount.toLocaleString('en-PH', { minimumFractionDigits: 2 });
    }
}

// GL Account search for Check Payment
setupGlAccountSearch('check_gl_account_search', 'check_gl_account_dropdown', 'check_gl_account_id', 'check_gl_account');

// GL Account search for Bank Transfer
setupGlAccountSearch('transfer_gl_account_search', 'transfer_gl_account_dropdown', 'transfer_gl_account_id', 'transfer_gl_account');

// GL Account search for Cash Payment
setupGlAccountSearch('cash_gl_account_search', 'cash_gl_account_dropdown', 'cash_gl_account_id', 'cash_gl_account');

function setupGlAccountSearch(searchId, dropdownId, idInputId, codeInputId) {
    const searchInput = document.getElementById(searchId);
    const dropdown = document.getElementById(dropdownId);
    const idInput = document.getElementById(idInputId);
    const codeInput = document.getElementById(codeInputId);
    let searchTimeout;

    if (!searchInput) return;

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
                        <div class="px-3 py-2 hover:bg-purple-700 cursor-pointer text-white" onclick="selectPaymentGlAccount('${searchId}', '${dropdownId}', '${idInputId}', '${codeInputId}', ${account.id}, '${account.display.replace(/'/g, "\\'")}', '${(account.code || '').replace(/'/g, "\\'")}')" >
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

function selectPaymentGlAccount(searchId, dropdownId, idInputId, codeInputId, id, display, code) {
    document.getElementById(idInputId).value = id;
    document.getElementById(codeInputId).value = code;
    document.getElementById(searchId).value = display;
    document.getElementById(dropdownId).classList.add('hidden');
}
</script>
@endsection