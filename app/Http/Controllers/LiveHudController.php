<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LiveHudController extends Controller
{
    public function show($vehicle_id = null): View|RedirectResponse
    {
        $vehicle = $vehicle_id ? Vehicle::find($vehicle_id) : Vehicle::first();
        if (!$vehicle) {
            return redirect()->route('dashboard');
        }

        return view('live_hud', [
            'vehicle' => $vehicle,
            'vehicle_id' => $vehicle->id,
            'page_title' => "Live Cockpit HUD - {$vehicle->model}",
        ]);
    }
}
