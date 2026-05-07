<?php

/**
 * @file: RouteService.php
 * @description: خدمة إدارة المسارات مع التحقيقات الشاملة - Route & Dispatch Service
 * @module: RouteDispatch
 */

namespace App\Modules\RouteDispatch\Services;

use App\Modules\RouteDispatch\Models\Route;
use App\Modules\RouteDispatch\Models\RouteStop;
use App\Modules\RouteDispatch\Models\Vehicle;
use App\Modules\RouteDispatch\Repositories\RouteRepository;
use App\Modules\RouteDispatch\Repositories\VehicleRepository;
use App\Modules\RouteDispatch\Repositories\RouteStopRepository;
use App\Modules\OrderManagement\Repositories\OrderRepository;
use App\Modules\OrderManagement\Models\Order;
use App\Modules\AuthIdentity\Models\Driver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Exception;

class RouteService
{
    protected RouteRepository $routeRepository;
    protected VehicleRepository $vehicleRepository;
    protected RouteStopRepository $routeStopRepository;
    protected OrderRepository $orderRepository;

    // Constants for fuel consumption estimation (liters per 100km)
    private const DEFAULT_FUEL_CONSUMPTION_RATE = 8.5;

    public function __construct(
        RouteRepository $routeRepository,
        VehicleRepository $vehicleRepository,
        RouteStopRepository $routeStopRepository,
        OrderRepository $orderRepository
    ) {
        $this->routeRepository = $routeRepository;
        $this->vehicleRepository = $vehicleRepository;
        $this->routeStopRepository = $routeStopRepository;
        $this->orderRepository = $orderRepository;
    }


    /**
     * Create a new route with comprehensive validations and idempotency
     * @param array $data
     * @param string|null $idempotencyKey
     * @return Route
     * @throws Exception
     */
    public function createRoute(array $data, ?string $idempotencyKey = null): Route
    {
        // 1. Idempotency check and acquire processing lock
        $lockKey = null;
        if ($idempotencyKey) {
            $cachedRoute = Cache::get("route_idempotency_{$idempotencyKey}");
            if ($cachedRoute) {
                return $cachedRoute;
            }

            $lockKey = "route_idempotency_lock_{$idempotencyKey}";
            // Try to acquire a lock atomically. If unable, another request is processing the same key.
            $acquired = Cache::add($lockKey, true, 3600); // 1 hour TTL
            if (!$acquired) {
                throw new Exception('Another request with the same Idempotency-Key is already being processed.');
            }
        }

        // Use database transaction to prevent partial inserts
        try {
            $route = DB::transaction(function () use ($data, $idempotencyKey) {
            // 2. Validate driver
            $driver = $this->validateDriver($data['driver_id']);

            // 3. Validate vehicle
            $vehicle = $this->validateVehicle($data['vehicle_id']);

            // 4. Validate driver license matches vehicle type
            $this->validateDriverLicenseMatchesVehicle($driver, $vehicle);

            // 5. Extract and validate stops
            $stops = $data['stops'] ?? [];
            if (empty($stops)) {
                throw new Exception('Route must have at least one stop.');
            }

            // 6. Validate orders exist and are not assigned to other routes
            $this->validateOrdersAvailable($stops);

            // 7. Calculate total distance and stops
            $totalDistance = $this->calculateTotalDistance($stops);
            $totalStops = count($stops);

            // 8. Calculate fuel consumption estimation
            $fuelConsumption = $this->calculateFuelConsumption($totalDistance, $vehicle);

            // 9. Create the route
            $routeData = [
                'route_name' => $data['route_name'],
                'driver_id' => $data['driver_id'],
                'dispatcher_id' => $data['dispatcher_id'] ?? null,
                'vehicle_id' => $data['vehicle_id'],
                'scheduled_start_time' => $data['scheduled_start_time'],
                'scheduled_end_time' => $data['scheduled_end_time'] ?? null,
                'status' => $data['status'] ?? 'Planned',
                'total_distance' => $totalDistance,
                'total_stops' => $totalStops,
                'fuel_consumption_est' => $fuelConsumption,
            ];

            $route = Route::create($routeData);

            // 10. Create route stops
            $this->createRouteStops($route->route_id, $stops);

            // 11. Update orders with driver_id and ETA
            $this->updateOrdersForRoute($route, $stops);

            // 12. Update driver record: set status to OnShift and assign vehicle
            try {
                $driver->update([
                    'status' => 'OnShift',
                    'vehicle_id' => $route->vehicle_id,
                ]);
            } catch (Exception $e) {
                // If driver update fails, rollback transaction by throwing
                throw new Exception('Failed to update driver status: ' . $e->getMessage());
            }

            // Return fresh route from transaction
            return $route->fresh()->load('stops');
            });
        } catch (Exception $e) {
            // Ensure lock is released on failure
            if (isset($lockKey)) {
                Cache::forget($lockKey);
            }
            throw $e;
        }

        // Note: The transaction returned a route model; cache it and release the lock
        if ($idempotencyKey && isset($route)) {
            try {
                $routeToCache = $route->fresh()->load('stops');
                Cache::put("route_idempotency_{$idempotencyKey}", $routeToCache, now()->addHour());
            } finally {
                Cache::forget($lockKey);
            }
            return $routeToCache;
        }

        return $route->fresh()->load('stops');
    }

    /**
     * Update an existing route
     * @param int $routeId
     * @param array $data
     * @return Route
     * @throws Exception
     */
    public function updateRoute(int $routeId, array $data): Route
    {
        $route = Route::findOrFail($routeId);

        // Prevent updates to completed or cancelled routes
        if (in_array($route->status, ['Completed', 'Cancelled'])) {
            throw new Exception("Cannot update a {$route->status} route.");
        }

        // 1. Validate driver if being changed
        if (isset($data['driver_id']) && $data['driver_id'] !== $route->driver_id) {
            $driver = $this->validateDriver($data['driver_id']);
            $vehicle = $route->vehicle;
            $this->validateDriverLicenseMatchesVehicle($driver, $vehicle);
        }

        // 2. Validate vehicle if being changed
        if (isset($data['vehicle_id']) && $data['vehicle_id'] !== $route->vehicle_id) {
            $vehicle = $this->validateVehicle($data['vehicle_id']);
            $driver = $route->driver;
            $this->validateDriverLicenseMatchesVehicle($driver, $vehicle);
        }

        // 3. If stops are being updated
        if (isset($data['stops'])) {
            $this->validateOrdersAvailable($data['stops']);
            
            // Delete old stops and create new ones
            RouteStop::where('route_id', $routeId)->delete();
            $this->createRouteStops($routeId, $data['stops']);

            // Update orders with new ETA and driver
            $route->refresh(); // Refresh to get updated data
            $this->updateOrdersForRoute($route, $data['stops']);

            // Recalculate fuel consumption
            $totalDistance = $this->calculateTotalDistance($data['stops']);
            $vehicle = $route->vehicle;
            $data['fuel_consumption_est'] = $this->calculateFuelConsumption($totalDistance, $vehicle);
            $data['total_distance'] = $totalDistance;
            $data['total_stops'] = count($data['stops']);
        }

        // Update the route
        $route->update($data);

        return $route->fresh()->load('stops');
    }

    /**
     * Validate driver exists and is active
     * @param int $driverId
     * @return Driver
     * @throws Exception
     */
    private function validateDriver(int $driverId): Driver
    {
        $driver = Driver::find($driverId);

        if (!$driver) {
            throw new Exception("Driver with ID {$driverId} does not exist.");
        }

        if ($driver->status !== 'Available' && $driver->status !== 'OnShift') {
            throw new Exception("Driver is not available. Current status: {$driver->status}.");
        }

        return $driver;
    }

    /**
     * Validate vehicle exists and is active
     * @param int $vehicleId
     * @return Vehicle
     * @throws Exception
     */
    private function validateVehicle(int $vehicleId): Vehicle
    {
        $vehicle = $this->vehicleRepository->findById($vehicleId);

        if (!$vehicle) {
            throw new Exception("Vehicle with ID {$vehicleId} does not exist.");
        }

        if ($vehicle->Status !== 'Active') {
            throw new Exception("Vehicle is not active. Current status: {$vehicle->Status}.");
        }

        return $vehicle;
    }

    /**
     * Validate driver license type matches vehicle type
     * @param Driver $driver
     * @param Vehicle $vehicle
     * @throws Exception
     */
    private function validateDriverLicenseMatchesVehicle(Driver $driver, Vehicle $vehicle): void
    {
        // Map vehicle types to required license types
        $licenseRequirements = [
            'light' => 'light',
            'medium' => 'heavy',
            'heavy' => 'heavy',
            'refrigerated' => 'refrigerated',
            'tanker' => 'heavy',
        ];

        $requiredLicense = $licenseRequirements[strtolower($vehicle->VehicleType)] ?? null;

        if (!$requiredLicense) {
            throw new Exception("Unknown vehicle type: {$vehicle->VehicleType}.");
        }

        if ($driver->license_type !== $requiredLicense) {
            throw new Exception(
                "Driver {$driver->driver_id} has '{$driver->license_type}' license, " .
                "but vehicle {$vehicle->vehicle_id} ({$vehicle->VehicleType}) requires '{$requiredLicense}' license."
            );
        }
    }

    /**
     * Validate all orders exist and are not already assigned to active routes
     * @param array $stops
     * @throws Exception
     */
    private function validateOrdersAvailable(array $stops): void
    {
        if (empty($stops)) {
            return;
        }

        $orderIds = array_column($stops, 'order_id');
        $orderIds = array_filter($orderIds); // Remove null values

        if (empty($orderIds)) {
            return;
        }

        // Check if all orders exist
        $orders = $this->orderRepository->findByIds($orderIds);
        if ($orders->count() !== count(array_unique($orderIds))) {
            throw new Exception('One or more order IDs do not exist in the database.');
        }

        // Check if any order is already assigned to an active route
        $assignedOrders = RouteStop::whereIn('order_id', $orderIds)
            ->whereHas('route', function ($query) {
                $query->whereIn('status', ['Planned', 'Active']); // Only check non-completed routes
            })
            ->pluck('order_id')
            ->toArray();

        if (!empty($assignedOrders)) {
            throw new Exception(
                'Order IDs: ' . implode(', ', $assignedOrders) . ' are already assigned to active routes.'
            );
        }
    }

    /**
     * Calculate total distance from stops coordinates
     * @param array $stops
     * @return float
     */
    private function calculateTotalDistance(array $stops): float
    {
        if (count($stops) < 2) {
            return 0.0;
        }

        $totalDistance = 0.0;

        // Calculate distance between consecutive stops
        for ($i = 0; $i < count($stops) - 1; $i++) {
            $lat1 = (float) ($stops[$i]['latitude'] ?? 0);
            $lon1 = (float) ($stops[$i]['longitude'] ?? 0);
            $lat2 = (float) ($stops[$i + 1]['latitude'] ?? 0);
            $lon2 = (float) ($stops[$i + 1]['longitude'] ?? 0);

            $distance = $this->haversineDistance($lat1, $lon1, $lat2, $lon2);
            $totalDistance += $distance;
        }

        return round($totalDistance, 2);
    }

    /**
     * Calculate Haversine distance between two coordinates in kilometers
     * @param float $lat1
     * @param float $lon1
     * @param float $lat2
     * @param float $lon2
     * @return float Distance in kilometers
     */
    private function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }

    /**
     * Calculate fuel consumption estimation
     * Formula: (total_distance / 100) * fuel_consumption_rate
     * @param float $totalDistance in kilometers
     * @param Vehicle $vehicle
     * @return float Fuel consumption in liters
     */
    private function calculateFuelConsumption(float $totalDistance, Vehicle $vehicle): float
    {
        // Use vehicle-specific fuel efficiency if available, otherwise use default
        $fuelRate = self::DEFAULT_FUEL_CONSUMPTION_RATE;

        // Calculate: distance in 100km segments * consumption per 100km
        $fuelConsumption = ($totalDistance / 100) * $fuelRate;

        return round($fuelConsumption, 2);
    }

    /**
     * Create route stops for a route
     * @param int $routeId
     * @param array $stops
     * @return void
     */
    private function createRouteStops(int $routeId, array $stops): void
    {
        foreach ($stops as $stopData) {
            RouteStop::create([
                'route_id' => $routeId,
                'stop_no' => $stopData['stop_no'],
                'order_id' => $stopData['order_id'] ?? null,
                'eta' => $stopData['eta'] ?? null,
                'latitude' => $stopData['latitude'] ?? null,
                'longitude' => $stopData['longitude'] ?? null,
            ]);
        }
    }

    /**
     * Update orders with driver_id, vehicle_id, ETA, Status, and UpdatedAt for the route
     * @param Route $route
     * @param array $stops
     * @return void
     */
    private function updateOrdersForRoute(Route $route, array $stops): void
    {
        // Map route status to order status
        $statusMap = [
            'Planned' => 'Assigned',
            'Active' => 'InTransit',
            'Completed' => 'Delivered',
            'Cancelled' => 'Pending',
        ];

        $orderStatus = $statusMap[$route->status] ?? 'Assigned';

        foreach ($stops as $stopData) {
            $orderId = $stopData['order_id'] ?? null;
            $eta = $stopData['eta'] ?? null;

            // Normalize ETA to match `order.ETA` column (char(10) - stored as HH:MM in seeders)
            $etaForDb = null;
            if ($eta) {
                try {
                    $dt = Carbon::parse($eta);
                    $etaForDb = $dt->format('H:i');
                } catch (\Exception $e) {
                    // If parsing fails, fall back to original string truncated to 10 chars
                    $etaForDb = mb_substr((string) $eta, 0, 10);
                }
            }

            if ($orderId) {
                Order::where('OrderID', $orderId)->update([
                    'DriverID(FK)' => $route->driver_id,
                    'vehicle_id(FK)' => $route->vehicle_id,
                    'ETA' => $etaForDb,
                    'Status' => $orderStatus,
                    'UpdatedAt' => now(),
                ]);
            }
        }
    }

    /**
     * Get route with all stops and related data
     * @param int $routeId
     * @return Route
     * @throws Exception
     */
    public function getRoute(int $routeId): Route
    {
        $route = Route::with(['driver', 'vehicle', 'stops.order'])->find($routeId);

        if (!$route) {
            throw new Exception("Route with ID {$routeId} not found.");
        }

        return $route;
    }

    /**
     * Get all routes for a driver
     * @param int $driverId
     * @return Collection
     */
    public function getDriverRoutes(int $driverId): Collection
    {
        return $this->routeRepository->getDriverRoutes($driverId);
    }

    /**
     * Cancel a route
     * @param int $routeId
     * @param string $reason
     * @return Route
     * @throws Exception
     */
    public function cancelRoute(int $routeId, string $reason = ''): Route
    {
        $route = Route::find($routeId);

        if (!$route) {
            throw new Exception("Route with ID {$routeId} not found.");
        }

        if ($route->status === 'Completed') {
            throw new Exception('Cannot cancel a completed route.');
        }

        $route->update([
            'status' => 'Cancelled',
        ]);

        return $route;
    }

    /**
     * بدء تنفيذ المسار
     * @param int $routeId
     * @return mixed
     * @throws Exception
     */
    public function startRoute(int $routeId)
    {
        $route = Route::findOrFail($routeId);

        if (strtolower($route->status) !== 'planned') {
            throw new Exception("Only planned routes can be started. Current status: {$route->status}");
        }

        // 2. Update status to 'active' and set actual_start_time = now()
        $route->status = 'Active';
        $route->actual_start_time = Carbon::now();
        $route->save();

        // 3. Update vehicle status to 'in_service'
        if ($route->vehicle_id) {
            $this->vehicleRepository->updateStatus($route->vehicle_id, 'in_service');
        }

        // 4. Fire event: RouteStarted ($routeId)
        // event(new \App\Events\RouteStarted($routeId));

        // 5. Return updated route
        return $route;
    }

    /**
     * إنهاء المسار وتحرير المركبة
     * @param int $routeId
     * @return mixed
     * @throws Exception
     */
    public function completeRoute(int $routeId)
    {
        $route = Route::findOrFail($routeId);

        if (strtolower($route->status) !== 'active') {
            throw new Exception("Only active routes can be completed. Current status: {$route->status}");
        }

        // 2. Update status to 'completed'
        // Note: The 'routes' table schema does not have a 'completed_at' or 'actual_end_time' column
        $route->status = 'Completed';
        $route->save();

        // 3. Update vehicle status to 'available'
        if ($route->vehicle_id) {
            $this->vehicleRepository->updateStatus($route->vehicle_id, 'available');
        }

        // 4. Fire event: RouteCompleted ($routeId)
        // event(new \App\Events\RouteCompleted($routeId));

        // 5. Return updated route
        return $route;
    }

    /**
     * انتقال المسار لسائق آخر (Shift Transition - RD-08 / fn09)
     * @param int $routeId
     * @param int $newDriverId
     * @return mixed
     */
    public function shiftTransition(int $routeId, int $newDriverId)
    {
        // TODO: Transfer route to new driver
        // 1. Validate new driver exists and is active
        // 2. Check license match with vehicle
        // 3. Update route driver_id
        // 4. Increment version
        // 5. Notify new driver
        // 6. Return updated route
    }
}
