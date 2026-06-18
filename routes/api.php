<?php

use Illuminate\Support\Facades\Route;
use Performance\Observatory\Controllers\ObservatoryController;

Route::prefix(config('observatory.route_prefix', 'observatory'))->group(function () {
    Route::get('api/requests', [ObservatoryController::class, 'index']);
    Route::get('api/requests/{id}', [ObservatoryController::class, 'show']);
    Route::post('api/frontend-metrics', [ObservatoryController::class, 'storeFrontendMetrics']);
});
