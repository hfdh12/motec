<?php

namespace App\Http\Controllers;

use App\Models\DtcLibrary;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SimulatorController extends Controller
{
    public function show(Request $request): View
    {
        $vehicles = Vehicle::orderBy('id', 'asc')->get();
        $dtcs = DtcLibrary::orderBy('code', 'asc')->get();

        $vehicleId = (int) $request->query('vehicle_id', $vehicles->isNotEmpty() ? $vehicles->first()->id : 1);

        return view('simulator', [
            'vehicles' => $vehicles,
            'dtcs' => $dtcs,
            'vehicle_id' => $vehicleId,
            'page_title' => 'Virtual Telematics & ECU Simulator',
        ]);
    }
}
