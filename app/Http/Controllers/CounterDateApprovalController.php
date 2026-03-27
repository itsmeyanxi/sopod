<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Deliveries;
use App\Models\ArAging;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CounterDateApprovalController extends Controller
{
    /**
     * Show deliveries with counter dates pending approval
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $filter = $request->input('filter', 'pending'); // pending, approved, all

        $query = Deliveries::where('status', 'Delivered')
            ->whereNotNull('counter_date');

        if ($filter === 'pending') {
            $query->where(function ($q) {
                $q->where('counter_date_approved', false)
                  ->orWhereNull('counter_date_approved');
            });
        } elseif ($filter === 'approved') {
            $query->where('counter_date_approved', true);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'LIKE', "%{$search}%")
                  ->orWhere('customer_code', 'LIKE', "%{$search}%")
                  ->orWhere('dr_no', 'LIKE', "%{$search}%")
                  ->orWhere('sales_invoice_no', 'LIKE', "%{$search}%");
            });
        }

        $deliveries = $query->orderBy('counter_date', 'desc')->paginate(50);

        // Stats
        $totalWithCounter = Deliveries::where('status', 'Delivered')->whereNotNull('counter_date')->count();
        $pendingCount = Deliveries::where('status', 'Delivered')
            ->whereNotNull('counter_date')
            ->where(function ($q) {
                $q->where('counter_date_approved', false)->orWhereNull('counter_date_approved');
            })->count();
        $approvedCount = Deliveries::where('status', 'Delivered')
            ->whereNotNull('counter_date')
            ->where('counter_date_approved', true)->count();

        return view('counter_date_approvals.index', compact(
            'deliveries', 'search', 'filter',
            'totalWithCounter', 'pendingCount', 'approvedCount'
        ));
    }

    /**
     * Show delivery detail with counter date info and attachment
     */
    public function show($id)
    {
        $delivery = Deliveries::with('items')->findOrFail($id);

        // Check if there's a linked ar_aging record
        $arAging = ArAging::where('dr_no', $delivery->dr_no)->first();

        return view('counter_date_approvals.show', compact('delivery', 'arAging'));
    }

    /**
     * Upload attachment for a delivery
     */
    public function uploadAttachment(Request $request, $id)
    {
        $request->validate([
            'attachment' => 'required|file|max:5120|mimes:pdf,png,jpg,jpeg',
        ]);

        $delivery = Deliveries::findOrFail($id);

        $file = $request->file('attachment');
        $attachmentName = $file->getClientOriginalName();
        $attachmentPath = $file->store('counter-date-attachments', 'public');

        $delivery->update([
            'counter_date_attachment_path' => $attachmentPath,
            'counter_date_attachment_name' => $attachmentName,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully.',
            'attachment_name' => $attachmentName,
            'attachment_path' => $attachmentPath,
        ]);
    }

    /**
     * Approve a single delivery counter date and push to aging reports
     */
    public function approve(Request $request, $id)
    {
        $delivery = Deliveries::with('items')->findOrFail($id);

        if (!$delivery->counter_date) {
            return response()->json(['success' => false, 'message' => 'No counter date set.'], 422);
        }

        DB::beginTransaction();
        try {
            $now = Carbon::now();

            // Mark as approved
            $delivery->update([
                'counter_date_approved' => true,
                'counter_date_approved_by' => Auth::user()->name ?? 'System',
                'counter_date_approved_at' => $now,
            ]);

            // Sync to ar_aging: update existing record or create new one
            $this->syncToAgingReport($delivery, $now);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Counter date approved for DR {$delivery->dr_no}. Added to Aging Reports.",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Counter date approval failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Bulk approve selected deliveries
     */
    public function bulkApprove(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:deliveries,id',
        ]);

        DB::beginTransaction();
        try {
            $now = Carbon::now();
            $approved = 0;

            foreach ($request->ids as $id) {
                $delivery = Deliveries::with('items')->find($id);
                if ($delivery && $delivery->counter_date && !$delivery->counter_date_approved) {
                    $delivery->update([
                        'counter_date_approved' => true,
                        'counter_date_approved_by' => Auth::user()->name ?? 'System',
                        'counter_date_approved_at' => $now,
                    ]);

                    $this->syncToAgingReport($delivery, $now);
                    $approved++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$approved} delivery counter date(s) approved and added to Aging Reports.",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk counter date approval failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Sync approved delivery to ar_aging table
     *
     * aging_date = approval date (now)
     * counter_date = user-inputted counter date from delivery
     * invoice_date = delivery request_delivery_date
     * record_date = keep existing or use now
     */
    private function syncToAgingReport(Deliveries $delivery, Carbon $approvalDate): void
    {
        $existing = ArAging::where('dr_no', $delivery->dr_no)->first();

        // Get total amount from delivery items
        $invoiceAmount = $delivery->items ? $delivery->items->sum('total_amount') : 0;

        $data = [
            'aging_date' => $approvalDate->format('Y-m-d'),
            'counter_date' => $delivery->counter_date,
            'invoice_date' => $delivery->request_delivery_date,
            'dr_no' => $delivery->dr_no,
            'invoice_no' => $delivery->sales_invoice_no,
            'customer_code' => $delivery->customer_code,
            'client_name' => $delivery->customer_name,
            'branch' => $delivery->branch,
            'sales_executive' => $delivery->sales_executive,
            'invoice_amount' => $invoiceAmount,
        ];

        if ($existing) {
            // Update existing ar_aging record — also update amount if it was 0
            if ((float)$existing->invoice_amount == 0 && $invoiceAmount > 0) {
                $data['net_ar_balance'] = $invoiceAmount;
                $data['gross_ar_balance'] = $invoiceAmount;
            }
            $existing->update($data);
        } else {
            // Create new ar_aging record
            $data['record_date'] = $approvalDate->format('Y-m-d');
            $data['status'] = 'Outstanding';
            $data['settled_invoice_amount'] = 0;
            $data['net_ar_balance'] = $invoiceAmount;
            $data['gross_ar_balance'] = $invoiceAmount;
            ArAging::create($data);
        }
    }
}
