<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class PrivacyPolicyController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Legal/PrivacyPolicy', [
            'appName' => config('app.name', 'E-Logbook PPDS'),
            'lastUpdated' => '29 Juni 2026',
            'contactEmail' => env('PRIVACY_CONTACT_EMAIL', config('mail.from.address')),
        ]);
    }
}
