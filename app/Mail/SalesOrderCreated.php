<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SalesOrderCreated extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $salesOrderData;

    public function __construct($salesOrderData)
    {
        $this->salesOrderData = $salesOrderData;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Sales Order: ' . ($this->salesOrderData['sales_order_number'] ?? 'N/A'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.sales-order-notification',
            // ✅ FIX: Pass individual variables, not wrapped object
            with: $this->salesOrderData,
        );
    }
}