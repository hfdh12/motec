<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelemetryLog extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'timestamp' => 'datetime',
        'engine_rpm' => 'integer',
        'speed_kmh' => 'float',
        'engine_temp_c' => 'float',
        'tps_throttle_percent' => 'float',
        'gear_position' => 'integer',
        'battery_voltage_v' => 'float',
        'stator_charging_amp' => 'float',
        'oil_health_percent' => 'float',
        'oil_pressure_kpa' => 'float',
        'o2_sensor_v' => 'float',
        'map_sensor_kpa' => 'float',
        'front_tire_psi' => 'float',
        'rear_tire_psi' => 'float',
        'brake_pad_front_percent' => 'float',
        'brake_pad_rear_percent' => 'float',
        'fuel_level_percent' => 'float',
        'ambient_temp_c' => 'float',
        'sidestand_down' => 'boolean',
        'lean_angle_deg' => 'float',
        'odometer_km' => 'float',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
