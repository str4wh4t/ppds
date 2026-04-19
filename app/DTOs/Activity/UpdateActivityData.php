<?php

namespace App\DTOs\Activity;

use App\Http\Requests\Activity\UpdateRequest;

class UpdateActivityData
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $date,
        public readonly string $startTime,
        public readonly string $finishTime,
        public readonly string $description,
        public readonly ?int $staseId,
        public readonly ?int $locationId,
        public readonly ?int $dosenUserId,
    ) {}

    public static function fromUpdateRequest(UpdateRequest $request): self
    {
        return self::fromValidated($request->validated());
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            name: $validated['name'],
            type: $validated['type'],
            date: $validated['date'],
            startTime: $validated['start_time'],
            finishTime: $validated['finish_time'],
            description: $validated['description'],
            staseId: isset($validated['stase_id']) ? (int) $validated['stase_id'] : null,
            locationId: isset($validated['location_id']) ? (int) $validated['location_id'] : null,
            dosenUserId: isset($validated['dosen_user_id']) ? (int) $validated['dosen_user_id'] : null,
        );
    }
}
