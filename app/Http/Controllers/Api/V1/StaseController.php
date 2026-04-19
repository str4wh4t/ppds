<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Stase;
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
}
