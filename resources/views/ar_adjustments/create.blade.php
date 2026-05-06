@extends('layouts.app')

@section('title', 'Create AR Adjustment')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
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

        @if(session('error'))
            <div class="bg-red-900/60 border border-red-600 text-red-200 px-4 py-3 rounded mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            </div>
        @endif

        <form action="{{ route('ar_adjustments.store') }}" method="POST" id="adjustmentForm" enctype="multipart/form-data">
            @csrf

            <!-- Transaction Date & Reference Number -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">Transaction Date: <span class="text-red-700">*</span></label>
                    <input type="date" name="transaction_date" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('transaction_date', date('Y-m-d')) }}" required>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">Reference Number:</label>
                    <input type="text" name="reference_number" class="w-full bg-gray-700 border border-gray-700 rounded px-3 py-2 text-gray-300 cursor-not-allowed" value="{{ $nextRefNumber }}" readonly>
                    <p class="text-xs text-gray-400 mt-1">Auto-generated on save</p>
                </div>
            </div>

            <!-- Transaction Type & Customer Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">Transaction Type: <span class="text-red-700">*</span></label>
                    <select name="transaction_type" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" required>
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
                        <option value="quantity_variants" {{ old('transaction_type') == 'quantity_variants' ? 'selected' : '' }}>Quantity Variants</option>
                        <option value="short_payment" {{ old('transaction_type') == 'short_payment' ? 'selected' : '' }}>Short Payment</option>
                        <option value="factoring" {{ old('transaction_type') == 'factoring' ? 'selected' : '' }}>Factoring</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">Customer Name: <span class="text-red-700">*</span></label>
                    <input type="text" name="customer_name" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Enter customer name" value="{{ old('customer_name') }}" required>
                </div>
            </div>

            <!-- Link Receiving Report -->
            <div class="mb-6 p-4 bg-purple-900/20 border border-purple-700 rounded-lg">
                <label class="block font-semibold text-purple-300 mb-2"><i class="fas fa-link mr-1"></i> Link Receiving Report (optional)</label>
                <div class="relative">
                    <input type="text" id="rrSearchInput" autocomplete="off"
                        class="w-full bg-gray-800 border border-purple-300 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="Search by RR number, DR number, customer name, or SO number...">
                    <div id="rrDropdown" class="absolute z-50 w-full bg-gray-800 border border-gray-700 rounded mt-1 shadow-lg hidden max-h-72 overflow-y-auto"></div>
                </div>
                <!-- Selected RR preview -->
                <div id="rrPreview" class="hidden mt-3 p-3 bg-gray-800 rounded border border-purple-700">
                    <div class="flex justify-between items-start">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm flex-1">
                            <div>
                                <p class="text-xs text-gray-300">RR Number</p>
                                <p class="font-semibold text-purple-400" id="rrPreviewRR"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-300">DR / Delivery Batch</p>
                                <p class="font-semibold text-white" id="rrPreviewDR"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-300">Customer</p>
                                <p class="font-semibold text-white" id="rrPreviewCustomer"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-300">RR Total Amount</p>
                                <p class="font-semibold text-green-400" id="rrPreviewAmount"></p>
                            </div>
                        </div>
                        <button type="button" id="rrClearBtn" class="text-red-500 hover:text-red-700 ml-3 text-sm">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                </div>
                <input type="hidden" name="receiving_report_id" id="receivingReportId">
            </div>

            <!-- Factoring Section (shown only when transaction_type = factoring) -->
            <div id="factoringSection" class="hidden mb-6">
                <div class="p-5 bg-indigo-900/20 border border-indigo-700 rounded-lg">
                    <h3 class="text-lg font-semibold text-indigo-300 mb-4"><i class="fas fa-file-invoice-dollar mr-2"></i>Factoring Details</h3>

                    <!-- DR Search inside Factoring -->
                    <div class="mb-4">
                        <label class="block font-semibold text-indigo-200 mb-2"><i class="fas fa-search mr-1"></i> Search DR Number</label>
                        <div class="relative">
                            <input type="text" id="factoringDrSearch" autocomplete="off"
                                class="w-full bg-gray-800 border border-indigo-500 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="Search by DR number, customer name, or SO number...">
                            <div id="factoringDrDropdown" class="absolute z-50 w-full bg-gray-800 border border-gray-700 rounded mt-1 shadow-lg hidden max-h-72 overflow-y-auto"></div>
                        </div>
                        <!-- Selected DR preview -->
                        <div id="factoringDrPreview" class="hidden mt-3 p-3 bg-gray-800 rounded border border-indigo-700">
                            <div class="flex justify-between items-start">
                                <div class="grid grid-cols-2 md:grid-cols-5 gap-3 text-sm flex-1">
                                    <div>
                                        <p class="text-xs text-gray-300">DR Number</p>
                                        <p class="font-semibold text-blue-400" id="factoringDrPreviewDR"></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-300">Customer</p>
                                        <p class="font-semibold text-white" id="factoringDrPreviewCustomer"></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-300">Branch</p>
                                        <p class="font-semibold text-white" id="factoringDrPreviewBranch"></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-300">Amount</p>
                                        <p class="font-semibold text-green-400" id="factoringDrPreviewAmount"></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-300">Status</p>
                                        <p class="font-semibold" id="factoringDrPreviewStatus"></p>
                                    </div>
                                </div>
                                <button type="button" id="factoringDrClearBtn" class="text-red-500 hover:text-red-700 ml-3 text-sm">
                                    <i class="fas fa-times"></i> Clear
                                </button>
                            </div>
                        </div>
                        <input type="hidden" name="delivery_id" id="factoringDeliveryId">
                        <p class="text-xs text-gray-400 mt-1">Select a DR to filter factoring file data by that delivery</p>
                    </div>

                    <!-- File Upload -->
                    <div class="mb-4">
                        <label class="block font-semibold text-gray-300 mb-2">Upload Collections File (XLSX):</label>
                        <div class="border-2 border-dashed border-indigo-600 rounded-lg p-4 text-center hover:border-indigo-400 transition cursor-pointer" id="factoringDropZone">
                            <input type="file" name="factoring_file" id="factoringFileInput" accept=".xlsx,.xls,.csv" class="hidden">
                            <div id="factoringUploadPlaceholder">
                                <i class="fas fa-file-excel text-3xl text-indigo-400 mb-2"></i>
                                <p class="text-sm text-gray-300">Click or drag & drop to upload collections file</p>
                                <p class="text-xs text-gray-400 mt-1">XLSX, XLS, CSV (max 50MB)</p>
                            </div>
                            <div id="factoringFilePreview" class="hidden">
                                <i class="fas fa-file-excel text-2xl text-green-400 mb-1"></i>
                                <p class="text-sm font-medium text-gray-200" id="factoringFileName"></p>
                                <p class="text-xs text-gray-400" id="factoringFileSize"></p>
                                <button type="button" id="factoringRemoveFile" class="text-red-500 text-xs mt-1 hover:underline">
                                    <i class="fas fa-times mr-1"></i>Remove
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Parsed Data Preview (populated after file upload) -->
                    <div id="factoringDataPreview" class="hidden">
                        <div class="flex justify-between items-center mb-3">
                            <h4 class="text-sm font-semibold text-indigo-300"><i class="fas fa-table mr-1"></i> Parsed Factoring Data</h4>
                            <span class="text-xs text-gray-400" id="factoringRowCount"></span>
                        </div>
                        <div class="overflow-x-auto max-h-96 overflow-y-auto">
                            <table class="min-w-full text-xs bg-gray-900 border border-gray-700 rounded">
                                <thead class="bg-gray-700 sticky top-0">
                                    <tr>
                                        <th class="px-2 py-2 text-left text-gray-300">DR No</th>
                                        <th class="px-2 py-2 text-left text-gray-300">Customer</th>
                                        <th class="px-2 py-2 text-left text-gray-300">Deposited Date</th>
                                        <th class="px-2 py-2 text-left text-gray-300">CR/OR</th>
                                        <th class="px-2 py-2 text-left text-gray-300">Bank Deposited</th>
                                        <th class="px-2 py-2 text-right text-gray-300">Check Amount</th>
                                        <th class="px-2 py-2 text-right text-gray-300">EWT</th>
                                        <th class="px-2 py-2 text-right text-gray-300">Rebate</th>
                                        <th class="px-2 py-2 text-right text-gray-300">Overpayment</th>
                                        <th class="px-2 py-2 text-right text-gray-300">Short Payment</th>
                                        <th class="px-2 py-2 text-right text-indigo-300 font-bold">Factoring</th>
                                        <th class="px-2 py-2 text-right text-gray-300">Refund</th>
                                        <th class="px-2 py-2 text-right text-gray-300">Small Balance</th>
                                        <th class="px-2 py-2 text-right text-gray-300">Others (CM)</th>
                                        <th class="px-2 py-2 text-right text-gray-300">Total Collection</th>
                                        <th class="px-2 py-2 text-left text-gray-300">Remarks</th>
                                        <th class="px-2 py-2 text-left text-gray-300">Week</th>
                                    </tr>
                                </thead>
                                <tbody id="factoringTableBody"></tbody>
                                <tfoot class="bg-gray-800 border-t-2 border-gray-600">
                                    <tr class="font-bold text-sm">
                                        <td colspan="5" class="px-2 py-2 text-right text-gray-300">TOTALS</td>
                                        <td class="px-2 py-2 text-right text-white" id="factoringTotalCheck">0.00</td>
                                        <td class="px-2 py-2 text-right text-white" id="factoringTotalEwt">0.00</td>
                                        <td class="px-2 py-2 text-right text-white" id="factoringTotalRebate">0.00</td>
                                        <td class="px-2 py-2 text-right text-white" id="factoringTotalOverpayment">0.00</td>
                                        <td class="px-2 py-2 text-right text-white" id="factoringTotalShort">0.00</td>
                                        <td class="px-2 py-2 text-right text-indigo-300" id="factoringTotalFactoring">0.00</td>
                                        <td class="px-2 py-2 text-right text-white" id="factoringTotalRefund">0.00</td>
                                        <td class="px-2 py-2 text-right text-white" id="factoringTotalSmallBal">0.00</td>
                                        <td class="px-2 py-2 text-right text-white" id="factoringTotalOthers">0.00</td>
                                        <td class="px-2 py-2 text-right text-green-400" id="factoringTotalCollection">0.00</td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Manual Factoring Fields -->
                    <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4" id="factoringFields">
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">Deposited Date</label>
                            <input type="date" name="factoring_deposited_date" class="w-full bg-gray-900 border border-gray-700 rounded px-2 py-1.5 text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" value="{{ old('factoring_deposited_date') }}">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">CR/OR</label>
                            <input type="text" name="factoring_cr_or" class="w-full bg-gray-900 border border-gray-700 rounded px-2 py-1.5 text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="CR/OR number" value="{{ old('factoring_cr_or') }}">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">Bank Deposited</label>
                            <input type="text" name="factoring_bank" class="w-full bg-gray-900 border border-gray-700 rounded px-2 py-1.5 text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="Bank name" value="{{ old('factoring_bank') }}">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">Check Amount</label>
                            <input type="number" step="0.01" name="factoring_check_amount" class="w-full bg-gray-900 border border-gray-700 rounded px-2 py-1.5 text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="0.00" value="{{ old('factoring_check_amount') }}">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">EWT</label>
                            <input type="number" step="0.01" name="factoring_ewt" class="w-full bg-gray-900 border border-gray-700 rounded px-2 py-1.5 text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="0.00" value="{{ old('factoring_ewt') }}">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">Rebate</label>
                            <input type="number" step="0.01" name="factoring_rebate" class="w-full bg-gray-900 border border-gray-700 rounded px-2 py-1.5 text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="0.00" value="{{ old('factoring_rebate') }}">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">Overpayment</label>
                            <input type="number" step="0.01" name="factoring_overpayment" class="w-full bg-gray-900 border border-gray-700 rounded px-2 py-1.5 text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="0.00" value="{{ old('factoring_overpayment') }}">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">Short Payment</label>
                            <input type="number" step="0.01" name="factoring_short_payment" class="w-full bg-gray-900 border border-gray-700 rounded px-2 py-1.5 text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="0.00" value="{{ old('factoring_short_payment') }}">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-indigo-300 mb-1 font-bold">Factoring Amount</label>
                            <input type="number" step="0.01" name="factoring_amount" class="w-full bg-gray-900 border border-indigo-600 rounded px-2 py-1.5 text-indigo-300 text-sm font-bold focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="0.00" value="{{ old('factoring_amount') }}">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">Refund</label>
                            <input type="number" step="0.01" name="factoring_refund" class="w-full bg-gray-900 border border-gray-700 rounded px-2 py-1.5 text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="0.00" value="{{ old('factoring_refund') }}">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">Small Balance (+/-10)</label>
                            <input type="number" step="0.01" name="factoring_small_balance" class="w-full bg-gray-900 border border-gray-700 rounded px-2 py-1.5 text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="0.00" value="{{ old('factoring_small_balance') }}">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">Others (CM)</label>
                            <input type="number" step="0.01" name="factoring_others" class="w-full bg-gray-900 border border-gray-700 rounded px-2 py-1.5 text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="0.00" value="{{ old('factoring_others') }}">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">Total Collection</label>
                            <input type="number" step="0.01" name="factoring_total_collection" class="w-full bg-gray-900 border border-gray-700 rounded px-2 py-1.5 text-green-400 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="0.00" value="{{ old('factoring_total_collection') }}" readonly>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">Week</label>
                            <input type="text" name="factoring_week" class="w-full bg-gray-900 border border-gray-700 rounded px-2 py-1.5 text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="Week #" value="{{ old('factoring_week') }}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Code & Branch -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">Customer Code:</label>
                    <input type="text" name="customer_code" id="customerCodeInput" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Customer code (optional)" value="{{ old('customer_code') }}">
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">Branch:</label>
                    <input type="text" name="branch" id="branchInput" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Branch (optional)" value="{{ old('branch') }}">
                </div>
            </div>

            <!-- DR & Invoice Numbers -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">DR Number:</label>
                    <input type="text" name="dr_no" id="drNoInput" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Delivery Report number (optional)" value="{{ old('dr_no') }}">
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">Invoice Number:</label>
                    <input type="text" name="invoice_number" id="invoiceNumberInput" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Invoice number (optional)" value="{{ old('invoice_number') }}">
                </div>
            </div>

            <!-- Amount & GL Account -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div id="amountFieldWrapper">
        <label class="block font-semibold text-gray-300 mb-2">Amount: <span class="text-red-700">*</span></label>
        <div class="relative">
            <span class="absolute left-3 top-2 text-gray-300 text-lg">₱</span>
            <input type="text" name="amount" id="amountInput"
                class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 pl-8 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                placeholder="0.00 (use - or () for increase)"
                value="{{ old('amount') }}" required>
        </div>
        <p class="text-xs text-gray-300 mt-1">Enter positive to decrease AR, negative/() to increase AR</p>
    </div>

    <div>
    <label class="block font-semibold text-gray-300 mb-2">GL Account: <span class="text-red-700">*</span></label>

    <input type="hidden" name="gl_account_id" id="glAccountId" value="{{ old('gl_account_id') }}">
    <input type="hidden" name="gl_account"    id="glAccountCode" value="{{ old('gl_account') }}">

    <div class="relative gl-search-container">

        {{-- Search input --}}
        <div class="relative">
            <input
                type="text"
                id="gl_search_input"
                class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 pr-10 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-purple-500"
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
            class="absolute z-50 w-full bg-gray-800 border border-gray-700 rounded mt-1 shadow-lg hidden max-h-64 overflow-y-auto">

            <div class="sticky top-0 bg-gray-700 px-3 py-2 text-xs text-gray-300 font-semibold border-b border-gray-700">
                Select a GL Account
            </div>

            @foreach($glAccounts as $account)
                <div
                    class="gl-option px-4 py-3 hover:bg-purple-600 hover:text-white cursor-pointer text-white border-b border-gray-100 last:border-b-0 transition-colors"
                    data-id="{{ $account['id'] }}"
                    data-code="{{ $account['code'] }}"
                    data-name="{{ $account['name'] }}"
                    data-display="{{ $account['display'] }}"
                    data-search="{{ strtolower($account['code'] . ' ' . $account['name'] . ' ' . ($account['fs_line_item'] ?? '')) }}">
                    <div class="font-semibold text-sm">{{ $account['code'] }}
                        <span class="font-normal text-gray-300"> — {{ $account['name'] }}</span>
                    </div>
                    <div class="text-xs text-gray-400">{{ $account['fs_line_item'] ?? 'No FS Item' }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>

            <!-- Signed By -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">Signed By: <span class="text-red-700">*</span></label>
                <input type="text" name="signed_by" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Name of person who signed" value="{{ old('signed_by') }}" required>
            </div>

            <!-- Remarks & Attachment -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">Remarks:</label>
                    <textarea name="remarks" rows="4" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Enter any remarks...">{{ old('remarks') }}</textarea>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">Supporting Document:</label>
                    <div class="border-2 border-dashed border-gray-600 rounded-lg p-4 text-center hover:border-purple-400 transition" id="dropZone">
                        <input type="file" name="attachment" id="attachmentInput" accept=".pdf,.png,.jpg,.jpeg" class="hidden">
                        <div id="uploadPlaceholder">
                            <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                            <p class="text-sm text-gray-300">Click or drag & drop to upload</p>
                            <p class="text-xs text-gray-400 mt-1">PDF, PNG, JPG (max 5MB)</p>
                        </div>
                        <div id="filePreview" class="hidden">
                            <i class="fas fa-file text-2xl text-purple-600 mb-1"></i>
                            <p class="text-sm font-medium text-gray-200" id="fileName"></p>
                            <p class="text-xs text-gray-400" id="fileSize"></p>
                            <button type="button" id="removeFile" class="text-red-500 text-xs mt-1 hover:underline">
                                <i class="fas fa-times mr-1"></i>Remove
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Box -->
            <div class="mb-6 p-4 bg-blue-900/20 border border-blue-700 rounded">
                <p class="text-blue-300 text-sm"><i class="fas fa-info-circle mr-2"></i><strong>Amount Format:</strong> Enter amount as positive number to decrease (credit) AR balance. Use negative sign (-) or parentheses () to increase (debit) AR balance.</p>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('ar_adjustments.index') }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-600 transition">
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
            <div class="rr-option px-4 py-3 hover:bg-purple-900/40 cursor-pointer border-b border-gray-700 last:border-0 transition"
                data-rr='${JSON.stringify(rr).replace(/'/g, "&#39;")}'>
                <div class="flex justify-between items-center">
                    <div>
                        <span class="font-semibold text-purple-400 text-sm">${rr.rr_number}</span>
                        <span class="text-gray-500 mx-1">|</span>
                        <span class="text-gray-300 text-sm">DR: ${rr.delivery_batch || '—'}</span>
                    </div>
                    <span class="text-green-400 font-semibold text-sm">₱${Number(rr.total_amount).toLocaleString('en', {minimumFractionDigits: 2})}</span>
                </div>
                <div class="text-xs text-gray-400 mt-1">
                    ${rr.customer_name || '—'} ${rr.customer_code ? '(' + rr.customer_code + ')' : ''}
                    <span class="text-gray-500 ml-1">${rr.received_date || ''}</span>
                    <span class="ml-1 px-1.5 py-0.5 rounded text-xs ${rr.status === 'Received' ? 'bg-green-900/40 text-green-300' : 'bg-yellow-900/40 text-yellow-300'}">${rr.status}</span>
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

    // ================= FACTORING DR SEARCH =================
    const factoringDrSearch = document.getElementById('factoringDrSearch');
    const factoringDrDropdown = document.getElementById('factoringDrDropdown');
    const factoringDrPreview = document.getElementById('factoringDrPreview');
    const factoringDrClearBtn = document.getElementById('factoringDrClearBtn');
    const factoringDeliveryId = document.getElementById('factoringDeliveryId');
    let factoringDrTimeout = null;
    let selectedDrNo = null; // Track selected DR for filtering factoring file
    let allFactoringRows = []; // Store all parsed rows for re-filtering

    factoringDrSearch.addEventListener('input', function() {
        clearTimeout(factoringDrTimeout);
        const val = this.value.trim();
        if (val.length < 2) {
            factoringDrDropdown.classList.add('hidden');
            return;
        }
        factoringDrTimeout = setTimeout(() => fetchFactoringDrResults(val), 300);
    });

    async function fetchFactoringDrResults(search) {
        try {
            const res = await fetch(`{{ route('ar_adjustments.search_delivery') }}?search=${encodeURIComponent(search)}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            });
            const data = await res.json();
            renderFactoringDrDropdown(data.results || []);
        } catch (e) {
            console.error('DR search failed:', e);
        }
    }

    function renderFactoringDrDropdown(results) {
        if (!results.length) {
            factoringDrDropdown.innerHTML = '<div class="px-4 py-3 text-center text-gray-400 text-sm">No deliveries found</div>';
            factoringDrDropdown.classList.remove('hidden');
            return;
        }

        factoringDrDropdown.innerHTML = results.map(d => `
            <div class="factoring-dr-option px-4 py-3 hover:bg-indigo-900/40 cursor-pointer border-b border-gray-700 last:border-0 transition"
                data-delivery='${JSON.stringify(d).replace(/'/g, "&#39;")}'>
                <div class="flex justify-between items-center">
                    <div>
                        <span class="font-semibold text-blue-400 text-sm">DR ${d.dr_no}</span>
                        <span class="text-gray-500 mx-1">|</span>
                        <span class="text-gray-300 text-sm">${d.customer_name || '—'}</span>
                        ${d.branch ? `<span class="text-gray-500 text-xs ml-1">(${d.branch})</span>` : ''}
                    </div>
                    <span class="text-green-400 font-semibold text-sm">₱${Number(d.total_amount || 0).toLocaleString('en', {minimumFractionDigits: 2})}</span>
                </div>
                <div class="text-xs text-gray-400 mt-1">
                    SO: ${d.sales_order_number || '—'}
                    <span class="text-gray-500 ml-1">${d.delivery_date || ''}</span>
                    <span class="ml-1 px-1.5 py-0.5 rounded text-xs ${d.status === 'Delivered' ? 'bg-green-900/40 text-green-300' : 'bg-yellow-900/40 text-yellow-300'}">${d.status || '—'}</span>
                </div>
            </div>
        `).join('');

        factoringDrDropdown.querySelectorAll('.factoring-dr-option').forEach(opt => {
            opt.addEventListener('click', function() {
                const d = JSON.parse(this.dataset.delivery);
                selectFactoringDr(d);
            });
        });

        factoringDrDropdown.classList.remove('hidden');
    }

    function selectFactoringDr(d) {
        factoringDeliveryId.value = d.id;
        selectedDrNo = String(d.dr_no).trim();

        // Fill form fields
        document.querySelector('[name="customer_name"]').value = d.customer_name || '';
        document.getElementById('customerCodeInput').value = d.customer_code || '';
        document.getElementById('branchInput').value = d.branch || '';
        document.getElementById('drNoInput').value = d.dr_no || '';
        if (d.sales_invoice_no) {
            document.getElementById('invoiceNumberInput').value = d.sales_invoice_no;
        }

        // Show preview
        document.getElementById('factoringDrPreviewDR').textContent = d.dr_no;
        document.getElementById('factoringDrPreviewCustomer').textContent = `${d.customer_name || '—'} (${d.customer_code || '—'})`;
        document.getElementById('factoringDrPreviewBranch').textContent = d.branch || '—';
        document.getElementById('factoringDrPreviewAmount').textContent = '₱' + Number(d.total_amount || 0).toLocaleString('en', {minimumFractionDigits: 2});

        const statusEl = document.getElementById('factoringDrPreviewStatus');
        statusEl.textContent = d.status || '—';
        statusEl.className = 'font-semibold ' + (d.status === 'Delivered' ? 'text-green-400' : 'text-yellow-400');

        factoringDrPreview.classList.remove('hidden');
        factoringDrDropdown.classList.add('hidden');
        factoringDrSearch.value = 'DR ' + d.dr_no + ' — ' + (d.customer_name || '');

        // If file was already parsed, re-filter by the new DR
        if (allFactoringRows.length > 0) {
            renderFactoringData(allFactoringRows);
        }
    }

    factoringDrClearBtn.addEventListener('click', function() {
        factoringDeliveryId.value = '';
        factoringDrSearch.value = '';
        selectedDrNo = null;
        factoringDrPreview.classList.add('hidden');
        // Re-render all rows if file was parsed
        if (allFactoringRows.length > 0) {
            renderFactoringData(allFactoringRows);
        }
    });

    // Close DR dropdown on outside click
    document.addEventListener('click', function(e) {
        if (!factoringDrSearch.contains(e.target) && !factoringDrDropdown.contains(e.target)) {
            factoringDrDropdown.classList.add('hidden');
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

    // ================= FACTORING SECTION TOGGLE =================
    const transactionTypeSelect = document.querySelector('[name="transaction_type"]');
    const factoringSection = document.getElementById('factoringSection');
    const amountFieldWrapper = document.getElementById('amountFieldWrapper');
    const amountInput = document.getElementById('amountInput');

    function toggleFactoringView(type) {
        if (type === 'factoring') {
            factoringSection.classList.remove('hidden');
            // Hide the amount wrapper visually but keep the input in the DOM so its value still submits
            amountFieldWrapper.style.display = 'none';
            amountInput.removeAttribute('required');
        } else {
            factoringSection.classList.add('hidden');
            amountFieldWrapper.style.display = '';
            amountInput.setAttribute('required', 'required');
        }
    }

    transactionTypeSelect.addEventListener('change', function() {
        toggleFactoringView(this.value);
    });
    // Trigger on load for old() values
    toggleFactoringView(transactionTypeSelect.value);

    // Sync factoring_amount to hidden amountInput when manually typed
    const factoringAmountField = document.querySelector('[name="factoring_amount"]');
    if (factoringAmountField) {
        factoringAmountField.addEventListener('input', function() {
            if (transactionTypeSelect.value === 'factoring') {
                amountInput.value = this.value || '0';
            }
        });
    }

    // ================= FACTORING FILE UPLOAD =================
    const factoringDropZone = document.getElementById('factoringDropZone');
    const factoringFileInput = document.getElementById('factoringFileInput');
    const factoringUploadPlaceholder = document.getElementById('factoringUploadPlaceholder');
    const factoringFilePreviewEl = document.getElementById('factoringFilePreview');
    const factoringRemoveFile = document.getElementById('factoringRemoveFile');

    factoringDropZone.addEventListener('click', () => factoringFileInput.click());
    factoringDropZone.addEventListener('dragover', (e) => { e.preventDefault(); factoringDropZone.classList.add('border-indigo-400', 'bg-indigo-900/20'); });
    factoringDropZone.addEventListener('dragleave', () => { factoringDropZone.classList.remove('border-indigo-400', 'bg-indigo-900/20'); });
    factoringDropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        factoringDropZone.classList.remove('border-indigo-400', 'bg-indigo-900/20');
        if (e.dataTransfer.files.length) {
            factoringFileInput.files = e.dataTransfer.files;
            handleFactoringFile(e.dataTransfer.files[0]);
        }
    });
    factoringFileInput.addEventListener('change', function() {
        if (this.files.length) handleFactoringFile(this.files[0]);
    });

    factoringRemoveFile.addEventListener('click', (e) => {
        e.stopPropagation();
        factoringFileInput.value = '';
        factoringFilePreviewEl.classList.add('hidden');
        factoringUploadPlaceholder.classList.remove('hidden');
        document.getElementById('factoringDataPreview').classList.add('hidden');
    });

    function handleFactoringFile(file) {
        const validTypes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'text/csv'
        ];
        if (!validTypes.includes(file.type) && !file.name.match(/\.(xlsx|xls|csv)$/i)) {
            Swal.fire({ icon: 'error', title: 'Invalid File', text: 'Only XLSX, XLS, and CSV files are allowed.', background: '#1f2937', color: '#f9fafb' });
            factoringFileInput.value = '';
            return;
        }
        if (file.size > 50 * 1024 * 1024) {
            Swal.fire({ icon: 'error', title: 'File Too Large', text: 'Maximum file size is 50MB.', background: '#1f2937', color: '#f9fafb' });
            factoringFileInput.value = '';
            return;
        }
        document.getElementById('factoringFileName').textContent = file.name;
        document.getElementById('factoringFileSize').textContent = (file.size / 1024).toFixed(1) + ' KB';
        factoringUploadPlaceholder.classList.add('hidden');
        factoringFilePreviewEl.classList.remove('hidden');

        // Upload and parse server-side
        parseFactoringFile(file);
    }

    function parseFactoringFile(file) {
        const formData = new FormData();
        formData.append('factoring_file', file);

        Swal.fire({ title: 'Parsing file...', text: 'Reading collections data', background: '#1f2937', color: '#f9fafb', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        fetch('{{ route("ar_adjustments.parse_factoring") }}', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            Swal.close();
            if (data.success && data.rows && data.rows.length > 0) {
                renderFactoringData(data.rows);
                const filterMsg = selectedDrNo ? ` Filtered by DR ${selectedDrNo}.` : '';
                Swal.fire({ icon: 'success', title: 'File Parsed', text: `Found ${data.rows.length} row(s) with data.${filterMsg}`, background: '#1f2937', color: '#f9fafb', timer: 2500, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'warning', title: 'No Data', text: data.message || 'No rows found in the file.', background: '#1f2937', color: '#f9fafb' });
            }
        })
        .catch(err => {
            Swal.close();
            console.error('Parse error:', err);
            Swal.fire({ icon: 'error', title: 'Parse Failed', text: 'Failed to parse the file. Please check the format.', background: '#1f2937', color: '#f9fafb' });
        });
    }

    function fmtNum(val) {
        const n = parseFloat(val) || 0;
        return n !== 0 ? n.toLocaleString('en-PH', { minimumFractionDigits: 2 }) : '';
    }

    function excelDateToStr(serial) {
        if (!serial || isNaN(serial)) return serial || '';
        const d = new Date((serial - 25569) * 86400 * 1000);
        return d.toLocaleDateString('en-PH', { month: 'short', day: '2-digit', year: 'numeric' });
    }

    function renderFactoringData(rows) {
        // Store all rows for re-filtering when DR changes
        allFactoringRows = rows;

        const tbody = document.getElementById('factoringTableBody');
        let totals = { check: 0, ewt: 0, rebate: 0, overpayment: 0, short: 0, factoring: 0, refund: 0, small_bal: 0, others: 0, total: 0 };

        // Filter by selected DR if one is chosen
        let displayRows = rows;
        if (selectedDrNo) {
            displayRows = rows.filter(r => {
                const rowDr = String(r.dr_no || '').trim();
                return rowDr === selectedDrNo || rowDr.includes(selectedDrNo) || selectedDrNo.includes(rowDr);
            });
        }

        tbody.innerHTML = displayRows.map(r => {
            totals.check += parseFloat(r.check_amount) || 0;
            totals.ewt += parseFloat(r.ewt) || 0;
            totals.rebate += parseFloat(r.rebate) || 0;
            totals.overpayment += parseFloat(r.overpayment) || 0;
            totals.short += parseFloat(r.short_payment) || 0;
            totals.factoring += parseFloat(r.factoring) || 0;
            totals.refund += parseFloat(r.refund) || 0;
            totals.small_bal += parseFloat(r.small_balance) || 0;
            totals.others += parseFloat(r.others) || 0;
            totals.total += parseFloat(r.total_collection) || 0;

            const factoringVal = parseFloat(r.factoring) || 0;
            const factoringClass = factoringVal > 0 ? 'text-indigo-300 font-bold' : 'text-gray-500';

            return `<tr class="border-b border-gray-700 hover:bg-gray-800">
                <td class="px-2 py-1.5 text-blue-400 font-mono">${r.dr_no || ''}</td>
                <td class="px-2 py-1.5 text-gray-200">${r.customer || ''}</td>
                <td class="px-2 py-1.5 text-gray-400">${r.deposited_date || ''}</td>
                <td class="px-2 py-1.5 text-gray-300">${r.cr_or || ''}</td>
                <td class="px-2 py-1.5 text-gray-300">${r.bank || ''}</td>
                <td class="px-2 py-1.5 text-right text-white">${fmtNum(r.check_amount)}</td>
                <td class="px-2 py-1.5 text-right text-orange-400">${fmtNum(r.ewt)}</td>
                <td class="px-2 py-1.5 text-right text-gray-300">${fmtNum(r.rebate)}</td>
                <td class="px-2 py-1.5 text-right text-purple-400">${fmtNum(r.overpayment)}</td>
                <td class="px-2 py-1.5 text-right text-red-400">${fmtNum(r.short_payment)}</td>
                <td class="px-2 py-1.5 text-right ${factoringClass}">${fmtNum(r.factoring)}</td>
                <td class="px-2 py-1.5 text-right text-gray-300">${fmtNum(r.refund)}</td>
                <td class="px-2 py-1.5 text-right text-gray-300">${fmtNum(r.small_balance)}</td>
                <td class="px-2 py-1.5 text-right text-gray-300">${fmtNum(r.others)}</td>
                <td class="px-2 py-1.5 text-right text-green-400">${fmtNum(r.total_collection)}</td>
                <td class="px-2 py-1.5 text-gray-400 text-xs">${r.remarks || ''}</td>
                <td class="px-2 py-1.5 text-gray-400">${r.week || ''}</td>
            </tr>`;
        }).join('');

        // If no matching rows found for selected DR
        if (selectedDrNo && displayRows.length === 0) {
            tbody.innerHTML = `<tr><td colspan="17" class="px-4 py-6 text-center text-yellow-400">
                <i class="fas fa-exclamation-triangle mr-2"></i>No rows found matching DR ${selectedDrNo} in the uploaded file.
            </td></tr>`;
        }

        document.getElementById('factoringTotalCheck').textContent = fmtNum(totals.check);
        document.getElementById('factoringTotalEwt').textContent = fmtNum(totals.ewt);
        document.getElementById('factoringTotalRebate').textContent = fmtNum(totals.rebate);
        document.getElementById('factoringTotalOverpayment').textContent = fmtNum(totals.overpayment);
        document.getElementById('factoringTotalShort').textContent = fmtNum(totals.short);
        document.getElementById('factoringTotalFactoring').textContent = fmtNum(totals.factoring);
        document.getElementById('factoringTotalRefund').textContent = fmtNum(totals.refund);
        document.getElementById('factoringTotalSmallBal').textContent = fmtNum(totals.small_bal);
        document.getElementById('factoringTotalOthers').textContent = fmtNum(totals.others);
        document.getElementById('factoringTotalCollection').textContent = fmtNum(totals.total);

        const label = selectedDrNo
            ? `Showing ${displayRows.length} of ${rows.length} row(s) — filtered by DR ${selectedDrNo}`
            : `${displayRows.length} row(s)`;
        document.getElementById('factoringRowCount').textContent = label;
        document.getElementById('factoringDataPreview').classList.remove('hidden');

        // Auto-fill factoring fields from the displayed rows
        const setField = (name, val) => {
            const el = document.querySelector(`[name="${name}"]`);
            if (el) el.value = val ? parseFloat(val).toFixed(2) : '0.00';
        };

        // If a single DR row is matched, populate from that row directly
        if (selectedDrNo && displayRows.length === 1) {
            const r = displayRows[0];
            setField('factoring_check_amount', r.check_amount);
            setField('factoring_ewt', r.ewt);
            setField('factoring_rebate', r.rebate);
            setField('factoring_overpayment', r.overpayment);
            setField('factoring_short_payment', r.short_payment);
            setField('factoring_amount', r.factoring);
            setField('factoring_refund', r.refund);
            setField('factoring_small_balance', r.small_balance);
            setField('factoring_others', r.others);
            setField('factoring_total_collection', r.total_collection);
            // Set deposited date, CR/OR, bank, week from the row
            if (r.deposited_date) document.querySelector('[name="factoring_deposited_date"]').value = r.deposited_date_raw || '';
            if (r.cr_or) document.querySelector('[name="factoring_cr_or"]').value = r.cr_or;
            if (r.bank) document.querySelector('[name="factoring_bank"]').value = r.bank;
            if (r.week) document.querySelector('[name="factoring_week"]').value = r.week;
            // Set main amount to factoring amount
            document.getElementById('amountInput').value = (parseFloat(r.factoring) || 0).toFixed(2);
        } else {
            // Multiple rows or no DR filter — use totals
            setField('factoring_check_amount', totals.check);
            setField('factoring_ewt', totals.ewt);
            setField('factoring_rebate', totals.rebate);
            setField('factoring_overpayment', totals.overpayment);
            setField('factoring_short_payment', totals.short);
            setField('factoring_amount', totals.factoring);
            setField('factoring_refund', totals.refund);
            setField('factoring_small_balance', totals.small_bal);
            setField('factoring_others', totals.others);
            setField('factoring_total_collection', totals.total);
            if (totals.factoring > 0) {
                document.getElementById('amountInput').value = totals.factoring.toFixed(2);
            }
        }
    }

});
</script>
@endsection
