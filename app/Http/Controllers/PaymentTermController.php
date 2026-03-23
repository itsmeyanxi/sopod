<?php

namespace App\Http\Controllers;

use App\Models\PaymentTerm;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentTermController extends Controller
{
    public function index()
    {
        $terms = PaymentTerm::orderBy('code')->get();
        return view('payment_terms.index', compact('terms'));
    }

    public function create()
    {
        return view('payment_terms.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:30|unique:payment_terms,code',
            'description' => 'required|string|max:100',
            'net_days' => 'required|integer|min:1',
            'discount_pct' => 'nullable|numeric|min:0|max:100',
            'discount_days' => 'nullable|integer|min:0',
        ]);

        $validated['discount_pct'] = $validated['discount_pct'] ?? 0;
        $validated['discount_days'] = $validated['discount_days'] ?? 0;

        $term = PaymentTerm::create($validated);

        Activity::create([
            'user_name' => Auth::user()->name,
            'action' => 'Created',
            'item' => $term->code,
            'target' => $term->description,
            'message' => 'Created payment term ' . $term->code,
        ]);

        return redirect()->route('payment_terms.index')
            ->with('success', 'Payment term created successfully.');
    }

    public function edit(PaymentTerm $payment_term)
    {
        return view('payment_terms.edit', compact('payment_term'));
    }

    public function update(Request $request, PaymentTerm $payment_term)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:30|unique:payment_terms,code,' . $payment_term->id,
            'description' => 'required|string|max:100',
            'net_days' => 'required|integer|min:1',
            'discount_pct' => 'nullable|numeric|min:0|max:100',
            'discount_days' => 'nullable|integer|min:0',
        ]);

        $validated['discount_pct'] = $validated['discount_pct'] ?? 0;
        $validated['discount_days'] = $validated['discount_days'] ?? 0;

        $payment_term->update($validated);

        Activity::create([
            'user_name' => Auth::user()->name,
            'action' => 'Updated',
            'item' => $payment_term->code,
            'target' => $payment_term->description,
            'message' => 'Updated payment term ' . $payment_term->code,
        ]);

        return redirect()->route('payment_terms.index')
            ->with('success', 'Payment term updated successfully.');
    }

    public function destroy(PaymentTerm $payment_term)
    {
        $code = $payment_term->code;
        $payment_term->delete();

        Activity::create([
            'user_name' => Auth::user()->name,
            'action' => 'Deleted',
            'item' => $code,
            'target' => '',
            'message' => 'Deleted payment term ' . $code,
        ]);

        return redirect()->route('payment_terms.index')
            ->with('success', 'Payment term deleted successfully.');
    }
}
