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
        try {
            $soLockedCount = SalesOrder::whereBetween('created_at', [$startDate, $endDate])
                ->where('is_locked', true)->count();
        } catch (\Exception $e) {
            $soLockedCount = 0;
        }

        $deliveryCount = Deliveries::whereBetween('request_delivery_date', [$startDate, $endDate])->count();
        try {
            $deliveryLockedCount = Deliveries::whereBetween('request_delivery_date', [$startDate, $endDate])
                ->where('is_locked', true)->count();
        } catch (\Exception $e) {
            $deliveryLockedCount = 0;
        }

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
        // TODO: Implement lock logic
        return response()->json(['success' => true, 'message' => 'Lock functionality coming soon']);
    }

    /**
     * Unlock records for a specific month
     */
    public function unlock(Request $request)
    {
        // TODO: Implement unlock logic
        return response()->json(['success' => true, 'message' => 'Unlock functionality coming soon']);
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

        // Get sales orders (handle is_locked column not existing)
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
                    'is_locked' => $so->is_locked ?? false,
                    'created_at' => $so->created_at,
                ];
            });

        // Get deliveries (handle is_locked column not existing)
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
                    'is_locked' => $d->is_locked ?? false,
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
