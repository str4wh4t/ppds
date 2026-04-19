<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
});

Route::middleware('auth:sanctum')->group(function () {

    // Canonical API route (versioned).
    Route::prefix('v1')->group(function () {
        Route::post('/activities/{user}/calendar-generatedays', [\App\Http\Controllers\Api\V1\ActivityController::class, 'calendarGenerateDays'])->name('activities.calendar-generatedays');

        Route::post('/activities/list', [\App\Http\Controllers\Api\V1\ActivityController::class, 'list'])->name('activity.list');

        Route::get('/user', [AuthController::class, 'profile'])->name('user');
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('/activities', [\App\Http\Controllers\Api\V1\ActivityController::class, 'store'])->name('activity.store');
        Route::post('/activities/checkin', [\App\Http\Controllers\Api\V1\ActivityController::class, 'checkIn'])->name('activity.checkin');
        Route::post('/activities/{activity}/checkout', [\App\Http\Controllers\Api\V1\ActivityController::class, 'checkOut'])->name('activity.checkout');
        Route::delete('/activities/{activity}', [\App\Http\Controllers\Api\V1\ActivityController::class, 'destroy'])->name('activity.destroy-api');
        Route::post('/stases', [\App\Http\Controllers\Api\V1\StaseController::class, 'index'])->name('stase.index');
        Route::post('/stases/by-unit', [\App\Http\Controllers\Api\V1\StaseController::class, 'byUnit'])->name('stase.by-unit');
        Route::post('/stases/locations', [\App\Http\Controllers\Api\V1\StaseController::class, 'locations'])->name('stase.locations');
        Route::post('/locations', [\App\Http\Controllers\Api\V1\LocationController::class, 'index'])->name('location.index');
        Route::post('/dosens', [\App\Http\Controllers\Api\V1\DosenController::class, 'index'])->name('dosen.index');
        Route::post('/week-monitors', [\App\Http\Controllers\Api\V1\WeekMonitorController::class, 'index'])->name('weekmonitor.index');
        Route::post('/week-monitors/by-week', [\App\Http\Controllers\Api\V1\WeekMonitorController::class, 'byWeek'])->name('weekmonitor.by-week');
    });
});
