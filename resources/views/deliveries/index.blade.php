@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-900 text-white p-8">
    <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-2">
        <h1 class="text-2xl font-bold">Deliveries List</h1>
        
        {{-- 🆕 Create Delivery --}}
        @if(auth()->user()->canManageDeliveries())
            <a href="{{ route('deliveries.deliveries') }}" 
               class="bg-gradient-to-r from-purple-500 to-indigo-500 hover:from-purple-600 hover:to-indigo-600 text-white px-4 py-2 rounded transition"
               title="Create Delivery"> Create delivery
                <i class="fas fa-plus text-lg"></i>
            </a>
        @endif
    </div>

    {{-- ✅ Alerts --}}
    @if(session('success'))
        <div class="bg-green-600 text-white p-3 rounded mb-4">{{ session('success') }}</div>
    @elseif(session('error'))
        <div class="bg-red-600 text-white p-3 rounded mb-4">{{ session('error') }}</div>
    @endif

    {{-- 📅 Date Filter Form --}}
    <div class="bg-gray-800 rounded-lg p-4 mb-4">
        <form method="GET" action="{{ route('deliveries.index') }}" class="flex gap-3 items-end">
            <div>
                <label class="block text-sm text-gray-300 mb-1">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" 
                       class="bg-gray-700 text-white px-3 py-2 rounded border border-gray-600">
            </div>
            <div>
                <label class="block text-sm text-gray-300 mb-1">Date To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" 
                       class="bg-gray-700 text-white px-3 py-2 rounded border border-gray-600">
            </div>
            <div>
                <label class="block text-sm text-gray-300 mb-1">Search</label>
                <input id="deliverySearchInput"
                       type="text" 
                       name="search" 
                       placeholder="Search..." 
                       value="{{ request('search') }}" 
                       class="border border-gray-700 bg-gray-800 text-gray-200 rounded px-3 py-2 w-full sm:w-64 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded text-white">
                Filter
            </button>
            <a href="{{ route('deliveries.index') }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded transition">
                Clear
            </a>
            @if(request('date_from') || request('date_to') || request('search'))
                <button type="button"
                        onclick="printList()"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded transition">
                   🖨️ Print List
                </button>
                <button type="button"
                        onclick="exportExcel()"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded transition">
                   📥 Export Excel
                </button>
            @endif
        </form>
    </div>

    {{-- 📋 Deliveries Table --}}
    <div class="bg-gray-800 rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-700 scrollbar-track-gray-800">
            <table id="deliveriesTable" class="min-w-full text-sm border-collapse">
                <thead class="bg-gray-700 text-gray-300 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left">DR No</th>
                        <th class="px-4 py-3 text-left">Sales Order</th>
                        <th class="px-4 py-3 text-left">Customer</th>
                        <th class="px-4 py-3 text-left">Quantity</th>
                        <th class="px-4 py-3 text-left">Amount</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deliveries as $delivery)
                    <tr class="border-b border-gray-700 hover:bg-gray-700 transition-colors">
                        <td class="px-4 py-3">{{ $delivery->dr_no }}</td>
                        <td class="px-4 py-3">{{ $delivery->sales_order_number }}</td>
                        <td class="px-4 py-3">
                            {{-- ✅ Fixed: Use customer_name from deliveries table first --}}
                            {{ $delivery->customer_name 
                               ?? $delivery->salesOrder?->customer?->customer_name 
                               ?? $delivery->salesOrder?->client_name 
                               ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3">{{ number_format($delivery->quantity, 2) }}</td>
                        <td class="px-4 py-3">₱{{ number_format($delivery->total_amount, 2) }}</td>  
                        <td class="px-4 py-3">
                            @if($delivery->status === 'Cancelled')
                                <span class="bg-red-600 text-white px-2 py-1 rounded text-xs">Cancelled</span>
                            @elseif($delivery->status === 'Completed')
                                <span class="bg-green-600 text-white px-2 py-1 rounded text-xs">Completed</span>
                            @elseif($delivery->status === 'Delivered')
                                <span class="bg-blue-500 text-white px-2 py-1 rounded text-xs">Delivered</span>
                            @else
                                <span class="bg-gray-600 text-white px-2 py-1 rounded text-xs">{{ $delivery->status }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('deliveries.show', $delivery->id) }}" 
                               class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-md text-xs">
                                View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-gray-400 py-4">No deliveries found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3 text-sm text-gray-300">
        Showing {{ $deliveries->count() }} delivery record(s)
    </div>
</div>

<script>
function printList() {
    const dateFrom = document.querySelector('[name="date_from"]').value;
    const dateTo = document.querySelector('[name="date_to"]').value;
    const search = document.querySelector('[name="search"]').value;
    let url = '{{ route("deliveries.printList") }}?';

    if (dateFrom) url += 'date_from=' + encodeURIComponent(dateFrom) + '&';
    if (dateTo) url += 'date_to=' + encodeURIComponent(dateTo) + '&';
    if (search) url += 'search=' + encodeURIComponent(search);

    window.open(url, '_blank');
}

function exportExcel() {
    const dateFrom = document.querySelector('[name="date_from"]').value;
    const dateTo = document.querySelector('[name="date_to"]').value;
    const search = document.querySelector('[name="search"]').value;

    let url = '{{ route("deliveries.exportExcel") }}?';
    if (dateFrom) url += 'date_from=' + encodeURIComponent(dateFrom) + '&';
    if (dateTo) url += 'date_to=' + encodeURIComponent(dateTo) + '&';
    if (search) url += 'search=' + encodeURIComponent(search);

    window.location.href = url;
}

const searchInput = document.getElementById('deliverySearchInput');
const table = document.getElementById('deliveriesTable');
const rows = table.querySelectorAll('tbody tr');

searchInput.addEventListener('input', function () {
    const q = this.value.toLowerCase().trim();

    rows.forEach(row => {
        const drNo = row.cells[0]?.textContent.toLowerCase().trim() || '';
        const soNo = row.cells[1]?.textContent.toLowerCase().trim() || '';
        const customer = row.cells[2]?.textContent.toLowerCase().trim() || '';
        const status = row.cells[5]?.textContent.toLowerCase().trim() || '';

        const match =
            drNo.includes(q) ||
            soNo.includes(q) ||
            customer.includes(q) ||
            status.includes(q);

        row.style.display = match ? '' : 'none';
    });
});
</script>

{{-- ✅ FontAwesome --}}
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
@endsection