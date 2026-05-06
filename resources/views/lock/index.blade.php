@extends('layouts.app')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="{{ asset('css/sales-dashboard.css') }}">

<div class="max-w-7xl mx-auto mt-10 bg-gray-900 text-gray-100 p-8 rounded-xl shadow-lg border border-gray-800">

    {{-- Header --}}
    <div class="flex justify-between items-center mb-8">
        <div>
            <h2 class="text-2xl font-bold text-white flex items-center gap-3">
                <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                Record Lock Management
            </h2>
            <p class="text-gray-300 mt-1">Lock Sales Orders, Deliveries, Customers, and Items by month to prevent modifications</p>
        </div>
        <div class="flex items-center gap-2 text-sm">
            <span class="px-3 py-1 bg-green-600/20 text-green-700 rounded-full border border-green-600">
                <span class="inline-block w-2 h-2 bg-green-500 rounded-full mr-1"></span>
                Unlocked
            </span>
            <span class="px-3 py-1 bg-red-600/20 text-red-700 rounded-full border border-red-600">
                <span class="inline-block w-2 h-2 bg-red-500 rounded-full mr-1"></span>
                Locked
            </span>
        </div>
    </div>

    {{-- Year / Month Filter (same style as Sales Analytics) --}}
    <form method="GET" action="{{ route('lock.index') }}" class="filter-form" style="margin-bottom: 1.5rem;">
        <div class="filter-group">
            <label class="filter-label">Year</label>
            <select name="year" class="filter-select">
                @for($i = 2020; $i <= date('Y') + 1; $i++)
                    <option value="{{ $i }}" {{ (isset($filterYear) && $filterYear == $i) ? 'selected' : '' }}>{{ $i }}</option>
                @endfor
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Month</label>
            <select name="month" class="filter-select">
                @for($i = 1; $i <= 12; $i++)
                    <option value="{{ $i }}" {{ (isset($filterMonth) && $filterMonth == $i) ? 'selected' : '' }}>
                        {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                    </option>
                @endfor
            </select>
        </div>
        <button type="submit" class="filter-button">Apply</button>
    </form>

    {{-- Quick Search --}}
    <div class="bg-gray-800 border border-gray-700 rounded-xl p-5 mb-6">
        <h4 class="text-white font-semibold mb-3 flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            Quick Search Delivery
        </h4>
        <div class="flex items-center gap-3">
            <input type="text" id="quickSearchInput" placeholder="Enter DR number, customer name, or SO number..."
                class="flex-1 bg-gray-900 border border-gray-700 rounded-lg px-4 py-2.5 text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <button onclick="quickSearchDelivery()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition">
                Search
            </button>
        </div>
        <div id="quickSearchResults" class="mt-4 hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-700 text-gray-300 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-2 text-left">DR No.</th>
                        <th class="px-4 py-2 text-left">SO No.</th>
                        <th class="px-4 py-2 text-left">Customer</th>
                        <th class="px-4 py-2 text-center">Delivery Date</th>
                        <th class="px-4 py-2 text-center">Status</th>
                        <th class="px-4 py-2 text-center">Lock Status</th>
                        <th class="px-4 py-2 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="quickSearchBody"></tbody>
            </table>
            <div id="quickSearchInfo" class="text-xs text-gray-400 mt-2"></div>
        </div>
    </div>

    {{-- Info Banner --}}
    <div class="bg-blue-100 border border-blue-700 rounded-lg p-4 mb-8">
        <div class="flex items-start gap-3">
            <svg class="w-6 h-6 text-blue-700 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <h4 class="text-blue-700 font-semibold">How Locking Works</h4>
                <ul class="text-sm text-blue-700 mt-2 space-y-1">
                    <li>• Locked records become <strong>view-only</strong> - no edits, deletions, or status changes allowed</li>
                    <li>• Locking is based on the record's <strong>creation month</strong></li>
                    <li>• Only <strong>Admin</strong> and <strong>IT</strong> roles can lock/unlock records</li>
                    <li>• Applies to: <strong>Sales Orders, Deliveries, Customers, Items</strong> (Purchase Orders and Suppliers are not affected)</li>
                    <li>• Unlocking requires confirmation to prevent accidental changes</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Months Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($monthsData as $monthData)
            @php
                $isFullyLocked = $monthData['is_fully_locked'];
                $hasLockedItems = $monthData['so_locked_count'] > 0 || $monthData['delivery_locked_count'] > 0
                    || $monthData['customer_locked_count'] > 0 || $monthData['item_locked_count'] > 0;
                $soPercentLocked = $monthData['so_count'] > 0 ? round(($monthData['so_locked_count'] / $monthData['so_count']) * 100) : 0;
                $deliveryPercentLocked = $monthData['delivery_count'] > 0 ? round(($monthData['delivery_locked_count'] / $monthData['delivery_count']) * 100) : 0;
                $customerPercentLocked = $monthData['customer_count'] > 0 ? round(($monthData['customer_locked_count'] / $monthData['customer_count']) * 100) : 0;
                $itemPercentLocked = $monthData['item_count'] > 0 ? round(($monthData['item_locked_count'] / $monthData['item_count']) * 100) : 0;
            @endphp

            <div class="bg-gray-800/60 border {{ $isFullyLocked ? 'border-red-600' : ($hasLockedItems ? 'border-yellow-600' : 'border-gray-700') }} rounded-xl p-5 hover:bg-gray-800/80 transition-all">

                {{-- Month Header --}}
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-white">{{ $monthData['month_name'] }}</h3>
                        <p class="text-xs text-gray-300">{{ $monthData['year'] }}-{{ str_pad($monthData['month'], 2, '0', STR_PAD_LEFT) }}</p>
                    </div>
                    @if($isFullyLocked)
                        <span class="px-3 py-1 bg-red-600/20 text-red-700 text-xs font-semibold rounded-full border border-red-600 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                            </svg>
                            LOCKED
                        </span>
                    @elseif($hasLockedItems)
                        <span class="px-3 py-1 bg-yellow-600/20 text-yellow-700 text-xs font-semibold rounded-full border border-yellow-600">
                            PARTIAL
                        </span>
                    @else
                        <span class="px-3 py-1 bg-green-600/20 text-green-700 text-xs font-semibold rounded-full border border-green-600 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2H7V7a3 3 0 015.905-.75 1 1 0 001.937-.5A5.002 5.002 0 0010 2z"></path>
                            </svg>
                            OPEN
                        </span>
                    @endif
                </div>

                {{-- Stats --}}
                <div class="space-y-4 mb-5">
                    {{-- Sales Orders --}}
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm text-gray-300">Sales Orders</span>
                            <span class="text-sm font-semibold {{ $monthData['so_locked_count'] > 0 ? 'text-red-700' : 'text-green-700' }}">
                                {{ $monthData['so_locked_count'] }} / {{ $monthData['so_count'] }} locked
                            </span>
                        </div>
                        <div class="w-full bg-gray-700 rounded-full h-2">
                            <div class="h-2 rounded-full transition-all duration-300 {{ $soPercentLocked == 100 ? 'bg-red-500' : ($soPercentLocked > 0 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                 style="width: {{ $soPercentLocked }}%"></div>
                        </div>
                    </div>

                    {{-- Deliveries --}}
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm text-gray-300">Deliveries</span>
                            <span class="text-sm font-semibold {{ $monthData['delivery_locked_count'] > 0 ? 'text-red-700' : 'text-green-700' }}">
                                {{ $monthData['delivery_locked_count'] }} / {{ $monthData['delivery_count'] }} locked
                            </span>
                        </div>
                        <div class="w-full bg-gray-700 rounded-full h-2">
                            <div class="h-2 rounded-full transition-all duration-300 {{ $deliveryPercentLocked == 100 ? 'bg-red-500' : ($deliveryPercentLocked > 0 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                 style="width: {{ $deliveryPercentLocked }}%"></div>
                        </div>
                        {{-- Delivery Status Breakdown --}}
                        @if(!empty($monthData['delivery_status_counts']))
                            <div class="mt-2 flex flex-wrap gap-2">
                                @php
                                    $statusColors = [
                                        'Delivered' => 'bg-green-600/20 text-green-700 border-green-600',
                                        'Pending' => 'bg-yellow-600/20 text-yellow-700 border-yellow-600',
                                        'Cancelled' => 'bg-red-600/20 text-red-700 border-red-600',
                                    ];
                                @endphp
                                @foreach($monthData['delivery_status_counts'] as $status => $count)
                                    <span class="px-2 py-0.5 text-xs font-medium rounded border {{ $statusColors[$status] ?? 'bg-gray-700 text-gray-300 border-gray-600' }}">
                                        {{ $status }}: {{ $count }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Customers --}}
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm text-gray-300">Customers</span>
                            <span class="text-sm font-semibold {{ $monthData['customer_locked_count'] > 0 ? 'text-red-700' : 'text-green-700' }}">
                                {{ $monthData['customer_locked_count'] }} / {{ $monthData['customer_count'] }} locked
                            </span>
                        </div>
                        <div class="w-full bg-gray-700 rounded-full h-2">
                            <div class="h-2 rounded-full transition-all duration-300 {{ $customerPercentLocked == 100 ? 'bg-red-500' : ($customerPercentLocked > 0 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                 style="width: {{ $customerPercentLocked }}%"></div>
                        </div>
                    </div>

                    {{-- Items --}}
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm text-gray-300">Items</span>
                            <span class="text-sm font-semibold {{ $monthData['item_locked_count'] > 0 ? 'text-red-700' : 'text-green-700' }}">
                                {{ $monthData['item_locked_count'] }} / {{ $monthData['item_count'] }} locked
                            </span>
                        </div>
                        <div class="w-full bg-gray-700 rounded-full h-2">
                            <div class="h-2 rounded-full transition-all duration-300 {{ $itemPercentLocked == 100 ? 'bg-red-500' : ($itemPercentLocked > 0 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                 style="width: {{ $itemPercentLocked }}%"></div>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex gap-2">
                    <button onclick="viewDetails({{ $monthData['year'] }}, {{ $monthData['month'] }}, '{{ $monthData['month_name'] }}')"
                            class="flex-1 bg-gray-700 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        View
                    </button>

                    @if(!$isFullyLocked)
                        <button onclick="lockMonth({{ $monthData['year'] }}, {{ $monthData['month'] }}, '{{ $monthData['month_name'] }}', {{ $monthData['so_count'] }}, {{ $monthData['delivery_count'] }}, {{ $monthData['customer_count'] }}, {{ $monthData['item_count'] }})"
                                class="flex-1 bg-red-600 hover:bg-red-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            Lock
                        </button>
                    @endif
                    @if($hasLockedItems)
                        <button onclick="unlockMonth({{ $monthData['year'] }}, {{ $monthData['month'] }}, '{{ $monthData['month_name'] }}')"
                                class="flex-1 bg-green-600 hover:bg-green-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                            </svg>
                            Unlock
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="text-gray-300 text-lg">No records found</p>
                <p class="text-gray-300 text-sm mt-1">Create Sales Orders, Deliveries, Customers, or Items to see them here</p>
            </div>
        @endforelse
    </div>
</div>

{{-- Details Modal --}}
<div id="detailsModal" class="fixed inset-0 bg-black/70 z-50 hidden items-center justify-center p-4">
    <div class="bg-gray-900 border border-gray-700 rounded-xl shadow-2xl max-w-5xl w-full max-h-[90vh] overflow-hidden">
        {{-- Modal Header --}}
        <div class="bg-gray-800 px-6 py-4 border-b border-gray-700 flex justify-between items-center">
            <h3 id="modalTitle" class="text-xl font-bold text-white">Month Details</h3>
            <button onclick="closeModal()" class="text-gray-300 hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        {{-- Modal Body --}}
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
            {{-- Tabs --}}
            <div class="flex gap-2 mb-6 flex-wrap">
                <button id="tabSO" onclick="switchTab('so')" class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium transition-all">
                    Sales Orders
                </button>
                <button id="tabDelivery" onclick="switchTab('delivery')" class="px-4 py-2 bg-gray-700 text-gray-300 rounded-lg font-medium transition-all hover:bg-gray-700">
                    Deliveries
                </button>
                <button id="tabCustomer" onclick="switchTab('customer')" class="px-4 py-2 bg-gray-700 text-gray-300 rounded-lg font-medium transition-all hover:bg-gray-700">
                    Customers
                </button>
                <button id="tabItem" onclick="switchTab('item')" class="px-4 py-2 bg-gray-700 text-gray-300 rounded-lg font-medium transition-all hover:bg-gray-700">
                    Items
                </button>
            </div>

            {{-- Sales Orders Table --}}
            <div id="soTable" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-800 text-gray-300 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3 text-left">SO Number</th>
                            <th class="px-4 py-3 text-left">Customer</th>
                            <th class="px-4 py-3 text-right">Total Amount</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-center">Lock Status</th>
                            <th class="px-4 py-3 text-center">Created</th>
                        </tr>
                    </thead>
                    <tbody id="soTableBody" class="divide-y divide-gray-700">
                        <tr><td colspan="6" class="text-center py-8 text-gray-300">Loading...</td></tr>
                    </tbody>
                </table>
            </div>

            {{-- Deliveries Table --}}
            <div id="deliveryTable" class="overflow-x-auto hidden">
                <div class="flex items-center gap-3 mb-3">
                    <input type="text" id="deliverySearchInput" placeholder="Search DR number..."
                        class="bg-gray-800 border border-gray-700 rounded px-3 py-2 text-sm text-white focus:ring focus:ring-blue-500 w-64">
                    <button onclick="searchDeliveryDR()" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-sm font-medium">Search</button>
                    <button onclick="clearDeliverySearch()" class="bg-gray-700 hover:bg-gray-600 text-gray-300 px-3 py-2 rounded text-sm">Clear</button>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-800 text-gray-300 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3 text-left">DR Number</th>
                            <th class="px-4 py-3 text-left">SO Number</th>
                            <th class="px-4 py-3 text-left">Customer</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-center">Lock Status</th>
                            <th class="px-4 py-3 text-center">Created</th>
                            <th class="px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="deliveryTableBody" class="divide-y divide-gray-700">
                        <tr><td colspan="7" class="text-center py-8 text-gray-300">Loading...</td></tr>
                    </tbody>
                </table>
            </div>

            {{-- Customers Table --}}
            <div id="customerTable" class="overflow-x-auto hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-800 text-gray-300 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3 text-left">Customer Code</th>
                            <th class="px-4 py-3 text-left">Customer Name</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-center">Lock Status</th>
                            <th class="px-4 py-3 text-center">Created</th>
                        </tr>
                    </thead>
                    <tbody id="customerTableBody" class="divide-y divide-gray-700">
                        <tr><td colspan="5" class="text-center py-8 text-gray-300">Loading...</td></tr>
                    </tbody>
                </table>
            </div>

            {{-- Items Table --}}
            <div id="itemTable" class="overflow-x-auto hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-800 text-gray-300 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3 text-left">Item Code</th>
                            <th class="px-4 py-3 text-left">Description</th>
                            <th class="px-4 py-3 text-center">Approval Status</th>
                            <th class="px-4 py-3 text-center">Lock Status</th>
                            <th class="px-4 py-3 text-center">Created</th>
                        </tr>
                    </thead>
                    <tbody id="itemTableBody" class="divide-y divide-gray-700">
                        <tr><td colspan="5" class="text-center py-8 text-gray-300">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

// Track current modal state
let modalState = { year: null, month: null, activeTab: 'so', pages: {} };

function viewDetails(year, month, monthName) {
    document.getElementById('modalTitle').textContent = monthName + ' - Details';
    document.getElementById('detailsModal').classList.remove('hidden');
    document.getElementById('detailsModal').classList.add('flex');

    modalState.year = year;
    modalState.month = month;
    modalState.pages = { so: 1, delivery: 1, customer: 1, item: 1 };

    // Load the default tab
    switchTab('so');
}

function fetchTabData(tab, page = 1) {
    const tbodyIds = { so: 'soTableBody', delivery: 'deliveryTableBody', customer: 'customerTableBody', item: 'itemTableBody' };
    const colSpans = { so: 6, delivery: 7, customer: 5, item: 5 };
    const tbody = document.getElementById(tbodyIds[tab]);

    tbody.innerHTML = `<tr><td colspan="${colSpans[tab]}" class="text-center py-8 text-gray-400">Loading...</td></tr>`;

    fetch(`{{ route('lock.details') }}?year=${modalState.year}&month=${modalState.month}&tab=${tab}&page=${page}`, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) return;
        modalState.pages[tab] = page;
        const rows = data.data || [];
        const populators = { so: populateSOTable, delivery: populateDeliveryTable, customer: populateCustomerTable, item: populateItemTable };
        populators[tab](rows);
        renderPagination(tab, page, data.total_pages, data.total);
    })
    .catch(err => {
        console.error('Error:', err);
        tbody.innerHTML = `<tr><td colspan="${colSpans[tab]}" class="text-center py-8 text-red-400">Failed to load data</td></tr>`;
    });
}

function renderPagination(tab, currentPage, totalPages, total) {
    const containerId = tab + 'Pagination';
    let container = document.getElementById(containerId);
    if (!container) {
        const tableDiv = document.getElementById(tab + 'Table');
        container = document.createElement('div');
        container.id = containerId;
        container.className = 'flex items-center justify-between mt-3 px-1';
        tableDiv.appendChild(container);
    }

    if (totalPages <= 1) {
        container.innerHTML = `<span class="text-xs text-gray-400">${total} record${total !== 1 ? 's' : ''}</span><span></span>`;
        return;
    }

    let buttons = '';
    // Previous
    buttons += `<button onclick="fetchTabData('${tab}', ${currentPage - 1})" ${currentPage <= 1 ? 'disabled' : ''} class="px-3 py-1 rounded text-sm ${currentPage <= 1 ? 'bg-gray-700 text-gray-500 cursor-not-allowed' : 'bg-gray-700 hover:bg-gray-600 text-white'}">Prev</button>`;

    // Page numbers (show max 5 around current)
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    for (let i = startPage; i <= endPage; i++) {
        buttons += `<button onclick="fetchTabData('${tab}', ${i})" class="px-3 py-1 rounded text-sm ${i === currentPage ? 'bg-blue-600 text-white' : 'bg-gray-700 hover:bg-gray-600 text-gray-300'}">${i}</button>`;
    }

    // Next
    buttons += `<button onclick="fetchTabData('${tab}', ${currentPage + 1})" ${currentPage >= totalPages ? 'disabled' : ''} class="px-3 py-1 rounded text-sm ${currentPage >= totalPages ? 'bg-gray-700 text-gray-500 cursor-not-allowed' : 'bg-gray-700 hover:bg-gray-600 text-white'}">Next</button>`;

    container.innerHTML = `<span class="text-xs text-gray-400">${total} record${total !== 1 ? 's' : ''} &middot; Page ${currentPage} of ${totalPages}</span><div class="flex gap-1">${buttons}</div>`;
}

function populateSOTable(salesOrders) {
    const tbody = document.getElementById('soTableBody');
    if (salesOrders.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-300">No Sales Orders found</td></tr>';
        return;
    }
    tbody.innerHTML = salesOrders.map(so => `
        <tr class="hover:bg-gray-800/50">
            <td class="px-4 py-3 font-mono text-blue-700">${so.sales_order_number}</td>
            <td class="px-4 py-3">${so.customer?.customer_name || '—'}</td>
            <td class="px-4 py-3 text-right font-mono">₱${parseFloat(so.total_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
            <td class="px-4 py-3 text-center"><span class="px-2 py-1 rounded text-xs font-medium ${getStatusClass(so.status)}">${so.status || '—'}</span></td>
            <td class="px-4 py-3 text-center">${lockBadge(so.is_locked)}</td>
            <td class="px-4 py-3 text-center text-gray-400">${formatDate(so.created_at)}</td>
        </tr>
    `).join('');
}

function populateDeliveryTable(deliveries) {
    const tbody = document.getElementById('deliveryTableBody');
    if (deliveries.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-gray-300">No Deliveries found</td></tr>';
        return;
    }
    tbody.innerHTML = deliveries.map(d => `
        <tr class="hover:bg-gray-800/50" id="delivery-row-${d.id}">
            <td class="px-4 py-3 font-mono text-green-700">${d.dr_no || '—'}</td>
            <td class="px-4 py-3 font-mono text-blue-700">${d.sales_order_number || '—'}</td>
            <td class="px-4 py-3">${d.customer_name || '—'}</td>
            <td class="px-4 py-3 text-center"><span class="px-2 py-1 rounded text-xs font-medium ${getStatusClass(d.status)}">${d.status || '—'}</span></td>
            <td class="px-4 py-3 text-center" id="delivery-lock-${d.id}">${lockBadge(d.is_locked)}</td>
            <td class="px-4 py-3 text-center text-gray-400">${formatDate(d.created_at)}</td>
            <td class="px-4 py-3 text-center">
                ${d.is_locked
                    ? `<button onclick="unlockSingleDelivery(${d.id}, '${d.dr_no}')" class="bg-green-600 hover:bg-green-500 text-white px-3 py-1 rounded text-xs font-medium transition">Unlock</button>`
                    : `<span class="text-xs text-gray-500">—</span>`}
            </td>
        </tr>
    `).join('');
}

function populateCustomerTable(customers) {
    const tbody = document.getElementById('customerTableBody');
    if (customers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-gray-300">No Customers found</td></tr>';
        return;
    }
    tbody.innerHTML = customers.map(c => `
        <tr class="hover:bg-gray-800/50">
            <td class="px-4 py-3 font-mono text-purple-700">${c.customer_code || '—'}</td>
            <td class="px-4 py-3">${c.customer_name || '—'}</td>
            <td class="px-4 py-3 text-center"><span class="px-2 py-1 rounded text-xs font-medium ${c.status === 'enabled' ? 'bg-green-600/20 text-green-700 border border-green-600' : 'bg-gray-700 text-gray-300 border border-gray-600'}">${c.status || '—'}</span></td>
            <td class="px-4 py-3 text-center">${lockBadge(c.is_locked)}</td>
            <td class="px-4 py-3 text-center text-gray-400">${formatDate(c.created_at)}</td>
        </tr>
    `).join('');
}

function populateItemTable(items) {
    const tbody = document.getElementById('itemTableBody');
    if (items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-gray-300">No Items found</td></tr>';
        return;
    }
    tbody.innerHTML = items.map(i => `
        <tr class="hover:bg-gray-800/50">
            <td class="px-4 py-3 font-mono text-orange-700">${i.item_code || '—'}</td>
            <td class="px-4 py-3">${i.item_description || '—'}</td>
            <td class="px-4 py-3 text-center"><span class="px-2 py-1 rounded text-xs font-medium ${getApprovalClass(i.approval_status)}">${i.approval_status || '—'}</span></td>
            <td class="px-4 py-3 text-center">${lockBadge(i.is_locked)}</td>
            <td class="px-4 py-3 text-center text-gray-400">${formatDate(i.created_at)}</td>
        </tr>
    `).join('');
}

function lockBadge(isLocked) {
    return isLocked
        ? '<span class="px-2 py-1 bg-red-600/20 text-red-700 rounded text-xs font-medium border border-red-600">LOCKED</span>'
        : '<span class="px-2 py-1 bg-green-600/20 text-green-700 rounded text-xs font-medium border border-green-600">OPEN</span>';
}

function getStatusClass(status) {
    const classes = {
        'Approved': 'bg-green-600/20 text-green-700 border border-green-600',
        'Pending': 'bg-yellow-600/20 text-yellow-700 border border-yellow-600',
        'Declined': 'bg-red-600/20 text-red-700 border border-red-600',
        'Delivered': 'bg-green-600/20 text-green-700 border border-green-600',
        'Cancelled': 'bg-red-600/20 text-red-700 border border-red-600',
    };
    return classes[status] || 'bg-gray-700 text-gray-300 border border-gray-600';
}

function getApprovalClass(status) {
    const classes = {
        'approved': 'bg-green-600/20 text-green-700 border border-green-600',
        'pending': 'bg-yellow-600/20 text-yellow-700 border border-yellow-600',
        'rejected': 'bg-red-600/20 text-red-700 border border-red-600',
    };
    return classes[status] || 'bg-gray-700 text-gray-300 border border-gray-600';
}

function formatDate(dateString) {
    if (!dateString) return '—';
    return new Date(dateString).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function switchTab(tab) {
    const tabs = ['so', 'delivery', 'customer', 'item'];
    tabs.forEach(t => {
        const btn = document.getElementById('tab' + t.charAt(0).toUpperCase() + t.slice(1));
        const table = document.getElementById(t + 'Table');
        if (t === tab) {
            btn.classList.remove('bg-gray-700', 'text-gray-300');
            btn.classList.add('bg-blue-600', 'text-white');
            table.classList.remove('hidden');
        } else {
            btn.classList.remove('bg-blue-600', 'text-white');
            btn.classList.add('bg-gray-700', 'text-gray-300');
            table.classList.add('hidden');
        }
    });
    modalState.activeTab = tab;
    // Fetch data for the selected tab
    fetchTabData(tab, modalState.pages[tab] || 1);
}

function closeModal() {
    document.getElementById('detailsModal').classList.add('hidden');
    document.getElementById('detailsModal').classList.remove('flex');
}

function lockMonth(year, month, monthName, soCount, deliveryCount, customerCount, itemCount) {
    Swal.fire({
        icon: 'warning',
        title: `Lock ${monthName}?`,
        html: `
            <div class="text-left">
                <p class="mb-3">This will lock <strong>all records</strong> for this month:</p>
                <ul class="space-y-2 text-sm">
                    <li class="flex items-center gap-2">
                        <span class="w-4 h-4 bg-blue-500 rounded"></span>
                        <strong>${soCount}</strong> Sales Order(s)
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="w-4 h-4 bg-green-500 rounded"></span>
                        <strong>${deliveryCount}</strong> Delivery/ies
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="w-4 h-4 bg-purple-500 rounded"></span>
                        <strong>${customerCount}</strong> Customer(s)
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="w-4 h-4 bg-orange-500 rounded"></span>
                        <strong>${itemCount}</strong> Item(s)
                    </li>
                </ul>
                <p class="mt-4 text-sm text-gray-300">Locked records become view-only and cannot be modified.</p>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Yes, Lock All',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`{{ route('lock.lock') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ year, month })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Locked!', text: data.message }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to lock records.' });
            });
        }
    });
}

function unlockMonth(year, month, monthName) {
    Swal.fire({
        icon: 'warning',
        title: `Unlock ${monthName}?`,
        html: `
            <div class="text-left">
                <p class="mb-3">This will <strong>unlock all records</strong> for this month.</p>
                <p class="text-sm text-orange-500">Warning: Unlocked records can be modified by authorized users.</p>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Yes, Unlock All',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#16a34a',
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`{{ route('lock.unlock') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ year, month })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Unlocked!', text: data.message }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to unlock records.' });
            });
        }
    });
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});

// Close modal on backdrop click
document.getElementById('detailsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

// Unlock a single delivery
function unlockSingleDelivery(id, drNo) {
    Swal.fire({
        icon: 'question',
        title: 'Unlock Delivery?',
        html: `<p>Unlock DR <strong>${drNo}</strong>?</p><p class="text-sm text-gray-400 mt-2">This will allow it to appear in the collections module.</p>`,
        showCancelButton: true,
        confirmButtonText: 'Yes, Unlock',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#16a34a',
        background: '#1f2937',
        color: '#f9fafb',
    }).then((result) => {
        if (!result.isConfirmed) return;

        fetch(`/lock/unlock-delivery/${id}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Unlocked!', text: data.message, timer: 1500, showConfirmButton: false, background: '#1f2937', color: '#f9fafb' });
                // Update the row in-place
                const lockCell = document.getElementById('delivery-lock-' + id);
                if (lockCell) lockCell.innerHTML = lockBadge(false);
                const row = document.getElementById('delivery-row-' + id);
                if (row) {
                    const actionsCell = row.querySelector('td:last-child');
                    if (actionsCell) actionsCell.innerHTML = '<span class="text-xs text-gray-500">—</span>';
                }
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message, background: '#1f2937', color: '#f9fafb' });
            }
        })
        .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to unlock delivery.', background: '#1f2937', color: '#f9fafb' }));
    });
}

// Search delivery by DR number within the modal
function searchDeliveryDR() {
    const search = document.getElementById('deliverySearchInput').value.trim();
    if (!search) return;

    const tbody = document.getElementById('deliveryTableBody');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-gray-400">Searching...</td></tr>';

    fetch(`{{ route('lock.details') }}?year=${modalState.year}&month=${modalState.month}&tab=delivery&search=${encodeURIComponent(search)}&page=1`, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            populateDeliveryTable(data.data || []);
            renderPagination('delivery', 1, data.total_pages, data.total);
        }
    })
    .catch(() => {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-red-400">Search failed</td></tr>';
    });
}

function clearDeliverySearch() {
    document.getElementById('deliverySearchInput').value = '';
    fetchTabData('delivery', 1);
}

// Enter key on delivery search
document.getElementById('deliverySearchInput')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); searchDeliveryDR(); }
});

// Quick search delivery from main page
function quickSearchDelivery() {
    const search = document.getElementById('quickSearchInput').value.trim();
    if (!search) return;

    const resultsDiv = document.getElementById('quickSearchResults');
    const tbody = document.getElementById('quickSearchBody');
    const info = document.getElementById('quickSearchInfo');

    resultsDiv.classList.remove('hidden');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-gray-400">Searching...</td></tr>';

    fetch(`{{ route('lock.searchDeliveries') }}?search=${encodeURIComponent(search)}`, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success || !data.data.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-gray-400">No deliveries found.</td></tr>';
            info.textContent = '';
            return;
        }

        tbody.innerHTML = data.data.map(d => `
            <tr class="border-b border-gray-700 hover:bg-gray-700/50" id="qs-row-${d.id}">
                <td class="px-4 py-2.5 font-mono text-green-400">${d.dr_no || '—'}</td>
                <td class="px-4 py-2.5 font-mono text-blue-400">${d.sales_order_number || '—'}</td>
                <td class="px-4 py-2.5 text-white">${d.customer_name || '—'}</td>
                <td class="px-4 py-2.5 text-center text-gray-300">${d.delivery_date ? new Date(d.delivery_date).toLocaleDateString('en-US', {month:'short', day:'numeric', year:'numeric'}) : '—'}</td>
                <td class="px-4 py-2.5 text-center"><span class="px-2 py-1 rounded text-xs font-medium ${getStatusClass(d.status)}">${d.status || '—'}</span></td>
                <td class="px-4 py-2.5 text-center" id="qs-lock-${d.id}">${lockBadge(d.is_locked)}</td>
                <td class="px-4 py-2.5 text-center" id="qs-action-${d.id}">
                    ${d.is_locked
                        ? `<button onclick="quickUnlock(${d.id}, '${d.dr_no}')" class="bg-green-600 hover:bg-green-500 text-white px-3 py-1 rounded text-xs font-medium transition">Unlock</button>`
                        : `<button onclick="quickLockOne(${d.id}, '${d.dr_no}')" class="bg-red-600 hover:bg-red-500 text-white px-3 py-1 rounded text-xs font-medium transition">Lock</button>`}
                </td>
            </tr>
        `).join('');

        info.textContent = `Found ${data.total} result${data.total !== 1 ? 's' : ''}${data.total >= 50 ? ' (showing first 50)' : ''}`;
    })
    .catch(() => {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-red-400">Search failed.</td></tr>';
    });
}

function quickUnlock(id, drNo) {
    Swal.fire({
        icon: 'question',
        title: 'Unlock Delivery?',
        html: `<p>Unlock DR <strong>${drNo}</strong>?</p><p class="text-sm text-gray-400 mt-2">This will allow it to appear in the collections module.</p>`,
        showCancelButton: true, confirmButtonText: 'Yes, Unlock', confirmButtonColor: '#16a34a',
        background: '#1f2937', color: '#f9fafb',
    }).then(result => {
        if (!result.isConfirmed) return;
        fetch(`/lock/unlock-delivery/${id}`, {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        }).then(r => r.json()).then(data => {
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Unlocked!', text: data.message, timer: 1500, showConfirmButton: false, background: '#1f2937', color: '#f9fafb' });
                document.getElementById('qs-lock-' + id).innerHTML = lockBadge(false);
                document.getElementById('qs-action-' + id).innerHTML = `<button onclick="quickLockOne(${id}, '${drNo}')" class="bg-red-600 hover:bg-red-500 text-white px-3 py-1 rounded text-xs font-medium transition">Lock</button>`;
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message, background: '#1f2937', color: '#f9fafb' });
            }
        }).catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to unlock.', background: '#1f2937', color: '#f9fafb' }));
    });
}

function quickLockOne(id, drNo) {
    Swal.fire({
        icon: 'warning',
        title: 'Lock Delivery?',
        html: `<p>Lock DR <strong>${drNo}</strong>?</p><p class="text-sm text-gray-400 mt-2">This will prevent it from appearing in the collections module.</p>`,
        showCancelButton: true, confirmButtonText: 'Yes, Lock', confirmButtonColor: '#dc2626',
        background: '#1f2937', color: '#f9fafb',
    }).then(result => {
        if (!result.isConfirmed) return;
        fetch(`/lock/lock-delivery/${id}`, {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        }).then(r => r.json()).then(data => {
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Locked!', text: data.message, timer: 1500, showConfirmButton: false, background: '#1f2937', color: '#f9fafb' });
                document.getElementById('qs-lock-' + id).innerHTML = lockBadge(true);
                document.getElementById('qs-action-' + id).innerHTML = `<button onclick="quickUnlock(${id}, '${drNo}')" class="bg-green-600 hover:bg-green-500 text-white px-3 py-1 rounded text-xs font-medium transition">Unlock</button>`;
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message, background: '#1f2937', color: '#f9fafb' });
            }
        }).catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to lock.', background: '#1f2937', color: '#f9fafb' }));
    });
}

document.getElementById('quickSearchInput')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); quickSearchDelivery(); }
});

</script>
@endsection
