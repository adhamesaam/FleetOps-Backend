<?php

namespace App\Modules\ReportingAnalytics\Services;

use Exception;
use App\Modules\ReportingAnalytics\Repositories\incidentReportsRepository;
use App\Modules\ReportingAnalytics\Models\IncidentReport;

class incidentReportService
{
    protected $incidentReportRepository;

    public function __construct(incidentReportsRepository $incidentReportRepository)
    {
        $this->incidentReportRepository = $incidentReportRepository;
    }

    public function createIncidentReport(array $data): IncidentReport
    {
        return $this->incidentReportRepository->createIncidentReport($data);
    }   
}
