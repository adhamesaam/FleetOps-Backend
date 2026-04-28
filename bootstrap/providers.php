<?php

use App\Providers\AppServiceProvider;
use Laravel\Sanctum\SanctumServiceProvider;

return [
    SanctumServiceProvider::class,
    App\Providers\AppServiceProvider::class,
    App\Providers\ModuleServiceProvider::class,
];
