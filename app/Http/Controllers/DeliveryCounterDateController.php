<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Deliveries;
use App\Models\ArAging;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DeliveryCounterDateController extends Controller
{
    /**
     * Show deliveries list with counter date management
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $filter = $request->input('filter', 'all'); // all, with_counter, without_counter
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = Deliveries::where('status', 'Delivered')
            ->orderBy('request_delivery_date', 'desc');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'LIKE', "%{$search}%")
                  ->orWhere('customer_code', 'LIKE', "%{$search}%")
                  ->orWhere('dr_no', 'LIKE', "%{$search}%")
                  ->orWhere('sales_invoice_no', 'LIKE', "%{$search}%")
                  ->orWhere('delivery_batch', 'LIKE', "%{$search}%");
            });
        }

        if ($filter === 'with_counter') {
            $query->whereNotNull('counter_date');
        } elseif ($filter === 'without_counter') {
            $query->whereNull('counter_date');
        }

        if ($dateFrom) {
            $query->whereDate('request_delivery_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('request_delivery_date', '<=', $dateTo);
        }

        $deliveries = $query->paginate(50);

        // Stats
        $totalDelivered = Deliveries::where('status', 'Delivered')->count();
        $withCounter = Deliveries::where('status', 'Delivered')->whereNotNull('counter_date')->count();
        $withoutCounter = $totalDelivered - $withCounter;

        return view('delivery_counter_dates.index', compact(
            'deliveries', 'search', 'filter', 'dateFrom', 'dateTo',
            'totalDelivered', 'withCounter', 'withoutCounter'
        ));
    }

    /**
     * Update counter date for a single delivery and sync to ar_aging
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'counter_date' => 'required|date',
        ]);

        $delivery = Deliveries::findOrFail($id);
        $delivery->update([
            'counter_date' => $request->counter_date,
            'counter_date_approved' => false,
            'counter_date_approved_by' => null,
            'counter_date_approved_at' => null,
        ]);

        // Sync to ar_aging by DR number
        $synced = $this->syncToArAging($delivery);

        $msg = "Counter date set to " . Carbon::parse($request->counter_date)->format('M d, Y') . " for DR {$delivery->dr_no}.";
        if ($synced > 0) {
            $msg .= " {$synced} aging record(s) updated.";
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Bulk update counter dates
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'updates' => 'required|array',
            'updates.*.id' => 'required|exists:deliveries,id',
            'updates.*.counter_date' => 'required|date',
        ]);

        DB::beginTransaction();
        try {
            $totalSynced = 0;
            $updated = 0;

            foreach ($request->updates as $item) {
                $delivery = Deliveries::find($item['id']);
                if ($delivery) {
                    $delivery->update([
                        'counter_date' => $item['counter_date'],
                        'counter_date_approved' => false,
                        'counter_date_approved_by' => null,
                        'counter_date_approved_at' => null,
                    ]);
                    $totalSynced += $this->syncToArAging($delivery);
                    $updated++;
                }
            }

            DB::commit();

            $msg = "{$updated} delivery counter date(s) updated.";
            if ($totalSynced > 0) {
                $msg .= " {$totalSynced} aging record(s) synced.";
            }

            return response()->json(['success' => true, 'message' => $msg]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk counter date update failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Clear counter date for a delivery
     */
    public function clear(Request $request, $id)
    {
        $delivery = Deliveries::findOrFail($id);
        $drNo = $delivery->dr_no;
        $delivery->update(['counter_date' => null]);

        // Also clear in ar_aging
        if ($drNo) {
            ArAging::where('dr_no', $drNo)->update(['counter_date' => null]);
        }

        $msg = "Counter date cleared for DR {$drNo}.";

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Sync counter date from delivery to ar_aging records
     */
    private function syncToArAging(Deliveries $delivery): int
    {
        $synced = 0;

        if ($delivery->dr_no && $delivery->counter_date) {
            $synced = ArAging::where('dr_no', $delivery->dr_no)
                ->update(['counter_date' => $delivery->counter_date]);
        }

        return $synced;
    }
}
