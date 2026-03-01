<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $message = strtolower(trim($request->message));

        /* =========================
         | 1) INTENT DETECTION (ORDER MATTERS)
         ========================= */
        $intent = null;

        // 1) Damaged items WITH borrowers
        if (preg_match('/(who|list|show).*(borrower|borrowed).*(damaged)|damaged.*(borrower|borrowed|who)/i', $message)) {
            $intent = 'DAMAGED_WITH_BORROWER';
        }
        // 2) List damaged items only
        elseif (preg_match('/list\s+.*damaged|damaged\s+items/i', $message)) {
            $intent = 'LIST_DAMAGED';
        }
        // 3) Who borrowed a SPECIFIC item (requires SN)
        elseif (preg_match('/who\s+(borrowed|issued|is\s+using|last).*sn[\-\s]?\d+/i', $message)) {
            $intent = 'WHO_BORROWED';
        }
        // 4) When issued (SN based)
        elseif (preg_match('/when.*(sn[\-\s]?\d+).*issued|when.*issued.*(sn[\-\s]?\d+)/i', $message)) {
            $intent = 'WHEN_ISSUED';
        }

        elseif (preg_match('/who\s+(damaged|reported\s+damage).*sn[\-\s]?\d+/i', $message)) {
            $intent = 'WHO_DAMAGED';
        }
        // 5) Item status (SN only)
        elseif (preg_match('/sn[\-\s]?\d+/i', $message)) {
            $intent = 'ITEM_STATUS';
        }
        // 6) Low stock
        elseif (preg_match('/low\s+stock|low\s+inventory|nearly\s+out|out\s+of\s+stock/i', $message)) {
            $intent = 'LOW_STOCK';
        }
        // 7) List available
        elseif (preg_match('/list\s+.*available/i', $message)) {
            $intent = 'LIST_AVAILABLE';
        }
        // 8) List all
        elseif (preg_match('/list\s+.*all\s+items|show\s+.*all\s+items/i', $message)) {
            $intent = 'LIST_ALL';
        }
        // 9) Total items
        elseif (preg_match('/how\s+many\s+items/i', $message)) {
            $intent = 'TOTAL_ITEMS';
        }
        // 10) Item count (how many printers)
        elseif (preg_match('/how\s+many\s+[a-z\s]+/i', $message)) {
            $intent = 'ITEM_COUNT';
        }

        // ✅ Unserviceable items WITH last borrower
        elseif (preg_match('/(who|last).*(borrower|borrowed).*(unserviceable)|unserviceable.*(who|last).*(borrower|borrowed)/i', $message)) {
            $intent = 'UNSERVICEABLE_WITH_BORROWER';
        }
        // ✅ List unserviceable items
        elseif (preg_match('/list\s+.*unserviceable|show\s+.*unserviceable|unserviceable\s+items/i', $message)) {
            $intent = 'LIST_UNSERVICEABLE';
        }

        /* =========================
         | 2) ROUTER
         ========================= */
        switch ($intent) {
            case 'WHO_BORROWED':
                return $this->whoBorrowed($message);

            case 'LOW_STOCK':
                return $this->lowStock();

            case 'LIST_AVAILABLE':
                return $this->listAvailable();

            case 'LIST_ALL':
                return $this->listAll();

            case 'TOTAL_ITEMS':
                return $this->totalItems();

            case 'ITEM_COUNT':
                return $this->itemCount($message);

            case 'WHEN_ISSUED':
                return $this->whenIssued($message);

            case 'WHO_DAMAGED':
                return $this->whoDamaged($message);

            case 'ITEM_STATUS':
                return $this->itemStatus($message);

            case 'DAMAGED_WITH_BORROWER':
                return $this->damagedWithBorrower();

            case 'LIST_DAMAGED':
                return $this->listDamaged();

            case 'UNSERVICEABLE_WITH_BORROWER':
                return $this->unserviceableWithBorrower();

            case 'LIST_UNSERVICEABLE':
                return $this->listUnserviceable();

            default:
                return $this->fallbackAI($request);
        }
    }

    /* =========================
     | HELPERS
     ========================= */

    private function extractSerial($message): ?string
{
    // supports SN0006, SN-0006, sn 0006
    if (!preg_match('/\b(sn)[\-\s]?(\d+)\b/i', $message, $m)) return null;

    $num = str_pad($m[2], 4, '0', STR_PAD_LEFT); // SN6 -> SN0006
    return 'SN' . $num;
}

    private function issuedByName($issuedBy): string
    {
        // If issued_by is numeric => join users
        if (is_numeric($issuedBy)) {
            $u = DB::table('users')->where('user_id', $issuedBy)->first();
            if ($u) return trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
        }

        // else it's already a name string
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
        return response()->json([
            'reply' => 'There are currently no unserviceable items.'
        ]);
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
        // latest issued log for this serial
        $issued = DB::table('issuedlog')
            ->where('serial_no', $it->serial_no)
            ->orderByDesc('issue_id')
            ->first();

        if ($issued) {
            $borrower = $issued->borrower_name ?: 'N/A';
            $issuedByName = $this->issuedByName($issued->issued_by);
            $issuedDate = $issued->issued_date ? date('F d, Y', strtotime($issued->issued_date)) : 'N/A';

            $reply .= "<strong>{$it->item_name}</strong> ({$it->serial_no})<br>"
                . "Last Borrower: <strong>{$borrower}</strong><br>"
                . "Issued By: {$issuedByName}<br>"
                . "Date Issued: {$issuedDate}<br><br>";
        } else {
            $reply .= "<strong>{$it->item_name}</strong> ({$it->serial_no})<br>"
                . "No issuance record found.<br><br>";
        }
    }

    return response()->json(['reply' => $reply]);
}

    private function listDamaged()
    {
        $items = DB::table('items')
            ->where('status', 'Damaged')
            ->select('item_name', 'serial_no')
            ->orderBy('item_name')
            ->orderBy('serial_no')
            ->get();

        if ($items->isEmpty()) {
            return response()->json(['reply' => 'There are currently no damaged items.']);
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

    private function whoDamaged($message)
{
    $serial = $this->extractSerial($message);

    if (!$serial) {
        return response()->json(['reply' => 'Please provide a serial number like SN0006.']);
    }

    // Get latest damage report for this serial + who reported it
    $damage = DB::table('damagereports as d')
        ->leftJoin('users as u', 'd.reported_by', '=', 'u.user_id')
        ->where('d.serial_no', $serial)
        ->orderByDesc('d.reported_at')
        ->select(
            'd.serial_no',
            'd.observation',
            'd.reported_at',
            'd.borrower_name',
            'u.first_name',
            'u.last_name'
        )
        ->first();

    if (!$damage) {
        return response()->json([
            'reply' => "No damage report found for {$serial}."
        ]);
    }

    $reportedBy = trim(($damage->first_name ?? '') . ' ' . ($damage->last_name ?? ''));
    if ($reportedBy === '') $reportedBy = 'Unknown user';

    $date = $damage->reported_at ? date('F d, Y', strtotime($damage->reported_at)) : 'Unknown date';
    $borrower = $damage->borrower_name ?: 'N/A';

    return response()->json([
        'reply' =>
            "<strong>{$serial}</strong><br>" .
            "Damaged reported by: <strong>{$reportedBy}</strong><br>" .
            "Borrower at time: <strong>{$borrower}</strong><br>" .
            "Date reported: {$date}<br>" .
            "Observation: {$damage->observation}"
    ]);
}

    private function damagedWithBorrower()
    {
        $damagedItems = DB::table('items')
            ->where('status', 'Damaged')
            ->select('item_name', 'serial_no')
            ->orderBy('item_name')
            ->get();

        if ($damagedItems->isEmpty()) {
            return response()->json(['reply' => 'There are currently no damaged items.']);
        }

        $reply = "<strong>Damaged Items and Last Issuance:</strong><br><br>";

        foreach ($damagedItems as $it) {
            // latest issued log for this serial
            $issued = DB::table('issuedlog')
                ->where('serial_no', $it->serial_no)
                ->orderByDesc('issue_id')
                ->first();

            if ($issued) {
                $issuedByName = $this->issuedByName($issued->issued_by);
                $borrower = $issued->borrower_name ?? 'N/A';
                $issuedDate = $issued->issued_date ? date('F d, Y', strtotime($issued->issued_date)) : 'N/A';

                $reply .= "<strong>{$it->item_name}</strong> ({$it->serial_no})<br>"
                    . "Borrower: {$borrower}<br>"
                    . "Issued By: {$issuedByName}<br>"
                    . "Date Issued: {$issuedDate}<br><br>";
            } else {
                $reply .= "<strong>{$it->item_name}</strong> ({$it->serial_no})<br>"
                    . "No issued record found.<br><br>";
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

    private function itemCount($message)
    {
        preg_match('/(how\s+many|number\s+of|stock\s+of|quantity\s+of)\s+([a-z\s]+)/i', $message, $match);
        $itemName = trim(rtrim($match[2] ?? '', 's'));

        if (!$itemName) {
            return response()->json(['reply' => "Please specify the item name (example: 'How many printers?')."]);
        }

        $item = DB::table('propertyinventory')
            ->select(DB::raw('SUM(quantity) as total'))
            ->whereRaw('LOWER(item_name) LIKE ?', ['%' . strtolower($itemName) . '%'])
            ->first();

        if (!$item || !$item->total) {
            return response()->json(['reply' => "I couldn’t find any {$itemName} in the inventory."]);
        }

        return response()->json([
            'reply' => "There are {$item->total} {$itemName}(s) in stock."
        ]);
    }

    private function whenIssued($message)
    {
        $serial = $this->extractSerial($message);
        if (!$serial) return response()->json(['reply' => "Please include a serial like SN100001."]);

        $issued = DB::table('issuedlog')
            ->where('serial_no', $serial)
            ->orderByDesc('issue_id')
            ->first();

        if (!$issued) {
            return response()->json(['reply' => "This item ({$serial}) has never been issued."]);
        }

        $date = $issued->issued_date ? date('F d, Y', strtotime($issued->issued_date)) : 'N/A';

        return response()->json([
            'reply' => "Last issued on {$date}."
        ]);
    }

    private function itemStatus($message)
    {
        $serial = $this->extractSerial($message);
        if (!$serial) return response()->json(['reply' => "Please include a serial like SN100001."]);

        $item = DB::table('items')->where('serial_no', $serial)->first();

        if (!$item) {
            return response()->json(['reply' => "No item found with serial {$serial}."]);
        }

        return response()->json([
            'reply' => "<strong>{$item->item_name}</strong><br>
                        Serial No: {$serial}<br>
                        Status: {$item->status}"
        ]);
    }

    private function whoBorrowed($message)
    {
        $serial = $this->extractSerial($message);
        if (!$serial) return response()->json(['reply' => "Please include a serial like SN100001."]);

        $item = DB::table('items')->where('serial_no', $serial)->first();
        if (!$item) {
            return response()->json(['reply' => "Item not found for serial {$serial}."]);
        }

        // Get latest issuance record for this serial
        $issued = DB::table('issuedlog')
            ->where('serial_no', $serial)
            ->orderByDesc('issue_id')
            ->first();

        if (!$issued) {
            return response()->json([
                'reply' => "<strong>{$item->item_name}</strong><br>
                            Serial No: {$serial}<br>
                            Status: {$item->status}<br><br>
                            No borrowing/issuance record found."
            ]);
        }

        $borrower = $issued->borrower_name ?? 'N/A';
        $issuedByName = $this->issuedByName($issued->issued_by);
        $issuedDate = $issued->issued_date ? date('F d, Y', strtotime($issued->issued_date)) : 'N/A';
        $returnDate = $issued->return_date ? date('F d, Y', strtotime($issued->return_date)) : 'N/A';
        $actualReturn = $issued->actual_return_date ? date('F d, Y', strtotime($issued->actual_return_date)) : null;

        $returnLine = $actualReturn
            ? "Returned: {$actualReturn}"
            : "Expected Return: {$returnDate}";

        return response()->json([
            'reply' => "<strong>{$item->item_name}</strong><br>
                        Serial No: {$serial}<br>
                        Status: {$item->status}<br><br>
                        Borrower: {$borrower}<br>
                        Issued By: {$issuedByName}<br>
                        Date Issued: {$issuedDate}<br>
                        {$returnLine}"
        ]);
    }

    

    private function fallbackAI(Request $request)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openrouter.key'),
                'Content-Type' => 'application/json',
            ])->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a TESDA inventory chatbot. If the question is not answerable, say: "Please contact the TESDA admin."'],
                    ['role' => 'user', 'content' => $request->message]
                ],
                'max_tokens' => 150
            ]);

            return response()->json([
                'reply' => $response->json()['choices'][0]['message']['content'] ?? 'No response.'
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['reply' => 'Chatbot service unavailable.']);
        }
    }
}