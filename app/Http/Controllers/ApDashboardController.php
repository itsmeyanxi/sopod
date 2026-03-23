<?php

namespace App\Http\Controllers;

use App\Models\AccountsPayableInvoice;
use App\Models\CheckVoucher;
use App\Models\DebitMemo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ApDashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $weekEnd = $now->copy()->addDays(7);
        $monthStart = $now->copy()->startOfMonth();

        // Total AP balance (unpaid invoices)
        $totalBalance = AccountsPayableInvoice::whereNotIn('status', ['rejected'])
            ->whereNotIn('approval_stage', ['rejected'])
            ->sum('grand_total');

        // Due this week
        $dueThisWeek = AccountsPayableInvoice::whereNotNull('due_date')
            ->where('due_date', '<=', $weekEnd)
            ->where('due_date', '>=', $now)
            ->whereNotIn('status', ['rejected']);
        $dueThisWeekAmount = $dueThisWeek->sum('grand_total');
        $dueThisWeekCount = $dueThisWeek->count();

        // Overdue
        $overdue = AccountsPayableInvoice::whereNotNull('due_date')
            ->where('due_date', '<', $now)
            ->whereNotIn('status', ['rejected'])
            ->whereNotIn('approval_stage', ['rejected']);
        $overdueAmount = $overdue->sum('grand_total');
        $overdueCount = $overdue->count();

        // Paid this month (approved CVs)
        $paidThisMonth = CheckVoucher::where('status', 'approved')
            ->where('created_at', '>=', $monthStart)
            ->sum('check_amount');
        $paidCount = CheckVoucher::where('status', 'approved')
            ->where('created_at', '>=', $monthStart)
            ->count();

        // Pipeline counts
        $pipeline = [
            'pending_dh' => AccountsPayableInvoice::where('approval_stage', 'pending_dh')->count(),
            'pending_accounting' => AccountsPayableInvoice::where('approval_stage', 'pending_accounting')->count(),
            'approved' => AccountsPayableInvoice::where('status', 'approved')->count(),
            'rejected' => AccountsPayableInvoice::where('status', 'rejected')->count(),
        ];

        // Top 5 suppliers by total APV amount
        $topSuppliers = AccountsPayableInvoice::select('vendor_name', DB::raw('SUM(grand_total) as total'))
            ->whereNotIn('status', ['rejected'])
            ->groupBy('vendor_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Recent invoices
        $recentInvoices = AccountsPayableInvoice::orderByDesc('created_at')
            ->limit(6)
            ->get();

        // Debit memo summary
        $openDebitMemos = DebitMemo::where('status', 'Open')->sum('amount');
        $debitMemoCount = DebitMemo::where('status', 'Open')->count();

        return view('ap_dashboard', compact(
            'totalBalance', 'dueThisWeekAmount', 'dueThisWeekCount',
            'overdueAmount', 'overdueCount', 'paidThisMonth', 'paidCount',
            'pipeline', 'topSuppliers', 'recentInvoices',
            'openDebitMemos', 'debitMemoCount'
        ));
    }
}
