<?php

namespace App\Http\Controllers;

use App\Models\DailyFeedUsage;
use App\Models\InHouseBom;
use App\Models\InHouseBomHouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DailyFeedUsageController extends Controller
{
    public function index(Request $request)
    {
        $bomId = $request->input('bom_id');
        $search = $request->input('search', '');

        $boms = InHouseBom::where('approved', true)
            ->orderBy('cycle_date', 'desc')
            ->get(['id', 'cycle_ref', 'grower', 'cycle_date']);

        $query = DailyFeedUsage::with('bom')
            ->orderBy('usage_date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($bomId) {
            $query->where('bom_id', $bomId);
        }

        if ($search) {
            $query->whereHas('bom', function ($q) use ($search) {
                $q->where('cycle_ref', 'LIKE', "%{$search}%")
                  ->orWhere('grower', 'LIKE', "%{$search}%");
            });
        }

        $usages = $query->paginate(30);

        return view('daily_feed_usage.index', compact('usages', 'boms', 'bomId', 'search'));
    }

    public function create(Request $request)
    {
        $bomId = $request->input('bom_id');

        $boms = InHouseBom::where('approved', true)
            ->orderBy('cycle_date', 'desc')
            ->get(['id', 'cycle_ref', 'grower', 'cycle_date']);

        $selectedBom = null;
        $houses = collect();

        if ($bomId) {
            $selectedBom = InHouseBom::with('houses')->find($bomId);
            if ($selectedBom) {
                $houses = $selectedBom->houses;
            }
        }

        return view('daily_feed_usage.create', compact('boms', 'selectedBom', 'houses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'bom_id'       => 'required|exists:inhouse_boms,id',
            'house_number' => 'required|integer|min:1',
            'usage_date'   => 'required|date',
            'materials'    => 'required|string',
        ]);

        $materials = json_decode($request->input('materials'), true);
        if (!$materials || !is_array($materials)) {
            return back()->withErrors(['materials' => 'Invalid materials data.'])->withInput();
        }

        DailyFeedUsage::create([
            'bom_id'         => $request->bom_id,
            'house_number'   => $request->house_number,
            'usage_date'     => $request->usage_date,
            'materials_used' => $materials,
            'notes'          => $request->notes,
            'logged_by'      => Auth::user()->name ?? 'System',
        ]);

        return redirect()->route('daily_feed_usage.index', ['bom_id' => $request->bom_id])
            ->with('success', 'Daily feed usage logged successfully.');
    }

    public function show(DailyFeedUsage $dailyFeedUsage)
    {
        $dailyFeedUsage->load('bom.houses');
        $house = $dailyFeedUsage->bom->houses
            ->where('house_number', $dailyFeedUsage->house_number)->first();

        return view('daily_feed_usage.show', [
            'usage' => $dailyFeedUsage,
            'house' => $house,
        ]);
    }

    public function destroy(DailyFeedUsage $dailyFeedUsage)
    {
        $bomId = $dailyFeedUsage->bom_id;
        $dailyFeedUsage->delete();
        return redirect()->route('daily_feed_usage.index', ['bom_id' => $bomId])
            ->with('success', 'Usage log deleted.');
    }

    /**
     * AJAX: Get houses and their materials for a given BOM
     */
    public function getBomHouses(Request $request)
    {
        $bom = InHouseBom::with('houses')->findOrFail($request->bom_id);

        $houses = $bom->houses->map(function ($house) {
            $mats = collect($house->materials ?? []);
            return [
                'house_number' => $house->house_number,
                'house_name'   => $house->house_name ?: 'House ' . $house->house_number,
                'materials'    => $mats->map(function ($m) {
                    return [
                        'name'     => $m['name'] ?? '',
                        'category' => $m['category'] ?? '',
                        'qty_kg'   => (float)($m['qty_kg'] ?? 0),
                        'qty_bags' => (float)($m['qty_bags'] ?? 0),
                        'uom'      => $m['uom'] ?? '',
                        'cost'     => (float)($m['cost'] ?? 0),
                    ];
                })->values(),
            ];
        });

        return response()->json(['houses' => $houses]);
    }

    /**
     * AJAX: Get cumulative usage for a BOM + house to show remaining
     */
    public function getCumulativeUsage(Request $request)
    {
        $bomId = $request->bom_id;
        $houseNumber = $request->house_number;

        $usages = DailyFeedUsage::where('bom_id', $bomId)
            ->where('house_number', $houseNumber)
            ->get();

        $cumulative = [];
        foreach ($usages as $usage) {
            foreach ($usage->materials_used as $mat) {
                $key = ($mat['name'] ?? '') . '|' . ($mat['category'] ?? '');
                if (!isset($cumulative[$key])) {
                    $cumulative[$key] = [
                        'name'     => $mat['name'] ?? '',
                        'category' => $mat['category'] ?? '',
                        'total_used' => 0,
                    ];
                }
                $cumulative[$key]['total_used'] += (float)($mat['qty_used'] ?? 0);
            }
        }

        return response()->json(['cumulative' => array_values($cumulative)]);
    }
}
