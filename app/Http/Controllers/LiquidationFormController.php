<?php

namespace App\Http\Controllers;

use App\Models\LiquidationForm;
use App\Models\LiquidationFormItem;
use App\Models\CashAdvanceRequest;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class LiquidationFormController extends Controller
{
    public function index()
    {
        $liquidations = LiquidationForm::with(['creator', 'cashAdvanceRequest'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('liquidation_forms.index', compact('liquidations'));
    }

    public function create(Request $request)
    {
        do {
            $liqNo = 'LIQ-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (LiquidationForm::where('liq_no', $liqNo)->exists());

        $selectedCAR = null;
        if ($request->has('car_id')) {
            $selectedCAR = CashAdvanceRequest::where('status', 'approved')->find($request->car_id);
        }

        return view('liquidation_forms.create', compact('liqNo', 'selectedCAR'));
    }

    public function searchCARs(Request $request)
    {
        $searchTerm = $request->input('search', '');

        try {
            $cars = CashAdvanceRequest::where('status', 'approved')
                ->where(function ($query) use ($searchTerm) {
                    $query->where('car_no', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('payee', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('department', 'LIKE', "%{$searchTerm}%");
                })
                ->limit(10)
                ->get()
                ->map(function ($car) {
                    return [
                        'id' => $car->id,
                        'car_no' => $car->car_no,
                        'payee' => $car->payee,
                        'department' => $car->department,
                        'amount' => (float) $car->amount_advanced,
                        'purpose' => $car->purpose,
                        'date_requested' => $car->date_requested?->format('Y-m-d'),
                    ];
                });

            return response()->json($cars);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'date_applied' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.particulars' => 'required|string',
            'items.*.amount' => 'required|numeric|min:0',
            'proof_documents' => 'nullable|array',
            'proof_documents.*' => 'nullable|file|mimes:doc,docx,odf,jpg,jpeg,png,gif,bmp,webp|max:10240',
        ]);

        DB::beginTransaction();
        try {
            do {
                $liqNo = 'LIQ-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            } while (LiquidationForm::where('liq_no', $liqNo)->exists());

            $totalAmountSpent = collect($request->items)->sum('amount');

            // Handle proof documents upload
            $proofDocuments = [];
            if ($request->hasFile('proof_documents')) {
                foreach ($request->file('proof_documents') as $file) {
                    $path = $file->store('liquidation_forms', 'public');
                    $proofDocuments[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ];
                }
            }

            $liquidation = LiquidationForm::create([
                'liq_no' => $liqNo,
                'cash_advance_request_id' => $request->cash_advance_request_id,
                'name' => $request->name,
                'department' => $request->department,
                'date_applied' => $request->date_applied,
                'total_amount_spent' => $totalAmountSpent,
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
                LiquidationFormItem::create([
                    'liquidation_form_id' => $liquidation->id,
                    'particulars' => $item['particulars'],
                    'amount' => $item['amount'],
                ]);
            }

            DB::commit();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'Created',
                'item' => $liquidation->liq_no,
                'target' => $liquidation->name,
                'type' => 'Liquidation Form',
                'message' => 'Created Liquidation Form ' . $liquidation->liq_no . ' for ' . $liquidation->name,
            ]);

            return redirect()
                ->route('liquidation_forms.show', $liquidation->id)
                ->with('success', 'Liquidation Form created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error creating Liquidation Form: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $liquidation = LiquidationForm::with(['creator', 'dhApprover', 'executiveApprover', 'items', 'cashAdvanceRequest'])
            ->findOrFail($id);

        return view('liquidation_forms.show', compact('liquidation'));
    }

    public function edit($id)
    {
        $liquidation = LiquidationForm::with(['items', 'cashAdvanceRequest'])->findOrFail($id);

        if ($liquidation->status === 'approved') {
            return redirect()->route('liquidation_forms.show', $liquidation->id)
                ->with('error', 'Cannot edit an approved Liquidation Form.');
        }

        return view('liquidation_forms.edit', compact('liquidation'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'date_applied' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.particulars' => 'required|string',
            'items.*.amount' => 'required|numeric|min:0',
            'proof_documents' => 'nullable|array',
            'proof_documents.*' => 'nullable|file|mimes:doc,docx,odf,jpg,jpeg,png,gif,bmp,webp|max:10240',
        ]);

        DB::beginTransaction();
        try {
            $liquidation = LiquidationForm::findOrFail($id);
            $totalAmountSpent = collect($request->items)->sum('amount');

            // Handle proof documents upload
            $proofDocuments = $liquidation->proof_documents ?? [];
            if ($request->hasFile('proof_documents')) {
                foreach ($request->file('proof_documents') as $file) {
                    $path = $file->store('liquidation_forms', 'public');
                    $proofDocuments[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ];
                }
            }

            $liquidation->update([
                'cash_advance_request_id' => $request->cash_advance_request_id,
                'name' => $request->name,
                'department' => $request->department,
                'date_applied' => $request->date_applied,
                'total_amount_spent' => $totalAmountSpent,
                'submitted_by' => $request->submitted_by,
                'checked_by' => $request->checked_by,
                'approved_by_name' => $request->approved_by_name,
                'remarks' => $request->remarks,
                'proof_documents' => !empty($proofDocuments) ? $proofDocuments : null,
            ]);

            // Delete old items and recreate
            $liquidation->items()->delete();
            foreach ($request->items as $item) {
                LiquidationFormItem::create([
                    'liquidation_form_id' => $liquidation->id,
                    'particulars' => $item['particulars'],
                    'amount' => $item['amount'],
                ]);
            }

            DB::commit();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'Updated',
                'item' => $liquidation->liq_no,
                'target' => $liquidation->name,
                'type' => 'Liquidation Form',
                'message' => 'Updated Liquidation Form ' . $liquidation->liq_no,
            ]);

            return redirect()
                ->route('liquidation_forms.show', $liquidation->id)
                ->with('success', 'Liquidation Form updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error updating Liquidation Form: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $liquidation = LiquidationForm::findOrFail($id);
            $liqNo = $liquidation->liq_no;
            $liquidation->delete();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'Deleted',
                'item' => $liqNo,
                'target' => 'N/A',
                'type' => 'Liquidation Form',
                'message' => 'Deleted Liquidation Form ' . $liqNo,
            ]);

            return redirect()
                ->route('liquidation_forms.index')
                ->with('success', 'Liquidation Form deleted successfully!');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Error deleting Liquidation Form: ' . $e->getMessage());
        }
    }

    public function approveDH(Request $request, $id)
    {
        $liquidation = LiquidationForm::findOrFail($id);
        if ($liquidation->approval_stage !== 'pending_dh') {
            return redirect()->back()->with('error', 'This Liquidation Form is not awaiting Immediate Superior approval.');
        }
        $liquidation->update([
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
            'item' => $liquidation->liq_no,
            'target' => $liquidation->name,
            'type' => 'Liquidation Form',
            'message' => 'Immediate Superior checked Liquidation Form ' . $liquidation->liq_no,
        ]);

        return redirect()->back()->with('success', 'Liquidation Form checked by Immediate Superior. Forwarded to Executive.');
    }

    public function approve(Request $request, $id)
    {
        $liquidation = LiquidationForm::findOrFail($id);
        if ($liquidation->approval_stage !== 'pending_executive') {
            return redirect()->back()->with('error', 'This Liquidation Form is not awaiting Executive approval.');
        }
        $liquidation->update([
            'status' => 'approved',
            'approval_stage' => 'approved',
            'executive_approved_by' => Auth::id(),
            'executive_approved_at' => now(),
            'executive_approved_latitude' => $request->input('latitude'),
            'executive_approved_longitude' => $request->input('longitude'),
            'executive_approved_location' => $request->input('location'),
            'rejection_reason' => null,
        ]);

        // Mark the linked Cash Advance Request as liquidated
        if ($liquidation->cash_advance_request_id) {
            $car = CashAdvanceRequest::find($liquidation->cash_advance_request_id);
            if ($car && $car->status === 'approved') {
                $car->update(['status' => 'liquidated']);
            }
        }

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Approved',
            'item' => $liquidation->liq_no,
            'target' => $liquidation->name,
            'type' => 'Liquidation Form',
            'message' => 'Approved Liquidation Form ' . $liquidation->liq_no,
        ]);

        return redirect()->back()->with('success', 'Liquidation Form approved!');
    }

    public function reject(Request $request, $id)
    {
        $request->validate(['rejection_reason' => 'nullable|string|max:500']);
        $liquidation = LiquidationForm::findOrFail($id);

        $liquidation->update([
            'status' => 'rejected',
            'approval_stage' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Rejected',
            'item' => $liquidation->liq_no,
            'target' => $liquidation->name,
            'type' => 'Liquidation Form',
            'message' => 'Rejected Liquidation Form ' . $liquidation->liq_no,
        ]);

        return redirect()->back()->with('success', 'Liquidation Form rejected!');
    }

    public function print($id)
    {
        $liquidation = LiquidationForm::with(['creator', 'dhApprover', 'executiveApprover', 'items', 'cashAdvanceRequest'])->findOrFail($id);
        return view('liquidation_forms.print', compact('liquidation'));
    }
}
