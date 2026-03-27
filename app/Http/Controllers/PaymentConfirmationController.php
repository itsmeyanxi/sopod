<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\TreasuryBankAccount;
use App\Models\TreasuryBankTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaymentConfirmationController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::where('status', 'Posted')
            ->where('confirmed', false);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('customer_name', 'like', "%{$s}%")
                  ->orWhere('collection_receipt_number', 'like', "%{$s}%")
                  ->orWhere('invoice_no', 'like', "%{$s}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(25);

        $stats = [
            'pending_count' => Payment::where('status', 'Posted')->where('confirmed', false)->count(),
            'pending_total' => Payment::where('status', 'Posted')->where('confirmed', false)->sum('gross_amount'),
            'confirmed_today' => Payment::where('confirmed', true)
                ->whereDate('confirmed_at', Carbon::today())->count(),
        ];

        $bankAccounts = TreasuryBankAccount::active()->orderBy('bank_name')->get();

        return view('treasury.payment_confirmation', compact('payments', 'stats', 'bankAccounts'));
    }

    public function confirm(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        if ($payment->confirmed) {
            return response()->json(['success' => false, 'message' => 'Already confirmed.'], 422);
        }

        DB::transaction(function () use ($payment) {
            $payment->confirmed = true;
            $payment->confirmed_by = Auth::user()->name;
            $payment->confirmed_at = now();
            $payment->status = 'Confirmed';

            // Auto-detect bank account from payment_means_data, or use manual selection
            $meansData = json_decode($payment->payment_means_data ?? '{}', true);
            $glAccountId = $meansData['gl_account_id'] ?? null;
            $manualBankId = request('manual_bank_account_id');

            $bankAccount = null;
            if ($glAccountId) {
                $bankAccount = TreasuryBankAccount::where('gl_account_id', $glAccountId)->active()->first();
            } elseif ($manualBankId) {
                $bankAccount = TreasuryBankAccount::where('id', $manualBankId)->active()->first();
            }

            if ($bankAccount) {
                $payment->bank_account_id = $bankAccount->id;

                $depositAmount = (float)($payment->gross_amount ?? $payment->amount);
                $newBalance = (float)$bankAccount->cash_balance + $depositAmount;

                TreasuryBankTransaction::create([
                    'bank_account_id' => $bankAccount->id,
                    'txn_date'        => now()->toDateString(),
                    'type'            => 'Deposit',
                    'reference'       => $payment->collection_receipt_number,
                    'description'     => 'Payment confirmed — ' . ($payment->customer_name ?? 'N/A'),
                    'payee_or_source' => $payment->customer_name,
                    'debit'           => $depositAmount,
                    'credit'          => 0,
                    'running_balance' => $newBalance,
                    'logged_by'       => Auth::user()->name,
                ]);

                $bankAccount->update([
                    'cash_balance'  => $newBalance,
                    'balance_as_of' => now()->toDateString(),
                ]);
            }

            $payment->save();
        });

        return response()->json(['success' => true, 'message' => 'Payment confirmed successfully.']);
    }

    public function bulkConfirm(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No payments selected.'], 422);
        }

        $count = 0;

        DB::transaction(function () use ($ids, &$count) {
            $payments = Payment::whereIn('id', $ids)->where('confirmed', false)->get();

            foreach ($payments as $payment) {
                $payment->confirmed = true;
                $payment->confirmed_by = Auth::user()->name;
                $payment->confirmed_at = now();
                $payment->status = 'Confirmed';

                $meansData = json_decode($payment->payment_means_data ?? '{}', true);
                $glAccountId = $meansData['gl_account_id'] ?? null;

                if ($glAccountId) {
                    $bankAccount = TreasuryBankAccount::where('gl_account_id', $glAccountId)->active()->first();
                    if ($bankAccount) {
                        $payment->bank_account_id = $bankAccount->id;

                        $depositAmount = (float)($payment->gross_amount ?? $payment->amount);
                        $newBalance = (float)$bankAccount->cash_balance + $depositAmount;

                        TreasuryBankTransaction::create([
                            'bank_account_id' => $bankAccount->id,
                            'txn_date'        => now()->toDateString(),
                            'type'            => 'Deposit',
                            'reference'       => $payment->collection_receipt_number,
                            'description'     => 'Payment confirmed — ' . ($payment->customer_name ?? 'N/A'),
                            'payee_or_source' => $payment->customer_name,
                            'debit'           => $depositAmount,
                            'credit'          => 0,
                            'running_balance' => $newBalance,
                            'logged_by'       => Auth::user()->name,
                        ]);

                        $bankAccount->update([
                            'cash_balance'  => $newBalance,
                            'balance_as_of' => now()->toDateString(),
                        ]);
                    }
                }

                $payment->save();
                $count++;
            }
        });

        return response()->json(['success' => true, 'message' => "{$count} payment(s) confirmed."]);
    }

    public function unconfirm(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        if (!$payment->confirmed) {
            return response()->json(['success' => false, 'message' => 'Not confirmed yet.'], 422);
        }

        DB::transaction(function () use ($payment) {
            // Reverse bank transaction if linked
            if ($payment->bank_account_id) {
                $bankAccount = TreasuryBankAccount::find($payment->bank_account_id);
                if ($bankAccount) {
                    $depositAmount = (float)($payment->gross_amount ?? $payment->amount);
                    $newBalance = (float)$bankAccount->cash_balance - $depositAmount;

                    // Remove the deposit transaction
                    TreasuryBankTransaction::where('bank_account_id', $bankAccount->id)
                        ->where('reference', $payment->collection_receipt_number)
                        ->where('type', 'Deposit')
                        ->latest()
                        ->first()
                        ?->delete();

                    $bankAccount->update([
                        'cash_balance'  => $newBalance,
                        'balance_as_of' => now()->toDateString(),
                    ]);
                }
            }

            $payment->confirmed = false;
            $payment->confirmed_by = null;
            $payment->confirmed_at = null;
            $payment->status = 'Posted';
            $payment->bank_account_id = null;
            $payment->save();
        });

        return response()->json(['success' => true, 'message' => 'Confirmation revoked.']);
    }
}
