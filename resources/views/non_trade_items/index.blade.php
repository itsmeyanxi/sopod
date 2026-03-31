@extends('layouts.app')

@section('title', 'Non-Trade Items Library')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-white">NON-TRADE ITEMS LIBRARY</h1>
                <p class="text-gray-500 text-sm mt-1">Master data for non-trade items. Used for autocomplete in Purchase Requests.</p>
            </div>
            <div class="flex gap-2">
                <button onclick="document.getElementById('addItemModal').classList.remove('hidden')"
                    class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition">
                    <i class="fas fa-plus mr-1"></i> Add Item
                </button>
                <button onclick="document.getElementById('importModal').classList.remove('hidden')"
                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition">
                    <i class="fas fa-upload mr-1"></i> Import CSV/Excel
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-600 text-white px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">{{ session('error') }}</div>
        @endif

        <!-- Search -->
        <form method="GET" action="{{ route('non_trade_items.index') }}" class="mb-4 flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search items..."
                class="flex-1 bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
            <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded text-sm hover:bg-purple-700 transition">
                <i class="fas fa-search mr-1"></i> Search
            </button>
            @if(request('search'))
                <a href="{{ route('non_trade_items.index') }}" class="bg-gray-200 text-white px-4 py-2 rounded text-sm hover:bg-gray-500 transition">Clear</a>
            @endif
        </form>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray-700">
                <thead class="bg-gray-700 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="border border-gray-700 px-3 py-3 text-left">#</th>
                        <th class="border border-gray-700 px-3 py-3 text-left">ITEM CODE</th>
                        <th class="border border-gray-700 px-3 py-3 text-left">ITEM DESCRIPTION</th>
                        <th class="border border-gray-700 px-3 py-3 text-left">GROUP</th>
                        <th class="border border-gray-700 px-3 py-3 text-left">BRAND</th>
                        <th class="border border-gray-700 px-3 py-3 text-left">UoM</th>
                        <th class="border border-gray-700 px-3 py-3 text-left">TRADING UoM</th>
                        <th class="border border-gray-700 px-3 py-3 text-left">CONVERSION</th>
                        <th class="border border-gray-700 px-3 py-3 text-center">STATUS</th>
                        <th class="border border-gray-700 px-3 py-3 text-left">SUPPLIER</th>
                        <th class="border border-gray-700 px-3 py-3 text-center">ACTION</th>
                    </tr>
                </thead>
                <tbody class="text-gray-300">
                    @forelse($items as $item)
                        <tr class="hover:bg-gray-900">
                            <td class="border border-gray-700 px-3 py-2 text-gray-500">{{ $items->firstItem() + $loop->index }}</td>
                            <td class="border border-gray-700 px-3 py-2">
                                <form action="{{ route('non_trade_items.update', $item->id) }}" method="POST" class="flex items-center gap-1">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="item_code" value="{{ $item->item_code }}"
                                        class="w-28 px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                                        placeholder="—">
                                    <button type="submit" class="text-green-500 hover:text-green-600 text-xs" title="Save">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            </td>
                            <td class="border border-gray-700 px-3 py-2">{{ $item->name }}</td>
                            <td class="border border-gray-700 px-3 py-2">{{ $item->group ?? '-' }}</td>
                            <td class="border border-gray-700 px-3 py-2">{{ $item->brand ?? '-' }}</td>
                            <td class="border border-gray-700 px-3 py-2">{{ $item->unit ?? '-' }}</td>
                            <td class="border border-gray-700 px-3 py-2">{{ $item->trading_uom ?? '-' }}</td>
                            <td class="border border-gray-700 px-3 py-2">{{ $item->conversion ?? '-' }}</td>
                            <td class="border border-gray-700 px-3 py-2 text-center">
                                @if(($item->status ?? 'active') === 'active')
                                    <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs font-semibold">Active</span>
                                @else
                                    <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded text-xs font-semibold">{{ ucfirst($item->status) }}</span>
                                @endif
                            </td>
                            <td class="border border-gray-700 px-3 py-2">
                                @if($item->supplier)
                                    <span class="text-purple-600">{{ $item->supplier->supplier_name }}</span>
                                @else
                                    <span class="text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="border border-gray-700 px-3 py-2 text-center">
                                <form action="{{ route('non_trade_items.destroy', $item->id) }}" method="POST" class="inline"
                                    onsubmit="return confirm('Remove this item from the library?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-600 text-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="border border-gray-700 px-4 py-8 text-center text-gray-500">
                                No items in the library yet. Import a CSV/Excel or add items manually.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex justify-between items-center">
            <p class="text-gray-500 text-sm">{{ $items->total() }} item(s) total</p>
            {{ $items->links() }}
        </div>
    </div>
</div>

<!-- Import Modal -->
<div id="importModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-gray-800 rounded-lg p-6 w-full max-w-lg mx-4">
        <h3 class="text-lg font-bold text-white mb-2">Import Items from CSV/Excel</h3>
        <p class="text-gray-500 text-sm mb-4">
            Supported column headers (order doesn't matter):<br>
            <strong class="text-gray-200">Item Code, Item Description, Group, Brand, UoM, Trading UoM, Conversion, Status</strong><br>
            <span class="text-gray-500">Optional: <strong>Supplier</strong> column to link items to suppliers.</span><br>
            First row must be the header row — it will be skipped automatically.<br>
            <span class="text-yellow-600">Duplicates (same item code or same name + supplier) will be updated.</span>
        </p>
        <form action="{{ route('non_trade_items.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <label class="block text-gray-300 mb-2">Select CSV or Excel File:</label>
                <input type="file" name="csv_file" accept=".csv,.txt,.xlsx,.xls" required
                    class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm">
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('importModal').classList.add('hidden')"
                    class="bg-gray-200 text-gray-200 px-4 py-2 rounded hover:bg-gray-300 transition">
                    Cancel
                </button>
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition">
                    <i class="fas fa-upload mr-1"></i> Upload & Import
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Add Item Modal -->
<div id="addItemModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-gray-800 rounded-lg p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-bold text-white mb-4">Add New Item</h3>
        <form action="{{ route('non_trade_items.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-300 text-sm mb-1">Item Code <span class="text-gray-500">(auto-generated if blank)</span></label>
                    <input type="text" name="item_code" maxlength="100"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="Leave blank to auto-generate">
                </div>
                <div>
                    <label class="block text-gray-300 text-sm mb-1">Item Description <span class="text-red-700">*</span></label>
                    <input type="text" name="name" required maxlength="500"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="e.g. MS Office License">
                </div>
                <div>
                    <label class="block text-gray-300 text-sm mb-1">Group</label>
                    <input type="text" name="group" maxlength="200"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="e.g. Office Supplies">
                </div>
                <div>
                    <label class="block text-gray-300 text-sm mb-1">Brand</label>
                    <input type="text" name="brand" maxlength="200"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="e.g. Microsoft">
                </div>
                <div>
                    <label class="block text-gray-300 text-sm mb-1">UoM</label>
                    <input type="text" name="unit" maxlength="100"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="e.g. pcs, box, kg">
                </div>
                <div>
                    <label class="block text-gray-300 text-sm mb-1">Trading UoM</label>
                    <input type="text" name="trading_uom" maxlength="100"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="e.g. pack, carton">
                </div>
                <div>
                    <label class="block text-gray-300 text-sm mb-1">Conversion</label>
                    <input type="text" name="conversion" maxlength="100"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="e.g. 1 box = 12 pcs">
                </div>
                <div>
                    <label class="block text-gray-300 text-sm mb-1">Status</label>
                    <select name="status"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-gray-300 text-sm mb-1">Supplier</label>
                    <select name="supplier_id"
                        class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">— No specific supplier —</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->supplier_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('addItemModal').classList.add('hidden')"
                    class="bg-gray-200 text-gray-200 px-4 py-2 rounded hover:bg-gray-300 transition">
                    Cancel
                </button>
                <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition">
                    <i class="fas fa-plus mr-1"></i> Add Item
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('importModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.add('hidden');
});
document.getElementById('addItemModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.add('hidden');
});
</script>
@endsection
