<?php

namespace App\Http\Controllers;

use App\Models\Speak;
use App\Http\Requests\StoreSpeakRequest;
use App\Http\Requests\UpdateSpeakRequest;

class SpeakController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function store(StoreSpeakRequest $request)
    {
        //
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
    public function update(UpdateSpeakRequest $request, Speak $speak)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Speak $speak)
    {
        //
    }
}
