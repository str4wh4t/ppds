<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\User;
use App\Models\WeekMonitor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use \Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;

class WeekMonitorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, User $user): Response
    {
        $search = $request->input('search');
        $unitSelected = $request->input('units');
        //
        $week_monitors = WeekMonitor::when($search, function ($query, $search) {
            $query->whereHas('user', function ($query) use ($search) {
                $query->where('fullname', 'like', "%{$search}%");
            });
        })->when($unitSelected, function ($query, $unitSelected) {
            $array = json_decode($unitSelected, true);
            if (!empty($array)) {
                $unitNames = array_map(function ($item) {
                    return $item['name'];
                }, $array);

                // Filter hanya user yang memiliki setidaknya satu role yang dipilih
                $query->whereHas('user.studentUnit', function ($query) use ($unitNames) {
                    $query->whereIn('name', $unitNames);
                });
            }
        })->with('user', 'user.studentUnit');

        if ($request->user()->hasRole('student')) {
            $week_monitors = $week_monitors->where('user_id', $request->user()->id);
        } else {
            if ($user->id != $request->user()->id) {
                $week_monitors = $week_monitors->where('user_id', $user->id);
            }
        }

        $week_monitors = $week_monitors->paginate(10)
            ->withQueryString();

        $units = Unit::all();
        return Inertia::render('WeekMonitors/Index', [
            'week_monitors' => $week_monitors,
            'units' => $units,
            'filters' => [
                'search' => $search,
                'units' => $unitSelected,
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WeekMonitor $week_monitors)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
