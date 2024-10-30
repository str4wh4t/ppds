<?php

namespace App\Http\Controllers;

use App\Models\Portofolio;
use App\Http\Requests\StorePortofolioRequest;
use App\Http\Requests\UpdatePortofolioRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PortofolioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, User $user): Response
    {

        if ($user->roles->count() == 1) {
            if ($user->hasRole('student')) {
                $user =  $request->user();
            }
        }

        $portofolio = Portofolio::where('user_id', $user->id)->get();

        return Inertia::render('Portofolios/Index', [
            'porto$portofolio' => $portofolio,
            'filters' => [
                // 'search' => $search,
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePortofolioRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Portofolio $portofolio)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Portofolio $portofolio)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePortofolioRequest $request, Portofolio $portofolio)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Portofolio $portofolio)
    {
        //
    }
}
