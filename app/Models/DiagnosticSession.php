<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiagnosticSession extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'total_score' => 'integer',
        'engine_score' => 'integer',
        'electrical_score' => 'integer',
        'cooling_score' => 'integer',
        'lubrication_score' => 'integer',
        'safety_score' => 'integer',
        'detected_dtc_count' => 'integer',
        'findings_summary' => 'array',
        'action_items' => 'array',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function faults(): HasMany
    {
        return $this->hasMany(VehicleFaultLog::class, 'session_id');
    }
}
