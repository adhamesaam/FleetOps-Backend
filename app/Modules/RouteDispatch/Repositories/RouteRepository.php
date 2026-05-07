<?php

/**
 * @file: RouteRepository.php
 * @description: مستودع بيانات المسارات - Route & Dispatch Service
 * @module: RouteDispatch
 * @author: Team Leader (Khalid)
 */

namespace App\Modules\RouteDispatch\Repositories;

use App\Modules\Shared\Repositories\BaseRepository;
use App\Modules\RouteDispatch\Models\Route;
use Illuminate\Database\Eloquent\Collection;

class RouteRepository extends BaseRepository
{
    public function __construct(Route $model)
    {
        parent::__construct($model);
    }

    public function getActiveRoutes(): Collection
    {
        // TODO: return $this->model->active()->with(['vehicle', 'stops'])->get();
    }

    public function getDriverActiveRoute(int $driverId): ?Route
    {
        return
         $this->model->where('status','!=','completed')
         ->where('status','!=','cancelled')
         ->where('driver_id', $driverId)->with('stops')->first();
    }

    public function getRouteWithStops(int $routeId): ?Route
    {
        // TODO: return $this->model->with(['stops'])->find($routeId);
    }

    public function incrementVersion(int $routeId): bool
    {
        // TODO: $this->model->where('route_id', $routeId)->increment('version');
        // return true;
    }

    public function getDriverRoutes(int $driverId): Collection
    {
        return $this->model->where('status','!=','completed')->where('status','!=','cancelled')->where('driver_id', $driverId)->with('stops')->get();
    }   
}
