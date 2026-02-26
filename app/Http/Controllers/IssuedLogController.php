<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class IssuedLogController extends Controller
{
    public function store(Request $request)
    {
        // Scanner sends serials[]
        $serials = $request->input('serials', []);

        $data = $request->validate([
            'borrower_name' => 'required|string|max:255',
            'form_type' => ['required', Rule::in(['ICS', 'PAR'])],
            'issued_date' => 'required|date',
            'return_date' => 'nullable|date|after_or_equal:issued_date',
        ]);

        if (!is_array($serials) || count($serials) < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Please scan at least one item.'
            ], 422);
        }

        // Clean serial list
        $serials = array_values(array_unique(array_map('trim', $serials)));

        // Generate reference number
        $year = date('Y');
        $type = $data['form_type'];

        $lastRecord = DB::table('formrecords')
            ->where('form_type', $type)
            ->where('reference_no', 'LIKE', "{$year}-{$type}-%")
            ->orderBy('form_id', 'desc')
            ->first();

        $nextNum = $lastRecord
            ? str_pad(((int) explode('-', $lastRecord->reference_no)[2]) + 1, 4, '0', STR_PAD_LEFT)
            : '0001';

        $referenceNo = "{$year}-{$type}-{$nextNum}";

        DB::beginTransaction();
        try {
            // Lock items
            $items = DB::table('items')
                ->whereIn('serial_no', $serials)
                ->lockForUpdate()
                ->get()
                ->keyBy('serial_no');

            // Validate existence + availability
            foreach ($serials as $sn) {
                if (!isset($items[$sn])) {
                    throw new \Exception("Serial {$sn} does not exist.");
                }
                if (strtolower($items[$sn]->status) !== 'available') {
                    throw new \Exception("Serial {$sn} is not available.");
                }
            }

            // Insert issued logs
            foreach ($serials as $sn) {
                $item = $items[$sn];

                DB::table('issuedlog')->insert([
                    'borrower_name' => $data['borrower_name'],
                    'serial_no' => $sn,
                    'property_no' => $item->property_no,
                    'form_type' => $type,
                    'issued_date' => $data['issued_date'],
                    'return_date' => $data['return_date'],
                    'reference_no' => $referenceNo,
                    'usage_hours' => 0,
                    'issued_by' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('items')->where('serial_no', $sn)->update([
                    'status' => 'Issued',
                    'usage_count' => DB::raw('COALESCE(usage_count,0) + 1'),
                    'updated_at' => now(),
                ]);
            }

            // Insert summary
            DB::table('formrecords')->insert([
                'form_type' => $type,
                'borrower_name' => $data['borrower_name'],
                'item_count' => count($serials),
                'issued_by' => Auth::user()->full_name ?? 'Admin',
                'status' => 'Active',
                'reference_no' => $referenceNo,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'reference_no' => $referenceNo
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function view($reference_no)
{
    $summary = DB::table('formrecords')->where('reference_no', $reference_no)->first();
    if (!$summary) {
        return response()->json(['error' => 'Record not found'], 404);
    }

    $logs = DB::table('issuedlog')->where('reference_no', $reference_no)->get();

    $details = [];
    foreach ($logs as $log) {
        $item = DB::table('items')->where('serial_no', $log->serial_no)->first();
        $inv = DB::table('propertyinventory')->where('property_no', $log->property_no)->first();

        $unit = $inv ? (float) $inv->unit_cost : 0;

        $details[] = [
            'property_no' => $log->property_no,
            'tool_name' => $item?->item_name ?? 'N/A',
            'quantity' => 1,
            'unit_cost' => $unit,
            'total_cost' => $unit,
            'serial_no' => $log->serial_no,
        ];
    }

    // ✅ get processor from issuedlog → join users → format "NAME (ROLE)"
    $processor = DB::table('issuedlog as i')
        ->leftJoin('users as u', 'i.issued_by', '=', 'u.user_id')
        ->where('i.reference_no', $reference_no)
        ->orderByDesc('i.issue_id')
        ->select(DB::raw("COALESCE(CONCAT(u.first_name,' ',u.last_name,' (',UPPER(u.role),')'), 'N/A') as processed_by"))
        ->first();

    return response()->json([
        'borrower_name' => $summary->borrower_name,
        'issued_by' => $processor?->processed_by ?? 'N/A',  // ✅ CJ SADSAD (ADMIN)
        'form_type' => $summary->form_type,
        'reference_no' => $summary->reference_no,
        'details' => $details
    ]);
}
}