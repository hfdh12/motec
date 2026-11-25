<?php

namespace App\Http\Controllers;

use App\Models\TelematicsDevice;
use App\Models\TelemetryLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleFaultLog;
use App\Services\MotorcycleDiagnosticsEngine;
use App\Services\TelemetryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VehicleApiController extends Controller
{
    public function index(TelemetryService $telemetryService): JsonResponse
    {
        $vehicles = Vehicle::orderBy('id', 'asc')->get();
        $result = [];

        foreach ($vehicles as $v) {
            $latestTelemetry = $telemetryService->getLatest($v->id);

            $activeDtcs = VehicleFaultLog::with('dtc')
                ->where('vehicle_id', $v->id)
                ->where('is_active', true)
                ->get()
                ->map(function ($fault) {
                    return [
                        'id' => $fault->id,
                        'code' => $fault->dtc_code,
                        'title_id' => $fault->dtc->title_id ?? 'Gangguan Sensor',
                        'severity' => $fault->dtc->severity ?? 'warning',
                        'recommendation_id' => $fault->dtc->recommendation_id ?? '',
                    ];
                })
                ->toArray();

            $evalRes = MotorcycleDiagnosticsEngine::evaluate($latestTelemetry, $activeDtcs);

            $result[] = array_merge($v->toArray(), [
                'latest_telemetry' => $latestTelemetry,
                'active_faults_count' => count($activeDtcs),
                'diagnostics' => [
                    'score' => $evalRes['total_score'],
                    'status' => $evalRes['status'],
                    'status_text' => $evalRes['status_text'],
                    'status_badge' => $evalRes['status_badge'],
                    'alerts' => $evalRes['alerts'],
                ],
            ]);
        }

        return response()->json($result);
    }

    public function show(int $vehicle_id, TelemetryService $telemetryService): JsonResponse
    {
        $vehicle = Vehicle::find($vehicle_id);
        if (!$vehicle) {
            return response()->json(['error' => 'Sepeda motor tidak ditemukan'], 404);
        }

        $latestTelemetry = $telemetryService->getLatest($vehicle_id);

        $activeDtcs = VehicleFaultLog::with('dtc')
            ->where('vehicle_id', $vehicle_id)
            ->where('is_active', true)
            ->orderBy('detected_at', 'desc')
            ->get()
            ->map(function ($fault) {
                return [
                    'id' => $fault->id,
                    'code' => $fault->dtc_code,
                    'category' => $fault->dtc->category ?? 'Sensor',
                    'title_id' => $fault->dtc->title_id ?? 'Gangguan Sensor',
                    'title_en' => $fault->dtc->title_en ?? 'Sensor Malfunction',
                    'severity' => $fault->dtc->severity ?? 'warning',
                    'description_id' => $fault->dtc->description_id ?? '',
                    'recommendation_id' => $fault->dtc->recommendation_id ?? '',
                    'detected_at' => $fault->detected_at ? $fault->detected_at->toDateTimeString() : null,
                ];
            })
            ->toArray();

        $evalRes = MotorcycleDiagnosticsEngine::evaluate($latestTelemetry, $activeDtcs);
        $telematics = TelematicsDevice::where('vehicle_id', $vehicle_id)->first();

        return response()->json([
            'vehicle' => $vehicle,
            'telematics' => $telematics,
            'latest_telemetry' => $latestTelemetry,
            'active_dtcs' => $activeDtcs,
            'evaluation' => $evalRes,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'brand' => 'required|string|max:60',
            'model' => 'required|string|max:100',
            'plate_number' => 'required|string|max:20|unique:vehicles,plate_number',
            'year' => 'required|integer',
            'engine_displacement_cc' => 'nullable|integer',
            'cooling_type' => 'nullable|string|max:30',
            'fuel_capacity_liter' => 'nullable|numeric',
            'vin_chassis' => 'nullable|string|max:50|unique:vehicles,vin_chassis',
            'engine_number' => 'nullable|string|max:50',
        ]);

        $user = User::first();
        $userId = $user ? $user->id : 1;

        $brandPrefix = strtoupper(substr($validated['brand'], 0, 3));
        $unitId = sprintf('MOTEC-%s-%s', $brandPrefix, strtoupper(Str::random(6)));

        $vehicle = Vehicle::create([
            'user_id' => $userId,
            'plate_number' => strtoupper(trim($validated['plate_number'])),
            'brand' => trim($validated['brand']),
            'model' => trim($validated['model']),
            'year' => (int) $validated['year'],
            'engine_displacement_cc' => (int) ($validated['engine_displacement_cc'] ?? 150),
            'cooling_type' => $validated['cooling_type'] ?? 'liquid',
            'fuel_capacity_liter' => (float) ($validated['fuel_capacity_liter'] ?? 6.5),
            'vin_chassis' => $validated['vin_chassis'] ?? null,
            'engine_number' => $validated['engine_number'] ?? null,
            'telematics_unit_id' => $unitId,
            'is_connected' => false,
            'current_odometer_km' => 0.0,
        ]);

        TelematicsDevice::create([
            'vehicle_id' => $vehicle->id,
            'device_uid' => $unitId,
            'device_model' => 'MOTEC-IoT-ESP32-V2',
            'firmware_version' => 'v2.4.1',
            'protocol' => 'MQTT/HTTP',
            'api_key' => 'key_' . Str::random(12),
            'status' => 'active',
        ]);

        TelemetryLog::create([
            'vehicle_id' => $vehicle->id,
            'engine_rpm' => 0,
            'speed_kmh' => 0.0,
            'engine_temp_c' => 30.0,
            'battery_voltage_v' => 12.6,
            'tps_throttle_percent' => 0.0,
            'oil_health_percent' => 100.0,
            'front_tire_psi' => 29.0,
            'rear_tire_psi' => 33.0,
            'brake_pad_front_percent' => 100.0,
            'brake_pad_rear_percent' => 100.0,
            'fuel_level_percent' => 100.0,
            'odometer_km' => 0.0,
        ]);

        return response()->json([
            'status' => 'success',
            'id' => $vehicle->id,
            'telematics_unit_id' => $unitId,
        ]);
    }
}
