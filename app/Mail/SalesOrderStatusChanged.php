<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SalesOrderStatusChanged extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $data; // ✅ CHANGED: Use generic name

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Sales Order ' . ($this->data['new_status'] ?? 'Updated') . ': ' . ($this->data['sales_order_number'] ?? 'N/A'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.sales-order-status-change',
            // ✅ FIX: Pass individual variables directly
            with: $this->data,
        );
    }
}