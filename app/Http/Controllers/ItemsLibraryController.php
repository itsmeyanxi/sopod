<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemsLibraryController extends Controller
{
    public function index(Request $request)
    {
        $search  = $request->search;
        $typeFilter = $request->type;
        $perPage = in_array((int)$request->per_page, [25, 50, 100, 250, 500]) ? (int)$request->per_page : 25;

        // Pull from items table
        $itemsQuery = DB::table('items')
            ->select(
                'id',
                DB::raw("'items' as source"),
                'item_code',
                'item_description',
                'item_category',
                'brand',
                'unit',
                'type',
                'approval_status',
                'is_enabled',
                'created_at'
            );

        // Also pull non_trade_items not already in items table
        $nonTradeQuery = DB::table('non_trade_items')
            ->whereNotIn('item_code', DB::table('items')->pluck('item_code'))
            ->select(
                'id',
                DB::raw("'non_trade_items' as source"),
                'item_code',
                DB::raw("name as item_description"),
                DB::raw("'' as item_category"),
                'brand',
                'unit',
                DB::raw("'non_trade' as type"),
                DB::raw("'approved' as approval_status"),
                DB::raw("1 as is_enabled"),
                'created_at'
            );

        if ($search) {
            $itemsQuery->where(function ($q) use ($search) {
                $q->where('item_code', 'LIKE', "%{$search}%")
                  ->orWhere('item_description', 'LIKE', "%{$search}%")
                  ->orWhere('brand', 'LIKE', "%{$search}%")
                  ->orWhere('item_category', 'LIKE', "%{$search}%");
            });
            $nonTradeQuery->where(function ($q) use ($search) {
                $q->where('item_code', 'LIKE', "%{$search}%")
                  ->orWhere('name', 'LIKE', "%{$search}%")
                  ->orWhere('brand', 'LIKE', "%{$search}%");
            });
        }

        if ($typeFilter) {
            if ($typeFilter === 'non_trade') {
                $nonTradeQuery; // keep non_trade_items
                $itemsQuery->where('type', 'non_trade');
            } elseif ($typeFilter === 'trade') {
                $itemsQuery->where('type', 'trade');
                $nonTradeQuery->whereRaw('1=0'); // exclude non_trade_items
            }
        }

        $items = $itemsQuery->union($nonTradeQuery)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return view('items_library.index', compact('items'));
    }

    public function create()
    {
        return view('items_library.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'item_code'        => 'required|string|max:255|unique:items,item_code',
            'item_description' => 'nullable|string',
            'item_category'    => 'nullable|string|max:255',
            'brand'            => 'nullable|string|max:255',
            'unit'             => 'nullable|string|max:50',
            'type'             => 'required|in:trade,non_trade',
        ]);

        $data['approval_status'] = 'approved';
        $data['is_enabled'] = 1;

        Item::create($data);

        return redirect()->route('items_library.index')->with('success', 'Item added to library.');
    }

    public function edit($id)
    {
        $item = Item::findOrFail($id);
        return view('items_library.edit', compact('item'));
    }

    public function update(Request $request, $id)
    {
        $item = Item::findOrFail($id);

        $data = $request->validate([
            'item_code'        => 'required|string|max:255|unique:items,item_code,' . $id,
            'item_description' => 'nullable|string',
            'item_category'    => 'nullable|string|max:255',
            'brand'            => 'nullable|string|max:255',
            'unit'             => 'nullable|string|max:50',
            'type'             => 'required|in:trade,non_trade',
        ]);

        $item->update($data);

        return redirect()->route('items_library.index')->with('success', 'Item updated.');
    }

    public function destroy($id)
    {
        Item::findOrFail($id)->delete();
        return redirect()->route('items_library.index')->with('success', 'Item deleted.');
    }
}
