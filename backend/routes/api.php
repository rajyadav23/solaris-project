<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EnergyController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\WeatherController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\OptimizationController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Public dashboard reads
Route::get('/energy/current',       [EnergyController::class, 'current']);
Route::get('/energy/hourly',        [EnergyController::class, 'hourly']);
Route::get('/energy/daily',         [EnergyController::class, 'daily']);
Route::get('/predictions/solar',    [PredictionController::class, 'solar']);
Route::get('/predictions/wind',     [PredictionController::class, 'wind']);
Route::get('/predictions/weekly',   [PredictionController::class, 'weekly']);
Route::get('/weather/current',      [WeatherController::class, 'current']);
Route::get('/weather/forecast',     [WeatherController::class, 'forecast']);
Route::get('/recommendations',      [RecommendationController::class, 'index']);
Route::get('/optimization/metrics', [OptimizationController::class, 'metrics']);
Route::get('/optimization/schedule',[OptimizationController::class, 'schedule']);
Route::post('/chat',                [ChatController::class, 'send']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user',    [AuthController::class, 'user']);

    // Energy readings
    Route::post('/energy/reading',      [EnergyController::class, 'store']);

    // Chatbot
    Route::get('/chat/history',         [ChatController::class, 'history']);
});
