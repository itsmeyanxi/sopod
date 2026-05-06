<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalesOrder;
use App\Models\Deliveries;
use App\Models\MonthlySale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesDashboardController extends Controller
{
    public function index(Request $request)
    {
        // Year filter (for YTD, monthly chart, annual report)
        $year = (int)$request->get('year', Carbon::now()->year);
        $month = Carbon::now()->month;
        $selectedAnnualYear = $request->get('annual_year', Carbon::now()->year);

        // Date range filter (for metrics, top customers, top items)
        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : Carbon::create($year, 1, 1)->startOfYear();
        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : Carbon::create($year, 12, 31)->endOfYear();

        $startOfYear = Carbon::create($year, 1, 1)->startOfYear();
        $endOfYear   = Carbon::create($year, 12, 31)->endOfYear();
        $startOfWeek = Carbon::parse($dateTo)->startOfWeek();
        $endOfWeek   = Carbon::parse($dateTo)->endOfWeek();

        // =================== KEY METRICS ===================

        // Sales for selected date range (PHP & KG)
        $rangeData = DB::table('delivery_items')
            ->join('deliveries', 'delivery_items.delivery_id', '=', 'deliveries.id')
            ->whereBetween('deliveries.request_delivery_date', [$dateFrom, $dateTo])
            ->where('deliveries.status', 'Delivered')
            ->where('deliveries.approval_status', 'Approved')
            ->selectRaw('COALESCE(SUM(delivery_items.total_amount),0) as php, COALESCE(SUM(delivery_items.quantity),0) as kg')
            ->first();
        $rangeSalesPHP = (float)($rangeData->php ?? 0);
        $rangeSalesKG  = (float)($rangeData->kg  ?? 0);

        // Weekly Sales (always current week)
        $weeklySalesPHP = DB::table('delivery_items')
            ->join('deliveries', 'delivery_items.delivery_id', '=', 'deliveries.id')
            ->whereBetween('deliveries.request_delivery_date', [$startOfWeek, $endOfWeek])
            ->where('deliveries.status', 'Delivered')
            ->where('deliveries.approval_status', 'Approved')
            ->sum('delivery_items.total_amount');

        $weeklySalesKG = DB::table('delivery_items')
            ->join('deliveries', 'delivery_items.delivery_id', '=', 'deliveries.id')
            ->whereBetween('deliveries.request_delivery_date', [$startOfWeek, $endOfWeek])
            ->where('deliveries.status', 'Delivered')
            ->where('deliveries.approval_status', 'Approved')
            ->sum('delivery_items.quantity');

        // Year to Date Sales (based on year derived from date_from)
        $ytdSalesPHP = DB::table('delivery_items')
            ->join('deliveries', 'delivery_items.delivery_id', '=', 'deliveries.id')
            ->whereBetween('deliveries.request_delivery_date', [$startOfYear, $dateTo])
            ->where('deliveries.status', 'Delivered')
            ->where('deliveries.approval_status', 'Approved')
            ->sum('delivery_items.total_amount');

        $ytdSalesKG = DB::table('delivery_items')
            ->join('deliveries', 'delivery_items.delivery_id', '=', 'deliveries.id')
            ->whereBetween('deliveries.request_delivery_date', [$startOfYear, $dateTo])
            ->where('deliveries.status', 'Delivered')
            ->where('deliveries.approval_status', 'Approved')
            ->sum('delivery_items.quantity');

        // Count Metrics — filtered by selected date range, all using request_delivery_date
        $totalSalesOrders = Deliveries::whereBetween('request_delivery_date', [$dateFrom, $dateTo])
            ->count();
        $deliveredCount   = Deliveries::whereBetween('request_delivery_date', [$dateFrom, $dateTo])
            ->where('status', 'Delivered')
            ->where('approval_status', 'Approved')
            ->count();
        $pendingSO = SalesOrder::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('status', 'pending')
            ->count();

        // =================== CHARTS DATA ===================

        // Internal customers: NBC, FEI, PMAI
        $internalCustomerPatterns = [
            '%North Breeders Corp%',
            '%FoodSolutions Enterprises%',
            '%Pacific Magalang Agriventures%',
        ];

        // Monthly Breakdown Data (Delivered Only) - Aggregate from delivery_items
        $monthlyBreakdownPHP = [];
        $monthlyBreakdownKG = [];
        $monthlyDataPHP = []; // For charts
        $monthlyDataKG = []; // For charts

        for ($m = 1; $m <= 12; $m++) {
            // Get data from delivery_items (joined with deliveries)
            // ✅ FIXED: Only count Delivered AND Approved deliveries (using request_delivery_date)
            $monthData = DB::table('delivery_items')
                ->join('deliveries', 'delivery_items.delivery_id', '=', 'deliveries.id')
                ->whereYear('deliveries.request_delivery_date', $year)
                ->whereMonth('deliveries.request_delivery_date', $m)
                ->where('deliveries.status', 'Delivered')
                ->where('deliveries.approval_status', 'Approved')
                ->selectRaw('
                    COALESCE(SUM(delivery_items.total_amount), 0) as total_php,
                    COALESCE(SUM(delivery_items.quantity), 0) as total_kg
                ')
                ->first();

            $phpAmount = (float)($monthData->total_php ?? 0);
            $kgAmount = (float)($monthData->total_kg ?? 0);

            // For breakdown table
            $monthlyBreakdownPHP[] = $phpAmount;
            $monthlyBreakdownKG[] = $kgAmount;

            // For charts (same data)
            $monthlyDataPHP[] = $phpAmount;
            $monthlyDataKG[] = $kgAmount;
        }

        // Internal monthly breakdown (NBC, FEI, PMAI)
        $internalMonthly = DB::table('delivery_items')
            ->join('deliveries', 'delivery_items.delivery_id', '=', 'deliveries.id')
            ->whereYear('deliveries.request_delivery_date', $year)
            ->where('deliveries.status', 'Delivered')
            ->where('deliveries.approval_status', 'Approved')
            ->where(function ($q) use ($internalCustomerPatterns) {
                foreach ($internalCustomerPatterns as $i => $pattern) {
                    if ($i === 0) {
                        $q->where('deliveries.customer_name', 'LIKE', $pattern);
                    } else {
                        $q->orWhere('deliveries.customer_name', 'LIKE', $pattern);
                    }
                }
            })
            ->selectRaw('MONTH(deliveries.request_delivery_date) as month_num, COALESCE(SUM(delivery_items.total_amount),0) as total_php, COALESCE(SUM(delivery_items.quantity),0) as total_kg')
            ->groupBy('month_num')
            ->get()
            ->keyBy('month_num');

        // External monthly breakdown (everyone except internal)
        $externalMonthly = DB::table('delivery_items')
            ->join('deliveries', 'delivery_items.delivery_id', '=', 'deliveries.id')
            ->whereYear('deliveries.request_delivery_date', $year)
            ->where('deliveries.status', 'Delivered')
            ->where('deliveries.approval_status', 'Approved')
            ->where(function ($q) use ($internalCustomerPatterns) {
                foreach ($internalCustomerPatterns as $pattern) {
                    $q->where('deliveries.customer_name', 'NOT LIKE', $pattern);
                }
            })
            ->selectRaw('MONTH(deliveries.request_delivery_date) as month_num, COALESCE(SUM(delivery_items.total_amount),0) as total_php, COALESCE(SUM(delivery_items.quantity),0) as total_kg')
            ->groupBy('month_num')
            ->get()
            ->keyBy('month_num');

        $monthlyInternalPHP = [];
        $monthlyInternalKG  = [];
        $monthlyExternalPHP = [];
        $monthlyExternalKG  = [];

        for ($m = 1; $m <= 12; $m++) {
            $int = $internalMonthly->get($m);
            $ext = $externalMonthly->get($m);
            $monthlyInternalPHP[] = (float)($int->total_php ?? 0);
            $monthlyInternalKG[]  = (float)($int->total_kg  ?? 0);
            $monthlyExternalPHP[] = (float)($ext->total_php ?? 0);
            $monthlyExternalKG[]  = (float)($ext->total_kg  ?? 0);
        }

        // Sales per Week (Last 8 weeks) - PHP & KG
        $weeklyDataPHP = [];
        $weeklyDataKG = [];
        $weekLabels = [];

        for ($i = 7; $i >= 0; $i--) {
            $weekStart = Carbon::now()->subWeeks($i)->startOfWeek();
            $weekEnd = Carbon::now()->subWeeks($i)->endOfWeek();
            
            $weekLabels[] = $weekStart->format('M d');
            
            // Get weekly data from delivery_items (using request_delivery_date)
            $weekData = DB::table('delivery_items')
                ->join('deliveries', 'delivery_items.delivery_id', '=', 'deliveries.id')
                ->whereBetween('deliveries.request_delivery_date', [$weekStart, $weekEnd])
                ->where('deliveries.status', 'Delivered')
                ->where('deliveries.approval_status', 'Approved')
                ->selectRaw('
                    COALESCE(SUM(delivery_items.total_amount), 0) as total_php,
                    COALESCE(SUM(delivery_items.quantity), 0) as total_kg
                ')
                ->first();
            
            $weeklyDataPHP[] = (float)($weekData->total_php ?? 0);
            $weeklyDataKG[] = (float)($weekData->total_kg ?? 0);
        }

        // =================== TOP CUSTOMERS ===================
        $topCustomers = DB::table('delivery_items')
            ->join('deliveries', 'delivery_items.delivery_id', '=', 'deliveries.id')
            ->select('deliveries.customer_name')
            ->selectRaw('SUM(delivery_items.total_amount) as total_sales')
            ->whereBetween('deliveries.request_delivery_date', [$dateFrom, $dateTo])
            ->where('deliveries.status', 'Delivered')
            ->where('deliveries.approval_status', 'Approved')
            ->whereNotNull('deliveries.customer_name')
            ->where('deliveries.customer_name', '!=', '')
            ->groupBy('deliveries.customer_name')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get();

        // No fallback — keep results scoped to the selected date range

        // =================== TOP 5 ITEMS ===================
        $topItems = DB::table('delivery_items')
            ->join('deliveries', 'delivery_items.delivery_id', '=', 'deliveries.id')
            ->selectRaw('delivery_items.item_description, SUM(delivery_items.quantity) as total_quantity, delivery_items.item_code')
            ->whereBetween('deliveries.request_delivery_date', [$dateFrom, $dateTo])
            ->where('deliveries.status', 'Delivered')
            ->where('deliveries.approval_status', 'Approved')
            ->whereNotNull('delivery_items.item_description')
            ->where('delivery_items.item_description', '!=', '')
            ->groupBy('delivery_items.item_description', 'delivery_items.item_code')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get();

        // No fallback — keep results scoped to the selected date range

        // Sales by Status — filtered by selected date range
        $salesByStatus = Deliveries::selectRaw('status, COUNT(*) as count')
            ->whereBetween('request_delivery_date', [$dateFrom, $dateTo])
            ->groupBy('status')
            ->get();

        // Recent Deliveries — filtered by selected date range
        $recentDeliveries = Deliveries::whereBetween('request_delivery_date', [$dateFrom, $dateTo])
            ->orderBy('request_delivery_date', 'desc')
            ->limit(10)
            ->get();

        // =================== ANNUAL REPORT DATA ===================
        $annualData = [];
        $annualTotalPHP = 0;
        $annualTotalKG = 0;
        
        $allMonthlySales = MonthlySale::orderBy('id', 'asc')->get();
        
        $monthlyDataMap = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlyDataMap[$m] = [
                'php' => 0,
                'kg' => 0,
                'orders' => 0
            ];
        }
        
        foreach ($allMonthlySales as $record) {
            $monthStr = trim($record->month);
            $yearMatch = null;
            $monthNum = null;
            
            if (preg_match('/(\d{4})-(\d{1,2})/', $monthStr, $matches)) {
                $yearMatch = (int)$matches[1];
                $monthNum = (int)$matches[2];
            }
            elseif (preg_match('/([A-Za-z]+)\s+(\d{4})/', $monthStr, $matches)) {
                $monthName = $matches[1];
                $yearMatch = (int)$matches[2];
                $date = date_create($monthName . ' 1, ' . $yearMatch);
                if ($date) {
                    $monthNum = (int)date_format($date, 'n');
                }
            }
            elseif (preg_match('/(\d{4})\/(\d{1,2})/', $monthStr, $matches)) {
                $yearMatch = (int)$matches[1];
                $monthNum = (int)$matches[2];
            }
            elseif (preg_match('/(\d{1,2})-(\d{4})/', $monthStr, $matches)) {
                $monthNum = (int)$matches[1];
                $yearMatch = (int)$matches[2];
            }
            elseif (preg_match('/^([A-Za-z]+)$/i', $monthStr, $matches)) {
                $monthName = $matches[1];
                $date = date_create($monthName . ' 1');
                if ($date) {
                    $monthNum = (int)date_format($date, 'n');
                    $yearMatch = $selectedAnnualYear;
                }
            }
            
            if ($yearMatch == $selectedAnnualYear && $monthNum >= 1 && $monthNum <= 12) {
                $phpAmount = (float)($record->php_amount ?? 0);
                $kgAmount = (float)($record->quantity ?? 0);
                
                $monthlyDataMap[$monthNum]['php'] += $phpAmount;
                $monthlyDataMap[$monthNum]['kg'] += $kgAmount;
                
                $annualTotalPHP += $phpAmount;
                $annualTotalKG += $kgAmount;
            }
        }
        
        for ($m = 1; $m <= 12; $m++) {
            $annualData[] = $monthlyDataMap[$m];
        }
        
        // Fallback: calculate from delivery_items if monthly_sales is empty
        if ($annualTotalPHP == 0 && $annualTotalKG == 0) {
            $annualData = [];
            $annualTotalPHP = 0;
            $annualTotalKG = 0;
            
            for ($m = 1; $m <= 12; $m++) {
                $monthData = DB::table('delivery_items')
                    ->join('deliveries', 'delivery_items.delivery_id', '=', 'deliveries.id')
                    ->whereYear('deliveries.request_delivery_date', $selectedAnnualYear)
                    ->whereMonth('deliveries.request_delivery_date', $m)
                    ->where('deliveries.status', 'Delivered')
                    ->where('deliveries.approval_status', 'Approved')
                    ->selectRaw('
                        COALESCE(SUM(delivery_items.total_amount), 0) as total_php,
                        COALESCE(SUM(delivery_items.quantity), 0) as total_kg,
                        COUNT(DISTINCT deliveries.id) as order_count
                    ')
                    ->first();
                
                $phpAmount = (float)($monthData->total_php ?? 0);
                $kgAmount = (float)($monthData->total_kg ?? 0);
                $orderCount = (int)($monthData->order_count ?? 0);
                
                $annualData[] = [
                    'php' => $phpAmount,
                    'kg' => $kgAmount,
                    'orders' => $orderCount
                ];
                
                $annualTotalPHP += $phpAmount;
                $annualTotalKG += $kgAmount;
            }
        }

        return view('sales.dashboard', compact(
            'dateFrom',
            'dateTo',
            'rangeSalesPHP',
            'rangeSalesKG',
            'weeklySalesPHP',
            'ytdSalesPHP',
            'weeklySalesKG',
            'ytdSalesKG',
            'totalSalesOrders',
            'deliveredCount',
            'pendingSO',
            'monthlyDataPHP',
            'monthlyDataKG',
            'weeklyDataPHP',
            'weeklyDataKG',
            'weekLabels',
            'topCustomers',
            'topItems',
            'salesByStatus',
            'recentDeliveries',
            'year',
            'month',
            'selectedAnnualYear',
            'annualData',
            'annualTotalPHP',
            'annualTotalKG',
            'monthlyBreakdownPHP',
            'monthlyBreakdownKG',
            'monthlyInternalPHP',
            'monthlyInternalKG',
            'monthlyExternalPHP',
            'monthlyExternalKG'
        ));
    }

    public function getMetrics(Request $request)
    {
        $period = $request->get('period', 'month');
        
        $startDate = match($period) {
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth()
        };
        
        $endDate = match($period) {
            'week' => Carbon::now()->endOfWeek(),
            'month' => Carbon::now()->endOfMonth(),
            'year' => Carbon::now()->endOfYear(),
            default => Carbon::now()->endOfMonth()
        };

        $salesPHP = DB::table('delivery_items')
            ->join('deliveries', 'delivery_items.delivery_id', '=', 'deliveries.id')
            ->whereBetween('deliveries.request_delivery_date', [$startDate, $endDate])
            ->where('deliveries.status', 'Delivered')
            ->where('deliveries.approval_status', 'Approved')
            ->sum('delivery_items.total_amount');

        $salesKG = DB::table('delivery_items')
            ->join('deliveries', 'delivery_items.delivery_id', '=', 'deliveries.id')
            ->whereBetween('deliveries.request_delivery_date', [$startDate, $endDate])
            ->where('deliveries.status', 'Delivered')
            ->where('deliveries.approval_status', 'Approved')
            ->sum('delivery_items.quantity');

        return response()->json([
            'period' => $period,
            'sales_php' => number_format($salesPHP, 2),
            'sales_kg' => number_format($salesKG, 2),
            'delivered_count' => Deliveries::where('status','Delivered')->where('approval_status', 'Approved')->count(),
            'pending_so' => SalesOrder::where('status', 'pending')->count(),
        ]);
    }
}