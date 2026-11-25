<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleFaultLog extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'detected_at' => 'datetime',
        'cleared_at' => 'datetime',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function dtc(): BelongsTo
    {
        return $this->belongsTo(DtcLibrary::class, 'dtc_code', 'code');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(DiagnosticSession::class, 'session_id');
    }
}
