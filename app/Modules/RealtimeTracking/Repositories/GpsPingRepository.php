<?php

namespace App\Modules\RealtimeTracking\Repositories;

use App\Modules\RealtimeTracking\Models\GpsPing;
use App\Modules\Shared\Repositories\BaseRepository;

class GpsPingRepository extends BaseRepository
{
    public function __construct(GpsPing $model)
    {
        parent::__construct($model);
    }

    public function getLastKnownLocation(int $driverId): ?GpsPing
    {
        return $this->model->forDriver($driverId)
            ->notSpoofed()
            ->latest('recorded_at')
            ->first();
    }

    public function getRouteTrail(int $routeId)
    {
        return $this->model
            ->where('route_id', $routeId)
            ->notSpoofed()
            ->orderBy('recorded_at')
            ->get();
    }

    public function isDriverOffline(int $driverId, int $minutes = 3): bool
    {
        return !$this->model
            ->forDriver($driverId)
            ->notSpoofed()
            ->recent($minutes)
            ->exists();
    }

    public function recordPing(array $data): GpsPing
    {
        return $this->create($data);
    }

    public function detectSpoofing(int $driverId, float $newLat, float $newLng, \DateTime $newTime): bool
    {
        return false;
    }
}
