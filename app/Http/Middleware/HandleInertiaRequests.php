<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $data = [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? $request->user()->load('roles.permissions') : null,
            ],
            'flashmsg' => [
                config('constants.public.flashmsg.ok') => session()->get(config('constants.public.flashmsg.ok')),
                config('constants.public.flashmsg.ko') => session()->get(config('constants.public.flashmsg.ko')),
            ],
            'constants' => config('constants.public'), // Bagikan semua konstanta
        ];

        // if (session()->get('token')) {
        //     $data['auth']['token'] = session()->get('token');
        // }

        // 'permissions' => $user->getPermissionsViaRoles()->pluck('name')

        return $data;
    }
}
