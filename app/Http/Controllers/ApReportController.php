<?php

namespace App\Http\Controllers;

use App\Models\AccountsPayableInvoice;
use App\Models\CheckVoucher;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ApReportController extends Controller
{
    public function aging()
    {
        $today = Carbon::today();

        $invoices = AccountsPayableInvoice::whereNotIn('status', ['rejected'])
            ->whereNotNull('due_date')
            ->orderBy('due_date')
            ->get();

        $buckets = [
            'current' => ['invoices' => collect(), 'total' => 0],
            '1_30' => ['invoices' => collect(), 'total' => 0],
            '31_60' => ['invoices' => collect(), 'total' => 0],
            '61_90' => ['invoices' => collect(), 'total' => 0],
            'over_90' => ['invoices' => collect(), 'total' => 0],
        ];

        foreach ($invoices as $inv) {
            $dueDate = Carbon::parse($inv->due_date);
            $daysOverdue = $today->diffInDays($dueDate, false) * -1;
            $balance = (float) $inv->grand_total;

            $inv->days_overdue = $daysOverdue;
            $inv->balance = $balance;

            if ($daysOverdue <= 0) {
                $buckets['current']['invoices']->push($inv);
                $buckets['current']['total'] += $balance;
            } elseif ($daysOverdue <= 30) {
                $buckets['1_30']['invoices']->push($inv);
                $buckets['1_30']['total'] += $balance;
            } elseif ($daysOverdue <= 60) {
                $buckets['31_60']['invoices']->push($inv);
                $buckets['31_60']['total'] += $balance;
            } elseif ($daysOverdue <= 90) {
                $buckets['61_90']['invoices']->push($inv);
                $buckets['61_90']['total'] += $balance;
            } else {
                $buckets['over_90']['invoices']->push($inv);
                $buckets['over_90']['total'] += $balance;
            }
        }

        $grandTotal = array_sum(array_column($buckets, 'total'));

        return view('ap_reports.aging', compact('buckets', 'grandTotal'));
    }

    public function cashForecast()
    {
        $today = Carbon::today();
        $sixtyDays = $today->copy()->addDays(60);

        $invoices = AccountsPayableInvoice::whereNotIn('status', ['rejected'])
            ->whereNotNull('due_date')
            ->where('due_date', '>=', $today)
            ->where('due_date', '<=', $sixtyDays)
            ->orderBy('due_date')
            ->get()
            ->map(function ($inv) use ($today) {
                $inv->days_until_due = Carbon::parse($inv->due_date)->diffInDays($today);
                $inv->balance = (float) $inv->grand_total;
                return $inv;
            });

        $totals = [
            '7_days' => $invoices->filter(fn($i) => $i->days_until_due <= 7)->sum('balance'),
            '30_days' => $invoices->filter(fn($i) => $i->days_until_due <= 30)->sum('balance'),
            '60_days' => $invoices->sum('balance'),
        ];

        return view('ap_reports.cash_forecast', compact('invoices', 'totals'));
    }

    public function spendAnalysis()
    {
        $invoices = AccountsPayableInvoice::whereIn('status', ['approved'])
            ->orWhere(function ($q) {
                $q->whereNotIn('status', ['rejected'])
                    ->whereNotIn('approval_stage', ['rejected']);
            });

        $totalSpend = AccountsPayableInvoice::whereNotIn('status', ['rejected'])
            ->sum('grand_total');

        $bySupplier = AccountsPayableInvoice::select('vendor_name', DB::raw('SUM(grand_total) as amount'))
            ->whereNotIn('status', ['rejected'])
            ->groupBy('vendor_name')
            ->orderByDesc('amount')
            ->get();

        // Average payment cycle
        $avgDays = DB::table('check_vouchers')
            ->join('accounts_payable_invoices', 'check_vouchers.accounts_payable_invoice_id', '=', 'accounts_payable_invoices.id')
            ->where('check_vouchers.status', 'approved')
            ->whereNotNull('accounts_payable_invoices.apv_date')
            ->selectRaw('AVG(DATEDIFF(check_vouchers.updated_at, accounts_payable_invoices.apv_date)) as avg_days')
            ->value('avg_days');

        return view('ap_reports.spend_analysis', compact('totalSpend', 'bySupplier', 'avgDays'));
    }
}
