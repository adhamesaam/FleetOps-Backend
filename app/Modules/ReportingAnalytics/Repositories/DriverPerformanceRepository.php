<?php

/**
 * @file: DriverPerformanceRepository.php
 * @description: مستودع بيانات نقاط أداء السائقين - Reporting & Analytics Service (AN-02)
 * @module: ReportingAnalytics
 * @author: Team Leader (Khalid)
 */

namespace App\Modules\ReportingAnalytics\Repositories;

use App\Modules\Shared\Repositories\BaseRepository;
use App\Modules\ReportingAnalytics\Models\DriverPerformanceScore;

class DriverPerformanceRepository extends BaseRepository
{
    public function __construct(DriverPerformanceScore $model)
    {
        parent::__construct($model);
    }

    /**
     * جلب تاريخ أداء سائق معين
     * @param int $driverId
     * @param int $perPage
     */
    public function getForDriver(int $driverId, int $perPage = 12)
    {
        return $this->model->where('driver_id', $driverId)->latest('period_start')->paginate($perPage);
    }

    /**
     * تصنيف السائقين بناءً على النقاط (Leaderboard - AN-05)
     * @param string $periodStart
     * @param string $periodEnd
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLeaderboard(string $periodStart, string $periodEnd)
    {
        return $this->model
            ->whereBetween('period_start', [$periodStart, $periodEnd])
            ->orderByDesc('composite_score')
            ->with('driver')
            ->get();
    }

    /**
     * حفظ أو تحديث نقاط السائق للفترة
     * @param int $driverId
     * @param string $periodStart
     * @param string $periodEnd
     * @param array $scoreData
     * @return DriverPerformanceScore
     */
    public function upsertScore(int $driverId, string $periodStart, string $periodEnd, array $scoreData): DriverPerformanceScore
    {
        // TODO: Upsert driver score
        return $this->model->updateOrCreate(
            ['driver_id' => $driverId, 'period_start' => $periodStart, 'period_end' => $periodEnd],
            $scoreData
        );
    }
}
