<?php

/**
 * @file: InspectionController.php
 * @description: متحكم فحص ما قبل الرحلة - Order Management Service (fn12)
 * @module: OrderManagement
 * @author: Team Leader (Khalid)
 */

namespace App\Modules\OrderManagement\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InspectionController extends Controller
{
    /**
     * تسجيل فحص ما قبل الرحلة
     * POST /api/v1/orders/inspections
     */
    public function store(Request $request): JsonResponse
    {
        // 1. Validate: driver_id, vehicle_id, route_id, tires_ok, brakes_ok, lights_ok, fuel_level, documents_ok, engine_ok
        $validated = $request->validate([
            'driver_id'    => 'required|integer',
            'vehicle_id'   => 'required|integer',
            'route_id'     => 'required|integer',
            'tires_ok'     => 'required|boolean',
            'brakes_ok'    => 'required|boolean',
            'lights_ok'    => 'required|boolean',
            'fuel_level'   => 'required|integer|min:0|max:100',
            'documents_ok' => 'required|boolean',
            'engine_ok'    => 'required|boolean',
            'odometer_reading' => 'sometimes|numeric|min:0',
            'fluids_ok'    => 'sometimes|boolean',
        ]);

        // 2. Calculate 'passed' = all boolean checks are true
        $passed = $validated['tires_ok'] && 
                  $validated['brakes_ok'] && 
                  $validated['lights_ok'] && 
                  $validated['documents_ok'] && 
                  $validated['engine_ok'];

        // 3. If !passed → driver cannot start route (return warning)
        if (!$passed) {
            return response()->json([
                'success' => false,
                'message' => 'Pre-trip inspection failed. Driver cannot start route.',
                'route_can_start' => false
            ], 400);
        }

        // 4. Create inspection record
        $inspection = \App\Modules\OrderManagement\Models\PreTripInspection::create([
            'driver_id'        => $validated['driver_id'],
            'vehicle_id'       => $validated['vehicle_id'],
            'odometer_reading' => $validated['odometer_reading'] ?? 0,
            'fuel_level'       => $validated['fuel_level'],
            'tires_ok'         => $validated['tires_ok'],
            'brakes_ok'        => $validated['brakes_ok'],
            'lights_ok'        => $validated['lights_ok'],
            'fluids_ok'        => $validated['fluids_ok'] ?? true,
        ]);

        // 5. Return inspection with 'route_can_start' flag
        return response()->json([
            'success' => true,
            'message' => 'Pre-trip inspection recorded successfully.',
            'route_can_start' => true,
            'data'    => $inspection
        ], 201);
    }

    /**
     * جلب فحوصات مركبة معينة
     * GET /api/v1/orders/inspections/vehicle/{vehicleId}
     */
    public function forVehicle(int $vehicleId): JsonResponse
    {
        // TODO: return pre-trip inspections for vehicle (paginated)
    }

    /**
     * جلب أحدث فحص قبل الرحلة لمسار معين
     * GET /api/v1/orders/inspections/route/{routeId}
     */
    public function forRoute(int $routeId): JsonResponse
    {
        // TODO: return latest inspection for given route
    }
}
