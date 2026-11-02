<?php

use App\Jobs\FetchWeatherData;
use App\Services\WeatherService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.weatherapi.key' => null]);
});

test('weather endpoint returns weather data', function () {
    Http::fake([
        'api.weatherapi.com/*' => Http::response([
            'location' => [
                'name' => 'Perth',
                'region' => 'Western Australia',
                'country' => 'Australia',
                'lat' => -31.9333,
                'lon' => 115.8333,
                'localtime' => '2026-11-02 18:08',
            ],
            'current' => [
                'temp_c' => 22.5,
                'feelslike_c' => 21.0,
                'humidity' => 65,
                'cloud' => 6,
                'condition' => [
                    'text' => 'Partly cloudy',
                    'icon' => '//cdn.weatherapi.com/weather/64x64/day/116.png',
                ],
                'wind_kph' => 18.5,
                'wind_dir' => 'W',
                'wind_degree' => 264,
                'pressure_mb' => 1018.0,
                'precip_mm' => 0.0,
                'vis_km' => 10.0,
                'uv' => 0.3,
            ],
        ]),
    ]);

    Cache::forget('weather_perth_au');

    config(['services.weatherapi.key' => 'test-api-key']);

    $response = $this->getJson('/api/weather');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'city',
                'region',
                'country',
                'latitude',
                'longitude',
                'localtime',
                'temperature',
                'temperature_unit',
                'feels_like',
                'humidity',
                'description',
                'cloud_cover',
                'wind_speed',
                'wind_speed_unit',
                'wind_direction',
                'wind_degree',
                'pressure_mb',
                'precipitation_mm',
                'visibility_km',
                'uv_index',
                'icon',
                'fetched_at',
            ],
        ])
        ->assertJsonPath('data.city', 'Perth')
        ->assertJsonPath('data.region', 'Western Australia')
        ->assertJsonPath('data.country', 'Australia')
        ->assertJsonPath('data.latitude', -31.9333)
        ->assertJsonPath('data.longitude', 115.8333)
        ->assertJsonPath('data.temperature', 22.5)
        ->assertJsonPath('data.temperature_unit', '°C')
        ->assertJsonPath('data.description', 'Partly cloudy')
        ->assertJsonPath('data.cloud_cover', 6)
        ->assertJsonPath('data.wind_direction', 'W')
        ->assertJsonPath('data.pressure_mb', 1018)
        ->assertJsonPath('data.precipitation_mm', 0)
        ->assertJsonPath('data.visibility_km', 10)
        ->assertJsonPath('data.uv_index', 0.3)
        ->assertJsonPath('data.icon', 'https://cdn.weatherapi.com/weather/64x64/day/116.png');

    Http::assertSentCount(1);
});

test('weather endpoint uses cached data on subsequent calls', function () {
    Http::fake([
        'api.weatherapi.com/*' => Http::response([
            'location' => [
                'name' => 'Perth',
                'region' => 'Western Australia',
                'country' => 'Australia',
                'lat' => -31.9333,
                'lon' => 115.8333,
                'localtime' => '2026-11-02 18:08',
            ],
            'current' => [
                'temp_c' => 22.5,
                'feelslike_c' => 21.0,
                'humidity' => 65,
                'cloud' => 6,
                'condition' => [
                    'text' => 'Partly cloudy',
                    'icon' => '//cdn.weatherapi.com/weather/64x64/day/116.png',
                ],
                'wind_kph' => 18.5,
                'wind_dir' => 'W',
                'wind_degree' => 264,
                'pressure_mb' => 1018.0,
                'precip_mm' => 0.0,
                'vis_km' => 10.0,
                'uv' => 0.3,
            ],
        ]),
    ]);

    Cache::forget('weather_perth_au');

    config(['services.weatherapi.key' => 'test-api-key']);

    // First call hits the API
    $this->getJson('/api/weather')->assertOk();

    // Second call should use cache
    $this->getJson('/api/weather')->assertOk();

    Http::assertSentCount(1);
});

test('weather endpoint handles api failure gracefully', function () {
    Http::fake([
        'api.weatherapi.com/*' => Http::response(['error' => ['code' => 2006, 'message' => 'API key is invalid.']], 401),
    ]);

    Cache::forget('weather_perth_au');

    config(['services.weatherapi.key' => 'test-api-key']);

    $response = $this->getJson('/api/weather');

    $response->assertStatus(503)
        ->assertJsonStructure(['message']);
});

test('weather endpoint returns 503 when api key is missing', function () {
    Cache::forget('weather_perth_au');

    config(['services.weatherapi.key' => null]);

    $response = $this->getJson('/api/weather');

    $response->assertStatus(503)
        ->assertJsonPath('message', 'WeatherAPI key is not configured. Set WEATHER_API_KEY in your .env file.');
});

test('fetch weather data job refreshes cache', function () {
    Http::fake([
        'api.weatherapi.com/*' => Http::response([
            'location' => [
                'name' => 'Perth',
                'region' => 'Western Australia',
                'country' => 'Australia',
                'lat' => -31.9333,
                'lon' => 115.8333,
                'localtime' => '2026-11-02 18:08',
            ],
            'current' => [
                'temp_c' => 25.0,
                'feelslike_c' => 24.0,
                'humidity' => 55,
                'cloud' => 0,
                'condition' => [
                    'text' => 'Sunny',
                    'icon' => '//cdn.weatherapi.com/weather/64x64/day/113.png',
                ],
                'wind_kph' => 12.0,
                'wind_dir' => 'W',
                'wind_degree' => 264,
                'pressure_mb' => 1018.0,
                'precip_mm' => 0.0,
                'vis_km' => 10.0,
                'uv' => 1.0,
            ],
        ]),
    ]);

    config(['services.weatherapi.key' => 'test-api-key']);

    Cache::forget('weather_perth_au');

    $job = new FetchWeatherData;
    $job->handle(app(WeatherService::class));

    expect(Cache::has('weather_perth_au'))->toBeTrue();
    expect(Cache::get('weather_perth_au')['temperature'])->toBe(25);

    Http::assertSentCount(1);
});

test('weather fetch job is scheduled hourly', function () {
    $schedule = app(Schedule::class);

    $scheduledEvent = collect($schedule->events())->first(function ($event) {
        return str_contains($event->description ?? '', FetchWeatherData::class);
    });

    expect($scheduledEvent)->not->toBeNull();
    expect($scheduledEvent->expression)->toBe('0 * * * *');
});
