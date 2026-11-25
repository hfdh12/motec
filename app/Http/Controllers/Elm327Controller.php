<?php

namespace App\Http\Controllers;

use App\Models\DtcLibrary;
use App\Models\TelematicsDevice;
use App\Models\Vehicle;
use App\Models\VehicleFaultLog;
use App\Services\TelemetryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class Elm327Controller extends Controller
{
    /**
     * Tampilkan halaman interface koneksi ELM327 OBD-II.
     */
    public function show(Request $request, ?int $vehicle_id = null): View
    {
        $vehicles = Vehicle::all();

        if ($vehicle_id) {
            $vehicle = Vehicle::find($vehicle_id);
        } else {
            $vehicle = $vehicles->first();
        }

        if (!$vehicle && $vehicles->isNotEmpty()) {
            $vehicle = $vehicles->first();
        }

        $vehicle_id = $vehicle ? $vehicle->id : 1;

        $device = TelematicsDevice::where('vehicle_id', $vehicle_id)
            ->where('protocol', 'OBD2-ELM327')
            ->first();

        $activeDtcs = VehicleFaultLog::with('dtc')
            ->where('vehicle_id', $vehicle_id)
            ->where('is_active', true)
            ->get();

        $dtcLibrary = DtcLibrary::all();

        return view('elm327', [
            'page_title' => 'MOTEC - Konektor ELM327 OBD-II',
            'vehicles' => $vehicles,
            'vehicle' => $vehicle,
            'vehicle_id' => $vehicle_id,
            'device' => $device,
            'activeDtcs' => $activeDtcs,
            'dtcLibrary' => $dtcLibrary,
        ]);
    }

    /**
     * Decode batch respons mentah ELM327 / OBD2 ke dalam nilai metrik telemetri yang valid.
     */
    public function decodeObd(Request $request): JsonResponse
    {
        $commands = $request->input('responses', []);
        $decoded = [];

        foreach ($commands as $cmd => $raw) {
            $clean = strtoupper(trim(preg_replace('/[^0-9A-Fa-f\.]/', '', $raw)));

            // 1. Battery Voltage via ATRV (e.g., '12.4V' or '12.4')
            if (str_starts_with(strtoupper($cmd), 'ATRV')) {
                if (preg_match('/([0-9]+\.?[0-9]*)/', $raw, $m)) {
                    $decoded['battery_voltage_v'] = (float) $m[1];
                }
                continue;
            }

            // 2. Mode 01 Responses (starts with 41 xx ...)
            if (str_starts_with($clean, '41')) {
                $pid = substr($clean, 2, 2);
                $bytes = substr($clean, 4);

                switch ($pid) {
                    case '0C': // RPM: ((A*256)+B)/4
                        if (strlen($bytes) >= 4) {
                            $a = hexdec(substr($bytes, 0, 2));
                            $b = hexdec(substr($bytes, 2, 2));
                            $decoded['engine_rpm'] = (int) round((($a * 256) + $b) / 4);
                        }
                        break;

                    case '0D': // Speed: A km/h
                        if (strlen($bytes) >= 2) {
                            $decoded['speed_kmh'] = (float) hexdec(substr($bytes, 0, 2));
                        }
                        break;

                    case '05': // Coolant Temp: A - 40 deg C
                        if (strlen($bytes) >= 2) {
                            $decoded['engine_temp_c'] = (float) (hexdec(substr($bytes, 0, 2)) - 40);
                        }
                        break;

                    case '11': // Throttle Position (TPS): (A * 100) / 255
                        if (strlen($bytes) >= 2) {
                            $decoded['tps_throttle_percent'] = (float) round((hexdec(substr($bytes, 0, 2)) * 100) / 255, 1);
                        }
                        break;

                    case '0B': // Intake Manifold Pressure (MAP): A kPa
                        if (strlen($bytes) >= 2) {
                            $decoded['map_sensor_kpa'] = (float) hexdec(substr($bytes, 0, 2));
                        }
                        break;

                    case '0F': // Intake Air Temp (IAT): A - 40 deg C
                        if (strlen($bytes) >= 2) {
                            $decoded['ambient_temp_c'] = (float) (hexdec(substr($bytes, 0, 2)) - 40);
                        }
                        break;

                    case '14': // O2 Sensor Voltage: A / 200 Volts
                        if (strlen($bytes) >= 2) {
                            $decoded['o2_sensor_v'] = (float) round(hexdec(substr($bytes, 0, 2)) / 200, 2);
                        }
                        break;

                    case '2F': // Fuel Tank Level: (A * 100) / 255
                        if (strlen($bytes) >= 2) {
                            $decoded['fuel_level_percent'] = (float) round((hexdec(substr($bytes, 0, 2)) * 100) / 255, 1);
                        }
                        break;

                    case '31': // Distance Traveled since codes cleared (odometer ref): (A*256)+B km
                        if (strlen($bytes) >= 4) {
                            $a = hexdec(substr($bytes, 0, 2));
                            $b = hexdec(substr($bytes, 2, 2));
                            $decoded['distance_cleared_km'] = (float) (($a * 256) + $b);
                        }
                        break;
                }
                continue;
            }

            // 3. Mode 03 Trouble Codes Response (starts with 43 xx xx ...)
            if (str_starts_with($clean, '43')) {
                $dtcBytes = substr($clean, 2);
                $dtcList = [];

                for ($i = 0; $i + 4 <= strlen($dtcBytes); $i += 4) {
                    $pair = substr($dtcBytes, $i, 4);
                    if ($pair === '0000') {
                        continue;
                    }

                    $b1 = hexdec(substr($pair, 0, 2));
                    $b2 = substr($pair, 2, 2);

                    $prefixType = ($b1 & 0xC0) >> 6;
                    $prefixChar = ['P', 'C', 'B', 'U'][$prefixType] ?? 'P';
                    $secondDigit = ($b1 & 0x30) >> 4;
                    $thirdDigit = dechex($b1 & 0x0F);

                    $code = sprintf('%s%d%s%s', $prefixChar, $secondDigit, strtoupper($thirdDigit), strtoupper($b2));
                    $dtcList[] = $code;
                }

                $decoded['dtc_codes'] = array_values(array_unique($dtcList));
            }
        }

        return response()->json([
            'status' => 'success',
            'decoded' => $decoded,
        ]);
    }

    /**
     * Daftarkan adapter ELM327 ke database kendaraan.
     */
    public function registerDevice(Request $request, int $vehicle_id): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($vehicle_id);

        $deviceUid = $request->input('device_uid', 'ELM327-BT-' . strtoupper(substr(md5($vehicle->id), 0, 6)));
        $deviceModel = $request->input('device_model', 'ELM327 v1.5 OBD-II');
        $protocol = 'OBD2-ELM327';

        $device = TelematicsDevice::updateOrCreate(
            ['vehicle_id' => $vehicle->id, 'protocol' => $protocol],
            [
                'device_uid' => $deviceUid,
                'device_model' => $deviceModel,
                'firmware_version' => $request->input('firmware_version', 'v1.5/v2.1'),
                'api_key' => 'elm327_' . bin2hex(random_bytes(8)),
                'status' => 'active',
            ]
        );

        $vehicle->update([
            'telematics_unit_id' => $deviceUid,
            'is_connected' => true,
            'last_ping_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Perangkat ELM327 berhasil dipasangkan dengan sepeda motor.',
            'device' => $device,
        ]);
    }

    /**
     * Proxy koneksi TCP untuk ELM327 WiFi (default 192.168.0.10:35000).
     */
    public function proxyTcpCommand(Request $request): JsonResponse
    {
        $host = $request->input('host', '192.168.0.10');
        $port = (int) $request->input('port', 35000);
        $command = trim($request->input('command', 'ATZ')) . "\r";

        $socket = @fsockopen($host, $port, $errno, $errstr, 2.0);
        if (!$socket) {
            return response()->json([
                'status' => 'error',
                'message' => "Gagal terhubung ke ELM327 WiFi ($host:$port): $errstr ($errno)",
            ], 502);
        }

        stream_set_timeout($socket, 2);
        fwrite($socket, $command);

        $response = '';
        while (!feof($socket)) {
            $char = fgetc($socket);
            if ($char === false) {
                break;
            }
            $response .= $char;
            if ($char === '>') {
                break;
            }
        }
        fclose($socket);

        return response()->json([
            'status' => 'success',
            'command' => trim($command),
            'response' => trim($response),
        ]);
    }
}

