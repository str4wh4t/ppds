<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dosen\StoreRequest;
use App\Http\Requests\Dosen\UpdateRequest;
use App\Models\Unit;
use Illuminate\Auth\Events\Registered;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;

class DosenController extends Controller
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

        $users = User::dosen()->when($search, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('username', 'like', "%{$search}%")
                    ->orWhere('fullname', 'like', "%{$search}%")
                    ->orWhere('identity', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        })->with('dosenUnits')
            ->paginate(10)
            ->withQueryString();

        $units = Unit::get();

        return Inertia::render('Dosens/Index', [
            'users' => $users,
            'units' => $units,
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
                $user = User::create([
                    'fullname' => $request->fullname,
                    'username' => $request->username,
                    'identity' => $request->identity,
                    'email' => $request->email,
                    'password' =>  Hash::make(config('constants.password_default')),
                ]);
                $user->assignRole('dosen');

                $dosen_units = $request->dosen_units;

                // dd($dosen_units);
                $ids = array_map(function ($dosen_unit) {
                    return $dosen_unit['id'];
                }, $dosen_units);

                $roleAs = 'dosen'; // Role yang sama untuk semua

                // Membuat array untuk sync dengan role_as yang sama
                $syncData = array_fill_keys($ids, ['role_as' => $roleAs]);

                // Menyinkronkan data dengan unit_ids dan role_as yang sama
                $user->dosenUnits()->sync($syncData);

                event(new Registered($user));
            });

            // Auth::login($user);

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Kaprodi created successfully');
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
                    'email' => $request->email,
                ]);

                $dosen_units = $request->dosen_units;

                // dd($dosen_units);
                $ids = array_map(function ($dosen_unit) {
                    return $dosen_unit['id'];
                }, $dosen_units);

                $roleAs = 'dosen'; // Role yang sama untuk semua

                // Membuat array untuk sync dengan role_as yang sama
                $syncData = array_fill_keys($ids, ['role_as' => $roleAs]);

                // Menyinkronkan data dengan unit_ids dan role_as yang sama
                $user->dosenUnits()->sync($syncData);
                // $user->assignRole('kaprodi'); // already assigned in store method
            });

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Kaprodi updated successfully');
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
}
