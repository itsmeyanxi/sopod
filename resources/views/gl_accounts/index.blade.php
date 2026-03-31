@extends('layouts.app')
@section('title', 'Chart of Accounts')

@section('content')
<div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
    {{-- Page Header --}}
    <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
        <h1 class="text-3xl font-bold">Chart of Accounts</h1>
        <div class="flex items-center gap-3">
            {{-- Import Button --}}
            <button type="button" id="import_modal_btn"
                class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-2 rounded font-medium transition flex items-center space-x-2">
                <i class="fas fa-upload"></i>
                <span>Import</span>
            </button>

            {{-- Export Button --}}
            <button type="button" onclick="exportList()"
                class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded font-medium transition flex items-center space-x-2">
                <i class="fas fa-file-excel"></i>
                <span>Export</span>
            </button>

            {{-- Create Button --}}
            <a href="{{ route('gl_accounts.create') }}"
                class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded font-medium transition flex items-center space-x-2">
                <i class="fas fa-plus"></i>
                <span>New GL Account</span>
            </a>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="bg-green-600 text-white px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    {{-- Search Section --}}
    <div class="bg-gray-700 rounded-lg p-4 mb-6">
        <div class="flex gap-3">
            <input type="text" id="search_input" placeholder="Search by Account Code or Name..."
                class="flex-1 bg-gray-800 text-white border border-gray-600 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="button" onclick="performSearch()"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded font-medium transition">
                <i class="fas fa-search mr-2"></i>Search
            </button>
            <button type="button" onclick="clearSearch()"
                class="bg-gray-200 hover:bg-gray-300 text-white px-6 py-2 rounded font-medium transition">
                Clear
            </button>
        </div>
    </div>

    {{-- GL Accounts Table --}}
    <div class="overflow-x-auto">
        <table class="min-w-full bg-gray-800 rounded-lg text-sm">
            <thead>
                <tr class="bg-gray-900 text-gray-500 text-xs">
                    <th class="px-3 py-3 text-left">Account Code</th>
                    <th class="px-3 py-3 text-left">Account Name</th>
                    <th class="px-3 py-3 text-left">FS Line Item</th>
                    <th class="px-3 py-3 text-left">FS Notes</th>
                    <th class="px-3 py-3 text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="gl_accounts_tbody" class="text-gray-500">
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                        <p>Loading GL accounts...</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- Import Modal --}}
<div id="import_modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg shadow-xl p-8 max-w-2xl w-full mx-4">
        {{-- Modal Header --}}
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold text-white">Import GL Accounts</h3>
            <button type="button" onclick="closeImportModal()" class="text-gray-500 hover:text-white text-2xl">×</button>
        </div>

        {{-- Info Callout --}}
        <div class="bg-blue-100 border border-blue-700 rounded-lg p-4 mb-6">
            <p class="text-blue-700 text-sm">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Required columns:</strong> Account Code, Account Name. Optional: FS Line Item, FS Notes.
            </p>
        </div>

        <form id="import_form" class="space-y-4">
            @csrf

            {{-- Drag-and-drop Zone --}}
            <div class="border-2 border-dashed border-gray-600 rounded-lg p-8 bg-gray-700/50 hover:bg-gray-700/70 transition cursor-pointer" id="drop_zone">
                <div class="text-center">
                    <i class="fas fa-cloud-upload-alt text-4xl text-gray-500 mb-3"></i>
                    <p class="text-white font-semibold mb-1">Drag and drop your file here</p>
                    <p class="text-gray-500 text-sm">or click to select a CSV/Excel file (max 10MB)</p>
                    <input type="file" id="import_file" name="file" accept=".csv,.xlsx,.xls" class="hidden" required>
                </div>
            </div>

            {{-- File Info --}}
            <div id="file_info" class="hidden bg-green-100 border border-green-200 rounded-lg p-3">
                <p class="text-green-700 text-sm">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span id="file_name">File selected</span>
                </p>
            </div>

            {{-- Modal Action Buttons --}}
            <div class="flex gap-3 pt-4 border-t border-gray-700">
                <button type="button" onclick="closeImportModal()"
                    class="flex-1 bg-gray-200 hover:bg-gray-300 text-white px-4 py-2 rounded font-medium transition">
                    Cancel
                </button>
                <button type="submit"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-medium transition flex items-center justify-center space-x-2">
                    <i class="fas fa-upload"></i>
                    <span>Import Now</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// ✅ Setup Import Modal
function setupImportModal() {
    document.getElementById('import_modal_btn').addEventListener('click', () => {
        document.getElementById('import_modal').classList.remove('hidden');
    });

    const dropZone = document.getElementById('drop_zone');
    const fileInput = document.getElementById('import_file');

    dropZone.addEventListener('click', () => fileInput.click());

    // Drag and drop
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.style.backgroundColor = '#4b5563';
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.style.backgroundColor = '';
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.style.backgroundColor = '';
        if (e.dataTransfer.files.length > 0) {
            fileInput.files = e.dataTransfer.files;
            updateFileInfo(e.dataTransfer.files[0]);
        }
    });

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            updateFileInfo(e.target.files[0]);
        }
    });

    document.getElementById('import_form').addEventListener('submit', (e) => {
        e.preventDefault();
        submitImport();
    });
}

function updateFileInfo(file) {
    document.getElementById('file_name').textContent = file.name;
    document.getElementById('file_info').classList.remove('hidden');
}

function closeImportModal() {
    document.getElementById('import_modal').classList.add('hidden');
    document.getElementById('import_file').value = '';
    document.getElementById('file_info').classList.add('hidden');
}

async function submitImport() {
    const fileInput = document.getElementById('import_file');
    if (!fileInput.files.length) {
        Swal.fire({
            icon: 'warning',
            title: 'No File Selected',
            text: 'Please select a file to import',
            background: '#1f2937',
            color: '#fff'
        });
        return;
    }

    const formData = new FormData();
    formData.append('file', fileInput.files[0]);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

    Swal.fire({
        title: 'Importing...',
        text: 'Processing your file',
        background: '#1f2937',
        color: '#fff',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const response = await fetch('/excel/import/gl-accounts', {
            method: 'POST',
            body: formData
        });

        Swal.close();

        // Check if response is OK
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Server error response:', errorText);
            Swal.fire({
                icon: 'error',
                title: 'Server Error',
                text: `Server returned status ${response.status}. Check browser console for details.`,
                background: '#1f2937',
                color: '#fff'
            });
            return;
        }

        const data = await response.json();

        if (data.success) {
            closeImportModal();
            let errorDetails = '';
            if (data.errors && data.errors.length > 0) {
                errorDetails = '<p style="color: #fca5a5; margin-top: 10px; text-align: left;"><strong>Issues:</strong><br>' +
                               data.errors.slice(0, 5).join('<br>') +
                               (data.errors.length > 5 ? '<br>... and ' + (data.errors.length - 5) + ' more' : '') +
                               '</p>';
            }
            Swal.fire({
                icon: 'success',
                title: 'Import Successful!',
                html: `<p>Imported: <strong>${data.imported}</strong></p>
                       <p>Updated: <strong>${data.updated}</strong></p>
                       <p>Skipped: <strong>${data.skipped}</strong></p>
                       ${errorDetails}`,
                background: '#1f2937',
                color: '#fff'
            }).then(() => {
                clearSearch();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Import Failed',
                text: data.message || 'An error occurred during import',
                background: '#1f2937',
                color: '#fff'
            });
        }
    } catch (error) {
        Swal.close();
        console.error('Import error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to import file: ' + error.message,
            background: '#1f2937',
            color: '#fff'
        });
    }
}

// ✅ Load GL Accounts
function loadGlAccounts(search = '') {
    const params = new URLSearchParams();
    if (search) params.append('search', search);

    fetch(`/accounting/gl-accounts/get?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('gl_accounts_tbody');
            tbody.innerHTML = '';

            if (!data.success || !data.accounts || data.accounts.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-2"></i>
                            <p>No GL accounts found</p>
                        </td>
                    </tr>
                `;
                return;
            }

            data.accounts.forEach(account => {
                const row = document.createElement('tr');
                row.className = 'border-b border-gray-700 hover:bg-gray-900';
                row.innerHTML = `
                    <td class="px-3 py-3 font-mono text-sm">${account.account_code}</td>
                    <td class="px-3 py-3 text-sm">${account.account_name}</td>
                    <td class="px-3 py-3 text-sm">${account.fs_line_item || '-'}</td>
                    <td class="px-3 py-3 text-sm">${account.fs_notes ? account.fs_notes.substring(0, 50) + '...' : '-'}</td>
                    <td class="px-3 py-3 text-center space-x-2">
                        <a href="/accounting/gl-accounts/${account.id}" class="text-blue-700 hover:text-blue-700 text-xs">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <a href="/accounting/gl-accounts/${account.id}/edit" class="text-purple-700 hover:text-purple-700 text-xs">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <button onclick="deleteAccount(${account.id})" class="text-red-700 hover:text-red-700 text-xs">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('gl_accounts_tbody').innerHTML = `
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-red-700">
                        <i class="fas fa-exclamation-circle text-2xl mb-2"></i>
                        <p>Failed to load GL accounts</p>
                    </td>
                </tr>
            `;
        });
}

function performSearch() {
    const search = document.getElementById('search_input').value.trim();
    loadGlAccounts(search);
}

function clearSearch() {
    document.getElementById('search_input').value = '';
    loadGlAccounts();
}

function exportList() {
    const search = document.getElementById('search_input').value.trim();
    const params = new URLSearchParams();
    if (search) params.append('search', search);

    Swal.fire({
        icon: 'info',
        title: 'Export Started',
        text: 'File will be downloaded shortly.',
        background: '#1f2937',
        color: '#fff',
        timer: 2000,
        showConfirmButton: false
    });

    window.location.href = `/accounting/gl-accounts/export?${params.toString()}`;
}

async function deleteAccount(id) {
    const result = await Swal.fire({
        title: 'Delete GL Account?',
        text: 'This action cannot be undone',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        background: '#1f2937',
        color: '#fff'
    });

    if (!result.isConfirmed) return;

    Swal.fire({
        title: 'Deleting...',
        background: '#1f2937',
        color: '#fff',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const response = await fetch(`/accounting/gl-accounts/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const data = await response.json();
        Swal.close();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Deleted!',
                text: 'GL account has been deleted.',
                background: '#1f2937',
                color: '#fff'
            }).then(() => {
                clearSearch();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to delete',
                background: '#1f2937',
                color: '#fff'
            });
        }
    } catch (error) {
        Swal.close();
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to delete GL account',
            background: '#1f2937',
            color: '#fff'
        });
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    setupImportModal();
    loadGlAccounts();

    // Allow Enter key to search
    document.getElementById('search_input').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') performSearch();
    });
});
</script>
@endsection
