<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    // session key for "memory"
    private const CTX_LAST_SERIAL = 'chatbot.last_serial';
    private const CTX_LAST_LIST_SERIALS = 'chatbot.last_list_serials';

    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $raw = trim((string) $request->message);
        $msg = strtolower($raw);

        // 1) If message contains a serial, store it into session context (FAST)
        $serial = $this->extractSerial($raw);
        if ($serial) {
            $this->setLastSerial($request, $serial);
        }

        // 2) Resolve intent
        $intent = $this->detectIntent($msg);

        // 3) Route intent
        switch ($intent) {
            case 'GREET':
                return $this->greet();

            case 'INTRO':
                return $this->intro();

            case 'HELP':
                return $this->help();

            case 'OUT_OF_SCOPE':
                return $this->outOfScope();
            case 'FAQ_SERIAL_TRACKING_IMPORTANCE':
                return $this->faqSerialTrackingImportance();

            case 'FAQ_VALIDATE_SERIAL_BEFORE_INSERT':
                return $this->faqValidateSerialBeforeInsert();

            case 'FAQ_TRACK_MAINTENANCE_REPAIR':
                return $this->faqTrackMaintenanceRepair();

            case 'FAQ_QR_BARCODE':
                return $this->faqQrBarcode();

            case 'FAQ_ITEM_APPROVAL':
                return $this->faqItemApproval();

            case 'FAQ_CHECK_STATUS':
                return $this->faqCheckItemStatus();

            case 'LOW_STOCK':
                return $this->lowStock();

            case 'LIST_AVAILABLE':
                return $this->listAvailable();

            case 'LIST_ALL':
                return $this->listAll();

            case 'TOTAL_ITEMS':
                return $this->totalItems();

            case 'ITEM_COUNT':
                return $this->itemCount($msg);

            case 'LIST_DAMAGED':
                return $this->listDamaged($request);

            case 'DAMAGED_WITH_BORROWER':
                return $this->damagedWithBorrower();

            case 'LIST_UNSERVICEABLE':
                return $this->listUnserviceable();

            case 'UNSERVICEABLE_WITH_BORROWER':
                return $this->unserviceableWithBorrower();

            case 'LIST_MISSING':
                return $this->listMissing($request);

            case 'MISSING_WITH_BORROWER':
                return $this->missingWithBorrower();    

            case 'ITEM_STATUS':
                return $this->itemStatus($request, $raw);

            case 'WHO_BORROWED':
                return $this->whoBorrowed($request, $raw);

            case 'WHEN_ISSUED':
                return $this->whenIssued($request, $raw);

            case 'WHO_DAMAGED':
                return $this->whoDamaged($request, $raw);

            default:
                return $this->fallbackAI($request);
        }
    }

    /* =========================
     | INTENT DETECTION
     ========================= */
    private function detectIntent(string $msg): string
    {

    // GREET / INTRO / HELP
        if (preg_match('/\b(hi|hello|hey|good\s*(morning|afternoon|evening))\b/i', $msg)) {
            return 'GREET';
        }

        if (preg_match('/\b(who\s+are\s+you|what\s+are\s+you|introduce\s+yourself)\b/i', $msg)) {
            return 'INTRO';
        }

        if (preg_match('/\b(what\s+can\s+you\s+do|help|commands|what\s+can\s+i\s+ask|how\s+to\s+use)\b/i', $msg)) {
            return 'HELP';
        }
        // FAQ
        if (preg_match('/(why|what\s+is).*(serial|serial\s+number).*tracking.*(important|purpose)|importance.*serial/i', $msg)) {
            return 'FAQ_SERIAL_TRACKING_IMPORTANCE';
        }
        if (preg_match('/(why|what\s+is).*(validate|validation).*(serial|serial\s+number).*(before|insert|inserting|add|adding).*(database|inventory)|validate.*serial.*database/i', $msg)) {
            return 'FAQ_VALIDATE_SERIAL_BEFORE_INSERT';
        }
        if (preg_match('/how.*track.*(maintenance|repair)|under\s+(maintenance|repair).*track|track.*(unserviceable|repair|maintenance)/i', $msg)) {
            return 'FAQ_TRACK_MAINTENANCE_REPAIR';
        }
        if (preg_match('/(difference|what\s+is).*(qr|qr\s+code).*(barcode)|qr\s+vs\s+barcode|barcode\s+vs\s+qr/i', $msg)) {
            return 'FAQ_QR_BARCODE';
        }
        if (preg_match('/item\s+approval\s+request|approve\s+item|approval\s+request.*item/i', $msg)) {
            return 'FAQ_ITEM_APPROVAL';
        }
        if (preg_match('/how.*check.*item.*status|check.*status.*item/i', $msg)) {
            return 'FAQ_CHECK_STATUS';
        }

        // LISTING
        if (preg_match('/low\s+stock|low\s+inventory|nearly\s+out|out\s+of\s+stock/i', $msg)) {
            return 'LOW_STOCK';
        }
        if (preg_match('/(who|list|show).*(borrower|borrowed).*(damaged)|damaged.*(borrower|borrowed|who)/i', $msg)) {
            return 'DAMAGED_WITH_BORROWER';
        }
        if (preg_match('/list\s+.*damaged|damaged\s+items/i', $msg)) {
            return 'LIST_DAMAGED';
        }
        if (preg_match('/(show|list|who|last).*(borrower|borrowed).*(unserviceable)|unserviceable.*(show|list|who|last).*(borrower|borrowed)|unserviceable.*with\s+borrower/i', $msg)) {
    return 'UNSERVICEABLE_WITH_BORROWER';
        }
        if (preg_match('/list\s+.*unserviceable|show\s+.*unserviceable|unserviceable\s+items/i', $msg)) {
            return 'LIST_UNSERVICEABLE';
        }
        if (preg_match('/(show|list|who|last).*(borrower|borrowed|has).*(missing)|missing.*(show|list|who|last).*(borrower|borrowed|has)|missing.*with\s+borrower/i', $msg)) {
            return 'MISSING_WITH_BORROWER';
        }
        if (preg_match('/list\s+.*missing|show\s+.*missing|missing\s+items/i', $msg)) {
            return 'LIST_MISSING';
        }
        if (preg_match('/list\s+.*available/i', $msg)) {
            return 'LIST_AVAILABLE';
        }
        if (preg_match('/list\s+.*all\s+items|show\s+.*all\s+items/i', $msg)) {
            return 'LIST_ALL';
        }
        if (preg_match('/how\s+many\s+items/i', $msg)) {
            return 'TOTAL_ITEMS';
        }
        if (preg_match('/how\s+many\s+[a-z\s]+/i', $msg)) {
            return 'ITEM_COUNT';
        }

        // SERIAL-BASED (with or without explicit SN)
        // If message contains SN -> direct handlers
        if (preg_match('/\b(who\s+borrowed|who\s+issued|who\s+is\s+using|who\s+has)\s+(it|them)?\b/i', $msg)) {
            return 'WHO_BORROWED';
        }
        if (preg_match('/when.*(sn[\-\s]?\d+).*issued|when.*issued.*(sn[\-\s]?\d+)/i', $msg)) {
            return 'WHEN_ISSUED';
        }
        if (preg_match('/who\s+(damaged|reported\s+damage).*sn[\-\s]?\d+/i', $msg)) {
            return 'WHO_DAMAGED';
        }
        if (preg_match('/sn[\-\s]?\d+/i', $msg)) {
            return 'ITEM_STATUS';
        }

        // FOLLOW-UP MEMORY INTENTS (NO SN in message)
        // "who borrowed it", "who borrowed?", "who borrowed it?" -> WHO_BORROWED
        if (preg_match('/\bwho\s+borrowed\b|\bwho\s+issued\b|\bwho\s+is\s+using\b|\bwho\s+has\s+it\b/i', $msg)) {
            return 'WHO_BORROWED';
        }
        // "when was it issued" -> WHEN_ISSUED
        if (preg_match('/\bwhen\b.*\bissued\b/i', $msg)) {
            return 'WHEN_ISSUED';
        }
        // "what is the status" -> ITEM_STATUS
        if (preg_match('/\bstatus\b/i', $msg)) {
            return 'ITEM_STATUS';
        }
        // "who damaged it" -> WHO_DAMAGED
        if (preg_match('/\bwho\b.*\b(damaged|reported)\b/i', $msg)) {
            return 'WHO_DAMAGED';
        }

        // OUT-OF-SCOPE (not related to inventory)
        if ($this->isOutOfScope($msg)) {
            return 'OUT_OF_SCOPE';
        }

        return 'FALLBACK';
    }

    /* =========================
     | SESSION MEMORY (FAST)
     ========================= */
     private function hasExplicitSerial(string $text): bool
            {
                return preg_match('/\bsn[\-\s]?\d+\b/i', $text) === 1;
            }
     private function getLastListSerials(Request $request): array
        {
            $v = $request->session()->get(self::CTX_LAST_LIST_SERIALS, []);
            return is_array($v) ? $v : [];
        }

        private function setLastListSerials(Request $request, array $serials): void
        {
            $request->session()->put(self::CTX_LAST_LIST_SERIALS, array_values($serials));
        }

        private function clearLastListSerials(Request $request): void
        {
            $request->session()->forget(self::CTX_LAST_LIST_SERIALS);
        }

    private function getLastSerial(Request $request): ?string
    {
        $v = $request->session()->get(self::CTX_LAST_SERIAL);
        return is_string($v) && $v !== '' ? $v : null;
    }

    private function setLastSerial(Request $request, string $serial): void
    {
        $request->session()->put(self::CTX_LAST_SERIAL, $serial);
    }

    private function extractSerial(string $text): ?string
    {
        // supports SN0006, SN-0006, sn 0006
        if (!preg_match('/\b(sn)[\-\s]?(\d+)\b/i', $text, $m)) {
            return null;
        }
        $num = str_pad($m[2], 4, '0', STR_PAD_LEFT);
        return 'SN' . $num;
    }

    private function resolveSerialFromMessageOrContext(Request $request, string $rawMessage): ?string
    {
        $serial = $this->extractSerial($rawMessage);
        if ($serial) return $serial;

        // If user asked a follow-up without SN, use session memory
        return $this->getLastSerial($request);
    }

    private function issuedByName($issuedBy): string
    {
        if (is_numeric($issuedBy)) {
            $u = DB::table('users')->where('user_id', $issuedBy)->first();
            if ($u) {
                return trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
            }
        }
        return (string) $issuedBy;
    }

    /* =========================
     | HANDLERS
     ========================= */
    private function lowStock()
    {
        $items = DB::table('propertyinventory')
            ->select('item_name', DB::raw('SUM(quantity) as total'))
            ->where('status', 'Available')
            ->groupBy('item_name')
            ->having('total', '<=', 5)
            ->get();

        if ($items->isEmpty()) {
            return response()->json(['reply' => 'Good news! There are no items currently low on stock.']);
        }

        $reply = "<strong>Low Stock Items:</strong><br><br>";
        foreach ($items as $item) {
            $reply .= "{$item->item_name}: {$item->total} left<br>";
        }

        return response()->json(['reply' => $reply]);
    }

    private function listAvailable()
    {
        $items = DB::table('items')
            ->where('status', 'Available')
            ->select('item_name', 'serial_no')
            ->orderBy('item_name')
            ->orderBy('serial_no')
            ->get();

        if ($items->isEmpty()) {
            return response()->json(['reply' => 'There are no available items in the inventory.']);
        }

        $reply = "<strong>Available Items:</strong><br><br>";
        $currentItem = null;

        foreach ($items as $item) {
            if ($currentItem !== $item->item_name) {
                $currentItem = $item->item_name;
                $reply .= "<br><strong>{$currentItem}</strong><br>";
            }
            $reply .= "• {$item->serial_no}<br>";
        }

        return response()->json(['reply' => $reply]);
    }

    private function listUnserviceable()
    {
        $items = DB::table('items')
            ->where('status', 'Unserviceable')
            ->select('item_name', 'serial_no')
            ->orderBy('item_name')
            ->orderBy('serial_no')
            ->get();

        if ($items->isEmpty()) {
            return response()->json(['reply' => 'There are currently no unserviceable items.']);
        }

        $reply = "<strong>Unserviceable Items:</strong><br><br>";
        $currentItem = null;

        foreach ($items as $item) {
            if ($currentItem !== $item->item_name) {
                $currentItem = $item->item_name;
                $reply .= "<br><strong>{$currentItem}</strong><br>";
            }
            $reply .= "• {$item->serial_no}<br>";
        }

        return response()->json(['reply' => $reply]);
    }

    private function unserviceableWithBorrower()
{
    $items = DB::table('items')
        ->where('status', 'Unserviceable')
        ->select('item_name', 'serial_no')
        ->orderBy('item_name')
        ->orderBy('serial_no')
        ->get();

    if ($items->isEmpty()) {
        return response()->json(['reply' => 'There are currently no unserviceable items.']);
    }

    $reply = "<strong>Unserviceable Items and Last Borrower:</strong><br><br>";

    foreach ($items as $it) {
        $issued = DB::table('issuedlog as i')
            ->leftJoin('formrecords as f', 'i.reference_no', '=', 'f.reference_no')
            ->where('i.serial_no', $it->serial_no)
            ->orderByDesc('i.issue_id')
            ->select(
                'i.issue_id',
                'i.issued_by',
                'i.issued_date',
                'i.return_date',
                'i.actual_return_date',
                DB::raw("COALESCE(NULLIF(i.borrower_name,''), NULLIF(f.borrower_name,'')) as borrower_name")
            )
            ->first();

        if ($issued) {
            $borrower = trim((string)($issued->borrower_name ?? '')) ?: 'N/A';
            $issuedByName = $this->issuedByName($issued->issued_by);
            $issuedDate = $issued->issued_date ? date('F d, Y', strtotime($issued->issued_date)) : 'N/A';
            $returnDate = $issued->return_date ? date('F d, Y', strtotime($issued->return_date)) : 'N/A';
            $actualReturn = $issued->actual_return_date ? date('F d, Y', strtotime($issued->actual_return_date)) : null;
            $returnLine = $actualReturn ? "Returned: {$actualReturn}" : "Expected Return: {$returnDate}";

            $reply .= "<strong>{$it->item_name}</strong><br>"
                . "Serial No: {$it->serial_no}<br>"
                . "Last Borrower: <strong>{$borrower}</strong><br>"
                . "Issued By: {$issuedByName}<br>"
                . "Date Issued: {$issuedDate}<br>"
                . "{$returnLine}<br><br>";
        } else {
            $reply .= "<strong>{$it->item_name}</strong><br>"
                . "Serial No: {$it->serial_no}<br>"
                . "No issuance record found.<br><br>";
        }
    }

    return response()->json(['reply' => $reply]);
}

    private function listDamaged(Request $request)
{
    $items = DB::table('items')
        ->where('status', 'Damaged')
        ->select('item_name', 'serial_no')
        ->orderBy('item_name')
        ->orderBy('serial_no')
        ->get();

    if ($items->isEmpty()) {
        $this->clearLastListSerials($request);
        return response()->json(['reply' => 'There are currently no damaged items.']);
    }

    $serials = $items->pluck('serial_no')->filter()->values()->toArray();
    $this->setLastListSerials($request, $serials);

    // keep this only if you still want single-item fallback
    if (count($serials) === 1) {
        $this->setLastSerial($request, $serials[0]);
    }

    $reply = "<strong>Damaged Items:</strong><br><br>";
    $currentItem = null;

    foreach ($items as $item) {
        if ($currentItem !== $item->item_name) {
            $currentItem = $item->item_name;
            $reply .= "<br><strong>{$currentItem}</strong><br>";
        }
        $reply .= "• {$item->serial_no}<br>";
    }

    return response()->json(['reply' => $reply]);
}

    private function damagedWithBorrower()
{
    $damagedItems = DB::table('items')
        ->where('status', 'Damaged')
        ->select('item_name', 'serial_no')
        ->orderBy('item_name')
        ->orderBy('serial_no')
        ->get();

    if ($damagedItems->isEmpty()) {
        return response()->json(['reply' => 'There are currently no damaged items.']);
    }

    $reply = "<strong>Damaged Items and Last Issuance:</strong><br><br>";

    foreach ($damagedItems as $it) {
        $issued = DB::table('issuedlog')
            ->where('serial_no', $it->serial_no)
            ->orderByDesc('issue_id')
            ->first();

        $damage = DB::table('damagereports')
            ->where('serial_no', $it->serial_no)
            ->orderByDesc('reported_at')
            ->select('observation', 'borrower_name')
            ->first();

        $observation = trim((string)($damage->observation ?? ''));
        $damageBorrower = trim((string)($damage->borrower_name ?? ''));

        if ($issued) {
            $issuedByName = $this->issuedByName($issued->issued_by);
            $borrower = trim((string)($issued->borrower_name ?? '')) ?: 'N/A';
            $issuedDate = $issued->issued_date ? date('F d, Y', strtotime($issued->issued_date)) : 'N/A';

            $reply .= "<strong>{$it->item_name}</strong> ({$it->serial_no})<br>"
                . "Borrower: {$borrower}<br>";

            if ($observation !== '' && preg_match('/upon\s+arrival|upon\s+arival/i', $observation)) {
                $nameToShow = $damageBorrower !== '' ? $damageBorrower : $borrower;
                $reply .= "Borrower Name: {$nameToShow} - Damaged upon arrival<br>";
            }

            $reply .= "Issued By: {$issuedByName}<br>"
                . "Date Issued: {$issuedDate}<br>";

            if ($observation !== '') {
                $reply .= "Observation: {$observation}<br>";
            }

            $reply .= "<br>";
        } else {
            $borrower = $damageBorrower !== '' ? $damageBorrower : 'N/A';

            $reply .= "<strong>{$it->item_name}</strong> ({$it->serial_no})<br>";

            if ($borrower !== 'N/A') {
                $reply .= "Borrower: {$borrower}<br>";
            }

            if ($observation !== '' && preg_match('/upon\s+arrival|upon\s+arival/i', $observation)) {
                $reply .= "Borrower Name: {$borrower} - Damaged upon arrival<br>";
            }

            if ($observation !== '') {
                $reply .= "Observation: {$observation}<br>";
            }

            $reply .= "No issued record found.<br><br>";
        }
    }

    return response()->json(['reply' => $reply]);
}

    private function listAll()
    {
        $items = DB::table('propertyinventory')
            ->select('item_name', DB::raw('SUM(quantity) as total'))
            ->groupBy('item_name')
            ->orderBy('item_name')
            ->get();

        if ($items->isEmpty()) {
            return response()->json(['reply' => 'There are no items in the inventory.']);
        }

        $reply = "<strong>All Inventory Items:</strong><br><br>";
        foreach ($items as $item) {
            $reply .= "{$item->item_name}: {$item->total}<br>";
        }

        return response()->json(['reply' => $reply]);
    }

    private function totalItems()
    {
        $total = DB::table('propertyinventory')->sum('quantity');

        return response()->json([
            'reply' => $total
                ? "There are a total of {$total} items in the inventory."
                : "There are currently no items in the inventory."
        ]);
    }

    private function itemCount(string $msg)
{
    // Capture item name but stop before common trailing phrases
    if (!preg_match('/\bhow\s+many\s+(.+?)(?:\s+are\s+there|\s+do\s+we\s+have|\s+in\s+the\s+inventory|\?|$)/i', $msg, $m)) {
        if (!preg_match('/\b(number\s+of|stock\s+of|quantity\s+of)\s+(.+?)(?:\s+are\s+there|\s+do\s+we\s+have|\s+in\s+the\s+inventory|\?|$)/i', $msg, $m2)) {
            return response()->json(['reply' => "Please specify the item name (example: 'How many printers?')."]);
        }
        $rawItem = $m2[2] ?? '';
    } else {
        $rawItem = $m[1] ?? '';
    }

    $itemName = strtolower(trim($rawItem));
    $itemName = preg_replace('/\s+/', ' ', $itemName);

    // Basic singular handling (printers -> printer, laptops -> laptop)
    if (strlen($itemName) > 3 && str_ends_with($itemName, 's')) {
        $itemName = rtrim($itemName, 's');
    }

    if ($itemName === '') {
        return response()->json(['reply' => "Please specify the item name (example: 'How many printers?')."]);
    }

    $row = DB::table('propertyinventory')
        ->select(DB::raw('SUM(quantity) as total'))
        ->whereRaw('LOWER(item_name) LIKE ?', ['%' . $itemName . '%'])
        ->first();

    $total = (int) ($row->total ?? 0);

    if ($total <= 0) {
        return response()->json(['reply' => "I couldn’t find any {$itemName} in the inventory."]);
    }

    return response()->json([
        'reply' => "There are {$total} {$itemName} in stock."
    ]);
}

    private function itemStatus(Request $request, string $rawMessage)
{
    $serial = $this->resolveSerialFromMessageOrContext($request, $rawMessage);

    if (!$serial) {
        return response()->json(['reply' => "Please include a serial like SN0001."]);
    }

    $item = DB::table('items')
        ->where('serial_no', $serial)
        ->select(
            'item_id',
            'item_name',
            'description',
            'specification',
            'classification',
            'source_of_fund',
            'date_acquired',
            'property_no',
            'serial_no',
            'stock',
            'usage_count',
            'remarks',
            'department',
            'status',
            'last_maintenance_date',
            'maintenance_interval_days',
            'maintenance_threshold_usage',
            'expected_life_years',
            'total_usage_hours'
        )
        ->first();

    if (!$item) {
        return response()->json(['reply' => "No item found with serial {$serial}."]);
    }

    $latestDamage = DB::table('damagereports')
        ->where('serial_no', $serial)
        ->orderByDesc('reported_at')
        ->select(
            'damage_id',
            'serial_no',
            'observation',
            'borrower_name',
            'reported_by',
            'is_ticketed',
            'ticketed_at',
            'reported_at'
        )
        ->first();

    $damageCount = DB::table('damagereports')
        ->where('serial_no', $serial)
        ->count();

    $latestUnserviceable = DB::table('unserviceablereports')
        ->where('serial_no', $serial)
        ->orderByDesc('reported_at')
        ->select(
            'unserviceable_id',
            'serial_no',
            'reason',
            'borrower_name',
            'reported_by',
            'reported_at'
        )
        ->first();

    $unserviceableCount = DB::table('unserviceablereports')
        ->where('serial_no', $serial)
        ->count();

    $assessment = $this->buildItemAssessment($item, $damageCount, $unserviceableCount);
    $usageStats = $this->getUsageAnalyticsData($serial);
    $riskLevel = $this->getItemRiskLevel($assessment, $usageStats);

    $descriptive = $this->buildDescriptiveAnalytics($item, $assessment, $usageStats, $damageCount, $unserviceableCount);
    $predictive = $this->buildPredictiveAnalytics($item, $assessment, $usageStats, $damageCount, $unserviceableCount);
    $prescriptive = $this->buildPrescriptiveAnalytics($item, $assessment, $usageStats, $damageCount, $unserviceableCount);
    $maintenanceRecommendations = $this->getItemSpecificRecommendations($item);

    $reply = "<strong>Item Status Summary</strong><br><br>"
        . "<strong>Item Name:</strong> {$item->item_name}<br>"
        . "<strong>Serial No:</strong> {$item->serial_no}<br>"
        . "<strong>Property No:</strong> " . ($item->property_no ?: 'N/A') . "<br>"
        . "<strong>Status:</strong> {$item->status}<br>"
        . "<strong>Department:</strong> " . ($item->department ?: 'N/A') . "<br>"
        . "<strong>Classification:</strong> " . ($item->classification ?: 'N/A') . "<br>"
        . "<strong>Risk Level:</strong> {$riskLevel}<br><br>"

        . "<strong>Operational Details</strong><br>"
        . "<strong>Date Acquired:</strong> " . (!empty($item->date_acquired) ? date('F d, Y', strtotime($item->date_acquired)) : 'N/A') . "<br>"
        . "<strong>Years in Service:</strong> {$assessment['years_used_text']}<br>"
        . "<strong>Expected Life:</strong> {$assessment['expected_life_text']}<br>"
        . "<strong>Total Usage Hours:</strong> {$assessment['usage_hours_text']}<br>"

        . "<strong>Descriptive Analytics</strong><br>"
        . $descriptive . "<br><br>"

        . "<strong>Predictive Analytics</strong><br>"
        . $predictive . "<br><br>"

        . "<strong>Prescriptive Analytics</strong><br>"
        . $prescriptive . "<br><br>"

        . "<strong>Recommended Maintenance Actions</strong><br>"
        . $maintenanceRecommendations;

    if ($damageCount > 0) {
        $reply .= "<br><br><strong>Damage Record Summary</strong><br>"
            . "<strong>Total Damage Reports:</strong> {$damageCount}<br>";

        if ($latestDamage) {
            $reply .= "<strong>Latest Observation:</strong> " . (trim((string)$latestDamage->observation) !== '' ? e($latestDamage->observation) : 'N/A') . "<br>"
                . "<strong>Borrower Name:</strong> " . (trim((string)$latestDamage->borrower_name) !== '' ? e($latestDamage->borrower_name) : 'N/A') . "<br>"
                . "<strong>Reported At:</strong> " . (!empty($latestDamage->reported_at) ? date('F d, Y', strtotime($latestDamage->reported_at)) : 'N/A') . "<br>"
                . "<strong>Ticketed:</strong> " . ((int)($latestDamage->is_ticketed ?? 0) === 1 ? 'Yes' : 'No');

            if (!empty($latestDamage->ticketed_at)) {
                $reply .= "<br><strong>Ticketed At:</strong> " . date('F d, Y', strtotime($latestDamage->ticketed_at));
            }
        }
    }

    if ($unserviceableCount > 0) {
        $reply .= "<br><br><strong>Unserviceable Record Summary</strong><br>"
            . "<strong>Total Unserviceable Reports:</strong> {$unserviceableCount}<br>";

        if ($latestUnserviceable) {
            $reply .= "<strong>Latest Reason:</strong> " . (trim((string)$latestUnserviceable->reason) !== '' ? e($latestUnserviceable->reason) : 'N/A') . "<br>"
                . "<strong>Borrower Name:</strong> " . (trim((string)$latestUnserviceable->borrower_name) !== '' ? e($latestUnserviceable->borrower_name) : 'N/A') . "<br>"
                . "<strong>Reported At:</strong> " . (!empty($latestUnserviceable->reported_at) ? date('F d, Y', strtotime($latestUnserviceable->reported_at)) : 'N/A');
        }
    }

    if (!empty($item->remarks)) {
        $reply .= "<br><br><strong>Remarks</strong><br>" . e($item->remarks);
    }

    return response()->json(['reply' => $reply]);
}

private function buildPredictiveAnalytics($item, array $assessment, array $usageStats, int $damageCount = 0, int $unserviceableCount = 0): string
{
    $status = strtolower((string)($item->status ?? ''));
    $lines = [];

    if ($status === 'available') {
        $lines[] = "• The item is currently available for use and can remain in service if monitoring continues.";
    } elseif ($status === 'damaged') {
        $lines[] = "• The item is currently damaged and has an increased probability of further deterioration if repair is delayed.";
    } elseif ($status === 'unserviceable') {
        $lines[] = "• The item is already unserviceable and has a very low probability of returning to normal service without major repair or replacement.";
    } elseif ($status === 'missing') {
        $lines[] = "• The item is currently missing and is expected to remain unavailable until traced and resolved.";
    } else {
        $lines[] = "• The item currently has a status of {$item->status}.";
    }

    if (!is_null($assessment['days_until_maintenance_due'])) {
        if ($assessment['days_until_maintenance_due'] < 0) {
            $lines[] = "• Preventive maintenance is overdue by " . abs($assessment['days_until_maintenance_due']) . " day(s), which raises the likelihood of operational failure.";
        } elseif ($assessment['days_until_maintenance_due'] <= 15) {
            $lines[] = "• Preventive maintenance will become due in {$assessment['days_until_maintenance_due']} day(s), indicating near-term servicing need.";
        }
    }

    if (!is_null($assessment['usage_percent'])) {
        if ($assessment['usage_percent'] >= 100) {
            $lines[] = "• Usage has already reached or exceeded the maintenance threshold, so the item is highly likely to need servicing immediately.";
        } elseif ($assessment['usage_percent'] >= 80) {
            $lines[] = "• Usage has reached " . number_format($assessment['usage_percent'], 2) . "% of the maintenance threshold, so servicing is likely to be needed soon.";
        }
    }

    if (!is_null($assessment['life_percent'])) {
        if ($assessment['life_percent'] >= 100) {
            $lines[] = "• The item has exceeded its expected life span, increasing the probability of replacement need and unstable performance.";
        } elseif ($assessment['life_percent'] >= 80) {
            $lines[] = "• The item is approaching end-of-life condition and may require replacement planning in the near term.";
        }
    }

    if ($usageStats['usage_trend'] === 'Increasing') {
        $lines[] = "• Recent usage shows an increasing trend, which suggests faster wear and earlier maintenance demand.";
    } elseif ($usageStats['usage_trend'] === 'Decreasing') {
        $lines[] = "• Recent usage shows a decreasing trend, which may reduce short-term wear pressure.";
    } else {
        $lines[] = "• Recent usage is relatively stable, suggesting no abrupt change in operating demand.";
    }

    if ($usageStats['average_usage_hours'] >= 8) {
        $lines[] = "• Average usage per issuance is high, which may accelerate component wear.";
    } elseif ($usageStats['average_usage_hours'] >= 4) {
        $lines[] = "• Average usage per issuance is moderate and should continue to be monitored.";
    }

    if ($usageStats['usage_last_30_days'] >= 40) {
        $lines[] = "• The item has been heavily used in the last 30 days, increasing the chance of near-term maintenance requirement.";
    }

    if ($damageCount >= 2) {
        $lines[] = "• Multiple damage records indicate recurring issues and raise the likelihood of repeated failure.";
    } elseif ($damageCount === 1) {
        $lines[] = "• One damage record suggests the item should be watched closely for repeat issues.";
    }

    if ($unserviceableCount >= 1) {
        $lines[] = "• The item has unserviceable history, indicating elevated long-term reliability risk.";
    }

    if (empty($lines)) {
        $lines[] = "• No immediate predictive concerns were detected from the current usage, age, maintenance, and incident data.";
    }

    return implode('<br>', array_values(array_unique($lines)));
}

private function buildPrescriptiveAnalytics($item, array $assessment, array $usageStats, int $damageCount = 0, int $unserviceableCount = 0): string
{
    $status = strtolower((string)($item->status ?? ''));
    $actions = [];

    if ($status === 'available') {
        $actions[] = "• Keep the item in service while continuing routine monitoring and preventive checks.";
    }

    if ($status === 'damaged') {
        $actions[] = "• Prioritize inspection and repair scheduling to prevent the item from becoming fully unserviceable.";
    }

    if ($status === 'unserviceable') {
        $actions[] = "• Remove the item from active deployment and evaluate whether replacement, disposal, or major repair is more cost-effective.";
    }

    if ($status === 'missing') {
        $actions[] = "• Begin tracing procedures immediately and verify the last borrower, issue record, and responsible custodian.";
    }

    if (!is_null($assessment['days_until_maintenance_due'])) {
        if ($assessment['days_until_maintenance_due'] < 0) {
            $actions[] = "• Perform preventive maintenance immediately because the schedule is already overdue.";
        } elseif ($assessment['days_until_maintenance_due'] <= 15) {
            $actions[] = "• Schedule preventive maintenance within the next {$assessment['days_until_maintenance_due']} day(s).";
        }
    }

    if (!is_null($assessment['usage_percent'])) {
        if ($assessment['usage_percent'] >= 100) {
            $actions[] = "• Service the item immediately because usage has exceeded the defined maintenance threshold.";
        } elseif ($assessment['usage_percent'] >= 80) {
            $actions[] = "• Closely monitor remaining usage allowance and prepare service scheduling before threshold exceedance.";
        }
    }

    if ($usageStats['usage_trend'] === 'Increasing') {
        $actions[] = "• Increase inspection frequency because recent usage demand is rising.";
    }

    if ($usageStats['usage_last_30_days'] >= 40) {
        $actions[] = "• Consider shorter maintenance intervals because recent monthly usage is high.";
    }

    if (!is_null($assessment['life_percent'])) {
        if ($assessment['life_percent'] >= 100) {
            $actions[] = "• Start replacement evaluation immediately because the item has exceeded expected life span.";
        } elseif ($assessment['life_percent'] >= 80) {
            $actions[] = "• Prepare budget and procurement planning because the item is nearing end-of-life.";
        }
    }

    if ($damageCount >= 2) {
        $actions[] = "• Review repeated damage incidents to determine whether user handling, environment, or storage practices must be corrected.";
    }

    if ($unserviceableCount >= 1) {
        $actions[] = "• Review past unserviceable cases before approving further repair spending.";
    }

    if (empty($actions)) {
        $actions[] = "• No urgent action is recommended at this time.";
    }

    return implode('<br>', array_values(array_unique($actions)));
}

    private function whenIssued(Request $request, string $rawMessage)
    {
        $serial = $this->resolveSerialFromMessageOrContext($request, $rawMessage);

        if (!$serial) {
            return response()->json(['reply' => "Please include a serial like SN0001."]);
        }

        $issued = DB::table('issuedlog')
            ->where('serial_no', $serial)
            ->orderByDesc('issue_id')
            ->select('issued_date')
            ->first();

        if (!$issued) {
            return response()->json(['reply' => "This item ({$serial}) has never been issued."]);
        }

        $date = $issued->issued_date ? date('F d, Y', strtotime($issued->issued_date)) : 'N/A';

        return response()->json(['reply' => "Last issued on {$date}."]);
    }

    private function whoBorrowed(Request $request, string $rawMessage)
{
    $hasExplicitSerial = $this->hasExplicitSerial($rawMessage);
    $lastListSerials = $this->getLastListSerials($request);

    // If user did NOT type a serial, but there is a recent listed group,
    // answer for the whole group (or single item if only one exists).
    if (!$hasExplicitSerial && !empty($lastListSerials)) {
        $reply = "<strong>Last Borrower of Listed Items:</strong><br><br>";

        foreach ($lastListSerials as $listSerial) {
            $row = DB::table('issuedlog as i')
                ->leftJoin('items as it', 'i.serial_no', '=', 'it.serial_no')
                ->leftJoin('damagereports as d', function ($join) {
                    $join->on('d.serial_no', '=', 'i.serial_no');
                })
                ->where('i.serial_no', $listSerial)
                ->orderByDesc('i.issue_id')
                ->select(
                    'i.borrower_name',
                    'i.issued_by',
                    'i.issued_date',
                    'i.return_date',
                    'i.actual_return_date',
                    'it.item_name',
                    'it.status',
                    'i.serial_no',
                    DB::raw('(SELECT dr.observation
                              FROM damagereports dr
                              WHERE dr.serial_no = i.serial_no
                              ORDER BY dr.reported_at DESC
                              LIMIT 1) as latest_observation')
                )
                ->first();

            if ($row) {
                $itemName = $row->item_name ?? 'Unknown item';
                $borrower = trim((string)($row->borrower_name ?? '')) ?: 'N/A';
                $issuedByName = $this->issuedByName($row->issued_by);
                $issuedDate = $row->issued_date ? date('F d, Y', strtotime($row->issued_date)) : 'N/A';
                $actualReturn = $row->actual_return_date ? date('F d, Y', strtotime($row->actual_return_date)) : null;
                $returnDate = $row->return_date ? date('F d, Y', strtotime($row->return_date)) : 'N/A';
                $returnLine = $actualReturn ? "Returned: {$actualReturn}" : "Expected Return: {$returnDate}";

                $observation = trim((string)($row->latest_observation ?? ''));
                $arrivalNote = '';

                if ($observation !== '' && preg_match('/upon\s+arrival|upon\s+arival/i', $observation)) {
                    $arrivalNote = "<br>Borrower Name: {$borrower} - Damaged upon arrival";
                }

                $reply .= "<strong>{$itemName}</strong><br>"
                    . "Serial No: {$listSerial}<br>"
                    . "Status: " . ($row->status ?? 'Unknown') . "<br><br>"
                    . "Borrower: {$borrower}{$arrivalNote}<br>"
                    . "Issued By: {$issuedByName}<br>"
                    . "Date Issued: {$issuedDate}<br>"
                    . "{$returnLine}";

                if ($observation !== '') {
                    $reply .= "<br>Observation: {$observation}";
                }

                $reply .= "<br><br>";
            } else {
                $item = DB::table('items')
                    ->where('serial_no', $listSerial)
                    ->select('item_name', 'status')
                    ->first();

                $itemName = $item->item_name ?? 'Unknown item';
                $status = $item->status ?? 'Unknown';

                $latestDamage = DB::table('damagereports')
                    ->where('serial_no', $listSerial)
                    ->orderByDesc('reported_at')
                    ->select('borrower_name', 'observation')
                    ->first();

                $borrower = trim((string)($latestDamage->borrower_name ?? '')) ?: 'N/A';
                $observation = trim((string)($latestDamage->observation ?? ''));
                $arrivalNote = '';

                if ($observation !== '' && preg_match('/upon\s+arrival|upon\s+arival/i', $observation)) {
                    $arrivalNote = " - Damaged upon arrival";
                }

                $reply .= "<strong>{$itemName}</strong><br>"
                    . "Serial No: {$listSerial}<br>"
                    . "Status: {$status}<br>";

                if ($borrower !== 'N/A') {
                    $reply .= "Borrower Name: {$borrower}{$arrivalNote}<br>";
                }

                if ($observation !== '') {
                    $reply .= "Observation: {$observation}<br>";
                }

                $reply .= "No borrowing/issuance record found.<br><br>";
            }
        }

        return response()->json(['reply' => $reply]);
    }

    // Explicit serial OR fallback to single remembered serial
    $serial = $this->resolveSerialFromMessageOrContext($request, $rawMessage);

    if (!$serial) {
        return response()->json(['reply' => "Please include a serial like SN0001 (or ask right after listing damaged items)."]);
    }

    $row = DB::table('issuedlog as i')
        ->leftJoin('items as it', 'i.serial_no', '=', 'it.serial_no')
        ->where('i.serial_no', $serial)
        ->orderByDesc('i.issue_id')
        ->select(
            'i.borrower_name',
            'i.issued_by',
            'i.issued_date',
            'i.return_date',
            'i.actual_return_date',
            'it.item_name',
            'it.status',
            DB::raw('(SELECT dr.observation
                      FROM damagereports dr
                      WHERE dr.serial_no = i.serial_no
                      ORDER BY dr.reported_at DESC
                      LIMIT 1) as latest_observation')
        )
        ->first();

    if (!$row) {
        $item = DB::table('items')
            ->where('serial_no', $serial)
            ->select('item_name', 'status')
            ->first();

        if (!$item) {
            return response()->json(['reply' => "Item not found for serial {$serial}."]);
        }

        $latestDamage = DB::table('damagereports')
            ->where('serial_no', $serial)
            ->orderByDesc('reported_at')
            ->select('borrower_name', 'observation')
            ->first();

        $borrower = trim((string)($latestDamage->borrower_name ?? '')) ?: 'N/A';
        $observation = trim((string)($latestDamage->observation ?? ''));
        $arrivalNote = '';

        if ($observation !== '' && preg_match('/upon\s+arrival|upon\s+arival/i', $observation)) {
            $arrivalNote = " - Damaged upon arrival";
        }

        $reply = "<strong>{$item->item_name}</strong><br>
                    Serial No: {$serial}<br>
                    Status: {$item->status}<br><br>";

        if ($borrower !== 'N/A') {
            $reply .= "Borrower Name: {$borrower}{$arrivalNote}<br>";
        }

        if ($observation !== '') {
            $reply .= "Observation: {$observation}<br>";
        }

        $reply .= "No borrowing/issuance record found.";

        return response()->json(['reply' => $reply]);
    }

    $itemName = $row->item_name ?? 'Unknown item';
    $status = $row->status ?? 'Unknown';
    $borrower = trim((string)($row->borrower_name ?? '')) ?: 'N/A';
    $issuedByName = $this->issuedByName($row->issued_by);
    $issuedDate = $row->issued_date ? date('F d, Y', strtotime($row->issued_date)) : 'N/A';
    $returnDate = $row->return_date ? date('F d, Y', strtotime($row->return_date)) : 'N/A';
    $actualReturn = $row->actual_return_date ? date('F d, Y', strtotime($row->actual_return_date)) : null;
    $returnLine = $actualReturn ? "Returned: {$actualReturn}" : "Expected Return: {$returnDate}";

    $observation = trim((string)($row->latest_observation ?? ''));
    $arrivalNote = '';

    if ($observation !== '' && preg_match('/upon\s+arrival|upon\s+arival/i', $observation)) {
        $arrivalNote = "<br>Borrower Name: {$borrower} - Damaged upon arrival";
    }

    $reply = "<strong>{$itemName}</strong><br>
                Serial No: {$serial}<br>
                Status: {$status}<br><br>
                Borrower: {$borrower}{$arrivalNote}<br>
                Issued By: {$issuedByName}<br>
                Date Issued: {$issuedDate}<br>
                {$returnLine}";

    if ($observation !== '') {
        $reply .= "<br>Observation: {$observation}";
    }

    return response()->json(['reply' => $reply]);
}

    /* =========================
     | FAQs (same as yours)
     ========================= */
    private function faqSerialTrackingImportance()
    {
        return response()->json([
            'reply' => 'Serial number tracking is important in inventory management because it allows each item to be uniquely identified, making it easier to track its location, usage, maintenance history, and status.'
        ]);
    }

    private function faqValidateSerialBeforeInsert()
    {
        return response()->json([
            'reply' => 'It is important to validate serial numbers before inserting them into the inventory database to ensure the data is accurate, prevent duplicates or unauthorized entries, and maintain the integrity of the inventory records.'
        ]);
    }

    private function faqTrackMaintenanceRepair()
    {
        return response()->json([
            'reply' => 'The system tracks items under maintenance or repair in the Maintenance section. After inspection, the item can be updated to Available if repaired or Unserviceable if it is no longer usable.'
        ]);
    }

    private function faqQrBarcode()
    {
        return response()->json([
            'reply' => 'QR codes and barcodes are used to quickly identify items in the inventory system. A barcode stores information in horizontal lines and usually contains less data, while a QR code can store more information and can be scanned from any direction.'
        ]);
    }

    private function faqItemApproval()
    {
        return response()->json([
            'reply' => 'An Item Approval Request is a process where new items must be reviewed and approved by the administrator before they can be added to the inventory system. This ensures that only valid and authorized items are recorded in the database.'
        ]);
    }

    private function faqCheckItemStatus()
    {
        return response()->json([
            'reply' => 'You can check the status of an item by entering its serial number in the chatbot, for example: "What is the status of SN0006?". The system will show whether the item is Available, Issued, Damaged, or Unserviceable.'
        ]);
    }

    /* =========================
     | SUGGESTIONS ENDPOINT
     ========================= */
    public function suggestions(Request $request)
    {
        $q = strtolower(trim($request->query('q', '')));

        $base = [
            "Why is serial number tracking important?",
            "Why validate serial numbers before inserting?",
            "How to track items under maintenance?",
            "QR vs Barcode",
            "What is item approval request?",
            "How to check item status?",
            "List available items",
            "List damaged items",
            "List unserviceable items",
            "List missing items",
            "List all items",
            "How many items are in inventory?",
            "Low stock items",
            "Who borrowed SN?",
            "When was SN issued?",
            "What is the status of SN?",
            "Who reported damage of SN?",
            "Show damaged items with borrower",
            "Show unserviceable items with borrower",
            "Show missing items with borrower",
        ];

        // Dynamic item-name prompts
        $itemNames = DB::table('propertyinventory')
            ->select('item_name')
            ->whereNotNull('item_name')
            ->groupBy('item_name')
            ->orderBy('item_name')
            ->limit(50)
            ->pluck('item_name')
            ->toArray();

        $dynamic = [];
        foreach ($itemNames as $name) {
            $clean = trim((string) $name);
            if ($clean === '') continue;
            $dynamic[] = "How many {$clean}?";
        }

        $all = array_merge($base, $dynamic);

        if ($q !== '') {
            $all = array_values(array_filter($all, function ($s) use ($q) {
                return str_contains(strtolower($s), $q);
            }));
        }

        $all = array_values(array_unique($all));
        $all = array_slice($all, 0, 8);

        return response()->json(['suggestions' => $all]);
    }

    private function greet()
{
    return response()->json([
        'reply' => "Hello! I’m the TESDA Inventory Chatbot. Type <strong>Help</strong> to see what I can do."
    ]);
}

private function intro()
{
    return response()->json([
        'reply' =>
            "I’m the <strong>TESDA Inventory Chatbot</strong>. " .
            "I can help you check item availability, stock counts, item status by serial number (SN), and item issuance history."
    ]);
}

private function help()
{
    $reply =
    "<strong>Here’s what I can help you with:</strong><br><br>" .
    "✅ <strong>Inventory Lists</strong><br>" .
    "• List available items<br>" .
    "• List damaged items<br>" .
    "• List unserviceable items<br>" .
    "• List missing items<br>" .
    "• Show damaged items with borrower<br>" .
    "• Show unserviceable items with borrower<br>" .
    "• Show missing items with borrower<br><br>" .
    "✅ <strong>Counts</strong><br>" .
    "• How many items are in inventory?<br>" .
    "• How many laptops?<br>" .
    "• Low stock items<br><br>" .
    "✅ <strong>Serial Number Queries</strong><br>" .
    "• What is the status of SN0001?<br>" .
    "• Who borrowed SN0001?<br>" .
    "• When was SN0001 issued?<br>" .
    "• Who reported damage of SN0001?<br>";

    return response()->json(['reply' => $reply]);
}

private function outOfScope()
{
    return response()->json([
        'reply' =>
            "I can only assist with the <strong>TESDA Inventory System</strong> (items, serial numbers, issuance, stock, and reports).<br><br>" .
            "Try asking:<br>" .
            "• List available items<br>" .
            "• What is the status of SN0001?<br>" .
            "• How many laptops?"
    ]);
}

private function listMissing(Request $request)
{
    $items = DB::table('items')
        ->where('status', 'Missing')
        ->select('item_name', 'serial_no')
        ->orderBy('item_name')
        ->orderBy('serial_no')
        ->get();

    if ($items->isEmpty()) {
        $this->clearLastListSerials($request);
        return response()->json(['reply' => 'There are currently no missing items.']);
    }

    $serials = $items->pluck('serial_no')->filter()->values()->toArray();
    $this->setLastListSerials($request, $serials);

    if (count($serials) === 1) {
        $this->setLastSerial($request, $serials[0]);
    }

    $reply = "<strong>Missing Items:</strong><br><br>";
    $currentItem = null;

    foreach ($items as $item) {
        if ($currentItem !== $item->item_name) {
            $currentItem = $item->item_name;
            $reply .= "<br><strong>{$currentItem}</strong><br>";
        }
        $reply .= "• {$item->serial_no}<br>";
    }

    return response()->json(['reply' => $reply]);
}

private function missingWithBorrower()
{
    $items = DB::table('items')
        ->where('status', 'Missing')
        ->select('item_name', 'serial_no')
        ->orderBy('item_name')
        ->orderBy('serial_no')
        ->get();

    if ($items->isEmpty()) {
        return response()->json(['reply' => 'There are currently no missing items.']);
    }

    $reply = "<strong>Missing Items and Last Borrower:</strong><br><br>";

    foreach ($items as $it) {
        $issued = DB::table('issuedlog as i')
            ->leftJoin('formrecords as f', 'i.reference_no', '=', 'f.reference_no')
            ->where('i.serial_no', $it->serial_no)
            ->orderByDesc('i.issue_id')
            ->select(
                'i.issue_id',
                'i.issued_by',
                'i.issued_date',
                'i.return_date',
                'i.actual_return_date',
                DB::raw("COALESCE(NULLIF(i.borrower_name,''), NULLIF(f.borrower_name,'')) as borrower_name")
            )
            ->first();

        if ($issued) {
            $borrower = trim((string)($issued->borrower_name ?? '')) ?: 'N/A';
            $issuedByName = $this->issuedByName($issued->issued_by);
            $issuedDate = $issued->issued_date ? date('F d, Y', strtotime($issued->issued_date)) : 'N/A';
            $returnDate = $issued->return_date ? date('F d, Y', strtotime($issued->return_date)) : 'N/A';
            $actualReturn = $issued->actual_return_date ? date('F d, Y', strtotime($issued->actual_return_date)) : null;
            $returnLine = $actualReturn ? "Returned: {$actualReturn}" : "Expected Return: {$returnDate}";

            $reply .= "<strong>{$it->item_name}</strong><br>"
                . "Serial No: {$it->serial_no}<br>"
                . "Last Borrower: <strong>{$borrower}</strong><br>"
                . "Issued By: {$issuedByName}<br>"
                . "Date Issued: {$issuedDate}<br>"
                . "{$returnLine}<br><br>";
        } else {
            $reply .= "<strong>{$it->item_name}</strong><br>"
                . "Serial No: {$it->serial_no}<br>"
                . "No issuance record found.<br><br>";
        }
    }

    return response()->json(['reply' => $reply]);
}

private function buildItemAssessment($item, int $damageCount = 0, int $unserviceableCount = 0): array
{
    $today = now();

    $yearsUsed = null;
    $serviceDurationText = 'N/A';

    if (!empty($item->date_acquired)) {
        $acquired = \Carbon\Carbon::parse($item->date_acquired);
        $yearsUsed = $acquired->diffInYears($today);
        $serviceDurationText = $this->formatServiceDuration($item->date_acquired);
    }

    $daysSinceMaintenance = null;
    $daysUntilMaintenanceDue = null;
    if (!empty($item->last_maintenance_date) && !empty($item->maintenance_interval_days)) {
        $daysSinceMaintenance = \Carbon\Carbon::parse($item->last_maintenance_date)->diffInDays($today);
        $daysUntilMaintenanceDue = (int)$item->maintenance_interval_days - $daysSinceMaintenance;
    }

    $usageHours = is_null($item->total_usage_hours) ? null : (float)$item->total_usage_hours;
    $usageThreshold = is_null($item->maintenance_threshold_usage) ? null : (float)$item->maintenance_threshold_usage;
    $usagePercent = null;

    if (!is_null($usageHours) && !is_null($usageThreshold) && $usageThreshold > 0) {
        $usagePercent = round(($usageHours / $usageThreshold) * 100, 2);
    }

    $lifePercent = null;
    if (!is_null($yearsUsed) && !empty($item->expected_life_years) && (int)$item->expected_life_years > 0) {
        $lifePercent = round(($yearsUsed / (int)$item->expected_life_years) * 100, 2);
    }

    return [
        'years_used' => $yearsUsed,
        'years_used_text' => $serviceDurationText,
        'expected_life' => !empty($item->expected_life_years) ? (int)$item->expected_life_years : null,
        'expected_life_text' => !empty($item->expected_life_years) ? ((int)$item->expected_life_years . " year(s)") : 'N/A',

        'last_maintenance_text' => !empty($item->last_maintenance_date)
            ? date('F d, Y', strtotime($item->last_maintenance_date))
            : 'N/A',

        'maintenance_interval' => !empty($item->maintenance_interval_days)
            ? (int)$item->maintenance_interval_days
            : null,

        'maintenance_interval_text' => !empty($item->maintenance_interval_days)
            ? ((int)$item->maintenance_interval_days . " day(s)")
            : 'N/A',

        'days_since_maintenance' => $daysSinceMaintenance,
        'days_until_maintenance_due' => $daysUntilMaintenanceDue,

        'usage_hours' => $usageHours,
        'usage_hours_text' => is_null($usageHours) ? 'N/A' : number_format($usageHours, 2) . " hour(s)",

        'usage_threshold' => $usageThreshold,
        'usage_threshold_text' => is_null($usageThreshold) ? 'N/A' : number_format($usageThreshold, 2) . " hour(s)",

        'usage_percent' => $usagePercent,
        'life_percent' => $lifePercent,
        'damage_count' => $damageCount,
        'unserviceable_count' => $unserviceableCount,
    ];
}

private function getItemRiskLevel(array $assessment, array $usageStats = []): string
{
    $score = 0;

    if ($assessment['damage_count'] >= 2) {
        $score += 2;
    } elseif ($assessment['damage_count'] === 1) {
        $score += 1;
    }

    if ($assessment['unserviceable_count'] >= 1) {
        $score += 3;
    }

    if (!is_null($assessment['days_until_maintenance_due'])) {
        if ($assessment['days_until_maintenance_due'] < 0) {
            $score += 2;
        } elseif ($assessment['days_until_maintenance_due'] <= 15) {
            $score += 1;
        }
    }

    if (!is_null($assessment['usage_percent'])) {
        if ($assessment['usage_percent'] >= 100) {
            $score += 2;
        } elseif ($assessment['usage_percent'] >= 80) {
            $score += 1;
        }
    }

    if (!is_null($assessment['life_percent'])) {
        if ($assessment['life_percent'] >= 100) {
            $score += 2;
        } elseif ($assessment['life_percent'] >= 80) {
            $score += 1;
        }
    }

    if (!empty($usageStats)) {
        if (($usageStats['usage_trend'] ?? '') === 'Increasing') {
            $score += 1;
        }

        if (($usageStats['usage_last_30_days'] ?? 0) >= 40) {
            $score += 1;
        }

        if (($usageStats['average_usage_hours'] ?? 0) >= 8) {
            $score += 1;
        }
    }

    if ($score >= 7) return 'High';
    if ($score >= 4) return 'Moderate';
    return 'Low';
}

private function formatServiceDuration(?string $startDate): string
{
    if (empty($startDate)) {
        return 'N/A';
    }

    try {
        $start = \Carbon\Carbon::parse($startDate);
        $now = now();

        if ($start->gt($now)) {
            return '0 month(s)';
        }

        $diff = $start->diff($now);

        $parts = [];

        if ($diff->y > 0) {
            $parts[] = $diff->y . ' year(s)';
        }

        if ($diff->m > 0) {
            $parts[] = $diff->m . ' month(s)';
        }

        if ($diff->y === 0 && $diff->m === 0) {
            $parts[] = $diff->d . ' day(s)';
        }

        return implode(' and ', $parts);
    } catch (\Throwable $e) {
        return 'N/A';
    }
}

private function getItemSpecificRecommendations($item): string
{
    $itemName = strtolower(trim((string)($item->item_name ?? '')));
    $description = strtolower(trim((string)($item->description ?? '')));
    $specification = strtolower(trim((string)($item->specification ?? '')));

    $text = $itemName . ' ' . $description . ' ' . $specification;
    $recommendations = [];

    // Printer
    if (str_contains($text, 'printer')) {
        $recommendations[] = "• Check ink or toner level every 3 weeks.";
        $recommendations[] = "• Clean the print head and rollers every month.";
        $recommendations[] = "• Inspect paper feed alignment and remove dust buildup regularly.";
        $recommendations[] = "• Replace cartridges immediately if print quality becomes faded or inconsistent.";
    }

    // Computer / Desktop / PC
    if (
        str_contains($text, 'computer') ||
        str_contains($text, 'desktop') ||
        str_contains($text, 'pc') ||
        str_contains($text, 'system unit')
    ) {
        $recommendations[] = "• Inspect wirings, power cables, and peripheral connections every 2 weeks.";
        $recommendations[] = "• Clean internal and external dust buildup every month.";
        $recommendations[] = "• Check system temperature, fan condition, and ventilation regularly.";
        $recommendations[] = "• Verify antivirus and software updates are active and current.";
    }

    // Laptop
    if (str_contains($text, 'laptop')) {
        $recommendations[] = "• Inspect charger, battery condition, and ports every 2 weeks.";
        $recommendations[] = "• Clean keyboard, vents, and screen surface every 2 to 4 weeks.";
        $recommendations[] = "• Monitor overheating, unusual noise, and battery health regularly.";
        $recommendations[] = "• Avoid overbending the charger cable and protect the device during transport.";
    }

    // Projector
    if (str_contains($text, 'projector')) {
        $recommendations[] = "• Clean the projector lens and air vents every 2 weeks.";
        $recommendations[] = "• Check lamp performance and overheating signs before extended use.";
        $recommendations[] = "• Store in a dust-free area when not in use.";
        $recommendations[] = "• Inspect HDMI/VGA and power cables for wear or loose connections.";
    }

    // Monitor
    if (str_contains($text, 'monitor')) {
        $recommendations[] = "• Clean the screen surface using proper materials every 2 weeks.";
        $recommendations[] = "• Check display cables and power connection regularly.";
        $recommendations[] = "• Inspect for flickering, dead pixels, or overheating signs.";
    }

    // Keyboard
    if (str_contains($text, 'keyboard')) {
        $recommendations[] = "• Clean keys and remove dust buildup every 2 weeks.";
        $recommendations[] = "• Inspect cable or USB connection regularly.";
        $recommendations[] = "• Replace immediately if keys become unresponsive or damaged.";
    }

    // Mouse
    if (str_contains($text, 'mouse')) {
        $recommendations[] = "• Clean the mouse surface and sensor every 2 weeks.";
        $recommendations[] = "• Check USB cable or wireless battery condition regularly.";
        $recommendations[] = "• Replace if pointer movement becomes erratic or buttons fail.";
    }

    // AVR / UPS
    if (
        str_contains($text, 'avr') ||
        str_contains($text, 'ups') ||
        str_contains($text, 'voltage regulator')
    ) {
        $recommendations[] = "• Inspect power input/output connections every 2 weeks.";
        $recommendations[] = "• Check for overheating, unusual smell, or buzzing sound regularly.";
        $recommendations[] = "• Test backup or voltage regulation function monthly.";
    }

    // Scanner
    if (str_contains($text, 'scanner')) {
        $recommendations[] = "• Clean scanner glass and rollers every 2 weeks.";
        $recommendations[] = "• Check image quality for lines, blur, or feed errors.";
        $recommendations[] = "• Inspect USB and power cables for secure connection.";
    }

    // Camera / CCTV
    if (
        str_contains($text, 'camera') ||
        str_contains($text, 'cctv')
    ) {
        $recommendations[] = "• Clean lens surface every 2 weeks.";
        $recommendations[] = "• Verify power and video/data connections regularly.";
        $recommendations[] = "• Check image quality, storage function, and mounting stability.";
    }

    // Electric fan
    if (
        str_contains($text, 'fan') ||
        str_contains($text, 'electric fan')
    ) {
        $recommendations[] = "• Clean fan blades and protective grill every 2 weeks.";
        $recommendations[] = "• Inspect motor noise and power cord condition regularly.";
        $recommendations[] = "• Tighten loose screws and check stability before use.";
    }

    // Air conditioner
    if (
        str_contains($text, 'aircon') ||
        str_contains($text, 'air conditioner') ||
        str_contains($text, 'ac unit')
    ) {
        $recommendations[] = "• Clean air filters every month.";
        $recommendations[] = "• Inspect drainage, wiring, and cooling performance regularly.";
        $recommendations[] = "• Schedule professional servicing every 3 to 6 months.";
    }

    // Generic fallback
    if (empty($recommendations)) {
        $recommendations[] = "• Perform routine inspection every month.";
        $recommendations[] = "• Check physical condition, wiring or connections, and cleanliness regularly.";
        $recommendations[] = "• Record any unusual performance, wear, or defects immediately.";
        $recommendations[] = "• Schedule preventive maintenance based on actual usage and condition history.";
    }

    return implode('<br>', array_values(array_unique($recommendations)));
}

private function isOutOfScope(string $msg): bool
{
    $inventoryKeywords = [
        'inventory', 'item', 'items', 'stock', 'available', 'issued', 'borrowed',
        'borrower', 'serial', 'sn', 'barcode', 'qr', 'maintenance', 'repair',
        'damaged', 'unserviceable','missing', 'approval', 'request', 'property', 'ics', 'par'
    ];

    foreach ($inventoryKeywords as $kw) {
        if (str_contains($msg, $kw)) return false;
    }
    $outOfScopeHints = [
        'weather', 'joke', 'love', 'crush', 'girlfriend', 'boyfriend',
        'song', 'lyrics', 'movie', 'anime', 'game', 'facebook', 'tiktok',
        'math', 'history', 'politics', 'president', 'travel', 'recipe', 'food'
    ];

    foreach ($outOfScopeHints as $kw) {
        if (str_contains($msg, $kw)) return true;
    }
    return strlen(trim($msg)) >= 8;
}

private function getUsageAnalyticsData(string $serial): array
{
    $completedIssuances = DB::table('issuedlog')
        ->where('serial_no', $serial)
        ->whereNotNull('usage_hours')
        ->count();

    $totalLoggedUsageHours = (float) (
        DB::table('issuedlog')
            ->where('serial_no', $serial)
            ->whereNotNull('usage_hours')
            ->sum('usage_hours') ?? 0
    );

    $averageUsageHours = $completedIssuances > 0
        ? round($totalLoggedUsageHours / $completedIssuances, 2)
        : 0;

    $latestUsage = DB::table('issuedlog')
        ->where('serial_no', $serial)
        ->whereNotNull('usage_hours')
        ->orderByDesc('actual_return_date')
        ->select('issued_date', 'actual_return_date', 'borrower_name', 'usage_hours')
        ->first();

    $monthStart = now()->copy()->startOfMonth();
    $threeMonthsAgo = now()->copy()->subMonths(3)->startOfDay();
    $sixMonthsAgo = now()->copy()->subMonths(6)->startOfDay();
    $thirtyDaysAgo = now()->copy()->subDays(30)->startOfDay();

    $usageThisMonth = (float) (
        DB::table('issuedlog')
            ->where('serial_no', $serial)
            ->whereNotNull('usage_hours')
            ->where('actual_return_date', '>=', $monthStart)
            ->sum('usage_hours') ?? 0
    );

    $usageLast3Months = (float) (
        DB::table('issuedlog')
            ->where('serial_no', $serial)
            ->whereNotNull('usage_hours')
            ->where('actual_return_date', '>=', $threeMonthsAgo)
            ->sum('usage_hours') ?? 0
    );

    $usageLast6Months = (float) (
        DB::table('issuedlog')
            ->where('serial_no', $serial)
            ->whereNotNull('usage_hours')
            ->where('actual_return_date', '>=', $sixMonthsAgo)
            ->sum('usage_hours') ?? 0
    );

    $usageLast30Days = (float) (
        DB::table('issuedlog')
            ->where('serial_no', $serial)
            ->whereNotNull('usage_hours')
            ->where('actual_return_date', '>=', $thirtyDaysAgo)
            ->sum('usage_hours') ?? 0
    );

    $recentBorrower = DB::table('issuedlog')
        ->where('serial_no', $serial)
        ->whereNotNull('usage_hours')
        ->whereNotNull('borrower_name')
        ->select('borrower_name', DB::raw('COUNT(*) as total'))
        ->groupBy('borrower_name')
        ->orderByDesc('total')
        ->first();

    $recentUsageRows = DB::table('issuedlog')
        ->where('serial_no', $serial)
        ->whereNotNull('usage_hours')
        ->orderByDesc('actual_return_date')
        ->limit(6)
        ->pluck('usage_hours')
        ->map(fn($v) => (float) $v)
        ->values()
        ->toArray();

    $usageTrend = 'Stable';

    if (count($recentUsageRows) >= 4) {
        $half = (int) floor(count($recentUsageRows) / 2);
        $recentAvg = array_sum(array_slice($recentUsageRows, 0, $half)) / max($half, 1);
        $olderAvg = array_sum(array_slice($recentUsageRows, $half)) / max(count($recentUsageRows) - $half, 1);

        if ($recentAvg > $olderAvg * 1.15) {
            $usageTrend = 'Increasing';
        } elseif ($recentAvg < $olderAvg * 0.85) {
            $usageTrend = 'Decreasing';
        }
    }

    return [
        'completed_issuances' => $completedIssuances,
        'total_logged_usage_hours' => $totalLoggedUsageHours,
        'average_usage_hours' => $averageUsageHours,
        'latest_usage' => $latestUsage,
        'usage_this_month' => round($usageThisMonth, 2),
        'usage_last_3_months' => round($usageLast3Months, 2),
        'usage_last_6_months' => round($usageLast6Months, 2),
        'usage_last_30_days' => round($usageLast30Days, 2),
        'top_borrower' => $recentBorrower->borrower_name ?? null,
        'top_borrower_count' => (int)($recentBorrower->total ?? 0),
        'usage_trend' => $usageTrend,
    ];
}

private function buildDescriptiveAnalytics($item, array $assessment, array $usageStats, int $damageCount = 0, int $unserviceableCount = 0): string
{
    $lines = [];

    $lines[] = "• Completed issuances: " . number_format($usageStats['completed_issuances']) . ".";
    $lines[] = "• Total logged usage hours: " . number_format($usageStats['total_logged_usage_hours'], 2) . " hour(s).";
    $lines[] = "• Average usage per issuance: " . number_format($usageStats['average_usage_hours'], 2) . " hour(s).";
    $lines[] = "• Usage in the last 30 days: " . number_format($usageStats['usage_last_30_days'], 2) . " hour(s).";
    $lines[] = "• Usage this month: " . number_format($usageStats['usage_this_month'], 2) . " hour(s).";
    $lines[] = "• Usage in the last 3 months: " . number_format($usageStats['usage_last_3_months'], 2) . " hour(s).";
    $lines[] = "• Usage trend based on recent completed issuances: {$usageStats['usage_trend']}.";

    if (!empty($usageStats['latest_usage'])) {
        $latest = $usageStats['latest_usage'];
        $latestDate = !empty($latest->actual_return_date)
            ? date('F d, Y', strtotime($latest->actual_return_date))
            : (!empty($latest->issued_date) ? date('F d, Y', strtotime($latest->issued_date)) : 'N/A');

        $latestBorrower = trim((string)($latest->borrower_name ?? '')) ?: 'N/A';
        $latestHours = is_null($latest->usage_hours) ? 'N/A' : number_format((float)$latest->usage_hours, 2) . " hour(s)";

        $lines[] = "• Most recent recorded usage: {$latestDate}, borrower: {$latestBorrower}, usage: {$latestHours}.";
    } else {
        $lines[] = "• No completed usage history has been recorded yet.";
    }

    if (!empty($usageStats['top_borrower'])) {
        $lines[] = "• Most frequent borrower: {$usageStats['top_borrower']} ({$usageStats['top_borrower_count']} issuance record(s)).";
    }

    if (!is_null($assessment['usage_percent'])) {
        $lines[] = "• The item has consumed " . number_format($assessment['usage_percent'], 2) . "% of its usage threshold.";
    }

    if (!is_null($assessment['life_percent'])) {
        $lines[] = "• The item has consumed " . number_format($assessment['life_percent'], 2) . "% of its expected life span.";
    }

    if ($damageCount > 0) {
        $lines[] = "• Damage history: {$damageCount} damage report(s).";
    }

    if ($unserviceableCount > 0) {
        $lines[] = "• Unserviceable history: {$unserviceableCount} report(s).";
    }

    return implode('<br>', $lines);
}

    /* =========================
     | FALLBACK AI (same logic)
     ========================= */
    private function fallbackAI(Request $request)
    {
        try {
            $key = config('services.openrouter.key');

            if (!$key) {
                Log::error("OpenRouter key is missing. Check .env and config/services.php");
                return response()->json([
                    'reply' => 'Chatbot AI is not configured. Please contact the TESDA admin.'
                ]);
            }

            $payload = [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a TESDA inventory chatbot. If the question is not answerable, say: "Please contact the TESDA admin."'],
                    ['role' => 'user', 'content' => $request->message],
                ],
                'max_tokens' => 150,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $key,
                'Content-Type'  => 'application/json',
            ])->post('https://openrouter.ai/api/v1/chat/completions', $payload);

            if (!$response->successful()) {
                Log::error("OpenRouter HTTP error: {$response->status()} | Body: " . $response->body());
                return response()->json([
                    'reply' => 'Chatbot service unavailable right now. Please contact the TESDA admin.'
                ]);
            }

            $json = $response->json();

            if (isset($json['error'])) {
                Log::error("OpenRouter API error: " . json_encode($json['error']));
                return response()->json([
                    'reply' => 'Chatbot service unavailable right now. Please contact the TESDA admin.'
                ]);
            }

            $content = $json['choices'][0]['message']['content'] ?? null;

            if (!$content) {
                Log::warning("OpenRouter returned no content. Full response: " . json_encode($json));
                return response()->json(['reply' => 'Please contact the TESDA admin.']);
            }

            return response()->json(['reply' => $content]);

        } catch (\Throwable $e) {
            Log::error("Fallback AI exception: " . $e->getMessage());
            return response()->json(['reply' => 'Chatbot service unavailable.']);
        }
    }
}