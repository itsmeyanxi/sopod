@extends('layouts.app')

@section('title', 'Sales Report')

@section('content')
<div class="bg-gray-800 p-6 min-h-screen">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-white text-2xl font-bold">Annual Sales Report</h1>
        <a href="{{ route('dashboard') }}" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded">
            ← Back to Dashboard
        </a>
    </div>

    {{-- Year Selector --}}
    <div class="bg-gray-700 p-6 rounded shadow mb-6">
        <form method="GET" action="{{ route('sales.report') }}" class="flex items-center gap-4">
            <label for="year" class="text-white font-semibold">Select Year:</label>
            <select name="year" id="year" 
                    class="bg-gray-600 text-white border border-gray-500 rounded px-4 py-2"
                    onchange="this.form.submit()">
                @foreach($availableYears as $year)
                    <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>
                        {{ $year }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- Year Total Summary --}}
    <div class="bg-gradient-to-br from-blue-600 to-blue-700 p-8 rounded shadow mb-6 text-center">
        <h2 class="text-white text-xl font-semibold mb-2">Total Sales for {{ $selectedYear }}</h2>
        <p class="text-5xl text-white font-bold">₱{{ number_format($yearTotal, 2) }}</p>
    </div>

    {{-- Monthly Breakdown --}}
    <div class="bg-gray-700 p-6 rounded shadow">
        <h2 class="text-white text-xl font-semibold mb-4">Monthly Breakdown</h2>
        
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-white">
                <thead class="text-xs uppercase bg-gray-800">
                    <tr>
                        <th class="px-6 py-3">Month</th>
                        <th class="px-6 py-3 text-right">Orders</th>
                        <th class="px-6 py-3 text-right">Total Sales</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($salesData as $data)
                        <tr class="border-b border-gray-600 hover:bg-gray-600">
                            <td class="px-6 py-4 font-medium">
                                {{ $data['month_name'] }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                {{ $data['order_count'] }}
                            </td>
                            <td class="px-6 py-4 text-right font-semibold">
                                ₱{{ number_format($data['total'], 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-800">
                    <tr class="font-bold text-lg">
                        <td class="px-6 py-4">TOTAL</td>
                        <td class="px-6 py-4 text-right">
                            {{ array_sum(array_column($salesData, 'order_count')) }}
                        </td>
                        <td class="px-6 py-4 text-right text-green-400">
                            ₱{{ number_format($yearTotal, 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection