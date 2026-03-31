@extends('layouts.app')
@section('title', $storage->storage_name)
@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <div>
                <h1 class="text-3xl font-bold text-white">{{ $storage->storage_name }}</h1>
                <p class="text-gray-500 text-sm">Code: <span class="font-mono font-semibold">{{ $storage->storage_code }}</span></p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('storages.edit', $storage->id) }}" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded transition">
                    <i class="fas fa-edit mr-1"></i> Edit
                </a>
                <a href="{{ route('storages.index') }}" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded transition">
                    <i class="fas fa-list mr-1"></i> Back to List
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-gray-900 rounded-lg p-4 border border-gray-700">
                <h3 class="text-lg font-semibold text-purple-700 mb-4">Details</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-gray-500 text-sm">Warehouse</p>
                        <p class="text-white font-semibold">{{ $storage->warehouse?->warehouse_name ?? 'Not Assigned' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Location</p>
                        <p class="text-white font-semibold">{{ $storage->location ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Temperature Controlled</p>
                        <span class="inline-block px-2 py-1 rounded text-xs {{ $storage->temperature_controlled === 'Yes' ? 'bg-blue-100/50 text-blue-700' : 'bg-gray-700 text-gray-500' }}">
                            {{ $storage->temperature_controlled }}
                        </span>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Capacity</p>
                        <p class="text-white font-semibold">{{ $storage->capacity ? $storage->capacity . ' Liters' : '-' }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-900 rounded-lg p-4 border border-gray-700">
                <h3 class="text-lg font-semibold text-purple-700 mb-4">Status</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-gray-500 text-sm">Status</p>
                        <span class="inline-block px-2 py-1 rounded text-xs {{ $storage->isActive() ? 'bg-green-100/50 text-green-700' : 'bg-red-100/50 text-red-700' }}">
                            {{ ucfirst($storage->status) }}
                        </span>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Created By</p>
                        <p class="text-white font-semibold">{{ $storage->creator?->name ?? 'System' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Created At</p>
                        <p class="text-white font-semibold">{{ $storage->created_at->format('M d, Y H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Last Updated</p>
                        <p class="text-white font-semibold">{{ $storage->updated_at->format('M d, Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>

        @if($storage->description)
        <div class="bg-gray-900 rounded-lg p-4 border border-gray-700 mb-6">
            <h3 class="text-lg font-semibold text-purple-700 mb-3">Description</h3>
            <p class="text-gray-500 whitespace-pre-wrap">{{ $storage->description }}</p>
        </div>
        @endif

        <div class="flex justify-end gap-2">
            <form action="{{ route('storages.destroy', $storage->id) }}" method="POST" onsubmit="return confirm('Delete this storage? This action cannot be undone.');">
                @csrf @method('DELETE')
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded transition">
                    <i class="fas fa-trash mr-1"></i> Delete Storage
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
