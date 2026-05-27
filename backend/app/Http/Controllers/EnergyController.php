<?php

namespace App\Http\Controllers;

use App\Models\EnergyReading;
use App\Services\WeatherData;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EnergyController extends Controller
{
    /** Latest reading for dashboard KPIs */
    public function current()
    {
        try {
            $reading = EnergyReading::latest()->first();
        } catch (\Exception $e) {
            $reading = null;
        }

        if (! $reading) {
            return response()->json($this->simulateCurrent());
        }

        return response()->json([
            'solar_kw'    => $reading->solar_kw,
            'wind_kw'     => $reading->wind_kw,
            'demand_kw'   => $reading->demand_kw,
            'battery_soc' => $reading->battery_soc,
            'temperature' => $reading->temperature,
            'wind_speed'  => $reading->wind_speed,
            'co2_saved'   => $reading->co2_saved ?? 284,
            'created_at'  => $reading->created_at,
        ]);
    }

    /** 24-hour hourly breakdown */
    public function hourly()
    {
        try {
            $readings = EnergyReading::where('created_at', '>=', Carbon::now()->subDay())
                ->orderBy('created_at')
                ->get(['solar_kw', 'wind_kw', 'demand_kw', 'created_at']);
        } catch (\Exception $e) {
            $readings = collect();
        }

        if ($readings->isEmpty()) {
            return response()->json($this->simulateHourly());
        }
        return response()->json($readings);
    }

    /** Last 7 days daily totals */
    public function daily()
    {
        try {
            $data = EnergyReading::selectRaw(
                'DATE(created_at) as date,
                 AVG(solar_kw) as solar_avg,
                 AVG(wind_kw) as wind_avg,
                 SUM(solar_kw) as solar_total,
                 SUM(wind_kw) as wind_total'
            )
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        } catch (\Exception $e) {
            $data = collect();
        }

        return response()->json($data);
    }

    /** Store a new sensor reading */
    public function store(Request $request)
    {
        $request->validate([
            'solar_kw'  => 'required|numeric|min:0',
            'wind_kw'   => 'required|numeric|min:0',
            'demand_kw' => 'required|numeric|min:0',
            'battery_soc' => 'nullable|numeric|between:0,100',
            'temperature' => 'nullable|numeric',
            'wind_speed'  => 'nullable|numeric|min:0',
        ]);

        $reading = EnergyReading::create([
            'user_id'     => $request->user()->id,
            'solar_kw'    => $request->solar_kw,
            'wind_kw'     => $request->wind_kw,
            'demand_kw'   => $request->demand_kw,
            'battery_soc' => $request->battery_soc ?? 75,
            'temperature' => $request->temperature ?? 30,
            'wind_speed'  => $request->wind_speed ?? 15,
        ]);

        return response()->json($reading, 201);
    }

    // ── Simulation helpers (used when DB is empty) ──

    private function simulateCurrent(): array
    {
        try {
            $weather = WeatherData::current();
        } catch (\Exception $e) {
            $weather = WeatherData::fallback();
        }

        $h = now()->hour;
        $solar = $weather['is_day']
            ? round(max(0, ($weather['solar_irr'] / 1000) * 8.5 * (1 - ($weather['cloud_cover'] / 180))), 1)
            : 0;
        $wind = round(max(0, min(9.5, pow(max(0, $weather['wind_speed'] - 3), 3) / 180)), 1);
        $demand = round(3.5 + ($h >= 18 && $h <= 23 ? 2.5 : 0) + ($weather['temperature'] > 30 ? 1.6 : 0), 1);

        return [
            'solar_kw'    => $solar,
            'wind_kw'     => $wind,
            'demand_kw'   => $demand,
            'battery_soc' => 74,
            'co2_saved'   => 284,
        ];
    }

    private function simulateHourly(): array
    {
        try {
            $liveHours = collect(WeatherData::current()['hourly'])
                ->filter(fn ($row) => Carbon::parse($row['time'])->gte(now()->startOfDay()))
                ->take(24)
                ->values();
        } catch (\Exception $e) {
            $liveHours = collect();
        }

        if ($liveHours->isNotEmpty()) {
            return $liveHours->map(function ($row) {
                $time = Carbon::parse($row['time']);
                $solar = round(max(0, ($row['solar_irr'] / 1000) * 8.5), 1);
                $wind = round(max(0, min(9.5, pow(max(0, $row['wind_speed'] - 3), 3) / 180)), 1);
                $demand = round(3.5 + ($time->hour >= 18 && $time->hour <= 23 ? 2.5 : 0) + ($row['temperature'] > 30 ? 1.6 : 0), 1);

                return [
                    'hour'      => $time->format('H:00'),
                    'solar_kw'  => $solar,
                    'wind_kw'   => $wind,
                    'demand_kw' => $demand,
                ];
            })->toArray();
        }

        return collect(range(0, 23))->map(function ($h) {
            $solar = $h >= 6 && $h <= 19 ? round(max(0, sin((($h - 6) / 13) * M_PI) * 8.5), 1) : 0;
            return [
                'hour'      => str_pad($h, 2, '0', STR_PAD_LEFT) . ':00',
                'solar_kw'  => $solar,
                'wind_kw'   => round(2.5 + sin($h * 0.4) * 1.8, 1),
                'demand_kw' => round(3.5 + ($h >= 18 && $h <= 23 ? 2.5 : 0), 1),
            ];
        })->toArray();
    }
}
