<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Deliveries;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixDeliveryBatches extends Command
{
    protected $signature = 'deliveries:fix-batches';
    protected $description = 'Fix duplicate batch numbers for deliveries with the same sales order';

    public function handle()
    {
        $this->info('🔄 Starting to fix delivery batch numbers...');

        // Get all sales orders that have multiple deliveries
        $soWithMultipleDeliveries = Deliveries::select('sales_order_number')
            ->where('status', '!=', 'Cancelled')
            ->groupBy('sales_order_number')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('sales_order_number');

        if ($soWithMultipleDeliveries->isEmpty()) {
            $this->info('✅ No sales orders with multiple deliveries found.');
            return 0;
        }

        $this->info("Found {$soWithMultipleDeliveries->count()} sales orders with multiple deliveries");

        $fixed = 0;
        $errors = 0;

        foreach ($soWithMultipleDeliveries as $soNumber) {
            try {
                // Get all deliveries for this SO, ordered by creation date
                $deliveries = Deliveries::where('sales_order_number', $soNumber)
                    ->where('status', '!=', 'Cancelled')
                    ->whereHas('items', function($q) {
                        $q->where('quantity', '>', 0);
                    })
                    ->orderBy('created_at', 'asc')
                    ->get();

                if ($deliveries->count() <= 1) {
                    continue;
                }

                $this->info("\n📦 Processing SO: {$soNumber} ({$deliveries->count()} deliveries)");

                // Check if any delivery is Full
                $hasFullDelivery = $deliveries->contains(function($d) {
                    return $d->delivery_type === 'Full';
                });

                $batchCounter = 1;
                foreach ($deliveries as $delivery) {
                    $oldBatch = $delivery->delivery_batch;

                    if ($hasFullDelivery && $delivery->delivery_type === 'Full') {
                        $newBatch = 'Full Delivery';
                    } else {
                        $newBatch = 'Batch ' . $batchCounter;
                        $batchCounter++;
                    }

                    // Only update if different
                    if ($oldBatch !== $newBatch) {
                        $delivery->delivery_batch = $newBatch;
                        $delivery->save();

                        $this->line("  ✅ DR {$delivery->dr_no}: {$oldBatch} → {$newBatch}");

                        Log::info('Fixed delivery batch', [
                            'delivery_id' => $delivery->id,
                            'dr_no' => $delivery->dr_no,
                            'so_number' => $soNumber,
                            'old_batch' => $oldBatch,
                            'new_batch' => $newBatch,
                        ]);

                        $fixed++;
                    } else {
                        $this->line("  ⏭ DR {$delivery->dr_no}: Already correct ({$newBatch})");
                    }
                }

            } catch (\Exception $e) {
                $errors++;
                $this->error("  ❌ Error processing SO {$soNumber}: {$e->getMessage()}");
                Log::error('Failed to fix delivery batch', [
                    'so_number' => $soNumber,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->newLine();
        $this->info("✅ Successfully fixed: {$fixed} deliveries");

        if ($errors > 0) {
            $this->error("❌ Errors: {$errors}");
        }

        $this->newLine();
        $this->info('🎉 Done! Refresh your deliveries page to see the corrected batch numbers.');

        return 0;
    }
}
