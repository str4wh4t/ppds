<?php

namespace App\Http\Controllers;

use App\Models\StaseLocation;
use App\Http\Requests\StaseLocation\StoreRequest;
use App\Http\Requests\StaseLocation\UpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use \Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class StaseLocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        //
        $search = $request->input('search');

        $stase_locations = StaseLocation::when($search, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            });
        })
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('StaseLocations/Index', [
            'stase_locations' => $stase_locations,
            'filters' => [
                'search' => $search,
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
    public function store(StoreRequest $request)
    {
        //
        try {
            DB::transaction(function () use ($request) {
                StaseLocation::create([
                    'name' => $request->name,
                    'description' => $request->description,
                ]);
            });

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Stase location created successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(StaseLocation $staseLocation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StaseLocation $staseLocation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, StaseLocation $stase_location)
    {
        //
        try {
            DB::transaction(function () use ($request, $stase_location) {
                $stase_location->update([
                    'name' => $request->name,
                    'description' => $request->description,
                ]);
            });

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Stase location updated successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StaseLocation $stase_location)
    {
        //
        try {
            DB::transaction(function () use ($stase_location) {
                $stase_location->delete();
            });
            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Stase location deleted successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }
}
