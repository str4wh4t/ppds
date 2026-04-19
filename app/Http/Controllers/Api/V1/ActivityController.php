<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Activity\CreateActivityData;
use App\DTOs\Activity\UpdateActivityData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Activity\StoreRequest;
use App\Http\Requests\Api\V1\ActivityUpdateRequest;
use App\Models\Activity;
use App\Models\StaseLocation;
use App\Models\UnitStase;
use App\Models\User;
use App\Models\WeekMonitor;
use App\Services\Activity\CreateActivityService;
use App\Services\Activity\SplitCheckoutService;
use App\Services\Activity\UpdateActivityService;
use Carbon\Carbon;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ActivityController extends Controller
{
    public function __construct(
        private readonly CreateActivityService $createActivityService,
        private readonly SplitCheckoutService $splitCheckoutService,
        private readonly UpdateActivityService $updateActivityService,
    ) {
        $this->middleware('can:create,\App\Models\Activity')->only('store');
        $this->middleware('can:checkout,activity')->only('checkOut');
        $this->middleware('can:delete,activity')->only('destroy');
    }

    /**
     * List activities
     *
     * Dipakai untuk menampilkan daftar aktivitas (Logbook List) dalam format JSON.
     * Untuk role `student`, data selalu diambil dari user pada token (abaikan `user_id`).
     *
     * @group Activities API
     *
     * @authenticated
     *
     * @bodyParam user_id integer Optional ID user yang aktivitinya ingin ditampilkan (hanya untuk role `system`)
     * @bodyParam search string Optional Pencarian substring pada **nama aktivitas** (`activities.name`).
     * @bodyParam units string Optional filter unit (JSON).
     *   Format yang didukung:
     *   - array nama unit: ["Kedokteran", "Keperawatan"]
     *   - array objek: [{"name":"Kedokteran"},{"name":"Keperawatan"}]
     * @bodyParam per_page integer Optional jumlah per halaman. Default: 10 (maks 50)
     * @bodyParam year integer Optional Filter `start_date` menurut tahun (1970–2100). Dapat dikombinasikan dengan `month` (tanpa `date`), atau dengan `date` (nilai harus sama dengan tahun pada `date`).
     * @bodyParam month integer Optional Filter `start_date` menurut bulan (1–12). Dapat dikombinasikan dengan `year`, atau dengan `date` (nilai harus sama dengan bulan pada `date`).
     * @bodyParam date string Optional Filter satu hari: `Y-m-d` pada `start_date`. Boleh bersama `year`/`month` jika konsisten (satu hari terpresisi).
     *
     * @response 401 { "message": "Unauthenticated." }
     * @response 422 { "message": "Validation error", "errors": [] }
     */
    #[Response(200, 'List activities', type: 'array{data: array<array{id: int, user_id: int, name: string, type: string, start_date: string, end_date: string, is_allowed: int}>, current_page: int, per_page: int, total: int, last_page: int}')]
    #[Response(401, 'Unauthenticated', type: 'array{message: string}')]
    #[Response(422, 'Validation error', type: 'array{message: string, errors: array}')]
    public function list(Request $request): JsonResponse
    {
        Validator::make($request->all(), [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'units' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'year' => ['nullable', 'integer', 'min:1970', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ])->after(function ($validator) use ($request) {
            if (! $request->filled('date')) {
                return;
            }
            $parsed = Carbon::createFromFormat('Y-m-d', $request->input('date'))->startOfDay();
            if ($request->filled('year') && (int) $request->input('year') !== (int) $parsed->year) {
                $validator->errors()->add('year', 'Field year harus sama dengan tahun pada date.');
            }
            if ($request->filled('month') && (int) $request->input('month') !== (int) $parsed->month) {
                $validator->errors()->add('month', 'Field month harus sama dengan bulan pada date.');
            }
        })->validate();

        $search = $request->input('search');
        if (is_string($search)) {
            $search = trim($search);
        }
        $unitsSelected = $request->input('units');
        $perPage = (int) ($request->input('per_page') ?? 10);

        if ($request->user()->hasRole('student')) {
            $targetUserId = $request->user()->id;
        } elseif ($request->user()->hasRole('system')) {
            $targetUserId = (int) ($request->input('user_id') ?? $request->user()->id);
        } else {
            $targetUserId = $request->user()->id;
        }

        $unitNames = null;
        if (! empty($unitsSelected)) {
            $decoded = json_decode($unitsSelected, true);
            if (is_array($decoded) && ! empty($decoded)) {
                // support: ["Unit A", "Unit B"] OR [{"name":"Unit A"}, {"name":"Unit B"}]
                $unitNames = array_map(function ($item) {
                    return is_array($item) ? ($item['name'] ?? null) : $item;
                }, $decoded);
                $unitNames = array_values(array_filter($unitNames, fn ($v) => ! empty($v)));
            }
        }

        $query = Activity::query()
            ->where('user_id', $targetUserId)
            ->when($search !== null && $search !== '', function ($q) use ($search) {
                $q->where('name', 'like', '%'.addcslashes($search, '%_\\').'%');
            })
            ->when($unitNames, function ($q) use ($unitNames) {
                $q->whereHas('user.studentUnit', function ($unitQuery) use ($unitNames) {
                    $unitQuery->whereIn('name', $unitNames);
                });
            });

        $this->applyActivityListDateFilters($query, $request);

        $query->with('user', 'user.studentUnit', 'unitStase', 'stase', 'staseLocation', 'location', 'dosenUser');

        $activities = $query->paginate($perPage);

        return response()->json($activities);
    }

    /**
     * Activity list by week_group_id
     *
     * week_group_id menggunakan pola ISO: tahun ISO + nomor minggu, mis. 202614.
     *
     * Pola `user_id` sama seperti `activity.list`: role `student` memakai token; `system` boleh kirim `user_id`.
     *
     * @group Activities API
     *
     * @authenticated
     *
     * @header Authorization string required Gunakan format: Bearer {access_token}
     *
     * @bodyParam week_group_id integer required Contoh: 202614
     * @bodyParam user_id integer Optional Hanya role `system` (sama seperti list).
     * @bodyParam search string Optional Substring pada nama aktivitas.
     * @bodyParam per_page integer Optional Default 10, maks 50.
     */
    #[Response(200, 'Paginated activities by week group', type: 'array{data: array, current_page: int, per_page: int, total: int, last_page: int}')]
    #[Response(401, 'Unauthenticated', type: 'array{message: string}')]
    #[Response(422, 'Validation error', type: 'array{message: string, errors: array}')]
    public function byWeekGroup(Request $request): JsonResponse
    {
        Validator::make($request->all(), [
            'week_group_id' => ['required', 'integer', 'min:1'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ])->validate();

        $search = $request->input('search');
        if (is_string($search)) {
            $search = trim($search);
        }
        $perPage = (int) ($request->input('per_page') ?? 10);

        if ($request->user()->hasRole('student')) {
            $targetUserId = $request->user()->id;
        } elseif ($request->user()->hasRole('system')) {
            $targetUserId = (int) ($request->input('user_id') ?? $request->user()->id);
        } else {
            $targetUserId = $request->user()->id;
        }

        $weekGroupId = (int) $request->input('week_group_id');

        $query = Activity::query()
            ->where('user_id', $targetUserId)
            ->where('week_group_id', $weekGroupId)
            ->when($search !== null && $search !== '', function ($q) use ($search) {
                $q->where('name', 'like', '%'.addcslashes($search, '%_\\').'%');
            })
            ->with('user', 'user.studentUnit', 'unitStase', 'stase', 'staseLocation', 'location', 'dosenUser');

        $activities = $query->paginate($perPage);

        return response()->json($activities);
    }

    /**
     * Detail activity by id
     *
     * Relasi dimuat sama seperti list: `user`, `user.studentUnit`, `unitStase`, `stase`, `staseLocation`, `location`, `dosenUser`.
     * Hanya pemilik activity atau role `system` yang dapat mengakses.
     *
     * @group Activities API
     *
     * @authenticated
     *
     * @header Authorization string required Gunakan format: Bearer {access_token}
     *
     * @urlParam activity int required ID activity. Example: 321
     */
    #[Response(200, 'Activity detail', type: 'array{data: array{id: int, user_id: int, name: string, type: string, start_date: string, end_date: string}}')]
    #[Response(401, 'Unauthenticated', type: 'array{message: string}')]
    #[Response(403, 'Forbidden', type: 'array{message: string}')]
    public function show(Request $request, Activity $activity): JsonResponse
    {
        $user = $request->user();
        if ((int) $activity->user_id !== (int) $user->id && ! $user->hasRole('system')) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke activity ini.',
            ], 403);
        }

        $activity->load(['user', 'user.studentUnit', 'unitStase', 'stase', 'staseLocation', 'location', 'dosenUser']);

        return response()->json([
            'data' => $activity,
        ]);
    }

    /**
     * Update activity by ID
     *
     * Input berupa nama, deskripsi, jenis, stase, lokasi stase, dosen, serta rentang waktu.
     * Logika validasi bentrok waktu dan batas beban kerja mengikuti form update web (`ActivityController@update`).
     * **Tidak** mengubah koordinat GPS (`latitude` / `longitude`) maupun path foto check-in / check-out.
     *
     * Aturan jam: `finish_time` harus **tidak sebelum** `start_time` (string `H:i` / `24:00`); **sama** (durasi nol) diperbolehkan.
     *
     * @group Activities API
     *
     * @authenticated
     *
     * @header Authorization string required Gunakan format: Bearer {access_token}
     *
     * @urlParam activity int required ID activity. Example: 321
     */
    #[Response(200, 'Activity updated', type: 'array{message: string, data: array}')]
    #[Response(401, 'Unauthenticated', type: 'array{message: string}')]
    #[Response(403, 'Forbidden', type: 'array{message: string}')]
    #[Response(422, 'Validation or process error', type: 'array{message: string, errors?: array}')]
    public function update(ActivityUpdateRequest $request, Activity $activity): JsonResponse
    {
        try {
            $data = UpdateActivityData::fromUpdateRequest($request);
            $activity = $this->updateActivityService->execute($activity, $data, $request->user());

            $activity->load(['user', 'user.studentUnit', 'unitStase', 'stase', 'staseLocation', 'location', 'dosenUser']);

            return response()->json([
                'message' => 'Activity updated successfully',
                'data' => $activity,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Stase atau lokasi tidak valid untuk kombinasi unit dan stase ini.',
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Calendar generate days
     *
     * Dipakai halaman kalender Inertia (`Calendar.vue`) via `POST` dengan body `year` dan `month`.
     * Nilai `month` mengikuti indeks JavaScript (0 = Januari, 11 = Desember); di server ditambah 1 untuk Carbon.
     *
     * @group Calendar API
     *
     * @authenticated
     *
     * @urlParam user int required ID user yang kalendernya diminta. Untuk `student` akan diabaikan karena user selalu diambil dari token.
     *
     * @bodyParam year int required Tahun kalender. Example: 2026
     * @bodyParam month int required Indeks bulan 0–11 (0 = Januari). Example: 3
     *
     * @response 403 {
     *   "message": "Tidak bisa melihat tahun 2023"
     * }
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "year": ["The year field is required."]
     *   }
     * }
     */
    #[Response(200, 'Calendar generate days', type: 'array{days: array<array{date: string, isCurrentMonth: bool, events: array<array{id: int, name: string, start_date: string, end_date: string, is_allowed: int}>, isWarning: bool, isDanger: bool, isDangerBold: bool, isToday: bool, workload: string, workload_hours: int}>}')]
    #[Response(403, 'Forbidden', type: 'array{message: string}')]
    #[Response(401, 'Unauthenticated', type: 'array{message: string}')]
    #[Response(422, 'Validation error', type: 'array{message: string, errors: array}')]
    public function calendarGenerateDays(Request $request, User $user): JsonResponse
    {
        Validator::make($request->all(), [
            'year' => ['required', 'integer', 'min:2024'],
            'month' => ['required', 'integer', 'between:0,11'],
        ])->validate();

        // Tentukan user target:
        // - Jika student: selalu pakai user dari token
        if ($request->user()->hasRole('student')) {
            $user = $request->user();
        }

        $year = $request->year;
        $month = $request->month + 1; // Tambahkan 1 untuk menyesuaikan dengan format PHP
        $days = [];

        // Tentukan tanggal pertama dan terakhir dari bulan yang diminta
        $startDate = Carbon::createFromDate($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        if ($startDate->year == '2023') {
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

            $today = Carbon::today();
            $user_id = $user->id;

            $activities = Activity::where('user_id', $user_id)
                ->whereDate('start_date', $date)
                ->with('weekMonitor', function ($query) use ($user_id) {
                    $query->where('user_id', $user_id);
                })
                ->get();

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
                ->where('week_group_id', $date->isoWeekYear.$date->isoWeek)
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
                        break;
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
            'days' => $days,
        ]);
    }

    /**
     * Create activity
     *
     * Endpoint ini menerima input yang sama seperti form logbook kalender
     * di `EntryActivity.vue` (`name`, `type`, `date`, `start_time`, `finish_time`,
     * `description`, `stase_id`, `location_id`, `dosen_user_id`).
     *
     * Aturan jam: `finish_time` harus **tidak sebelum** `start_time` (string `H:i` / `24:00`); **sama** (durasi nol) diperbolehkan.
     *
     * @group Activities API
     *
     * @authenticated
     *
     * @header Authorization string required Gunakan format: Bearer {access_token}
     *
     * @bodyParam name string required Nama aktivitas. Example: Jaga malam IGD
     * @bodyParam type string required Jenis aktivitas (jaga/nonjaga). Example: nonjaga
     * @bodyParam date string required Tanggal aktivitas (Y-m-d). Example: 2026-04-15
     * @bodyParam start_time string required Jam mulai (H:i). Example: 08:00
     * @bodyParam finish_time string required Jam selesai (H:i atau 24:00). Tidak boleh sebelum `start_time`; boleh sama untuk durasi nol. Example: 12:30
     * @bodyParam description string required Deskripsi aktivitas. Example: Visit pasien rawat inap
     * @bodyParam stase_id integer ID stase (wajib jika type=nonjaga). Example: 3
     * @bodyParam location_id integer ID lokasi (wajib jika type=nonjaga). Example: 7
     * @bodyParam dosen_user_id integer ID user dosen pembimbing. Example: 12
     * @bodyParam latitude number Latitude lokasi saat membuat aktivitas (nullable). Example: -7.050549
     * @bodyParam longitude number Longitude lokasi saat membuat aktivitas (nullable). Example: 110.393465
     *
     * @response 201 {
     *   "message": "Activity created successfully",
     *   "data": {
     *     "id": 123,
     *     "name": "Jaga malam IGD",
     *     "type": "nonjaga",
     *     "start_date": "2026-04-15T08:00:00.000000Z",
     *     "end_date": "2026-04-15T12:30:00.000000Z",
     *     "time_spend": "04:30:00"
     *   }
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "start_time": ["Waktu yang dipilih bentrok dengan aktifitas yang lain."]
     *   }
     * }
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     */
    #[Response(201, 'Activity created successfully', type: 'array{message: string, data: array{id: int, name: string, type: string, start_date: string, end_date: string, time_spend: string}}')]
    #[Response(422, 'Validation/process error', type: 'array{message: string}')]
    #[Response(401, 'Unauthenticated', type: 'array{message: string}')]
    public function store(StoreRequest $request): JsonResponse
    {
        try {
            $activityData = CreateActivityData::fromRequest($request);
            $activity = $this->createActivityService->execute($activityData);
            $activity->load(['stase', 'location', 'dosenUser']);

            return response()->json([
                'message' => 'Activity created successfully',
                'data' => $activity,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Activity check-in
     *
     * Membuat activity baru dengan datetime penuh `start_at`.
     * `finish_at` akan diisi saat checkout.
     *
     * Activity terbuka yang diperiksa untuk batas “belum checkout” hanya yang `is_overdue_checkout` masih false.
     *
     * @group Activities API
     *
     * @authenticated
     *
     * @header Authorization string required Gunakan format: Bearer {access_token}
     *
     * @bodyParam name string required Nama aktivitas. Example: Jaga malam IGD
     * @bodyParam type string required Jenis aktivitas (jaga/nonjaga). Example: nonjaga
     * @bodyParam start_at string required Datetime mulai (Y-m-d H:i:s). Example: 2026-04-15 08:00:00
     * @bodyParam description string required Deskripsi aktivitas. Example: Visit pasien rawat inap
     * @bodyParam stase_id integer ID stase (wajib jika type=nonjaga). Example: 3
     * @bodyParam location_id integer ID lokasi (wajib jika type=nonjaga). Example: 7
     * @bodyParam dosen_user_id integer ID user dosen pembimbing. Example: 12
     * @bodyParam latitude number required Latitude lokasi saat check-in. Example: -7.050549
     * @bodyParam longitude number required Longitude lokasi saat check-in. Example: 110.393465
     * @bodyParam photo file required Foto bukti saat check-in (jpg/png/webp, max 500KB). Gunakan multipart/form-data.
     */
    #[Response(201, 'Activity check-in successful', type: 'array{message: string, data: array{id: int, name: string, start_date: string, end_date: string, time_spend: string}}')]
    #[Response(422, 'Validation/process error', type: 'array{message: string, errors?: array}')]
    #[Response(401, 'Unauthenticated', type: 'array{message: string}')]
    public function checkIn(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:jaga,nonjaga'],
            'start_at' => ['required', 'date_format:Y-m-d H:i:s'],
            'description' => ['required', 'string'],
            'stase_id' => ['nullable', 'integer', 'exists:stases,id', 'required_if:type,nonjaga'],
            'location_id' => ['nullable', 'integer', 'required_if:type,nonjaga'],
            'dosen_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:500'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $startDate = Carbon::createFromFormat('Y-m-d H:i:s', $request->input('start_at'));

            $openActivities = Activity::where('user_id', $request->user()->id)
                ->where('is_generated', 0)
                ->where('is_overdue_checkout', false)
                ->whereColumn('start_date', 'end_date')
                ->where('time_spend', '00:00:00')
                ->get();

            $hasNonOverdueOpenActivity = false;
            foreach ($openActivities as $openActivity) {
                if ($openActivity->isOverdueCheckoutByElapsedHours()) {
                    if (! $openActivity->is_overdue_checkout) {
                        $openActivity->update(['is_overdue_checkout' => true]);
                    }

                    continue;
                }
                $hasNonOverdueOpenActivity = true;
            }

            if ($hasNonOverdueOpenActivity) {
                $validator->errors()->add('start_at', 'Masih ada activity check-in yang belum checkout (belum melewati 24 jam).');
            }

            $overlapExists = Activity::where('user_id', $request->user()->id)
                ->where('is_generated', 0)
                ->where('start_date', '<=', $startDate)
                ->where('end_date', '>=', $startDate)
                ->exists();

            if ($overlapExists) {
                $validator->errors()->add('start_at', 'Waktu check-in bentrok dengan activity lain.');
            }
        });

        $validator->validate();

        try {
            $checkinYear = (int) Carbon::createFromFormat('Y-m-d H:i:s', $request->input('start_at'))->year;
            $checkinPhotoPath = $request->file('photo')->store(
                $this->publicActivityPhotoDirectory('checkin', $checkinYear),
                'public'
            );

            $activityData = new CreateActivityData(
                userId: $request->user()->id,
                studentUnitId: $request->user()->student_unit_id,
                name: $request->input('name'),
                type: $request->input('type'),
                date: Carbon::createFromFormat('Y-m-d H:i:s', $request->input('start_at'))->format('Y-m-d'),
                startTime: Carbon::createFromFormat('Y-m-d H:i:s', $request->input('start_at'))->format('H:i'),
                finishTime: Carbon::createFromFormat('Y-m-d H:i:s', $request->input('start_at'))->format('H:i'),
                description: $request->input('description'),
                staseId: $request->input('stase_id'),
                locationId: $request->input('location_id'),
                dosenUserId: $request->input('dosen_user_id'),
                latitude: (float) $request->input('latitude'),
                longitude: (float) $request->input('longitude'),
                checkinPhotoPath: $checkinPhotoPath,
                createdVia: 'api',
                deviceInfo: [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'accept_language' => $request->header('Accept-Language'),
                ],
            );

            $activity = $this->createActivityService->execute($activityData);
            $activity->load(['stase', 'location', 'dosenUser']);

            return response()->json([
                'message' => 'Activity check-in successful',
                'data' => $activity,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Activity check-out
     *
     * Menutup activity hasil check-in dengan mengisi `finish_at`.
     *
     * @group Activities API
     *
     * @authenticated
     *
     * @header Authorization string required Gunakan format: Bearer {access_token}
     *
     * @urlParam activity int required ID activity hasil check-in. Example: 123
     *
     * @bodyParam finish_at string required Datetime selesai (Y-m-d H:i:s). Example: 2026-04-15 12:30:00
     * @bodyParam latitude number required Latitude lokasi saat check-out. Example: -7.050549
     * @bodyParam longitude number required Longitude lokasi saat check-out. Example: 110.393465
     * @bodyParam photo file required Foto bukti saat check-out (jpg/png/webp, max 500KB). Gunakan multipart/form-data.
     */
    #[Response(200, 'Activity check-out successful', type: 'array{message: string, data: array{id: int, name: string, start_date: string, end_date: string, time_spend: string}}')]
    #[Response(422, 'Validation/process error', type: 'array{message: string, errors?: array}')]
    #[Response(401, 'Unauthenticated', type: 'array{message: string}')]
    #[Response(403, 'Forbidden', type: 'array{message: string}')]
    public function checkOut(Request $request, Activity $activity): JsonResponse
    {
        if ($activity->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke activity ini.',
            ], 403);
        }

        if ($activity->is_generated) {
            return response()->json([
                'message' => 'Activity generated tidak bisa di-checkout.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'finish_at' => ['required', 'date_format:Y-m-d H:i:s'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:500'],
        ]);

        $validator->after(function ($validator) use ($request, $activity) {
            $startDate = Carbon::parse($activity->start_date);
            $finishDate = Carbon::createFromFormat('Y-m-d H:i:s', $request->input('finish_at'));

            if ($activity->isOverdueCheckoutByElapsedHours()) {
                if (! $activity->is_overdue_checkout) {
                    $activity->update(['is_overdue_checkout' => true]);
                }

                $validator->errors()->add('finish_at', 'Activity sudah 24 jam atau lebih tanpa checkout (lupa checkout). Hubungi admin.');

                return;
            }

            if ($finishDate->lte($startDate)) {
                $validator->errors()->add('finish_at', 'Waktu selesai tidak boleh kurang dari atau sama dengan waktu mulai.');

                return;
            }

            $overlapExists = Activity::where('user_id', $request->user()->id)
                ->where('is_generated', 0)
                ->where('id', '!=', $activity->id)
                ->where('start_date', '<', $finishDate)
                ->where('end_date', '>', $startDate)
                ->exists();

            if ($overlapExists) {
                $validator->errors()->add('finish_at', 'Rentang waktu checkout bentrok dengan activity lain.');
            }
        });

        $validator->validate();

        try {
            $finishDate = Carbon::createFromFormat('Y-m-d H:i:s', $request->input('finish_at'));
            $checkoutPhotoPath = $request->file('photo')->store(
                $this->publicActivityPhotoDirectory('checkout', (int) $finishDate->year),
                'public'
            );
            $checkoutLatitude = (float) $request->input('latitude');
            $checkoutLongitude = (float) $request->input('longitude');

            $result = DB::transaction(function () use ($activity, $finishDate, $checkoutPhotoPath, $checkoutLatitude, $checkoutLongitude) {
                if (! empty($activity->checkout_photo_path) && Storage::disk('public')->exists($activity->checkout_photo_path)) {
                    Storage::disk('public')->delete($activity->checkout_photo_path);
                }

                $activity->update([
                    'checkout_photo_path' => $checkoutPhotoPath,
                    'checkout_latitude' => $checkoutLatitude,
                    'checkout_longitude' => $checkoutLongitude,
                ]);

                return $this->splitCheckoutService->execute($activity, $finishDate);
            });

            $result['primary']->load(['stase', 'location', 'dosenUser']);

            return response()->json([
                'message' => 'Activity check-out successful',
                'data' => $result['primary'],
                'split_activities' => $result['additional']->values()->all(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete activity
     *
     * Menghapus activity. Student hanya record sendiri; role lain boleh menghapus record user lain (policy `delete`).
     *
     * @group Activities API
     *
     * @authenticated
     *
     * @header Authorization string required Gunakan format: Bearer {access_token}
     *
     * @urlParam activity int required ID activity yang akan dihapus. Example: 321
     */
    #[Response(200, 'Activity deleted successfully', type: 'array{message: string}')]
    #[Response(401, 'Unauthenticated', type: 'array{message: string}')]
    #[Response(403, 'Forbidden', type: 'array{message: string}')]
    #[Response(422, 'Validation/process error', type: 'array{message: string}')]
    public function destroy(Request $request, Activity $activity): JsonResponse
    {
        if ($request->user()->hasRole('student') && (int) $activity->user_id !== (int) $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke activity ini.',
            ], 403);
        }

        try {
            DB::transaction(function () use ($activity) {
                $updatedWorkloadHours = null;
                $ownerUserId = (int) $activity->user_id;

                if ((int) $activity->is_allowed === 1) {
                    $weekMonitor = WeekMonitor::where('user_id', $ownerUserId)
                        ->where('week_group_id', $activity->week_group_id)
                        ->first();

                    if ($weekMonitor) {
                        [$prevActivityHours] = explode(':', $activity->time_spend);
                        $updatedWorkloadHours = $weekMonitor->workload_hours - (int) $prevActivityHours;
                        if ($updatedWorkloadHours <= 80) {
                            Activity::where('is_allowed', 0)
                                ->where('user_id', $ownerUserId)
                                ->where('week_group_id', $activity->week_group_id)
                                ->update(['is_allowed' => 1]);
                        }
                    }
                }

                $activity->delete();

                if ($updatedWorkloadHours === 0) {
                    Activity::withoutEvents(function () use ($activity, $ownerUserId) {
                        Activity::withoutGlobalScopes()
                            ->where([
                                'user_id' => $ownerUserId,
                                'week_group_id' => $activity->week_group_id,
                                'is_generated' => 1,
                            ])
                            ->delete();
                    });
                }
            });

            return response()->json([
                'message' => 'Activity deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Path relatif di disk `public`: `activities/{checkin|checkout}/{tahun}` agar file mudah diarsip per tahun.
     */
    private function publicActivityPhotoDirectory(string $kind, int $year): string
    {
        return sprintf('activities/%s/%d', $kind, $year);
    }

    /**
     * Filter list activity menurut komponen tanggal pada `start_date`.
     *
     * - Hanya `date` → satu hari (`whereDate`).
     * - Hanya `year` → tahun tersebut.
     * - Hanya `month` → bulan tersebut (semua tahun).
     * - `year` + `month` (tanpa `date`) → bulan kalender tersebut di tahun tersebut.
     * - `date` + `year` dan/atau `month` → sama dengan hanya `date` (setelah validasi konsistensi).
     */
    private function applyActivityListDateFilters(Builder $query, Request $request): void
    {
        if ($request->filled('date')) {
            $query->whereDate('start_date', $request->input('date'));

            return;
        }

        if ($request->filled('year')) {
            $query->whereYear('start_date', (int) $request->input('year'));
        }
        if ($request->filled('month')) {
            $query->whereMonth('start_date', (int) $request->input('month'));
        }
    }
}
