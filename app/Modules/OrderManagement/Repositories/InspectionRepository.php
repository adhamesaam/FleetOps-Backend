<?php

/**
 * @file: InspectionRepository.php
 * @description: مستودع بيانات فحوصات ما قبل الرحلة - Order Management Service (fn12)
 * @module: OrderManagement
 * @author: Team Leader (Khalid)
 */

namespace App\Modules\OrderManagement\Repositories;

use App\Modules\Shared\Repositories\BaseRepository;
use App\Modules\OrderManagement\Models\PreTripInspection;

class InspectionRepository extends BaseRepository
{
    public function __construct(PreTripInspection $model)
    {
        parent::__construct($model);
    }

    /**
     * جلب آخر فحص لمركبة معينة
     * @param int $vehicleId
     * @return PreTripInspection|null
     */
    public function getLatestForVehicle(int $vehicleId): ?PreTripInspection
    {
        return $this->model->where('vehicle_id', $vehicleId)->latest('inspection_ts')->first();
    }

    /**
     * جلب فحوصات مسار معين
     * @param int $routeId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getForRoute(int $routeId)
    {
        // Note: pre_trip_inspections does not have a route_id column by default, 
        // you might need to join with another table or adjust this if the schema changed.
        return $this->model->where('route_id', $routeId)->orderBy('inspection_ts', 'desc')->get();
    }

    /**
     * جلب فحوصات مركبة معينة (مع Pagination)
     * @param int $vehicleId
     * @param int $perPage
     */
    public function getForVehiclePaginated(int $vehicleId, int $perPage = 15)
    {
        return $this->model->where('vehicle_id', $vehicleId)->latest('inspection_ts')->paginate($perPage);
    }
}
