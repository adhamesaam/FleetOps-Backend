<?php

/**
 * @file: ReportService.php
 * @description: خدمة تصدير التقارير إلى PDF/Excel - Reporting & Analytics Service (AN-06)
 * @module: ReportingAnalytics
 * @author: Team Leader (Khalid)
 */

namespace App\Modules\ReportingAnalytics\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
        // 1. Validate reportType and format
        $validTypes = ['driver_performance', 'fleet_kpis', 'delivery_summary', 'co2', 'maintenance_cost'];
        if (!in_array($reportType, $validTypes)) {
            throw new \InvalidArgumentException("Invalid report type: {$reportType}");
        }

        $validFormats = ['xlsx', 'csv', 'pdf'];
        if (!in_array($format, $validFormats)) {
            throw new \InvalidArgumentException("Invalid format: {$format}");
        }

        $periodStart = $filters['period_start'] ?? Carbon::now()->subDays(30)->toDateString();
        $periodEnd = $filters['period_end'] ?? Carbon::now()->toDateString();
        
        // 2. Fetch data based on reportType and filters
        $data = [];
        $kpiService = app(\App\Modules\ReportingAnalytics\Services\KpiService::class);

        switch ($reportType) {
            case 'driver_performance':
                if (!empty($filters['driver_id'])) {
                    $data = [$kpiService->calculateDriverPerformanceScore($filters['driver_id'], $periodStart, $periodEnd)];
                } else {
                    $repo = app(\App\Modules\ReportingAnalytics\Repositories\DriverPerformanceRepository::class);
                    $data = $repo->getLeaderboard($periodStart, $periodEnd);
                }
                break;
            case 'fleet_kpis':
                $data = [$kpiService->getAnalyticsKpis('30d')];
                break;
            case 'delivery_summary':
                $data = [$this->getDeliverySummary($periodStart, $periodEnd, $filters['driver_id'] ?? null)];
                break;
            case 'co2':
                $res = $kpiService->generateCO2Report('monthly');
                $data = $res['vehicles'] ?? [];
                break;
            case 'maintenance_cost':
                $res = $this->getMaintenanceCostReport($periodStart, $periodEnd);
                $data = $res['monthly_breakdown'] ?? [];
                break;
        }

        // Flatten data for CSV/XLSX
        $flatData = [];
        foreach ($data as $item) {
            $flatData[] = $this->flattenArray((array) $item);
        }

        // 4. Generate filename
        $timestamp = Carbon::now()->format('Ymd_His');
        $filename = "{$reportType}_{$periodStart}_{$periodEnd}_{$timestamp}.{$format}";
        $path = "exports/{$filename}";

        // 3. Based on format & 5. Store file temporarily
        if ($format === 'pdf' && class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML("<h1>{$reportType} Report</h1><pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>");
            \Illuminate\Support\Facades\Storage::disk('local')->put($path, $pdf->output());
        } elseif (($format === 'xlsx' || $format === 'csv') && class_exists('\Maatwebsite\Excel\Facades\Excel')) {
            // Native fallback for Excel if Export class isn't created
            $this->generateNativeCsv($flatData, $path);
        } else {
            // Fallback for all to native CSV
            if ($format === 'xlsx' || $format === 'pdf') {
                $filename = str_replace(['.xlsx', '.pdf'], '.csv', $filename);
                $path = "exports/{$filename}";
            }
            $this->generateNativeCsv($flatData, $path);
        }

        // 6. Return file info with download URL
        return [
            'file_path' => $path,
            'filename' => $filename,
            'size_bytes' => \Illuminate\Support\Facades\Storage::disk('local')->exists($path) ? \Illuminate\Support\Facades\Storage::disk('local')->size($path) : 0,
            'download_url' => url("storage/{$path}")
        ];
    }

    /**
     * Helper to flatten nested array for CSV
     */
    private function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix . (empty($prefix) ? '' : '_') . $key;
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = is_bool($value) ? ($value ? 'Yes' : 'No') : $value;
            }
        }
        return $result;
    }

    /**
     * Helper to generate CSV natively
     */
    private function generateNativeCsv(array $data, string $path): void
    {
        $output = fopen('php://temp', 'r+');
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
            foreach ($data as $row) {
                fputcsv($output, array_values($row));
            }
        } else {
            fputcsv($output, ['No data available for this report']);
        }
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);
        
        \Illuminate\Support\Facades\Storage::disk('local')->put($path, $csvContent);
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
        // 1. Query orders in period from SQL Server
        $query = \Illuminate\Support\Facades\DB::table('order')
            ->whereBetween('Created_at', [$periodStart, $periodEnd]);
            
        // 4. If driverId provided → filter by driver
        if ($driverId) {
            $query->where('DriverID(FK)', $driverId);
        }
        
        $orders = $query->get();
        
        // 2. Group by status: delivered, returned, failed, in_transit
        // 3. Calculate totals and percentages
        $total = $orders->count();
        
        $summary = [
            'total_orders' => $total,
            'delivered' => 0,
            'returned' => 0,
            'failed' => 0,
            'in_transit' => 0,
            'other' => 0
        ];
        
        foreach ($orders as $order) {
            $status = strtolower($order->Status ?? '');
            if ($status === 'delivered') {
                $summary['delivered']++;
            } elseif ($status === 'returned') {
                $summary['returned']++;
            } elseif ($status === 'failed') {
                $summary['failed']++;
            } elseif (in_array($status, ['intransit', 'in_transit', 'out for delivery'])) {
                $summary['in_transit']++;
            } else {
                $summary['other']++;
            }
        }
        
        $summary['delivered_pct'] = $total > 0 ? round(($summary['delivered'] / $total) * 100, 2) : 0;
        $summary['returned_pct'] = $total > 0 ? round(($summary['returned'] / $total) * 100, 2) : 0;
        $summary['failed_pct'] = $total > 0 ? round(($summary['failed'] / $total) * 100, 2) : 0;
        $summary['in_transit_pct'] = $total > 0 ? round(($summary['in_transit'] / $total) * 100, 2) : 0;
        
        return [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'driver_id' => $driverId,
            'summary' => $summary
        ];
    }

    /**
     * تقرير تكاليف الصيانة لأسطول المركبات (analytics-maintenance-cost)
     * Breaks down maintenance costs between Preventive (routine) and Reactive (emergency/breakdown).
     *
     * @param string $periodStart
     * @param string $periodEnd
     * @return array  per-month cost breakdown
     */
    public function getMaintenanceCostReport(string $periodStart, string $periodEnd): array
    {
        $start = Carbon::parse($periodStart)->startOfDay();
        $end   = Carbon::parse($periodEnd)->endOfDay();

        // Query maintenance_assignments grouped by month and type category
        $workOrders = DB::table('maintenance_assignments')
            ->leftJoin('maintenance_parts_used', 'maintenance_assignments.assignment_id', '=', 'maintenance_parts_used.log_id')
            ->whereBetween('maintenance_assignments.created_at', [$start, $end])
            ->select(
                DB::raw("FORMAT(maintenance_assignments.created_at, 'yyyy-MM') as month"),
                'maintenance_assignments.service_type as type',
                DB::raw('ISNULL(SUM(maintenance_parts_used.quantity_used * maintenance_parts_used.unit_cost), 0) as total_cost'),
                DB::raw('COUNT(DISTINCT maintenance_assignments.assignment_id) as order_count')
            )
            ->groupBy(DB::raw("FORMAT(maintenance_assignments.created_at, 'yyyy-MM')"), 'maintenance_assignments.service_type')
            ->orderBy(DB::raw("FORMAT(maintenance_assignments.created_at, 'yyyy-MM')"))
            ->get();

        // Classify: routine = Preventive, emergency/breakdown = Reactive
        $months = [];
        foreach ($workOrders as $row) {
            $category = in_array($row->type, ['oil_change', 'tire_rotation', 'inspection']) ? 'preventive' : 'reactive';

            if (!isset($months[$row->month])) {
                $months[$row->month] = [
                    'month'      => $row->month,
                    'preventive' => 0,
                    'reactive'   => 0,
                    'total'      => 0,
                    'preventive_count' => 0,
                    'reactive_count'   => 0,
                ];
            }

            $months[$row->month][$category]              += round($row->total_cost, 2);
            $months[$row->month][$category . '_count']   += $row->order_count;
            $months[$row->month]['total']                 += round($row->total_cost, 2);
        }

        $monthlyData = array_values($months);

        // Top vehicles by maintenance cost
        $topVehicles = DB::table('maintenance_assignments')
            ->join('vehicles', 'maintenance_assignments.vehicle_id', '=', 'vehicles.vehicle_id')
            ->leftJoin('maintenance_parts_used', 'maintenance_assignments.assignment_id', '=', 'maintenance_parts_used.log_id')
            ->whereBetween('maintenance_assignments.created_at', [$start, $end])
            ->select(
                'vehicles.vehicle_id',
                'vehicles.VehicleLicense',
                'vehicles.VehicleType',
                DB::raw('ISNULL(SUM(maintenance_parts_used.quantity_used * maintenance_parts_used.unit_cost), 0) as total_cost'),
                DB::raw('COUNT(DISTINCT maintenance_assignments.assignment_id) as wo_count')
            )
            ->groupBy('vehicles.vehicle_id', 'vehicles.VehicleLicense', 'vehicles.VehicleType')
            ->orderByDesc(DB::raw('ISNULL(SUM(maintenance_parts_used.quantity_used * maintenance_parts_used.unit_cost), 0)'))
            ->limit(10)
            ->get();

        $grandTotal = round(array_sum(array_column($monthlyData, 'total')), 2);

        return [
            'period_start'      => $start->toDateString(),
            'period_end'        => $end->toDateString(),
            'grand_total'       => $grandTotal,
            'total_preventive'  => round(array_sum(array_column($monthlyData, 'preventive')), 2),
            'total_reactive'    => round(array_sum(array_column($monthlyData, 'reactive')), 2),
            'monthly_breakdown' => $monthlyData,
            'top_vehicles'      => $topVehicles,
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Analytics Page — Revenue Chart
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * مخطط الإيرادات الشهري (analytics-revenue-chart)
     * Returns monthly Revenue vs Costs for the last N months.
     *
     * Revenue = cash_ledger (collected payments)
     * Costs   = fuel_audit_logs (total_cost) + work_orders (repair_cost)
     *
     * @param int $months  Number of months to include (default 6)
     * @return array
     */
    public function getRevenueChart(int $months = 6): array
    {
        $now = Carbon::now();
        $labels   = [];
        $revenue  = [];
        $costs    = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $monthStart = $now->copy()->subMonths($i)->startOfMonth();
            $monthEnd   = $now->copy()->subMonths($i)->endOfMonth();
            $label      = $monthStart->format('M Y');

            $labels[] = $label;

            // Revenue: collected payments
            $rev = DB::table('cash_ledger')
                ->where('payment_status', 'collected')
                ->whereBetween('transaction_ts', [$monthStart, $monthEnd])
                ->sum('amount_collected');

            $revenue[] = round($rev, 2);

            // Costs: fuel + maintenance
            $fuelCost = DB::table('fuel_audit_logs')
                ->whereBetween('log_ts', [$monthStart, $monthEnd])
                ->sum('total_cost');

            $maintenanceCost = DB::table('maintenance_assignments')
                ->leftJoin('maintenance_parts_used', 'maintenance_assignments.assignment_id', '=', 'maintenance_parts_used.log_id')
                ->whereBetween('maintenance_assignments.created_at', [$monthStart, $monthEnd])
                ->sum(DB::raw('maintenance_parts_used.quantity_used * maintenance_parts_used.unit_cost'));

            $costs[] = round($fuelCost + $maintenanceCost, 2);
        }

        // Calculate net profit per month
        $profit = [];
        for ($i = 0; $i < count($revenue); $i++) {
            $profit[] = round($revenue[$i] - $costs[$i], 2);
        }

        return [
            'months'  => $months,
            'labels'  => $labels,
            'revenue' => $revenue,
            'costs'   => $costs,
            'profit'  => $profit,
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Daily Dashboard (existing)
    // ═══════════════════════════════════════════════════════════════════════════

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
                ->where('status', 'Active')
                ->whereDate('scheduled_start_time', $date)
                ->count();

            $yesterdayRoutes = Route::query()
                ->where('status', 'Active')
                ->whereDate('scheduled_start_time', $yesterday->toDateString())
                ->count();

            // ── 2. Orders Today ──────────────────────────────────────────────────
            $ordersToday     = Order::query()
                ->whereDate('Created_at', $date)
                ->count();

            $ordersYesterday = Order::query()
                ->whereDate('Created_at', $yesterday->toDateString())
                ->count();

            // ── 3. Open Alerts (unresolved incidents) ────────────────────────────
            $openAlerts = IncidentReport::query()
                ->whereDate('incident_ts', '<=', $date)
                ->count();

            // ── 4. Fuel Efficiency (km/L) ────────────────────────────────────────
            $fuelToday     = $this->calculateFuelEfficiency($date);
            $fuelYesterday = $this->calculateFuelEfficiency($yesterday->toDateString());

            // ── 5. Delivery Rate (%) ─────────────────────────────────────────────
            $deliveredToday    = Order::query()
                ->where('Status', 'Delivered')
                ->whereDate('DeliveredAt', $date)
                ->count();

            $deliveredYesterday = Order::query()
                ->where('Status', 'Delivered')
                ->whereDate('DeliveredAt', $yesterday->toDateString())
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
        $totalDistance = Route::whereIn('status', ['Completed', 'Active'])
            ->whereDate('scheduled_start_time', $date)
            ->whereNotNull('total_distance')
            ->sum('total_distance');

        if ($totalDistance == 0) {
            return null;
        }

        return round($totalDistance / $totalFuel, 1);
    }
}
