<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Item;
use App\Models\SalesOrder;
use App\Models\Activity;
use App\Models\Deliveries;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\RequestForPayment;
use App\Models\AccountsPayableInvoice;
use App\Models\CheckVoucher;
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

        // Recent activities (sales module only)
        $recentActivities = Activity::whereIn('type', ['Sales Order', 'Customer', 'Item', 'Delivery', 'Supplier'])
                                    ->latest()->take(10)->get();

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

    public function poDashboard()
    {
        $pendingPRs = PurchaseRequest::where('status', 'pending')->count();
        $pendingPOs = PurchaseOrder::where('status', 'pending')->count();
        $pendingRFPs = RequestForPayment::where('status', 'pending')->count();
        $pendingAPVs = AccountsPayableInvoice::where('status', 'pending')->count();
        $pendingCVs = CheckVoucher::where('status', 'pending')->count();

        // Recent activities (PO module only)
        $recentActivities = Activity::whereIn('type', ['Purchase Request', 'Purchase Order', 'Request For Payment', 'Accounts Payable Invoice', 'Check Voucher'])
                                    ->latest()->take(10)->get();

        return view('po_dashboard', compact(
            'pendingPRs',
            'pendingPOs',
            'pendingRFPs',
            'pendingAPVs',
            'pendingCVs',
            'recentActivities'
        ));
    }

    public function viewAllActivities(Request $request)
    {
        $module = $request->query('module');

        $salesTypes = ['Sales Order', 'Customer', 'Item', 'Delivery', 'Supplier'];
        $poTypes    = ['Purchase Request', 'Purchase Order', 'Request For Payment', 'Accounts Payable Invoice', 'Check Voucher'];

        $query = Activity::orderBy('created_at', 'desc');

        if ($module === 'sales') {
            $query->whereIn('type', $salesTypes);
            $pageTitle = 'Sales Module Activities';
            $backRoute = route('dashboard');
        } elseif ($module === 'po') {
            $query->whereIn('type', $poTypes);
            $pageTitle = 'PO Module Activities';
            $backRoute = route('po_dashboard');
        } else {
            $pageTitle = 'All Recent Activities';
            $backRoute = route('dashboard');
        }

        $recentActivities = $query->paginate(15)->withQueryString();

        return view('recent_activities.index', compact('recentActivities', 'pageTitle', 'backRoute'));
    }
}