<?php

namespace App\Http\Controllers;

use App\Models\AccountsPayableInvoice;
use App\Models\ARAdjustment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ARDashboardController extends Controller
{
    /**
     * Display AR Dashboard with overdue and upcoming due invoices
     */
    public function index()
    {
        $today = Carbon::today();
        $sevenDaysFromNow = $today->copy()->addDays(7);
        $threeDaysFromNow = $today->copy()->addDays(3);

        // Overdue invoices (due_date < today)
        $overdueInvoices = AccountsPayableInvoice::where('due_date', '<', $today)
            ->where('status', '!=', 'paid')
            ->with(['creator', 'requestForPayment'])
            ->orderBy('due_date', 'asc')
            ->get();

        // Upcoming due invoices (7 days before due date)
        $upcomingDue7Days = AccountsPayableInvoice::whereBetween('due_date', [$today, $sevenDaysFromNow])
            ->where('status', '!=', 'paid')
            ->with(['creator', 'requestForPayment'])
            ->orderBy('due_date', 'asc')
            ->get()
            ->map(function ($invoice) use ($today, $threeDaysFromNow) {
                $daysUntilDue = $invoice->due_date->diffInDays($today);
                $invoice->urgency = $daysUntilDue <= 3 ? 'urgent' : 'warning';
                $invoice->days_until_due = $daysUntilDue;
                return $invoice;
            });

        // Summary statistics
        $totalOverdue = $overdueInvoices->sum('grand_total');
        $totalUpcoming = $upcomingDue7Days->sum('grand_total');
        $overdueCount = $overdueInvoices->count();
        $upcomingCount = $upcomingDue7Days->count();

        return view('ar_dashboard.index', compact(
            'overdueInvoices',
            'upcomingDue7Days',
            'totalOverdue',
            'totalUpcoming',
            'overdueCount',
            'upcomingCount'
        ));
    }

    /**
     * Export summary data
     */
    public function exportSummary()
    {
        $today = Carbon::today();

        $overdueInvoices = AccountsPayableInvoice::where('due_date', '<', $today)
            ->where('status', '!=', 'paid')
            ->with(['creator', 'requestForPayment'])
            ->get();

        $upcomingInvoices = AccountsPayableInvoice::whereBetween('due_date', [$today, $today->copy()->addDays(7)])
            ->where('status', '!=', 'paid')
            ->with(['creator', 'requestForPayment'])
            ->get();

        $filename = 'ar_summary_' . now()->format('Y-m-d_His') . '.csv';
        $handle = fopen('php://memory', 'r+');

        // Write CSV headers
        fputcsv($handle, ['APV No', 'Vendor', 'Due Date', 'Amount', 'Days Overdue/Until Due', 'Status']);

        // Write overdue invoices
        foreach ($overdueInvoices as $invoice) {
            $daysOverdue = $invoice->due_date->diffInDays($today);
            fputcsv($handle, [
                $invoice->apv_no,
                $invoice->vendor_name,
                $invoice->due_date->format('Y-m-d'),
                $invoice->grand_total,
                $daysOverdue . ' days overdue',
                'Overdue'
            ]);
        }

        // Write upcoming invoices
        foreach ($upcomingInvoices as $invoice) {
            $daysUntilDue = $today->diffInDays($invoice->due_date);
            fputcsv($handle, [
                $invoice->apv_no,
                $invoice->vendor_name,
                $invoice->due_date->format('Y-m-d'),
                $invoice->grand_total,
                $daysUntilDue . ' days until due',
                'Upcoming'
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export detailed data
     */
    public function exportDetails()
    {
        $today = Carbon::today();

        $allInvoices = AccountsPayableInvoice::where('status', '!=', 'paid')
            ->with(['creator', 'requestForPayment', 'approver'])
            ->orderBy('due_date', 'asc')
            ->get();

        $filename = 'ar_details_' . now()->format('Y-m-d_His') . '.csv';
        $handle = fopen('php://memory', 'r+');

        // Write CSV headers
        fputcsv($handle, [
            'APV No',
            'PO No',
            'Vendor',
            'Document Date',
            'Due Date',
            'Total Amount',
            'Status',
            'Days Overdue/Until Due',
            'Prepared By',
            'Reviewed By',
            'Remarks'
        ]);

        // Write invoice details
        foreach ($allInvoices as $invoice) {
            $daysCalculation = $invoice->due_date->isPast()
                ? $invoice->due_date->diffInDays($today) . ' days overdue'
                : $today->diffInDays($invoice->due_date) . ' days until due';

            fputcsv($handle, [
                $invoice->apv_no,
                $invoice->purchase_order_no,
                $invoice->vendor_name,
                $invoice->document_date->format('Y-m-d'),
                $invoice->due_date->format('Y-m-d'),
                $invoice->grand_total,
                $invoice->status,
                $daysCalculation,
                $invoice->creator->name ?? 'N/A',
                $invoice->approver->name ?? 'N/A',
                $invoice->remarks
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
