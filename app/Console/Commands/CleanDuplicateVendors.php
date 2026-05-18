<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Vendor;

class CleanDuplicateVendors extends Command
{
    protected $signature   = 'vendors:clean-duplicates {--dry-run : Preview without deleting}';
    protected $description = 'Delete vendor records whose vendor_code does not start with VE, VN, or VT';

    public function handle()
    {
        $toDelete = Vendor::whereRaw("vendor_code NOT REGEXP '^(VE|VN|VT)'")->get();

        if ($toDelete->isEmpty()) {
            $this->info('No invalid vendor codes found.');
            return;
        }

        $this->table(['ID', 'Vendor Code', 'Vendor Name', 'Category'], $toDelete->map(fn($v) => [
            $v->id, $v->vendor_code, $v->vendor_name, $v->category,
        ])->toArray());

        if ($this->option('dry-run')) {
            $this->warn("Dry run — {$toDelete->count()} record(s) would be deleted.");
            return;
        }

        if (!$this->confirm("Delete these {$toDelete->count()} record(s)?")) {
            $this->info('Cancelled.');
            return;
        }

        Vendor::whereRaw("vendor_code NOT REGEXP '^(VE|VN|VT)'")->delete();
        $this->info("Deleted {$toDelete->count()} vendor record(s).");
    }
}
