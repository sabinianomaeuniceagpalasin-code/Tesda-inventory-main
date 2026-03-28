<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\Item;
use App\Models\DamageReport;

class DamageReportController extends Controller
{
    // =========================================================
    //  TABLE — Fetch damage report rows for the Blade partial.
    //  Includes image_path so the view can render thumbnails.
    //  Only shows reports where the item is still "Damaged" and
    //  the report has NOT yet been converted to a maintenance
    //  ticket (is_ticketed = 0 or NULL).
    // =========================================================
    public function table()
    {
        $damageReports = DB::table('damagereports as d')
            ->leftJoin('items as i', 'i.serial_no', '=', 'd.serial_no')
            ->where('i.status', 'Damaged')                          // ✅ only items still marked Damaged
            ->where(function ($q) {
                $q->whereNull('d.is_ticketed')                      // ✅ treat NULL the same as 0
                  ->orWhere('d.is_ticketed', 0);
            })
            ->select(
                'd.damage_id',
                'd.serial_no',
                'd.observation',
                'd.reported_at',
                'd.image_path',                                     // ✅ needed to render thumbnail
                'i.item_name'                                       // ✅ human-readable item label
            )
            ->orderByDesc('d.reported_at')
            ->get();

        return view('partials.damage_rows', compact('damageReports'));
    }


    // =========================================================
    //  STORE — Create a new damage report with an optional
    //  image attachment.
    //
    //  Expected multipart/form-data fields:
    //    serial_no   (required) — item being reported
    //    observation (optional) — description of the damage
    //    image       (optional) — JPEG / PNG / WebP ≤ 5 MB
    //
    //  The uploaded file is saved to:
    //    storage/app/public/damage_images/<hash>.<ext>
    //  so it is publicly accessible via:
    //    /storage/damage_images/<hash>.<ext>
    //  (run `php artisan storage:link` once if not already done)
    // =========================================================
    public function store(Request $request)
    {
        $request->validate([
            'serial_no'   => 'required|string|exists:items,serial_no',
            'observation' => 'nullable|string|max:1000',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // ✅ max 5 MB
        ]);

        $serialNo = trim($request->serial_no);

        // ── Resolve item ─────────────────────────────────────
        $item = Item::where('serial_no', $serialNo)->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found.',
            ], 404);
        }

        // ── Guard: already damaged ────────────────────────────
        if ($item->status === 'Damaged') {
            return response()->json([
                'success' => false,
                'message' => 'This item is already marked as damaged.',
            ], 422);
        }

        // ── Handle optional image upload ──────────────────────
        // Store under storage/app/public/damage_images so that
        // asset('storage/' . $imagePath) resolves correctly.
        $imagePath = null;
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $imagePath = $request->file('image')->store('damage_images', 'public'); // ✅ returns relative path
        }

        DB::beginTransaction();

        try {
            // 1) Mark item as Damaged
            $item->status     = 'Damaged';
            $item->updated_at = now();
            $item->save();

            // 2) Insert damage report row
            //    image_path is NULL when no file was uploaded — that is fine.
            $damageId = DB::table('damagereports')->insertGetId([
                'serial_no'   => $item->serial_no,
                'observation' => $request->input('observation') ?? null,
                'reported_by' => auth()->id(),                      // ✅ track who filed the report
                'reported_at' => now(),
                'image_path'  => $imagePath,                        // ✅ persisted relative path (nullable)
                'is_ticketed' => 0,                                 // ✅ not yet sent to maintenance
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            // 3) Notify Admin + Property Custodian users
            $notifId = DB::table('notifications')->insertGetId([
                'type'               => 'damage_report',
                'title'              => 'Damage Report Filed',
                'message'            => "{$item->item_name} ({$item->serial_no}) was reported as damaged.",
                'severity'           => 'warning',
                'entity_type'        => 'damage_report',
                'entity_id'          => $damageId,
                'action_url'         => route('dashboard', ['section' => 'damaged']),
                'data'               => json_encode([
                    'serial_no'   => $item->serial_no,
                    'item_name'   => $item->item_name,
                    'observation' => $request->input('observation'),
                    'image_url'   => $imagePath
                                        ? asset('storage/' . $imagePath) // ✅ full public URL in notification payload
                                        : null,
                ]),
                'created_by_user_id' => auth()->id(),
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

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
                'message' => 'Damage report filed successfully.',
                'damage'  => [
                    'damage_id'   => $damageId,
                    'serial_no'   => $item->serial_no,
                    'item_name'   => $item->item_name,
                    'reported_at' => now()->format('F d, Y'),
                    'image_url'   => $imagePath
                                        ? asset('storage/' . $imagePath) // ✅ return public URL to frontend
                                        : null,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            // ✅ Clean up the uploaded file if the DB transaction failed
            //    so we don't leave orphaned files on disk.
            if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to file damage report: ' . $e->getMessage(),
            ], 500);
        }
    }


    // =========================================================
    //  SHOW — Return a single damage report (JSON).
    //  Useful for populating a "View Details" modal on the
    //  frontend without a full page reload.
    //  Returns image_url as a full public URL so the frontend
    //  can render the attached photo directly.
    // =========================================================
    public function show($damageId)
    {
        $report = DB::table('damagereports as d')
            ->leftJoin('items as i', 'i.serial_no', '=', 'd.serial_no')
            ->leftJoin('users as u', 'u.user_id', '=', 'd.reported_by')
            ->where('d.damage_id', $damageId)
            ->select(
                'd.damage_id',
                'd.serial_no',
                'd.observation',
                'd.reported_at',
                'd.image_path',                                     // ✅ raw relative path from DB
                'd.is_ticketed',
                'i.item_name',
                DB::raw("COALESCE(CONCAT(u.first_name,' ',u.last_name),'Unknown') as reported_by_name")
            )
            ->first();

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Damage report not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'report'  => [
                'damage_id'        => $report->damage_id,
                'serial_no'        => $report->serial_no,
                'item_name'        => $report->item_name,
                'observation'      => $report->observation,
                'reported_at'      => $report->reported_at
                                        ? Carbon::parse($report->reported_at)->format('F d, Y')
                                        : '-',
                'reported_by'      => $report->reported_by_name,
                'is_ticketed'      => (bool) $report->is_ticketed,
                // ✅ Convert relative DB path → full public URL.
                //    Returns null if no image was attached.
                'image_url'        => $report->image_path
                                        ? asset('storage/' . $report->image_path)
                                        : null,
            ],
        ]);
    }


    // =========================================================
    //  UPDATE IMAGE — Replace (or attach for the first time)
    //  an image on an existing damage report.
    //  Deletes the old file from disk before saving the new one
    //  so we don't accumulate stale files in storage.
    // =========================================================
    public function updateImage(Request $request, $damageId)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // ✅ max 5 MB
        ]);

        $report = DB::table('damagereports')->where('damage_id', $damageId)->first();

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Damage report not found.',
            ], 404);
        }

        // ✅ Delete the old image from disk before uploading the replacement
        if ($report->image_path && Storage::disk('public')->exists($report->image_path)) {
            Storage::disk('public')->delete($report->image_path);
        }

        // ── Upload new image ──────────────────────────────────
        $newImagePath = $request->file('image')->store('damage_images', 'public');

        DB::table('damagereports')
            ->where('damage_id', $damageId)
            ->update([
                'image_path' => $newImagePath,                      // ✅ persist updated path
                'updated_at' => now(),
            ]);

        return response()->json([
            'success'   => true,
            'message'   => 'Damage report image updated.',
            'image_url' => asset('storage/' . $newImagePath),       // ✅ return new public URL
        ]);
    }


    // =========================================================
    //  DESTROY — Soft-delete by removing the image file and
    //  clearing image_path, but keep the DB row for audit trail.
    //  Use a hard delete only if you have no audit requirements.
    // =========================================================
    public function destroy($damageId)
    {
        $report = DB::table('damagereports')->where('damage_id', $damageId)->first();

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Damage report not found.',
            ], 404);
        }

        // ✅ Remove the attached image from disk to free storage
        if ($report->image_path && Storage::disk('public')->exists($report->image_path)) {
            Storage::disk('public')->delete($report->image_path);
        }

        // Hard-delete the DB row.
        // Swap with a soft-delete (update deleted_at) if you want an audit trail.
        DB::table('damagereports')->where('damage_id', $damageId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Damage report deleted.',
        ]);
    }
}