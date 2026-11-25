<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $vehicles = Vehicle::orderBy('id', 'asc')->get();

        if ($vehicles->isEmpty()) {
            return view('empty');
        }

        $selectedId = (int) $request->query('vehicle_id', $vehicles->first()->id);
        $selectedVehicle = $vehicles->firstWhere('id', $selectedId) ?? $vehicles->first();
        $selectedId = $selectedVehicle->id;

        return view('dashboard', [
            'vehicles' => $vehicles,
            'selected_id' => $selectedId,
            'vehicle' => $selectedVehicle,
            'page_title' => 'MOTEC - Dashboard Pemeriksaan Motor',
        ]);
    }
}
