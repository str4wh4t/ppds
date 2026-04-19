<?php

namespace App\Http\Requests\Activity;

use App\Models\Activity;
use App\Models\UnitStase;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi update: `finish_time` tidak boleh **sebelum** `start_time` (perbandingan string `H:i` / `24:00`); **sama** diperbolehkan.
 */
class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Activity|null $activity */
        $activity = $this->route('activity');

        return $activity instanceof Activity
            && $this->user()->can('update', $activity);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(config('constants.public.activity_types'))],
            'date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'finish_time' => 'required',
            'description' => 'required|string',
            'stase_id' => 'nullable|integer|exists:stases,id|required_if:type,nonjaga',
            'location_id' => [
                'nullable',
                'integer',
                'required_if:type,nonjaga',
                Rule::exists('stase_locations', 'location_id')->where(function ($query) {
                    $query->where('stase_id', $this->input('stase_id'));
                }),
            ],
            'dosen_user_id' => 'nullable|integer|exists:users,id',
        ];
    }

    public function withValidator($validator): void
    {
        /** @var Activity $activity */
        $activity = $this->route('activity');
        $ownerId = (int) $activity->user_id;

        $date = $this->input('date');
        $startTime = $this->input('start_time');
        $finishTime = $this->input('finish_time');

        $validator->after(function ($validator) use ($startTime, $finishTime) {
            if ($startTime && $finishTime && $startTime > $finishTime) {
                $validator->errors()->add('finish_time', 'Waktu selesai tidak boleh sebelum waktu mulai.');
            }

            if (! (preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $finishTime) || $finishTime === '24:00')) {
                $validator->errors()->add(
                    'finish_time',
                    'The finish time must be in the format HH:MM (24-hour format) or 24:00.'
                );
            }
        });

        $startDate = Carbon::parse($date.' '.$startTime);
        $endDate = Carbon::parse($date.' '.$finishTime);

        $validator->after(function ($validator) use ($activity, $date, $startDate, $endDate, $ownerId) {
            $overlapExists = Activity::query()
                ->where('user_id', $ownerId)
                ->where('id', '!=', $activity->id)
                ->whereDate('start_date', $date)
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->where('start_date', '<', $endDate)
                        ->where('end_date', '>', $startDate);
                })
                ->exists();

            if ($overlapExists) {
                $validator->errors()->add('start_time', 'Waktu yang dipilih bentrok dengan aktifitas yang lain.');
                $validator->errors()->add('finish_time', 'Waktu yang dipilih bentrok dengan aktifitas yang lain.');
            }
        });

        $validator->after(function ($validator) use ($activity) {
            if ($this->input('type') !== 'nonjaga' || ! $this->filled('stase_id')) {
                return;
            }
            $activity->loadMissing('user');
            $unitId = $activity->user?->student_unit_id;
            if (! $unitId) {
                $validator->errors()->add('stase_id', 'User pemilik activity tidak memiliki student_unit_id.');

                return;
            }
            $exists = UnitStase::query()
                ->where('stase_id', $this->input('stase_id'))
                ->where('unit_id', $unitId)
                ->exists();
            if (! $exists) {
                $validator->errors()->add('stase_id', 'Stase tidak tersedia untuk unit prodi pemilik activity.');
            }
        });
    }
}
