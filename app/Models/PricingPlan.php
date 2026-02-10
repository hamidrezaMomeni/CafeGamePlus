<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PricingPlan extends Model
{
    public const TYPE_BONUS_TIME = 'bonus_time';
    public const TYPE_DURATION_DISCOUNT = 'duration_discount';
    public const TYPE_HAPPY_HOUR = 'happy_hour';
    public const TYPE_WEEKLY_VOLUME_DISCOUNT = 'weekly_volume_discount';

    public const APPLIES_CONSOLE = 'console';
    public const APPLIES_TABLE = 'table';
    public const APPLIES_BOARD_GAME = 'board_game';
    public const APPLIES_BOTH = 'both';

    protected $fillable = [
        'name',
        'type',
        'applies_to',
        'is_active',
        'priority',
        'starts_at',
        'ends_at',
        'config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'config' => 'array',
    ];

    public static function availableTypes(): array
    {
        return [
            self::TYPE_BONUS_TIME => 'زمان هدیه (بعد از رسیدن به حد مشخص)',
            self::TYPE_DURATION_DISCOUNT => 'تخفیف بر اساس مدت سشن',
            self::TYPE_HAPPY_HOUR => 'هپی آور (تخفیف ساعتی)',
            self::TYPE_WEEKLY_VOLUME_DISCOUNT => 'تخفیف بر اساس بازی در بازه زمانی',
        ];
    }

    public static function availableAppliesTo(): array
    {
        return [
            self::APPLIES_BOTH => 'همه (کنسول + میز + بردگیم)',
            self::APPLIES_CONSOLE => 'فقط کنسول',
            self::APPLIES_TABLE => 'فقط میز',
            self::APPLIES_BOARD_GAME => 'فقط بردگیم',
        ];
    }

    public function scopeActive(Builder $query, ?Carbon $now = null): Builder
    {
        $now = $now ?? Carbon::now();

        return $query
            ->where('is_active', true)
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }

    public function summary(): string
    {
        $config = $this->config ?? [];

        return match ($this->type) {
            self::TYPE_BONUS_TIME => sprintf(
                'بعد از %s دقیقه، %s دقیقه هدیه',
                $config['threshold_minutes'] ?? '?',
                $config['bonus_minutes'] ?? '?'
            ),
            self::TYPE_DURATION_DISCOUNT => sprintf(
                'اگر سشن حداقل %s دقیقه باشد، %s٪ تخفیف',
                $config['min_minutes'] ?? '?',
                $config['discount_percent'] ?? '?'
            ),
            self::TYPE_HAPPY_HOUR => sprintf(
                'تخفیف %s٪ بین %s تا %s',
                $config['discount_percent'] ?? '?',
                $config['start_time'] ?? '?',
                $config['end_time'] ?? '?'
            ),
            self::TYPE_WEEKLY_VOLUME_DISCOUNT => sprintf(
                'اگر در %s روز اخیر حداقل %s دقیقه بازی، %s٪ تخفیف',
                $config['lookback_days'] ?? '?',
                $config['min_total_minutes'] ?? '?',
                $config['discount_percent'] ?? '?'
            ),
            default => '—',
        };
    }
}
