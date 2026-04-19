<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                // Menambahkan input bearer token di UI docs (Authorize/Try It).
                $openApi->secure(SecurityScheme::http('bearer'));
            });

        if (app()->environment('production')) {
            $this->app['request']->server->set('HTTPS', true);
        }
    }
}
