@extends('layouts.app')
@section('title', 'Storage List')
@section('content')
<div class="container mx-auto">
    <div class="bg-white text-gray-800 rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6 border-b border-gray-200 pb-4">
            <h1 class="text-2xl font-bold text-gray-800">STORAGE MANAGEMENT</h1>
            <a href="{{ route('storages.create') }}" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800">
                <i class="fas fa-plus mr-1"></i> Create Storage
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-600 text-white px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif

        @if($storages->isEmpty())
            <div class="text-center py-12">
                <i class="fas fa-inbox text-4xl text-gray-500 mb-3"></i>
                <p class="text-gray-500">No storages created yet.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left">Code</th>
                            <th class="px-4 py-3 text-left">Name</th>
                            <th class="px-4 py-3 text-left">Warehouse</th>
                            <th class="px-4 py-3 text-left">Location</th>
                            <th class="px-4 py-3 text-center">Temperature</th>
                            <th class="px-4 py-3 text-right">Capacity</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($storages as $storage)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 font-mono font-semibold">{{ $storage->storage_code }}</td>
                            <td class="px-4 py-3">{{ $storage->storage_name }}</td>
                            <td class="px-4 py-3">{{ $storage->warehouse?->warehouse_name ?? 'N/A' }}</td>
                            <td class="px-4 py-3">{{ $storage->location ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 rounded text-xs {{ $storage->temperature_controlled === 'Yes' ? 'bg-blue-100/50 text-blue-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $storage->temperature_controlled }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">{{ $storage->capacity ? $storage->capacity . ' L' : '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 rounded text-xs {{ $storage->isActive() ? 'bg-green-100/50 text-green-700' : 'bg-red-100/50 text-red-700' }}">
                                    {{ ucfirst($storage->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="{{ route('storages.show', $storage->id) }}" class="text-blue-700 hover:text-blue-700" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('storages.edit', $storage->id) }}" class="text-yellow-700 hover:text-yellow-700" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('storages.destroy', $storage->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this storage?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-700 hover:text-red-700" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $storages->links() }}
        @endif
    </div>
</div>
@endsection
