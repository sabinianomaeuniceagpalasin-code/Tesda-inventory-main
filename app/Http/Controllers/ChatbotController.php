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
            ->select('item_name', 'status')
            ->first();

        if (!$item) {
            return response()->json(['reply' => "No item found with serial {$serial}."]);
        }

        return response()->json([
            'reply' => "<strong>{$item->item_name}</strong><br>
                        Serial No: {$serial}<br>
                        Status: {$item->status}"
        ]);
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

private function isOutOfScope(string $msg): bool
{
    // If message contains inventory keywords, it's NOT out of scope
    $inventoryKeywords = [
        'inventory', 'item', 'items', 'stock', 'available', 'issued', 'borrowed',
        'borrower', 'serial', 'sn', 'barcode', 'qr', 'maintenance', 'repair',
        'damaged', 'unserviceable','missing', 'approval', 'request', 'property', 'ics', 'par'
    ];

    foreach ($inventoryKeywords as $kw) {
        if (str_contains($msg, $kw)) return false;
    }

    // If message looks like a general chit-chat / random topic → out of scope
    $outOfScopeHints = [
        'weather', 'joke', 'love', 'crush', 'girlfriend', 'boyfriend',
        'song', 'lyrics', 'movie', 'anime', 'game', 'facebook', 'tiktok',
        'math', 'history', 'politics', 'president', 'travel', 'recipe', 'food'
    ];

    foreach ($outOfScopeHints as $kw) {
        if (str_contains($msg, $kw)) return true;
    }

    // If it has no inventory keywords AND it's not a very short message,
    // treat it as out of scope.
    // (Short messages like "help", "hi" are handled earlier.)
    return strlen(trim($msg)) >= 8;
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