<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vehicle extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'year' => 'integer',
        'engine_displacement_cc' => 'integer',
        'fuel_capacity_liter' => 'float',
        'current_odometer_km' => 'float',
        'is_connected' => 'boolean',
        'last_ping_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function telematicsDevice(): HasOne
    {
        return $this->hasOne(TelematicsDevice::class);
    }

    public function telemetryLogs(): HasMany
    {
        return $this->hasMany(TelemetryLog::class);
    }

    public function latestTelemetry(): HasOne
    {
        return $this->hasOne(TelemetryLog::class)->latestOfMany('timestamp');
    }

    public function faultLogs(): HasMany
    {
        return $this->hasMany(VehicleFaultLog::class);
    }

    public function activeFaultLogs(): HasMany
    {
        return $this->hasMany(VehicleFaultLog::class)->where('is_active', true);
    }

    public function diagnosticSessions(): HasMany
    {
        return $this->hasMany(DiagnosticSession::class);
    }

    public function latestDiagnosticSession(): HasOne
    {
        return $this->hasOne(DiagnosticSession::class)->latestOfMany();
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class)->orderBy('service_date', 'desc');
    }
}
