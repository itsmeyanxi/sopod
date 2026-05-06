@extends('layouts.app')

@section('title', 'Payment Entry Screen')

@section('content')
<div class="w-full px-2 sm:px-4">
    <div class="bg-gray-800 rounded-lg shadow-lg p-3 sm:p-5">

        <!-- Header -->
        <div class="flex flex-wrap justify-between items-center mb-4 gap-2">
            <h2 class="text-lg sm:text-xl font-bold text-white flex items-center gap-2">
                <i class="fas fa-cash-register text-blue-400"></i> Payment Entry
            </h2>
            <button type="button" id="toggle_view_btn" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-1.5 rounded text-sm font-medium transition flex items-center gap-1.5">
                <i class="fas fa-table"></i>
                <span id="toggle_btn_text">Payment List</span>
            </button>
        </div>

        <!-- Search Section -->
        <div class="bg-gray-700 rounded-lg p-3 mb-4">
            <p class="text-gray-400 text-xs mb-2">Search by Customer Name, Code, or DR Number</p>
            <div class="flex gap-2">
                <input type="text" id="customer_search" placeholder="Customer name, code, or DR #"
                       class="flex-1 min-w-0 bg-gray-600 text-white border border-gray-600 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="button" id="search_customer_btn" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium transition flex items-center gap-1.5 shrink-0">
                    <i class="fas fa-search"></i>
                    <span class="hidden sm:inline">Search</span>
                </button>
            </div>
        </div>

        <!-- Payment List View -->
        <div id="payment_list_view" class="bg-gray-700 rounded-lg p-3">
            <div class="flex flex-wrap justify-between items-center mb-3 gap-2">
                <h4 class="text-sm font-semibold text-white">Payment List</h4>
                <button type="button" onclick="exportPaymentList()" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded text-xs flex items-center gap-1.5">
                    <i class="fas fa-file-excel"></i>
                    <span>Export</span>
                </button>
            </div>

            <!-- Filters -->
            <div class="bg-gray-800 rounded-lg p-3 mb-3">
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">From</label>
                        <input type="date" id="report_date_from" class="w-full bg-gray-600 text-white border border-gray-600 rounded px-2 py-1.5 text-xs">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">To</label>
                        <input type="date" id="report_date_to" class="w-full bg-gray-600 text-white border border-gray-600 rounded px-2 py-1.5 text-xs">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Customer</label>
                        <input type="text" id="report_customer_filter" placeholder="Filter..." class="w-full bg-gray-600 text-white border border-gray-600 rounded px-2 py-1.5 text-xs">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">DR Number</label>
                        <input type="text" id="report_dr_filter" placeholder="e.g. 138698" class="w-full bg-gray-600 text-white border border-gray-600 rounded px-2 py-1.5 text-xs">
                    </div>
                    <div class="flex items-end gap-1">
                        <button type="button" onclick="filterPaymentList()" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-2 py-1.5 rounded text-xs">Filter</button>
                        <button type="button" onclick="viewAllPayments()" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white px-2 py-1.5 rounded text-xs">All</button>
                    </div>
                </div>
            </div>

            <!-- Payment List Table -->
            <div class="overflow-x-auto rounded">
                <table class="min-w-full bg-gray-800 text-xs">
                    <thead>
                        <tr class="bg-gray-900 text-gray-400 uppercase tracking-wide">
                            <th class="px-3 py-2 text-left whitespace-nowrap">Customer</th>
                            <th class="px-3 py-2 text-left whitespace-nowrap">CR No</th>
                            <th class="px-3 py-2 text-left whitespace-nowrap">DR No</th>
                            <th class="px-3 py-2 text-left whitespace-nowrap">Invoice</th>
                            <th class="px-3 py-2 text-left whitespace-nowrap">CR Date</th>
                            <th class="px-3 py-2 text-left whitespace-nowrap">Post Date</th>
                            <th class="px-3 py-2 text-left whitespace-nowrap">Means</th>
                            <th class="px-3 py-2 text-right whitespace-nowrap">Amount</th>
                            <th class="px-3 py-2 text-right whitespace-nowrap">EWT</th>
                            <th class="px-3 py-2 text-right whitespace-nowrap">Net</th>
                            <th class="px-3 py-2 text-center whitespace-nowrap">Status</th>
                            <th class="px-3 py-2 text-center whitespace-nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody id="payment_list_tbody" class="text-gray-300 divide-y divide-gray-700">
                        <tr>
                            <td colspan="12" class="px-4 py-8 text-center text-gray-400">
                                <i class="fas fa-spinner fa-spin text-3xl mb-2 block"></i>
                                Loading collections...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Payment Entries View -->
        <div id="payment_entries_view" class="hidden space-y-3">

            <!-- Outstanding Payments -->
            <div id="customer_payment_history" class="bg-gray-700 rounded-lg p-3">
                <div class="flex flex-wrap justify-between items-center mb-2 gap-2">
                    <h4 class="text-xs font-semibold text-white flex items-center gap-1.5">
                        <i class="fas fa-file-invoice-dollar text-orange-400"></i>
                        Outstanding — <span id="history_customer_name" class="text-blue-400 font-bold"></span>
                    </h4>
                    <div class="flex items-center gap-2">
                        <button type="button" id="add_selected_btn" onclick="addSelectedToPaymentTable()" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs flex items-center gap-1 hidden transition">
                            <i class="fas fa-plus"></i>
                            Add Selected (<span id="selected_count">0</span>)
                        </button>
                        <button type="button" onclick="togglePaymentHistory()" class="text-gray-400 hover:text-white text-xs p-1">
                            <i class="fas fa-chevron-down" id="history_toggle_icon"></i>
                        </button>
                    </div>
                </div>

                <div id="payment_history_content" class="overflow-x-auto rounded" style="max-height: 220px;">
                    <table class="min-w-full bg-gray-800 text-xs">
                        <thead class="bg-gray-900 text-gray-400 sticky top-0">
                            <tr>
                                <th class="px-2 py-1.5 w-8">
                                    <input type="checkbox" id="select_all_checkbox" onchange="toggleSelectAll()"
                                           class="w-3.5 h-3.5 rounded bg-gray-700 border-gray-600 text-blue-600 cursor-pointer" title="Select All">
                                </th>
                                <th class="px-2 py-1.5 text-left whitespace-nowrap">Date</th>
                                <th class="px-2 py-1.5 text-left whitespace-nowrap">DR No</th>
                                <th class="px-2 py-1.5 text-left whitespace-nowrap">Invoice</th>
                                <th class="px-2 py-1.5 text-left whitespace-nowrap">Branch</th>
                                <th class="px-2 py-1.5 text-right whitespace-nowrap">Outstanding</th>
                                <th class="px-2 py-1.5 text-center whitespace-nowrap">Status</th>
                            </tr>
                        </thead>
                        <tbody id="customer_payment_history_tbody" class="text-gray-300 divide-y divide-gray-700">
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-gray-400">
                                    <i class="fas fa-search text-2xl mb-1 block"></i>
                                    Search a customer to view outstanding payments
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Customer Info + Credit -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div id="customer_info_container" class="bg-gray-700 rounded-lg p-3">
                    <div class="flex justify-between items-start mb-2">
                        <h4 class="text-xs font-semibold text-white flex items-center gap-1">
                            <i class="fas fa-user text-blue-400"></i> Customer
                        </h4>
                        <a href="#" id="customerProfileLink" class="bg-purple-600 hover:bg-purple-700 text-white px-2 py-1 rounded text-xs flex items-center gap-1 hidden" target="_blank">
                            <i class="fas fa-user-circle"></i> AR Profile
                        </a>
                    </div>
                    <p class="text-white font-semibold text-sm" id="display_customer_name">—</p>
                    <p class="text-gray-400 text-xs mt-0.5 hidden" id="display_customer_address"></p>
                    <div class="mt-2">
                        <label class="block text-gray-400 text-xs">Outstanding Balance</label>
                        <p class="text-red-400 font-bold text-base" id="display_outstanding_balance">₱0.00</p>
                    </div>
                </div>

                <div id="credit_balance_container" class="hidden bg-gray-700 rounded-lg p-3">
                    <h4 class="text-xs font-semibold text-white flex items-center gap-1 mb-2">
                        <i class="fas fa-coins text-purple-400"></i> Credit Balance
                    </h4>
                    <p class="text-purple-400 font-bold text-base" id="display_credit_balance">₱0.00</p>
                    <button type="button" onclick="showCreditDetails()" class="text-purple-400 hover:text-purple-300 text-xs mt-1 flex items-center gap-1">
                        <i class="fas fa-info-circle"></i> View Credits
                    </button>
                </div>
            </div>

            <!-- Credit Details Panel -->
            <div id="credit_details_panel" class="hidden bg-gray-700 border border-purple-500 rounded-lg p-3">
                <div class="flex justify-between items-center mb-2">
                    <h5 class="text-xs font-bold text-purple-400 uppercase tracking-wide">Credits from Overpayments</h5>
                    <button type="button" onclick="hideCreditDetails()" class="text-gray-400 hover:text-white text-xs"><i class="fas fa-times"></i></button>
                </div>
                <div id="credit_details_list" class="space-y-2 text-xs"></div>
            </div>

            <!-- Payment Entries Cards -->
            <div id="payment_table_container" class="bg-gray-700 rounded-lg p-3">
                <div class="flex flex-wrap justify-between items-center mb-3 gap-2">
                    <h4 class="text-sm font-semibold text-white flex items-center gap-1.5">
                        <i class="fas fa-list text-blue-400"></i> Payment Entries
                    </h4>
                    <div class="flex gap-2">
                        <button type="button" onclick="addPaymentRow()" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded text-xs flex items-center gap-1 font-medium">
                            <i class="fas fa-plus"></i> Add Row
                        </button>
                        <button type="button" onclick="saveAllPayments()" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-xs flex items-center gap-1 font-medium">
                            <i class="fas fa-save"></i> Save All
                        </button>
                    </div>
                </div>

                <!-- Totals bar -->
                <div id="payment_totals_row" class="hidden bg-gray-900 border border-gray-600 rounded-lg px-4 py-2 mb-3 flex flex-wrap gap-6 text-xs font-semibold">
                    <span class="text-gray-400">TOTAL</span>
                    <span class="text-gray-300">Outstanding: <span class="text-orange-400" id="total_amount">₱0.00</span></span>
                    <span class="text-gray-300">EWT: <span class="text-gray-400" id="total_tax">0.00</span></span>
                    <span class="text-gray-300">Discount: <span class="text-yellow-400" id="total_discount">₱0.00</span></span>
                    <span class="text-gray-300">Net Collectible: <span class="text-green-400 text-sm" id="total_net">₱0.00</span></span>
                </div>

                <!-- Card list -->
                <div id="payment_entries_tbody" class="space-y-3"></div>
            </div>
        </div>

        <!-- No Results -->
        <div id="no_results_message" class="hidden bg-gray-700 rounded-lg p-8 text-center">
            <i class="fas fa-user-slash text-4xl text-gray-400 mb-3 block"></i>
            <h3 class="text-base font-semibold text-white mb-1">Customer Not Found</h3>
            <p class="text-gray-400 text-sm">No customer matched your search. Please check and try again.</p>
        </div>
    </div>
</div>

<!-- SIDE PANEL for Payment Means Details -->
<div id="payment_means_panel" class="fixed top-0 right-0 h-full w-full sm:w-96 bg-gray-800 shadow-2xl transform translate-x-full transition-transform duration-300 ease-in-out z-50 overflow-y-auto">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h3 class="text-lg font-bold text-white flex items-center">
                <i class="fas fa-credit-card mr-2 text-blue-400"></i>
                Payment Means Details
            </h3>
            <button type="button" onclick="closePaymentMeansPanel()" class="text-gray-300 hover:text-white transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div class="bg-gray-900 rounded-lg p-3 mb-4">
            <p class="text-sm text-gray-300">Editing Row: <span id="panel_row_number" class="text-white font-semibold">#1</span></p>
            <p class="text-xs text-gray-300 mt-1">DR: <span id="panel_dr_number" class="text-gray-300">—</span></p>
        </div>

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
            <h4 class="text-sm font-semibold text-blue-400 mb-3 flex items-center">
                <i class="fas fa-money-check mr-2"></i> Check Details
            </h4>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">G/L Account *</label>
                <div class="relative">
                    <input type="hidden" id="check_gl_account_id">
                    <input type="text" id="check_gl_account_search" placeholder="Search GL Account (code/name)" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <div id="check_gl_account_dropdown" class="absolute top-full left-0 right-0 bg-gray-800 border border-gray-700 rounded max-h-48 overflow-y-auto z-[200] hidden mt-1"></div>
                </div>
                <input type="hidden" id="check_gl_account">
                <p class="text-xs text-gray-400 mt-1">For PDC, use Clearing Account - PDC</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Deposit Date *</label>
                <input type="date" id="check_due_date" max="9999-12-31" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
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
                    <option value="Asia United Bank">Asia United Bank</option>
                    <option value="BDO">BDO</option>
                    <option value="BPI">BPI</option>
                    <option value="Bank of Commerce">Bank of Commerce</option>
                    <option value="Citibank">Citibank</option>
                    <option value="China Bank">China Bank</option>
                    <option value="Eastwest Bank">Eastwest Bank</option>
                    <option value="Landbank">Landbank</option>
                    <option value="Maybank">Maybank</option>
                    <option value="Metrobank">Metrobank</option>
                    <option value="PBCOM">PBCOM</option>
                    <option value="Philtrust">Philtrust</option>
                    <option value="PNB">PNB</option>
                    <option value="Philippine Business Bank">Philippine Business Bank</option>
                    <option value="RCBC">RCBC</option>
                    <option value="Robinsons Bank">Robinsons Bank</option>
                    <option value="Rural Bank">Rural Bank</option>
                    <option value="Security Bank">Security Bank</option>
                    <option value="UnionBank">UnionBank</option>
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
            <h4 class="text-sm font-semibold text-green-400 mb-3 flex items-center">
                <i class="fas fa-exchange-alt mr-2"></i> Bank Transfer Details
            </h4>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">G/L Account *</label>
                <div class="relative">
                    <input type="hidden" id="transfer_gl_account_id">
                    <input type="text" id="transfer_gl_account_search" placeholder="Search GL Account (code/name)" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <div id="transfer_gl_account_dropdown" class="absolute top-full left-0 right-0 bg-gray-800 border border-gray-700 rounded max-h-48 overflow-y-auto z-[200] hidden mt-1"></div>
                </div>
                <input type="hidden" id="transfer_gl_account">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Transfer Date *</label>
                <input type="date" id="transfer_date" max="9999-12-31" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
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
            <h4 class="text-sm font-semibold text-yellow-400 mb-3 flex items-center">
                <i class="fas fa-money-bill-wave mr-2"></i> Cash Payment Details
            </h4>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">G/L Account *</label>
                <div class="relative">
                    <input type="hidden" id="cash_gl_account_id">
                    <input type="text" id="cash_gl_account_search" placeholder="Search GL Account (code/name)" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <div id="cash_gl_account_dropdown" class="absolute top-full left-0 right-0 bg-gray-800 border border-gray-700 rounded max-h-48 overflow-y-auto z-[200] hidden mt-1"></div>
                </div>
                <input type="hidden" id="cash_gl_account">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Deposit Date *</label>
                <input type="date" id="cash_deposit_date" max="9999-12-31" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
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
            <button type="button" onclick="closePaymentMeansPanel()" class="flex-1 bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded font-medium transition">
                Cancel
            </button>
        </div>
    </div>
</div>

<!-- Overlay -->
<div id="panel_overlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-40" onclick="closePaymentMeansPanel()"></div>

<script>
let currentCustomer = null;
let paymentRowCounter = 0;
let originalOutstandingBalance = 0;
let currentView = 'payment_list';
let outstandingInvoices = {};
let selectedOutstandingPayments = new Set();
let outstandingPaymentsData = [];
let currentEditingRowId = null;
let paymentMeansData = {};
let availableCredits = [];
let totalCreditBalance = 0;
let appliedCreditsMap = {};

// ─── helpers ─────────────────────────────────────────────────────────────────
function parsePeso(val) {
    if (val === null || val === undefined) return 0;
    return parseFloat(String(val).replace(/[₱,\s]/g, '')) || 0;
}

// ─── Overpayment: meansAmount − netCollectible ────────────────────────────────
// Called ONLY after net has been fully calculated (i.e. after recalculateNet or
// after savePaymentMeans sets the amount). Returns the excess the customer paid.
// ₱10 leeway: differences within ₱10 are ignored (not counted as overpayment).
const PAYMENT_LEEWAY = 10;

function computeRowOverpayment(rowId) {
    const meansData = paymentMeansData[rowId];
    if (!meansData || !meansData.amount) return 0;

    const meansAmount = parseFloat(meansData.amount) || 0;
    const row = document.getElementById(`payment_row_${rowId}`);
    if (!row) return 0;

    // Compare means vs outstanding (gross), NOT net collectible.
    // EWT is withheld by the customer — it reduces what they pay, not what they owe.
    // So means = outstanding is a normal full payment, NOT an overpayment.
    const outstandingInput = row.querySelector('[data-field="outstanding_balance"]');
    const outstanding = outstandingInput ? parsePeso(outstandingInput.value) : 0;

    if (meansAmount > 0 && outstanding > 0 && meansAmount > outstanding) {
        const diff = parseFloat((meansAmount - outstanding).toFixed(2));
        // ₱10 leeway — small differences are not overpayments
        if (diff <= PAYMENT_LEEWAY) return 0;
        return diff;
    }
    return 0;
}

// ─── Show/hide overpayment credit badge on a row ──────────────────────────────
function refreshOverpaymentBadge(rowId) {
    const row = document.getElementById(`payment_row_${rowId}`);
    if (!row) return;

    const overpayment = computeRowOverpayment(rowId);
    let badge = row.querySelector('.overpayment-badge');

    if (overpayment > 0) {
        row.dataset.overpaymentCredit = overpayment.toFixed(2);

        if (!badge) {
            badge = document.createElement('div');
            badge.className = 'overpayment-badge mt-2 p-2 bg-purple-900 border border-purple-500 rounded text-xs text-purple-200';
            // Insert after the last grid in the card
            const lastGrid = row.querySelector('.grid:last-of-type');
            if (lastGrid) lastGrid.after(badge);
            else row.appendChild(badge);
        }

        const meansAmount = parseFloat(paymentMeansData[rowId]?.amount) || 0;
        const outstanding = parsePeso(row.querySelector('[data-field="outstanding_balance"]')?.value);
        badge.innerHTML = `
            <i class="fas fa-coins text-purple-400 mr-1"></i>
            <strong>Overpayment / Credit:</strong>
            ₱${overpayment.toLocaleString('en-PH', { minimumFractionDigits: 2 })}
            <span class="text-gray-400 ml-1">(Paid ₱${meansAmount.toLocaleString('en-PH', { minimumFractionDigits: 2 })} − Outstanding ₱${outstanding.toLocaleString('en-PH', { minimumFractionDigits: 2 })})</span>`;

        // Show alert once per row (debounced)
        clearTimeout(row._overpaymentTimer);
        row._overpaymentTimer = setTimeout(() => {
            Swal.fire({
                icon: 'info',
                title: 'Overpayment Detected',
                html: `<div class="text-left">
                    <p>The payment means amount is <strong>more than the outstanding balance</strong>.</p>
                    <ul class="mt-2 text-sm space-y-1">
                        <li>Check / Transfer Amount: <strong>₱${meansAmount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</strong></li>
                        <li>Outstanding Balance: <strong>₱${outstanding.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</strong></li>
                        <li class="text-purple-300 font-bold">Excess Credit: ₱${overpayment.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</li>
                    </ul>
                    <p class="mt-2 text-sm text-gray-400">This excess will be recorded as a credit balance for the customer.</p>
                </div>`,
                background: '#1f2937',
                color: '#f9fafb',
                confirmButtonColor: '#7c3aed'
            });
        }, 300);
    } else {
        delete row.dataset.overpaymentCredit;
        if (badge) badge.remove();
    }

    // Also refresh the top-level credit balance container (sum across all rows)
    refreshCreditBalanceDisplay();
}

// ─── Refresh the top-level credit balance display ─────────────────────────────
function refreshCreditBalanceDisplay() {
    const rows = document.querySelectorAll('#payment_entries_tbody [id^="payment_row_"]');
    let totalCredit = 0;
    rows.forEach(row => {
        const rowId = parseInt(row.id?.replace('payment_row_', '')) || 0;
        totalCredit += computeRowOverpayment(rowId);
    });

    const container = document.getElementById('credit_balance_container');
    if (totalCredit > 0) {
        document.getElementById('display_credit_balance').textContent =
            '₱' + totalCredit.toLocaleString('en-PH', { minimumFractionDigits: 2 });
        container.classList.remove('hidden');
    } else {
        container.classList.add('hidden');
    }
}

// ─── Checkbox helpers ────────────────────────────────────────────────────────
function toggleOutstandingPayment(drNo) {
    if (selectedOutstandingPayments.has(drNo)) {
        selectedOutstandingPayments.delete(drNo);
    } else {
        selectedOutstandingPayments.add(drNo);
    }
    updateSelectedCount();
    updateCheckboxStates();
}

function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('select_all_checkbox');
    if (selectAllCheckbox.checked) {
        outstandingPaymentsData.forEach(payment => selectedOutstandingPayments.add(payment.dr_no));
    } else {
        selectedOutstandingPayments.clear();
    }
    updateSelectedCount();
    updateCheckboxStates();
}

function updateSelectedCount() {
    const count = selectedOutstandingPayments.size;
    document.getElementById('selected_count').textContent = count;
    const addSelectedBtn = document.getElementById('add_selected_btn');
    count > 0 ? addSelectedBtn.classList.remove('hidden') : addSelectedBtn.classList.add('hidden');
    const selectAllCheckbox = document.getElementById('select_all_checkbox');
    if (outstandingPaymentsData.length > 0) {
        selectAllCheckbox.checked = selectedOutstandingPayments.size === outstandingPaymentsData.length;
        selectAllCheckbox.indeterminate = selectedOutstandingPayments.size > 0 && selectedOutstandingPayments.size < outstandingPaymentsData.length;
    }
}

function updateCheckboxStates() {
    document.querySelectorAll('[data-payment-checkbox]').forEach(checkbox => {
        const drNo = checkbox.dataset.drNo;
        checkbox.checked = selectedOutstandingPayments.has(drNo);
        const row = checkbox.closest('tr');
        checkbox.checked ? row.classList.add('bg-blue-900', 'bg-opacity-20') : row.classList.remove('bg-blue-900', 'bg-opacity-20');
    });
}

// ─── Add selected to payment table ───────────────────────────────────────────
function addSelectedToPaymentTable() {
    if (selectedOutstandingPayments.size === 0) {
        Swal.fire({ icon: 'warning', title: 'No Selection', text: 'Please select at least one outstanding payment.', background: '#1f2937', color: '#f9fafb' });
        return;
    }
    let addedCount = 0;
    const tbody = document.getElementById('payment_entries_tbody');
    const existingRows = tbody.querySelectorAll('[id^="payment_row_"]');
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
    Swal.fire({ icon: 'success', title: 'Added!', text: `${addedCount} payment(s) added to the entry table.`, background: '#1f2937', color: '#f9fafb', timer: 2000, showConfirmButton: false });
}

// ─── Add payment row with pre-filled data ────────────────────────────────────
function addPaymentRowWithData(paymentData) {
    paymentRowCounter++;
    const tbody = document.getElementById('payment_entries_tbody');
    const today = new Date().toISOString().split('T')[0];
    // gross_amount = invoice amount minus prior payments (pre-tax outstanding)
    // check_amount = net_ar_balance (may already include CWT/EWT) — do NOT use as outstanding
    const gross = parseFloat(paymentData.gross_amount || paymentData.invoice_amount || paymentData.check_amount || 0);
    const card = document.createElement('div');
    card.className = 'bg-gray-800 border border-green-700 rounded-lg p-4 hover:border-green-600 transition';
    card.id = `payment_row_${paymentRowCounter}`;
    card.innerHTML = buildPaymentCardHTML(paymentRowCounter, today, paymentData.dr_no || '', paymentData.invoice_no || '', gross, true);
    tbody.appendChild(card);
    autoFillDiscount(paymentRowCounter);
    if (paymentData.invoice_no) {
        fetchCustomerTaxAndCalculateNet(paymentRowCounter, gross);
    }
}

function buildPaymentCardHTML(rowId, today, drNo = '', invoiceNo = '', gross = 0, readonly = false) {
    const drField = readonly
        ? `<input type="text" value="${drNo}" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-2.5 py-1.5 text-sm" data-field="dr_number" readonly>`
        : `<input type="text" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-2.5 py-1.5 text-sm focus:ring-1 focus:ring-blue-500" placeholder="e.g. 12345" data-field="dr_number" data-row-id="${rowId}" onchange="handleDRNumberChange(this)">`;
    const invoiceField = readonly
        ? `<input type="text" value="${invoiceNo}" class="w-full bg-gray-700 text-gray-300 border border-gray-600 rounded px-2.5 py-1.5 text-sm" data-field="invoice_no" readonly>`
        : `<input type="text" class="w-full bg-gray-700 text-gray-300 border border-gray-600 rounded px-2.5 py-1.5 text-sm" placeholder="Auto-filled" data-field="invoice_no" readonly>`;
    const balanceVal = gross > 0 ? `₱${gross.toLocaleString('en-PH', { minimumFractionDigits: 2 })}` : '₱0.00';
    return `
        <div class="flex items-center justify-between mb-3 pb-2 border-b border-gray-700">
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Entry #${rowId}</span>
            <button type="button" onclick="deletePaymentRow(${rowId})" class="bg-red-700 hover:bg-red-600 text-white px-2.5 py-1 rounded text-xs flex items-center gap-1">
                <i class="fas fa-trash"></i> Remove
            </button>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3">
            <div><label class="block text-xs font-semibold text-gray-400 mb-1">DR Number ${!readonly ? '<span class="text-red-400">*</span>' : ''}</label>${drField}</div>
            <div><label class="block text-xs font-semibold text-gray-400 mb-1">Invoice No</label>${invoiceField}</div>
            <div><label class="block text-xs font-semibold text-gray-400 mb-1">Outstanding Balance</label>
                <input type="text" value="${balanceVal}" class="w-full bg-gray-900 text-orange-400 border border-gray-600 rounded px-2.5 py-1.5 text-sm font-bold text-right" data-field="outstanding_balance" readonly></div>
            <div><label class="block text-xs font-semibold text-gray-400 mb-1">Payment Type</label>
                <select class="w-full bg-gray-700 text-white border border-gray-600 rounded px-2.5 py-1.5 text-sm focus:ring-1 focus:ring-blue-500" data-field="payment_type" data-row-id="${rowId}" onchange="handlePaymentTypeChange(this)">
                    <option value="full">Full Payment</option>
                    <option value="partial">Partial Payment</option>
                </select></div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-3">
            <div><label class="block text-xs font-semibold text-gray-400 mb-1">CR / Receipt No. <span class="text-red-400">*</span></label>
                <input type="text" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-2.5 py-1.5 text-sm focus:ring-1 focus:ring-blue-500" placeholder="e.g. CR-2024-001" data-field="receipt_number"></div>
            <div><label class="block text-xs font-semibold text-gray-400 mb-1">CR Date <span class="text-red-400">*</span></label>
                <input type="date" value="${today}" max="9999-12-31" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-2.5 py-1.5 text-sm focus:ring-1 focus:ring-blue-500" data-field="receipt_date"></div>
            <div><label class="block text-xs font-semibold text-gray-400 mb-1">Posting Date <span class="text-red-400">*</span></label>
                <input type="date" value="${today}" max="9999-12-31" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-2.5 py-1.5 text-sm focus:ring-1 focus:ring-blue-500" data-field="posting_date"></div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
            <div><label class="block text-xs font-semibold text-gray-400 mb-1">Payment Means <span class="text-red-400">*</span></label>
                <button type="button" onclick="openPaymentMeansPanel(${rowId})" class="w-full bg-purple-700 hover:bg-purple-600 text-white px-2.5 py-1.5 rounded text-sm font-medium transition flex items-center justify-center gap-1.5" id="payment_means_btn_${rowId}">
                    <i class="fas fa-credit-card text-xs"></i>
                    <span id="payment_means_label_${rowId}">Set Payment Means</span>
                </button></div>
            <div><label class="block text-xs font-semibold text-gray-400 mb-1">EWT / Tax %</label>
                <input type="number" step="0.01" min="0" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-2.5 py-1.5 text-sm text-right focus:ring-1 focus:ring-blue-500" placeholder="0.00" data-field="tax" data-row-id="${rowId}" oninput="handleTaxChange(this)"></div>
            <div><label class="block text-xs font-semibold text-gray-400 mb-1">Discount %</label>
                <input type="number" step="0.01" min="0" max="100" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-2.5 py-1.5 text-sm text-right focus:ring-1 focus:ring-blue-500" placeholder="0.00" data-field="discount" data-row-id="${rowId}" oninput="handleDiscountChange(this)"></div>
            <div><label class="block text-xs font-semibold text-gray-400 mb-1">Net Collectible</label>
                <input type="text" class="w-full bg-gray-900 text-green-400 border border-gray-600 rounded px-2.5 py-1.5 text-sm text-right font-bold" placeholder="₱0.00" data-field="net" readonly></div>
            <div><label class="block text-xs font-semibold text-gray-400 mb-1">Notes</label>
                <input type="text" class="w-full bg-gray-700 text-white border border-gray-600 rounded px-2.5 py-1.5 text-sm focus:ring-1 focus:ring-blue-500" placeholder="Optional notes..." data-field="notes"></div>
        </div>`;
}

// ─── Toggle views ─────────────────────────────────────────────────────────────
document.getElementById('toggle_view_btn').addEventListener('click', function() {
    currentView === 'payment_list' ? switchToPaymentEntries() : switchToPaymentList();
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

// ─── Search customer ──────────────────────────────────────────────────────────
document.getElementById('search_customer_btn').addEventListener('click', function() {
    const searchValue = document.getElementById('customer_search').value.trim();
    if (searchValue === '') {
        Swal.fire({ icon: 'warning', title: 'Empty Search', text: 'Please enter a customer name.', background: '#1f2937', color: '#f9fafb' });
        return;
    }
    Swal.fire({ title: 'Searching...', text: 'Looking up customer information', background: '#1f2937', color: '#f9fafb', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    fetch(`/payments/search-customer?search=${encodeURIComponent(searchValue)}`, {
        method: 'GET', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
    })
    .then(response => { if (!response.ok) throw new Error('Server returned ' + response.status); return response.json(); })
    .then(data => {
        Swal.close();
        if (data.success) {
            currentCustomer = data.customer;
            originalOutstandingBalance = parseFloat(currentCustomer.outstanding_balance);
            outstandingInvoices = {};
            if (data.outstanding_invoices) {
                data.outstanding_invoices.forEach(invoice => {
                    if (invoice.dr_no) {
                        let key = invoice.dr_no.trim().replace(/\.0+$/, '');
                        if (!outstandingInvoices[key]) outstandingInvoices[key] = [];
                        outstandingInvoices[key].push({
                            invoice_no: invoice.invoice_no,
                            outstanding_balance: parseFloat(invoice.gross_remaining || invoice.invoice_amount || invoice.net_ar_balance || 0),
                            gross_amount: parseFloat(invoice.gross_remaining || invoice.invoice_amount || invoice.net_ar_balance || 0),
                            invoice_date: invoice.invoice_date,
                            due_date: invoice.due_date
                        });
                    }
                });
            }
            let displayName = currentCustomer.name;
            if (currentCustomer.branch && currentCustomer.branch !== 'N/A') {
                displayName += ' — ' + currentCustomer.branch;
            }
            document.getElementById('display_customer_name').textContent = displayName;
            // Show address if available
            const addressEl = document.getElementById('display_customer_address');
            if (addressEl) {
                if (currentCustomer.billing_address) {
                    addressEl.textContent = currentCustomer.billing_address;
                    addressEl.classList.remove('hidden');
                } else {
                    addressEl.classList.add('hidden');
                }
            }
            const balanceElement = document.getElementById('display_outstanding_balance');
            balanceElement.textContent = '₱' + parseFloat(currentCustomer.outstanding_balance).toLocaleString('en-PH', { minimumFractionDigits: 2 });
            balanceElement.className = 'text-red-400 font-bold text-base';
            const profileLink = document.getElementById('customerProfileLink');
            if (currentCustomer.code) { profileLink.href = `/ar/customer/${currentCustomer.code}`; profileLink.classList.remove('hidden'); }
            else profileLink.classList.add('hidden');
            loadCustomerPaymentHistory(currentCustomer.code, currentCustomer.name);
            loadCustomerCredits(currentCustomer.code, currentCustomer.name);
            switchToPaymentEntries();
            document.getElementById('payment_entries_tbody').innerHTML = '';
            paymentRowCounter = 0;
            appliedCreditsMap = {};
            addPaymentRow();
            if (searchValue && /^[\d.]+$/.test(searchValue)) {
                let matchedKey = outstandingInvoices[searchValue] ? searchValue : null;
                if (!matchedKey) {
                    const numericSearch = parseFloat(searchValue);
                    if (!isNaN(numericSearch)) matchedKey = Object.keys(outstandingInvoices).find(key => parseFloat(key) === numericSearch);
                }
                if (matchedKey) {
                    const drInput = document.querySelector('[data-field="dr_number"]');
                    if (drInput) { drInput.value = matchedKey; drInput.dispatchEvent(new Event('change')); }
                } else {
                    const firstRow = document.querySelector('#payment_entries_tbody [id^="payment_row_"]');
                    const firstRowId = firstRow ? parseInt(firstRow.id.replace('payment_row_', '')) : null;
                    if (firstRow && firstRowId) checkIfDRAlreadyPaid(searchValue, firstRow, firstRowId);
                }
            }
        } else if (data.locked) {
            document.getElementById('payment_list_view').classList.add('hidden');
            document.getElementById('payment_entries_view').classList.add('hidden');
            document.getElementById('no_results_message').classList.add('hidden');
            Swal.fire({
                icon: 'warning',
                title: 'Delivery Locked',
                html: `<div class="text-left">
                    <p>DR <strong>"${data.dr_no}"</strong> is locked and cannot be collected.</p>
                    <p class="mt-2 text-sm text-gray-400">Customer: <strong>${data.customer_name || ''}</strong></p>
                    <p class="mt-3 text-sm text-gray-400">Please contact your administrator if you believe this is an error.</p>
                </div>`,
                background: '#1f2937',
                color: '#f9fafb',
                confirmButtonColor: '#2563eb'
            });
        } else if (data.fully_paid) {
            document.getElementById('payment_list_view').classList.add('hidden');
            document.getElementById('payment_entries_view').classList.add('hidden');
            document.getElementById('no_results_message').classList.add('hidden');
            Swal.fire({
                icon: 'info',
                title: 'Fully Paid',
                html: `<div class="text-left">
                    <p>DR <strong>"${data.dr_no}"</strong> has already been fully paid.</p>
                    <p class="mt-2 text-sm text-gray-400">Customer: <strong>${data.customer_name || ''}</strong></p>
                    <p class="text-sm text-gray-400">Total Paid: <strong class="text-green-400">₱${parseFloat(data.total_paid || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</strong></p>
                    <p class="mt-3 text-sm text-gray-400">This delivery cannot be searched for payment again.</p>
                </div>`,
                background: '#1f2937',
                color: '#f9fafb',
                confirmButtonColor: '#2563eb'
            });
        } else {
            document.getElementById('payment_list_view').classList.add('hidden');
            document.getElementById('payment_entries_view').classList.add('hidden');
            document.getElementById('no_results_message').classList.remove('hidden');
        }
    })
    .catch(error => {
        Swal.close();
        Swal.fire({ icon: 'error', title: 'Error', html: `<p>Failed to search customer.</p><p class="text-xs text-gray-400 mt-2">${error?.message || error}</p>`, background: '#1f2937', color: '#f9fafb' });
    });
});

document.getElementById('customer_search').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); document.getElementById('search_customer_btn').click(); }
});

// ─── Add payment row ──────────────────────────────────────────────────────────
function addPaymentRow() {
    paymentRowCounter++;
    const tbody = document.getElementById('payment_entries_tbody');
    const today = new Date().toISOString().split('T')[0];
    const card = document.createElement('div');
    card.className = 'bg-gray-800 border border-gray-600 rounded-lg p-4 hover:border-gray-500 transition';
    card.id = `payment_row_${paymentRowCounter}`;
    card.innerHTML = buildPaymentCardHTML(paymentRowCounter, today);
    tbody.appendChild(card);
    autoFillDiscount(paymentRowCounter);
    updatePaymentTotals();
}

// ─── Delete payment row ───────────────────────────────────────────────────────
function deletePaymentRow(rowId) {
    const row = document.getElementById(`payment_row_${rowId}`);
    if (!row) return;
    const rowCount = document.querySelectorAll('#payment_entries_tbody [id^="payment_row_"]').length;
    if (rowCount === 1) {
        Swal.fire({ icon: 'warning', title: 'Cannot Delete', text: 'You must have at least one payment row.', background: '#1f2937', color: '#f9fafb' });
        return;
    }
    Swal.fire({
        icon: 'question', title: 'Delete Row?', text: 'Are you sure you want to delete this payment entry?',
        background: '#1f2937', color: '#f9fafb', showCancelButton: true,
        confirmButtonColor: '#dc2626', cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it', cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const rowKey = row.id;
            if (appliedCreditsMap[rowKey]) {
                appliedCreditsMap[rowKey].forEach(credit => {
                    const avail = availableCredits.find(c => c.payment_id === credit.credit_source_payment_id);
                    if (avail) avail.remaining_credit += credit.amount;
                });
                delete appliedCreditsMap[rowKey];
            }
            row.remove();
            refreshCreditBalanceDisplay();
            updatePaymentTotals();
            Swal.fire({ icon: 'success', title: 'Deleted!', text: 'Payment row has been removed.', background: '#1f2937', color: '#f9fafb', timer: 1500, showConfirmButton: false });
        }
    });
}

// ─── DR number change ─────────────────────────────────────────────────────────
function handleDRNumberChange(input) {
    const rowId = input.dataset.rowId;
    const row = document.getElementById(`payment_row_${rowId}`);
    const drNumber = input.value.trim();
    if (!drNumber) {
        row.querySelector('[data-field="invoice_no"]').value = '';
        row.querySelector('[data-field="outstanding_balance"]').value = '';
        row.querySelector('[data-field="tax"]').value = '';
        row.querySelector('[data-field="discount"]').value = '';
        row.querySelector('[data-field="net"]').value = '';
        row.querySelector('[data-field="payment_type"]').value = 'full';
        delete paymentMeansData[rowId];
        const btn = document.getElementById(`payment_means_btn_${rowId}`);
        const label = document.getElementById(`payment_means_label_${rowId}`);
        if (btn) { btn.classList.remove('bg-green-600', 'hover:bg-green-700'); btn.classList.add('bg-purple-700', 'hover:bg-purple-600'); }
        if (label) label.textContent = 'Set Payment Means';
        return;
    }
    let invoiceList = outstandingInvoices[drNumber];
    if (!invoiceList) {
        const numericDr = parseFloat(drNumber);
        if (!isNaN(numericDr)) {
            const matchingKey = Object.keys(outstandingInvoices).find(key => parseFloat(key) === numericDr);
            if (matchingKey) invoiceList = outstandingInvoices[matchingKey];
        }
    }
    if (invoiceList && invoiceList.length > 1) { showInvoicePicker(drNumber, invoiceList, row, rowId); return; }
    if (invoiceList && invoiceList.length === 1) { applyInvoiceToRow(invoiceList[0], row, rowId); return; }
    const panelMatch = outstandingPaymentsData.find(p => String(p.dr_no).trim() === String(drNumber).trim());
    if (panelMatch) {
        const invoiceInfo = { invoice_no: panelMatch.invoice_no || '', outstanding_balance: parseFloat(panelMatch.gross_amount || panelMatch.check_amount || 0), gross_amount: parseFloat(panelMatch.gross_amount || panelMatch.check_amount || 0), invoice_date: panelMatch.deposit_date || '', due_date: '' };
        outstandingInvoices[drNumber] = [invoiceInfo];
        applyInvoiceToRow(invoiceInfo, row, rowId);
        return;
    }
    checkIfDRAlreadyPaid(drNumber, row, rowId);
}

function showInvoicePicker(drNumber, invoiceList, row, rowId) {
    const tableRows = invoiceList.map((inv, idx) => `
        <tr class="border-b border-gray-700 hover:bg-gray-700 cursor-pointer" onclick="document.getElementById('invoice_pick_${idx}').checked = true">
            <td class="p-2 text-center"><input type="radio" name="invoice_pick" id="invoice_pick_${idx}" value="${idx}" ${idx === 0 ? 'checked' : ''}></td>
            <td class="p-2 text-sm font-semibold text-white">${inv.invoice_no || '—'}</td>
            <td class="p-2 text-sm text-right text-orange-400 font-semibold">₱${inv.outstanding_balance.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
            <td class="p-2 text-xs text-gray-400">${inv.invoice_date || '—'}</td>
            <td class="p-2 text-xs text-gray-400">${inv.due_date || '—'}</td>
        </tr>`).join('');
    Swal.fire({
        title: `Multiple Invoices for DR #${drNumber}`,
        html: `<p class="text-sm text-gray-400 mb-3">Select which invoice to pay:</p>
            <table class="w-full text-left border border-gray-700 rounded">
                <thead class="bg-gray-700"><tr>
                    <th class="p-2 text-xs text-center w-10"></th>
                    <th class="p-2 text-xs text-gray-300">Invoice No</th>
                    <th class="p-2 text-xs text-right text-gray-300">Outstanding</th>
                    <th class="p-2 text-xs text-gray-300">Invoice Date</th>
                    <th class="p-2 text-xs text-gray-300">Due Date</th>
                </tr></thead>
                <tbody>${tableRows}</tbody>
            </table>`,
        background: '#1f2937', color: '#f9fafb', width: '600px',
        showCancelButton: true, confirmButtonText: 'Select', confirmButtonColor: '#2563eb', cancelButtonText: 'Cancel',
        preConfirm: () => {
            const selected = document.querySelector('input[name="invoice_pick"]:checked');
            if (!selected) { Swal.showValidationMessage('Please select an invoice'); return false; }
            return parseInt(selected.value);
        }
    }).then(result => {
        if (result.isConfirmed) applyInvoiceToRow(invoiceList[result.value], row, rowId);
        else row.querySelector('[data-field="dr_number"]').value = '';
    });
}

function applyInvoiceToRow(invoiceInfo, row, rowId) {
    const gross = invoiceInfo.gross_amount || invoiceInfo.outstanding_balance || 0;
    row.querySelector('[data-field="invoice_no"]').value = invoiceInfo.invoice_no || '';
    row.querySelector('[data-field="outstanding_balance"]').value = '₱' + gross.toLocaleString('en-PH', { minimumFractionDigits: 2 });
    row.querySelector('[data-field="payment_type"]').value = 'full';
    fetchCustomerTaxAndCalculateNet(rowId, gross);
    row.classList.remove('border-red-700', 'border-gray-600');
    row.classList.add('border-green-700');
    Swal.fire({
        icon: 'success', title: 'DR Found!',
        html: `<div class="text-left"><p><strong>Invoice:</strong> ${invoiceInfo.invoice_no}</p><p><strong>Outstanding:</strong> ₱${gross.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</p></div>`,
        background: '#1f2937', color: '#f9fafb', timer: 3000, showConfirmButton: false
    });
}

function checkIfDRAlreadyPaid(drNumber, row, rowId) {
    if (!currentCustomer) { showDRNotFound(drNumber, row); return; }
    fetch(`/payments/check-dr-status?customer_code=${encodeURIComponent(currentCustomer.code)}&dr_no=${encodeURIComponent(drNumber)}`, { headers: { 'Accept': 'application/json' } })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'paid') {
            row.querySelector('[data-field="invoice_no"]').value = data.invoice_no || '';
            row.querySelector('[data-field="outstanding_balance"]').value = '₱0.00';
            row.querySelector('[data-field="dr_number"]').value = '';
            row.classList.remove('border-green-700', 'border-red-700');
            row.classList.add('border-gray-600');
            Swal.fire({ icon: 'info', title: 'Fully Paid', html: `<div class="text-left"><p>DR <strong>"${drNumber}"</strong> is already fully paid.</p><p class="mt-2 text-sm text-gray-400">Total Paid: ₱${parseFloat(data.total_paid || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</p></div>`, background: '#1f2937', color: '#f9fafb' });
        } else if (data.status === 'partial') {
            const partialGross = parseFloat(data.invoice_amount || data.remaining);
            row.querySelector('[data-field="invoice_no"]').value = data.invoice_no || '';
            row.querySelector('[data-field="outstanding_balance"]').value = '₱' + partialGross.toLocaleString('en-PH', { minimumFractionDigits: 2 });
            row.querySelector('[data-field="payment_type"]').value = 'full';
            outstandingInvoices[drNumber] = [{ invoice_no: data.invoice_no, outstanding_balance: partialGross, gross_amount: partialGross }];
            fetchCustomerTaxAndCalculateNet(rowId, partialGross);
            row.classList.remove('border-red-700', 'border-gray-600');
            row.classList.add('border-yellow-600');
            Swal.fire({ icon: 'info', title: 'Partial Payment Exists', html: `<div class="text-left"><p>DR <strong>"${drNumber}"</strong> remaining balance:</p><p class="text-lg font-bold text-blue-400 mt-1">₱${partialGross.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</p><p class="text-sm text-gray-400 mt-1">Previously paid: ₱${parseFloat(data.total_paid || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</p></div>`, background: '#1f2937', color: '#f9fafb', timer: 4000, showConfirmButton: false });
        } else if (data.status === 'outstanding') {
            const gross = parseFloat(data.invoice_amount || data.remaining || 0);
            row.querySelector('[data-field="invoice_no"]').value = data.invoice_no || 'PENDING';
            row.querySelector('[data-field="outstanding_balance"]').value = '₱' + gross.toLocaleString('en-PH', { minimumFractionDigits: 2 });
            row.querySelector('[data-field="payment_type"]').value = 'full';
            outstandingInvoices[drNumber] = [{ invoice_no: data.invoice_no || 'PENDING', outstanding_balance: gross, gross_amount: gross }];
            fetchCustomerTaxAndCalculateNet(rowId, gross);
            row.classList.remove('border-red-700', 'border-gray-600');
            row.classList.add('border-green-700');
        } else if (data.status === 'wrong_customer') {
            row.querySelector('[data-field="invoice_no"]').value = '';
            row.querySelector('[data-field="outstanding_balance"]').value = '';
            row.querySelector('[data-field="dr_number"]').value = '';
            row.classList.remove('border-green-700', 'border-gray-600');
            row.classList.add('border-red-700');
            Swal.fire({
                icon: 'warning', title: 'Different Customer',
                html: `<div class="text-left"><p>DR <strong>"${drNumber}"</strong> belongs to a different customer:</p><p class="mt-2 text-lg font-bold" style="color:#f59e0b">${data.actual_customer_name}</p><p class="text-sm mt-1" style="color:#9ca3af">Code: ${data.actual_customer_code}</p><p class="mt-3 text-sm" style="color:#9ca3af">To collect for this DR, create a separate collection under that customer.</p></div>`,
                background: '#1f2937', color: '#f9fafb'
            });
        } else { showDRNotFound(drNumber, row); }
    })
    .catch(err => { console.error('Error checking DR status:', err); showDRNotFound(drNumber, row); });
}

function showDRNotFound(drNumber, row) {
    row.querySelector('[data-field="invoice_no"]').value = '';
    row.querySelector('[data-field="outstanding_balance"]').value = '';
    row.classList.remove('border-green-700', 'border-gray-600');
    row.classList.add('border-red-700');
    Swal.fire({ icon: 'warning', title: 'DR Not Found', text: `DR Number "${drNumber}" not found for this customer.`, background: '#1f2937', color: '#f9fafb' });
}

// ─── Payment type change ──────────────────────────────────────────────────────
function handlePaymentTypeChange(select) {
    const rowId = select.dataset.rowId;
    const row = document.getElementById(`payment_row_${rowId}`);
    const outstanding = parseFloat((row.querySelector('[data-field="outstanding_balance"]').value || '0').replace(/[₱,]/g, '')) || 0;
    if (select.value === 'partial') {
        // Keep tax rate populated; just clear net — it will be recalculated
        // when means is saved based on the means amount
        row.querySelector('[data-field="net"]').value = '';
        if (!row.querySelector('[data-field="tax"]').value && currentCustomer && currentCustomer.whtrate) {
            row.querySelector('[data-field="tax"]').value = parseFloat(currentCustomer.whtrate) || 0;
        }
    } else {
        if (outstanding > 0) fetchCustomerTaxAndCalculateNet(rowId, outstanding);
    }
}

// ─── Net calculation ──────────────────────────────────────────────────────────
// Full payment:  Net = outstanding − discount − EWT
// Partial payment: Net = meansAmount − EWT (based on outstanding for tax base)
function recalculateNet(rowId) {
    const row = document.getElementById(`payment_row_${rowId}`);
    if (!row) return;

    const outstandingInput = row.querySelector('[data-field="outstanding_balance"]');
    const taxInput         = row.querySelector('[data-field="tax"]');
    const discountInput    = row.querySelector('[data-field="discount"]');
    const netInput         = row.querySelector('[data-field="net"]');
    const paymentType      = row.querySelector('[data-field="payment_type"]')?.value || 'full';

    const outstanding    = outstandingInput ? parsePeso(outstandingInput.value) : 0;
    const taxRate        = taxInput    ? (parseFloat(taxInput.value)    || 0) : 0;
    const discountRate   = discountInput ? (parseFloat(discountInput.value) || 0) : 0;

    const discountAmount = outstanding * (discountRate / 100);
    const afterDiscount  = outstanding - discountAmount;
    const ewtAmount      = afterDiscount * (taxRate / 100);

    let net;
    if (paymentType === 'partial') {
        // For partial: means amount is what they paid; net = means − EWT on outstanding
        const meansAmount = parseFloat(paymentMeansData[rowId]?.amount) || 0;
        net = meansAmount > 0 ? Math.max(0, meansAmount - ewtAmount) : 0;
    } else {
        // For full: net = outstanding − discount − EWT
        net = Math.max(0, afterDiscount - ewtAmount);
    }

    if (netInput) netInput.value = '₱' + net.toLocaleString('en-PH', { minimumFractionDigits: 2 });

    // After net is updated, refresh overpayment (means vs net)
    refreshOverpaymentBadge(rowId);
    updatePaymentTotals();
}

function handleTaxChange(input)      { recalculateNet(input.dataset.rowId); }
function handleDiscountChange(input) { recalculateNet(input.dataset.rowId); }

function isGrandUnionCustomer() {
    return currentCustomer && currentCustomer.name && currentCustomer.name.toLowerCase().includes('grand union supermarket');
}

function autoFillDiscount(rowId) {
    if (isGrandUnionCustomer()) {
        const row = document.getElementById(`payment_row_${rowId}`);
        if (row) {
            const discountInput = row.querySelector('[data-field="discount"]');
            if (discountInput && !discountInput.value) discountInput.value = '2';
        }
    }
}

// ─── Update totals ────────────────────────────────────────────────────────────
function updatePaymentTotals() {
    const rows = document.querySelectorAll('#payment_entries_tbody [id^="payment_row_"]');
    const totalsRow = document.getElementById('payment_totals_row');
    if (rows.length < 2) { totalsRow.classList.add('hidden'); return; }
    totalsRow.classList.remove('hidden');
    let totalOutstanding = 0, totalTax = 0, totalDiscount = 0, totalNet = 0;
    rows.forEach(row => {
        const taxField      = row.querySelector('[data-field="tax"]');
        const discountField = row.querySelector('[data-field="discount"]');
        const netField      = row.querySelector('[data-field="net"]');
        const outstanding   = parsePeso(row.querySelector('[data-field="outstanding_balance"]')?.value);
        const discountRate  = discountField ? (parseFloat(discountField.value) || 0) : 0;
        const discountAmt   = outstanding * (discountRate / 100);
        const afterDiscount = outstanding - discountAmt;
        const taxRate       = taxField ? (parseFloat(taxField.value) || 0) : 0;
        const taxAmt        = afterDiscount * (taxRate / 100);
        totalOutstanding   += outstanding;
        totalTax           += taxAmt;
        totalDiscount      += discountAmt;
        if (netField) totalNet += parsePeso(netField.value);
    });
    document.getElementById('total_amount').textContent   = '₱' + totalOutstanding.toLocaleString('en-PH', { minimumFractionDigits: 2 });
    document.getElementById('total_tax').textContent      = totalTax.toFixed(2);
    document.getElementById('total_discount').textContent = '₱' + totalDiscount.toLocaleString('en-PH', { minimumFractionDigits: 2 });
    document.getElementById('total_net').textContent      = '₱' + totalNet.toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

// ─── Save all payments ────────────────────────────────────────────────────────
function saveAllPayments() {
    if (!currentCustomer) {
        Swal.fire({ icon: 'error', title: 'No Customer Selected', text: 'Please search for a customer first.', background: '#1f2937', color: '#f9fafb' });
        return;
    }
    const rows = document.querySelectorAll('#payment_entries_tbody [id^="payment_row_"]');
    let hasErrors = false;

    rows.forEach(row => {
        const rowId         = parseInt(row.id.replace('payment_row_', ''));
        const dr_number     = row.querySelector('[data-field="dr_number"]')?.value;
        const receipt_number = row.querySelector('[data-field="receipt_number"]')?.value;
        const receipt_date  = row.querySelector('[data-field="receipt_date"]')?.value;
        const posting_date  = row.querySelector('[data-field="posting_date"]')?.value;
        const meansAmount   = paymentMeansData[rowId] ? parseFloat(paymentMeansData[rowId].amount) || 0 : 0;

        if (!dr_number || !receipt_number || !receipt_date || !posting_date || meansAmount <= 0 || !paymentMeansData[rowId]) {
            hasErrors = true;
            row.classList.add('border-red-700');
            row.classList.remove('border-green-700', 'border-gray-600');
        } else {
            row.classList.remove('border-red-700');
            row.classList.add('border-green-700');
        }
    });

    if (hasErrors) {
        Swal.fire({ icon: 'error', title: 'Validation Error', text: 'Please fill in all required fields and set payment means for all rows (highlighted in red).', background: '#1f2937', color: '#f9fafb' });
        return;
    }

    // Check full payment rows where means < net
    let fullPaymentMismatch = false;
    let mismatchRowId = null;
    let mismatchNet = 0;
    rows.forEach(row => {
        const paymentTypeSelect = row.querySelector('[data-field="payment_type"]');
        if (paymentTypeSelect?.value !== 'full') return;
        const rowId      = parseInt(row.id.replace('payment_row_', ''));
        const meansAmount = parseFloat(paymentMeansData[rowId]?.amount) || 0;
        const net        = parsePeso(row.querySelector('[data-field="net"]')?.value);
        if (net > 0 && meansAmount > 0 && meansAmount < net && (net - meansAmount) > PAYMENT_LEEWAY) {
            fullPaymentMismatch = true;
            mismatchRowId = row.id;
            mismatchNet = net;
        }
    });

    if (fullPaymentMismatch) {
        const mismatchRow = document.getElementById(mismatchRowId);
        Swal.fire({
            icon: 'error', title: 'Full Payment Incomplete',
            html: `<div class="text-left"><p>A row marked as <strong>Full Payment</strong> has a payment means amount less than the net collectible of <strong>₱${mismatchNet.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</strong>.</p><p class="mt-2 text-sm text-gray-400">Either enter the full net amount or change the Payment Type to <strong>Partial Payment</strong>.</p></div>`,
            background: '#1f2937', color: '#f9fafb', showCancelButton: true,
            confirmButtonText: 'Switch to Partial', cancelButtonText: "I'll fix it",
            confirmButtonColor: '#f59e0b', cancelButtonColor: '#6b7280'
        }).then(result => { if (result.isConfirmed && mismatchRow) mismatchRow.querySelector('[data-field="payment_type"]').value = 'partial'; });
        return;
    }

    // Collect payment data
    const payments = [];
    rows.forEach(row => {
        const rowId             = parseInt(row.id.replace('payment_row_', ''));
        const meansAmount       = parseFloat(paymentMeansData[rowId]?.amount) || 0;
        const taxRatePercent    = parseFloat(row.querySelector('[data-field="tax"]')?.value) || 0;
        const discountRatePercent = parseFloat(row.querySelector('[data-field="discount"]')?.value) || 0;
        const outstandingVal    = parsePeso(row.querySelector('[data-field="outstanding_balance"]')?.value);
        const netVal            = parsePeso(row.querySelector('[data-field="net"]')?.value);
        const discountAmount    = outstandingVal * (discountRatePercent / 100);
        const afterDiscount     = outstandingVal - discountAmount;
        const ewtAmount         = afterDiscount * (taxRatePercent / 100);

        // Overpayment = means amount exceeds the outstanding balance (gross)
        // EWT reduces the check amount, not the obligation — so compare vs outstanding, not net
        // ₱10 leeway — small differences are not overpayments
        const overpaymentRaw = meansAmount > outstandingVal && outstandingVal > 0
            ? parseFloat((meansAmount - outstandingVal).toFixed(2))
            : 0;
        const overpayment = overpaymentRaw > PAYMENT_LEEWAY ? overpaymentRaw : 0;

        payments.push({
            customer_code:              currentCustomer.code,
            customer_name:              currentCustomer.name,
            dr_number:                  row.querySelector('[data-field="dr_number"]')?.value,
            invoice_no:                 row.querySelector('[data-field="invoice_no"]')?.value,
            collection_receipt_number:  row.querySelector('[data-field="receipt_number"]')?.value,
            collection_receipt_date:    row.querySelector('[data-field="receipt_date"]')?.value,
            payment_posting_date:       row.querySelector('[data-field="posting_date"]')?.value,
            payment_means:              paymentMeansData[rowId],
            // amount = the physical payment means amount (check/transfer/cash value)
            amount:                     meansAmount,
            discount_rate:              discountRatePercent,
            discount_amount:            discountAmount,
            tax:                        ewtAmount,
            // net = net collectible (outstanding − discount − EWT)
            net:                        netVal,
            payment_notes:              row.querySelector('[data-field="notes"]')?.value,
            invoice_outstanding:        outstandingVal || null,
            credit_applied:             parseFloat(row.dataset.creditApplied || 0),
            credit_from_payment_id:     row.dataset.creditFromPaymentId ? parseInt(row.dataset.creditFromPaymentId) : null,
            credits:                    (appliedCreditsMap[row.id] || []).map(c => ({ credit_source_payment_id: c.credit_source_payment_id, amount: c.amount })),
        });
    });

    if (payments.length === 0) {
        Swal.fire({ icon: 'warning', title: 'No Payments', text: 'Please add at least one payment entry.', background: '#1f2937', color: '#f9fafb' });
        return;
    }

    // Build confirmation summary
    const typeLabels = { 'check': 'Check', 'bank_transfer': 'Bank Transfer', 'cash': 'Cash' };
    let totalMeansAmt = 0, totalNetAmt = 0, totalOverpayment = 0, summaryRows = '';
    payments.forEach((p, i) => {
        const ovpRaw = p.invoice_outstanding > 0 && p.amount > p.invoice_outstanding ? parseFloat((p.amount - p.invoice_outstanding).toFixed(2)) : 0;
        const ovp = ovpRaw > PAYMENT_LEEWAY ? ovpRaw : 0;
        totalMeansAmt  += p.amount;
        totalNetAmt    += p.net;
        totalOverpayment += ovp;
        summaryRows += `
            <tr class="border-b border-gray-700">
                <td class="py-2 px-2">${i + 1}</td>
                <td class="py-2 px-2">${p.dr_number}</td>
                <td class="py-2 px-2">${p.invoice_no || '—'}</td>
                <td class="py-2 px-2 text-right">${p.invoice_outstanding ? '₱' + parseFloat(p.invoice_outstanding).toLocaleString('en-PH', {minimumFractionDigits: 2}) : '—'}</td>
                <td class="py-2 px-2 text-center"><span class="px-2 py-0.5 rounded text-xs font-semibold bg-blue-900/40 border border-blue-600/50 text-blue-300">${typeLabels[p.payment_means?.type] || '—'}</span></td>
                <td class="py-2 px-2 text-right text-orange-400 font-semibold">₱${p.amount.toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                <td class="py-2 px-2 text-right text-green-400">₱${p.net.toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                ${ovp > 0 ? `<td class="py-2 px-2 text-right text-purple-400">₱${ovp.toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>` : '<td class="py-2 px-2 text-right text-gray-500">—</td>'}
                <td class="py-2 px-2 text-center"><input type="checkbox" data-short-payment-idx="${i}" class="short-payment-checkbox w-4 h-4 rounded bg-gray-700 border-gray-500 text-red-600 cursor-pointer"></td>
            </tr>`;
    });

    const summaryHtml = `
        <div style="max-height: 400px; overflow-y: auto; text-align: left;">
            <div class="mb-3 p-3 bg-gray-900 rounded">
                <p class="text-sm text-gray-300"><strong>Customer:</strong> ${currentCustomer.name}</p>
                <p class="text-sm text-gray-300"><strong>Entries:</strong> ${payments.length} payment(s)</p>
            </div>
            <table class="w-full text-xs border-collapse">
                <thead><tr class="bg-gray-700 text-gray-300">
                    <th class="py-2 px-2 text-left">#</th>
                    <th class="py-2 px-2 text-left">DR No</th>
                    <th class="py-2 px-2 text-left">Invoice</th>
                    <th class="py-2 px-2 text-right">Outstanding</th>
                    <th class="py-2 px-2 text-center">Payment</th>
                    <th class="py-2 px-2 text-right">Means Amt</th>
                    <th class="py-2 px-2 text-right">Net Collectible</th>
                    <th class="py-2 px-2 text-right">Excess/Credit</th>
                    <th class="py-2 px-2 text-center">Short Payment</th>
                </tr></thead>
                <tbody>${summaryRows}</tbody>
                <tfoot><tr class="bg-gray-900 font-bold border-t-2 border-gray-600">
                    <td colspan="5" class="py-2 px-2 text-right text-gray-300">TOTAL</td>
                    <td class="py-2 px-2 text-right text-orange-400">₱${totalMeansAmt.toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                    <td class="py-2 px-2 text-right text-green-400">₱${totalNetAmt.toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                    <td class="py-2 px-2 text-right text-purple-400">${totalOverpayment > 0 ? '₱' + totalOverpayment.toLocaleString('en-PH', {minimumFractionDigits: 2}) : '—'}</td>
                    <td></td>
                </tr></tfoot>
            </table>
        </div>`;

    Swal.fire({
        title: 'Confirm Payment Entries', html: summaryHtml, icon: 'question', width: '900px',
        background: '#1f2937', color: '#f9fafb', showCancelButton: true,
        confirmButtonText: '<i class="fas fa-save mr-1"></i> Confirm & Save', cancelButtonText: 'Cancel',
        confirmButtonColor: '#2563eb', cancelButtonColor: '#6b7280',
        preConfirm: () => {
            // Read short payment checkboxes while dialog is still open
            const flags = [];
            document.querySelectorAll('.short-payment-checkbox').forEach(cb => flags.push(cb.checked));
            return { shortPaymentFlags: flags };
        }
    }).then(async (result) => {
        if (!result.isConfirmed) return;

        // Apply short payment flags captured from preConfirm
        const shortPaymentFlags = result.value?.shortPaymentFlags || [];
        payments.forEach((p, i) => {
            p.is_short_payment = shortPaymentFlags[i] || false;
        });

        Swal.fire({ title: 'Saving...', text: 'Processing payment entries', background: '#1f2937', color: '#f9fafb', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const results   = [];

        for (const payment of payments) {
            try {
                const response = await fetch('/payments/store', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify(payment)
                });
                const responseText = await response.text();
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (parseErr) {
                    console.error(`JSON parse error for DR ${payment.dr_number}:`, parseErr, 'Response:', responseText.substring(0, 500));
                    data = { success: false, message: 'Server error: ' + responseText.substring(0, 200) };
                }
                if (!response.ok && !data.success) {
                    // ✅ Show special SweetAlert for duplicate CR+DR
                    if (data.duplicate) {
                        Swal.fire({
                            icon: 'error',
                            title: 'IT HAS BEEN PAID',
                            html: `<p>A payment with CR# <strong>${payment.collection_receipt_number}</strong> and DR# <strong>${payment.dr_number}</strong> already exists.</p>`,
                            background: '#1f2937', color: '#f9fafb'
                        });
                    }
                    const errorMsg = data.errors ? Object.values(data.errors).flat().join(', ') : (data.message || 'Unknown error');
                    data.success = false;
                    data.message = errorMsg;
                }
                results.push(data);
                if (!data.success) console.error(`Failed DR ${payment.dr_number}:`, data.message);
            } catch (err) {
                console.error(`Network error on DR ${payment.dr_number}:`, err);
                results.push({ success: false, message: err.message });
            }
        }

        const successCount = results.filter(r => r.success).length;
        const failCount    = results.length - successCount;

        if (failCount === 0) {
            Swal.fire({ icon: 'success', title: 'Success!', text: `${successCount} payment(s) saved successfully.`, background: '#1f2937', color: '#f9fafb' }).then(() => location.reload());
        } else {
            const failedRows = results.map((r, i) => !r.success ? `DR ${payments[i].dr_number}: ${r.message || 'Unknown error'}` : null).filter(Boolean).join('<br>');
            Swal.fire({
                icon: 'warning', title: 'Partial Success',
                html: `${successCount} saved, ${failCount} failed:<br><br><div class="text-left text-sm text-red-400">${failedRows}</div>`,
                background: '#1f2937', color: '#f9fafb'
            });
        }
    });
}

// ─── Load payment list ────────────────────────────────────────────────────────
function loadPaymentList() {
    const dateFrom       = document.getElementById('report_date_from').value;
    const dateTo         = document.getElementById('report_date_to').value;
    const customerFilter = document.getElementById('report_customer_filter').value;
    const drFilter       = document.getElementById('report_dr_filter').value;
    const csrfToken      = document.querySelector('meta[name="csrf-token"]')?.content;
    fetch(`/payments/collection-report?date_from=${dateFrom}&date_to=${dateTo}&customer=${encodeURIComponent(customerFilter)}&dr_no=${encodeURIComponent(drFilter)}`, {
        method: 'GET', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const tbody = document.getElementById('payment_list_tbody');
            tbody.innerHTML = '';
            if (!data.payments || data.payments.length === 0) {
                tbody.innerHTML = `<tr><td colspan="12" class="px-4 py-8 text-center text-gray-400"><i class="fas fa-file-invoice text-4xl mb-2 block"></i>No collections found</td></tr>`;
                return;
            }
            data.payments.forEach(payment => {
                const row = document.createElement('tr');
                row.className = 'border-b border-gray-700 hover:bg-gray-900';
                let paymentMeansDisplay   = '—';
                let paymentMeansBadgeClass = 'bg-gray-700 text-gray-300';
                if (payment.payment_method) {
                    const typeLabels = { 'check': 'Check', 'bank_transfer': 'Bank Transfer', 'cash': 'Cash' };
                    paymentMeansDisplay = typeLabels[payment.payment_method] || payment.payment_method;
                    if (payment.payment_method === 'check')         paymentMeansBadgeClass = 'bg-blue-900/40 border border-blue-600/50 text-blue-300';
                    else if (payment.payment_method === 'bank_transfer') paymentMeansBadgeClass = 'bg-green-900/40 border border-green-600/50 text-green-300';
                    else if (payment.payment_method === 'cash')     paymentMeansBadgeClass = 'bg-yellow-900/40 border border-yellow-600/50 text-yellow-300';
                } else if (payment.payment_option) {
                    paymentMeansDisplay   = payment.payment_option;
                    paymentMeansBadgeClass = 'bg-purple-900/40 border border-purple-600/50 text-purple-300';
                }
                const displayAmount = parseFloat(payment.gross_amount || payment.amount || 0);
                const displayEwt    = parseFloat(payment.ewt || payment.tax || 0);
                const displayNet    = parseFloat(payment.check_amount || payment.net || (displayAmount - displayEwt));
                let statusBadge = '<span class="px-2 py-1 rounded text-xs font-semibold bg-yellow-900/40 border border-yellow-600/50 text-yellow-400">Clearing</span>';
                if (payment.status === 'Posted')  statusBadge = '<span class="px-2 py-1 rounded text-xs font-semibold bg-green-900/40 border border-green-600/50 text-green-400">Posted</span>';
                if (payment.status === 'Bounced') statusBadge = '<span class="px-2 py-1 rounded text-xs font-semibold bg-red-900/40 border border-red-600/50 text-red-400">Bounced</span>';
                if (payment.status === 'Voided')  statusBadge = '<span class="px-2 py-1 rounded text-xs font-semibold bg-red-900/40 border border-red-600/50 text-red-400">Voided</span>';
                const shortBadge = payment.is_short_payment ? '<span class="px-2 py-1 rounded text-xs font-semibold bg-orange-900/40 border border-orange-600/50 text-orange-400 ml-1">Short</span>' : '';
                row.innerHTML = `
                    <td class="px-3 py-2 text-white font-medium">${payment.customer_name || '—'}</td>
                    <td class="px-3 py-2 text-gray-300">${payment.collection_receipt_number || '—'}</td>
                    <td class="px-3 py-2 text-gray-300">${payment.dr_no || '—'}</td>
                    <td class="px-3 py-2 text-gray-300">${payment.invoice_no || '—'}</td>
                    <td class="px-3 py-2 text-gray-400">${payment.collection_receipt_date ? new Date(payment.collection_receipt_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'}</td>
                    <td class="px-3 py-2 text-gray-400">${payment.payment_posting_date ? new Date(payment.payment_posting_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'}</td>
                    <td class="px-3 py-2"><span class="px-2 py-1 rounded text-xs font-semibold ${paymentMeansBadgeClass}">${paymentMeansDisplay}</span></td>
                    <td class="px-3 py-2 text-right font-semibold text-white">₱${displayAmount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                    <td class="px-3 py-2 text-right text-orange-400">${displayEwt > 0 ? '₱' + displayEwt.toLocaleString('en-PH', { minimumFractionDigits: 2 }) : '—'}</td>
                    <td class="px-3 py-2 text-right font-semibold text-green-400">₱${displayNet.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                    <td class="px-3 py-2 text-center">${statusBadge}${shortBadge}</td>
                    <td class="px-3 py-2 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="/payments/${payment.id}" class="text-blue-400 hover:text-blue-300 text-xs font-semibold"><i class="fas fa-eye mr-1"></i>View</a>
                            @if(auth()->user()->canEditPayments() || auth()->user()->canRequestPaymentEdit())
                            ${payment.status !== 'Voided' ? `<a href="/payments/${payment.id}/edit" class="text-orange-400 hover:text-orange-300 text-xs font-semibold"><i class="fas fa-edit mr-1"></i>Edit</a>` : ''}
                            @endif
                        </div>
                    </td>`;
                tbody.appendChild(row);
            });
        }
    })
    .catch(error => console.error('Error:', error));
}

function filterPaymentList() { loadPaymentList(); }
document.getElementById('report_dr_filter').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') filterPaymentList();
});
document.getElementById('report_customer_filter').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') filterPaymentList();
});
function viewAllPayments() {
    document.getElementById('report_date_from').value = '';
    document.getElementById('report_date_to').value   = '';
    document.getElementById('report_customer_filter').value = '';
    document.getElementById('report_dr_filter').value = '';
    loadPaymentList();
}
function exportPaymentList() {
    const dateFrom       = document.getElementById('report_date_from').value;
    const dateTo         = document.getElementById('report_date_to').value;
    const customerFilter = document.getElementById('report_customer_filter').value;
    const drFilter       = document.getElementById('report_dr_filter').value;
    Swal.fire({ icon: 'info', title: 'Export Started', text: 'Payment list will be downloaded shortly.', background: '#1f2937', color: '#f9fafb', timer: 2000, showConfirmButton: false });
    window.location.href = `/payments/export?date_from=${dateFrom}&date_to=${dateTo}&customer=${encodeURIComponent(customerFilter)}&dr_no=${encodeURIComponent(drFilter)}`;
}

// ─── Load outstanding payments ────────────────────────────────────────────────
function loadCustomerPaymentHistory(customerCode, customerName) {
    document.getElementById('history_customer_name').textContent = customerName;
    selectedOutstandingPayments.clear();
    outstandingPaymentsData = [];
    updateSelectedCount();
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    fetch(`/payments/customer-history?customer_code=${encodeURIComponent(customerCode)}&status=outstanding`, {
        method: 'GET', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
    })
    .then(response => response.json())
    .then(data => {
        const tbody = document.getElementById('customer_payment_history_tbody');
        if (!data.success || !data.payments || data.payments.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="px-4 py-6 text-center text-gray-400"><i class="fas fa-check-circle text-2xl mb-1 text-green-500 block"></i>No outstanding payments — all invoices paid!</td></tr>`;
            return;
        }
        outstandingPaymentsData = data.payments;
        tbody.innerHTML = '';
        let totalOutstanding = 0;
        data.payments.forEach(payment => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-700 cursor-pointer transition';
            row.onclick = (e) => { if (e.target.type !== 'checkbox') toggleOutstandingPayment(payment.dr_no); };
            const depositDate   = payment.deposit_date ? new Date(payment.deposit_date).toLocaleDateString('en-PH', {month:'short',day:'2-digit',year:'numeric'}) : '—';
            const checkAmount   = parseFloat(payment.check_amount || 0);
            totalOutstanding   += checkAmount;
            const isLocked = payment.is_locked;
            const statusLabel = isLocked ? 'Locked' : (payment.status || 'Outstanding');
            const statusColor = isLocked
                ? 'bg-red-500/20 text-red-400'
                : ({ 'Outstanding': 'bg-orange-500/20 text-orange-400', 'Partial': 'bg-yellow-500/20 text-yellow-400', 'Paid': 'bg-green-500/20 text-green-400' }[payment.status] || 'bg-gray-500/20 text-gray-400');
            const adjApplied = payment.ar_adjustment_applied != null ? parseFloat(payment.ar_adjustment_applied) : null;
            const adjDetail  = payment.ar_adjustment_detail || '';
            const adjHtml = adjApplied !== null && adjApplied !== 0
                ? `<div class="text-xs ${adjApplied < 0 ? 'text-green-400' : 'text-red-400'} mt-0.5" title="${adjDetail}">
                       (Adjusted ${adjApplied < 0 ? '' : '+'}₱${adjApplied.toLocaleString('en-PH', { minimumFractionDigits: 2 })})
                   </div>`
                : '';
            row.innerHTML = `
                <td class="px-2 py-1.5" onclick="event.stopPropagation()">
                    <input type="checkbox" data-payment-checkbox data-dr-no="${payment.dr_no}" onchange="toggleOutstandingPayment('${payment.dr_no}')" class="w-3.5 h-3.5 rounded bg-gray-700 border-gray-600 text-blue-600 cursor-pointer" ${isLocked ? 'disabled title="This DR is locked"' : ''}>
                </td>
                <td class="px-2 py-1.5 text-gray-400 whitespace-nowrap">${depositDate}</td>
                <td class="px-2 py-1.5 font-semibold ${isLocked ? 'text-red-400' : 'text-blue-400'} whitespace-nowrap">${payment.dr_no || '—'}${isLocked ? ' <i class="fas fa-lock text-xs ml-1"></i>' : ''}</td>
                <td class="px-2 py-1.5 text-gray-300 whitespace-nowrap">${payment.invoice_no || '—'}</td>
                <td class="px-2 py-1.5 text-gray-400 whitespace-nowrap">${payment.branch || '—'}</td>
                <td class="px-2 py-1.5 text-right font-semibold text-orange-400 whitespace-nowrap">
                    ₱${checkAmount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}
                    ${adjHtml}
                </td>
                <td class="px-2 py-1.5 text-center whitespace-nowrap"><span class="px-2 py-0.5 rounded text-xs font-medium ${statusColor}">${statusLabel}</span></td>`;
            tbody.appendChild(row);
        });
        const totalRow = document.createElement('tr');
        totalRow.className = 'bg-gray-900 border-t-2 border-orange-500 font-bold';
        totalRow.innerHTML = `<td></td><td colspan="4" class="px-2 py-2 text-right text-gray-300 text-xs">TOTAL OUTSTANDING:</td><td class="px-2 py-2 text-right text-orange-400 font-bold">₱${totalOutstanding.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td><td></td>`;
        tbody.appendChild(totalRow);
    })
    .catch(error => {
        console.error('Error loading outstanding payments:', error);
        document.getElementById('customer_payment_history_tbody').innerHTML = `<tr><td colspan="7" class="px-4 py-6 text-center text-red-400"><i class="fas fa-exclamation-triangle text-2xl mb-1 block"></i>Failed to load outstanding payments</td></tr>`;
    });
}

function togglePaymentHistory() {
    const content = document.getElementById('payment_history_content');
    const icon    = document.getElementById('history_toggle_icon');
    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.classList.replace('fa-chevron-right', 'fa-chevron-down');
    } else {
        content.style.display = 'none';
        icon.classList.replace('fa-chevron-down', 'fa-chevron-right');
    }
}

document.addEventListener('DOMContentLoaded', () => loadPaymentList());

// ─── Payment means panel ──────────────────────────────────────────────────────
function openPaymentMeansPanel(rowId) {
    currentEditingRowId = rowId;
    const row = document.getElementById(`payment_row_${rowId}`);
    if (!row) return;
    document.getElementById('panel_row_number').textContent  = `#${rowId}`;
    document.getElementById('panel_dr_number').textContent   = row.querySelector('[data-field="dr_number"]').value || '—';
    if (paymentMeansData[rowId]) {
        const data = paymentMeansData[rowId];
        document.getElementById('panel_payment_type').value = data.type;
        updatePaymentMeansFields();
        if (data.type === 'check') {
            document.getElementById('check_gl_account').value        = data.gl_account || '';
            document.getElementById('check_gl_account_search').value = data.gl_account_name || data.gl_account || '';
            document.getElementById('check_gl_account_id').value     = data.gl_account_id || '';
            document.getElementById('check_due_date').value          = data.due_date || '';
            document.getElementById('check_amount').value            = data.amount || '';
            document.getElementById('check_bank_name').value         = data.bank_name || '';
            document.getElementById('check_number').value            = data.check_number || '';
        } else if (data.type === 'bank_transfer') {
            document.getElementById('transfer_gl_account').value        = data.gl_account || '';
            document.getElementById('transfer_gl_account_search').value = data.gl_account_name || data.gl_account || '';
            document.getElementById('transfer_gl_account_id').value     = data.gl_account_id || '';
            document.getElementById('transfer_date').value              = data.transfer_date || '';
            document.getElementById('transfer_reference').value         = data.reference || '';
            document.getElementById('transfer_amount').value            = data.amount || '';
        } else if (data.type === 'cash') {
            document.getElementById('cash_gl_account').value        = data.gl_account || '';
            document.getElementById('cash_gl_account_search').value = data.gl_account_name || data.gl_account || '';
            document.getElementById('cash_gl_account_id').value     = data.gl_account_id || '';
            document.getElementById('cash_deposit_date').value      = data.deposit_date || '';
            document.getElementById('cash_amount').value            = data.amount || '';
        }
    } else {
        document.getElementById('panel_payment_type').value = '';
        updatePaymentMeansFields();
    }
    document.getElementById('payment_means_panel').classList.remove('translate-x-full');
    document.getElementById('panel_overlay').classList.remove('hidden');
}

function closePaymentMeansPanel() {
    document.getElementById('payment_means_panel').classList.add('translate-x-full');
    document.getElementById('panel_overlay').classList.add('hidden');
    currentEditingRowId = null;
}

function updatePaymentMeansFields() {
    const paymentType = document.getElementById('panel_payment_type').value;
    ['check_fields', 'bank_transfer_fields', 'cash_fields'].forEach(id => document.getElementById(id).classList.add('hidden'));
    if (paymentType === 'check') {
        document.getElementById('check_fields').classList.remove('hidden');
        if (!document.getElementById('check_due_date').value) document.getElementById('check_due_date').value = new Date().toISOString().split('T')[0];
    } else if (paymentType === 'bank_transfer') {
        document.getElementById('bank_transfer_fields').classList.remove('hidden');
        if (!document.getElementById('transfer_date').value) document.getElementById('transfer_date').value = new Date().toISOString().split('T')[0];
    } else if (paymentType === 'cash') {
        document.getElementById('cash_fields').classList.remove('hidden');
        if (!document.getElementById('cash_deposit_date').value) document.getElementById('cash_deposit_date').value = new Date().toISOString().split('T')[0];
    }
}

function savePaymentMeans() {
    if (!currentEditingRowId) return;
    const paymentType = document.getElementById('panel_payment_type').value;
    if (!paymentType) {
        Swal.fire({ icon: 'warning', title: 'Payment Type Required', text: 'Please select a payment means type.', background: '#1f2937', color: '#f9fafb' });
        return;
    }
    let paymentData = { type: paymentType };
    let isValid = true, errorMessage = '';
    if (paymentType === 'check') {
        paymentData.gl_account_name = document.getElementById('check_gl_account_search').value;
        paymentData.gl_account      = document.getElementById('check_gl_account').value || paymentData.gl_account_name;
        paymentData.gl_account_id   = document.getElementById('check_gl_account_id').value;
        paymentData.due_date        = document.getElementById('check_due_date').value;
        paymentData.amount          = document.getElementById('check_amount').value;
        paymentData.bank_name       = document.getElementById('check_bank_name').value;
        paymentData.check_number    = document.getElementById('check_number').value;
        if (!paymentData.gl_account || !paymentData.due_date || !paymentData.amount || !paymentData.bank_name || !paymentData.check_number)
            { isValid = false; errorMessage = 'Please fill in all required check fields.'; }
    } else if (paymentType === 'bank_transfer') {
        paymentData.gl_account_name = document.getElementById('transfer_gl_account_search').value;
        paymentData.gl_account      = document.getElementById('transfer_gl_account').value || paymentData.gl_account_name;
        paymentData.gl_account_id   = document.getElementById('transfer_gl_account_id').value;
        paymentData.transfer_date   = document.getElementById('transfer_date').value;
        paymentData.reference       = document.getElementById('transfer_reference').value;
        paymentData.amount          = document.getElementById('transfer_amount').value;
        if (!paymentData.gl_account || !paymentData.transfer_date || !paymentData.amount)
            { isValid = false; errorMessage = 'Please fill in all required bank transfer fields.'; }
    } else if (paymentType === 'cash') {
        paymentData.gl_account_name = document.getElementById('cash_gl_account_search').value;
        paymentData.gl_account      = document.getElementById('cash_gl_account').value || paymentData.gl_account_name;
        paymentData.gl_account_id   = document.getElementById('cash_gl_account_id').value;
        paymentData.deposit_date    = document.getElementById('cash_deposit_date').value;
        paymentData.amount          = document.getElementById('cash_amount').value;
        if (!paymentData.gl_account || !paymentData.deposit_date || !paymentData.amount)
            { isValid = false; errorMessage = 'Please fill in all required cash fields.'; }
    }
    if (!isValid) {
        Swal.fire({ icon: 'error', title: 'Validation Error', text: errorMessage, background: '#1f2937', color: '#f9fafb' });
        return;
    }
    paymentMeansData[currentEditingRowId] = paymentData;

    const typeLabels = { 'check': 'Check', 'bank_transfer': 'Bank Transfer', 'cash': 'Cash' };
    const label = document.getElementById(`payment_means_label_${currentEditingRowId}`);
    const btn   = document.getElementById(`payment_means_btn_${currentEditingRowId}`);
    if (label && btn) {
        label.textContent = typeLabels[paymentType];
        btn.classList.remove('bg-purple-700', 'hover:bg-purple-600');
        btn.classList.add('bg-green-600', 'hover:bg-green-700');
    }

    // ✅ After saving payment means, recalculate net then refresh overpayment
    recalculateNet(currentEditingRowId);
    closePaymentMeansPanel();
    Swal.fire({ icon: 'success', title: 'Saved!', text: 'Payment means details have been saved.', background: '#1f2937', color: '#f9fafb', timer: 1500, showConfirmButton: false });
}

function copyBalanceDueToCheck() {
    if (!currentEditingRowId) return;
    const row = document.getElementById(`payment_row_${currentEditingRowId}`);
    const net = parsePeso(row.querySelector('[data-field="net"]')?.value);
    // Copy the net collectible (after EWT/discount), since EWT is withheld by the customer
    document.getElementById('check_amount').value = (net > 0 ? net : parsePeso(row.querySelector('[data-field="outstanding_balance"]').value)).toFixed(2);
}
function copyBalanceDueToTransfer() {
    if (!currentEditingRowId) return;
    const row = document.getElementById(`payment_row_${currentEditingRowId}`);
    const net = parsePeso(row.querySelector('[data-field="net"]')?.value);
    document.getElementById('transfer_amount').value = (net > 0 ? net : parsePeso(row.querySelector('[data-field="outstanding_balance"]').value)).toFixed(2);
}
function copyBalanceDueToCash() {
    if (!currentEditingRowId) return;
    const row = document.getElementById(`payment_row_${currentEditingRowId}`);
    const net = parsePeso(row.querySelector('[data-field="net"]')?.value);
    document.getElementById('cash_amount').value = (net > 0 ? net : parsePeso(row.querySelector('[data-field="outstanding_balance"]').value)).toFixed(2);
}

// ─── Tax / net calculation ────────────────────────────────────────────────────
function fetchCustomerTaxAndCalculateNet(rowId, grossAmount) {
    const row = document.getElementById(`payment_row_${rowId}`);
    if (!row) return;
    const taxInput      = row.querySelector('[data-field="tax"]');
    const discountInput = row.querySelector('[data-field="discount"]');

    if (currentCustomer && currentCustomer.whtrate) {
        if (taxInput) taxInput.value = parseFloat(currentCustomer.whtrate) || 0;
    } else {
        if (taxInput) taxInput.value = '0';
    }

    if (currentCustomer && currentCustomer.name && currentCustomer.name.toLowerCase().includes('grand union supermarket')) {
        if (discountInput) discountInput.value = '2';
    }

    recalculateNet(rowId);
}

// ─── GL Account search ────────────────────────────────────────────────────────
setupGlAccountSearch('check_gl_account_search',    'check_gl_account_dropdown',    'check_gl_account_id',    'check_gl_account');
setupGlAccountSearch('transfer_gl_account_search', 'transfer_gl_account_dropdown', 'transfer_gl_account_id', 'transfer_gl_account');
setupGlAccountSearch('cash_gl_account_search',     'cash_gl_account_dropdown',     'cash_gl_account_id',     'cash_gl_account');

function setupGlAccountSearch(searchId, dropdownId, idInputId, codeInputId) {
    const searchInput = document.getElementById(searchId);
    const dropdown    = document.getElementById(dropdownId);
    const idInput     = document.getElementById(idInputId);
    const codeInput   = document.getElementById(codeInputId);
    let searchTimeout, allAccountsCache = null;
    if (!searchInput) return;

    async function fetchAndRenderAccounts(query) {
        try {
            const res  = await fetch(`/ar-adjustments/gl-accounts?search=${encodeURIComponent(query || '')}`);
            const data = await res.json();  
            if (data.success && data.accounts.length > 0) {
                if (!query) allAccountsCache = data.accounts;
                dropdown.innerHTML = data.accounts.map(a => `
                    <div class="px-3 py-2 hover:bg-gray-600 cursor-pointer border-b border-gray-700 bg-gray-800"
                         onmousedown="selectPaymentGlAccount('${searchId}','${dropdownId}','${idInputId}','${codeInputId}',${a.id},'${a.display.replace(/'/g,"\\'")}','${(a.code||'').replace(/'/g,"\\'")}')">
                        <div class="font-semibold text-sm text-white">${a.display}</div>
                        <div class="text-xs text-gray-400">${a.fs_line_item || 'No FS Item'}</div>
                    </div>`).join('');
            } else {
                dropdown.innerHTML = '<div class="px-3 py-2 text-gray-400 bg-gray-800">No GL accounts found</div>';
            }
            dropdown.classList.remove('hidden');
        } catch (e) { console.error('Error fetching GL accounts:', e); }
    }

    searchInput.addEventListener('focus', function() {
        if (allAccountsCache) {
            dropdown.innerHTML = allAccountsCache.map(a => `
                <div class="px-3 py-2 hover:bg-gray-600 cursor-pointer border-b border-gray-700 bg-gray-800"
                     onmousedown="selectPaymentGlAccount('${searchId}','${dropdownId}','${idInputId}','${codeInputId}',${a.id},'${a.display.replace(/'/g,"\\'")}','${(a.code||'').replace(/'/g,"\\'")}')">
                    <div class="font-semibold text-sm text-white">${a.display}</div>
                    <div class="text-xs text-gray-400">${a.fs_line_item || 'No FS Item'}</div>
                </div>`).join('');
            dropdown.classList.remove('hidden');
        } else { fetchAndRenderAccounts(''); }
    });
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => fetchAndRenderAccounts(this.value.trim()), 200);
    });
    document.addEventListener('mousedown', function(e) {
        if (e.target !== searchInput && !dropdown.contains(e.target)) dropdown.classList.add('hidden');
    });
}

function selectPaymentGlAccount(searchId, dropdownId, idInputId, codeInputId, id, display, code) {
    document.getElementById(idInputId).value  = id;
    document.getElementById(codeInputId).value = code || display;
    document.getElementById(searchId).value   = display;
    document.getElementById(dropdownId).classList.add('hidden');
}

// ─── Credit balance ───────────────────────────────────────────────────────────
function loadCustomerCredits(customerCode, customerName) {
    availableCredits  = [];
    totalCreditBalance = 0;
    document.getElementById('credit_balance_container').classList.add('hidden');
    document.getElementById('credit_details_panel').classList.add('hidden');
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    fetch(`/payments/customer-credits?customer_code=${encodeURIComponent(customerCode||'')}&customer_name=${encodeURIComponent(customerName||'')}`, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.total > 0) {
            availableCredits  = data.credits;
            totalCreditBalance = data.total;
            document.getElementById('display_credit_balance').textContent = '₱' + data.total.toLocaleString('en-PH', { minimumFractionDigits: 2 });
            document.getElementById('credit_balance_container').classList.remove('hidden');
        }
    })
    .catch(err => console.error('Error loading credits:', err));
}

function hideCreditDetails() { document.getElementById('credit_details_panel').classList.add('hidden'); }

function showCreditDetails() {
    const panel = document.getElementById('credit_details_panel');
    const list  = document.getElementById('credit_details_list');
    list.innerHTML = '';
    if (!availableCredits.some(c => c.remaining_credit > 0)) {
        list.innerHTML = `<p class="text-gray-400 text-xs">${availableCredits.length === 0 ? 'No available credits.' : 'All credits have been applied.'}</p>`;
        panel.classList.remove('hidden');
        return;
    }
    availableCredits.forEach(c => {
        if (c.remaining_credit <= 0) return;
        const div = document.createElement('div');
        div.className = 'flex items-center justify-between bg-gray-900 rounded p-3 border border-purple-500 mb-2';
        div.innerHTML = `
            <div>
                <a href="/payments/${c.payment_id}" target="_blank" class="text-blue-400 hover:text-blue-300 underline text-xs font-semibold">CR #${c.collection_receipt_number}</a>
                <span class="text-gray-400 text-xs ml-2">${c.invoice_no ? 'Inv #'+c.invoice_no : (c.dr_no ? 'DR #'+c.dr_no : '')}</span>
                <span class="text-gray-500 text-xs ml-2">${c.date ? new Date(c.date).toLocaleDateString('en-US', {month:'short',day:'numeric',year:'numeric'}) : ''}</span>
            </div>
            <div class="flex items-center gap-3 shrink-0 ml-3">
                <span class="text-sm font-bold text-purple-300">₱${c.remaining_credit.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</span>
                <button type="button" onclick="applyCreditToRow(${c.payment_id}, ${c.remaining_credit})" class="bg-purple-600 hover:bg-purple-500 text-white px-3 py-1.5 rounded text-xs font-semibold transition">Apply</button>
            </div>`;
        list.appendChild(div);
    });
    panel.classList.remove('hidden');
}

function applyCreditToRow(creditPaymentId, creditAmount) {
    const rows = document.querySelectorAll('#payment_entries_tbody [id^="payment_row_"]');
    if (rows.length === 0) { Swal.fire({ icon: 'error', title: 'No Payment Row', text: 'Please add a payment row first.', background: '#1f2937', color: '#f9fafb' }); return; }
    const firstRow = rows[0];
    const rowId    = firstRow.id;
    const netInput = firstRow.querySelector('[data-field="net"]');
    if (!netInput) { Swal.fire({ icon: 'warning', title: 'No Net Amount', text: 'Please set the DR number first.', background: '#1f2937', color: '#f9fafb' }); return; }
    const currentNet = parsePeso(netInput.value);
    if (currentNet <= 0) { Swal.fire({ icon: 'warning', title: 'No Net Amount', text: 'Please set the DR number first so net is calculated.', background: '#1f2937', color: '#f9fafb' }); return; }
    if (!appliedCreditsMap[rowId]) appliedCreditsMap[rowId] = [];
    const alreadyApplied = appliedCreditsMap[rowId].reduce((sum, c) => sum + c.amount, 0);
    const remainingNet   = Math.max(0, currentNet - alreadyApplied);
    if (remainingNet <= 0) { Swal.fire({ icon: 'info', title: 'Fully Covered', text: 'The net amount is already fully covered by applied credits.', background: '#1f2937', color: '#f9fafb' }); return; }
    const toApply    = Math.min(creditAmount, remainingNet);
    const creditInfo = availableCredits.find(c => c.payment_id === creditPaymentId);
    const crNumber   = creditInfo ? creditInfo.collection_receipt_number : creditPaymentId;
    appliedCreditsMap[rowId].push({ credit_source_payment_id: creditPaymentId, amount: toApply, cr_number: crNumber });
    const totalCredit = appliedCreditsMap[rowId].reduce((sum, c) => sum + c.amount, 0);
    firstRow.dataset.creditApplied        = totalCredit;
    firstRow.dataset.creditFromPaymentId  = appliedCreditsMap[rowId][0].credit_source_payment_id;
    const newNet = Math.max(0, currentNet - toApply);
    netInput.value = '₱' + newNet.toLocaleString('en-PH', { minimumFractionDigits: 2 });
    let badge = firstRow.querySelector('.credit-badge');
    if (!badge) { badge = document.createElement('div'); badge.className = 'credit-badge text-xs text-purple-300 font-semibold mt-1'; netInput.parentElement.appendChild(badge); }
    badge.innerHTML = `<span class="text-purple-400">Credit applied:</span><br><span class="text-gray-300">${appliedCreditsMap[rowId].map(c => `CR #${c.cr_number}: -₱${c.amount.toLocaleString('en-PH',{minimumFractionDigits:2})}`).join('<br>')}</span><br><span class="text-purple-300 font-bold">Total: -₱${totalCredit.toLocaleString('en-PH',{minimumFractionDigits:2})}</span>`;
    if (creditInfo) creditInfo.remaining_credit -= toApply;
    showCreditDetails();
    refreshCreditBalanceDisplay();
    updatePaymentTotals();
    Swal.fire({
        icon: 'success', title: 'Credit Applied!',
        html: `<div class="text-left"><p>Applied <strong class="text-purple-300">₱${toApply.toLocaleString('en-PH',{minimumFractionDigits:2})}</strong> from CR #${crNumber}</p><p class="mt-1">New Net: <strong class="text-green-400">₱${newNet.toLocaleString('en-PH',{minimumFractionDigits:2})}</strong></p></div>`,
        background: '#1f2937', color: '#f9fafb', timer: 3000, showConfirmButton: false
    });
}
</script>

@endsection
