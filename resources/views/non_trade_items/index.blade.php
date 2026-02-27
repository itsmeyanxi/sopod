@extends('layouts.app')

@section('title', 'Non-Trade Items Library')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-white">NON-TRADE ITEMS LIBRARY</h1>
                <p class="text-gray-400 text-sm mt-1">Items linked to suppliers. Used for autocomplete in Purchase Requests — filtered by selected supplier.</p>
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
                <a href="{{ route('non_trade_items.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded text-sm hover:bg-gray-500 transition">Clear</a>
            @endif
        </form>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray-700">
                <thead class="bg-gray-700 text-gray-300 uppercase text-xs">
                    <tr>
                        <th class="border border-gray-700 px-4 py-3 text-left">#</th>
                        <th class="border border-gray-700 px-4 py-3 text-left">ITEM CODE</th>
                        <th class="border border-gray-700 px-4 py-3 text-left">ITEM DESCRIPTION</th>
                        <th class="border border-gray-700 px-4 py-3 text-left">SUPPLIER</th>
                        <th class="border border-gray-700 px-4 py-3 text-left">UNIT</th>
                        <th class="border border-gray-700 px-4 py-3 text-left">DATE ADDED</th>
                        <th class="border border-gray-700 px-4 py-3 text-center">ACTION</th>
                    </tr>
                </thead>
                <tbody class="text-gray-300">
                    @forelse($items as $item)
                        <tr class="hover:bg-gray-700/40">
                            <td class="border border-gray-700 px-4 py-3 text-gray-500">{{ $items->firstItem() + $loop->index }}</td>
                            <td class="border border-gray-700 px-4 py-3">
                                <form action="{{ route('non_trade_items.update', $item->id) }}" method="POST" class="flex items-center gap-1">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="item_code" value="{{ $item->item_code }}"
                                        class="w-32 px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                                        placeholder="—">
                                    <button type="submit" class="text-green-400 hover:text-green-300 text-xs" title="Save">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            </td>
                            <td class="border border-gray-700 px-4 py-3">{{ $item->name }}</td>
                            <td class="border border-gray-700 px-4 py-3">
                                @if($item->supplier)
                                    <span class="text-purple-300">{{ $item->supplier->supplier_name }}</span>
                                @else
                                    <span class="text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="border border-gray-700 px-4 py-3">{{ $item->unit ?? '-' }}</td>
                            <td class="border border-gray-700 px-4 py-3">{{ $item->created_at->format('M d, Y') }}</td>
                            <td class="border border-gray-700 px-4 py-3 text-center">
                                <form action="{{ route('non_trade_items.destroy', $item->id) }}" method="POST" class="inline"
                                    onsubmit="return confirm('Remove this item from the library?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-400 hover:text-red-300 text-sm">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="border border-gray-700 px-4 py-8 text-center text-gray-400">
                                No items in the library yet. Import a CSV or submit a Purchase Request to populate.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex justify-between items-center">
            <p class="text-gray-400 text-sm">{{ $items->total() }} item(s) total</p>
            {{ $items->links() }}
        </div>
    </div>
</div>

<!-- Import Modal -->
<div id="importModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-gray-800 rounded-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-bold text-white mb-2">Import Items from CSV</h3>
        <p class="text-gray-400 text-sm mb-4">
            Format: <strong class="text-gray-300">Column A = Supplier Name, Column B = Item Description</strong><br>
            First row must be the header row — it will be skipped automatically.<br>
            Supplier names must match exactly as entered in the system.<br>
            <span class="text-yellow-400">Same item from a different supplier will be added as a separate entry. Exact duplicates (same item + same supplier) are skipped.</span>
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
                    class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-500 transition">
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
    <div class="bg-gray-800 rounded-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-bold text-white mb-4">Add New Item</h3>
        <form action="{{ route('non_trade_items.store') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-gray-300 text-sm mb-1">Item Description <span class="text-red-400">*</span></label>
                <input type="text" name="name" required maxlength="500"
                    class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                    placeholder="e.g. MS Office License">
            </div>
            <div class="mb-4">
                <label class="block text-gray-300 text-sm mb-1">Item Code <span class="text-gray-500">(auto-generated if blank)</span></label>
                <input type="text" name="item_code" maxlength="100"
                    class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                    placeholder="Leave blank to auto-generate">
            </div>
            <div class="mb-4">
                <label class="block text-gray-300 text-sm mb-1">Supplier</label>
                <select name="supplier_id"
                    class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">— No specific supplier —</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->supplier_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-gray-300 text-sm mb-1">Unit <span class="text-gray-500">(optional)</span></label>
                <input type="text" name="unit" maxlength="100"
                    class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                    placeholder="e.g. pcs, box, kg">
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('addItemModal').classList.add('hidden')"
                    class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-500 transition">
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
