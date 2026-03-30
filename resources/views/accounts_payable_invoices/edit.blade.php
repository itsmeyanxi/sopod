@extends('layouts.app')

@section('title', 'Edit Accounts Payable Invoice')

@section('content')
<div class="container mx-auto">
    <div class="bg-white text-gray-800 rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-200 pb-4">
            <h1 class="text-2xl font-bold text-gray-800">EDIT ACCOUNTS PAYABLE VOUCHER</h1>
            <div class="text-right">
                <label class="font-semibold text-gray-500">APV NO:</label>
                <span class="ml-2 px-4 py-1 bg-gray-50 border border-gray-200 text-gray-800 rounded">{{ $invoice->apv_no }}</span>
            </div>
        </div>

        @if($errors->any())
            <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($invoice->requestForPayment)
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded">
            <div class="flex items-center text-green-700 mb-2">
                <i class="fas fa-link mr-2"></i>
                <span class="font-semibold">Linked to RFP: {{ $invoice->requestForPayment->rfp_no }}</span>
            </div>
            <div class="text-sm text-gray-500">
                Payee: {{ $invoice->requestForPayment->payee }} | Company: {{ $invoice->requestForPayment->company }}
            </div>
        </div>
        @endif

        <form action="{{ route('accounts_payable_invoices.update', $invoice->id) }}" method="POST" id="apvForm">
            @csrf
            @method('PUT')

            <!-- APV Date and Payment Type -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">APV DATE: <span class="text-red-700">*</span></label>
                    <input type="date" name="apv_date" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('apv_date', $invoice->apv_date->format('Y-m-d')) }}" required>
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">PAYMENT TYPE: <span class="text-red-700">*</span></label>
                    <div class="flex gap-4">
                        <label class="flex items-center p-3 bg-gray-50 border border-gray-200 rounded hover:bg-gray-100 cursor-pointer transition flex-1">
                            <input type="radio" name="payment_type" value="full_payment" class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 focus:ring-purple-500" {{ old('payment_type', $invoice->payment_type) == 'full_payment' ? 'checked' : '' }} required>
                            <span class="ml-3 text-gray-800">Full Payment</span>
                        </label>
                        <label class="flex items-center p-3 bg-gray-50 border border-gray-200 rounded hover:bg-gray-100 cursor-pointer transition flex-1">
                            <input type="radio" name="payment_type" value="downpayment" class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 focus:ring-purple-500" {{ old('payment_type', $invoice->payment_type) == 'downpayment' ? 'checked' : '' }}>
                            <span class="ml-3 text-gray-800">Downpayment</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Vendor Information -->
            <div class="mb-6 bg-gray-50 border border-gray-200 rounded p-4">
                <h3 class="font-semibold text-gray-800 mb-4">VENDOR INFORMATION</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-500 mb-2">VENDOR CODE:</label>
                        <input type="text" name="vendor_code" class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('vendor_code', $invoice->vendor_code) }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-2">VENDOR NAME: <span class="text-red-700">*</span></label>
                        <input type="text" name="vendor_name" class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('vendor_name', $invoice->vendor_name) }}" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block font-semibold text-gray-500 mb-2">VENDOR ADDRESS:</label>
                        <textarea name="vendor_address" rows="2" class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('vendor_address', $invoice->vendor_address) }}</textarea>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-2">VENDOR TIN:</label>
                        <input type="text" name="vendor_tin" class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('vendor_tin', $invoice->vendor_tin) }}">
                    </div>
                </div>
            </div>

            <!-- Document Details -->
            <div class="mb-6 bg-gray-50 border border-gray-200 rounded p-4">
                <h3 class="font-semibold text-gray-800 mb-4">DOCUMENT DETAILS</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-500 mb-2">DOCUMENT DATE: <span class="text-red-700">*</span></label>
                        <input type="date" name="document_date" class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('document_date', $invoice->document_date->format('Y-m-d')) }}" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-2">PAYMENT TERMS:</label>
                        <input type="text" name="payment_terms" class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('payment_terms', $invoice->payment_terms) }}" placeholder="e.g., Net 30">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-2">DUE DATE:</label>
                        <input type="date" name="due_date" class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('due_date', $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '') }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-2">REFERENCE NO:</label>
                        <input type="text" name="reference_no" class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('reference_no', $invoice->reference_no) }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-2">PURCHASE ORDER NO:</label>
                        <input type="text" name="purchase_order_no" class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('purchase_order_no', $invoice->purchase_order_no) }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-2">CURRENCY: <span class="text-red-700">*</span></label>
                        <select name="currency" class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                            <option value="PHP" {{ old('currency', $invoice->currency) == 'PHP' ? 'selected' : '' }}>PHP</option>
                            <option value="USD" {{ old('currency', $invoice->currency) == 'USD' ? 'selected' : '' }}>USD</option>
                            <option value="EUR" {{ old('currency', $invoice->currency) == 'EUR' ? 'selected' : '' }}>EUR</option>
                            <option value="JPY" {{ old('currency', $invoice->currency) == 'JPY' ? 'selected' : '' }}>JPY</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-2">FOREX RATE:</label>
                        <input type="number" step="0.0001" name="forex_rate" class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('forex_rate', $invoice->forex_rate) }}" placeholder="1.0000">
                    </div>
                </div>
            </div>

            <!-- Particulars and Accounting -->
            <div class="mb-6 bg-gray-50 border border-gray-200 rounded p-4">
                <h3 class="font-semibold text-gray-800 mb-4">PARTICULARS & ACCOUNTING</h3>
                <div class="mb-4">
                    <label class="block font-semibold text-gray-500 mb-2">PARTICULARS: <span class="text-red-700">*</span></label>
                    <textarea name="particulars" rows="4" class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" required>{{ old('particulars', $invoice->particulars) }}</textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-500 mb-2">ITEM CODE:</label>
                        <input type="text" name="item_code" class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('item_code', $invoice->item_code) }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-2">COST CENTER:</label>
                        <input type="text" name="cost_center" class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('cost_center', $invoice->cost_center) }}">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block font-semibold text-gray-500 mb-2">ACCOUNT CODE / NAME:</label>
                        @include('partials.gl_account_selector', ['field' => 'account_code', 'label' => '', 'uid' => 'apv_account', 'value' => old('account_code', $invoice->account_code), 'glAccounts' => $glAccounts])
                        <input type="hidden" name="account_name" id="apv_account_name_hidden" value="{{ old('account_name', $invoice->account_name) }}">
                        @include('partials.gl_account_selector_js')
                        <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            document.getElementById('gl_dropdown_apv_account')
                                ?.querySelectorAll('.gl-option')
                                .forEach(function (opt) {
                                    opt.addEventListener('click', function () {
                                        document.getElementById('apv_account_name_hidden').value = this.dataset.name;
                                    });
                                });
                        });
                        </script>
                    </div>
                </div>
            </div>

            <!-- Amount Calculations -->
            <div class="mb-6 bg-gray-50 border border-gray-200 rounded p-4">
                <h3 class="font-semibold text-gray-800 mb-4">AMOUNT DETAILS</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-500 mb-2">TOTAL AMOUNT: <span class="text-red-700">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-gray-500">₱</span>
                            <input type="number" step="0.01" name="total" id="totalAmount" class="w-full bg-white border border-gray-200 rounded pl-8 pr-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('total', $invoice->total) }}" required>
                        </div>
                    </div>
                    <div id="downpaymentField" style="display: none;">
                        <label class="block font-semibold text-gray-500 mb-2">DOWNPAYMENT AMOUNT: <span class="text-red-700">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-gray-500">₱</span>
                            <input type="number" step="0.01" name="downpayment_amount" id="downpaymentAmount" class="w-full bg-white border border-gray-200 rounded pl-8 pr-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('downpayment_amount', $invoice->downpayment_amount) }}">
                        </div>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-2">VAT AMOUNT:</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-gray-500">₱</span>
                            <input type="number" step="0.01" name="vat_amount" id="vatAmount" class="w-full bg-white border border-gray-200 rounded pl-8 pr-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('vat_amount', $invoice->vat_amount) }}">
                        </div>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 mb-2">W-TAX AMOUNT:</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-gray-500">₱</span>
                            <input type="number" step="0.01" name="w_tax_amount" id="wTaxAmount" class="w-full bg-white border border-gray-200 rounded pl-8 pr-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('w_tax_amount', $invoice->w_tax_amount) }}">
                        </div>
                    </div>
                </div>

                <!-- Grand Total Display -->
                <div class="mt-4 p-4 bg-white border-2 border-purple-600 rounded">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-semibold text-gray-500">GRAND TOTAL:</span>
                        <span id="grandTotalDisplay" class="text-2xl font-bold text-purple-700">₱ 0.00</span>
                    </div>
                </div>
            </div>

            <!-- Prepared and Reviewed By -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">PREPARED BY:</label>
                    <input type="text" name="prepared_by" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('prepared_by', $invoice->prepared_by) }}">
                </div>
                <div>
                    <label class="block font-semibold text-gray-500 mb-2">REVIEWED BY:</label>
                    <input type="text" name="reviewed_by" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('reviewed_by', $invoice->reviewed_by) }}">
                </div>
            </div>

            <!-- Remarks -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-500 mb-2">REMARKS:</label>
                <textarea name="remarks" rows="3" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('remarks', $invoice->remarks) }}</textarea>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('accounts_payable_invoices.show', $invoice->id) }}" class="bg-gray-100 text-gray-800 px-6 py-2 rounded hover:bg-gray-100 transition">
                    Cancel
                </a>
                <button type="submit" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800">
                    <i class="fas fa-save mr-1"></i> Update Invoice
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Grand Total Calculation — declare inputs first so calculateGrandTotal() can reference them
    const totalInput = document.getElementById('totalAmount');
    const vatInput = document.getElementById('vatAmount');
    const wTaxInput = document.getElementById('wTaxAmount');
    const grandTotalDisplay = document.getElementById('grandTotalDisplay');

    // Payment Type Toggle
    const paymentTypeRadios = document.querySelectorAll('input[name="payment_type"]');
    const downpaymentField = document.getElementById('downpaymentField');
    const downpaymentInput = document.getElementById('downpaymentAmount');

    function toggleDownpaymentField() {
        const selectedType = document.querySelector('input[name="payment_type"]:checked').value;
        if (selectedType === 'downpayment') {
            downpaymentField.style.display = 'block';
            downpaymentInput.required = true;
        } else {
            downpaymentField.style.display = 'none';
            downpaymentInput.required = false;
        }
        calculateGrandTotal();
    }

    paymentTypeRadios.forEach(radio => {
        radio.addEventListener('change', toggleDownpaymentField);
    });

    toggleDownpaymentField();

    function calculateGrandTotal() {
        const paymentType = document.querySelector('input[name="payment_type"]:checked').value;
        const total = parseFloat(totalInput.value) || 0;
        const downpayment = parseFloat(downpaymentInput.value) || 0;
        const vat = parseFloat(vatInput.value) || 0;
        const wTax = parseFloat(wTaxInput.value) || 0;

        let totalBeforeVat;
        if (paymentType === 'downpayment') {
            totalBeforeVat = downpayment;
        } else {
            totalBeforeVat = total;
        }

        const totalAfterVat = totalBeforeVat + vat;
        const grandTotal = totalAfterVat - wTax;

        grandTotalDisplay.textContent = '₱ ' + grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    totalInput.addEventListener('input', calculateGrandTotal);
    downpaymentInput.addEventListener('input', calculateGrandTotal);
    vatInput.addEventListener('input', calculateGrandTotal);
    wTaxInput.addEventListener('input', calculateGrandTotal);

    calculateGrandTotal();
});
</script>
@endsection
