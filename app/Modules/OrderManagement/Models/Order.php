<?php

/**
 * @file Order.php
 * @description Eloquent Model for the orders table — OrderManagement Module
 * @module OrderManagement
 * @table Order
 *
 * NOTE: OrderID is NOT auto-incremented — IDs are assigned externally per DDL.
 *
 * @author Team Leader (Khalid)
 */

namespace App\Modules\OrderManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\AuthIdentity\Models\Customer;
use App\Modules\AuthIdentity\Models\Driver;
use App\Modules\RouteDispatch\Models\RouteStop;
use App\Modules\RouteDispatch\Models\Vehicle;
use App\Modules\ReportingAnalytics\Models\CashLedger;

class Order extends Model
{
    use HasFactory;

    protected $table      = 'order';
    protected $primaryKey = 'OrderID';
    protected $keyType    = 'int';
    public $incrementing  = true; // Enabled auto-increment

    const CREATED_AT = 'Created_at';
    const UPDATED_AT = 'UpdatedAt';

    /** @var array<string> */
    protected $fillable = [
        'OrderID',
        'DriverID(FK)',
        'CustomerID(FK)',
        'vehicle_id(FK)',
        'TransactionID(FK)',
        'Status',
        'ETA',
        'PromisedWindow',
        'Priority',
        'Type',
        'Price',
        'digital_signature',
        'Delivery_preference',
        'Payment_method',
        'Created_at',
        'UpdatedAt',
        'Perishable',
        'DeliveredAt',
        'Weight',
        'Volume',
        'LiveTrackingLink',
        'DeliveryTimeWindow',
        'Longitude',
        'Latitude',
        'Area'
    ];

    /** @var array<string, string> */
    protected $casts = [
        'Priority'           => 'integer',
        'Price'              => 'integer',
        'Weight'             => 'integer',
        'Volume'             => 'integer',
        'Perishable'         => 'boolean',
        'DeliveryTimeWindow' => 'decimal:2',
        'Longitude'          => 'decimal:8',
        'Latitude'           => 'decimal:8',
        'PromisedWindow'     => 'datetime',
        'DeliveredAt'        => 'datetime',
        'Created_at'         => 'datetime',
        'UpdatedAt'         => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    /** Customer who placed this order */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'CustomerID(FK)', 'customer_id');
    }

    /** Driver assigned to deliver this order */
    public function driver()
    {
        return $this->belongsTo(Driver::class, 'DriverID(FK)', 'driver_id');
    }

    /** Vehicle assigned to deliver this order */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id(FK)', 'vehicle_id');
    }

    /** Route stop(s) associated with this order */
    public function routeStops()
    {
        return $this->hasMany(RouteStop::class, 'order_id', 'OrderID');
    }

    /** Cash ledger entry for this order */
    public function cashLedgerEntry()
    {
        return $this->belongsTo(CashLedger::class, 'TransactionID(FK)', 'transaction_id');
    }
}
