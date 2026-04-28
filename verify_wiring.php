<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controllers = [
    \App\Modules\AuthIdentity\Controllers\AuthController::class,
    \App\Modules\LoggingAudit\Controllers\SystemLogController::class, // Checked correct name? Let's check below
    \App\Modules\Maintenance\Controllers\VehicleController::class,
    \App\Modules\Notification\Controllers\NotificationController::class,
    \App\Modules\OrderManagement\Controllers\OrderController::class,
    \App\Modules\RealtimeTracking\Controllers\LocationController::class,
    \App\Modules\ReportingAnalytics\Controllers\DashboardController::class,
    \App\Modules\RouteDispatch\Controllers\RouteController::class,
];

foreach ($controllers as $controller) {
    try {
        if (!class_exists($controller)) {
            echo 'WARNING: Class does not exist: ' . $controller . PHP_EOL;
            continue;
        }
        app()->make($controller);
        echo 'SUCCESS: ' . class_basename($controller) . PHP_EOL;
    } catch (\Throwable $e) {
        echo 'ERROR: ' . class_basename($controller) . ' - ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
    }
}
