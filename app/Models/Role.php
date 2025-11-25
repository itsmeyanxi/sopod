<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = [
            'Admin', 
            'IT', 
            'CSR_Approver',
            'CSR_Creator', 
            'Delivery_Creator', 
            'Delivery_Approver',
            'CC_Creator',
            'CC_Approver',
            'Accounting_Creator',
            'Accounting_Approver',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }
}
