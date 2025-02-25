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
    Route::get('/notifs/index', [\App\Http\Controllers\NotifController::class, 'index'])->name('notifs.index');
    Route::post('/read-guideline', [\App\Http\Controllers\StudentController::class, 'readGuideline'])->name('students.read-guideline');
});

Route::group(['middleware' => ['permission:access-control']], function () {
    Route::resource('/users', \App\Http\Controllers\UserController::class)->except(['destroy']); // << destroy method is moved to the bottom
    Route::resource('/roles', \App\Http\Controllers\RoleController::class);
    Route::resource('/permissions', \App\Http\Controllers\PermissionController::class);
});

Route::group(['middleware' => ['permission:access-control|kaprodi.*|dosen.*|mahasiswa.*']], function () {
    Route::delete('/users/{user}', [\App\Http\Controllers\UserController::class, 'destroy'])->name('users.destroy');
});

Route::group(['middleware' => ['permission:reset-password']], function () {
    Route::patch('/users/{user}/reset-password', [\App\Http\Controllers\UserController::class, 'resetPassword'])->name('users.reset-password');
});

Route::middleware(['permission:mahasiswa.*'])->group(function () {
    Route::resource('/students', \App\Http\Controllers\StudentController::class)->parameters([
        'students' => 'user'
    ]);
});

Route::middleware(['permission:dosen.*'])->group(function () {
    Route::resource('/dosens', \App\Http\Controllers\DosenController::class)->parameters([
        'dosens' => 'user'
    ]);
});

Route::middleware('permission:dosen.read')->get('/dosens', [\App\Http\Controllers\DosenController::class, 'index'])->name('dosens.index');

Route::middleware(['permission:kaprodi.*'])->group(function () {
    Route::resource('/kaprodis', \App\Http\Controllers\KaprodiController::class)->parameters([
        'kaprodis' => 'user'
    ]);
});

Route::middleware('permission:kaprodi.read')->get('/kaprodis', [\App\Http\Controllers\KaprodiController::class, 'index'])->name('kaprodis.index');

Route::middleware(['permission:unit.*'])->group(function () {
    Route::resource('/units', UnitController::class);
    Route::get('/units/{unit}/stases', [UnitController::class, 'stases'])->name('units.stases');
    Route::put('/units/{unit}/stases', [UnitController::class, 'staseUpdate'])->name('units.stases.update');
    Route::post('/units/{unit}/upload-guideline', [UnitController::class, 'uploadGuidelineDocument'])->name('units.upload-document-guideline');
    Route::delete('/units/{unit}/delete-guideline', [UnitController::class, 'deleteGuidelineDocument'])->name('units.delete-document-guideline');
});


Route::middleware(['permission:stase.*'])->group(function () {
    Route::resource('/stases', \App\Http\Controllers\StaseController::class);
});
Route::middleware('permission:stase.read')->get('/stases', [\App\Http\Controllers\StaseController::class, 'index'])->name('stases.index');

Route::middleware(['permission:location.*'])->group(function () {
    Route::resource('/locations', \App\Http\Controllers\LocationController::class);
});

Route::middleware('permission:location.read')->get('/locations', [\App\Http\Controllers\LocationController::class, 'index'])->name('locations.index');

Route::middleware(['permission:logbook.*'])->group(function () {
    Route::get('/activities/{user}/index', [\App\Http\Controllers\ActivityController::class, 'index'])->name('activities.index');
    // Route::resource('/activities', \App\Http\Controllers\ActivityController::class)->except(['index']);
    // Menambahkan middleware 'can' untuk aksi update dan delete pada resource activities
    Route::resource('/activities', \App\Http\Controllers\ActivityController::class)
        ->except(['index', 'show']);
    Route::get('/activities/{user}/calendar/{month_number?}/{year?}', [\App\Http\Controllers\ActivityController::class, 'calendar'])->name('activities.calendar');
    Route::get('/activities/{user}/report', [\App\Http\Controllers\ActivityController::class, 'report'])->name('activities.report');
    Route::get('/activities/{user}/schedule/{month_number}/month/{year}/year', [\App\Http\Controllers\ActivityController::class, 'schedule'])->name('activities.schedule');
    Route::post('/activities/{activity}/allow-activity', [\App\Http\Controllers\ActivityController::class, 'allowActivity'])->name('activities.allow');
});

Route::middleware(['permission:report-logbook'])->group(function () {
    Route::get('/activities/{user}/report', [\App\Http\Controllers\ActivityController::class, 'report'])->name('activities.report');
    Route::get('/activities/{user}/statistic', [\App\Http\Controllers\ActivityController::class, 'statistic'])->name('activities.statistic');
});

Route::middleware(['permission:portofolio.*'])->group(function () {
    Route::get('/portofolios/{user}/index', [\App\Http\Controllers\PortofolioController::class, 'index'])->name('portofolios.index'); // << agar bisa diakses oleh admin
    Route::post('/portofolios/{portofolio}', [\App\Http\Controllers\PortofolioController::class, 'update'])->name('portofolios.update'); // << karena ada upload file
    Route::resource('/portofolios', \App\Http\Controllers\PortofolioController::class)->except(['index', 'update', 'show']);
});

Route::middleware(['permission:consult.*'])->group(function () {
    Route::get('/consults/{user}/index', [\App\Http\Controllers\ConsultController::class, 'index'])->name('consults.index');
    Route::post('/consults/{consult}', [\App\Http\Controllers\ConsultController::class, 'update'])->name('consults.update'); // << karena ada upload file
    Route::resource('/consults', \App\Http\Controllers\ConsultController::class)->except(['index', 'update', 'show']);
    Route::get('/consults/student-list', [\App\Http\Controllers\ConsultController::class, 'studentList'])->name('consults.student-list');
    Route::post('/consults/{consult}/reply', [\App\Http\Controllers\ConsultController::class, 'reply'])->name('consults.reply'); // << karena ada upload file
    Route::delete('/consults/{consult}/destroy-reply', [\App\Http\Controllers\ConsultController::class, 'destroyReply'])->name('consults.destroyReply');
});

Route::middleware(['permission:speak.*'])->group(function () {
    Route::get('/speaks/{user}/index', [\App\Http\Controllers\SpeakController::class, 'index'])->name('speaks.index');
    Route::post('/speaks/{speak}', [\App\Http\Controllers\SpeakController::class, 'update'])->name('speaks.update'); // << karena ada upload file
    Route::resource('/speaks', \App\Http\Controllers\SpeakController::class)->except(['index', 'update', 'show']);
    Route::get('/speaks/student-list', [\App\Http\Controllers\SpeakController::class, 'studentList'])->name('speaks.student-list');
    Route::post('/speaks/{speak}/answer', [\App\Http\Controllers\SpeakController::class, 'answer'])->name('speaks.answer'); // << karena ada upload file
    Route::get('/speaks/index-flyer', [\App\Http\Controllers\SpeakController::class, 'indexFlyer'])->name('speaks.index-flyer');
});

Route::middleware(['permission:week-monitor.index'])->group(function () {
    Route::get('/week-monitors/{user}/index', [\App\Http\Controllers\WeekMonitorController::class, 'index'])->name('week-monitors.index');
});

Route::middleware(['permission:schedule.*'])->group(function () {
    Route::get('/schedules/{unit?}', [\App\Http\Controllers\ScheduleController::class, 'index'])->name('schedules.index');
    Route::post('/schedules/{schedule}/upload-document', [\App\Http\Controllers\ScheduleController::class, 'uploadDocument'])->name('schedules.upload-document');
    Route::delete('/schedules/{schedule}/delete-document', [\App\Http\Controllers\ScheduleController::class, 'deleteDocument'])->name('schedules.delete-document');
});


require __DIR__ . '/auth.php';
