<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Console extends Model
{
    protected $fillable = [
        'name',
        'type',
        'status',
        'hourly_rate_single',
        'hourly_rate_double',
        'hourly_rate_triple',
        'hourly_rate_quadruple',
    ];

    public function sessions(): HasMany
    {
        return $this->hasMany(ConsoleSession::class);
    }
}
