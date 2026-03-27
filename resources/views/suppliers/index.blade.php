@extends('layouts.app')

@section('title', 'Suppliers')

@section('content')
<div class="container mx-auto">
    <div class="bg-white text-gray-800 rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-200 pb-4">
            <h1 class="text-2xl font-bold text-gray-800">SUPPLIERS</h1>
            <div class="flex gap-2">
                <a href="{{ route('suppliers.export') }}" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition">
                    <i class="fas fa-file-excel mr-1"></i> Export to Excel
                </a>
                <a href="{{ route('suppliers.create') }}" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-4 py-2 rounded hover:from-purple-700 hover:to-purple-800 transition">
                    <i class="fas fa-plus mr-1"></i> Add New Supplier
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-600 text-white px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        <!-- Suppliers Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full bg-gray-50 border border-gray-200">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="px-4 py-2 border-b border-gray-200 text-left text-gray-500">Supplier</th>
                        <th class="px-4 py-2 border-b border-gray-200 text-left text-gray-500">Payment Terms</th>
                        <th class="px-4 py-2 border-b border-gray-200 text-left text-gray-500">Contact Person</th>
                        <th class="px-4 py-2 border-b border-gray-200 text-left text-gray-500">Email</th>
                        <th class="px-4 py-2 border-b border-gray-200 text-left text-gray-500">Contact Number</th>
                        <th class="px-4 py-2 border-b border-gray-200 text-left text-gray-500">Bank</th>
                        <th class="px-4 py-2 border-b border-gray-200 text-center text-gray-500">Status</th>
                        <th class="px-4 py-2 border-b border-gray-200 text-center text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppliers as $supplier)
                        <tr class="hover:bg-gray-100 transition">
                            <td class="px-4 py-2 border-b border-gray-200 text-gray-500">
                                <div class="font-semibold text-gray-700">{{ $supplier->supplier_name }}</div>
                                <div class="text-xs text-gray-500">{{ $supplier->supplier_code }}</div>
                            </td>
                            <td class="px-4 py-2 border-b border-gray-200 text-gray-500">{{ $supplier->terms ?? 'N/A' }}</td>
                            <td class="px-4 py-2 border-b border-gray-200 text-gray-500">{{ $supplier->contact_person ?? 'N/A' }}</td>
                            <td class="px-4 py-2 border-b border-gray-200 text-gray-500">{{ $supplier->email ?? 'N/A' }}</td>
                            <td class="px-4 py-2 border-b border-gray-200 text-gray-500">{{ $supplier->contact_number ?? 'N/A' }}</td>
                            <td class="px-4 py-2 border-b border-gray-200 text-gray-500 text-sm">{{ $supplier->bank ?? 'N/A' }}</td>
                            <td class="px-4 py-2 border-b border-gray-200 text-center">
                                @if($supplier->status === 'active')
                                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">Active</span>
                                @else
                                    <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 border-b border-gray-200 text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="{{ route('suppliers.show', $supplier->id) }}" class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 text-xs">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="{{ route('suppliers.edit', $supplier->id) }}" class="bg-yellow-600 text-white px-3 py-1 rounded hover:bg-yellow-700 text-xs">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <form action="{{ route('suppliers.toggleStatus', $supplier->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="bg-{{ $supplier->status === 'active' ? 'gray' : 'green' }}-600 text-white px-3 py-1 rounded hover:bg-{{ $supplier->status === 'active' ? 'gray' : 'green' }}-700 text-xs">
                                            <i class="fas fa-{{ $supplier->status === 'active' ? 'ban' : 'check' }}"></i> {{ $supplier->status === 'active' ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                No suppliers found. Create your first supplier using the button above.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $suppliers->links() }}
        </div>
    </div>
</div>
@endsection
