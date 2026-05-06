@extends('layouts.app')

@section('title', 'Integrated Customer AR Profile')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-gray-800 rounded-lg shadow-lg p-6">
        {{-- Header Section --}}
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <div>
                <h2 class="text-2xl font-bold text-white flex items-center gap-2">
                    <i class="fas fa-user-circle text-blue-400"></i>
                     Customer AR Profile
                </h2>
                <p class="text-gray-400 text-sm mt-1">Complete AR History: Invoices + Collections + Adjustments</p>
            </div>
            <a href="{{ route('aging_reports.view') }}"
               class="bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded transition flex items-center gap-2">
                <i class="fas fa-arrow-left"></i>
                Back to Reports
            </a>
        </div>

        {{-- Customer Information Cards --}}
        @if(isset($customerInfo))
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-blue-900/30 border border-blue-700 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-blue-400 mb-3">Customer Details</h3>
                <div class="space-y-2 text-sm">
                    <div><span class="text-gray-400">Name:</span> <span class="text-white font-medium">{{ $customerInfo['customer_name'] }}</span></div>
                    <div><span class="text-gray-400">Code:</span> <span class="text-gray-200">{{ $customerInfo['customer_code'] }}</span></div>
                    <div><span class="text-gray-400">Branch:</span> <span class="text-gray-200">{{ $customerInfo['branch'] }}</span></div>
                </div>
            </div>

            <div class="bg-purple-900/30 border border-purple-700 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-purple-400 mb-3">Sales Information</h3>
                <div class="space-y-2 text-sm">
                    <div><span class="text-gray-400">SE:</span> <span class="text-white font-medium">{{ $customerInfo['sales_executive'] }}</span></div>
                    <div><span class="text-gray-400">SE2:</span> <span class="text-gray-200">{{ $customerInfo['se2'] }}</span></div>
                    <div><span class="text-gray-400">Terms:</span> <span class="text-gray-200">{{ $customerInfo['terms'] }}</span></div>
                </div>
            </div>

            <div class="bg-green-900/30 border border-green-700 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-green-400 mb-3">Current AR Balance</h3>
                <p class="text-white font-bold text-3xl">₱{{ number_format($totalAR ?? 0, 2) }}</p>
                <p class="text-gray-400 text-xs mt-1">{{ $recordCount ?? 0 }} record(s)</p>
            </div>
        </div>
        @endif

        {{-- AR Calculation Summary --}}
        @if(isset($financialSummary))
        <div class="bg-indigo-900/20 border border-indigo-700 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                <i class="fas fa-calculator text-indigo-400"></i>
                How We Got to Current AR Balance
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                <div class="text-center">
                    <p class="text-gray-400 text-xs mb-2">Starting: Invoices</p>
                    <p class="text-white text-2xl font-bold">₱{{ number_format($financialSummary['original_invoice_amount'], 2) }}</p>
                    <p class="text-gray-400 text-sm mt-1">What they owe</p>
                </div>

                <div class="flex items-center justify-center">
                    <i class="fas fa-minus text-red-400 text-3xl"></i>
                </div>

                <div class="text-center">
                    <p class="text-gray-400 text-xs mb-2">Collections</p>
                    <p class="text-green-400 text-2xl font-bold">₱{{ number_format($financialSummary['total_collections'], 2) }}</p>
                    @if($financialSummary['ewt_total'] > 0)
                    <p class="text-gray-400 text-xs mt-1">Check: ₱{{ number_format($financialSummary['total_collections'] + $financialSummary['ewt_total'], 2) }}</p>
                    <p class="text-orange-400 text-xs">Less EWT: ₱{{ number_format($financialSummary['ewt_total'], 2) }}</p>
                    @else
                    <p class="text-gray-400 text-sm mt-1">Payments received</p>
                    @endif
                </div>

                <div class="flex items-center justify-center">
                    <div class="text-center">
                        @if($financialSummary['net_adjustments'] >= 0)
                            <i class="fas fa-plus text-yellow-400 text-3xl"></i>
                            <p class="text-yellow-400 text-xs mt-1">Add</p>
                        @else
                            <i class="fas fa-minus text-red-400 text-3xl"></i>
                            <p class="text-red-400 text-xs mt-1">Subtract</p>
                        @endif
                    </div>
                </div>

                <div class="text-center">
                    <p class="text-gray-400 text-xs mb-2">Adjustments</p>
                    <p class="text-{{ $financialSummary['net_adjustments'] >= 0 ? 'yellow' : 'red' }}-400 text-2xl font-bold">
                        {{ $financialSummary['net_adjustments'] >= 0 ? '+' : '' }}₱{{ number_format(abs($financialSummary['net_adjustments']), 2) }}
                    </p>
                    <p class="text-gray-400 text-xs mt-1">+ Charges: ₱{{ number_format($financialSummary['total_adjustments_increase'], 2) }}</p>
                    <p class="text-gray-400 text-xs">- Credits: ₱{{ number_format($financialSummary['total_adjustments_decrease'], 2) }}</p>
                </div>
            </div>

            <div class="mt-6 pt-6 border-t border-indigo-700 text-center">
                <p class="text-gray-400 text-sm mb-2">= Current AR Balance</p>
                <p class="text-white text-4xl font-bold">₱{{ number_format($totalAR ?? 0, 2) }}</p>
                <p class="text-gray-400 text-xs mt-2">
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
                <button onclick="showTab('ar_records')" id="tab_ar_records" class="tab-button px-6 py-3 text-gray-400 hover:text-white whitespace-nowrap">
                    <i class="fas fa-file-invoice mr-2"></i>Invoices ({{ $recordCount ?? 0 }})
                </button>
                <button onclick="showTab('collections')" id="tab_collections" class="tab-button px-6 py-3 text-gray-400 hover:text-white whitespace-nowrap">
                    <i class="fas fa-money-bill-wave mr-2"></i>Collections ({{ isset($collections) ? count($collections) : 0 }})
                </button>
                <button onclick="showTab('adjustments')" id="tab_adjustments" class="tab-button px-6 py-3 text-gray-400 hover:text-white whitespace-nowrap">
                    <i class="fas fa-edit mr-2"></i>Adjustments ({{ isset($adjustments) ? count($adjustments) : 0 }})
                </button>
            </div>

            {{-- Transaction History Tab --}}
            <div id="content_transaction_history" class="tab-content">
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-gray-800 rounded-lg text-xs">
                        <thead>
                            <tr class="bg-gray-900 text-gray-300 uppercase text-xs">
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
                        <tbody class="text-gray-300">
                            @forelse($transactionHistory as $trans)
                            <tr class="border-b border-gray-700 hover:bg-gray-900">
                                <td class="px-3 py-3">{{ \Carbon\Carbon::parse($trans['date'])->format('Y-m-d') }}</td>
                                <td class="px-3 py-3">
                                    @if($trans['type'] === 'Invoice')
                                        <span class="bg-blue-600 px-2 py-1 rounded text-xs text-white">{{ $trans['type'] }}</span>
                                    @elseif($trans['type'] === 'Collection')
                                        <span class="bg-green-600 px-2 py-1 rounded text-xs text-white">{{ $trans['type'] }}</span>
                                    @else
                                        <span class="bg-yellow-600 px-2 py-1 rounded text-xs text-white">{{ $trans['type'] }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    <span class="bg-gray-700 px-2 py-1 rounded font-mono text-gray-200">{{ $trans['reference'] }}</span>
                                </td>
                                <td class="px-3 py-3 text-xs">{{ $trans['description'] }}</td>
                                <td class="px-3 py-3 text-right font-semibold {{ $trans['debit'] > 0 ? 'text-red-400' : 'text-gray-500' }}">
                                    {{ $trans['debit'] > 0 ? '+₱' . number_format($trans['debit'], 2) : '-' }}
                                </td>
                                <td class="px-3 py-3 text-right font-semibold {{ $trans['credit'] > 0 ? 'text-green-400' : 'text-gray-500' }}">
                                    {{ $trans['credit'] > 0 ? '-₱' . number_format($trans['credit'], 2) : '-' }}
                                </td>
                                <td class="px-3 py-3 text-right font-bold text-white">
                                    ₱{{ number_format($trans['balance'], 2) }}
                                </td>
                                <td class="px-3 py-3 text-xs text-gray-400">{{ $trans['created_by'] }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No transaction history available</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- AR Records Tab (Invoices) --}}
            <div id="content_ar_records" class="tab-content hidden">
                <div class="overflow-x-auto">
                    <table class="bg-gray-800 rounded-lg text-xs" style="min-width:max-content;width:100%;">
                        <thead>
                            <tr class="bg-gray-900 text-gray-300 text-xs uppercase tracking-wider sticky top-0">
                                <th class="px-3 py-2 text-left whitespace-nowrap">Invoice No</th>
                                <th class="px-3 py-2 text-left whitespace-nowrap">DR No</th>
                                <th class="px-3 py-2 text-left whitespace-nowrap">PO No</th>
                                <th class="px-3 py-2 text-left whitespace-nowrap">Invoice Date</th>
                                <th class="px-3 py-2 text-right whitespace-nowrap">Invoice Amount</th>
                                <th class="px-3 py-2 text-right whitespace-nowrap text-green-400">Settled Amount</th>
                                <th class="px-3 py-2 text-right whitespace-nowrap">CWT</th>
                                <th class="px-3 py-2 text-right whitespace-nowrap">EWT</th>
                                <th class="px-3 py-2 text-right whitespace-nowrap">Annual</th>
                                <th class="px-3 py-2 text-right whitespace-nowrap">Factoring</th>
                                <th class="px-3 py-2 text-right whitespace-nowrap">Factoring Int.</th>
                                <th class="px-3 py-2 text-left whitespace-nowrap" style="min-width:180px;">Others - Particulars</th>
                                <th class="px-3 py-2 text-right whitespace-nowrap">Others Amt</th>
                                <th class="px-3 py-2 text-right whitespace-nowrap">Check Amount</th>
                                <th class="px-3 py-2 text-right whitespace-nowrap">AR Adjustments</th>
                                <th class="px-3 py-2 text-right whitespace-nowrap text-blue-400">Net AR Balance</th>
                                <th class="px-3 py-2 text-center whitespace-nowrap">Age</th>
                                <th class="px-3 py-2 text-center whitespace-nowrap">Status</th>
                                @if(auth()->user()->isAdminUser())
                                <th class="px-3 py-2 text-center whitespace-nowrap text-red-400">IT Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="text-gray-300">
                            @forelse($arRecords as $record)
                            @php
                                $drKey = trim($record->dr_no ?? '');
                                $totalPaid = isset($paymentsPerDr[$drKey]) ? (float)$paymentsPerDr[$drKey]->total_paid : 0;
                                $baseBalance = max((float)($record->net_ar_balance ?? 0), (float)($record->invoice_amount ?? 0) - (float)($record->settled_invoice_amount ?? 0));
                                if ($baseBalance <= 0) $baseBalance = (float)($record->net_of_cwt ?? $record->invoice_amount ?? 0);
                                // Subtract payments dynamically; apply EWT threshold (gap <= 3% = fully paid)
                                if ($totalPaid > 0 && $baseBalance > 0) {
                                    $gap = ($baseBalance - $totalPaid) / $baseBalance;
                                    $displayBalance = ($gap <= 0.03) ? 0 : max(0, $baseBalance - $totalPaid);
                                } else {
                                    $displayBalance = $baseBalance;
                                }
                                $hasDelivery = isset($drNosWithDelivery) && $drNosWithDelivery->contains($drKey);
                                $canEdit = auth()->user()->isAdminUser() && !$hasDelivery && $drKey !== '';
                            @endphp
                            <tr class="border-b border-gray-700 hover:bg-gray-900">
                                <td class="px-3 py-2">
                                    <span class="bg-indigo-900/40 border border-indigo-700 px-2 py-1 rounded font-mono text-indigo-300">
                                        {{ $record->invoice_no ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 font-mono text-gray-300">{{ $record->dr_no ?? 'N/A' }}</td>
                                <td class="px-3 py-2 text-gray-300">{{ $record->po_no ?? 'N/A' }}</td>
                                <td class="px-3 py-2 whitespace-nowrap text-gray-300">{{ $record->invoice_date ?? 'N/A' }}</td>
                                <td class="px-3 py-2 text-right font-semibold text-white whitespace-nowrap">₱{{ number_format($record->invoice_amount ?? 0, 2) }}</td>
                                <td class="px-3 py-2 text-right text-green-400 whitespace-nowrap">₱{{ number_format($record->settled_invoice_amount ?? 0, 2) }}</td>
                                <td class="px-3 py-2 text-right text-gray-300 whitespace-nowrap">₱{{ number_format($record->cwt ?? 0, 2) }}</td>
                                <td class="px-3 py-2 text-right text-gray-300 whitespace-nowrap">₱{{ number_format($record->ewt ?? 0, 2) }}</td>
                                <td class="px-3 py-2 text-right text-gray-300 whitespace-nowrap">₱{{ number_format($record->annual ?? 0, 2) }}</td>
                                <td class="px-3 py-2 text-right text-gray-300 whitespace-nowrap">₱{{ number_format($record->factoring ?? 0, 2) }}</td>
                                <td class="px-3 py-2 text-right text-gray-300 whitespace-nowrap">₱{{ number_format($record->factoring_interest ?? 0, 2) }}</td>
                                <td class="px-3 py-2 text-gray-300 text-xs" title="{{ $record->others_particulars ?? '' }}">{{ $record->others_particulars ?? '' }}</td>
                                <td class="px-3 py-2 text-right text-gray-300 whitespace-nowrap">₱{{ number_format($record->others_amount ?? 0, 2) }}</td>
                                <td class="px-3 py-2 text-right text-gray-300 whitespace-nowrap">₱{{ number_format($record->check_amount ?? 0, 2) }}</td>
                                <td class="px-3 py-2 text-right text-yellow-400 whitespace-nowrap">
                                    @php $adjAmount = $record->ar_adjustments ?? 0; @endphp
                                    {{ $adjAmount != 0 ? ($adjAmount > 0 ? '+' : '') . '₱' . number_format(abs($adjAmount), 2) : '-' }}
                                </td>
                                <td class="px-3 py-2 text-right font-bold whitespace-nowrap {{ $displayBalance > 0 ? 'text-blue-400' : 'text-gray-500' }}">
                                    ₱{{ number_format($displayBalance, 2) }}
                                    @if($totalPaid > 0 && $displayBalance == 0)
                                        <span class="block text-xs text-green-400 font-normal">Paid</span>
                                    @elseif($totalPaid > 0)
                                        <span class="block text-xs text-orange-400 font-normal">-₱{{ number_format($totalPaid, 2) }} paid</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-center whitespace-nowrap">
                                    @php
                                        $age = $record->age ?? 0;
                                        $badgeClass = $age <= 30 ? 'bg-green-600' : ($age <= 60 ? 'bg-yellow-600' : ($age <= 90 ? 'bg-orange-600' : 'bg-red-600'));
                                    @endphp
                                    <span class="{{ $badgeClass }} px-2 py-1 rounded text-xs font-semibold text-white">{{ $age }} days</span>
                                </td>
                                <td class="px-3 py-2 text-center whitespace-nowrap">
                                    @php
                                        $status = $record->status ?? 'Outstanding';
                                        $statusClass = $status === 'Closed' ? 'bg-red-600' : 'bg-blue-600';
                                    @endphp
                                    <span class="{{ $statusClass }} px-2 py-1 rounded text-xs font-semibold text-white">{{ $status }}</span>
                                </td>
                                @if(auth()->user()->isAdminUser())
                                <td class="px-3 py-2 text-center whitespace-nowrap">
                                    @if($canEdit)
                                        <button onclick="openEditModal({{ $record->id }}, {{ json_encode([
                                            'dr_no'            => $record->dr_no,
                                            'invoice_no'       => $record->invoice_no,
                                            'invoice_date'     => $record->invoice_date,
                                            'invoice_amount'   => $record->invoice_amount,
                                            'net_ar_balance'   => $record->net_ar_balance,
                                            'status'           => $record->status,
                                            'po_no'            => $record->po_no,
                                            'client_name'      => $record->client_name,
                                            'branch'           => $record->branch,
                                            'sales_executive'  => $record->sales_executive,
                                            'collection_terms' => $record->terms,
                                            'others_particulars' => $record->others_particulars,
                                        ]) }})"
                                            class="bg-yellow-600 hover:bg-yellow-500 text-white px-2 py-1 rounded text-xs mr-1">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button onclick="openDeleteModal({{ $record->id }}, '{{ addslashes($record->dr_no ?? 'N/A') }}', {{ (int)$totalPaid > 0 ? DB::table('payments')->whereRaw('TRIM(dr_no) = ?', [trim($record->dr_no ?? '')])->count() : 0 }})"
                                            class="bg-red-700 hover:bg-red-600 text-white px-2 py-1 rounded text-xs">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    @else
                                        <span class="text-gray-600 text-xs italic">
                                            {{ $hasDelivery ? 'Has delivery' : 'No DR' }}
                                        </span>
                                    @endif
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr><td colspan="{{ auth()->user()->isAdminUser() ? '19' : '18' }}" class="px-4 py-8 text-center text-gray-400">No invoices found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Collections Tab --}}
            <div id="content_collections" class="tab-content hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-gray-800 rounded-lg text-xs">
                        <thead>
                            <tr class="bg-gray-900 text-gray-300 uppercase text-xs">
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
                                <th class="px-2 py-2 text-right text-red-400">Final AR (Remaining)</th>
                                <th class="px-2 py-2 text-left">Checking SI</th>
                                <th class="px-2 py-2 text-left">Week No</th>
                                <th class="px-2 py-2 text-left">AR Class</th>
                                <th class="px-2 py-2 text-left">Remarks</th>
                                <th class="px-2 py-2 text-left">Data Check</th>
                                <th class="px-2 py-2 text-center">Status</th>
                                <th class="px-2 py-2 text-left">Signed By</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-300">
                            @forelse($collections as $coll)
                            <tr class="border-b border-gray-700 hover:bg-gray-900">
                                <td class="px-2 py-3">{{ \Carbon\Carbon::parse($coll['deposit_date'])->format('Y-m-d') }}</td>
                                <td class="px-2 py-3">
                                    <span class="bg-green-900/40 border border-green-700 px-2 py-1 rounded font-mono text-green-300 text-xs">
                                        {{ $coll['collection_receipt_number'] }}
                                    </span>
                                </td>
                                <td class="px-2 py-3">{{ $coll['dr_no'] ?? '-' }}</td>
                                <td class="px-2 py-3">{{ $coll['invoice_no'] ?? '-' }}</td>
                                <td class="px-2 py-3">{{ $coll['client_name'] }}</td>
                                <td class="px-2 py-3">{{ $coll['branch'] }}</td>
                                <td class="px-2 py-3 text-right font-semibold text-white">₱{{ number_format($coll['gross_amount'], 2) }}</td>
                                <td class="px-2 py-3 text-right text-orange-400">₱{{ number_format($coll['ewt'], 2) }}</td>
                                <td class="px-2 py-3 text-right text-yellow-400">
                                    @if($coll['other_adjustment'] != 0)
                                        {{ $coll['other_adjustment'] > 0 ? '+' : '' }}₱{{ number_format($coll['other_adjustment'], 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-2 py-3 text-right text-purple-400">
                                    @if($coll['factoring'] != 0)
                                        ₱{{ number_format($coll['factoring'], 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-2 py-3 text-right font-bold text-green-400">₱{{ number_format($coll['check_amount'], 2) }}</td>
                                <td class="px-2 py-3 text-right font-bold {{ ($coll['net_ar_balance'] ?? 0) > 0 ? 'text-red-400' : 'text-gray-500' }}">
                                    {{ isset($coll['net_ar_balance']) && $coll['net_ar_balance'] != 0 ? '₱' . number_format($coll['net_ar_balance'], 2) : '-' }}
                                </td>
                                <td class="px-2 py-3">{{ $coll['checking_si'] ?? '-' }}</td>
                                <td class="px-2 py-3">{{ $coll['week_no'] ?? '-' }}</td>
                                <td class="px-2 py-3">
                                    @if($coll['ar_class'])
                                        <span class="bg-indigo-600 px-2 py-1 rounded text-xs text-white">{{ $coll['ar_class'] }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-2 py-3 text-xs">{{ $coll['remarks'] ?? '-' }}</td>
                                <td class="px-2 py-3 text-xs">{{ $coll['data_check'] ?? '-' }}</td>
                                <td class="px-2 py-3 text-center">
                                    <span class="bg-green-600 px-2 py-1 rounded text-xs text-white">{{ $coll['status'] }}</span>
                                </td>
                                <td class="px-2 py-3 text-xs">{{ $coll['signed_by'] }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="19" class="px-4 py-8 text-center text-gray-400">No collections found</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-900">
                            <tr class="font-bold text-white">
                                <td colspan="6" class="px-2 py-3 text-right text-gray-400">TOTALS:</td>
                                <td class="px-2 py-3 text-right">₱{{ number_format($collections->sum('gross_amount'), 2) }}</td>
                                <td class="px-2 py-3 text-right text-orange-400">₱{{ number_format($collections->sum('ewt'), 2) }}</td>
                                <td class="px-2 py-3 text-right text-yellow-400">₱{{ number_format($collections->sum('other_adjustment'), 2) }}</td>
                                <td class="px-2 py-3 text-right text-purple-400">₱{{ number_format($collections->sum('factoring'), 2) }}</td>
                                <td class="px-2 py-3 text-right text-green-400">₱{{ number_format($collections->sum('check_amount'), 2) }}</td>
                                <td class="px-2 py-3 text-right text-red-400">₱{{ number_format($collections->sum('net_ar_balance'), 2) }}</td>
                                <td colspan="7"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Adjustments Tab --}}
            <div id="content_adjustments" class="tab-content hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-gray-800 rounded-lg text-xs">
                        <thead>
                            <tr class="bg-gray-900 text-gray-300 uppercase text-xs">
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
                        <tbody class="text-gray-300">
                            @forelse($adjustments as $adj)
                            <tr class="border-b border-gray-700 hover:bg-gray-900">
                                <td class="px-3 py-3">
                                    <span class="bg-purple-900/40 border border-purple-700 px-2 py-1 rounded font-mono text-purple-300">
                                        {{ $adj['reference_number'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-3">{{ \Carbon\Carbon::parse($adj['transaction_date'])->format('Y-m-d') }}</td>
                                <td class="px-3 py-3">
                                    <span class="bg-indigo-600 px-2 py-1 rounded text-xs text-white">{{ $adj['transaction_type'] }}</span>
                                </td>
                                <td class="px-3 py-3">{{ $adj['invoice_number'] ?? 'All/Oldest' }}</td>
                                <td class="px-3 py-3 text-right font-bold {{ $adj['amount'] >= 0 ? 'text-yellow-400' : 'text-red-400' }}">
                                    {{ $adj['amount'] >= 0 ? '+' : '' }}₱{{ number_format($adj['amount'], 2) }}
                                </td>
                                <td class="px-3 py-3 text-center">
                                    @if($adj['is_decrease'])
                                        <span class="bg-red-600 px-2 py-1 rounded text-xs text-white">↓ Decrease AR</span>
                                    @else
                                        <span class="bg-green-600 px-2 py-1 rounded text-xs text-white">↑ Increase AR</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-gray-300">{{ $adj['gl_account'] }}</td>
                                <td class="px-3 py-3 text-gray-300">{{ $adj['signed_by'] }}</td>
                                <td class="px-3 py-3 text-xs text-gray-400">{{ $adj['remarks'] ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">No adjustments found</td></tr>
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
        btn.classList.add('text-gray-400');
    });

    document.getElementById('content_' + tabName).classList.remove('hidden');
    const activeBtn = document.getElementById('tab_' + tabName);
    activeBtn.classList.add('border-blue-500', 'text-white');
    activeBtn.classList.remove('text-gray-400');
}

function exportProfile() {
    const customerCode = '{{ $customerInfo["customer_code"] ?? "" }}';

    Swal.fire({
        icon: 'success',
        title: 'Exporting...',
        text: 'Your complete AR profile will be downloaded',
        background: '#1f2937',
        color: '#f9fafb',
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

@if(auth()->user()->isAdminUser())
{{-- ── Edit Modal ─────────────────────────────────────────────────── --}}
<div id="editModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70">
    <div class="bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl mx-4 p-6 border border-yellow-600">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-yellow-400 flex items-center gap-2">
                <i class="fas fa-edit"></i> Edit AR Aging Record <span class="text-xs text-gray-400 ml-2">(IT Only)</span>
            </h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-white text-xl">&times;</button>
        </div>
        <form id="editForm" class="grid grid-cols-2 gap-4 text-sm">
            <input type="hidden" id="edit_id">
            <div>
                <label class="text-gray-400 block mb-1">DR Number</label>
                <input id="edit_dr_no" type="text" class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white focus:border-yellow-500 focus:outline-none">
                <p class="text-yellow-400 text-xs mt-1">Changing DR will update linked payments &amp; adjustments.</p>
            </div>
            <div>
                <label class="text-gray-400 block mb-1">Invoice No</label>
                <input id="edit_invoice_no" type="text" class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white focus:border-yellow-500 focus:outline-none">
            </div>
            <div>
                <label class="text-gray-400 block mb-1">Invoice Date</label>
                <input id="edit_invoice_date" type="date" class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white focus:border-yellow-500 focus:outline-none">
            </div>
            <div>
                <label class="text-gray-400 block mb-1">PO Number</label>
                <input id="edit_po_no" type="text" class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white focus:border-yellow-500 focus:outline-none">
            </div>
            <div>
                <label class="text-gray-400 block mb-1">Invoice Amount</label>
                <input id="edit_invoice_amount" type="number" step="0.01" class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white focus:border-yellow-500 focus:outline-none">
            </div>
            <div>
                <label class="text-gray-400 block mb-1">Net AR Balance</label>
                <input id="edit_net_ar_balance" type="number" step="0.01" class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white focus:border-yellow-500 focus:outline-none">
            </div>
            <div>
                <label class="text-gray-400 block mb-1">Client Name</label>
                <input id="edit_client_name" type="text" class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white focus:border-yellow-500 focus:outline-none">
            </div>
            <div>
                <label class="text-gray-400 block mb-1">Branch</label>
                <input id="edit_branch" type="text" class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white focus:border-yellow-500 focus:outline-none">
            </div>
            <div>
                <label class="text-gray-400 block mb-1">Sales Executive</label>
                <input id="edit_sales_executive" type="text" class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white focus:border-yellow-500 focus:outline-none">
            </div>
            <div>
                <label class="text-gray-400 block mb-1">Collection Terms</label>
                <input id="edit_collection_terms" type="text" class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white focus:border-yellow-500 focus:outline-none">
            </div>
            <div>
                <label class="text-gray-400 block mb-1">Status</label>
                <select id="edit_status" class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white focus:border-yellow-500 focus:outline-none">
                    <option value="">Outstanding</option>
                    <option value="Paid">Paid</option>
                    <option value="Partial">Partial</option>
                    <option value="Closed">Closed</option>
                </select>
            </div>
            <div>
                <label class="text-gray-400 block mb-1">Others Particulars</label>
                <input id="edit_others_particulars" type="text" class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white focus:border-yellow-500 focus:outline-none">
            </div>
        </form>
        <div class="flex justify-end gap-3 mt-6">
            <button onclick="closeEditModal()" class="bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded text-sm">Cancel</button>
            <button onclick="submitEdit()" class="bg-yellow-600 hover:bg-yellow-500 text-white px-4 py-2 rounded text-sm font-semibold">
                <i class="fas fa-save mr-1"></i> Save Changes
            </button>
        </div>
    </div>
</div>

{{-- ── Delete Modal ────────────────────────────────────────────────── --}}
<div id="deleteModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70">
    <div class="bg-gray-800 rounded-lg shadow-xl w-full max-w-md mx-4 p-6 border border-red-700">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-red-400 flex items-center gap-2">
                <i class="fas fa-trash"></i> Delete AR Aging Record
            </h3>
            <button onclick="closeDeleteModal()" class="text-gray-400 hover:text-white text-xl">&times;</button>
        </div>
        <p class="text-gray-300 text-sm mb-2">You are about to delete:</p>
        <div class="bg-gray-900 rounded p-3 mb-4 text-sm">
            <p class="text-white">DR Number: <span id="delete_dr_display" class="font-mono text-red-300"></span></p>
            <p class="text-orange-400 mt-1" id="delete_payments_warning"></p>
        </div>
        <p class="text-red-300 text-sm font-semibold mb-4">This will permanently delete the AR aging row AND all linked payments. This cannot be undone.</p>
        <input type="hidden" id="delete_id">
        <div class="flex justify-end gap-3">
            <button onclick="closeDeleteModal()" class="bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded text-sm">Cancel</button>
            <button onclick="submitDelete()" class="bg-red-700 hover:bg-red-600 text-white px-4 py-2 rounded text-sm font-semibold">
                <i class="fas fa-trash mr-1"></i> Confirm Delete
            </button>
        </div>
    </div>
</div>

<script>
function openEditModal(id, data) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_dr_no').value             = data.dr_no ?? '';
    document.getElementById('edit_invoice_no').value        = data.invoice_no ?? '';
    document.getElementById('edit_invoice_date').value      = data.invoice_date ?? '';
    document.getElementById('edit_invoice_amount').value    = data.invoice_amount ?? '';
    document.getElementById('edit_net_ar_balance').value    = data.net_ar_balance ?? '';
    document.getElementById('edit_status').value            = data.status ?? '';
    document.getElementById('edit_po_no').value             = data.po_no ?? '';
    document.getElementById('edit_client_name').value       = data.client_name ?? '';
    document.getElementById('edit_branch').value            = data.branch ?? '';
    document.getElementById('edit_sales_executive').value   = data.sales_executive ?? '';
    document.getElementById('edit_collection_terms').value  = data.collection_terms ?? '';
    document.getElementById('edit_others_particulars').value = data.others_particulars ?? '';
    const modal = document.getElementById('editModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function closeEditModal() {
    const modal = document.getElementById('editModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
function submitEdit() {
    const id = document.getElementById('edit_id').value;
    const data = {
        _method: 'PUT',
        _token: '{{ csrf_token() }}',
        dr_no:             document.getElementById('edit_dr_no').value,
        invoice_no:        document.getElementById('edit_invoice_no').value,
        invoice_date:      document.getElementById('edit_invoice_date').value,
        invoice_amount:    document.getElementById('edit_invoice_amount').value,
        net_ar_balance:    document.getElementById('edit_net_ar_balance').value,
        status:            document.getElementById('edit_status').value,
        po_no:             document.getElementById('edit_po_no').value,
        client_name:       document.getElementById('edit_client_name').value,
        branch:            document.getElementById('edit_branch').value,
        sales_executive:   document.getElementById('edit_sales_executive').value,
        terms:             document.getElementById('edit_collection_terms').value,
        others_particulars: document.getElementById('edit_others_particulars').value,
    };
    fetch(`/aging-reports/ar-aging/${id}/update`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify(data),
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            Swal.fire({ icon: 'success', title: 'Updated', text: res.message, timer: 2000, showConfirmButton: false })
                .then(() => location.reload());
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: res.message });
        }
    })
    .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Request failed.' }));
}

function openDeleteModal(id, drNo, paymentCount) {
    document.getElementById('delete_id').value = id;
    document.getElementById('delete_dr_display').textContent = drNo;
    document.getElementById('delete_payments_warning').textContent =
        paymentCount > 0 ? `⚠ This will also delete ${paymentCount} linked payment record(s).` : 'No linked payments found.';
    const modal = document.getElementById('deleteModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
function submitDelete() {
    const id = document.getElementById('delete_id').value;
    fetch(`/aging-reports/ar-aging/${id}/delete`, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ _token: '{{ csrf_token() }}' }),
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            Swal.fire({ icon: 'success', title: 'Deleted', text: res.message, timer: 2000, showConfirmButton: false })
                .then(() => location.reload());
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: res.message });
        }
    })
    .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Request failed.' }));
}

// Close on backdrop click
['editModal','deleteModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.add('hidden');
            this.classList.remove('flex');
        }
    });
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeEditModal(); closeDeleteModal(); }
});
</script>
@endif
@endsection
