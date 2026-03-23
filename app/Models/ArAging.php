<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArAging extends Model
{
    protected $table = 'ar_aging';

    protected $fillable = [
        'aging_date',
        'counter_date',
        'invoice_date',
        'record_date',
        'due_date',

        'invoice_no',
        'po_no',
        'dr_no',

        'customer_code',
        'client_name',
        'branch',

        'sales_executive',
        'se2',
        'terms',
        'sales_week_no',

        'age',
        'age_category',

        'invoice_amount',
        'ar_adjustments',
        'settled_invoice_amount',
        'gross_ar_balance',
        'net_ar',
        'cwt',
        'net_of_cwt',
        'net_ar_balance',
        'factored_ar_amount',

        'ewt',
        'annual',
        'factoring',
        'factoring_interest',
        'others_particulars',
        'others_amount',
        'check_amount',

        'status',
        'include_flag',
        'ar_class',
    ];
}
