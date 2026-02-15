<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\IssuedLogController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\PredictiveAnalyticsController;
use App\Http\Controllers\InventorySettingsController;
use App\Http\Controllers\IssuedReturnController;
use App\Http\Controllers\IssuedUnserviceableController;
use App\Http\Controllers\IssuedDamageController;
use App\Http\Controllers\SerialController;
use App\Http\Controllers\ItemApprovalRequestController;


// --------------------
// ROOT REDIRECT
// --------------------
Route::get('/', fn() => redirect()->route('login'));

// Fetch next N unused serials
Route::get('/serials/next/{qty}', [SerialController::class, 'getNextSerials']);

// --------------------
// AUTH ROUTES
// --------------------
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/check-email', [AuthController::class, 'checkEmail']);

Route::get('/create-account', [AuthController::class, 'showCreateAccount'])->name('create.account');
Route::post('/create-account', [AuthController::class, 'register'])->name('register');

Route::get('/verify', [AuthController::class, 'showVerifyCodeForm'])->name('verify.form');
Route::post('/verify', [AuthController::class, 'verifyCode'])->name('verify.code');

Route::get('/waiting', [AuthController::class, 'waitingPage'])->name('waiting.page');

// --------------------
// PASSWORD RESET FLOW
// --------------------
Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetCode'])->name('password.email');

Route::get('/forgot-verify', [AuthController::class, 'showForgotVerifyForm'])->name('password.verify.form');
Route::post('/forgot-verify', [AuthController::class, 'verifyResetCode'])->name('password.verify');

Route::get('/reset-password', [AuthController::class, 'showResetPasswordForm'])->name('password.reset.form');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
Route::get('/reset-success', [AuthController::class, 'showResetSuccess'])->name('password.success');

// --------------------
// LOGOUT
// --------------------
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// --------------------
// PROTECTED ROUTES (Authenticated users)
// --------------------
Route::middleware(['auth'])->group(function () {

    // --------------------
    // COMMON ROUTES (All users)
    // --------------------
    Route::get('/issued/search-students', [IssuedLogController::class, 'searchStudents']);
    Route::get('/issued/available-serials', [IssuedLogController::class, 'availableSerials']);
    Route::get('/issued/check-ref/{reference}', [IssuedLogController::class, 'checkReference']);
    Route::post('/issued/store', [IssuedLogController::class, 'store'])->name('issued.store');
    Route::patch('/forms/{id}/update-status', [DashboardController::class, 'updateStatus'])->name('forms.updateStatus');
    Route::post('/issued/return/{id}', [IssuedReturnController::class, 'returnItem'])->name('issued.return');
    Route::get('/maintenance/show/{id}', [MaintenanceController::class, 'show']);
    Route::post('/chatbot/message', [ChatbotController::class, 'chat'])->name('chatbot.message');

    /* ✅ ADD THIS HERE */
        Route::post(
            '/item-approval/request',
            [ItemApprovalRequestController::class, 'store']
        )->name('item-approval.request');

    // --------------------
    // ADMIN ROUTES
    // --------------------
    Route::middleware(['role:Admin'])->group(function () {

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        //Item Approval

        Route::post('/item-approval/{request_id}/approve',[InventorySettingsController::class, 'approveItem']
            )->name('item.approve');

            Route::post('/item-approval/{request_id}/reject',[InventorySettingsController::class, 'rejectItem']
            )->name('item.reject');

        // Subpages
        Route::get('/dashboard/summary/{type}', [DashboardController::class, 'getDashboardSummary']);
        Route::get('/dashboard/inventory', [DashboardController::class, 'inventory'])->name('dashboard.inventory');
        Route::get('/dashboard/reports', [DashboardController::class, 'reports'])->name('dashboard.reports');
        Route::get('/dashboard/settings', [DashboardController::class, 'settings'])->name('dashboard.settings');
        Route::get('/dashboard/forms', [IssuedLogController::class, 'indexForms'])->name('dashboard.forms');

        // Inventory management
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('/inventory/settings', [InventorySettingsController::class, 'index'])->name('inventory.settings');
        Route::post('/inventory/store', [InventoryController::class, 'store'])->name('inventory.store');
        Route::get('/inventory/get-tool/{tool_name}', [InventoryController::class, 'getTool']);
        Route::get('/check-property-no/{property_no}', [InventoryController::class, 'checkPropertyNo']);
        Route::get('/check-serial-no/{serial_no}', function ($serial_no) {
            $exists = DB::table('tools')->where('serial_no', $serial_no)->exists();
            return response()->json(['exists' => $exists]);
        });

        // Damage Reports (moved to DashboardController)
        Route::get('/damage-reports', [DashboardController::class, 'getDamageReports'])->name('damage.index');
        Route::post('/damage-reports/store', [DashboardController::class, 'storeDamageReport'])->name('damage.store');
        Route::post('/damage/move/{id}', [DashboardController::class, 'moveDamageToMaintenance']);
        // Route::post('/damage-reports/update/{id}', [DashboardController::class, 'updateDamageReport'])->name('damage.update');
        // Route::post('/damage-reports/delete/{id}', [DashboardController::class, 'deleteDamageReport'])->name('damage.delete');

        // ---------- Maintenance Routes ----------

        // Store a new maintenance record
        Route::post('/maintenance/store', [DashboardController::class, 'storeMaintenance'])->name('maintenance.store');
        Route::get('/maintenance/latest-damage/{serialNo}', [DashboardController::class, 'getLatestDamageReport'])->name('maintenance.latestDamage');
        Route::get('/maintenance/{id}', [DashboardController::class, 'showMaintenance'])->name('maintenance.show');
        Route::put('/maintenance/{id}/update', [DashboardController::class, 'updateMaintenance'])->name('maintenance.update');
        Route::delete('/maintenance/{id}/delete', [DashboardController::class, 'destroyMaintenance'])->name('maintenance.delete');
        Route::get('/maintenance/records', [DashboardController::class, 'getMaintenanceRecords'])->name('maintenance.records');
        Route::get('/maintenance/latest-damage/{serialNo}', [DashboardController::class, 'getLatestDamageReport'])
            ->name('maintenance.latestDamage');
        Route::post('/maintenance/report/{serial_no}', [DashboardController::class, 'report']);
        Route::post('/maintenance/make-available/{serial}', [DashboardController::class, 'makeAvailable']);
        Route::get('/maintenance/history/{serial}', [DashboardController::class, 'getMaintenanceHistory']);

        // Analytics
        Route::get('/analytics/generate', [PredictiveAnalyticsController::class, 'generate'])->name('analytics.generate');

        // Chatbot
        Route::get('/chatbot/templates', [ChatbotController::class, 'templates']);
        Route::post('/chatbot/execute/{id}', [ChatbotController::class, 'execute']);
    });

    // --------------------
    // NON-ADMIN ROUTES
    // --------------------
    Route::middleware(['role:Property Custodian,User,Regular Employee'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/forms', [IssuedLogController::class, 'indexForms'])->name('dashboard.forms');
        Route::get('/form-records', [IssuedLogController::class, 'indexForms'])->name('form.records');
        Route::get('/issued/view/{reference_no}', [IssuedLogController::class, 'view']);
    });

    // --------------------
    // ISSUED LOG / INVENTORY TABLES (AJAX)
    // --------------------
    Route::get('/dashboard/issued/items-table', [DashboardController::class, 'getIssuedItemsTable'])
        ->name('dashboard.issued.table');
    Route::get('/dashboard/inventory/table', [DashboardController::class, 'getInventoryTable'])
        ->name('dashboard.inventory.table');
    Route::get('/dashboard/form/table', [DashboardController::class, 'getFormTable'])
        ->name('dashboard.form.table');
    Route::get('/dashboard/get-total-items-and-equipment', [DashboardController::class, 'getListOfAllItemsTable']);
    Route::get('/dashboard/get-available-items', [DashboardController::class, 'getListofAllAvailableItemsTable']);
    Route::get('/dashboard/get-issued-items', [DashboardController::class, 'getListofIssuedItemsTable']);
    Route::get(
        '/dashboard/get-under-maintenance',
        [DashboardController::class, 'getUnderMaintenanceListTable']
    );
    Route::get('/dashboard/get-low-stock-items', [DashboardController::class, 'getLowStockItems']);
    Route::get('/dashboard/get-missing-items', [DashboardController::class, 'getMissingItems']);
    Route::get('/dashboard/items', [DashboardController::class, 'items']);

    // --------------------
    // UNSERVICEABLE ITEMS
    // --------------------
    Route::post('/issued/unserviceable/{id}', [IssuedUnserviceableController::class, 'markUnserviceable'])
        ->name('issued.unserviceable');

    // --------------------
    // USER APPROVAL
    // --------------------
    Route::post('/user/approve/{id}', [InventorySettingsController::class, 'approve'])->name('user.approve');
    Route::post('/user/reject/{id}', [InventorySettingsController::class, 'reject'])->name('user.reject');
});

// --------------------
// DEBUG ROLE
// --------------------
Route::get('/debug-role', function () {
    return auth()->user()->role;
});
