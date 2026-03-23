@extends('layouts.app')
@section('title', 'Warehouses')
@section('content')
<div class="container mx-auto">
    <div class="bg-white text-gray-800 rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6 border-b border-gray-200 pb-4">
            <h1 class="text-2xl font-bold">WAREHOUSE LIST</h1>
            <a href="{{ route('warehouses.create') }}" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition">
                <i class="fas fa-plus mr-1"></i> Add Warehouse
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-600 text-white px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-100 text-gray-500">
                        <th class="px-4 py-2 text-left">Code</th>
                        <th class="px-4 py-2 text-left">Name</th>
                        <th class="px-4 py-2 text-left">Address</th>
                        <th class="px-4 py-2 text-left">Contact</th>
                        <th class="px-4 py-2 text-left">Status</th>
                        <th class="px-4 py-2 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($warehouses as $warehouse)
                    <tr class="border-b border-gray-200 hover:bg-gray-100">
                        <td class="px-4 py-2 font-mono text-purple-400">{{ $warehouse->warehouse_code }}</td>
                        <td class="px-4 py-2 font-semibold">{{ $warehouse->warehouse_name }}</td>
                        <td class="px-4 py-2 text-gray-400">{{ Str::limit($warehouse->address, 40) }}</td>
                        <td class="px-4 py-2 text-gray-400">{{ $warehouse->contact_number }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-1 rounded text-xs font-semibold {{ $warehouse->status === 'active' ? 'bg-green-900 text-green-300' : 'bg-red-900 text-red-300' }}">
                                {{ strtoupper($warehouse->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-2">
                            <a href="{{ route('warehouses.show', $warehouse->id) }}" class="text-blue-400 hover:text-blue-300 mr-2"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('warehouses.edit', $warehouse->id) }}" class="text-yellow-400 hover:text-yellow-300 mr-2"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('warehouses.destroy', $warehouse->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this warehouse?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-400 hover:text-red-300"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No warehouses found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $warehouses->links() }}</div>
    </div>
</div>
@endsection