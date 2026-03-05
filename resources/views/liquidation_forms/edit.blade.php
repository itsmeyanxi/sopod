@extends('layouts.app')

@section('title', 'Edit Liquidation Form')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">EDIT LIQUIDATION FORM</h1>
            <div class="text-right">
                <label class="font-semibold text-gray-300">LIQ NO:</label>
                <span class="ml-2 px-4 py-1 bg-gray-900 border border-gray-700 text-white rounded">{{ $liquidation->liq_no }}</span>
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

        <!-- Search CAR Section -->
        <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
            <h3 class="font-semibold text-white mb-2"><i class="fas fa-search mr-2"></i>Search Approved Cash Advance Request</h3>
            <p class="text-gray-400 text-sm mb-3">Search by CAR Number, Payee Name, or Department to link a Cash Advance Request</p>
            <div class="relative">
                <input
                    type="text"
                    id="carSearchInput"
                    class="w-full bg-gray-800 border border-gray-700 rounded px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-purple-500 pr-10"
                    placeholder="Type to search approved CARs..."
                    autocomplete="off"
                    value="{{ $liquidation->cashAdvanceRequest ? ($liquidation->cashAdvanceRequest->car_no ?? '') : '' }}"
                />
                <span class="absolute right-4 top-3.5 text-gray-500">
                    <i class="fas fa-search"></i>
                </span>
                <div id="carSearchResults" class="hidden absolute z-10 w-full mt-2 bg-gray-800 border border-gray-700 rounded shadow-lg max-h-96 overflow-y-auto"></div>
            </div>

            <!-- Linked CAR Display -->
            <div id="linkedCARDisplay" class="mt-3">
                @if($liquidation->cashAdvanceRequest)
                    <div class="p-3 bg-green-900/20 border border-green-700 rounded text-green-300 flex justify-between items-center">
                        <span>
                            <i class="fas fa-link mr-2"></i>{{ $liquidation->cashAdvanceRequest->car_no }}
                            — {{ $liquidation->cashAdvanceRequest->payee ?? $liquidation->cashAdvanceRequest->name ?? '' }}
                            <span class="text-gray-400 ml-2">(CAR Amount: &#8369;{{ number_format($liquidation->cashAdvanceRequest->amount, 2) }})</span>
                        </span>
                        <button type="button" id="unlinkCAR" class="text-red-400 hover:text-red-300 text-sm"><i class="fas fa-times"></i></button>
                    </div>
                @else
                    <div class="p-3 bg-gray-800 border border-gray-700 rounded text-gray-400">
                        No CAR linked — use search above (optional)
                    </div>
                @endif
            </div>
        </div>

        <form action="{{ route('liquidation_forms.update', $liquidation->id) }}" method="POST" id="liquidationForm" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" name="cash_advance_request_id" id="cash_advance_request_id" value="{{ old('cash_advance_request_id', $liquidation->cash_advance_request_id) }}">

            <!-- Main Form Fields -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">NAME: <span class="text-red-400">*</span></label>
                    <input type="text" name="name" id="liqName" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('name', $liquidation->name) }}" required>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">DEPARTMENT: <span class="text-red-400">*</span></label>
                    <input type="text" name="department" id="liqDepartment" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('department', $liquidation->department) }}" required>
                </div>
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">DATE APPLIED: <span class="text-red-400">*</span></label>
                    <input type="date" name="date_applied" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('date_applied', $liquidation->date_applied->format('Y-m-d')) }}" required>
                </div>
            </div>

            <!-- Line Items Table -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-3">PARTICULARS / LINE ITEMS:</label>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse border border-gray-700" id="itemsTable">
                        <thead class="bg-gray-700 text-gray-300 uppercase text-sm">
                            <tr>
                                <th class="border border-gray-700 px-4 py-3" style="width: 5%;">#</th>
                                <th class="border border-gray-700 px-4 py-3" style="width: 65%;">Particulars</th>
                                <th class="border border-gray-700 px-4 py-3" style="width: 20%;">Amount</th>
                                <th class="border border-gray-700 px-4 py-3" style="width: 10%;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            @foreach($liquidation->items as $index => $item)
                                <tr>
                                    <td class="border border-gray-700 px-4 py-2 text-center text-gray-400">{{ $index + 1 }}</td>
                                    <td class="border border-gray-700 px-2 py-2">
                                        <input type="text" name="items[{{ $index }}][particulars]" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old("items.{$index}.particulars", $item->particulars) }}" placeholder="Enter particulars..." required>
                                    </td>
                                    <td class="border border-gray-700 px-2 py-2">
                                        <input type="number" step="0.01" name="items[{{ $index }}][amount]" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500 item-amount" value="{{ old("items.{$index}.amount", $item->amount) }}" placeholder="0.00" oninput="calculateTotal()" required>
                                    </td>
                                    <td class="border border-gray-700 px-2 py-2 text-center">
                                        <button type="button" onclick="removeRow(this)" class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700 transition">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-700">
                                <td colspan="2" class="border border-gray-700 px-4 py-3 text-right font-bold text-white uppercase">Total Amount Spent:</td>
                                <td class="border border-gray-700 px-4 py-3 text-right font-bold text-white text-lg">
                                    &#8369;<span id="totalDisplay">0.00</span>
                                </td>
                                <td class="border border-gray-700 px-4 py-3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <input type="hidden" name="total_amount_spent" id="totalAmountSpent" value="{{ old('total_amount_spent', $liquidation->total_amount_spent) }}">

                <div class="mt-3">
                    <button type="button" id="addRowBtn" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-4 py-2 rounded hover:from-purple-700 hover:to-purple-800 transition text-sm">
                        <i class="fas fa-plus mr-1"></i> Add Row
                    </button>
                </div>
            </div>

            <!-- Submitted By -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block font-semibold text-gray-300 mb-2">SUBMITTED BY:</label>
                    <input type="text" name="submitted_by" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" value="{{ old('submitted_by', $liquidation->submitted_by) }}">
                </div>
            </div>

            <!-- Proof Documents -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">PROOF DOCUMENTS:</label>

                @if($liquidation->proof_documents && count($liquidation->proof_documents) > 0)
                    <div class="mb-4 p-4 bg-gray-900 border border-gray-700 rounded">
                        <p class="text-gray-300 font-semibold mb-3">Currently uploaded documents:</p>
                        <ul class="list-disc list-inside text-gray-300 text-sm space-y-2">
                            @foreach($liquidation->proof_documents as $index => $doc)
                                <li>
                                    <a href="{{ asset('storage/' . $doc['path']) }}" target="_blank" class="text-purple-400 hover:text-purple-300">{{ $doc['name'] }}</a>
                                    <span class="text-gray-500">({{ number_format($doc['size'] / 1024, 2) }} KB)</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="bg-gray-900 border-2 border-dashed border-gray-700 rounded px-4 py-6 text-center">
                    <input type="file" name="proof_documents[]" id="proofDocuments" class="hidden" multiple accept=".doc,.docx,.odf,.jpg,.jpeg,.png,.gif,.bmp,.webp">
                    <label for="proofDocuments" class="cursor-pointer">
                        <div class="text-gray-400 mb-2">
                            <i class="fas fa-cloud-upload-alt text-2xl"></i>
                        </div>
                        <p class="text-white font-semibold mb-1">Click to upload or drag and drop</p>
                        <p class="text-gray-500 text-sm">Accepted formats: .doc, .docx, .odf, .jpg, .jpeg, .png, .gif, .bmp, .webp</p>
                    </label>
                </div>
                <div id="fileList" class="mt-3"></div>
            </div>

            <!-- Remarks -->
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">REMARKS (Optional):</label>
                <textarea name="remarks" rows="3" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Enter any additional remarks...">{{ old('remarks', $liquidation->remarks) }}</textarea>
            </div>

            <!-- Footer Note -->
            <div class="mb-6 p-3 bg-yellow-900/20 border border-yellow-700 rounded">
                <p class="text-yellow-300 text-sm"><i class="fas fa-paperclip mr-2"></i>Please attach invoices, Official receipts (OR) and other supporting documents.</p>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('liquidation_forms.show', $liquidation->id) }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-600 transition">
                    Cancel
                </a>
                <button type="submit" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-2 rounded hover:from-purple-700 hover:to-purple-800">
                    <i class="fas fa-save mr-1"></i> Update Liquidation Form
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== Dynamic Row Management =====
    let rowIndex = {{ count($liquidation->items) }};

    document.getElementById('addRowBtn').addEventListener('click', function() {
        const tbody = document.getElementById('itemsBody');
        const rowCount = tbody.querySelectorAll('tr').length + 1;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="border border-gray-700 px-4 py-2 text-center text-gray-400">${rowCount}</td>
            <td class="border border-gray-700 px-2 py-2">
                <input type="text" name="items[${rowIndex}][particulars]" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Enter particulars..." required>
            </td>
            <td class="border border-gray-700 px-2 py-2">
                <input type="number" step="0.01" name="items[${rowIndex}][amount]" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500 item-amount" placeholder="0.00" oninput="calculateTotal()" required>
            </td>
            <td class="border border-gray-700 px-2 py-2 text-center">
                <button type="button" onclick="removeRow(this)" class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700 transition">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
        rowIndex++;
    });

    // Calculate total on page load
    calculateTotal();

    // ===== CAR Search (AJAX with debounce) =====
    const carSearchInput = document.getElementById('carSearchInput');
    const carSearchResults = document.getElementById('carSearchResults');
    let debounceTimer;

    if (carSearchInput) {
        carSearchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const searchTerm = this.value.trim();

            if (searchTerm.length < 2) {
                carSearchResults.classList.add('hidden');
                return;
            }

            carSearchResults.innerHTML = '<div class="p-3 text-gray-400 text-center"><i class="fas fa-spinner fa-spin mr-2"></i>Searching...</div>';
            carSearchResults.classList.remove('hidden');

            debounceTimer = setTimeout(() => {
                fetch(`{{ route('liquidation_forms.search_cars') }}?search=${encodeURIComponent(searchTerm)}`)
                    .then(response => response.json())
                    .then(cars => {
                        if (cars.length === 0) {
                            carSearchResults.innerHTML = `
                                <div class="p-4 text-center">
                                    <div class="text-gray-400 mb-2">
                                        <i class="fas fa-inbox text-2xl mb-2"></i>
                                        <p>No approved CARs found matching "${searchTerm}"</p>
                                    </div>
                                </div>
                            `;
                            return;
                        }

                        let html = '<div class="divide-y divide-gray-700">';
                        cars.forEach(car => {
                            const dateFormatted = car.date ? new Date(car.date).toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'short',
                                day: 'numeric'
                            }) : '';

                            html += `
                                <div class="car-result-item block p-4 hover:bg-gray-700 transition cursor-pointer"
                                     data-id="${car.id}"
                                     data-car-no="${(car.car_no || '').replace(/"/g, '&quot;')}"
                                     data-payee="${(car.payee || car.name || '').replace(/"/g, '&quot;')}"
                                     data-department="${(car.department || '').replace(/"/g, '&quot;')}"
                                     data-amount="${car.amount || 0}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="font-semibold text-white mb-1">
                                                <i class="fas fa-file-invoice-dollar mr-2 text-purple-400"></i>${car.car_no}
                                            </div>
                                            <div class="text-sm text-gray-300">${car.payee || car.name || 'N/A'} &bull; ${car.department || 'N/A'}</div>
                                            <div class="text-xs text-gray-500 mt-1">
                                                <i class="far fa-calendar mr-1"></i>${dateFormatted}
                                            </div>
                                        </div>
                                        <div class="ml-4 text-right">
                                            <div class="text-sm text-green-400">&#8369; ${parseFloat(car.amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                                            <span class="px-3 py-1 bg-purple-900/30 border border-purple-700 text-purple-300 rounded text-xs mt-1 inline-block">
                                                Select <i class="fas fa-arrow-right ml-1"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        html += '</div>';
                        carSearchResults.innerHTML = html;

                        // Attach click handlers
                        carSearchResults.querySelectorAll('.car-result-item').forEach(item => {
                            item.addEventListener('click', function() {
                                const carId = this.dataset.id;
                                const carNo = this.dataset.carNo;
                                const payee = this.dataset.payee;
                                const department = this.dataset.department;
                                const amount = this.dataset.amount;

                                document.getElementById('cash_advance_request_id').value = carId;
                                document.getElementById('liqName').value = payee;
                                document.getElementById('liqDepartment').value = department;

                                document.getElementById('linkedCARDisplay').innerHTML = `
                                    <div class="p-3 bg-green-900/20 border border-green-700 rounded text-green-300 flex justify-between items-center">
                                        <span>
                                            <i class="fas fa-link mr-2"></i>${carNo}
                                            — ${payee}
                                            <span class="text-gray-400 ml-2">(CAR Amount: &#8369;${parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2})})</span>
                                        </span>
                                        <button type="button" id="unlinkCAR" class="text-red-400 hover:text-red-300 text-sm"><i class="fas fa-times"></i></button>
                                    </div>
                                `;
                                attachUnlinkHandler();

                                carSearchResults.classList.add('hidden');
                                carSearchInput.value = carNo;
                            });
                        });
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        carSearchResults.innerHTML = '<div class="p-3 text-red-400 text-center"><i class="fas fa-exclamation-triangle mr-2"></i>Error loading results</div>';
                    });
            }, 300);
        });

        document.addEventListener('click', function(e) {
            if (!carSearchInput.contains(e.target) && !carSearchResults.contains(e.target)) {
                carSearchResults.classList.add('hidden');
            }
        });

        carSearchInput.addEventListener('focus', function() {
            if (this.value.trim().length >= 2 && carSearchResults.innerHTML.trim() !== '') {
                carSearchResults.classList.remove('hidden');
            }
        });
    }

    // Unlink CAR handler
    function attachUnlinkHandler() {
        const unlinkBtn = document.getElementById('unlinkCAR');
        if (unlinkBtn) {
            unlinkBtn.addEventListener('click', function() {
                document.getElementById('cash_advance_request_id').value = '';
                document.getElementById('linkedCARDisplay').innerHTML = `
                    <div class="p-3 bg-gray-800 border border-gray-700 rounded text-gray-400">
                        No CAR linked — use search above (optional)
                    </div>
                `;
                carSearchInput.value = '';
            });
        }
    }
    attachUnlinkHandler();
});

// ===== Row Management & Total Calculation =====
function removeRow(btn) {
    const tbody = document.getElementById('itemsBody');
    if (tbody.querySelectorAll('tr').length > 1) {
        btn.closest('tr').remove();
        tbody.querySelectorAll('tr').forEach((tr, index) => {
            tr.querySelector('td:first-child').textContent = index + 1;
        });
        calculateTotal();
    } else {
        alert('At least one line item is required.');
    }
}

function calculateTotal() {
    let total = 0;
    document.querySelectorAll('.item-amount').forEach(input => {
        total += parseFloat(input.value) || 0;
    });
    document.getElementById('totalDisplay').textContent = total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('totalAmountSpent').value = total.toFixed(2);
}

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
