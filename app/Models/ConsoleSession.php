<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsoleSession extends Model
{
    protected $fillable = [
        'console_id',
        'customer_id',
        'invoice_id',
        'controller_count',
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

    public function console(): BelongsTo
    {
        return $this->belongsTo(Console::class);
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
