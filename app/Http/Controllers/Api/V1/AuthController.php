<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login
     *
     * @group Authentication API
     *
     * @unauthenticated
     *
     * @bodyParam username string required Username user. Example: mahasiswa01
     * @bodyParam password string required Password user. Example: secret123
     * @bodyParam device_name string Optional nama device/client token. Example: mobile-app
     */
    #[Response(201, 'Login successful', type: 'array{message: string, token_type: string, access_token: string, user: array{id: int, username: string, fullname: string}}')]
    #[Response(422, 'Validation error', type: 'array{message: string, errors: array{username: array<string>}}')]
    #[Response(401, 'Unauthenticated', type: 'array{message: string}')]
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::where('username', $credentials['username'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'username' => [trans('auth.failed')],
            ]);
        }

        if ((int) ($user->is_active_student ?? 1) === 0) {
            throw ValidationException::withMessages([
                'username' => ['Akun anda nonaktif. Silakan hubungi admin.'],
            ]);
        }

        $tokenName = $credentials['device_name'] ?? 'api-client';
        $plainTextToken = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token_type' => 'Bearer',
            'access_token' => $plainTextToken,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'fullname' => $user->fullname,
            ],
        ], 201);
    }

    /**
     * Logout
     *
     * @group Authentication API
     *
     * @authenticated
     *
     * @response 200 {
     *   "message": "Logout successful"
     * }
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     */
    #[Response(200, 'Logout successful', type: 'array{message: string}')]
    #[Response(401, 'Unauthenticated', type: 'array{message: string}')]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logout successful',
        ]);
    }

    /**
     * Profile
     *
     * Di OpenAPI/Scramble tampil sebagai **Profile**; URL resmi `GET /api/v1/user`.
     * Detail field dan contoh respons: `docs/api-profile.md`.
     *
     * @group Profile API
     *
     * @authenticated
     *
     * @header Authorization string required Gunakan format: Bearer {access_token}
     */
    #[Response(200, 'Profil lengkap + permission_names', type: 'array{user: object, permission_names: array<string>}')]
    #[Response(401, 'Unauthenticated', type: 'array{message: string}')]
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load([
            'roles.permissions',
            'dosenUnits',
            'kaprodiUnits',
            'dosbingStudents',
            'adminUnits',
            'studentUnit',
        ]);

        $permissionNames = $user->getAllPermissions()->pluck('name')->unique()->values()->all();

        return response()->json([
            'user' => $user,
            'permission_names' => $permissionNames,
        ]);
    }
}
