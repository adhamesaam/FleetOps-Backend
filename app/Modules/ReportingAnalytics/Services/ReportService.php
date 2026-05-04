<?php

/**
 * @file: ReportService.php
 * @description: خدمة تصدير التقارير إلى PDF/Excel - Reporting & Analytics Service (AN-06)
 * @module: ReportingAnalytics
 * @author: Team Leader (Khalid)
 */

namespace App\Modules\ReportingAnalytics\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use App\Modules\RouteDispatch\Models\Route;
use App\Modules\OrderManagement\Models\Order;
use App\Modules\ReportingAnalytics\Models\IncidentReport;
use App\Modules\ReportingAnalytics\Models\FuelAuditLog;

class ReportService
{
    /**
     * تصدير تقرير الأداء إلى Excel (AN-06 / fn42)
     * @param string $reportType  (driver_performance | fleet_kpis | delivery_summary | co2 | maintenance_cost)
     * @param array  $filters     (period_start, period_end, driver_id?, vehicle_id?)
     * @param string $format      ('xlsx' | 'csv' | 'pdf')
     * @return array  ['file_path' => string, 'filename' => string, 'size_bytes' => int]
     * @throws Exception
     */
    public function exportReport(string $reportType, array $filters, string $format = 'xlsx'): array
    {
        // TODO: Export report
        // 1. Validate reportType and format
        // 2. Fetch data based on reportType and filters
        // 3. Based on format:
        //    - xlsx/csv: use maatwebsite/excel or fputcsv for CSV
        //    - pdf: use barryvdh/laravel-dompdf
        // 4. Generate filename: "{reportType}_{period_start}_{period_end}.{format}"
        // 5. Store file temporarily in storage/app/exports/
        // 6. Return file info with download URL
    }

    /**
     * تقرير ملخص التسليمات (AN-04 / fn41)
     * @param string $periodStart
     * @param string $periodEnd
     * @param int|null $driverId
     * @return array  delivery summary data
     */
    public function getDeliverySummary(string $periodStart, string $periodEnd, ?int $driverId = null): array
    {
        // TODO: Get delivery summary
        // 1. Query orders in period from SQL Server
        // 2. Group by status: delivered, returned, failed, in_transit
        // 3. Calculate totals and percentages
        // 4. If driverId provided → filter by driver
        // 5. Return summary array
    }

    /**
     * تقرير تكاليف الصيانة لأسطول المركبات (AN-01)
     * @param string $periodStart
     * @param string $periodEnd
     * @return array  per-vehicle maintenance costs
     */
    public function getMaintenanceCostReport(string $periodStart, string $periodEnd): array
    {
        // TODO: Get maintenance cost report
        // 1. Query work_orders in period with repair_cost
        // 2. Group by vehicle_id
        // 3. Sum total_cost per vehicle
        // 4. Join with vehicle market_value for cost-to-value ratio
        // 5. Return sorted by total_cost descending
    }

    /**
     * تقرير لوحة قيادة العمليات اليومية
     * Aggregates data from Routes, Orders, Alerts, and Fuel logs
     * to provide the frontend dashboard with summary metrics.
     *
     * @param string $date  YYYY-MM-DD
     * @return array  dashboard summary metrics
     */
    public function getDailyDashboard(string $date): array
    {

        try {
            
            $today     = Carbon::parse($date)->startOfDay();
            $yesterday = Carbon::parse($date)->copy()->subDay();

            // ── 1. Active Routes ─────────────────────────────────────────────────
            $activeRoutes    = Route::query()
                ->where('status', 'in_progress')
                ->whereDate('scheduled_start_time', $date)
                ->count();

            $yesterdayRoutes = Route::query()
                ->where('status', 'in_progress')
                ->whereDate('scheduled_start_time', $yesterday->toDateString())
                ->count();

            // ── 2. Orders Today ──────────────────────────────────────────────────
            $ordersToday     = Order::query()
                ->whereDate('created_at', $date)
                ->count();

            $ordersYesterday = Order::query()
                ->whereDate('created_at', $yesterday->toDateString())
                ->count();

            // ── 3. Open Alerts (unresolved incidents) ────────────────────────────
            $openAlerts = IncidentReport::query()
                ->whereIn('status', ['open', 'pending', 'investigating'])
                ->whereDate('incident_ts', '<=', $date)
                ->count();

            // ── 4. Fuel Efficiency (km/L) ────────────────────────────────────────
            $fuelToday     = $this->calculateFuelEfficiency($date);
            $fuelYesterday = $this->calculateFuelEfficiency($yesterday->toDateString());

            // ── 5. Delivery Rate (%) ─────────────────────────────────────────────
            $deliveredToday    = Order::query()
                ->where('status', 'delivered')
                ->whereDate('delivered_at', $date)
                ->count();

            $deliveredYesterday = Order::query()
                ->where('status', 'delivered')
                ->whereDate('delivered_at', $yesterday->toDateString())
                ->count();

            $deliveryRate          = $ordersToday > 0 ? round(($deliveredToday / $ordersToday) * 100, 1) : 0.0;
            $deliveryRateYesterday = $ordersYesterday > 0 ? round(($deliveredYesterday / $ordersYesterday) * 100, 1) : 0.0;
            
            // ── Build Response ───────────────────────────────────────────────────
            return [
                'active_routes'   => [
                    'count'    => (string) $activeRoutes,
                    'change'   => $this->calculateChange((float) $activeRoutes, (float) $yesterdayRoutes),
                    'positive' => $this->isPositive((float) $activeRoutes, (float) $yesterdayRoutes),
                ],
                'orders_today'    => [
                    'count'    => (string) $ordersToday,
                    'change'   => $this->calculateChange((float) $ordersToday, (float) $ordersYesterday),
                    'positive' => $this->isPositive((float) $ordersToday, (float) $ordersYesterday),
                ],
                'open_alerts'     => [
                    'count'    => (string) $openAlerts,
                    'change'   => 'N/A',
                    'positive' => null,
                ],
                'fuel_efficiency' => [
                    'count'    => $fuelToday !== null ? $fuelToday . ' km/L' : 'N/A',
                    'change'   => ($fuelToday !== null && $fuelYesterday !== null)
                        ? $this->calculateChange($fuelToday, $fuelYesterday)
                        : 'N/A',
                    'positive' => ($fuelToday !== null && $fuelYesterday !== null)
                        ? $this->isPositive($fuelToday, $fuelYesterday)
                        : null,
                ],
                'delivery_rate'   => [
                    'count'    => $deliveryRate . '%',
                    'change'   => $this->calculateChange($deliveryRate, $deliveryRateYesterday),
                    'positive' => $this->isPositive($deliveryRate, $deliveryRateYesterday),
                ],
            ];

        } catch (\Exception $e) {
            Log::error($e);
            return array('message' => 'Server error');
        }
        
        }

    // ═══════════════════════════════════════════════════════════════════════════
    // Private Helpers
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Calculate percentage change between today and yesterday values.
     */
    private function calculateChange(float $current, float $previous): string
    {
        if ($previous == 0.0) {
            return $current > 0 ? '+100%' : '0%';
        }

        $change = round((($current - $previous) / $previous) * 100, 1);
        $sign   = $change >= 0 ? '+' : '';

        return $sign . $change . '%';
    }

    /**
     * Determine if the change is positive (current >= previous).
     */
    private function isPositive(float $current, float $previous): ?bool
    {
        if ($current == $previous) {
            return null;
        }

        return $current > $previous;
    }

    /**
     * Calculate fuel efficiency (km/L) for a given date.
     * Uses fuel_audit_logs: total distance driven / total fuel consumed.
     */
    private function calculateFuelEfficiency(string $date): ?float
    {
        $logs = FuelAuditLog::whereDate('log_ts', $date)->get();

        if ($logs->isEmpty()) {
            return null;
        }

        $totalFuel = $logs->sum('fuel_quantity');

        if ($totalFuel == 0) {
            return null;
        }

        // Calculate distance from routes that have actual distance recorded on this date
        $totalDistance = Route::whereIn('status', ['completed', 'in_progress'])
            ->whereDate('scheduled_start_time', $date)
            ->whereNotNull('total_distance')
            ->sum('total_distance');

        if ($totalDistance == 0) {
            return null;
        }

        return round($totalDistance / $totalFuel, 1);
    }
}
