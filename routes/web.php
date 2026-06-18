<?php

use Illuminate\Support\Facades\Route;

Route::prefix(config('observatory.route_prefix', 'observatory'))->group(function () {
    Route::get('/', function () {
        return view('observatory::dashboard');
    });
});
