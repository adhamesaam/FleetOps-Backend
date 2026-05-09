<?php

namespace Database\Seeders;

use App\Modules\RealtimeTracking\Models\GpsPing;
use App\Modules\RouteDispatch\Models\Route;
use Illuminate\Database\Seeder;

class GpsPingSeeder extends Seeder
{
    public function run(): void
    {
        $routes = Route::whereIn('status', ['Planned', 'Active', 'InProgress', 'In_progress'])
            ->orderBy('route_id')
            ->get();

        $anchors = [
            ['lat' => 30.0444, 'lng' => 31.2357],
            ['lat' => 30.0619, 'lng' => 31.3283],
            ['lat' => 29.9792, 'lng' => 31.1342],
            ['lat' => 30.0131, 'lng' => 31.2089],
            ['lat' => 30.1286, 'lng' => 31.2422],
            ['lat' => 30.0595, 'lng' => 31.2233],
        ];

        foreach ($routes as $routeIndex => $route) {
            if (!$route->driver_id) {
                continue;
            }

            GpsPing::where('route_id', $route->route_id)->delete();

            $anchor = $anchors[$routeIndex % count($anchors)];
            $points = $this->buildTrailPoints($anchor, (int) $route->route_id);
            $startedAt = now()->subMinutes(count($points) * 2);

            foreach ($points as $index => $point) {
                GpsPing::create([
                    'driver_id' => $route->driver_id,
                    'vehicle_id' => $route->vehicle_id,
                    'route_id' => $route->route_id,
                    'lat' => $point['lat'],
                    'lng' => $point['lng'],
                    'speed_kmh' => $index === count($points) - 1 ? 0 : mt_rand(24, 58),
                    'accuracy_m' => mt_rand(5, 18),
                    'heading' => $this->headingFor($points, $index),
                    'is_spoofed' => false,
                    'recorded_at' => (clone $startedAt)->addMinutes($index * 2),
                ]);
            }
        }
    }

    private function buildTrailPoints(array $anchor, int $routeId): array
    {
        $pointCount = 10;
        $radius = 0.018 + ($routeId % 5) * 0.004;
        $points = [];

        for ($i = 0; $i < $pointCount; $i++) {
            $angle = (($i + $routeId) / ($pointCount - 1)) * 1.35 * pi();
            $points[] = [
                'lat' => $anchor['lat'] + sin($angle) * $radius + ($i * 0.0012),
                'lng' => $anchor['lng'] + cos($angle) * $radius + ($i * 0.001),
            ];
        }

        return $points;
    }

    private function headingFor(array $points, int $index): float
    {
        if ($index >= count($points) - 1) {
            return 0.0;
        }

        $current = $points[$index];
        $next = $points[$index + 1];
        $angle = atan2($next['lng'] - $current['lng'], $next['lat'] - $current['lat']);

        return round(fmod(rad2deg($angle) + 360, 360), 2);
    }
}
