<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelematicsDevice extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'battery_backup_v' => 'float',
        'last_seen' => 'datetime',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
