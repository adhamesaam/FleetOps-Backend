<?php

/**
 * @file: WorkOrderController.php
 * @description: متحكم أوامر العمل - Maintenance Service (MT-02/03/04/05/06/07)
 * @module: Maintenance
 * @author: Team Leader (Khalid)
 */

namespace App\Modules\Maintenance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Maintenance\Services\WorkOrderService;
use App\Modules\Maintenance\Requests\WorkOrderRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Throwable;

class WorkOrderController extends Controller
{
    protected WorkOrderService $workOrderService;

    public function __construct(WorkOrderService $workOrderService)
    {
        $this->workOrderService = $workOrderService;
    }

    /** GET /api/v1/maintenance/work-orders */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('per_page', 15);
            $orders  = $this->workOrderService->getAllWorkOrders($perPage);

            return response()->json([
                'success' => true,
                'data'    => $orders,
            ]);
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }

    /** GET /api/v1/maintenance/work-orders/{id} */
    public function show(int $id): JsonResponse
    {
        try {
            $order = $this->workOrderService->getWorkOrderById($id);

            return response()->json([
                'success' => true,
                'data'    => $order->load('vehicle'),
            ]);
        } catch (ModelNotFoundException) {
            return $this->notFound('أمر العمل غير موجود.');
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }

    /** POST /api/v1/maintenance/work-orders */
    public function store(WorkOrderRequest $request): JsonResponse
    {
        try {
            $data               = $request->validated();
            $data['created_by'] = Auth::id();

            $order = $this->workOrderService->createWorkOrder($data);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء أمر العمل بنجاح.',
                'data'    => $order,
            ], 201);
        } catch (ModelNotFoundException) {
            return $this->notFound('المركبة غير موجودة.');
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }

    /** PUT /api/v1/maintenance/work-orders/{id} */
    public function update(int $id, WorkOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->workOrderService->getWorkOrderById($id);

            if (!in_array($order->status, ['open', 'assigned'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تعديل أمر العمل في حالته الحالية.',
                ], 422);
            }

            $order->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث أمر العمل بنجاح.',
                'data'    => $order->fresh(),
            ]);
        } catch (ModelNotFoundException) {
            return $this->notFound('أمر العمل غير موجود.');
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }

    /** DELETE /api/v1/maintenance/work-orders/{id} */
    public function destroy(int $id): JsonResponse
    {
        try {
            $order = $this->workOrderService->getWorkOrderById($id);

            if ($order->status !== 'open') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف أمر العمل إلا إذا كان في حالة مفتوحة.',
                ], 422);
            }

            $order->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم إلغاء أمر العمل بنجاح.',
            ]);
        } catch (ModelNotFoundException) {
            return $this->notFound('أمر العمل غير موجود.');
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }

    /**
     * تعيين ميكانيكي (MT-03 / fn30)
     * POST /api/v1/maintenance/work-orders/{id}/assign
     */
    public function assignMechanic(int $id, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'mechanic_id' => 'required|integer|exists:users,user_id',
            ]);

            $order = $this->workOrderService->assignMechanic($id, (int) $request->mechanic_id);

            return response()->json([
                'success' => true,
                'message' => 'تم تعيين الميكانيكي بنجاح.',
                'data'    => $order,
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (ModelNotFoundException) {
            return $this->notFound('أمر العمل أو الميكانيكي غير موجود.');
        } catch (\Exception $e) {
            // Business rule violations (mechanic busy, wrong role, etc.)
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }

    /**
     * تحديث حالة أمر العمل (MT-02)
     * PATCH /api/v1/maintenance/work-orders/{id}/status
     */
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'status'      => 'required|in:in_progress,resolved,closed',
                'repair_cost' => 'required_if:status,resolved|numeric|min:0',
                'notes'       => 'nullable|string|max:1000',
            ]);

            $order = $this->workOrderService->updateStatus($id, $request->status, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث حالة أمر العمل بنجاح.',
                'data'    => $order,
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (ModelNotFoundException) {
            return $this->notFound('أمر العمل غير موجود.');
        } catch (\Exception $e) {
            // Invalid status transition
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }

    /**
     * تسجيل قطع الغيار المستخدمة (fn26/31)
     * POST /api/v1/maintenance/work-orders/{id}/parts
     */
    public function recordParts(int $id, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'parts'             => 'required|array|min:1',
                'parts.*.part_id'   => 'required|integer|exists:spare_parts,part_id',
                'parts.*.quantity'  => 'required|integer|min:1',
            ]);

            $this->workOrderService->recordPartsUsed($id, $request->parts);

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل قطع الغيار المستخدمة بنجاح.',
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (ModelNotFoundException) {
            return $this->notFound('أمر العمل أو قطعة الغيار غير موجودة.');
        } catch (\Exception $e) {
            // Insufficient stock or other business rule
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }

    /**
     * الطلبات المفتوحة فقط
     * GET /api/v1/maintenance/work-orders/open
     */
    public function openOrders(): JsonResponse
    {
        try {
            $orders = $this->workOrderService->getOpenWorkOrders();

            return response()->json([
                'success' => true,
                'data'    => $orders,
            ]);
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }

    /**
     * أوامر عمل مركبة معينة
     * GET /api/v1/maintenance/work-orders/vehicle/{vehicleId}
     */
    public function forVehicle(int $vehicleId): JsonResponse
    {
        try {
            $orders = $this->workOrderService->getWorkOrdersForVehicle($vehicleId);

            return response()->json([
                'success' => true,
                'data'    => $orders,
            ]);
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }

    /**
     * أوامر عمل ميكانيكي معين
     * GET /api/v1/maintenance/work-orders/mechanic/{mechanicId}
     */
    public function forMechanic(int $mechanicId): JsonResponse
    {
        try {
            $orders = $this->workOrderService->getWorkOrdersForMechanic($mechanicId);

            return response()->json([
                'success' => true,
                'data'    => $orders,
            ]);
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function notFound(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 404);
    }

    private function validationError(ValidationException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'بيانات غير صالحة.',
            'errors'  => $e->errors(),
        ], 422);
    }

    private function serverError(Throwable $e): JsonResponse
    {
        logger()->error('[WorkOrderController] ' . $e->getMessage(), [
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ داخلي في الخادم. يرجى المحاولة لاحقاً.',
        ], 500);
    }
}
