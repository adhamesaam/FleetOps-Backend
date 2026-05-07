<?php

/**
 * @file: ProofOfDeliveryController.php
 * @description: متحكم إثبات التسليم (POD) - صورة وتوقيع (fn13/14)
 * @module: OrderManagement
 * @author: Team Leader (Khalid)
 */

namespace App\Modules\OrderManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\OrderManagement\Requests\ProofOfDeliveryRequest;
use App\Modules\OrderManagement\Services\ProofOfDeliveryService;
use Illuminate\Http\JsonResponse;

class ProofOfDeliveryController extends Controller
{
    protected ProofOfDeliveryService $podService;

    public function __construct(ProofOfDeliveryService $podService)
    {
        $this->podService = $podService;
    }

    /**
     * حفظ إثبات التسليم
     * POST /api/v1/orders/{orderId}/pod
     */
    public function store(int $orderId, ProofOfDeliveryRequest $request): JsonResponse
    {
        try {
            $pod = $this->podService->storePOD($orderId, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل إثبات التسليم بنجاح',
                'data' => $pod
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * عرض إثبات التسليم لطلب معين
     * GET /api/v1/orders/{orderId}/pod
     */
    public function show(int $orderId): JsonResponse
    {
        // TODO: Get POD for order
        // return POD record with URLs
        return response()->json(['message' => 'Not implemented yet'], 501);
    }
}
