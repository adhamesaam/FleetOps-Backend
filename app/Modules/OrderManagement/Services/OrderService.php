<?php

/**
 * @file: OrderService.php
 * @description: خدمة إدارة دورة حياة الطلبات (State Machine) - Order Management Service
 * @module: OrderManagement
 * @author: Team Leader (Khalid)
 */

namespace App\Modules\OrderManagement\Services;

use App\Modules\OrderManagement\Repositories\OrderRepository;
use Exception;

class OrderService
{
    protected OrderRepository $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function getAllOrders(int $perPage = 15)
    {
        // TODO: return $this->orderRepository->paginate($perPage);
    }

    public function getOrderById(int $id)
    {
        // TODO: return $this->orderRepository->findByIdOrFail($id);
    }


    /**
     * Get all orders filtered by status, with customer details eagerly loaded
     * @param string $status (e.g., 'Pending', 'InTransit', 'Delivered')
     * @return Collection Orders with customer names and details
     */
    public function getOrdersByStatus(string $status)
    { 
        return $this->orderRepository->findByStatus($status);
    }
    

    public function createOrder(array $data)
    {
        // TODO: Create order
        // 1. Generate QR code: Str::uuid() or barcode library
        // 2. Set status to 'pending'
        // 3. return $this->orderRepository->create($data)
    }

    public function updateOrder(int $id, array $data)
    {
        // TODO: Update order (only if pending status)
    }

    public function deleteOrder(int $id): bool
    {
        // TODO: Soft delete (only if pending)
    }

    /**
     * تحديث حالة الطلب (State Machine - OM-08)
     * @param int $orderId
     * @param string $newStatus  (in_transit | delivered | returned | failed)
     * @param array $extraData   (failure_reason, failure_reason_code, etc.)
     * @return mixed
     * @throws Exception
     */
    public function updateOrderStatus(int $orderId, string $newStatus, array $extraData = [])
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($orderId, $newStatus, $extraData) {
            return $this->orderRepository->updateStatus($orderId, $newStatus, $extraData);
        });
    }

    /**
     * التحقق من QR Code عند التسليم (fn17 / OM-04)
     * @param int $orderId
     * @param string $scannedQr
     * @return bool
     * @throws Exception
     */
    public function verifyQrCode(int $orderId, string $scannedQr): bool
    {
        $order = $this->orderRepository->findByIdOrFail($orderId);

        // Only allow QR verification for orders that are out for active delivery
        $deliverableStatuses = ['InTransit', 'Out for Delivery'];
        if (!in_array($order->Status, $deliverableStatuses)) {
            throw new Exception(
                "لا يمكن التحقق من QR Code للطلب بحالة: [{$order->Status}]. " .
                "الحالات المسموحة: " . implode(', ', $deliverableStatuses)
            );
        }

        // QR codes encode the OrderID (matches LiveTrackingLink pattern: /track/{OrderID})
        if ((string) $order->OrderID !== trim($scannedQr)) {
            throw new Exception("رمز QR غير صحيح. يرجى إعادة المسح.");
        }

        return true;
    }

    /**
     * تفعيل سير عمل الإرجاع (RTB - fn21 / OM-07)
     * @param int $orderId
     * @param string $failureReason
     * @return mixed
     */
    public function initiateReturn(int $orderId, string $failureReason)
    {
        // TODO: RTB Workflow
        // 1. Update order status to 'returned'
        // 2. Set failure_reason
        // 3. Increment retry_count
        // 4. Schedule retry if retry_count < 3
        // 5. Fire event: OrderReturned
        // 6. Return updated order
    }

    public function getRouteOrders(int $routeId)
    {
        // TODO: return $this->orderRepository->getForRoute($routeId);
    }

    public function getDriverOrders(int $driverId)
    {
        // TODO: return $this->orderRepository->getForDriver($driverId);
    }

    /**
     * Get cash orders for a specific driver
     * @param int $driverId
     * @return Collection
     */
    public function getCashOrdersForDriver(int $driverId)
    {
        return $this->orderRepository->cashOrders($driverId);
    }
}
