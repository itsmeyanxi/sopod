<?php

namespace App\Exports;

use App\Models\SalesOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesOrdersExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $dateFrom;
    protected $dateTo;
    protected $soNumber;

    public function __construct($dateFrom, $dateTo, $soNumber)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->soNumber = $soNumber;
    }

    public function collection()
    {
        $query = SalesOrder::query()->with(['customer', 'preparer', 'approver']);

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        
        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }
        
        if ($this->soNumber) {
            $query->where('sales_order_number', 'like', '%' . $this->soNumber . '%');
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function headings(): array
    {
        return [
            'SO Number',
            'Customer',
            'Date',
            'Total Amount',
            'Status',
            'Prepared By',
            'Approved By',
            'Sales Representative',
            'Branch',
            'PO Number',
        ];
    }

    public function map($order): array
    {
        return [
            $order->sales_order_number,
            $order->customer->customer_name ?? 'N/A',
            $order->created_at ? $order->created_at->format('Y-m-d') : '',
            $order->total_amount,
            $order->status,
            $order->preparer->name ?? '—',
            $order->approver->name ?? '—',
            $order->sales_representative ?? '',
            $order->branch ?? '',
            $order->po_number ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'C00000']
                ],
                'font' => [
                    'color' => ['rgb' => 'FFFFFF'],
                    'bold' => true
                ]
            ],
        ];
    }
}