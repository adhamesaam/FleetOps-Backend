<?php

/**
 * @file: OrderRepository.php
 * @description: مستودع بيانات الطلبات - Order Management Service
 * @module: OrderManagement
 * @author: Team Leader (Khalid)
 */

namespace App\Modules\OrderManagement\Repositories;

use App\Modules\Shared\Repositories\BaseRepository;
use App\Modules\OrderManagement\Models\Order;
use Illuminate\Database\Eloquent\Collection;

class OrderRepository extends BaseRepository
{
    public function __construct(Order $model)
    {
        parent::__construct($model);
    }

    public function getForRoute(int $routeId): Collection
    {
        // TODO: return $this->model->forRoute($routeId)->with('proofOfDelivery')->get();
    }

    public function getForDriver(int $driverId): Collection
    {
        // TODO: return $this->model->forDriver($driverId)->orderBy('created_at', 'desc')->get();
    }

    public function findByQrCode(string $qrCode): ?Order
    {
        // TODO: return $this->model->where('qr_code', $qrCode)->first();
    }

    /**
     * تحديث حالة الطلب (State Machine - يمنع الانتقالات الغير صحيحة)
     * @param int $orderId
     * @param string $newStatus
     * @return bool
     */
    public function updateStatus(int $orderId, string $newStatus): bool
    {
        // TODO: Update order status (idempotent)
        // 1. Get current order
        // 2. Validate state transition is allowed (State Machine rules)
        //    pending → in_transit → delivered | returned | failed
        // 3. If transition is invalid → throw Exception
        // 4. Update status
        // 5. Return true
    }

    public function bulkInsert(array $orders): bool
    {
        // TODO: Bulk insert orders from CSV import
        // return $this->model->insert($orders);
    }

    public function getPendingForReattempt(): Collection
    {
        // TODO: Get failed/returned orders eligible for retry
        // return $this->model->where('status', 'returned')->where('retry_count', '<', 3)->get();
    }

    public function findByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)
            ->with('customer:customer_id,address', 'customer.user:user_id,name')
            ->get();
    }
}
