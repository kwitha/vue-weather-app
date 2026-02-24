<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WeatherController extends Controller
{
    public function current(Request $request)
    {
        $request->validate(['city' => 'required|string']);
        $city = $request->city;
        $apiKey = env('OPENWEATHER_KEY');

        $response = Http::get('https://api.openweathermap.org/data/2.5/weather', [
            'q' => $city,
            'appid' => $apiKey,
            'units' => 'metric',
        ]);

        return $response->json();
    }

    public function forecast(Request $request)
    {
        $request->validate(['city' => 'required|string']);
        $city = $request->city;
        $apiKey = env('OPENWEATHER_KEY');

        $response = Http::get('https://api.openweathermap.org/data/2.5/forecast', [
            'q' => $city,
            'appid' => $apiKey,
            'units' => 'metric',
        ]);

        return $response->json();
    }
}