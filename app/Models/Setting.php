<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public const DEFAULT_CREDIT_LIMIT = 'default_credit_limit';

    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = static::query()->where('key', $key)->value('value');

        return $value ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * سقف اعتبار پیش‌فرض (تومان) برای مشتریانی که سقف اختصاصی ندارند.
     */
    public static function defaultCreditLimit(): float
    {
        return max(0, (float) static::get(self::DEFAULT_CREDIT_LIMIT, 0));
    }
}