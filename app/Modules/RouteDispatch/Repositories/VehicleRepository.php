<?php

/**
 * @file: VehicleRepository.php
 * @description: مستودع بيانات المركبات - Route & Dispatch Service
 * @module: RouteDispatch
 * @author: Team Leader (Khalid)
 */

namespace App\Modules\RouteDispatch\Repositories;

use App\Modules\Shared\Repositories\BaseRepository;
use App\Modules\RouteDispatch\Models\Vehicle;
use Illuminate\Database\Eloquent\Collection;

class VehicleRepository extends BaseRepository
{
    public function __construct(Vehicle $model)
    {
        parent::__construct($model);
    }

    public function getAvailableVehicles(): Collection
    {
        return $this->model->available()->get();
    }

    public function getByType(string $type): Collection
    {
        // TODO: return $this->model->available()->byType($type)->get();
    }

    public function updateStatus(int $vehicleId, string $status): bool
    {
        // TODO: return $this->update($vehicleId, ['status' => $status]);
    }

    public function lockVehicle(int $vehicleId): bool
    {
        // TODO: Set status to 'out_of_service' (MT-04 / fn25)
        // return $this->updateStatus($vehicleId, 'out_of_service');
    }

    public function unlockVehicle(int $vehicleId): bool
    {
        // TODO: Set status to 'available'
        // return $this->updateStatus($vehicleId, 'available');
    }
}
