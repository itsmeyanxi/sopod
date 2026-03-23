@extends('layouts.app')

@section('title', 'Supplier Details')

@section('content')
<div class="container mx-auto">
    <div class="bg-white text-gray-800 rounded-lg shadow-lg p-6">
        <!-- Action Buttons -->
        <div class="flex justify-between items-center mb-6">
            <a href="{{ route('suppliers.index') }}" class="bg-gray-100 text-gray-800 px-4 py-2 rounded hover:bg-gray-100 transition">
                <i class="fas fa-arrow-left mr-1"></i> Back to List
            </a>
            <div class="flex gap-2">
                <a href="{{ route('suppliers.edit', $supplier->id) }}" class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700 transition">
                    <i class="fas fa-edit mr-1"></i> Edit
                </a>
                <form action="{{ route('suppliers.toggleStatus', $supplier->id) }}" method="POST" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="bg-{{ $supplier->status === 'active' ? 'gray' : 'green' }}-600 text-gray-800 px-4 py-2 rounded hover:bg-{{ $supplier->status === 'active' ? 'gray' : 'green' }}-700 transition">
                        <i class="fas fa-{{ $supplier->status === 'active' ? 'ban' : 'check' }} mr-1"></i> {{ $supplier->status === 'active' ? 'Deactivate' : 'Activate' }}
                    </button>
                </form>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-600 text-white px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <!-- Supplier Details -->
        <div class="bg-gray-50 border border-gray-200 rounded p-6">
            <div class="flex justify-between items-start mb-6">
                <h1 class="text-3xl font-bold text-gray-800">{{ $supplier->supplier_name }}</h1>
                @if($supplier->status === 'active')
                    <span class="px-3 py-1 bg-green-900 text-green-300 rounded text-sm">Active</span>
                @else
                    <span class="px-3 py-1 bg-red-900 text-red-300 rounded text-sm">Inactive</span>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Basic Information -->
                <div class="space-y-4">
                    <h3 class="text-xl font-semibold text-purple-400 mb-4">Basic Information</h3>

                    <div>
                        <label class="text-gray-400 text-sm">Supplier Code</label>
                        <p class="text-gray-800 text-lg font-semibold">{{ $supplier->supplier_code }}</p>
                    </div>

                    <div>
                        <label class="text-gray-400 text-sm">Supplier Name</label>
                        <p class="text-gray-800 text-lg">{{ $supplier->supplier_name }}</p>
                    </div>

                    <div>
                        <label class="text-gray-400 text-sm">Address</label>
                        <p class="text-gray-800">{{ $supplier->address ?? 'N/A' }}</p>
                    </div>

                    <div>
                        <label class="text-gray-400 text-sm">TIN</label>
                        <p class="text-gray-800">{{ $supplier->tin ?? 'N/A' }}</p>
                    </div>

                    <div>
                        <label class="text-gray-400 text-sm">Storage / Warehouse</label>
                        <p class="text-gray-800">{{ $supplier->storage ?? 'N/A' }}</p>
                    </div>
                </div>

                <!-- Contact & Bank Information -->
                <div class="space-y-4">
                    <h3 class="text-xl font-semibold text-purple-400 mb-4">Contact Information</h3>

                    <div>
                        <label class="text-gray-400 text-sm">Payment Terms</label>
                        <p class="text-gray-800">{{ $supplier->terms ?? 'N/A' }}</p>
                    </div>

                    <div>
                        <label class="text-gray-400 text-sm">Contact Person</label>
                        <p class="text-gray-800">{{ $supplier->contact_person ?? 'N/A' }}</p>
                    </div>

                    <div>
                        <label class="text-gray-400 text-sm">Email Addresses</label>
                        <p class="text-gray-800">{{ $supplier->email ?? 'N/A' }}</p>
                    </div>

                    <div>
                        <label class="text-gray-400 text-sm">Contact Number</label>
                        <p class="text-gray-800">{{ $supplier->contact_number ?? 'N/A' }}</p>
                    </div>

                    <div class="mt-6">
                        <h3 class="text-xl font-semibold text-purple-400 mb-4">Bank Information (Primary)</h3>

                        <div class="mb-3">
                            <label class="text-gray-400 text-sm">Bank</label>
                            <p class="text-gray-800">{{ $supplier->bank ?? 'N/A' }}</p>
                        </div>

                        <div class="mb-3">
                            <label class="text-gray-400 text-sm">Account Name</label>
                            <p class="text-gray-800">{{ $supplier->account_name ?? 'N/A' }}</p>
                        </div>

                        <div>
                            <label class="text-gray-400 text-sm">Account Number</label>
                            <p class="text-gray-800">{{ $supplier->account_number ?? 'N/A' }}</p>
                        </div>
                    </div>

                    @if($supplier->bank2 || $supplier->account_name2 || $supplier->account_number2)
                    <div class="mt-6">
                        <h3 class="text-xl font-semibold text-purple-400 mb-4">Bank Information (Secondary)</h3>

                        <div class="mb-3">
                            <label class="text-gray-400 text-sm">Bank</label>
                            <p class="text-gray-800">{{ $supplier->bank2 ?? 'N/A' }}</p>
                        </div>

                        <div class="mb-3">
                            <label class="text-gray-400 text-sm">Account Name</label>
                            <p class="text-gray-800">{{ $supplier->account_name2 ?? 'N/A' }}</p>
                        </div>

                        <div>
                            <label class="text-gray-400 text-sm">Account Number</label>
                            <p class="text-gray-800">{{ $supplier->account_number2 ?? 'N/A' }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Metadata -->
            <div class="mt-6 pt-6 border-t border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <label class="text-gray-400">Created By</label>
                        <p class="text-gray-500">{{ $supplier->creator->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-gray-400">Created At</label>
                        <p class="text-gray-500">{{ $supplier->created_at->format('F d, Y h:i A') }}</p>
                    </div>
                    @if($supplier->updated_at && $supplier->updated_at != $supplier->created_at)
                    <div>
                        <label class="text-gray-400">Last Updated</label>
                        <p class="text-gray-500">{{ $supplier->updated_at->format('F d, Y h:i A') }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- =================== BUSINESS DOCUMENTS =================== -->
        <div class="bg-gray-50 border border-gray-200 rounded p-6 mt-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-purple-400">
                    <i class="fas fa-folder-open mr-2"></i>Business Documents
                </h3>
                <button type="button" onclick="document.getElementById('uploadModal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition text-sm">
                    <i class="fas fa-upload mr-1"></i> Upload Documents
                </button>
            </div>

            @if($supplier->documents->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($supplier->documents as $document)
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <!-- Preview -->
                            <div class="mb-3">
                                @if($document->isImage())
                                    <a href="{{ route('suppliers.viewDocument', [$supplier->id, $document->id]) }}" target="_blank">
                                        <img src="{{ asset('storage/' . $document->file_path) }}" alt="{{ $document->document_name }}" class="w-full h-40 object-cover rounded cursor-pointer hover:opacity-80 transition">
                                    </a>
                                @elseif($document->isPdf())
                                    <div class="w-full h-40 bg-gray-100 rounded flex flex-col items-center justify-center">
                                        <i class="fas fa-file-pdf text-red-400 text-5xl mb-2"></i>
                                        <span class="text-gray-400 text-sm">PDF Document</span>
                                    </div>
                                @else
                                    <div class="w-full h-40 bg-gray-100 rounded flex flex-col items-center justify-center">
                                        <i class="fas fa-file-alt text-blue-400 text-5xl mb-2"></i>
                                        <span class="text-gray-400 text-sm">{{ strtoupper(pathinfo($document->original_filename, PATHINFO_EXTENSION)) }} File</span>
                                    </div>
                                @endif
                            </div>

                            <!-- Document Info -->
                            <div class="mb-2">
                                <p class="text-gray-800 font-semibold text-sm truncate" title="{{ $document->document_name }}">{{ $document->document_name }}</p>
                                <p class="text-gray-400 text-xs truncate" title="{{ $document->original_filename }}">{{ $document->original_filename }}</p>
                                <p class="text-gray-500 text-xs mt-1">{{ $document->getFileSizeFormatted() }} &bull; {{ $document->created_at->format('M d, Y') }}</p>
                                @if($document->uploader)
                                    <p class="text-gray-500 text-xs">Uploaded by: {{ $document->uploader->name }}</p>
                                @endif
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-2 mt-3">
                                @if($document->isImage())
                                    <a href="{{ route('suppliers.viewDocument', [$supplier->id, $document->id]) }}" target="_blank" class="flex-1 bg-green-600 text-white text-center px-3 py-1.5 rounded text-sm hover:bg-green-700 transition">
                                        <i class="fas fa-eye mr-1"></i> View
                                    </a>
                                @endif
                                <a href="{{ route('suppliers.downloadDocument', [$supplier->id, $document->id]) }}" class="flex-1 bg-blue-600 text-white text-center px-3 py-1.5 rounded text-sm hover:bg-blue-700 transition">
                                    <i class="fas fa-download mr-1"></i> Download
                                </a>
                                <form action="{{ route('suppliers.deleteDocument', [$supplier->id, $document->id]) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this document?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-600 text-white px-3 py-1.5 rounded text-sm hover:bg-red-700 transition">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <i class="fas fa-folder-open text-gray-600 text-5xl mb-3"></i>
                    <p class="text-gray-400">No business documents uploaded yet.</p>
                    <p class="text-gray-500 text-sm mt-1">Click "Upload Documents" to add business permits, BIR certificates, etc.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- =================== UPLOAD MODAL =================== -->
<div id="uploadModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-lg mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800"><i class="fas fa-upload mr-2"></i>Upload Business Documents</h3>
            <button type="button" onclick="document.getElementById('uploadModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-800 text-xl">&times;</button>
        </div>

        <form action="{{ route('suppliers.uploadDocuments', $supplier->id) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div id="fileInputs">
                <div class="file-row mb-4 bg-gray-50 border border-gray-200 rounded p-3">
                    <div class="mb-2">
                        <label class="block text-gray-500 text-sm mb-1">Document Label:</label>
                        <input type="text" name="document_names[]" class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="e.g., Business Permit, BIR Certificate, DTI...">
                    </div>
                    <div>
                        <label class="block text-gray-500 text-sm mb-1">File:</label>
                        <input type="file" name="documents[]" class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-800 text-sm" accept=".png,.jpg,.jpeg,.gif,.pdf,.doc,.docx,.xls,.xlsx" required>
                    </div>
                    <p class="text-gray-500 text-xs mt-1">Accepted: PNG, JPG, PDF, DOC, DOCX, XLS, XLSX (max 10MB)</p>
                </div>
            </div>

            <button type="button" onclick="addFileInput()" class="mb-4 text-blue-400 hover:text-blue-300 text-sm">
                <i class="fas fa-plus mr-1"></i> Add Another Document
            </button>

            <div class="flex justify-end gap-3 mt-4 border-t border-gray-200 pt-4">
                <button type="button" onclick="document.getElementById('uploadModal').classList.add('hidden')" class="bg-gray-100 text-gray-800 px-4 py-2 rounded hover:bg-gray-100 transition">
                    Cancel
                </button>
                <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition">
                    <i class="fas fa-upload mr-1"></i> Upload
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function addFileInput() {
    const container = document.getElementById('fileInputs');
    const div = document.createElement('div');
    div.className = 'file-row mb-4 bg-gray-50 border border-gray-200 rounded p-3 relative';
    div.innerHTML = `
        <button type="button" onclick="this.parentElement.remove()" class="absolute top-2 right-2 text-red-400 hover:text-red-300 text-sm">
            <i class="fas fa-times"></i>
        </button>
        <div class="mb-2">
            <label class="block text-gray-500 text-sm mb-1">Document Label:</label>
            <input type="text" name="document_names[]" class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="e.g., Business Permit, BIR Certificate, DTI...">
        </div>
        <div>
            <label class="block text-gray-500 text-sm mb-1">File:</label>
            <input type="file" name="documents[]" class="w-full bg-white border border-gray-200 rounded px-3 py-2 text-gray-800 text-sm" accept=".png,.jpg,.jpeg,.gif,.pdf,.doc,.docx,.xls,.xlsx" required>
        </div>
        <p class="text-gray-500 text-xs mt-1">Accepted: PNG, JPG, PDF, DOC, DOCX, XLS, XLSX (max 10MB)</p>
    `;
    container.appendChild(div);
}
</script>
@endsection
