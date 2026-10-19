<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TimbanganApiController;
use App\Http\Controllers\Api\TelemetryController;
use App\Http\Controllers\Api\TernakApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// =========================================================================
// ENDPOINT TELEMETRI IOT ESP32 (JJFARM)
// =========================================================================

// 1. Endpoint Tembakan Firmware ESP32 Teman (Port 8080 / rfid-scan)
Route::post('/rfid-scan', [TimbanganApiController::class, 'store']);

// 2. Endpoint Timbangan IoT (Rute Eksisting)
Route::post('/v1/timbangan/telemetri', [TimbanganApiController::class, 'store']);

// 3. Endpoint Jalur Pendek / Gateway ESP32
Route::post('/telemetry', [TelemetryController::class, 'store']);

// 4. Endpoint Sinkronisasi Master Ternak & Security Log
Route::post('/ternak/sync', [TernakApiController::class, 'store']);