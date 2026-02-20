@extends('layouts.app')

@section('title', 'Trade Items Library')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-white">TRADE ITEMS LIBRARY</h1>
                <p class="text-gray-400 text-sm mt-1">Trade items linked to suppliers (Local or Import). Used for autocomplete in Purchase Orders — filtered by selected supplier.</p>
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

        <!-- Search & Filter -->
        <form method="GET" action="{{ route('trade_items.index') }}" class="mb-4 flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search items..."
                class="flex-1 bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
            <select name="account"
                class="bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                <option value="">— All Accounts —</option>
                @foreach($accounts as $acc)
                    <option value="{{ $acc }}" {{ request('account') == $acc ? 'selected' : '' }}>{{ $acc }}</option>
                @endforeach
            </select>
            <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded text-sm hover:bg-purple-700 transition">
                <i class="fas fa-search mr-1"></i> Search
            </button>
            @if(request('search') || request('account'))
                <a href="{{ route('trade_items.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded text-sm hover:bg-gray-500 transition">Clear</a>
            @endif
        </form>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray-700">
                <thead class="bg-gray-700 text-gray-300 uppercase text-xs">
                    <tr>
                        <th class="border border-gray-700 px-4 py-3 text-left">#</th>
                        <th class="border border-gray-700 px-4 py-3 text-left">ITEM DESCRIPTION</th>
                        <th class="border border-gray-700 px-4 py-3 text-left">SUPPLIER</th>
                        <th class="border border-gray-700 px-4 py-3 text-left">ACCOUNT</th>
                        <th class="border border-gray-700 px-4 py-3 text-left">VENDOR CODE</th>
                        <th class="border border-gray-700 px-4 py-3 text-center">TYPE</th>
                        <th class="border border-gray-700 px-4 py-3 text-left">DATE ADDED</th>
                        <th class="border border-gray-700 px-4 py-3 text-center">ACTION</th>
                    </tr>
                </thead>
                <tbody class="text-gray-300">
                    @forelse($items as $item)
                        <tr class="hover:bg-gray-700/40">
                            <td class="border border-gray-700 px-4 py-3 text-gray-500">{{ $items->firstItem() + $loop->index }}</td>
                            <td class="border border-gray-700 px-4 py-3">{{ $item->name }}</td>
                            <td class="border border-gray-700 px-4 py-3">
                                @if($item->supplier)
                                    <span class="text-purple-300">{{ $item->supplier->supplier_name }}</span>
                                @else
                                    <span class="text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="border border-gray-700 px-4 py-3 text-sm">{{ $item->account ?? '-' }}</td>
                            <td class="border border-gray-700 px-4 py-3 text-sm">{{ $item->vendor_code ?? '-' }}</td>
                            <td class="border border-gray-700 px-4 py-3 text-center">
                                @if($item->local_or_import)
                                    <span class="px-2 py-1 rounded text-xs {{ $item->local_or_import === 'Local' ? 'bg-blue-600' : 'bg-orange-600' }}">
                                        {{ $item->local_or_import }}
                                    </span>
                                @else
                                    <span class="text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="border border-gray-700 px-4 py-3">{{ $item->created_at->format('M d, Y') }}</td>
                            <td class="border border-gray-700 px-4 py-3 text-center">
                                <form action="{{ route('trade_items.destroy', $item->id) }}" method="POST" class="inline"
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
                            <td colspan="8" class="border border-gray-700 px-4 py-8 text-center text-gray-400">
                                No trade items in the library yet. Import a CSV or Excel file to populate.
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
        <h3 class="text-lg font-bold text-white mb-2">Import Trade Items from CSV</h3>
        <p class="text-gray-400 text-sm mb-4">
            <strong>Format:</strong><br>
            • Column A = Supplier Name<br>
            • Column B = Item Description<br>
            • Column C = Account (e.g., "Accounts Payable - Trade - Local")<br>
            • Column D = Vendor Code<br>
            • Column E = Local or Import<br><br>
            First row must be the header row — it will be skipped automatically.<br>
            Supplier names must match exactly as entered in the system.<br>
            <span class="text-yellow-400">Same item from a different supplier will be added as a separate entry.</span>
        </p>
        <form action="{{ route('trade_items.import') }}" method="POST" enctype="multipart/form-data">
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
        <h3 class="text-lg font-bold text-white mb-4">Add New Trade Item</h3>
        <form action="{{ route('trade_items.store') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-gray-300 text-sm mb-1">Item Description <span class="text-red-400">*</span></label>
                <input type="text" name="name" required maxlength="500"
                    class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                    placeholder="e.g. Electrical Materials">
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
                <label class="block text-gray-300 text-sm mb-1">Account</label>
                <input type="text" name="account" maxlength="100"
                    class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                    placeholder="e.g. Accounts Payable - Trade - Local">
            </div>
            <div class="mb-4">
                <label class="block text-gray-300 text-sm mb-1">Vendor Code</label>
                <input type="text" name="vendor_code" maxlength="100"
                    class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                    placeholder="e.g. VND001">
            </div>
            <div class="mb-4">
                <label class="block text-gray-300 text-sm mb-1">Type</label>
                <select name="local_or_import"
                    class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">— Select Type —</option>
                    <option value="Local">Local</option>
                    <option value="Import">Import</option>
                </select>
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
