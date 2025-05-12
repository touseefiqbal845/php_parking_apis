<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessCode extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'lot_number_id',
        'access_code',
        'permits_per_month',
        'duration',
        'is_active',
        'last_used_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'permits_per_month' => 'integer'
    ];

    public function lotNumber(): BelongsTo
    {
        return $this->belongsTo(LotNumber::class);
    }
}
