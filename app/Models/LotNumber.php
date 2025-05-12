<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LotNumber extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'lot_code',
        'address',
        'city',
        'permits_per_month',
        'duration',
        'status',
        'note',
        'pricing'
    ];

    protected $casts = [
        'pricing' => 'array',
        'permits_per_month' => 'integer'
    ];
}
