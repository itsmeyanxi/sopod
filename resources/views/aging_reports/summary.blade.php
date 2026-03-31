@extends('layouts.app')

@section('title', 'AR Aging Summary')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-white mb-2">AR Aging Summary Report</h2>
                <p class="text-gray-500 text-sm">
                    Record Date ≤ <strong class="text-white">{{ $filterDate }}</strong> 
                    | Include: <strong class="text-white">{{ ucfirst($include) }}</strong>
                </p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('aging_reports.view') }}" 
                   class="bg-gray-200 hover:bg-gray-300 text-white px-6 py-2 rounded font-medium transition flex items-center space-x-2">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to List</span>
                </a>
                <button type="button" id="export_summary_btn" 
                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2 rounded font-medium transition flex items-center space-x-2">
                    <i class="fas fa-file-excel"></i>
                    <span>Export to Excel</span>
                </button>
            </div>
        </div>

        <!-- Grand Total Card -->
        <div class="bg-gradient-to-r from-purple-600 to-blue-600 rounded-lg p-6 mb-6 shadow-lg">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-white text-sm opacity-90 mb-1">Grand Total AR Balance</p>
                    <p class="text-white text-4xl font-bold">₱{{ number_format($grandTotals['total'], 2) }}</p>
                </div>
                <div class="text-right">
                    <p class="text-white text-sm opacity-90">Total Clients</p>
                    <p class="text-white text-2xl font-bold">{{ count($agingSummary) }}</p>
                </div>
            </div>
        </div>

        <!-- ✅ NEW: AR Notifications Section -->
        @if($notifications && (count($notifications['due_soon']) > 0 || count($notifications['just_overdue']) > 0 || count($notifications['seriously_overdue']) > 0))
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
            <!-- Due Soon Alert -->
            @if(count($notifications['due_soon']) > 0)
            <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-lg p-4 border-l-4 border-blue-400 shadow-lg">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center">
                        <i class="fas fa-clock text-blue-700 text-2xl mr-2"></i>
                        <div>
                            <h4 class="text-white font-bold">Due Soon</h4>
                            <p class="text-blue-100 text-sm">Next 5-7 days</p>
                        </div>
                    </div>
                    <span class="bg-blue-500 text-white px-3 py-1 rounded-full text-sm font-bold">{{ count($notifications['due_soon']) }}</span>
                </div>
                <div class="space-y-2 max-h-32 overflow-y-auto">
                    @foreach($notifications['due_soon'] as $invoice)
                    <div class="bg-blue-100 bg-opacity-40 p-2 rounded text-sm">
                        <p class="text-blue-100 font-semibold">{{ $invoice['invoice_no'] }}</p>
                        <p class="text-blue-700 text-xs">{{ $invoice['customer'] }} • {{ $invoice['days_until_due'] }} days</p>
                        <p class="text-blue-100 font-bold">₱{{ number_format($invoice['amount'], 2) }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Just Overdue Alert -->
            @if(count($notifications['just_overdue']) > 0)
            <div class="bg-gradient-to-br from-yellow-600 to-yellow-700 rounded-lg p-4 border-l-4 border-yellow-400 shadow-lg">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-yellow-200 text-2xl mr-2"></i>
                        <div>
                            <h4 class="text-white font-bold">Just Overdue</h4>
                            <p class="text-yellow-100 text-sm">1-30 days past due</p>
                        </div>
                    </div>
                    <span class="bg-yellow-500 text-white px-3 py-1 rounded-full text-sm font-bold">{{ count($notifications['just_overdue']) }}</span>
                </div>
                <div class="space-y-2 max-h-32 overflow-y-auto">
                    @foreach($notifications['just_overdue'] as $invoice)
                    <div class="bg-yellow-100 bg-opacity-40 p-2 rounded text-sm">
                        <p class="text-yellow-100 font-semibold">{{ $invoice['invoice_no'] }}</p>
                        <p class="text-yellow-200 text-xs">{{ $invoice['customer'] }} • {{ $invoice['days_overdue'] }} days overdue</p>
                        <p class="text-yellow-100 font-bold">₱{{ number_format($invoice['amount'], 2) }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Seriously Overdue Alert -->
            @if(count($notifications['seriously_overdue']) > 0)
            <div class="bg-gradient-to-br from-red-600 to-red-700 rounded-lg p-4 border-l-4 border-red-400 shadow-lg">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-700 text-2xl mr-2"></i>
                        <div>
                            <h4 class="text-white font-bold">⚠️ Seriously Overdue</h4>
                            <p class="text-red-100 text-sm">61+ days past due</p>
                        </div>
                    </div>
                    <span class="bg-red-500 text-white px-3 py-1 rounded-full text-sm font-bold">{{ count($notifications['seriously_overdue']) }}</span>
                </div>
                <div class="space-y-2 max-h-32 overflow-y-auto">
                    @foreach($notifications['seriously_overdue'] as $invoice)
                    <div class="bg-red-100 bg-opacity-40 p-2 rounded text-sm">
                        <p class="text-red-100 font-semibold">{{ $invoice['invoice_no'] }}</p>
                        <p class="text-red-700 text-xs">{{ $invoice['customer'] }} • {{ $invoice['days_overdue'] }} days overdue</p>
                        <p class="text-red-100 font-bold">₱{{ number_format($invoice['amount'], 2) }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif

        <!-- Summary Table -->
        <div class="bg-gray-700 rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-orange-600 text-white text-sm">
                            <th class="px-4 py-3 text-left font-semibold border-r border-orange-200">Client Name</th>
                            <th class="px-4 py-3 text-left font-semibold border-r border-orange-200">SE2</th>
                            <th class="px-4 py-3 text-right font-semibold border-r border-orange-200">Current</th>
                            <th class="px-4 py-3 text-right font-semibold border-r border-orange-200">1-30 Days</th>
                            <th class="px-4 py-3 text-right font-semibold border-r border-orange-200">31-60 Days</th>
                            <th class="px-4 py-3 text-right font-semibold border-r border-orange-200">61-90 Days</th>
                            <th class="px-4 py-3 text-right font-semibold border-r border-orange-200">91-120 Days</th>
                            <th class="px-4 py-3 text-right font-semibold border-r border-orange-200">More than 120 Days</th>
                            <th class="px-4 py-3 text-right font-semibold bg-orange-700">Grand Total</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-200">
                        @forelse($agingSummary as $index => $row)
                        <tr class="border-b border-gray-600 hover:bg-gray-700 transition {{ $index % 2 == 0 ? 'bg-gray-750' : 'bg-gray-700' }}">
                            <td class="px-4 py-3 font-medium">{{ $row['client_name'] }}</td>
                            <td class="px-4 py-3">{{ $row['se2'] }}</td>
                            <td class="px-4 py-3 text-right">
                                @if($row['current'] > 0)
                                    <a href="{{ route('aging_reports.detail', ['customer_code' => $row['customer_code'], 'bucket' => 'current', 'filter_date' => $filterDate, 'include' => $include]) }}" class="text-green-700 font-semibold hover:underline cursor-pointer transition hover:text-green-700">₱{{ number_format($row['current'], 2) }}</a>
                                @else
                                    <span class="text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if($row['1_30'] > 0)
                                    <a href="{{ route('aging_reports.detail', ['customer_code' => $row['customer_code'], 'bucket' => '1_30', 'filter_date' => $filterDate, 'include' => $include]) }}" class="text-yellow-700 font-semibold hover:underline cursor-pointer transition hover:text-yellow-700">₱{{ number_format($row['1_30'], 2) }}</a>
                                @else
                                    <span class="text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if($row['31_60'] > 0)
                                    <a href="{{ route('aging_reports.detail', ['customer_code' => $row['customer_code'], 'bucket' => '31_60', 'filter_date' => $filterDate, 'include' => $include]) }}" class="text-orange-700 font-semibold hover:underline cursor-pointer transition hover:text-orange-700">₱{{ number_format($row['31_60'], 2) }}</a>
                                @else
                                    <span class="text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if($row['61_90'] > 0)
                                    <a href="{{ route('aging_reports.detail', ['customer_code' => $row['customer_code'], 'bucket' => '61_90', 'filter_date' => $filterDate, 'include' => $include]) }}" class="text-red-700 font-semibold hover:underline cursor-pointer transition hover:text-red-700">₱{{ number_format($row['61_90'], 2) }}</a>
                                @else
                                    <span class="text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if($row['91_120'] > 0)
                                    <a href="{{ route('aging_reports.detail', ['customer_code' => $row['customer_code'], 'bucket' => '91_120', 'filter_date' => $filterDate, 'include' => $include]) }}" class="text-red-500 font-semibold hover:underline cursor-pointer transition hover:text-red-700">₱{{ number_format($row['91_120'], 2) }}</a>
                                @else
                                    <span class="text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if($row['over_120'] > 0)
                                    <a href="{{ route('aging_reports.detail', ['customer_code' => $row['customer_code'], 'bucket' => 'over_120', 'filter_date' => $filterDate, 'include' => $include]) }}" class="text-red-600 font-bold hover:underline cursor-pointer transition hover:text-red-500">₱{{ number_format($row['over_120'], 2) }}</a>
                                @else
                                    <span class="text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-white bg-gray-800">
                                ₱{{ number_format($row['total'], 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-2"></i>
                                <p>No aging data found for the selected filters.</p>
                            </td>
                        </tr>
                        @endforelse

                        <!-- Grand Total Row -->
                        @if(count($agingSummary) > 0)
                        <tr class="bg-orange-600 text-white font-bold text-lg">
                            <td class="px-4 py-4" colspan="2">GRAND TOTAL</td>
                            <td class="px-4 py-4 text-right">₱{{ number_format($grandTotals['current'], 2) }}</td>
                            <td class="px-4 py-4 text-right">₱{{ number_format($grandTotals['1_30'], 2) }}</td>
                            <td class="px-4 py-4 text-right">₱{{ number_format($grandTotals['31_60'], 2) }}</td>
                            <td class="px-4 py-4 text-right">₱{{ number_format($grandTotals['61_90'], 2) }}</td>
                            <td class="px-4 py-4 text-right">₱{{ number_format($grandTotals['91_120'], 2) }}</td>
                            <td class="px-4 py-4 text-right">₱{{ number_format($grandTotals['over_120'], 2) }}</td>
                            <td class="px-4 py-4 text-right bg-orange-700 text-xl">₱{{ number_format($grandTotals['total'], 2) }}</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Stats Cards -->
        @if(count($agingSummary) > 0)
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
            <div class="bg-gray-700 rounded-lg p-4">
                <p class="text-gray-500 text-sm mb-1">Current</p>
                <p class="text-green-700 text-xl font-bold">₱{{ number_format($grandTotals['current'], 2) }}</p>
                <p class="text-gray-500 text-xs mt-1">{{ number_format(($grandTotals['current'] / $grandTotals['total']) * 100, 1) }}% of total</p>
            </div>
            <div class="bg-gray-700 rounded-lg p-4">
                <p class="text-gray-500 text-sm mb-1">1-30 Days</p>
                <p class="text-yellow-700 text-xl font-bold">₱{{ number_format($grandTotals['1_30'], 2) }}</p>
                <p class="text-gray-500 text-xs mt-1">{{ number_format(($grandTotals['1_30'] / $grandTotals['total']) * 100, 1) }}% of total</p>
            </div>
            <div class="bg-gray-700 rounded-lg p-4">
                <p class="text-gray-500 text-sm mb-1">31-60 Days</p>
                <p class="text-orange-700 text-xl font-bold">₱{{ number_format($grandTotals['31_60'], 2) }}</p>
                <p class="text-gray-500 text-xs mt-1">{{ number_format(($grandTotals['31_60'] / $grandTotals['total']) * 100, 1) }}% of total</p>
            </div>
            <div class="bg-gray-700 rounded-lg p-4">
                <p class="text-gray-500 text-sm mb-1">Over 120 Days</p>
                <p class="text-red-600 text-xl font-bold">₱{{ number_format($grandTotals['over_120'], 2) }}</p>
                <p class="text-gray-500 text-xs mt-1">{{ number_format(($grandTotals['over_120'] / $grandTotals['total']) * 100, 1) }}% of total</p>
            </div>
        </div>
        @endif
    </div>
</div>

<script>
    document.getElementById('export_summary_btn').addEventListener('click', function() {
        const filterDate = '{{ $filterDate }}';
        const include = '{{ $include }}';
        
        Swal.fire({
            icon: 'success',
            title: 'Exporting...',
            text: 'Your Excel file will be downloaded shortly',
            background: '#ffffff',
            color: '#1f2937',
            timer: 2000,
            showConfirmButton: false
        });

        window.location.href = `/aging-reports/export-ar-aging?filter_date=${filterDate}&include=${include}`;
    });
</script>
@endsection