<?php

namespace App\Modules\ReportingAnalytics\Repositories;

use App\Modules\Shared\Repositories\BaseRepository;
use App\Modules\ReportingAnalytics\Models\IncidentReport;

class incidentReportsRepository extends BaseRepository
{
    public function __construct(IncidentReport $model)
    {
        parent::__construct($model);
    }

    /**
     * إنشاء سجل جديد في جدول تقارير الحوادث
     *
     * @param array $data
     * @return IncidentReport
     */
    public function createIncidentReport(array $data): IncidentReport
    {
        // تعيين وقت الحادث إذا لم يكن موجوداً
        if (!isset($data['incident_ts'])) {
            $data['incident_ts'] = now();
        }

        return $this->create($data);
    }
}