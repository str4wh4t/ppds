<?php

namespace App\Http\Controllers;

use App\DTOs\Activity\CreateActivityData;
use App\DTOs\Activity\UpdateActivityData;
use App\Http\Requests\Activity\StoreRequest;
use App\Http\Requests\Activity\UpdateRequest;
use App\Models\Activity;
use App\Models\Location;
use App\Models\Schedule;
use App\Models\Stase;
use App\Models\Unit;
use App\Models\User;
use App\Models\WeekMonitor;
use App\Services\Activity\CreateActivityService;
use App\Services\Activity\SplitCheckoutService;
use App\Services\Activity\UpdateActivityService;
use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    public function __construct(
        private readonly CreateActivityService $createActivityService,
        private readonly SplitCheckoutService $splitCheckoutService,
        private readonly UpdateActivityService $updateActivityService,
    ) {
        // Menambahkan Policy untuk otorisasi update dan delete
        $this->middleware('can:create,\App\Models\Activity')->only('store');
        $this->middleware('can:update,activity')->only(['update', 'edit']);
        $this->middleware('can:checkout,activity')->only('checkout');
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
            if (! empty($array)) {
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

        $adminUnitIds = $request->user()->adminProdiUnitIds();
        if ($adminUnitIds !== null) {
            if ($adminUnitIds->isEmpty()) {
                $activities = $activities->whereRaw('0 = 1');
            } else {
                $activities = $activities->whereHas('user', function ($query) use ($adminUnitIds) {
                    $query->whereIn('student_unit_id', $adminUnitIds);
                });
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

        $units = $adminUnitIds !== null ? $request->user()->adminUnits : Unit::all();
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
            ],
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
            $activityData = CreateActivityData::fromRequest($request);
            $this->createActivityService->execute($activityData);

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
            $data = UpdateActivityData::fromUpdateRequest($request);
            $this->updateActivityService->execute($activity, $data);

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Activity updated successfully');
        } catch (ModelNotFoundException $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), 'Stase atau lokasi tidak valid untuk kombinasi unit dan stase ini.');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    /**
     * Activity check-out (khusus checkout overdue oleh admin).
     *
     * Endpoint ini menyimpan `finish_time` ke `end_date` dan selalu mengubah
     * `is_overdue_checkout` menjadi `false`.
     */
    public function checkout(Request $request, Activity $activity): RedirectResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'date' => 'required|date_format:Y-m-d',
                'finish_time' => [
                    'required',
                    'string',
                    // support: "HH:mm" (00-23) dan "24:00"
                    'regex:/^(?:(?:[01]?[0-9]|2[0-3]):[0-5][0-9]|24:00)$/',
                ],
            ]);

            $validator->after(function ($validator) use ($request, $activity) {
                $startDate = Carbon::parse($activity->start_date);

                $date = Carbon::parse($request->input('date'));
                $finishTime = $request->input('finish_time');

                if ($finishTime === '24:00') {
                    $finishDate = $date->copy()->addDay()->setTime(0, 0, 0);
                } else {
                    [$hour, $minute] = array_map('intval', explode(':', $finishTime));
                    $finishDate = $date->copy()->setTime($hour, $minute, 0);
                }

                if ($finishDate->lte($startDate)) {
                    $validator->errors()->add('finish_time', 'Waktu selesai tidak boleh kurang dari atau sama dengan waktu mulai.');

                    return;
                }

                $overlapExists = Activity::where('user_id', $activity->user_id)
                    ->where('is_generated', 0)
                    ->where('id', '!=', $activity->id)
                    ->where('start_date', '<', $finishDate)
                    ->where('end_date', '>', $startDate)
                    ->exists();

                if ($overlapExists) {
                    $validator->errors()->add('finish_time', 'Rentang waktu checkout bentrok dengan activity lain.');
                }
            });

            $validator->validate();

            DB::transaction(function () use ($request, $activity) {
                $date = Carbon::parse($request->input('date'));
                $finishTime = $request->input('finish_time');

                if ($finishTime === '24:00') {
                    $finishDate = $date->copy()->addDay()->setTime(0, 0, 0);
                } else {
                    [$hour, $minute] = array_map('intval', explode(':', $finishTime));
                    $finishDate = $date->copy()->setTime($hour, $minute, 0);
                }

                $this->splitCheckoutService->execute($activity, $finishDate);
            });

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Activity checked out successfully');
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
                    [$prev_activity_hours, $prev_activity_minutes, $prev_activity_seconds] = explode(':', $activity->time_spend);
                    $updated_workload_hours = $weekMonitor->workload_hours - $prev_activity_hours;
                    if ($updated_workload_hours <= 80) {
                        Activity::where('is_allowed', 0)
                            ->where('user_id', $request->user()->id)
                            ->where('week_group_id', $activity->week_group_id)
                            ->update(['is_allowed' => 1]);
                    }
                }
                $activity->delete();
                if ($updated_workload_hours === 0) {
                    Activity::withoutEvents(function () use ($request, $activity) {
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
    public function calendar(Request $request, User $user, ?int $month_number = null, ?int $year = null): Response
    {
        $weekGroupId = $request->input('weekGroupId');
        $userId = $request->input('userId');
        $month = $month_number ?? date('m') - 1;
        $year = $year ?? date('Y');
        // dd($weekGroupId);
        if (! empty($weekGroupId)) {
            $year = substr($weekGroupId, 0, 4);
            $week = substr($weekGroupId, 4, 2);
            $date = new DateTime;
            $date->setISODate($year, $week);
            $year = $date->format('Y');
            $month = $date->format('m');
            $month = $month - 1;
        }

        if ($request->user()->hasRole('student')) {
            $user = $request->user();
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
            ],
        ]);
    }

    // buat fungsi untuk menampilkan laporan dengan nama report dengan param request dan user
    public function report(Request $request, User $user): Response
    {
        $search = $request->input('search');
        $unitsSelected = $request->input('units');
        $unitSelected = $request->input('unitSelected');
        $monthIndexSelected = $request->input('monthIndexSelected');

        $adminUnitIds = $request->user()->adminProdiUnitIds();

        if (! $request->user()->hasRole('student')) {
            if ($adminUnitIds !== null) {
                if ($adminUnitIds->isEmpty()) {
                    $unitSelected = null;
                } elseif ($unitSelected === null || ! $adminUnitIds->contains((int) $unitSelected)) {
                    $unitSelected = $adminUnitIds->first();
                }
            } else {
                $unitFirst = Unit::firstOrFail();
                if ($unitSelected == null) {
                    $unitSelected = $unitFirst->id;
                }
            }
        }

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

        if ($adminUnitIds !== null) {
            if ($adminUnitIds->isEmpty()) {
                $activities = $activities->whereRaw('0 = 1');
            } else {
                $activities = $activities->whereHas('user', function ($query) use ($adminUnitIds) {
                    $query->whereIn('student_unit_id', $adminUnitIds);
                });
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
            if (! isset($userStaseCounts[$userName])) {
                $userStaseCounts[$userName] = [
                    'user' => $activity->user,
                    'stases' => [],
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
            if (! isset($userStaseCounts[$userName]['stases'][$staseName])) {
                $userStaseCounts[$userName]['stases'][$staseName] = [
                    'stase_id' => $activity->unitStase->stase->id,
                    'name' => $staseName,
                    'count' => 0,
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
            } elseif ($unitSelected === null) {
                $query->whereRaw('0 = 1');
            } else {
                $query->where('units.id', $unitSelected);
            }
        })
            // ->join('stase_locations', 'stases.stase_location_id', '=', 'stase_locations.id')
            // ->selectRaw('stases.id,stases.name,stase_locations.name AS location')->get();
            ->get();

        $units = $adminUnitIds !== null ? $request->user()->adminUnits : Unit::all();

        // tampilkan halaman report dengan data activities dan stases
        return Inertia::render('Activities/Report', [
            'user_stase_counts' => $userStaseCounts,
            'stases' => $stases,
            'units' => $units,
            'filters' => [
                'search' => $search,
                'units' => $unitsSelected,
                'unitSelected' => $unitSelected,
                'monthIndexSelected' => $monthIndexSelected ?? 0,
            ],
        ]);
    }

    public function statistic(Request $request, User $user): Response
    {
        //
        $yearSelected = $request->input('yearSelected');
        $monthIndexSelected = $request->input('monthIndexSelected');
        $weekIndexSelected = $request->input('weekIndexSelected');

        $adminUnitScope = $request->user()->adminProdiUnitIds();

        if ($adminUnitScope !== null && $adminUnitScope->isEmpty()) {
            return Inertia::render('Activities/Statistic', [
                'barData' => collect(),
                'pieData' => collect(),
                'tableData' => collect(),
                'weekMonitorStats' => collect(),
                'pieChartData' => [
                    ['name' => 'Workload < 71', 'value' => 0],
                    ['name' => 'Workload 71 - 80', 'value' => 0],
                    ['name' => 'Workload > 80', 'value' => 0],
                ],
                'filters' => [
                    'monthIndexSelected' => (int) $monthIndexSelected - 1 ?? null,
                    'yearSelected' => (int) $yearSelected ?? null,
                    'weekIndexSelected' => (int) $weekIndexSelected ?? null,
                ],
            ]);
        }

        // Ambil total user per unit
        $totalUsersPerUnit = User::withoutGlobalScopes()
            ->select('student_unit_id', DB::raw('COUNT(id) as total_users'))
            ->where('is_active_student', 1)
            ->when($adminUnitScope, fn ($q) => $q->whereIn('student_unit_id', $adminUnitScope))
            ->groupBy('student_unit_id')
            ->pluck('total_users', 'student_unit_id'); // Menghasilkan array [unit_id => total_users]

        // Ambil jumlah user yang ada di week_monitors per unit (Gunakan LEFT JOIN agar unit tetap muncul meskipun 0%)
        $monitoredUsersPerUnit = Unit::select(
            'units.id',
            'units.name as name',
            DB::raw('COALESCE(COUNT(DISTINCT week_monitors.user_id), 0) as monitored_users')
        )
            ->when($adminUnitScope, fn ($q) => $q->whereIn('units.id', $adminUnitScope))
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
                'value' => round(($unit->monitored_users / $totalUsers) * 100, 2), // Hitung persen
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
                'percentage' => $percentage,
            ];
        });

        $weekMonitorStats = Unit::select(
            'units.name as name',
            DB::raw('COUNT(DISTINCT week_monitors.user_id) as total_monitored_users'),
            DB::raw('SUM(CASE WHEN week_monitors.workload_hours < 71 THEN 1 ELSE 0 END) as workload_below_71'),
            DB::raw('SUM(CASE WHEN week_monitors.workload_hours BETWEEN 71 AND 80 THEN 1 ELSE 0 END) as workload_71_to_80'),
            DB::raw('SUM(CASE WHEN week_monitors.workload_hours > 80 THEN 1 ELSE 0 END) as workload_above_80')
        )
            ->when($adminUnitScope, fn ($q) => $q->whereIn('units.id', $adminUnitScope))
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
        $workloadPieRecord = Unit::query()
            ->when($adminUnitScope, fn ($q) => $q->whereIn('units.id', $adminUnitScope))
            ->leftJoin('users', function ($join) {
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
            ['name' => 'Workload > 80', 'value' => $workloadPieRecord->workload_above_80],
        ];

        // Data untuk Pie Chart (Distribusi workload_hours per unit)
        $pieData = Unit::select(
            'units.name as name',
            DB::raw('COUNT(DISTINCT week_monitors.user_id) as value') // Menghitung user unik dalam week_monitors
        )
            ->when($adminUnitScope, fn ($q) => $q->whereIn('units.id', $adminUnitScope))
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
                'monthIndexSelected' => (int) $monthIndexSelected - 1 ?? null, // karena index 0 merupakan januari tapi 0 ketika dikirim jadi null
                'yearSelected' => (int) $yearSelected ?? null,
                'weekIndexSelected' => (int) $weekIndexSelected ?? null,
            ],
        ]);
    }

    // tampilkan view Activity/Schedule
    public function schedule(Request $request, User $user, int $month_number, int $year): Response|RedirectResponse
    {
        //
        try {
            $schedule_document_path = null;
            $schedule = Schedule::where([
                'unit_id' => $user->student_unit_id,
                'month_number' => $month_number + 1,
                'year' => $year,
            ])->first();
            if (! empty($schedule)) {
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
