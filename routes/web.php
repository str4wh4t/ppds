<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\UnitController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/', function () {
//     return Inertia::render('Welcome', [
//         'canLogin' => Route::has('login'),
//         'canRegister' => Route::has('register'),
//         'laravelVersion' => Application::VERSION,
//         'phpVersion' => PHP_VERSION,
//     ]);
// });

Route::get('/', [AuthenticatedSessionController::class, 'create']);

Route::get('/dashboard', [MainController::class, 'dashboard'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/notifs', [\App\Http\Controllers\NotifController::class, 'index'])->name('notifs.index');
    Route::post('/readguideline', [\App\Http\Controllers\StudentController::class, 'readGuideline'])->name('students.read-guideline');
});

Route::group(['middleware' => ['permission:access-control']], function () {
    Route::resource('/users', \App\Http\Controllers\UserController::class)->except(['destroy']); // << destroy method is moved to the bottom
    Route::resource('/roles', \App\Http\Controllers\RoleController::class);
    Route::resource('/permissions', \App\Http\Controllers\PermissionController::class);
});

Route::group(['middleware' => ['permission:access-control|crud-kaprodi|crud-dosen|crud-mahasiswa']], function () {
    Route::delete('/users/{user}', [\App\Http\Controllers\UserController::class, 'destroy'])->name('users.destroy');
});

Route::group(['middleware' => ['permission:reset-password']], function () {
    Route::patch('/users/{user}/resetpassword', [\App\Http\Controllers\UserController::class, 'resetPassword'])->name('users.reset-password');
});

Route::middleware(['permission:crud-mahasiswa'])->group(function () {
    Route::resource('/students', \App\Http\Controllers\StudentController::class)->parameters([
        'students' => 'user'
    ]);
});

Route::middleware(['permission:crud-dosen'])->group(function () {
    Route::resource('/dosens', \App\Http\Controllers\DosenController::class)->parameters([
        'dosens' => 'user'
    ]);
});

Route::middleware(['permission:crud-kaprodi'])->group(function () {
    Route::resource('/kaprodis', \App\Http\Controllers\KaprodiController::class)->parameters([
        'kaprodis' => 'user'
    ]);
});

Route::middleware(['permission:crud-unit'])->group(function () {
    Route::resource('/units', UnitController::class);
    Route::get('/units/{unit}/stases', [UnitController::class, 'stases'])->name('units.stases');
    Route::put('/units/{unit}/stases', [UnitController::class, 'staseUpdate'])->name('units.stases.update');
    Route::post('/units/{unit}/uploadschedule', [UnitController::class, 'uploadScheduleDocument'])->name('units.upload-document-schedule');
    Route::post('/units/{unit}/uploadguideline', [UnitController::class, 'uploadGuidelineDocument'])->name('units.upload-document-guideline');
    Route::delete('/units/{unit}/deleteschedule', [UnitController::class, 'deleteScheduleDocument'])->name('units.delete-document-schedule');
    Route::delete('/units/{unit}/deleteguideline', [UnitController::class, 'deleteGuidelineDocument'])->name('units.delete-document-guideline');
});


Route::middleware(['permission:crud-stase'])->group(function () {
    Route::resource('/stases', \App\Http\Controllers\StaseController::class);
});

Route::middleware(['permission:crud-logbook'])->group(function () {
    Route::get('/activities/{user}', [\App\Http\Controllers\ActivityController::class, 'index'])->name('activities.index');
    // Route::resource('/activities', \App\Http\Controllers\ActivityController::class)->except(['index']);
    // Menambahkan middleware 'can' untuk aksi update dan delete pada resource activities
    Route::resource('/activities', \App\Http\Controllers\ActivityController::class)
        ->except(['index']);
    Route::get('/activities/{user}/calendar', [\App\Http\Controllers\ActivityController::class, 'calendar'])->name('activities.calendar');
    Route::get('/activities/{user}/report', [\App\Http\Controllers\ActivityController::class, 'report'])->name('activities.report');
    Route::get('/activities/{user}/schedule', [\App\Http\Controllers\ActivityController::class, 'schedule'])->name('activities.schedule');
});

Route::middleware(['permission:report-logbook'])->group(function () {
    Route::get('/activities/{user}/report', [\App\Http\Controllers\ActivityController::class, 'report'])->name('activities.report');
});

Route::middleware(['permission:crud-portofolio'])->group(function () {
    Route::resource('/portofolios', \App\Http\Controllers\PortofolioController::class);
});


require __DIR__ . '/auth.php';
