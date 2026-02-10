<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardGameSession extends Model
{
    protected $fillable = [
        'board_game_id',
        'customer_id',
        'invoice_id',
        'planned_duration_minutes',
        'start_time',
        'planned_end_time',
        'end_time',
        'duration_minutes',
        'discount_percent',
        'total_price',
        'status',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'planned_end_time' => 'datetime',
        'end_time' => 'datetime',
        'planned_duration_minutes' => 'integer',
        'discount_percent' => 'integer',
    ];

    public function boardGame(): BelongsTo
    {
        return $this->belongsTo(BoardGame::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}

