<?php

namespace App\Listeners;

use App\Events\SalesOrderChanged;
use App\Models\ChangeNotification;
use App\Models\SalesOrderChange;
use App\Models\User;

class NotifyUsersOfSalesOrderChange
{
    public function handle(SalesOrderChanged $event): void
    {
        // Get the latest change
        $latestChange = SalesOrderChange::where('sales_order_id', $event->salesOrder->id)
            ->latest()
            ->first();

        if (!$latestChange) {
            return;
        }

        // Get users to notify (admins, managers, or specific users)
        // Customize this based on your needs
        $usersToNotify = User::whereIn('role', ['admin', 'manager'])
            ->where('id', '!=', auth()->id()) // Don't notify the user making the change
            ->get();

        // Create notifications for each user
        foreach ($usersToNotify as $user) {
            ChangeNotification::create([
                'user_id' => $user->id,
                'sales_order_change_id' => $latestChange->id,
                'is_read' => false,
            ]);
        }
    }
}