@if($status === 'pending')
    <span class="px-2 py-1 bg-yellow-900 text-yellow-300 rounded text-xs">Pending</span>
@elseif($status === 'approved')
    <span class="px-2 py-1 bg-green-900 text-green-300 rounded text-xs">Approved</span>
@elseif($status === 'rejected')
    <span class="px-2 py-1 bg-red-900 text-red-300 rounded text-xs">Rejected</span>
@elseif($status === 'paid')
    <span class="px-2 py-1 bg-blue-900 text-blue-300 rounded text-xs">Paid</span>
@elseif($status === 'cancelled')
    <span class="px-2 py-1 bg-gray-100 text-gray-400 rounded text-xs">Cancelled</span>
@else
    <span class="px-2 py-1 bg-gray-100 text-gray-400 rounded text-xs">{{ ucfirst($status) }}</span>
@endif
