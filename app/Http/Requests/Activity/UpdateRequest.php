<?php

namespace App\Http\Requests\Activity;

use App\Models\Activity;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
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
            // 'is_approved' => 'boolean',
            // 'approved_by' => 'nullable|integer|exists:users,id', // Assuming users table exists for approved_by
            // 'approved_at' => 'nullable|date',
            // 'unit_stase_id' => 'nullable|integer|exists:units,id|required_with:stase_id',
            'stase_id' => 'nullable|integer|exists:stases,id|required_if:type,jaga',
            'location_id' => 'nullable|integer|exists:locations,id|required_if:type,jaga',
            'dosen_user_id' => 'nullable|integer|exists:users,id',
        ];
    }

    public function withValidator($validator)
    {
        $activity = $this->route('activity');
        $date = $this->input('date');
        $startTime = $this->input('start_time');
        $finishTime = $this->input('finish_time');
        $staseId = $this->input('stase_id');
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
        $validator->after(function ($validator) use ($activity, $date, $startDate, $endDate) {
            // Query untuk memeriksa apakah ada aktivitas yang memiliki overlap
            $overlapExists = Activity::where('user_id', $this->user()->id)->where('id', '!=', $activity->id)->whereDate('start_date', $date)
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

    // public function messages()
    // {
    //     return [
    //         'user_id.required' => 'User ID is required.',
    //         'user_id.integer' => 'User ID must be an integer.',
    //         'name.required' => 'Activity name is required.',
    //         'type.required' => 'Activity type is required.',
    //         'type.in' => 'Activity type must be either stase or non-stase.',
    //         'start_date.required' => 'Start date is required.',
    //         'end_date.after_or_equal' => 'End date must be after or equal to the start date.',
    //         'time_spend.date_format' => 'Time spend must be in the format HH:MM:SS.',
    //         'is_approved.boolean' => 'Is approved must be true or false.',
    //         'approved_by.integer' => 'Approved by must be an integer.',
    //         'approved_at.date' => 'Approved at must be a valid date.',
    //         'unit_stase_id.integer' => 'Unit Stase ID must be an integer.',
    //     ];
    // }
}
