@if($status === 'pending')
    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs">Pending</span>
@elseif($status === 'approved')
    <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">Approved</span>
@elseif($status === 'rejected')
    <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs">Rejected</span>
@elseif($status === 'paid')
    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">Paid</span>
@elseif($status === 'cancelled')
    <span class="px-2 py-1 bg-gray-700 text-gray-500 rounded text-xs">Cancelled</span>
@else
    <span class="px-2 py-1 bg-gray-700 text-gray-500 rounded text-xs">{{ ucfirst($status) }}</span>
@endif
