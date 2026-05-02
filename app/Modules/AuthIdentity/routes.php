<?php

/**
 * @file: routes.php
 * @description: Auth & Identity Service Routes
 * @module: AuthIdentity
 * @author: Team Leader (Khalid)
 */

use Illuminate\Support\Facades\Route;
use App\Modules\AuthIdentity\Controllers\AuthController;
use App\Modules\AuthIdentity\Controllers\UserController;
use App\Modules\AuthIdentity\Controllers\RoleController;

Route::prefix('api/v1')->group(function () {

    // =====================================================================
    // Public Auth Routes (No Authentication Required)
    // =====================================================================
    Route::prefix('auth')->group(function () {

        // POST /api/v1/auth/login
        Route::post('/login', [AuthController::class, 'login'])
            ->name('auth.login')
            ->middleware('throttle:5,1');

        // POST /api/v1/auth/forgot-password
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
            ->name('auth.forgot-password')
            ->middleware('throttle:5,1');

        // POST /api/v1/auth/reset-password
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])
            ->name('auth.reset-password')
            ->middleware('throttle:5,1');
    });

    // =====================================================================
    // Protected Routes (Sanctum Auth Required)
    // =====================================================================
    Route::middleware('auth:sanctum')->group(function () {

        // ── Auth Actions ──────────────────────────────────────────────────

        // GET  /api/v1/auth/me
        Route::get('/auth/me', [AuthController::class, 'me'])
            ->name('auth.me');

        // POST /api/v1/auth/logout
        Route::post('/auth/logout', [AuthController::class, 'logout'])
            ->name('auth.logout');

        // POST /api/v1/auth/logout-all
        Route::post('/auth/logout-all', [AuthController::class, 'logoutAll'])
            ->name('auth.logout-all');

        // POST /api/v1/auth/refresh
        Route::post('/auth/refresh', [AuthController::class, 'refreshToken'])
            ->name('auth.refresh');

        // POST /api/v1/auth/change-password
        Route::post('/auth/change-password', [AuthController::class, 'changePassword'])
            ->name('auth.change-password');

        // ── User Management ───────────────────────────────────────────────

        Route::prefix('users')->group(function () {

            // Specialized routes MUST come before /{id} to avoid conflicts
            Route::get('/active', [UserController::class, 'active'])->name('users.active');
            Route::get('/role/drivers', [UserController::class, 'drivers'])->name('users.drivers');
            Route::get('/role/dispatchers', [UserController::class, 'dispatchers'])->name('users.dispatchers');
            Route::get('/role/fleet-managers', [UserController::class, 'fleetManagers'])->name('users.fleet-managers');
            Route::get('/role/mechanics', [UserController::class, 'mechanics'])->name('users.mechanics');

            // CRUD
            Route::get('/', [UserController::class, 'index'])->name('users.index');
            Route::post('/', [UserController::class, 'store'])->name('users.store');
            Route::get('/{id}', [UserController::class, 'show'])->name('users.show')->where('id', '[0-9]+');
            Route::put('/{id}', [UserController::class, 'update'])->name('users.update')->where('id', '[0-9]+');
            Route::delete('/{id}', [UserController::class, 'destroy'])->name('users.destroy')->where('id', '[0-9]+');
        });

        // ── Role & Permission Management (RBAC) ───────────────────────────

        // Route::prefix('roles')->group(function () {

        //     // Specialized routes first
        //     Route::get('/type/non-system', [RoleController::class, 'nonSystemRoles'])->name('roles.non-system');

        //     // CRUD
        //     Route::get('/',     [RoleController::class, 'index'])->name('roles.index');
        //     Route::post('/',    [RoleController::class, 'store'])->name('roles.store');
        //     Route::get('/{id}', [RoleController::class, 'show'])->name('roles.show')->where('id', '[0-9]+');
        //     Route::put('/{id}', [RoleController::class, 'update'])->name('roles.update')->where('id', '[0-9]+');
        //     Route::delete('/{id}', [RoleController::class, 'destroy'])->name('roles.destroy')->where('id', '[0-9]+');
        // });
    });
});
