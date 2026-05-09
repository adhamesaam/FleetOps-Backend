<?php

/**
 * @file: OrderController.php
 * @description: متحكم الطلبات - CRUD والاستيراد وتحديث الحالة
 * @module: OrderManagement
 * @author: Team Leader (Khalid)
 */

namespace App\Modules\OrderManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\OrderManagement\Services\OrderService;
use App\Modules\OrderManagement\Services\OrderImportService;
use App\Modules\OrderManagement\Requests\OrderRequest;
use App\Modules\OrderManagement\Requests\BulkImportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected OrderService $orderService;
    protected OrderImportService $importService;

    public function __construct(OrderService $orderService, OrderImportService $importService)
    {
        $this->orderService  = $orderService;
        $this->importService = $importService;
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 15);
        return response()->json([
            'success' => true, 
            'data' => $this->orderService->getAllOrders((int) $perPage)
        ]);
    }

    public function show(int $id): JsonResponse
    {
        try {
            $order = $this->orderService->getOrderById($id);
            return response()->json(['success' => true, 'data' => $order]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }
    }

    public function store(OrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->createOrder($request->validated());
            return response()->json(['success' => true, 'data' => $order], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /** PUT /api/v1/orders/{id} */
    public function update(int $id, OrderRequest $request): JsonResponse
    {
        // TODO: Update order (only if pending)
    }

    /** DELETE /api/v1/orders/{id} */
    public function destroy(int $id): JsonResponse
    {
        // TODO: Soft delete order (only if pending)
    }

    /** GET /api/v1/orders/{status} */
    public function getByStatus(string $status): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->orderService->getOrdersByStatus($status)]);
    }


    /**
     * تحديث حالة الطلب (State Machine)
     * PATCH /api/v1/orders/{id}/status
     */
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        // TODO: Validate status field
        // $request->validate(['status' => 'required|in:in_transit,delivered,returned,failed', 'failure_reason' => 'required_if:status,failed|string'])
        // $order = $this->orderService->updateOrderStatus($id, $request->status, $request->all())
        // return success response
    }

    /**
     * التحقق من QR Code (fn17)
     * POST /api/v1/orders/{id}/verify-qr
     */
    public function verifyQr(int $id, Request $request): JsonResponse
    {
        // TODO: Validate qr_code field in request
        // $this->orderService->verifyQrCode($id, $request->qr_code)
        // return success response
    }

    /**
     * بدء سير عمل الإرجاع (fn21 - RTB)
     * POST /api/v1/orders/{id}/return
     */
    public function initiateReturn(int $id, Request $request): JsonResponse
    {
        // TODO: Validate failure_reason field
        // $order = $this->orderService->initiateReturn($id, $request->failure_reason)
        // return response with returned order
    }

    /**
     * استيراد طلبات جماعية من CSV/XML (fn39)
     * POST /api/v1/orders/import
     */
    public function bulkImport(BulkImportRequest $request): JsonResponse
    {
        try {
            $result = $this->importService->importOrders($request->file('file'), $request->format);
            return response()->json(['success' => true, 'data' => $result], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /** GET /api/v1/orders/route/{routeId} */
    public function routeOrders(int $routeId): JsonResponse
    {
        // TODO: return orders for specific route
    }

    /** GET /api/v1/orders/driver/{driverId} */
    public function driverOrders(int $driverId): JsonResponse
    {
        // TODO: return orders assigned to specific driver
    }
}
