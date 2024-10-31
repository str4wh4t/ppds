<?php

namespace App\Http\Controllers;

use App\Events\ActivityLogged;
use App\Http\Requests\Activity\StoreRequest;
use App\Http\Requests\Activity\UpdateRequest;
use App\Models\Activity;
use App\Models\Stase;
use App\Models\Unit;
use App\Models\UnitStase;
use App\Models\UnitStaseUser;
use App\Models\User;
use App\Models\WeekMonitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Redirect;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

use function PHPUnit\Framework\isNull;

class ActivityController extends Controller
{
    public function __construct()
    {
        // Menambahkan Policy untuk otorisasi update dan delete
        $this->middleware('can:update,activity')->only(['update', 'edit']);
        $this->middleware('can:delete,activity')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, User $user): Response
    {
        //
        if ($user->roles->count() == 1) {
            if ($user->hasRole('student')) {
                $user =  $request->user();
            }
        }

        $search = $request->input('search');

        $activities = Activity::where('user_id', $user->id)
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->with(['unitStase', 'unitStase.stase'])
            ->paginate(10)
            ->withQueryString();

        $stases = Stase::whereHas('units', function ($query) use ($user) {
            $query->where('units.id', $user->student_unit_id);
        })->get(['id', 'name']);

        return Inertia::render('Activities/Index', [
            'activities' => $activities,
            'stases' => $stases,
            'filters' => [
                'search' => $search,
            ]
        ]);
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
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request) {
                // Menghitung time_spend sebagai selisih antara end_date dan start_date
                $startDate = Carbon::parse($request->date . ' ' . $request->start_time);
                $endDate = Carbon::parse($request->date . ' ' . $request->finish_time);
                $timeSpendInSeconds = $startDate->diffInSeconds($endDate);

                // Menghitung jam, menit, dan detik
                $hours = floor($timeSpendInSeconds / 3600);
                $minutes = floor(($timeSpendInSeconds % 3600) / 60);
                $seconds = $timeSpendInSeconds % 60;

                // Format dengan sprintf untuk memastikan dua digit
                $timeSpend = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

                $date = Carbon::parse($startDate); // Buat objek Carbon dari tanggal input

                // Membuat week_group_id dengan format Tahun + Minggu (ISO-8601)
                $weekGroupId = $date->year . $date->isoWeek;

                // Memeriksa apakah `week_group_id` sudah ada di Activity
                $activity = Activity::where('user_id',  $request->user()->id)->where('week_group_id', $weekGroupId)->first();

                // init week monitor
                WeekMonitor::updateOrCreate(
                    ['user_id' => $request->user()->id, 'week_group_id' => $weekGroupId],  // Kondisi pencarian
                    ['workload' => 0, 'workload_as_seconds' => 0]           // Data yang akan diupdate/insert
                );

                if ($activity == null) {
                    // Pindah ke hari Senin minggu yang sama
                    $date->startOfWeek(Carbon::MONDAY);

                    // Loop untuk menambahkan tanggal dari Senin hingga Minggu
                    for ($i = 0; $i < 7; $i++) {
                        Activity::create([
                            'user_id' => $request->user()->id,
                            'name' => '',
                            'type' => null,
                            'start_date' => $date->format('Y-m-d'),
                            'end_date' => $date->format('Y-m-d'),
                            'time_spend' => '00:00',
                            'description' => null,
                            'is_approved' => 0, // Nilai default
                            'approved_by' => null,
                            'approved_at' => null,
                            'unit_stase_id' => null,
                            'week_group_id' => $weekGroupId,
                            'is_generated' => 1,
                        ]);

                        $date->addDay(); // Tambah satu hari
                    }
                }

                $unit_stase_id = null;

                if (!is_null($request->stase_id)) {
                    $unit_stase = UnitStase::where('stase_id', $request->stase_id)->where('unit_id', $request->user()->student_unit_id)->first();
                    $unit_stase_id = $unit_stase->id;
                }

                $activity = Activity::create([
                    'user_id' => $request->user()->id,
                    'name' => $request->name,
                    'type' => $request->type,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'time_spend' => $timeSpend,
                    'description' => $request->description,
                    'is_approved' => 0, // default value
                    'approved_by' => null,
                    'approved_at' => null,
                    'unit_stase_id' =>  $request->type == 'nonstase' ? null : $unit_stase_id,
                    'week_group_id' => $weekGroupId,
                    'is_generated' => 0,
                ]);

                event(new ActivityLogged($request->user(), $activity->toArray()));
            });

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Activity created successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Activity $activity)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Activity $activity)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Activity $activity): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $activity) {
                // Menghitung time_spend sebagai selisih antara end_date dan start_date
                $startDate = Carbon::parse($request->date . ' ' . $request->start_time);
                $endDate = Carbon::parse($request->date . ' ' . $request->finish_time);
                $timeSpendInSeconds = $startDate->diffInSeconds($endDate);

                // Menghitung jam, menit, dan detik
                $hours = floor($timeSpendInSeconds / 3600);
                $minutes = floor(($timeSpendInSeconds % 3600) / 60);
                $seconds = $timeSpendInSeconds % 60;

                // Format dengan sprintf untuk memastikan dua digit
                $timeSpend = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

                $unit_stase_id = null;

                if (!is_null($request->stase_id)) {
                    $unit_stase = UnitStase::where('stase_id', $request->stase_id)->where('unit_id', $request->user()->student_unit_id)->first();
                    $unit_stase_id = $unit_stase->id;
                }
                $activity->update([
                    'name' => $request->name,
                    'type' => $request->type,
                    'unit_stase_id' => $request->type == 'nonstase' ? null : $unit_stase_id,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'time_spend' => $timeSpend,
                    'description' => $request->description,
                ]);

                event(new ActivityLogged($request->user(), $activity->toArray()));
            });

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Activity updated successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Activity $activity): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $activity) {
                $activity->delete();
                event(new ActivityLogged($request->user(), $activity->toArray()));
            });

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Activity deleted successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    // create function named calendar
    public function calendar(Request $request, User $user): Response
    {
        // $search = $request->input('search');
        if ($user->roles->count() == 1) {
            if ($user->hasRole('student')) {
                $user =  $request->user();
            }
        }

        $activities = Activity::where('user_id', $user->id)
            ->with(['unitStase', 'unitStase.stase'])
            ->with('weekMonitor', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->get();

        $stases = Stase::whereHas('units', function ($query) use ($user) {
            $query->where('units.id', $user->student_unit_id);
        })->get(['id', 'name']);

        return Inertia::render('Activities/Calendar', [
            'activities' => $activities,
            'stases' => $stases,
            'filters' => [
                // 'search' => $search,
            ]
        ]);
    }


    public function generateDays(Request $request, User $user): \Illuminate\Http\JsonResponse
    {
        $year = $request->input('year');
        $month = $request->input('month') + 1; // Tambahkan 1 untuk menyesuaikan dengan format PHP
        $days = [];

        // Tentukan tanggal pertama dan terakhir dari bulan yang diminta
        $startDate = Carbon::createFromDate($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        // Tentukan hari dalam seminggu di mana bulan ini dimulai
        $startDayOfWeek = ($startDate->dayOfWeek + 6) % 7; // Konversi agar Senin = 0
        $endDayOfWeek = ($endDate->dayOfWeek + 6) % 7;

        // Tambahkan tanggal dari bulan sebelumnya untuk mengisi slot kosong di awal
        for ($i = $startDayOfWeek - 1; $i >= 0; $i--) {
            $prevDate = $startDate->copy()->subDays($i + 1);
            $days[] = [
                'date' => $prevDate->format('Y-m-d'),
                'isCurrentMonth' => false,
                'events' => [],
                'workload' => 0,
            ];
        }

        // Loop melalui setiap hari dalam bulan yang diminta
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dayObj = [
                'date' => $date->format('Y-m-d'),
                'isCurrentMonth' => true,
                'events' => [],
                'isWarning' => false,
                'isDanger' => false,
                'isToday' => false,
                'workload' => 0,
            ];

            // Tentukan apakah tanggal adalah hari ini
            $today = Carbon::today();

            $user_id = $user->id;
            if ($user->roles->count() == 1) {
                if ($user->hasRole('student')) {
                    $user_id =  $request->user()->id;
                }
            }

            $activities = Activity::where('user_id', $user_id)
                ->whereDate('start_date', $date)
                ->with('weekMonitor', function ($query) use ($user_id) {
                    $query->where('user_id', $user_id);
                })->get();

            if ($date->isSameDay($today)) {
                $dayObj['isToday'] = true;
            }

            foreach ($activities as $activity) {
                $dayObj['events'][] = [
                    'id' => $activity->id,
                    'name' => $activity->name,
                    'start_date' => $activity->start_date,
                    'end_date' => $activity->end_date,
                ];
            }

            $weekMonitor = WeekMonitor::where('user_id', $user_id)
                ->where('week_group_id', $date->year . $date->isoWeek)
                ->first();
            if (isset($weekMonitor)) {
                $dayObj['workload'] = $weekMonitor->workload;
            } else {
                $dayObj['workload'] = '00:00';
            }

            list($hours, $minutes) = explode(':', $dayObj['workload']);
            // Mengambil jam sebagai integer
            $hoursInteger = (int) $hours;

            // Logika untuk menandai tanggal sebagai warning atau danger
            if ($hoursInteger >= 70 && $hoursInteger < 80) {
                $dayObj['isWarning'] = true;
            }
            if ($hoursInteger >= 80) {
                $dayObj['isDanger'] = true;
            }

            $days[] = $dayObj;
        }

        // Tambahkan tanggal dari bulan berikutnya untuk mengisi slot kosong di akhir
        for ($i = 1; $i < 7 - $endDayOfWeek; $i++) {
            $nextDate = $endDate->copy()->addDays($i);
            $days[] = [
                'date' => $nextDate->format('Y-m-d'),
                'isCurrentMonth' => false,
                'events' => [],
                'workload' => 0,
            ];
        }

        return response()->json([
            'days' => $days
        ]);
    }

    // buat fungsi untuk menampilkan laporan dengan nama report dengan param request dan user
    public function report(Request $request, User $user): Response
    {
        $search = $request->input('search');
        $unitSelected = $request->input('units');

        // cek apakah user memiliki role student
        if ($user->roles->count() == 1) {
            if ($user->hasRole('student')) {
                $user =  $request->user();
            }
        }

        // ambil data activities berdasarkan user_id
        $activities = Activity::where('type', 'stase')
            ->when($search, function ($query, $search) {
                $query->whereHas('user', function ($query) use ($search) {
                    $query->where('fullname', 'like', "%{$search}%");
                });
            })
            ->when($unitSelected, function ($query, $unitSelected) {
                $array = json_decode($unitSelected, true);
                if (!empty($array)) {
                    $unitNames = array_map(function ($item) {
                        return $item['name'];
                    }, $array);

                    // Filter hanya user yang memiliki setidaknya satu role yang dipilih
                    $query->whereHas('user.studentUnit', function ($query) use ($unitNames) {
                        $query->whereIn('name', $unitNames);
                    });
                }
            })
            ->with('unitStase', 'unitStase.stase', 'user');

        if ($user->roles->count() == 1) {
            if ($user->hasRole('student')) {
                $activities = $activities->where('user_id', $user->id);
            }
        }

        $activities = $activities->get();

        $userStaseCounts = [];

        foreach ($activities as $activity) {
            $userName = $activity->user->username;       // Mengambil nama user dari activity
            $staseName = $activity->unitStase->stase->name;  // Mengambil nama stase dari relasi unit_stase

            // Jika user belum ada di array, inisialisasi dengan array kosong
            if (!isset($userStaseCounts[$userName])) {
                $userStaseCounts[$userName] = [
                    'user' => $activity->user,
                    'stases' => []
                ];
            }

            // Jika stase belum ada di array user, inisialisasi dengan 0
            if (!isset($userStaseCounts[$userName]['stases'][$staseName])) {
                $userStaseCounts[$userName]['stases'][$staseName] = [
                    'stase_id' => $activity->unitStase->stase->id,
                    'name' => $staseName,
                    'count' => 0
                ];
            }

            // Tambah jumlah pengambilan stase oleh user
            $userStaseCounts[$userName]['stases'][$staseName]['count']++;
        }
        foreach ($userStaseCounts as &$userStaseCount) {
            $userStaseCount['stases'] = array_values($userStaseCount['stases']);
        }
        unset($userStaseCount);
        $userStaseCounts = array_values($userStaseCounts);

        // dd($userStaseCounts);

        $stases = Stase::whereHas('units', function ($query) use ($user) {
            if ($user->roles->count() == 1) {
                if ($user->hasRole('student')) {
                    $query->where('units.id', $user->student_unit_id);
                }
            }
        })->get(['id', 'name', 'location']);

        $units = Unit::all();

        // tampilkan halaman report dengan data activities dan stases
        return Inertia::render('Activities/Report', [
            'user_stase_counts' =>  $userStaseCounts,
            'stases' => $stases,
            'units' => $units,
            'filters' => [
                'search' => $search,
                'units' => $unitSelected,
            ]
        ]);
    }

    // tampilkan view Activity/Schedule
    public function schedule(Request $request, User $user): Response
    {
        //
        $schedule_document_path = $user->studentUnit->schedule_document_path;
        return Inertia::render('Activities/Schedule', [
            'schedule' => Storage::url("public/" . $schedule_document_path),
        ]);
    }
}
