<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRecord;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaintenanceController extends Controller
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

        $records = MaintenanceRecord::where('vehicle_id', $vehicleId)
            ->orderBy('service_date', 'desc')
            ->get();

        return view('maintenance', [
            'vehicles' => $vehicles,
            'vehicle' => $vehicle,
            'vehicle_id' => $vehicleId,
            'records' => $records,
            'page_title' => "Riwayat Servis - {$vehicle->model}",
        ]);
    }

    public function getRecords(int $vehicle_id): JsonResponse
    {
        $records = MaintenanceRecord::where('vehicle_id', $vehicle_id)
            ->orderBy('service_date', 'desc')
            ->get();

        return response()->json($records);
    }

    public function store(Request $request, int $vehicle_id): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'service_type' => 'required|string|max:100',
            'service_date' => 'required|date',
            'odometer_km' => 'required|numeric',
            'cost_idr' => 'nullable|integer',
            'workshop_name' => 'nullable|string|max:120',
            'notes' => 'nullable|string',
        ]);

        $record = MaintenanceRecord::create([
            'vehicle_id' => $vehicle_id,
            'service_type' => $validated['service_type'],
            'service_date' => $validated['service_date'],
            'odometer_km' => (float) $validated['odometer_km'],
            'cost_idr' => (int) ($validated['cost_idr'] ?? 0),
            'workshop_name' => $validated['workshop_name'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'id' => $record->id,
                'record' => $record,
            ]);
        }

        return redirect()->route('maintenance.show', $vehicle_id)->with('success', 'Riwayat servis berhasil dicatat!');
    }
}
