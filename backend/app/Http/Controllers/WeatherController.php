<?php

namespace App\Http\Controllers;

use App\Services\WeatherData;

class WeatherController extends Controller
{
    private string $apiKey;
    private string $city;

    public function __construct()
    {
        $this->apiKey = env('OPENWEATHER_API_KEY', '');
        $this->city = env('WEATHER_CITY', 'New Delhi');
    }

    public function current()
    {
        if ($this->apiKey) {
            try {
                $d = $this->getJson('https://api.openweathermap.org/data/2.5/weather', [
                    'q' => $this->city,
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                ]);

                $cloudCover = $d['clouds']['all'] ?? 0;
                $isDay = (($d['dt'] ?? time()) >= ($d['sys']['sunrise'] ?? 0))
                    && (($d['dt'] ?? time()) <= ($d['sys']['sunset'] ?? 0));

                return response()->json([
                    'temperature' => $d['main']['temp'],
                    'humidity' => $d['main']['humidity'],
                    'wind_speed' => round(($d['wind']['speed'] ?? 0) * 3.6, 1),
                    'pressure' => $d['main']['pressure'],
                    'uv_index' => $isDay ? 7 : 0,
                    'solar_irr' => $isDay ? round(max(0, (1 - ($cloudCover / 100)) * 850), 1) : 0,
                    'cloud_cover' => $cloudCover,
                    'is_day' => $isDay ? 1 : 0,
                    'description' => 'openweather: ' . ($d['weather'][0]['description'] ?? 'live weather'),
                ]);
            } catch (\Exception $e) {
            }
        }

        try {
            $weather = WeatherData::current();
        } catch (\Exception $e) {
            $weather = WeatherData::fallback();
            $weather['fallback_reason'] = $e->getMessage();
        }

        unset($weather['hourly']);

        return response()->json($weather);
    }

    public function forecast()
    {
        if ($this->apiKey) {
            try {
                $data = $this->getJson('https://api.openweathermap.org/data/2.5/forecast', [
                    'q' => $this->city,
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                    'cnt' => 40,
                ]);

                return response()->json($data['list']);
            } catch (\Exception $e) {
            }
        }

        try {
            return response()->json(WeatherData::current()['hourly']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Live forecast unavailable']);
        }
    }

    private function getJson(string $url, array $query): array
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
            throw new \RuntimeException($error ?: "OpenWeather returned HTTP {$status}");
        }

        $data = json_decode($body, true);
        if (! is_array($data)) {
            throw new \RuntimeException('OpenWeather returned invalid JSON');
        }

        return $data;
    }
}
