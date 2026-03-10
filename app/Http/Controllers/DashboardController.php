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
        $maintenanceData = $this->getMaintenanceRecords();
        $maintenanceRecords = $maintenanceData['records'];
        $maintenanceCounts = $maintenanceData['counts'];

        $notifications = DB::table('notification_recipients as nr')
            ->join('notifications as n', 'n.notif_id', '=', 'nr.notif_id')
            ->where('nr.recipient_user_id', auth()->user()->user_id)
            ->whereNull('nr.deleted_at')
            ->orderByDesc('n.created_at')
            ->select(
                'nr.recipient_id',
                'nr.read_at',
                'n.notif_id',
                'n.title',
                'n.message',
                'n.type',
                'n.severity',
                'n.action_url',
                'n.entity_type',
                'n.entity_id',
                'n.created_at'
            )
            ->get();

        $unreadCount = DB::table('notification_recipients')
            ->where('recipient_user_id', auth()->user()->user_id)
            ->whereNull('read_at')
            ->whereNull('deleted_at')
            ->count();

        $damageData = $this->getDamageReports();
        $damageReports = $damageData['damageReports'];
        $damageCounts  = $damageData['damageCounts'];

        $items = Item::orderBy('item_name')->get();

        $totalIssuedItems = DB::table('issuedlog')->count();
        $totalItems = DB::table('items')->count();
        $availableItems = DB::table('items')->where('status', 'Available')->count();
        $issuedItems = DB::table('items')->where('status', 'Issued')->count();
        $forRepair = DB::table('items')->whereIn('status', ['For Repair', 'Maintenance'])->count();
        $missingItems = DB::table('items')->whereIn('status', ['Missing', 'Lost'])->count();
        $unserviceableItems = DB::table('items')->where('status', 'Unserviceable')->count();

        $lowStockThreshold = 10;
        $lowStock = DB::table('propertyinventory')
            ->where('quantity', '<', $lowStockThreshold)
            ->count();

        $itemsUsage = DB::table('issuedlog')
            ->select('serial_no', DB::raw('SUM(usage_hours) as total_usage_hours'))
            ->groupBy('serial_no');

        $inventory = DB::table('items as i')
            ->leftJoin('propertyinventory as pi', 'i.property_no', '=', 'pi.property_no')
            ->select(
                'i.serial_no',
                'i.item_name',
                'i.description',
                'i.specification',
                'i.source_of_fund',
                'i.classification',
                DB::raw('DATE(i.date_acquired) as date_acquired'),
                'i.status',
                'i.property_no',
                'i.expected_life_years',
                'i.created_at',
                'pi.unit_cost'
            )
            ->orderByDesc('i.item_id')
            ->get();

        // Latest issue_id per serial_no (subquery)
        $latest = DB::table('issuedlog')
            ->selectRaw('MAX(issue_id) as issue_id')
            ->groupBy('serial_no');

        /**
         * ✅ ISSUED ITEMS LIST (Latest per serial)
         * - Removed student join
         * - Uses i.borrower_name
         */
        $issuedItemsList = DB::table('issuedlog as i')
            ->joinSub($latest, 'latest', function ($join) {
                $join->on('i.issue_id', '=', 'latest.issue_id');
            })
            ->join('items as it', 'i.serial_no', '=', 'it.serial_no')
            ->leftJoin('users as u', 'i.issued_by', '=', 'u.user_id')
            ->where('it.status', '=', 'Issued')
            ->select(
                'i.issue_id',
                'i.serial_no',
                'i.issued_date',
                'i.return_date',
                'it.item_name as item',
                DB::raw("COALESCE(i.borrower_name, 'N/A') as issued_to"),
                DB::raw("COALESCE(CONCAT(u.first_name, ' ', u.last_name, ' (', UPPER(u.role), ')'), 'N/A') as issued_by")
            )
            ->orderByDesc('i.issued_date')
            ->get();

        /**
         * ✅ FORMS LIST
         * NOTE: This assumes formrecords column is borrower_name.
         */
        $issuedForms = DB::table('formrecords')
            ->select('form_id', 'form_type', 'reference_no', 'created_at', 'borrower_name', 'item_count', 'status')
            ->orderBy('created_at', 'desc')
            ->get();

        $formSummaryCounts = (object) [
            'total_forms' => $issuedForms->count(),
            'ics_forms' => $issuedForms->where('form_type', 'ICS')->count(),
            'par_forms' => $issuedForms->where('form_type', 'PAR')->count(),
            'active_forms' => $issuedForms->where('status', 'Active')->count(),
            'archived_forms' => $issuedForms->where('status', 'Archived')->count(),
        ];

        $issuedFrequency = DB::table('issuedlog')
            ->join('items', 'issuedlog.serial_no', '=', 'items.serial_no')
            ->select('items.item_name', DB::raw('COUNT(*) as total'))
            ->groupBy('items.item_name')
            ->get();

        $usageData = DB::table('items')
            ->select('item_name', DB::raw('SUM(usage_count) as total_usage'))
            ->groupBy('item_name')
            ->get();

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


        $overdueMaintenance = $maintenanceForecast
            ->filter(fn($item) => $item->next_maintenance_date && Carbon::parse($item->next_maintenance_date)->isPast());

        $upcomingMaintenance = $maintenanceForecast
            ->filter(fn($item) =>
                $item->next_maintenance_date &&
                Carbon::parse($item->next_maintenance_date)->between(now(), now()->addDays(7))
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
            ->filter(fn($item) =>
                ($item->maintenance_threshold_usage > 0 && $item->total_usage_hours >= $item->maintenance_threshold_usage)
                || ($item->next_maintenance_date && Carbon::parse($item->next_maintenance_date)->isPast())
            );

        return view('dashboard', compact(
            'totalItems',
            'availableItems',
            'issuedItems',
            'forRepair',
            'lowStock',
            'missingItems',
            'unserviceableItems',
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
            'unreadCount',
            'damageReports',
            'damageCounts',
            'items',
        
        ));
    }

    public function updateStatus(Request $request, $id)
    {
        DB::table('formrecords')
            ->where('form_id', $id)
            ->update(['status' => $request->status]);

        return back()->with('success', 'Form status updated successfully.');
    }

    public function getFormTable()
    {
        $issuedForms = DB::table('formrecords')
            ->select('form_id', 'form_type', 'reference_no', 'created_at', 'borrower_name', 'item_count', 'status')
            ->orderBy('created_at', 'desc')
            ->get();

        $html = '';

        foreach ($issuedForms as $form) {
            $statusClass = strtolower($form->status);
            $createdAt = Carbon::parse($form->created_at)->format('F d, Y');

            $html .= "
                <tr>
                    <td>{$form->form_type}</td>
                    <td>{$form->reference_no}</td>
                    <td>{$createdAt}</td>
                    <td>{$form->borrower_name}</td>
                    <td>{$form->item_count}</td>
                    <td><span class='status {$statusClass}'>{$form->status}</span></td>
                    <td><a href='#'>View</a></td>
                </tr>
            ";
        }

        return response()->json(['html' => $html]);
    }

    public function getInventoryTable(Request $request)
{
    $status = $request->query('status', 'All');

    $user = auth()->user();
    $canManageInventory = $user && in_array($user->role, ['Admin', 'Property Custodian']);

    $query = DB::table('items as i')
        ->leftJoin('propertyinventory as pi', 'i.property_no', '=', 'pi.property_no')
        ->select(
            'i.serial_no',
            'i.item_name',
            'i.description',
            'i.specification',
            'i.source_of_fund',
            'i.classification',
            DB::raw('DATE(i.date_acquired) as date_acquired'),
            'i.status',
            'i.property_no',
            'i.expected_life_years',
            'i.created_at',
            'pi.unit_cost'
        )
        ->orderByDesc('i.item_id');

    if ($status !== 'All') {
        if ($status === 'Missing') {
            $query->whereIn('i.status', ['Lost', 'Missing']);
        } else {
            $query->where('i.status', $status);
        }
    }

    $inventory = $query->get();

    $html = '';

    foreach ($inventory as $item) {
        if ($item->status === 'Available') {
            $statusClass = 'text-green';
        } elseif ($item->status === 'For Repair' || $item->status === 'Maintenance') {
            $statusClass = 'text-brown';
        } elseif ($item->status === 'Issued') {
            $statusClass = 'text-blue';
        } elseif (in_array($item->status, ['Unserviceable', 'Damaged', 'Lost', 'Missing'])) {
            $statusClass = 'text-red';
        } else {
            $statusClass = '';
        }

        $dateAcquired   = $item->date_acquired ? Carbon::parse($item->date_acquired)->format('F d, Y') : '-';
        $sourceOfFund   = $item->source_of_fund ?? '-';
        $classification = $item->classification ?? '-';
        $description    = $item->description ?? '-';
        $itemName       = $item->item_name ?? '-';
        $serialNo       = $item->serial_no ?? '-';
        $statusText     = $item->status ?? '-';

        $itemJson = htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8');

        $actionsHtml = '';
        if ($canManageInventory) {
            $actionsHtml = "
                <td class='action-buttons'>
                    <button
                        type='button'
                        class='inventory-edit-btn'
                        onclick='event.stopPropagation(); openInventoryEditModal(this)'
                    >
                        ✏️
                    </button>
                    <button
                        type='button'
                        class='inventory-delete-btn'
                        onclick='event.stopPropagation(); deleteItem(\"{$serialNo}\")'
                    >
                        🗑️
                    </button>
                </td>
            ";
        }

        $html .= "
            <tr class='inventory-row' data-item='{$itemJson}' style='cursor:pointer;'>
                <td>{$serialNo}</td>
                <td>{$itemName}</td>
                <td>{$description}</td>
                <td>{$sourceOfFund}</td>
                <td>{$classification}</td>
                <td>{$dateAcquired}</td>
                <td><span class='{$statusClass}'>{$statusText}</span></td>
                {$actionsHtml}
            </tr>
        ";
    }

    if ($html === '') {
        $colspan = $canManageInventory ? 8 : 7;
        $html = "<tr><td colspan='{$colspan}' style='text-align:center; padding:20px;'>No items found.</td></tr>";
    }

    return response()->json(['html' => $html]);
}

    public function getListOfAllItemsTable()
{
    $inventory = DB::table('items')
        ->select('item_name', 'serial_no', 'status')
        ->orderBy('item_name', 'asc')
        ->get();

    $html = '';

    foreach ($inventory as $item) {
        if ($item->status === 'Available') $statusClass = 'text-green';
        elseif ($item->status === 'For Repair') $statusClass = 'text-brown';
        elseif ($item->status === 'Issued') $statusClass = 'text-blue';
        elseif ($item->status === 'Damaged' || $item->status === 'Lost' || $item->status === 'Missing' || $item->status === 'Unserviceable') $statusClass = 'text-red';
        else $statusClass = '';

        $html .= "
            <tr>
                <td>{$item->serial_no}</td>
                <td>{$item->item_name}</td>
                <td><span class='{$statusClass}'>{$item->status}</span></td>
            </tr>
        ";
    }

    return response()->json(['html' => $html]);
}

    public function getListofAllAvailableItemsTable()
{
    $inventory = DB::table('items')
        ->select('item_name', 'serial_no')
        ->where('status', 'Available')
        ->orderBy('item_name', 'asc')
        ->get();

    $html = '';
    foreach ($inventory as $item) {
        $html .= "
            <tr>
                <td>{$item->serial_no}</td>
                <td>{$item->item_name}</td>
            </tr>
        ";
    }

    if ($html === '') {
        $html = "<tr><td colspan='2' style='text-align:center; padding:20px;'>No available items found.</td></tr>";
    }

    return response()->json(['html' => $html]);
}

    public function getListofIssuedItemsTable()
    {
        $issuedItemsList = DB::table('issuedlog as i')
            ->join('items as it', 'i.serial_no', '=', 'it.serial_no')
            ->leftJoin('users as u', 'i.issued_by', '=', 'u.user_id')
            ->where('it.status', '=', 'Issued')
            ->whereRaw('i.issue_id = (SELECT MAX(issue_id) FROM issuedlog WHERE serial_no = i.serial_no)')
            ->select(
                'i.issue_id',
                'i.serial_no',
                'i.issued_date',
                'i.return_date',
                'i.form_type',
                'i.reference_no',
                'it.item_name as item',
                DB::raw("COALESCE(i.borrower_name, 'N/A') as issued_to"),
                DB::raw("COALESCE(CONCAT(u.first_name, ' ', u.last_name), 'N/A') as issued_by")
            )
            ->orderByDesc('i.issued_date')
            ->get();

        $html = '';
        foreach ($issuedItemsList as $item) {
            $returnDate = $item->return_date ? Carbon::parse($item->return_date)->format('F d, Y') : '-';
            $html .= "
                <tr>
                    <td>{$item->serial_no}</td>
                    <td>{$item->item}</td>
                    <td>{$item->issued_to}</td>
                    <td>{$item->issued_by}</td>
                    <td>{$item->form_type}</td>
                    <td>{$item->reference_no}</td>
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
            ->where('it.status', '=', 'Issued')
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
                'f.borrower_name as issued_to',
                'f.issued_by as issued_by',
                'i.issued_date',
                'it.item_name as item'
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
                    <td>" . Carbon::parse($item->issued_date)->format('F d, Y') . "</td>
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
        $damageReports = DB::table('damagereports as d')
            ->join('items as i', 'i.serial_no', '=', 'd.serial_no')
            ->where('i.status', 'Damaged')   // ✅ THIS IS THE KEY
            ->select(
                'd.damage_id',
                'd.serial_no',
                'd.observation',
                'd.reported_at',
                'i.item_name'
            )
            ->orderByDesc('d.reported_at')
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

    // ---- the rest of your methods below are unchanged ----

    public function storeDamageReport(Request $request)
    {
        $serialNo = $request->input('serial_no');
        $item = Item::where('serial_no', $serialNo)->first();

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item not found.']);
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
            'observation' => 'required|string',
            'date_reported' => 'required|date',
            'repair_cost' => 'required|numeric',
            'expected_completion' => 'nullable|date',
            'remarks' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
        }

        try {
            $maintenanceId = DB::table('maintenance')->insertGetId([
                'serial_no' => $data['serial_no'],
                'observation' => $data['observation'],
                'date_reported' => $data['date_reported'],
                'repair_cost' => $data['repair_cost'],
                'expected_completion' => $data['expected_completion'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['success' => true, 'message' => 'Maintenance record added successfully!', 'maintenance_id' => $maintenanceId]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create maintenance record: ' . $e->getMessage()]);
        }
    }

    public function showMaintenance($id)
    {
        $record = DB::table('maintenance')
            ->join('items', 'maintenance.serial_no', '=', 'items.serial_no')
            ->select(
                'maintenance.maintenance_id as id',
                'maintenance.serial_no',
                'maintenance.observation',
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

        $record->date_reported = Carbon::parse($record->date_reported)->format('Y-m-d');
        if ($record->expected_completion) {
            $record->expected_completion = Carbon::parse($record->expected_completion)->format('Y-m-d');
        }

        return response()->json(['success' => true, 'record' => $record]);
    }

    public function updateMaintenance(Request $request, $id)
    {
        $data = $request->all();

        $validator = \Validator::make($data, [
            'serial_no' => 'required|exists:items,serial_no',
            'observation' => 'required|string',
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
            'observation' => $data['observation'],
            'date_reported' => $data['date_reported'],
            'repair_cost' => $data['repair_cost'],
            'expected_completion' => $data['expected_completion'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'updated_at' => now(),
        ]);

        if (!$updated) {
            return response()->json(['success' => false, 'message' => 'Failed to update record.']);
        }

        return response()->json(['success' => true, 'message' => 'Maintenance record updated!']);
    }

    public function destroyMaintenance($id)
    {
        // NOTE: your table uses maintenance_id, not id
        $deleted = DB::table('maintenance')->where('maintenance_id', $id)->delete();

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Failed to delete record']);
        }

        return response()->json(['success' => true, 'message' => 'Maintenance record deleted']);
    }

    public function getMaintenanceRecords()
{
    $maintenanceRecords = DB::table('maintenance')
        ->join('items', 'maintenance.serial_no', '=', 'items.serial_no')
        ->select(
            'maintenance.*',
            'items.item_name',
            'items.property_no',
            'items.status as item_status'
        )
        ->orderBy('maintenance.date_reported', 'desc')
        ->get();

    $maintenanceCounts = [
        'total' => $maintenanceRecords->count(),
        'for_repair' => $maintenanceRecords->where('item_status', 'For Repair')->count(),
        'available' => $maintenanceRecords->where('item_status', 'Available')->count(),
        'upcoming' => $maintenanceRecords->filter(function ($record) {
            return !empty($record->expected_completion)
                && \Carbon\Carbon::parse($record->expected_completion)->isFuture();
        })->count(),
    ];

    return ['records' => $maintenanceRecords, 'counts' => $maintenanceCounts];
}

public function getUnderMaintenanceItemsTable()
{
    $items = DB::table('items as i')
        ->leftJoin('damagereports as d', function ($join) {
            $join->on('i.serial_no', '=', 'd.serial_no')
                 ->whereRaw('d.damage_id = (
                     SELECT MAX(d2.damage_id)
                     FROM damagereports as d2
                     WHERE d2.serial_no = i.serial_no
                 )');
        })
        ->select(
            'i.serial_no',
            'i.item_name',
            'i.status',
            'd.observation',
            'd.borrower_name'
        )
        ->whereIn('i.status', ['Maintenance', 'For Repair'])
        ->orderBy('i.item_name', 'asc')
        ->get();

    $html = '';

    foreach ($items as $item) {
        $statusClass = $item->status === 'For Repair' ? 'text-brown' : 'text-blue';
        $observation = $item->observation ?? '-';
        $borrowerName = $item->borrower_name ?? '-';

        $html .= "
            <tr>
                <td>{$item->serial_no}</td>
                <td>{$item->item_name}</td>
                <td>{$observation}</td>
                <td>{$borrowerName}</td>
                <td><span class='{$statusClass}'>{$item->status}</span></td>
            </tr>
        ";
    }

    if ($html === '') {
        $html = "<tr><td colspan='5' style='text-align:center; padding:20px;'>No under maintenance items found.</td></tr>";
    }

    return response()->json(['html' => $html]);
}

    public function getLatestDamageReport($serialNo)
    {
        $damage = DamageReport::with('item')
            ->where('serial_no', $serialNo)
            ->orderBy('reported_at', 'desc')
            ->first();

        if (!$damage) {
            return response()->json(['success' => true, 'damage' => null]);
        }

        return response()->json([
            'success' => true,
            'damage' => [
                'item_name' => $damage->item->item_name,
                'reported_at' => Carbon::parse($damage->reported_at)->format('Y-m-d'),
            ],
        ]);
    }

    public function getUnserviceableItemsTable()
{
    $items = DB::table('unserviceablereports as ur')
        ->leftJoin('items as i', 'ur.serial_no', '=', 'i.serial_no')
        ->leftJoin('users as u', 'ur.reported_by', '=', 'u.user_id')
        ->select(
            'ur.serial_no',
            'i.item_name',
            'ur.reason',
            DB::raw("COALESCE(ur.borrower_name, 'N/A') as borrower_name"),
            DB::raw("COALESCE(CONCAT(u.first_name, ' ', u.last_name), 'Unknown') as reported_by"),
            'ur.reported_at'
        )
        ->orderByDesc('ur.reported_at')
        ->get();

    $html = '';

    foreach ($items as $item) {
        $reason = $item->reason ?? '-';
        $borrowerName = $item->borrower_name ?? '-';
        $reportedBy = $item->reported_by ?? '-';
        $reported = $item->reported_at
            ? Carbon::parse($item->reported_at)->format('F d, Y')
            : '-';

        $html .= "
        
            <tr>
                <td>{$item->serial_no}</td>
                <td>{$item->item_name}</td>
                <td>{$reason}</td>
                <td>{$borrowerName}</td>
                <td>{$reportedBy}</td>
                <td>{$reported}</td>
            </tr>
        ";
    }

    if ($html === '') {
        $html = "<tr><td colspan='6' style='text-align:center; padding:20px;'>No unserviceable items found.</td></tr>";
    }

    return response()->json(['html' => $html]);
}

    public function getMissingItemsTable()
{
    $missingItems = DB::table('missing as m')
        ->leftJoin('items as i', 'm.serial_number', '=', 'i.serial_no')
        ->leftJoin('issuedlog as il', function ($join) {
            $join->on('m.serial_number', '=', 'il.serial_no')
                 ->whereRaw('il.issue_id = (
                     SELECT MAX(issue_id)
                     FROM issuedlog
                     WHERE serial_no = m.serial_number
                 )');
        })
        ->select(
            'm.serial_number',
            'm.item_name',
            'i.classification',
            'm.borrower_name',
            'il.issued_date',
            'm.reported_at'
        )
        ->orderByDesc('m.reported_at')
        ->get();

    $html = '';

    foreach ($missingItems as $item) {
        $classification = $item->classification ?? '-';
        $borrowerName   = $item->borrower_name ?? '-';
        $issuedDate     = $item->issued_date ? Carbon::parse($item->issued_date)->format('F d, Y') : '-';
        $reportedAt     = $item->reported_at ? Carbon::parse($item->reported_at)->format('F d, Y') : '-';

        $html .= "
            <tr>
                <td>{$item->serial_number}</td>
                <td>{$item->item_name}</td>
                <td>{$classification}</td>
                <td>{$borrowerName}</td>
                <td>{$issuedDate}</td>
                <td>{$reportedAt}</td>
            </tr>
        ";
    }

    if ($html === '') {
        $html = "<tr><td colspan='6' style='text-align:center; padding:20px;'>No missing items found.</td></tr>";
    }

    return response()->json(['html' => $html]);
}

    public function report($serial_no)
    {
        $damage = DB::table('damagereports')
            ->where('serial_no', $serial_no)
            ->orderByDesc('reported_at')
            ->first();

        if (!$damage) {
            return response()->json(['error' => 'No damage report found.'], 404);
        }

        DB::table('maintenance')->insert([
            'serial_no' => $damage->serial_no,
            'observation' => $damage->damage_type,
            'date_reported' => now(),
            'repair_cost' => $damage->repair_cost ?? 0,
            'expected_completion' => $damage->expected_completion,
            'remarks' => $damage->description ?? 'Reported from damage report',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['message' => 'Item successfully reported to maintenance!']);
    }


    

    public function moveDamageToMaintenance(Request $request, $damage_id)
{
    // validate incoming fields (optional fields can be null)
    $request->validate([
        'repair_cost' => 'nullable|numeric|min:0',
        'expected_completion' => 'nullable|date',
        'remarks' => 'nullable|string|max:500',
    ]);

    // ✅ Get damage report using damage_id
    $damage = DB::table('damagereports')->where('damage_id', $damage_id)->first();

    if (!$damage) {
        return response()->json([
            'success' => false,
            'message' => 'Damage report not found.'
        ], 404);
    }

    // ✅ Prevent duplicate ticket for same damage report
    $existing = DB::table('maintenance')->where('damage_id', $damage_id)->first();
    if ($existing) {
        return response()->json([
            'success' => false,
            'message' => 'This damage report already has a maintenance ticket.'
        ], 409);
    }

    // ✅ Create ticket in maintenance table
    DB::table('maintenance')->insert([
        'serial_no' => $damage->serial_no,
        'observation' => $damage->observation,         // or "Damage: {$damage->observation}"
        'repair_cost' => $request->repair_cost ?? null,
        'date_reported' => $damage->reported_at ?? now(),
        'expected_completion' => $request->expected_completion ?? null,
        'remarks' => $request->remarks ?? null,
        'damage_id' => $damage_id,
    ]);

    // (Optional) Update item status
    DB::table('items')
        ->where('serial_no', $damage->serial_no)
        ->update(['status' => 'For Repair']);

    return response()->json([
        'success' => true,
        'message' => 'Maintenance ticket created successfully.'
    ]);
}

    public function makeAvailable($serial)
    {
        try {
            $item = DB::table('items')->where('serial_no', $serial)->first();

            if (!$item) {
                return response()->json(['error' => 'Item not found'], 404);
            }

            DB::table('items')->where('serial_no', $serial)->update([
                'status' => 'Available',
                'updated_at' => now(),
            ]);

            DB::table('maintenance')->where('serial_no', $serial)->update([
                'remarks' => 'Item is now available',
                'updated_at' => now(),
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

            return response()->json(['maintenance' => $maintenance, 'damage' => $damage]);
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
                    'list' => '<li>• Active Issuances</li>',
                    'footer' => '',
                ]);

            default:
                return response()->json(['error' => 'Invalid type'], 404);
        }
    }

    public function issuedTableHtml()
{
    $issuedItemsList = $this->getListofIssuedItemsTable()->getData(true)['html'] ?? '';
    // BUT your getListofIssuedItemsTable returns JSON, not html string directly.
    // Better: replicate query here:

    $latest = DB::table('issuedlog')
        ->selectRaw('MAX(issue_id) as issue_id')
        ->groupBy('serial_no');

    $items = DB::table('issuedlog as i')
        ->joinSub($latest, 'latest', function ($join) {
            $join->on('i.issue_id', '=', 'latest.issue_id');
        })
        ->join('items as it', 'i.serial_no', '=', 'it.serial_no')
        ->leftJoin('users as u', 'i.issued_by', '=', 'u.user_id')
        ->where('it.status', '=', 'Issued')
        ->select(
            'i.issue_id',
            'i.serial_no',
            'i.issued_date',
            'i.return_date',
            'it.item_name as item',
            DB::raw("COALESCE(i.borrower_name, 'N/A') as issued_to"),
            DB::raw("COALESCE(CONCAT(u.first_name, ' ', u.last_name, ' (', UPPER(u.role), ')'), 'N/A') as issued_by")
        )
        ->orderByDesc('i.issued_date')
        ->get();

    $html = '';
    foreach ($items as $item) {
        $returnDate = $item->return_date ? Carbon::parse($item->return_date)->format('F d, Y') : '-';
        $html .= "
            <tr>
              <td>{$item->serial_no}</td>
              <td>{$item->issued_to}</td>
              <td>{$item->issued_by}</td>
              <td>" . Carbon::parse($item->issued_date)->format('F d, Y') . "</td>
              <td>{$returnDate}</td>
              <td>{$item->item}</td>
              <td class='action-buttons-issued'>
                <button class='action-btn-issued return-btn-issued' title='Return' data-id='{$item->issue_id}'>
                  <i class='fas fa-undo'></i>
                </button>
                <button class='action-btn-issued damaged-btn-issued' data-id='{$item->serial_no}' title='Damaged'>
                  <i class='fas fa-exclamation-triangle'></i>
                </button>
                <button class='action-btn-issued unserviceable-btn-issued' title='Unserviceable'>
                  <i class='fas fa-times-circle'></i>
                </button>
              </td>
            </tr>
        ";
    }

    return response($html, 200)->header('Content-Type', 'text/html');
}

public function getLowStockItems()
{
    $lowStockThreshold = 10;

    $items = DB::table('propertyinventory as pi')
        ->select(
            'pi.property_no',
            'pi.item_name',
            'pi.classification',
            'pi.quantity'
        )
        ->where('pi.quantity', '<', $lowStockThreshold)
        ->orderBy('pi.quantity', 'asc')
        ->orderBy('pi.item_name', 'asc')
        ->get();

    $html = '';

    foreach ($items as $item) {
        $propertyNo = $item->property_no ?? '-';
        $itemName = $item->item_name ?? '-';
        $classification = $item->classification ?? '-';
        $quantity = $item->quantity ?? 0;

        $html .= "
            <tr>
                <td>{$propertyNo}</td>
                <td>{$itemName}</td>
                <td>{$classification}</td>
                <td>{$quantity}</td>
            </tr>
        ";
    }

    if ($html === '') {
        $html = "<tr><td colspan='4' style='text-align:center; padding:20px;'>No low stock items found.</td></tr>";
    }

    return response()->json(['html' => $html]);
}

public function maintenanceTableHtml()
{
    $records = DB::table('maintenance')
        ->leftJoin('items', 'maintenance.serial_no', '=', 'items.serial_no')
        ->select(
            'maintenance.maintenance_id',
            'maintenance.serial_no',
            'items.item_name',
            'maintenance.observation',
            'maintenance.date_reported',
            'maintenance.repair_cost',
            'maintenance.expected_completion',
            'maintenance.remarks'
        )
        ->orderBy('maintenance.date_reported', 'desc')
        ->get();

    $html = '';

    foreach ($records as $record) {
        $dateReported = $record->date_reported
            ? \Carbon\Carbon::parse($record->date_reported)->format('F d, Y')
            : '-';

        $expectedCompletion = $record->expected_completion
            ? \Carbon\Carbon::parse($record->expected_completion)->format('F d, Y')
            : '-';

        $repairCost = $record->repair_cost !== null
            ? '₱' . number_format($record->repair_cost, 2)
            : '-';

        $itemName = $record->item_name ?? '-';
        $issueType = $record->issue_type ?? '-';
        $remarks = $record->remarks ?? '-';

        $html .= "
            <tr>
                <td class='serial-cell' data-serial='{$record->serial_no}'>
                    {$record->serial_no}
                </td>
                <td>{$itemName}</td>
                <td>{$issueType}</td>
                <td>{$dateReported}</td>
                <td>{$repairCost}</td>
                <td>{$expectedCompletion}</td>
                <td>{$remarks}</td>
                <td>
                    <div class='btn-with-icon'>
                        <button class='edit-btn' data-id='{$record->maintenance_id}' data-serial='{$record->serial_no}'>
                            <i class='fa fa-pen-to-square'></i>
                        </button>
                    </div>
                    <div class='right-side'>
                        <div class='btn-with-icon'>
                            <button class='make-available-btn' data-serial='{$record->serial_no}' title='Make Available'>
                                <i class='fa-solid fa-check'></i>
                            </button>
                        </div>
                    </div>
                </td>
            </tr>
        ";
    }

    if ($html === '') {
        $html = "<tr><td colspan='8' style='text-align:center;'>No maintenance records found.</td></tr>";
    }

    return response()->json(['html' => $html]);
}

public function damageTableHtml()
{
    // ✅ IMPORTANT: show only not yet ticketed AND item still Damaged
    $damageReports = DB::table('damagereports as d')
        ->join('items as i', 'i.serial_no', '=', 'd.serial_no')
        ->where('i.status', 'Damaged')
        ->where(function($q){
            $q->whereNull('d.is_ticketed')->orWhere('d.is_ticketed', 0);
        })
        ->select('d.damage_id', 'd.serial_no', 'd.observation', 'd.reported_at', 'i.item_name')
        ->orderByDesc('d.reported_at')
        ->get();

    $html = '';
    foreach ($damageReports as $report) {
        $date = $report->reported_at ? Carbon::parse($report->reported_at)->format('F d, Y') : '-';
        $obs = $report->observation ?? '-';
        $name = $report->item_name ?? '-';

        $html .= "
          <tr>
            <td>{$report->serial_no}</td>
            <td>{$name}</td>
            <td>{$obs}</td>
            <td>{$date}</td>
            <td>
              <div class='button-container'>
                <button class='action-btn-issued maintenance-btn-issued'
                  data-damage-id='{$report->damage_id}'
                  data-serial='{$report->serial_no}'
                  title='Maintenance'>
                  <i class='fas fa-exclamation-triangle'></i>
                </button>
              </div>
            </td>
          </tr>
        ";
    }

    if ($html === '') {
        $html = "<tr><td colspan='5' style='text-align:center; padding:20px;'>No damage reports found.</td></tr>";
    }

    return response($html, 200)->header('Content-Type', 'text/html');
}
}