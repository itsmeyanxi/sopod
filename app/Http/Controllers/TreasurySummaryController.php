<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\TreasuryBankAccount;
use App\Models\TreasuryBankTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TreasurySummaryController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::where('confirmed', true);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('customer_name', 'like', "%{$s}%")
                  ->orWhere('collection_receipt_number', 'like', "%{$s}%")
                  ->orWhere('invoice_no', 'like', "%{$s}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('confirmed_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('confirmed_at', '<=', $request->date_to);
        }

        $payments = $query->orderBy('confirmed_at', 'desc')->paginate(25);

        // Overall stats for confirmed payments
        $totalConfirmedAmount = Payment::where('confirmed', true)->sum('amount');
        $totalConfirmedNet    = Payment::where('confirmed', true)->sum(DB::raw('COALESCE(net, amount)'));
        $totalConfirmedCount  = Payment::where('confirmed', true)->count();

        // Overpayment / credit balance totals
        $totalOverpayment = Payment::where('confirmed', true)->sum('overpayment');

        // Used credits: sum of credit_applied on confirmed payments
        $totalCreditApplied = Payment::where('confirmed', true)->sum('credit_applied');

        // Remaining credit balance = total overpayments - total credits applied across all payments
        $totalOverpaymentAll = Payment::where('overpayment', '>', 0)->sum('overpayment');
        $totalCreditUsed     = Payment::where('credit_applied', '>', 0)->sum('credit_applied');
        $remainingCredit     = $totalOverpaymentAll - $totalCreditUsed;

        // Today's confirmed
        $todayAmount = Payment::where('confirmed', true)
            ->whereDate('confirmed_at', Carbon::today())->sum('amount');
        $todayCount  = Payment::where('confirmed', true)
            ->whereDate('confirmed_at', Carbon::today())->count();

        $stats = [
            'total_amount'      => $totalConfirmedAmount,
            'total_net'         => $totalConfirmedNet,
            'total_count'       => $totalConfirmedCount,
            'total_overpayment' => $totalOverpayment,
            'remaining_credit'  => max(0, $remainingCredit),
            'today_amount'      => $todayAmount,
            'today_count'       => $todayCount,
        ];

        // Bank account totals — adjusted for hidden DR transactions
        $pesoAccounts  = TreasuryBankAccount::peso()->active()->get();
        $dollarAccounts = TreasuryBankAccount::dollar()->active()->get();

        $pesoTotal = $pesoAccounts->sum(function ($acct) {
            $hiddenDeposits = TreasuryBankTransaction::withHiddenDr()->where('bank_account_id', $acct->id)->sum('debit')
                            - TreasuryBankTransaction::where('bank_account_id', $acct->id)->sum('debit');
            $hiddenWithdrawals = TreasuryBankTransaction::withHiddenDr()->where('bank_account_id', $acct->id)->sum('credit')
                               - TreasuryBankTransaction::where('bank_account_id', $acct->id)->sum('credit');
            return (float)$acct->cash_balance - $hiddenDeposits + $hiddenWithdrawals;
        });
        $dollarTotal = $dollarAccounts->sum(function ($acct) {
            $hiddenDeposits = TreasuryBankTransaction::withHiddenDr()->where('bank_account_id', $acct->id)->sum('debit')
                            - TreasuryBankTransaction::where('bank_account_id', $acct->id)->sum('debit');
            $hiddenWithdrawals = TreasuryBankTransaction::withHiddenDr()->where('bank_account_id', $acct->id)->sum('credit')
                               - TreasuryBankTransaction::where('bank_account_id', $acct->id)->sum('credit');
            return (float)$acct->cash_balance - $hiddenDeposits + $hiddenWithdrawals;
        });
        $pesoCount   = $pesoAccounts->count();
        $dollarCount = $dollarAccounts->count();

        $bankStats = compact('pesoTotal', 'dollarTotal', 'pesoCount', 'dollarCount');

        return view('treasury.summary', compact('payments', 'stats', 'bankStats'));
    }

    public function storeBank(Request $request)
{
    $request->validate([
        'bank_name'      => 'required|string|max:100',
        'account_number' => 'required|string|max:100',
        'account_type'   => 'required|in:CA,SA',
        'currency'       => 'required|in:PHP,USD',
        'cash_balance'   => 'nullable|numeric|min:0',
        'balance_as_of'  => 'nullable|date',
        'gl_account_id'  => 'nullable|integer',
        'short_name'     => 'nullable|string|max:100',
    ]);

    TreasuryBankAccount::create([
        'bank_name'      => $request->bank_name,
        'account_number' => $request->account_number,
        'account_type'   => $request->account_type,
        'currency'       => $request->currency,
        'cash_balance'   => $request->cash_balance ?? 0,
        'balance_as_of'  => $request->balance_as_of,
        'gl_account_id'  => $request->gl_account_id ?: null,
        'short_name'     => $request->short_name,
        'is_active'      => true,
        'created_by'     => auth()->id(),  // or auth()->user()->name if you store names
    ]);

    return redirect()->back()->with('success', 'Bank account "' . $request->bank_name . '" added successfully.');
}
}
