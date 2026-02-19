<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Currency;

class CurrencyController extends Controller
{
    public function index()
    {
        $currencies = Currency::orderByRaw("FIELD(code,'PHP','USD','AUD','GBP','EUR')")->get();
        return view('currencies.index', compact('currencies'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'rate_to_php' => 'required|numeric|min:0.0001',
        ]);

        $currency = Currency::findOrFail($id);

        // PHP rate is always 1 and cannot be changed
        if ($currency->code === 'PHP') {
            return back()->with('error', 'PHP rate cannot be changed.');
        }

        $currency->update([
            'rate_to_php' => $request->rate_to_php,
            'updated_by'  => auth()->id(),
        ]);

        return back()->with('success', "{$currency->name} ({$currency->code}) rate updated to ₱" . number_format($request->rate_to_php, 4) . " per {$currency->code}.");
    }

    public function rate($code)
    {
        $currency = Currency::where('code', strtoupper($code))->first();
        if (!$currency) {
            return response()->json(['error' => 'Currency not found'], 404);
        }
        return response()->json([
            'code'        => $currency->code,
            'name'        => $currency->name,
            'symbol'      => $currency->symbol,
            'rate_to_php' => (float) $currency->rate_to_php,
        ]);
    }
}
