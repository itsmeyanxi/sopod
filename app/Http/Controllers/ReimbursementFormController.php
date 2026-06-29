<?php

namespace App\Http\Controllers;

use App\Models\ReimbursementForm;
use App\Models\ReimbursementFormItem;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ReimbursementFormController extends Controller
{
    public function index()
    {
        // Check if user can access reimbursement forms
        if (!Auth::user()->canAccessReimbursementForms()) {
            abort(403, 'Unauthorized action.');
        }

        $reimbursements = ReimbursementForm::with(['creator'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('reimbursement_forms.index', compact('reimbursements'));
    }

    public function create()
    {
        // Check if user can create reimbursement forms
        if (!Auth::user()->canCreateReimbursementForms()) {
            abort(403, 'Unauthorized action.');
        }

        do {
            $riNo = 'RI-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (ReimbursementForm::where('ri_no', $riNo)->exists());

        return view('reimbursement_forms.create', compact('riNo'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'department' => 'required|string|max:255',
            'date_applied' => 'required|date',
            'amount_to_be_reimbursed' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.particulars' => 'required|string',
            'items.*.cost' => 'required|numeric|min:0',
            'items.*.date' => 'nullable|string|max:100',
            'proof_documents' => 'nullable|array',
            'proof_documents.*' => 'nullable|file|mimes:doc,docx,odf,jpg,jpeg,png,gif,bmp,webp|max:10240',
        ]);

        DB::beginTransaction();
        try {
            do {
                $riNo = 'RI-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            } while (ReimbursementForm::where('ri_no', $riNo)->exists());

            $totalAmountSpent = collect($request->items)->sum('cost');

            // Handle proof documents upload
            $proofDocuments = [];
            if ($request->hasFile('proof_documents')) {
                foreach ($request->file('proof_documents') as $file) {
                    $path = $file->store('reimbursement_forms', 'public');
                    $proofDocuments[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ];
                }
            }

            $reimbursement = ReimbursementForm::create([
                'ri_no' => $riNo,
                'department' => $request->department,
                'date_applied' => $request->date_applied,
                'total_amount_spent' => $totalAmountSpent,
                'amount_to_be_reimbursed' => $request->amount_to_be_reimbursed,
                'submitted_by' => $request->submitted_by,
                'checked_by' => $request->checked_by,
                'approved_by_name' => $request->approved_by_name,
                'remarks' => $request->remarks,
                'proof_documents' => !empty($proofDocuments) ? $proofDocuments : null,
                'status' => 'pending',
                'approval_stage' => 'pending_dh',
                'created_by' => Auth::id(),
            ]);

            foreach ($request->items as $item) {
                ReimbursementFormItem::create([
                    'reimbursement_form_id' => $reimbursement->id,
                    'date' => $item['date'] ?? null,
                    'particulars' => $item['particulars'],
                    'cost' => $item['cost'],
                ]);
            }

            DB::commit();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'Created',
                'item' => $reimbursement->ri_no,
                'target' => $reimbursement->department,
                'type' => 'Reimbursement Form',
                'message' => 'Created Reimbursement Form ' . $reimbursement->ri_no . ' for ' . $reimbursement->department,
            ]);

            return redirect()
                ->route('reimbursement_forms.show', $reimbursement->id)
                ->with('success', 'Reimbursement Form created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error creating Reimbursement Form: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $reimbursement = ReimbursementForm::with(['creator', 'dhApprover', 'executiveApprover', 'items'])
            ->findOrFail($id);

        return view('reimbursement_forms.show', compact('reimbursement'));
    }

    public function edit($id)
    {
        $reimbursement = ReimbursementForm::with(['items'])->findOrFail($id);

        if ($reimbursement->status === 'approved') {
            return redirect()->route('reimbursement_forms.show', $reimbursement->id)
                ->with('error', 'Cannot edit an approved Reimbursement Form.');
        }

        return view('reimbursement_forms.edit', compact('reimbursement'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'department' => 'required|string|max:255',
            'date_applied' => 'required|date',
            'amount_to_be_reimbursed' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.particulars' => 'required|string',
            'items.*.cost' => 'required|numeric|min:0',
            'items.*.date' => 'nullable|string|max:100',
            'proof_documents' => 'nullable|array',
            'proof_documents.*' => 'nullable|file|mimes:doc,docx,odf,jpg,jpeg,png,gif,bmp,webp|max:10240',
        ]);

        DB::beginTransaction();
        try {
            $reimbursement = ReimbursementForm::findOrFail($id);
            $totalAmountSpent = collect($request->items)->sum('cost');

            // Handle proof documents upload
            $proofDocuments = $reimbursement->proof_documents ?? [];
            if ($request->hasFile('proof_documents')) {
                foreach ($request->file('proof_documents') as $file) {
                    $path = $file->store('reimbursement_forms', 'public');
                    $proofDocuments[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ];
                }
            }

            $reimbursement->update([
                'department' => $request->department,
                'date_applied' => $request->date_applied,
                'total_amount_spent' => $totalAmountSpent,
                'amount_to_be_reimbursed' => $request->amount_to_be_reimbursed,
                'submitted_by' => $request->submitted_by,
                'checked_by' => $request->checked_by,
                'approved_by_name' => $request->approved_by_name,
                'remarks' => $request->remarks,
                'proof_documents' => !empty($proofDocuments) ? $proofDocuments : null,
            ]);

            $reimbursement->items()->delete();
            foreach ($request->items as $item) {
                ReimbursementFormItem::create([
                    'reimbursement_form_id' => $reimbursement->id,
                    'date' => $item['date'] ?? null,
                    'particulars' => $item['particulars'],
                    'cost' => $item['cost'],
                ]);
            }

            DB::commit();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'Updated',
                'item' => $reimbursement->ri_no,
                'target' => $reimbursement->department,
                'type' => 'Reimbursement Form',
                'message' => 'Updated Reimbursement Form ' . $reimbursement->ri_no,
            ]);

            return redirect()
                ->route('reimbursement_forms.show', $reimbursement->id)
                ->with('success', 'Reimbursement Form updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error updating Reimbursement Form: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $reimbursement = ReimbursementForm::findOrFail($id);
            $riNo = $reimbursement->ri_no;
            $reimbursement->delete();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'Deleted',
                'item' => $riNo,
                'target' => 'N/A',
                'type' => 'Reimbursement Form',
                'message' => 'Deleted Reimbursement Form ' . $riNo,
            ]);

            return redirect()
                ->route('reimbursement_forms.index')
                ->with('success', 'Reimbursement Form deleted successfully!');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Error deleting Reimbursement Form: ' . $e->getMessage());
        }
    }

    public function approveDH(Request $request, $id)
    {
        // Check if user can approve reimbursement forms
        if (!Auth::user()->canApproveReimbursementForms()) {
            abort(403, 'Unauthorized action.');
        }

        $reimbursement = ReimbursementForm::findOrFail($id);
        if ($reimbursement->approval_stage !== 'pending_dh') {
            return redirect()->back()->with('error', 'This reimbursement is no longer pending DH approval (current stage: ' . $reimbursement->approval_stage . ').');
        }
        $reimbursement->update([
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
            'item' => $reimbursement->ri_no,
            'target' => $reimbursement->department,
            'type' => 'Reimbursement Form',
            'message' => 'Department Head checked Reimbursement Form ' . $reimbursement->ri_no,
        ]);

        return redirect()->back()->with('success', 'Reimbursement Form checked by Department Head. Forwarded to Executive.');
    }

    public function approve(Request $request, $id)
    {
        // Check if user can approve reimbursement forms
        if (!Auth::user()->canApproveReimbursementForms()) {
            abort(403, 'Unauthorized action.');
        }

        $reimbursement = ReimbursementForm::findOrFail($id);
        if ($reimbursement->approval_stage !== 'pending_executive') {
            return redirect()->back()->with('error', 'This reimbursement is not yet ready for executive approval (current stage: ' . $reimbursement->approval_stage . ').');
        }
        $reimbursement->update([
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
            'item' => $reimbursement->ri_no,
            'target' => $reimbursement->department,
            'type' => 'Reimbursement Form',
            'message' => 'Approved Reimbursement Form ' . $reimbursement->ri_no,
        ]);

        return redirect()->back()->with('success', 'Reimbursement Form approved!');
    }

    public function reject(Request $request, $id)
    {
        // Check if user can approve/reject reimbursement forms
        if (!Auth::user()->canApproveReimbursementForms()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate(['rejection_reason' => 'nullable|string|max:500']);
        $reimbursement = ReimbursementForm::findOrFail($id);

        $reimbursement->update([
            'status' => 'rejected',
            'approval_stage' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Rejected',
            'item' => $reimbursement->ri_no,
            'target' => $reimbursement->department,
            'type' => 'Reimbursement Form',
            'message' => 'Rejected Reimbursement Form ' . $reimbursement->ri_no,
        ]);

        return redirect()->back()->with('success', 'Reimbursement Form rejected!');
    }

    public function print($id)
    {
        $reimbursement = ReimbursementForm::with(['creator', 'dhApprover', 'executiveApprover', 'items'])->findOrFail($id);
        return view('reimbursement_forms.print', compact('reimbursement'));
    }
}
