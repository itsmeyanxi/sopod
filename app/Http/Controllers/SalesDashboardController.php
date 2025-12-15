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
        // Get filter parameters
        $year = $request->get('year', Carbon::now()->year);
        $month = $request->get('month', Carbon::now()->month);
        $selectedAnnualYear = $request->get('annual_year', Carbon::now()->year);
        
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

        // Monthly Sales (KG)
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

        // =================== TOP CUSTOMERS ===================
        $topCustomers = SalesOrder::select('customer_name')
            ->selectRaw('SUM(total_amount) as total_sales')
            ->whereYear('created_at', $year)
            ->whereNotNull('customer_name')
            ->where('customer_name', '!=', '')
            ->groupBy('customer_name')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get();

        // Debug: If still empty, try without year filter
        if ($topCustomers->isEmpty() || $topCustomers->sum('total_sales') == 0) {
            $topCustomers = SalesOrder::select('customer_name')
                ->selectRaw('SUM(total_amount) as total_sales')
                ->whereNotNull('customer_name')
                ->where('customer_name', '!=', '')
                ->groupBy('customer_name')
                ->orderByDesc('total_sales')
                ->limit(5)
                ->get();
        }

// =================== TOP 5 ITEMS ===================
// Use delivery_items table with join to deliveries for date filtering
$topItems = DB::table('delivery_items')
    ->join('deliveries', 'delivery_items.delivery_id', '=', 'deliveries.id')
    ->selectRaw('delivery_items.item_description, SUM(delivery_items.quantity) as total_quantity, delivery_items.item_code')
    ->whereYear('deliveries.created_at', $year)
    ->whereNotNull('delivery_items.item_description')
    ->where('delivery_items.item_description', '!=', '')
    ->groupBy('delivery_items.item_description', 'delivery_items.item_code')
    ->orderByDesc('total_quantity')
    ->limit(5)
    ->get();

// If empty with year filter, try without year filter
if ($topItems->isEmpty() || $topItems->sum('total_quantity') == 0) {
    $topItems = DB::table('delivery_items')
        ->selectRaw('item_description, SUM(quantity) as total_quantity, item_code')
        ->whereNotNull('item_description')
        ->where('item_description', '!=', '')
        ->groupBy('item_description', 'item_code')
        ->orderByDesc('total_quantity')
        ->limit(5)
        ->get();
}

        // Sales by Status
        $salesByStatus = Deliveries::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        // Recent Deliveries
        $recentDeliveries = Deliveries::orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // =================== ANNUAL REPORT DATA ===================
        // Use MonthlySale model (exactly like Records controller)
        $annualData = [];
        $annualTotalPHP = 0;
        $annualTotalKG = 0;
        
        // Get ALL monthly sales records (same as Records controller)
        $allMonthlySales = MonthlySale::orderBy('id', 'asc')->get();
        
        // Initialize all 12 months with zero values
        $monthlyDataMap = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlyDataMap[$m] = [
                'php' => 0,
                'kg' => 0,
                'orders' => 0
            ];
        }
        
        // Filter and parse monthly sales
        foreach ($allMonthlySales as $record) {
            $monthStr = trim($record->month);
            
            // Parse month string - support multiple formats
            $yearMatch = null;
            $monthNum = null;
            
            // Try: "2024-01" or "2024-1" or "2024-01-01"
            if (preg_match('/(\d{4})-(\d{1,2})/', $monthStr, $matches)) {
                $yearMatch = (int)$matches[1];
                $monthNum = (int)$matches[2];
            }
            // Try: "January 2024" or "Jan 2024"
            elseif (preg_match('/([A-Za-z]+)\s+(\d{4})/', $monthStr, $matches)) {
                $monthName = $matches[1];
                $yearMatch = (int)$matches[2];
                $date = date_create($monthName . ' 1, ' . $yearMatch);
                if ($date) {
                    $monthNum = (int)date_format($date, 'n');
                }
            }
            // Try: "2024/01" format
            elseif (preg_match('/(\d{4})\/(\d{1,2})/', $monthStr, $matches)) {
                $yearMatch = (int)$matches[1];
                $monthNum = (int)$matches[2];
            }
            // Try: "01-2024" format (reverse)
            elseif (preg_match('/(\d{1,2})-(\d{4})/', $monthStr, $matches)) {
                $monthNum = (int)$matches[1];
                $yearMatch = (int)$matches[2];
            }
            // NEW: Handle month name only (no year) - "January", "February", etc.
            elseif (preg_match('/^([A-Za-z]+)$/i', $monthStr, $matches)) {
                $monthName = $matches[1];
                $date = date_create($monthName . ' 1');
                if ($date) {
                    $monthNum = (int)date_format($date, 'n');
                    // Since there's no year in the data, show it for ALL years
                    // or treat it as current/most recent data
                    $yearMatch = $selectedAnnualYear; // Show for selected year
                }
            }
            
            // Validate and add data
            if ($yearMatch == $selectedAnnualYear && $monthNum >= 1 && $monthNum <= 12) {
                $phpAmount = (float)($record->php_amount ?? 0);
                $kgAmount = (float)($record->quantity ?? 0);
                
                // Add to existing value (in case of duplicates)
                $monthlyDataMap[$monthNum]['php'] += $phpAmount;
                $monthlyDataMap[$monthNum]['kg'] += $kgAmount;
                
                $annualTotalPHP += $phpAmount;
                $annualTotalKG += $kgAmount;
            }
        }
        
        // Convert map to indexed array (0-11) for the view
        for ($m = 1; $m <= 12; $m++) {
            $annualData[] = $monthlyDataMap[$m];
        }
        
        // Fallback: If monthly_sales is empty, calculate from Deliveries
        if ($annualTotalPHP == 0 && $annualTotalKG == 0) {
            $annualData = [];
            $annualTotalPHP = 0;
            $annualTotalKG = 0;
            
            for ($m = 1; $m <= 12; $m++) {
                $monthData = Deliveries::whereYear('created_at', $selectedAnnualYear)
                    ->whereMonth('created_at', $m)
                    ->selectRaw('
                        COALESCE(SUM(total_amount), 0) as total_php,
                        COALESCE(SUM(quantity), 0) as total_kg,
                        COUNT(*) as order_count
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
            'month',
            'selectedAnnualYear',
            'annualData',
            'annualTotalPHP',
            'annualTotalKG'
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