@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 text-gray-800 p-8">
    <div class="flex justify-between items-center mb-6 border-b border-gray-200 pb-2">
        <h1 class="text-2xl font-bold">Sales Order List</h1>
    </div>

    @if(session('success'))
        <div class="bg-green-600 text-white p-3 rounded mb-4">{{ session('success') }}</div>
    @elseif(session('error'))
        <div class="bg-red-600 text-white p-3 rounded mb-4">{{ session('error') }}</div>
    @endif

    {{-- ⚠️ DELIVERY ALERTS --}}
    @php
        $overdueOrders = [];
        $cancelledOrders = [];
        $today = now();

        // Check ALL sales orders, not just filtered ones
        $allOrders = \App\Models\SalesOrder::with(['customer', 'deliveries'])->get();

        foreach($allOrders as $order) {
            if($order->deliveries && $order->deliveries->status === 'Cancelled') {
                $cancelledOrders[] = $order;
            }
            
            if($order->deliveries && $order->deliveries->request_delivery_date) {
                $requestedDate = \Carbon\Carbon::parse($order->deliveries->request_delivery_date);
                $isOverdue = $today->gt($requestedDate) && 
                            $order->deliveries->status !== 'Delivered' && 
                            $order->deliveries->status !== 'Cancelled';
                
                if($isOverdue) {
                    $overdueOrders[] = $order;
                }
            }
        }
    @endphp

    @if(count($cancelledOrders) > 0)
        <div id="cancelledAlert" class="bg-red-600 text-white p-4 rounded-lg mb-4 shadow-lg" style="display: none;">
            <div class="flex items-start justify-between">
                <div class="flex items-start flex-1">
                    <span class="text-2xl mr-3">🚫</span>
                    <div class="flex-1">
                        <h3 class="font-bold text-lg mb-2">Cancelled Deliveries</h3>
                        <p class="mb-2">The following sales orders have cancelled deliveries:</p>
                        <ul class="list-disc list-inside space-y-1" id="cancelledList">
                            @foreach($cancelledOrders as $order)
                                <li data-order-id="{{ $order->id }}">
                                    <a href="{{ route('sales_orders.show', $order->id) }}" class="font-bold underline hover:text-gray-700">
                                        {{ $order->sales_order_number }}
                                    </a> - {{ $order->customer->customer_name ?? 'N/A' }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <button onclick="closeAlert('cancelledAlert')" class="text-gray-800 hover:text-gray-700 text-2xl font-bold ml-4 leading-none">
                    ×
                </button>
            </div>
        </div>
    @endif

    @if(count($overdueOrders) > 0)
        <div id="overdueAlert" class="bg-orange-600 text-white p-4 rounded-lg mb-4 shadow-lg" style="display: none;">
            <div class="flex items-start justify-between">
                <div class="flex items-start flex-1">
                    <span class="text-2xl mr-3">⏰</span>
                    <div class="flex-1">
                        <h3 class="font-bold text-lg mb-2">Overdue Deliveries</h3>
                        <p class="mb-2">The following sales orders have not been delivered by the requested delivery date:</p>
                        <ul class="list-disc list-inside space-y-1" id="overdueList">
                            @foreach($overdueOrders as $order)
                                <li data-order-id="{{ $order->id }}">
                                    <a href="{{ route('sales_orders.show', $order->id) }}" class="font-bold underline hover:text-gray-700">
                                        {{ $order->sales_order_number }}
                                    </a> - {{ $order->customer->customer_name ?? 'N/A' }}
                                    <span class="text-sm opacity-90">
                                        (Expected: {{ \Carbon\Carbon::parse($order->deliveries->request_delivery_date)->format('M d, Y') }})
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <button onclick="closeAlert('overdueAlert')" class="text-gray-800 hover:text-gray-700 text-2xl font-bold ml-4 leading-none">
                    ×
                </button>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-lg p-4 mb-4 flex items-end justify-between">
        <!-- FILTER FORM (LEFT) -->
        <form action="{{ route('sales_orders.index') }}" method="GET" class="flex items-end gap-4">
            <div>
                <label class="block text-sm text-gray-500 mb-1">Delivery Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                    class="bg-gray-100 text-gray-800 px-3 py-2 rounded border border-gray-300 focus:outline-none focus:border-blue-500">
            </div>

            <div>
                <label class="block text-sm text-gray-500 mb-1">Delivery Date To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                    class="bg-gray-100 text-gray-800 px-3 py-2 rounded border border-gray-300 focus:outline-none focus:border-blue-500">
            </div>

            <div>
                <label class="block text-sm text-gray-500 mb-1">Search</label>
                <input type="text" name="search" placeholder="Search..."
                    value="{{ request('search') }}"
                    class="bg-gray-100 text-gray-800 px-3 py-2 rounded border border-gray-300 w-full">
            </div>

            <div>
                <label class="block text-sm text-gray-500 mb-1">SO Status</label>
                <select name="so_status" class="bg-gray-100 text-gray-800 px-3 py-2 rounded border border-gray-300 focus:outline-none focus:border-blue-500">
                    <option value="">All</option>
                    <option value="Pending" {{ request('so_status') === 'Pending' ? 'selected' : '' }}>Pending</option>
                    <option value="Approved" {{ request('so_status') === 'Approved' ? 'selected' : '' }}>Approved</option>
                    <option value="Declined" {{ request('so_status') === 'Declined' ? 'selected' : '' }}>Declined</option>
                    <option value="Cancelled" {{ request('so_status') === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <div>
                <label class="block text-sm text-gray-500 mb-1">DR Status</label>
                <select name="dr_status" class="bg-gray-100 text-gray-800 px-3 py-2 rounded border border-gray-300 focus:outline-none focus:border-blue-500">
                    <option value="">All</option>
                    <option value="Awaiting Delivery" {{ request('dr_status') === 'Awaiting Delivery' ? 'selected' : '' }}>Awaiting Delivery</option>
                    <option value="Partial" {{ request('dr_status') === 'Partial' ? 'selected' : '' }}>Partial</option>
                    <option value="Delivered (Full)" {{ request('dr_status') === 'Delivered (Full)' ? 'selected' : '' }}>Full Delivery</option>
                    <option value="Fully Delivered" {{ request('dr_status') === 'Fully Delivered' ? 'selected' : '' }}>Fully Delivered</option>
                    <option value="Pulled Out" {{ request('dr_status') === 'Pulled Out' ? 'selected' : '' }}>Pullout</option>
                </select>
            </div>

            <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition">
                Filter
            </button>

            <a href="{{ route('sales_orders.index') }}" 
            class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded transition">
                Clear
            </a>

            @if(request('date_from') || request('date_to') || request('search') || request('so_status') || request('dr_status'))
                @php
                    $hasNonPendingSO = $salesOrders->contains(function($order) {
                        return $order->status !== 'Pending';
                    });
                @endphp

                @if($hasNonPendingSO)
                    <button type="button" onclick="printList()"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded transition">
                        🖨️ Print List
                    </button>
                    
                    <button type="button" onclick="exportExcel()"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded transition">
                        📥 Export Excel
                    </button>
                @else
                    <div class="bg-yellow-600/20 border border-yellow-600 text-yellow-700 px-4 py-2 rounded text-sm">
                        ⚠️ Cannot print/export: All filtered sales orders are pending approval
                    </div>
                @endif
            @endif
        </form>

        <!-- CREATE BUTTON (RIGHT) -->
        @if(auth()->user()->canCreateSalesOrders())
            <a href="{{ route('sales_orders.create') }}" 
            class="bg-gradient-to-r from-purple-500 to-indigo-500 hover:from-purple-600 hover:to-indigo-600 text-white px-4 py-2 rounded transition whitespace-nowrap">
                Create Sales Order
            </a>
        @endif
    </div>

    {{-- ✅ BULK ACTION BAR --}}
    @php
        $hasPendingOrders = $salesOrders->contains(function($order) {
            return $order->status === 'Pending';
        });
    @endphp

    @if($hasPendingOrders)
    <div id="bulkActionBar" class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-lg p-4 mb-4 shadow-lg" style="display: none;">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <span class="text-lg font-bold">
                    <span id="selectedCount">0</span> order(s) selected
                </span>
                <button type="button" onclick="deselectAll()" 
                    class="text-sm underline hover:text-gray-700">
                    Deselect All
                </button>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" onclick="showApproveConfirm()"
                    class="bg-green-600 hover:bg-green-700 text-white font-bold px-6 py-2 rounded transition">
                    ✓ Approve Selected
                </button>
                <button type="button" onclick="showDeclineModal()"
                    class="bg-red-600 hover:bg-red-700 text-white font-bold px-6 py-2 rounded transition">
                    ✗ Decline Selected
                </button>
                <button type="button" onclick="hideBulkBar()" 
                    class="text-gray-800 hover:text-gray-700 text-2xl font-bold">
                    ×
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Sales Order Table -->
    <div class="flex items-center justify-between mb-2">
        <div class="flex items-center gap-2 text-sm text-gray-500">
            <span>Number of rows:</span>
            <select onchange="changePerPage(this.value)"
                class="bg-gray-100 border border-gray-200 text-gray-800 text-sm rounded px-2 py-1 focus:outline-none focus:ring-1 focus:ring-purple-500">
                @foreach([25,50,100,250,500] as $n)
                    <option value="{{ $n }}" {{ request('per_page', 25) == $n ? 'selected' : '' }}>{{ $n }}</option>
                @endforeach
            </select>
        </div>
        <div>{{ $salesOrders->onEachSide(1)->links('vendor.pagination.elegant') }}</div>
    </div>
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <table id="soTable" class="w-full text-sm">
            <thead class="bg-gray-100 text-gray-500 uppercase text-xs">
                <tr>
                    @if($hasPendingOrders)
                    <th class="px-4 py-3 text-center w-12">
                        <input type="checkbox" id="selectAll" 
                            class="w-4 h-4 cursor-pointer"
                            onchange="toggleSelectAll(this)">
                    </th>
                    @endif
                    <th class="px-4 py-3 text-left">SO Number</th>
                    <th class="px-4 py-3 text-left">Customer</th>
                    <th class="px-4 py-3 text-left">Date Created</th>
                    <th class="px-4 py-3 text-left">Total Amount</th>
                    <th class="px-4 py-3 text-left">SO Status</th>
                    <th class="px-4 py-3 text-left">Requested Date</th>
                    <th class="px-4 py-3 text-left">DR Status</th>
                    <th class="px-4 py-3 text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($salesOrders as $order)
                <tr class="border-b border-gray-200 hover:bg-gray-100">
                    @if($hasPendingOrders)
                    <td class="px-4 py-3 text-center">
                        @if($order->status === 'Pending')
                            <input type="checkbox" name="order_ids[]" 
                                value="{{ $order->id }}" 
                                class="order-checkbox w-4 h-4 cursor-pointer"
                                onchange="updateSelectedCount()">
                        @endif
                    </td>
                    @endif
                    <td class="px-4 py-3">{{ $order->sales_order_number }}</td>
                    <td class="px-4 py-3">{{ $order->customer->customer_name ?? 'N/A' }}</td>
                    <td class="px-4 py-3">{{ $order->created_at->format('Y-m-d') }}</td>
                    <td class="px-4 py-3">₱{{ number_format($order->total_amount, 2) }}</td>
                    <td class="px-4 py-3">
                        @if($order->status === 'Pending')
                            <span class="bg-yellow-500 text-black px-2 py-1 rounded text-xs">Pending</span>
                        @elseif($order->status === 'Approved')
                            <span class="bg-green-600 text-white px-2 py-1 rounded text-xs">Approved</span>
                        @elseif($order->status === 'Declined')
                            <span class="bg-red-600 text-white px-2 py-1 rounded text-xs">Declined</span>
                        @elseif($order->status === 'Cancelled')
                            <span class="bg-gray-200 text-gray-800 px-2 py-1 rounded text-xs">Cancelled</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($order->deliveries && $order->deliveries->request_delivery_date)
                            {{ \Carbon\Carbon::parse($order->deliveries->request_delivery_date)->format('Y-m-d') }}
                        @else
                            <span class="text-gray-500">N/A</span>
                        @endif
                    </td>                   
                    <td class="px-4 py-3">
                        @php
                            $delivery = $order->deliveries;
                            
                            if (!$delivery) {
                                $drStatus = ($order->status === 'Approved') ? 'Awaiting Delivery' : 'Not Delivered';
                                $statusClass = 'bg-gray-200';
                            } else {
                                if ($delivery->is_pulled_out) {
                                    $drStatus = 'Pulled Out';
                                    $statusClass = 'bg-orange-600';
                                } elseif ($delivery->approval_status === 'Rejected') {
                                    $drStatus = 'Rejected';
                                    $statusClass = 'bg-red-700';
                                } elseif ($delivery->status === 'Cancelled') {
                                    $drStatus = 'Cancelled';
                                    $statusClass = 'bg-red-600';
                                } elseif ($order->is_closed) {
                                    $drStatus = 'Fully Delivered';
                                    $statusClass = 'bg-green-600';
                                } elseif ($delivery->approval_status === 'Pending') {
                                    $drStatus = 'Pending Approval';
                                    $statusClass = 'bg-yellow-500 text-black';
                                } elseif ($delivery->status === 'Delivered' && $delivery->delivery_type === 'Full') {
                                    $drStatus = 'Delivered (Full)';
                                    $statusClass = 'bg-green-600';
                                } elseif ($delivery->status === 'Delivered' && $delivery->delivery_type === 'Partial') {
                                    $drStatus = 'Partial';
                                    $statusClass = 'bg-orange-500';
                                } elseif ($delivery->status === 'Delivered') {
                                    $drStatus = 'Delivered';
                                    $statusClass = 'bg-green-600';
                                } elseif ($delivery->status === 'In Transit') {
                                    $drStatus = 'In Transit';
                                    $statusClass = 'bg-blue-600';
                                } elseif ($delivery->status === 'Preparing') {
                                    $drStatus = 'Preparing';
                                    $statusClass = 'bg-indigo-600';
                                } else {
                                    $drStatus = $delivery->status ?? 'Pending';
                                    $statusClass = 'bg-gray-200';
                                }
                            }
                        @endphp
                        
                        <span class="{{ $statusClass }} text-gray-800 px-2 py-1 rounded text-xs font-medium">
                            {{ $drStatus }}
                        </span>
                    </td>

                    <td class="px-4 py-3 text-center">
                        <a href="{{ route('sales_orders.show', $order->id) }}" 
                            class="bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded text-xs inline-block">
                            View
                        </a>
                        
                    @php
                        $user = auth()->user();
                        $canShowEdit = false;
                        $canShowApproveEdit = false;
                        
                        $hasDelivery = $order->deliveries !== null;
                        $isDelivered = false;
                        
                        if ($hasDelivery) {
                            $delivery = $order->deliveries;
                            if ($delivery->status === 'Delivered') {
                                $isDelivered = true;
                            }
                        }
                        
                        if (!$isDelivered) {
                            if ($user->canInitiateEdit() || $user->canEditAfterCCApproval()) {
                                $canShowEdit = true;
                            }
                        } else {
                            if ($user->canInitiateEdit()) {
                                $canShowEdit = true;
                                if (!$order->isEditApprovedByCC()) {
                                    $canShowApproveEdit = true;
                                }
                            } elseif ($user->canEditAfterCCApproval() && $order->isEditApprovedByCC()) {
                                $canShowEdit = true;
                            }
                        }
                    @endphp

                    @if($canShowEdit)
                        <a href="{{ route('sales_orders.edit', $order->id) }}" 
                            class="bg-yellow-600 hover:bg-yellow-700 px-3 py-1 rounded text-xs inline-block ml-2">
                            Edit
                        </a>
                    @endif

                    @if($canShowApproveEdit)
                        <form action="{{ route('sales_orders.approveForEdit', $order->id) }}" method="POST" class="inline ml-2">
                            @csrf
                            <button type="submit" 
                                class="bg-green-600 hover:bg-green-700 px-3 py-1 rounded text-xs"
                                onclick="return confirm('Approve this SO for editing by CSR team?')">
                                ✓ Approve Edit
                            </button>
                        </form>
                    @endif

                    @if($isDelivered)
                        <span class="bg-gray-200 text-gray-800 px-3 py-1 rounded text-xs ml-2" title="Order has been delivered">
                            🔒 Locked
                        </span>
                    @endif
                        
                        <form action="{{ route('sales_orders.destroy', $order->id) }}" method="POST" class="inline ml-2">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="bg-red-600 hover:bg-red-700 px-3 py-1 rounded text-xs"
                                    onclick="return confirm('Are you sure you want to delete this sales order?')">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-3 flex items-center justify-between">
        <div class="text-sm text-gray-500">
            Showing {{ $salesOrders->firstItem() }}–{{ $salesOrders->lastItem() }} of {{ $salesOrders->total() }} record(s)
        </div>
        <div>{{ $salesOrders->onEachSide(1)->links('vendor.pagination.elegant') }}</div>
    </div>
</div>

{{-- ✅ DECLINE REASON MODAL --}}
<div id="declineModal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">Decline Selected Orders</h3>
        <p class="text-gray-500 mb-4">Please provide a reason for declining these sales orders:</p>
        
        <form id="bulkDeclineForm" action="{{ route('sales_orders.bulkDecline') }}" method="POST">
            @csrf
            <div id="declineOrderIdsContainer"></div>
            
            <textarea name="decline_reason" id="declineReason" rows="4" required
                class="w-full bg-gray-100 text-gray-800 border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-red-500 mb-4"
                placeholder="Enter reason for declining..."></textarea>
            
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeDeclineModal()"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded">
                    Cancel
                </button>
                <button type="submit"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
                    Confirm Decline
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// ✅ PERSISTENT ALERT SYSTEM
function closeAlert(alertId) {
    const alert = document.getElementById(alertId);
    if (alert) {
        alert.style.display = 'none';
        localStorage.setItem(alertId + '_closed', 'true');
    }
}

window.addEventListener('DOMContentLoaded', function() {
    const cancelledAlert = document.getElementById('cancelledAlert');
    const overdueAlert = document.getElementById('overdueAlert');
    
    if (cancelledAlert && localStorage.getItem('cancelledAlert_closed') !== 'true') {
        cancelledAlert.style.display = 'block';
    }
    
    if (overdueAlert && localStorage.getItem('overdueAlert_closed') !== 'true') {
        overdueAlert.style.display = 'block';
    }
});

function resetAlerts() {
    localStorage.removeItem('cancelledAlert_closed');
    localStorage.removeItem('overdueAlert_closed');
    location.reload();
}

// ✅ BULK ACTION FUNCTIONS
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.order-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateSelectedCount();
}

function updateSelectedCount() {
    const checked = document.querySelectorAll('.order-checkbox:checked');
    const count = checked.length;
    document.getElementById('selectedCount').textContent = count;
    
    const bulkBar = document.getElementById('bulkActionBar');
    if (count > 0) {
        bulkBar.style.display = 'block';
    } else {
        bulkBar.style.display = 'none';
        document.getElementById('selectAll').checked = false;
    }
}

function deselectAll() {
    document.querySelectorAll('.order-checkbox').forEach(cb => {
        cb.checked = false;
    });
    document.getElementById('selectAll').checked = false;
    updateSelectedCount();
}

function hideBulkBar() {
    deselectAll();
}

function showApproveConfirm() {
    const checked = document.querySelectorAll('.order-checkbox:checked');
    if (checked.length === 0) {
        alert('No orders selected');
        return;
    }
    
    if (confirm(`Are you sure you want to approve ${checked.length} selected sales order(s)?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("sales_orders.bulkApprove") }}';
        
        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = '{{ csrf_token() }}';
        form.appendChild(csrf);
        
        checked.forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'order_ids[]';
            input.value = cb.value;
            form.appendChild(input);
        });
        
        document.body.appendChild(form);
        form.submit();
    }
}

function showDeclineModal() {
    const checked = document.querySelectorAll('.order-checkbox:checked');
    if (checked.length === 0) {
        alert('No orders selected');
        return;
    }
    
    // Clear previous inputs
    const container = document.getElementById('declineOrderIdsContainer');
    container.innerHTML = '';
    
    // Add hidden inputs for selected order IDs
    checked.forEach(cb => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'order_ids[]';
        input.value = cb.value;
        container.appendChild(input);
    });
    
    // Show modal
    document.getElementById('declineModal').classList.remove('hidden');
    document.getElementById('declineReason').focus();
}

function closeDeclineModal() {
    document.getElementById('declineModal').classList.add('hidden');
    document.getElementById('declineReason').value = '';
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDeclineModal();
    }
});

function printList() {
    const dateFrom = document.querySelector('input[name="date_from"]').value;
    const dateTo = document.querySelector('input[name="date_to"]').value;
    const search = document.querySelector('input[name="search"]').value;
    const soStatus = document.querySelector('select[name="so_status"]').value;
    const drStatus = document.querySelector('select[name="dr_status"]').value;

    let url = '{{ route("sales_orders.printList") }}?';

    if (dateFrom) url += 'date_from=' + dateFrom + '&';
    if (dateTo) url += 'date_to=' + dateTo + '&';
    if (search) url += 'search=' + encodeURIComponent(search) + '&';
    if (soStatus) url += 'so_status=' + encodeURIComponent(soStatus) + '&';
    if (drStatus) url += 'dr_status=' + encodeURIComponent(drStatus);

    window.open(url, '_blank');
}

function exportExcel() {
    const dateFrom = document.querySelector('input[name="date_from"]').value;
    const dateTo = document.querySelector('input[name="date_to"]').value;
    const search = document.querySelector('input[name="search"]').value;
    const soStatus = document.querySelector('select[name="so_status"]').value;
    const drStatus = document.querySelector('select[name="dr_status"]').value;

    let url = '{{ route("sales_orders.exportExcel") }}?';

    if (dateFrom) url += 'date_from=' + dateFrom + '&';
    if (dateTo) url += 'date_to=' + dateTo + '&';
    if (search) url += 'search=' + encodeURIComponent(search) + '&';
    if (soStatus) url += 'so_status=' + encodeURIComponent(soStatus) + '&';
    if (drStatus) url += 'dr_status=' + encodeURIComponent(drStatus);

    window.location.href = url;
}

function changePerPage(val) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', val);
    url.searchParams.set('page', 1);
    window.location.href = url.toString();
}
</script>

@endsection