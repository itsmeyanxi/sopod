<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixArAdjustmentCustomerCodes extends Command
{
    protected $signature = 'ar:fix-adjustment-customer-codes';
    protected $description = 'Populate missing customer_code in ar_adjustments table by matching customer names from ar_aging';

    public function handle()
    {
        $this->info('🔄 Starting to fix AR adjustment customer codes...');

        // Get all adjustments with NULL customer_code
        $adjustments = DB::table('ar_adjustments')
            ->whereNull('customer_code')
            ->get();

        if ($adjustments->isEmpty()) {
            $this->info('✅ No adjustments need fixing. All have customer codes!');
            return 0;
        }

        $this->info("Found {$adjustments->count()} adjustments with missing customer codes");

        $updated = 0;
        $failed = 0;

        $progressBar = $this->output->createProgressBar($adjustments->count());
        $progressBar->start();

        foreach ($adjustments as $adj) {
            // Try to find matching customer code from ar_aging by customer name
            $customerCode = null;

            // Strategy 1: Match by customer name
            if (!empty($adj->customer_name)) {
                $match = DB::table('ar_aging')
                    ->where('client_name', 'LIKE', '%' . trim($adj->customer_name) . '%')
                    ->first();

                if ($match && !empty($match->customer_code)) {
                    $customerCode = $match->customer_code;
                }
            }

            // Strategy 2: Match by invoice number
            if (!$customerCode && !empty($adj->invoice_number)) {
                $match = DB::table('ar_aging')
                    ->where('invoice_no', $adj->invoice_number)
                    ->first();

                if ($match && !empty($match->customer_code)) {
                    $customerCode = $match->customer_code;
                }
            }

            // Strategy 3: Match by DR number
            if (!$customerCode && !empty($adj->dr_no)) {
                $match = DB::table('ar_aging')
                    ->where('dr_no', $adj->dr_no)
                    ->first();

                if ($match && !empty($match->customer_code)) {
                    $customerCode = $match->customer_code;
                }
            }

            // Update if we found a match
            if ($customerCode) {
                DB::table('ar_adjustments')
                    ->where('id', $adj->id)
                    ->update(['customer_code' => $customerCode]);

                $updated++;

                Log::info('✅ Fixed adjustment customer code', [
                    'adjustment_id' => $adj->id,
                    'customer_name' => $adj->customer_name,
                    'matched_code' => $customerCode,
                ]);
            } else {
                $failed++;

                Log::warning('⚠️ Could not find customer code', [
                    'adjustment_id' => $adj->id,
                    'customer_name' => $adj->customer_name,
                    'invoice_number' => $adj->invoice_number,
                    'dr_no' => $adj->dr_no,
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✅ Successfully updated: {$updated}");
        $this->warn("⚠️ Could not match: {$failed}");

        if ($failed > 0) {
            $this->warn("\nRun this SQL to see unmatched adjustments:");
            $this->line("SELECT id, customer_name, invoice_number, dr_no FROM ar_adjustments WHERE customer_code IS NULL;");
        }

        return 0;
    }
}
