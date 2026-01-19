<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ArAdjustment;
use App\Models\ArAging;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReapplyArAdjustments extends Command
{
    protected $signature = 'ar:reapply-adjustments {--reset : Reset all ar_adjustments columns to 0 before reapplying}';
    protected $description = 'Reapply all AR adjustments to the ar_aging table';

    public function handle()
    {
        $this->info('🔄 Starting to reapply AR adjustments...');

        // Step 1: Optionally reset all ar_adjustments columns
        if ($this->option('reset')) {
            $this->warn('⚠️  Resetting all ar_adjustments columns to 0...');
            DB::table('ar_aging')->update([
                'ar_adjustments' => 0
            ]);
            $this->info('✅ Reset complete');
        }

        // Step 2: Get all adjustments with customer_code
        $adjustments = ArAdjustment::whereNotNull('customer_code')->get();

        if ($adjustments->isEmpty()) {
            $this->error('❌ No adjustments found with customer_code. Run ar:fix-adjustment-customer-codes first!');
            return 1;
        }

        $this->info("Found {$adjustments->count()} adjustments to apply");

        $applied = 0;
        $skipped = 0;
        $errors = 0;

        $progressBar = $this->output->createProgressBar($adjustments->count());
        $progressBar->start();

        foreach ($adjustments as $adjustment) {
            try {
                $query = ArAging::where('customer_code', $adjustment->customer_code);

                // If specific invoice, adjust that one
                if ($adjustment->invoice_number) {
                    $query->where('invoice_no', $adjustment->invoice_number);
                } else {
                    // Otherwise, adjust oldest outstanding invoice
                    $query->where('net_ar_balance', '>', 0)
                          ->orderBy('invoice_date', 'asc')
                          ->limit(1);
                }

                $record = $query->first();

                if (!$record) {
                    $skipped++;
                    Log::warning('No matching AR record for adjustment', [
                        'adjustment_id' => $adjustment->id,
                        'customer_code' => $adjustment->customer_code,
                        'invoice_number' => $adjustment->invoice_number
                    ]);
                    $progressBar->advance();
                    continue;
                }

                // Apply the adjustment
                $record->ar_adjustments += $adjustment->amount;
                $record->gross_ar_balance += $adjustment->amount;
                $record->net_ar_balance += $adjustment->amount;
                $record->net_ar += $adjustment->amount;

                // Update status
                if ($record->net_ar_balance <= 0.01) {
                    $record->status = 'Paid';
                    $record->net_ar_balance = 0;
                    $record->net_ar = 0;
                } elseif ($record->settled_invoice_amount > 0) {
                    $record->status = 'Partial';
                } else {
                    $record->status = 'Outstanding';
                }

                $record->save();
                $applied++;

                Log::info('✅ Applied adjustment to AR', [
                    'adjustment_id' => $adjustment->id,
                    'ar_aging_id' => $record->id,
                    'invoice_no' => $record->invoice_no,
                    'adjustment_amount' => $adjustment->amount,
                    'new_ar_adjustments' => $record->ar_adjustments,
                    'new_balance' => $record->net_ar_balance
                ]);

            } catch (\Exception $e) {
                $errors++;
                Log::error('Failed to apply adjustment', [
                    'adjustment_id' => $adjustment->id,
                    'error' => $e->getMessage()
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✅ Successfully applied: {$applied}");
        $this->warn("⚠️  Skipped (no matching AR): {$skipped}");

        if ($errors > 0) {
            $this->error("❌ Errors: {$errors}");
        }

        $this->newLine();
        $this->info('🎉 Done! Check your AR Profile to see the adjustments now appearing in invoices.');

        return 0;
    }
}
