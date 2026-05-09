<?php

/**
 * @file: VehicleService.php
 * @description: خدمة إدارة المركبات - CRUD والإتاحة والقفل
 * @module: RouteDispatch
 * @author: Team Leader (Khalid)
 */

namespace App\Modules\RouteDispatch\Services;

use App\Modules\RouteDispatch\Repositories\VehicleRepository;
use Exception;

class VehicleService
{
    protected VehicleRepository $vehicleRepository;

    public function __construct(VehicleRepository $vehicleRepository)
    {
        $this->vehicleRepository = $vehicleRepository;
    }

    public function getAllVehicles(int $perPage = 15)
    {
        // TODO: return $this->vehicleRepository->paginate($perPage);
    }

    public function getVehicleById(int $id)
    {
        // TODO: return $this->vehicleRepository->findByIdOrFail($id);
    }

    public function getAvailableVehicles()
    {
        return $this->vehicleRepository->getAvailableVehicles();
    }

    public function createVehicle(array $data)
    {
        // TODO: return $this->vehicleRepository->create($data);
    }

    public function updateVehicle(int $id, array $data)
    {
        // TODO: $this->vehicleRepository->update($id, $data); return updated vehicle;
    }

    public function deleteVehicle(int $id): bool
    {
        // TODO: Check vehicle is not in active route first
        // return $this->vehicleRepository->delete($id);
    }

    /**
     * قفل المركبة من التوزيع (fn25 / MT-04)
     * @param int $vehicleId
     * @return bool
     */
    public function lockVehicle(int $vehicleId): bool
    {
        // TODO: Set vehicle status to 'out_of_service'
        // 1. $this->vehicleRepository->lockVehicle($vehicleId)
        // 2. Fire event: VehicleLocked → triggers sync across services
        // 3. Return true
    }

    /**
     * تحرير المركبة بعد الصيانة
     * @param int $vehicleId
     * @return bool
     */
    public function unlockVehicle(int $vehicleId): bool
    {
        // TODO: Set vehicle status to 'available'
        // 1. $this->vehicleRepository->unlockVehicle($vehicleId)
        // 2. Fire event: VehicleUnlocked
        // 3. Return true
    }

    public function getFleetVehicles(): array
    {
        try {
            $rows = \Illuminate\Support\Facades\DB::table('vehicles')->get();

            return $rows->map(function ($vehicle) {
                // Format last_service as Y-m-d if present, otherwise empty string
                $lastService = '';
                if (!empty($vehicle->UpdatedAt)) {
                    try {
                        $lastService = date('Y-m-d', strtotime((string) $vehicle->UpdatedAt));
                    } catch (\Throwable $dateErr) {
                        $lastService = (string) $vehicle->UpdatedAt;
                    }
                }

                return [
                    'id'           => (string) ($vehicle->vehicle_id ?? 0),
                    'plate'        => $vehicle->VehicleLicense ?? '',
                    'type'         => strtolower((string) ($vehicle->VehicleType ?? '')),
                    'max_weight'   => (float) ($vehicle->MaxWeightCapacity ?? 0),
                    'max_volume'   => (float) ($vehicle->MaxVolume ?? 0),
                    'odometer'     => (float) ($vehicle->Current_odometer ?? 0),
                    'status'       => $vehicle->Status ?? '',
                    'mechanic'     => '',
                    'market_value' => (int) ($vehicle->MarketValue ?? 0),
                    'last_service' => $lastService,
                ];
            })->toArray();
        } catch (\Exception $e) {
            // Log and re-throw so VehicleController returns a proper 500 JSON response
            \Illuminate\Support\Facades\Log::error(
                '[VehicleService::getFleetVehicles] DB query failed: ' . $e->getMessage()
            );
            throw $e;
        }
    }

    public function getMaintenanceVehicles(): array
    {
        try {
            $rows = \Illuminate\Support\Facades\DB::table('vehicles')->get();

            return $rows->map(function ($vehicle) {
                // Determine Next Service & Health State
                $odo = (float) ($vehicle->Current_odometer ?? 0);
                $nextDue = 'Oil Change';
                $state = 'healthy';
                
                if ($odo > 150000) {
                    $nextDue = 'Engine Overhaul';
                    $state = 'critical';
                } elseif ($odo > 100000) {
                    $nextDue = 'Transmission Check';
                    $state = 'warning';
                } elseif ($odo > 50000) {
                    $nextDue = 'Brake Inspection';
                }

                // Format Last Service Date
                $lastService = '';
                if (!empty($vehicle->UpdatedAt)) {
                    try {
                        $lastService = date('Y-m-d', strtotime((string) $vehicle->UpdatedAt));
                    } catch (\Throwable $dateErr) {
                        $lastService = (string) $vehicle->UpdatedAt;
                    }
                }
                
                // Insurance Expiry calculation (mocked as 1 year from CreatedAt)
                $createdAt = strtotime((string) ($vehicle->CreatedAt ?? 'now'));
                $insuranceExpiry = date('Y-m-d', strtotime('+1 year', $createdAt));

                return [
                    'id'               => (string) ($vehicle->vehicle_id ?? 0),
                    'plate'            => $vehicle->VehicleLicense ?? '',
                    'type'             => strtolower((string) ($vehicle->VehicleType ?? '')),
                    'status'           => $vehicle->Status ?? '',
                    'odometer'         => number_format($odo, 2) . ' km',
                    'market_value'     => number_format((int) ($vehicle->MarketValue ?? 0)),
                    'make_model'       => $vehicle->VehicleModel ?? 'Unknown Make/Model',
                    'lastService'      => $lastService,
                    'nextDue'          => $nextDue,
                    'state'            => $state,
                    'insurance_expiry' => $insuranceExpiry,
                ];
            })->toArray();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error(
                '[VehicleService::getMaintenanceVehicles] DB query failed: ' . $e->getMessage()
            );
            throw $e;
        }
    }


    public function getFleetDrivers(): array
    {
        try {
            // JOIN drivers with users to get the driver's full name
            $rows = \Illuminate\Support\Facades\DB::table('drivers as d')
                ->join('users as u', 'u.user_id', '=', 'd.driver_id')
                ->select(
                    'd.driver_id',
                    'u.name',
                    'd.license_no',
                    'd.license_type',
                    'd.status',
                    'd.score',
                    'd.vehicle_id'
                )
                ->get();

            return $rows->map(function ($driver) {
                // Derive initials from name (e.g. "Ahmed Sayed" → "AS")
                $name     = $driver->name ?? '';
                $initials = '';
                if ($name) {
                    $initials = implode('', array_map(
                        fn($word) => strtoupper(mb_substr($word, 0, 1)),
                        array_filter(explode(' ', $name))
                    ));
                }

                return [
                    'driver_id'       => (string) ($driver->driver_id ?? 0),
                    'name'            => $name,
                    'initials'        => $initials,
                    'status'          => $driver->status ?? '',
                    'score'           => (int) ($driver->score ?? 0),
                    'shift'           => $driver->status ?? '',   // mirrors status until dedicated shift col exists
                    'license_type'    => $driver->license_type ?? '',
                    'license_no'      => $driver->license_no ?? '',
                    'stats'           => [
                        'deliveries'   => 0,
                        'success_rate' => 0,
                        'on_time_rate' => 0,
                        'avg_time'     => 0,
                    ],
                    'current_vehicle' => $driver->vehicle_id ? (string) $driver->vehicle_id : null,
                    'current_route'   => null,
                ];
            })->toArray();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error(
                '[VehicleService::getFleetDrivers] DB query failed: ' . $e->getMessage()
            );
            throw $e;
        }
    }
}

