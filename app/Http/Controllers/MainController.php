<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MainController extends Controller
{
    //
    /**
     * Display the dashboard.
     */
    public function dashboard(Request $request): \Inertia\Response
    {
        $user = $request->user();
        $guideline_document_path = null;
        if ($user->hasRole('student')) {
            $guideline_document_path = $user->studentUnit->guideline_document_path;
            if ($user->read_guideline_at && Carbon::parse($user->read_guideline_at)->lt(Carbon::now()->subMonth())) {
                // Tanggal `read_guideline_at` sudah lebih dari satu bulan
                // Lakukan sesuatu, misalnya:
                // echo $user->name . ' has not read the guideline in over a month.';
                $user->is_read_guideline = false;
                $user->save();
            }
        }
        return Inertia::render('Mains/Dashboard', [
            'guideline' => Storage::url("public/" . $guideline_document_path),
        ]);
    }
}
