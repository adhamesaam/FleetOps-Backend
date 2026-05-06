<?php

namespace App\Modules\Maintenance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Maintenance\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Dashboard of Maintenance
     * GET api/v1/maintenance/dashboard-summary
     */
    public function getDashboardSummary(): JsonResponse
    {
        try {
            $data = $this->dashboardService->getSummary();

            return response()->json([
                'success' => true,
                'message' => "تم جلب البيانات بنجاح",
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "فشل جلب البيانات",
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
