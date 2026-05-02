<?php

/**
 * @file Vehicle.php
 * @description Eloquent Model for the vehicles table — RouteDispatch Module
 * @module RouteDispatch
 * @table vehicles
 * @author Team Leader (Khalid)
 */

namespace App\Modules\RouteDispatch\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\AuthIdentity\Models\Driver;
use App\Modules\OrderManagement\Models\PreTripInspection;
use App\Modules\ReportingAnalytics\Models\IncidentReport;
use App\Modules\ReportingAnalytics\Models\FuelAuditLog;
use App\Modules\Maintenance\Models\MaintenanceAssignment;

class Vehicle extends Model
{
    use HasFactory;

    protected $table      = 'vehicles';
    protected $primaryKey = 'vehicle_id';
    protected $keyType    = 'int';
    public $incrementing  = true;

    // DDL has CreatedAt only (no UpdatedAt)
    const UPDATED_AT = null;
    const CREATED_AT = 'created_at';

    /** @var array<string> */
    protected $fillable = [
        'vehicle_brand',
        'vehicle_license',
        'max_weight_capacity',
        'fuel_type',
        'status',
        'current_odometer',
        'created_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'max_weight_capacity' => 'float',
        'current_odometer'    => 'float',
        'created_at'          => 'datetime',
    ];

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'Active');
    }

    public function scopeInMaintenance($query)
    {
        return $query->where('status', 'Maintenance');
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    /** Drivers currently assigned to this vehicle */
    public function drivers()
    {
        return $this->hasMany(Driver::class, 'vehicle_id', 'vehicle_id');
    }

    /** Routes assigned to this vehicle */
    public function routes()
    {
        return $this->hasMany(Route::class, 'vehicle_id', 'vehicle_id');
    }

    /** Pre-trip inspections for this vehicle */
    public function preTripInspections()
    {
        return $this->hasMany(PreTripInspection::class, 'vehicle_id', 'vehicle_id');
    }

    /** Incident reports involving this vehicle */
    public function incidentReports()
    {
        return $this->hasMany(IncidentReport::class, 'vehicle_id', 'vehicle_id');
    }

    /** Fuel audit log entries for this vehicle */
    public function fuelAuditLogs()
    {
        return $this->hasMany(FuelAuditLog::class, 'vehicle_id', 'vehicle_id');
    }

    /** Maintenance assignments for this vehicle */
    public function maintenanceAssignments()
    {
        return $this->hasMany(MaintenanceAssignment::class, 'vehicle_id', 'vehicle_id');
    }
}
