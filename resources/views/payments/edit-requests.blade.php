@extends('layouts.app')

@section('title', 'Payment Edit Requests')

@section('content')
<div class="container mx-auto">
    <div class="mb-4">
        <a href="{{ route('payments.entry') }}" class="text-sm text-gray-300 hover:text-gray-200">
            <i class="fas fa-arrow-left mr-1"></i> Back to Payments
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-900/40 border border-green-600 text-green-300 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-900/40 border border-red-600 text-red-300 p-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-4">
            <h2 class="text-xl font-bold text-white">Payment Edit Requests</h2>
            <p class="text-orange-100 text-sm mt-0.5">Review and approve/reject payment edit requests</p>
        </div>

        <div class="p-6">
            @if($requests->isEmpty())
                <div class="text-center py-8 text-gray-400">
                    <i class="fas fa-inbox text-4xl mb-2"></i>
                    <p>No edit requests found.</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($requests as $req)
                    @php
                        $original = json_decode($req->original_data, true);
                        $proposed = json_decode($req->proposed_data, true);
                        $isPending = $req->status === 'Pending';
                        $fields = ['collection_receipt_number' => 'CR Number', 'collection_receipt_date' => 'CR Date', 'payment_posting_date' => 'Posting Date', 'dr_no' => 'DR No', 'invoice_no' => 'Invoice No', 'amount' => 'Amount', 'tax' => 'EWT', 'net' => 'Net', 'payment_method' => 'Payment Method', 'bank' => 'Bank', 'reference_no' => 'Reference', 'payment_notes' => 'Notes'];
                    @endphp
                    <div class="border rounded-lg {{ $isPending ? 'border-orange-700 bg-orange-900/20' : ($req->status === 'Approved' ? 'border-green-700 bg-green-900/20' : 'border-red-700 bg-red-900/20') }}">
                        <div class="px-4 py-3 flex justify-between items-start">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-white">{{ $req->customer_name }}</span>
                                    <span class="text-xs text-gray-300">CR #{{ $req->cr_number }}</span>
                                    <span class="px-2 py-0.5 rounded text-xs font-bold
                                        {{ $isPending ? 'bg-orange-900/40 text-orange-300 border border-orange-700' : ($req->status === 'Approved' ? 'bg-green-900/40 text-green-300 border border-green-700' : 'bg-red-900/40 text-red-300 border border-red-700') }}">
                                        {{ $req->status }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-300 mt-1">
                                    Requested by <strong>{{ $req->requested_by_name }}</strong>
                                    on {{ \Carbon\Carbon::parse($req->created_at)->format('M d, Y h:i A') }}
                                </p>
                                @if($req->reason)
                                <p class="text-sm text-gray-300 mt-1"><strong>Reason:</strong> {{ $req->reason }}</p>
                                @endif
                                @if($req->attachment_path)
                                <p class="text-xs text-blue-400 mt-1">
                                    <i class="fas fa-paperclip mr-1"></i>
                                    <a href="{{ asset('storage/' . $req->attachment_path) }}" target="_blank" class="hover:underline">
                                        {{ $req->attachment_name ?? 'View Attachment' }}
                                    </a>
                                </p>
                                @endif
                            </div>
                            <a href="{{ route('payments.show', $req->payment_id) }}" class="text-blue-400 hover:text-blue-300 text-xs">
                                <i class="fas fa-eye mr-1"></i>View Payment
                            </a>
                        </div>

                        <!-- Changes diff -->
                        <div class="px-4 pb-3">
                            <table class="w-full text-xs border-collapse">
                                <thead>
                                    <tr class="border-b border-gray-700">
                                        <th class="text-left py-1 text-gray-300 w-1/4">Field</th>
                                        <th class="text-left py-1 text-gray-300 w-3/8">Original</th>
                                        <th class="text-left py-1 text-gray-300 w-3/8">Proposed</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($fields as $key => $label)
                                        @if(($original[$key] ?? '') != ($proposed[$key] ?? ''))
                                        <tr class="border-b border-gray-700">
                                            <td class="py-1 font-medium text-gray-300">{{ $label }}</td>
                                            <td class="py-1 text-red-400 line-through">{{ $original[$key] ?? '—' }}</td>
                                            <td class="py-1 text-green-400 font-semibold">{{ $proposed[$key] ?? '—' }}</td>
                                        </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($isPending)
                            @if(auth()->user()->canApprovePaymentEditRequests())
                            <div class="px-4 py-3 border-t border-gray-700 flex items-center gap-2">
                                <form action="{{ route('payments.approveEditRequest', $req->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-1.5 rounded text-sm font-medium transition"
                                        onclick="return confirm('Approve this edit request? The payment will be updated immediately.')">
                                        <i class="fas fa-check mr-1"></i>Approve
                                    </button>
                                </form>
                                <form action="{{ route('payments.rejectEditRequest', $req->id) }}" method="POST" class="inline flex items-center gap-2">
                                    @csrf
                                    <input type="text" name="review_notes" placeholder="Rejection reason (optional)" class="bg-gray-800 border border-gray-600 rounded px-2 py-1.5 text-sm text-gray-200 w-48">
                                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-1.5 rounded text-sm font-medium transition"
                                        onclick="return confirm('Reject this edit request?')">
                                        <i class="fas fa-times mr-1"></i>Reject
                                    </button>
                                </form>
                            </div>
                            @else
                            <div class="px-4 py-2 border-t border-gray-700 text-xs text-orange-400">
                                <i class="fas fa-clock mr-1"></i> Awaiting approval from Joey Fernandez
                            </div>
                            @endif
                        @else
                        <div class="px-4 py-2 border-t border-gray-700 text-xs text-gray-300">
                            {{ $req->status }} by {{ $req->reviewed_by_name ?? 'System' }}
                            on {{ $req->reviewed_at ? \Carbon\Carbon::parse($req->reviewed_at)->format('M d, Y h:i A') : '—' }}
                            @if($req->review_notes)
                                — {{ $req->review_notes }}
                            @endif
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
