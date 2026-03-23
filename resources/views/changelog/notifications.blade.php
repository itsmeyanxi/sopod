@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="bg-gray-50 text-gray-100 min-h-screen p-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold">🔔 Notifications</h1>
            <p class="text-gray-400 mt-2">{{ $unreadCount }} unread notification(s)</p>
        </div>
        
        @if($unreadCount > 0)
        <form method="POST" action="{{ route('notifications.read_all') }}">
            @csrf
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg">
                <i class="fas fa-check-double"></i> Mark All as Read
            </button>
        </form>
        @endif
    </div>

    <!-- Notifications List -->
    <div class="space-y-4">
        @forelse($notifications as $notification)
        <div class="bg-white rounded-lg p-4 {{ $notification->is_read ? 'opacity-60' : 'border-l-4 border-blue-500' }}">
            <div class="flex justify-between items-start">
                <!-- Notification Content -->
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <!-- Icon -->
                        <div class="w-10 h-10 rounded-full flex items-center justify-center
                            @if($notification->change->change_type === 'create') bg-green-600
                            @elseif($notification->change->change_type === 'delete') bg-red-600
                            @else bg-blue-600 @endif">
                            @if($notification->change->change_type === 'create')
                                <i class="fas fa-plus text-gray-800"></i>
                            @elseif($notification->change->change_type === 'delete')
                                <i class="fas fa-trash text-gray-800"></i>
                            @else
                                <i class="fas fa-edit text-gray-800"></i>
                            @endif
                        </div>

                        <!-- Title -->
                        <div>
                            <h3 class="text-gray-800 font-semibold">
                                Sales Order Updated
                                @if(!$notification->is_read)
                                    <span class="ml-2 bg-blue-600 text-white text-xs px-2 py-0.5 rounded-full">NEW</span>
                                @endif
                            </h3>
                            <p class="text-gray-400 text-sm">
                                <a href="{{ route('sales_orders.show', $notification->change->sales_order_id) }}" 
                                   class="text-blue-400 hover:underline">
                                    {{ $notification->change->salesOrder->sales_order_number ?? 'N/A' }}
                                </a>
                            </p>
                        </div>
                    </div>

                    <!-- Change Details -->
                    <div class="ml-13 bg-gray-100 p-3 rounded mt-2">
                        <span class="px-2 py-1 bg-purple-900 rounded-full text-xs">
                            {{ ucwords(str_replace('_', ' ', $notification->change->field_changed)) }}
                        </span>
                        
                        <div class="mt-2 text-sm">
                            @if($notification->change->change_type === 'create')
                                <p class="text-green-400">
                                    <i class="fas fa-plus-circle mr-2"></i>{{ $notification->change->new_value }}
                                </p>
                            @elseif($notification->change->change_type === 'delete')
                                <p class="text-red-400">
                                    <i class="fas fa-trash mr-2"></i>{{ $notification->change->old_value }}
                                </p>
                            @else
                                <div class="flex items-center gap-2 text-sm">
                                    <span class="text-red-400">{{ $notification->change->old_value }}</span>
                                    <i class="fas fa-arrow-right text-gray-500"></i>
                                    <span class="text-green-400">{{ $notification->change->new_value }}</span>
                                </div>
                            @endif
                        </div>

                        <p class="text-gray-400 text-xs mt-2">
                            Changed by: <span class="text-gray-800">{{ $notification->change->user->name ?? 'System' }}</span>
                        </p>
                    </div>

                    <!-- Timestamp -->
                    <p class="text-gray-500 text-xs mt-2 ml-13">
                        {{ $notification->created_at->diffForHumans() }}
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-2 ml-4">
                    <a href="{{ route('changelog.sales_order', $notification->change->sales_order_id) }}"
                       class="bg-gray-100 hover:bg-gray-100 text-gray-800 px-3 py-2 rounded text-sm">
                        <i class="fas fa-history"></i>
                    </a>
                    
                    @if(!$notification->is_read)
                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                        @csrf
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-sm">
                            <i class="fas fa-check"></i>
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-lg p-8 text-center">
            <i class="fas fa-bell-slash text-gray-600 text-6xl mb-4"></i>
            <p class="text-gray-400">No notifications yet</p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $notifications->links() }}
    </div>
</div>

@if(session('success'))
<div class="fixed bottom-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg">
    {{ session('success') }}
</div>
@endif
@endsection