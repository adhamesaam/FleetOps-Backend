<?php

/**
 * @file: routes.php
 * @description: Logging & Audit Service Routes (Complete)
 * @module: LoggingAudit
 * @author: Team Leader (Khalid)
 */

use Illuminate\Support\Facades\Route;
use App\Modules\LoggingAudit\Controllers\AuditLogController;
use App\Modules\LoggingAudit\Controllers\SystemLogController;

Route::prefix('s')->middleware('auth:sanctum')->group(function () {

    // ══════════════════════════════════════════════════════════════════════════
    // Audit Logs (LA-01 / fn37) — Immutable, Read-Only
    // ══════════════════════════════════════════════════════════════════════════

    Route::prefix('logs')->group(function () {
        // Specialized routes first
        Route::get('/export', [AuditLogController::class, 'export'])->name('audit.logs.export');

        // List + Filter
        Route::get('/', [AuditLogController::class, 'index'])->name('audit.logs.index');
    });

    // Entity-specific audit trail
    Route::get('/entity/{entityType}/{entityId}', [AuditLogController::class, 'entityTrail'])
        ->name('audit.entity.trail')
        ->where('entityId', '[0-9]+');

    // ══════════════════════════════════════════════════════════════════════════
    // System Logs (LA-02) — Structured technical logs
    // ══════════════════════════════════════════════════════════════════════════

    Route::prefix('system-logs')->group(function () {
        // Specialized routes first
        Route::get('/errors', [SystemLogController::class, 'errors'])->name('audit.system-logs.errors');
        Route::get('/stats', [SystemLogController::class, 'stats'])->name('audit.system-logs.stats');
        Route::get('/channel/{channel}', [SystemLogController::class, 'byChannel'])->name('audit.system-logs.by-channel');

        // List + Filter
        Route::get('/', [SystemLogController::class, 'index'])->name('audit.system-logs.index');
    });
});
