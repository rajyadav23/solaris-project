<?php

namespace App\Http\Controllers;

class OptimizationController extends Controller
{
    public function metrics()
    {
        return response()->json([
            'self_consumption' => 78,
            'grid_independence' => 63,
            'battery_efficiency' => 91,
            'peak_shaving' => 55,
            'co2_offset_kg' => 284,
            'monthly_savings' => 4250,
        ]);
    }

    public function schedule()
    {
        return response()->json([
            ['name' => 'EV Charging', 'time' => '10:00-13:00', 'kw' => 7.4, 'status' => 'optimal', 'saving' => 'Rs. 120'],
            ['name' => 'Washing Machine', 'time' => '11:00-12:30', 'kw' => 2.1, 'status' => 'scheduled', 'saving' => 'Rs. 35'],
            ['name' => 'Air Conditioner', 'time' => 'Reduce 17:00-20:00', 'kw' => 1.8, 'status' => 'caution', 'saving' => 'Rs. 45'],
            ['name' => 'Battery Storage', 'time' => '08:00-15:00', 'kw' => 5.0, 'status' => 'active', 'saving' => 'Rs. 80'],
        ]);
    }
}
