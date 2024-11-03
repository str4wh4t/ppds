<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \App\Models\Activity::class => \App\Policies\ActivityPolicy::class,
        \App\Models\Unit::class => \App\Policies\UnitPolicy::class,
        \Spatie\Permission\Models\Permission::class => \App\Policies\PermissionPolicy::class,
        \App\Models\Portofolio::class => \App\Policies\PortofolioPolicy::class,
        \App\Models\Consult::class => \App\Policies\ConsultPolicy::class,
        \App\Models\Speak::class => \App\Policies\SpeakPolicy::class
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
