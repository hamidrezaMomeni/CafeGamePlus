<?php

namespace App\Support;

use Carbon\Carbon;
use DateTimeInterface;
use Morilog\Jalali\Jalalian;

class JalaliDate
{
    public static function format(mixed $value, string $format = 'Y/m/d H:i'): string
    {
        if (! $value) {
            return '—';
        }

        try {
            $carbon = $value instanceof DateTimeInterface
                ? Carbon::instance($value)
                : Carbon::parse($value);
        } catch (\Throwable $e) {
            return '—';
        }

        return Jalalian::fromCarbon($carbon)->format($format);
    }

    public static function parse(?string $value, bool $withTime = false): ?Carbon
    {
        if (! $value) {
            return null;
        }

        $value = trim($value);
        $value = str_replace('/', '-', $value);
        if ($withTime && preg_match('/^\d{1,2}:\d{2}(?::\d{2})?$/', $value)) {
            $timeParts = array_map('intval', explode(':', $value));
            $hour = $timeParts[0] ?? 0;
            $minute = $timeParts[1] ?? 0;
            $second = $timeParts[2] ?? 0;

            $now = Carbon::now();
            $carbon = $now->copy()->setTime($hour, $minute, $second);
            if ($carbon->greaterThan($now)) {
                $carbon->subDay();
            }

            return $carbon;
        } else {
        $pattern = $withTime
            ? '/^\d{4}-\d{1,2}-\d{1,2}(?:\s+\d{1,2}:\d{2}(?::\d{2})?)?$/'
            : '/^\d{4}-\d{1,2}-\d{1,2}$/';

        if (! preg_match($pattern, $value)) {
            return null;
        }

        [$datePart, $timePart] = array_pad(explode(' ', $value, 2), 2, null);
        }
        [$year, $month, $day] = array_map('intval', explode('-', $datePart));

        try {
            $jalali = Jalalian::fromFormat('Y-m-d', sprintf('%04d-%02d-%02d', $year, $month, $day));
        } catch (\Throwable $e) {
            return null;
        }

        $carbon = $jalali->toCarbon()->startOfDay();

        if ($withTime && $timePart) {
            $timeParts = array_map('intval', explode(':', $timePart));
            $hour = $timeParts[0] ?? 0;
            $minute = $timeParts[1] ?? 0;
            $second = $timeParts[2] ?? 0;
            $carbon->setTime($hour, $minute, $second);
        }

        return $carbon;
    }

    public static function canParse(?string $value, bool $withTime = false): bool
    {
        return self::parse($value, $withTime) !== null;
    }
}
