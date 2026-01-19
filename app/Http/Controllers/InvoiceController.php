<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    /**
     * Display the invoice screen
     */
    public function screen()
    {
        return view('invoices.screen');
    }
}