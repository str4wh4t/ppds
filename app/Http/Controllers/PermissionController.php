<?php

namespace App\Http\Controllers;

use App\Http\Requests\Permission\UpdateRequest;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use \Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $permissions = Permission::where('name', '!=', 'global')->with('roles')->orderBy('name')->paginate(10);
        $roles = Role::where('name', '!=', 'system')->get();
        return Inertia::render('Permissions/Index', [
            'permissions' => $permissions,
            'roles' => $roles
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
    public function update(UpdateRequest $request, Permission $permission): RedirectResponse
    {
        //
        try {
            DB::transaction(function () use ($request, $permission) {
                $permission->roles()->detach();
                if (!empty($request->roles)) {
                    $roles = Role::whereIn('name', $request->roles)->get();
                    foreach ($roles as $role) {
                        $role->givePermissionTo($permission);
                    }
                } else {
                    $permission->roles()->detach();
                }
            });

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Permission updated successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
