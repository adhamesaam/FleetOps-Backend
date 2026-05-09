<?php

/**
 * @file RouteStop.php
 * @description Eloquent Model for the route_stops table — RouteDispatch Module
 * @module RouteDispatch
 * @table route_stops
 * @author Team Leader (Khalid)
 */

namespace App\Modules\RouteDispatch\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\OrderManagement\Models\Order;

class RouteStop extends Model
{
    use HasFactory;

    protected $table      = 'route_stops';
    protected $primaryKey = 'stop_id';
    protected $keyType    = 'int';
    public $incrementing  = true;

    // No timestamps in DDL
    public $timestamps = false;

    /** @var array<string> */
    protected $fillable = [
        'route_id',
        'stop_no',
        'order_id',
        'eta',
        'actual_arrival_time',
        'latitude',
        'longitude',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'eta'                 => 'datetime',
        'actual_arrival_time' => 'datetime',
        'latitude'            => 'float',
        'longitude'           => 'float',
        'stop_no'             => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    /** The route this stop belongs to */
    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id', 'route_id');
    }

    /** The order to be delivered at this stop */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'OrderID');
    }
}
