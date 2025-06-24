<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SurveyController extends Controller
{
    //

    public function index(Request $request, User $user): Response
    {
        return Inertia::render('Surveys/Index');
    }
}
