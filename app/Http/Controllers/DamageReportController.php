<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DamageReportController extends Controller
{
    // 👇 PUT IT HERE
    public function table()
        {
            $damageReports = DB::table('damagereports as d')
                ->leftJoin('items as i', 'i.serial_no', '=', 'd.serial_no')
                ->where('d.is_ticketed', 0)
                ->select('d.*', 'i.item_name') // ✅ include item_name
                ->orderByDesc('d.reported_at')
                ->get();

            return view('partials.damage_rows', compact('damageReports'));
        }
}