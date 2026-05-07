<?php

namespace App\Modules\ReportingAnalytics\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Modules\ReportingAnalytics\Services\incidentReportService;
use App\Modules\ReportingAnalytics\Requests\CreateIncidentReportRequest;

class incidentReportController extends Controller
{
    protected $incidentReportService;

    public function __construct(incidentReportService $incidentReportService)
    {
        $this->incidentReportService = $incidentReportService;
    }

    public function createIncidentReport(CreateIncidentReportRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            $report = $this->incidentReportService->createIncidentReport($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Incident report created successfully',
                'data' => $report
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create incident report: ' . $e->getMessage()
            ], 500);
        }
    }   
}