<?php

namespace App\Modules\OrderManagement\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Modules\OrderManagement\Services\CustomerPortalService;
use App\Modules\OrderManagement\Requests\UpdateDeliveryInstructionsRequest;
use App\Modules\OrderManagement\Requests\SubmitFeedbackRequest;

/**
 * CustomerPortalController
 *
 * All responses follow the standardised envelope:
 *   { success: bool, message: string, data: array, errors?: array }
 *
 * `data` is ALWAYS an array (never a plain object) to satisfy the Tech Lead
 * requirement that data is a List [].
 */
class CustomerPortalController extends Controller
{
    public function __construct(private readonly CustomerPortalService $portalService) {}

    // -------------------------------------------------------------------------
    // Token & Order
    // -------------------------------------------------------------------------

    public function validateToken(string $token): JsonResponse
    {
        $result = $this->portalService->validateTrackingToken($token);

        if (!($result['success'] ?? false)) {
            return $this->errorResponse($result);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'Token is valid.',
            'data'    => array_values((array) ($result['data'] ?? [])),
        ]);
    }

    public function getOrderDetails(string $token): JsonResponse
    {
        $result = $this->portalService->fetchOrderDetails($token);

        if (!($result['success'] ?? false)) {
            return $this->errorResponse($result);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'Order details retrieved successfully.',
            'data'    => [$result['data'] ?? []],   // wrap single object in list
        ]);
    }

    // -------------------------------------------------------------------------
    // Live Tracking
    // -------------------------------------------------------------------------

    public function getTrackingData(string $token): JsonResponse
    {
        $result = $this->portalService->fetchTrackingData($token);

        if (!($result['success'] ?? false)) {
            return $this->errorResponse($result);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'Tracking data retrieved successfully.',
            'data'    => [$result['data'] ?? []],
        ]);
    }

    // -------------------------------------------------------------------------
    // Delivery Instructions & Arrival
    // -------------------------------------------------------------------------

    public function updateDeliveryInstructions(UpdateDeliveryInstructionsRequest $request, string $token): JsonResponse
    {
        $result = $this->portalService->saveDeliveryInstructions($token, $request->validated());

        if (!($result['success'] ?? false)) {
            return $this->errorResponse($result);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'Delivery instructions updated successfully.',
            'data'    => [$result['data'] ?? []],
        ]);
    }

    public function getArrivalDetails(string $token): JsonResponse
    {
        $result = $this->portalService->fetchArrivalDetails($token);

        if (!($result['success'] ?? false)) {
            return $this->errorResponse($result);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'Arrival details retrieved successfully.',
            'data'    => [$result['data'] ?? []],
        ]);
    }

    public function confirmCustomerReady(string $token): JsonResponse
    {
        $result = $this->portalService->markCustomerAsReady($token);

        if (!($result['success'] ?? false)) {
            return $this->errorResponse($result);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'Driver has been notified that you are ready.',
            'data'    => [$result['data'] ?? []],
        ]);
    }

    // -------------------------------------------------------------------------
    // Delivery Proof & Feedback
    // -------------------------------------------------------------------------

    public function getDeliveryProof(string $token): JsonResponse
    {
        $result = $this->portalService->fetchDeliveryProof($token);

        if (!($result['success'] ?? false)) {
            return $this->errorResponse($result);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'Delivery proof retrieved successfully.',
            'data'    => [$result['data'] ?? []],
        ]);
    }

    public function submitFeedback(SubmitFeedbackRequest $request, string $token): JsonResponse
    {
        $result = $this->portalService->saveFeedback($token, $request->validated());

        if (!($result['success'] ?? false)) {
            return $this->errorResponse($result);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'Thank you for your feedback!',
            'data'    => [$result['data'] ?? []],
        ], 201);
    }

    // -------------------------------------------------------------------------
    // Unsuccessful Attempt
    // -------------------------------------------------------------------------

    public function getUnsuccessfulAttempt(string $token): JsonResponse
    {
        $result = $this->portalService->fetchUnsuccessfulAttemptDetails($token);

        if (!($result['success'] ?? false)) {
            return $this->errorResponse($result);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'Attempt details retrieved successfully.',
            'data'    => [$result['data'] ?? []],
        ]);
    }

    // -------------------------------------------------------------------------
    // Support
    // -------------------------------------------------------------------------

    public function getSupportInfo(): JsonResponse
    {
        $result = $this->portalService->fetchSupportInfo();

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'Support information retrieved successfully.',
            'data'    => [$result['data'] ?? []],
        ]);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Build a standardised error JSON response.
     *
     * Envelope: { success: false, message: string, data: [], errors: array }
     */
    private function errorResponse(array $result): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'An error occurred.',
            'data'    => [],
            'errors'  => $result['errors']  ?? [],
        ], $result['status_code'] ?? 422);
    }
}
