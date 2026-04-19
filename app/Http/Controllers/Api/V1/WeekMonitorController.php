<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WeekMonitor;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WeekMonitorController extends Controller
{
    /**
     * List week monitors.
     *
     * Filters mengikuti pola halaman web WeekMonitors (`WeekMonitorController@index`):
     * - `search`: fullname user
     * - `units`: filter student_unit.name (JSON string)
     * - `yearSelected`, `monthIndexSelected`, `categoryWorkloadSelected`
     *
     * Untuk role `student`, data selalu diambil dari token (abaikan `user_id`).
     * Untuk role `system`, bisa filter user lain via `user_id`.
     *
     * @group Week Monitor API
     *
     * @authenticated
     *
     * @bodyParam user_id integer Optional ID user (hanya untuk role `system`)
     * @bodyParam search string Optional pencarian berdasarkan fullname user
     * @bodyParam units string Optional filter unit (JSON)
     * @bodyParam yearSelected integer Optional year. Example: 2026
     * @bodyParam monthIndexSelected integer Optional month (1-12). Example: 4
     * @bodyParam categoryWorkloadSelected integer Optional kategori beban kerja:
     *   1 = <71, 2 = 71-80, 3 = >80
     * @bodyParam per_page integer Optional jumlah per halaman. Default: 10 (maks 50)
     *
     * Respons **200**: JSON **LengthAwarePaginator** (`response()->json($paginator)`). Field tingkat atas selain `data`:
     * `current_page`, `first_page_url`, `from`, `last_page`, `last_page_url`, `links`, `next_page_url`, `path`, `per_page`,
     * `prev_page_url`, `to`, `total`. Setiap elemen `data[]` adalah `WeekMonitor` + `user` + `user.student_unit` (password user tidak disertakan).
     *
     * @response 200 {
     *   "current_page": 1,
     *   "data": [
     *     {
     *       "id": 1,
     *       "user_id": 2,
     *       "week_group_id": 202614,
     *       "year": 2026,
     *       "month": 4,
     *       "week": 14,
     *       "week_month": 2,
     *       "workload": "72:30:00",
     *       "workload_hours": 72,
     *       "workload_hours_not_allowed": 0,
     *       "workload_as_seconds": 261000,
     *       "created_at": "2026-04-01T08:00:00.000000Z",
     *       "updated_at": "2026-04-08T10:15:00.000000Z",
     *       "user": {
     *         "id": 2,
     *         "username": "mhs01",
     *         "fullname": "Nama Mahasiswa",
     *         "identity": "1234567890",
     *         "semester": 4,
     *         "email": "mhs@example.test",
     *         "student_unit_id": 1,
     *         "dosbing_user_id": 10,
     *         "doswal_user_id": 11,
     *         "email_verified_at": null,
     *         "created_at": "2025-01-10T00:00:00.000000Z",
     *         "updated_at": "2026-04-01T00:00:00.000000Z",
     *         "student_unit": {
     *           "id": 1,
     *           "name": "Neurologi",
     *           "kaprodi_user_id": 5,
     *           "admin_user_id": 6,
     *           "guideline_document_path": "units/guideline.pdf",
     *           "created_at": "2024-01-01T00:00:00.000000Z",
     *           "updated_at": "2024-06-01T00:00:00.000000Z"
     *         }
     *       }
     *     }
     *   ],
     *   "first_page_url": "http://localhost:8000/api/v1/week-monitors?page=1",
     *   "from": 1,
     *   "last_page": 3,
     *   "last_page_url": "http://localhost:8000/api/v1/week-monitors?page=3",
     *   "links": [
     *     { "url": null, "label": "&laquo; Previous", "active": false },
     *     { "url": "http://localhost:8000/api/v1/week-monitors?page=1", "label": "1", "active": true },
     *     { "url": "http://localhost:8000/api/v1/week-monitors?page=2", "label": "2", "active": false },
     *     { "url": "http://localhost:8000/api/v1/week-monitors?page=2", "label": "Next &raquo;", "active": false }
     *   ],
     *   "next_page_url": "http://localhost:8000/api/v1/week-monitors?page=2",
     *   "path": "http://localhost:8000/api/v1/week-monitors",
     *   "per_page": 10,
     *   "prev_page_url": null,
     *   "to": 10,
     *   "total": 25
     * }
     * @response 401 { "message": "Unauthenticated." }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "per_page": ["The per page must not be greater than 50."]
     *   }
     * }
     */
    #[Response(200, 'Paginated week monitors', type: 'array{data: array<array{id: int, user_id: int, week_group_id: int, year: int, month: int, week: int, week_month: int, workload: string, workload_hours: int, workload_hours_not_allowed: int, workload_as_seconds: int, created_at: string, updated_at: string}>, current_page: int, per_page: int, total: int, last_page: int}')]
    #[Response(401, 'Unauthenticated', type: 'array{message: string}')]
    #[Response(422, 'Validation error', type: 'array{message: string, errors: array}')]
    public function index(Request $request): JsonResponse
    {
        Validator::make($request->all(), [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'units' => ['nullable', 'string'],
            'yearSelected' => ['nullable', 'integer'],
            'monthIndexSelected' => ['nullable', 'integer', 'between:1,12'],
            'categoryWorkloadSelected' => ['nullable', 'integer', 'in:1,2,3'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ])->validate();

        $search = $request->input('search');
        $unitsSelected = $request->input('units');
        $yearSelected = $request->input('yearSelected');
        $monthIndexSelected = $request->input('monthIndexSelected');
        $categoryWorkloadSelected = $request->input('categoryWorkloadSelected');
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
                $unitNames = array_map(function ($item) {
                    return is_array($item) ? ($item['name'] ?? null) : $item;
                }, $decoded);
                $unitNames = array_values(array_filter($unitNames, fn ($v) => ! empty($v)));
            }
        }

        $query = WeekMonitor::query()
            ->where('user_id', $targetUserId)
            ->when($search, function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('fullname', 'like', "%{$search}%");
                });
            })
            ->when($yearSelected, function ($q) use ($yearSelected) {
                $q->where('year', $yearSelected);
            })
            ->when($monthIndexSelected, function ($q) use ($monthIndexSelected) {
                $q->where('month', $monthIndexSelected);
            })
            ->when($categoryWorkloadSelected, function ($q) use ($categoryWorkloadSelected) {
                switch ((int) $categoryWorkloadSelected) {
                    case 1:
                        $q->where('workload_hours', '<', 71);
                        break;
                    case 2:
                        $q->whereBetween('workload_hours', [71, 80]);
                        break;
                    case 3:
                        $q->where('workload_hours', '>', 80);
                        break;
                }
            })
            ->when($unitNames, function ($q) use ($unitNames) {
                $q->whereHas('user.studentUnit', function ($unitQuery) use ($unitNames) {
                    $unitQuery->whereIn('name', $unitNames);
                });
            })
            ->with('user', 'user.studentUnit')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->orderBy('week_month', 'asc');

        $weekMonitors = $query->paginate($perPage);

        return response()->json($weekMonitors);
    }

    /**
     * Week monitor by week group id
     *
     * - Role **student**: `user_id` tidak perlu; pemilik rekaman selalu diambil dari **token**.
     * - Role **selain student**: `user_id` **wajib** (user yang dimaksud, mis. mahasiswa).
     *
     * @group Week Monitor API
     *
     * @authenticated
     *
     * @bodyParam week_group_id integer required Contoh: 202615 (ISO week year + week). Example: 202615
     * @bodyParam user_id integer ID user pemilik rekaman (wajib jika bukan student). Example: 12
     *
     * Respons **200**: envelope `{ "data": { ... } }` berisi satu `WeekMonitor` + relasi `user` + `user.student_unit`
     * (serialisasi Eloquent; `password` user tidak ikut karena `$hidden` pada model `User`).
     *
     * @response 200 {
     *   "data": {
     *     "id": 42,
     *     "user_id": 12,
     *     "week_group_id": 202615,
     *     "year": 2026,
     *     "month": 4,
     *     "week": 15,
     *     "week_month": 2,
     *     "workload": "40:15:30",
     *     "workload_hours": 40,
     *     "workload_hours_not_allowed": 2,
     *     "workload_as_seconds": 144930,
     *     "created_at": "2026-04-01T08:00:00.000000Z",
     *     "updated_at": "2026-04-08T16:45:00.000000Z",
     *     "user": {
     *       "id": 12,
     *       "username": "mhs01",
     *       "fullname": "Nama Mahasiswa",
     *       "identity": "1234567890",
     *       "semester": 4,
     *       "email": "mhs@example.test",
     *       "student_unit_id": 1,
     *       "dosbing_user_id": 10,
     *       "doswal_user_id": 11,
     *       "email_verified_at": null,
     *       "created_at": "2025-01-10T00:00:00.000000Z",
     *       "updated_at": "2026-04-01T00:00:00.000000Z",
     *       "student_unit": {
     *         "id": 1,
     *         "name": "Neurologi",
     *         "kaprodi_user_id": 5,
     *         "admin_user_id": 6,
     *         "guideline_document_path": "units/guideline.pdf",
     *         "created_at": "2024-01-01T00:00:00.000000Z",
     *         "updated_at": "2024-06-01T00:00:00.000000Z"
     *       }
     *     }
     *   }
     * }
     * @response 401 { "message": "Unauthenticated." }
     * @response 404 { "message": "Week monitor tidak ditemukan." }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "week_group_id": ["The week group id field is required."],
     *     "user_id": ["Field user_id wajib untuk role selain student."]
     *   }
     * }
     */
    #[Response(200, 'Single week monitor with relations', type: 'array{data: array{id: int, user_id: int, week_group_id: int, year: int, month: int, week: int, week_month: int, workload: string, workload_hours: int, workload_hours_not_allowed: int, workload_as_seconds: int, created_at: string|null, updated_at: string|null}}')]
    #[Response(404, 'Not found', type: 'array{message: string}')]
    #[Response(401, 'Unauthenticated', type: 'array{message: string}')]
    #[Response(422, 'Validation error', type: 'array{message: string, errors: array}')]
    public function byWeek(Request $request): JsonResponse
    {
        $user = $request->user();
        $isStudent = $user->hasRole('student');

        Validator::make($request->all(), [
            'week_group_id' => ['required', 'integer'],
            'user_id' => [
                Rule::requiredIf(! $isStudent),
                'nullable',
                'integer',
                'exists:users,id',
            ],
        ], [
            'user_id.required' => 'Field user_id wajib untuk role selain student.',
        ])->validate();

        $targetUserId = $isStudent
            ? (int) $user->id
            : (int) $request->input('user_id');

        $record = WeekMonitor::query()
            ->where('user_id', $targetUserId)
            ->where('week_group_id', (int) $request->input('week_group_id'))
            ->with('user', 'user.studentUnit')
            ->first();

        if (! $record) {
            return response()->json([
                'message' => 'Week monitor tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'data' => $record,
        ]);
    }
}
