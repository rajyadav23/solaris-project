<?php

namespace App\Http\Controllers;

class RecommendationController extends Controller
{
    public function index()
    {
        return response()->json([
            [
                'id' => 1,
                'type' => 'solar',
                'title' => 'Peak Solar Window',
                'desc' => '10:00-14:00 today. Run high-load devices now for maximum self-consumption.',
                'severity' => 'high',
                'saving' => 'Rs. 180',
                'icon' => 'solar',
            ],
            [
                'id' => 2,
                'type' => 'wind',
                'title' => 'Wind Surge Tonight',
                'desc' => 'Wind forecast 22-28 km/h after 8 PM. Ideal for overnight battery storage.',
                'severity' => 'medium',
                'saving' => 'Rs. 95',
                'icon' => 'wind',
            ],
            [
                'id' => 3,
                'type' => 'grid',
                'title' => 'Grid Export Opportunity',
                'desc' => 'Excess 12 kWh predicted. Export now at Rs. 3.85/kWh feed-in tariff.',
                'severity' => 'high',
                'saving' => 'Rs. 240',
                'icon' => 'grid',
            ],
            [
                'id' => 4,
                'type' => 'battery',
                'title' => 'Battery Optimization',
                'desc' => 'Charge to 90% before 6 PM to buffer evening demand peak.',
                'severity' => 'medium',
                'saving' => 'Rs. 60',
                'icon' => 'battery',
            ],
        ]);
    }
}
