<?php

/**
 * @file: FuelAuditLogRepository.php
 * @description: مستودع بيانات سجلات تدقيق الوقود - Reporting & Analytics Service
 * @module: ReportingAnalytics
 * @author: Team Leader (Khalid)
 */

namespace App\Modules\ReportingAnalytics\Repositories;

use App\Modules\Shared\Repositories\BaseRepository;
use App\Modules\ReportingAnalytics\Models\FuelAuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

class FuelAuditLogRepository extends BaseRepository
{
    public function __construct(FuelAuditLog $model)
    {
        parent::__construct($model);
    }

    /**
     * Get all fuel logs for a given period, eager-loading vehicle info.
     */
    public function getForPeriod(string $periodStart, string $periodEnd): Collection
    {
        return $this->model
            ->with('vehicle:vehicle_id,VehicleModel,VehicleType,VehicleLicense')
            ->whereBetween('log_ts', [$periodStart . ' 00:00:00', $periodEnd . ' 23:59:59'])
            ->orderBy('log_ts', 'desc')
            ->get();
    }

    /**
     * Get all fuel logs for a specific vehicle in a period.
     */
    public function getForVehicleInPeriod(int $vehicleId, string $periodStart, string $periodEnd): Collection
    {
        return $this->model
            ->where('vehicle_id', $vehicleId)
            ->whereBetween('log_ts', [$periodStart . ' 00:00:00', $periodEnd . ' 23:59:59'])
            ->orderBy('log_ts', 'asc')
            ->get();
    }

    /**
     * Aggregate total fuel consumed per vehicle in a period.
     * Returns: vehicle_id, total_fuel_litres, total_cost, fill_count, min_odometer, max_odometer
     */
    public function aggregateByVehicle(string $periodStart, string $periodEnd): Collection
    {
        return $this->model
            ->select(
                'vehicle_id',
                DB::raw('SUM(fuel_quantity)    AS total_fuel_litres'),
                DB::raw('SUM(total_cost)       AS total_cost'),
                DB::raw('COUNT(*)              AS fill_count'),
                DB::raw('MIN(odometer_reading) AS min_odometer'),
                DB::raw('MAX(odometer_reading) AS max_odometer')
            )
            ->whereBetween('log_ts', [$periodStart . ' 00:00:00', $periodEnd . ' 23:59:59'])
            ->groupBy('vehicle_id')
            ->get();
    }
}
