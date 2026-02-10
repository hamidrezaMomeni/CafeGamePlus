<?php

namespace App\Services;

use App\Models\ConsoleSession;
use App\Models\BoardGameSession;
use App\Models\Customer;
use App\Models\PricingPlan;
use App\Models\TableSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;

class PricingPlanService
{
    protected static ?bool $pricingPlansTableExists = null;

    /**
     * @return Collection<int, PricingPlan>
     */
    public function getActivePlans(string $appliesTo, Carbon $at): Collection
    {
        if (! $this->pricingPlansTableExists()) {
            return collect();
        }

        return PricingPlan::query()
            ->active($at)
            ->whereIn('applies_to', [PricingPlan::APPLIES_BOTH, $appliesTo])
            ->orderBy('priority')
            ->get();
    }

    public function bonusMinutesForPlannedDuration(string $appliesTo, Carbon $startTime, int $plannedDurationMinutes): int
    {
        $bonus = 0;

        $plans = $this->getActivePlans($appliesTo, $startTime);

        foreach ($plans as $plan) {
            if ($plan->type !== PricingPlan::TYPE_BONUS_TIME) {
                continue;
            }

            $threshold = (int) ($plan->config['threshold_minutes'] ?? 0);
            $bonusMinutes = (int) ($plan->config['bonus_minutes'] ?? 0);

            if ($threshold <= 0 || $bonusMinutes <= 0) {
                continue;
            }

            if ($plannedDurationMinutes >= $threshold) {
                $bonus += $bonusMinutes;
            }
        }

        return max(0, $bonus);
    }

    /**
     * @return array{bonus_minutes:int,discount_percent:int}
     */
    public function adjustmentsForSession(
        string $appliesTo,
        ?Customer $customer,
        Carbon $startTime,
        Carbon $endTime,
        int $durationMinutes
    ): array {
        $plans = $this->getActivePlans($appliesTo, $startTime);

        $bonusMinutes = 0;
        $discountPercent = 0;

        foreach ($plans as $plan) {
            $config = $plan->config ?? [];

            if ($plan->type === PricingPlan::TYPE_BONUS_TIME) {
                $threshold = (int) ($config['threshold_minutes'] ?? 0);
                $bonus = (int) ($config['bonus_minutes'] ?? 0);

                if ($threshold > 0 && $bonus > 0 && $durationMinutes > $threshold) {
                    $usable = max(0, $durationMinutes - $threshold);
                    $bonusMinutes += min($bonus, $usable);
                }

                continue;
            }

            if ($plan->type === PricingPlan::TYPE_DURATION_DISCOUNT) {
                $minMinutes = (int) ($config['min_minutes'] ?? 0);
                $percent = (int) ($config['discount_percent'] ?? 0);

                if ($minMinutes > 0 && $percent > 0 && $durationMinutes >= $minMinutes) {
                    $discountPercent += $percent;
                }

                continue;
            }

            if ($plan->type === PricingPlan::TYPE_HAPPY_HOUR) {
                $percent = (int) ($config['discount_percent'] ?? 0);
                $start = (string) ($config['start_time'] ?? '');
                $end = (string) ($config['end_time'] ?? '');
                $days = array_values(array_map('intval', $config['days_of_week'] ?? []));

                if ($percent <= 0 || $start === '' || $end === '') {
                    continue;
                }

                if (count($days) > 0 && ! in_array($startTime->dayOfWeek, $days, true)) {
                    continue;
                }

                if ($this->isTimeInRange($startTime, $start, $end)) {
                    $discountPercent += $percent;
                }

                continue;
            }

            if ($plan->type === PricingPlan::TYPE_WEEKLY_VOLUME_DISCOUNT) {
                $percent = (int) ($config['discount_percent'] ?? 0);
                $lookbackDays = (int) ($config['lookback_days'] ?? 7);
                $minTotal = (int) ($config['min_total_minutes'] ?? 0);

                if ($percent <= 0 || $lookbackDays <= 0 || $minTotal <= 0) {
                    continue;
                }

                if (! $customer) {
                    continue;
                }

                $since = $endTime->copy()->subDays($lookbackDays);

                $totalMinutes = (int) ConsoleSession::query()
                    ->where('customer_id', $customer->id)
                    ->where('status', 'completed')
                    ->where('end_time', '>=', $since)
                    ->sum('duration_minutes');

                $totalMinutes += (int) TableSession::query()
                    ->where('customer_id', $customer->id)
                    ->where('status', 'completed')
                    ->where('end_time', '>=', $since)
                    ->sum('duration_minutes');

                $totalMinutes += (int) BoardGameSession::query()
                    ->where('customer_id', $customer->id)
                    ->where('status', 'completed')
                    ->where('end_time', '>=', $since)
                    ->sum('duration_minutes');

                $totalMinutes += $durationMinutes;

                if ($totalMinutes >= $minTotal) {
                    $discountPercent += $percent;
                }

                continue;
            }
        }

        $bonusMinutes = max(0, $bonusMinutes);
        $discountPercent = max(0, min(100, $discountPercent));

        return [
            'bonus_minutes' => $bonusMinutes,
            'discount_percent' => $discountPercent,
        ];
    }

    protected function isTimeInRange(Carbon $time, string $start, string $end): bool
    {
        $timeMinutes = ((int) $time->format('H')) * 60 + (int) $time->format('i');

        [$startHour, $startMinute] = array_map('intval', explode(':', $start) + [0, 0]);
        [$endHour, $endMinute] = array_map('intval', explode(':', $end) + [0, 0]);

        $startMinutes = ($startHour * 60) + $startMinute;
        $endMinutes = ($endHour * 60) + $endMinute;

        if ($startMinutes === $endMinutes) {
            return true;
        }

        if ($startMinutes < $endMinutes) {
            return $timeMinutes >= $startMinutes && $timeMinutes <= $endMinutes;
        }

        // crosses midnight
        return $timeMinutes >= $startMinutes || $timeMinutes <= $endMinutes;
    }

    protected function pricingPlansTableExists(): bool
    {
        if (self::$pricingPlansTableExists === null) {
            self::$pricingPlansTableExists = Schema::hasTable('pricing_plans');
        }

        return self::$pricingPlansTableExists;
    }
}
