<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SerialController extends Controller
{
    public function getNextSerials($qty = 1): JsonResponse
    {
        $qty = (int) $qty;
        if ($qty <= 0) {
            return response()->json(['serials' => []]);
        }

        // 1) Serial numbers already in ITEMS
        $dbSerials = DB::table('items')
            ->whereNotNull('serial_no')
            ->pluck('serial_no')
            ->map(fn($sn) => strtoupper(trim($sn)))
            ->toArray();

        // 2) Serial numbers already requested in ITEM APPROVAL REQUEST
        // NOTE: adjust table name if yours is plural
        // NOTE: include pending + approved so they are "reserved"
        $requestedSerialStrings = DB::table('item_approval_requests')
            ->whereIn('status', ['pending', 'approved'])
            ->pluck('serial_number')
            ->toArray();

        $requestedSerials = [];
        foreach ($requestedSerialStrings as $row) {
            $parts = array_filter(array_map('trim', explode(',', (string) $row)));
            foreach ($parts as $sn) {
                $requestedSerials[] = strtoupper($sn);
            }
        }

        // 3) Serials to exclude from request (current queue)
        $exclude = request()->query('exclude', '');
        $excludeSerials = array_map(
            fn($s) => strtoupper(trim($s)),
            array_filter(explode(',', (string) $exclude))
        );

        // Merge all serials we must avoid duplicates with
        $allSerials = array_values(array_unique(array_merge($dbSerials, $requestedSerials, $excludeSerials)));

        // Extract numeric parts for max finding (SN0001 => 1)
        $numbers = [];
        foreach ($allSerials as $sn) {
            $sn = strtoupper(trim($sn));
            // keep only digits
            $num = (int) preg_replace('/\D+/', '', $sn);
            if ($num > 0) $numbers[] = $num;
        }

        $start = count($numbers) ? max($numbers) + 1 : 1;

        // Generate next N serials, ensuring they don't exist in allSerials
        $existingSet = array_fill_keys($allSerials, true);

        $nextSerials = [];
        $current = $start;

        while (count($nextSerials) < $qty) {
            $candidate = 'SN' . str_pad($current, 4, '0', STR_PAD_LEFT);

            if (!isset($existingSet[$candidate])) {
                $nextSerials[] = $candidate;
                $existingSet[$candidate] = true;
            }

            $current++;
        }

        return response()->json(['serials' => $nextSerials]);
    }
}
