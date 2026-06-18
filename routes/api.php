<?php

use Illuminate\Support\Facades\Route;
use Performance\Observatory\Controllers\ObservatoryController;

Route::prefix(config('observatory.route_prefix', 'observatory') . '/api')
    ->middleware(config('observatory.middleware', []))
    ->group(function () {
        Route::get('/requests', [ObservatoryController::class, 'index']);
        Route::get('/requests/{id}', [ObservatoryController::class, 'show']);
        
        // Static Analysis Routes
        Route::get('/scan/server', [ObservatoryController::class, 'scanServer']);
        Route::get('/scan/database', [ObservatoryController::class, 'scanDatabase']);
        Route::get('/scan/backend', [ObservatoryController::class, 'scanBackend']);
        Route::get('/scan/frontend', [ObservatoryController::class, 'scanFrontend']);
        
        Route::post('/frontend-metrics', [ObservatoryController::class, 'storeFrontendMetrics']);
    });
