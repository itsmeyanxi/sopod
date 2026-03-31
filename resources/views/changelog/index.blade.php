@extends('layouts.app')

@section('title', 'Change Log')

@section('content')
<div class="bg-gray-900 text-gray-100 min-h-screen p-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">📜 Sales Order Change Log</h1>
        <a href="{{ route('changelog.export', request()->query()) }}" 
           class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded-lg">
            <i class="fas fa-download"></i> Export CSV
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-gray-800 p-6 rounded-lg mb-6">
        <form method="GET" action="{{ route('changelog.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Field Filter -->
            <div>
                <label class="text-gray-500 text-sm mb-2 block">Field Changed</label>
                <select name="field" class="w-full bg-gray-700 text-white px-4 py-2 rounded border border-gray-600">
                    <option value="">All Fields</option>
                    @foreach($fields as $field)
                        <option value="{{ $field }}" {{ request('field') == $field ? 'selected' : '' }}>
                            {{ app('App\Http\Controllers\ChangeLogController')->getFieldDisplay($field) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- From Date -->
            <div>
                <label class="text-gray-500 text-sm mb-2 block">From Date</label>
                <input type="date" name="from_date" value="{{ request('from_date') }}"
                       class="w-full bg-gray-700 text-white px-4 py-2 rounded border border-gray-600">
            </div>

            <!-- To Date -->
            <div>
                <label class="text-gray-500 text-sm mb-2 block">To Date</label>
                <input type="date" name="to_date" value="{{ request('to_date') }}"
                       class="w-full bg-gray-700 text-white px-4 py-2 rounded border border-gray-600">
            </div>

            <!-- Submit -->
            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Changes Table -->
    <div class="bg-gray-800 rounded-lg overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Date/Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">SO Number</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Action</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Field</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Change Details</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Changed By</th>
                    <th class="px-6 py-3 text-center text-xs font-medium uppercase">View</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($changes as $change)
                @php
                    $controller = app('App\Http\Controllers\ChangeLogController');
                    $actionType = $controller->getActionType($change);
                    $changeDesc = $controller->getChangeDescription($change);
                    $fieldDisplay = $controller->getFieldDisplay($change->field_changed);
                    
                    // Determine if this is a simple update field
                    $isSimpleUpdate = in_array($change->field_changed, ['quantity', 'unit_price', 'total_amount']) && $actionType === 'updated';
                    
                    $actionConfig = [
                        'added' => ['icon' => 'fa-plus', 'bg' => 'bg-green-100 text-green-700'],
                        'removed' => ['icon' => 'fa-trash', 'bg' => 'bg-red-100 text-red-700'],
                        'updated' => ['icon' => 'fa-edit', 'bg' => 'bg-blue-100 text-blue-700'],
                    ];
                    
                    $config = $actionConfig[$actionType] ?? $actionConfig['updated'];
                @endphp
                
                <tr class="hover:bg-gray-700">
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div>{{ $change->created_at->format('M d, Y') }}</div>
                        <div class="text-gray-500 text-xs">{{ $change->created_at->format('h:i A') }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm font-semibold text-blue-700">
                        <a href="{{ route('sales_orders.show', $change->sales_order_id) }}" class="hover:underline">
                            {{ $change->salesOrder->sales_order_number ?? 'N/A' }}
                        </a>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <span class="px-3 py-1 {{ $config['bg'] }} rounded-full text-xs font-semibold">
                            <i class="fas {{ $config['icon'] }} mr-1"></i>{{ ucfirst($actionType) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <span class="text-gray-500">{{ $fieldDisplay }}</span>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <div class="max-w-md">
                            {!! $changeDesc !!}
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-user-circle text-gray-500"></i>
                            {{ $change->user->name ?? 'System' }}
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <a href="{{ route('changelog.sales_order', $change->sales_order_id) }}"
                           class="text-blue-700 hover:text-blue-700 text-sm inline-flex items-center gap-1 bg-blue-50 px-3 py-1 rounded">
                            <i class="fas fa-history"></i> History
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2 block"></i>
                        No changes found
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $changes->appends(request()->query())->links() }}
    </div>
</div>
@endsection