<?php

namespace App\Http\Controllers;

use App\Models\ArAging;
use App\Models\ArAdjustment;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class CustomerARProfileController extends Controller
{
    /**
     * Display unified AR profile for a customer
     */
    public function show($customerCode)
    {
        // Get customer name from first AR aging record (using TRIM for whitespace handling)
        $firstAging = ArAging::whereRaw('TRIM(customer_code) = ?', [trim($customerCode)])->first();
        $customerName = $firstAging?->client_name ?? $customerCode;

        // Get AR aging invoices (using TRIM to handle whitespace)
        $arAging = ArAging::whereRaw('TRIM(customer_code) = ?', [trim($customerCode)])
            ->orderBy('invoice_date', 'desc')
            ->get();

        // Get collection/payment history (using TRIM to handle whitespace)
        $payments = Payment::whereRaw('TRIM(customer_code) = ?', [trim($customerCode)])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get AR adjustments (using TRIM to handle whitespace)
        $adjustments = ArAdjustment::whereRaw('TRIM(customer_code) = ?', [trim($customerCode)])
            ->orderBy('transaction_date', 'desc')
            ->get();

        // Try customer_ar_summary VIEW first, fallback to inline calculation (using TRIM for whitespace)
        $summary = DB::table('customer_ar_summary')
            ->whereRaw('TRIM(customer_code) = ?', [trim($customerCode)])
            ->first();

        if (!$summary) {
            $summary = (object)[
                'total_invoiced' => $arAging->sum('invoice_amount'),
                'total_collected' => $arAging->sum('settled_invoice_amount'),
                'total_adjustments' => $adjustments->sum('amount'),
                'outstanding_balance' => $arAging->where('net_ar_balance', '>', 0)->sum('net_ar_balance'),
            ];
        }

        return view('ar.customer_profile', compact(
            'customerCode',
            'customerName',
            'arAging',
            'payments',
            'adjustments',
            'summary'
        ));
    }
}
