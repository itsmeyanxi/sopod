<?php

namespace App\Http\Controllers;

use App\Models\CheckVoucher;
use App\Models\AccountsPayableInvoice;
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
            ->select('id', 'apv_no', 'vendor_name', 'apv_date', 'currency', 'grand_total', 'reference_no')
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
                'branch' => $request->branch,
                'check_amount' => $request->check_amount,
                'payment_date' => $request->payment_date,
                'payment_type' => $request->payment_type,
                'reference_no' => $request->reference_no,
                'apv_no' => $request->apv_no,
                'paid_amount' => $request->paid_amount,
                'particulars' => $request->particulars,
                'journal_entries' => json_encode($journalEntries),
                'prepared_by' => $request->prepared_by,
                'reviewed_by' => $request->reviewed_by,
                'approved_by' => $request->approved_by,
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            DB::commit();

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
                'branch' => $request->branch,
                'check_amount' => $request->check_amount,
                'payment_date' => $request->payment_date,
                'payment_type' => $request->payment_type,
                'reference_no' => $request->reference_no,
                'apv_no' => $request->apv_no,
                'paid_amount' => $request->paid_amount,
                'particulars' => $request->particulars,
                'journal_entries' => json_encode($journalEntries),
                'prepared_by' => $request->prepared_by,
                'reviewed_by' => $request->reviewed_by,
                'approved_by' => $request->approved_by,
            ]);

            DB::commit();

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
            $voucher->delete();

            return redirect()
                ->route('check_vouchers.index')
                ->with('success', 'Check Voucher deleted successfully!');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Error deleting check voucher: ' . $e->getMessage());
        }
    }

    /**
     * Approve a check voucher
     */
    public function approve($id)
    {
        $voucher = CheckVoucher::findOrFail($id);

        $voucher->update([
            'status' => 'approved',
            'approval_user_id' => Auth::id(),
            'approval_date' => now(),
            'rejection_reason' => null,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Check Voucher approved successfully!');
    }

    /**
     * Reject a check voucher
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $voucher = CheckVoucher::findOrFail($id);

        $voucher->update([
            'status' => 'rejected',
            'approval_user_id' => Auth::id(),
            'approval_date' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Check Voucher rejected.');
    }
}
