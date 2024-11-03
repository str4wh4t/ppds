<?php

namespace App\Http\Controllers;

use App\Http\Requests\Student\StoreRequest;
use App\Http\Requests\Student\UpdateRequest;
use App\Models\Unit;
use Illuminate\Auth\Events\Registered;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;

class StudentController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:create,\App\Models\User')->only(['create', 'store']);
        $this->middleware('can:update,user')->only(['update', 'edit']);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        //
        $search = $request->input('search');
        $unitSelected = $request->input('units');

        $users = User::student()->when($search, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('username', 'like', "%{$search}%")
                    ->orWhere('fullname', 'like', "%{$search}%")
                    ->orWhere('identity', 'like', "%{$search}%")
                    ->orWhere('semester', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        })->when($unitSelected, function ($query, $unitSelected) {
            $array = json_decode($unitSelected, true);
            if (!empty($array)) {
                $unitNames = array_map(function ($item) {
                    return $item['name'];
                }, $array);

                // Filter hanya user yang memiliki setidaknya satu role yang dipilih
                $query->whereHas('studentUnit', function ($query) use ($unitNames) {
                    $query->whereIn('name', $unitNames);
                });
            }
        })->with('roles', 'studentUnit', 'dosbingUser')
            ->paginate(10)
            ->withQueryString();

        $units = Unit::get();
        $dosen_list = User::role('dosen')->get();

        return Inertia::render('Students/Index', [
            'users' => $users,
            'units' => $units,
            'dosen_list' => $dosen_list,
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
    public function store(StoreRequest $request)
    {
        //
        try {
            DB::transaction(function () use ($request) {
                $user = User::create([
                    'fullname' => $request->fullname,
                    'username' => $request->username,
                    'identity' => $request->identity,
                    'semester' => $request->semester,
                    'student_unit_id' => $request->student_unit_id,
                    'dosbing_user_id' => $request->dosbing_user_id,
                    'email' => $request->email,
                    'password' =>  Hash::make(config('constants.password_default')),

                ]);
                $user->assignRole('student');
                event(new Registered($user));
            });

            // Auth::login($user);

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Student created successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, User $user)
    {
        //
        try {
            DB::transaction(function () use ($request, $user) {
                $user->update([
                    'fullname' => $request->fullname,
                    'username' => $request->username,
                    'identity' => $request->identity,
                    'semester' => $request->semester,
                    'student_unit_id' => $request->student_unit_id,
                    'dosbing_user_id' => $request->dosbing_user_id,
                    'email' => $request->email,
                ]);

                // $user->assignRole('student'); // already assigned in store method
            });

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Student updated successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }

    public function readGuideline(Request $request, User $user)
    {
        try {
            $user = $request->user();
            $user->is_read_guideline = true;
            $user->read_guideline_at = now();
            $user->save();

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Guideline document process successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }
}
