<?php

namespace App\Http\Controllers;

use App\Http\Requests\Unit\StoreRequest;
use App\Http\Requests\Unit\UpdateRequest;
use App\Http\Requests\Unit\UploadDocumentRequest;
use App\Http\Requests\UnitStase\UpdateRequest as UnitStaseUpdateRequest;
use App\Models\Stase;
use App\Models\Unit;
use App\Models\UnitStase;
use App\Models\UnitStaseUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use \Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

class UnitController extends Controller
{
    public function __construct()
    {
        // Menambahkan Policy untuk otorisasi update dan delete
        $this->middleware('can:create,\App\Models\Unit')->only(['create', 'store']);
        $this->middleware('can:update,unit')->only(['update', 'edit']);
        $this->middleware('can:delete,unit')->only('destroy');
        $this->middleware('can:processSchedule,unit')->only(['uploadScheduleDocument', 'deleteScheduleDocument']);
        $this->middleware('can:processGuideline,unit')->only(['uploadGuidelineDocument', 'deleteGuidelineDocument']);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        //
        $search = $request->input('search');

        $units = Unit::when($search, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                // Pencarian pada kolom 'name' di tabel Unit
                $query->where('name', 'like', "%{$search}%");
            })
                ->orWhereHas('kaprodiUser', function ($query) use ($search) {
                    // Pencarian pada kolom 'fullname' di relasi kaprodiUser
                    $query->where('fullname', 'like', "%{$search}%");
                })
                ->orWhereHas('unitAdmins', function ($query) use ($search) {
                    // Pencarian pada kolom 'fullname' di relasi unitAdmins
                    $query->where('fullname', 'like', "%{$search}%");
                });
        })->with('kaprodiUser', 'unitAdmins', 'stases');
        // ->with('kaprodiUser', 'unitAdmins', 'stases.locations'); // Eager load relasi
        if ($request->user()->hasRole('admin_prodi')) {
            $unit_ids_all = $request->user()->adminUnits->pluck('id')->toArray();
            $units = $units->whereIn('id', $unit_ids_all);
        }
        $units = $units
            ->paginate(10)
            ->withQueryString();

        $kaprodi_list = User::role('dosen')->get(); // diganti dosen karena kaprodi dari dosen
        $admin_list = User::role('admin_prodi')->get(); // diganti dosen karena kaprodi dari dosen
        $stases = Stase::with('locations')->get();
        return Inertia::render('Units/Index', [
            'units' => $units,
            'kaprodi_list' => $kaprodi_list,
            'admin_list' => $admin_list,
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
                $unit = Unit::create([
                    'name' => $request->name,
                    'kaprodi_user_id' => $request->kaprodi_user_id,
                ]);

                if ($request->kaprodi_user_id) {
                    $user = User::find($request->kaprodi_user_id);
                    $user->assignRole('kaprodi');
                }

                $unit_admins = $request->unit_admins;
                if (!empty($unit_admins)) {
                    $ids = array_map(function ($unit_admin) {
                        return $unit_admin['id'];
                    }, $unit_admins);

                    $roleAs = 'admin_prodi'; // Role yang sama untuk semua

                    $syncData = array_fill_keys($ids, ['role_as' => $roleAs]);
                    $unit->unitAdmins()->sync($syncData);
                }
            });

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Unit created successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Unit $unit)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Unit $unit)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Unit $unit)
    {
        //
        try {
            DB::transaction(function () use ($request, $unit) {
                $unit->update([
                    'name' => $request->name,
                    'kaprodi_user_id' => $request->kaprodi_user_id,
                ]);
                if ($request->kaprodi_user_id) {
                    $user = User::find($request->kaprodi_user_id);
                    $user->assignRole('kaprodi');
                }

                $unit_admins = $request->unit_admins;
                $ids = array_map(function ($unit_admin) {
                    return $unit_admin['id'];
                }, $unit_admins);

                $roleAs = 'admin_prodi'; // Role yang sama untuk semua

                $syncData = array_fill_keys($ids, ['role_as' => $roleAs]);
                $unit->unitAdmins()->sync($syncData);

                $stases = $request->stases;
                $ids = array_map(function ($stase) {
                    return $stase['id'];
                }, $stases);

                $unit->stases()->sync($ids);

                // $stase_ids = $request->stases;
                // $unit->stases()->sync($stase_ids);
            });

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Unit updated successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Unit $unit): RedirectResponse
    {
        //
        try {
            DB::transaction(function () use ($unit) {
                $oldScheduleFilePath = $unit->schedule_document_path;
                $oldGuidelineFilePath = $unit->guideline_document_path;

                $unit->delete();

                if ($oldScheduleFilePath && Storage::disk('public')->exists($oldScheduleFilePath)) {
                    Storage::disk('public')->delete($oldScheduleFilePath);
                }
                if ($oldGuidelineFilePath && Storage::disk('public')->exists($oldGuidelineFilePath)) {
                    Storage::disk('public')->delete($oldGuidelineFilePath);
                }
            });
            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Unit deleted successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    public function stases(Request $request, Unit $unit)
    {
        $search = $request->input('search');

        $stases = $unit->stases()->when($search, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        })->with(['unitStases' => function ($query) use ($unit) {
            $query->where('unit_id', $unit->id); // Pastikan hanya unitStases terkait yang dimuat
        }, 'unitStases.users', 'locations'])
            ->paginate(10)
            ->withQueryString();

        $dosen_list = $unit->unitDosens;
        $unit = $unit->load('kaprodiUser');

        return Inertia::render('Units/Stases', [
            'unit' => $unit,
            'stases' => $stases,
            'dosen_list' => $dosen_list,
            'filters' => [
                'search' => $search,
            ]
        ]);
    }

    public function staseUpdate(UnitStaseUpdateRequest $request, Unit $unit)
    {
        //
        try {
            DB::transaction(function () use ($request, $unit) {
                $unitStase = UnitStase::where(['unit_id' => $unit->id, 'stase_id' => $request->stase_id])->first();
                $unitStase->update([
                    'is_mandatory' => $request->is_mandatory ? true : false,
                ]);
                $dosen_stases = $request->dosen_stases;
                $ids = array_map(function ($dosen_stase) {
                    return $dosen_stase['id'];
                }, $dosen_stases);
                $unitStase->users()->sync($ids);
            });

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Unit stase updated successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    public function uploadGuidelineDocument(UploadDocumentRequest $request, Unit $unit)
    {
        try {
            // Ambil file yang sudah divalidasi
            $file = $request->file('document');

            // Simpan file ke direktori 'documents'
            $path = $file->store('guidelines', 'public');

            $unit->guideline_document_path = $path;
            $unit->save();

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Unit document uploaded successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    public function deleteGuidelineDocument(Unit $unit)
    {
        try {
            $oldFilePath = $unit->guideline_document_path;

            if ($oldFilePath && Storage::disk('public')->exists($oldFilePath)) {
                Storage::disk('public')->delete($oldFilePath);
            }

            $unit->guideline_document_path = null;
            $unit->save();

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Unit document delete successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }
}
