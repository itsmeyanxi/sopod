@extends('layouts.app')
@section('title', 'Fixed Asset — ' . ($asset->asset_code ?? $asset->asset_description))

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold">FIXED ASSET DETAILS</h1>
            <div class="flex items-center gap-3">
                @if($asset->disposal_date)
                    <span class="bg-red-100 text-red-700 px-3 py-1 rounded text-sm font-semibold">Disposed</span>
                @elseif($asset->remaining_life_months <= 0)
                    <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded text-sm font-semibold">Fully Depreciated</span>
                @else
                    <span class="bg-green-100 text-green-700 px-3 py-1 rounded text-sm font-semibold">Active</span>
                @endif
                <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded text-sm">{{ $asset->asset_group }}</span>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 text-green-800 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif

        <!-- Asset Identification -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div>
                <label class="block font-semibold text-gray-500 mb-1">Asset Code:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded font-mono font-bold text-blue-700">{{ $asset->asset_code ?: 'N/A' }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-500 mb-1">Serial / Engine No.:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded">{{ $asset->serial_engine_no ?: 'N/A' }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-500 mb-1">Plate No.:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded">{{ $asset->plate_no ?: 'N/A' }}</p>
            </div>
        </div>

        <!-- Description -->
        <div class="mb-6">
            <label class="block font-semibold text-gray-500 mb-1">Asset Description:</label>
            <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded">{{ $asset->asset_description }}</p>
        </div>

        <!-- Classification -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div>
                <label class="block font-semibold text-gray-500 mb-1">Asset Group:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded">{{ $asset->asset_group }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-500 mb-1">Asset Class:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded">{{ $asset->asset_class ?: 'N/A' }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-500 mb-1">Depreciation Type:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded">{{ $asset->dep_type }}</p>
            </div>
        </div>

        <!-- Dates -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div>
                <label class="block font-semibold text-gray-500 mb-1">Date Posted:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded">{{ $asset->date_posted?->format('M d, Y') ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-500 mb-1">Acquisition Date:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded">{{ $asset->acquisition_date?->format('M d, Y') ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-500 mb-1">Dep. Start Date:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded">{{ $asset->dep_start_date?->format('M d, Y') ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-500 mb-1">Dep. End Date:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded">{{ $asset->dep_end_date?->format('M d, Y') ?? 'N/A' }}</p>
            </div>
        </div>

        @if($asset->disposal_date)
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded">
            <h3 class="font-semibold text-red-700 mb-2"><i class="fas fa-ban mr-1"></i> Disposal Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-gray-500">Disposal Date</p>
                    <p class="font-semibold text-red-700">{{ $asset->disposal_date->format('M d, Y') }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Disposal Amount</p>
                    <p class="font-semibold text-red-700">{{ number_format($asset->disposal_amount, 2) }}</p>
                </div>
            </div>
        </div>
        @endif

        <!-- Financial Information -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-5 mb-6">
            <h3 class="font-semibold text-blue-800 mb-4"><i class="fas fa-calculator mr-1"></i> Financial Information</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <p class="text-xs text-gray-500 mb-1">Cost</p>
                    <p class="text-lg font-bold text-white">{{ number_format($asset->cost, 2) }}</p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-500 mb-1">Accum. Depreciation</p>
                    <p class="text-lg font-bold text-orange-700">{{ number_format($asset->accumulated_depreciation, 2) }}</p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-500 mb-1">Net Book Value</p>
                    <p class="text-lg font-bold text-green-700">{{ number_format($asset->net_book_value, 2) }}</p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-500 mb-1">Salvage Value</p>
                    <p class="text-lg font-bold text-gray-300">{{ number_format($asset->salvage_value, 2) }}</p>
                </div>
            </div>
        </div>

        <!-- Depreciation Schedule -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div>
                <label class="block font-semibold text-gray-500 mb-1">Useful Life (Years):</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded">{{ $asset->useful_life_years }} yrs ({{ $asset->useful_life_months }} months)</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-500 mb-1">Remaining Life:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded font-semibold {{ $asset->remaining_life_months > 0 ? 'text-blue-700' : 'text-red-700' }}">{{ $asset->remaining_life_months }} months</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-500 mb-1">Monthly Depreciation:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded">{{ number_format($asset->monthly_depreciation, 2) }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-500 mb-1">Yearly Depreciation:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded">{{ number_format($asset->yearly_depreciation, 2) }}</p>
            </div>
        </div>

        <!-- Accounting Information -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div>
                <label class="block font-semibold text-gray-500 mb-1">GL Account:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded">{{ $asset->gl_account ?: 'N/A' }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-500 mb-1">Depreciation Account:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded">{{ $asset->depreciation_account ?: 'N/A' }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-500 mb-1">Cost Center:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded">{{ $asset->cost_center_name ?: 'N/A' }} {{ $asset->cost_center_code ? '(' . $asset->cost_center_code . ')' : '' }}</p>
            </div>
        </div>

        <!-- Assignment -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div>
                <label class="block font-semibold text-gray-500 mb-1">Assigned Person:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded">{{ $asset->assigned_person ?: 'N/A' }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-500 mb-1">Employee (Accountability):</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded">{{ $asset->employee_name ?: 'N/A' }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-500 mb-1">Vendor:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded">{{ $asset->vendor_name ?: 'N/A' }}</p>
            </div>
        </div>

        <!-- References -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block font-semibold text-gray-500 mb-1">Reference APV/JV:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded">{{ $asset->reference_apv_jv ?: 'N/A' }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-500 mb-1">Reference (Reversal):</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded">{{ $asset->reference_reversal ?: 'N/A' }}</p>
            </div>
        </div>

        <!-- Audit -->
        <div class="bg-gray-700 rounded-lg p-4 mb-6">
            <h3 class="font-semibold text-gray-500 mb-3">Audit Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-500">
                <div>
                    <label class="font-semibold">Created By:</label>
                    <p>{{ $asset->created_by ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="font-semibold">Created:</label>
                    <p>{{ $asset->created_at?->format('M d, Y h:i A') }}</p>
                </div>
                <div>
                    <label class="font-semibold">Last Updated:</label>
                    <p>{{ $asset->updated_at?->format('M d, Y h:i A') }}</p>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-4">
            <a href="{{ route('fixed_assets.index') }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-600 transition">
                <i class="fas fa-arrow-left mr-1"></i> Back to List
            </a>
            @if(!$asset->disposal_date)
                <a href="{{ route('fixed_assets.edit', $asset->id) }}" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
                    <i class="fas fa-edit mr-1"></i> Edit
                </a>
                <button onclick="document.getElementById('dispose-modal').classList.remove('hidden')" class="bg-orange-600 text-white px-6 py-2 rounded hover:bg-orange-700 transition">
                    <i class="fas fa-archive mr-1"></i> Dispose
                </button>
            @endif
            <form action="{{ route('fixed_assets.destroy', $asset->id) }}" method="POST" style="display:inline" onsubmit="return confirm('Delete this asset?')">
                @csrf @method('DELETE')
                <button class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 transition">
                    <i class="fas fa-trash mr-1"></i> Delete
                </button>
            </form>
        </div>

        <!-- DISPOSE MODAL -->
        <div id="dispose-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-gray-800 rounded-lg shadow-2xl w-full max-w-lg mx-4">
                <div class="bg-red-600 text-white px-6 py-4 border-b">
                    <h3 class="text-lg font-bold"><i class="fas fa-archive mr-2"></i> Dispose Asset</h3>
                    <p class="text-red-100 text-sm mt-1">This action will permanently move the asset to the Disposal Module</p>
                </div>
                <form action="{{ route('fixed_assets.dispose', $asset->id) }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block font-semibold text-gray-200 mb-2">Asset</label>
                        <p class="px-4 py-2 bg-gray-700 rounded border border-gray-600">{{ $asset->asset_description }}</p>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-200 mb-2">Disposal Date *</label>
                        <input type="date" name="disposal_date" value="{{ date('Y-m-d') }}" required
                               class="w-full px-3 py-2 border border-gray-600 rounded focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-200 mb-2">Disposal Amount *</label>
                        <div class="text-xs text-gray-300 mb-2">
                            Suggested: <strong>₱{{ number_format($asset->net_book_value, 2) }}</strong> (Net Book Value)
                        </div>
                        <input type="number" name="disposal_amount" id="disposal-amount" step="0.01" min="0" value="{{ $asset->net_book_value }}" required
                               class="w-full px-3 py-2 border border-gray-600 rounded focus:outline-none focus:border-blue-500"
                               placeholder="Enter proceeds or salvage value">
                        <p class="text-xs text-gray-500 mt-1">You can edit this amount if different from net book value</p>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-200 mb-2">Gain/Loss GL Account</label>
                        @include('partials.gl_account_selector', [
                            'field'      => 'gain_loss_gl_account',
                            'label'      => '',
                            'uid'        => 'disposal_gl',
                            'value'      => '703000002',
                            'glAccounts' => $glAccounts,
                        ])
                        <p class="text-xs text-gray-500 mt-1">Default: 703000002 — Gain/Loss on Retirement of Fixed Assets</p>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-200 mb-2">Reason for Disposal *</label>
                        <textarea name="disposal_reason" required rows="4"
                                  class="w-full px-3 py-2 border border-gray-600 rounded focus:outline-none focus:border-blue-500"
                                  placeholder="e.g., Irreparable, Obsolete, Buy-out, etc."></textarea>
                    </div>
                    <div class="flex gap-3 justify-end pt-4 border-t">
                        <button type="button" onclick="document.getElementById('dispose-modal').classList.add('hidden')"
                                class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-600 font-semibold">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 font-semibold">
                            <i class="fas fa-check mr-1"></i> Confirm Disposal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@include('partials.gl_account_selector_js')
@endsection
