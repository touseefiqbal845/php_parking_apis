<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleManagement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'lot_number_id',
        'license_plate',
        'permit_id',
        'status',
        'start_date',
        'end_date',
        'duration_type',
        'is_active',
        'notes'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean'
    ];

    public function lotNumber(): BelongsTo
    {
        return $this->belongsTo(LotNumber::class);
    }
}
