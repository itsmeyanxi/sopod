@extends('layouts.app')

@section('title', 'Goods Receipt PO')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6 max-w-4xl mx-auto">

        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <div>
                <h1 class="text-2xl font-bold">Goods Receipt PO</h1>
                @if($record->grpo_no)
                    <span class="text-purple-300 font-mono text-lg">{{ $record->grpo_no }}</span>
                @endif
            </div>
            <div class="flex gap-2 flex-wrap justify-end">
                <a href="{{ route('live_chickens.print', $record->id) }}" target="_blank"
                   class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded text-sm font-semibold">
                    <i class="fas fa-print mr-1"></i> Print GRPO
                </a>
                @if(auth()->user()->canManageLiveChicken())
                    <a href="{{ route('live_chickens.edit', $record->id) }}"
                       class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded text-sm font-semibold">
                        Edit
                    </a>
                    <form action="{{ route('live_chickens.destroy', $record->id) }}" method="POST"
                          onsubmit="return confirm('Delete this record?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-red-700 hover:bg-red-800 text-white rounded text-sm font-semibold">
                            Delete
                        </button>
                    </form>
                @endif
                <a href="{{ route('live_chickens.index') }}" class="px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded text-sm">
                    ← Back
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-600 text-white px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-600 text-white px-4 py-3 rounded mb-4">{{ session('error') }}</div>
        @endif

        @php $colors = \App\Models\LiveChicken::STATUS_COLORS; $color = $colors[$record->status] ?? 'gray'; @endphp
        <div class="mb-5">
            <span class="px-3 py-1 rounded text-sm font-semibold
                @if($color === 'green') bg-green-700 text-green-100
                @elseif($color === 'blue') bg-blue-700 text-blue-100
                @elseif($color === 'yellow') bg-yellow-700 text-yellow-100
                @else bg-red-700 text-red-100 @endif">
                {{ $record->status }}
            </span>
        </div>

        {{-- Core Details --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="bg-gray-900 rounded p-4">
                <div class="text-xs text-gray-400 mb-1">GRPO Date</div>
                <div class="text-white font-semibold">{{ $record->date->format('F d, Y') }}</div>
            </div>
            <div class="bg-gray-900 rounded p-4">
                <div class="text-xs text-gray-400 mb-1">PO Number</div>
                <div class="text-purple-300 font-mono font-semibold">{{ $record->po_no ?? '—' }}</div>
                @if($record->rfp_no)
                    <div class="text-xs text-green-400 mt-1">RFP: {{ $record->rfp_no }}</div>
                @endif
                @if($record->po_no)
                    @php $poQty = $record->getPoQty(); @endphp
                    <div class="text-xs mt-1 {{ $record->isPoQtyMet() ? 'text-green-400' : 'text-red-400' }}">
                        PO Qty: {{ number_format($poQty, 2) }} — {{ $record->isPoQtyMet() ? '✓ Met' : '⚠ Not Met' }}
                    </div>
                @endif
            </div>
            <div class="bg-gray-900 rounded p-4">
                <div class="text-xs text-gray-400 mb-1">Vendor / Supplier</div>
                <div class="text-white">{{ $record->supplier }}</div>
            </div>
            <div class="bg-gray-900 rounded p-4">
                <div class="text-xs text-gray-400 mb-1">Brand</div>
                <div class="text-white">{{ $record->brand ?? '—' }}</div>
            </div>
            <div class="bg-gray-900 rounded p-4">
                <div class="text-xs text-gray-400 mb-1">Container No.</div>
                <div class="text-white font-mono">{{ $record->container_no ?? '—' }}</div>
            </div>
            <div class="bg-gray-900 rounded p-4">
                <div class="text-xs text-gray-400 mb-1">Pallet No.</div>
                <div class="text-white font-mono">{{ $record->pallet_no ?? '—' }}</div>
            </div>
            <div class="bg-gray-900 rounded p-4">
                <div class="text-xs text-gray-400 mb-1">Storage Name</div>
                <div class="text-white">{{ $record->storage_name ?? '—' }}</div>
            </div>
            <div class="bg-gray-900 rounded p-4">
                <div class="text-xs text-gray-400 mb-1">Storage Reference No.</div>
                <div class="text-white font-mono">{{ $record->storage_reference_no ?? '—' }}</div>
            </div>
            <div class="bg-gray-900 rounded p-4">
                <div class="text-xs text-gray-400 mb-1">Shipping Type</div>
                <div class="text-white">{{ $record->shipping_type ?? '—' }}</div>
            </div>
            <div class="bg-gray-900 rounded p-4 md:col-span-2">
                <div class="text-xs text-gray-400 mb-2">Items</div>
                @php
                    $showItems = $record->items_data ?: (is_string($record->items) ? collect(explode("\n",$record->items))->filter()->map(fn($l)=>['description'=>trim($l),'brand'=>'','qty'=>0,'uom'=>'','unit_price'=>0])->values()->toArray() : []);
                @endphp
                @if(count($showItems))
                <table class="w-full text-xs">
                    <thead class="text-gray-400 border-b border-gray-700">
                        <tr><th class="text-left py-1">Description</th><th class="text-left py-1">Brand</th><th class="text-left py-1">Qty</th><th class="text-left py-1">UOM</th><th class="text-left py-1">Unit Price</th></tr>
                    </thead>
                    <tbody>
                    @foreach($showItems as $si)
                    <tr class="border-b border-gray-800">
                        <td class="py-1 text-white">{{ $si['description']??'' }}</td>
                        <td class="py-1 text-gray-300">{{ $si['brand']??'' }}</td>
                        <td class="py-1 text-gray-300">{{ $si['qty']??'' }}</td>
                        <td class="py-1 text-gray-300">{{ $si['uom']??'' }}</td>
                        <td class="py-1 text-gray-300">{{ isset($si['unit_price']) && $si['unit_price'] ? number_format($si['unit_price'],2) : '' }}</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
                @else
                <div class="text-white">{{ $record->items }}</div>
                @endif
            </div>
            <div class="bg-gray-900 rounded p-4">
                <div class="text-xs text-gray-400 mb-1">Price</div>
                <div class="text-white">{{ number_format($record->price, 2) }}</div>
            </div>
            <div class="bg-gray-900 rounded p-4">
                <div class="text-xs text-gray-400 mb-1">Actual Qty</div>
                <div class="text-white font-semibold">{{ number_format($record->actual_qty, 2) }}</div>
            </div>
            <div class="bg-gray-900 rounded p-4">
                <div class="text-xs text-gray-400 mb-1">Amount</div>
                <div class="text-white font-semibold text-lg">{{ number_format($record->amount, 2) }}</div>
            </div>
            <div class="bg-gray-900 rounded p-4">
                <div class="text-xs text-gray-400 mb-1">Delivery Date</div>
                <div class="text-white">{{ $record->delivery_date?->format('F d, Y') ?? '—' }}</div>
            </div>
        </div>

        {{-- PO Items --}}
        @if($record->purchaseOrder && $record->purchaseOrder->items->isNotEmpty())
            <div class="mt-4 mb-6">
                <div class="text-sm font-semibold text-gray-300 mb-2">Linked PO Items</div>
                <table class="w-full text-sm bg-gray-900 rounded overflow-hidden">
                    <thead class="bg-gray-700 text-gray-300 text-xs uppercase">
                        <tr>
                            <th class="px-3 py-2 text-left">Item Code</th>
                            <th class="px-3 py-2 text-left">Description</th>
                            <th class="px-3 py-2 text-right">Qty</th>
                            <th class="px-3 py-2 text-center">UOM</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800">
                        @foreach($record->purchaseOrder->items as $item)
                            <tr>
                                <td class="px-3 py-2 font-mono text-purple-300">{{ $item->item_code }}</td>
                                <td class="px-3 py-2 text-gray-200">{{ $item->description }}</td>
                                <td class="px-3 py-2 text-right text-white">{{ number_format($item->qty, 2) }}</td>
                                <td class="px-3 py-2 text-center text-gray-400">{{ $item->uom }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Signature Section --}}
        <div class="border border-gray-700 rounded overflow-hidden mb-6">
            <div class="bg-gray-900 border-b border-gray-700 px-4 py-2 text-center font-semibold text-yellow-600">
                <i class="fas fa-signature mr-2"></i>SIGNATURES
            </div>
            <div class="grid grid-cols-2 divide-x divide-gray-700">
                {{-- Received By --}}
                <div class="p-4 text-center">
                    <div class="text-xs text-gray-400 uppercase mb-3">Received By</div>
                    @if($record->receivedBy)
                        <div class="mb-2 min-h-[60px] flex items-center justify-center">
                            @if($record->receivedBy->esignature)
                                <img src="{{ asset('storage/' . $record->receivedBy->esignature) }}"
                                     alt="Signature" class="h-12 mx-auto object-contain">
                            @else
                                <span class="text-green-400 text-sm font-semibold">✓ Digitally Signed</span>
                            @endif
                        </div>
                        <div class="font-semibold text-white text-sm">{{ $record->receivedBy->name }}</div>
                        <div class="text-xs text-gray-400 italic">Warehouse Receiving Staff</div>
                        <div class="text-xs text-gray-500 mt-1">{{ $record->received_at?->format('M d, Y h:i A') }}</div>
                        @if($record->received_location)
                            <div class="text-xs text-gray-500"><i class="fas fa-map-marker-alt mr-1"></i>{{ $record->received_location }}</div>
                        @endif
                    @else
                        <div class="min-h-[60px] flex items-center justify-center text-gray-600 text-sm">— Not yet signed —</div>
                        <div class="text-xs text-gray-500 italic">Warehouse Receiving Staff</div>
                        @if(auth()->user()->canManageLiveChicken())
                            <button onclick="openReceiveModal()" class="mt-3 px-4 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs rounded font-semibold">
                                <i class="fas fa-pen mr-1"></i> Sign as Received
                            </button>
                        @endif
                    @endif
                </div>

                {{-- Approved By --}}
                <div class="p-4 text-center">
                    <div class="text-xs text-gray-400 uppercase mb-3">Approved By</div>
                    @if($record->grpoApprovedBy)
                        <div class="mb-2 min-h-[60px] flex items-center justify-center">
                            @if($record->grpoApprovedBy->esignature)
                                <img src="{{ asset('storage/' . $record->grpoApprovedBy->esignature) }}"
                                     alt="Signature" class="h-12 mx-auto object-contain">
                            @else
                                <span class="text-green-400 text-sm font-semibold">✓ Digitally Signed</span>
                            @endif
                        </div>
                        <div class="font-semibold text-white text-sm">{{ $record->grpoApprovedBy->name }}</div>
                        <div class="text-xs text-gray-400 italic">Warehouse Supervisor</div>
                        <div class="text-xs text-gray-500 mt-1">{{ $record->grpo_approved_at?->format('M d, Y h:i A') }}</div>
                        @if($record->grpo_approved_location)
                            <div class="text-xs text-gray-500"><i class="fas fa-map-marker-alt mr-1"></i>{{ $record->grpo_approved_location }}</div>
                        @endif
                    @else
                        <div class="min-h-[60px] flex items-center justify-center text-gray-600 text-sm">— Not yet signed —</div>
                        <div class="text-xs text-gray-500 italic">Warehouse Supervisor</div>
                        @if(auth()->user()->canManageLiveChicken())
                            <button onclick="openApproveModal()" class="mt-3 px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded font-semibold">
                                <i class="fas fa-check mr-1"></i> Sign as Approved
                            </button>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-2 text-xs text-gray-500">
            Created by {{ $record->creator?->name ?? 'N/A' }} on {{ $record->created_at->format('F d, Y h:i A') }}
        </div>
    </div>
</div>

{{-- Receive Modal --}}
<div id="receiveModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 w-96">
        <h3 class="text-xl font-bold text-white mb-4">Sign as Received</h3>
        <form action="{{ route('live_chickens.receive', $record->id) }}" method="POST" id="receiveForm">
            @csrf
            <input type="hidden" name="latitude" id="recv_latitude">
            <input type="hidden" name="longitude" id="recv_longitude">
            <input type="hidden" name="location" id="recv_location">
            <p class="text-gray-300 mb-2 text-sm">Your e-signature will be applied. Geolocation will be captured.</p>
            <div id="recv_geo_status" class="text-sm text-yellow-400 mb-4">Waiting for location...</div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeReceiveModal()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">Cancel</button>
                <button type="submit" id="recv_submit" class="bg-gray-500 text-white px-4 py-2 rounded cursor-not-allowed" disabled>
                    <i class="fas fa-spinner fa-spin mr-1"></i> Waiting...
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Approve Modal --}}
<div id="approveModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 w-96">
        <h3 class="text-xl font-bold text-white mb-4">Sign as Approved (Warehouse Supervisor)</h3>
        <form action="{{ route('live_chickens.approve', $record->id) }}" method="POST" id="approveForm">
            @csrf
            <input type="hidden" name="latitude" id="appr_latitude">
            <input type="hidden" name="longitude" id="appr_longitude">
            <input type="hidden" name="location" id="appr_location">
            <p class="text-gray-300 mb-2 text-sm">Your e-signature will be applied. Geolocation will be captured.</p>
            <div id="appr_geo_status" class="text-sm text-yellow-400 mb-4">Waiting for location...</div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeApproveModal()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">Cancel</button>
                <button type="submit" id="appr_submit" class="bg-gray-500 text-white px-4 py-2 rounded cursor-not-allowed" disabled>
                    <i class="fas fa-spinner fa-spin mr-1"></i> Waiting...
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function getLocation(latId, lngId, locId, statusId, btnId) {
    if (!navigator.geolocation) {
        document.getElementById(statusId).textContent = 'Geolocation not supported.';
        enableBtn(btnId);
        return;
    }
    navigator.geolocation.getCurrentPosition(function(pos) {
        document.getElementById(latId).value = pos.coords.latitude;
        document.getElementById(lngId).value = pos.coords.longitude;
        document.getElementById(statusId).textContent = 'Location captured ✓';
        document.getElementById(statusId).className = 'text-sm text-green-400 mb-4';
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${pos.coords.latitude}&lon=${pos.coords.longitude}`)
            .then(r => r.json()).then(d => {
                document.getElementById(locId).value = d.display_name || '';
            }).catch(() => {});
        enableBtn(btnId);
    }, function() {
        document.getElementById(statusId).textContent = 'Could not get location. You can still sign.';
        document.getElementById(statusId).className = 'text-sm text-orange-400 mb-4';
        enableBtn(btnId);
    });
}

function enableBtn(btnId) {
    const btn = document.getElementById(btnId);
    btn.disabled = false;
    btn.className = btn.className.replace('bg-gray-500 cursor-not-allowed', 'bg-purple-600 hover:bg-purple-700 cursor-pointer');
    btn.innerHTML = '<i class="fas fa-pen mr-1"></i> Confirm Signature';
}

function openReceiveModal() {
    document.getElementById('receiveModal').classList.remove('hidden');
    getLocation('recv_latitude', 'recv_longitude', 'recv_location', 'recv_geo_status', 'recv_submit');
}
function closeReceiveModal() { document.getElementById('receiveModal').classList.add('hidden'); }

function openApproveModal() {
    document.getElementById('approveModal').classList.remove('hidden');
    getLocation('appr_latitude', 'appr_longitude', 'appr_location', 'appr_geo_status', 'appr_submit');
}
function closeApproveModal() { document.getElementById('approveModal').classList.add('hidden'); }
</script>
@endsection
