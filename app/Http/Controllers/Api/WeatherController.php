<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;

/**
 * @tags Weather
 */
class WeatherController extends Controller
{
    /**
     * Get current weather.
     *
     * Returns current weather data for Perth, Australia.
     * Data is cached for 15 minutes to minimise external API calls.
     * A background job refreshes the cache hourly.
     */
    public function index(WeatherService $weatherService): JsonResponse
    {
        try {
            $weatherData = $weatherService->getWeather();

            return response()->json([
                'data' => $weatherData,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 503);
        }
    }
}
