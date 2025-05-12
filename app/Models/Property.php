<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    protected $fillable = ['code'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'property_user', 'property_id', 'user_id');
    }
}
