<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Consolidate user roles:
     * CSR_Creator + CSR_Approver → CSR
     * PR_Creator → Requisitioner
     * PR_Approver → PR_Approver (KEPT SEPARATE - PR approval only)
     * PO_Creator + RFP_Creator → Purchasing
     * PO_Approver + RFP_Approver → Procurement_Approver
     */
    public function up(): void
    {
        $roleMap = [
            'CSR_Creator' => 'CSR',
            'CSR_Approver' => 'CSR',
            'PR_Creator' => 'Requisitioner',
            // PR_Approver stays as PR_Approver (no change)
            'PO_Creator' => 'Purchasing',
            'PO_Approver' => 'Procurement_Approver',
            'RFP_Creator' => 'Purchasing',
            'RFP_Approver' => 'Procurement_Approver',
        ];

        // Update legacy 'role' column
        foreach ($roleMap as $old => $new) {
            DB::table('users')->where('role', $old)->update(['role' => $new]);
        }

        // Update 'roles' JSON column
        $users = DB::table('users')->whereNotNull('roles')->get();

        foreach ($users as $user) {
            $roles = json_decode($user->roles, true);
            if (!is_array($roles)) continue;

            $updated = false;
            $newRoles = [];

            foreach ($roles as $role) {
                if (isset($roleMap[$role])) {
                    $newRoles[] = $roleMap[$role];
                    $updated = true;
                } else {
                    $newRoles[] = $role;
                }
            }

            if ($updated) {
                // Remove duplicates while preserving order
                $newRoles = array_values(array_unique($newRoles));
                DB::table('users')->where('id', $user->id)->update([
                    'roles' => json_encode($newRoles),
                ]);
            }
        }
    }

    /**
     * Reverse the migration (restore old role names)
     */
    public function down(): void
    {
        $reverseMap = [
            'CSR' => 'CSR_Creator',
            'Requisitioner' => 'PR_Creator',
            'Purchasing' => 'PO_Creator',
            'Procurement_Approver' => 'PR_Approver',
        ];

        foreach ($reverseMap as $new => $old) {
            DB::table('users')->where('role', $new)->update(['role' => $old]);
        }

        $users = DB::table('users')->whereNotNull('roles')->get();

        foreach ($users as $user) {
            $roles = json_decode($user->roles, true);
            if (!is_array($roles)) continue;

            $updated = false;
            $newRoles = [];

            foreach ($roles as $role) {
                if (isset($reverseMap[$role])) {
                    $newRoles[] = $reverseMap[$role];
                    $updated = true;
                } else {
                    $newRoles[] = $role;
                }
            }

            if ($updated) {
                $newRoles = array_values(array_unique($newRoles));
                DB::table('users')->where('id', $user->id)->update([
                    'roles' => json_encode($newRoles),
                ]);
            }
        }
    }
};
