<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalesOrder;
use App\Models\Deliveries;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesDashboardController extends Controller
{
    public function index(Request $request)
    {
        // Get filter parameters
        $year = $request->get('year', Carbon::now()->year);
        $month = $request->get('month', Carbon::now()->month);
        
        // Calculate date ranges
        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth();
        $startOfYear = Carbon::create($year, 1, 1)->startOfYear();
        $endOfYear = Carbon::create($year, 12, 31)->endOfYear();
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        // =================== KEY METRICS ===================
        
        // Monthly Sales (PHP)
        $monthlySalesPHP = Deliveries::whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('total_amount');

        // Weekly Sales (PHP)
        $weeklySalesPHP = Deliveries::whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->sum('total_amount');

        // Year to Date Sales (PHP)
        $ytdSalesPHP = Deliveries::whereBetween('created_at', [$startOfYear, $endOfYear])
            ->sum('total_amount');

        // Monthly Sales (KG) - sum all quantities (assuming they represent weight)
        $monthlySalesKG = Deliveries::whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('quantity');

        // Weekly Sales (KG)
        $weeklySalesKG = Deliveries::whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->sum('quantity');

        // Year to Date Sales (KG)
        $ytdSalesKG = Deliveries::whereBetween('created_at', [$startOfYear, $endOfYear])
            ->sum('quantity');

        // Count Metrics
        $totalSalesOrders = SalesOrder::count();
        $deliveredCount = Deliveries::whereIn('status', ['delivered', 'completed'])->count();
        $pendingSO = SalesOrder::where('status', 'pending')->count();

        // =================== CHARTS DATA ===================
        
        // Sales per Month (Current Year) - PHP
        $salesPerMonthPHP = Deliveries::selectRaw('MONTH(created_at) as month, SUM(total_amount) as total')
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        // Sales per Month (Current Year) - KG
        $salesPerMonthKG = Deliveries::selectRaw('MONTH(created_at) as month, SUM(quantity) as total')
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        // Fill missing months with 0
        $monthlyDataPHP = [];
        $monthlyDataKG = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyDataPHP[] = $salesPerMonthPHP[$i] ?? 0;
            $monthlyDataKG[] = $salesPerMonthKG[$i] ?? 0;
        }

        // Sales per Week (Last 8 weeks) - PHP & KG
        $weeklyDataPHP = [];
        $weeklyDataKG = [];
        $weekLabels = [];
        
        for ($i = 7; $i >= 0; $i--) {
            $weekStart = Carbon::now()->subWeeks($i)->startOfWeek();
            $weekEnd = Carbon::now()->subWeeks($i)->endOfWeek();
            
            $weekLabels[] = $weekStart->format('M d');
            
            $weeklyDataPHP[] = Deliveries::whereBetween('created_at', [$weekStart, $weekEnd])
                ->sum('total_amount');
                
            $weeklyDataKG[] = Deliveries::whereBetween('created_at', [$weekStart, $weekEnd])
                ->sum('quantity');
        }

        // Top 5 Customers by Sales
        $topCustomers = Deliveries::selectRaw('customer_name, SUM(total_amount) as total_sales')
            ->whereBetween('created_at', [$startOfYear, $endOfYear])
            ->groupBy('customer_name')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get();

        // Top 5 Items by Quantity Sold
        $topItems = Deliveries::selectRaw('item_description, SUM(quantity) as total_quantity, item_code')
            ->whereBetween('created_at', [$startOfYear, $endOfYear])
            ->groupBy('item_description', 'item_code')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get();

        // Sales by Status
        $salesByStatus = Deliveries::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        // Recent Deliveries
        $recentDeliveries = Deliveries::orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('sales.dashboard', compact(
            'monthlySalesPHP',
            'weeklySalesPHP',
            'ytdSalesPHP',
            'monthlySalesKG',
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
            'month'
        ));
    }

    // API endpoint for real-time data updates
    public function getMetrics(Request $request)
    {
        $period = $request->get('period', 'month'); // month, week, year
        
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

        $salesPHP = Deliveries::whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_amount');

        $salesKG = Deliveries::whereBetween('created_at', [$startDate, $endDate])
            ->sum('quantity');

        return response()->json([
            'period' => $period,
            'sales_php' => number_format($salesPHP, 2),
            'sales_kg' => number_format($salesKG, 2),
            'delivered_count' => Deliveries::whereIn('status', ['delivered', 'completed'])->count(),
            'pending_so' => SalesOrder::where('status', 'pending')->count(),
        ]);
    }
}