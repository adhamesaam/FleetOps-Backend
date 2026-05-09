<?php

/**
 * @file: GpsPing.php
 * @description: نموذج Eloquent لنبضات GPS - Real-time Tracking & GPS Service
 * @module: RealtimeTracking
 * @author: Team Leader (Khalid)
 */

namespace App\Modules\RealtimeTracking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\AuthIdentity\Models\Driver;
use App\Modules\RouteDispatch\Models\Route;
use App\Modules\RouteDispatch\Models\Vehicle;

class GpsPing extends Model
{
    use HasFactory;

    protected $table = 'gps_pings';
    protected $primaryKey = 'ping_id';
    public $incrementing = true;

    protected $fillable = [
        'driver_id',
        'vehicle_id',
        'route_id',
        'lat',
        'lng',
        'speed_kmh',
        'accuracy_m',
        'heading',
        'is_spoofed',
        'recorded_at',
    ];

    protected $casts = [
        'lat'          => 'float',
        'lng'          => 'float',
        'speed_kmh'    => 'float',
        'accuracy_m'   => 'float',
        'heading'      => 'float',
        'is_spoofed'   => 'boolean',
        'recorded_at'  => 'datetime',
        'created_at'   => 'datetime',
    ];

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeForDriver($query, int $driverId)
    {
        return $query->where('driver_id', $driverId);
    }

    public function scopeForVehicle($query, int $vehicleId)
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    public function scopeNotSpoofed($query)
    {
        return $query->where('is_spoofed', false);
    }

    public function scopeRecent($query, int $minutes = 5)
    {
        return $query->where('recorded_at', '>=', now()->subMinutes($minutes));
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id', 'driver_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id', 'vehicle_id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id', 'route_id');
    }
}
