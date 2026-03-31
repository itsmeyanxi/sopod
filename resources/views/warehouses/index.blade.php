@extends('layouts.app')
@section('title', 'Warehouses')
@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
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
                    <tr class="bg-gray-700 text-gray-500">
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
                    <tr class="border-b border-gray-700 hover:bg-gray-700">
                        <td class="px-4 py-2 font-mono text-purple-700">{{ $warehouse->warehouse_code }}</td>
                        <td class="px-4 py-2 font-semibold">{{ $warehouse->warehouse_name }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ Str::limit($warehouse->address, 40) }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $warehouse->contact_number }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-1 rounded text-xs font-semibold {{ $warehouse->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ strtoupper($warehouse->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-2">
                            <a href="{{ route('warehouses.show', $warehouse->id) }}" class="text-blue-700 hover:text-blue-700 mr-2"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('warehouses.edit', $warehouse->id) }}" class="text-yellow-700 hover:text-yellow-700 mr-2"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('warehouses.destroy', $warehouse->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this warehouse?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-700 hover:text-red-700"><i class="fas fa-trash"></i></button>
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