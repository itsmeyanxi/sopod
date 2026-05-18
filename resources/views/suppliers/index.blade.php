@extends('layouts.app')

@section('title', 'Suppliers & Vendors')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">SUPPLIERS & VENDORS</h1>
            <div class="flex gap-2">
                <a href="{{ route('excel.import') }}" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition text-sm">
                    <i class="fas fa-file-import mr-1"></i> Import Vendors
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-600 text-white px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">{{ session('error') }}</div>
        @endif

        <!-- Tabs -->
        <div class="flex border-b border-gray-700 mb-4">
            <a href="{{ route('suppliers.index', ['tab' => 'vendors']) }}"
               class="px-6 py-3 font-semibold text-sm border-b-2 transition border-orange-500 text-orange-400">
                <i class="fas fa-building mr-1"></i> Vendors
                <span class="ml-1 px-2 py-0.5 rounded-full text-xs bg-orange-500/20 text-orange-300">{{ $vendorCategoryCounts->sum() }}</span>
            </a>
        </div>

        {{-- ─── VENDORS TAB ──────────────────────────────────────────────── --}}

            <!-- Category sub-tabs -->
            <div class="flex flex-wrap gap-2 mb-4">
                <a href="{{ route('suppliers.index', ['tab' => 'vendors', 'search' => $search]) }}"
                   class="px-4 py-1.5 rounded-full text-xs font-medium transition {{ !$category ? 'bg-orange-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                    All <span class="ml-1 opacity-75">({{ $vendorCategoryCounts->sum() }})</span>
                </a>
                @foreach(['TRADE' => 'Trade', 'NON TRADE' => 'Non-Trade', 'EMPLOYEES' => 'Employees'] as $catKey => $catLabel)
                    <a href="{{ route('suppliers.index', ['tab' => 'vendors', 'category' => $catKey, 'search' => $search]) }}"
                       class="px-4 py-1.5 rounded-full text-xs font-medium transition {{ $category === $catKey ? 'bg-orange-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                        {{ $catLabel }} <span class="ml-1 opacity-75">({{ $vendorCategoryCounts[$catKey] ?? 0 }})</span>
                    </a>
                @endforeach
            </div>

            <!-- Search -->
            <form method="GET" action="{{ route('suppliers.index') }}" class="mb-4 flex gap-2 items-center">
                <input type="hidden" name="tab" value="vendors">
                @if($category)<input type="hidden" name="category" value="{{ $category }}">@endif
                <input type="text" name="search" value="{{ $search }}" placeholder="Search vendor code or name..."
                       class="flex-1 bg-gray-700 text-white border border-gray-600 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700 transition text-sm">
                    <i class="fas fa-search mr-1"></i> Search
                </button>
                @if($search || $category)
                    <a href="{{ route('suppliers.index', ['tab' => 'vendors']) }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-500 transition text-sm">
                        <i class="fas fa-times mr-1"></i> Clear
                    </a>
                @endif
                <a href="{{ route('suppliers.vendors.create') }}" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition text-sm whitespace-nowrap">
                    <i class="fas fa-plus mr-1"></i> New Vendor
                </a>
            </form>

            <!-- Vendors Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full bg-gray-900 border border-gray-700">
                    <thead>
                        <tr class="bg-gray-700">
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-300">Vendor Code</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-300">Vendor Name</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-300">Category</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-300">Group</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-left text-gray-300">GL Account</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-center text-gray-400">Status</th>
                            <th class="px-4 py-2 border-b border-gray-700 text-center text-gray-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vendors as $vendor)
                            <tr class="hover:bg-gray-700 transition">
                                <td class="px-4 py-2 border-b border-gray-700 text-blue-400 font-mono text-sm">{{ $vendor->vendor_code }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-gray-200 font-semibold">{{ $vendor->vendor_name }}</td>
                                <td class="px-4 py-2 border-b border-gray-700">
                                    @php
                                        $catColors = [
                                            'TRADE' => 'bg-green-500/20 text-green-400',
                                            'NON TRADE' => 'bg-yellow-500/20 text-yellow-400',
                                            'EMPLOYEES' => 'bg-purple-500/20 text-purple-400',
                                        ];
                                    @endphp
                                    <span class="px-2 py-0.5 rounded text-xs font-medium {{ $catColors[$vendor->category] ?? 'bg-gray-500/20 text-gray-400' }}">
                                        {{ $vendor->category ?: '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 border-b border-gray-700 text-gray-300 text-sm">{{ $vendor->group ?: '—' }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-gray-400 text-sm">{{ $vendor->gl_account ?: '—' }}</td>
                                <td class="px-4 py-2 border-b border-gray-700 text-center">
                                    @if($vendor->status === 'active')
                                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">Active</span>
                                    @else
                                        <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 border-b border-gray-700 text-center">
                                    <div class="flex justify-center gap-2">
                                        <a href="{{ route('suppliers.vendors.edit', $vendor->id) }}" class="bg-yellow-600 text-white px-3 py-1 rounded hover:bg-yellow-700 text-xs">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form action="{{ route('suppliers.vendors.destroy', $vendor->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this vendor?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 text-xs">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                                    @if($search || $category)
                                        No vendors found matching your filters.
                                    @else
                                        No vendors found. Import vendor data from the <a href="{{ route('imports.index') }}" class="text-blue-400 underline">Import Module</a>.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $vendors->links() }}</div>
    </div>
</div>
@endsection
