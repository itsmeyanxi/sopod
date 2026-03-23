@extends('layouts.app')

@section('content')
<div class="p-6 bg-gray-50 min-h-screen text-gray-800">
    <h1 class="text-2xl font-bold mb-6">Customers</h1>

    <!-- 🔍 Search, Filters & Create -->
    <form method="GET" action="{{ route('customers.index') }}" id="filterForm">
        <div class="flex flex-col gap-4 mb-4">
            <!-- First Row: Export Button -->
            <div class="flex gap-2 w-full sm:w-auto">
                <a href="{{ route('customers.export') }}" 
                   class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded transition w-full sm:w-auto text-center inline-flex items-center justify-center gap-2">
                   <i class="fas fa-file-excel"></i>
                   Export to Excel
                </a>
            </div>

            <!-- Second Row: Filters and Search -->
            <div class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-center">
                <!-- Flag Filter -->
                <select name="flag_filter" 
                        class="border border-gray-200 bg-white text-gray-200 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500"
                        onchange="document.getElementById('filterForm').submit()">
                    <option value="">All Flag Status</option>
                    <option value="flagged" {{ request('flag_filter') === 'flagged' ? 'selected' : '' }}>🚩 Flagged</option>
                    <option value="unflagged" {{ request('flag_filter') === 'unflagged' ? 'selected' : '' }}>✅ Unflagged</option>
                </select>

                <!-- Status Filter -->
                <select name="status_filter" 
                        class="border border-gray-200 bg-white text-gray-200 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500"
                        onchange="document.getElementById('filterForm').submit()">
                    <option value="">All Status</option>
                    <option value="enabled" {{ request('status_filter') === 'enabled' ? 'selected' : '' }}>🟢 Enabled</option>
                    <option value="disabled" {{ request('status_filter') === 'disabled' ? 'selected' : '' }}>🔴 Disabled</option>
                </select>

                <!-- Search Input -->
                <input 
                    name="search"
                    type="text" 
                    placeholder="Search customer code / name" 
                    value="{{ request('search') }}"
                    class="border border-gray-200 bg-white text-gray-200 rounded px-3 py-2 flex-1 focus:outline-none focus:ring-2 focus:ring-purple-500"
                />

                <!-- Clear Filters Button -->
                @if(request()->hasAny(['flag_filter', 'status_filter', 'search']))
                    <a href="{{ route('customers.index') }}" 
                       class="bg-gray-100 hover:bg-gray-100 text-gray-800 px-4 py-2 rounded transition whitespace-nowrap">
                       Clear Filters
                    </a>
                @endif

                <!-- Create Customer Button -->
                @if(auth()->user()->canManageCustomers())
                    <a href="{{ route('customers.create') }}" 
                       class="bg-gradient-to-r from-purple-500 to-indigo-500 hover:from-purple-600 hover:to-indigo-600 text-gray-800 px-4 py-2 rounded transition whitespace-nowrap">
                       Create Customer
                    </a>
                @endif
            </div>
        </div>
    </form>

    <!-- 📋 Responsive Table -->
    <div class="overflow-x-auto bg-white rounded-lg shadow border border-gray-200">
        <table id="customersTable" class="min-w-full divide-y divide-gray-700 text-sm">
            <thead class="bg-gray-100 text-gray-500 uppercase text-xs tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">ID</th>
                    <th class="px-4 py-3 text-left">Code</th>
                    <th class="px-4 py-3 text-left">Name</th>
                    <!-- <th class="px-4 py-3 text-left">Business Style</th> -->
                     <th class="px-4 py-3 text-left">Billing Address</th>
                    <!-- <th class="px-4 py-3 text-left">TIN</th> 
                    <th class="px-4 py-3 text-left">Shipping Address</th> -->
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Flag Status</th>
                    <th class="px-4 py-3 text-center">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-700 text-gray-500">
                @forelse($customers as $customer)
                    <tr class="hover:bg-gray-100/40 transition">
                        <td class="px-4 py-3">{{ $customer->id }}</td>
                        <td class="px-4 py-3">{{ $customer->customer_code }}</td>
                        <td class="px-4 py-3">{{ $customer->customer_name }}</td>
                        <!-- <td class="px-4 py-3">{{ $customer->business_style ?? 'N/A' }}</td> -->
                        <td class="px-4 py-3">{{ Str::limit($customer->billing_address ?? 'N/A', 30) }}</td>
                        <!-- <td class="px-4 py-3">{{ $customer->tin_no ?? '000-000-000-00000' }}</td> -->
                        <!-- <td class="px-4 py-3">{{ Str::limit($customer->shipping_address ?? 'N/A', 30) }}</td> -->
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded text-xs font-medium {{ $customer->status === 'enabled' ? 'bg-green-600 text-white' : 'bg-red-600 text-white' }}">
                                {{ ucfirst($customer->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded text-xs font-medium {{ $customer->is_flagged ? 'bg-orange-600 text-white' : 'bg-blue-600 text-white' }}">
                                {{ $customer->is_flagged ? '🚩 Flagged' : '✅ Unflagged' }}
                            </span>
                        </td>

                        <td class="px-4 py-3 text-center">
                            <div class="flex flex-wrap justify-center gap-2">
                                <!-- 👁️ View Button -->
                                <a href="{{ route('customers.show', $customer->id) }}" 
                                   class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium px-3 py-1.5 rounded transition">
                                   View
                                </a>

                                @if(auth()->user()->canManageCustomers())
                                    <!-- ✏️ Edit -->
                                    @if(auth()->user()->canEditCustomers())
                                        <a href="{{ route('customers.edit', $customer->id) }}" 
                                           class="bg-yellow-600 hover:bg-yellow-700 text-gray-800 text-xs font-medium px-3 py-1.5 rounded transition">
                                           Edit
                                        </a>
                                    @endif

                                    <!-- 🔄 Enable / Disable Toggle -->
                                    @if(auth()->user()->canEditCustomers())
                                        <form action="{{ route('customers.toggleStatus', $customer->id) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" 
                                                    class="text-gray-800 text-xs font-medium px-3 py-1.5 rounded transition
                                                           {{ $customer->status === 'enabled' 
                                                              ? 'bg-gray-600 hover:bg-gray-100' 
                                                              : 'bg-green-600 hover:bg-green-700' }}">
                                                {{ $customer->status === 'enabled' ? 'Disable' : 'Enable' }}
                                            </button>
                                        </form>
                                    @endif

                                    <!-- 🚩 Flag / Unflag Toggle (CC_Approver only) -->
                                    @if(auth()->user()->canPerformInModule('can_manage', 'customers'))
                                        <form action="{{ route('customers.toggleFlag', $customer->id) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" 
                                                    class="text-gray-800 text-xs font-medium px-3 py-1.5 rounded transition
                                                           {{ $customer->is_flagged 
                                                              ? 'bg-orange-600 hover:bg-orange-700' 
                                                              : 'bg-blue-600 hover:bg-blue-700' }}"
                                                    title="{{ $customer->is_flagged ? 'Click to unflag customer' : 'Click to flag customer' }}">
                                                {{ $customer->is_flagged ? ' Unflag' : ' Flag' }}
                                            </button>
                                        </form>
                                    @endif

                                    <!-- 🗑️ Delete -->
                                    @if(auth()->user()->canDeleteCustomers())
                                        <form action="{{ route('customers.destroy', $customer->id) }}" 
                                              method="POST" 
                                              class="inline-block"
                                              onsubmit="return confirm('Are you sure you want to delete this customer?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="bg-red-600 hover:bg-red-700 text-white text-xs font-medium px-3 py-1.5 rounded transition">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" 
                            class="px-6 py-4 text-center text-gray-400">
                            No customers found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- 📊 Count -->
    <div class="mt-4 text-gray-400 text-sm">
        Showing {{ $customers->count() }} customer{{ $customers->count() !== 1 ? 's' : '' }}
    </div>
</div>
@endsection