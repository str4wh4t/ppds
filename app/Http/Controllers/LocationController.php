<?php

namespace App\Http\Controllers;

use App\Http\Requests\Location\StoreRequest;
use App\Http\Requests\Location\UpdateRequest;
use App\Models\User;
use App\Models\Location;
use App\Models\StaseLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use \Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        //
        $search = $request->input('search');

        $locations = Location::when($search, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })->orWhereHas('stases', function ($query) use ($search) {
                // Pencarian pada kolom 'name' di relasi staseLocation
                $query->where('name', 'like', "%{$search}%");
            });
        })
            ->with('stases')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Locations/Index', [
            'locations' => $locations,
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
    public function store(StoreRequest $request): RedirectResponse
    {
        //
        try {
            DB::transaction(function () use ($request) {
                Location::create([
                    'name' => $request->name,
                    'description' => $request->description,
                ]);
            });

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Location created successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Location $location)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Location $location)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Location $location)
    {
        //
        try {
            DB::transaction(function () use ($request, $location) {
                $location->update([
                    'name' => $request->name,
                    'description' => $request->description,
                ]);
            });

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Location updated successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Location $location): RedirectResponse
    {
        //
        try {
            DB::transaction(function () use ($location) {
                $location->delete();
            });
            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Location deleted successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }
}
