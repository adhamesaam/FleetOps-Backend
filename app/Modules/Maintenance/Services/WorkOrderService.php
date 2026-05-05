<?php

/**
 * @file: WorkOrderService.php
 * @description: خدمة دورة حياة أوامر العمل - Maintenance Service (MT-02/03/04/06)
 * @module: Maintenance
 * @author: Team Leader (Khalid)
 */

namespace App\Modules\Maintenance\Services;

use App\Modules\Maintenance\Repositories\WorkOrderRepository;
use App\Modules\Maintenance\Repositories\SparePartRepository;
use App\Modules\AuthIdentity\Models\User;
use App\Modules\RouteDispatch\Models\Vehicle;
use App\Modules\LoggingAudit\Services\AuditService;
use Exception;
use Illuminate\Support\Facades\DB;

class WorkOrderService
{
    protected WorkOrderRepository $workOrderRepository;
    protected SparePartRepository $sparePartRepository;
    protected AuditService $auditService;

    public function __construct(
        WorkOrderRepository $workOrderRepository,
        SparePartRepository $sparePartRepository,
        AuditService $auditService
    ) {
        $this->workOrderRepository = $workOrderRepository;
        $this->sparePartRepository = $sparePartRepository;
        $this->auditService        = $auditService;
    }

    public function getAllWorkOrders(int $perPage = 15)
    {
        return $this->workOrderRepository->paginate($perPage);
    }

    public function getWorkOrderById(int $id)
    {
        return $this->workOrderRepository->findByIdOrFail($id);
    }

    public function createWorkOrder(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1. Set opened_at = now()
            $data['opened_at'] = now();
            $data['status']    = 'open';

            // 2. Lock vehicle: update vehicle status to 'Maintenance' (MT-04)
            $vehicle = Vehicle::findOrFail($data['vehicle_id']);
            $vehicle->update(['Status' => 'Maintenance']);

            // 3. Create work order record
            $workOrder = $this->workOrderRepository->create($data);

            // 4. Log to audit trail
            $this->auditService->log(
                action:     'created',
                entityType: 'work_order',
                entityId:   $workOrder->work_order_id,
                afterState: $workOrder->toArray(),
                module:     'Maintenance'
            );

            return $workOrder;
        });
    }

    /**
     * تعيين ميكانيكي لأمر العمل (MT-03 / fn30)
     */
    public function assignMechanic(int $workOrderId, int $mechanicId)
    {
        return DB::transaction(function () use ($workOrderId, $mechanicId) {
            // 1. Validate mechanic exists and has 'mechanic' role
            $mechanic = User::where('user_id', $mechanicId)
                ->where('role', 'mechanic')
                ->where('is_active', true)
                ->first();

            if (!$mechanic) {
                throw new Exception('الميكانيكي غير موجود أو غير نشط أو لا يملك الدور الصحيح.');
            }

            // 2. Check mechanic availability (no other in_progress work orders)
            $busy = $this->workOrderRepository
                ->search(['mechanic_id' => $mechanicId, 'status' => 'in_progress'])
                ->exists();

            if ($busy) {
                throw new Exception('الميكانيكي مشغول حالياً بأمر عمل آخر قيد التنفيذ.');
            }

            // 3. Update status to 'assigned', set mechanic_id, set assigned_at = now()
            $workOrder = $this->workOrderRepository->findByIdOrFail($workOrderId);
            $before    = $workOrder->toArray();

            $this->workOrderRepository->update($workOrderId, [
                'mechanic_id' => $mechanicId,
                'status'      => 'assigned',
                'assigned_at' => now(),
            ]);

            $workOrder->refresh();

            // 4. Log to audit trail
            $this->auditService->log(
                action:      'updated',
                entityType:  'work_order',
                entityId:    $workOrderId,
                beforeState: $before,
                afterState:  $workOrder->toArray(),
                module:      'Maintenance'
            );

            return $workOrder;
        });
    }

    /**
     * تحديث حالة أمر العمل (MT-02)
     * @param string $newStatus  (in_progress | resolved | closed)
     * @param array  $data       (repair_cost, parts_used[], notes)
     */
    public function updateStatus(int $workOrderId, string $newStatus, array $data = [])
    {
        return DB::transaction(function () use ($workOrderId, $newStatus, $data) {
            $workOrder = $this->workOrderRepository->findByIdOrFail($workOrderId);
            $before    = $workOrder->toArray();

            // 1. Validate status transition
            $allowedTransitions = [
                'open'        => ['assigned', 'in_progress'],
                'assigned'    => ['in_progress'],
                'in_progress' => ['resolved'],
                'resolved'    => ['closed'],
            ];

            $currentStatus = $workOrder->status;
            if (!in_array($newStatus, $allowedTransitions[$currentStatus] ?? [])) {
                throw new Exception("الانتقال من '{$currentStatus}' إلى '{$newStatus}' غير مسموح.");
            }

            $updateData = [];

            // 2. If resolved: calculate cost-to-value ratio (MT-08 / fn29)
            if ($newStatus === 'resolved') {
                $repairCost = $data['repair_cost'] ?? $workOrder->repair_cost ?? 0;
                $updateData['repair_cost'] = $repairCost;

                $analysis = $this->analyzeCostToValue($workOrder->vehicle_id, (float) $repairCost);
                if ($analysis['recommend_replacement']) {
                    logger()->warning(
                        "[WorkOrderService] تحليل التكلفة/القيمة: نسبة {$analysis['ratio']} تتجاوز 40% — يُنصح باستبدال المركبة (vehicle_id: {$workOrder->vehicle_id})"
                    );
                }
            }

            // 3. If closed: set vehicle status back to 'Active'
            if ($newStatus === 'closed') {
                Vehicle::where('vehicle_id', $workOrder->vehicle_id)
                    ->update(['Status' => 'Active']);
            }

            if (isset($data['notes'])) {
                $updateData['notes'] = $data['notes'];
            }

            // 4. Update status with appropriate timestamp
            $this->workOrderRepository->updateStatus($workOrderId, $newStatus, $updateData);

            $workOrder->refresh();

            // 5. Log to audit trail
            $this->auditService->log(
                action:      'status_changed',
                entityType:  'work_order',
                entityId:    $workOrderId,
                beforeState: $before,
                afterState:  $workOrder->toArray(),
                module:      'Maintenance'
            );

            return $workOrder;
        });
    }

    /**
     * تسجيل قطع الغيار المستخدمة في الإصلاح (MT-05/06 / fn26/31)
     * @param array $parts  [['part_id' => 1, 'quantity' => 2], ...]
     */
    public function recordPartsUsed(int $workOrderId, array $parts): bool
    {
        return DB::transaction(function () use ($workOrderId, $parts) {
            $workOrder = $this->workOrderRepository->findByIdOrFail($workOrderId);

            // 1. For each part: deduct from inventory via sparePartRepository->deductStock()
            foreach ($parts as $part) {
                $this->sparePartRepository->deductStock((int) $part['part_id'], (int) $part['quantity']);
            }

            // 2. Update work_order's parts_used JSON array (merge with existing)
            $existing  = $workOrder->parts_used ?? [];
            $merged    = array_merge($existing, $parts);

            $this->workOrderRepository->update($workOrderId, ['parts_used' => $merged]);

            return true;
        });
    }

    public function getWorkOrdersForVehicle(int $vehicleId)
    {
        return $this->workOrderRepository->getForVehicle($vehicleId);
    }

    public function getWorkOrdersForMechanic(int $mechanicId)
    {
        return $this->workOrderRepository->getForMechanic($mechanicId);
    }

    public function getOpenWorkOrders()
    {
        return $this->workOrderRepository->getOpenWorkOrders();
    }

    /**
     * تحليل تكلفة/قيمة المركبة (MT-08 / fn29)
     * repair_cost / market_value > 0.40 → Recommend Replacement
     * @return array ['ratio' => float, 'recommend_replacement' => bool]
     */
    public function analyzeCostToValue(int $vehicleId, float $repairCost): array
    {
        // 1. Get vehicle market_value
        $vehicle = Vehicle::findOrFail($vehicleId);
        $marketValue = (float) $vehicle->MarketValue;

        if ($marketValue <= 0) {
            return ['ratio' => 0.0, 'recommend_replacement' => false];
        }

        // 2. ratio = repair_cost / market_value
        $ratio = $repairCost / $marketValue;

        // 3. recommend_replacement = ratio > 0.40
        $recommendReplacement = $ratio > 0.40;

        // 4. Return result
        return [
            'ratio'                => round($ratio, 4),
            'recommend_replacement' => $recommendReplacement,
        ];
    }
}
