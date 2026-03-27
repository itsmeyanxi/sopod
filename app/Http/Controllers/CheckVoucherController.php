<?php

namespace App\Http\Controllers;

use App\Models\CheckVoucher;
use App\Models\AccountsPayableInvoice;
use App\Models\Activity;
use App\Models\TreasuryBankAccount;
use App\Models\TreasuryBankTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CheckVoucherController extends Controller
{
    /**
     * Display a listing of check vouchers
     */
    public function index()
    {
        $vouchers = CheckVoucher::with(['creator', 'accountsPayableInvoice'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('check_vouchers.index', compact('vouchers'));
    }

    /**
     * Show the form for creating a new check voucher
     */
    public function create(Request $request)
    {
        // Generate CV number
        $cvNo = 'CV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // Check if an APV was selected
        $selectedAPV = null;
        if ($request->has('apv_id')) {
            $selectedAPV = AccountsPayableInvoice::with('requestForPayment')->find($request->apv_id);
        }

        return view('check_vouchers.create', compact('cvNo', 'selectedAPV'));
    }

    /**
     * Search approved APV invoices (AJAX)
     */
    public function searchAPVs(Request $request)
    {
        $searchTerm = $request->input('search', '');

        $apvs = AccountsPayableInvoice::where('status', 'approved')
            ->where(function ($query) use ($searchTerm) {
                $query->where('apv_no', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('vendor_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('reference_no', 'LIKE', "%{$searchTerm}%");
            })
            ->select('id', 'apv_no', 'vendor_name', 'vendor_code', 'vendor_address', 'vendor_tin',
                     'apv_date', 'currency', 'grand_total', 'reference_no', 'particulars')
            ->limit(10)
            ->get();

        return response()->json($apvs);
    }

    /**
     * Store a newly created check voucher
     */
    public function store(Request $request)
    {
        $request->validate([
            'cv_date' => 'required|date',
            'check_date' => 'required|date',
            'supplier_name' => 'required|string',
            'check_amount' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'particulars' => 'required|string',
        ]);

        // Validate amounts do not exceed APV grand total
        if ($request->accounts_payable_invoice_id) {
            $apv = AccountsPayableInvoice::find($request->accounts_payable_invoice_id);
            if ($apv) {
                $maxAmount = (float) $apv->grand_total;
                if ((float) $request->check_amount > $maxAmount) {
                    return back()->withInput()->with('error', 'Check amount (₱' . number_format($request->check_amount, 2) . ') exceeds APV grand total (₱' . number_format($maxAmount, 2) . ').');
                }
                if ((float) $request->paid_amount > $maxAmount) {
                    return back()->withInput()->with('error', 'Paid amount (₱' . number_format($request->paid_amount, 2) . ') exceeds APV grand total (₱' . number_format($maxAmount, 2) . ').');
                }
            }
        }

        DB::beginTransaction();
        try {
            // Generate CV number
            $cvNo = 'CV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // Process journal entries
            $journalEntries = [];
            if ($request->has('journal_entries')) {
                foreach ($request->journal_entries as $entry) {
                    if (!empty($entry['account_code']) || !empty($entry['account_name'])) {
                        $journalEntries[] = [
                            'account_code' => $entry['account_code'] ?? '',
                            'account_name' => $entry['account_name'] ?? '',
                            'debit' => $entry['debit'] ?? 0,
                            'credit' => $entry['credit'] ?? 0,
                        ];
                    }
                }
            }

            // Create check voucher
            $voucher = CheckVoucher::create([
                'cv_no' => $cvNo,
                'accounts_payable_invoice_id' => $request->accounts_payable_invoice_id,
                'cv_date' => $request->cv_date,
                'check_date' => $request->check_date,
                'supplier_code' => $request->supplier_code,
                'supplier_name' => $request->supplier_name,
                'supplier_address' => $request->supplier_address,
                'supplier_tin' => $request->supplier_tin,
                'check_no' => $request->check_no ?? '0',
                'bank' => $request->bank,
                'gl_account_id' => $request->gl_account_id,
                'branch' => $request->branch,
                'check_amount' => $request->check_amount,
                'payment_date' => $request->payment_date,
                'payment_type' => $request->payment_type,
                'reference_no' => $request->reference_no,
                'apv_no' => $request->apv_no,
                'paid_amount' => $request->paid_amount,
                'particulars' => $request->particulars,
                'journal_entries' => $journalEntries,
                'prepared_by' => $request->prepared_by,
                'reviewed_by' => $request->reviewed_by,
                'approved_by' => $request->approved_by,
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'Created',
                'item' => $voucher->cv_no,
                'target' => $voucher->supplier_name,
                'type' => 'Check Voucher',
                'message' => 'Created Check Voucher ' . $voucher->cv_no . ' for ' . $voucher->supplier_name,
            ]);

            return redirect()
                ->route('check_vouchers.show', $voucher->id)
                ->with('success', 'Check Voucher created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error creating check voucher: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified check voucher
     */
    public function show($id)
    {
        $voucher = CheckVoucher::with(['creator', 'accountsPayableInvoice', 'approvalUser'])
            ->findOrFail($id);

        return view('check_vouchers.show', compact('voucher'));
    }

    /**
     * Show the form for editing the specified check voucher
     */
    public function edit($id)
    {
        $voucher = CheckVoucher::findOrFail($id);

        return view('check_vouchers.edit', compact('voucher'));
    }

    /**
     * Update the specified check voucher
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'cv_date' => 'required|date',
            'check_date' => 'required|date',
            'supplier_name' => 'required|string',
            'check_amount' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'particulars' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $voucher = CheckVoucher::findOrFail($id);

            // Validate amounts do not exceed APV grand total
            $apvId = $request->accounts_payable_invoice_id ?: $voucher->accounts_payable_invoice_id;
            if ($apvId) {
                $apv = AccountsPayableInvoice::find($apvId);
                if ($apv) {
                    $maxAmount = (float) $apv->grand_total;
                    if ((float) $request->check_amount > $maxAmount) {
                        return back()->withInput()->with('error', 'Check amount (₱' . number_format($request->check_amount, 2) . ') exceeds APV grand total (₱' . number_format($maxAmount, 2) . ').');
                    }
                    if ((float) $request->paid_amount > $maxAmount) {
                        return back()->withInput()->with('error', 'Paid amount (₱' . number_format($request->paid_amount, 2) . ') exceeds APV grand total (₱' . number_format($maxAmount, 2) . ').');
                    }
                }
            }

            // Process journal entries
            $journalEntries = [];
            if ($request->has('journal_entries')) {
                foreach ($request->journal_entries as $entry) {
                    if (!empty($entry['account_code']) || !empty($entry['account_name'])) {
                        $journalEntries[] = [
                            'account_code' => $entry['account_code'] ?? '',
                            'account_name' => $entry['account_name'] ?? '',
                            'debit' => $entry['debit'] ?? 0,
                            'credit' => $entry['credit'] ?? 0,
                        ];
                    }
                }
            }

            // Update check voucher
            $voucher->update([
                'cv_date' => $request->cv_date,
                'check_date' => $request->check_date,
                'supplier_code' => $request->supplier_code,
                'supplier_name' => $request->supplier_name,
                'supplier_address' => $request->supplier_address,
                'supplier_tin' => $request->supplier_tin,
                'check_no' => $request->check_no,
                'bank' => $request->bank,
                'gl_account_id' => $request->gl_account_id,
                'branch' => $request->branch,
                'check_amount' => $request->check_amount,
                'payment_date' => $request->payment_date,
                'payment_type' => $request->payment_type,
                'reference_no' => $request->reference_no,
                'apv_no' => $request->apv_no,
                'paid_amount' => $request->paid_amount,
                'particulars' => $request->particulars,
                'journal_entries' => $journalEntries,
                'prepared_by' => $request->prepared_by,
                'reviewed_by' => $request->reviewed_by,
                'approved_by' => $request->approved_by,
            ]);

            DB::commit();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'Updated',
                'item' => $voucher->cv_no,
                'target' => $voucher->supplier_name,
                'type' => 'Check Voucher',
                'message' => 'Updated Check Voucher ' . $voucher->cv_no,
            ]);

            return redirect()
                ->route('check_vouchers.show', $voucher->id)
                ->with('success', 'Check Voucher updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error updating check voucher: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified check voucher
     */
    public function destroy($id)
    {
        try {
            $voucher = CheckVoucher::findOrFail($id);
            $cvNo = $voucher->cv_no;
            $voucher->delete();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'Deleted',
                'item' => $cvNo,
                'target' => 'N/A',
                'type' => 'Check Voucher',
                'message' => 'Deleted Check Voucher ' . $cvNo,
            ]);

            return redirect()
                ->route('check_vouchers.index')
                ->with('success', 'Check Voucher deleted successfully!');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Error deleting check voucher: ' . $e->getMessage());
        }
    }

    /**
     * Approve as Accounting Manager (Level 1 - Review)
     */
    public function approveAccounting(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->canApproveCVAsAccounting()) {
            return redirect()->route('check_vouchers.index')->with('error', 'Unauthorized.');
        }

        $voucher = CheckVoucher::where('approval_stage', 'pending_accounting')->findOrFail($id);
        $voucher->update([
            'approval_stage' => 'pending_odm',
            'accounting_reviewed_by' => Auth::id(),
            'accounting_reviewed_at' => now(),
            'accounting_reviewed_latitude' => $request->input('latitude'),
            'accounting_reviewed_longitude' => $request->input('longitude'),
            'accounting_reviewed_location' => $request->input('location'),
            'rejection_reason' => null,
        ]);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Reviewed (Accounting)',
            'item' => $voucher->cv_no,
            'target' => $voucher->supplier_name,
            'type' => 'Check Voucher',
            'message' => 'Accounting Manager reviewed Check Voucher ' . $voucher->cv_no,
        ]);

        return redirect()->back()->with('success', 'CV reviewed by Accounting Manager. Forwarded to ODM/FDM.');
    }

    /**
     * Approve as ODM/FDM (Level 2 - Final)
     */
    public function approve(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->canApproveCV()) {
            return redirect()->route('check_vouchers.index')->with('error', 'Unauthorized.');
        }

        $voucher = CheckVoucher::where('approval_stage', 'pending_odm')->findOrFail($id);

        DB::transaction(function () use ($voucher, $request) {
            $voucher->update([
                'status' => 'approved',
                'approval_stage' => 'approved',
                'approval_user_id' => Auth::id(),
                'approval_date' => now(),
                'approved_latitude' => $request->input('latitude'),
                'approved_longitude' => $request->input('longitude'),
                'approved_location' => $request->input('location'),
                'rejection_reason' => null,
            ]);

            // Deduct from treasury bank account if GL account is set
            if ($voucher->gl_account_id) {
                $bankAccount = TreasuryBankAccount::where('gl_account_id', $voucher->gl_account_id)->active()->first();
                if ($bankAccount) {
                    $voucher->update(['bank_account_id' => $bankAccount->id]);

                    $withdrawalAmount = (float) $voucher->check_amount;
                    $newBalance = (float) $bankAccount->cash_balance - $withdrawalAmount;

                    TreasuryBankTransaction::create([
                        'bank_account_id' => $bankAccount->id,
                        'txn_date'        => now()->toDateString(),
                        'type'            => 'Withdrawal',
                        'reference'       => $voucher->cv_no,
                        'description'     => 'Check Voucher approved — ' . ($voucher->supplier_name ?? 'N/A'),
                        'payee_or_source' => $voucher->supplier_name,
                        'debit'           => 0,
                        'credit'          => $withdrawalAmount,
                        'running_balance' => $newBalance,
                        'logged_by'       => Auth::user()->name,
                    ]);

                    $bankAccount->update([
                        'cash_balance'  => $newBalance,
                        'balance_as_of' => now()->toDateString(),
                    ]);
                }
            }

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'Approved',
                'item' => $voucher->cv_no,
                'target' => $voucher->supplier_name,
                'type' => 'Check Voucher',
                'message' => 'Approved Check Voucher ' . $voucher->cv_no,
            ]);
        });

        return redirect()->back()->with('success', 'Check Voucher approved!');
    }

    /**
     * Reject a check voucher (any stage)
     */
    public function reject(Request $request, $id)
    {
        $request->validate(['rejection_reason' => 'nullable|string|max:500']);
        $voucher = CheckVoucher::findOrFail($id);

        $voucher->update([
            'status' => 'rejected',
            'approval_stage' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Rejected',
            'item' => $voucher->cv_no,
            'target' => $voucher->supplier_name,
            'type' => 'Check Voucher',
            'message' => 'Rejected Check Voucher ' . $voucher->cv_no,
        ]);

        return redirect()->back()->with('success', 'Check Voucher rejected.');
    }

    /**
     * Print check voucher
     */
    public function print($id)
    {
        $checkVoucher = CheckVoucher::with(['creator', 'approvalUser', 'accountingReviewer'])->findOrFail($id);
        return view('check_vouchers.print', ['checkVoucher' => $checkVoucher]);
    }
}
