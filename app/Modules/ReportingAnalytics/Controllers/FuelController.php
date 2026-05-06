<?php

/**
 * @file: FuelController.php
 * @description: متحكم صفحة الوقود والكفاءة - Reporting & Analytics Service
 *               Serves the "Fuel & Efficiency" page with two tabs:
 *               - Fuel Expense Audit  (GET /fuel/audit)
 *               - Fuel Efficiency Comparator (GET /fuel/efficiency)
 *               Plus: Add Fuel Invoice (POST /fuel/invoices) and Export CSV (GET /fuel/export)
 * @module: ReportingAnalytics
 * @author: Team Leader (Khalid)
 */

namespace App\Modules\ReportingAnalytics\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ReportingAnalytics\Services\FuelService;
use App\Modules\ReportingAnalytics\Requests\FuelInvoiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FuelController extends Controller
{
    protected FuelService $fuelService;

    public function __construct(FuelService $fuelService)
    {
        $this->fuelService = $fuelService;
    }

    // =========================================================================
    // TAB 1 — Fuel Expense Audit
    // GET /api/v1/analytics/fuel/audit?period_start=2026-04-01&period_end=2026-04-30
    // =========================================================================

    public function audit(Request $request): JsonResponse
    {
        $request->validate([
            'period_start' => 'required|date',
            'period_end'   => 'required|date|after_or_equal:period_start',
        ]);

        $data = $this->fuelService->getFuelExpenseAudit(
            $request->period_start,
            $request->period_end
        );

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    // =========================================================================
    // TAB 2 — Fuel Efficiency Comparator
    // GET /api/v1/analytics/fuel/efficiency?period_start=2026-04-01&period_end=2026-04-30
    // =========================================================================

    public function efficiency(Request $request): JsonResponse
    {
        $request->validate([
            'period_start' => 'required|date',
            'period_end'   => 'required|date|after_or_equal:period_start',
        ]);

        $data = $this->fuelService->getFuelEfficiencyComparator(
            $request->period_start,
            $request->period_end
        );

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    // =========================================================================
    // Add Fuel Invoice (modal form)
    // POST /api/v1/analytics/fuel/invoices
    // =========================================================================

    public function storeInvoice(FuelInvoiceRequest $request): JsonResponse
    {
        $log = $this->fuelService->addFuelInvoice($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ فاتورة الوقود بنجاح.',
            'data'    => $log,
        ], 201);
    }

    // =========================================================================
    // Export CSV
    // GET /api/v1/analytics/fuel/export?tab=audit&period_start=...&period_end=...
    // =========================================================================

    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $request->validate([
            'tab'          => 'required|in:audit,efficiency',
            'period_start' => 'required|date',
            'period_end'   => 'required|date|after_or_equal:period_start',
        ]);

        $tab   = $request->tab;
        $start = $request->period_start;
        $end   = $request->period_end;

        if ($tab === 'audit') {
            $result  = $this->fuelService->getFuelExpenseAudit($start, $end);
            $rows    = $result['rows'];
            $headers = ['Vehicle', 'Period', 'GPS Distance (km)', 'Expected Fuel (L)', 'Actual Fuel (L)', 'Discrepancy %', 'Flag'];
            $mapRow  = fn($r) => [
                $r['vehicle_license'],
                $r['period'],
                $r['gps_distance_km'],
                $r['expected_fuel_l'],
                $r['actual_fuel_l'],
                $r['discrepancy_pct'] . '%',
                ucfirst($r['flag']),
            ];
        } else {
            $result  = $this->fuelService->getFuelEfficiencyComparator($start, $end);
            $rows    = $result['table'];
            $headers = ['#', 'Vehicle', 'Type', 'Avg Efficiency (km/L)', 'vs Fleet Avg (%)', 'Trend'];
            $mapRow  = fn($r) => [
                $r['rank'],
                $r['vehicle_license'],
                $r['vehicle_type'],
                $r['avg_efficiency'],
                ($r['vs_fleet_avg'] >= 0 ? '+' : '') . $r['vs_fleet_avg'] . '%',
                $r['trend'] !== null ? ($r['trend'] >= 0 ? '+' : '') . $r['trend'] : 'N/A',
            ];
        }

        $filename = "fuel_{$tab}_{$start}_{$end}.csv";

        return response()->streamDownload(function () use ($headers, $rows, $mapRow) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $mapRow($row));
            }
            fclose($handle);
        }, $filename, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
