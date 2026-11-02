<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    private const CACHE_KEY = 'weather_perth_au';

    private const CACHE_TTL_SECONDS = 900; // 15 minutes

    /**
     * Get the current weather data for Perth, Australia.
     *
     * Returns cached data when available, otherwise fetches from WeatherAPI.com.
     *
     * @return array<string, mixed>
     */
    public function getWeather(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function () {
            return $this->fetchFromApi();
        });
    }

    /**
     * Fetch weather data from WeatherAPI.com.
     *
     * @return array<string, mixed>
     *
     * @throws \RuntimeException
     */
    public function fetchFromApi(): array
    {
        $apiKey = config('services.weatherapi.key');

        if (empty($apiKey)) {
            throw new \RuntimeException('WeatherAPI key is not configured. Set WEATHER_API_KEY in your .env file.');
        }

        try {
            $response = Http::timeout(10)
                ->retry(3, 100)
                ->get('https://api.weatherapi.com/v1/current.json', [
                    'key' => $apiKey,
                    'q' => 'Perth',
                    'aqi' => 'no',
                ])
                ->throw();

            $data = $response->json();
            $location = $data['location'] ?? [];
            $current = $data['current'] ?? [];
            $condition = $current['condition'] ?? [];

            return [
                'city' => $location['name'] ?? 'Perth',
                'region' => $location['region'] ?? 'Western Australia',
                'country' => $location['country'] ?? 'Australia',
                'latitude' => isset($location['lat']) ? (float) $location['lat'] : null,
                'longitude' => isset($location['lon']) ? (float) $location['lon'] : null,
                'localtime' => $location['localtime'] ?? null,
                'temperature' => $current['temp_c'] ?? null,
                'temperature_unit' => '°C',
                'feels_like' => $current['feelslike_c'] ?? null,
                'humidity' => $current['humidity'] ?? null,
                'description' => $condition['text'] ?? null,
                'cloud_cover' => $current['cloud'] ?? null,
                'wind_speed' => $current['wind_kph'] ?? null,
                'wind_speed_unit' => 'km/h',
                'wind_direction' => $current['wind_dir'] ?? null,
                'wind_degree' => $current['wind_degree'] ?? null,
                'pressure_mb' => isset($current['pressure_mb']) ? (float) $current['pressure_mb'] : null,
                'precipitation_mm' => isset($current['precip_mm']) ? (float) $current['precip_mm'] : 0.0,
                'visibility_km' => isset($current['vis_km']) ? (float) $current['vis_km'] : null,
                'uv_index' => isset($current['uv']) ? (float) $current['uv'] : null,
                'icon' => $this->formatIconUrl($condition['icon'] ?? null),
                'fetched_at' => now()->toIso8601String(),
            ];
        } catch (ConnectionException $e) {
            Log::error('Weather API connection failed', ['error' => $e->getMessage()]);

            throw new \RuntimeException('Unable to connect to the weather service. Please try again later.');
        } catch (RequestException $e) {
            Log::error('Weather API request failed', [
                'status' => $e->response->status(),
                'body' => $e->response->body(),
            ]);

            throw new \RuntimeException('Weather service returned an error. Please try again later.');
        }
    }

    /**
     * Clear the cached weather data.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Format the icon URL to ensure it is fully qualified with an https:// scheme.
     */
    protected function formatIconUrl(?string $icon): ?string
    {
        if (! $icon) {
            return null;
        }

        if (str_starts_with($icon, '//')) {
            return 'https:'.$icon;
        }

        if (! str_starts_with($icon, 'http://') && ! str_starts_with($icon, 'https://')) {
            return 'https://'.ltrim($icon, '/');
        }

        return $icon;
    }
}
