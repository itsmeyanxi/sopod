@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-900 text-white p-8">
    <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-2">
        <h1 class="text-2xl text-white font-bold">Items Library</h1>
        @if(auth()->user()->canManageItems())
            <a href="{{ route('items_library.create') }}"
               class="bg-gradient-to-r from-purple-500 to-indigo-500 hover:from-purple-600 hover:to-indigo-600 text-white px-4 py-2 rounded transition">
                Add New Item
            </a>
        @endif
    </div>

    @if(session('success'))
        <div class="bg-green-600 text-white p-3 rounded mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-600 text-white p-3 rounded mb-4">{{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route('items_library.index') }}" class="flex flex-wrap items-end gap-3 mb-4">
        <div class="flex-1 min-w-[250px]">
            <label class="block text-xs text-gray-400 font-semibold mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Item code, description, brand, category..."
                class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white focus:ring focus:ring-purple-500">
        </div>
        <div>
            <label class="block text-xs text-gray-400 font-semibold mb-1">Type</label>
            <select name="type" class="bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white">
                <option value="">All Types</option>
                <option value="trade" {{ request('type') === 'trade' ? 'selected' : '' }}>Trade</option>
                <option value="non_trade" {{ request('type') === 'non_trade' ? 'selected' : '' }}>Non-Trade</option>
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-400 font-semibold mb-1">Per Page</label>
            <select name="per_page" class="bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white">
                @foreach([25, 50, 100, 250, 500] as $pp)
                    <option value="{{ $pp }}" {{ (int)request('per_page', 25) === $pp ? 'selected' : '' }}>{{ $pp }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded transition font-medium">
            Search
        </button>
        @if(request('search') || request('type') || request('per_page'))
            <a href="{{ route('items_library.index') }}" class="bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded transition">Clear</a>
        @endif
    </form>

    <div class="bg-gray-800 rounded-xl shadow-md overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-700 text-gray-300 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Type</th>
                    <th class="px-4 py-3 text-left">Item Code</th>
                    <th class="px-4 py-3 text-left">Description</th>
                    <th class="px-4 py-3 text-left">Brand</th>
                    <th class="px-4 py-3 text-left">Category</th>
                    <th class="px-4 py-3 text-left">Unit</th>
                    <th class="px-4 py-3 text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr class="border-b border-gray-700 hover:bg-gray-700 transition">
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded text-xs {{ $item->type === 'non_trade' ? 'bg-orange-700 text-white' : 'bg-blue-700 text-white' }}">
                            {{ $item->type === 'non_trade' ? 'Non-Trade' : 'Trade' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 font-mono text-xs text-gray-300">{{ $item->item_code }}</td>
                    <td class="px-4 py-3">{{ Str::limit($item->item_description, 60) }}</td>
                    <td class="px-4 py-3 text-gray-400">{{ $item->brand ?: '—' }}</td>
                    <td class="px-4 py-3 text-gray-400">{{ $item->item_category ?: '—' }}</td>
                    <td class="px-4 py-3 text-gray-400">{{ $item->unit ?: '—' }}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex justify-center gap-2">
                            <button onclick="viewItem({{ json_encode(['code'=>$item->item_code,'desc'=>$item->item_description,'brand'=>$item->brand,'category'=>$item->item_category,'unit'=>$item->unit,'type'=>$item->type]) }})"
                                class="bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded text-xs">View</button>
                            @if($item->source === 'items' && auth()->user()->canManageItems())
                                <a href="{{ route('items_library.edit', $item->id) }}"
                                   class="bg-yellow-600 hover:bg-yellow-700 px-3 py-1 rounded text-xs">Edit</a>
                                <form action="{{ route('items_library.destroy', $item->id) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Delete this item?')">
                                    @csrf @method('DELETE')
                                    <button class="bg-red-600 hover:bg-red-700 px-3 py-1 rounded text-xs">Delete</button>
                                </form>
                            @elseif($item->source === 'non_trade_items')
                                <span class="text-xs text-gray-500 italic">Legacy</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-6 text-center text-gray-400">No items found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($items->hasPages())
    <div class="mt-4 flex items-center justify-between">
        <div class="text-sm text-gray-400">
            Showing {{ $items->firstItem() }}–{{ $items->lastItem() }} of {{ number_format($items->total()) }} items
        </div>
        <div>{{ $items->links() }}</div>
    </div>
    @else
    <div class="mt-4 text-sm text-gray-400">
        Showing {{ number_format($items->total()) }} item{{ $items->total() !== 1 ? 's' : '' }}
    </div>
    @endif
</div>
{{-- View Modal --}}
<div id="viewModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center">
    <div class="bg-gray-800 rounded-xl p-6 w-full max-w-md shadow-xl">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold text-white">Item Details</h2>
            <button onclick="document.getElementById('viewModal').classList.add('hidden')" class="text-gray-400 hover:text-white text-xl">&times;</button>
        </div>
        <table class="w-full text-sm">
            <tbody id="viewModalBody" class="divide-y divide-gray-700">
            </tbody>
        </table>
    </div>
</div>
<script>
function viewItem(item) {
    const typeLabel = item.type === 'non_trade' ? 'Non-Trade' : 'Trade';
    const rows = [
        ['Item Code', item.code],
        ['Description', item.desc],
        ['Brand', item.brand || '—'],
        ['Category', item.category || '—'],
        ['Unit', item.unit || '—'],
        ['Type', typeLabel],
    ];
    document.getElementById('viewModalBody').innerHTML = rows.map(([k,v]) =>
        `<tr><td class="py-2 pr-4 text-gray-400 font-medium w-32">${k}</td><td class="py-2 text-white">${v}</td></tr>`
    ).join('');
    document.getElementById('viewModal').classList.remove('hidden');
}
</script>
@endsection
