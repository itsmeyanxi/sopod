@extends('layouts.app')
@section('title', 'Edit Loan — ' . $loan->loan_no)

@section('content')
<div class="container mx-auto">
    <div class="bg-white text-gray-800 rounded-lg shadow-lg p-6">

        <div class="flex justify-between items-center mb-6 border-b border-gray-200 pb-4">
            <h1 class="text-2xl font-bold text-gray-800">EDIT LOAN</h1>
            <a href="{{ route('loans.show', $loan->id) }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>

        @if($errors->any())
            <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside text-sm">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('loans.update', $loan->id) }}">
            @csrf @method('PUT')

            <h2 class="text-base font-semibold text-gray-600 mb-3 border-b pb-2">Loan Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Loan No.</label>
                    <input type="text" value="{{ $loan->loan_no }}" readonly class="w-full bg-gray-100 border border-gray-300 rounded px-3 py-2 text-sm font-mono text-gray-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Loan Date <span class="text-red-500">*</span></label>
                    <input type="date" name="loan_date" value="{{ old('loan_date', $loan->loan_date->format('Y-m-d')) }}" required
                           class="w-full bg-white border border-gray-300 rounded px-3 py-2 text-sm @error('loan_date') border-red-400 @enderror">
                    @error('loan_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Loan Type <span class="text-red-500">*</span></label>
                    <select name="loan_type" required class="w-full bg-white border border-gray-300 rounded px-3 py-2 text-sm">
                        <option value="">— Select Type —</option>
                        @foreach($loanTypes as $t)
                            <option value="{{ $t }}" {{ old('loan_type', $loan->loan_type) === $t ? 'selected' : '' }}>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <h2 class="text-base font-semibold text-gray-600 mb-3 border-b pb-2">Lender Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Lender Name <span class="text-red-500">*</span></label>
                    <input type="text" name="lender_name" value="{{ old('lender_name', $loan->lender_name) }}" required
                           class="w-full bg-white border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Lender Address</label>
                    <input type="text" name="lender_address" value="{{ old('lender_address', $loan->lender_address) }}"
                           class="w-full bg-white border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-600 mb-1">Purpose</label>
                <textarea name="purpose" rows="2" class="w-full bg-white border border-gray-300 rounded px-3 py-2 text-sm">{{ old('purpose', $loan->purpose) }}</textarea>
            </div>

            <h2 class="text-base font-semibold text-gray-600 mb-3 border-b pb-2">Financial Terms</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Principal Amount <span class="text-red-500">*</span></label>
                    <input type="number" name="principal_amount" value="{{ old('principal_amount', $loan->principal_amount) }}" required step="0.01" min="0.01"
                           class="w-full bg-white border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Interest Rate (% p.a.) <span class="text-red-500">*</span></label>
                    <input type="number" name="interest_rate" value="{{ old('interest_rate', $loan->interest_rate) }}" required step="0.0001" min="0"
                           class="w-full bg-white border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Term (Months) <span class="text-red-500">*</span></label>
                    <input type="number" name="loan_term_months" value="{{ old('loan_term_months', $loan->loan_term_months) }}" required min="1"
                           class="w-full bg-white border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Disbursement Date</label>
                    <input type="date" name="disbursement_date" value="{{ old('disbursement_date', optional($loan->disbursement_date)->format('Y-m-d')) }}"
                           class="w-full bg-white border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Maturity Date <span class="text-red-500">*</span></label>
                    <input type="date" name="maturity_date" value="{{ old('maturity_date', $loan->maturity_date->format('Y-m-d')) }}" required
                           class="w-full bg-white border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Payment Frequency</label>
                    <select name="payment_frequency" class="w-full bg-white border border-gray-300 rounded px-3 py-2 text-sm">
                        @foreach(['Monthly','Quarterly','Semi-Annual','Annually','Bullet'] as $freq)
                            <option value="{{ $freq }}" {{ old('payment_frequency', $loan->payment_frequency) === $freq ? 'selected' : '' }}>{{ $freq }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <h2 class="text-base font-semibold text-gray-600 mb-3 border-b pb-2">GL Account Mapping</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                @include('partials.gl_account_selector', ['field' => 'gl_loan_account',    'label' => 'Loans Payable Account',       'uid' => 'loan_payable',  'value' => old('gl_loan_account',    $loan->gl_loan_account),    'glAccounts' => $glAccounts])
                @include('partials.gl_account_selector', ['field' => 'gl_cash_account',    'label' => 'Cash / Bank Received Account','uid' => 'loan_cash',     'value' => old('gl_cash_account',    $loan->gl_cash_account),    'glAccounts' => $glAccounts])
                @include('partials.gl_account_selector', ['field' => 'gl_interest_expense','label' => 'Interest Expense Account',    'uid' => 'loan_interest', 'value' => old('gl_interest_expense',$loan->gl_interest_expense),'glAccounts' => $glAccounts])
            </div>
            @include('partials.gl_account_selector_js')

            <h2 class="text-base font-semibold text-gray-600 mb-3 border-b pb-2">Approval & Notes</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Approved By</label>
                    <input type="text" name="approved_by" value="{{ old('approved_by', $loan->approved_by) }}"
                           class="w-full bg-white border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div></div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Terms & Conditions</label>
                    <textarea name="terms_conditions" rows="3" class="w-full bg-white border border-gray-300 rounded px-3 py-2 text-sm">{{ old('terms_conditions', $loan->terms_conditions) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Remarks</label>
                    <textarea name="remarks" rows="3" class="w-full bg-white border border-gray-300 rounded px-3 py-2 text-sm">{{ old('remarks', $loan->remarks) }}</textarea>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 text-sm">
                    <i class="fas fa-save mr-1"></i> Update Loan
                </button>
                <a href="{{ route('loans.show', $loan->id) }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded hover:bg-gray-300 text-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
