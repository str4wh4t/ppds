<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Http\Requests\Schedule\StoreRequest;
use App\Http\Requests\Schedule\UpdateRequest;
use App\Models\Unit;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Http\Requests\Schedule\UploadDocumentRequest;
use Exception;
use \Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

class ScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Unit $unit): Response
    {
        $yearSelected = $request->input('yearSelected');
        $currentYear = Carbon::now()->year;

        if ($yearSelected) {
            $currentYear = $yearSelected;
        }

        // Query untuk mendapatkan jadwal berdasarkan unit dan tahun
        $schedules = Schedule::where('unit_id', $unit->id)
            ->where('year', $currentYear);



        // Periksa apakah jadwal untuk tahun tersebut kosong
        if ($schedules->doesntExist()) {
            // Buat jadwal baru untuk 12 bulan
            $newSchedules = collect();
            for ($i = 1; $i <= 12; $i++) {
                $newSchedules->push([
                    'unit_id' => $unit->id,
                    'user_id' => $request->user()->id,
                    'year' => $currentYear,
                    'month_number' => $i,
                    'month_name' => Carbon::createFromDate($currentYear, $i, 1)->monthName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Insert data secara batch untuk meningkatkan efisiensi
            Schedule::insert($newSchedules->toArray());
        }

        // Paginate jadwal berdasarkan query
        $schedulesToPaginate = Schedule::where('unit_id', $unit->id)
            ->where('year', $currentYear)
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Schedules/Index', [
            'schedules' => $schedulesToPaginate,
            'unit' => $unit,
            'currentYear' => $currentYear,
            'filters' => [
                'yearSelected' => $yearSelected,
            ]
        ]);
    }

    public function uploadDocument(UploadDocumentRequest $request, Schedule $schedule)
    {
        try {
            if ($schedule->user_id != $request->user()->id) {
                throw new Exception("User invalid");
            }
            // Ambil file yang sudah divalidasi
            $file = $request->file('document');

            // Simpan file ke direktori 'documents'
            $path = $file->store('schedules', 'public');

            $schedule->document_path = $path;
            $schedule->save();

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Schedule document uploaded successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    public function deleteDocument(Schedule $schedule)
    {
        try {
            $oldFilePath = $schedule->document_path;

            if ($oldFilePath && Storage::disk('public')->exists($oldFilePath)) {
                Storage::disk('public')->delete($oldFilePath);
            }

            $schedule->document_path = null;
            $schedule->save();

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Schedule document delete successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Schedule $schedule)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Schedule $schedule)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Schedule $schedule)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Schedule $schedule)
    {
        //
    }
}
