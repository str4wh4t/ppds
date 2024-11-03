<?php

namespace App\Http\Requests\Activity;

use App\Models\Activity;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(config('constants.public.activity_types'))],
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'finish_time' => 'required',
            'description' => 'required|string',
            'is_approved' => 'boolean',
            'approved_by' => 'nullable|integer|exists:users,id',
            'approved_at' => 'nullable|date',
            'stase_id' => 'nullable|integer|exists:stases,id|required_if:type,stase',
        ];
    }

    public function withValidator($validator)
    {
        $date = $this->input('date');
        $startTime = $this->input('start_time');
        $finishTime = $this->input('finish_time');
        $validator->after(function ($validator) use ($startTime, $finishTime) {

            if ($startTime && $finishTime && $startTime >= $finishTime) {
                $validator->errors()->add('finish_time', 'Waktu selesai tidak boleh kurang dari waktu mulai.');
            }

            if (!(preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $finishTime) || $finishTime === '24:00')) {
                $validator->errors()->add(
                    'finish_time',
                    'The finish time must be in the format HH:MM (24-hour format) or 24:00.'
                );
            }
        });

        // Ambil start_date dan end_date dari request
        $startDate = Carbon::parse($date . ' ' . $startTime);
        $endDate = Carbon::parse($date . ' ' . $finishTime);

        // Tambahkan validasi custom untuk memeriksa overlap
        $validator->after(function ($validator) use ($date, $startDate, $endDate) {
            // Query untuk memeriksa apakah ada aktivitas yang memiliki overlap
            $overlapExists = Activity::where('user_id', $this->user()->id)->whereDate('start_date', $date)
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->where('start_date', '<', $endDate)
                        ->where('end_date', '>', $startDate);
                })->exists();

            if ($overlapExists) {
                $validator->errors()->add('start_time', 'Waktu yang dipilih bentrok dengan aktifitas yang lain.');
                $validator->errors()->add('finish_time', 'Waktu yang dipilih bentrok dengan aktifitas yang lain.');
            }
        });
    }
}
