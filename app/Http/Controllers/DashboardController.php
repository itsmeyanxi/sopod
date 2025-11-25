<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Item;
use App\Models\SalesOrder;
use App\Models\Activity;
use App\Models\Deliveries;

class DashboardController extends Controller
{
    public function index()
    {
        // Basic counts
        $totalSalesOrders = SalesOrder::count();
        $totalCustomers = Customer::count();
        $totalItems = Item::count();

        // 📌 New Dashboard Stats
        $totalSoThisMonth = SalesOrder::whereMonth('created_at', now()->month)
                                      ->whereYear('created_at', now()->year)
                                      ->count();

        $totalDelivered = Deliveries::where('status', 'Delivered')->count();
        $totalPending = SalesOrder::where('status', 'Pending')->count();
        $totalDeclined = SalesOrder::where('status', 'Declined')->count();

        // Recent activities (you already have this)
        $recentActivities = Activity::latest()->take(10)->get();

        return view('dashboard', compact(
            'recentActivities',
            'totalSalesOrders',
            'totalCustomers',
            'totalItems',
            'totalSoThisMonth',
            'totalDelivered',
            'totalPending',
            'totalDeclined'
        ));
    }

    public function viewAllActivities()
    {
        $recentActivities = Activity::orderBy('created_at', 'desc')->paginate(15);
        return view('recent_activities.index', compact('recentActivities'));
    }
}
