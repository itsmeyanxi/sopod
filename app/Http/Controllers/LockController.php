<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalesOrder;
use App\Models\Deliveries;
use App\Models\Customer;
use App\Models\Item;
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

        // Consolidated counts (1 query per table instead of 2)
        $soCounts = SalesOrder::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN is_locked = 1 THEN 1 ELSE 0 END) as locked')
            ->first();
        $soCount = (int) $soCounts->total;
        $soLockedCount = (int) $soCounts->locked;

        $deliveryCounts = Deliveries::whereBetween('request_delivery_date', [$startDate, $endDate])
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN is_locked = 1 THEN 1 ELSE 0 END) as locked')
            ->first();
        $deliveryCount = (int) $deliveryCounts->total;
        $deliveryLockedCount = (int) $deliveryCounts->locked;

        $customerCounts = Customer::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN is_locked = 1 THEN 1 ELSE 0 END) as locked')
            ->first();
        $customerCount = (int) $customerCounts->total;
        $customerLockedCount = (int) $customerCounts->locked;

        $itemCounts = Item::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN is_locked = 1 THEN 1 ELSE 0 END) as locked')
            ->first();
        $itemCount = (int) $itemCounts->total;
        $itemLockedCount = (int) $itemCounts->locked;

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
            'customer_count' => $customerCount,
            'customer_locked_count' => $customerLockedCount,
            'item_count' => $itemCount,
            'item_locked_count' => $itemLockedCount,
            'delivery_status_counts' => $deliveryStatusCounts,
            'is_fully_locked' => ($soCount > 0 && $soCount === $soLockedCount) &&
                                 ($deliveryCount > 0 && $deliveryCount === $deliveryLockedCount) &&
                                 ($customerCount === 0 || $customerCount === $customerLockedCount) &&
                                 ($itemCount === 0 || $itemCount === $itemLockedCount),
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

            $customerLocked = Customer::whereBetween('created_at', [$startDate, $endDate])
                ->where('is_locked', false)
                ->update(['is_locked' => true]);

            $itemLocked = Item::whereBetween('created_at', [$startDate, $endDate])
                ->where('is_locked', false)
                ->update(['is_locked' => true]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Locked {$soLocked} Sales Order(s), {$deliveryLocked} Delivery/ies, {$customerLocked} Customer(s), and {$itemLocked} Item(s).",
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

            $customerUnlocked = Customer::whereBetween('created_at', [$startDate, $endDate])
                ->where('is_locked', true)
                ->update(['is_locked' => false]);

            $itemUnlocked = Item::whereBetween('created_at', [$startDate, $endDate])
                ->where('is_locked', true)
                ->update(['is_locked' => false]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Unlocked {$soUnlocked} Sales Order(s), {$deliveryUnlocked} Delivery/ies, {$customerUnlocked} Customer(s), and {$itemUnlocked} Item(s).",
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
     * Unlock a single delivery by ID
     */
    public function unlockDelivery(Request $request, $id)
    {
        try {
            $delivery = Deliveries::findOrFail($id);

            if (!$delivery->is_locked) {
                return response()->json(['success' => false, 'message' => 'This delivery is already unlocked.']);
            }

            $delivery->is_locked = false;
            $delivery->save();

            return response()->json([
                'success' => true,
                'message' => "Delivery DR {$delivery->dr_no} has been unlocked.",
                'dr_no' => $delivery->dr_no,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unlock delivery: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lock a single delivery by ID
     */
    public function lockDelivery(Request $request, $id)
    {
        try {
            $delivery = Deliveries::findOrFail($id);

            if ($delivery->is_locked) {
                return response()->json(['success' => false, 'message' => 'This delivery is already locked.']);
            }

            $delivery->is_locked = true;
            $delivery->save();

            return response()->json([
                'success' => true,
                'message' => "Delivery DR {$delivery->dr_no} has been locked.",
                'dr_no' => $delivery->dr_no,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to lock delivery: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search deliveries across all months by DR number, customer, or SO number
     */
    public function searchDeliveries(Request $request)
    {
        $search = $request->input('search', '');
        if (empty(trim($search))) {
            return response()->json(['success' => false, 'message' => 'Please enter a search term.']);
        }

        $deliveries = Deliveries::where(function ($q) use ($search) {
                $q->where('dr_no', 'LIKE', "%{$search}%")
                  ->orWhere('customer_name', 'LIKE', "%{$search}%")
                  ->orWhere('sales_order_number', 'LIKE', "%{$search}%");
            })
            ->select('id', 'dr_no', 'sales_order_number', 'customer_name', 'status', 'is_locked', 'request_delivery_date')
            ->orderBy('request_delivery_date', 'desc')
            ->limit(50)
            ->get()
            ->map(fn($d) => [
                'id' => $d->id,
                'dr_no' => $d->dr_no,
                'sales_order_number' => $d->sales_order_number,
                'customer_name' => $d->customer_name,
                'status' => $d->status,
                'is_locked' => (bool) $d->is_locked,
                'delivery_date' => $d->request_delivery_date,
            ]);

        return response()->json(['success' => true, 'data' => $deliveries, 'total' => $deliveries->count()]);
    }

    /**
     * Get details for a specific month (paginated per tab)
     */
    public function getMonthDetails(Request $request)
    {
        $year = $request->input('year');
        $month = $request->input('month');
        $tab = $request->input('tab', 'so');
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 50;

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $result = ['success' => true, 'tab' => $tab, 'page' => $page, 'per_page' => $perPage];

        switch ($tab) {
            case 'so':
                $query = SalesOrder::whereBetween('created_at', [$startDate, $endDate]);
                $result['total'] = $query->count();
                $result['data'] = $query
                    ->select('id', 'sales_order_number', 'customer_id', 'total_amount', 'status', 'is_locked', 'created_at')
                    ->with('customer:id,customer_name')
                    ->orderBy('created_at', 'desc')
                    ->offset(($page - 1) * $perPage)
                    ->limit($perPage)
                    ->get()
                    ->map(fn($so) => [
                        'id' => $so->id,
                        'sales_order_number' => $so->sales_order_number,
                        'customer' => $so->customer,
                        'total_amount' => $so->total_amount,
                        'status' => $so->status,
                        'is_locked' => (bool) $so->is_locked,
                        'created_at' => $so->created_at,
                    ]);
                break;

            case 'delivery':
                $query = Deliveries::whereBetween('request_delivery_date', [$startDate, $endDate]);
                if ($request->filled('search')) {
                    $searchTerm = $request->input('search');
                    $query->where(function ($q) use ($searchTerm) {
                        $q->where('dr_no', 'LIKE', "%{$searchTerm}%")
                          ->orWhere('customer_name', 'LIKE', "%{$searchTerm}%")
                          ->orWhere('sales_order_number', 'LIKE', "%{$searchTerm}%");
                    });
                }
                $result['total'] = $query->count();
                $result['data'] = $query
                    ->select('id', 'dr_no', 'sales_order_number', 'customer_name', 'status', 'is_locked', 'created_at')
                    ->orderBy('request_delivery_date', 'desc')
                    ->offset(($page - 1) * $perPage)
                    ->limit($perPage)
                    ->get()
                    ->map(fn($d) => [
                        'id' => $d->id,
                        'dr_no' => $d->dr_no,
                        'sales_order_number' => $d->sales_order_number,
                        'customer_name' => $d->customer_name,
                        'status' => $d->status,
                        'is_locked' => (bool) $d->is_locked,
                        'created_at' => $d->created_at,
                    ]);
                break;

            case 'customer':
                $query = Customer::whereBetween('created_at', [$startDate, $endDate]);
                $result['total'] = $query->count();
                $result['data'] = $query
                    ->select('id', 'customer_code', 'customer_name', 'status', 'is_locked', 'created_at')
                    ->orderBy('created_at', 'desc')
                    ->offset(($page - 1) * $perPage)
                    ->limit($perPage)
                    ->get()
                    ->map(fn($c) => [
                        'id' => $c->id,
                        'customer_code' => $c->customer_code,
                        'customer_name' => $c->customer_name,
                        'status' => $c->status,
                        'is_locked' => (bool) $c->is_locked,
                        'created_at' => $c->created_at,
                    ]);
                break;

            case 'item':
                $query = Item::whereBetween('created_at', [$startDate, $endDate]);
                $result['total'] = $query->count();
                $result['data'] = $query
                    ->select('id', 'item_code', 'item_description', 'approval_status', 'is_locked', 'created_at')
                    ->orderBy('created_at', 'desc')
                    ->offset(($page - 1) * $perPage)
                    ->limit($perPage)
                    ->get()
                    ->map(fn($i) => [
                        'id' => $i->id,
                        'item_code' => $i->item_code,
                        'item_description' => $i->item_description,
                        'approval_status' => $i->approval_status,
                        'is_locked' => (bool) $i->is_locked,
                        'created_at' => $i->created_at,
                    ]);
                break;
        }

        $result['total_pages'] = isset($result['total']) ? ceil($result['total'] / $perPage) : 0;

        return response()->json($result);
    }
}
