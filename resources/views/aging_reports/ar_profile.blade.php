@extends('layouts.app')

@section('title', 'Integrated Customer AR Profile')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-gray-800 rounded-lg shadow-lg p-6">
        {{-- Header Section --}}
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <div>
                <h2 class="text-2xl font-bold text-white flex items-center gap-2">
                    <i class="fas fa-user-circle text-blue-700"></i>
                     Customer AR Profile
                </h2>
                <p class="text-gray-500 text-sm mt-1">Complete AR History: Invoices + Collections + Adjustments</p>
            </div>
            <a href="{{ route('aging_reports.view') }}" 
               class="bg-gray-200 hover:bg-gray-300 text-white px-4 py-2 rounded transition flex items-center gap-2">
                <i class="fas fa-arrow-left"></i>
                Back to Reports
            </a>
        </div>

        {{-- Customer Information Cards --}}
        @if(isset($customerInfo))
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-blue-700 mb-3">Customer Details</h3>
                <div class="space-y-2 text-sm">
                    <div><span class="text-gray-500">Name:</span> <span class="text-white font-medium">{{ $customerInfo['customer_name'] }}</span></div>
                    <div><span class="text-gray-500">Code:</span> <span class="text-gray-200">{{ $customerInfo['customer_code'] }}</span></div>
                    <div><span class="text-gray-500">Branch:</span> <span class="text-gray-200">{{ $customerInfo['branch'] }}</span></div>
                </div>
            </div>

            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-purple-700 mb-3">Sales Information</h3>
                <div class="space-y-2 text-sm">
                    <div><span class="text-gray-500">SE:</span> <span class="text-white font-medium">{{ $customerInfo['sales_executive'] }}</span></div>
                    <div><span class="text-gray-500">SE2:</span> <span class="text-gray-200">{{ $customerInfo['se2'] }}</span></div>
                    <div><span class="text-gray-500">Terms:</span> <span class="text-gray-200">{{ $customerInfo['terms'] }}</span></div>
                </div>
            </div>

            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-green-700 mb-3">Current AR Balance</h3>
                <p class="text-white font-bold text-3xl">₱{{ number_format($totalAR ?? 0, 2) }}</p>
                <p class="text-gray-500 text-xs mt-1">{{ $recordCount ?? 0 }} record(s)</p>
            </div>
        </div>
        @endif

        {{-- AR Calculation Summary (How we got to current balance) --}}
        @if(isset($financialSummary))
        <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                <i class="fas fa-calculator text-indigo-700"></i>
                How We Got to Current AR Balance
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                <div class="text-center">
                    <p class="text-gray-500 text-xs mb-2">Starting: Invoices</p>
                    <p class="text-white text-2xl font-bold">₱{{ number_format($financialSummary['original_invoice_amount'], 2) }}</p>
                    <p class="text-gray-500 text-sm mt-1">What they owe</p>
                </div>

                <div class="flex items-center justify-center">
                    <i class="fas fa-minus text-red-700 text-3xl"></i>
                </div>

                <div class="text-center">
                    <p class="text-gray-500 text-xs mb-2">Collections</p>
                    <p class="text-green-700 text-2xl font-bold">₱{{ number_format($financialSummary['total_collections'], 2) }}</p>
                    @if($financialSummary['ewt_total'] > 0)
                    <p class="text-gray-500 text-xs mt-1">Check: ₱{{ number_format($financialSummary['total_collections'] + $financialSummary['ewt_total'], 2) }}</p>
                    <p class="text-orange-700 text-xs">Less EWT: ₱{{ number_format($financialSummary['ewt_total'], 2) }}</p>
                    @else
                    <p class="text-gray-500 text-sm mt-1">Payments received</p>
                    @endif
                </div>

                <div class="flex items-center justify-center">
                    <div class="text-center">
                        @if($financialSummary['net_adjustments'] >= 0)
                            <i class="fas fa-plus text-yellow-700 text-3xl"></i>
                            <p class="text-yellow-700 text-xs mt-1">Add</p>
                        @else
                            <i class="fas fa-minus text-red-700 text-3xl"></i>
                            <p class="text-red-700 text-xs mt-1">Subtract</p>
                        @endif
                    </div>
                </div>

                <div class="text-center">
                    <p class="text-gray-500 text-xs mb-2">Adjustments</p>
                    <p class="text-{{ $financialSummary['net_adjustments'] >= 0 ? 'yellow' : 'red' }}-700 text-2xl font-bold">
                        {{ $financialSummary['net_adjustments'] >= 0 ? '+' : '' }}₱{{ number_format(abs($financialSummary['net_adjustments']), 2) }}
                    </p>
                    <p class="text-gray-500 text-xs mt-1">+ Charges: ₱{{ number_format($financialSummary['total_adjustments_increase'], 2) }}</p>
                    <p class="text-gray-500 text-xs">- Credits: ₱{{ number_format($financialSummary['total_adjustments_decrease'], 2) }}</p>
                </div>
            </div>

            <div class="mt-6 pt-6 border-t border-indigo-200 text-center">
                <p class="text-gray-500 text-sm mb-2">= Current AR Balance</p>
                <p class="text-white text-4xl font-bold">₱{{ number_format($financialSummary['net_ar_balance'], 2) }}</p>
                <p class="text-gray-500 text-xs mt-2">
                    (Invoices - Collections {{ $financialSummary['net_adjustments'] >= 0 ? '+' : '-' }} Adjustments)
                </p>
            </div>
        </div>
        @endif

        {{-- Tabbed Interface --}}
        <div class="bg-gray-700 rounded-lg p-6 mb-6">
            <div class="flex border-b border-gray-600 mb-4 overflow-x-auto">
                <button onclick="showTab('transaction_history')" id="tab_transaction_history" class="tab-button px-6 py-3 text-white font-semibold border-b-2 border-blue-500 whitespace-nowrap">
                    <i class="fas fa-history mr-2"></i>Complete History ({{ isset($transactionHistory) ? count($transactionHistory) : 0 }})
                </button>
                <button onclick="showTab('ar_records')" id="tab_ar_records" class="tab-button px-6 py-3 text-gray-500 hover:text-white whitespace-nowrap">
                    <i class="fas fa-file-invoice mr-2"></i>Invoices ({{ $recordCount ?? 0 }})
                </button>
                <button onclick="showTab('collections')" id="tab_collections" class="tab-button px-6 py-3 text-gray-500 hover:text-white whitespace-nowrap">
                    <i class="fas fa-money-bill-wave mr-2"></i>Collections ({{ isset($collections) ? count($collections) : 0 }})
                </button>
                <button onclick="showTab('adjustments')" id="tab_adjustments" class="tab-button px-6 py-3 text-gray-500 hover:text-white whitespace-nowrap">
                    <i class="fas fa-edit mr-2"></i>Adjustments ({{ isset($adjustments) ? count($adjustments) : 0 }})
                </button>
            </div>

            {{-- Transaction History Tab (DEFAULT - Shows everything in chronological order) --}}
            <div id="content_transaction_history" class="tab-content">
                <!-- <div class="mb-4 bg-blue-100 border border-blue-200 rounded-lg p-4">
                    <p class="text-blue-700 text-sm flex items-center gap-2">
                        <i class="fas fa-info-circle"></i>
                        <strong>Complete Transaction History:</strong> Shows all AR activity (invoices, collections, adjustments) in chronological order with running balance
                    </p>
                </div> -->
                
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-gray-800 rounded-lg text-xs">
                        <thead>
                            <tr class="bg-gray-900 text-gray-200">
                                <th class="px-3 py-2 text-left">Date</th>
                                <th class="px-3 py-2 text-left">Type</th>
                                <th class="px-3 py-2 text-left">Reference</th>
                                <th class="px-3 py-2 text-left">Description</th>
                                <th class="px-3 py-2 text-right">Amount In (+)</th>
                                <th class="px-3 py-2 text-right">Amount Out (-)</th>
                                <th class="px-3 py-2 text-right">Balance</th>
                                <th class="px-3 py-2 text-left">By</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-200">
                            @forelse($transactionHistory as $trans)
                            <tr class="border-b border-gray-700 hover:bg-gray-900">
                                <td class="px-3 py-3">{{ \Carbon\Carbon::parse($trans['date'])->format('Y-m-d') }}</td>
                                <td class="px-3 py-3">
                                    @if($trans['type'] === 'Invoice')
                                        <span class="bg-blue-600 px-2 py-1 rounded text-xs">{{ $trans['type'] }}</span>
                                    @elseif($trans['type'] === 'Collection')
                                        <span class="bg-green-600 px-2 py-1 rounded text-xs">{{ $trans['type'] }}</span>
                                    @else
                                        <span class="bg-yellow-600 px-2 py-1 rounded text-xs">{{ $trans['type'] }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    <span class="bg-gray-700 px-2 py-1 rounded font-mono">{{ $trans['reference'] }}</span>
                                </td>
                                <td class="px-3 py-3 text-xs">{{ $trans['description'] }}</td>
                                <td class="px-3 py-3 text-right font-semibold {{ $trans['debit'] > 0 ? 'text-red-700' : 'text-gray-500' }}">
                                    {{ $trans['debit'] > 0 ? '+₱' . number_format($trans['debit'], 2) : '-' }}
                                </td>
                                <td class="px-3 py-3 text-right font-semibold {{ $trans['credit'] > 0 ? 'text-green-700' : 'text-gray-500' }}">
                                    {{ $trans['credit'] > 0 ? '-₱' . number_format($trans['credit'], 2) : '-' }}
                                </td>
                                <td class="px-3 py-3 text-right font-bold text-white">
                                    ₱{{ number_format($trans['balance'], 2) }}
                                </td>
                                <td class="px-3 py-3 text-xs">{{ $trans['created_by'] }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">No transaction history available</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- AR Records Tab (Invoices) --}}
            <div id="content_ar_records" class="tab-content hidden">
                <!-- <div class="mb-4 bg-blue-100 border border-blue-200 rounded-lg p-4">
                    <p class="text-blue-700 text-sm">
                        <i class="fas fa-info-circle mr-2"></i>
                        These are the original invoices that created the AR balance
                    </p>
                </div> -->
                
                <div class="overflow-x-auto">
                    <table class="bg-gray-800 rounded-lg text-xs" style="min-width:max-content;width:100%;">
                        <thead>
                            <tr class="bg-gray-900 text-gray-500 text-xs uppercase tracking-wider sticky top-0">
                                <th class="px-3 py-2 text-left whitespace-nowrap">Invoice No</th>
                                <th class="px-3 py-2 text-left whitespace-nowrap">DR No</th>
                                <th class="px-3 py-2 text-left whitespace-nowrap">PO No</th>
                                <th class="px-3 py-2 text-left whitespace-nowrap">Invoice Date</th>
                                <th class="px-3 py-2 text-right whitespace-nowrap">Invoice Amount</th>
                                <th class="px-3 py-2 text-right whitespace-nowrap text-green-700">Settled Amount</th>
                                <th class="px-3 py-2 text-right whitespace-nowrap">CWT</th>
                                <th class="px-3 py-2 text-right whitespace-nowrap">EWT</th>
                                <th class="px-3 py-2 text-right whitespace-nowrap">Annual</th>
                                <th class="px-3 py-2 text-right whitespace-nowrap">Factoring</th>
                                <th class="px-3 py-2 text-right whitespace-nowrap">Factoring Int.</th>
                                <th class="px-3 py-2 text-left whitespace-nowrap" style="min-width:180px;">Others - Particulars</th>
                                <th class="px-3 py-2 text-right whitespace-nowrap">Others Amt</th>
                                <th class="px-3 py-2 text-right whitespace-nowrap">Check Amount</th>
                                <th class="px-3 py-2 text-right whitespace-nowrap">AR Adjustments</th>
                                <th class="px-3 py-2 text-right whitespace-nowrap text-blue-700">Net AR Balance</th>
                                <th class="px-3 py-2 text-center whitespace-nowrap">Age</th>
                                <th class="px-3 py-2 text-center whitespace-nowrap">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-200">
                            @forelse($arRecords as $record)
                            <tr class="border-b border-gray-700 hover:bg-gray-900">
                                <td class="px-3 py-2">
                                    <span class="bg-indigo-100 border border-indigo-200 px-2 py-1 rounded font-mono text-indigo-700">
                                        {{ $record->invoice_no ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 font-mono text-gray-500">{{ $record->dr_no ?? 'N/A' }}</td>
                                <td class="px-3 py-2 text-gray-500">{{ $record->po_no ?? 'N/A' }}</td>
                                <td class="px-3 py-2 whitespace-nowrap text-gray-500">{{ $record->invoice_date ?? 'N/A' }}</td>
                                <td class="px-3 py-2 text-right font-semibold text-white whitespace-nowrap">₱{{ number_format($record->invoice_amount ?? 0, 2) }}</td>
                                <td class="px-3 py-2 text-right text-green-700 whitespace-nowrap">₱{{ number_format($record->settled_invoice_amount ?? 0, 2) }}</td>
                                <td class="px-3 py-2 text-right text-gray-500 whitespace-nowrap">₱{{ number_format($record->cwt ?? 0, 2) }}</td>
                                <td class="px-3 py-2 text-right text-gray-500 whitespace-nowrap">₱{{ number_format($record->ewt ?? 0, 2) }}</td>
                                <td class="px-3 py-2 text-right text-gray-500 whitespace-nowrap">₱{{ number_format($record->annual ?? 0, 2) }}</td>
                                <td class="px-3 py-2 text-right text-gray-500 whitespace-nowrap">₱{{ number_format($record->factoring ?? 0, 2) }}</td>
                                <td class="px-3 py-2 text-right text-gray-500 whitespace-nowrap">₱{{ number_format($record->factoring_interest ?? 0, 2) }}</td>
                                <td class="px-3 py-2 text-gray-500 text-xs" title="{{ $record->others_particulars ?? '' }}">{{ $record->others_particulars ?? '' }}</td>
                                <td class="px-3 py-2 text-right text-gray-500 whitespace-nowrap">₱{{ number_format($record->others_amount ?? 0, 2) }}</td>
                                <td class="px-3 py-2 text-right text-gray-500 whitespace-nowrap">₱{{ number_format($record->check_amount ?? 0, 2) }}</td>
                                <td class="px-3 py-2 text-right text-yellow-700 whitespace-nowrap">
                                    @php $adjAmount = $record->ar_adjustments ?? 0; @endphp
                                    {{ $adjAmount != 0 ? ($adjAmount > 0 ? '+' : '') . '₱' . number_format(abs($adjAmount), 2) : '-' }}
                                </td>
                                @php
                                    $displayBalance = (float)($record->net_ar_balance ?? 0);
                                    if ($displayBalance == 0) {
                                        $displayBalance = (float)($record->net_of_cwt ?? $record->invoice_amount ?? 0);
                                    }
                                @endphp
                                <td class="px-3 py-2 text-right font-bold text-blue-700 whitespace-nowrap">₱{{ number_format($displayBalance, 2) }}</td>
                                <td class="px-3 py-2 text-center whitespace-nowrap">
                                    @php
                                        $age = $record->age ?? 0;
                                        $badgeClass = $age <= 30 ? 'bg-green-600' : ($age <= 60 ? 'bg-yellow-600' : ($age <= 90 ? 'bg-orange-600' : 'bg-red-600'));
                                    @endphp
                                    <span class="{{ $badgeClass }} px-2 py-1 rounded text-xs font-semibold">{{ $age }} days</span>
                                </td>
                                <td class="px-3 py-2 text-center whitespace-nowrap">
                                    @php
                                        $status = $record->status ?? 'Outstanding';
                                        $statusClass = $status === 'Closed' ? 'bg-red-600' : 'bg-blue-500';
                                    @endphp
                                    <span class="{{ $statusClass }} px-2 py-1 rounded text-xs font-semibold">{{ $status }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="18" class="px-4 py-8 text-center text-gray-500">No invoices found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

           {{-- Collections Tab (Payments) - ALL Excel Columns --}}
            <div id="content_collections" class="tab-content hidden">
                <!-- <div class="mb-4 bg-green-100 border border-green-200 rounded-lg p-4">
                    <p class="text-green-700 text-sm">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Payment Calculation:</strong> Gross Amount - EWT - Other Adjustments - Factoring = Check Amount
                    </p>
                </div> -->
                
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-gray-800 rounded-lg text-xs">
                        <thead>
                            <tr class="bg-gray-900 text-gray-500">
                                <th class="px-2 py-2 text-left">Deposit Date</th>
                                <th class="px-2 py-2 text-left">CR Number</th>
                                <th class="px-2 py-2 text-left">DR No</th>
                                <th class="px-2 py-2 text-left">Invoice No</th>
                                <th class="px-2 py-2 text-left">Client Name</th>
                                <th class="px-2 py-2 text-left">Branch</th>
                                <th class="px-2 py-2 text-right">Gross Amount</th>
                                <th class="px-2 py-2 text-right">EWT</th>
                                <th class="px-2 py-2 text-right">Other Adj.</th>
                                <th class="px-2 py-2 text-right">Factoring</th>
                                <th class="px-2 py-2 text-right">Check Amount</th>
                                <th class="px-2 py-2 text-right text-red-700">Final AR (Remaining)</th>
                                <th class="px-2 py-2 text-left">Checking SI</th>
                                <th class="px-2 py-2 text-left">Week No</th>
                                <th class="px-2 py-2 text-left">AR Class</th>
                                <th class="px-2 py-2 text-left">Remarks</th>
                                <th class="px-2 py-2 text-left">Data Check</th>
                                <th class="px-2 py-2 text-center">Status</th>
                                <th class="px-2 py-2 text-left">Signed By</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-200">
                            @forelse($collections as $coll)
                            <tr class="border-b border-gray-700 hover:bg-gray-900">
                                <td class="px-2 py-3">{{ \Carbon\Carbon::parse($coll['deposit_date'])->format('Y-m-d') }}</td>
                                <td class="px-2 py-3">
                                    <span class="bg-green-100 border border-green-200 px-2 py-1 rounded font-mono text-xs">
                                        {{ $coll['collection_receipt_number'] }}
                                    </span>
                                </td>
                                <td class="px-2 py-3">{{ $coll['dr_no'] ?? '-' }}</td>
                                <td class="px-2 py-3">{{ $coll['invoice_no'] ?? '-' }}</td>
                                <td class="px-2 py-3">{{ $coll['client_name'] }}</td>
                                <td class="px-2 py-3">{{ $coll['branch'] }}</td>
                                <td class="px-2 py-3 text-right font-semibold text-white">₱{{ number_format($coll['gross_amount'], 2) }}</td>
                                <td class="px-2 py-3 text-right text-orange-700">₱{{ number_format($coll['ewt'], 2) }}</td>
                                <td class="px-2 py-3 text-right text-yellow-700">
                                    @if($coll['other_adjustment'] != 0)
                                        {{ $coll['other_adjustment'] > 0 ? '+' : '' }}₱{{ number_format($coll['other_adjustment'], 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-2 py-3 text-right text-purple-700">
                                    @if($coll['factoring'] != 0)
                                        ₱{{ number_format($coll['factoring'], 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-2 py-3 text-right font-bold text-green-700">₱{{ number_format($coll['check_amount'], 2) }}</td>
                                <td class="px-2 py-3 text-right font-bold {{ ($coll['net_ar_balance'] ?? 0) > 0 ? 'text-red-700' : 'text-gray-500' }}">
                                    {{ isset($coll['net_ar_balance']) && $coll['net_ar_balance'] != 0 ? '₱' . number_format($coll['net_ar_balance'], 2) : '-' }}
                                </td>
                                <td class="px-2 py-3">{{ $coll['checking_si'] ?? '-' }}</td>
                                <td class="px-2 py-3">{{ $coll['week_no'] ?? '-' }}</td>
                                <td class="px-2 py-3">
                                    @if($coll['ar_class'])
                                        <span class="bg-indigo-600 px-2 py-1 rounded text-xs">{{ $coll['ar_class'] }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-2 py-3 text-xs">{{ $coll['remarks'] ?? '-' }}</td>
                                <td class="px-2 py-3 text-xs">{{ $coll['data_check'] ?? '-' }}</td>
                                <td class="px-2 py-3 text-center">
                                    <span class="bg-green-600 px-2 py-1 rounded text-xs">{{ $coll['status'] }}</span>
                                </td>
                                <td class="px-2 py-3 text-xs">{{ $coll['signed_by'] }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="19" class="px-4 py-8 text-center text-gray-500">No collections found</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-900">
                            <tr class="font-bold text-white">
                                <td colspan="6" class="px-2 py-3 text-right">TOTALS:</td>
                                <td class="px-2 py-3 text-right">₱{{ number_format($collections->sum('gross_amount'), 2) }}</td>
                                <td class="px-2 py-3 text-right text-orange-700">₱{{ number_format($collections->sum('ewt'), 2) }}</td>
                                <td class="px-2 py-3 text-right text-yellow-700">₱{{ number_format($collections->sum('other_adjustment'), 2) }}</td>
                                <td class="px-2 py-3 text-right text-purple-700">₱{{ number_format($collections->sum('factoring'), 2) }}</td>
                                <td class="px-2 py-3 text-right text-green-700">₱{{ number_format($collections->sum('check_amount'), 2) }}</td>
                                <td class="px-2 py-3 text-right text-red-700">₱{{ number_format($collections->sum('net_ar_balance'), 2) }}</td>
                                <td colspan="7"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Adjustments Tab --}}
            <div id="content_adjustments" class="tab-content hidden">
                <!-- <div class="mb-4 bg-yellow-100 border border-yellow-200 rounded-lg p-4">
                    <p class="text-yellow-700 text-sm">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Adjustment Types:</strong> Negative (with parentheses) = Reduces AR (credit memo) | Positive = Increases AR (debit memo, extra charges)
                    </p>
                </div> -->
                
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-gray-800 rounded-lg text-xs">
                        <thead>
                            <tr class="bg-gray-900 text-gray-200">
                                <th class="px-3 py-2 text-left">Reference No</th>
                                <th class="px-3 py-2 text-left">Date</th>
                                <th class="px-3 py-2 text-left">Type</th>
                                <th class="px-3 py-2 text-left">Invoice No</th>
                                <th class="px-3 py-2 text-right">Amount</th>
                                <th class="px-3 py-2 text-center">Effect on AR</th>
                                <th class="px-3 py-2 text-left">GL Account</th>
                                <th class="px-3 py-2 text-left">Signed By</th>
                                <th class="px-3 py-2 text-left">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-200">
                            @forelse($adjustments as $adj)
                            <tr class="border-b border-gray-700 hover:bg-gray-900">
                                <td class="px-3 py-3">
                                    <span class="bg-purple-100 border border-purple-200 px-2 py-1 rounded font-mono">
                                        {{ $adj['reference_number'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-3">{{ \Carbon\Carbon::parse($adj['transaction_date'])->format('Y-m-d') }}</td>
                                <td class="px-3 py-3">
                                    <span class="bg-indigo-600 px-2 py-1 rounded text-xs">{{ $adj['transaction_type'] }}</span>
                                </td>
                                <td class="px-3 py-3">{{ $adj['invoice_number'] ?? 'All/Oldest' }}</td>
                                <td class="px-3 py-3 text-right font-bold {{ $adj['amount'] >= 0 ? 'text-yellow-700' : 'text-red-700' }}">
                                    {{ $adj['amount'] >= 0 ? '+' : '' }}₱{{ number_format($adj['amount'], 2) }}
                                </td>
                                <td class="px-3 py-3 text-center">
                                    @if($adj['is_decrease'])
                                        <span class="bg-red-600 px-2 py-1 rounded text-xs">↓ Decrease AR</span>
                                    @else
                                        <span class="bg-green-600 px-2 py-1 rounded text-xs">↑ Increase AR</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">{{ $adj['gl_account'] }}</td>
                                <td class="px-3 py-3">{{ $adj['signed_by'] }}</td>
                                <td class="px-3 py-3 text-xs">{{ $adj['remarks'] ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="9" class="px-4 py-8 text-center text-gray-500">No adjustments found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Export Button --}}
        <div class="flex justify-end">
            <button onclick="exportProfile()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2 rounded font-medium transition">
                <i class="fas fa-file-excel mr-2"></i>Export Complete Profile
            </button>
        </div>
    </div>
</div>

<script>
function showTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('border-blue-500', 'text-white');
        btn.classList.add('text-gray-500');
    });
    
    document.getElementById('content_' + tabName).classList.remove('hidden');
    const activeBtn = document.getElementById('tab_' + tabName);
    activeBtn.classList.add('border-blue-500', 'text-white');
    activeBtn.classList.remove('text-gray-500');
}

function exportProfile() {
    const customerCode = '{{ $customerInfo["customer_code"] ?? "" }}';
    
    Swal.fire({
        icon: 'success',
        title: 'Exporting...',
        text: 'Your complete AR profile will be downloaded',
        background: '#ffffff',
        color: '#1f2937',
        timer: 2000,
        showConfirmButton: false
    });

    window.location.href = "{{ route('aging_reports.ar_profile.export') }}?customer_code=" + encodeURIComponent(customerCode);
}
</script>

<style>
.tab-button {
    transition: all 0.3s ease;
}
.tab-button:hover {
    background-color: rgba(59, 130, 246, 0.1);
}
</style>
@endsection