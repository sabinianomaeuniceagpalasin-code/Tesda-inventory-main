<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class MaintenanceController extends Controller
{
    public function createTicketFromDamage($damageId)
    {
        // Get damage report
        $damage = DB::table('damagereports')
            ->where('damage_id', $damageId)
            ->first();

        if (!$damage) {
            return response()->json([
                'success' => false,
                'message' => 'Damage report not found.'
            ], 404);
        }

        // Already ticketed? (fast check)
        if ((int)($damage->is_ticketed ?? 0) === 1) {
            return response()->json([
                'success' => false,
                'message' => 'This damage report is already ticketed.'
            ], 409);
        }

        // Extra safety: check maintenance table for an existing ticket for this damage_id
        $exists = DB::table('maintenance')
            ->where('damage_id', $damage->damage_id)
            ->exists();

        if ($exists) {
            // If this happens, keep data consistent
            DB::table('damagereports')
                ->where('damage_id', $damage->damage_id)
                ->update([
                    'is_ticketed' => 1,
                    'ticketed_at' => now(),
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => false,
                'message' => 'Maintenance ticket already exists for this damage report.'
            ], 409);
        }

        // Insert maintenance ticket
        // repair_cost and expected_completion can be null if you want to fill them later in the Edit modal
        $maintenanceId = DB::table('maintenance')->insertGetId([
            'serial_no' => $damage->serial_no,
            'issue_type' => $damage->observation,   // map observation -> issue_type
            'repair_cost' => null,
            'date_reported' => $damage->reported_at ?? now(),
            'expected_completion' => null,
            'remarks' => null,
            'damage_id' => $damage->damage_id,
        ]);

        // Mark damage report as ticketed
        DB::table('damagereports')
            ->where('damage_id', $damage->damage_id)
            ->update([
                'is_ticketed' => 1,
                'ticketed_at' => now(),
                'updated_at' => now(),
            ]);

            // ✅ Update item status so it becomes "Maintenance / For Repair"
            DB::table('items') // <-- change if your inventory table is not "tools"
            ->where('serial_no', $damage->serial_no)
            ->update([
                'status' => 'For Repair', // or 'Maintenance' if that's what you display
                'updated_at' => now(),    // if tools has updated_at
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance ticket created.',
            'maintenance_id' => $maintenanceId,
        ]);
    }
}