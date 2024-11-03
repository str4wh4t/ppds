<?php

namespace App\Http\Controllers;

use App\Http\Requests\Speak\AnswerRequest;
use App\Http\Requests\Speak\StoreRequest;
use App\Http\Requests\Speak\UpdateRequest;
use App\Models\Speak;
use App\Http\Requests\StoreSpeakRequest;
use App\Http\Requests\UpdateSpeakRequest;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SpeakController extends Controller
{
    public function __construct()
    {
        // $this->middleware('can:index,speak')->only('index');
        $this->middleware('can:create,\App\Models\Speak')->only('store');
        $this->middleware('can:update,speak')->only('update');
        $this->middleware('can:delete,speak')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, User $user): Response
    {
        //
        $search = $request->input('search');

        $speaks = Speak::when($search, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('speak_title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        })->with('user', 'employee');

        if ($request->user()->hasRole('student')) {
            $speaks = $speaks->where('user_id', $request->user()->id);
        } else {
            if ($user->id != $request->user()->id) {
                $speaks = $speaks->where('user_id', $user->id);
            }
        }

        $speaks = $speaks->paginate(10)
            ->withQueryString();

        return Inertia::render('Speaks/Index', [
            'speaks' => $speaks,
            'filters' => [
                'search' => $search,
            ]
        ]);
    }

    public function studentList(Request $request): Response
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
            ->whereHas('speaks')
            ->paginate(10)
            ->withQueryString();

        $units = Unit::get();
        $dosen_list = User::role('dosen')->get();

        return Inertia::render('Speaks/StudentList', [
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
                    $path = $file->store('speaks', 'public');
                    $fileSizeByte = $file->getSize();
                    $fileSizeInKB = $fileSizeByte / 1024;
                    $fileSize = round($fileSizeInKB, 2);
                }

                Speak::create([
                    'user_id' => $request->user()->id,
                    'speak_title' => $request->speak_title,
                    'description' => $request->description,
                    'speak_document_path' => $path,
                    'speak_document_size' => $fileSize,
                ]);
            });

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Speak created successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Speak $speak)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Speak $speak)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Speak $speak)
    {
        //
        try {
            DB::transaction(function () use ($request, $speak) {
                $oldSpeakFilePath = $speak->speak_document_path;
                $oldFileSize = $speak->speak_document_size;
                // Ambil file yang sudah divalidasi
                $file = $request->file('document');

                // Default path menggunakan file lama
                $path = $speak->speak_document_path;

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
                    $oldFilePath = $speak->speak_document_path;
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

                        // Simpan file baru ke direktori 'speaks'
                        $path = $file->store('speaks', 'public');
                    }
                }

                $newPath = $request->speak_document_path;
                if (!$newPath) {
                    if ($oldSpeakFilePath && Storage::disk('public')->exists($oldSpeakFilePath)) {
                        Storage::disk('public')->delete($oldSpeakFilePath);
                    }
                    $path = null;
                    $fileSize = 0;
                }

                // Update data speak
                $speak->update([
                    'speak_title' => $request->speak_title,
                    'description' => $request->description,
                    'speak_document_path' => $path,
                    'speak_document_size' => $fileSize,
                ]);
            });

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Speak updated successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    public function answer(AnswerRequest $request, Speak $speak)
    {
        //
        try {
            DB::transaction(function () use ($request, $speak) {
                $oldSpeakFilePath = $speak->answer_document_path;
                $oldFileSize = $speak->answer_document_size;
                // Ambil file yang sudah divalidasi
                $file = $request->file('document');

                // Default path menggunakan file lama
                $path = $speak->answer_document_path;

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
                    $oldFilePath = $speak->answer_document_path;
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

                        // Simpan file baru ke direktori 'speaks'
                        $path = $file->store('speaks', 'public');
                    }
                }

                $newPath = $request->answer_document_path;
                if (!$newPath) {
                    if ($oldSpeakFilePath && Storage::disk('public')->exists($oldSpeakFilePath)) {
                        Storage::disk('public')->delete($oldSpeakFilePath);
                    }
                    $path = null;
                    $fileSize = 0;
                }

                // Update data speak
                $speak->update([
                    'employee_user_id' => $request->user()->id,
                    'answer_title' => $request->answer_title,
                    'answer' => $request->answer,
                    'answer_document_path' => $path,
                    'answer_document_size' => $fileSize,
                    'answer_at' => now(),
                ]);
            });

            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Speak updated successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Speak $speak)
    {
        //
        try {
            DB::transaction(function () use ($speak) {
                $oldSpeakFilePath = $speak->speak_document_path;
                $oldReplyFilePath = $speak->answer_document_path;

                $speak->delete();

                if ($oldSpeakFilePath && Storage::disk('public')->exists($oldSpeakFilePath)) {
                    Storage::disk('public')->delete($oldSpeakFilePath);
                }
                if ($oldReplyFilePath && Storage::disk('public')->exists($oldReplyFilePath)) {
                    Storage::disk('public')->delete($oldReplyFilePath);
                }
            });
            return Redirect::back()->with(config('constants.public.flashmsg.ok'), 'Speak deleted successfully');
        } catch (\Exception $e) {
            return Redirect::back()->with(config('constants.public.flashmsg.ko'), $e->getMessage());
        }
    }
}
