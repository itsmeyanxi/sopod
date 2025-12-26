<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeliveryStatusChanged extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Delivery ' . ucfirst($this->data['action'] ?? 'Updated') . ': ' . ($this->data['dr_no'] ?? 'N/A'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.delivery-status-change',
            with: $this->data,
        );
    }
}