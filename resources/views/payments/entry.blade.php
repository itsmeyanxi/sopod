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
            <p class="text-gray-500 text-sm mb-3">Search by: Customer Name, Customer Code, or DR Number</p>
            <div class="flex items-center space-x-4">
                <div class="flex-1">
                    <input type="text" id="customer_search" placeholder="Enter Customer Name, Code, or DR Number"
                           class="w-full bg-gray-200 text-white border border-gray-600 rounded px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                        <label class="block text-sm font-medium text-gray-500 mb-2">Date From</label>
                        <input type="date" id="report_date_from" class="w-full bg-gray-200 text-white border border-gray-600 rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-2">Date To</label>
                        <input type="date" id="report_date_to" class="w-full bg-gray-200 text-white border border-gray-600 rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-2">Customer</label>
                        <input type="text" id="report_customer_filter" placeholder="Filter by customer" class="w-full bg-gray-200 text-white border border-gray-600 rounded px-3 py-2">
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
                <p class="text-gray-500 text-xs mt-2 px-2">💡 Leave dates empty to show all records, or set specific date range to filter.</p>
            </div>

            <!-- Payment List Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full bg-gray-800 rounded-lg text-sm">
                    <thead>
                        <tr class="bg-gray-900 text-gray-500">
                            <th class="px-4 py-3 text-left">Customer Name</th>
                            <th class="px-4 py-3 text-left">CR Number</th>
                            <th class="px-4 py-3 text-left">DR No</th>
                            <th class="px-4 py-3 text-left">Invoice No</th>
                            <th class="px-4 py-3 text-left">CR Date</th>
                            <th class="px-4 py-3 text-left">Posting Date</th>
                            <th class="px-4 py-3 text-left">Payment Means</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                            <th class="px-4 py-3 text-right">EWT</th>
                            <th class="px-4 py-3 text-right">Net</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="payment_list_tbody" class="text-gray-500">
                        <tr>
                            <td colspan="12" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-spinner fa-spin text-4xl mb-2"></i>
                                <p>Loading collections...</p>
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
            <i class="fas fa-file-invoice-dollar mr-2 text-orange-700"></i>
            Outstanding Payments for <span id="history_customer_name" class="ml-2 text-blue-700"></span>
        </h4>
        <div class="flex items-center space-x-2">
            <!-- ✅ NEW: Add Selected Button -->
            <button type="button" id="add_selected_btn" onclick="addSelectedToPaymentTable()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm flex items-center space-x-2 hidden transition">
                <i class="fas fa-plus"></i>
                <span>Add Selected (<span id="selected_count">0</span>)</span>
            </button>
            <button type="button" onclick="togglePaymentHistory()" class="text-gray-500 hover:text-white text-xs">
                <i class="fas fa-chevron-down" id="history_toggle_icon"></i>
            </button>
        </div>
    </div>

    <div id="payment_history_content" class="overflow-x-auto" style="max-height: 300px;">
        <table class="min-w-full bg-gray-800 rounded-lg text-xs">
            <thead class="bg-gray-900 text-gray-500 sticky top-0">
                <tr>
                    <!-- ✅ NEW: Select All Checkbox -->
                    <th class="px-2 py-2 text-left w-12">
                        <input type="checkbox" 
                               id="select_all_checkbox" 
                               onchange="toggleSelectAll()" 
                               class="w-4 h-4 rounded bg-gray-700 border-gray-600 text-blue-600 focus:ring-blue-500 cursor-pointer"
                               title="Select All">
                    </th>
                    <th class="px-2 py-2 text-left">Invoice Date</th>
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
            <tbody id="customer_payment_history_tbody" class="text-gray-500">
                <tr>
                    <td colspan="19" class="px-4 py-8 text-center text-gray-500">
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
                        <label class="block text-gray-500 text-xs mb-1">Customer Name</label>
                        <p class="text-white font-semibold" id="display_customer_name">—</p>
                    </div>
                    <div>
                        <label class="block text-gray-500 text-xs mb-1">Outstanding Balance</label>
                        <p class="text-red-700 font-bold text-lg" id="display_outstanding_balance">₱0.00</p>
                    </div>
                    <div id="credit_balance_container" class="hidden">
                        <label class="block text-gray-500 text-xs mb-1">Available Credit Balance</label>
                        <p class="text-purple-700 font-bold text-lg" id="display_credit_balance">₱0.00</p>
                        <button type="button" onclick="showCreditDetails()" class="text-purple-600 hover:text-purple-800 text-xs mt-1">
                            <i class="fas fa-info-circle mr-1"></i>View Credits
                        </button>
                    </div>
                </div>
            </div>

            <!-- Credit Balance Details (collapsible) -->
            <div id="credit_details_panel" class="hidden bg-purple-50 border border-purple-200 rounded-lg p-3 mt-3 mx-4 mb-4">
                <div class="flex justify-between items-center mb-2">
                    <h5 class="text-xs font-bold text-purple-700 uppercase">Available Credits from Overpayments</h5>
                    <button type="button" onclick="hideCreditDetails()" class="text-gray-400 hover:text-gray-300 text-xs"><i class="fas fa-times"></i></button>
                </div>
                <div id="credit_details_list" class="space-y-2 text-sm"></div>
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
            <thead class="bg-gray-900 text-gray-500 sticky top-0">
                <tr>
                    <th class="px-2 py-2 text-left w-8">#</th>
                    <th class="px-2 py-2 text-left">DR Number *</th>
                    <th class="px-2 py-2 text-left">Invoice No</th>
                    <th class="px-2 py-2 text-right">Outstanding Balance</th>
                    <th class="px-2 py-2 text-center">Payment Type</th>
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
            <tfoot id="payment_totals_row" class="hidden">
                <tr class="bg-blue-50 border-t-2 border-blue-300 font-bold text-xs">
                    <td colspan="9" class="px-2 py-2 text-right">
                        <span class="text-gray-200 pr-2">TOTAL</span>
                    </td>
                    <td class="px-2 py-2 text-right text-orange-700" id="total_amount">₱0.00</td>
                    <td class="px-2 py-2 text-right text-gray-200" id="total_tax">0.00</td>
                    <td class="px-2 py-2 text-right text-green-700" id="total_net">₱0.00</td>
                    <td colspan="2" class="px-2 py-2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
        </div>

        <!-- No Results Message -->
        <div id="no_results_message" class="hidden bg-gray-700 rounded-lg p-8 text-center">
            <i class="fas fa-user-slash text-5xl text-gray-500 mb-4"></i>
            <h3 class="text-xl font-semibold text-white mb-2">Customer Not Found</h3>
            <p class="text-gray-500">The customer you searched for does not exist. Please check and try again.</p>
        </div>
    </div>
</div>

<!-- ✅ SIDE PANEL for Payment Means Details -->
<div id="payment_means_panel" class="fixed top-0 right-0 h-full w-96 bg-gray-800 shadow-2xl transform translate-x-full transition-transform duration-300 ease-in-out z-50 overflow-y-auto">
    <div class="p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h3 class="text-lg font-bold text-white flex items-center">
                <i class="fas fa-credit-card mr-2 text-blue-700"></i>
                Payment Means Details
            </h3>
            <button type="button" onclick="closePaymentMeansPanel()" class="text-gray-500 hover:text-white transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <!-- Row Info -->
        <div class="bg-gray-900 rounded-lg p-3 mb-4">
            <p class="text-sm text-gray-500">Editing Row: <span id="panel_row_number" class="text-white font-semibold">#1</span></p>
            <p class="text-xs text-gray-500 mt-1">DR: <span id="panel_dr_number" class="text-gray-500">—</span></p>
        </div>

        <!-- Payment Type Selection -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-500 mb-2">Payment Means Type *</label>
            <select id="panel_payment_type" onchange="updatePaymentMeansFields()" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
                <option value="">Select Payment Type</option>
                <option value="check">Check</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="cash">Cash</option>
            </select>
        </div>

        <!-- CHECK Fields -->
        <div id="check_fields" class="hidden space-y-4">
            <h4 class="text-sm font-semibold text-blue-700 mb-3 flex items-center">
                <i class="fas fa-money-check mr-2"></i>
                Check Details
            </h4>
            
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-2">G/L Account *</label>
                <div class="relative">
                    <input type="hidden" id="check_gl_account_id">
                    <input type="text" id="check_gl_account_search" placeholder="Search GL Account (code/name)" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <div id="check_gl_account_dropdown" class="absolute top-full left-0 right-0 bg-gray-800 border border-gray-700 rounded max-h-48 overflow-y-auto z-10 hidden mt-1"></div>
                </div>
                <input type="hidden" id="check_gl_account">
                <p class="text-xs text-gray-500 mt-1">For PDC, use Clearing Account - PDC</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-500 mb-2">Due Date *</label>
                <input type="date" id="check_due_date" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-500 mb-2">Amount *</label>
                <input type="number" step="0.01" id="check_amount" placeholder="0.00" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
                <button type="button" onclick="copyBalanceDueToCheck()" class="text-xs text-blue-700 hover:text-blue-700 mt-1">
                    <i class="fas fa-copy mr-1"></i>Copy Balance Due
                </button>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-500 mb-2">Bank Name *</label>
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
                <label class="block text-sm font-medium text-gray-500 mb-2">Check Number *</label>
                <input type="text" id="check_number" placeholder="Check #" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <!-- BANK TRANSFER Fields -->
        <div id="bank_transfer_fields" class="hidden space-y-4">
            <h4 class="text-sm font-semibold text-green-700 mb-3 flex items-center">
                <i class="fas fa-exchange-alt mr-2"></i>
                Bank Transfer Details
            </h4>
            
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-2">G/L Account *</label>
                <div class="relative">
                    <input type="hidden" id="transfer_gl_account_id">
                    <input type="text" id="transfer_gl_account_search" placeholder="Search GL Account (code/name)" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <div id="transfer_gl_account_dropdown" class="absolute top-full left-0 right-0 bg-gray-800 border border-gray-700 rounded max-h-48 overflow-y-auto z-10 hidden mt-1"></div>
                </div>
                <input type="hidden" id="transfer_gl_account">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-500 mb-2">Transfer Date *</label>
                <input type="date" id="transfer_date" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-500 mb-2">Reference Number</label>
                <input type="text" id="transfer_reference" placeholder="Reference #" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-500 mb-2">Total Amount *</label>
                <input type="number" step="0.01" id="transfer_amount" placeholder="0.00" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
                <button type="button" onclick="copyBalanceDueToTransfer()" class="text-xs text-blue-700 hover:text-blue-700 mt-1">
                    <i class="fas fa-copy mr-1"></i>Copy Balance Due
                </button>
            </div>
        </div>

        <!-- CASH Fields -->
        <div id="cash_fields" class="hidden space-y-4">
            <h4 class="text-sm font-semibold text-yellow-700 mb-3 flex items-center">
                <i class="fas fa-money-bill-wave mr-2"></i>
                Cash Payment Details
            </h4>
            
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-2">G/L Account *</label>
                <div class="relative">
                    <input type="hidden" id="cash_gl_account_id">
                    <input type="text" id="cash_gl_account_search" placeholder="Search GL Account (code/name)" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <div id="cash_gl_account_dropdown" class="absolute top-full left-0 right-0 bg-gray-800 border border-gray-700 rounded max-h-48 overflow-y-auto z-10 hidden mt-1"></div>
                </div>
                <input type="hidden" id="cash_gl_account">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-500 mb-2">Total Amount *</label>
                <input type="number" step="0.01" id="cash_amount" placeholder="0.00" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
                <button type="button" onclick="copyBalanceDueToCash()" class="text-xs text-blue-700 hover:text-blue-700 mt-1">
                    <i class="fas fa-copy mr-1"></i>Copy Balance Due
                </button>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex space-x-3 mt-6 pt-4 border-t border-gray-700">
            <button type="button" onclick="savePaymentMeans()" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-medium transition">
                <i class="fas fa-check mr-2"></i>Save
            </button>
            <button type="button" onclick="closePaymentMeansPanel()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-white px-4 py-2 rounded font-medium transition">
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
            row.classList.add('bg-blue-100', 'bg-opacity-20');
        } else {
            row.classList.remove('bg-blue-100', 'bg-opacity-20');
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
            background: '#ffffff',
            color: '#1f2937'
        });
        return;
    }

    let addedCount = 0;

    // If the only existing row is empty, remove it before adding selected rows
    const tbody = document.getElementById('payment_entries_tbody');
    const existingRows = tbody.querySelectorAll('tr');
    if (existingRows.length === 1) {
        const drInput = existingRows[0].querySelector('[data-field="dr_number"]');
        if (drInput && drInput.value.trim() === '') {
            existingRows[0].remove();
            paymentRowCounter = 0;
        }
    }

    outstandingPaymentsData.forEach(payment => {
        if (selectedOutstandingPayments.has(payment.dr_no)) {
            addPaymentRowWithData(payment);
            addedCount++;
        }
    });

    selectedOutstandingPayments.clear();
    updateSelectedCount();
    updateCheckboxStates();
    updatePaymentTotals();

    Swal.fire({
        icon: 'success',
        title: 'Added!',
        text: `${addedCount} payment(s) added to the entry table.`,
        background: '#ffffff',
        color: '#1f2937',
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
    row.className = 'border-b border-gray-700 bg-green-100 bg-opacity-10';
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
                   class="w-full bg-gray-700 text-gray-500 border border-gray-600 rounded px-2 py-1 text-xs" 
                   data-field="invoice_no"
                   readonly>
        </td>
        <td class="px-2 py-1.5">
            <input type="text"
                   value="₱${parseFloat(paymentData.check_amount || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })}"
                   class="w-full bg-gray-700 text-orange-700 border border-gray-600 rounded px-2 py-1 text-xs text-right font-semibold"
                   data-field="outstanding_balance"
                   readonly>
        </td>
        <td class="px-2 py-1.5">
            <select class="w-full bg-gray-700 text-white border border-gray-600 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500"
                    data-field="payment_type"
                    data-row-id="${paymentRowCounter}"
                    onchange="handlePaymentTypeChange(this)">
                <option value="full" selected>Full Payment</option>
                <option value="partial">Partial Payment</option>
            </select>
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
                   class="w-full bg-gray-800 text-orange-600 border border-blue-400 rounded px-2 py-1 text-xs text-right focus:ring-1 focus:ring-blue-500 font-semibold"
                   data-field="amount"
                   data-row-id="${paymentRowCounter}"
                   onchange="handlePaymentAmountChange(this)">
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
                   class="w-full bg-gray-700 text-green-600 border border-gray-600 rounded px-2 py-1 text-xs text-right font-semibold"
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
        balanceElement.classList.remove('text-red-700', 'text-yellow-700');
        balanceElement.classList.add('text-green-700');
    } else if (newBalance === 0) {
        balanceElement.classList.remove('text-red-700', 'text-green-700');
        balanceElement.classList.add('text-yellow-700');
    } else {
        balanceElement.classList.remove('text-green-700', 'text-yellow-700');
        balanceElement.classList.add('text-red-700');
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
            background: '#ffffff',
            color: '#1f2937'
        });
        return;
    }

    Swal.fire({
        title: 'Searching...',
        text: 'Looking up customer information',
        background: '#ffffff',
        color: '#1f2937',
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
    .then(response => {
        if (!response.ok) {
            throw new Error('Server returned ' + response.status + ': ' + response.statusText);
        }
        return response.json();
    })
    .then(data => {
        Swal.close();

        if (data.success) {
            currentCustomer = data.customer;
            originalOutstandingBalance = parseFloat(currentCustomer.outstanding_balance);
            
            // ✅ Store outstanding invoices for quick lookup (supports multiple invoices per DR)
            outstandingInvoices = {};
            if (data.outstanding_invoices) {
                data.outstanding_invoices.forEach(invoice => {
                    if (invoice.dr_no) {
                        const key = invoice.dr_no.trim();
                        if (!outstandingInvoices[key]) outstandingInvoices[key] = [];
                        outstandingInvoices[key].push({
                            invoice_no: invoice.invoice_no,
                            outstanding_balance: parseFloat(invoice.net_ar_balance || 0),
                            invoice_date: invoice.invoice_date,
                            due_date: invoice.due_date
                        });
                    }
                });
            }
            
            console.log('Outstanding invoices loaded:', outstandingInvoices);

            document.getElementById('display_customer_name').textContent = currentCustomer.name;
            document.getElementById('display_outstanding_balance').textContent = '₱' + parseFloat(currentCustomer.outstanding_balance).toLocaleString('en-PH', { minimumFractionDigits: 2 });

            const balanceElement = document.getElementById('display_outstanding_balance');
            balanceElement.classList.remove('text-green-700', 'text-yellow-700');
            balanceElement.classList.add('text-red-700');

            // Update customer profile link
            const profileLink = document.getElementById('customerProfileLink');
            if (currentCustomer.code) {
                profileLink.href = `/ar/customer/${currentCustomer.code}`;
                profileLink.classList.remove('hidden');
            } else {
                profileLink.classList.add('hidden');
            }

            loadCustomerPaymentHistory(currentCustomer.code, currentCustomer.name);
            loadCustomerCredits(currentCustomer.code, currentCustomer.name);
            switchToPaymentEntries();

            document.getElementById('payment_entries_tbody').innerHTML = '';
            paymentRowCounter = 0;
            appliedCreditsMap = {};
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
                    // DR not in outstanding — check if it's fully paid or partially paid
                    console.warn('⚠️ DR number searched but not in outstanding invoices:', searchValue);
                    const firstRow = document.querySelector('#payment_entries_tbody tr');
                    const firstRowId = firstRow ? parseInt(firstRow.id.replace('payment_row_', '')) : null;
                    if (firstRow && firstRowId) {
                        checkIfDRAlreadyPaid(searchValue, firstRow, firstRowId);
                    }
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
        console.error('Search error details:', error, error?.message, error?.stack);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            html: `<p>Failed to search customer. Please try again.</p><p class="text-xs text-gray-400 mt-2">${error?.message || error}</p>`,
            background: '#ffffff',
            color: '#1f2937'
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
    row.className = 'border-b border-gray-700 hover:bg-gray-900 transition';
    row.id = `payment_row_${paymentRowCounter}`;
    row.innerHTML = `
        <td class="px-2 py-1.5 text-xs text-gray-500">${paymentRowCounter}</td>
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
                   class="w-full bg-gray-700 text-gray-500 border border-gray-600 rounded px-2 py-1 text-xs" 
                   placeholder="Auto-filled" 
                   data-field="invoice_no"
                   readonly>
        </td>
        <td class="px-2 py-1.5">
            <input type="text"
                   class="w-full bg-gray-700 text-orange-700 border border-gray-600 rounded px-2 py-1 text-xs text-right font-semibold"
                   placeholder="₱0.00"
                   data-field="outstanding_balance"
                   readonly>
        </td>
        <td class="px-2 py-1.5">
            <select class="w-full bg-gray-700 text-white border border-gray-600 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500"
                    data-field="payment_type"
                    data-row-id="${paymentRowCounter}"
                    onchange="handlePaymentTypeChange(this)">
                <option value="full">Full Payment</option>
                <option value="partial">Partial Payment</option>
            </select>
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
                   class="w-full bg-gray-800 text-orange-600 border border-blue-400 rounded px-2 py-1 text-xs text-right focus:ring-1 focus:ring-blue-500 font-semibold"
                   placeholder="₱0.00"
                   data-field="amount"
                   data-row-id="${paymentRowCounter}"
                   onchange="handlePaymentAmountChange(this)">
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
                   class="w-full bg-gray-700 text-green-600 border border-gray-600 rounded px-2 py-1 text-xs text-right font-semibold"
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
    updatePaymentTotals();
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
            background: '#ffffff',
            color: '#1f2937'
        });
        return;
    }
    
    // For multiple rows, confirm deletion
    Swal.fire({
        icon: 'question',
        title: 'Delete Row?',
        text: 'Are you sure you want to delete this payment entry?',
        background: '#ffffff',
        color: '#1f2937',
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
                background: '#ffffff',
                color: '#1f2937',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });
}

// ✅ NEW: Handle DR number input change — checks for already-paid invoices
function handleDRNumberChange(input) {
    const rowId = input.dataset.rowId;
    const row = document.getElementById(`payment_row_${rowId}`);
    const drNumber = input.value.trim();

    if (!drNumber) {
        row.querySelector('[data-field="invoice_no"]').value = '';
        row.querySelector('[data-field="outstanding_balance"]').value = '';
        row.querySelector('[data-field="amount"]').value = '';
        row.querySelector('[data-field="tax"]').value = '';
        row.querySelector('[data-field="net"]').value = '';
        row.querySelector('[data-field="payment_type"]').value = 'full';
        return;
    }

    console.log('DR Number entered:', drNumber);
    console.log('Looking up in:', outstandingInvoices);

    // Look up invoice info from outstanding invoices (now an array)
    const invoiceList = outstandingInvoices[drNumber];

    if (invoiceList && invoiceList.length > 1) {
        // Multiple invoices for same DR — show picker
        showInvoicePicker(drNumber, invoiceList, row, rowId);
    } else if (invoiceList && invoiceList.length === 1) {
        // Single invoice — auto-fill directly
        applyInvoiceToRow(invoiceList[0], row, rowId);
    } else {
        // ✅ DR not in outstanding list — check if it's already fully paid
        checkIfDRAlreadyPaid(drNumber, row, rowId);
    }
}

// Show a picker when multiple invoices share the same DR number
function showInvoicePicker(drNumber, invoiceList, row, rowId) {
    let tableRows = invoiceList.map((inv, idx) => `
        <tr class="border-b hover:bg-blue-50 cursor-pointer" onclick="document.getElementById('invoice_pick_${idx}').checked = true">
            <td class="p-2 text-center">
                <input type="radio" name="invoice_pick" id="invoice_pick_${idx}" value="${idx}" ${idx === 0 ? 'checked' : ''}>
            </td>
            <td class="p-2 text-sm font-semibold">${inv.invoice_no || '—'}</td>
            <td class="p-2 text-sm text-right text-orange-600 font-semibold">₱${inv.outstanding_balance.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
            <td class="p-2 text-xs text-gray-500">${inv.invoice_date || '—'}</td>
            <td class="p-2 text-xs text-gray-500">${inv.due_date || '—'}</td>
        </tr>
    `).join('');

    Swal.fire({
        title: `Multiple Invoices for DR #${drNumber}`,
        html: `
            <p class="text-sm text-gray-500 mb-3">This DR number has ${invoiceList.length} invoices. Select which one to pay:</p>
            <table class="w-full text-left border border-gray-700 rounded">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="p-2 text-xs text-center w-10"></th>
                        <th class="p-2 text-xs">Invoice No</th>
                        <th class="p-2 text-xs text-right">Outstanding</th>
                        <th class="p-2 text-xs">Invoice Date</th>
                        <th class="p-2 text-xs">Due Date</th>
                    </tr>
                </thead>
                <tbody>${tableRows}</tbody>
            </table>
        `,
        background: '#ffffff',
        color: '#1f2937',
        width: '600px',
        showCancelButton: true,
        confirmButtonText: 'Select',
        confirmButtonColor: '#2563eb',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const selected = document.querySelector('input[name="invoice_pick"]:checked');
            if (!selected) {
                Swal.showValidationMessage('Please select an invoice');
                return false;
            }
            return parseInt(selected.value);
        }
    }).then(result => {
        if (result.isConfirmed) {
            const selectedInvoice = invoiceList[result.value];
            applyInvoiceToRow(selectedInvoice, row, rowId);
        } else {
            // User cancelled — clear the DR field
            row.querySelector('[data-field="dr_number"]').value = '';
        }
    });
}

// Apply selected invoice info to a payment row
function applyInvoiceToRow(invoiceInfo, row, rowId) {
    row.querySelector('[data-field="invoice_no"]').value = invoiceInfo.invoice_no || '';
    row.querySelector('[data-field="outstanding_balance"]').value = '₱' + invoiceInfo.outstanding_balance.toLocaleString('en-PH', { minimumFractionDigits: 2 });

    // Default to Full Payment — auto-fill amount with outstanding balance (editable)
    row.querySelector('[data-field="payment_type"]').value = 'full';
    const amountInput = row.querySelector('[data-field="amount"]');
    amountInput.value = '₱' + invoiceInfo.outstanding_balance.toLocaleString('en-PH', { minimumFractionDigits: 2 });
    amountInput.readOnly = false;
    amountInput.classList.remove('bg-gray-700');
    amountInput.classList.add('bg-gray-800', 'border-blue-400');

    // Fetch customer tax rate and calculate net
    fetchCustomerTaxAndCalculateNet(rowId, invoiceInfo.outstanding_balance);

    // Highlight row as valid
    row.classList.remove('bg-red-100', 'bg-opacity-20');
    row.classList.add('bg-green-100', 'bg-opacity-10');

    updateOutstandingBalance();

    Swal.fire({
        icon: 'success',
        title: 'DR Found!',
        html: `<div class="text-left">
            <p><strong>Invoice:</strong> ${invoiceInfo.invoice_no}</p>
            <p><strong>Outstanding:</strong> ₱${invoiceInfo.outstanding_balance.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</p>
        </div>`,
        background: '#ffffff',
        color: '#1f2937',
        timer: 3000,
        showConfirmButton: false
    });
}

// ✅ Check if a DR number is already fully paid
function checkIfDRAlreadyPaid(drNumber, row, rowId) {
    if (!currentCustomer) {
        showDRNotFound(drNumber, row);
        return;
    }

    // Check against the server if this DR has existing payments
    fetch(`/payments/check-dr-status?customer_code=${encodeURIComponent(currentCustomer.code)}&dr_no=${encodeURIComponent(drNumber)}`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'paid') {
            // Already fully paid — show info and clear row
            row.querySelector('[data-field="invoice_no"]').value = data.invoice_no || '';
            row.querySelector('[data-field="outstanding_balance"]').value = '₱0.00';
            row.querySelector('[data-field="amount"]').value = '';
            row.querySelector('[data-field="dr_number"]').value = '';
            row.classList.remove('bg-green-100', 'bg-opacity-10', 'bg-red-100', 'bg-opacity-20');

            Swal.fire({
                icon: 'info',
                title: 'Fully Paid',
                html: `<div class="text-left">
                    <p>DR Number <strong>"${drNumber}"</strong> is already <strong>fully paid</strong>.</p>
                    <p class="mt-2 text-sm text-gray-500">Total Paid: ₱${parseFloat(data.total_paid || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</p>
                    <p class="text-sm text-gray-500">This DR cannot be entered for collection again.</p>
                </div>`,
                background: '#ffffff',
                color: '#1f2937'
            });
        } else if (data.status === 'partial') {
            // Partially paid — allow entry with remaining balance
            row.querySelector('[data-field="invoice_no"]').value = data.invoice_no || '';
            row.querySelector('[data-field="outstanding_balance"]').value = '₱' + parseFloat(data.remaining).toLocaleString('en-PH', { minimumFractionDigits: 2 });

            const amountInput = row.querySelector('[data-field="amount"]');
            amountInput.value = '₱' + parseFloat(data.remaining).toLocaleString('en-PH', { minimumFractionDigits: 2 });
            row.querySelector('[data-field="payment_type"]').value = 'full';
            amountInput.readOnly = false;
            amountInput.classList.remove('bg-gray-700');
            amountInput.classList.add('bg-gray-800', 'border-blue-400');

            // Store in outstandingInvoices for future reference (array format)
            outstandingInvoices[drNumber] = [{
                invoice_no: data.invoice_no,
                outstanding_balance: parseFloat(data.remaining)
            }];

            fetchCustomerTaxAndCalculateNet(rowId, parseFloat(data.remaining));
            row.classList.remove('bg-red-100', 'bg-opacity-20');
            row.classList.add('bg-yellow-50', 'bg-opacity-50');
            updateOutstandingBalance();

            Swal.fire({
                icon: 'info',
                title: 'Partial Payment Exists',
                html: `<div class="text-left">
                    <p>DR <strong>"${drNumber}"</strong> has a remaining balance of:</p>
                    <p class="text-lg font-bold text-blue-700 mt-1">₱${parseFloat(data.remaining).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</p>
                    <p class="text-sm text-gray-500 mt-1">Previously paid: ₱${parseFloat(data.total_paid || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</p>
                </div>`,
                background: '#ffffff',
                color: '#1f2937',
                timer: 4000,
                showConfirmButton: false
            });
        } else {
            // Not found at all
            showDRNotFound(drNumber, row);
        }
    })
    .catch(err => {
        console.error('Error checking DR status:', err);
        showDRNotFound(drNumber, row);
    });
}

function showDRNotFound(drNumber, row) {
    row.querySelector('[data-field="invoice_no"]').value = '';
    row.querySelector('[data-field="outstanding_balance"]').value = '';
    row.classList.remove('bg-green-100', 'bg-opacity-10');
    row.classList.add('bg-red-100', 'bg-opacity-20');

    Swal.fire({
        icon: 'warning',
        title: 'DR Not Found',
        text: `DR Number "${drNumber}" not found in outstanding invoices for this customer.`,
        background: '#ffffff',
        color: '#1f2937'
    });
}

// ✅ Handle Payment Type toggle (Full / Partial)
function handlePaymentTypeChange(select) {
    const rowId = select.dataset.rowId;
    const row = document.getElementById(`payment_row_${rowId}`);
    const amountInput = row.querySelector('[data-field="amount"]');
    const outstandingText = row.querySelector('[data-field="outstanding_balance"]').value;
    const outstanding = parseFloat(outstandingText.replace(/[₱,]/g, '')) || 0;

    // Amount is always editable
    amountInput.readOnly = false;
    amountInput.classList.remove('bg-gray-700');
    amountInput.classList.add('bg-gray-800', 'border-blue-400');

    if (select.value === 'partial') {
        // Partial — clear amount for user input
        amountInput.value = '';
        amountInput.placeholder = 'Enter amount';
        amountInput.focus();

        // Clear tax and net so they recalculate on amount change
        row.querySelector('[data-field="tax"]').value = '';
        row.querySelector('[data-field="net"]').value = '';
    } else {
        // Full — pre-fill with outstanding balance but still editable
        if (outstanding > 0) {
            amountInput.value = '₱' + outstanding.toLocaleString('en-PH', { minimumFractionDigits: 2 });
            fetchCustomerTaxAndCalculateNet(rowId, outstanding);
        }

        amountInput.classList.remove('border-orange-500');
        updateOutstandingBalance();
    }
}

// ✅ Handle payment amount change — validates and recalculates tax/net for partial payments
function handlePaymentAmountChange(input) {
    const rowId = input.dataset.rowId;
    const row = document.getElementById(`payment_row_${rowId}`);
    const rawValue = input.value.toString().replace(/[₱,]/g, '');
    const amount = parseFloat(rawValue) || 0;

    // Get outstanding balance from the row
    const outstandingText = row.querySelector('[data-field="outstanding_balance"]').value;
    const outstanding = parseFloat(outstandingText.replace(/[₱,]/g, '')) || 0;

    if (amount <= 0) {
        input.classList.add('border-red-500');
        return;
    }

    if (outstanding > 0 && amount > outstanding) {
        Swal.fire({
            icon: 'warning',
            title: 'Amount Exceeds Outstanding',
            html: `<div class="text-left">
                <p>Payment: <strong>₱${amount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</strong></p>
                <p>Outstanding: <strong>₱${outstanding.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</strong></p>
                <p class="mt-2 text-sm text-gray-500">The excess will be recorded as overpayment/credit.</p>
            </div>`,
            background: '#ffffff',
            color: '#1f2937'
        });
        input.classList.add('border-orange-500');
    } else {
        input.classList.remove('border-orange-500', 'border-red-500');
    }

    // Format the amount with peso sign
    input.value = '₱' + amount.toLocaleString('en-PH', { minimumFractionDigits: 2 });

    // Recalculate tax and net
    fetchCustomerTaxAndCalculateNet(rowId, amount);
    updateOutstandingBalance();
    updatePaymentTotals();
}

// ✅ Update totals row when there are 2+ payment entries
function updatePaymentTotals() {
    const rows = document.querySelectorAll('#payment_entries_tbody tr[id^="payment_row_"]');
    const totalsRow = document.getElementById('payment_totals_row');

    if (rows.length < 2) {
        totalsRow.classList.add('hidden');
        return;
    }

    totalsRow.classList.remove('hidden');
    let totalAmount = 0;
    let totalTax = 0;
    let totalNet = 0;

    rows.forEach(row => {
        const amountField = row.querySelector('[data-field="amount"]');
        const taxField = row.querySelector('[data-field="tax"]');
        const netField = row.querySelector('[data-field="net"]');

        if (amountField) {
            totalAmount += parseFloat(amountField.value.toString().replace(/[₱,]/g, '')) || 0;
        }
        if (taxField) {
            totalTax += parseFloat(taxField.value.toString().replace(/[₱,]/g, '')) || 0;
        }
        if (netField) {
            totalNet += parseFloat(netField.value.toString().replace(/[₱,]/g, '')) || 0;
        }
    });

    document.getElementById('total_amount').textContent = '₱' + totalAmount.toLocaleString('en-PH', { minimumFractionDigits: 2 });
    document.getElementById('total_tax').textContent = totalTax.toFixed(2);
    document.getElementById('total_net').textContent = '₱' + totalNet.toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

function deletePaymentRow(rowId) {
    const row = document.getElementById(`payment_row_${rowId}`);
    if (row) {
        // Restore credits back to available pool when deleting a row
        const rowKey = row.id;
        if (appliedCreditsMap[rowKey]) {
            appliedCreditsMap[rowKey].forEach(credit => {
                const avail = availableCredits.find(c => c.payment_id === credit.credit_source_payment_id);
                if (avail) avail.remaining_credit += credit.amount;
            });
            delete appliedCreditsMap[rowKey];
        }
        row.remove();
        updateOutstandingBalance();
        updatePaymentTotals();
    }
}

// ✅ UPDATED: Save all payments with DR number
function saveAllPayments() {
    if (!currentCustomer) {
        Swal.fire({
            icon: 'error',
            title: 'No Customer Selected',
            text: 'Please search for a customer first.',
            background: '#ffffff',
            color: '#1f2937'
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
            row.classList.add('bg-red-100', 'bg-opacity-20');
        } else if (!paymentMeansData[rowId]) {
            // Check if payment means is set
            hasErrors = true;
            row.classList.add('bg-red-100', 'bg-opacity-20');
        } else {
            // If validation passed, remove error styling
            row.classList.remove('bg-red-100', 'bg-opacity-20');
        }
    });

    // ✅ If ANY errors found, stop here and show error
    if (hasErrors) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please fill in all required fields and set payment means for all rows (highlighted in red).',
            background: '#ffffff',
            color: '#1f2937'
        });
        return;
    }

    // ✅ STEP 1.5: Check if payment entry amount exceeds payment means amount
    let amountMismatch = false;
    rows.forEach((row) => {
        const rowId = parseInt(row.id.replace('payment_row_', ''));
        const meansData = paymentMeansData[rowId];
        if (!meansData) return;

        const rowAmountRaw = row.querySelector('[data-field="amount"]')?.value || '0';
        const rowAmount = parseFloat(rowAmountRaw.toString().replace(/[₱,]/g, '')) || 0;
        const meansAmount = parseFloat(meansData.amount) || 0;

        if (rowAmount > meansAmount) {
            amountMismatch = true;
            const typeLabels = { 'check': 'Check', 'bank_transfer': 'Bank Transfer', 'cash': 'Cash on Hand' };
            const typeName = typeLabels[meansData.type] || meansData.type;
            row.classList.add('bg-red-100', 'bg-opacity-20');
            Swal.fire({
                icon: 'warning',
                title: 'Amount Exceeds Payment Means',
                html: `<div class="text-left">
                    <p class="mb-2">Row #${rowId} — DR: <strong>${row.querySelector('[data-field="dr_number"]')?.value || ''}</strong></p>
                    <p>Entry Amount: <strong class="text-orange-700">₱${rowAmount.toLocaleString('en-PH', {minimumFractionDigits: 2})}</strong></p>
                    <p>${typeName} Amount: <strong class="text-green-700">₱${meansAmount.toLocaleString('en-PH', {minimumFractionDigits: 2})}</strong></p>
                    <hr class="my-2">
                    <p class="text-red-600 text-sm">The entry amount is higher than the ${typeName.toLowerCase()} amount. Please update the payment means amount first.</p>
                </div>`,
                background: '#ffffff',
                color: '#1f2937'
            });
        }
    });

    if (amountMismatch) return;

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

        // Get outstanding balance and credit info from row
        const outstandingInput = row.querySelector('[data-field="outstanding_balance"]');
        const outstandingVal = outstandingInput ? parseFloat(outstandingInput.value.toString().replace(/₱|,/g, '')) || 0 : 0;
        const creditApplied = parseFloat(row.dataset.creditApplied || 0);
        const creditFromPaymentId = row.dataset.creditFromPaymentId ? parseInt(row.dataset.creditFromPaymentId) : null;

        // Get multi-credit array for this row
        const rowCredits = appliedCreditsMap[row.id] || [];

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
            net: cleanNetValue,
            payment_notes: notes,
            invoice_outstanding: outstandingVal > 0 ? outstandingVal : null,
            credit_applied: creditApplied > 0 ? creditApplied : 0,
            credit_from_payment_id: creditFromPaymentId,
            credits: rowCredits.length > 0 ? rowCredits.map(c => ({
                credit_source_payment_id: c.credit_source_payment_id,
                amount: c.amount
            })) : [],
        });
    });

    if (payments.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Payments',
            text: 'Please add at least one payment entry.',
            background: '#ffffff',
            color: '#1f2937'
        });
        return;
    }

    // Build confirmation summary
    const typeLabels = { 'check': 'Check', 'bank_transfer': 'Bank Transfer', 'cash': 'Cash' };
    let totalAmount = 0;
    let totalNet = 0;
    let summaryRows = '';

    payments.forEach((p, i) => {
        totalAmount += p.amount;
        totalNet += p.net || (p.amount - (p.tax || 0));
        const payType = typeLabels[p.payment_means?.type] || '—';
        const outstanding = p.invoice_outstanding ? '₱' + parseFloat(p.invoice_outstanding).toLocaleString('en-PH', {minimumFractionDigits: 2}) : '—';
        summaryRows += `
            <tr class="border-b border-gray-100">
                <td class="py-2 px-2 text-left">${i + 1}</td>
                <td class="py-2 px-2 text-left">${p.dr_number}</td>
                <td class="py-2 px-2 text-left">${p.invoice_no || '—'}</td>
                <td class="py-2 px-2 text-right">${outstanding}</td>
                <td class="py-2 px-2 text-center"><span class="px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-700">${payType}</span></td>
                <td class="py-2 px-2 text-right font-semibold text-orange-700">₱${p.amount.toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                <td class="py-2 px-2 text-right text-green-700">₱${(p.net || (p.amount - (p.tax || 0))).toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
            </tr>`;
    });

    const summaryHtml = `
        <div style="max-height: 400px; overflow-y: auto; text-align: left;">
            <div class="mb-3 p-3 bg-gray-900 rounded">
                <p class="text-sm text-gray-300"><strong>Customer:</strong> ${currentCustomer.name}</p>
                <p class="text-sm text-gray-300"><strong>Entries:</strong> ${payments.length} payment(s)</p>
            </div>
            <table class="w-full text-xs border-collapse">
                <thead>
                    <tr class="bg-gray-700 text-gray-300">
                        <th class="py-2 px-2 text-left">#</th>
                        <th class="py-2 px-2 text-left">DR No</th>
                        <th class="py-2 px-2 text-left">Invoice</th>
                        <th class="py-2 px-2 text-right">Outstanding</th>
                        <th class="py-2 px-2 text-center">Payment</th>
                        <th class="py-2 px-2 text-right">Amount</th>
                        <th class="py-2 px-2 text-right">Net</th>
                    </tr>
                </thead>
                <tbody>${summaryRows}</tbody>
                <tfoot>
                    <tr class="bg-blue-50 font-bold border-t-2 border-blue-300">
                        <td colspan="5" class="py-2 px-2 text-right text-gray-200">TOTAL</td>
                        <td class="py-2 px-2 text-right text-orange-700">₱${totalAmount.toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                        <td class="py-2 px-2 text-right text-green-700">₱${totalNet.toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                    </tr>
                </tfoot>
            </table>
        </div>`;

    Swal.fire({
        title: 'Confirm Payment Entries',
        html: summaryHtml,
        icon: 'question',
        width: '700px',
        background: '#ffffff',
        color: '#1f2937',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-save mr-1"></i> Confirm & Save',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#6b7280',
    }).then((result) => {
        if (!result.isConfirmed) return;

        Swal.fire({
            title: 'Saving...',
            text: 'Processing payment entries',
            background: '#ffffff',
            color: '#1f2937',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
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
                    background: '#ffffff',
                    color: '#1f2937'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Partial Success',
                    text: `${successCount} succeeded, ${failCount} failed. Please check and try again.`,
                    background: '#ffffff',
                    color: '#1f2937'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to save payments. Please try again.',
                background: '#ffffff',
                color: '#1f2937'
            });
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
                        <td colspan="12" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-file-invoice text-4xl mb-2"></i>
                            <p>No collections found</p>
                        </td>
                    </tr>
                `;
                return;
            }

            data.payments.forEach(payment => {
    const row = document.createElement('tr');
    row.className = 'border-b border-gray-700 hover:bg-gray-900';

    // Handle BOTH old (payment_option) and new (payment_method) data
    let paymentMeansDisplay = '—';
    let paymentMeansBadgeClass = 'bg-gray-700 text-gray-500';

    if (payment.payment_method) {
        const typeLabels = { 'check': 'Check', 'bank_transfer': 'Bank Transfer', 'cash': 'Cash' };
        paymentMeansDisplay = typeLabels[payment.payment_method] || payment.payment_method;
        if (payment.payment_method === 'check') paymentMeansBadgeClass = 'bg-blue-100 text-blue-700 border border-blue-200';
        else if (payment.payment_method === 'bank_transfer') paymentMeansBadgeClass = 'bg-green-100 text-green-700 border border-green-200';
        else if (payment.payment_method === 'cash') paymentMeansBadgeClass = 'bg-yellow-100 text-yellow-700 border border-yellow-200';
    } else if (payment.payment_option) {
        paymentMeansDisplay = payment.payment_option;
        paymentMeansBadgeClass = 'bg-purple-100 text-purple-700 border border-purple-200';
    }

    // Use gross_amount with fallback to amount
    const displayAmount = parseFloat(payment.gross_amount || payment.amount || 0);
    const displayEwt = parseFloat(payment.ewt || payment.tax || 0);
    const displayNet = parseFloat(payment.check_amount || payment.net || (displayAmount - displayEwt));

    // Status badge
    let statusBadge = '<span class="px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-700">Posted</span>';
    if (payment.status === 'Voided') {
        statusBadge = '<span class="px-2 py-1 rounded text-xs font-semibold bg-red-100 text-red-700">Voided</span>';
    }

    row.innerHTML = `
        <td class="px-4 py-3 text-white font-medium">${payment.customer_name || '—'}</td>
        <td class="px-4 py-3">${payment.collection_receipt_number || '—'}</td>
        <td class="px-4 py-3">${payment.dr_no || '—'}</td>
        <td class="px-4 py-3">${payment.invoice_no || '—'}</td>
        <td class="px-4 py-3">${payment.collection_receipt_date ? new Date(payment.collection_receipt_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'}</td>
        <td class="px-4 py-3">${payment.payment_posting_date ? new Date(payment.payment_posting_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'}</td>
        <td class="px-4 py-3">
            <span class="px-2 py-1 rounded text-xs font-semibold ${paymentMeansBadgeClass}">
                ${paymentMeansDisplay}
            </span>
        </td>
        <td class="px-4 py-3 text-right font-semibold text-white">₱${displayAmount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
        <td class="px-4 py-3 text-right text-orange-600">${displayEwt > 0 ? '₱' + displayEwt.toLocaleString('en-PH', { minimumFractionDigits: 2 }) : '—'}</td>
        <td class="px-4 py-3 text-right font-semibold text-green-700">₱${displayNet.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
        <td class="px-4 py-3 text-center">${statusBadge}</td>
        <td class="px-4 py-3 text-center">
            <div class="flex items-center justify-center gap-2">
                <a href="/payments/${payment.id}" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">
                    <i class="fas fa-eye mr-1"></i>View
                </a>
                @if(auth()->user()->canEditPayments() || auth()->user()->canRequestPaymentEdit())
                ${payment.status !== 'Voided' ? `<a href="/payments/${payment.id}/edit" class="text-orange-600 hover:text-orange-800 text-xs font-semibold"><i class="fas fa-edit mr-1"></i>Edit</a>` : ''}
                @endif
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
        background: '#ffffff',
        color: '#1f2937',
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
                    <td colspan="19" class="px-4 py-6 text-center text-gray-500">
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
            row.className = 'border-b border-gray-700 hover:bg-gray-900 cursor-pointer transition';
            
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
            let otherAdjClass = 'text-yellow-700';
            if (otherAdj !== 0) {
                otherAdjClass = otherAdj > 0 ? 'text-green-700' : 'text-red-700';
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
                <td class="px-2 py-2"><span class="bg-orange-100 border border-orange-200 px-2 py-1 rounded font-mono text-xs">${payment.collection_receipt_number || '—'}</span></td>
                <td class="px-2 py-2">${payment.invoice_no || '—'}</td>
                <td class="px-2 py-2 font-semibold text-blue-700">${payment.dr_no || '—'}</td>
                <td class="px-2 py-2 text-xs">${payment.customer_name || '—'}</td>
                <td class="px-2 py-2 text-xs">${payment.branch || 'N/A'}</td>
                <td class="px-2 py-2 text-right font-semibold text-orange-700">₱${grossAmount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                <td class="px-2 py-2 text-right text-orange-700">₱${ewt.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                <td class="px-2 py-2 text-right ${otherAdjClass}">${otherAdjDisplay}</td>
                <td class="px-2 py-2 text-right ${factoring !== 0 ? 'text-purple-700' : ''}">—</td>
                <td class="px-2 py-2 text-right font-semibold text-green-700">₱${checkAmount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                <td class="px-2 py-2 text-right">—</td>
                <td class="px-2 py-2">${payment.week_no || '—'}</td>
                <td class="px-2 py-2">${payment.ar_class || '—'}</td>
                <td class="px-2 py-2">${payment.bank || 'BDO'}</td>
                <td class="px-2 py-2">${payment.checking_si || 'OK'}</td>
                <td class="px-2 py-2"><span class="bg-orange-600/30 text-orange-700 px-2 py-1 rounded text-xs">${payment.status || 'Outstanding'}</span></td>
                <td class="px-2 py-2 text-xs">${payment.remarks || 'N/A'}</td>
            `;
            tbody.appendChild(row);
        });
        
        // Add total outstanding row
        const totalRow = document.createElement('tr');
        totalRow.className = 'bg-orange-100/20 border-t-2 border-orange-600 font-bold';
        totalRow.innerHTML = `
            <td></td>
            <td colspan="10" class="px-2 py-3 text-right text-white">TOTAL OUTSTANDING:</td>
            <td class="px-2 py-3 text-right text-orange-700 font-bold text-base">₱${totalOutstanding.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
            <td colspan="7"></td>
        `;
        tbody.appendChild(totalRow);
    })
    .catch(error => {
        console.error('Error loading outstanding payments:', error);
        const tbody = document.getElementById('customer_payment_history_tbody');
        tbody.innerHTML = `
            <tr>
                <td colspan="19" class="px-4 py-6 text-center text-red-700">
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
            background: '#ffffff',
            color: '#1f2937'
        });
        return;
    }
    
    let paymentData = { type: paymentType };
    let isValid = true;
    let errorMessage = '';
    
    // Validate and collect data based on payment type
    if (paymentType === 'check') {
        paymentData.gl_account = document.getElementById('check_gl_account').value;
        paymentData.gl_account_id = document.getElementById('check_gl_account_id').value;
        paymentData.gl_account_name = document.getElementById('check_gl_account_search').value;
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
        paymentData.gl_account_id = document.getElementById('transfer_gl_account_id').value;
        paymentData.gl_account_name = document.getElementById('transfer_gl_account_search').value;
        paymentData.transfer_date = document.getElementById('transfer_date').value;
        paymentData.reference = document.getElementById('transfer_reference').value;
        paymentData.amount = document.getElementById('transfer_amount').value;

        if (!paymentData.gl_account || !paymentData.transfer_date || !paymentData.amount) {
            isValid = false;
            errorMessage = 'Please fill in all required bank transfer fields.';
        }
    } else if (paymentType === 'cash') {
        paymentData.gl_account = document.getElementById('cash_gl_account').value;
        paymentData.gl_account_id = document.getElementById('cash_gl_account_id').value;
        paymentData.gl_account_name = document.getElementById('cash_gl_account_search').value;
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
            background: '#ffffff',
            color: '#1f2937'
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
    
    // Always sync payment means amount back to the payment entry row
    const row = document.getElementById(`payment_row_${currentEditingRowId}`);
    const amountInput = row.querySelector('[data-field="amount"]');
    if (amountInput && paymentData.amount) {
        const parsedAmount = parseFloat(paymentData.amount) || 0;
        amountInput.value = '₱' + parsedAmount.toLocaleString('en-PH', { minimumFractionDigits: 2 });
        // Recalculate tax/net for this row
        fetchCustomerTaxAndCalculateNet(currentEditingRowId, parsedAmount);
        updateOutstandingBalance();
        updatePaymentTotals();
    }
    
    closePaymentMeansPanel();
    
    Swal.fire({
        icon: 'success',
        title: 'Saved!',
        text: 'Payment means details have been saved.',
        background: '#ffffff',
        color: '#1f2937',
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

    // If no invoice number, still calculate net = gross - tax (no auto WHT lookup)
    if (!invoiceNo) {
        const taxVal = parseFloat(row.querySelector('[data-field="tax"]').value.toString().replace(/[₱,]/g, '')) || 0;
        const netVal = grossAmount - taxVal;
        row.querySelector('[data-field="net"]').value = '₱' + netVal.toLocaleString('en-PH', { minimumFractionDigits: 2 });
        updatePaymentTotals();
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
    updatePaymentTotals();
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
    let allAccountsCache = null;

    if (!searchInput) return;

    // Fetch and render GL accounts
    async function fetchAndRenderAccounts(query) {
        try {
            const url = query
                ? `/ar-adjustments/gl-accounts?search=${encodeURIComponent(query)}`
                : `/ar-adjustments/gl-accounts?search=`;
            const response = await fetch(url);
            const data = await response.json();

            if (data.success && data.accounts.length > 0) {
                if (!query) allAccountsCache = data.accounts;
                dropdown.innerHTML = data.accounts.map(account => `
                    <div class="px-3 py-2 hover:bg-blue-50 cursor-pointer text-white border-b border-gray-100" onclick="selectPaymentGlAccount('${searchId}', '${dropdownId}', '${idInputId}', '${codeInputId}', ${account.id}, '${account.display.replace(/'/g, "\\'")}', '${(account.code || '').replace(/'/g, "\\'")}')" >
                        <div class="font-semibold text-sm">${account.display}</div>
                        <div class="text-xs text-gray-500">${account.fs_line_item || 'No FS Item'}</div>
                    </div>
                `).join('');
                dropdown.classList.remove('hidden');
            } else {
                dropdown.innerHTML = '<div class="px-3 py-2 text-gray-500">No GL accounts found</div>';
                dropdown.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Error fetching GL accounts:', error);
        }
    }

    // Show all accounts on focus/click
    searchInput.addEventListener('focus', function() {
        if (allAccountsCache) {
            // Use cache for instant display
            dropdown.innerHTML = allAccountsCache.map(account => `
                <div class="px-3 py-2 hover:bg-blue-50 cursor-pointer text-white border-b border-gray-100" onclick="selectPaymentGlAccount('${searchId}', '${dropdownId}', '${idInputId}', '${codeInputId}', ${account.id}, '${account.display.replace(/'/g, "\\'")}', '${(account.code || '').replace(/'/g, "\\'")}')" >
                    <div class="font-semibold text-sm">${account.display}</div>
                    <div class="text-xs text-gray-500">${account.fs_line_item || 'No FS Item'}</div>
                </div>
            `).join('');
            dropdown.classList.remove('hidden');
        } else {
            fetchAndRenderAccounts('');
        }
    });

    // Filter as user types
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        searchTimeout = setTimeout(() => fetchAndRenderAccounts(query), 200);
    });

    // Hide dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target !== searchInput && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
}

function selectPaymentGlAccount(searchId, dropdownId, idInputId, codeInputId, id, display, code) {
    document.getElementById(idInputId).value = id;
    // Use code if available, otherwise use display name (some accounts have null codes)
    document.getElementById(codeInputId).value = code || display;
    document.getElementById(searchId).value = display;
    document.getElementById(dropdownId).classList.add('hidden');
}

// ═══════════════════════════════════════════════════════════════════════════
// CREDIT BALANCE FUNCTIONS
// ═══════════════════════════════════════════════════════════════════════════

let availableCredits = [];
let totalCreditBalance = 0;
// Track multiple applied credits per row: { rowId: [{credit_source_payment_id, amount, cr_number}] }
let appliedCreditsMap = {};

function loadCustomerCredits(customerCode, customerName) {
    availableCredits = [];
    totalCreditBalance = 0;
    document.getElementById('credit_balance_container').classList.add('hidden');
    document.getElementById('credit_details_panel').classList.add('hidden');

    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    fetch(`/payments/customer-credits?customer_code=${encodeURIComponent(customerCode || '')}&customer_name=${encodeURIComponent(customerName || '')}`, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.total > 0) {
            availableCredits = data.credits;
            totalCreditBalance = data.total;
            document.getElementById('display_credit_balance').textContent =
                '₱' + data.total.toLocaleString('en-PH', { minimumFractionDigits: 2 });
            document.getElementById('credit_balance_container').classList.remove('hidden');
        }
    })
    .catch(err => console.error('Error loading credits:', err));
}

function showCreditDetails() {
    const panel = document.getElementById('credit_details_panel');
    const list = document.getElementById('credit_details_list');
    list.innerHTML = '';

    if (availableCredits.length === 0) {
        list.innerHTML = '<p class="text-gray-500 text-xs">No available credits.</p>';
        panel.classList.remove('hidden');
        return;
    }

    availableCredits.forEach(c => {
        if (c.remaining_credit <= 0) return; // Skip fully used credits
        const div = document.createElement('div');
        div.className = 'flex items-center justify-between bg-gray-800 rounded p-2 border border-purple-100';
        div.innerHTML = `
            <div>
                <a href="/payments/${c.payment_id}" target="_blank" class="text-blue-600 hover:underline text-xs font-semibold">
                    CR #${c.collection_receipt_number}
                </a>
                <span class="text-xs text-gray-500 ml-2">${c.invoice_no ? 'Inv #' + c.invoice_no : (c.dr_no ? 'DR #' + c.dr_no : '')}</span>
                <span class="text-xs text-gray-400 ml-2">${c.date ? new Date(c.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : ''}</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm font-bold text-purple-700">₱${c.remaining_credit.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</span>
                <button type="button" onclick="applyCreditToRow(${c.payment_id}, ${c.remaining_credit})"
                    class="bg-purple-600 hover:bg-purple-700 text-white px-2 py-1 rounded text-xs font-semibold">
                    Apply
                </button>
            </div>
        `;
        list.appendChild(div);
    });

    // If all credits are used up
    if (!availableCredits.some(c => c.remaining_credit > 0)) {
        list.innerHTML = '<p class="text-gray-500 text-xs">All credits have been applied.</p>';
    }

    panel.classList.remove('hidden');
}

function hideCreditDetails() {
    document.getElementById('credit_details_panel').classList.add('hidden');
}

function applyCreditToRow(creditPaymentId, creditAmount) {
    // Find the first active payment row
    const rows = document.querySelectorAll('#payment_entries_tbody tr');
    if (rows.length === 0) {
        Swal.fire('Error', 'Please add a payment row first.', 'error');
        return;
    }

    const firstRow = rows[0];
    const rowId = firstRow.id;
    const amountInput = firstRow.querySelector('[data-field="amount"]');
    const outstandingEl = firstRow.querySelector('[data-field="outstanding_balance"]');

    if (!amountInput) return;

    const currentAmount = parseFloat(amountInput.value.replace(/[₱,\s]/g, '')) || 0;
    const outstanding = parseFloat((outstandingEl ? outstandingEl.value : '0').replace(/[₱,\s]/g, '')) || 0;

    // Calculate how much credit is already applied to this row
    if (!appliedCreditsMap[rowId]) appliedCreditsMap[rowId] = [];
    const alreadyApplied = appliedCreditsMap[rowId].reduce((sum, c) => sum + c.amount, 0);

    // Determine remaining balance that can still accept credit
    const remainingBalance = (outstanding > 0 ? outstanding : currentAmount) - alreadyApplied;

    // Determine how much of this credit to apply
    const toApply = Math.min(creditAmount, Math.max(0, remainingBalance));

    if (toApply <= 0) {
        Swal.fire('Info', 'No remaining balance to apply credit to.', 'info');
        return;
    }

    // Find the CR number for display
    const creditInfo = availableCredits.find(c => c.payment_id === creditPaymentId);
    const crNumber = creditInfo ? creditInfo.collection_receipt_number : creditPaymentId;

    // Add to applied credits map
    appliedCreditsMap[rowId].push({
        credit_source_payment_id: creditPaymentId,
        amount: toApply,
        cr_number: crNumber
    });

    const totalCredit = appliedCreditsMap[rowId].reduce((sum, c) => sum + c.amount, 0);

    // Store total credit on the row dataset for save function
    firstRow.dataset.creditApplied = totalCredit;
    firstRow.dataset.creditFromPaymentId = appliedCreditsMap[rowId][0].credit_source_payment_id;

    // Show credit applied indicator (all credits)
    let badge = firstRow.querySelector('.credit-badge');
    if (!badge) {
        badge = document.createElement('div');
        badge.className = 'credit-badge text-xs text-purple-700 font-semibold mt-1';
        const amountCell = amountInput.closest('td');
        amountCell.appendChild(badge);
    }
    const creditLines = appliedCreditsMap[rowId].map(c =>
        `CR #${c.cr_number}: -₱${c.amount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}`
    ).join('<br>');
    badge.innerHTML = `Credit: -₱${totalCredit.toLocaleString('en-PH', { minimumFractionDigits: 2 })}<br><span class="text-xs text-gray-500">${creditLines}</span>`;

    // Reduce this credit's remaining amount in the available credits panel
    if (creditInfo) {
        creditInfo.remaining_credit -= toApply;
    }

    // Recalculate net
    const taxInput = firstRow.querySelector('[data-field="tax"]');
    const netInput = firstRow.querySelector('[data-field="net"]');
    const tax = parseFloat(taxInput ? taxInput.value : '0') || 0;
    const netVal = currentAmount - tax - totalCredit;
    if (netInput) netInput.value = '₱' + Math.max(0, netVal).toLocaleString('en-PH', { minimumFractionDigits: 2 });

    // Refresh the credit details panel to show updated remaining amounts
    showCreditDetails();

    Swal.fire({
        icon: 'success',
        title: 'Credit Applied',
        html: `₱${toApply.toLocaleString('en-PH', { minimumFractionDigits: 2 })} credit from CR #${crNumber} applied.<br>Total credit: ₱${totalCredit.toLocaleString('en-PH', { minimumFractionDigits: 2 })}`,
        timer: 2500,
        showConfirmButton: false
    });
}
</script>
@endsection