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
        | 1️⃣ INTENT DETECTION (ORDER MATTERS)
        ========================= */

        $intent = null;

        /**
         * MOST SPECIFIC FIRST
         */

        // 1️⃣ Damaged items WITH borrowers (plural)
        if (preg_match('/(who|list|show).*(borrower|borrowed).*(damaged)|damaged.*(borrower|borrowed|who)/i', $message)) {
            $intent = 'DAMAGED_WITH_BORROWER';
        }

        // 2️⃣ List damaged items only
        elseif (preg_match('/list\s+.*damaged|damaged\s+items/i', $message)) {
            $intent = 'LIST_DAMAGED';
        }

        // 3️⃣ Who borrowed a SPECIFIC item (requires SN)
        elseif (preg_match('/who\s+(borrowed|issued|is\s+using|last).*sn\d+/i', $message)) {
            $intent = 'WHO_BORROWED';
        }

        // 4️⃣ When issued (SN based)
        elseif (preg_match('/when.*issued.*sn\d+/i', $message)) {
            $intent = 'WHEN_ISSUED';
        }

        // 5️⃣ Item status (SN only)
        elseif (preg_match('/sn\d+/i', $message)) {
            $intent = 'ITEM_STATUS';
        }

        // 6️⃣ Low stock
        elseif (preg_match('/low\s+stock|low\s+inventory|nearly\s+out|out\s+of\s+stock/i', $message)) {
            $intent = 'LOW_STOCK';
        }

        // 7️⃣ List available
        elseif (preg_match('/list\s+.*available/i', $message)) {
            $intent = 'LIST_AVAILABLE';
        }

        // 8️⃣ List all
        elseif (preg_match('/list\s+.*all\s+items|show\s+.*all\s+items/i', $message)) {
            $intent = 'LIST_ALL';
        }

        // 9️⃣ Total items
        elseif (preg_match('/how\s+many\s+items/i', $message)) {
            $intent = 'TOTAL_ITEMS';
        }

        // 🔟 Item count (how many printers)
        elseif (preg_match('/how\s+many\s+[a-z\s]+/i', $message)) {
            $intent = 'ITEM_COUNT';
        }


        /* =========================
         | 2️⃣ ROUTER
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

            case 'ITEM_STATUS':
                return $this->itemStatus($message);

            case 'DAMAGED_WITH_BORROWER':
                return $this->damagedWithBorrower();

            case 'LIST_DAMAGED':
                return $this->listDamaged();

            default:
                return $this->fallbackAI($request);
        }
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
            return response()->json([
                'reply' => 'There are no available items in the inventory.'
            ]);
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


    private function listDamaged()
    {
        $items = DB::table('items')
            ->where('status', 'Damaged')
            ->select('item_name', 'serial_no')
            ->orderBy('item_name')
            ->orderBy('serial_no')
            ->get();

        if ($items->isEmpty()) {
            return response()->json([
                'reply' => 'There are currently no damaged items.'
            ]);
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
            ->get();

        if ($damagedItems->isEmpty()) {
            return response()->json([
                'reply' => 'There are currently no damaged items.'
            ]);
        }

        $reply = "<strong>Damaged Items and Borrowers:</strong><br><br>";

        foreach ($damagedItems as $item) {

            $issued = DB::table('issuedlog')
                ->join('student', 'issuedlog.student_id', '=', 'student.student_id')
                ->where('issuedlog.serial_no', $item->serial_no)
                ->orderByDesc('issuedlog.issued_date')
                ->select('student.student_name', 'issuedlog.issued_date')
                ->first();

            if ($issued) {
                $reply .= "<strong>{$item->item_name}</strong> ({$item->serial_no})<br>
                       Last Borrowed By: {$issued->student_name}<br>
                       Date Issued: " . date('F d, Y', strtotime($issued->issued_date)) . "<br><br>";
            } else {
                $reply .= "<strong>{$item->item_name}</strong> ({$item->serial_no})<br>
                       No borrowing record found.<br><br>";
            }
        }

        return response()->json(['reply' => $reply]);
    }



    private function listAll()
    {
        $items = DB::table('propertyinventory')
            ->select('item_name', DB::raw('SUM(quantity) as total'))
            ->groupBy('item_name')
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

        $itemName = trim(rtrim($match[2], 's'));

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
        preg_match('/(sn\d+)/i', $message, $sn);
        $serial = strtoupper($sn[1]);

        $issued = DB::table('issuedlog')
            ->where('serial_no', $serial)
            ->orderByDesc('issued_date')
            ->first();

        if (!$issued) {
            return response()->json(['reply' => 'This item has never been issued.']);
        }

        return response()->json([
            'reply' => "Last issued on " . date('F d, Y', strtotime($issued->issued_date))
        ]);
    }

    private function itemStatus($message)
    {
        preg_match('/(sn\d+)/i', $message, $sn);
        $serial = strtoupper($sn[1]);

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
        preg_match('/(sn\d+)/i', $message, $sn);
        $serial = strtoupper($sn[1]);

        $item = DB::table('items')->where('serial_no', $serial)->first();
        if (!$item) {
            return response()->json(['reply' => 'Item not found.']);
        }

        $issued = DB::table('issuedlog')
            ->join('student', 'issuedlog.student_id', '=', 'student.student_id')
            ->where('issuedlog.serial_no', $serial)
            ->orderByDesc('issuedlog.issued_date')
            ->select('student.student_name', 'issuedlog.issued_date')
            ->first();

        $borrower = $issued
            ? "Last Borrowed By: {$issued->student_name}<br>Date Issued: " . date('F d, Y', strtotime($issued->issued_date))
            : "No borrowing record found.";

        return response()->json([
            'reply' => "<strong>{$item->item_name}</strong><br>
                        Serial No: {$serial}<br>
                        Status: {$item->status}<br><br>
                        {$borrower}"
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
                            ['role' => 'system', 'content' => 'You are a TESDA inventory chatbot. if the question is not answerable say contact the tesda admin'],
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
