<?php

namespace App\Modules\RealtimeTracking\Services;

use App\Modules\RealtimeTracking\Repositories\GpsPingRepository;
use Carbon\Carbon;

class LocationService
{
    protected GpsPingRepository $gpsPingRepository;

    public function __construct(GpsPingRepository $gpsPingRepository)
    {
        $this->gpsPingRepository = $gpsPingRepository;
    }

    public function ingestLocation(array $data): array
    {
        $recordedAt = isset($data['recorded_at'])
            ? Carbon::parse($data['recorded_at'])
            : now();

        $data['recorded_at'] = $recordedAt;
        $data['is_spoofed'] = $data['is_spoofed'] ?? false;
        $data['speed_kmh'] = $data['speed_kmh'] ?? 0;

        $ping = $this->gpsPingRepository->recordPing($data);

        return [
            'ping' => $ping->toArray(),
            'is_spoofed' => (bool) $data['is_spoofed'],
            'geofence_events' => [],
        ];
    }

    public function getLastKnownLocation(int $driverId): ?array
    {
        $ping = $this->gpsPingRepository->getLastKnownLocation($driverId);

        return $ping ? $ping->toArray() : null;
    }

    public function getRouteTrail(int $routeId): array
    {
        return $this->gpsPingRepository->getRouteTrail($routeId)->toArray();
    }

    public function isDriverOffline(int $driverId): bool
    {
        return $this->gpsPingRepository->isDriverOffline($driverId, 3);
    }

    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusMeters = 6371000.0;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return 2 * $earthRadiusMeters * atan2(sqrt($a), sqrt(1 - $a));
    }
}
