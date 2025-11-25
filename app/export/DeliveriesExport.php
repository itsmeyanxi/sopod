namespace App\Exports;

use App\Models\Delivery;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;

class DeliveriesExport implements FromCollection, WithHeadings
{
    protected $dateFrom;
    protected $dateTo;
    protected $search;

    public function __construct($dateFrom, $dateTo, $search)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->search = $search;
    }

    public function collection()
    {
        $query = Delivery::query()->with('salesOrder.customer');

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }
        if ($this->search) {
            $query->where(function($q) {
                $q->where('dr_no', 'like', "%{$this->search}%")
                  ->orWhereHas('salesOrder.customer', function($q) {
                      $q->where('customer_name', 'like', "%{$this->search}%");
                  });
            });
        }

        return $query->get()->map(function ($delivery) {
            return [
                'DR No' => $delivery->dr_no,
                'Sales Order' => $delivery->sales_order_number,
                'Customer' => $delivery->salesOrder?->customer?->customer_name ?? 'N/A',
                'Quantity' => $delivery->quantity,
                'Amount' => $delivery->amount,
                'Status' => $delivery->status,
                'Date' => $delivery->created_at->format('Y-m-d'),
            ];
        });
    }

    public function headings(): array
    {
        return ['DR No', 'Sales Order', 'Customer', 'Quantity', 'Amount', 'Status', 'Date'];
    }
}
