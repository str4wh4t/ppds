<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DosenController extends Controller
{
    /**
     * List dosen.
     *
     * Mengembalikan daftar user dengan role `dosen`.
     *
     * @group Master Data API
     *
     * @authenticated
     *
     * @header Authorization string required Gunakan format: Bearer {access_token}
     *
     * @bodyParam search string Optional filter fullname atau username dosen. Example: dr.
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 12,
     *       "username": "dosen01",
     *       "fullname": "dr. Budi Santoso",
     *       "email": "dosen01@example.com"
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
    #[Response(200, 'List dosen', type: 'array{data: array<array{id: int, username: string, fullname: string, email: string}>}')]
    #[Response(401, 'Unauthenticated', type: 'array{message: string}')]
    public function index(Request $request): JsonResponse
    {
        Validator::make($request->all(), [
            'search' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $search = $request->input('search');

        $dosens = User::whereHas('roles', function ($query) {
            $query->where('name', 'dosen');
        })
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('fullname', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->get(['id', 'username', 'fullname', 'email']);

        return response()->json([
            'data' => $dosens,
        ]);
    }
}
