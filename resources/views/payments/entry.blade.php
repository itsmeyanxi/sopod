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
            <div class="flex items-center space-x-4">
                <div class="flex-1">
                    <input type="text" id="customer_search" placeholder="Enter Customer Name" 
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
                    <div class="flex items-end">
                        <button type="button" onclick="filterPaymentList()" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                            Apply Filter
                        </button>
                    </div>
                </div>
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
                            <th class="px-4 py-3 text-left">Payment Option</th>
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
            <!-- Customer Payment History -->
            <div id="customer_payment_history" class="bg-gray-700 rounded-lg p-4 mb-4">
                <div class="flex justify-between items-center mb-3">
                    <h4 class="text-sm font-semibold text-white flex items-center">
                        <i class="fas fa-history mr-2 text-blue-400"></i>
                        Payment History for <span id="history_customer_name" class="ml-2 text-blue-300"></span>
                    </h4>
                    <button type="button" onclick="togglePaymentHistory()" class="text-gray-400 hover:text-white text-xs">
                        <i class="fas fa-chevron-down" id="history_toggle_icon"></i>
                    </button>
                </div>

                <div id="payment_history_content" class="overflow-x-auto" style="max-height: 300px;">
                    <table class="min-w-full bg-gray-800 rounded-lg text-xs">
                        <thead class="bg-gray-900 text-gray-300 sticky top-0">
                            <tr>
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
                                <td colspan="18" class="px-4 py-8 text-center text-gray-400">
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
                <h4 class="text-sm font-semibold text-white mb-3 flex items-center">
                    <i class="fas fa-user mr-2"></i>
                    Customer Information
                </h4>
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
                                <th class="px-2 py-2 text-left">Collection Receipt Number *</th>
                                <th class="px-2 py-2 text-left">Collection Receipt Date *</th>
                                <th class="px-2 py-2 text-left">Payment Posting Date *</th>
                                <th class="px-2 py-2 text-left">Payment Option *</th>
                                <th class="px-2 py-2 text-right">Amount *</th>
                                <th class="px-2 py-2 text-right">Tax</th>
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

<script>
let currentCustomer = null;
let paymentRowCounter = 0;
let originalOutstandingBalance = 0;
let currentView = 'payment_list'; // ✅ Track current view

// ✅ Toggle between views
document.getElementById('toggle_view_btn').addEventListener('click', function() {
    if (currentView === 'payment_list') {
        // Switch to payment entries view
        switchToPaymentEntries();
    } else {
        // Switch to payment list view
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
    
    // Reload payment list
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
            totalPayments += parseFloat(amountInput.value) || 0;
        }
    });
    
    const newBalance = originalOutstandingBalance - totalPayments;
    const balanceElement = document.getElementById('display_outstanding_balance');
    
    // Update display with color coding
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

// ✅ UPDATED: Search customer - automatically switch to payment entries view
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
            
            document.getElementById('display_customer_name').textContent = currentCustomer.name;
            document.getElementById('display_outstanding_balance').textContent = '₱' + parseFloat(currentCustomer.outstanding_balance).toLocaleString('en-PH', { minimumFractionDigits: 2 });

            // Reset color
            const balanceElement = document.getElementById('display_outstanding_balance');
            balanceElement.classList.remove('text-green-400', 'text-yellow-400');
            balanceElement.classList.add('text-red-400');

            // ✅ Load payment history for this customer
            loadCustomerPaymentHistory(currentCustomer.code, currentCustomer.name);

            // ✅ Switch to payment entries view
            switchToPaymentEntries();

            // Clear previous entries and add one empty row
            document.getElementById('payment_entries_tbody').innerHTML = '';
            paymentRowCounter = 0;
            addPaymentRow();
        } else {
            // Show no results message
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

// Allow Enter key to trigger search
document.getElementById('customer_search').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('search_customer_btn').click();
    }
});

// Add a new payment row
function addPaymentRow() {
    paymentRowCounter++;
    const tbody = document.getElementById('payment_entries_tbody');
    const today = new Date().toISOString().split('T')[0];
    
    const row = document.createElement('tr');
    row.className = 'border-b border-gray-700';
    row.id = `payment_row_${paymentRowCounter}`;
    row.innerHTML = `
        <td class="px-2 py-1.5 text-xs">${paymentRowCounter}</td>
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
            <select class="w-full bg-gray-700 text-white border border-gray-600 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500" 
                    data-field="payment_option">
                <option value="">Select</option>
                <option value="Full Payment">Full Payment</option>
                <option value="Partial Payment">Partial Payment</option>
            </select>
        </td>
        <td class="px-2 py-1.5">
            <input type="number" 
                   step="0.01" 
                   min="0"
                   class="w-full bg-gray-700 text-white border border-gray-600 rounded px-2 py-1 text-xs text-right focus:ring-1 focus:ring-blue-500" 
                   placeholder="0.00"
                   data-field="amount"
                   oninput="updateOutstandingBalance()">
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
                   class="w-full bg-gray-700 text-white border border-gray-600 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500" 
                   placeholder="Notes"
                   data-field="notes">
        </td>
        <td class="px-2 py-1.5 text-center">
            <button type="button" onclick="deletePaymentRow(${paymentRowCounter})" class="text-red-400 hover:text-red-300 text-xs">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    
    tbody.appendChild(row);
}

// Delete a payment row
function deletePaymentRow(rowId) {
    const row = document.getElementById(`payment_row_${rowId}`);
    if (row) {
        row.remove();
        updateOutstandingBalance();
    }
}

// Save all payments
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

    rows.forEach((row, index) => {
        const receipt_number = row.querySelector('[data-field="receipt_number"]')?.value;
        const receipt_date = row.querySelector('[data-field="receipt_date"]')?.value;
        const posting_date = row.querySelector('[data-field="posting_date"]')?.value;
        const payment_option = row.querySelector('[data-field="payment_option"]')?.value;
        const amount = row.querySelector('[data-field="amount"]')?.value;
        const tax = row.querySelector('[data-field="tax"]')?.value;
        const notes = row.querySelector('[data-field="notes"]')?.value;

        if (!receipt_number || !receipt_date || !posting_date || !payment_option || !amount || parseFloat(amount) <= 0) {
            hasErrors = true;
            row.classList.add('bg-red-900', 'bg-opacity-20');
            return;
        } else {
            row.classList.remove('bg-red-900', 'bg-opacity-20');
        }

        payments.push({
            customer_name: currentCustomer.name,
            collection_receipt_number: receipt_number,
            collection_receipt_date: receipt_date,
            payment_posting_date: posting_date,
            payment_option: payment_option,
            amount: parseFloat(amount),
            tax: tax ? parseFloat(tax) : 0,
            payment_notes: notes
        });
    });

    if (hasErrors) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please fill in all required fields (highlighted in red).',
            background: '#1f2937',
            color: '#fff'
        });
        return;
    }

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
                text: `${successCount} payment(s) saved successfully.`,
                background: '#1f2937',
                color: '#fff'
            }).then(() => {
                document.getElementById('payment_entries_tbody').innerHTML = '';
                paymentRowCounter = 0;
                
                const totalSaved = payments.reduce((sum, p) => sum + p.amount, 0);
                originalOutstandingBalance -= totalSaved;
                
                updateOutstandingBalance();
                addPaymentRow();
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
    const today = new Date();
    const thirtyDaysAgo = new Date(today);
    thirtyDaysAgo.setDate(today.getDate() - 30);
    
    // Set default dates if not already set
    if (!document.getElementById('report_date_from').value) {
        document.getElementById('report_date_from').valueAsDate = thirtyDaysAgo;
    }
    if (!document.getElementById('report_date_to').value) {
        document.getElementById('report_date_to').valueAsDate = today;
    }
    
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
                row.innerHTML = `
                    <td class="px-4 py-3">${payment.customer_name}</td>
                    <td class="px-4 py-3">${payment.collection_receipt_number}</td>
                    <td class="px-4 py-3">${new Date(payment.collection_receipt_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
                    <td class="px-4 py-3">${new Date(payment.payment_posting_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
                    <td class="px-4 py-3">${payment.payment_option}</td>
                    <td class="px-4 py-3 text-right font-semibold">₱${parseFloat(payment.amount).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                    <td class="px-4 py-3 text-right">${payment.tax ? '₱' + parseFloat(payment.tax).toLocaleString('en-PH', { minimumFractionDigits: 2 }) : '—'}</td>
                `;
                tbody.appendChild(row);
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Filter payment list
function filterPaymentList() {
    loadPaymentList();
}

// Export payment list
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

// ✅ Load payment history for a specific customer
function loadCustomerPaymentHistory(customerCode, customerName) {
    document.getElementById('history_customer_name').textContent = customerName;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    fetch(`/payments/customer-history?customer_code=${encodeURIComponent(customerCode)}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        const tbody = document.getElementById('customer_payment_history_tbody');

        if (!data.success || !data.payments || data.payments.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="18" class="px-4 py-6 text-center text-gray-400">
                        <i class="fas fa-inbox text-3xl mb-2"></i>
                        <p>No payment history found for this customer</p>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = '';
        data.payments.forEach(payment => {
            const row = document.createElement('tr');
            row.className = 'border-b border-gray-700 hover:bg-gray-750';

            const depositDate = payment.deposit_date ? new Date(payment.deposit_date).toLocaleDateString('en-US') : 'N/A';
            const grossAmount = parseFloat(payment.gross_amount || 0);
            const ewt = parseFloat(payment.ewt || 0);
            const otherAdj = parseFloat(payment.other_adjustment || 0);
            const factoring = parseFloat(payment.factoring || 0);
            const checkAmount = parseFloat(payment.check_amount || 0);
            const netOfCwt = parseFloat(payment.net_of_cwt || 0);

            // Format other_adjustment with + or - sign and color
            let otherAdjDisplay = '—';
            let otherAdjClass = 'text-yellow-400';
            if (otherAdj !== 0) {
                otherAdjClass = otherAdj > 0 ? 'text-green-400' : 'text-red-400';
                otherAdjDisplay = (otherAdj > 0 ? '+' : '') + '₱' + Math.abs(otherAdj).toLocaleString('en-PH', { minimumFractionDigits: 2 });
            }

            row.innerHTML = `
                <td class="px-2 py-2">${depositDate}</td>
                <td class="px-2 py-2"><span class="bg-green-900/30 border border-green-700/50 px-2 py-1 rounded font-mono text-xs">${payment.collection_receipt_number || '—'}</span></td>
                <td class="px-2 py-2">${payment.invoice_no || '—'}</td>
                <td class="px-2 py-2">${payment.dr_no || '—'}</td>
                <td class="px-2 py-2 text-xs">${payment.customer_name || '—'}</td>
                <td class="px-2 py-2 text-xs">${payment.branch || 'N/A'}</td>
                <td class="px-2 py-2 text-right font-semibold">₱${grossAmount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                <td class="px-2 py-2 text-right text-orange-400">₱${ewt.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                <td class="px-2 py-2 text-right ${otherAdjClass}">${otherAdjDisplay}</td>
                <td class="px-2 py-2 text-right ${factoring !== 0 ? 'text-purple-400' : ''}">—</td>
                <td class="px-2 py-2 text-right font-semibold text-green-400">₱${checkAmount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                <td class="px-2 py-2 text-right">—</td>
                <td class="px-2 py-2">${payment.week_no || '—'}</td>
                <td class="px-2 py-2">${payment.ar_class || '—'}</td>
                <td class="px-2 py-2">${payment.bank || 'BDO'}</td>
                <td class="px-2 py-2">${payment.checking_si || 'OK'}</td>
                <td class="px-2 py-2">${payment.status || 'Posted'}</td>
                <td class="px-2 py-2 text-xs">${payment.remarks || 'N/A'}</td>
            `;
            tbody.appendChild(row);
        });
    })
    .catch(error => {
        console.error('Error loading payment history:', error);
        const tbody = document.getElementById('customer_payment_history_tbody');
        tbody.innerHTML = `
            <tr>
                <td colspan="18" class="px-4 py-6 text-center text-red-400">
                    <i class="fas fa-exclamation-triangle text-3xl mb-2"></i>
                    <p>Failed to load payment history</p>
                </td>
            </tr>
        `;
    });
}

// ✅ Toggle payment history visibility
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

// ✅ Initialize: Load payment list on page load
document.addEventListener('DOMContentLoaded', function() {
    loadPaymentList();
});
</script>
@endsection