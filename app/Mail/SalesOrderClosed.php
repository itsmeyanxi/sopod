<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SalesOrderClosed extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $emailMessage;
    public $title;
    public $sales_order_number;
    public $customer_name;
    public $total_amount;
    public $closed_by;
    public $closed_at;
    public $view_url;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->emailMessage = $data['emailMessage'] ?? 'Sales Order has been closed.';
        $this->title = $data['title'] ?? 'Sales Order Closed';
        $this->sales_order_number = $data['sales_order_number'] ?? 'N/A';
        $this->customer_name = $data['customer_name'] ?? 'N/A';
        $this->total_amount = $data['total_amount'] ?? '0.00';
        $this->closed_by = $data['closed_by'] ?? 'System';
        $this->closed_at = $data['closed_at'] ?? now()->format('M d, Y h:i A');
        $this->view_url = $data['view_url'] ?? '#';
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->title)
                    ->view('emails.sales-order-closed');
    }
}