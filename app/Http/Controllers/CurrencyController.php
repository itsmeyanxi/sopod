<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Currency;
use App\Models\CurrencyRateLog;

class CurrencyController extends Controller
{
    public function index()
    {
        $currencies = Currency::orderByRaw("FIELD(code,'PHP','USD','AUD','GBP','EUR')")->get();
        $logs = CurrencyRateLog::with('updater')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->groupBy('currency_code');
        return view('currencies.index', compact('currencies', 'logs'));
    }

    public function update(Request $request, $id)
    {
        // Block updates after 5:00 PM
        $hour = (int) now()->format('H');
        if ($hour >= 17) {
            return back()->with('error', 'Currency rates cannot be updated after 5:00 PM. Please try again tomorrow morning.');
        }

        $request->validate([
            'rate_to_php' => 'required|numeric|min:0.0001',
        ]);

        $currency = Currency::findOrFail($id);

        if ($currency->code === 'PHP') {
            return back()->with('error', 'PHP rate cannot be changed.');
        }

        $oldRate = (float) $currency->rate_to_php;
        $newRate = (float) $request->rate_to_php;

        CurrencyRateLog::create([
            'currency_id'   => $currency->id,
            'currency_code' => $currency->code,
            'old_rate'      => $oldRate,
            'new_rate'      => $newRate,
            'updated_by'    => auth()->id(),
        ]);

        $currency->update([
            'rate_to_php' => $newRate,
            'updated_by'  => auth()->id(),
        ]);

        return back()->with('success', "{$currency->name} ({$currency->code}) rate updated to ₱" . number_format($newRate, 4) . " per {$currency->code}.");
    }

    public function history(Request $request)
    {
        $query = CurrencyRateLog::with('updater')
            ->orderByDesc('created_at');

        if ($request->filled('code')) {
            $query->where('currency_code', $request->code);
        }
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))   $query->whereDate('created_at', '<=', $request->date_to);

        $logs = $query->paginate(30)->withQueryString();
        return view('currencies.history', compact('logs'));
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
