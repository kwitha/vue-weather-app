<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WeatherController;

// Weather API
Route::get('/weather/current', [WeatherController::class, 'current']);
Route::get('/weather/forecast', [WeatherController::class, 'forecast']);