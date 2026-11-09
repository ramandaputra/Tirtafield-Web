<?php

use App\Http\Controllers\Api\ApprovalController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BidangController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\OfficeController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Web\WebDashboardController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (Dashboard & Portal Tirtafield)
|--------------------------------------------------------------------------
*/

Route::get('/manifest.json', function () {
    $path = public_path('manifest.json');
    if (!file_exists($path)) abort(404);
    return response(file_get_contents($path), 200, [
        'Content-Type' => 'application/json'
    ]);
});

Route::get('/sw.js', function () {
    $path = public_path('sw.js');
    if (!file_exists($path)) abort(404);
    return response(file_get_contents($path), 200, [
        'Content-Type' => 'application/javascript'
    ]);
});

Route::get('/', [WebDashboardController::class, 'splash'])->name('splash');
Route::get('/splash', [WebDashboardController::class, 'splash'])->name('web.splash');
Route::get('/login', [WebDashboardController::class, 'showLogin'])->name('login');
Route::get('/portal-login', [WebDashboardController::class, 'showLogin'])->name('web.login');
Route::post('/login', [WebDashboardController::class, 'doLogin'])->name('web.login.submit');
Route::post('/logout', [WebDashboardController::class, 'doLogout'])->name('web.logout');
Route::get('/switch-role/{role}', [WebDashboardController::class, 'switchRole'])->name('web.switchRole');

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/dashboard', [WebDashboardController::class, 'dashboard'])->name('web.dashboard');
    Route::get('/employees', [WebDashboardController::class, 'employees'])->name('web.employees');
    Route::get('/approvals', [WebDashboardController::class, 'approvals'])->name('web.approvals');
    Route::get('/settings', [WebDashboardController::class, 'settings'])->name('web.settings');
});

/*
|--------------------------------------------------------------------------
| Dual-compatibility routes in Web context (jika diakses tanpa prefix /api)
|--------------------------------------------------------------------------
*/
Route::prefix('api')->group(function () {
    Route::match(['GET', 'POST'], '/login.php', [AuthController::class, 'login']);
    Route::match(['GET', 'POST'], '/attendance.php', [AttendanceController::class, 'legacyRouter']);
    Route::match(['GET', 'POST'], '/approval.php', [ApprovalController::class, 'legacyRouter']);
    Route::match(['GET', 'POST'], '/employees.php', [EmployeeController::class, 'legacyRouter']);
    Route::match(['GET', 'POST'], '/offices.php', [OfficeController::class, 'legacyRouter']);
    Route::match(['GET', 'POST'], '/bidang.php', [BidangController::class, 'legacyRouter']);
    Route::match(['GET', 'POST'], '/settings.php', [SettingController::class, 'legacyRouter']);
    Route::match(['GET', 'POST'], '/update_profile.php', [AuthController::class, 'updateProfile']);
    Route::match(['GET', 'POST'], '/resolve_maps.php', [SettingController::class, 'resolveMaps']);
});

Route::match(['GET', 'POST'], '/login.php', [AuthController::class, 'login']);
Route::match(['GET', 'POST'], '/attendance.php', [AttendanceController::class, 'legacyRouter']);
Route::match(['GET', 'POST'], '/approval.php', [ApprovalController::class, 'legacyRouter']);
Route::match(['GET', 'POST'], '/employees.php', [EmployeeController::class, 'legacyRouter']);
Route::match(['GET', 'POST'], '/offices.php', [OfficeController::class, 'legacyRouter']);
Route::match(['GET', 'POST'], '/bidang.php', [BidangController::class, 'legacyRouter']);
Route::match(['GET', 'POST'], '/settings.php', [SettingController::class, 'legacyRouter']);
Route::match(['GET', 'POST'], '/update_profile.php', [AuthController::class, 'updateProfile']);
Route::match(['GET', 'POST'], '/resolve_maps.php', [SettingController::class, 'resolveMaps']);
