<?php

namespace App\Modules\RealtimeTracking\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RealtimeTracking\Requests\LocationRequest;
use App\Modules\RealtimeTracking\Services\LocationService;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    protected LocationService $locationService;

    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    public function ingest(LocationRequest $request): JsonResponse
    {
        try {
            $result = $this->locationService->ingestLocation($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Location recorded successfully',
                'data' => $result,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to record location: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function lastKnown(int $driverId): JsonResponse
    {
        $location = $this->locationService->getLastKnownLocation($driverId);

        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => 'No saved location found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Last location fetched successfully',
            'data' => $location,
        ]);
    }

    public function routeTrail(int $routeId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Route trail fetched successfully',
            'data' => $this->locationService->getRouteTrail($routeId),
        ]);
    }

    public function driverStatus(int $driverId): JsonResponse
    {
        $isOffline = $this->locationService->isDriverOffline($driverId);

        return response()->json([
            'success' => true,
            'data' => [
                'driver_id' => $driverId,
                'status' => $isOffline ? 'offline' : 'online',
                'is_offline' => $isOffline,
            ],
        ]);
    }
}
