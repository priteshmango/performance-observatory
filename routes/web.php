<?php

use Illuminate\Support\Facades\Route;

Route::prefix(config('observatory.route_prefix', 'observatory'))->group(function () {
    Route::get('/', function () {
        return view('observatory::dashboard');
    });
});

Route::get('vendor/observatory/tracker.js', function () {
    $path = __DIR__ . '/../resources/js/tracker.js';
    return response(file_get_contents($path), 200, [
        'Content-Type' => 'application/javascript',
    ]);
});
