<?php

/**
 * @file: RouteStopController.php
 * @description: متحكم محطات المسار - إدارة الترتيب والـ ETA والحالة
 * @module: RouteDispatch
 * @author: Team Leader (Khalid)
 */

namespace App\Modules\RouteDispatch\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RouteDispatch\Services\RouteOptimizationService;
use App\Modules\RouteDispatch\Repositories\RouteStopRepository;
use App\Modules\RouteDispatch\Requests\RouteStopRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RouteStopController extends Controller
{
    protected RouteStopRepository $stopRepository;
    protected RouteOptimizationService $optimizationService;

    public function __construct(
        RouteStopRepository $stopRepository,
        RouteOptimizationService $optimizationService
    ) {
        $this->stopRepository      = $stopRepository;
        $this->optimizationService = $optimizationService;
    }

    /**
     * جلب محطات مسار معين
     * GET /api/v1/dispatch/routes/{routeId}/stops
     */
    public function index(int $routeId): JsonResponse
    {
        try {
            $stops = $this->stopRepository->getForRoute($routeId);

            return response()->json([
                'success' => true,
                'message' => 'Route stops retrieved successfully.',
                'data' => $stops
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * إضافة محطة لمسار
     * POST /api/v1/dispatch/routes/{routeId}/stops
     */
    public function store(int $routeId, RouteStopRequest $request): JsonResponse
    {
        // TODO: Add new stop to route
        // 1. Validate route exists and is planned
        // 2. Add stop at end of sequence
        // 3. Recalculate ETAs
        // 4. Return new stop with updated ETAs
    }

    /**
     * تحديث حالة محطة (وصول / إنهاء / تخطي)
     * PATCH /api/v1/dispatch/stops/{stopId}/status
     */
    public function updateStatus(int $stopId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:arrived,completed,skipped'
        ]);

        try {
            $this->stopRepository->updateStatus($stopId, $validated['status']);

            // Fetch the updated stop to return
            $stop = \App\Modules\RouteDispatch\Models\RouteStop::findOrFail($stopId);

            return response()->json([
                'success' => true,
                'message' => 'Stop status updated successfully.',
                'data' => $stop
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * إعادة ترتيب محطات مسار يدوياً
     * PUT /api/v1/dispatch/routes/{routeId}/stops/reorder
     */
    public function reorder(int $routeId, Request $request): JsonResponse
    {
        // TODO: Manual reorder
        // 1. Validate: 'stops' => 'required|array', 'stops.*.stop_id' => 'integer', 'stops.*.sequence' => 'integer'
        // 2. $this->stopRepository->reorderStops($request->stops)
        // 3. Recalculate ETAs
        // 4. Return updated stops list
    }

    /**
     * حذف محطة من مسار
     * DELETE /api/v1/dispatch/stops/{stopId}
     */
    public function destroy(int $stopId): JsonResponse
    {
        // TODO: Remove stop from route
        // 1. Validate stop belongs to a 'planned' route
        // 2. Delete stop
        // 3. Resequence remaining stops
        // 4. Return success response
    }
}
