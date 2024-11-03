<?php

namespace App\Http\Controllers;

use App\Http\Requests\Portofolio\StoreRequest;
use App\Http\Requests\Portofolio\UpdateRequest;
use App\Models\Portofolio;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PortofolioController extends Controller
{
    public function __construct()
    {
        // $this->middleware('can:index,portofolio')->only('index');
        $this->middleware('can:create,\App\Models\Portofolio')->only('store');
        $this->middleware('can:update,portofolio')->only('update');
        $this->middleware('can:delete,portofolio')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, User $user): Response
    {
        $search = $request->input('search');
        $unitSelected = $request->input('units');

        $portofolios = Portofolio::when($search, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($query) use ($search) {
                        $query->where('fullname', 'like', "%{$search}%");
                    });
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
            $portofolios = $portofolios->where('user_id', $request->user()->id);
        } else {
            if ($user->id != $request->user()->id) {
                $portofolios = $portofolios->where('user_id', $user->id);
            }
        }

        $portofolios = $portofolios->paginate(10)->withQueryString();
        $units = Unit::all();

        return Inertia::render('Portofolios/Index', [
            'portofolios' => $portofolios,
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
    public function store(StoreRequest $request): RedirectResponse
    {
        //
        try {
            DB::transaction(function () use ($request) {
                // Ambil file yang sudah divalidasi
                $file = $request->file('document');

                // Simpan file ke direktori 'documents'
                $path = $file->store('portofolios', 'public');

                Portofolio::create([
                    'user_id' => $request->user()->id,
                    'name' => $request->name,
                    'description' => $request->description,
                    'portofolio_document_path' => $path,
                ]);
            });

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Portofolio created successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Portofolio $portofolio)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Portofolio $portofolio)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Portofolio $portofolio)
    {
        try {
            DB::transaction(function () use ($request, $portofolio) {
                // Ambil file yang sudah divalidasi
                $file = $request->file('document');

                // Default path menggunakan file lama
                $path = $portofolio->portofolio_document_path;

                // Cek jika ada file baru diunggah
                if ($file) {
                    // Menghitung hash dari file baru
                    $newFileHash = hash_file('md5', $file->getRealPath());

                    // Menghitung hash dari file lama jika ada
                    $oldFilePath = $portofolio->portofolio_document_path;
                    $oldFileHash = $oldFilePath && Storage::disk('public')->exists($oldFilePath)
                        ? md5(Storage::disk('public')->get($oldFilePath))
                        : null;

                    // Hanya update jika file baru berbeda dengan file lama
                    if ($newFileHash !== $oldFileHash) {
                        // Hapus file lama jika ada
                        if ($oldFilePath && Storage::disk('public')->exists($oldFilePath)) {
                            Storage::disk('public')->delete($oldFilePath);
                        }

                        // Simpan file baru ke direktori 'portofolios'
                        $path = $file->store('portofolios', 'public');
                    }
                }

                // Update data portofolio
                $portofolio->update([
                    'name' => $request->name,
                    'description' => $request->description,
                    'portofolio_document_path' => $path,
                ]);
            });

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Portofolio updated successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Portofolio $portofolio)
    {
        //
        try {
            DB::transaction(function () use ($portofolio) {
                $oldFilePath = $portofolio->portofolio_document_path;

                $portofolio->delete();

                if ($oldFilePath && Storage::disk('public')->exists($oldFilePath)) {
                    Storage::disk('public')->delete($oldFilePath);
                }
            });
            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Portofolio deleted successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }
}
