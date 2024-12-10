<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {

    // Route::post('/activities/{user}', [\App\Http\Controllers\ActivityController::class, 'getUserActivities'])->name('activities.getuseractivities');
    Route::post('/activities/{user}/calendar-generatedays', [\App\Http\Controllers\ActivityController::class, 'calendarGenerateDays'])->name('activities.calendar-generatedays');
});

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/stases/{stase}/avail-location', [\App\Http\Controllers\StaseController::class, 'getAvailLocation'])->name('stases.avail-location');
});
