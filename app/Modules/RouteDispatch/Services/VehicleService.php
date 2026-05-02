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
}
