<?php

use App\Http\Controllers\Api\ApprovalController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BidangController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\OfficeController;
use App\Http\Controllers\Api\SettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| RESTful API Routes (Modern Laravel Structure)
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::match(['GET', 'POST'], '/login', [AuthController::class, 'login'])->name('api.auth.login');
});

Route::match(['GET', 'POST'], '/update-profile', [AuthController::class, 'updateProfile'])->name('api.profile.update');

Route::prefix('attendance')->group(function () {
    Route::get('/today', [AttendanceController::class, 'today'])->name('api.attendance.today');
    Route::post('/record', [AttendanceController::class, 'record'])->name('api.attendance.record');
    Route::get('/history', [AttendanceController::class, 'history'])->name('api.attendance.history');
    Route::get('/recap', [AttendanceController::class, 'recap'])->name('api.attendance.recap');
});

Route::prefix('approvals')->group(function () {
    Route::get('/', [ApprovalController::class, 'list'])->name('api.approvals.list');
    Route::post('/submit', [ApprovalController::class, 'submit'])->name('api.approvals.submit');
    Route::post('/review', [ApprovalController::class, 'review'])->name('api.approvals.review');
});

Route::prefix('employees')->group(function () {
    Route::get('/', [EmployeeController::class, 'list'])->name('api.employees.list');
    Route::post('/', [EmployeeController::class, 'store'])->name('api.employees.store');
    Route::match(['PUT', 'POST'], '/{id}', [EmployeeController::class, 'update'])->name('api.employees.update');
    Route::delete('/{id}', [EmployeeController::class, 'destroy'])->name('api.employees.destroy');
});

Route::prefix('offices')->group(function () {
    Route::get('/', [OfficeController::class, 'list'])->name('api.offices.list');
    Route::post('/', [OfficeController::class, 'store'])->name('api.offices.store');
    Route::delete('/{id}', [OfficeController::class, 'destroy'])->name('api.offices.destroy');
    Route::post('/{id}/primary', [OfficeController::class, 'setPrimary'])->name('api.offices.primary');
});

Route::prefix('bidang')->group(function () {
    Route::get('/', [BidangController::class, 'list'])->name('api.bidang.list');
});

Route::prefix('settings')->group(function () {
    Route::get('/', [SettingController::class, 'get'])->name('api.settings.get');
    Route::post('/operational', [SettingController::class, 'saveOperational'])->name('api.settings.operational');
    Route::post('/geofencing', [SettingController::class, 'saveGeofencing'])->name('api.settings.geofencing');
});

Route::match(['GET', 'POST'], '/resolve-maps', [SettingController::class, 'resolveMaps'])->name('api.maps.resolve');

/*
|--------------------------------------------------------------------------
| Legacy Compatibility API Routes (Mendukung endpoint *.php lama)
|--------------------------------------------------------------------------
*/
Route::match(['GET', 'POST'], '/login.php', [AuthController::class, 'login']);
Route::match(['GET', 'POST'], '/attendance.php', [AttendanceController::class, 'legacyRouter']);
Route::match(['GET', 'POST'], '/approval.php', [ApprovalController::class, 'legacyRouter']);
Route::match(['GET', 'POST'], '/employees.php', [EmployeeController::class, 'legacyRouter']);
Route::match(['GET', 'POST'], '/offices.php', [OfficeController::class, 'legacyRouter']);
Route::match(['GET', 'POST'], '/bidang.php', [BidangController::class, 'legacyRouter']);
Route::match(['GET', 'POST'], '/settings.php', [SettingController::class, 'legacyRouter']);
Route::match(['GET', 'POST'], '/update_profile.php', [AuthController::class, 'updateProfile']);
Route::match(['GET', 'POST'], '/resolve_maps.php', [SettingController::class, 'resolveMaps']);

