<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Notification;
use Carbon\Carbon;

class PredictiveAnalyticsController extends Controller
{
    public function generate()
    {
        $items = Item::all();

        foreach ($items as $item) {
            // Time-based maintenance
            if ($item->last_maintenance_date && $item->maintenance_interval_days) {
                $nextMaintenance = Carbon::parse($item->last_maintenance_date)
                    ->addDays($item->maintenance_interval_days);

                $daysLeft = now()->diffInDays($nextMaintenance, false);

                if ($daysLeft <= 30) {
                    Notification::create([
                        'item_id' => $item->item_id,
                        'title' => "Item nearing maintenance schedule",
                        'message' => "{$item->item_name} (SN: {$item->serial_no}) requires maintenance in {$daysLeft} days.",
                        'type' => "maintenance"
                    ]);
                }
            }

            // Usage-based maintenance
            if ($item->maintenance_threshold_usage && $item->usage_count >= $item->maintenance_threshold_usage) {
                Notification::create([
                    'item_id' => $item->item_id,
                    'title' => "High Usage Warning",
                    'message' => "{$item->item_name} (SN: {$item->serial_no}) has high usage ({$item->usage_count}).",
                    'type' => "inventory"
                ]);
            }
        }

        return back()->with('success', 'Notifications generated successfully!');
    }
}
