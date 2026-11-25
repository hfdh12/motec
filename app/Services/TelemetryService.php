<?php

namespace App\Services;

use App\Models\TelemetryLog;
use App\Models\Vehicle;
use App\Models\VehicleFaultLog;
use Illuminate\Support\Facades\Cache;

class TelemetryService
{
    /**
     * Dapatkan telemetry terkini untuk suatu kendaraan.
     */
    public function getLatest(int $vehicleId): array
    {
        $cached = Cache::get("telemetry_latest_{$vehicleId}");
        if ($cached && is_array($cached)) {
            return $cached;
        }

        $row = TelemetryLog::where('vehicle_id', $vehicleId)
            ->latest('timestamp')
            ->first();

        if ($row) {
            $data = $row->toArray();
            Cache::put("telemetry_latest_{$vehicleId}", $data, 300);
            return $data;
        }

        return [
            'vehicle_id' => $vehicleId,
            'engine_rpm' => 0,
            'speed_kmh' => 0.0,
            'engine_temp_c' => 32.0,
            'battery_voltage_v' => 12.6,
            'tps_throttle_percent' => 0.0,
            'gear_position' => 0,
            'oil_health_percent' => 90.0,
            'oil_pressure_kpa' => 250.0,
            'front_tire_psi' => 29.0,
            'rear_tire_psi' => 33.0,
            'brake_pad_front_percent' => 85.0,
            'brake_pad_rear_percent' => 85.0,
            'fuel_level_percent' => 70.0,
            'o2_sensor_v' => 0.45,
            'odometer_km' => 1000.0,
            'timestamp' => now()->toDateTimeString(),
        ];
    }

    /**
     * Ingest telemetry data dari IoT ESP32 / OBD-II Scanner / Simulator.
     */
    public function ingest(array $data): array
    {
        $vehicleId = $data['vehicle_id'] ?? null;
        $deviceUid = $data['device_uid'] ?? null;

        if (!$vehicleId && $deviceUid) {
            $v = Vehicle::where('telematics_unit_id', $deviceUid)->first();
            if ($v) {
                $vehicleId = $v->id;
                $data['vehicle_id'] = $vehicleId;
            }
        }

        if (!$vehicleId) {
            throw new \InvalidArgumentException('Vehicle ID atau Device UID tidak ditemukan.');
        }

        $vehicle = Vehicle::find($vehicleId);
        if (!$vehicle) {
            throw new \InvalidArgumentException('Kendaraan tidak terdaftar.');
        }

        $currentOdo = $vehicle->current_odometer_km ?? 0.0;
        $odo = isset($data['odometer_km']) ? (float) $data['odometer_km'] : $currentOdo;
        if ($odo <= 0) {
            $odo = $currentOdo;
        }

        $now = now();

        // 1. Simpan ke telemetry_logs
        $log = TelemetryLog::create([
            'vehicle_id' => $vehicleId,
            'timestamp' => $now,
            'engine_rpm' => (int) ($data['engine_rpm'] ?? 0),
            'speed_kmh' => (float) ($data['speed_kmh'] ?? 0.0),
            'engine_temp_c' => (float) ($data['engine_temp_c'] ?? 85.0),
            'tps_throttle_percent' => (float) ($data['tps_throttle_percent'] ?? 0.0),
            'gear_position' => (int) ($data['gear_position'] ?? 0),
            'battery_voltage_v' => (float) ($data['battery_voltage_v'] ?? 12.6),
            'stator_charging_amp' => (float) ($data['stator_charging_amp'] ?? 3.5),
            'oil_health_percent' => (float) ($data['oil_health_percent'] ?? 90.0),
            'oil_pressure_kpa' => (float) ($data['oil_pressure_kpa'] ?? 250.0),
            'o2_sensor_v' => (float) ($data['o2_sensor_v'] ?? 0.45),
            'map_sensor_kpa' => (float) ($data['map_sensor_kpa'] ?? 101.3),
            'front_tire_psi' => (float) ($data['front_tire_psi'] ?? 29.0),
            'rear_tire_psi' => (float) ($data['rear_tire_psi'] ?? 33.0),
            'brake_pad_front_percent' => (float) ($data['brake_pad_front_percent'] ?? 85.0),
            'brake_pad_rear_percent' => (float) ($data['brake_pad_rear_percent'] ?? 85.0),
            'fuel_level_percent' => (float) ($data['fuel_level_percent'] ?? 70.0),
            'ambient_temp_c' => (float) ($data['ambient_temp_c'] ?? 30.0),
            'sidestand_down' => !empty($data['sidestand_down']),
            'lean_angle_deg' => (float) ($data['lean_angle_deg'] ?? 0.0),
            'odometer_km' => $odo,
            'raw_payload' => json_encode($data),
        ]);

        // 2. Update status motor terkoneksi & odometer
        $vehicle->update([
            'is_connected' => true,
            'last_ping_at' => $now,
            'current_odometer_km' => $odo,
        ]);

        // 3. Handle DTC codes jika dikirim dari scanner ECU motor
        $dtcCodes = $data['dtc_codes'] ?? [];
        if (is_array($dtcCodes) && !empty($dtcCodes)) {
            foreach ($dtcCodes as $code) {
                $exists = VehicleFaultLog::where('vehicle_id', $vehicleId)
                    ->where('dtc_code', $code)
                    ->where('is_active', true)
                    ->exists();

                if (!$exists) {
                    VehicleFaultLog::create([
                        'vehicle_id' => $vehicleId,
                        'dtc_code' => $code,
                        'is_active' => true,
                        'detected_at' => $now,
                    ]);
                }
            }
        }

        // 4. Evaluasi kesehatan dengan diagnostic engine
        $activeDtcs = VehicleFaultLog::with('dtc')
            ->where('vehicle_id', $vehicleId)
            ->where('is_active', true)
            ->get()
            ->map(function ($fault) {
                return [
                    'id' => $fault->id,
                    'code' => $fault->dtc_code,
                    'title_id' => $fault->dtc->title_id ?? 'Gangguan Sensor',
                    'title_en' => $fault->dtc->title_en ?? 'Sensor Malfunction',
                    'severity' => $fault->dtc->severity ?? 'warning',
                    'recommendation_id' => $fault->dtc->recommendation_id ?? '',
                ];
            })
            ->toArray();

        $diagRes = MotorcycleDiagnosticsEngine::evaluate($data, $activeDtcs);

        $fullPayload = [
            'type' => 'telemetry_update',
            'vehicle_id' => (int) $vehicleId,
            'timestamp' => $now->toDateTimeString(),
            'telemetry' => $data,
            'evaluation' => $diagRes,
            'active_dtcs' => $activeDtcs,
        ];

        Cache::put("telemetry_latest_{$vehicleId}", $data, 300);

        return $fullPayload;
    }
}
