<?php

namespace App\Http\Controllers;

use App\Http\Requests\Kaprodi\StoreRequest;
use App\Http\Requests\Kaprodi\UpdateRequest;
use App\Models\Unit;
use Illuminate\Auth\Events\Registered;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;

class KaprodiController extends Controller
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

        $users = User::kaprodi()->when($search, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('username', 'like', "%{$search}%")
                    ->orWhere('fullname', 'like', "%{$search}%")
                    ->orWhere('identity', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        })->with('kaprodiUnits')
            ->paginate(10)
            ->withQueryString();

        $units = Unit::get();

        return Inertia::render('Kaprodis/Index', [
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
                $user->assignRole(['dosen', 'kaprodi']);
                foreach ($request->kaprodi_units as $unit) {
                    $unit = Unit::find($unit['id']);
                    $unit->update(
                        ['kaprodi_user_id' => $user->id]
                    );
                }
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
                $user->kaprodiUnits()->update(['kaprodi_user_id' => null]); // menghapus kaprodi_user_id di semua unit

                foreach ($request->kaprodi_units as $unit) {
                    $unit = Unit::find($unit['id']);
                    $unit->update(
                        ['kaprodi_user_id' => $user->id]
                    );
                }

                if (empty($request->kaprodi_units)) {
                    // menghapus role kaprodi jika tidak ada unit yang di-assign
                    $user->removeRole('kaprodi');
                }
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
