<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class NotifController extends Controller
{
    // index controller
    public function index(Request $request)
    {
        // render Notif/Index
        return Inertia::render('Notifs/Index');
    }
}
