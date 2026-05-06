<?php

namespace App\Http\Controllers;

use App\Models\CashAdvanceRequest;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CashAdvanceRequestController extends Controller
{
    public function index()
    {
        // Check if user can access cash advance requests
        if (!Auth::user()->canAccessCashAdvanceRequests()) {
            abort(403, 'Unauthorized action.');
        }

        $cars = CashAdvanceRequest::with(['creator'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('cash_advance_requests.index', compact('cars'));
    }

    public function create()
    {
        // Check if user can create cash advance requests
        if (!Auth::user()->canCreateCashAdvanceRequests()) {
            abort(403, 'Unauthorized action.');
        }

        do {
            $carNo = 'CAR-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (CashAdvanceRequest::where('car_no', $carNo)->exists());

        return view('cash_advance_requests.create', compact('carNo'));
    }

    public function store(Request $request)
    {
        // Check if user can create cash advance requests
        if (!Auth::user()->canCreateCashAdvanceRequests()) {
            abort(403, 'Unauthorized action.');
        }

        $hasFile = $request->hasFile('attachment');
        $required = $hasFile ? 'nullable' : 'required';

        $request->validate([
            'payee'            => "$required|string|max:255",
            'department'       => "$required|string|max:255",
            'purpose'          => "$required|string",
            'date_requested'   => "$required|date",
            'date_needed'      => "$required|date",
            'amount_advanced'  => "$required|numeric|min:0",
            'attachment'       => 'nullable|file|mimes:pdf,jpg,jpeg,png,xlsx,xls,doc,docx|max:10240',
            'car_no'           => 'nullable|string|max:100',
        ]);

        DB::beginTransaction();
        try {
            $submittedCarNo = trim($request->car_no ?? '');
            if ($submittedCarNo && !CashAdvanceRequest::where('car_no', $submittedCarNo)->exists()) {
                $carNo = $submittedCarNo;
            } else {
                do {
                    $carNo = 'CAR-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                } while (CashAdvanceRequest::where('car_no', $carNo)->exists());
            }

            $attachmentPath = null;
            $attachmentName = null;
            if ($hasFile) {
                $file = $request->file('attachment');
                $attachmentName = $file->getClientOriginalName();
                $attachmentPath = $file->store('car-attachments', 'public');
            }

            $car = CashAdvanceRequest::create([
                'car_no'          => $carNo,
                'payee'           => $request->payee,
                'department'      => $request->department,
                'purpose'         => $request->purpose,
                'date_requested'  => $request->date_requested,
                'date_needed'     => $request->date_needed,
                'amount_advanced' => $request->amount_advanced,
                'requested_by'    => $request->requested_by,
                'checked_by'      => $request->checked_by,
                'approved_by_name'=> $request->approved_by_name,
                'remarks'         => $request->remarks,
                'attachment_path' => $attachmentPath,
                'attachment_name' => $attachmentName,
                'status'          => 'pending',
                'approval_stage'  => 'pending_dh',
                'created_by'      => Auth::id(),
            ]);

            DB::commit();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'Created',
                'item' => $car->car_no,
                'target' => $car->payee,
                'type' => 'Cash Advance Request',
                'message' => 'Created Cash Advance Request ' . $car->car_no . ' for ' . $car->payee,
            ]);

            return redirect()
                ->route('cash_advance_requests.show', $car->id)
                ->with('success', 'Cash Advance Request created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error creating Cash Advance Request: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $car = CashAdvanceRequest::with(['creator', 'dhApprover', 'executiveApprover', 'liquidationForms'])
            ->findOrFail($id);

        return view('cash_advance_requests.show', compact('car'));
    }

    public function edit($id)
    {
        // Check if user can create/edit cash advance requests
        if (!Auth::user()->canCreateCashAdvanceRequests()) {
            abort(403, 'Unauthorized action.');
        }

        $car = CashAdvanceRequest::findOrFail($id);

        if ($car->status === 'approved' || $car->status === 'liquidated') {
            return redirect()->route('cash_advance_requests.show', $car->id)
                ->with('error', 'Cannot edit an approved or liquidated Cash Advance Request.');
        }

        return view('cash_advance_requests.edit', compact('car'));
    }

    public function update(Request $request, $id)
    {
        // Check if user can create/edit cash advance requests
        if (!Auth::user()->canCreateCashAdvanceRequests()) {
            abort(403, 'Unauthorized action.');
        }

        $car = CashAdvanceRequest::findOrFail($id);
        $hasFile = $request->hasFile('attachment');
        $hasExisting = !empty($car->attachment_path);
        $required = ($hasFile || $hasExisting) ? 'nullable' : 'required';

        $request->validate([
            'payee'           => "$required|string|max:255",
            'department'      => "$required|string|max:255",
            'purpose'         => "$required|string",
            'date_requested'  => "$required|date",
            'date_needed'     => "$required|date",
            'amount_advanced' => "$required|numeric|min:0",
            'attachment'      => 'nullable|file|mimes:pdf,jpg,jpeg,png,xlsx,xls,doc,docx|max:10240',
            'car_no'          => 'nullable|string|max:100',
        ]);

        DB::beginTransaction();
        try {
            $attachmentPath = $car->attachment_path;
            $attachmentName = $car->attachment_name;
            if ($hasFile) {
                $file = $request->file('attachment');
                $attachmentName = $file->getClientOriginalName();
                $attachmentPath = $file->store('car-attachments', 'public');
            }

            $submittedCarNo = trim($request->car_no ?? '');
            $newCarNo = ($submittedCarNo && $submittedCarNo !== $car->car_no && !CashAdvanceRequest::where('car_no', $submittedCarNo)->exists())
                ? $submittedCarNo
                : $car->car_no;

            $car->update([
                'car_no'          => $newCarNo,
                'payee'           => $request->payee,
                'department'      => $request->department,
                'purpose'         => $request->purpose,
                'date_requested'  => $request->date_requested,
                'date_needed'     => $request->date_needed,
                'amount_advanced' => $request->amount_advanced,
                'requested_by'    => $request->requested_by,
                'checked_by'      => $request->checked_by,
                'approved_by_name'=> $request->approved_by_name,
                'remarks'         => $request->remarks,
                'attachment_path' => $attachmentPath,
                'attachment_name' => $attachmentName,
            ]);

            DB::commit();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'Updated',
                'item' => $car->car_no,
                'target' => $car->payee,
                'type' => 'Cash Advance Request',
                'message' => 'Updated Cash Advance Request ' . $car->car_no,
            ]);

            return redirect()
                ->route('cash_advance_requests.show', $car->id)
                ->with('success', 'Cash Advance Request updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error updating Cash Advance Request: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $car = CashAdvanceRequest::findOrFail($id);
            $carNo = $car->car_no;
            $car->delete();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'Deleted',
                'item' => $carNo,
                'target' => 'N/A',
                'type' => 'Cash Advance Request',
                'message' => 'Deleted Cash Advance Request ' . $carNo,
            ]);

            return redirect()
                ->route('cash_advance_requests.index')
                ->with('success', 'Cash Advance Request deleted successfully!');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Error deleting Cash Advance Request: ' . $e->getMessage());
        }
    }

    public function approveDH(Request $request, $id)
    {
        // Check if user can approve cash advance requests
        if (!Auth::user()->canApproveCashAdvanceRequests()) {
            abort(403, 'Unauthorized action.');
        }

        $car = CashAdvanceRequest::where('approval_stage', 'pending_dh')->findOrFail($id);
        $car->update([
            'approval_stage' => 'pending_executive',
            'dh_approved_by' => Auth::id(),
            'dh_approved_at' => now(),
            'dh_approved_latitude' => $request->input('latitude'),
            'dh_approved_longitude' => $request->input('longitude'),
            'dh_approved_location' => $request->input('location'),
            'rejection_reason' => null,
        ]);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Approved (DH)',
            'item' => $car->car_no,
            'target' => $car->payee,
            'type' => 'Cash Advance Request',
            'message' => 'Department Head checked Cash Advance Request ' . $car->car_no,
        ]);

        return redirect()->back()->with('success', 'Cash Advance Request checked by Department Head. Forwarded to Executive.');
    }

    public function approve(Request $request, $id)
    {
        // Check if user can approve cash advance requests
        if (!Auth::user()->canApproveCashAdvanceRequests()) {
            abort(403, 'Unauthorized action.');
        }

        $car = CashAdvanceRequest::where('approval_stage', 'pending_executive')->findOrFail($id);
        $car->update([
            'status' => 'approved',
            'approval_stage' => 'approved',
            'executive_approved_by' => Auth::id(),
            'executive_approved_at' => now(),
            'executive_approved_latitude' => $request->input('latitude'),
            'executive_approved_longitude' => $request->input('longitude'),
            'executive_approved_location' => $request->input('location'),
            'rejection_reason' => null,
        ]);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Approved',
            'item' => $car->car_no,
            'target' => $car->payee,
            'type' => 'Cash Advance Request',
            'message' => 'Approved Cash Advance Request ' . $car->car_no,
        ]);

        return redirect()->back()->with('success', 'Cash Advance Request approved!');
    }

    public function reject(Request $request, $id)
    {
        // Check if user can approve/reject cash advance requests
        if (!Auth::user()->canApproveCashAdvanceRequests()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate(['rejection_reason' => 'nullable|string|max:500']);
        $car = CashAdvanceRequest::findOrFail($id);

        $car->update([
            'status' => 'rejected',
            'approval_stage' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Rejected',
            'item' => $car->car_no,
            'target' => $car->payee,
            'type' => 'Cash Advance Request',
            'message' => 'Rejected Cash Advance Request ' . $car->car_no,
        ]);

        return redirect()->back()->with('success', 'Cash Advance Request rejected!');
    }

    public function print($id)
    {
        $car = CashAdvanceRequest::with(['creator', 'dhApprover', 'executiveApprover'])->findOrFail($id);
        return view('cash_advance_requests.print', compact('car'));
    }
}
