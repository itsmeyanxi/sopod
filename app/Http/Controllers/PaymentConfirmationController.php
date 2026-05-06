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
        $query = Payment::where('status', 'Clearing')
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
            'pending_count' => Payment::where('status', 'Clearing')->where('confirmed', false)->count(),
            'pending_total' => Payment::where('status', 'Clearing')->where('confirmed', false)->sum('gross_amount'),
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

        $clearingDate = $request->input('clearing_date');
        if (!$clearingDate) {
            return response()->json(['success' => false, 'message' => 'Clearing date is required.'], 422);
        }

        DB::transaction(function () use ($payment, $clearingDate) {
            $payment->confirmed    = true;
            $payment->confirmed_by = Auth::user()->name;
            $payment->confirmed_at = now();
            $payment->clearing_date = $clearingDate;
            $payment->status       = 'Posted';

            $meansData   = json_decode($payment->payment_means_data ?? '{}', true);
            $glAccountId = $meansData['gl_account_id'] ?? null;
            $manualBankId = request('manual_bank_account_id');

            $bankAccount = null;
            if ($glAccountId) {
                $bankAccount = TreasuryBankAccount::where('gl_account_id', $glAccountId)->active()->first();
            } elseif ($manualBankId) {
                $bankAccount = TreasuryBankAccount::where('id', $manualBankId)->active()->first();
            }

            if (!$bankAccount) {
                throw new \Exception('No bank account linked. Please select a bank account.');
            }

            $payment->bank_account_id = $bankAccount->id;
            $depositAmount = (float)($payment->gross_amount ?? $payment->amount);
            $newBalance    = (float)$bankAccount->cash_balance + $depositAmount;
            $checkNumber   = $meansData['check_number'] ?? ($meansData['check_no'] ?? null);

            TreasuryBankTransaction::create([
                'bank_account_id' => $bankAccount->id,
                'txn_date'        => $clearingDate,
                'type'            => 'Deposit',
                'reference'       => $payment->collection_receipt_number,
                'check_number'    => $checkNumber,
                'currency'        => 'PHP',
                'exchange_rate'   => 1,
                'amount_php'      => $depositAmount,
                'description'     => 'Payment confirmed — ' . ($payment->customer_name ?? 'N/A'),
                'payee_or_source' => $payment->customer_name,
                'debit'           => $depositAmount,
                'credit'          => 0,
                'running_balance' => $newBalance,
                'logged_by'       => Auth::user()->name,
            ]);

            $bankAccount->update([
                'cash_balance'  => $newBalance,
                'balance_as_of' => $clearingDate,
            ]);

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
                $payment->status = 'Posted';

                $meansData = json_decode($payment->payment_means_data ?? '{}', true);
                $glAccountId = $meansData['gl_account_id'] ?? null;

                if ($glAccountId) {
                    $bankAccount = TreasuryBankAccount::where('gl_account_id', $glAccountId)->active()->first();
                    if ($bankAccount) {
                        $payment->bank_account_id = $bankAccount->id;

                        $depositAmount = (float)($payment->gross_amount ?? $payment->amount);
                        $newBalance = (float)$bankAccount->cash_balance + $depositAmount;
                        $checkNumber = $meansData['check_number'] ?? ($meansData['check_no'] ?? null);

                        TreasuryBankTransaction::create([
                            'bank_account_id' => $bankAccount->id,
                            'txn_date'        => now()->toDateString(),
                            'type'            => 'Deposit',
                            'reference'       => $payment->collection_receipt_number,
                            'check_number'    => $checkNumber,
                            'currency'        => 'PHP',
                            'exchange_rate'   => 1,
                            'amount_php'      => $depositAmount,
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
            if ($payment->bank_account_id) {
                $bankAccount = TreasuryBankAccount::find($payment->bank_account_id);
                if ($bankAccount) {
                    $depositAmount = (float)($payment->gross_amount ?? $payment->amount);
                    TreasuryBankTransaction::where('bank_account_id', $bankAccount->id)
                        ->where('reference', $payment->collection_receipt_number)
                        ->where('type', 'Deposit')
                        ->latest()->first()?->delete();
                    $bankAccount->update([
                        'cash_balance'  => (float)$bankAccount->cash_balance - $depositAmount,
                        'balance_as_of' => now()->toDateString(),
                    ]);
                }
            }

            // ✅ Same reversal logic
            if ($payment->dr_no) {
                $drNo = trim($payment->dr_no);
                $customerCode = trim($payment->customer_code ?? '');

                $arRecord = DB::table('ar_aging')
                    ->whereRaw('TRIM(dr_no) = ?', [$drNo])
                    ->when($customerCode, fn($q) => $q->whereRaw('TRIM(customer_code) = ?', [$customerCode]))
                    ->first();

                if ($arRecord) {
                    $paymentAmount = (float)($payment->amount ?? 0)
                                + (float)($payment->discount_amount ?? 0)
                                + (float)($payment->ewt ?? 0);

                    $newSettled = max(0, (float)($arRecord->settled_invoice_amount ?? 0) - $paymentAmount);
                    $newNetAR   = max(0, (float)($arRecord->invoice_amount ?? 0) - $newSettled);

                    DB::table('ar_aging')
                        ->where('id', $arRecord->id)
                        ->update([
                            'status'                 => 'Outstanding',
                            'settled_invoice_amount' => $newSettled,
                            'net_ar_balance'         => $newNetAR,
                        ]);
                }
            }

            $payment->confirmed       = false;
            $payment->confirmed_by    = null;
            $payment->confirmed_at    = null;
            $payment->status          = 'Clearing';
            $payment->bank_account_id = null;
            $payment->save();
        });
        return response()->json(['success' => true, 'message' => 'Confirmation revoked.']);
    }

   public function bounce(Request $request, $id)
{
    $payment = Payment::findOrFail($id);

    if ($payment->status === 'Bounced') {
        return response()->json(['success' => false, 'message' => 'Already bounced.'], 422);
    }

    $reason = trim($request->input('bounce_reason', ''));
    if (!$reason) {
        return response()->json(['success' => false, 'message' => 'Bounce reason is required.'], 422);
    }

    DB::transaction(function () use ($payment, $reason) {
        // Reverse bank transaction if already confirmed
        if ($payment->bank_account_id) {
            $bankAccount = TreasuryBankAccount::find($payment->bank_account_id);
            if ($bankAccount) {
                $depositAmount = (float)($payment->gross_amount ?? $payment->amount);
                TreasuryBankTransaction::where('bank_account_id', $bankAccount->id)
                    ->where('reference', $payment->collection_receipt_number)
                    ->where('type', 'Deposit')
                    ->latest()->first()?->delete();
                $bankAccount->update([
                    'cash_balance'  => (float)$bankAccount->cash_balance - $depositAmount,
                    'balance_as_of' => now()->toDateString(),
                ]);
            }
        }

        // ✅ FIXED: Fully reverse ar_aging — restore net_ar_balance AND settled_invoice_amount
        if ($payment->dr_no) {
            $drNo = trim($payment->dr_no);
            $customerCode = trim($payment->customer_code ?? '');

            $arRecord = DB::table('ar_aging')
                ->whereRaw('TRIM(dr_no) = ?', [$drNo])
                ->when($customerCode, fn($q) => $q->whereRaw('TRIM(customer_code) = ?', [$customerCode]))
                ->first();

            if ($arRecord) {
                $paymentAmount = (float)($payment->amount ?? 0)
                               + (float)($payment->discount_amount ?? 0)
                               + (float)($payment->ewt ?? 0);

                // Reverse settled amount — add back what this payment had settled
                $newSettled = max(0, (float)($arRecord->settled_invoice_amount ?? 0) - $paymentAmount);
                $invoiceAmount = (float)($arRecord->invoice_amount ?? 0);
                $newNetAR = max(0, $invoiceAmount - $newSettled);

                DB::table('ar_aging')
                    ->where('id', $arRecord->id)
                    ->update([
                        'status'                  => 'Outstanding',
                        'settled_invoice_amount'  => $newSettled,
                        'net_ar_balance'          => $newNetAR,
                    ]);
            }
        }

        $payment->status        = 'Bounced';
        $payment->bounce_reason = $reason;
        $payment->bounced_by    = Auth::user()->name;
        $payment->bounced_at    = now();
        $payment->confirmed     = false;
        $payment->bank_account_id = null;
        $payment->save();
    });

    return response()->json(['success' => true, 'message' => 'Payment marked as bounced.']);
}

    public function bouncedHistory(Request $request)
    {
        $query = Payment::withoutGlobalScope('not_hidden_dr')
            ->where('status', 'Bounced');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('customer_name', 'like', "%{$s}%")
                  ->orWhere('collection_receipt_number', 'like', "%{$s}%")
                  ->orWhere('dr_no', 'like', "%{$s}%");
            });
        }

        $payments = $query->orderBy('bounced_at', 'desc')->paginate(25);
        return view('treasury.bounced_history', compact('payments'));
    }
}
