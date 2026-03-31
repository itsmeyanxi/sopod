@extends('layouts.app')

@section('title', 'Edit Payment — CR #' . ($payment->collection_receipt_number ?? 'N/A'))

@section('content')
<div class="container mx-auto max-w-3xl">
    <div class="mb-4">
        <a href="{{ route('payments.show', $payment->id) }}" class="text-sm text-gray-500 hover:text-gray-200">
            <i class="fas fa-arrow-left mr-1"></i> Back to Payment Details
        </a>
    </div>

    @if ($errors->any())
        <div class="bg-red-100 border border-red-300 text-red-700 p-3 rounded mb-4">
            <ul class="list-disc ml-5 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
            <h2 class="text-xl font-bold text-white">
                {{ auth()->user()->canEditPayments() ? 'Edit Payment' : 'Request Payment Edit' }}
            </h2>
            <p class="text-blue-100 text-sm mt-0.5">CR #{{ $payment->collection_receipt_number ?? 'N/A' }} — {{ $payment->customer_name }}</p>
        </div>

        <form action="{{ auth()->user()->canEditPayments() ? route('payments.update', $payment->id) : route('payments.submitEditRequest', $payment->id) }}" method="POST" class="p-6" enctype="multipart/form-data">
            @csrf
            @if(auth()->user()->canEditPayments())
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- CR Number -->
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Collection Receipt Number <span class="text-red-600">*</span></label>
                    <input type="text" name="collection_receipt_number"
                        value="{{ old('collection_receipt_number', $payment->collection_receipt_number) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        required>
                </div>

                <!-- DR No -->
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">DR Number <span class="text-red-600">*</span></label>
                    <input type="text" name="dr_no"
                        value="{{ old('dr_no', $payment->dr_no) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        required>
                </div>

                <!-- Invoice No -->
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Invoice Number</label>
                    <input type="text" name="invoice_no"
                        value="{{ old('invoice_no', $payment->invoice_no) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <!-- CR Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Collection Receipt Date <span class="text-red-600">*</span></label>
                    <input type="date" name="collection_receipt_date"
                        value="{{ old('collection_receipt_date', $payment->collection_receipt_date ? \Carbon\Carbon::parse($payment->collection_receipt_date)->format('Y-m-d') : '') }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        required>
                </div>

                <!-- Posting Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Payment Posting Date <span class="text-red-600">*</span></label>
                    <input type="date" name="payment_posting_date"
                        value="{{ old('payment_posting_date', $payment->payment_posting_date ? \Carbon\Carbon::parse($payment->payment_posting_date)->format('Y-m-d') : '') }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        required>
                </div>

                <!-- Amount -->
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Gross Amount <span class="text-red-600">*</span></label>
                    <input type="number" step="0.01" name="amount"
                        value="{{ old('amount', number_format((float)($payment->gross_amount ?? $payment->amount ?? 0), 2, '.', '')) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        required id="edit_amount" onchange="recalcNet()">
                </div>

                <!-- EWT -->
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">EWT</label>
                    <input type="number" step="0.01" name="tax"
                        value="{{ old('tax', number_format((float)($payment->ewt ?? $payment->tax ?? 0), 2, '.', '')) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        id="edit_tax" onchange="recalcNet()">
                </div>

                <!-- Net -->
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Net Amount</label>
                    <input type="number" step="0.01" name="net"
                        value="{{ old('net', number_format((float)($payment->check_amount ?? $payment->net ?? 0), 2, '.', '')) }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        id="edit_net">
                </div>
            </div>

            <!-- Payment Method -->
            <div class="mt-5">
                <label class="block text-sm font-medium text-gray-400 mb-1">Payment Method <span class="text-red-600">*</span></label>
                <select name="payment_method"
                    class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    required id="edit_payment_method" onchange="togglePaymentFields()">
                    <option value="check" {{ ($payment->payment_method ?? '') === 'check' ? 'selected' : '' }}>Check</option>
                    <option value="bank_transfer" {{ ($payment->payment_method ?? '') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                    <option value="cash" {{ ($payment->payment_method ?? '') === 'cash' ? 'selected' : '' }}>Cash</option>
                </select>
            </div>

            <!-- Bank -->
            <div class="mt-4" id="bank_field">
                <label class="block text-sm font-medium text-gray-400 mb-1">Bank</label>
                <input type="text" name="bank"
                    value="{{ old('bank', $payment->bank ?? $paymentMeans['bank_name'] ?? '') }}"
                    class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Reference No -->
            <div class="mt-4" id="reference_field">
                <label class="block text-sm font-medium text-gray-400 mb-1" id="reference_label">Check Number / Reference</label>
                <input type="text" name="reference_no"
                    value="{{ old('reference_no', $payment->reference_no ?? $paymentMeans['check_number'] ?? $paymentMeans['reference'] ?? '') }}"
                    class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Notes -->
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-400 mb-1">Payment Notes</label>
                <textarea name="payment_notes" rows="2"
                    class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ old('payment_notes', $payment->payment_notes) }}</textarea>
            </div>

            @if(!auth()->user()->canEditPayments())
            <!-- Edit Reason (required for edit requests) -->
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-400 mb-1">Reason for Edit <span class="text-red-600">*</span></label>
                <textarea name="edit_reason" rows="2" required
                    class="w-full bg-gray-900 border border-gray-700 rounded-md p-2.5 text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Explain why this payment needs to be edited...">{{ old('edit_reason') }}</textarea>
            </div>

            <!-- File Attachment -->
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-400 mb-1">Attachment (optional)</label>
                <input type="file" name="attachment"
                    accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx"
                    class="w-full bg-gray-900 border border-gray-700 rounded-md p-2 text-gray-200 text-sm file:mr-3 file:py-1.5 file:px-4 file:rounded file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                <p class="text-xs text-gray-400 mt-1">Supported: JPG, PNG, PDF, DOC, DOCX, XLS, XLSX (max 5MB)</p>
            </div>
            @endif

            <div class="mt-6 flex items-center gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-md font-medium transition">
                    <i class="fas fa-save mr-1"></i>
                    {{ auth()->user()->canEditPayments() ? 'Save Changes' : 'Submit Edit Request' }}
                </button>
                <a href="{{ route('payments.show', $payment->id) }}" class="bg-gray-600 hover:bg-gray-600 text-gray-200 px-6 py-2.5 rounded-md font-medium transition">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function recalcNet() {
    const amount = parseFloat(document.getElementById('edit_amount').value) || 0;
    const tax = parseFloat(document.getElementById('edit_tax').value) || 0;
    document.getElementById('edit_net').value = (amount - tax).toFixed(2);
}

function togglePaymentFields() {
    const method = document.getElementById('edit_payment_method').value;
    const bankField = document.getElementById('bank_field');
    const refField = document.getElementById('reference_field');
    const refLabel = document.getElementById('reference_label');

    if (method === 'cash') {
        bankField.style.display = 'none';
        refField.style.display = 'none';
    } else {
        bankField.style.display = 'block';
        refField.style.display = 'block';
        refLabel.textContent = method === 'check' ? 'Check Number' : 'Reference Number';
    }
}

// Init on load
togglePaymentFields();
</script>
@endsection
