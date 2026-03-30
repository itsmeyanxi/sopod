<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Loan;
use App\Models\LoanPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $query = Loan::query();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('loan_no', 'LIKE', "%{$s}%")
                  ->orWhere('lender_name', 'LIKE', "%{$s}%")
                  ->orWhere('purpose', 'LIKE', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('loan_type')) {
            $query->where('loan_type', $request->loan_type);
        }

        $loans = $query->orderByDesc('loan_date')->orderByDesc('id')->paginate(50);

        $summary = [
            'total'             => Loan::count(),
            'active'            => Loan::active()->count(),
            'paid'              => Loan::where('status', 'Paid')->count(),
            'total_outstanding' => Loan::active()->sum('outstanding_principal'),
        ];

        return view('loans.index', compact('loans', 'summary'));
    }

    public function create()
    {
        $nextLoanNo = $this->generateNextLoanNo();
        $loanTypes  = ['Term Loan', 'Line of Credit', 'Mortgage Loan', 'Equipment Loan', 'Bridge Loan', 'Other'];
        $glAccounts = $this->getGlAccounts();

        return view('loans.create', compact('nextLoanNo', 'loanTypes', 'glAccounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'loan_date'        => 'required|date',
            'lender_name'      => 'required|string|max:255',
            'loan_type'        => 'required|string|max:100',
            'principal_amount' => 'required|numeric|min:0.01',
            'interest_rate'    => 'required|numeric|min:0',
            'loan_term_months' => 'required|integer|min:1',
            'maturity_date'    => 'required|date|after:loan_date',
        ]);

        DB::beginTransaction();
        try {
            $loan = Loan::create([
                'loan_no'               => $this->generateNextLoanNo(),
                'loan_date'             => $request->loan_date,
                'lender_name'           => $request->lender_name,
                'lender_address'        => $request->lender_address,
                'loan_type'             => $request->loan_type,
                'purpose'               => $request->purpose,
                'principal_amount'      => $request->principal_amount,
                'interest_rate'         => $request->interest_rate,
                'loan_term_months'      => $request->loan_term_months,
                'disbursement_date'     => $request->disbursement_date ?: null,
                'maturity_date'         => $request->maturity_date,
                'payment_frequency'     => $request->payment_frequency ?? 'Monthly',
                'gl_loan_account'       => $request->gl_loan_account,
                'gl_cash_account'       => $request->gl_cash_account,
                'gl_interest_expense'   => $request->gl_interest_expense,
                'outstanding_principal' => $request->principal_amount,
                'total_interest_paid'   => 0,
                'total_principal_paid'  => 0,
                'status'                => 'Active',
                'terms_conditions'      => $request->terms_conditions,
                'remarks'               => $request->remarks,
                'prepared_by'           => Auth::user()->name,
                'approved_by'           => $request->approved_by,
                'created_by'            => Auth::id(),
            ]);

            DB::commit();

            Activity::create([
                'user_name' => Auth::user()->name,
                'action'    => 'Created',
                'item'      => $loan->loan_no,
                'target'    => $loan->lender_name,
                'type'      => 'Loan',
                'message'   => "Created Loan {$loan->loan_no} from {$loan->lender_name}",
            ]);

            return redirect()->route('loans.show', $loan->id)
                ->with('success', 'Loan record created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error creating loan: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $loan = Loan::with('payments')->findOrFail($id);
        return view('loans.show', compact('loan'));
    }

    public function edit($id)
    {
        $loan = Loan::findOrFail($id);

        if ($loan->status !== 'Active') {
            return redirect()->route('loans.show', $id)->with('error', 'Only active loans can be edited.');
        }

        $loanTypes  = ['Term Loan', 'Line of Credit', 'Mortgage Loan', 'Equipment Loan', 'Bridge Loan', 'Other'];
        $glAccounts = $this->getGlAccounts();

        return view('loans.edit', compact('loan', 'loanTypes', 'glAccounts'));
    }

    public function update(Request $request, $id)
    {
        $loan = Loan::findOrFail($id);

        if ($loan->status !== 'Active') {
            return redirect()->route('loans.show', $id)->with('error', 'Only active loans can be edited.');
        }

        $request->validate([
            'loan_date'        => 'required|date',
            'lender_name'      => 'required|string|max:255',
            'loan_type'        => 'required|string|max:100',
            'principal_amount' => 'required|numeric|min:0.01',
            'interest_rate'    => 'required|numeric|min:0',
            'loan_term_months' => 'required|integer|min:1',
            'maturity_date'    => 'required|date',
        ]);

        $loan->update([
            'loan_date'             => $request->loan_date,
            'lender_name'           => $request->lender_name,
            'lender_address'        => $request->lender_address,
            'loan_type'             => $request->loan_type,
            'purpose'               => $request->purpose,
            'principal_amount'      => $request->principal_amount,
            'interest_rate'         => $request->interest_rate,
            'loan_term_months'      => $request->loan_term_months,
            'disbursement_date'     => $request->disbursement_date ?: null,
            'maturity_date'         => $request->maturity_date,
            'payment_frequency'     => $request->payment_frequency ?? 'Monthly',
            'gl_loan_account'       => $request->gl_loan_account,
            'gl_cash_account'       => $request->gl_cash_account,
            'gl_interest_expense'   => $request->gl_interest_expense,
            'terms_conditions'      => $request->terms_conditions,
            'remarks'               => $request->remarks,
            'approved_by'           => $request->approved_by,
        ]);

        Activity::create([
            'user_name' => Auth::user()->name,
            'action'    => 'Updated',
            'item'      => $loan->loan_no,
            'target'    => $loan->lender_name,
            'type'      => 'Loan',
            'message'   => "Updated Loan {$loan->loan_no}",
        ]);

        return redirect()->route('loans.show', $loan->id)->with('success', 'Loan updated successfully.');
    }

    public function storePayment(Request $request, $id)
    {
        $loan = Loan::findOrFail($id);

        if ($loan->status !== 'Active') {
            return back()->with('error', 'Cannot record payments for a non-active loan.');
        }

        $request->validate([
            'payment_date'   => 'required|date',
            'principal_paid' => 'required|numeric|min:0',
            'interest_paid'  => 'required|numeric|min:0',
        ]);

        $principal = (float) $request->principal_paid;
        $interest  = (float) $request->interest_paid;

        if ($principal <= 0 && $interest <= 0) {
            return back()->with('error', 'At least one of principal or interest must be greater than zero.');
        }

        if ($principal > (float) $loan->outstanding_principal) {
            return back()->with('error', 'Principal paid cannot exceed outstanding principal of ' . number_format($loan->outstanding_principal, 2));
        }

        DB::beginTransaction();
        try {
            LoanPayment::create([
                'loan_id'        => $loan->id,
                'payment_date'   => $request->payment_date,
                'principal_paid' => $principal,
                'interest_paid'  => $interest,
                'total_payment'  => $principal + $interest,
                'reference_no'   => $request->reference_no,
                'payment_method' => $request->payment_method,
                'remarks'        => $request->remarks,
                'created_by'     => Auth::user()->name,
            ]);

            $newOutstanding    = max(0, (float) $loan->outstanding_principal - $principal);
            $newTotalPrincipal = (float) $loan->total_principal_paid + $principal;
            $newTotalInterest  = (float) $loan->total_interest_paid + $interest;

            $newStatus = ($newOutstanding <= 0) ? 'Paid' : 'Active';

            $loan->update([
                'outstanding_principal' => $newOutstanding,
                'total_principal_paid'  => $newTotalPrincipal,
                'total_interest_paid'   => $newTotalInterest,
                'status'                => $newStatus,
            ]);

            DB::commit();

            Activity::create([
                'user_name' => Auth::user()->name,
                'action'    => 'Payment',
                'item'      => $loan->loan_no,
                'target'    => number_format($principal + $interest, 2),
                'type'      => 'Loan',
                'message'   => "Recorded payment of " . number_format($principal + $interest, 2) . " on Loan {$loan->loan_no}",
            ]);

            return redirect()->route('loans.show', $loan->id)
                ->with('success', 'Payment recorded successfully.' . ($newStatus === 'Paid' ? ' Loan is now fully paid!' : ''));

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error recording payment: ' . $e->getMessage());
        }
    }

    public function markVoid($id)
    {
        $loan = Loan::findOrFail($id);

        if ($loan->payments()->count() > 0) {
            return back()->with('error', 'Cannot void a loan that already has payments recorded.');
        }

        $loan->update(['status' => 'Void']);

        Activity::create([
            'user_name' => Auth::user()->name,
            'action'    => 'Voided',
            'item'      => $loan->loan_no,
            'type'      => 'Loan',
            'message'   => "Voided Loan {$loan->loan_no}",
        ]);

        return back()->with('success', 'Loan voided.');
    }

    // -----------------------------------------------------------------------
    private function getGlAccounts(): array
    {
        return \App\Models\GlAccount::orderBy('account_code')
            ->get(['account_code', 'account_name'])
            ->map(fn($a) => [
                'code'    => $a->account_code,
                'name'    => $a->account_name,
                'display' => $a->account_code . ' — ' . $a->account_name,
                'search'  => strtolower($a->account_code . ' ' . $a->account_name),
            ])->toArray();
    }

    private function generateNextLoanNo(): string
    {
        $prefix = 'LN-' . date('Ym') . '-';
        $last   = Loan::where('loan_no', 'LIKE', "{$prefix}%")
            ->orderByDesc('loan_no')
            ->value('loan_no');

        $next = 1;
        if ($last) {
            $next = (int) substr($last, -4) + 1;
        }

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
