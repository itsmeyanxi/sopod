<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Item;
use App\Models\SalesOrder;
use App\Models\Activity;
use App\Models\Deliveries;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Basic counts
        $totalSalesOrders = SalesOrder::count();
        $totalCustomers = Customer::count();
        $totalItems = Item::count();

        // 📌 Dashboard Stats
        $totalSoThisMonth = SalesOrder::whereMonth('created_at', now()->month)
                                      ->whereYear('created_at', now()->year)
                                      ->count();

        $totalDelivered = Deliveries::where('status', 'Delivered')->count();
        $totalPending = SalesOrder::where('status', 'Pending')->count();
        $totalDeclined = SalesOrder::where('status', 'Declined')->count();

        // 📦 Pending & Approved Purchase Orders
        $pendingPOs = PurchaseOrder::where('status', 'pending')->count();

        $approvedPendingPOsList = PurchaseOrder::where('status', 'approved')
                                               ->whereNull('rejection_reason')
                                               ->orderBy('created_at', 'desc')
                                               ->get();
        $approvedPendingPOs = $approvedPendingPOsList->count();

        // 💰 Month-to-Date Total Sales (ONLY DELIVERED ORDERS)
        $totalSalesThisMonth = SalesOrder::whereMonth('created_at', now()->month)
                                         ->whereYear('created_at', now()->year)
                                         ->whereHas('deliveries', function($query) {
                                             $query->where('status', 'Delivered');
                                         })
                                         ->sum('total_amount');

        // Recent activities
        $recentActivities = Activity::latest()->take(10)->get();

        return view('dashboard', compact(
            'recentActivities',
            'totalSalesOrders',
            'totalCustomers',
            'totalItems',
            'totalSoThisMonth',
            'totalDelivered',
            'totalPending',
            'totalDeclined',
            'totalSalesThisMonth',
            'pendingPOs',
            'approvedPendingPOs',
            'approvedPendingPOsList'
        ));
    }

    public function viewAllActivities()
    {
        $recentActivities = Activity::orderBy('created_at', 'desc')->paginate(15);
        return view('recent_activities.index', compact('recentActivities'));
    }
}