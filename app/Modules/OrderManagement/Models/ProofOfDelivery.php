<?php

/**
 * @file: ProofOfDelivery.php
 * @description: نموذج Eloquent لإثبات التسليم (POD) - Order Management Service (fn13/14)
 * @module: OrderManagement
 * @author: Team Leader (Khalid)
 */

namespace App\Modules\OrderManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProofOfDelivery extends Model
{
    use HasFactory;

    protected $table = 'order';
    protected $primaryKey = 'OrderID';
    public $incrementing = true;

    protected $fillable = [
        'DriverID(FK)',
        'digital_signature',    // Azure Blob Storage URL / base64 signature
        'Latitude',
        'Longitude',
        'DeliveredAt',
        'Status',
    ];

    protected $casts = [
        'Latitude'    => 'float',
        'Longitude'   => 'float',
        'DeliveredAt' => 'datetime',
        'Created_at'  => 'datetime',
        'UpdatedAt'   => 'datetime',
    ];

    // Disable automatic timestamps — the 'order' table uses non-standard column names
    public $timestamps = false;

    public function order()
    {
        return $this->belongsTo(Order::class, 'OrderID', 'OrderID');
    }
}
