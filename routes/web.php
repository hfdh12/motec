<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiagnosticsController;
use App\Http\Controllers\DtcScannerController;
use App\Http\Controllers\Elm327Controller;
use App\Http\Controllers\LiveHudController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\SimulatorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - MOTEC (Motor Technical Check)
|--------------------------------------------------------------------------
*/

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/diagnostics/{vehicle_id?}', [DiagnosticsController::class, 'show'])->name('diagnostics.show');
Route::get('/live-hud/{vehicle_id?}', [LiveHudController::class, 'show'])->name('live_hud.show');
Route::get('/dtc-scanner/{vehicle_id?}', [DtcScannerController::class, 'show'])->name('dtc_scanner.show');
Route::get('/elm327/{vehicle_id?}', [Elm327Controller::class, 'show'])->name('elm327.show');
Route::get('/simulator', [SimulatorController::class, 'show'])->name('simulator.show');
Route::get('/maintenance/{vehicle_id?}', [MaintenanceController::class, 'show'])->name('maintenance.show');
Route::post('/maintenance/{vehicle_id}', [MaintenanceController::class, 'store'])->name('maintenance.store');
