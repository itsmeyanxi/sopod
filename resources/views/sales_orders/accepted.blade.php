@extends('layouts.app')

@section('content')
<div class="p-6 bg-gray-900 min-h-screen text-white">
    <h2 class="text-2xl font-bold mb-6">Accepted Sales Orders</h2>

    <!-- SEARCH BAR -->
    <div class="flex justify-between items-center mb-6">
        <div class="flex gap-2 items-center">
            <input id="soSearchInput" 
                   type="text" 
                   placeholder="Search SO number / customer / approver"
                   class="border border-gray-600 bg-gray-800 text-white rounded px-3 py-2 w-72 focus:outline-none focus:ring focus:ring-blue-500" />
        </div>
    </div>

    <!-- TABLE -->
    <div class="overflow-x-auto bg-gray-800 rounded-lg shadow">
        <table id="salesOrdersTable" class="w-full text-sm">
            <thead class="bg-gray-700 text-gray-300 uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">SO Number</th>
                    <th class="px-4 py-3 text-left">Customer</th>
                    <th class="px-4 py-3 text-left">Date</th>
                    <th class="px-4 py-3 text-left">Total Amount</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Approved By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salesOrders as $order)
                    @php 
                        $status = strtolower(trim($order->status)); 
                    @endphp

                    @if(in_array($status, ['approved', 'declined', 'cancelled']))
                    <tr class="border-b border-gray-700 hover:bg-gray-700 transition">
                        <td class="px-4 py-3">{{ $order->sales_order_number ?? $order->so_number }}</td>
                        <td class="px-4 py-3">{{ $order->customer->customer_name ?? 'N/A' }}</td>
                        <td class="px-4 py-3">{{ $order->created_at->format('Y-m-d') }}</td>
                        <td class="px-4 py-3">₱{{ number_format($order->total_amount, 2) }}</td>
                        <td class="px-4 py-3">
                            @if($status === 'approved')
                                <span class="bg-green-600 text-white px-2 py-1 rounded text-xs font-semibold">Approved</span>
                            @elseif($status === 'declined')
                                <span class="bg-red-600 text-white px-2 py-1 rounded text-xs font-semibold">Declined</span>
                            @elseif($status === 'cancelled')
                                <span class="bg-gray-600 text-white px-2 py-1 rounded text-xs font-semibold">Cancelled</span>
                            @endif
                        </td>
                        <td class="px-4 py- text-gray-300">
                            @if($order->approver)
                                {{ $order->approver->name }}
                            @else
                                <span class="text-gray-500 italic">—</span>
                            @endif
                        </td>
                    </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-gray-400">
                            No accepted sales orders found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div id="soCount" class="text-sm text-gray-400 px-4 py-3">
            Showing {{ $salesOrders->count() }} accepted sales orders
        </div>
    </div>
</div>

<!-- SEARCH  -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('soSearchInput');
    const rows = document.querySelectorAll('#salesOrdersTable tbody tr');
    const countDisplay = document.getElementById('soCount');

    function updateVisibleCount() {
        const visible = Array.from(rows).filter(r => r.style.display !== 'none');
        countDisplay.textContent = `Showing ${visible.length} sales order${visible.length !== 1 ? 's' : ''}`;
    }

    searchInput.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        rows.forEach(row => {
            const txt = row.innerText.toLowerCase();
            row.style.display = txt.includes(q) ? '' : 'none';
        });
        updateVisibleCount();
    });

    updateVisibleCount();
});
</script>
@endsection