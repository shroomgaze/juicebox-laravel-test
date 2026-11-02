<?php

namespace App\Jobs;

use App\Services\WeatherService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchWeatherData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Execute the job.
     */
    public function handle(WeatherService $weatherService): void
    {
        try {
            $weatherService->clearCache();
            $weatherService->getWeather();

            Log::info('Weather data fetched and cached successfully.');
        } catch (\RuntimeException $e) {
            Log::warning('Failed to fetch weather data', ['error' => $e->getMessage()]);

            throw $e;
        }
    }
}
