<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Notification;
use App\Models\DamageReport;
use App\Models\Item;

class DashboardController extends Controller
{
    public function index()
    {
        // ---------- Dashboard Stats ----------
        $maintenanceData = $this->getMaintenanceRecords();
        $maintenanceRecords = $maintenanceData['records'];
        $maintenanceCounts = $maintenanceData['counts'];
        $notifications = Notification::latest()->get();
        $damageData = $this->getDamageReports();
        $items = Item::orderBy('item_name')->get();
        $damageReports = $damageData['damageReports'];
        $damageCounts = $damageData['damageCounts'];
        $damageReports = DamageReport::with('item')->orderBy('reported_at', 'desc')->get();
        $totalIssuedItems = DB::table('issuedlog')->count();
        $totalItems = DB::table('items')->count();
        $availableItems = DB::table('items')->where('status', 'Available')->count();
        $issuedItems = DB::table('items')->where('status', 'Issued')->count();
        $forRepair = DB::table('items')->whereIn('status', ['For Repair', 'Damaged'])->count();
        $missingItems = DB::table('items')->where('status', 'Lost')->count();
        $lowStockThreshold = 5;
        $lowStock = DB::table('propertyinventory')->where('quantity', '<', $lowStockThreshold)->count();
        $itemsUsage = DB::table('issuedlog')
            ->select(
                'serial_no',
                DB::raw('SUM(usage_hours) as total_usage_hours')
            )
            ->groupBy('serial_no');

        // ---------- Inventory ----------
        $inventory = DB::table('items')
            ->select('status', 'serial_no', 'item_name', 'source_of_fund', 'classification', DB::raw('DATE(date_acquired) as date_acquired'))
            ->get();

        // ---------- Issued Items List ----------
        $issuedItemsList = DB::table('issuedlog as i')
            ->join('items as it', 'i.serial_no', '=', 'it.serial_no')
            ->leftJoin('formrecords as f', 'i.reference_no', '=', 'f.reference_no')
            ->where('it.status', 'Issued')
            ->whereRaw('i.issue_id = (
                SELECT MAX(issue_id) 
                FROM issuedlog 
                WHERE serial_no = i.serial_no
            )')
            ->select(
                'i.issue_id',
                'i.property_no',
                'i.serial_no',
                'i.return_date',
                'f.student_name as issued_to',
                'f.issued_by as issued_by',
                'i.issued_date',
                'it.item_name as item',
            )
            ->orderBy('i.issued_date', 'desc')
            ->get();



        // ---------- Form Records ----------
        $issuedForms = DB::table('formrecords')
            ->select('form_id', 'form_type', 'reference_no', 'created_at', 'student_name', 'item_count', 'status')
            ->orderBy('created_at', 'desc')
            ->get();

        $formSummaryCounts = (object) [
            'total_forms' => $issuedForms->count(),
            'ics_forms' => $issuedForms->where('form_type', 'ICS')->count(),
            'par_forms' => $issuedForms->where('form_type', 'PAR')->count(),
            'active_forms' => $issuedForms->where('status', 'Active')->count(),
            'archived_forms' => $issuedForms->where('status', 'Archived')->count(),
        ];

        // ---------- Usage & Issued Frequency ----------
        $issuedFrequency = DB::table('issuedlog')
            ->join('items', 'issuedlog.serial_no', '=', 'items.serial_no')
            ->select('items.item_name', DB::raw('COUNT(*) as total'))
            ->groupBy('items.item_name')
            ->get();

        $usageData = DB::table('items')
            ->select('item_name', DB::raw('SUM(usage_count) as total_usage'))
            ->groupBy('item_name')
            ->get();

        // ---------- Maintenance Records ----------
        $maintenanceForecast = DB::table('items')
            ->leftJoinSub($itemsUsage, 'usage', function ($join) {
                $join->on('items.serial_no', '=', 'usage.serial_no');
            })
            ->select(
                'items.item_id',
                'items.item_name',
                'items.serial_no',
                DB::raw("IFNULL(items.maintenance_threshold_usage, 0) as maintenance_threshold_usage"),
                'items.last_maintenance_date',
                'items.maintenance_interval_days',
                DB::raw("DATE_ADD(items.last_maintenance_date, INTERVAL items.maintenance_interval_days DAY) as next_maintenance_date"),
                DB::raw("IFNULL(usage.total_usage_hours, 0) as total_usage_hours")
            )
            ->get();

        $maintenanceCounts = [
            'total' => $maintenanceRecords->count(),
            'pending' => $maintenanceRecords->where('status', 'Pending')->count(),
            'completed' => $maintenanceRecords->where('status', 'Completed')->count(),
            'upcoming' => $maintenanceRecords->where('date', '>', now())->count(),
        ];

        $overdueMaintenance = $maintenanceForecast
            ->filter(fn($item) => $item->next_maintenance_date && Carbon::parse($item->next_maintenance_date)->isPast());

        $upcomingMaintenance = $maintenanceForecast
            ->filter(
                fn($item) => $item->next_maintenance_date
                && Carbon::parse($item->next_maintenance_date)->between(now(), now()->addDays(7))
            );


        $usageAlerts = DB::table('items')
            ->leftJoinSub($itemsUsage, 'usage', function ($join) {
                $join->on('items.serial_no', '=', 'usage.serial_no');
            })
            ->whereNotNull('maintenance_threshold_usage')
            ->select(
                'items.item_name',
                'items.serial_no',
                DB::raw('IFNULL(usage.total_usage_hours, 0) as total_usage_hours'),
                'items.maintenance_threshold_usage',
                DB::raw('items.maintenance_threshold_usage - IFNULL(usage.total_usage_hours, 0) AS remaining_hours')
            )
            ->orderBy('remaining_hours', 'ASC')
            ->get();

        $highRiskItems = $maintenanceForecast
            ->filter(fn($item) => ($item->maintenance_threshold_usage > 0 && $item->total_usage_hours >= $item->maintenance_threshold_usage)
                || ($item->next_maintenance_date && Carbon::parse($item->next_maintenance_date)->isPast()));


        // ---------- Return view ----------
        return view('dashboard', compact(
            'totalItems',
            'availableItems',
            'issuedItems',
            'forRepair',
            'lowStock',
            'missingItems',
            'inventory',
            'issuedForms',
            'formSummaryCounts',
            'issuedFrequency',
            'usageData',
            'maintenanceRecords',
            'maintenanceCounts',
            'issuedItemsList',
            'totalIssuedItems',
            'maintenanceForecast',
            'overdueMaintenance',
            'upcomingMaintenance',
            'usageAlerts',
            'highRiskItems',
            'notifications',
            'damageReports',
            'damageCounts',
            'items',
        ));
    }

    public function updateStatus(Request $request, $id)
    {
        DB::table('formrecords')
            ->where('form_id', $id)
            ->update([
                'status' => $request->status
            ]);

        return back()->with('success', 'Form status updated successfully.');
    }

    public function getFormTable()
    {
        $issuedForms = DB::table('formrecords')
            ->select('form_id', 'form_type', 'reference_no', 'created_at', 'student_name', 'item_count', 'status')
            ->orderBy('created_at', 'desc')
            ->get();

        $html = '';

        foreach ($issuedForms as $form) {
            $statusClass = strtolower($form->status);
            $createdAt = \Carbon\Carbon::parse($form->created_at)->format('F d, Y');

            $html .= "
            <tr>
                <td>{$form->form_type}</td>
                <td>{$form->reference_no}</td>
                <td>{$createdAt}</td>
                <td>{$form->student_name}</td>
                <td>{$form->item_count}</td>
                <td><span class='status {$statusClass}'>{$form->status}</span></td>
                <td><a href='#'>View</a></td>
            </tr>
        ";
        }

        return response()->json(['html' => $html]);
    }

    public function getInventoryTable()
    {
        $inventory = DB::table('items')
            ->select('serial_no', 'item_name', 'source_of_fund', 'classification', 'date_acquired', 'status')
            ->orderBy('item_name', 'asc')
            ->limit(10)
            ->get();

        $html = '';

        foreach ($inventory as $item) {

            if ($item->status === 'Available')
                $statusClass = 'text-green';
            elseif ($item->status === 'For Repair')
                $statusClass = 'text-brown';
            elseif ($item->status === 'Issued')
                $statusClass = 'text-blue';
            elseif ($item->status === 'Damaged' || $item->status === 'Lost')
                $statusClass = 'text-red';
            else
                $statusClass = '';

            $dateAcquired = $item->date_acquired ? \Carbon\Carbon::parse($item->date_acquired)->format('F d, Y') : '-';

            $html .= "
            <tr>
                <td>{$item->serial_no}</td>
                <td>{$item->item_name}</td>
                <td>{$item->source_of_fund}</td>
                <td>{$item->classification}</td>
                <td>{$dateAcquired}</td>
                <td><span class='{$statusClass}'>{$item->status}</span></td>
                <td class='action-buttons'>
                    <button class='edit-btn'>✏️</button>
                    <button class='delete-btn'>🗑️</button>
                </td>
            </tr>
        ";
        }

        return response()->json(['html' => $html]);
    }

    public function getListOfAllItemsTable()
    {
        $inventory = DB::table('items')
            ->select('item_name', 'classification', 'stock', 'remarks', 'status')
            ->orderBy('item_name', 'asc')
            ->limit(10)
            ->get();

        $html = '';

        foreach ($inventory as $item) {

            if ($item->status === 'Available')
                $statusClass = 'text-green';
            elseif ($item->status === 'For Repair')
                $statusClass = 'text-brown';
            elseif ($item->status === 'Issued')
                $statusClass = 'text-blue';
            elseif ($item->status === 'Damaged' || $item->status === 'Lost')
                $statusClass = 'text-red';
            else
                $statusClass = '';

            $html .= "
            <tr>
                <td>{$item->item_name}</td>
                <td>{$item->classification}</td>
                <td>{$item->stock}</td>
                <td>{$item->remarks}</td>
                <td><span class='{$statusClass}'>{$item->status}</span></td>
            </tr>
        ";
        }

        return response()->json(['html' => $html]);
    }

    public function getListofAllAvailableItemsTable()
    {
        $inventory = DB::table('items')
            ->select('item_name', 'classification', 'stock', 'remarks')
            ->orderBy('item_name', 'asc')
            ->limit(10)
            ->get();

        $html = '';

        foreach ($inventory as $item) {

            $html .= "
            <tr>
                <td>{$item->item_name}</td>
                <td>{$item->classification}</td>
                <td>{$item->stock}</td>
                <td>{$item->remarks}</td>
            </tr>
        ";
        }

        return response()->json(['html' => $html]);
    }

    public function getListofIssuedItemsTable()
    {
        $issuedItemsList = DB::table('issuedlog as i')
            ->join('items as it', 'i.serial_no', '=', 'it.serial_no')
            ->leftJoin('formrecords as f', 'i.reference_no', '=', 'f.reference_no')
            ->where('it.status', 'Issued')
            ->whereRaw('i.issue_id = (
            SELECT MAX(issue_id)
            FROM issuedlog
            WHERE serial_no = i.serial_no
        )')
            ->select(
                'i.issue_id',
                'i.property_no',
                'i.serial_no',
                'i.return_date',
                'f.student_name as issued_to',
                'f.issued_by as issued_by',
                'i.issued_date',
                'it.item_name as item',
                'it.classification as classification',
            )
            ->orderBy('i.issued_date', 'desc')
            ->limit(10)
            ->get();

        $html = '';
        foreach ($issuedItemsList as $item) {
            $returnDate = $item->return_date ? Carbon::parse($item->return_date)->format('F d, Y') : '-';
            $html .= "
            <tr>
                <td>{$item->item}</td>
                <td>{$item->classification}</td>
                <td>{$item->issued_to}</td>
                <td>" . Carbon::parse($item->issued_date)->format('F d, Y') . "</td>
                <td>{$returnDate}</td>
            </tr>
        ";
        }

        return response()->json(['html' => $html]);
    }

    public function getUnderMaintenanceListTable()
    {
        $records = DB::table('maintenance')
            ->join('items', 'maintenance.serial_no', '=', 'items.serial_no')
            ->select(
                'items.item_name',
                'items.classification',
                'maintenance.date_reported',
                'maintenance.issue_type',
                'items.remarks'
            )
            ->orderBy('maintenance.date_reported', 'desc')
            ->limit(10)
            ->get();

        $html = '';

        foreach ($records as $record) {
            $html .= "
        <tr>
            <td>{$record->item_name}</td>
            <td>{$record->classification}</td>
            <td>" . ($record->date_reported
                ? Carbon::parse($record->date_reported)->format('F d, Y')
                : 'N/A') . "</td>
            <td>{$record->issue_type}</td>
            <td>{$record->remarks}</td>
        </tr>";
        }

        return response()->json(['html' => $html]);
    }

    public function getLowStockItems()
    {
        $inventory = DB::table('items')
            ->select('item_name', 'classification', 'stock')
            ->orderBy('item_name', 'asc')
            ->limit(10)
            ->get();

        $html = '';

        foreach ($inventory as $item) {

            $html .= "
            <tr>
                <td>{$item->item_name}</td>
                <td>{$item->classification}</td>
                <td>{$item->stock}</td>
                <td>NOT YET</td>
                <td>NOT YET</td>
            </tr>
        ";
        }

        return response()->json(['html' => $html]);
    }

    public function getMissingItems()
    {
        $inventory = DB::table('items')
            ->select('item_name', 'classification', 'stock')
            ->orderBy('item_name', 'asc')
            ->limit(10)
            ->get();

        $html = '';

        foreach ($inventory as $item) {

            $html .= "
            <tr>
                <td>{$item->item_name}</td>
                <td>{$item->classification}</td>
                <td>NOT YET</td>
                <td>NOT YET</td>
            </tr>
        ";
        }

        return response()->json(['html' => $html]);
    }

    public function getIssuedItemsTable()
    {
        $issuedItemsList = DB::table('issuedlog as i')
            ->join('items as it', 'i.serial_no', '=', 'it.serial_no')
            ->leftJoin('formrecords as f', 'i.reference_no', '=', 'f.reference_no')
            ->where('it.status', 'Issued')
            ->whereRaw('i.issue_id = (
            SELECT MAX(issue_id)
            FROM issuedlog
            WHERE serial_no = i.serial_no
        )')
            ->select(
                'i.issue_id',
                'i.property_no',
                'i.serial_no',
                'i.return_date',
                'f.student_name as issued_to',
                'f.issued_by as issued_by',
                'i.issued_date',
                'it.item_name as item',
            )
            ->orderBy('i.issued_date', 'desc')
            ->get();

        $html = '';
        foreach ($issuedItemsList as $item) {
            $returnDate = $item->return_date ? Carbon::parse($item->return_date)->format('F d, Y') : '-';
            $html .= "
            <tr>
                <td>{$item->serial_no}</td>
                <td>{$item->issued_to}</td>
                <td>{$item->issued_by}</td>
                <td>" . \Carbon\Carbon::parse($item->issued_date)->format('F d, Y') . "</td>
                <td>{$returnDate}</td>
                <td>{$item->item}</td>
                <td class='action-buttons-issued'>
                    <button class='action-btn-issued return-btn-issued' title='Return' data-id='{$item->issue_id}'>
                        <i class='fas fa-undo'></i>
                    </button>
                    <button class='action-btn-issued damaged-btn-issued' title='Damaged'>
                        <i class='fas fa-exclamation-triangle'></i>
                    </button>
                    <button class='action-btn-issued unserviceable-btn-issued' title='Unserviceable'>
                        <i class='fas fa-times-circle'></i>
                    </button>
                </td>
            </tr>
        ";
        }

        return response()->json(['html' => $html]);
    }

    public function getDamageReports()
    {
        $damageReports = DamageReport::with('item')
            ->orderBy('reported_at', 'desc')
            ->get();

        $damageCounts = [
            'total' => $damageReports->count(),
            'reported' => $damageReports->whereNotNull('reported_at')->count(),
        ];

        return [
            'damageReports' => $damageReports,
            'damageCounts' => $damageCounts,
        ];
    }


    public function storeDamageReport(Request $request)
    {
        $serialNo = $request->input('serial_no');

        $item = Item::where('serial_no', $serialNo)->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found.'
            ]);
        }

        $item->status = 'Damaged';
        $item->save();

        $damage = DamageReport::create([
            'serial_no' => $item->serial_no,
            'reported_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Item marked as damaged and report created!',
            'damage' => [
                'serial_No' => $item->serial_no,
                'item_name' => $item->item_name,
                'reported_at' => $damage->reported_at->format('F d, Y')
            ]
        ]);
    }

    public function getDamageTableHtml()
    {
        $damageReports = DamageReport::with('item')->orderBy('reported_at', 'desc')->get();
        $damageCounts = [
            'total' => $damageReports->count(),
            'reported' => $damageReports->count(),
        ];

        return view('partials.damage_table', compact('damageReports', 'damageCounts'))->render();
    }

    public function storeMaintenance(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        $validator = \Validator::make($data, [
            'serial_no' => 'required|exists:items,serial_no',
            'issue_type' => 'required|string',
            'date_reported' => 'required|date',
            'repair_cost' => 'required|numeric',
            'expected_completion' => 'nullable|date',
            'remarks' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        try {
            $maintenanceId = DB::table('maintenance')->insertGetId([
                'serial_no' => $data['serial_no'],
                'issue_type' => $data['issue_type'],
                'date_reported' => $data['date_reported'],
                'repair_cost' => $data['repair_cost'],
                'expected_completion' => $data['expected_completion'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Maintenance record added successfully!',
                'maintenance_id' => $maintenanceId
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create maintenance record: ' . $e->getMessage(),
            ]);
        }
    }

    public function showMaintenance($id)
    {
        $record = DB::table('maintenance')
            ->join('items', 'maintenance.serial_no', '=', 'items.serial_no')
            ->select(
                'maintenance.maintenance_id as id',
                'maintenance.serial_no',
                'maintenance.issue_type',
                'maintenance.repair_cost',
                'maintenance.date_reported',
                'maintenance.expected_completion',
                'maintenance.remarks',
                'items.item_name'
            )
            ->where('maintenance.maintenance_id', $id)
            ->first();

        if (!$record) {
            return response()->json(['success' => false, 'message' => 'Record not found']);
        }

        // Format date for <input type="date">
        $record->date_reported = \Carbon\Carbon::parse($record->date_reported)->format('Y-m-d');
        if ($record->expected_completion) {
            $record->expected_completion = \Carbon\Carbon::parse($record->expected_completion)->format('Y-m-d');
        }

        return response()->json(['success' => true, 'record' => $record]);
    }



    public function updateMaintenance(Request $request, $id)
    {
        $data = $request->all();

        $validator = \Validator::make($data, [
            'serial_no' => 'required|exists:items,serial_no',
            'issue_type' => 'required|string',
            'date_reported' => 'required|date',
            'repair_cost' => 'required|numeric',
            'expected_completion' => 'nullable|date',
            'remarks' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
        }

        $updated = DB::table('maintenance')->where('maintenance_id', $id)->update([
            'serial_no' => $data['serial_no'],
            'issue_type' => $data['issue_type'],
            'date_reported' => $data['date_reported'],
            'repair_cost' => $data['repair_cost'],
            'expected_completion' => $data['expected_completion'] ?? null,
            'remarks' => $data['remarks'] ?? null,
        ]);

        if (!$updated) {
            return response()->json(['success' => false, 'message' => 'Failed to update record.']);
        }

        return response()->json(['success' => true, 'message' => 'Maintenance record updated!']);
    }

    public function destroyMaintenance($id)
    {
        $deleted = DB::table('maintenance')->where('id', $id)->delete();

        if (!$deleted)
            return response()->json(['success' => false, 'message' => 'Failed to delete record']);

        return response()->json(['success' => true, 'message' => 'Maintenance record deleted']);
    }

    public function getMaintenanceRecords()
    {
        $maintenanceRecords = DB::table('maintenance')
            ->join('items', 'maintenance.serial_no', '=', 'items.serial_no')
            ->select('maintenance.*', 'items.item_name', 'items.property_no')
            ->orderBy('maintenance.date_reported', 'desc')
            ->get();

        $maintenanceCounts = [
            'total' => $maintenanceRecords->count(),
            'pending' => $maintenanceRecords->where('status', 'Pending')->count(),
            'completed' => $maintenanceRecords->where('status', 'Completed')->count(),
            'upcoming' => $maintenanceRecords->where('expected_completion', '>', now())->count(),
        ];

        return [
            'records' => $maintenanceRecords,
            'counts' => $maintenanceCounts,
        ];
    }

    public function getLatestDamageReport($serialNo)
    {
        $damage = DamageReport::with('item')
            ->where('serial_no', $serialNo)
            ->orderBy('reported_at', 'desc')
            ->first();

        if (!$damage) {
            return response()->json([
                'success' => true,
                'damage' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'damage' => [
                'item_name' => $damage->item->item_name,
                'reported_at' => Carbon::parse($damage->reported_at)->format('Y-m-d'),
            ],
        ]);
    }

    public function report($serial_no)
    {
        // Fetch damage report
        $damage = DB::table('damagereports')
            ->where('serial_no', $serial_no)
            ->orderByDesc('reported_at')
            ->first();

        if (!$damage) {
            return response()->json(['error' => 'No damage report found.'], 404);
        }

        DB::table('maintenance')->insert([
            'serial_no' => $damage->serial_no,
            'item_name' => $damage->item_name,
            'issue_type' => $damage->damage_type,
            'date_reported' => now(),
            'repair_cost' => $damage->repair_cost ?? 0,
            'expected_completion' => $damage->expected_completion,
            'remarks' => $damage->description ?? 'Reported from damage report',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'message' => 'Item successfully reported to maintenance!'
        ]);
    }

    public function moveDamageToMaintenance($id)
    {
        $damage = DB::table('damagereports')->where('id', $id)->first();

        if (!$damage) {
            return response()->json(['error' => 'Damage report not found']);
        }

        DB::table('maintenance')->insert([
            'serial_no' => $damage->serial_no,
            'issue_type' => 'Reported Issue',
            'date_reported' => $damage->reported_at,
            'repair_cost' => 0,
            'expected_completion' => now()->addDays(3),
            'remarks' => 'Auto-transferred from damage report',
        ]);

        DB::table('damagereports')->where('id', $id)->delete();

        return response()->json(['message' => 'Damage moved to maintenance successfully!']);
    }

    public function makeAvailable($serial)
    {
        try {
            $item = DB::table('items')->where('serial_no', $serial)->first();

            if (!$item) {
                return response()->json(['error' => 'Item not found'], 404);
            }

            DB::table('items')->where('serial_no', $serial)->update([
                'status' => 'Available'
            ]);

            DB::table('maintenance')->where('serial_no', $serial)->update([
                'remarks' => 'Item is now available',
            ]);

            return response()->json(['message' => 'Item status updated to Available successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function getMaintenanceHistory($serial)
    {
        try {
            $maintenance = DB::table('maintenance')
                ->where('serial_no', $serial)
                ->orderBy('date_reported', 'desc')
                ->get();

            $damage = DB::table('damagereports')
                ->where('serial_no', $serial)
                ->orderBy('reported_at', 'desc')
                ->get();

            return response()->json([
                'maintenance' => $maintenance,
                'damage' => $damage,
            ]);
        } catch (\Exception $e) {
            \Log::error('Maintenance history error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch history'], 500);
        }
    }

    public function getDashboardSummary($type)
    {
        switch ($type) {

            case 'total':
                return response()->json([
                    'title' => 'Total Items & Equipment',
                    'summary' => 'Total items: <strong>' . DB::table('items')->count() . '</strong>',
                    'label' => 'Breakdown:',
                    'list' => '
                    <li>• Available: ' . DB::table('items')->where('status', 'Available')->count() . '</li>
                    <li>• Issued: ' . DB::table('items')->where('status', 'Issued')->count() . '</li>
                    <li>• For Repair: ' . DB::table('items')->whereIn('status', ['For Repair', 'Damaged'])->count() . '</li>
                ',
                    'footer' => '',
                ]);

            case 'issued':
                return response()->json([
                    'title' => 'Issued Items',
                    'summary' => 'Currently issued: <strong>' . DB::table('items')->where('status', 'Issued')->count() . '</strong>',
                    'label' => 'Issuance Status:',
                    'list' => '
                    <li>• Active Issuances</li>
                ',
                    'footer' => '',
                ]);

            default:
                return response()->json(['error' => 'Invalid type'], 404);
        }
    }


}
