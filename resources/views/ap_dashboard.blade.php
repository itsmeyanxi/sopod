@extends('layouts.app')

@section('title', 'AP Dashboard')

@section('content')
<div class="container mx-auto">

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Total AP Balance -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-700 rounded-lg p-5 text-white shadow">
            <div class="text-sm font-medium opacity-90">Total AP Balance</div>
            <div class="text-2xl font-bold mt-1">{{ number_format($totalBalance, 2) }}</div>
            <div class="text-xs opacity-80 mt-1">All outstanding invoices</div>
        </div>

        <!-- Due This Week -->
        <div class="bg-gradient-to-br from-orange-500 to-orange-700 rounded-lg p-5 text-white shadow">
            <div class="text-sm font-medium opacity-90">Due This Week</div>
            <div class="text-2xl font-bold mt-1">{{ number_format($dueThisWeekAmount, 2) }}</div>
            <div class="text-xs opacity-80 mt-1">{{ $dueThisWeekCount }} invoice(s)</div>
        </div>

        <!-- Overdue -->
        <div class="bg-gradient-to-br from-red-500 to-red-700 rounded-lg p-5 text-white shadow">
            <div class="text-sm font-medium opacity-90">Overdue</div>
            <div class="text-2xl font-bold mt-1">{{ number_format($overdueAmount, 2) }}</div>
            <div class="text-xs opacity-80 mt-1">{{ $overdueCount }} invoice(s) past due</div>
        </div>

        <!-- Paid This Month -->
        <div class="bg-gradient-to-br from-green-500 to-green-700 rounded-lg p-5 text-white shadow">
            <div class="text-sm font-medium opacity-90">Paid This Month</div>
            <div class="text-2xl font-bold mt-1">{{ number_format($paidThisMonth, 2) }}</div>
            <div class="text-xs opacity-80 mt-1">{{ $paidCount }} payment(s)</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

        <!-- Pipeline -->
        <div class="bg-gray-800 rounded-lg shadow p-5">
            <h3 class="text-lg font-semibold text-white mb-4 border-b border-gray-700 pb-2">Invoice Pipeline</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-300 text-sm">Pending DH Approval</span>
                    <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded text-sm font-bold">{{ $pipeline['pending_dh'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-300 text-sm">Pending Accounting</span>
                    <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded text-sm font-bold">{{ $pipeline['pending_accounting'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-300 text-sm">Approved</span>
                    <span class="bg-green-100 text-green-700 px-3 py-1 rounded text-sm font-bold">{{ $pipeline['approved'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-300 text-sm">Rejected</span>
                    <span class="bg-red-100 text-red-700 px-3 py-1 rounded text-sm font-bold">{{ $pipeline['rejected'] }}</span>
                </div>
            </div>

            @if($debitMemoCount > 0)
            <div class="mt-4 pt-3 border-t border-gray-700">
                <div class="flex justify-between items-center">
                    <span class="text-gray-300 text-sm">Open Debit Memos</span>
                    <span class="text-orange-600 font-bold text-sm">{{ number_format($openDebitMemos, 2) }} ({{ $debitMemoCount }})</span>
                </div>
            </div>
            @endif
        </div>

        <!-- Top 5 Suppliers -->
        <div class="bg-gray-800 rounded-lg shadow p-5">
            <h3 class="text-lg font-semibold text-white mb-4 border-b border-gray-700 pb-2">Top 5 Suppliers</h3>
            <div class="space-y-3">
                @forelse($topSuppliers as $sup)
                <div class="flex justify-between items-center">
                    <span class="text-gray-200 text-sm truncate mr-2" title="{{ $sup->vendor_name }}">{{ Str::limit($sup->vendor_name, 25) }}</span>
                    <span class="text-white font-semibold text-sm whitespace-nowrap">{{ number_format($sup->total, 2) }}</span>
                </div>
                @empty
                <p class="text-gray-300 text-sm">No data yet.</p>
                @endforelse
            </div>
        </div>

        <!-- Quick Links -->
        <div class="bg-gray-800 rounded-lg shadow p-5">
            <h3 class="text-lg font-semibold text-white mb-4 border-b border-gray-700 pb-2">Quick Links</h3>
            <div class="space-y-2">
                <a href="{{ route('ap_reports.aging') }}" class="block px-4 py-2 bg-gray-900 rounded hover:bg-gray-700 text-gray-200 text-sm transition">
                    <i class="fas fa-clock mr-2 text-blue-500"></i> Aging Report
                </a>
                <a href="{{ route('ap_reports.cash_forecast') }}" class="block px-4 py-2 bg-gray-900 rounded hover:bg-gray-700 text-gray-200 text-sm transition">
                    <i class="fas fa-chart-line mr-2 text-green-500"></i> Cash Forecast
                </a>
                <a href="{{ route('ap_reports.spend_analysis') }}" class="block px-4 py-2 bg-gray-900 rounded hover:bg-gray-700 text-gray-200 text-sm transition">
                    <i class="fas fa-chart-pie mr-2 text-purple-500"></i> Spend Analysis
                </a>
                <a href="{{ route('accounts_payable_invoices.index') }}" class="block px-4 py-2 bg-gray-900 rounded hover:bg-gray-700 text-gray-200 text-sm transition">
                    <i class="fas fa-book mr-2 text-orange-500"></i> AP Ledger
                </a>
                <a href="{{ route('debit_memos.index') }}" class="block px-4 py-2 bg-gray-900 rounded hover:bg-gray-700 text-gray-200 text-sm transition">
                    <i class="fas fa-file-invoice mr-2 text-red-500"></i> Debit Memos
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Invoices -->
    <div class="bg-gray-800 rounded-lg shadow p-5">
        <h3 class="text-lg font-semibold text-white mb-4 border-b border-gray-700 pb-2">Recent Invoices</h3>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-sm">
                <thead class="bg-gray-900 text-gray-300 uppercase text-xs">
                    <tr>
                        <th class="border border-gray-700 px-4 py-2 text-left">APV No.</th>
                        <th class="border border-gray-700 px-4 py-2 text-left">Vendor</th>
                        <th class="border border-gray-700 px-4 py-2 text-center">Due Date</th>
                        <th class="border border-gray-700 px-4 py-2 text-right">Amount</th>
                        <th class="border border-gray-700 px-4 py-2 text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentInvoices as $inv)
                    <tr class="hover:bg-gray-900">
                        <td class="border border-gray-700 px-4 py-2 font-semibold text-blue-600">{{ $inv->apv_no }}</td>
                        <td class="border border-gray-700 px-4 py-2 text-gray-200">{{ $inv->vendor_name }}</td>
                        <td class="border border-gray-700 px-4 py-2 text-center text-gray-200">{{ $inv->due_date ? $inv->due_date->format('M d, Y') : '-' }}</td>
                        <td class="border border-gray-700 px-4 py-2 text-right font-semibold text-white">{{ number_format($inv->grand_total, 2) }}</td>
                        <td class="border border-gray-700 px-4 py-2 text-center">
                            @if($inv->status === 'approved')
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-semibold">Approved</span>
                            @elseif($inv->status === 'rejected')
                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-semibold">Rejected</span>
                            @else
                                <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs font-semibold">Pending</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="border border-gray-700 px-4 py-6 text-center text-gray-400">No invoices yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
