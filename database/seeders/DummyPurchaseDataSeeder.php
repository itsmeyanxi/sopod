<?php

namespace Database\Seeders;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use Illuminate\Database\Seeder;

class DummyPurchaseDataSeeder extends Seeder
{
    public function run(): void
    {
        $userId = 25; // Josh Bautista
        $now    = now();

        $purchaseRequests = [
            [
                'pr_no'        => 'PR-20260401-0001',
                'company'      => 'MeatPlus',
                'requisitioner'=> 'Juan Dela Cruz',
                'department'   => 'Operations',
                'supplier_id'  => 1,
                'supplier'     => 'BRF Global',
                'terms'        => 'Net 30',
                'address'      => 'Guglgasse 17/5/1.0, 1110 Vienna, Austria',
                'delivery_address' => 'MDP Warehouse, Taguig City',
                'contact_person'   => 'Hector Tan',
                'date_of_request'  => '2026-04-01',
                'date_needed'      => '2026-04-15',
                'type_of_request'  => 'regular',
                'with_budget'      => 'yes',
                'charge_to'        => 'Operations',
                'contact_number'   => '55 41 3401 7747',
                'reason_for_requisition' => 'Replenishment of frozen meat inventory',
                'items' => [
                    ['item_code' => 'BRF-CHK-001', 'description' => 'Frozen Chicken Breast (Boneless)', 'qty' => 500, 'uom' => 'Kgs', 'unit_price' => 280.00],
                    ['item_code' => 'BRF-CHK-002', 'description' => 'Frozen Chicken Thigh (Bone-In)',   'qty' => 300, 'uom' => 'Kgs', 'unit_price' => 240.00],
                    ['item_code' => 'BRF-CHK-003', 'description' => 'Frozen Chicken Drumstick',         'qty' => 200, 'uom' => 'Kgs', 'unit_price' => 210.00],
                ],
            ],
            [
                'pr_no'        => 'PR-20260402-0002',
                'company'      => 'MeatPlus',
                'requisitioner'=> 'Maria Santos',
                'department'   => 'Logistics',
                'supplier_id'  => 2,
                'supplier'     => 'Premier Brands International',
                'terms'        => 'Net 60',
                'address'      => '9C Becketts Way, Parkwest Business Park, Dublin 12, Ireland',
                'delivery_address' => 'MDP Cold Storage, Pasig City',
                'contact_person'   => 'Sinead Kennedy',
                'date_of_request'  => '2026-04-02',
                'date_needed'      => '2026-04-20',
                'type_of_request'  => 'regular',
                'with_budget'      => 'yes',
                'charge_to'        => 'Logistics',
                'contact_number'   => null,
                'reason_for_requisition' => 'Imported beef for Q2 client orders',
                'items' => [
                    ['item_code' => 'PBI-BEF-010', 'description' => 'Australian Beef Tenderloin',    'qty' => 100, 'uom' => 'Kgs', 'unit_price' => 1200.00],
                    ['item_code' => 'PBI-BEF-011', 'description' => 'Australian Beef Ribeye',        'qty' => 150, 'uom' => 'Kgs', 'unit_price' => 980.00],
                    ['item_code' => 'PBI-BEF-012', 'description' => 'Australian Beef Striploin',     'qty' => 120, 'uom' => 'Kgs', 'unit_price' => 900.00],
                    ['item_code' => 'PBI-BEF-013', 'description' => 'Australian Beef Cuberoll',      'qty' => 80,  'uom' => 'Kgs', 'unit_price' => 850.00],
                ],
            ],
            [
                'pr_no'        => 'PR-20260405-0003',
                'company'      => 'MeatPlus',
                'requisitioner'=> 'Pedro Reyes',
                'department'   => 'Warehouse',
                'supplier_id'  => 5,
                'supplier'     => 'Partners Network',
                'terms'        => 'COD',
                'address'      => 'West Europe B.V. Laan 21 8071 JG Nunspeet The Netherlands',
                'delivery_address' => 'MeatPlus Main Office, Ermita Manila',
                'contact_person'   => 'Aby Ramos',
                'date_of_request'  => '2026-04-05',
                'date_needed'      => '2026-04-12',
                'type_of_request'  => 'urgent',
                'with_budget'      => 'yes',
                'charge_to'        => 'Warehouse',
                'contact_number'   => null,
                'reason_for_requisition' => 'Packaging materials for upcoming deliveries',
                'items' => [
                    ['item_code' => 'PKG-BOX-001', 'description' => 'Insulated Shipping Box (Large)',   'qty' => 500,  'uom' => 'Pcs', 'unit_price' => 85.00],
                    ['item_code' => 'PKG-BOX-002', 'description' => 'Insulated Shipping Box (Medium)',  'qty' => 800,  'uom' => 'Pcs', 'unit_price' => 65.00],
                    ['item_code' => 'PKG-ICE-001', 'description' => 'Gel Ice Pack 500g',                'qty' => 2000, 'uom' => 'Pcs', 'unit_price' => 12.00],
                    ['item_code' => 'PKG-TYP-001', 'description' => 'Thermal Tape Roll',                'qty' => 100,  'uom' => 'Rolls', 'unit_price' => 45.00],
                    ['item_code' => 'PKG-LBL-001', 'description' => 'Cold Chain Warning Labels',         'qty' => 5000, 'uom' => 'Pcs', 'unit_price' => 1.50],
                ],
            ],
            [
                'pr_no'        => 'PR-20260408-0004',
                'company'      => 'MeatPlus',
                'requisitioner'=> 'Ana Garcia',
                'department'   => 'Finance',
                'supplier_id'  => 3,
                'supplier'     => 'Leadex',
                'terms'        => 'Net 45',
                'address'      => 'Tour Europa Ctre Belle Epine / Thiais - France',
                'delivery_address' => 'MDP Warehouse, Taguig City',
                'contact_person'   => 'David LALLOUET',
                'date_of_request'  => '2026-04-08',
                'date_needed'      => '2026-04-25',
                'type_of_request'  => 'regular',
                'with_budget'      => 'yes',
                'charge_to'        => 'Finance',
                'contact_number'   => null,
                'reason_for_requisition' => 'European pork products for hotel clients',
                'items' => [
                    ['item_code' => 'LDX-PRK-001', 'description' => 'French Pork Loin (Boneless)',     'qty' => 200, 'uom' => 'Kgs', 'unit_price' => 650.00],
                    ['item_code' => 'LDX-PRK-002', 'description' => 'French Pork Belly (Skin-On)',     'qty' => 300, 'uom' => 'Kgs', 'unit_price' => 520.00],
                    ['item_code' => 'LDX-PRK-003', 'description' => 'French Pork Spare Ribs',          'qty' => 150, 'uom' => 'Kgs', 'unit_price' => 480.00],
                ],
            ],
            [
                'pr_no'        => 'PR-20260410-0005',
                'company'      => 'MeatPlus',
                'requisitioner'=> 'Carlo Mendoza',
                'department'   => 'Operations',
                'supplier_id'  => 4,
                'supplier'     => 'World Wide Food Distribution',
                'terms'        => 'Net 30',
                'address'      => 'Room 612, 6/F, Hong Man Industrial Centre, 2 Hong Man Street, Chai Wan, Hong Kong',
                'delivery_address' => 'MDP Cold Storage, Pasig City',
                'contact_person'   => 'Maxime Pascal Lemaitre',
                'date_of_request'  => '2026-04-10',
                'date_needed'      => '2026-04-30',
                'type_of_request'  => 'regular',
                'with_budget'      => 'yes',
                'charge_to'        => 'Operations',
                'contact_number'   => null,
                'reason_for_requisition' => 'Seafood products for restaurant chain clients',
                'items' => [
                    ['item_code' => 'WWF-SFD-001', 'description' => 'Frozen Salmon Fillet (Norwegian)', 'qty' => 100, 'uom' => 'Kgs', 'unit_price' => 1500.00],
                    ['item_code' => 'WWF-SFD-002', 'description' => 'Frozen Shrimp 21/25 (HOSO)',       'qty' => 200, 'uom' => 'Kgs', 'unit_price' => 680.00],
                    ['item_code' => 'WWF-SFD-003', 'description' => 'Frozen Squid Tube (Cleaned)',       'qty' => 150, 'uom' => 'Kgs', 'unit_price' => 420.00],
                    ['item_code' => 'WWF-SFD-004', 'description' => 'Frozen Tuna Loin (Sashimi Grade)', 'qty' => 50,  'uom' => 'Kgs', 'unit_price' => 2200.00],
                ],
            ],
        ];

        $prIds = [];

        foreach ($purchaseRequests as $prData) {
            $items = $prData['items'];
            unset($prData['items']);

            $pr = PurchaseRequest::create(array_merge($prData, [
                'status'         => 'approved',
                'approval_stage' => 'approved',
                'created_by'     => $userId,
                'approved_by'    => $userId,
                'approved_at'    => $now,
                'department_head_approved_by' => $userId,
                'department_head_approved_at' => $now,
                'management_approved_by'      => $userId,
                'management_approved_at'      => $now,
            ]));

            $itemNo = 1;
            foreach ($items as $item) {
                PurchaseRequestItem::create([
                    'purchase_request_id' => $pr->id,
                    'supplier_id'         => $prData['supplier_id'],
                    'supplier_name'       => $prData['supplier'] ?? null,
                    'item_no'             => $itemNo,
                    'item_code'           => $item['item_code'],
                    'date_needed'         => $prData['date_needed'],
                    'qty'                 => $item['qty'],
                    'uom'                 => $item['uom'],
                    'description'         => $item['description'],
                    'unit_price'          => $item['unit_price'],
                    'amount'              => $item['qty'] * $item['unit_price'],
                    'remarks'             => null,
                    'note'                => null,
                ]);
                $itemNo++;
            }

            $prIds[$prData['pr_no']] = $pr;
            $this->command->info("Created PR {$pr->pr_no} with " . count($items) . " items");
        }

        // Create POs linked to each PR
        $poIndex = 1;
        foreach ($prIds as $prNo => $pr) {
            $poNo = 'PO-2026040' . $poIndex . '-' . str_pad($poIndex, 4, '0', STR_PAD_LEFT);

            $totalAmount = $pr->items->sum('amount');

            $po = PurchaseOrder::create([
                'po_no'                  => $poNo,
                'purchase_request_id'    => $pr->id,
                'company'                => $pr->company,
                'supplier'               => $pr->supplier,
                'supplier_id'            => $pr->supplier_id,
                'supplier_address'       => $pr->address,
                'consignee'              => 'MeatPlus Trading Corporation',
                'consignee_address'      => 'Suite 1207 Victoria Building, 429 U.N. Avenue, Ermita, Manila',
                'delivery_address'       => $pr->delivery_address,
                'order_date'             => $pr->date_of_request,
                'expected_delivery_date' => $pr->date_needed,
                'payment_terms'          => $pr->terms,
                'location'               => 'Manila',
                'house'                  => 'Main',
                'pr_no'                  => $pr->pr_no,
                'lc_price'               => $totalAmount,
                'currency'               => 'PHP',
                'exchange_rate'          => 1.0000,
                'remarks'                => null,
                'quotation'              => null,
                'status'                 => 'approved',
                'approval_stage'         => 'approved',
                'created_by'             => $userId,
                'approved_by'            => $userId,
                'approved_at'            => $now,
                'department_head_approved_by' => $userId,
                'department_head_approved_at' => $now,
                'management_approved_by'      => $userId,
                'management_approved_at'      => $now,
            ]);

            $prItems = $pr->items;
            foreach ($prItems as $prItem) {
                PurchaseOrderItem::create([
                    'purchase_order_id'        => $po->id,
                    'purchase_request_item_id' => $prItem->id,
                    'supplier_id'              => $prItem->supplier_id,
                    'supplier_name'            => $prItem->supplier_name,
                    'item_no'                  => $prItem->item_no,
                    'item_code'                => $prItem->item_code,
                    'date_needed'              => $prItem->date_needed,
                    'qty'                      => $prItem->qty,
                    'uom'                      => $prItem->uom,
                    'description'              => $prItem->description,
                    'unit_price'               => $prItem->unit_price,
                    'tax'                      => 0.00,
                    'total'                    => $prItem->amount,
                    'note'                     => null,
                ]);
            }

            $this->command->info("Created PO {$po->po_no} (₱" . number_format($totalAmount, 2) . ") linked to {$pr->pr_no}");
            $poIndex++;
        }

        $this->command->info('');
        $this->command->info('✅ Seeded 5 Purchase Requests and 5 Purchase Orders with realistic items.');
        $this->command->info('All are fully approved and ready for RFP testing.');
    }
}
