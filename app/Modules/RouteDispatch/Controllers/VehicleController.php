<?php

/**
 * @file: VehicleController.php
 * @description: متحكم المركبات - CRUD وإدارة الحالة والإتاحة
 * @module: RouteDispatch
 * @author: Team Leader (Khalid)
 */

namespace App\Modules\RouteDispatch\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RouteDispatch\Services\VehicleService;
use App\Modules\RouteDispatch\Requests\VehicleRequest;
use Exception;
use Illuminate\Http\JsonResponse;

class VehicleController extends Controller
{
    protected VehicleService $vehicleService;

    public function __construct(VehicleService $vehicleService)
    {
        $this->vehicleService = $vehicleService;
    }

    /** GET /api/v1/dispatch/vehicles */
    public function index(): JsonResponse
    {
        // TODO: return paginated vehicles list
    }

    /** GET /api/v1/dispatch/vehicles/{id} */
    public function show(int $id): JsonResponse
    {
        // TODO: return single vehicle with full details
    }

    /** POST /api/v1/dispatch/vehicles */
    public function store(VehicleRequest $request): JsonResponse
    {
        // TODO: Create vehicle → 201
    }

    /** PUT /api/v1/dispatch/vehicles/{id} */
    public function update(int $id, VehicleRequest $request): JsonResponse
    {
        // TODO: Update vehicle
    }

    /** DELETE /api/v1/dispatch/vehicles/{id} */
    public function destroy(int $id): JsonResponse
    {
        // TODO: Soft delete vehicle (check no active routes)
    }

    /**
     * جلب المركبات المتاحة للتوزيع
     * GET /api/v1/dispatch/vehicles/available
     */
    public function available(): JsonResponse
    {
        try {
            $vehicles = $this->vehicleService->getAvailableVehicles();

            return response()->json([
                'success' => true,
                'data' => $vehicles,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getTrace(),
            ], 500);
        }
    }

    /**
     * قفل مركبة من التوزيع (fn25 / MT-04)
     * POST /api/v1/dispatch/vehicles/{id}/lock
     */
    public function lock(int $id): JsonResponse
    {
        // TODO: $this->vehicleService->lockVehicle($id)
        // return success response
    }

    /**
     * تحرير مركبة بعد الصيانة
     * POST /api/v1/dispatch/vehicles/{id}/unlock
     */
    public function unlock(int $id): JsonResponse
    {
        // TODO: $this->vehicleService->unlockVehicle($id)
        // return success response
    }
}
