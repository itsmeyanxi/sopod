<?php

namespace App\Http\Controllers;

use App\Models\TreasuryBankAccount;
use App\Models\TreasuryBankTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TreasuryBankController extends Controller
{
    public function allAccounts(Request $request)
    {
        $accounts = TreasuryBankAccount::active()->orderBy('bank_name')->get();

        $bankColors = [
            'BDO'=>'#0052a0','UNIONBANK'=>'#f97316','SECURITY BANK'=>'#7c3aed','PBB'=>'#dc2626',
            'AUB'=>'#059669','METROBANK'=>'#7c3aed','PBCOM'=>'#0e7490','BPI'=>'#dc2626',
            'BOC'=>'#b45309','CHINABANK'=>'#be123c','RCBC'=>'#1d4ed8','MAYBANK'=>'#f59e0b',
        ];

        foreach ($accounts as $acct) {
            $visibleDeposits    = TreasuryBankTransaction::where('bank_account_id', $acct->id)->sum('debit');
            $visibleWithdrawals = TreasuryBankTransaction::where('bank_account_id', $acct->id)->sum('credit');
            $allDeposits        = TreasuryBankTransaction::withHiddenDr()->where('bank_account_id', $acct->id)->sum('debit');
            $allWithdrawals     = TreasuryBankTransaction::withHiddenDr()->where('bank_account_id', $acct->id)->sum('credit');
            $hiddenDeposits     = $allDeposits - $visibleDeposits;
            $hiddenWithdrawals  = $allWithdrawals - $visibleWithdrawals;
            $acct->display_balance = (float)$acct->cash_balance - $hiddenDeposits + $hiddenWithdrawals;
            $acct->icon_color = $acct->icon_color ?? ($bankColors[$acct->bank_name] ?? '#6b7280');
        }

        $phpAccounts = $accounts->where('currency', 'PHP');
        $usdAccounts = $accounts->where('currency', 'USD');
        $totalPhp = $phpAccounts->sum('display_balance');
        $totalUsd = $usdAccounts->sum('display_balance');

        return view('treasury.bank_accounts', compact('accounts', 'phpAccounts', 'usdAccounts', 'totalPhp', 'totalUsd'));
    }

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

        return view('treasury.banks_account', compact('accounts', 'cur', 'label', 'totalBalance'));
    }

    public function show(Request $request, $id)
    {
        $account = TreasuryBankAccount::with('glAccount')->findOrFail($id);

        $activeTab = $request->get('tab', 'in');

        $query = TreasuryBankTransaction::where('bank_account_id', $id);

        // Filter by tab: incoming = debit > 0, outgoing = credit > 0
        if ($activeTab === 'out') {
            $query->where('credit', '>', 0);
        } else {
            $query->where('debit', '>', 0);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('txn_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('txn_date', '<=', $request->date_to);
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
            'check_number'    => 'nullable|string|max:100',
            'currency'        => 'nullable|string|max:10',
            'exchange_rate'   => 'nullable|numeric|min:0',
            'description'     => 'nullable|string|max:255',
            'payee_or_source' => 'nullable|string|max:255',
            'dr_account'      => 'nullable|string|max:255',
            'cr_account'      => 'nullable|string|max:255',
            'amount'          => 'required|numeric|min:0.01',
        ]);

        $amount    = (float)$validated['amount'];
        $currency  = $validated['currency'] ?? 'PHP';
        $rate      = (float)($validated['exchange_rate'] ?? 1);
        $amountPhp = $currency === 'PHP' ? $amount : round($amount * $rate, 2);
        $isDebit   = in_array($validated['type'], ['Deposit', 'Interest', 'Adjustment']);

        $debit  = $isDebit ? $amountPhp : 0;
        $credit = $isDebit ? 0 : $amountPhp;

        $newBalance = $isDebit
            ? (float)$account->cash_balance + $amountPhp
            : (float)$account->cash_balance - $amountPhp;

        TreasuryBankTransaction::create([
            'bank_account_id' => $account->id,
            'txn_date'        => $validated['txn_date'],
            'type'            => $validated['type'],
            'reference'       => $validated['reference'] ?? null,
            'check_number'    => $validated['check_number'] ?? null,
            'currency'        => $currency,
            'exchange_rate'   => $rate,
            'amount_php'      => $amountPhp,
            'description'     => $validated['description'] ?? null,
            'payee_or_source' => $validated['payee_or_source'] ?? null,
            'dr_account'      => $validated['dr_account'] ?? null,
            'cr_account'      => $validated['cr_account'] ?? null,
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
            ->with('success', ucfirst(strtolower($validated['type'])) . ' transaction recorded successfully.');
    }

        public function deleteTransaction(Request $request, $txnId)
    {
        $txn     = TreasuryBankTransaction::withHiddenDr()->findOrFail($txnId);
        $account = TreasuryBankAccount::findOrFail($txn->bank_account_id);

        DB::transaction(function () use ($txn, $account) {
            // 1. Reverse cash balance
            $reversal = (float)$txn->debit - (float)$txn->credit;
            $account->update(['cash_balance' => (float)$account->cash_balance - $reversal]);

            // 2. If this txn was a Deposit linked to a confirmed payment, reverse it
            if ($txn->type === 'Deposit' && $txn->reference) {
                $payment = \App\Models\Payment::where('collection_receipt_number', $txn->reference)
                    ->where('confirmed', true)
                    ->first();

                if ($payment) {
                    // Reverse ar_aging — restore outstanding status
                    if ($payment->dr_no) {
                        \Illuminate\Support\Facades\DB::table('ar_aging')
                            ->whereRaw('TRIM(dr_no) = ?', [trim($payment->dr_no)])
                            ->whereRaw('TRIM(customer_code) = ?', [trim($payment->customer_code)])
                            ->update([
                                'status'         => 'Outstanding',
                                'net_ar_balance' => \Illuminate\Support\Facades\DB::raw(
                                    'COALESCE(invoice_amount, 0) - COALESCE(settled_invoice_amount, 0)'
                                ),
                            ]);
                    }

                    // Reverse the payment record itself
                    $payment->confirmed       = false;
                    $payment->confirmed_by    = null;
                    $payment->confirmed_at    = null;
                    $payment->clearing_date   = null;
                    $payment->status          = 'Clearing';
                    $payment->bank_account_id = null;
                    $payment->save();
                }
            }

            // 3. Delete the transaction
            $txn->delete();
        });

        return response()->json(['success' => true, 'message' => 'Transaction deleted and payment reversal applied.']);
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

    public function store(Request $request)
    {
        $data = $request->validate([
            'bank_name'      => 'required|string|max:100',
            'short_name'     => 'nullable|string|max:100',
            'account_number' => 'required|string|max:100|unique:treasury_bank_accounts,account_number',
            'account_type'   => 'nullable|in:SA,CA',
            'currency' => 'required|string|max:10',
            'cash_balance'   => 'nullable|numeric|min:0',
            'balance_as_of'  => 'nullable|date',
            'icon_color'     => 'nullable|string|max:7',
        ]);

        TreasuryBankAccount::create(array_merge($data, [
            'cash_balance' => $data['cash_balance'] ?? 0,
            'is_active'    => true,
            'created_by'   => Auth::user()->name,
        ]));

        // Change the redirect at the bottom:
        $cur = strtoupper($data['currency'] ?? 'PHP') === 'USD' ? 'dollar' : 'peso';
        return redirect()->route('treasury.banks', $cur)
                         ->with('success', 'Bank account added successfully.');
    }

    public function update(Request $request, $id)
    {
        $account = TreasuryBankAccount::findOrFail($id);

        $data = $request->validate([
            'bank_name'      => 'required|string|max:100',
            'short_name'     => 'nullable|string|max:100',
            'account_number' => 'required|string|max:100|unique:treasury_bank_accounts,account_number,' . $account->id,
            'account_type'   => 'nullable|in:SA,CA',
             'currency' => 'required|string|max:10',
            'cash_balance'   => 'nullable|numeric|min:0',
            'balance_as_of'  => 'nullable|date',
            'icon_color'     => 'nullable|string|max:7',
        ]);

        $account->update($data);
        $cur = ($data['currency'] ?? 'PHP') === 'USD' ? 'dollar' : 'peso';
        return redirect()->route('treasury.banks', $cur)
                         ->with('success', 'Bank account updated successfully.');
    }

    public function destroy($id)
    {
        if (!Auth::user()->isAdminUser()) {
            abort(403, 'Unauthorized: Only IT/Admin can delete bank accounts.');
        }

        $account = TreasuryBankAccount::findOrFail($id);
        $cur = $account->currency === 'USD' ? 'dollar' : 'peso';
        $account->delete();

        return redirect()->route('treasury.banks', $cur)
                         ->with('success', 'Bank account deleted.');
    }
}
