@extends('layouts.app')

@section('content')
<div class="p-6 bg-gray-900 min-h-screen text-white">
    <h1 class="text-2xl font-bold mb-6">Customers</h1>

    <!-- 🔍 Search & Create -->
    <div class="flex flex-col sm:flex-row justify-end items-center mb-4 gap-2">
        <input 
            id="searchInput" 
            type="text" 
            placeholder="Search customer code / name" 
            class="border border-gray-700 bg-gray-800 text-gray-200 rounded px-3 py-2 w-full sm:w-64 focus:outline-none focus:ring-2 focus:ring-purple-500"
        />

        @if(auth()->user()->canManageCustomers())
            <a href="{{ route('customers.create') }}" 
               class="bg-gradient-to-r from-purple-500 to-indigo-500 hover:from-purple-600 hover:to-indigo-600 text-white px-4 py-2 rounded transition w-full sm:w-auto text-center">
               Create Customer
            </a>
        @endif
    </div>

    <!-- 📋 Responsive Table -->
    <div class="overflow-x-auto bg-gray-800 rounded-lg shadow border border-gray-700">
        <table id="customersTable" class="min-w-full divide-y divide-gray-700 text-sm">
            <thead class="bg-gray-700 text-gray-300 uppercase text-xs tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">ID</th>
                    <th class="px-4 py-3 text-left">Code</th>
                    <th class="px-4 py-3 text-left">Name</th>
                    <th class="px-4 py-3 text-left">Business Style</th>
                    <th class="px-4 py-3 text-left">Billing Address</th>
                    <th class="px-4 py-3 text-left">TIN</th>
                    <th class="px-4 py-3 text-left">Shipping Address</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-center">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-700 text-gray-300">
                @forelse($customers as $customer)
                    <tr class="hover:bg-gray-700/40 transition">
                        <td class="px-4 py-3">{{ $customer->id }}</td>
                        <td class="px-4 py-3">{{ $customer->customer_code }}</td>
                        <td class="px-4 py-3">{{ $customer->customer_name }}</td>
                        <td class="px-4 py-3">{{ $customer->business_style ?? 'N/A' }}</td>
                        <td class="px-4 py-3">{{ Str::limit($customer->billing_address ?? 'N/A', 30) }}</td>
                        <td class="px-4 py-3">{{ $customer->tin_no ?? 'N/A' }}</td>
                        <td class="px-4 py-3">{{ Str::limit($customer->shipping_address ?? 'N/A', 30) }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded text-xs font-medium {{ $customer->status === 'enabled' ? 'bg-green-600 text-white' : 'bg-red-600 text-white' }}">
                                {{ ucfirst($customer->status) }}
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
                                           class="bg-yellow-600 hover:bg-yellow-700 text-white text-xs font-medium px-3 py-1.5 rounded transition">
                                           Edit
                                        </a>
                                    @endif

                                    <!-- 🔄 Enable / Disable Toggle -->
                                    @if(auth()->user()->canEditCustomers())
                                        <form action="{{ route('customers.toggleStatus', $customer->id) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" 
                                                    class="text-white text-xs font-medium px-3 py-1.5 rounded transition
                                                           {{ $customer->status === 'enabled' 
                                                              ? 'bg-gray-600 hover:bg-gray-700' 
                                                              : 'bg-green-600 hover:bg-green-700' }}">
                                                {{ $customer->status === 'enabled' ? 'Disable' : 'Enable' }}
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
                        <td colspan="9" 
                            class="px-6 py-4 text-center text-gray-400">
                            No customers found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- 📊 Count -->
    <div class="mt-4 text-gray-400 text-sm" id="itemsCount"></div>
</div>

<script>
    const searchInput = document.getElementById('searchInput');
    const table = document.getElementById('customersTable');
    const rows = table.querySelectorAll('tbody tr');
    const countDisplay = document.getElementById('itemsCount');

    function updateVisibleCount() {
        const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
        countDisplay.textContent = `Showing ${visibleRows.length} customer${visibleRows.length !== 1 ? 's' : ''}`;
    }

    searchInput.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        rows.forEach(row => {
            const code = row.cells[1]?.textContent.toLowerCase() || '';
            const name = row.cells[2]?.textContent.toLowerCase() || '';
            const match = code.includes(q) || name.includes(q);
            row.style.display = match ? '' : 'none';
        });
        updateVisibleCount();
    });

    updateVisibleCount();
</script>
@endsection