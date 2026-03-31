@extends('layouts.app')

@section('title', 'View Cash Advance Request')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">CASH ADVANCE REQUEST</h1>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <label class="font-semibold text-gray-500">CAR NO:</label>
                    <span class="ml-2 px-4 py-1 bg-gray-900 border border-gray-700 text-white rounded">{{ $car->car_no }}</span>
                </div>
                <span class="px-3 py-1 rounded font-semibold
                    @if($car->status === 'pending') bg-yellow-600 text-white
                    @elseif($car->status === 'approved') bg-green-600 text-white
                    @elseif($car->status === 'rejected') bg-red-600 text-white
                    @elseif($car->status === 'liquidated') bg-blue-600 text-white
                    @else bg-gray-200 text-white
                    @endif">
                    {{ ucfirst($car->status) }}
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

        <!-- Main Fields -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block font-semibold text-gray-500 mb-1">PAYEE:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $car->payee }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-500 mb-1">DEPARTMENT:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $car->department }}</p>
            </div>
        </div>

        <!-- Purpose -->
        <div class="mb-6">
            <label class="block font-semibold text-gray-500 mb-2">PURPOSE:</label>
            <div class="px-4 py-3 bg-gray-900 border border-gray-700 rounded text-gray-200 min-h-[100px]">
                {{ $car->purpose ?? 'No purpose provided' }}
            </div>
        </div>

        <!-- Dates and Amount -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div>
                <label class="block font-semibold text-gray-500 mb-1">DATE REQUESTED:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $car->date_requested ? $car->date_requested->format('F d, Y') : 'N/A' }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-500 mb-1">DATE NEEDED:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $car->date_needed ? $car->date_needed->format('F d, Y') : 'N/A' }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-500 mb-1">AMOUNT ADVANCED:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200 font-semibold text-lg">&#8369;{{ number_format($car->amount, 2) }}</p>
            </div>
        </div>

        <!-- Remarks -->
        <div class="mb-6">
            <label class="block font-semibold text-gray-500 mb-1">REMARKS:</label>
            <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $car->remarks ?? 'N/A' }}</p>
        </div>

        <!-- Fine Print -->
        <div class="mb-6 p-4 bg-gray-900 border border-gray-700 rounded">
            <p class="text-gray-500 text-sm italic">
                I hereby acknowledge receipt of the above sum of money and hereby agree to liquidate in 5 calendar days after the cash advance serve its purpose and provide receipts to document the expenditures.
            </p>
        </div>

        <!-- Created By -->
        <div class="mb-6">
            <label class="block font-semibold text-gray-500 mb-1">CREATED BY:</label>
            <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $car->creator->name ?? 'N/A' }}</p>
        </div>

        <!-- Signature Section -->
        <div class="mb-6">
            <div class="border border-gray-700 rounded">
                <table class="w-full" style="table-layout: fixed;">
                    <colgroup>
                        <col style="width: 33.33%;">
                        <col style="width: 33.33%;">
                        <col style="width: 33.33%;">
                    </colgroup>
                    <thead>
                        <tr class="bg-gray-700">
                            <th class="border border-gray-700 px-4 py-2 text-center text-gray-500 text-sm">Requested By:</th>
                            <th class="border border-gray-700 px-4 py-2 text-center text-gray-500 text-sm">Checked By:</th>
                            <th class="border border-gray-700 px-4 py-2 text-center text-gray-500 text-sm">Approved By:</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border border-gray-700 px-4 py-8 text-center align-bottom">
                                <span class="text-white font-semibold text-sm">{{ $car->creator->name ?? '' }}</span>
                            </td>
                            <td class="border border-gray-700 px-4 py-8 text-center align-bottom">
                                <span class="text-white font-semibold text-sm">{{ $car->departmentHeadApprover->name ?? '' }}</span>
                                @if($car->departmentHeadApprover && $car->department_head_approved_at)
                                    <div class="text-xs text-gray-500 italic mt-1">
                                        Digitally Signed<br>
                                        {{ $car->department_head_approved_at->format('d M Y | H:i') }}
                                        @if($car->department_head_approved_latitude && $car->department_head_approved_longitude)
                                            <br>Coords: {{ $car->department_head_approved_latitude }}, {{ $car->department_head_approved_longitude }}
                                            @if($car->department_head_approved_location)
                                                ({{ $car->department_head_approved_location }})
                                            @endif
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="border border-gray-700 px-4 py-8 text-center align-bottom">
                                <span class="text-white font-semibold text-sm">{{ $car->approver->name ?? '' }}</span>
                                @if($car->approver && $car->approved_at)
                                    <div class="text-xs text-gray-500 italic mt-1">
                                        Digitally Signed<br>
                                        {{ $car->approved_at->format('d M Y | H:i') }}
                                        @if($car->approved_latitude && $car->approved_longitude)
                                            <br>Coords: {{ $car->approved_latitude }}, {{ $car->approved_longitude }}
                                            @if($car->approved_location)
                                                ({{ $car->approved_location }})
                                            @endif
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                        <tr class="bg-gray-700 text-gray-500 text-xs italic">
                            <td class="border border-gray-700 px-4 py-2 text-center">Requisitioner</td>
                            <td class="border border-gray-700 px-4 py-2 text-center">Department Head</td>
                            <td class="border border-gray-700 px-4 py-2 text-center">Executive (President / VP / CFO)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Approval Trail -->
        <div class="mb-6 p-4 bg-gray-900 border border-gray-700 rounded">
            <h3 class="text-lg font-semibold text-white mb-4">Approval Trail</h3>
            <div class="space-y-3">
                <!-- Department Head Level -->
                <div class="flex items-start gap-4 p-3 bg-gray-800 rounded">
                    <div class="flex-shrink-0">
                        @if($car->department_head_approved_by)
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-green-600">
                                <i class="fas fa-check text-white"></i>
                            </div>
                        @else
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-300">
                                <i class="fas fa-clock text-gray-500"></i>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <p class="text-gray-500">
                            <span class="font-semibold">Department Head Check</span>
                            @if($car->department_head_approved_by && $car->departmentHeadApprover)
                                <span class="text-green-700">&#10003; Checked</span>
                                <br>
                                <small class="text-gray-500">
                                    {{ $car->departmentHeadApprover->name }}
                                    on {{ $car->department_head_approved_at->format('M d, Y h:i A') }}
                                </small>
                            @else
                                <span class="text-yellow-700">Pending</span>
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Executive Level -->
                <div class="flex items-start gap-4 p-3 bg-gray-800 rounded">
                    <div class="flex-shrink-0">
                        @if($car->status === 'approved' && $car->approver)
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-green-600">
                                <i class="fas fa-check text-white"></i>
                            </div>
                        @elseif($car->department_head_approved_by)
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-300">
                                <i class="fas fa-clock text-gray-500"></i>
                            </div>
                        @else
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-700">
                                <i class="fas fa-lock text-gray-500"></i>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <p class="text-gray-500">
                            <span class="font-semibold">Executive Approval (President / VP / CFO)</span>
                            @if($car->status === 'approved' && $car->approver)
                                <span class="text-green-700">&#10003; Approved</span>
                                <br>
                                <small class="text-gray-500">
                                    {{ $car->approver->name }}
                                    on {{ $car->approved_at->format('M d, Y h:i A') }}
                                </small>
                            @elseif($car->department_head_approved_by)
                                <span class="text-yellow-700">Pending</span>
                            @else
                                <span class="text-gray-500">Locked</span>
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Rejection Status -->
                @if($car->status === 'rejected')
                    <div class="flex items-start gap-4 p-3 bg-red-50 border border-red-200 rounded">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-red-600">
                                <i class="fas fa-times text-white"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <p class="text-red-700">
                                <span class="font-semibold">Rejected</span>
                            </p>
                            @if($car->rejection_reason)
                                <p class="text-gray-500 mt-2">
                                    <strong>Reason:</strong> {{ $car->rejection_reason }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Approval Buttons -->
        @if($car->status === 'pending' && $car->approval_stage !== 'rejected')
            <div class="flex gap-3 mb-4 mt-6">
                @if($car->approval_stage === 'pending_dh' && auth()->user()->canApproveRFPAsDH() || auth()->user()->canPerformInModule('can_approve', 'rfp'))
                    <button type="button" onclick="showApproveDHModal()" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">
                        <i class="fas fa-check mr-1"></i> Approve as Department Head
                    </button>
                @endif

                @if($car->approval_stage === 'pending_executive' && auth()->user()->canApproveRFPAsExecutive())
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

        <!-- Create Liquidation Button -->
        @if($car->status === 'approved')
            <div class="mb-4">
                <a href="{{ route('liquidation_forms.create', ['car_id' => $car->id]) }}" class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-2 rounded hover:from-blue-700 hover:to-blue-800 transition inline-block">
                    <i class="fas fa-file-invoice-dollar mr-1"></i> Create Liquidation
                </a>
            </div>
        @endif

        <!-- Linked Liquidation Forms -->
        @if($car->liquidationForms && $car->liquidationForms->count() > 0)
            <div class="mb-6 p-4 bg-gray-900 border border-gray-700 rounded">
                <h3 class="text-lg font-semibold text-white mb-3"><i class="fas fa-file-invoice-dollar mr-2 text-blue-700"></i>Linked Liquidation Forms</h3>
                <div class="space-y-2">
                    @foreach($car->liquidationForms as $liquidation)
                        <div class="flex items-center justify-between p-3 bg-gray-800 rounded">
                            <div>
                                <span class="text-white font-semibold">{{ $liquidation->liquidation_no ?? 'LF-' . $liquidation->id }}</span>
                                <span class="text-gray-500 ml-3">&#8369;{{ number_format($liquidation->total_amount ?? 0, 2) }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="px-2 py-1 rounded text-xs font-semibold
                                    @if($liquidation->status === 'pending') bg-yellow-600 text-white
                                    @elseif($liquidation->status === 'approved') bg-green-600 text-white
                                    @elseif($liquidation->status === 'rejected') bg-red-600 text-white
                                    @else bg-gray-200 text-white
                                    @endif">
                                    {{ ucfirst($liquidation->status) }}
                                </span>
                                <a href="{{ route('liquidation_forms.show', $liquidation->id) }}" class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 transition">
                                    View
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Form Actions -->
        <div class="flex justify-between items-center">
            <a href="{{ route('cash_advance_requests.index') }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-700 transition">
                Back to List
            </a>
            <div class="flex gap-4">
                <a href="{{ route('cash_advance_requests.edit', $car->id) }}" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
                    Edit
                </a>
                <a href="{{ route('cash_advance_requests.print', $car->id) }}" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition inline-block">
                    Print
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Department Head Approval Modal -->
<div id="approveDHModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 w-96">
        <h3 class="text-xl font-bold text-white mb-4">Approve as Department Head</h3>
        <form action="{{ route('cash_advance_requests.approve_dh', $car->id) }}" method="POST">
            @csrf
            <input type="hidden" name="latitude" id="dh_latitude">
            <input type="hidden" name="longitude" id="dh_longitude">
            <input type="hidden" name="location" id="dh_location">
            <div class="mb-4">
                <p class="text-gray-500 mb-2">Geolocation will be captured automatically.</p>
                <div id="dh_geolocation_status" class="text-sm text-gray-500">Waiting for location...</div>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeApproveDHModal()" class="bg-gray-200 text-white px-4 py-2 rounded hover:bg-gray-700">
                    Cancel
                </button>
                <button type="submit" id="dh_submit_btn" class="bg-gray-500 text-white px-4 py-2 rounded cursor-not-allowed" disabled>
                    <i class="fas fa-spinner fa-spin mr-1"></i> Waiting for location...
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Executive Approval Modal -->
<div id="approveExecutiveModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 w-96">
        <h3 class="text-xl font-bold text-white mb-4">Approve as Executive (President / VP / CFO)</h3>
        <form action="{{ route('cash_advance_requests.approve', $car->id) }}" method="POST">
            @csrf
            <input type="hidden" name="latitude" id="exec_latitude">
            <input type="hidden" name="longitude" id="exec_longitude">
            <input type="hidden" name="location" id="exec_location">
            <div class="mb-4">
                <p class="text-gray-500 mb-2">Geolocation will be captured automatically.</p>
                <div id="exec_geolocation_status" class="text-sm text-gray-500">Waiting for location...</div>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeApproveExecutiveModal()" class="bg-gray-200 text-white px-4 py-2 rounded hover:bg-gray-700">
                    Cancel
                </button>
                <button type="submit" id="exec_submit_btn" class="bg-gray-500 text-white px-4 py-2 rounded cursor-not-allowed" disabled>
                    <i class="fas fa-spinner fa-spin mr-1"></i> Waiting for location...
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 w-96">
        <h3 class="text-xl font-bold text-white mb-4">Reject Cash Advance Request</h3>
        <form action="{{ route('cash_advance_requests.reject', $car->id) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-gray-500 mb-2">Rejection Reason (Optional):</label>
                <textarea name="rejection_reason" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white" rows="4"></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeRejectModal()" class="bg-gray-200 text-white px-4 py-2 rounded hover:bg-gray-700">
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
// Department Head Approval
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
    statusEl.className = 'text-sm text-blue-700';
    fetch('https://ipapi.co/json/')
        .then(response => response.json())
        .then(data => {
            if (data.latitude && data.longitude) {
                document.getElementById(prefix + '_latitude').value = data.latitude;
                document.getElementById(prefix + '_longitude').value = data.longitude;
                document.getElementById(prefix + '_location').value = data.city || data.region || 'Unknown';
                statusEl.textContent = 'Location captured (IP-based): ' + data.latitude + ', ' + data.longitude + ' (' + (data.city || 'Unknown') + ')';
                statusEl.className = 'text-sm text-green-700';
                enableSubmitButton(submitBtn, true);
            } else {
                statusEl.innerHTML = 'Could not determine location.<br>You can still approve without location data.';
                statusEl.className = 'text-sm text-yellow-700';
                enableSubmitButton(submitBtn, false);
            }
        })
        .catch(() => {
            statusEl.innerHTML = 'Could not determine location.<br>You can still approve without location data.';
            statusEl.className = 'text-sm text-yellow-700';
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
    statusEl.className = 'text-sm text-blue-700';
    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            document.getElementById(prefix + '_latitude').value = lat;
            document.getElementById(prefix + '_longitude').value = lng;
            getLocationName(lat, lng, prefix);
            statusEl.textContent = 'Location captured: ' + lat.toFixed(6) + ', ' + lng.toFixed(6);
            statusEl.className = 'text-sm text-green-700';
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
