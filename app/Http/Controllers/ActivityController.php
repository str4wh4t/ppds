<?php

namespace App\Http\Controllers;

use App\Events\ActivityLogged;
use App\Http\Requests\Activity\StoreRequest;
use App\Http\Requests\Activity\UpdateRequest;
use App\Models\Activity;
use App\Models\Location;
use App\Models\Schedule;
use App\Models\Stase;
use App\Models\StaseLocation;
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
        $this->middleware('can:create,\App\Models\Activity')->only('store');
        $this->middleware('can:update,activity')->only(['update', 'edit']);
        $this->middleware('can:delete,activity')->only('destroy');
        $this->middleware('can:permitActivity,activity')->only('allowActivity');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, User $user): Response
    {
        //
        $search = $request->input('search');
        $unitSelected = $request->input('units');

        $activities = Activity::when($search, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($query) use ($search) {
                        $query->where('fullname', 'like', "%{$search}%");
                    });
            });
        })->when($unitSelected, function ($query, $unitSelected) {
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
        })->with('user', 'user.studentUnit', 'unitStase', 'stase', 'staseLocation', 'location', 'dosenUser');

        if ($request->user()->hasRole('student')) {
            $activities = $activities->where('user_id', $request->user()->id);
        } else {
            if ($user->id != $request->user()->id) {
                $activities = $activities->where('user_id', $user->id);
            }
        }

        $activities = $activities
            ->paginate(10)
            ->withQueryString();

        $stases = Stase::whereHas('units', function ($query) use ($user, $request) {
            if ($request->user()->hasRole('student')) {
                $query->where('units.id', $user->student_unit_id);
            }
        })
            // ->join('stase_locations', 'stases.stase_location_id', '=', 'stase_locations.id')
            // ->selectRaw('stases.id,stases.name,CONCAT(stases.name," - ",stase_locations.name) AS label')->get();
            ->get();

        $units = Unit::all();
        $dosenList = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['dosen']);
        })->get();

        return Inertia::render('Activities/Index', [
            'activities' => $activities,
            'stases' => $stases,
            'units' => $units,
            'dosen_list' => $dosenList,
            'filters' => [
                'search' => $search,
                'units' => $unitSelected,
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
            $startDate = Carbon::parse($request->date . ' ' . $request->start_time); // Tanggal asli tetap digunakan
            $startOfMonthDate = $startDate->copy()->startOfMonth(); // Variabel baru untuk awal bulan

            $now = Carbon::now();
            $nowStartOfMonth = $now->copy()->startOfMonth(); // Variabel baru untuk awal bulan saat ini

            // Hitung selisih bulan dari awal bulan ini
            $diffForward = $nowStartOfMonth->diffInMonths($startOfMonthDate, false); // `false` agar bisa negatif

            // Validasi jika lebih dari 2 bulan ke depan atau lebih dari 1 bulan ke belakang
            // if ($diffForward > 1) {
            //     throw new \Exception('Cannot insert activity more than 1 months ahead');
            // } elseif ($diffForward < -1) {
            //     throw new \Exception('Cannot insert activity more than 1 month in the past');
            // }

            DB::transaction(function () use ($request, $startDate) {
                $endDate = Carbon::parse($request->date . ' ' . $request->finish_time);
                // Menghitung time_spend sebagai selisih antara end_date dan start_date
                $timeSpendInSeconds = $startDate->diffInSeconds($endDate);

                // Menghitung jam, menit, dan detik
                $hours = floor($timeSpendInSeconds / 3600);
                $minutes = floor(($timeSpendInSeconds % 3600) / 60);
                $seconds = $timeSpendInSeconds % 60;

                // Format dengan sprintf untuk memastikan dua digit
                $timeSpend = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

                $date = Carbon::parse($startDate); // membuat instance Carbon dari start_date untuk me-looping satu minggu

                // Membuat week_group_id dengan format Tahun + Minggu (ISO-8601)
                $year = $date->year;
                $month = $date->month;
                $yearIso = $date->isoWeekYear;
                $weekIso = $date->isoWeek;

                // dd($year, $month, $yearIso, $weekIso);  

                $weekGroupId = intval($yearIso . $weekIso);

                // dd($weekGroupId);

                // init week monitor
                // WeekMonitor::updateOrCreate(
                //     ['user_id' => $request->user()->id, 'week_group_id' => $weekGroupId],  // Kondisi pencarian
                //     ['year' => $date->year, 'week' => $date->isoWeek, 'workload' => 0, 'workload_hours' => 0, 'workload_as_seconds' => 0]           // Data yang akan diupdate/insert
                // );

                $isWorkloadExceeded = false;

                $weekMonitor = WeekMonitor::where('user_id', $request->user()->id)
                    ->where('week_group_id', $weekGroupId)
                    ->first();
                if (empty($weekMonitor)) {
                    // Jika record week monitor tidak ditemukan

                    // Mengambil tahun dan minggu dari weekGroupId
                    // $year = substr($weekGroupId, 0, 4);
                    // $week = substr($weekGroupId, 4, 2);

                    // Menentukan tanggal pertama dari minggu tersebut
                    // $date = Carbon::now()->setISODate($year, $week);

                    // Menentukan bulan berdasarkan tanggal minggu tersebut
                    // $month = $date->month;

                    // Menentukan minggu keberapa dalam bulan tersebut
                    $firstDayOfMonth = Carbon::create($year, $month, 1);
                    $weekMonth = $date->diffInWeeks($firstDayOfMonth) + 1;

                    $weekMonitor = new WeekMonitor();
                    $weekMonitor->user_id = $request->user()->id;
                    $weekMonitor->week_group_id = $weekGroupId;
                    $weekMonitor->year = $year;
                    $weekMonitor->week = $weekIso;
                    $weekMonitor->month = $month;
                    $weekMonitor->week_month = $weekMonth;
                    $weekMonitor->workload_hours = 0;
                    $weekMonitor->workload = 0;
                    $weekMonitor->workload_as_seconds = 0;
                    $weekMonitor->save();
                } else {
                    // if ($weekMonitor->workload_hours > 80) {
                    //     throw new \Exception('Workload exceeded');
                    // }
                    $activity = Activity::where('user_id',  $request->user()->id)
                        ->where('week_group_id', $weekGroupId)
                        ->where('is_allowed', 0)
                        ->first();
                    if (!empty($activity)) {
                        // ada kegiatan yang belum di ijinkan
                        throw new \Exception('Workload exceeded');
                    }
                    $next_workhours = $weekMonitor->workload_hours + $hours;
                    if ($next_workhours > 88) {
                        throw new \Exception('Workload more than 88 hours');
                    }
                    if ($next_workhours > 80) {
                        $isWorkloadExceeded = true;
                    }
                }

                // Memeriksa apakah `week_group_id` sudah ada di Activity
                $activity = Activity::where('user_id',  $request->user()->id)->where('week_group_id', $weekGroupId)->first();

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
                            'description' => '',
                            'is_approved' => 0, // Nilai default
                            'approved_by' => null,
                            'approved_at' => null,
                            'unit_stase_id' => null,
                            'stase_id' => null,
                            'stase_location_id' => null,
                            'location_id' => null,
                            'dosen_user_id' => null,
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

                $stase_location_id = null;

                if (!is_null($request->stase_id)) {
                    $stase_location = StaseLocation::where('stase_id', $request->stase_id)->where('location_id', $request->location_id)->first();
                    $stase_location_id = $stase_location->id;
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
                    'unit_stase_id' =>  $request->type == 'jaga' ? null : $unit_stase_id,
                    'stase_id' =>  $request->type == 'jaga' ? null : $request->stase_id,
                    'stase_location_id' =>  $request->type == 'jaga' ? null : $stase_location_id,
                    'location_id' =>  $request->type == 'jaga' ? null : $request->location_id,
                    'dosen_user_id' => $request->dosen_user_id ?? null,
                    'week_group_id' => $weekGroupId,
                    'is_generated' => 0,
                    'is_allowed' => $isWorkloadExceeded ? 0 : 1,
                ]);
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

                $changeToAllow = false;
                // proteksi update tidak boleh melebihi 80 jam
                list($prev_activity_hours, $prev_activity_minutes, $prev_activity_seconds) = explode(':', $activity->time_spend);
                if ($prev_activity_hours != $hours) {
                    $weekMonitor = WeekMonitor::where('user_id', $request->user()->id)
                        ->where('week_group_id', $activity->week_group_id)
                        ->first();
                    $updated_workload_hours = ($weekMonitor->workload_hours - $prev_activity_hours) + $hours;
                    if ($updated_workload_hours > 80) {
                        if ($activity->is_allowed == 1) {
                            throw new \Exception('Workload exceeded');
                        }
                    } else {
                        if ($activity->is_allowed == 0) {
                            $changeToAllow = true;
                        } else {
                            Activity::where('is_allowed', 0)
                                ->where('user_id',  $request->user()->id)
                                ->where('week_group_id', $activity->week_group_id)
                                ->update(['is_allowed' => 1]);
                        }
                    }
                }

                $unit_stase_id = null;

                if (!is_null($request->stase_id)) {
                    $unit_stase = UnitStase::where('stase_id', $request->stase_id)->where('unit_id', $request->user()->student_unit_id)->first();
                    $unit_stase_id = $unit_stase->id;
                }

                $stase_location_id = null;

                if (!is_null($request->stase_id)) {
                    $stase_location = StaseLocation::where('stase_id', $request->stase_id)->where('location_id', $request->location_id)->first();
                    $stase_location_id = $stase_location->id;
                }

                $activity->update([
                    'name' => $request->name,
                    'type' => $request->type,
                    'unit_stase_id' => $request->type == 'jaga' ? null : $unit_stase_id,
                    'stase_id' =>  $request->type == 'jaga' ? null : $request->stase_id,
                    'stase_location_id' =>  $request->type == 'jaga' ? null : $stase_location_id,
                    'location_id' =>  $request->type == 'jaga' ? null : $request->location_id,
                    'dosen_user_id' =>  $request->dosen_user_id ?? null,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'time_spend' => $timeSpend,
                    'description' => $request->description,
                    'is_allowed' => $activity->is_allowed == 0 ? ($changeToAllow ? 1 : 0) : 1,
                ]);
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
                $updated_workload_hours = null;
                if ($activity->is_allowed == 1) {
                    $weekMonitor = WeekMonitor::where('user_id', $request->user()->id)
                        ->where('week_group_id', $activity->week_group_id)
                        ->first();
                    list($prev_activity_hours, $prev_activity_minutes, $prev_activity_seconds) = explode(':', $activity->time_spend);
                    $updated_workload_hours = $weekMonitor->workload_hours - $prev_activity_hours;
                    if ($updated_workload_hours <= 80) {
                        Activity::where('is_allowed', 0)
                            ->where('user_id',  $request->user()->id)
                            ->where('week_group_id', $activity->week_group_id)
                            ->update(['is_allowed' => 1]);
                    }
                }
                $activity->delete();
                if($updated_workload_hours === 0){
                    Activity::withoutEvents(function () use($request, $activity) {
                        Activity::withoutGlobalScopes()->where(['user_id' => $request->user()->id, 'week_group_id' => $activity->week_group_id, 'is_generated' => 1])
                            ->delete();
                    });
                }
            });

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Activity deleted successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    // create function named calendar
    public function calendar(Request $request, User $user, int $month_number = null, int $year = null): Response
    {
        $weekGroupId = $request->input('weekGroupId');
        $userId = $request->input('userId');
        $month =  $month_number ?? date('m') - 1;
        $year = $year ?? date('Y');
        // dd($weekGroupId);
        if (!empty($weekGroupId)) {
            $year = substr($weekGroupId, 0, 4);
            $week = substr($weekGroupId, 4, 2);
            $date = new DateTime();
            $date->setISODate($year, $week);
            $year = $date->format('Y');
            $month = $date->format('m');
            $month = $month - 1;
        }

        if ($request->user()->hasRole('student')) {
            $user =  $request->user();
        } else {
            $user = User::find($userId);
        }

        $activities = Activity::where('user_id', $user->id)
            ->with('unitStase', 'staseLocation', 'stase', 'location', 'dosenUser')
            ->with('weekMonitor', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->get();

        $stases = Stase::whereHas('units', function ($query) use ($user) {
            $query->where('units.id', $user->student_unit_id);
        })
            // ->join('stase_locations', 'stases.stase_location_id', '=', 'stase_locations.id')
            // ->selectRaw('stases.id,stases.name,CONCAT(stases.name," - ",stase_locations.name) AS label')->get();
            ->get();

        $dosenList = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['dosen']);
        })->get();

        return Inertia::render('Activities/Calendar', [
            'activities' => $activities,
            'stases' => $stases,
            'month' => $month,
            'year' => $year,
            'selectedUser' => $user,
            'dosen_list' => $dosenList,
            'filters' => [
                // 'search' => $search,
            ]
        ]);
    }


    public function calendarGenerateDays(Request $request, User $user): \Illuminate\Http\JsonResponse
    {
        
        $year = $request->year;
        $month = $request->month + 1; // Tambahkan 1 untuk menyesuaikan dengan format PHP
        $days = [];
        
        
        if ($request->user()->hasRole('student')) {
            $user =  $request->user();
        }
        
        // Tentukan tanggal pertama dan terakhir dari bulan yang diminta
        $startDate = Carbon::createFromDate($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        
        if($startDate->year == '2023'){
            abort(403, 'Tidak bisa melihat tahun 2023');
        }

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
                'isDangerBold' => false,
                'isToday' => false,
                'workload' => '00:00',
                'workload_hours' => 0,
            ];

            // Tentukan apakah tanggal adalah hari ini
            $today = Carbon::today();

            $user_id =  $user->id;

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
                    'is_allowed' => $activity->is_allowed,
                ];
            }

            $weekMonitor = WeekMonitor::where('user_id', $user_id)
                ->where('week_group_id', $date->isoWeekYear . $date->isoWeek)
                ->first();
            if (isset($weekMonitor)) {
                $dayObj['workload'] = $weekMonitor->workload;
                $dayObj['workload_hours'] = $weekMonitor->workload_hours;
            }

            $workload_hours = $dayObj['workload_hours'];

            // Logika untuk menandai tanggal sebagai warning atau danger
            if ($workload_hours > 70 && $workload_hours <= 80) {
                $dayObj['isWarning'] = true;
            }
            if ($workload_hours > 80) {
                $dayObj['isDanger'] = true;
                foreach ($dayObj['events'] as $event) {
                    if ($event['is_allowed'] == 0) {
                        $dayObj['isDanger'] = false;
                        $dayObj['isDangerBold'] = true;
                        break; // Hentikan iterasi jika ditemukan
                    }
                }
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
        $unitsSelected = $request->input('units');
        $unitSelected = $request->input('unitSelected');
        $monthIndexSelected = $request->input('monthIndexSelected');

        if (!$request->user()->hasRole('student')) {
            $unitFirst = Unit::firstOrFail();
            // if($unitsSelected == null){
            //     $unitsSelectedArray = [
            //         [
            //             'id' => $unitFirst->id,
            //             'name' => $unitFirst->name,
            //         ]
            //     ];
            //     $unitsSelected = json_encode($unitsSelectedArray);
            // }
            if($unitSelected == null){
                $unitSelected = $unitFirst->id;
            }
        }

        // dd($user);

        // ambil data activities berdasarkan user_id
        // $activities = Activity::where('type', 'nonjaga')
        $activities = Activity::whereHas('unitStase')
            ->when($search, function ($query, $search) {
                $query->whereHas('user', function ($query) use ($search) {
                    $query->where('fullname', 'like', "%{$search}%");
                });
            })
            ->when($unitSelected, function ($query, $unitSelected) {
                $query->whereHas('user.studentUnit', function ($query) use ($unitSelected) {
                    $query->where('id', $unitSelected);
                });
            })
            // ->when($unitsSelected, function ($query, $unitsSelected) {
            //     $array = json_decode($unitsSelected, true);
            //     if (!empty($array)) {
            //         $unitNames = array_map(function ($item) {
            //             return $item['name'];
            //         }, $array);

            //         // Filter hanya user yang memiliki setidaknya satu role yang dipilih
            //         $query->whereHas('user.studentUnit', function ($query) use ($unitNames) {
            //             $query->whereIn('name', $unitNames);
            //         });
            //     }
            // })
            ->with('unitStase', 'unitStase.stase', 'user');

        if ($user->roles->count() == 1) {
            if ($user->hasRole('student')) {
                $activities = $activities->where('user_id', $user->id);
            }
        }

        if ($request->user()->hasRole('student')) {
            $activities = $activities->where('user_id', $request->user()->id);
        } else {
            if ($user->id != $request->user()->id) {
                $activities = $activities->where('user_id', $user->id);
            }
        }

        $activities = $activities->get();

        $userStaseCounts = [];

        foreach ($activities as $activity) {
            $userName = $activity->user->username;       // Mengambil nama user dari activity
            $staseName = $activity->stase->name;  // Mengambil nama stase dari relasi unit_stase
            // $staseLocation = $activity->location->name;  // Mengambil lokasi stase dari relasi unit_stase

            // Jika user belum ada di array, inisialisasi dengan array kosong
            if (!isset($userStaseCounts[$userName])) {
                $userStaseCounts[$userName] = [
                    'user' => $activity->user,
                    'stases' => []
                ];
            }

            // Jika stase belum ada di array user, inisialisasi dengan 0
            // if (!isset($userStaseCounts[$userName]['stases'][$staseName . '|' . $staseLocation])) {
            //     $userStaseCounts[$userName]['stases'][$staseName . '|' . $staseLocation] = [
            //         'stase_id' => $activity->unitStase->stase->id,
            //         'name' => $staseName,
            //         'location' => $staseLocation,
            //         'count' => 0
            //     ];
            // }

            // Jika stase belum ada di array user, inisialisasi dengan 0
            if (!isset($userStaseCounts[$userName]['stases'][$staseName])) {
                $userStaseCounts[$userName]['stases'][$staseName] = [
                    'stase_id' => $activity->unitStase->stase->id,
                    'name' => $staseName,
                    'count' => 0
                ];
            }

            // Tambah jumlah pengambilan stase oleh user
            // $userStaseCounts[$userName]['stases'][$staseName . '|' . $staseLocation]['count']++;
            $userStaseCounts[$userName]['stases'][$staseName]['count']++;
        }
        foreach ($userStaseCounts as &$userStaseCount) {
            $userStaseCount['stases'] = array_values($userStaseCount['stases']);
        }
        unset($userStaseCount);
        $userStaseCounts = array_values($userStaseCounts);

        // dd($userStaseCounts);

        $stases = Stase::whereHas('units', function ($query) use ($user, $unitSelected) {
            if ($user->hasRole('student')) {
                $query->where('units.id', $user->student_unit_id);
            }else{
                $query->where('units.id', $unitSelected);
            }
        })
            // ->join('stase_locations', 'stases.stase_location_id', '=', 'stase_locations.id')
            // ->selectRaw('stases.id,stases.name,stase_locations.name AS location')->get();
            ->get();

        $units = Unit::all();

        // tampilkan halaman report dengan data activities dan stases
        return Inertia::render('Activities/Report', [
            'user_stase_counts' =>  $userStaseCounts,
            'stases' => $stases,
            'units' => $units,
            'filters' => [
                'search' => $search,
                'units' => $unitsSelected,
                'unitSelected' => $unitSelected,
                'monthIndexSelected' => $monthIndexSelected ?? 0,
            ]
        ]);
    }

    public function statistic(Request $request, User $user): Response
    {
        // 
        $yearSelected = $request->input('yearSelected');
        $monthIndexSelected = $request->input('monthIndexSelected');
        $weekIndexSelected = $request->input('weekIndexSelected');

        // Ambil total user per unit
        $totalUsersPerUnit = User::withoutGlobalScopes()
            ->select('student_unit_id', DB::raw('COUNT(id) as total_users'))
            ->where('is_active_student', 1)
            ->groupBy('student_unit_id')
            ->pluck('total_users', 'student_unit_id'); // Menghasilkan array [unit_id => total_users]

        // Ambil jumlah user yang ada di week_monitors per unit (Gunakan LEFT JOIN agar unit tetap muncul meskipun 0%)
        $monitoredUsersPerUnit = Unit::select(
                'units.id',
                'units.name as name',
                DB::raw('COALESCE(COUNT(DISTINCT week_monitors.user_id), 0) as monitored_users')
            )
            // ->leftJoin('users', 'users.student_unit_id', '=', 'units.id')
            ->leftJoin('users', function ($join) {
                $join->on('users.student_unit_id', '=', 'units.id')
                     ->where('users.is_active_student', 1);
            })
            ->leftJoin('week_monitors', function ($join) use ($yearSelected, $monthIndexSelected, $weekIndexSelected) {
                $join->on('week_monitors.user_id', '=', 'users.id');
                
                // Tambahkan kondisi hanya di dalam LEFT JOIN
                if ($yearSelected) {
                    $join->where('week_monitors.year', $yearSelected);
                }
                if ($monthIndexSelected) {
                    $join->where('week_monitors.month', $monthIndexSelected);
                }
                if ($weekIndexSelected) {
                    $join->where('week_monitors.week_month', $weekIndexSelected);
                }
            })
            ->groupBy('units.id')
            ->get();

        // Hitung persentase user yang ada di week_monitors dibanding total user di unit
        $barData = $monitoredUsersPerUnit->map(function ($unit) use ($totalUsersPerUnit) {
            $totalUsers = $totalUsersPerUnit[$unit->id] ?? 1; // Hindari pembagian dengan 0
            return [
                'name' => $unit->name,
                'value' => round(($unit->monitored_users / $totalUsers) * 100, 2) // Hitung persen
            ];
        });

        // Hitung persentase user yang memiliki week_monitor dibanding total user
        $tableData = $monitoredUsersPerUnit->map(function ($unit) use ($totalUsersPerUnit) {
            $totalUsers = $totalUsersPerUnit[$unit->id] ?? 0;
            $monitoredUsers = $unit->monitored_users;
            $notMonitoredUsers = $totalUsers - $monitoredUsers;
            $percentage = $totalUsers > 0 ? round(($monitoredUsers / $totalUsers) * 100, 2) : 0;

            return [
                'name' => $unit->name,
                'total_users' => $totalUsers,
                'monitored_users' => $monitoredUsers,
                'not_monitored_users' => max($notMonitoredUsers, 0),
                'percentage' => $percentage
            ];
        });

        $weekMonitorStats = Unit::select(
                'units.name as name',
                DB::raw('COUNT(DISTINCT week_monitors.user_id) as total_monitored_users'),
                DB::raw('SUM(CASE WHEN week_monitors.workload_hours < 71 THEN 1 ELSE 0 END) as workload_below_71'),
                DB::raw('SUM(CASE WHEN week_monitors.workload_hours BETWEEN 71 AND 80 THEN 1 ELSE 0 END) as workload_71_to_80'),
                DB::raw('SUM(CASE WHEN week_monitors.workload_hours > 80 THEN 1 ELSE 0 END) as workload_above_80')
            )
            // ->leftJoin('users', 'users.student_unit_id', '=', 'units.id')
            ->leftJoin('users', function ($join) {
                $join->on('users.student_unit_id', '=', 'units.id')
                     ->where('users.is_active_student', 1);
            })
            ->leftJoin('week_monitors', function ($join) use ($yearSelected, $monthIndexSelected, $weekIndexSelected) {
                $join->on('week_monitors.user_id', '=', 'users.id');
                
                // Tambahkan kondisi hanya di dalam LEFT JOIN
                if ($yearSelected) {
                    $join->where('week_monitors.year', $yearSelected);
                }
                if ($monthIndexSelected) {
                    $join->where('week_monitors.month', $monthIndexSelected);
                }
                if ($weekIndexSelected) {
                    $join->where('week_monitors.week_month', $weekIndexSelected);
                }
            })
            ->groupBy('units.id')
            ->get();

        // $workloadPieRecord = Unit::leftJoin('users', 'users.student_unit_id', '=', 'units.id')
        $workloadPieRecord = Unit::leftJoin('users', function ($join) {
                $join->on('users.student_unit_id', '=', 'units.id')
                    ->where('users.is_active_student', 1);
            })
            ->leftJoin('week_monitors', function ($join) use ($yearSelected, $monthIndexSelected, $weekIndexSelected) {
                $join->on('week_monitors.user_id', '=', 'users.id');
        
                // Filter hanya di week_monitors
                if ($yearSelected) {
                    $join->where('week_monitors.year', $yearSelected);
                }
                if ($monthIndexSelected) {
                    $join->where('week_monitors.month', $monthIndexSelected);
                }
                if ($weekIndexSelected) {
                    $join->where('week_monitors.week_month', $weekIndexSelected);
                }
            })
            ->select(
                DB::raw('COALESCE(SUM(CASE WHEN week_monitors.workload_hours < 71 THEN 1 ELSE 0 END), 0) as workload_below_71'),
                DB::raw('COALESCE(SUM(CASE WHEN week_monitors.workload_hours BETWEEN 71 AND 80 THEN 1 ELSE 0 END), 0) as workload_71_to_80'),
                DB::raw('COALESCE(SUM(CASE WHEN week_monitors.workload_hours > 80 THEN 1 ELSE 0 END), 0) as workload_above_80')
            )
            ->first();
        
        // Format data untuk Pie Chart
        $pieChartData = [
            ['name' => 'Workload < 71', 'value' => $workloadPieRecord->workload_below_71],
            ['name' => 'Workload 71 - 80', 'value' => $workloadPieRecord->workload_71_to_80],
            ['name' => 'Workload > 80', 'value' => $workloadPieRecord->workload_above_80]
        ];

        // Data untuk Pie Chart (Distribusi workload_hours per unit)
        $pieData = Unit::select(
                    'units.name as name', 
                    DB::raw('COUNT(DISTINCT week_monitors.user_id) as value') // Menghitung user unik dalam week_monitors
                )
                // ->join('users', 'users.student_unit_id', '=', 'units.id')
                ->leftJoin('users', function ($join) {
                    $join->on('users.student_unit_id', '=', 'units.id')
                         ->where('users.is_active_student', 1);
                })
                ->leftJoin('week_monitors', function ($join) use ($yearSelected, $monthIndexSelected, $weekIndexSelected) {
                    $join->on('week_monitors.user_id', '=', 'users.id');
                    
                    // Tambahkan kondisi hanya di dalam LEFT JOIN
                    if ($yearSelected) {
                        $join->where('week_monitors.year', $yearSelected);
                    }
                    if ($monthIndexSelected) {
                        $join->where('week_monitors.month', $monthIndexSelected);
                    }
                    if ($weekIndexSelected) {
                        $join->where('week_monitors.week_month', $weekIndexSelected);
                    }
                })
                ->groupBy('units.id')
                ->orderByDesc('value')
                ->get();
    

        return Inertia::render('Activities/Statistic', [
            'barData' => $barData,
            'pieData' => $pieData,
            'tableData' => $tableData,
            'weekMonitorStats' => $weekMonitorStats,
            'pieChartData' => $pieChartData,
            'filters' => [
                'monthIndexSelected' =>  (int) $monthIndexSelected - 1 ?? null, // karena index 0 merupakan januari tapi 0 ketika dikirim jadi null
                'yearSelected' => (int) $yearSelected ?? null,
                'weekIndexSelected' => (int) $weekIndexSelected ?? null,
            ]
        ]);

    }

    // tampilkan view Activity/Schedule
    public function schedule(Request $request, User $user, int $month_number, int $year): Response
    {
        //
        try{
            $schedule_document_path = null;
            $schedule = Schedule::where([
                'unit_id' => $user->student_unit_id,
                'month_number' => $month_number + 1,
                'year' => $year,
            ])->first();
            if (!empty($schedule)) {
                $schedule_document_path = $schedule->document_path;
            }
            return Inertia::render('Activities/Schedule', [
                'month_number' => $month_number,
                'year' => $year,
                'schedule' => $schedule_document_path,
            ]);
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    public function allowActivity(Activity $activity): RedirectResponse
    {
        //
        try {
            $activity->update([
                'is_allowed' => 1,
            ]);

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Activity allow successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }
}
