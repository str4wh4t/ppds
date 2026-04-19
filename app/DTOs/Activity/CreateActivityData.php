<?php

namespace App\DTOs\Activity;

use App\Http\Requests\Activity\StoreRequest;

class CreateActivityData
{
    public function __construct(
        public readonly int $userId,
        public readonly ?int $studentUnitId,
        public readonly string $name,
        public readonly string $type,
        public readonly string $date,
        public readonly string $startTime,
        public readonly string $finishTime,
        public readonly string $description,
        public readonly ?int $staseId,
        public readonly ?int $locationId,
        public readonly ?int $dosenUserId,
        public readonly ?float $latitude,
        public readonly ?float $longitude,
        public readonly ?string $checkinPhotoPath,
        public readonly string $createdVia,
        public readonly ?array $deviceInfo,
    ) {}

    public static function fromRequest(StoreRequest $request): self
    {
        $validated = $request->validated();
        $user = $request->user();
        $isApiRequest = $request->is('api/*');

        return new self(
            userId: $user->id,
            studentUnitId: $user->student_unit_id,
            name: $validated['name'],
            type: $validated['type'],
            date: $validated['date'],
            startTime: $validated['start_time'],
            finishTime: $validated['finish_time'],
            description: $validated['description'],
            staseId: $validated['stase_id'] ?? null,
            locationId: $validated['location_id'] ?? null,
            dosenUserId: $validated['dosen_user_id'] ?? null,
            latitude: isset($validated['latitude']) ? (float) $validated['latitude'] : null,
            longitude: isset($validated['longitude']) ? (float) $validated['longitude'] : null,
            checkinPhotoPath: null,
            createdVia: $isApiRequest ? 'api' : 'web',
            deviceInfo: $isApiRequest ? [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'accept_language' => $request->header('Accept-Language'),
            ] : null,
        );
    }
}
