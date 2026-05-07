<?php

/**
 * @file: RouteStopRepository.php
 * @description: مستودع بيانات محطات المسار - Route & Dispatch Service
 * @module: RouteDispatch
 * @author: Team Leader (Khalid)
 */

namespace App\Modules\RouteDispatch\Repositories;

use App\Modules\Shared\Repositories\BaseRepository;
use App\Modules\RouteDispatch\Models\RouteStop;
use Illuminate\Database\Eloquent\Collection;

class RouteStopRepository extends BaseRepository
{
    public function __construct(RouteStop $model)
    {
        parent::__construct($model);
    }

    /**
     * جلب محطات مسار مرتبة بالتسلسل
     * @param int $routeId
     * @return Collection
     */
    public function getForRoute(int $routeId): Collection
    {
        return $this->model->where('route_id', $routeId)->orderBy('stop_no')->get();
    }

    /**
     * تحديث ترتيب المحطات بعد التحسين (TSP)
     * @param array $stopsData  [['stop_id' => int, 'sequence' => int], ...]
     * @return bool
     */
    public function reorderStops(array $stopsData): bool
    {
        // Bulk update stop_no (sequence)
        foreach ($stopsData as $stop) {
            $this->model->where('stop_id', $stop['stop_id'])->update(['stop_no' => $stop['sequence']]);
        }
        return true;
    }

    /**
     * تحديث ETA لمحطة
     * @param int $stopId
     * @param \DateTime $eta
     * @return bool
     */
    public function updateEta(int $stopId, \DateTime $eta): bool
    {
        return (bool) $this->model->where('stop_id', $stopId)->update(['eta' => $eta]);
    }

    /**
     * تحديث حالة المحطة عند الوصول
     * @param int $stopId
     * @param string $status  (arrived | completed | skipped)
     * @return bool
     */
    public function updateStatus(int $stopId, string $status): bool
    {
        $data = [];

        // Based on the RouteStop model, there are no 'status' or 'departure_at' columns.
        // We can only update the 'actual_arrival_time' when the status is 'arrived'.
        if ($status === 'arrived') {
            $data['actual_arrival_time'] = now();
        }

        if (!empty($data)) {
            return (bool) $this->model->where('stop_id', $stopId)->update($data);
        }

        return true; // Return true if no relevant columns needed updating
    }

    /**
     * إضافة محطة في موضع معين (Express Insertion - fn07)
     * @param array $stopData
     * @param int   $afterSequence  رقم التسلسل قبل موضع الإدراج
     * @return RouteStop
     */
    public function insertAtPosition(array $stopData, int $afterSequence): RouteStop
    {
        // TODO: Insert stop at position
        // 1. Shift all stops with sequence > $afterSequence up by 1
        //    $this->model->where('route_id', $stopData['route_id'])
        //                ->where('sequence', '>', $afterSequence)
        //                ->increment('sequence');
        // 2. Set new stop sequence = $afterSequence + 1
        // 3. Create and return new stop
    }
}
