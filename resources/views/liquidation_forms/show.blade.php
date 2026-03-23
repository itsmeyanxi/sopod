@extends('layouts.app')

@section('title', 'View Liquidation Form')

@section('content')
<div class="container mx-auto">
    <div class="bg-white text-gray-800 rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-200 pb-4">
            <h1 class="text-2xl font-bold text-gray-800">LIQUIDATION FORM</h1>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <label class="font-semibold text-gray-500">LIQ NO:</label>
                    <span class="ml-2 px-4 py-1 bg-gray-50 border border-gray-200 text-gray-800 rounded">{{ $liquidation->liq_no }}</span>
                </div>
                <span class="px-3 py-1 rounded font-semibold
                    @if($liquidation->status === 'pending') bg-yellow-600 text-white
                    @elseif($liquidation->status === 'approved') bg-green-600 text-white
                    @elseif($liquidation->status === 'rejected') bg-red-600 text-white
                    @else bg-blue-600 text-white
                    @endif">
                    {{ ucfirst($liquidation->status) }}
                </span>
            </div>
        </div>

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

        <!-- Linked CAR Info -->
        <div class="mb-6">
            <label class="block font-semibold text-gray-500 mb-1">LINKED CASH ADVANCE REQUEST:</label>
            <p class="px-4 py-2 bg-gray-50 border border-gray-200 rounded text-gray-200">
                @if($liquidation->cashAdvanceRequest)
                    <a href="{{ route('cash_advance_requests.show', $liquidation->cashAdvanceRequest->id) }}" class="text-purple-400 hover:text-purple-300">
                        <i class="fas fa-link mr-1"></i>{{ $liquidation->cashAdvanceRequest->car_no }}
                    </a>
                    <span class="text-gray-400 ml-2">(CAR Amount: &#8369;{{ number_format($liquidation->cashAdvanceRequest->amount, 2) }})</span>
                @else
                    N/A
                @endif
            </p>
        </div>

        <!-- Main Fields -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div>
                <label class="block font-semibold text-gray-500 mb-1">NAME:</label>
                <p class="px-4 py-2 bg-gray-50 border border-gray-200 rounded text-gray-200">{{ $liquidation->name }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-500 mb-1">DEPARTMENT:</label>
                <p class="px-4 py-2 bg-gray-50 border border-gray-200 rounded text-gray-200">{{ $liquidation->department }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-500 mb-1">DATE APPLIED:</label>
                <p class="px-4 py-2 bg-gray-50 border border-gray-200 rounded text-gray-200">{{ $liquidation->date_applied->format('F d, Y') }}</p>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="mb-6">
            <label class="block font-semibold text-gray-500 mb-3">PARTICULARS / LINE ITEMS:</label>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse border border-gray-200">
                    <thead class="bg-gray-100 text-gray-500 uppercase text-sm">
                        <tr>
                            <th class="border border-gray-200 px-4 py-3" style="width: 5%;">#</th>
                            <th class="border border-gray-200 px-4 py-3" style="width: 70%;">Particulars</th>
                            <th class="border border-gray-200 px-4 py-3 text-right" style="width: 25%;">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-500">
                        @foreach($liquidation->items as $index => $item)
                            <tr class="hover:bg-gray-100/40">
                                <td class="border border-gray-200 px-4 py-3 text-center text-gray-400">{{ $index + 1 }}</td>
                                <td class="border border-gray-200 px-4 py-3">{{ $item->particulars }}</td>
                                <td class="border border-gray-200 px-4 py-3 text-right">&#8369;{{ number_format($item->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-100">
                            <td colspan="2" class="border border-gray-200 px-4 py-3 text-right font-bold text-gray-800 uppercase">Total Amount Spent:</td>
                            <td class="border border-gray-200 px-4 py-3 text-right font-bold text-gray-800 text-lg">&#8369;{{ number_format($liquidation->total_amount_spent, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Remarks -->
        @if($liquidation->remarks)
            <div class="mb-6">
                <label class="block font-semibold text-gray-500 mb-2">REMARKS:</label>
                <div class="px-4 py-3 bg-gray-50 border border-gray-200 rounded text-gray-200 min-h-[60px]">
                    {{ $liquidation->remarks }}
                </div>
            </div>
        @endif

        <!-- Proof Documents -->
        @if($liquidation->proof_documents && count($liquidation->proof_documents) > 0)
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-3">PROOF DOCUMENTS:</label>
                <div class="bg-gray-900 border border-gray-700 rounded p-4">
                    <ul class="space-y-2">
                        @foreach($liquidation->proof_documents as $doc)
                            <li class="flex items-center justify-between p-3 bg-gray-800 rounded">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-file text-purple-400"></i>
                                    <div>
                                        <p class="text-white font-semibold">{{ $doc['name'] }}</p>
                                        <p class="text-gray-400 text-sm">{{ number_format($doc['size'] / 1024, 2) }} KB</p>
                                    </div>
                                </div>
                                <a href="{{ asset('storage/' . $doc['path']) }}" download class="bg-purple-600 text-white px-3 py-1 rounded text-sm hover:bg-purple-700 transition">
                                    <i class="fas fa-download mr-1"></i> Download
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- Created By -->
        <div class="mb-6">
            <label class="block font-semibold text-gray-500 mb-1">CREATED BY:</label>
            <p class="px-4 py-2 bg-gray-50 border border-gray-200 rounded text-gray-200">{{ $liquidation->creator->name ?? 'N/A' }}</p>
        </div>

        <!-- Signature Section -->
        <div class="mb-6">
            <div class="border border-gray-200 rounded">
                <table class="w-full" style="table-layout: fixed;">
                    <colgroup>
                        <col style="width: 33.33%;">
                        <col style="width: 33.33%;">
                        <col style="width: 33.33%;">
                    </colgroup>
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border border-gray-200 px-4 py-2 text-center text-gray-500 text-sm">Submitted By:</th>
                            <th class="border border-gray-200 px-4 py-2 text-center text-gray-500 text-sm">Checked By:<br><span class="text-xs italic">(Immediate Superior)</span></th>
                            <th class="border border-gray-200 px-4 py-2 text-center text-gray-500 text-sm">Approved By:<br><span class="text-xs italic">(Executive)</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border border-gray-200 px-4 py-8 text-center align-bottom">
                                <span class="text-gray-800 font-semibold text-sm">{{ $liquidation->submitted_by ?? $liquidation->creator->name ?? '' }}</span>
                            </td>
                            <td class="border border-gray-200 px-4 py-8 text-center align-bottom">
                                <span class="text-gray-800 font-semibold text-sm">{{ $liquidation->departmentHeadApprover->name ?? '' }}</span>
                                @if($liquidation->departmentHeadApprover && $liquidation->department_head_approved_at)
                                    <div class="text-xs text-gray-400 italic mt-1">
                                        Digitally Signed<br>
                                        {{ $liquidation->department_head_approved_at->format('d M Y | H:i') }}
                                        @if($liquidation->department_head_approved_latitude && $liquidation->department_head_approved_longitude)
                                            <br>Coords: {{ $liquidation->department_head_approved_latitude }}, {{ $liquidation->department_head_approved_longitude }}
                                            @if($liquidation->department_head_approved_location)
                                                ({{ $liquidation->department_head_approved_location }})
                                            @endif
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="border border-gray-200 px-4 py-8 text-center align-bottom">
                                <span class="text-gray-800 font-semibold text-sm">{{ $liquidation->executiveApprover->name ?? '' }}</span>
                                @if($liquidation->executiveApprover && $liquidation->executive_approved_at)
                                    <div class="text-xs text-gray-400 italic mt-1">
                                        Digitally Signed<br>
                                        {{ $liquidation->executive_approved_at->format('d M Y | H:i') }}
                                        @if($liquidation->executive_approved_latitude && $liquidation->executive_approved_longitude)
                                            <br>Coords: {{ $liquidation->executive_approved_latitude }}, {{ $liquidation->executive_approved_longitude }}
                                            @if($liquidation->executive_approved_location)
                                                ({{ $liquidation->executive_approved_location }})
                                            @endif
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                        <tr class="bg-gray-100 text-gray-500 text-xs italic">
                            <td class="border border-gray-200 px-4 py-2 text-center">Requestor</td>
                            <td class="border border-gray-200 px-4 py-2 text-center">Immediate Superior</td>
                            <td class="border border-gray-200 px-4 py-2 text-center">Executive</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Approval Trail -->
        <div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Approval Trail</h3>
            <div class="space-y-3">
                <!-- Immediate Superior Level -->
                <div class="flex items-start gap-4 p-3 bg-white rounded">
                    <div class="flex-shrink-0">
                        @if($liquidation->department_head_approved_by)
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-green-600">
                                <i class="fas fa-check text-gray-800"></i>
                            </div>
                        @else
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-600">
                                <i class="fas fa-clock text-gray-500"></i>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <p class="text-gray-500">
                            <span class="font-semibold">Immediate Superior Check</span>
                            @if($liquidation->department_head_approved_by && $liquidation->departmentHeadApprover)
                                <span class="text-green-400">&#10003; Checked</span>
                                <br>
                                <small class="text-gray-400">
                                    {{ $liquidation->departmentHeadApprover->name }}
                                    on {{ $liquidation->department_head_approved_at->format('M d, Y h:i A') }}
                                </small>
                            @else
                                <span class="text-yellow-400">Pending</span>
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Executive Level -->
                <div class="flex items-start gap-4 p-3 bg-white rounded">
                    <div class="flex-shrink-0">
                        @if($liquidation->status === 'approved' && $liquidation->executiveApprover)
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-green-600">
                                <i class="fas fa-check text-gray-800"></i>
                            </div>
                        @elseif($liquidation->department_head_approved_by)
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-600">
                                <i class="fas fa-clock text-gray-500"></i>
                            </div>
                        @else
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-100">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <p class="text-gray-500">
                            <span class="font-semibold">Executive Approval</span>
                            @if($liquidation->status === 'approved' && $liquidation->executiveApprover)
                                <span class="text-green-400">&#10003; Approved</span>
                                <br>
                                <small class="text-gray-400">
                                    {{ $liquidation->executiveApprover->name }}
                                    on {{ $liquidation->executive_approved_at->format('M d, Y h:i A') }}
                                </small>
                            @elseif($liquidation->department_head_approved_by)
                                <span class="text-yellow-400">Pending</span>
                            @else
                                <span class="text-gray-400">Locked</span>
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Rejection Status -->
                @if($liquidation->status === 'rejected')
                    <div class="flex items-start gap-4 p-3 bg-red-900/20 border border-red-700 rounded">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-red-600">
                                <i class="fas fa-times text-gray-800"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <p class="text-red-400">
                                <span class="font-semibold">Rejected</span>
                            </p>
                            @if($liquidation->rejection_reason)
                                <p class="text-gray-500 mt-2">
                                    <strong>Reason:</strong> {{ $liquidation->rejection_reason }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Approval Buttons -->
        @if($liquidation->status === 'pending' && $liquidation->approval_stage !== 'rejected')
            <div class="flex gap-3 mb-4 mt-6">
                @if($liquidation->approval_stage === 'pending_dh' && auth()->user()->canApproveRFPAsDH() || auth()->user()->canPerformInModule('can_approve', 'rfp'))
                    <button type="button" onclick="showApproveDHModal()" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">
                        <i class="fas fa-check mr-1"></i> Approve as Immediate Superior
                    </button>
                @endif

                @if($liquidation->approval_stage === 'pending_executive' && auth()->user()->canApproveRFPAsExecutive())
                    <button type="button" onclick="showApproveExecutiveModal()" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">
                        <i class="fas fa-check mr-1"></i> Approve as Executive
                    </button>
                @endif

                @if(auth()->user()->canApproveRequestForPayments())
                    <button type="button" onclick="showRejectModal()" class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 transition">
                        <i class="fas fa-times mr-1"></i> Reject
                    </button>
                @endif
            </div>
        @endif

        <!-- Form Actions -->
        <div class="flex justify-between items-center">
            <a href="{{ route('liquidation_forms.index') }}" class="bg-gray-100 text-gray-800 px-6 py-2 rounded hover:bg-gray-100 transition">
                Back to List
            </a>
            <div class="flex gap-4">
                <a href="{{ route('liquidation_forms.edit', $liquidation->id) }}" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
                    Edit
                </a>
                <a href="{{ route('liquidation_forms.print', $liquidation->id) }}" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition inline-block">
                    Print
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Immediate Superior Approval Modal -->
<div id="approveDHModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-96">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Approve as Immediate Superior</h3>
        <form action="{{ route('liquidation_forms.approve_dh', $liquidation->id) }}" method="POST">
            @csrf
            <input type="hidden" name="latitude" id="dh_latitude">
            <input type="hidden" name="longitude" id="dh_longitude">
            <input type="hidden" name="location" id="dh_location">
            <div class="mb-4">
                <p class="text-gray-500 mb-2">Geolocation will be captured automatically.</p>
                <div id="dh_geolocation_status" class="text-sm text-gray-400">Waiting for location...</div>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeApproveDHModal()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-100">
                    Cancel
                </button>
                <button type="submit" id="dh_submit_btn" class="bg-gray-500 text-gray-800 px-4 py-2 rounded cursor-not-allowed" disabled>
                    <i class="fas fa-spinner fa-spin mr-1"></i> Waiting for location...
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Executive Approval Modal -->
<div id="approveExecutiveModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-96">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Approve as Executive</h3>
        <form action="{{ route('liquidation_forms.approve', $liquidation->id) }}" method="POST">
            @csrf
            <input type="hidden" name="latitude" id="exec_latitude">
            <input type="hidden" name="longitude" id="exec_longitude">
            <input type="hidden" name="location" id="exec_location">
            <div class="mb-4">
                <p class="text-gray-500 mb-2">Geolocation will be captured automatically.</p>
                <div id="exec_geolocation_status" class="text-sm text-gray-400">Waiting for location...</div>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeApproveExecutiveModal()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-100">
                    Cancel
                </button>
                <button type="submit" id="exec_submit_btn" class="bg-gray-500 text-gray-800 px-4 py-2 rounded cursor-not-allowed" disabled>
                    <i class="fas fa-spinner fa-spin mr-1"></i> Waiting for location...
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-96">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Reject Liquidation Form</h3>
        <form action="{{ route('liquidation_forms.reject', $liquidation->id) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-gray-500 mb-2">Rejection Reason (Optional):</label>
                <textarea name="rejection_reason" class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800" rows="4"></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeRejectModal()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-100">
                    Cancel
                </button>
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                    Confirm Reject
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Immediate Superior Approval
function showApproveDHModal() {
    document.getElementById('approveDHModal').classList.remove('hidden');
    captureGeolocation('dh');
}
function closeApproveDHModal() {
    document.getElementById('approveDHModal').classList.add('hidden');
}

// Executive Approval
function showApproveExecutiveModal() {
    document.getElementById('approveExecutiveModal').classList.remove('hidden');
    captureGeolocation('exec');
}
function closeApproveExecutiveModal() {
    document.getElementById('approveExecutiveModal').classList.add('hidden');
}

// Reject
function showRejectModal() {
    document.getElementById('rejectModal').classList.remove('hidden');
}
function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
}

// Geolocation Capture
function enableSubmitButton(submitBtn, withLocation) {
    if (!submitBtn) return;
    submitBtn.disabled = false;
    if (withLocation) {
        submitBtn.className = 'bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700';
        submitBtn.innerHTML = '<i class="fas fa-check mr-1"></i> Confirm Approval';
    } else {
        submitBtn.className = 'bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700';
        submitBtn.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i> Approve Without Location';
    }
}

function captureGeolocationByIP(prefix, statusEl, submitBtn) {
    statusEl.textContent = 'Using IP-based location as fallback...';
    statusEl.className = 'text-sm text-blue-400';
    fetch('https://ipapi.co/json/')
        .then(response => response.json())
        .then(data => {
            if (data.latitude && data.longitude) {
                document.getElementById(prefix + '_latitude').value = data.latitude;
                document.getElementById(prefix + '_longitude').value = data.longitude;
                document.getElementById(prefix + '_location').value = data.city || data.region || 'Unknown';
                statusEl.textContent = 'Location captured (IP-based): ' + data.latitude + ', ' + data.longitude + ' (' + (data.city || 'Unknown') + ')';
                statusEl.className = 'text-sm text-green-400';
                enableSubmitButton(submitBtn, true);
            } else {
                statusEl.innerHTML = 'Could not determine location.<br>You can still approve without location data.';
                statusEl.className = 'text-sm text-yellow-400';
                enableSubmitButton(submitBtn, false);
            }
        })
        .catch(() => {
            statusEl.innerHTML = 'Could not determine location.<br>You can still approve without location data.';
            statusEl.className = 'text-sm text-yellow-400';
            enableSubmitButton(submitBtn, false);
        });
}

function captureGeolocation(prefix) {
    const statusEl = document.getElementById(prefix + '_geolocation_status');
    const submitBtn = document.getElementById(prefix + '_submit_btn');
    if (!navigator.geolocation) {
        captureGeolocationByIP(prefix, statusEl, submitBtn);
        return;
    }
    statusEl.textContent = 'Capturing location...';
    statusEl.className = 'text-sm text-blue-400';
    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            document.getElementById(prefix + '_latitude').value = lat;
            document.getElementById(prefix + '_longitude').value = lng;
            getLocationName(lat, lng, prefix);
            statusEl.textContent = 'Location captured: ' + lat.toFixed(6) + ', ' + lng.toFixed(6);
            statusEl.className = 'text-sm text-green-400';
            enableSubmitButton(submitBtn, true);
        },
        function(error) {
            captureGeolocationByIP(prefix, statusEl, submitBtn);
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
}

function getLocationName(lat, lng, prefix) {
    fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng)
        .then(response => response.json())
        .then(data => {
            if (data.address) {
                const locationName = data.address.city || data.address.town || data.address.village || 'Unknown';
                document.getElementById(prefix + '_location').value = locationName;
            }
        })
        .catch(() => {
            document.getElementById(prefix + '_location').value = 'Location: ' + lat.toFixed(6) + ', ' + lng.toFixed(6);
        });
}
</script>
@endsection
