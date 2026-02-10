<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoardGame extends Model
{
    protected $fillable = [
        'name',
        'status',
        'hourly_rate',
    ];

    public function sessions(): HasMany
    {
        return $this->hasMany(BoardGameSession::class);
    }
}

