<?php

namespace App\Http\Controllers;

use App\Models\TreasuryBankAccount;
use App\Models\TreasuryBankTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TreasuryBankController extends Controller
{
    public function banks(Request $request, $currency)
    {
        $cur = strtoupper($currency) === 'DOLLAR' ? 'USD' : 'PHP';
        $label = $cur === 'PHP' ? 'Peso Accounts' : 'Dollar Accounts';

        $accounts = TreasuryBankAccount::where('currency', $cur)
            ->active()
            ->orderBy('bank_name')
            ->get();

        // Adjust each account's displayed balance for hidden DR transactions
        foreach ($accounts as $acct) {
            $visibleDeposits  = TreasuryBankTransaction::where('bank_account_id', $acct->id)->sum('debit');
            $visibleWithdrawals = TreasuryBankTransaction::where('bank_account_id', $acct->id)->sum('credit');
            $allDeposits      = TreasuryBankTransaction::withHiddenDr()->where('bank_account_id', $acct->id)->sum('debit');
            $allWithdrawals   = TreasuryBankTransaction::withHiddenDr()->where('bank_account_id', $acct->id)->sum('credit');
            $hiddenDeposits   = $allDeposits - $visibleDeposits;
            $hiddenWithdrawals = $allWithdrawals - $visibleWithdrawals;
            $acct->display_balance = (float)$acct->cash_balance - $hiddenDeposits + $hiddenWithdrawals;
        }

        $totalBalance = $accounts->sum('display_balance');

        return view('treasury.banks', compact('accounts', 'cur', 'label', 'totalBalance'));
    }

    public function show(Request $request, $id)
    {
        $account = TreasuryBankAccount::with('glAccount')->findOrFail($id);

        $query = TreasuryBankTransaction::where('bank_account_id', $id);

        if ($request->filled('date_from')) {
            $query->whereDate('txn_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('txn_date', '<=', $request->date_to);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $transactions = $query->orderBy('txn_date', 'desc')->orderBy('id', 'desc')->paginate(30);

        $stats = [
            'total_deposits'    => TreasuryBankTransaction::where('bank_account_id', $id)->sum('debit'),
            'total_withdrawals' => TreasuryBankTransaction::where('bank_account_id', $id)->sum('credit'),
            'txn_count'         => TreasuryBankTransaction::where('bank_account_id', $id)->count(),
        ];

        // Adjust displayed balance: subtract hidden transaction deposits, add back hidden withdrawals
        $hiddenDeposits    = TreasuryBankTransaction::withHiddenDr()->where('bank_account_id', $id)->sum('debit') - $stats['total_deposits'];
        $hiddenWithdrawals = TreasuryBankTransaction::withHiddenDr()->where('bank_account_id', $id)->sum('credit') - $stats['total_withdrawals'];
        $account->display_balance = (float)$account->cash_balance - $hiddenDeposits + $hiddenWithdrawals;

        return view('treasury.bank_detail', compact('account', 'transactions', 'stats'));
    }

    public function addTransaction(Request $request, $id)
    {
        $account = TreasuryBankAccount::findOrFail($id);

        $validated = $request->validate([
            'txn_date'        => 'required|date',
            'type'            => 'required|in:Deposit,Withdrawal,Transfer,Fee,Interest,Adjustment',
            'reference'       => 'nullable|string|max:100',
            'description'     => 'nullable|string|max:255',
            'payee_or_source' => 'nullable|string|max:255',
            'amount'          => 'required|numeric|min:0.01',
        ]);

        $amount = (float)$validated['amount'];
        $isDebit = in_array($validated['type'], ['Deposit', 'Interest', 'Adjustment']);

        $debit  = $isDebit ? $amount : 0;
        $credit = $isDebit ? 0 : $amount;

        // Update running balance
        $newBalance = $isDebit
            ? (float)$account->cash_balance + $amount
            : (float)$account->cash_balance - $amount;

        TreasuryBankTransaction::create([
            'bank_account_id' => $account->id,
            'txn_date'        => $validated['txn_date'],
            'type'            => $validated['type'],
            'reference'       => $validated['reference'],
            'description'     => $validated['description'],
            'payee_or_source' => $validated['payee_or_source'],
            'debit'           => $debit,
            'credit'          => $credit,
            'running_balance' => $newBalance,
            'logged_by'       => Auth::user()->name,
        ]);

        $account->update([
            'cash_balance'  => $newBalance,
            'balance_as_of' => $validated['txn_date'],
        ]);

        return redirect()->route('treasury.bank.show', $account->id)
            ->with('success', 'Transaction recorded successfully.');
    }

    public function updateBalance(Request $request, $id)
    {
        $account = TreasuryBankAccount::findOrFail($id);

        $validated = $request->validate([
            'cash_balance'  => 'required|numeric|min:0',
            'balance_as_of' => 'required|date',
        ]);

        $account->update($validated);

        return response()->json(['success' => true, 'message' => 'Balance updated.']);
    }
}
