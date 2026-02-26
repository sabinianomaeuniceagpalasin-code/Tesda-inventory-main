<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class IssuedLogController extends Controller
{

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_name' => 'required|string',
            'selected_serials' => 'required|array|min:1',
            'selected_serials.*' => 'required|string|exists:items,serial_no',
            'form_type' => ['required', Rule::in(['ICS', 'PAR'])],
            'issued_date' => 'required|date',
            'return_date' => 'nullable|date|after_or_equal:issued_date',
        ]);

        $year = date('Y');
        $type = $data['form_type'];
        $lastRecord = DB::table('formrecords')
            ->where('form_type', $type)
            ->where('reference_no', 'LIKE', "{$year}-{$type}-%")
            ->orderBy('form_id', 'desc')
            ->first();

        $nextNum = $lastRecord ? str_pad(((int) explode('-', $lastRecord->reference_no)[2]) + 1, 4, '0', STR_PAD_LEFT) : '0001';
        $autoReferenceNo = "{$year}-{$type}-{$nextNum}";

        $student = DB::table('student')->where('student_name', $data['student_name'])->first();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found.'], 400);
        }

        DB::beginTransaction();
        try {
            foreach ($data['selected_serials'] as $serial) {
                $item = DB::table('items')->where('serial_no', $serial)->first();

                DB::table('issuedlog')->insert([
                    'student_id' => $student->student_id,
                    'serial_no' => $serial,
                    'property_no' => $item->property_no,
                    'form_type' => $type,
                    'issued_date' => $data['issued_date'],
                    'return_date' => $data['return_date'],
                    'reference_no' => $autoReferenceNo,
                    'usage_hours' => 0,
                    'created_at' => now(),
                ]);

                DB::table('items')->where('serial_no', $serial)->update([
                    'status' => 'Issued',
                    'usage_count' => DB::raw('COALESCE(usage_count, 0) + 1'),
                    'updated_at' => now()
                ]);
            }

            DB::table('formrecords')->insert([
                'form_type' => $type,
                'student_name' => $data['student_name'],
                'item_count' => count($data['selected_serials']),
                'issued_by' => Auth::user()->full_name ?? 'Admin',
                'status' => 'Active',
                'reference_no' => $autoReferenceNo,
                'created_at' => now(),
            ]);

            DB::commit();
            return response()->json(['success' => true, 'reference_no' => $autoReferenceNo]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function view($reference_no)
    {
        $summary = DB::table('formrecords')->where('reference_no', $reference_no)->first();
        if (!$summary) {
            return response()->json(['error' => 'Record not found'], 404);
        }

        $issuedLogs = DB::table('issuedlog')->where('reference_no', $reference_no)->get();
        if ($issuedLogs->isEmpty()) {
            return response()->json(['error' => 'No issued items found'], 404);
        }

        $details = [];
        foreach ($issuedLogs as $log) {
            $tool = DB::table('items')->where('serial_no', $log->serial_no)->first();
            $inventory = DB::table('propertyinventory')->where('property_no', $log->property_no)->first();

            $details[] = [
                'property_no' => $log->property_no,
                'tool_name' => $tool ? $tool->item_name : 'N/A',
                'quantity' => 1,
                'unit_cost' => $inventory ? (float) $inventory->unit_cost : 0,
                'total_cost' => $inventory ? (float) $inventory->unit_cost : 0,
                'serial_no' => $log->serial_no
            ];
        }

        return response()->json([
            'issued_to' => $summary->student_name,
            'form_type' => $summary->form_type,
            'reference_no' => $summary->reference_no,
            'details' => $details
        ]);
    }
}