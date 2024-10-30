<?php

namespace App\Http\Controllers;

use App\Http\Requests\Stase\StoreRequest;
use App\Http\Requests\Stase\UpdateRequest;
use App\Models\User;
use App\Models\Stase;
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
            });
        })
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Stases/Index', [
            'stases' => $stases,
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
                Stase::create([
                    'name' => $request->name,
                    'location' => $request->location,
                    'description' => $request->description,
                ]);
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
                    'location' => $request->location,
                    'description' => $request->description,
                ]);
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
}
