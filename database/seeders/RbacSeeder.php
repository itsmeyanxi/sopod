<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\SubDepartment;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            [
                'name'  => 'Supply Chain',
                'color' => '#3B82F6',
                'subs'  => ['Category', 'Logistics', 'Imports', 'Quality Assurance'],
            ],
            [
                'name'  => 'Administration',
                'color' => '#8B5CF6',
                'subs'  => ['General Administration'],
            ],
            [
                'name'  => 'Purchasing',
                'color' => '#F59E0B',
                'subs'  => ['Purchasing'],
            ],
            [
                'name'  => 'Warehouse',
                'color' => '#10B981',
                'subs'  => ['Warehouse Operations'],
            ],
            [
                'name'  => 'HR & Administration',
                'color' => '#EC4899',
                'subs'  => ['Benefits', 'Employee Relations', 'Recruitment', 'Training and Development'],
            ],
            [
                'name'  => 'Information System',
                'color' => '#6366F1',
                'subs'  => ['IT Operations'],
            ],
            [
                'name'  => 'Sales',
                'color' => '#EF4444',
                'subs'  => ['Sales', 'Customer Service'],
            ],
            [
                'name'  => 'Credit & Collection',
                'color' => '#F97316',
                'subs'  => ['Credit & Collection'],
            ],
            [
                'name'  => 'Executive',
                'color' => '#1F2937',
                'subs'  => ['Department Head', 'General Manager', 'CFO', 'President', 'Vice President'],
            ],
            [
                'name'  => 'Finance',
                'color' => '#059669',
                'subs'  => ['Finance Management'],
            ],
            [
                'name'  => 'Finance & Accounting',
                'color' => '#0D9488',
                'subs'  => ['Trade Local/Imports', 'Nontrade/Officers and Employee'],
            ],
            [
                'name'  => 'Treasury',
                'color' => '#7C3AED',
                'subs'  => ['Treasury Operations'],
            ],
            [
                'name'  => 'Delivery',
                'color' => '#2563EB',
                'subs'  => ['Delivery'],
            ],
            [
                'name'  => 'Accounting',
                'color' => '#16A34A',
                'subs'  => ['Accounting'],
            ],
            [
                'name'  => 'Procurement',
                'color' => '#CA8A04',
                'subs'  => ['Requisitioner', 'PR Approver', 'Procurement Approver', 'PO Creator'],
            ],
            [
                'name'  => 'Operations NBC',
                'color' => '#D97706',
                'subs'  => ['Operations NBC'],
            ],
        ];

        foreach ($departments as $dept) {
            $department = Department::firstOrCreate(
                ['name' => $dept['name']],
                ['color' => $dept['color']]
            );

            foreach ($dept['subs'] as $sub) {
                SubDepartment::firstOrCreate(
                    ['department_id' => $department->id, 'name' => $sub]
                );
            }
        }
    }
}
