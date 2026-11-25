<?php

namespace App\Http\Controllers;

use App\Models\DiagnosticSession;
use App\Models\Vehicle;
use App\Models\VehicleFaultLog;
use App\Services\MotorcycleDiagnosticsEngine;
use App\Services\TelemetryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Str;

class DiagnosticsController extends Controller
{
    public function show($vehicle_id = null): View|RedirectResponse
    {
        $vehicles = Vehicle::orderBy('id', 'asc')->get();
        if ($vehicles->isEmpty()) {
            return redirect()->route('dashboard');
        }

        $vehicleId = $vehicle_id ? (int) $vehicle_id : $vehicles->first()->id;
        $vehicle = Vehicle::find($vehicleId);

        if (!$vehicle) {
            return redirect()->route('dashboard');
        }

        return view('diagnostics', [
            'vehicles' => $vehicles,
            'vehicle' => $vehicle,
            'vehicle_id' => $vehicleId,
            'page_title' => "Pemeriksaan Teknis - {$vehicle->model}",
        ]);
    }

    public function runScan(int $vehicle_id, TelemetryService $telemetryService): JsonResponse
    {
        $vehicle = Vehicle::find($vehicle_id);
        if (!$vehicle) {
            return response()->json(['error' => 'Motor tidak ditemukan'], 404);
        }

        $latestTelemetry = $telemetryService->getLatest($vehicle_id);

        $activeDtcs = VehicleFaultLog::with('dtc')
            ->where('vehicle_id', $vehicle_id)
            ->where('is_active', true)
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
                ];
            })
            ->toArray();

        $evalRes = MotorcycleDiagnosticsEngine::evaluate($latestTelemetry, $activeDtcs);
        $sessionUid = sprintf('MOTEC-SCAN-%s-%s', now()->format('YmdHis'), strtoupper(Str::random(4)));

        $session = DiagnosticSession::create([
            'vehicle_id' => $vehicle_id,
            'session_uid' => $sessionUid,
            'total_score' => $evalRes['total_score'],
            'engine_score' => $evalRes['sub_scores']['engine'],
            'electrical_score' => $evalRes['sub_scores']['electrical'],
            'cooling_score' => $evalRes['sub_scores']['cooling'],
            'lubrication_score' => $evalRes['sub_scores']['lubrication'],
            'safety_score' => $evalRes['sub_scores']['safety'],
            'status' => $evalRes['status'],
            'detected_dtc_count' => count($activeDtcs),
            'findings_summary' => $evalRes['findings'],
            'action_items' => $evalRes['recommendations'],
            'tested_by' => 'motec-ai-scanner',
        ]);

        return response()->json([
            'session_uid' => $sessionUid,
            'scanned_at' => now()->translatedFormat('d F Y, H:i:s') . ' WIB',
            'vehicle' => $vehicle,
            'evaluation' => $evalRes,
            'active_dtcs' => $activeDtcs,
        ]);
    }

    public function sessions(int $vehicle_id): JsonResponse
    {
        $sessions = DiagnosticSession::where('vehicle_id', $vehicle_id)
            ->latest()
            ->limit(10)
            ->get();

        return response()->json($sessions);
    }
}
