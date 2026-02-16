<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DeliveryItem;
use App\Models\Activity;
use Illuminate\Support\Facades\Log;

class RecalculateDeliveryTotals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deliveries:recalculate-totals {delivery_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate total amounts for delivery items (fixes decimal truncation issues)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $deliveryId = $this->argument('delivery_id');

        if ($deliveryId) {
            $this->info("🔄 Recalculating totals for delivery ID: {$deliveryId}");
            $updatedCount = $this->recalculateSingleDelivery($deliveryId);
            $this->info("✅ Updated {$updatedCount} item(s) for delivery {$deliveryId}");
        } else {
            $this->info("🔄 Recalculating totals for ALL deliveries...");

            if (!$this->confirm('This will recalculate ALL delivery item totals. Continue?', true)) {
                $this->warn('Operation cancelled.');
                return 0;
            }

            $allItems = DeliveryItem::with('delivery')->get();
            $updatedCount = 0;
            $deliveriesAffected = [];

            $progressBar = $this->output->createProgressBar($allItems->count());
            $progressBar->start();

            foreach ($allItems as $item) {
                $correctTotal = round(($item->quantity ?? 0) * ($item->unit_price ?? 0), 2);

                // Only update if there's a mismatch
                if (abs($item->total_amount - $correctTotal) > 0.01) {
                    $oldTotal = $item->total_amount;
                    $item->update(['total_amount' => $correctTotal]);
                    $updatedCount++;

                    if (!in_array($item->delivery_id, $deliveriesAffected)) {
                        $deliveriesAffected[] = $item->delivery_id;
                    }

                    Log::info('✅ Fixed total amount', [
                        'delivery_id' => $item->delivery_id,
                        'dr_no' => $item->delivery?->dr_no,
                        'item_code' => $item->item_code,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'old_total' => $oldTotal,
                        'new_total' => $correctTotal,
                    ]);
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            Activity::create([
                'user_name' => 'System Console',
                'action' => 'System Recalculation',
                'item' => 'All Deliveries',
                'target' => "{$updatedCount} items across " . count($deliveriesAffected) . " deliveries",
                'type' => 'Delivery',
                'message' => "Recalculated total amounts for {$updatedCount} delivery items via console command",
            ]);

            $this->info("✅ Successfully recalculated {$updatedCount} item(s) across " . count($deliveriesAffected) . " deliveries");
        }

        return 0;
    }

    /**
     * Recalculate a single delivery's totals
     */
    private function recalculateSingleDelivery($deliveryId)
    {
        $items = DeliveryItem::where('delivery_id', $deliveryId)->get();
        $updatedCount = 0;

        foreach ($items as $item) {
            $correctTotal = round(($item->quantity ?? 0) * ($item->unit_price ?? 0), 2);

            if (abs($item->total_amount - $correctTotal) > 0.01) {
                $oldTotal = $item->total_amount;
                $item->update(['total_amount' => $correctTotal]);
                $updatedCount++;

                $this->line("  ✅ Fixed {$item->item_code}: {$oldTotal} → {$correctTotal}");
            }
        }

        return $updatedCount;
    }
}
