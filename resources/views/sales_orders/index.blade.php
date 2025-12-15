@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-900 text-white p-8">
    <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-2">
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
            // ✅ FIXED: Check if delivery exists first
            if($order->deliveries && $order->deliveries->status === 'Cancelled') {
                $cancelledOrders[] = $order;
            }
            
            // Check for overdue deliveries
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
                                    <a href="{{ route('sales_orders.show', $order->id) }}" class="font-bold underline hover:text-gray-200">
                                        {{ $order->sales_order_number }}
                                    </a> - {{ $order->customer->customer_name ?? 'N/A' }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <button onclick="closeAlert('cancelledAlert')" class="text-white hover:text-gray-200 text-2xl font-bold ml-4 leading-none">
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
                                    <a href="{{ route('sales_orders.show', $order->id) }}" class="font-bold underline hover:text-gray-200">
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
                <button onclick="closeAlert('overdueAlert')" class="text-white hover:text-gray-200 text-2xl font-bold ml-4 leading-none">
                    ×
                </button>
            </div>
        </div>
    @endif

    <div class="bg-gray-800 rounded-lg p-4 mb-4 flex items-end justify-between">
        <!-- FILTER FORM (LEFT) -->
        <form action="{{ route('sales_orders.index') }}" method="GET" class="flex items-end gap-4">
            <div>
                <label class="block text-sm text-gray-300 mb-1">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                    class="bg-gray-700 text-white px-3 py-2 rounded border border-gray-600 focus:outline-none focus:border-blue-500">
            </div>

            <div>
                <label class="block text-sm text-gray-300 mb-1">Date To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                    class="bg-gray-700 text-white px-3 py-2 rounded border border-gray-600 focus:outline-none focus:border-blue-500">
            </div>

            <div>
                <label class="block text-sm text-gray-300 mb-1">Search</label>
                <input type="text" name="search" placeholder="Search..."
                    value="{{ request('search') }}"
                    class="bg-gray-700 text-white px-3 py-2 rounded border border-gray-600 w-full">
            </div>

            <button type="submit" 
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition">
                Filter
            </button>

            <a href="{{ route('sales_orders.index') }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded transition">
                Clear
            </a>

           @if(request('date_from') || request('date_to') || request('search'))
                @php
                    // Check if any filtered SO is NOT pending
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
                    <div class="bg-yellow-600/20 border border-yellow-600 text-yellow-300 px-4 py-2 rounded text-sm">
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

    <!-- Sales Order Table -->
    <div class="bg-gray-800 rounded-xl shadow-md overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-700 text-gray-300 uppercase text-xs">
                <tr>
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
                <tr class="border-b border-gray-700 hover:bg-gray-700">
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
                            <span class="bg-gray-600 text-white px-2 py-1 rounded text-xs">Cancelled</span>
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
                            $delivery = $order->deliveries; // This might be null
                            
                            // ✅ Priority 1: Check if delivery exists first
                            if (!$delivery) {
                                // No delivery created yet
                                $drStatus = ($order->status === 'Approved') ? 'Awaiting Delivery' : 'Not Delivered';
                                $statusClass = 'bg-gray-600';
                            } 
                            // ✅ Priority 2: Check CRITICAL statuses BEFORE checking is_closed
                            else {
                                // Check if pulled out FIRST (highest priority)
                                if ($delivery->is_pulled_out) {
                                    $drStatus = 'Pulled Out';
                                    $statusClass = 'bg-orange-600';
                                }
                                // Check if rejected by approver
                                elseif ($delivery->approval_status === 'Rejected') {
                                    $drStatus = 'Rejected';
                                    $statusClass = 'bg-red-700';
                                }
                                // Check if cancelled
                                elseif ($delivery->status === 'Cancelled') {
                                    $drStatus = 'Cancelled';
                                    $statusClass = 'bg-red-600';
                                }
                                // ✅ NOW check if SO is closed (only if not pulled out/rejected/cancelled)
                                elseif ($order->is_closed) {
                                    $drStatus = 'Fully Delivered';
                                    $statusClass = 'bg-green-600';
                                }
                                // Check if pending approval
                                elseif ($delivery->approval_status === 'Pending') {
                                    $drStatus = 'Pending Approval';
                                    $statusClass = 'bg-yellow-500 text-black';
                                }
                                // Check if delivered (Full)
                                elseif ($delivery->status === 'Delivered' && $delivery->delivery_type === 'Full') {
                                    $drStatus = 'Delivered (Full)';
                                    $statusClass = 'bg-green-600';
                                }
                                // Check if delivered (Partial)
                                elseif ($delivery->status === 'Delivered' && $delivery->delivery_type === 'Partial') {
                                    $drStatus = 'Partial';
                                    $statusClass = 'bg-orange-500';
                                }
                                // Check if just delivered (no type specified)
                                elseif ($delivery->status === 'Delivered') {
                                    $drStatus = 'Delivered';
                                    $statusClass = 'bg-green-600';
                                }
                                // Check if in transit
                                elseif ($delivery->status === 'In Transit') {
                                    $drStatus = 'In Transit';
                                    $statusClass = 'bg-blue-600';
                                }
                                // Check if preparing
                                elseif ($delivery->status === 'Preparing') {
                                    $drStatus = 'Preparing';
                                    $statusClass = 'bg-indigo-600';
                                }
                                // Default fallback
                                else {
                                    $drStatus = $delivery->status ?? 'Pending';
                                    $statusClass = 'bg-gray-600';
                                }
                            }
                        @endphp
                        
                        <span class="{{ $statusClass }} text-white px-2 py-1 rounded text-xs font-medium">
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
                        
                        // ✅ Check if delivery exists and is delivered
                        $hasDelivery = $order->deliveries !== null;
                        $isDelivered = false;
                        
                        if ($hasDelivery) {
                            $delivery = $order->deliveries;
                            // Check if delivery is marked as Delivered (any type)
                            if ($delivery->status === 'Delivered') {
                                $isDelivered = true;
                            }
                        }
                        
                        // ✅ NEW LOGIC: Request edit permission only AFTER delivery
                        if (!$isDelivered) {
                            // NOT delivered yet - CSR and CC_Approver can edit freely
                            if ($user->canInitiateEdit() || $user->canEditAfterCCApproval()) {
                                $canShowEdit = true;
                            }
                        } else {
                            // DELIVERED - Need permission system
                            // CC_Approver can always edit
                            if ($user->canInitiateEdit()) {
                                $canShowEdit = true;
                                // CC_Approver can approve for CSR editing
                                if (!$order->isEditApprovedByCC()) {
                                    $canShowApproveEdit = true;
                                }
                            }
                            // CSR roles need CC approval to edit delivered orders
                            elseif ($user->canEditAfterCCApproval() && $order->isEditApprovedByCC()) {
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
                        <span class="bg-gray-600 text-white px-3 py-1 rounded text-xs ml-2" title="Order has been delivered">
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

    <div class="mt-3 text-sm text-gray-300">
        Showing {{ $salesOrders->count() }} sales order(s)
    </div>
</div>

<script>
// ✅ PERSISTENT ALERT SYSTEM - Stays hidden after closing
function closeAlert(alertId) {
    const alert = document.getElementById(alertId);
    if (alert) {
        alert.style.display = 'none';
        // Store closed state in localStorage
        localStorage.setItem(alertId + '_closed', 'true');
    }
}

// ✅ CHECK AND SHOW ALERTS ON PAGE LOAD
window.addEventListener('DOMContentLoaded', function() {
    const cancelledAlert = document.getElementById('cancelledAlert');
    const overdueAlert = document.getElementById('overdueAlert');
    
    // Only show cancelled alert if not previously closed
    if (cancelledAlert && localStorage.getItem('cancelledAlert_closed') !== 'true') {
        cancelledAlert.style.display = 'block';
    }
    
    // Only show overdue alert if not previously closed
    if (overdueAlert && localStorage.getItem('overdueAlert_closed') !== 'true') {
        overdueAlert.style.display = 'block';
    }
});

// ✅ OPTIONAL: Function to reset alerts (clear localStorage)
// Call this if you want to make alerts reappear
function resetAlerts() {
    localStorage.removeItem('cancelledAlert_closed');
    localStorage.removeItem('overdueAlert_closed');
    location.reload();
}

function printList() {
    const dateFrom = document.querySelector('input[name="date_from"]').value;
    const dateTo = document.querySelector('input[name="date_to"]').value;
    const search = document.querySelector('input[name="search"]').value;
    
    let url = '{{ route("sales_orders.printList") }}?';
    
    if (dateFrom) url += 'date_from=' + dateFrom + '&';
    if (dateTo) url += 'date_to=' + dateTo + '&';
    if (search) url += 'search=' + encodeURIComponent(search);
    
    window.open(url, '_blank');
}

function exportExcel() {
    const dateFrom = document.querySelector('input[name="date_from"]').value;
    const dateTo = document.querySelector('input[name="date_to"]').value;
    const search = document.querySelector('input[name="search"]').value;
    
    let url = '{{ route("sales_orders.exportExcel") }}?';
    
    if (dateFrom) url += 'date_from=' + dateFrom + '&';
    if (dateTo) url += 'date_to=' + dateTo + '&';
    if (search) url += 'search=' + encodeURIComponent(search);
    
    window.location.href = url;
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('statusUpdateForm');
    const submitBtn = document.getElementById('submitStatusBtn');
    const modal = document.getElementById('notesModal');
    const modalNotesTextarea = document.getElementById('modalNotesTextarea');
    const modalStatusText = document.getElementById('modalStatusText');
    const hiddenNotes = document.getElementById('hiddenNotes');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const cancelModalBtn = document.getElementById('cancelModalBtn');
    const confirmModalBtn = document.getElementById('confirmModalBtn');
    const statusRadios = document.querySelectorAll('.status-radio');

    let selectedStatus = '';

    if (submitBtn) {
        submitBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            selectedStatus = document.querySelector('.status-radio:checked')?.value;
            
            if (!selectedStatus) {
                alert('Please select a status first.');
                return;
            }

            if (selectedStatus === 'Declined' || selectedStatus === 'Cancelled') {
                modalStatusText.textContent = selectedStatus.toLowerCase();
                modalNotesTextarea.value = '';
                modal.classList.remove('hidden');
                modalNotesTextarea.focus();
            } else {
                if (confirm('Are you sure you want to update this status to Approved?')) {
                    hiddenNotes.value = '';
                    form.submit();
                }
            }
        });
    }

    if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
    if (cancelModalBtn) cancelModalBtn.addEventListener('click', closeModal);
    
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }

    function closeModal() {
        modal.classList.add('hidden');
        modalNotesTextarea.value = '';
    }

    if (confirmModalBtn) {
        confirmModalBtn.addEventListener('click', function() {
            const notes = modalNotesTextarea.value.trim();
            
            if (!notes) {
                alert('Please provide a reason before confirming.');
                modalNotesTextarea.focus();
                return;
            }

            hiddenNotes.value = notes;
            modal.classList.add('hidden');
            
            setTimeout(function() {
                form.submit();
            }, 100);
        });
    }

    if (modalNotesTextarea) {
        modalNotesTextarea.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.ctrlKey) {
                confirmModalBtn.click();
            }
        });
    }
});
</script>

@endsection