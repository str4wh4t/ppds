<?php

namespace App\Http\Controllers;

use App\Http\Requests\Consult\StoreRequest;
use App\Http\Requests\Consult\UpdateRequest;
use App\Models\Consult;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ConsultController extends Controller
{
    public function __construct()
    {
        // $this->middleware('can:index,consult')->only('index');
        $this->middleware('can:create,\App\Models\Consult')->only('store');
        $this->middleware('can:update,consult')->only('update');
        $this->middleware('can:delete,consult')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, User $user): Response
    {
        //
        if ($user->roles->count() == 1) {
            if ($user->hasRole('student')) {
                $user =  $request->user();
            }
        }

        $search = $request->input('search');

        $consults = Consult::when($search, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        })->where('user_id', $user->id)
            ->with('user', 'dosbing')
            ->paginate(10)
            ->withQueryString();

        $dosbing_user = $user->dosbingUser;

        return Inertia::render('Consults/Index', [
            'consults' => $consults,
            'dosbing_user' => $dosbing_user,
            'filters' => [
                'search' => $search,
            ]
        ]);
    }

    public function studentList(Request $request): Response
    {
        //
        dd('2');
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

        return Inertia::render('Consults/StudentList', [
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
    public function store(StoreRequest $request): RedirectResponse
    {
        //
        try {
            DB::transaction(function () use ($request) {
                // Ambil file yang sudah divalidasi
                $file = $request->file('document');

                $path = null;
                $fileSize = 0;

                if ($file) {
                    $path = $file->store('consults', 'public');
                    $fileSizeByte = $file->getSize();
                    $fileSizeInKB = $fileSizeByte / 1024;
                    $fileSize = round($fileSizeInKB, 2);
                }

                Consult::create([
                    'user_id' => $request->user()->id,
                    'consult_title' => $request->consult_title,
                    'description' => $request->description,
                    'consult_document_path' => $path,
                    'consult_document_size' => $fileSize,
                ]);
            });

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Consult created successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Consult $consult)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Consult $consult)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Consult $consult)
    {
        //
        try {
            DB::transaction(function () use ($request, $consult) {
                $oldConsultFilePath = $consult->consult_document_path;
                $oldFileSize = $consult->speak_document_size;
                // Ambil file yang sudah divalidasi
                $file = $request->file('document');

                // Default path menggunakan file lama
                $path = $consult->consult_document_path;

                $fileSize = $oldFileSize;

                if ($file) {
                    $fileSizeByte = $file->getSize();
                    $fileSizeInKB = $fileSizeByte / 1024;
                    $fileSize = round($fileSizeInKB, 2);
                }

                // Cek jika ada file baru diunggah
                if ($file) {
                    // Menghitung hash dari file baru
                    $newFileHash = hash_file('md5', $file->getRealPath());

                    // Menghitung hash dari file lama jika ada
                    $oldFilePath = $consult->consult_document_path;
                    $oldFileHash = $oldFilePath && Storage::disk('public')->exists($oldFilePath)
                        ? md5(Storage::disk('public')->get($oldFilePath))
                        : null;

                    // Hanya update jika file baru berbeda dengan file lama
                    // dd($newFileHash !== $oldFileHash);
                    if ($newFileHash !== $oldFileHash) {
                        // Hapus file lama jika ada
                        if ($oldFilePath && Storage::disk('public')->exists($oldFilePath)) {
                            Storage::disk('public')->delete($oldFilePath);
                        }

                        // Simpan file baru ke direktori 'consults'
                        $path = $file->store('consults', 'public');
                    }
                }

                $newPath = $request->consult_document_path;
                if (!$newPath) {
                    if ($oldConsultFilePath && Storage::disk('public')->exists($oldConsultFilePath)) {
                        Storage::disk('public')->delete($oldConsultFilePath);
                    }
                    $path = null;
                    $fileSize = 0;
                }

                // Update data consult
                $consult->update([
                    'consult_title' => $request->consult_title,
                    'description' => $request->description,
                    'consult_document_path' => $path,
                    'consult_document_size' => $fileSize,
                ]);
            });

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Consult updated successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Consult $consult)
    {
        //
        try {
            DB::transaction(function () use ($consult) {
                $oldConsultFilePath = $consult->consult_document_path;
                $oldReplyFilePath = $consult->reply_document_path;

                $consult->delete();

                if ($oldConsultFilePath && Storage::disk('public')->exists($oldConsultFilePath)) {
                    Storage::disk('public')->delete($oldConsultFilePath);
                }
                if ($oldReplyFilePath && Storage::disk('public')->exists($oldReplyFilePath)) {
                    Storage::disk('public')->delete($oldReplyFilePath);
                }
            });
            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Consult deleted successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }
}
