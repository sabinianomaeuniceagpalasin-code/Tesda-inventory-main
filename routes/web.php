<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\IssuedLogController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\PredictiveAnalyticsController;
use App\Http\Controllers\InventorySettingsController;
use App\Http\Controllers\IssuedReturnController;
use App\Http\Controllers\IssuedUnserviceableController;
use App\Http\Controllers\IssuedDamageController;
use App\Http\Controllers\SerialController;
use App\Http\Controllers\ItemApprovalRequestController;
use App\Http\Controllers\FormRecordsItemScanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DamageReportController;
use App\Http\Controllers\NotificationController; // ✅ added
use App\Http\Controllers\LockController;
use App\Http\Controllers\ItemMissingController;

Route::get('/', fn() => redirect()->route('login'));

Route::get('/serials/next/{qty}', [SerialController::class, 'getNextSerials']);

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/check-email', [AuthController::class, 'checkEmail']);

Route::get('/create-account', [AuthController::class, 'showCreateAccount'])->name('create.account');
Route::post('/create-account', [AuthController::class, 'register'])->name('register');

Route::get('/verify', [AuthController::class, 'showVerifyCodeForm'])->name('verify.form');
Route::post('/verify', [AuthController::class, 'verifyCode'])->name('verify.code');

Route::get('/waiting', [AuthController::class, 'waitingPage'])->name('waiting.page');

Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetCode'])->name('password.email');

Route::get('/forgot-verify', [AuthController::class, 'showForgotVerifyForm'])->name('password.verify.form');
Route::post('/forgot-verify', [AuthController::class, 'verifyResetCode'])->name('password.verify');

Route::get('/reset-password', [AuthController::class, 'showResetPasswordForm'])->name('password.reset.form');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
Route::get('/reset-success', [AuthController::class, 'showResetSuccess'])->name('password.success');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {

    // =========================
    // PROFILE
    // =========================
    Route::get('/profile-settings', [ProfileController::class, 'edit'])->name('profile-settings');
    Route::post('/profile-settings', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/login-history', [ProfileController::class, 'loginHistory'])->name('login-history');

    // IDLE LOCK 
    Route::get('/lock', [LockController::class, 'show'])->name('lock.screen');
    Route::post('/unlock', [LockController::class, 'unlock'])->name('unlock');

    Route::post('/unlock-screen', [App\Http\Controllers\LockController::class, 'unlockScreen'])
        ->name('unlock.screen');

    //Missing
    Route::post('/items/missing', [ItemMissingController::class, 'markMissing'])->name('items.missing');

    //EDIT ITEMS INVENTORY
    Route::post('/inventory/update-specifications', [InventoryController::class, 'updateSpecifications'])
    ->name('inventory.updateSpecifications');
    Route::post('/inventory/update-source-of-fund', [InventoryController::class, 'updateSourceOfFund']);
    Route::post('/inventory/update-classification', [InventoryController::class, 'updateClassification']);
    Route::post('/inventory/update-unit-cost', [InventoryController::class, 'updateUnitCost']);
         


    // =========================
    // USER APPROVAL
    // =========================
        Route::post('/users/{user_id}/approve', [InventorySettingsController::class, 'approve'])
            ->name('user.approve');

        Route::post('/users/{user_id}/reject', [InventorySettingsController::class, 'reject'])
            ->name('user.reject');
            
    // =========================
    // HTML TABLE RELOAD ROUTES (AJAX)
    // =========================
    Route::get('/dashboard/issued/table-html', [DashboardController::class, 'issuedTableHtml']);
    Route::get('/dashboard/maintenance/table-html', [DashboardController::class, 'maintenanceTableHtml']);
    Route::get('/dashboard/damage/table-html', [DashboardController::class, 'damageTableHtml']);

    // =========================
    // ISSUED / FORMS
    // =========================
    Route::get('/issued/search-students', [IssuedLogController::class, 'searchStudents']);
    Route::get('/issued/available-serials', [IssuedLogController::class, 'availableSerials']);
    Route::get('/issued/check-ref/{reference}', [IssuedLogController::class, 'checkReference']);
    Route::post('/issued/store', [IssuedLogController::class, 'store'])->name('issued.store');

    Route::patch('/forms/{id}/update-status', [DashboardController::class, 'updateStatus'])->name('forms.updateStatus');

    Route::post('/issued/return/{id}', [IssuedReturnController::class, 'returnItem'])->name('issued.return');
    Route::post('/issued/unserviceable/{id}', [IssuedUnserviceableController::class, 'markUnserviceable'])->name('issued.unserviceable');

    // =========================
    // CHATBOT
    // =========================
    Route::post('/chatbot/message', [ChatbotController::class, 'chat'])->name('chatbot.message');
    Route::get('/chatbot/suggestions', [ChatbotController::class, 'suggestions']);

    // =========================
    // DAMAGE / MAINTENANCE TICKETS FROM DAMAGE
    // =========================
    Route::post('/damage/move/{damage_id}', [DashboardController::class, 'moveDamageToMaintenance'])->name('damage.move');

    Route::post('/damage-reports/{damageId}/ticket', [MaintenanceController::class, 'createTicketFromDamage'])
        ->name('damage.ticket');

    Route::post('/damage-reports/store', [IssuedDamageController::class, 'store']);

    // JSON endpoint (OK to keep) — just don't use it for table reload
    Route::get('/damage-reports/{serialNo}', [IssuedDamageController::class, 'showBySerial']);

    // =========================
    // SCANNER
    // =========================
    Route::post('/items/scan/validate', [InventoryController::class, 'validateScan']);
    Route::post('/items/receive-batch', [InventoryController::class, 'receiveBatch']);
    Route::get('/items/scan', [FormRecordsItemScanController::class, 'scan'])->name('items.scan');

    // =========================
    // NOTIFICATIONS
    // =========================
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
Route::post('/notifications/{recipientId}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');

    // =========================
    // ITEM APPROVAL
    // =========================
    Route::post('/item-approval/request', [ItemApprovalRequestController::class, 'store'])->name('item-approval.request');
    Route::post('/item-approval/{request_id}/approve', [InventorySettingsController::class, 'approveItem'])->name('item.approve');
    Route::post('/item-approval/{request_id}/reject', [InventorySettingsController::class, 'rejectItem'])->name('item.reject');

    Route::post('/item-approval/batch/{batch_id}/approve', [InventorySettingsController::class, 'approveBatch'])
    ->name('item.batch.approve');

    Route::post('/item-approval/batch/{batch_id}/reject', [InventorySettingsController::class, 'rejectBatch'])
        ->name('item.batch.reject');

        Route::put('/inventory/update/{serial_no}', [InventoryController::class, 'update'])->name('inventory.update');
        Route::delete('/inventory/{serial_no}', [InventoryController::class, 'destroy'])->name('inventory.destroy');


    // ==========================================================
    // ✅ MAINTENANCE ROUTES FOR ADMIN + PROPERTY CUSTODIAN
    // (Moved out of Admin-only so Property Custodian won't get 404)
    // ==========================================================
    Route::middleware(['role:Admin,Property Custodian'])->group(function () {

        Route::post('/maintenance/store', [DashboardController::class, 'storeMaintenance'])->name('maintenance.store');

        Route::get('/maintenance/latest-damage/{serialNo}', [DashboardController::class, 'getLatestDamageReport'])
            ->name('maintenance.latestDamage');

        Route::get('/maintenance/{id}', [DashboardController::class, 'showMaintenance'])->name('maintenance.show');

        Route::put('/maintenance/{id}/update', [DashboardController::class, 'updateMaintenance'])
            ->name('maintenance.update');

        Route::delete('/maintenance/{id}/delete', [DashboardController::class, 'destroyMaintenance'])
            ->name('maintenance.delete');

        Route::get('/maintenance/records', [DashboardController::class, 'getMaintenanceRecords'])
            ->name('maintenance.records');

        Route::post('/maintenance/report/{serial_no}', [DashboardController::class, 'report']);
        Route::post('/maintenance/make-available/{serial}', [DashboardController::class, 'makeAvailable']);
        Route::get('/maintenance/history/{serial}', [DashboardController::class, 'getMaintenanceHistory']);

        // Optional admin-only analytics in its own group below
    });

    // =========================
    // ADMIN ONLY
    // =========================
    Route::middleware(['role:Admin'])->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/dashboard/summary/{type}', [DashboardController::class, 'getDashboardSummary']);
        Route::get('/dashboard/inventory', [DashboardController::class, 'inventory'])->name('dashboard.inventory');
        Route::get('/dashboard/reports', [DashboardController::class, 'reports'])->name('dashboard.reports');
        Route::get('/dashboard/settings', [DashboardController::class, 'settings'])->name('dashboard.settings');
        Route::get('/dashboard/forms', [IssuedLogController::class, 'indexForms'])->name('dashboard.forms');

        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('/inventory/settings', [InventorySettingsController::class, 'index'])->name('inventory.settings');
        Route::post('/inventory/store', [InventoryController::class, 'store'])->name('inventory.store');

        Route::get('/inventory/get-tool/{tool_name}', [InventoryController::class, 'getTool']);
        Route::get('/check-property-no/{property_no}', [InventoryController::class, 'checkPropertyNo']);

        Route::post('/inventory/settings/lifespan/update', [InventorySettingsController::class, 'updateLifespan'])
            ->name('inventory.settings.lifespan.update');

        Route::post('/inventory/settings/lifespan/delete', [InventorySettingsController::class, 'deleteLifespan'])
            ->name('inventory.settings.lifespan.delete');

        Route::post('/inventory/settings/classification/update', [InventorySettingsController::class, 'updateClassification'])
            ->name('inventory.settings.classification.update');

        Route::post('/inventory/settings/classification/delete', [InventorySettingsController::class, 'deleteClassification'])
            ->name('inventory.settings.classification.delete');

        Route::get('/check-serial-no/{serial_no}', function ($serial_no) {
            $exists = DB::table('tools')->where('serial_no', $serial_no)->exists();
            return response()->json(['exists' => $exists]);
        });

        Route::get('/damage-reports', [DashboardController::class, 'getDamageReports'])->name('damage.index');

        Route::get('/analytics/generate', [PredictiveAnalyticsController::class, 'generate'])->name('analytics.generate');

        Route::get('/chatbot/templates', [ChatbotController::class, 'templates']);
        Route::post('/chatbot/execute/{id}', [ChatbotController::class, 'execute']);
    });

    Route::post('/inventory/settings/source-of-fund/update', [InventorySettingsController::class, 'updateSourceOfFund'])
        ->name('inventory.settings.source-of-fund.update');

    Route::post('/inventory/settings/source-of-fund/delete', [InventorySettingsController::class, 'deleteSourceOfFund'])
        ->name('inventory.settings.source-of-fund.delete');

    // =========================
    // PROPERTY CUSTODIAN + USER + REGULAR EMPLOYEE
    // =========================
    Route::middleware(['role:Property Custodian,User,Regular Employee'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/forms', [IssuedLogController::class, 'indexForms'])->name('dashboard.forms');
        Route::get('/form-records', [IssuedLogController::class, 'indexForms'])->name('form.records');
        Route::get('/issued/view/{reference_no}', [IssuedLogController::class, 'view']);
    });

    // =========================
    // DASHBOARD AJAX JSON (existing)
    // =========================
    Route::get('/dashboard/issued/items-table', [DashboardController::class, 'getIssuedItemsTable'])->name('dashboard.issued.table');
    Route::get('/dashboard/inventory/table', [DashboardController::class, 'getInventoryTable'])->name('dashboard.inventory.table');
    Route::get('/dashboard/form/table', [DashboardController::class, 'getFormTable'])->name('dashboard.form.table');

    Route::get('/dashboard/get-total-items-and-equipment', [DashboardController::class, 'getListOfAllItemsTable']);
    Route::get('/dashboard/get-available-items', [DashboardController::class, 'getListofAllAvailableItemsTable']);
    Route::get('/dashboard/get-issued-items', [DashboardController::class, 'getListofIssuedItemsTable']);

    // ⚠️ This returns array, not HTML. Keep if used, but don't inject directly into table.
    Route::get('/dashboard/get-under-maintenance', [DashboardController::class, 'getUnderMaintenanceItemsTable']);
    Route::get('/dashboard/get-missing-items', [DashboardController::class, 'getMissingItemsTable']);
    Route::get('/dashboard/get-low-stock-items', [DashboardController::class, 'getLowStockItems']);
    Route::get('/dashboard/get-unserviceable-items', [DashboardController::class, 'getUnserviceableItemsTable']);
    Route::get('/dashboard/items', [DashboardController::class, 'items']);
});

Route::get('/debug-role', function () {
    return auth()->user()->role;
});