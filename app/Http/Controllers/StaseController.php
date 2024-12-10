<?php

namespace App\Http\Controllers;

use App\Http\Requests\Stase\StoreRequest;
use App\Http\Requests\Stase\UpdateRequest;
use App\Models\Location;
use App\Models\User;
use App\Models\Stase;
use App\Models\StaseLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use \Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class StaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        //
        $search = $request->input('search');

        $stases = Stase::when($search, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })->orWhereHas('locations', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            });
        })
            ->with('locations')
            ->paginate(10)
            ->withQueryString();

        $location_list = Location::all();

        return Inertia::render('Stases/Index', [
            'stases' => $stases,
            'location_list' => $location_list,
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
                $stase = Stase::create([
                    'name' => $request->name,
                    'description' => $request->description,
                ]);
                $locations = $request->locations;
                if (!empty($locations)) {
                    $ids = array_map(function ($location) {
                        return $location['id'];
                    }, $locations);

                    $stase->locations()->sync($ids);
                }
            });

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Stase created successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Stase $stase)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Stase $stase)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Stase $stase)
    {
        //
        try {
            DB::transaction(function () use ($request, $stase) {
                $stase->update([
                    'name' => $request->name,
                    'description' => $request->description,
                ]);
                $locations = $request->locations;
                if (!empty($locations)) {
                    $ids = array_map(function ($location) {
                        return $location['id'];
                    }, $locations);

                    $stase->locations()->sync($ids);
                }
            });

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Stase updated successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Stase $stase): RedirectResponse
    {
        //
        try {
            DB::transaction(function () use ($stase) {
                $stase->delete();
            });
            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Stase deleted successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    public function getAvailLocation(Stase $stase): JsonResponse
    {
        try {
            // Mengambil lokasi terkait dari relasi locations()
            $locations = $stase->locations()->get();

            // Mengembalikan data sebagai JSON
            return response()->json([
                'success' => true,
                'data' => $locations,
            ], 200);
        } catch (\Exception $e) {
            // Menangani error dan mengembalikan pesan JSON
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
