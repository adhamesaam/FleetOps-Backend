<?php

/**
 * @file: routes.php
 * @description: Notification Service Routes
 * @module: Notification
 * @author: Team Leader (Khalid)
 */

use Illuminate\Support\Facades\Route;
use App\Modules\Notification\Controllers\NotificationController;

Route::prefix('api/v1/notifications')->middleware('auth:sanctum')->group(function () {

    // Preferences (must come before /{id})
    Route::get('/preferences', [NotificationController::class, 'getPreferences'])->name('notifications.preferences.get');
    Route::put('/preferences', [NotificationController::class, 'updatePreferences'])->name('notifications.preferences.update');
    Route::post('/fcm-token', [NotificationController::class, 'updateFcmToken'])->name('notifications.fcm-token');

    // CRUD
    Route::get('/', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/{id}', [NotificationController::class, 'show'])->name('notifications.show')->where('id', '[0-9]+');
});
