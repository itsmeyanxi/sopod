@extends('layouts.app')

@section('title', 'Records')

@section('content')
<div class="bg-gray-900 text-gray-100 min-h-screen p-8">

    <!-- HEADER -->
    <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-3">
        <h1 class="text-2xl font-bold">Records — {{ ucfirst(str_replace('_', ' ', $type)) }}</h1>

        <div class="inline-flex gap-2 p-1.5 bg-gray-800 rounded-lg border border-gray-700 shadow-lg">

            <!-- Export Excel -->
            <a href="{{ route('records.export.excel', ['type' => $type, 'report' => $report ?? null]) }}"
                class="bg-yellow-600 hover:bg-yellow-700 px-4 py-2 rounded-md font-medium shadow">
                Export Excel
            </a>

            <!-- SALES ORDERS TAB -->
            <a href="{{ route('records.index', ['type' => 'sales_orders']) }}" 
                class="relative px-6 py-2.5 rounded-md font-medium transition-all duration-200 ease-in-out
                {{ $type === 'sales_orders'
                    ? 'bg-blue-600 text-white shadow-md shadow-blue-600/50' 
                    : 'text-gray-300 hover:text-white hover:bg-gray-700/50' }}">
                <span class="relative z-10 flex items-center gap-2">
                    <i class="fa-solid fa-file-invoice"></i> Sales Orders
                </span>
            </a>

            <!-- DELIVERIES TAB -->
            <a href="{{ route('records.index', ['type' => 'deliveries']) }}" 
                class="relative px-6 py-2.5 rounded-md font-medium transition-all duration-200 ease-in-out
                {{ $type === 'deliveries'
                    ? 'bg-green-600 text-white shadow-md shadow-green-600/50' 
                    : 'text-gray-300 hover:text-white hover:bg-gray-700/50' }}">
                <span class="relative z-10 flex items-center gap-2">
                    <i class="fa-solid fa-truck"></i> Deliveries
                </span>
            </a>
        </div>
    </div>

@if($type === 'deliveries')
<div class="bg-gray-800 p-4 rounded-md mb-4">
    <h2 class="text-xl font-bold mb-2">Monthly Summary</h2>

    <div class="flex gap-3">

        <form method="GET" class="flex items-center gap-3">
            <input type="hidden" name="type" value="deliveries">

            <!-- Sort By -->
            <select name="sort" class="bg-gray-700 text-white px-3 py-2 rounded">
                <option value="">Sort By</option>
                <option value="amount" {{ $sort=='amount' ? 'selected' : '' }}>Sales Amount</option>
                <option value="customer" {{ $sort=='customer' ? 'selected' : '' }}>Customer</option>
                <option value="item" {{ $sort=='item' ? 'selected' : '' }}>Item</option>
            </select>

            <select name="dir" class="bg-gray-700 text-white px-3 py-2 rounded">
                <option value="asc" {{ $direction=='asc' ? 'selected' : '' }}>Ascending</option>
                <option value="desc" {{ $direction=='desc' ? 'selected' : '' }}>Descending</option>
            </select>

            <!-- Cancelled Filter -->
            <label class="flex items-center gap-2 text-white ml-4">
                <input 
                    type="checkbox" 
                    name="cancelled" 
                    value="1"
                    {{ $showCancelled==1 ? 'checked' : '' }}
                    class="h-4 w-4"
                >
                Cancelled Only
            </label>

            <!-- Delivered Only Filter -->
            <label class="flex items-center gap-2 text-white">
                <input 
                    type="checkbox" 
                    name="delivered_only" 
                    value="1"
                    {{ request('delivered_only') ? 'checked' : '' }}
                    class="h-4 w-4"
                >
                Delivered Only
            </label>

            <button class="bg-blue-600 px-4 py-2 rounded ml-2">Apply</button>

        </form>
    </div>
</div>
@endif



    <style>
        .report-card {
            display: block;
            background: #1f2937;
            padding: 16px;
            border-radius: 10px;
            border: 1px solid #374151;
            color: #e5e7eb;
            font-weight: 500;
            transition: 0.2s;
            text-align: center;
        }
        .report-card:hover {
            background: #374151;
            transform: translateY(-2px);
        }
    </style>

    <!-- ====================== -->
    <!-- 📄 TABLE SECTION -->
    <!-- ====================== -->
    @if($records->count() > 0)
        <div class="overflow-x-auto">
        <table class="min-w-full bg-gray-800 rounded-lg overflow-hidden text-left">
            <thead class="bg-gray-700 text-gray-200 ">
                <tr>
                    @if($report === 'monthly_sales')
                        <th class="px-4 py-2">Month</th>
                        <th class="px-4 py-2">Total Amount</th>

                    @elseif($report === 'sales_by_customer')
                        <th class="px-4 py-2">Customer</th>
                        <th class="px-4 py-2">Total Sales</th>

                    @elseif($report === 'sales_by_item')
                        <th class="px-4 py-2">Item</th>
                        <th class="px-4 py-2">Total Sales</th>

                    @elseif($report === 'cancelled_so')
                        <th class="px-4 py-2">SO Number</th>
                        <th class="px-4 py-2">Customer</th>
                        <th class="px-4 py-2">Branch</th>
                        <th class="px-4 py-2">Items</th>
                        <th class="px-4 py-2">Total Amount</th>

                    @else
                        <th class="px-4 py-2">#</th>
                        <th class="px-4 py-2">Customer</th>
                        <th class="px-4 py-2">Branch</th>
                        <th class="px-4 py-2">Item Description</th>
                        <th class="px-4 py-2">Total Amount</th>
                        <th class="px-4 py-2">Status</th>
                        <th class="px-4 py-2 text-center">Action</th>
                    @endif
                </tr>
            </thead>

           <tbody>
                @foreach($records as $record)

                    @php
                        // unify data source → if delivery, use related sales order
                        $so = $type === 'deliveries' ? $record->salesOrder : $record;
                    @endphp

                    <tr class="border-b border-gray-700 hover:bg-gray-700">

                        {{-- ===================== REPORT: Monthly Sales ===================== --}}
                        @if($report === 'monthly_sales')
                            <td class="px-4 py-2">{{ $record->month }}</td>
                            <td class="px-4 py-2">{{ $record->total_amount }}</td>

                        {{-- ===================== REPORT: Sales by Customer ===================== --}}
                        @elseif($report === 'sales_by_customer')
                            <td class="px-4 py-2">{{ $record->customer_name }}</td>
                            <td class="px-4 py-2">{{ $record->total_amount }}</td>

                        {{-- ===================== REPORT: Sales by Item ===================== --}}
                        @elseif($report === 'sales_by_item')
                            <td class="px-4 py-2">{{ $record->item_description }}</td>
                            <td class="px-4 py-2">{{ $record->total_amount }}</td>

                        {{-- ===================== REPORT: Cancelled SO ===================== --}}
                        @elseif($report === 'cancelled_so')
                            <td class="px-4 py-2">{{ $record->sales_order_number }}</td>
                            <td class="px-4 py-2">{{ $so->customer_name }}</td>
                            <td class="px-4 py-2">{{ $so->branch }}</td>
                            <td class="px-4 py-2">{{ $so->item_description }}</td>
                            <td class="px-4 py-2">{{ $so->total_amount }}</td>

                        {{-- ===================== DEFAULT TABLE ===================== --}}
                        @else
                            <td class="px-4 py-2">
                                {{ $type === 'deliveries' ? $record->dr_no : $record->sales_order_number }}
                            </td>

                            <td class="px-4 py-2">{{ $so->customer_name }}</td>
                            <td class="px-4 py-2">{{ $so->branch }}</td>
                            <td class="px-4 py-2">{{ $so->item_description }}</td>
                            <td class="px-4 py-2">{{ $so->total_amount }}</td>

                            {{-- STATUS --}}
                            <td class="px-4 py-2">
                                <span class="px-2 py-1 rounded text-sm 
                                    @if($record->status === 'Delivered' || $record->status === 'Approved') bg-green-600
                                    @elseif($record->status === 'Declined' || $record->status === 'Cancelled') bg-red-600
                                    @elseif($record->status === 'Pending') bg-yellow-600
                                    @else bg-gray-600 @endif">
                                    {{ $record->status ?? '—' }}
                                </span>
                            </td>

                            {{-- ACTION --}}
                            <td class="px-4 py-2 text-center">
                                @if($type === 'deliveries')
                                    <a href="{{ route('records.dshow', $record->id) }}"
                                    class="bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded text-white text-sm">
                                        <i class="fa-solid fa-eye"></i> View
                                    </a>
                                @else
                                    <a href="{{ route('records.so_show', $record->id) }}"
                                    class="bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded text-white text-sm">
                                        <i class="fa-solid fa-eye"></i> View
                                    </a>
                                @endif
                            </td>
                        @endif

                    </tr>
                @endforeach
                </tbody>

        </table>
        </div>

        @if(!$report)
        <div class="mt-4">
            {{ $records->links() }}
        </div>
        @endif

    @else
        <p class="text-gray-400">
            No {{ $report ? 'data found for this report' : ($type === 'deliveries' ? 'deliveries' : 'sales orders') }}.
        </p>
    @endif

</div>
@endsection
