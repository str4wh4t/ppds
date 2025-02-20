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
        $monthIndexSelected = $request->input('monthIndexSelected');
        $categoryWorkloadSelected = $request->input('categoryWorkloadSelected');
        //
        $week_monitors = WeekMonitor::when($search, function ($query, $search) {
            $query->whereHas('user', function ($query) use ($search) {
                $query->where('fullname', 'like', "%{$search}%");
            });
        })->when($monthIndexSelected, function ($query, $monthIndexSelected) {
            $query->where('month', $monthIndexSelected);
        })->when($categoryWorkloadSelected, function ($query, $categoryWorkloadSelected) {
            switch ($categoryWorkloadSelected) {
                case 1:
                    $query->where('workload_hours', '<', 71); // lebih dari 70
                    break;
                case 2:
                    $query->whereBetween('workload_hours', [71, 80]); // antara 71 sampai 80
                    break;
                case 3:
                    $query->where('workload_hours', '>', 80); // lebih dari 80
                    break;
                default:
                    // Jika tidak ada kategori yang cocok, tidak ada filter diterapkan
                    break;
            }            
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

        $week_monitors = $week_monitors->orderBy('year', 'asc')  // Urutkan berdasarkan year
            ->orderBy('month', 'asc') // Lalu berdasarkan month
            ->orderBy('week_month', 'asc') // Terakhir berdasarkan week_month
            ->paginate(10)
            ->withQueryString();

        $units = Unit::all();
        return Inertia::render('WeekMonitors/Index', [
            'week_monitors' => $week_monitors,
            'units' => $units,
            'filters' => [
                'search' => $search,
                'units' => $unitSelected,
                'monthIndexSelected' =>  (int) $monthIndexSelected - 1 ?? null, // karena index 0 merupakan januari tapi 0 ketika dikirim jadi null
                'categoryWorkloadSelected' => (int) $categoryWorkloadSelected ?? null
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
