<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalesOrder;
use App\Models\Deliveries;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LockController extends Controller
{
    /**
     * Display the lock management page
     */
    public function index(Request $request)
    {
        // Get filter parameters
        $filterYear = $request->get('year', Carbon::now()->year);
        $filterMonth = $request->get('month', Carbon::now()->month);

        $startDate = Carbon::create($filterYear, $filterMonth, 1)->startOfMonth();
        $endDate = Carbon::create($filterYear, $filterMonth, 1)->endOfMonth();

        // Get counts for the selected month
        $soCount = SalesOrder::whereBetween('created_at', [$startDate, $endDate])->count();
        $soLockedCount = SalesOrder::whereBetween('created_at', [$startDate, $endDate])
            ->where('is_locked', true)->count();

        $deliveryCount = Deliveries::whereBetween('request_delivery_date', [$startDate, $endDate])->count();
        $deliveryLockedCount = Deliveries::whereBetween('request_delivery_date', [$startDate, $endDate])
            ->where('is_locked', true)->count();

        // Delivery status breakdown
        $deliveryStatusCounts = Deliveries::whereBetween('request_delivery_date', [$startDate, $endDate])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $monthsData = [[
            'year' => (int) $filterYear,
            'month' => (int) $filterMonth,
            'month_name' => Carbon::create($filterYear, $filterMonth, 1)->format('F Y'),
            'so_count' => $soCount,
            'so_locked_count' => $soLockedCount,
            'delivery_count' => $deliveryCount,
            'delivery_locked_count' => $deliveryLockedCount,
            'delivery_status_counts' => $deliveryStatusCounts,
            'is_fully_locked' => ($soCount > 0 && $soCount === $soLockedCount) &&
                                 ($deliveryCount > 0 && $deliveryCount === $deliveryLockedCount),
        ]];

        return view('lock.index', compact('monthsData', 'filterYear', 'filterMonth'));
    }

    /**
     * Lock records for a specific month
     */
    public function lock(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|between:1,12',
        ]);

        $year = $request->input('year');
        $month = $request->input('month');

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        try {
            DB::beginTransaction();

            $soLocked = SalesOrder::whereBetween('created_at', [$startDate, $endDate])
                ->where('is_locked', false)
                ->update(['is_locked' => true]);

            $deliveryLocked = Deliveries::whereBetween('request_delivery_date', [$startDate, $endDate])
                ->where('is_locked', false)
                ->update(['is_locked' => true]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Locked {$soLocked} Sales Order(s) and {$deliveryLocked} Delivery/ies.",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to lock records: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unlock records for a specific month
     */
    public function unlock(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|between:1,12',
        ]);

        $year = $request->input('year');
        $month = $request->input('month');

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        try {
            DB::beginTransaction();

            $soUnlocked = SalesOrder::whereBetween('created_at', [$startDate, $endDate])
                ->where('is_locked', true)
                ->update(['is_locked' => false]);

            $deliveryUnlocked = Deliveries::whereBetween('request_delivery_date', [$startDate, $endDate])
                ->where('is_locked', true)
                ->update(['is_locked' => false]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Unlocked {$soUnlocked} Sales Order(s) and {$deliveryUnlocked} Delivery/ies.",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to unlock records: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get details for a specific month
     */
    public function getMonthDetails(Request $request)
    {
        $year = $request->input('year');
        $month = $request->input('month');

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $salesOrders = SalesOrder::whereBetween('created_at', [$startDate, $endDate])
            ->with('customer:id,customer_name')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($so) {
                return [
                    'id' => $so->id,
                    'sales_order_number' => $so->sales_order_number,
                    'customer' => $so->customer,
                    'total_amount' => $so->total_amount,
                    'status' => $so->status,
                    'is_locked' => (bool) $so->is_locked,
                    'created_at' => $so->created_at,
                ];
            });

        $deliveries = Deliveries::whereBetween('request_delivery_date', [$startDate, $endDate])
            ->orderBy('request_delivery_date', 'desc')
            ->get()
            ->map(function ($d) {
                return [
                    'id' => $d->id,
                    'dr_no' => $d->dr_no,
                    'sales_order_number' => $d->sales_order_number,
                    'customer_name' => $d->customer_name,
                    'status' => $d->status,
                    'is_locked' => (bool) $d->is_locked,
                    'created_at' => $d->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'sales_orders' => $salesOrders,
            'deliveries' => $deliveries,
        ]);
    }
}
