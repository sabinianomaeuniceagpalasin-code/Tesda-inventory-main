<?php

namespace App\Services;

use App\Models\FormRecord;
use App\Models\IssuedLog;
use Illuminate\Support\Facades\DB;

class FormArchiveService
{
    public static function tryArchiveByReference(string $referenceNo): bool
    {
        // total items issued under this reference
        $totalItems = IssuedLog::where('reference_no', $referenceNo)->count();

        if ($totalItems === 0) return false;

        // completed means: returned OR item status in actioned list
        $completedItems = DB::table('issuedlog as i')
            ->join('items as it', 'i.serial_no', '=', 'it.serial_no')
            ->where('i.reference_no', $referenceNo)
            ->where(function ($q) {
                $q->whereNotNull('i.actual_return_date')
                  ->orWhereIn('it.status', ['Damaged', 'Unserviceable', 'Lost']);
            })
            ->count();

        if ($completedItems === $totalItems) {
            FormRecord::where('reference_no', $referenceNo)
                ->update(['status' => 'Archived', 'updated_at' => now()]);
            return true;
        }

        return false;
    }
}