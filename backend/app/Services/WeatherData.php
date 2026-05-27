<?php

namespace App\Services;

class WeatherData
{
    public static function current(): array
    {
        $hourly = [];

        try {
            $hourly = self::openMeteo()['hourly'];
        } catch (\Exception $e) {
            $hourly = [];
        }

        if (env('OPENWEATHER_API_KEY')) {
            try {
                return self::openWeather($hourly);
            } catch (\Exception $e) {
                // Fall back to Open-Meteo if OpenWeather is temporarily unavailable.
            }
        }

        return self::openMeteo();
    }

    private static function openMeteo(): array
    {
        $lat = (float) env('WEATHER_LATITUDE', 28.6139);
        $lon = (float) env('WEATHER_LONGITUDE', 77.2090);

        $data = self::getJson('https://api.open-meteo.com/v1/forecast', [
            'latitude' => $lat,
            'longitude' => $lon,
            'current' => implode(',', [
                'temperature_2m',
                'relative_humidity_2m',
                'wind_speed_10m',
                'pressure_msl',
                'uv_index',
                'shortwave_radiation',
                'cloud_cover',
                'is_day',
            ]),
            'hourly' => 'shortwave_radiation,wind_speed_10m,temperature_2m,relative_humidity_2m',
            'forecast_days' => 2,
            'timezone' => 'auto',
            'wind_speed_unit' => 'kmh',
        ]);
        $current = $data['current'] ?? [];

        return [
            'temperature' => self::num($current['temperature_2m'] ?? null, 32),
            'humidity' => self::num($current['relative_humidity_2m'] ?? null, 64),
            'wind_speed' => self::num($current['wind_speed_10m'] ?? null, 19),
            'pressure' => self::num($current['pressure_msl'] ?? null, 1012),
            'uv_index' => self::num($current['uv_index'] ?? null, 7),
            'solar_irr' => self::num($current['shortwave_radiation'] ?? null, 0),
            'cloud_cover' => self::num($current['cloud_cover'] ?? null, 40),
            'is_day' => (int) ($current['is_day'] ?? 0),
            'description' => 'live weather model',
            'hourly' => self::hourly($data['hourly'] ?? []),
        ];
    }

    private static function openWeather(array $hourly): array
    {
        $data = self::getJson('https://api.openweathermap.org/data/2.5/weather', [
            'q' => env('WEATHER_CITY', 'New Delhi'),
            'appid' => env('OPENWEATHER_API_KEY'),
            'units' => 'metric',
        ]);

        $cloudCover = self::num($data['clouds']['all'] ?? null, 0);
        $timestamp = (int) ($data['dt'] ?? time());
        $sunrise = (int) ($data['sys']['sunrise'] ?? 0);
        $sunset = (int) ($data['sys']['sunset'] ?? 0);
        $isDay = $timestamp >= $sunrise && $timestamp <= $sunset;

        return [
            'temperature' => self::num($data['main']['temp'] ?? null, 32),
            'humidity' => self::num($data['main']['humidity'] ?? null, 64),
            'wind_speed' => round(self::num($data['wind']['speed'] ?? null, 0) * 3.6, 1),
            'pressure' => self::num($data['main']['pressure'] ?? null, 1012),
            'uv_index' => $isDay ? 7 : 0,
            'solar_irr' => $isDay ? round(max(0, (1 - ($cloudCover / 100)) * 850), 1) : 0,
            'cloud_cover' => $cloudCover,
            'is_day' => $isDay ? 1 : 0,
            'description' => 'openweather: ' . ($data['weather'][0]['description'] ?? 'live weather'),
            'hourly' => $hourly,
        ];
    }

    public static function fallback(): array
    {
        $isDay = now()->hour >= 6 && now()->hour <= 18 ? 1 : 0;

        return [
            'temperature' => 32,
            'humidity' => 64,
            'wind_speed' => 19,
            'pressure' => 1012,
            'uv_index' => $isDay ? 7 : 0,
            'solar_irr' => $isDay ? 780 : 0,
            'cloud_cover' => 35,
            'is_day' => $isDay,
            'description' => 'fallback simulation',
            'hourly' => [],
        ];
    }

    private static function hourly(array $hourly): array
    {
        $times = $hourly['time'] ?? [];

        return collect($times)->map(fn ($time, $i) => [
            'time' => $time,
            'solar_irr' => self::num($hourly['shortwave_radiation'][$i] ?? null, 0),
            'wind_speed' => self::num($hourly['wind_speed_10m'][$i] ?? null, 0),
            'temperature' => self::num($hourly['temperature_2m'][$i] ?? null, 0),
            'humidity' => self::num($hourly['relative_humidity_2m'][$i] ?? null, 0),
        ])->toArray();
    }

    private static function num(mixed $value, float $fallback): float
    {
        return is_numeric($value) ? (float) $value : $fallback;
    }

    private static function getJson(string $url, array $query): array
    {
        $ch = curl_init($url . '?' . http_build_query($query));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_FAILONERROR => false,
            CURLOPT_PROXY => '',
            CURLOPT_NOPROXY => '*',
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false || $status < 200 || $status >= 300) {
            throw new \RuntimeException($error ?: "Open-Meteo returned HTTP {$status}");
        }

        $data = json_decode($body, true);
        if (! is_array($data)) {
            throw new \RuntimeException('Open-Meteo returned invalid JSON');
        }

        return $data;
    }
}
