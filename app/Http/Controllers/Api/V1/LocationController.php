<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{
    /**
     * List locations.
     *
     * Mengembalikan daftar lokasi beserta stase yang terkait.
     *
     * @group Master Data API
     *
     * @authenticated
     *
     * @header Authorization string required Gunakan format: Bearer {access_token}
     *
     * @bodyParam search string Optional filter nama lokasi atau nama stase. Example: kariadi
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "RSUP Kariadi",
     *       "description": null,
     *       "latitude": -6.9932000,
     *       "longitude": 110.4091000,
     *       "stases": [
     *         {
     *           "id": 1,
     *           "name": "IGD",
     *           "description": "Instalasi Gawat Darurat"
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
    #[Response(200, 'List locations', type: 'array{data: array<array{id: int, name: string, latitude: number, longitude: number}>}')]
    #[Response(401, 'Unauthenticated', type: 'array{message: string}')]
    public function index(Request $request): JsonResponse
    {
        Validator::make($request->all(), [
            'search' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $search = $request->input('search');

        $locations = Location::when($search, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })->orWhereHas('stases', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            });
        })
            ->with('stases')
            ->get();

        return response()->json([
            'data' => $locations,
        ]);
    }
}
