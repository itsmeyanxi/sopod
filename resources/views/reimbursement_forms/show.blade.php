@extends('layouts.app')

@section('title', 'View Reimbursement Form')

@section('content')
<div class="container mx-auto">
    <div class="bg-gray-800 text-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
            <h1 class="text-2xl font-bold text-white">REIMBURSEMENT FORM</h1>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <label class="font-semibold text-gray-300">RI NO:</label>
                    <span class="ml-2 px-4 py-1 bg-gray-900 border border-gray-700 text-white rounded">{{ $reimbursement->ri_no }}</span>
                </div>
                <span class="px-3 py-1 rounded font-semibold
                    @if($reimbursement->status === 'pending') bg-yellow-600 text-white
                    @elseif($reimbursement->status === 'approved') bg-green-600 text-white
                    @elseif($reimbursement->status === 'rejected') bg-red-600 text-white
                    @else bg-blue-600 text-white
                    @endif">
                    {{ ucfirst($reimbursement->status) }}
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

        <!-- Department and Date Applied -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block font-semibold text-gray-300 mb-1">DEPARTMENT:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $reimbursement->department }}</p>
            </div>
            <div>
                <label class="block font-semibold text-gray-300 mb-1">DATE APPLIED:</label>
                <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $reimbursement->date_applied->format('F d, Y') }}</p>
            </div>
        </div>

        <!-- Items Table -->
        <div class="mb-6">
            <label class="block font-semibold text-gray-300 mb-3">EXPENSE ITEMS:</label>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse border border-gray-700">
                    <thead class="bg-gray-700 text-gray-300 uppercase text-sm">
                        <tr>
                            <th class="border border-gray-700 px-4 py-3" style="width: 20%;">DATE</th>
                            <th class="border border-gray-700 px-4 py-3" style="width: 55%;">PARTICULARS</th>
                            <th class="border border-gray-700 px-4 py-3" style="width: 25%;">COST</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-300">
                        @foreach($reimbursement->items as $item)
                            <tr class="hover:bg-gray-700/40">
                                <td class="border border-gray-700 px-4 py-3">{{ $item->date ? \Carbon\Carbon::parse($item->date)->format('M d, Y') : '' }}</td>
                                <td class="border border-gray-700 px-4 py-3">{{ $item->particulars }}</td>
                                <td class="border border-gray-700 px-4 py-3 text-right">&#8369;{{ number_format($item->cost, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-900">
                            <td colspan="2" class="border border-gray-700 px-4 py-3 text-right font-bold text-white">TOTAL AMOUNT SPENT:</td>
                            <td class="border border-gray-700 px-4 py-3 text-right font-bold text-green-400 text-lg">&#8369;{{ number_format($reimbursement->total_amount_spent, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Amount to be Reimbursed -->
        <div class="mb-6 bg-gray-900 border border-gray-700 rounded p-4">
            <label class="block font-semibold text-gray-300 mb-1">AMOUNT TO BE REIMBURSED:</label>
            <p class="text-2xl font-bold text-green-400">&#8369;{{ number_format($reimbursement->amount_to_reimburse, 2) }}</p>
        </div>

        <!-- Submitted By -->
        <div class="mb-6">
            <label class="block font-semibold text-gray-300 mb-1">SUBMITTED BY:</label>
            <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $reimbursement->submitted_by ?? 'N/A' }}</p>
        </div>

        <!-- Remarks -->
        @if($reimbursement->remarks)
            <div class="mb-6">
                <label class="block font-semibold text-gray-300 mb-2">REMARKS:</label>
                <div class="px-4 py-3 bg-gray-900 border border-gray-700 rounded text-gray-200 min-h-[60px]">
                    {{ $reimbursement->remarks }}
                </div>
            </div>
        @endif

        <!-- Created By -->
        <div class="mb-6">
            <label class="block font-semibold text-gray-300 mb-1">CREATED BY:</label>
            <p class="px-4 py-2 bg-gray-900 border border-gray-700 rounded text-gray-200">{{ $reimbursement->creator->name ?? 'N/A' }}</p>
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
                            <th class="border border-gray-700 px-4 py-2 text-center text-gray-300 text-sm">Submitted By:</th>
                            <th class="border border-gray-700 px-4 py-2 text-center text-gray-300 text-sm">Checked By:</th>
                            <th class="border border-gray-700 px-4 py-2 text-center text-gray-300 text-sm">Approved By:</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border border-gray-700 px-4 py-8 text-center align-bottom">
                                <span class="text-white font-semibold text-sm">{{ $reimbursement->submitted_by ?? ($reimbursement->creator->name ?? '') }}</span>
                            </td>
                            <td class="border border-gray-700 px-4 py-8 text-center align-bottom">
                                <span class="text-white font-semibold text-sm">{{ $reimbursement->departmentHeadApprover->name ?? '' }}</span>
                                @if($reimbursement->departmentHeadApprover && $reimbursement->department_head_approved_at)
                                    <div class="text-xs text-gray-400 italic mt-1">
                                        Digitally Signed<br>
                                        {{ $reimbursement->department_head_approved_at->format('d M Y | H:i') }}
                                        @if($reimbursement->department_head_approved_latitude && $reimbursement->department_head_approved_longitude)
                                            <br>Coords: {{ $reimbursement->department_head_approved_latitude }}, {{ $reimbursement->department_head_approved_longitude }}
                                            @if($reimbursement->department_head_approved_location)
                                                ({{ $reimbursement->department_head_approved_location }})
                                            @endif
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="border border-gray-700 px-4 py-8 text-center align-bottom">
                                <span class="text-white font-semibold text-sm">{{ $reimbursement->executiveApprover->name ?? '' }}</span>
                                @if($reimbursement->executiveApprover && $reimbursement->executive_approved_at)
                                    <div class="text-xs text-gray-400 italic mt-1">
                                        Digitally Signed<br>
                                        {{ $reimbursement->executive_approved_at->format('d M Y | H:i') }}
                                        @if($reimbursement->executive_approved_latitude && $reimbursement->executive_approved_longitude)
                                            <br>Coords: {{ $reimbursement->executive_approved_latitude }}, {{ $reimbursement->executive_approved_longitude }}
                                            @if($reimbursement->executive_approved_location)
                                                ({{ $reimbursement->executive_approved_location }})
                                            @endif
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                        <tr class="bg-gray-700 text-gray-300 text-xs italic">
                            <td class="border border-gray-700 px-4 py-2 text-center">Requisitioner</td>
                            <td class="border border-gray-700 px-4 py-2 text-center">Department Head</td>
                            <td class="border border-gray-700 px-4 py-2 text-center">President / Vice President</td>
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
                        @if($reimbursement->department_head_approved_by)
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-green-600">
                                <i class="fas fa-check text-white"></i>
                            </div>
                        @else
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-600">
                                <i class="fas fa-clock text-gray-300"></i>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <p class="text-gray-300">
                            <span class="font-semibold">Department Head Check</span>
                            @if($reimbursement->department_head_approved_by && $reimbursement->departmentHeadApprover)
                                <span class="text-green-400">&#10003; Checked</span>
                                <br>
                                <small class="text-gray-400">
                                    {{ $reimbursement->departmentHeadApprover->name }}
                                    on {{ $reimbursement->department_head_approved_at->format('M d, Y h:i A') }}
                                </small>
                            @else
                                <span class="text-yellow-400">Pending</span>
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Executive Level -->
                <div class="flex items-start gap-4 p-3 bg-gray-800 rounded">
                    <div class="flex-shrink-0">
                        @if($reimbursement->status === 'approved' && $reimbursement->executiveApprover)
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-green-600">
                                <i class="fas fa-check text-white"></i>
                            </div>
                        @elseif($reimbursement->department_head_approved_by)
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-600">
                                <i class="fas fa-clock text-gray-300"></i>
                            </div>
                        @else
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-700">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <p class="text-gray-300">
                            <span class="font-semibold">Executive Approval (President/Vice President)</span>
                            @if($reimbursement->status === 'approved' && $reimbursement->executiveApprover)
                                <span class="text-green-400">&#10003; Approved</span>
                                <br>
                                <small class="text-gray-400">
                                    {{ $reimbursement->executiveApprover->name }}
                                    on {{ $reimbursement->executive_approved_at->format('M d, Y h:i A') }}
                                </small>
                            @elseif($reimbursement->department_head_approved_by)
                                <span class="text-yellow-400">Pending</span>
                            @else
                                <span class="text-gray-400">Locked</span>
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Rejection Status -->
                @if($reimbursement->status === 'rejected')
                    <div class="flex items-start gap-4 p-3 bg-red-900/20 border border-red-700 rounded">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-red-600">
                                <i class="fas fa-times text-white"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <p class="text-red-400">
                                <span class="font-semibold">Rejected</span>
                            </p>
                            @if($reimbursement->rejection_reason)
                                <p class="text-gray-300 mt-2">
                                    <strong>Reason:</strong> {{ $reimbursement->rejection_reason }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Footer Note -->
        <div class="mb-6 p-3 bg-gray-900 border border-gray-700 rounded">
            <p class="text-gray-400 text-sm italic">
                <i class="fas fa-info-circle mr-1 text-yellow-400"></i>
                Please attach invoices, Official receipts (OR), and other supporting documents.
            </p>
        </div>

        <!-- Approval Buttons -->
        @if($reimbursement->status === 'pending' && $reimbursement->approval_stage !== 'rejected')
            <div class="flex gap-3 mb-4 mt-6">
                @if($reimbursement->approval_stage === 'pending_dh' && auth()->user()->canApproveRFPAsDH() || auth()->user()->canPerformInModule('can_approve', 'rfp'))
                    <button type="button" onclick="showApproveDHModal()" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">
                        <i class="fas fa-check mr-1"></i> Approve as Department Head
                    </button>
                @endif

                @if($reimbursement->approval_stage === 'pending_executive' && auth()->user()->canApproveRFPAsExecutive())
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
            <a href="{{ route('reimbursement_forms.index') }}" class="bg-gray-700 text-white px-6 py-2 rounded hover:bg-gray-600 transition">
                Back to List
            </a>
            <div class="flex gap-4">
                <a href="{{ route('reimbursement_forms.edit', $reimbursement->id) }}" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
                    Edit
                </a>
                <a href="{{ route('reimbursement_forms.print', $reimbursement->id) }}" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition inline-block">
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
        <form action="{{ route('reimbursement_forms.approve_dh', $reimbursement->id) }}" method="POST">
            @csrf
            <input type="hidden" name="latitude" id="dh_latitude">
            <input type="hidden" name="longitude" id="dh_longitude">
            <input type="hidden" name="location" id="dh_location">
            <div class="mb-4">
                <p class="text-gray-300 mb-2">Geolocation will be captured automatically.</p>
                <div id="dh_geolocation_status" class="text-sm text-gray-400">Waiting for location...</div>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeApproveDHModal()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
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
        <h3 class="text-xl font-bold text-white mb-4">Approve as Executive (President/Vice President)</h3>
        <form action="{{ route('reimbursement_forms.approve', $reimbursement->id) }}" method="POST">
            @csrf
            <input type="hidden" name="latitude" id="exec_latitude">
            <input type="hidden" name="longitude" id="exec_longitude">
            <input type="hidden" name="location" id="exec_location">
            <div class="mb-4">
                <p class="text-gray-300 mb-2">Geolocation will be captured automatically.</p>
                <div id="exec_geolocation_status" class="text-sm text-gray-400">Waiting for location...</div>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeApproveExecutiveModal()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
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
        <h3 class="text-xl font-bold text-white mb-4">Reject Reimbursement Form</h3>
        <form action="{{ route('reimbursement_forms.reject', $reimbursement->id) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-gray-300 mb-2">Rejection Reason (Optional):</label>
                <textarea name="rejection_reason" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white" rows="4"></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeRejectModal()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
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
