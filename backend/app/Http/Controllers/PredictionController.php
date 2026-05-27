<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\EnergyReading;
use App\Services\WeatherData;

class PredictionController extends Controller
{
    private string $mlUrl;

    public function __construct()
    {
        $this->mlUrl = config('services.ml_api.url', env('ML_API_URL', 'http://localhost:8001'));
    }

    /** Solar prediction for next 24 hours */
    public function solar()
    {
        $features = $this->getLatestFeatures();

        if ($this->shouldCallMlApi()) {
            try {
                $response = Http::timeout(2)->post("{$this->mlUrl}/predict/solar", $features);
                if ($response->successful()) {
                    return response()->json($response->json());
                }
            } catch (\Exception $e) {
                // Fall back to simulation
            }
        }

        return response()->json($this->simulateSolarForecast());
    }

    /** Wind prediction for next 24 hours */
    public function wind()
    {
        $features = $this->getLatestFeatures();

        if ($this->shouldCallMlApi()) {
            try {
                $response = Http::timeout(2)->post("{$this->mlUrl}/predict/wind", $features);
                if ($response->successful()) {
                    return response()->json($response->json());
                }
            } catch (\Exception $e) {
                // Fall back to simulation
            }
        }

        return response()->json($this->simulateWindForecast());
    }

    /** Weekly forecast (7 days) */
    public function weekly()
    {
        if ($this->shouldCallMlApi()) {
            try {
                $response = Http::timeout(2)->get("{$this->mlUrl}/predict/weekly");
                if ($response->successful()) {
                    return response()->json($response->json());
                }
            } catch (\Exception $e) {
                // Fall back
            }
        }

        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        return response()->json(collect($days)->map(fn($d) => [
            'day'       => $d,
            'solar_kwh' => round(rand(45, 83), 1),
            'wind_kwh'  => round(rand(28, 48), 1),
            'confidence'=> round(rand(88, 97), 1),
        ]));
    }

    private function getLatestFeatures(): array
    {
        try {
            $reading = EnergyReading::latest()->first();
        } catch (\Exception $e) {
            $reading = null;
        }

        try {
            $weather = WeatherData::current();
        } catch (\Exception $e) {
            $weather = WeatherData::fallback();
        }

        return [
            'temperature'  => $reading?->temperature ?? $weather['temperature'],
            'humidity'     => $weather['humidity'],
            'wind_speed'   => $reading?->wind_speed ?? $weather['wind_speed'],
            'solar_irr'    => $weather['solar_irr'],
            'hour'         => now()->hour,
            'day_of_year'  => now()->dayOfYear,
        ];
    }

    private function shouldCallMlApi(): bool
    {
        $host = parse_url($this->mlUrl, PHP_URL_HOST);
        $port = (int) parse_url($this->mlUrl, PHP_URL_PORT);

        return ! in_array($host, ['localhost', '127.0.0.1'], true) || $port !== 8000;
    }

    private function simulateSolarForecast(): array
    {
        return collect(range(0, 23))->map(fn($h) => [
            'hour'       => str_pad($h, 2, '0', STR_PAD_LEFT) . ':00',
            'solar_kw'   => $h >= 6 && $h <= 19
                ? round(max(0, sin((($h - 6) / 13) * M_PI) * 8.5), 1)
                : 0,
            'confidence' => round(rand(88, 96), 1),
        ])->toArray();
    }

    private function simulateWindForecast(): array
    {
        return collect(range(0, 23))->map(fn($h) => [
            'hour'       => str_pad($h, 2, '0', STR_PAD_LEFT) . ':00',
            'wind_kw'    => round(2.5 + sin($h * 0.4) * 1.8, 1),
            'confidence' => round(rand(85, 95), 1),
        ])->toArray();
    }
}
