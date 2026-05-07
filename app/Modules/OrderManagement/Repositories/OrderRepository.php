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
    public function updateStatus(int $orderId, string $newStatus, array $extraData = []): Order
    {
        /** @var Order $order */
        $order = $this->findByIdOrFail($orderId);

        // ── State Machine: allowed transitions (matches DB Status values) ─────
        // DB statuses: Pending | Assigned | InTransit | Out for Delivery | Delivered | Returned | Failed
        $allowedTransitions = [
            'Pending'          => ['Assigned', 'InTransit'],
            'Assigned'         => ['InTransit', 'Out for Delivery'],
            'InTransit'        => ['Out for Delivery', 'Delivered', 'Returned', 'Failed'],
            'Out for Delivery' => ['Delivered', 'Returned', 'Failed'],
            'Delivered'        => [],   // terminal
            'Returned'         => [],   // terminal
            'Failed'           => [],   // terminal
        ];

        $currentStatus = $order->Status;

        // Normalize API-facing snake_case aliases → exact DB Status strings
        $statusMap = [
            'pending'          => 'Pending',
            'assigned'         => 'Assigned',
            'in_transit'       => 'InTransit',
            'intransit'        => 'InTransit',
            'out_for_delivery' => 'Out for Delivery',
            'delivered'        => 'Delivered',
            'returned'         => 'Returned',
            'failed'           => 'Failed',
        ];
        $normalizedNew = $statusMap[strtolower($newStatus)] ?? $newStatus;

        $allowed = $allowedTransitions[$currentStatus] ?? [];
        if (!in_array($normalizedNew, $allowed)) {
            throw new \Exception(
                "Transition not allowed: [{$currentStatus}] → [{$normalizedNew}]. Allowed: " .
                (empty($allowed) ? 'none (terminal state)' : implode(', ', $allowed))
            );
        }

        // ── Build the update payload ──────────────────────────────────────────
        $updateData = ['Status' => $normalizedNew];

        if ($normalizedNew === 'Delivered') {
            $updateData['DeliveredAt'] = now();
        }

        $this->update($orderId, $updateData);

        return $this->findByIdOrFail($orderId);
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


    public function findByIds(array $orderIds): Collection
    {
        return $this->model->whereIn('OrderID', $orderIds)->get();
    }

    public function findByStatus(string $status): Collection
    {
        return $this->model->where('Status', $status)
            ->with('customer:customer_id,address', 'customer.user:user_id,name')
            ->get();
    }

    public function cashOrders ($driverId){
        return $this->model
            ->where('DriverID(FK)', $driverId)
            ->where('Payment_method', 'cash')
            ->get();
    }
}
