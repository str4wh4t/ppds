<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Stase;
use App\Models\Unit;
use App\Models\UnitStase;
use App\Models\User;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StaseController extends Controller
{
    /**
     * List stases.
     *
     * Mengembalikan daftar stase beserta lokasi yang terkait.
     *
     * @group Master Data API
     *
     * @authenticated
     *
     * @header Authorization string required Gunakan format: Bearer {access_token}
     *
     * @bodyParam search string Optional filter nama stase atau nama lokasi. Example: igd
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "IGD",
     *       "description": "Instalasi Gawat Darurat",
     *       "locations": [
     *         {
     *           "id": 1,
     *           "name": "RSUP Kariadi",
     *           "description": null,
     *           "latitude": -6.9932000,
     *           "longitude": 110.4091000
     *         }
     *       ]
     *     }
     *   ]
     * }
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     */
    #[Response(200, 'List stases', type: 'array{data: array<array{id: int, name: string}>}')]
    #[Response(401, 'Unauthenticated', type: 'array{message: string}')]
    public function index(Request $request): JsonResponse
    {
        Validator::make($request->all(), [
            'search' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $search = $request->input('search');

        $stases = Stase::when($search, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })->orWhereHas('locations', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            });
        })
            ->with('locations')
            ->get();

        return response()->json([
            'data' => $stases,
        ]);
    }

    /**
     * List available locations by stase.
     *
     * Mengembalikan daftar location yang valid untuk stase tertentu.
     *
     * @group Master Data API
     *
     * @authenticated
     *
     * @header Authorization string required Gunakan format: Bearer {access_token}
     *
     * @bodyParam stase_id int required ID stase. Example: 1
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "RSUP Kariadi",
     *       "description": null,
     *       "latitude": -6.9932000,
     *       "longitude": 110.4091000
     *     }
     *   ]
     * }
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     */
    #[Response(200, 'List locations by stase', type: 'array{data: array<array{id: int, name: string, latitude: number, longitude: number}>}')]
    #[Response(401, 'Unauthenticated', type: 'array{message: string}')]
    public function locations(Request $request): JsonResponse
    {
        Validator::make($request->all(), [
            'stase_id' => ['required', 'integer', 'exists:stases,id'],
        ])->validate();

        $stase = Stase::findOrFail((int) $request->input('stase_id'));

        return response()->json([
            'data' => $stase->locations()->get(),
        ]);
    }

    /**
     * Daftar stase by unit
     *
     * Untuk role `student`, unit diambil dari `student_unit_id` pada token (abaikan `unit_id`).
     * Untuk role lain: gunakan `unit_id` di body bila dikirim, atau `student_unit_id`, atau unit kaprodi (`kaprodi_user_id`).
     * Role `system` dapat meminta `unit_id` mana pun. User lain hanya jika berhak atas unit tersebut.
     *
     * @group Master Data API
     *
     * @authenticated
     *
     * @header Authorization string required Gunakan format: Bearer {access_token}
     *
     * @bodyParam unit_id integer Optional ID unit (wajib disertai logika di atas untuk non-student). Example: 2
     * @bodyParam search string Optional filter nama atau deskripsi stase. Example: igd
     */
    #[Response(200, 'Stases for user unit', type: 'array{data: array<array{id: int, unit_id: int, stase_id: int, is_mandatory: bool, stase: array}>}')]
    #[Response(401, 'Unauthenticated', type: 'array{message: string}')]
    #[Response(403, 'Forbidden', type: 'array{message: string}')]
    #[Response(422, 'Validation / unit tidak dapat ditentukan', type: 'array{message: string}')]
    public function byUnit(Request $request): JsonResponse
    {
        Validator::make($request->all(), [
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'search' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $user = $request->user();
        $unitId = null;

        if ($user->hasRole('student')) {
            $unitId = $user->student_unit_id;
            if (empty($unitId)) {
                return response()->json([
                    'message' => 'Mahasiswa belum terhubung ke unit (student_unit_id kosong).',
                ], 422);
            }
        } else {
            $unitId = $request->input('unit_id') ?? $user->student_unit_id;
            if (empty($unitId)) {
                $unitId = Unit::query()->where('kaprodi_user_id', $user->id)->value('id');
            }
            if (empty($unitId)) {
                return response()->json([
                    'message' => 'Tidak dapat menentukan unit. Kirim unit_id atau hubungkan user ke unit.',
                ], 422);
            }
            if (! $this->userCanAccessUnitForStaseList($user, (int) $unitId)) {
                return response()->json([
                    'message' => 'Anda tidak memiliki akses ke unit ini.',
                ], 403);
            }
        }

        $search = $request->input('search');

        $unitStases = UnitStase::query()
            ->where('unit_id', $unitId)
            ->with(['stase' => function ($query) {
                $query->with('locations');
            }])
            ->when($search, function ($query) use ($search) {
                $query->whereHas('stase', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->get()
            ->sortBy(fn(UnitStase $row) => $row->stase?->name ?? '')
            ->values();

        $data = $unitStases->map(function (UnitStase $row) {
            return [
                'id' => $row->id,
                'unit_id' => $row->unit_id,
                'stase_id' => $row->stase_id,
                'is_mandatory' => (bool) $row->is_mandatory,
                'stase' => $row->stase,
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }

    private function userCanAccessUnitForStaseList(User $user, int $unitId): bool
    {
        if ($user->hasRole('system')) {
            return true;
        }
        if ((int) $user->student_unit_id === $unitId) {
            return true;
        }
        if (Unit::query()->whereKey($unitId)->where('kaprodi_user_id', $user->id)->exists()) {
            return true;
        }
        if ($user->adminUnits()->where('units.id', $unitId)->exists()) {
            return true;
        }
        if ($user->dosenUnits()->where('units.id', $unitId)->exists()) {
            return true;
        }

        return false;
    }
}
