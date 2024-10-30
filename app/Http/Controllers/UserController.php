<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreRequest;
use App\Http\Requests\User\UpdateRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use \Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        // Menambahkan Policy untuk otorisasi update dan delete
        $this->middleware('can:create,\App\Models\User')->only(['create', 'store']);
        $this->middleware('can:update,user')->only(['update', 'edit']);
        $this->middleware('can:delete,user')->only('destroy');
        $this->middleware('can:resetPassword,user')->only('resetPassword');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        //
        $search = $request->input('search');
        $rolesSelected = $request->input('roles');

        // $users = User::toSql();
        // dd($users);

        $users = User::whereDoesntHave('roles', function ($query) {
            $query->where('name', 'system');
        })
            ->with('roles')
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('username', 'like', "%{$search}%")
                        ->orWhere('fullname', 'like', "%{$search}%")
                        ->orWhere('identity', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($rolesSelected, function ($query, $rolesSelected) {
                $array = json_decode($rolesSelected, true);
                if (!empty($array)) {
                    $roleNames = array_map(function ($item) {
                        return $item['name'];
                    }, $array);

                    // Filter hanya user yang memiliki setidaknya satu role yang dipilih
                    $query->whereHas('roles', function ($query) use ($roleNames) {
                        $query->whereIn('name', $roleNames);
                    });
                }
            })
            ->paginate(10)
            ->withQueryString();



        $roles = Role::whereNotIn('name', ['system', 'dosen', 'student', ['kaprodi']])->get();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'filters' => [
                'search' => $search,
                'roles' => $rolesSelected,
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
                $user = User::create([
                    'fullname' => $request->fullname,
                    'username' => $request->username,
                    'identity' => $request->identity,
                    'email' => $request->email,
                    'password' => Hash::make(config('constants.password_default')),
                ]);
                $user->syncRoles($request->roles);
                event(new Registered($user));
            });

            // Auth::login($user);

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'User created successfully');
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
    public function update(UpdateRequest $request, User $user): RedirectResponse
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

                $user->syncRoles($request->roles);
            });

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'User updated successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        //
        try {
            DB::transaction(function () use ($user) {
                $user->roles()->detach();
                $user->delete();
            });
            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'User deleted successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    /**
     * Reset password user
     */
    public function resetPassword(User $user): RedirectResponse
    {
        //
        try {
            $user->password = Hash::make(config('constants.password_default'));
            $user->save();
            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Password reset successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    /**
     * Login as selected role
     */
    public function roleAs(Role $role): RedirectResponse
    {
        // Simpan peran aktif yang dipilih di sesi
        // Session::put('selected_role', $role->name);
        return Redirect::route('dashboard');
    }
}
