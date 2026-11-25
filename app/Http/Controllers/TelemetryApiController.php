<?php

namespace App\Http\Controllers;

use App\Models\TelemetryLog;
use App\Models\Vehicle;
use App\Models\VehicleFaultLog;
use App\Services\MotorcycleDiagnosticsEngine;
use App\Services\TelemetryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TelemetryApiController extends Controller
{
    public function ingest(Request $request, TelemetryService $telemetryService): JsonResponse
    {
        $payload = $request->all();

        try {
            $result = $telemetryService->ingest($payload);
            return response()->json([
                'status' => 'success',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'detail' => $e->getMessage(),
            ], 400);
        }
    }

    public function latest(int $vehicle_id, TelemetryService $telemetryService): JsonResponse
    {
        $data = $telemetryService->getLatest($vehicle_id);
        return response()->json($data);
    }

    public function history(Request $request, int $vehicle_id): JsonResponse
    {
        $limit = min((int) $request->query('limit', 30), 100);

        $rows = TelemetryLog::where('vehicle_id', $vehicle_id)
            ->latest('timestamp')
            ->limit($limit)
            ->get([
                'timestamp',
                'engine_rpm',
                'speed_kmh',
                'engine_temp_c',
                'battery_voltage_v',
                'tps_throttle_percent',
                'lean_angle_deg',
                'front_tire_psi',
                'rear_tire_psi',
            ])
            ->reverse()
            ->values();

        return response()->json($rows);
    }

    /**
     * Server-Sent Events (SSE) Stream untuk pembaruan telemetri secara real-time ke web HUD / Cockpit.
     */
    public function stream(int $vehicle_id, TelemetryService $telemetryService): StreamedResponse
    {
        return response()->stream(function () use ($vehicle_id, $telemetryService) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            ignore_user_abort(false);

            $lastSentTimestamp = null;
            $iterations = 0;

            // Stream for up to 15 iterations per request (~12 seconds, reconnects automatically)
            while ($iterations < 15) {
                if (connection_aborted()) {
                    break;
                }

                $latest = $telemetryService->getLatest($vehicle_id);
                $currentTimestamp = $latest['timestamp'] ?? null;

                if ($currentTimestamp !== $lastSentTimestamp || $iterations === 0) {
                    $lastSentTimestamp = $currentTimestamp;

                    $activeDtcs = VehicleFaultLog::with('dtc')
                        ->where('vehicle_id', $vehicle_id)
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

                    $evalRes = MotorcycleDiagnosticsEngine::evaluate($latest, $activeDtcs);

                    $payload = [
                        'type' => 'telemetry_update',
                        'vehicle_id' => $vehicle_id,
                        'timestamp' => $currentTimestamp,
                        'telemetry' => $latest,
                        'evaluation' => $evalRes,
                        'active_dtcs' => $activeDtcs,
                    ];

                    echo "event: telemetry\n";
                    echo "data: " . json_encode($payload) . "\n\n";
                    ob_flush();
                    flush();
                }

                // Heartbeat ping
                echo ": heartbeat\n\n";
                ob_flush();
                flush();

                usleep(800000); // 800ms
                $iterations++;
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
