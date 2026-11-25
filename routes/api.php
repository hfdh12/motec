<?php

use App\Http\Controllers\DiagnosticsController;
use App\Http\Controllers\DtcScannerController;
use App\Http\Controllers\Elm327Controller;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\TelemetryApiController;
use App\Http\Controllers\VehicleApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - MOTEC Telemetry & Diagnostics API
|--------------------------------------------------------------------------
*/

// 1. Vehicles
Route::get('/vehicles', [VehicleApiController::class, 'index']);
Route::get('/vehicles/{vehicle_id}', [VehicleApiController::class, 'show']);
Route::post('/vehicles', [VehicleApiController::class, 'store']);

// 2. Telemetry Ingest & Real-Time Stream
Route::post('/telemetry/ingest', [TelemetryApiController::class, 'ingest']);
Route::get('/telemetry/{vehicle_id}/latest', [TelemetryApiController::class, 'latest']);
Route::get('/telemetry/{vehicle_id}/history', [TelemetryApiController::class, 'history']);
Route::get('/telemetry/stream/{vehicle_id}', [TelemetryApiController::class, 'stream']);

// 3. Diagnostics & Technical Check
Route::post('/diagnostics/run-scan/{vehicle_id}', [DiagnosticsController::class, 'runScan']);
Route::get('/diagnostics/sessions/{vehicle_id}', [DiagnosticsController::class, 'sessions']);

// 4. DTC Fault Codes & Library
Route::get('/dtc/library', [DtcScannerController::class, 'library']);
Route::post('/dtc/inject/{vehicle_id}', [DtcScannerController::class, 'inject']);
Route::post('/dtc/clear/{vehicle_id}', [DtcScannerController::class, 'clear']);

// 5. Maintenance Records
Route::get('/maintenance/{vehicle_id}', [MaintenanceController::class, 'getRecords']);
Route::post('/maintenance/{vehicle_id}', [MaintenanceController::class, 'store']);

// 6. ELM327 OBD-II Endpoints
Route::post('/elm327/decode', [Elm327Controller::class, 'decodeObd']);
Route::post('/elm327/register/{vehicle_id}', [Elm327Controller::class, 'registerDevice']);
Route::post('/elm327/wifi-proxy', [Elm327Controller::class, 'proxyTcpCommand']);
