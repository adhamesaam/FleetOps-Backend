<?php

/**
 * @file: routes.php
 * @description: Order Management Service Routes
 * @module: OrderManagement
 * @author: Team Leader (Khalid)
 */

use Illuminate\Support\Facades\Route;
use App\Modules\OrderManagement\Controllers\OrderController;
use App\Modules\OrderManagement\Controllers\ProofOfDeliveryController;
use App\Modules\OrderManagement\Controllers\InspectionController;

Route::prefix('api/v1')->middleware('auth:sanctum')->group(function () {

    // =====================================================================
    // Order Management
    // =====================================================================
    Route::prefix('orders')->group(function () {

        // Specialized routes MUST come before /{id}
        Route::post('/import',              [OrderController::class, 'bulkImport'])->name('orders.import');
        Route::get('/route/{routeId}',      [OrderController::class, 'routeOrders'])->name('orders.by-route')->where('routeId', '[0-9]+');
        Route::get('/driver/{driverId}',    [OrderController::class, 'driverOrders'])->name('orders.by-driver')->where('driverId', '[0-9]+');

        // Pre-Trip Inspections (fn12)
        Route::get('/inspections/vehicle/{vehicleId}', [InspectionController::class, 'forVehicle'])->name('inspections.by-vehicle')->where('vehicleId', '[0-9]+');
        Route::get('/inspections/route/{routeId}',     [InspectionController::class, 'forRoute'])->name('inspections.by-route')->where('routeId', '[0-9]+');
        Route::post('/inspections',                    [InspectionController::class, 'store'])->name('inspections.store');

        // CRUD
        Route::get('/',     [OrderController::class, 'index'])->name('orders.index');
        Route::post('/',    [OrderController::class, 'store'])->name('orders.store');
        Route::get('/{id}', [OrderController::class, 'show'])->name('orders.show')->where('id', '[0-9]+');
        Route::put('/{id}', [OrderController::class, 'update'])->name('orders.update')->where('id', '[0-9]+');
        Route::delete('/{id}', [OrderController::class, 'destroy'])->name('orders.destroy')->where('id', '[0-9]+');
        Route::get('/{status}', [OrderController::class, 'getByStatus'])->name('orders.by-status')->where('status', '[a-zA-Z]+');

        // Order State Machine Actions
        Route::patch('/{id}/status',     [OrderController::class, 'updateStatus'])->name('orders.update-status')->where('id', '[0-9]+');
        Route::post('/{id}/verify-qr',   [OrderController::class, 'verifyQr'])->name('orders.verify-qr')->where('id', '[0-9]+');
        Route::post('/{id}/return',      [OrderController::class, 'initiateReturn'])->name('orders.return')->where('id', '[0-9]+');

        // Proof of Delivery (fn13/14)
        Route::get('/{orderId}/pod',  [ProofOfDeliveryController::class, 'show'])->name('orders.pod.show')->where('orderId', '[0-9]+');
        Route::post('/{orderId}/pod', [ProofOfDeliveryController::class, 'store'])->name('orders.pod.store')->where('orderId', '[0-9]+');
    });
});
