<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\Item;
use App\Models\DamageReport;

class IssuedDamageController extends Controller
{
    // ============================================================================
    //  store
    //  Called when the user clicks the ⚠️ (damaged) button on the Issued table.
    //
    //  ✅ UPDATED: Now accepts an optional `image` file via multipart/form-data.
    //     Previously this endpoint only accepted JSON, but JSON cannot carry files.
    //     The JS side now sends FormData instead of JSON.stringify(), which is why
    //     we read fields with $request->input() / $request->file() rather than
    //     json_decode($request->getContent()).
    //
    //  Expected fields (multipart/form-data):
    //    serial_no   (required) — serial number of the issued item
    //    observation (required) — description of the damage
    //    image       (optional) — JPEG / PNG / WebP ≤ 5 MB
    //
    //  The image is saved to storage/app/public/damage_images/<hash>.<ext>
    //  and is publicly accessible via /storage/damage_images/<hash>.<ext>
    //  (run `php artisan storage:link` once if not already done).
    // ============================================================================
    public function store(Request $request)
    {
        $request->validate([
            'serial_no'   => 'required|string|exists:items,serial_no',
            'observation' => 'required|string|max:1000',
            // ✅ image is optional; when present it must be a valid image ≤ 5 MB
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $serialNo    = trim($request->input('serial_no'));
        $observation = trim($request->input('observation'));

        // ── Resolve item ──────────────────────────────────────────────────────
        $item = Item::where('serial_no', $serialNo)->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found.',
            ], 404);
        }

        // ── Guard: item must be Issued to be reportable from the Issued table ─
        if ($item->status !== 'Issued') {
            return response()->json([
                'success' => false,
                'message' => "Item is not currently issued (status: {$item->status}).",
            ], 422);
        }

        // ── Handle optional image upload ──────────────────────────────────────
        // store() returns a relative path like "damage_images/abc123.jpg"
        // which is saved to damagereports.image_path.
        // Render it anywhere with: asset('storage/' . $imagePath)
        $imagePath = null;
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            // ✅ Store under storage/app/public/damage_images (publicly accessible)
            $imagePath = $request->file('image')->store('damage_images', 'public');
        }

        // ── Wrap in a transaction so status + report row stay in sync ─────────
        DB::beginTransaction();

        try {
            // 1) Mark item as Damaged
            $item->status     = 'Damaged';
            $item->updated_at = now();
            $item->save();

            // 2) Get the latest issuedlog entry for this serial so we can link
            //    the borrower name to the damage report for audit purposes
            $latestLog = DB::table('issuedlog')
                ->where('serial_no', $serialNo)
                ->orderByDesc('issue_id')
                ->first();

            // 3) Insert the damage report row
            //    image_path is NULL when no file was uploaded — that is intentional
            $damageId = DB::table('damagereports')->insertGetId([
                'serial_no'     => $serialNo,
                'observation'   => $observation,
                'borrower_name' => $latestLog->borrower_name ?? null, // ✅ link to last borrower
                'reported_by'   => auth()->id(),                       // ✅ track who filed it
                'reported_at'   => now(),
                'image_path'    => $imagePath,                         // ✅ nullable — fine if no file
                'is_ticketed'   => 0,                                  // not yet sent to maintenance
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            // 4) Create an admin notification so staff are alerted immediately
            $notifId = DB::table('notifications')->insertGetId([
                'type'               => 'damage_report',
                'title'              => 'Damage Report Filed',
                'message'            => "{$item->item_name} ({$serialNo}) was reported as damaged. Reason: {$observation}",
                'severity'           => 'warning',
                'entity_type'        => 'damage_report',
                'entity_id'          => $damageId,
                'action_url'         => route('dashboard', ['section' => 'damaged']),
                'data'               => json_encode([
                    'serial_no'    => $serialNo,
                    'item_name'    => $item->item_name,
                    'observation'  => $observation,
                    'borrower'     => $latestLog->borrower_name ?? null,
                    // ✅ Include the full public URL in the notification payload
                    //    so the notification panel can show the image thumbnail
                    'image_url'    => $imagePath
                                        ? asset('storage/' . $imagePath)
                                        : null,
                ]),
                'created_by_user_id' => auth()->id(),
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            // 5) Fan the notification out to all Admin + Property Custodian users
            $recipientIds = DB::table('users')
                ->whereIn('role', ['Admin', 'Property Custodian'])
                ->pluck('user_id');

            $recipientRows = $recipientIds->map(fn($uid) => [
                'notif_id'          => $notifId,
                'recipient_user_id' => $uid,
                'read_at'           => null,
                'deleted_at'        => null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ])->all();

            if (!empty($recipientRows)) {
                DB::table('notification_recipients')->insert($recipientRows);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item marked as damaged and report created successfully.',
                'damage'  => [
                    'damage_id'   => $damageId,
                    'serial_no'   => $serialNo,
                    'item_name'   => $item->item_name,
                    'observation' => $observation,
                    'reported_at' => now()->format('F d, Y'),
                    // ✅ Return the full public URL so the frontend can display
                    //    the uploaded image immediately without a page reload
                    'image_url'   => $imagePath
                                        ? asset('storage/' . $imagePath)
                                        : null,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            // ✅ Clean up the uploaded file if the DB transaction failed
            //    so we don't leave orphaned image files on disk
            if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to report damage: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ============================================================================
    //  showBySerial
    //  Returns the latest damage report for a given serial number as JSON.
    //  Used by the frontend to check if a damage report already exists before
    //  opening a form, and to display the attached image if one was uploaded.
    // ============================================================================
    public function showBySerial($serialNo)
    {
        $report = DB::table('damagereports as d')
            ->leftJoin('items as i', 'i.serial_no', '=', 'd.serial_no')
            ->where('d.serial_no', $serialNo)
            ->orderByDesc('d.reported_at')
            ->select(
                'd.damage_id',
                'd.serial_no',
                'd.observation',
                'd.reported_at',
                'd.image_path',      // ✅ raw relative path — convert to URL below
                'd.is_ticketed',
                'i.item_name'
            )
            ->first();

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'No damage report found for this serial number.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'report'  => [
                'damage_id'   => $report->damage_id,
                'serial_no'   => $report->serial_no,
                'item_name'   => $report->item_name,
                'observation' => $report->observation,
                'reported_at' => $report->reported_at
                                    ? Carbon::parse($report->reported_at)->format('F d, Y')
                                    : '-',
                'is_ticketed' => (bool) $report->is_ticketed,
                // ✅ Convert stored relative path → full public URL for the frontend.
                //    Returns null if no image was attached to this report.
                'image_url'   => $report->image_path
                                    ? asset('storage/' . $report->image_path)
                                    : null,
            ],
        ]);
    }
}