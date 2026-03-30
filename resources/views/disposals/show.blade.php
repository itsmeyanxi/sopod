@extends('layouts.app')

@section('title', $asset->asset_code ?? 'Disposed Asset')

@section('content')
<style>
    .info-grid { display:grid; grid-template-columns:repeat(2, 1fr); gap:2rem; }
    .info-row { display:flex; gap:1rem; }
    .info-label { font-weight:600; color:#6b7280; min-width:180px; }
    .info-value { color:#111827; font-weight:500; }
    .badge { display:inline-block; padding:.25rem .75rem; border-radius:9999px; font-size:.75rem; font-weight:600; }
    .badge-disposed { background:#fee2e2; color:#991b1b; }
</style>

<!-- HEADER -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">{{ $asset->asset_description }}</h1>
        <p class="text-gray-600 mt-1">Asset Code: <strong>{{ $asset->asset_code ?? 'N/A' }}</strong></p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('disposals.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-semibold">
            ← Back to Disposals
        </a>
    </div>
</div>

<!-- STATUS CARD -->
<div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
    <div class="flex items-center gap-3">
        <i class="fas fa-archive text-red-600 text-2xl"></i>
        <div>
            <div class="font-bold text-gray-900">Asset Disposed</div>
            <div class="text-sm text-gray-700">This asset has been permanently disposed and removed from active capitalization.</div>
        </div>
        <span class="badge badge-disposed ml-auto">DISPOSED</span>
    </div>
</div>

<!-- CONTENT -->
<div class="grid grid-cols-3 gap-6">
    <!-- LEFT COLUMN: ASSET DETAILS -->
    <div class="col-span-2">
        <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">Asset Information</h2>
            <div class="info-grid">
                <div>
                    <div class="info-row">
                        <div class="info-label">Asset Group</div>
                        <div class="info-value">{{ $asset->asset_group }}</div>
                    </div>
                    <div class="info-row mt-3">
                        <div class="info-label">Asset Class</div>
                        <div class="info-value">{{ $asset->asset_class ?? '—' }}</div>
                    </div>
                    <div class="info-row mt-3">
                        <div class="info-label">Serial/Engine No.</div>
                        <div class="info-value font-mono">{{ $asset->serial_engine_no ?? '—' }}</div>
                    </div>
                    <div class="info-row mt-3">
                        <div class="info-label">Plate No.</div>
                        <div class="info-value">{{ $asset->plate_no ?? '—' }}</div>
                    </div>
                </div>
                <div>
                    <div class="info-row">
                        <div class="info-label">Assigned To</div>
                        <div class="info-value">{{ $asset->assigned_person ?? '—' }}</div>
                    </div>
                    <div class="info-row mt-3">
                        <div class="info-label">Cost Center</div>
                        <div class="info-value">{{ $asset->cost_center_name ?? '—' }}</div>
                    </div>
                    <div class="info-row mt-3">
                        <div class="info-label">GL Account</div>
                        <div class="info-value">{{ $asset->gl_account ?? '—' }}</div>
                    </div>
                    <div class="info-row mt-3">
                        <div class="info-label">Vendor</div>
                        <div class="info-value">{{ $asset->vendor_name ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FINANCIAL DETAILS -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">Financial Details</h2>
            <div class="info-grid">
                <div>
                    <div class="info-row">
                        <div class="info-label">Acquisition Date</div>
                        <div class="info-value">{{ $asset->acquisition_date ? $asset->acquisition_date->format('M d, Y') : '—' }}</div>
                    </div>
                    <div class="info-row mt-3">
                        <div class="info-label">Original Cost</div>
                        <div class="info-value font-bold text-lg">₱{{ number_format($asset->cost, 2) }}</div>
                    </div>
                    <div class="info-row mt-3">
                        <div class="info-label">Useful Life</div>
                        <div class="info-value">{{ $asset->useful_life_years }} years ({{ $asset->useful_life_months }} months)</div>
                    </div>
                    <div class="info-row mt-3">
                        <div class="info-label">Depreciation Type</div>
                        <div class="info-value">{{ $asset->dep_type }}</div>
                    </div>
                </div>
                <div>
                    <div class="info-row">
                        <div class="info-label">Annual Depreciation</div>
                        <div class="info-value">₱{{ number_format($asset->yearly_depreciation, 2) }}</div>
                    </div>
                    <div class="info-row mt-3">
                        <div class="info-label">Accumulated Depreciation</div>
                        <div class="info-value font-bold">₱{{ number_format($asset->accumulated_depreciation, 2) }}</div>
                    </div>
                    <div class="info-row mt-3">
                        <div class="info-label">Net Book Value</div>
                        <div class="info-value font-bold text-lg">₱{{ number_format($asset->net_book_value, 2) }}</div>
                    </div>
                    <div class="info-row mt-3">
                        <div class="info-label">Salvage Value</div>
                        <div class="info-value">₱{{ number_format($asset->salvage_value, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- DISPOSAL DETAILS -->
        <div class="bg-red-50 border border-red-200 rounded-lg p-6">
            <h2 class="text-lg font-bold text-red-900 mb-4">Disposal Information</h2>
            <div class="info-grid">
                <div>
                    <div class="info-row">
                        <div class="info-label">Disposal Date</div>
                        <div class="info-value font-bold">{{ $asset->disposal_date ? $asset->disposal_date->format('M d, Y') : '—' }}</div>
                    </div>
                    <div class="info-row mt-3">
                        <div class="info-label">Disposed By</div>
                        <div class="info-value">{{ $asset->disposed_by ?? '—' }}</div>
                    </div>
                </div>
                <div>
                    <div class="info-row">
                        <div class="info-label">Disposal Amount</div>
                        <div class="info-value font-bold text-lg text-green-700">₱{{ number_format($asset->disposal_amount, 2) }}</div>
                    </div>
                </div>
            </div>
            @if($asset->disposal_reason)
            <div class="mt-4 pt-4 border-t border-red-200">
                <div class="font-semibold text-red-900 mb-2">Disposal Reason</div>
                <div class="text-red-800 text-sm leading-relaxed">{{ $asset->disposal_reason }}</div>
            </div>
            @endif
        </div>
    </div>

    <!-- RIGHT COLUMN: SUMMARY -->
    <div>
        <div class="bg-white border border-gray-200 rounded-lg p-6 sticky top-4">
            <h3 class="font-bold text-gray-900 mb-4">Summary</h3>

            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Status</span>
                    <span class="badge badge-disposed">DISPOSED</span>
                </div>

                <div class="border-t pt-3">
                    <div class="flex justify-between font-semibold">
                        <span>Original Cost</span>
                        <span>₱{{ number_format($asset->cost, 2) }}</span>
                    </div>
                </div>

                <div class="flex justify-between text-gray-600">
                    <span>Less: Accumulated Depreciation</span>
                    <span>₱{{ number_format($asset->accumulated_depreciation, 2) }}</span>
                </div>

                <div class="border-t pt-3 flex justify-between font-bold">
                    <span>Net Book Value</span>
                    <span>₱{{ number_format($asset->net_book_value, 2) }}</span>
                </div>

                <div class="border-t pt-3">
                    <div class="flex justify-between font-semibold text-green-700">
                        <span>Disposal Proceeds</span>
                        <span>₱{{ number_format($asset->disposal_amount, 2) }}</span>
                    </div>
                </div>

                @php $nbv_at_disposal = $asset->cost - $asset->accumulated_depreciation; @endphp
                @php $gain_loss = $asset->disposal_amount - $nbv_at_disposal; @endphp
                @if($gain_loss != 0)
                <div class="border-t pt-3">
                    <div class="flex justify-between font-semibold {{ $gain_loss > 0 ? 'text-green-700' : 'text-red-700' }}">
                        <span>{{ $gain_loss > 0 ? 'Gain' : 'Loss' }} on Disposal</span>
                        <span>{{ $gain_loss < 0 ? '(' : '' }}₱{{ number_format(abs($gain_loss), 2) }}{{ $gain_loss < 0 ? ')' : '' }}</span>
                    </div>
                    @if($asset->gain_loss_gl_account)
                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                        <span>GL Account</span>
                        <span class="font-mono">{{ $asset->gain_loss_gl_account }}</span>
                    </div>
                    @endif
                </div>
                @endif
            </div>

            <div class="mt-6 pt-6 border-t">
                <p class="text-xs text-gray-500 text-center">
                    Record Date: {{ $asset->created_at->format('M d, Y H:i') }}
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
