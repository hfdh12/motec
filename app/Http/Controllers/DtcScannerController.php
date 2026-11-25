<?php

namespace App\Http\Controllers;

use App\Models\DtcLibrary;
use App\Models\Vehicle;
use App\Models\VehicleFaultLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DtcScannerController extends Controller
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

        return view('dtc_scanner', [
            'vehicles' => $vehicles,
            'vehicle' => $vehicle,
            'vehicle_id' => $vehicleId,
            'page_title' => "ECU Fault Scanner - {$vehicle->model}",
        ]);
    }

    public function library(): JsonResponse
    {
        $dtcs = DtcLibrary::orderBy('code', 'asc')->get();
        return response()->json($dtcs);
    }

    public function inject(Request $request, int $vehicle_id): JsonResponse
    {
        $dtcCode = $request->query('dtc_code') ?? $request->input('dtc_code');
        if (!$dtcCode) {
            return response()->json(['error' => 'Parameter dtc_code diperlukan'], 400);
        }

        $dtcInfo = DtcLibrary::where('code', $dtcCode)->first();
        if (!$dtcInfo) {
            return response()->json(['error' => 'Kode DTC tidak ditemukan di library'], 400);
        }

        $exists = VehicleFaultLog::where('vehicle_id', $vehicle_id)
            ->where('dtc_code', $dtcCode)
            ->where('is_active', true)
            ->exists();

        if (!$exists) {
            VehicleFaultLog::create([
                'vehicle_id' => $vehicle_id,
                'dtc_code' => $dtcCode,
                'is_active' => true,
                'detected_at' => now(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => "DTC {$dtcCode} berhasil diinjeksi ke motor #{$vehicle_id}",
        ]);
    }

    public function clear(int $vehicle_id): JsonResponse
    {
        $now = now();
        $clearedCount = VehicleFaultLog::where('vehicle_id', $vehicle_id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'cleared_at' => $now,
                'cleared_by' => 'user-scanner',
            ]);

        return response()->json([
            'status' => 'success',
            'cleared_count' => $clearedCount,
            'message' => 'Semua kode DTC berhasil dibersihkan',
        ]);
    }
}
