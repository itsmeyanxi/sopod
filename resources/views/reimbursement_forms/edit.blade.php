@extends('layouts.app')

@section('title', 'Edit Reimbursement Form')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">EDIT REIMBURSEMENT FORM</h1>
            <div class="text-right">
                <label class="font-semibold text-gray-300">RI NO:</label>
                <span class="ml-2 px-4 py-1 bg-gray-900 border border-gray-700 text-white rounded">{{ $reimbursement->ri_no }}</span>
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

        <form action="{{ route('reimbursement_forms.update', $reimbursement->id) }}" method="POST" id="reimbursementForm" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <!-- Department and Date Applied -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">DEPARTMENT: <span class="text-red-700">*</span></label>
                    <input type="text" name="department" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('department', $reimbursement->department) }}" required placeholder="Enter department name">
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">DATE APPLIED: <span class="text-red-700">*</span></label>
                    <input type="date" name="date_applied" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('date_applied', $reimbursement->date_applied->format('Y-m-d')) }}" required>
                </div>
            </div>

            <!-- Line Items Table -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-3">EXPENSE ITEMS:</label>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse border border-gray-700" id="itemsTable">
                        <thead class="bg-gray-700 text-gray-300 uppercase text-sm">
                            <tr>
                                <th class="border border-gray-700 px-4 py-3" style="width: 20%;">DATE</th>
                                <th class="border border-gray-700 px-4 py-3" style="width: 50%;">PARTICULARS</th>
                                <th class="border border-gray-700 px-4 py-3" style="width: 20%;">COST</th>
                                <th class="border border-gray-700 px-4 py-3" style="width: 10%;">ACTION</th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            @foreach($reimbursement->items as $index => $item)
                                <tr>
                                    <td class="border border-gray-700 px-2 py-2">
                                        <input type="text" name="items[{{ $index }}][date]" class="w-full bg-gray-900 border border-gray-700 rounded px-2 py-1 text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="e.g. Jan 15, 2026" value="{{ old('items.'.$index.'.date', $item->date ?? '') }}">
                                    </td>
                                    <td class="border border-gray-700 px-2 py-2">
                                        <input type="text" name="items[{{ $index }}][particulars]" class="w-full bg-gray-900 border border-gray-700 rounded px-2 py-1 text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Enter description" value="{{ old('items.'.$index.'.particulars', $item->particulars) }}">
                                    </td>
                                    <td class="border border-gray-700 px-2 py-2">
                                        <input type="number" step="0.01" name="items[{{ $index }}][cost]" class="w-full bg-gray-900 border border-gray-700 rounded px-2 py-1 text-white text-sm text-right focus:outline-none focus:ring-2 focus:ring-purple-500 item-cost" placeholder="0.00" value="{{ old('items.'.$index.'.cost', $item->cost) }}" oninput="calculateTotal()">
                                    </td>
                                    <td class="border border-gray-700 px-2 py-2 text-center">
                                        <button type="button" onclick="removeRow(this)" class="bg-red-600 text-white px-2 py-1 rounded text-xs hover:bg-red-700 transition">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-900">
                                <td colspan="2" class="border border-gray-700 px-4 py-3 text-right font-bold text-white">TOTAL AMOUNT SPENT:</td>
                                <td class="border border-gray-700 px-4 py-3 text-right font-bold text-green-700 text-lg">
                                    <span id="totalDisplay">{{ number_format($reimbursement->total_amount_spent, 2) }}</span>
                                </td>
                                <td class="border border-gray-700 px-2 py-2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <input type="hidden" name="total_amount_spent" id="totalAmountSpent" value="{{ $reimbursement->total_amount_spent }}">

                <div class="mt-3">
                    <button type="button" id="addRowBtn" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-4 py-2 rounded hover:from-purple-700 hover:to-purple-800 transition text-sm">
                        <i class="fas fa-plus mr-1"></i> Add Row
                    </button>
                </div>
            </div>

            <!-- Amount to be Reimbursed -->
            <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
                <label class="block font-semibold text-gray-300 mb-2">AMOUNT TO BE REIMBURSED: <span class="text-red-700">*</span></label>
                <div class="relative">
                    <span class="absolute left-3 top-2.5 text-gray-300">&#8369;</span>
                    <input type="number" step="0.01" name="amount_to_be_reimbursed" class="w-full bg-gray-800 border border-gray-700 rounded pl-8 pr-3 py-2 text-white text-lg font-semibold focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('amount_to_be_reimbursed', $reimbursement->amount_to_be_reimbursed) }}" required placeholder="0.00">
                </div>
            </div>

            <!-- Submitted By -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">SUBMITTED BY:</label>
                <input type="text" name="submitted_by" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('submitted_by', $reimbursement->submitted_by) }}" placeholder="Name of person submitting">
            </div>

            <!-- Proof Documents -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">PROOF DOCUMENTS:</label>

                @if($reimbursement->proof_documents && count($reimbursement->proof_documents) > 0)
                    <div class="mb-4 p-4 bg-gray-900 border border-gray-700 rounded">
                        <p class="text-gray-300 font-semibold mb-3">Currently uploaded documents:</p>
                        <ul class="list-disc list-inside text-gray-300 text-sm space-y-2">
                            @foreach($reimbursement->proof_documents as $index => $doc)
                                <li>
                                    <a href="{{ asset('storage/' . $doc['path']) }}" target="_blank" class="text-purple-700 hover:text-purple-700">{{ $doc['name'] }}</a>
                                    <span class="text-gray-300">({{ number_format($doc['size'] / 1024, 2) }} KB)</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="bg-gray-900 border-2 border-dashed border-gray-700 rounded px-4 py-6 text-center">
                    <input type="file" name="proof_documents[]" id="proofDocuments" class="hidden" multiple accept=".doc,.docx,.odf,.jpg,.jpeg,.png,.gif,.bmp,.webp">
                    <label for="proofDocuments" class="cursor-pointer">
                        <div class="text-gray-300 mb-2">
                            <i class="fas fa-cloud-upload-alt text-2xl"></i>
                        </div>
                        <p class="text-white font-semibold mb-1">Click to upload or drag and drop</p>
                        <p class="text-gray-300 text-sm">Accepted formats: .doc, .docx, .odf, .jpg, .jpeg, .png, .gif, .bmp, .webp</p>
                    </label>
                </div>
                <div id="fileList" class="mt-3"></div>
            </div>

            <!-- Remarks -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">REMARKS:</label>
                <textarea name="remarks" rows="3" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Optional remarks...">{{ old('remarks', $reimbursement->remarks) }}</textarea>
            </div>

            <!-- Footer Note -->
            <div class="mb-6 p-3 bg-gray-900 border border-gray-700 rounded">
                <p class="text-gray-300 text-sm italic">
                    <i class="fas fa-info-circle mr-1 text-yellow-700"></i>
                    Please attach invoices, Official receipts (OR), and other supporting documents.
                </p>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('reimbursement_forms.show', $reimbursement->id) }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-700 transition">
                    Cancel
                </a>
                <button type="submit" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800">
                    <i class="fas fa-save mr-1"></i> Update Reimbursement Form
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let rowIndex = {{ $reimbursement->items->count() }};

document.getElementById('addRowBtn').addEventListener('click', function() {
    const tbody = document.getElementById('itemsBody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td class="border border-gray-700 px-2 py-2">
            <input type="text" name="items[${rowIndex}][date]" class="w-full bg-gray-900 border border-gray-700 rounded px-2 py-1 text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="e.g. Jan 15, 2026">
        </td>
        <td class="border border-gray-700 px-2 py-2">
            <input type="text" name="items[${rowIndex}][particulars]" class="w-full bg-gray-900 border border-gray-700 rounded px-2 py-1 text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Enter description">
        </td>
        <td class="border border-gray-700 px-2 py-2">
            <input type="number" step="0.01" name="items[${rowIndex}][cost]" class="w-full bg-gray-900 border border-gray-700 rounded px-2 py-1 text-white text-sm text-right focus:outline-none focus:ring-2 focus:ring-purple-500 item-cost" placeholder="0.00" oninput="calculateTotal()">
        </td>
        <td class="border border-gray-700 px-2 py-2 text-center">
            <button type="button" onclick="removeRow(this)" class="bg-red-600 text-white px-2 py-1 rounded text-xs hover:bg-red-700 transition">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    rowIndex++;
});

function removeRow(btn) {
    const tbody = document.getElementById('itemsBody');
    if (tbody.rows.length > 1) {
        btn.closest('tr').remove();
        calculateTotal();
    } else {
        alert('At least one item row is required.');
    }
}

function calculateTotal() {
    let total = 0;
    document.querySelectorAll('.item-cost').forEach(input => {
        total += parseFloat(input.value) || 0;
    });
    document.getElementById('totalDisplay').textContent = total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('totalAmountSpent').value = total.toFixed(2);
}

// Calculate initial total on page load
document.addEventListener('DOMContentLoaded', function() {
    calculateTotal();
});

// File upload handling
const proofDocumentsInput = document.getElementById('proofDocuments');
const fileListDiv = document.getElementById('fileList');
const uploadArea = proofDocumentsInput.closest('.bg-gray-900');

// Click to upload
proofDocumentsInput.addEventListener('change', function() {
    updateFileList();
});

// Drag and drop
uploadArea.addEventListener('dragover', function(e) {
    e.preventDefault();
    uploadArea.classList.add('border-purple-500', 'bg-gray-800');
});

uploadArea.addEventListener('dragleave', function(e) {
    e.preventDefault();
    uploadArea.classList.remove('border-purple-500', 'bg-gray-800');
});

uploadArea.addEventListener('drop', function(e) {
    e.preventDefault();
    uploadArea.classList.remove('border-purple-500', 'bg-gray-800');
    proofDocumentsInput.files = e.dataTransfer.files;
    updateFileList();
});

function updateFileList() {
    fileListDiv.innerHTML = '';
    const files = proofDocumentsInput.files;

    if (files.length > 0) {
        const ul = document.createElement('ul');
        ul.className = 'list-disc list-inside text-gray-300 text-sm space-y-1';

        for (let i = 0; i < files.length; i++) {
            const li = document.createElement('li');
            li.textContent = files[i].name + ' (' + (files[i].size / 1024).toFixed(2) + ' KB)';
            ul.appendChild(li);
        }

        fileListDiv.appendChild(ul);
    }
}
</script>
@endsection
