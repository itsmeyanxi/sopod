@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="max-w-3xl mx-auto bg-gray-800 text-white p-8 rounded-lg mt-8 shadow-md">
    <h2 class="text-2xl font-bold mb-6">Edit User</h2>

    @if ($errors->any())
        <div class="bg-red-600 text-white p-3 rounded mb-4">
            <ul class="list-disc ml-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="block text-sm mb-1">Name</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}"
                   class="w-full bg-gray-700 border border-gray-600 rounded-lg p-2 text-white focus:ring-blue-500" required>
        </div>

        <div class="mb-4">
            <label class="block text-sm mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}"
                   class="w-full bg-gray-700 border border-gray-600 rounded-lg p-2 text-white focus:ring-blue-500" required>
        </div>

        <div class="mb-4">
            <label class="block text-sm mb-1">
                New Password
                <span class="text-gray-400 text-xs">(Leave blank to keep current password)</span>
            </label>
            <div class="relative">
                <input type="password"
                       name="password"
                       id="password"
                       class="w-full bg-gray-700 border border-gray-600 rounded-lg p-2 pr-12 text-white focus:ring-blue-500"
                       placeholder="Enter new password (min. 6 characters)">
                <button type="button"
                        id="togglePassword"
                        class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-gray-600 hover:bg-gray-500 text-white px-3 py-1 rounded">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <small class="text-gray-400 text-xs">Minimum 6 characters</small>
        </div>

        <div class="bg-yellow-900/30 border border-yellow-600/30 rounded-lg p-3 mb-4">
            <p class="text-yellow-300 text-sm"><i class="fas fa-info-circle mr-1"></i> To manage this user's module access and permissions, use the <a href="{{ route('rbac.index') }}" class="underline">RBAC Management</a> page.</p>
        </div>

        <div class="flex justify-end space-x-3 mt-6">
            <a href="{{ route('admin.users.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">Cancel</a>
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">Update User</button>
        </div>
    </form>
</div>

{{-- Module Access Overrides Panel --}}
@php
// Grouped nav structure: section => [ key => label ]
// Keys match what navAccess() checks in the nav blade.
// Module-level keys (sales_orders, customers, etc.) control section visibility.
// Sub-item keys (sales_orders.create, etc.) control individual links.
$navStructure = [
    'Dashboard' => [
        'po_dashboard' => 'PO Dashboard',
        'po_summary'   => 'PO Summary',
    ],
    'Sales Orders' => [
        'sales_orders'          => '📂 Section Access',
        'sales_orders.create'   => 'Create Order',
        'sales_orders.list'     => 'Order List',
        'sales_orders.accepted' => 'Accepted Orders',
    ],
    'Customers' => [
        'customers'        => '📂 Section Access',
        'customers.create' => 'Add Customer',
        'customers.list'   => 'Customer List',
    ],
    'Supply Chain' => [
        'suppliers'                      => '📂 Section Access (Suppliers)',
        'supply_chain.add_supplier'      => 'Add Supplier',
        'supply_chain.supplier_list'     => 'Supplier List',
        'supplier_rr'                    => '📂 Section Access (Supplier RR)',
        'supply_chain.receiving_reports' => 'Receiving Reports',
        'issue_slips'                    => '📂 Section Access (Issue Slips)',
        'supply_chain.issue_slips'       => 'Issue Slips',
        'purchase_requests'              => '📂 Section Access (PR)',
        'supply_chain.purchase_requests' => 'Purchase Request (PR)',
        'purchase_orders'                => '📂 Section Access (PO)',
        'supply_chain.purchase_orders'   => 'Purchase Order (PO)',
        'rfp'                            => '📂 Section Access (RFP)',
        'supply_chain.rfp'               => 'Request For Payment (RFP)',
        'non_trade_items'                => '📂 Section Access (Non-Trade)',
        'supply_chain.non_trade_items'   => 'Non-Trade Items Library',
        'trade_items'                    => '📂 Section Access (Trade)',
        'supply_chain.trade_items'       => 'Trade Items Library',
    ],
    'Storage / Warehouse' => [
        'warehouse' => '📂 Section Access',
    ],
    'Items' => [
        'items'        => '📂 Section Access',
        'items.create' => 'Add Item',
        'items.list'   => 'Item List',
    ],
    'Deliveries' => [
        'deliveries'      => '📂 Section Access',
        'deliveries.view' => 'View Delivery',
        'deliveries.list' => 'Delivery List',
    ],
    'Receiving Reports' => [
        'receiving_reports' => '📂 Section Access',
    ],
    'Finance' => [
        'purchase_requests' => 'Purchase Request (PR)',
        'purchase_orders'   => 'Purchase Order (PO)',
        'non_trade_items'   => 'Non-Trade Items Library',
        'trade_items'       => 'Trade Items Library',
        'rfp'               => 'Request For Payment (RFP)',
        'apv'               => 'APV',
        'cv'                => 'Check Voucher (CV)',
        'cash_advance'      => 'Cash Advance Request (CAR)',
        'liquidation'       => 'Liquidation Form (LIQ)',
        'reimbursement'     => 'Reimbursement Form (RI)',
        'currency_rates'    => 'Currency Rates',
    ],
    'Credits & Collection' => [
        'aging_reports'     => '📂 Section Access (Aging)',
        'aging.view'        => 'Aging Reports View',
        'ar_dashboard'      => 'AR Dashboard',
        'payments'          => 'Collection',
        'aging.adjustments' => 'AR Adjustments',
    ],
    'Accounting' => [
        'gl_accounts' => 'Chart of Accounts',
    ],
    'Other' => [
        'change_log'     => 'Change Log',
        'sales_analytics'=> 'Sales Analytics',
        'records'        => 'Records',
        'excel_import'   => 'Excel Import',
        'record_lock'    => 'Record Lock',
        'user_management'=> 'User Management',
    ],
];
@endphp

<div class="max-w-7xl mx-auto bg-gray-800 text-white p-6 rounded-lg mt-6 shadow-md">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-bold">Module Access Overrides</h3>
        <div class="flex gap-4 text-xs">
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-gray-500 inline-block"></span> Default</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block"></span> Grant</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span> Deny</span>
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 items-start" id="moduleOverridesGrid">
        @foreach($navStructure as $sectionName => $items)
        <div class="rounded-lg overflow-hidden border border-gray-700 flex flex-col">
            {{-- Section Header --}}
            <button type="button"
                onclick="toggleOverrideSection(this)"
                class="flex items-center justify-between px-3 py-2 bg-gray-700 hover:bg-gray-600 text-left w-full">
                <span class="text-xs font-semibold text-gray-200 uppercase tracking-wide">{{ $sectionName }}</span>
                <span class="toggle-chevron text-gray-400 text-xs">▼</span>
            </button>
            {{-- Items --}}
            <div class="section-content hidden flex-col divide-y divide-gray-700/50 bg-gray-750">
                @foreach($items as $key => $label)
                @php
                    $ov = $overrides->get($key);
                    $current = $ov ? ($ov->allowed ? 'grant' : 'deny') : 'default';
                    $isSection = str_starts_with($label, '📂');
                @endphp
                <div class="flex items-center justify-between px-3 py-1.5 {{ $isSection ? 'bg-gray-700/40' : 'bg-gray-800' }} gap-2">
                    <span class="text-xs truncate {{ $isSection ? 'text-gray-400 italic' : 'text-gray-300' }}" title="{{ $isSection ? ltrim(str_replace('📂', '', $label)) : $label }}">
                        {{ $isSection ? str_replace('📂 ', '', $label) : $label }}
                    </span>
                    <select
                        class="module-override-select flex-shrink-0 border text-xs rounded px-1 py-0.5 focus:outline-none focus:ring-1 focus:ring-indigo-500
                            {{ $current === 'grant' ? 'bg-green-800 border-green-600 text-green-200' : ($current === 'deny' ? 'bg-red-900 border-red-700 text-red-200' : 'bg-gray-600 border-gray-500 text-white') }}"
                        data-module="{{ $key }}"
                        data-user="{{ $user->id }}">
                        <option value="default" {{ $current === 'default' ? 'selected' : '' }}>Default</option>
                        <option value="grant"   {{ $current === 'grant'   ? 'selected' : '' }}>Grant</option>
                        <option value="deny"    {{ $current === 'deny'    ? 'selected' : '' }}>Deny</option>
                    </select>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>

    <p class="text-gray-500 text-xs mt-3">Changes are saved instantly. Click a section header to expand/collapse.</p>
</div>

<script>
function toggleOverrideSection(btn) {
    const content = btn.nextElementSibling;
    const isOpen  = !content.classList.contains('hidden');

    // Close all sections
    document.querySelectorAll('#moduleOverridesGrid .section-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('#moduleOverridesGrid .toggle-chevron').forEach(el => el.textContent = '▼');

    // Open clicked one (unless it was already open)
    if (!isOpen) {
        content.classList.remove('hidden');
        btn.querySelector('.toggle-chevron').textContent = '▲';
    }
}

document.querySelectorAll('.module-override-select').forEach(function(select) {
    // Color the select based on current value
    function updateColor(sel) {
        sel.classList.remove(
            'bg-green-800','border-green-600','text-green-200',
            'bg-red-900','border-red-700','text-red-200',
            'bg-gray-600','border-gray-500','text-white'
        );
        if (sel.value === 'grant') {
            sel.classList.add('bg-green-800','border-green-600','text-green-200');
        } else if (sel.value === 'deny') {
            sel.classList.add('bg-red-900','border-red-700','text-red-200');
        } else {
            sel.classList.add('bg-gray-600','border-gray-500','text-white');
        }
    }
    updateColor(select);

    select.addEventListener('change', function() {
        const module   = this.getAttribute('data-module');
        const userId   = this.getAttribute('data-user');
        const override = this.value;
        const sel      = this;

        updateColor(this);

        fetch(`/admin/users/${userId}/module-overrides`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({ module, override }),
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to save override.', background: '#1f2937', color: '#fff' });
            }
        })
        .catch(() => {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Request failed.', background: '#1f2937', color: '#fff' });
        });
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const togglePassword = document.getElementById('togglePassword');
    const passwordField = document.getElementById('password');
    const icon = togglePassword.querySelector('i');

    togglePassword.addEventListener('click', function() {
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordField.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
});

@if(session('error'))
    Swal.fire({
        icon: 'error',
        title: 'Error!',
        text: '{{ session('error') }}',
        showConfirmButton: true,
        background: '#1f2937',
        color: '#fff'
    });
@endif
</script>
@endsection
