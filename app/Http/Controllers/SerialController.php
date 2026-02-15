<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SerialController extends Controller
{
    public function getNextSerials($qty = 1): JsonResponse
    {
        // Get serials from DB
        $dbSerials = DB::table('items')
            ->whereNotNull('serial_no')
            ->pluck('serial_no')
            ->map(fn($sn) => strtoupper(trim($sn)))
            ->toArray();

        // Get serials to exclude from request (current queue)
        $exclude = request()->query('exclude', '');
        $excludeSerials = array_map(fn($s) => strtoupper(trim($s)), array_filter(explode(',', $exclude)));

        // Merge DB + exclude serials
        $allSerials = array_merge($dbSerials, $excludeSerials);

        // Extract numbers
        $numbers = array_map(fn($sn) => intval(str_replace('SN', '', $sn)), $allSerials);
        $numbers = array_filter($numbers, fn($n) => $n > 0);

        // Starting number
        $start = count($numbers) ? max($numbers) + 1 : 1;

        // Generate next N serials
        $nextSerials = [];
        for ($i = 0; $i < $qty; $i++) {
            $nextSerials[] = 'SN' . str_pad($start + $i, 4, '0', STR_PAD_LEFT);
        }

        return response()->json(['serials' => $nextSerials]);
    }
}
