<?php

namespace App\Events;

use App\Models\SalesOrder;
use App\Models\SalesOrderChange;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SalesOrderChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $salesOrder;
    public $fieldChanged;
    public $change;

    public function __construct(SalesOrder $salesOrder, $fieldChanged, SalesOrderChange $change)
    {
        $this->salesOrder = $salesOrder;
        $this->fieldChanged = $fieldChanged;
        $this->change = $change;
    }
}