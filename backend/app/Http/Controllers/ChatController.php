<?php

namespace App\Http\Controllers;

use App\Models\EnergyReading;
use App\Services\WeatherData;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function send(Request $request)
    {
        $request->validate(['message' => 'required|string|max:1000']);

        $user = $request->user();

        if ($user) {
            \App\Models\ChatMessage::create([
                'user_id' => $user->id,
                'role' => 'user',
                'message' => $request->message,
            ]);
        }

        $reply = $this->generateReply($request->message);

        if ($user) {
            \App\Models\ChatMessage::create([
                'user_id' => $user->id,
                'role' => 'assistant',
                'message' => $reply,
            ]);
        }

        return response()->json(['reply' => $reply]);
    }

    public function history(Request $request)
    {
        $history = \App\Models\ChatMessage::where('user_id', $request->user()->id)
            ->orderBy('created_at')
            ->take(50)
            ->get(['role', 'message', 'created_at']);

        return response()->json($history);
    }

    private function generateReply(string $msg): string
    {
        $msg = strtolower($msg);
        $data = $this->dashboardSnapshot();

        if (str_contains($msg, 'solar')) {
            return "Solar is generating {$data['solar_kw']} kW right now with irradiance around {$data['solar_irr']} W/m2. Best high-load window is still 10 AM-2 PM when solar production is strongest.";
        }
        if (str_contains($msg, 'wind')) {
            return "Current wind speed is {$data['wind_speed']} km/h and estimated turbine output is {$data['wind_kw']} kW. If wind stays above 12 km/h, battery charging from wind becomes useful.";
        }
        if (str_contains($msg, 'battery')) {
            return "Battery state of charge is {$data['battery_soc']}%. With demand near {$data['demand_kw']} kW, keep battery reserve for the evening peak and charge more during strong solar hours.";
        }
        if (str_contains($msg, 'saving') || str_contains($msg, 'cost') || str_contains($msg, 'bill')) {
            return "Current renewable generation is {$data['total_kw']} kW against {$data['demand_kw']} kW demand. Run flexible loads when generation exceeds demand to reduce grid import and improve savings.";
        }
        if (str_contains($msg, 'co2') || str_contains($msg, 'carbon')) {
            return "Your CO2 offset is about {$data['co2_saved']} kg this month, roughly equivalent to planting 13 trees.";
        }
        if (str_contains($msg, 'weather') || str_contains($msg, 'temperature')) {
            return "Live weather: {$data['temperature']}°C, humidity {$data['humidity']}%, wind {$data['wind_speed']} km/h, and {$data['description']}. These conditions are being used for the dashboard estimates.";
        }
        if (str_contains($msg, 'optimize') || str_contains($msg, 'recommend') || str_contains($msg, 'schedule')) {
            return "Recommendation: prioritize washing, EV charging, or other flexible loads while solar is near {$data['solar_kw']} kW. Avoid adding load during the evening demand peak unless battery SOC is above 80%.";
        }

        return "Right now solar is {$data['solar_kw']} kW, wind is {$data['wind_kw']} kW, demand is {$data['demand_kw']} kW, and battery is {$data['battery_soc']}%. Ask me about solar, wind, battery, savings, weather, or optimization for a specific recommendation.";
    }

    private function dashboardSnapshot(): array
    {
        try {
            $weather = WeatherData::current();
        } catch (\Exception $e) {
            $weather = WeatherData::fallback();
        }

        try {
            $reading = EnergyReading::latest()->first();
        } catch (\Exception $e) {
            $reading = null;
        }

        $hour = now()->hour;
        $solar = $reading?->solar_kw ?? ($weather['is_day']
            ? round(max(0, ($weather['solar_irr'] / 1000) * 8.5 * (1 - ($weather['cloud_cover'] / 180))), 1)
            : 0);
        $wind = $reading?->wind_kw ?? round(max(0, min(9.5, pow(max(0, $weather['wind_speed'] - 3), 3) / 180)), 1);
        $demand = $reading?->demand_kw ?? round(3.5 + ($hour >= 18 && $hour <= 23 ? 2.5 : 0) + ($weather['temperature'] > 30 ? 1.6 : 0), 1);

        return [
            'solar_kw' => round($solar, 1),
            'wind_kw' => round($wind, 1),
            'total_kw' => round($solar + $wind, 1),
            'demand_kw' => round($demand, 1),
            'battery_soc' => round($reading?->battery_soc ?? 74),
            'co2_saved' => round($reading?->co2_saved ?? 284),
            'temperature' => round($weather['temperature'], 1),
            'humidity' => round($weather['humidity']),
            'wind_speed' => round($weather['wind_speed'], 1),
            'solar_irr' => round($weather['solar_irr']),
            'description' => $weather['description'] ?? 'live weather',
        ];
    }
}
