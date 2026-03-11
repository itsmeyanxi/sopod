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
        // Get customer name from first AR aging record
        $firstAging = ArAging::where('customer_code', $customerCode)->first();
        $customerName = $firstAging?->client_name ?? $customerCode;

        // Get AR aging invoices
        $arAging = ArAging::where('customer_code', $customerCode)
            ->orderBy('invoice_date', 'desc')
            ->get();

        // Get collection/payment history
        $payments = Payment::where('customer_code', $customerCode)
            ->orderBy('created_at', 'desc')
            ->get();

        // Get AR adjustments
        $adjustments = ArAdjustment::where('customer_code', $customerCode)
            ->orderBy('transaction_date', 'desc')
            ->get();

        // Try customer_ar_summary VIEW first, fallback to inline calculation
        $summary = DB::table('customer_ar_summary')
            ->where('customer_code', $customerCode)
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
