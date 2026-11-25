<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DtcLibrary extends Model
{
    use HasFactory;

    protected $table = 'dtc_library';

    protected $guarded = ['id'];

    public function faultLogs(): HasMany
    {
        return $this->hasMany(VehicleFaultLog::class, 'dtc_code', 'code');
    }
}
