<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class IssuedLogController extends Controller
{
    /**
     * Return student matches for live suggestions
     */
    public function searchStudents(Request $request)
    {
        $q = $request->get('query', '');
        if (strlen($q) < 1) {
            return response()->json([]);
        }

        $students = DB::table('student')
            ->select('student_id', 'student_name', 'student_number', 'batch')
            ->where('student_name', 'LIKE', "%{$q}%")
            ->limit(10)
            ->get();

        return response()->json($students);
    }

    /**
     * Return available serial numbers for items filtered by form type
     */
    public function availableSerials(Request $request)
    {
        try {
            $formType = $request->get('form_type', 'ICS');
            $propertyNo = trim($request->get('property_no', ''));

            if (empty($propertyNo)) {
                return response()->json([]);
            }

            $tools = DB::table('items')
                ->join('propertyinventory', 'items.property_no', '=', 'propertyinventory.property_no')
                ->whereRaw("TRIM(LOWER(items.status)) = ?", ['available'])
                ->where('items.property_no', $propertyNo)
                ->select(
                    'items.serial_no',
                    'items.property_no',
                    'items.item_name',
                    'propertyinventory.unit_cost'
                )
                ->get();

            // Filter by form type and cost
            $filtered = $tools->filter(function ($item) use ($formType) {
                $cost = floatval(str_replace(',', '', $item->unit_cost));
                if ($formType === 'ICS') return $cost >= 15000 && $cost <= 49000;
                if ($formType === 'PAR') return $cost >= 50000;
                return false;
            })->values();

            return response()->json($filtered);
        } catch (\Exception $e) {
            \Log::error('Available serials error: '.$e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    }

    /**
     * Quick check if reference number already exists
     */
    public function checkReference($reference)
    {
        $exists = DB::table('formrecords')
            ->where('reference_no', $reference)
            ->exists();

        return response()->json(['exists' => $exists]);
    }

    /**
     * Store the issuance and update items
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'student_name' => 'required|string',
            'selected_serials' => 'required|array|min:1',
            'selected_serials.*' => 'required|string',
            'form_type' => ['required', Rule::in(['ICS','PAR'])],
            'issued_date' => 'required|date',
            'return_date' => 'nullable|date|after_or_equal:issued_date',
            'reference_no' => 'required|string|unique:formrecords,reference_no',
        ]);

        // Find student_id from student_name
        $student = DB::table('student')
            ->where('student_name', $data['student_name'])
            ->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => "Student '{$data['student_name']}' not found in the database."
            ], 400);
        }

        $studentId = $student->student_id;
        $issuedDate = Carbon::parse($data['issued_date'])->toDateString();
        $returnDate = $data['return_date'] ? Carbon::parse($data['return_date'])->toDateString() : null;

        DB::beginTransaction();
        try {
            $propertyNos = [];

            foreach ($data['selected_serials'] as $serial) {
                $tool = DB::table('items')->where('serial_no', $serial)->first();
                if (!$tool) throw new \Exception("Serial number {$serial} not found.");

                $propertyNo = $tool->property_no;
                $propertyNos[] = $propertyNo;

                // Insert into issuedlog
                DB::table('issuedlog')->insert([
                    'student_id' => $studentId,
                    'serial_no' => $serial,
                    'property_no' => $propertyNo,
                    'form_type' => $data['form_type'],
                    'issued_date' => $issuedDate,
                    'return_date' => $returnDate,
                    'reference_no' => $data['reference_no'],
                    'usage_hours' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Update item status
                DB::table('items')->where('serial_no', $serial)
                    ->update([
                        'status' => 'Issued',
                        'usage_count' => DB::raw('COALESCE(usage_count, 0) + 1'),
                        'updated_at' => now()
                    ]);
            }

            // Insert into formrecords with logged-in user
            DB::table('formrecords')->insert([
                'form_type' => $data['form_type'],
                'student_name' => $data['student_name'],
                'item_count' => count($data['selected_serials']),
                
                // ⭐ CORRECT FIELD
                'issued_by' => Auth::user()->full_name ?? 'System',
                
                'status' => 'Active',
                'reference_no' => $data['reference_no'],
                'created_at' => now(),
                'updated_at' => now(),
            ]); 

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Form saved successfully',
                'data' => $data,
                'property_nos' => $propertyNos
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('IssuedLog store error: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server error: '.$e->getMessage()
            ], 500);
        }
    }

    /**
     * View issuance details
     */
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
            $tool = DB::table('items')->where('property_no', $log->property_no)->first();
            $inventory = DB::table('propertyinventory')->where('property_no', $log->property_no)->first();

            $details[] = [
                'property_no' => $log->property_no,
                'tool_name' => $tool ? $tool->item_name : 'N/A',
                'quantity' => 1,
                'unit_cost' => $inventory ? (float)$inventory->unit_cost : 0,
                'total_cost' => $inventory ? (float)$inventory->unit_cost : 0,
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
