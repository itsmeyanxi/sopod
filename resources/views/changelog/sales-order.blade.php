@extends('layouts.app')

@section('title', 'Change History')

@section('content')
<div class="bg-gray-900 text-gray-100 min-h-screen p-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <a href="{{ route('changelog.index') }}" class="text-blue-700 hover:text-blue-700 text-sm mb-2 inline-block">
                ← Back to Change Log
            </a>
            <h1 class="text-3xl font-bold">📜 Change History</h1>
            <p class="text-gray-500 mt-2">Sales Order: <span class="text-white font-semibold">{{ $salesOrder->sales_order_number }}</span></p>
        </div>
        <a href="{{ route('sales_orders.show', $salesOrder->id) }}" 
           class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg">
            <i class="fas fa-eye"></i> View Sales Order
        </a>
    </div>

    <!-- Sales Order Info Card -->
    <div class="bg-gray-800 p-6 rounded-lg mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <p class="text-gray-500 text-sm">Customer</p>
            <p class="text-white font-semibold">{{ $salesOrder->customer_name ?? 'N/A' }}</p>
        </div>
        <div>
            <p class="text-gray-500 text-sm">Status</p>
            <p class="text-white font-semibold">
                <span class="px-3 py-1 rounded-full text-sm 
                    @if($salesOrder->status === 'Approved') bg-green-600
                    @elseif($salesOrder->status === 'Pending') bg-yellow-600
                    @else bg-gray-200 @endif">
                    {{ $salesOrder->status }}
                </span>
            </p>
        </div>
        <div>
            <p class="text-gray-500 text-sm">Total Amount</p>
            <p class="text-white font-semibold">₱{{ number_format($salesOrder->total_amount ?? 0, 2) }}</p>
        </div>
    </div>

    <!-- Timeline -->
    <div class="bg-gray-800 rounded-lg p-6">
        <h2 class="text-xl font-bold mb-6">Change Timeline</h2>
        
        @if($changes->isEmpty())
            <p class="text-gray-500 text-center py-8">No changes recorded for this sales order</p>
        @else
            <div class="space-y-6">
                @foreach($changes as $change)
                @php
                    $controller = app('App\Http\Controllers\ChangeLogController');
                    $actionType = $controller->getActionType($change);
                    $fieldDisplay = $controller->getFieldDisplay($change->field_changed);
                    
                    // Parse JSON values
                    $oldData = json_decode($change->old_value, true);
                    $newData = json_decode($change->new_value, true);
                    $isJson = (is_array($oldData) || is_array($newData));
                    
                    $actionConfig = [
                        'added' => ['icon' => 'fa-plus', 'bg' => 'bg-green-600'],
                        'removed' => ['icon' => 'fa-trash', 'bg' => 'bg-red-600'],
                        'updated' => ['icon' => 'fa-edit', 'bg' => 'bg-blue-600'],
                    ];
                    
                    $config = $actionConfig[$actionType] ?? $actionConfig['updated'];
                @endphp
                
                <div class="flex gap-4">
                    <!-- Timeline Icon -->
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $config['bg'] }}">
                            <i class="fas {{ $config['icon'] }} text-white"></i>
                        </div>
                        @if(!$loop->last)
                            <div class="w-0.5 h-full bg-gray-700 flex-1 mt-2"></div>
                        @endif
                    </div>

                    <!-- Change Content -->
                    <div class="flex-1 pb-8">
                        <div class="bg-gray-700 p-5 rounded-lg">
                            <!-- Header -->
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex items-center gap-2">
                                    <span class="px-3 py-1 bg-purple-100 rounded-full text-xs font-semibold">
                                        {{ $fieldDisplay }}
                                    </span>
                                    <span class="px-3 py-1 {{ $config['bg'] }}/20 text-{{ explode('-', $config['bg'])[1] }}-400 rounded-full text-xs font-semibold uppercase">
                                        {{ ucfirst($actionType) }}
                                    </span>
                                </div>
                                <div class="text-right text-sm text-gray-500">
                                    <p class="font-semibold">{{ $change->created_at->format('M d, Y') }}</p>
                                    <p>{{ $change->created_at->format('h:i A') }}</p>
                                </div>
                            </div>

                            <!-- Change Details with Side-by-Side Comparison -->
                            @if($actionType === 'updated')
                                @php
                                    // Check if this is a simple field update (quantity, unit_price, etc)
                                    $isSimpleField = in_array($change->field_changed, ['quantity', 'unit_price', 'total_amount', 'status', 'po_number', 'customer_id', 'request_delivery_date', 'sales_rep']);
                                @endphp
                                
                                @if($isSimpleField)
                                    <!-- Simple Field Update - Show Side by Side like Total Amount -->
                                    <div class="grid grid-cols-2 gap-4">
                                        <!-- BEFORE -->
                                        <div>
                                            <div class="flex items-center gap-2 mb-2">
                                                <p class="text-red-700 text-xs font-bold uppercase">Before</p>
                                            </div>
                                            <div class="bg-red-100 border-2 border-red-900/50 rounded-lg p-4">
                                                <p class="text-red-700 font-semibold text-lg">{{ $change->old_value }}</p>
                                            </div>
                                        </div>

                                        <!-- AFTER -->
                                        <div>
                                            <div class="flex items-center gap-2 mb-2">
                                                <p class="text-green-700 text-xs font-bold uppercase">After</p>
                                            </div>
                                            <div class="bg-green-100 border-2 border-green-900/50 rounded-lg p-4">
                                                <p class="text-green-700 font-semibold text-lg">{{ $change->new_value }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @elseif($isJson)
                                    <!-- For Item Updates - Show what changed in summary -->
                                    <div class="bg-gray-800 rounded p-3 mb-3 text-sm">
                                        <p class="text-gray-500">
                                            <i class="fas fa-info-circle text-blue-700 mr-2"></i>
                                            <strong>What Changed:</strong>
                                            @php
                                                $changes = [];
                                                if (is_array($oldData) && is_array($newData)) {
                                                    foreach($newData as $key => $value) {
                                                        if (isset($oldData[$key]) && $oldData[$key] != $value) {
                                                            $oldVal = $oldData[$key];
                                                            $newVal = $value;
                                                            if ($key === 'unit_price') {
                                                                $oldVal = '₱' . number_format($oldVal, 2);
                                                                $newVal = '₱' . number_format($newVal, 2);
                                                            } elseif ($key === 'discount') {
                                                                $oldVal .= '%';
                                                                $newVal .= '%';
                                                            }
                                                            $changes[] = ucwords(str_replace('_', ' ', $key)) . " ({$oldVal} → {$newVal})";
                                                        }
                                                    }
                                                }
                                            @endphp
                                            {{ !empty($changes) ? implode(', ', $changes) : 'Item details updated' }}
                                        </p>
                                    </div>

                                    <!-- Side-by-Side Comparison - Show only changed fields -->
                                    <div class="grid grid-cols-2 gap-4">
                                        @php
                                            $changedFields = [];
                                            if (is_array($oldData) && is_array($newData)) {
                                                foreach($newData as $key => $value) {
                                                    if (isset($oldData[$key]) && $oldData[$key] != $value) {
                                                        $changedFields[$key] = [
                                                            'old' => $oldData[$key],
                                                            'new' => $value
                                                        ];
                                                    }
                                                }
                                            }
                                        @endphp
                                        
                                        @if(!empty($changedFields))
                                            <!-- BEFORE -->
                                            <div>
                                                <div class="flex items-center gap-2 mb-2">
                                                    <p class="text-red-700 text-xs font-bold uppercase">Before</p>
                                                </div>
                                                <div class="bg-red-100 border-2 border-red-900/50 rounded-lg p-4">
                                                    @foreach($changedFields as $key => $values)
                                                        <div class="mb-3 last:mb-0">
                                                            <span class="text-gray-500 text-xs block mb-1">{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                                                            <span class="text-red-700 font-semibold text-lg">
                                                                @if($key === 'unit_price')
                                                                    ₱{{ number_format($values['old'], 2) }}
                                                                @elseif($key === 'discount')
                                                                    {{ $values['old'] }}%
                                                                @else
                                                                    {{ $values['old'] }}
                                                                @endif
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <!-- AFTER -->
                                            <div>
                                                <div class="flex items-center gap-2 mb-2">
                                                    <p class="text-green-700 text-xs font-bold uppercase">After</p>
                                                </div>
                                                <div class="bg-green-100 border-2 border-green-900/50 rounded-lg p-4">
                                                    @foreach($changedFields as $key => $values)
                                                        <div class="mb-3 last:mb-0">
                                                            <span class="text-gray-500 text-xs block mb-1">{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                                                            <span class="text-green-700 font-semibold text-lg">
                                                                @if($key === 'unit_price')
                                                                    ₱{{ number_format($values['new'], 2) }}
                                                                @elseif($key === 'discount')
                                                                    {{ $values['new'] }}%
                                                                @else
                                                                    {{ $values['new'] }}
                                                                @endif
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @else
                                            <div class="col-span-2 text-center text-gray-500 py-4">
                                                No specific field changes detected
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <!-- For Simple Field Updates - Show Side by Side -->
                                    <div class="grid grid-cols-2 gap-4">
                                        <!-- BEFORE -->
                                        <div>
                                            <div class="flex items-center gap-2 mb-2">
                                                <p class="text-red-700 text-xs font-bold uppercase">Before</p>
                                            </div>
                                            <div class="bg-red-100 border-2 border-red-900/50 rounded-lg p-4">
                                                <p class="text-red-700 font-semibold text-lg">{{ $change->old_value }}</p>
                                            </div>
                                        </div>

                                        <!-- AFTER -->
                                        <div>
                                            <div class="flex items-center gap-2 mb-2">
                                                <p class="text-green-700 text-xs font-bold uppercase">After</p>
                                            </div>
                                            <div class="bg-green-100 border-2 border-green-900/50 rounded-lg p-4">
                                                <p class="text-green-700 font-semibold text-lg">{{ $change->new_value }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                            @elseif($actionType === 'added')
                                <!-- For Added Items/Fields -->
                                <div class="bg-green-100 border-2 border-green-900/50 rounded-lg p-4 mb-4">
                                    <div class="flex items-center gap-2 mb-3">
                                        <i class="fas fa-plus-circle text-green-700"></i>
                                        <p class="text-green-700 text-sm font-bold uppercase">New Item Added</p>
                                    </div>
                                    @if($isJson && $newData)
                                        <div class="space-y-2">
                                            @foreach($newData as $key => $value)
                                                <div class="flex justify-between">
                                                    <span class="text-gray-500 text-sm">{{ ucwords(str_replace('_', ' ', $key)) }}:</span>
                                                    <span class="text-green-700 font-semibold text-sm">
                                                        @if($key === 'unit_price')
                                                            ₱{{ number_format($value, 2) }}
                                                        @elseif($key === 'discount')
                                                            {{ $value }}%
                                                        @else
                                                            {{ $value }}
                                                        @endif
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-green-700 font-semibold">{{ $change->new_value }}</p>
                                    @endif
                                </div>

                            @else
                                <!-- For Removed Items/Fields -->
                                <div class="bg-red-100 border-2 border-red-900/50 rounded-lg p-4 mb-4">
                                    <div class="flex items-center gap-2 mb-3">
                                        <i class="fas fa-trash-alt text-red-700"></i>
                                        <p class="text-red-700 text-sm font-bold uppercase">Item Removed</p>
                                    </div>
                                    @if($isJson && $oldData)
                                        <div class="space-y-2">
                                            @foreach($oldData as $key => $value)
                                                <div class="flex justify-between">
                                                    <span class="text-gray-500 text-sm">{{ ucwords(str_replace('_', ' ', $key)) }}:</span>
                                                    <span class="text-red-700 font-semibold text-sm">
                                                        @if($key === 'unit_price')
                                                            ₱{{ number_format($value, 2) }}
                                                        @elseif($key === 'discount')
                                                            {{ $value }}%
                                                        @else
                                                            {{ $value }}
                                                        @endif
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-red-700 font-semibold">{{ $change->old_value }}</p>
                                    @endif
                                </div>
                            @endif

                            <!-- Changed By Footer -->
                            <div class="pt-3 border-t border-gray-600 flex justify-between items-center">
                                <p class="text-gray-500 text-xs">
                                    <i class="fas fa-user-circle mr-1"></i>
                                    Changed by: <span class="text-white font-semibold">{{ $change->user->name ?? 'System' }}</span>
                                </p>
                                <p class="text-gray-500 text-xs">
                                    <i class="far fa-clock mr-1"></i>
                                    {{ $change->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection