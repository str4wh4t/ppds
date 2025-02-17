<?php

namespace App\Http\Middleware;

use App\Models\Consult;
use App\Models\WeekMonitor;
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
            // 'auth' => [
            //     'user' => $request->user() ?
            //         ($request->user()->hasRole('student') ? $request->user()->load('roles.permissions', 'dosenUnits', 'kaprodiUnits', 'dosbingStudents', 'adminUnits') :
            //             $request->user()->load('roles.permissions', 'dosenUnits', 'kaprodiUnits', 'dosbingStudents', 'adminUnits')) :
            //         null,
            // ],
            'auth' => [
                'user' => $request->user() ? $request->user()->load('roles.permissions', 'dosenUnits', 'kaprodiUnits', 'dosbingStudents', 'adminUnits') : null,
            ],
            'flashmsg' => [
                config('constants.public.flashmsg.ok') => session()->get(config('constants.public.flashmsg.ok')),
                config('constants.public.flashmsg.ko') => session()->get(config('constants.public.flashmsg.ko')),
            ],
            'constants' => config('constants.public'), // Bagikan semua konstanta
            'exceededWorkloads' => $request->user() ?
                ($request->user()->hasRole('student') ?
                    WeekMonitor::where('workload_hours', '>', 80)
                    ->where('user_id', $request->user()->id)
                    ->whereHas('activities', function ($query) use ($request) {
                        $query->where('user_id', $request->user()->id)
                            ->where('is_allowed', 0);
                    })
                    ->get() : ($request->user()->hasRole('kaprodi') ?
                        WeekMonitor::where('workload_hours', '>', 80)
                        ->with('user', 'user.studentUnit')
                        ->whereHas('activities', function ($query) use ($request) {
                            $query->where('is_allowed', 0);
                        })
                        ->whereHas('user', function ($query) use ($request) {
                            $query->whereIn('student_unit_id', $request->user()->kaprodiUnits->pluck('id')->toArray());
                        })
                        ->get() : null)) :
                null,
            'unreadConsults' => $request->user() ?
                ($request->user()->hasRole('student') ?
                    Consult::where('reply_at', null)
                    ->where('user_id', $request->user()->id)
                    ->get() : ($request->user()->hasRole('dosen') ?
                        Consult::where('reply_at', null)
                        ->with('user', 'user.studentUnit')
                        ->get() : null)) :
                null,
        ];

        // if (session()->get('token')) {
        //     $data['auth']['token'] = session()->get('token');
        // }

        // 'permissions' => $user->getPermissionsViaRoles()->pluck('name')

        return $data;
    }
}
