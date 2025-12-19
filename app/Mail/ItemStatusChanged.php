<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ItemStatusChanged extends Mailable
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
            subject: 'Item ' . ucfirst($this->data['action'] ?? 'Updated') . ': ' . ($this->data['item_code'] ?? 'N/A'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.item-status-change',
            with: $this->data,
        );
    }
}