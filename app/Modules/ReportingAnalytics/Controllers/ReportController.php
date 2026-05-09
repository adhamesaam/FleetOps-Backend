<?php

/**
 * @file: ReportController.php
 * @description: متحكم التقارير والتصدير - Reporting & Analytics Service (AN-06 / fn42)
 * @module: ReportingAnalytics
 * @author: Team Leader (Khalid)
 */

namespace App\Modules\ReportingAnalytics\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ReportingAnalytics\Services\ReportService;
use App\Modules\ReportingAnalytics\Services\KpiService;
use App\Modules\ReportingAnalytics\Repositories\DriverPerformanceRepository;
use App\Modules\ReportingAnalytics\Requests\KpiFilterRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected ReportService $reportService;
    protected KpiService $kpiService;
    protected DriverPerformanceRepository $performanceRepository;

    public function __construct(
        ReportService $reportService,
        KpiService $kpiService,
        DriverPerformanceRepository $performanceRepository
    ) {
        $this->reportService         = $reportService;
        $this->kpiService            = $kpiService;
        $this->performanceRepository = $performanceRepository;
    }

    /**
     * لوحة القيادة اليومية
     * GET /api/v1/analytics/reports/daily-dashboard
     */
    public function dailyDashboard(Request $request): JsonResponse
    {
        try {
            // Validate date format if provided
            $request->validate([
                'date' => 'nullable|date|date_format:Y-m-d',
            ]);
            // Get date from request or default to today
            // Use filled() to avoid passing null to the strictly typed getDailyDashboard() method
            $date = $request->filled('date') ? $request->input('date') : now()->toDateString();

            // Fetch dashboard data from service
            $data = $this->reportService->getDailyDashboard($date);

            return response()->json([
                'success' => true,
                'message' => 'تم جلب البيانات بنجاح',
                'data'    => $data,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في التحقق من البيانات: ' . $e->getMessage(),
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            // Log the error for debugging
            Log::error('Daily Dashboard Error: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ملخص التسليمات (AN-04 / fn41)
     * GET /api/v1/analytics/reports/delivery-summary
     */
    public function deliverySummary(KpiFilterRequest $request): JsonResponse
    {
        try {
            $summary = $this->reportService->getDeliverySummary(
                $request->period_start, 
                $request->period_end, 
                $request->driver_id
            );
            return response()->json(['success' => true, 'data' => $summary]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * تقرير تكاليف الصيانة (analytics-maintenance-cost)
     * GET /api/v1/analytics/reports/maintenance-cost
     */
    public function maintenanceCost(Request $request): JsonResponse
    {
        $request->validate([
            'period_start' => 'sometimes|date',
            'period_end'   => 'sometimes|date|after_or_equal:period_start',
        ]);

        try {
            // Default: last 6 months
            $periodStart = $request->input('period_start', now()->subMonths(6)->startOfMonth()->toDateString());
            $periodEnd   = $request->input('period_end', now()->toDateString());

            $data = $this->reportService->getMaintenanceCostReport($periodStart, $periodEnd);

            return response()->json([
                'success' => true,
                'message' => 'تم جلب البيانات بنجاح',
                'data'    => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * مخطط الإيرادات الشهري (analytics-revenue-chart)
     * GET /api/v1/analytics/reports/revenue-chart?months=6
     */
    public function revenueChart(Request $request): JsonResponse
    {
        $request->validate([
            'months' => 'sometimes|integer|min:1|max:24',
        ]);

        try {
            $months = (int) $request->input('months', 6);
            $data   = $this->reportService->getRevenueChart($months);

            return response()->json([
                'success' => true,
                'message' => 'تم جلب البيانات بنجاح',
                'data'    => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * لوحة الترتيب (Leaderboard) بنقاط السائقين (AN-05)
     * GET /api/v1/analytics/reports/driver-leaderboard
     */
    public function driverLeaderboard(Request $request): JsonResponse
    {
        $request->validate([
            'period_start' => 'sometimes|date',
            'period_end'   => 'sometimes|date|after_or_equal:period_start',
        ]);

        try {
            // Default: last 6 months
            $periodStart = $request->input('period_start', now()->subMonths(6)->startOfMonth()->toDateString());
            $periodEnd   = $request->input('period_end', now()->toDateString());

            $leaderboard = $this->performanceRepository->getLeaderboard($periodStart, $periodEnd);

            return response()->json([
                'success' => true,
                'message' => 'تم جلب البيانات بنجاح',
                'data'    => $leaderboard,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تصدير تقرير إلى Excel/CSV/PDF (AN-06 / fn42)
     * POST /api/v1/analytics/reports/export
     */
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'report_type' => 'required|string|in:driver_performance,fleet_kpis,delivery_summary,co2,maintenance_cost',
            'format'      => 'required|string|in:xlsx,csv,pdf',
            'period_start'=> 'required|date',
            'period_end'  => 'required|date|after_or_equal:period_start',
        ]);

        try {
            $result = $this->reportService->exportReport(
                $request->report_type, 
                $request->only(['period_start', 'period_end', 'driver_id', 'vehicle_id']), 
                $request->format
            );
            
            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
