<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePricingPlanRequest;
use App\Http\Requests\UpdatePricingPlanRequest;
use App\Models\PricingPlan;
use App\Support\JalaliDate;
use Illuminate\Http\RedirectResponse;

class PricingPlanController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function store(StorePricingPlanRequest $request): RedirectResponse
    {
        $data = $this->normalizeData($request->validated());

        PricingPlan::create($data);

        return redirect()->route('dashboard')->with('success', 'طرح قیمتی با موفقیت ایجاد شد.');
    }

    public function edit(PricingPlan $pricingPlan): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function update(UpdatePricingPlanRequest $request, PricingPlan $pricingPlan): RedirectResponse
    {
        $data = $this->normalizeData($request->validated());

        $pricingPlan->update($data);

        return redirect()->route('dashboard')->with('success', 'طرح قیمتی با موفقیت ویرایش شد.');
    }

    public function destroy(PricingPlan $pricingPlan): RedirectResponse
    {
        $pricingPlan->delete();

        return redirect()->route('dashboard')->with('success', 'طرح قیمتی حذف شد.');
    }

    protected function normalizeData(array $data): array
    {
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['priority'] = (int) ($data['priority'] ?? 100);
        $data['config'] = $this->extractConfig($data);
        $data['starts_at'] = ! empty($data['starts_at'])
            ? JalaliDate::parse($data['starts_at'])
            : null;
        $data['ends_at'] = ! empty($data['ends_at'])
            ? JalaliDate::parse($data['ends_at'])
            : null;

        unset(
            $data['threshold_minutes'],
            $data['bonus_minutes'],
            $data['min_minutes'],
            $data['discount_percent'],
            $data['start_time'],
            $data['end_time'],
            $data['days_of_week'],
            $data['lookback_days'],
            $data['min_total_minutes']
        );

        return $data;
    }

    protected function extractConfig(array $data): array
    {
        $type = $data['type'] ?? null;

        return match ($type) {
            PricingPlan::TYPE_BONUS_TIME => [
                'threshold_minutes' => (int) ($data['threshold_minutes'] ?? 0),
                'bonus_minutes' => (int) ($data['bonus_minutes'] ?? 0),
            ],
            PricingPlan::TYPE_DURATION_DISCOUNT => [
                'min_minutes' => (int) ($data['min_minutes'] ?? 0),
                'discount_percent' => (int) ($data['discount_percent'] ?? 0),
            ],
            PricingPlan::TYPE_HAPPY_HOUR => [
                'discount_percent' => (int) ($data['discount_percent'] ?? 0),
                'start_time' => (string) ($data['start_time'] ?? ''),
                'end_time' => (string) ($data['end_time'] ?? ''),
                'days_of_week' => array_values(array_map('intval', $data['days_of_week'] ?? [])),
            ],
            PricingPlan::TYPE_WEEKLY_VOLUME_DISCOUNT => [
                'lookback_days' => (int) ($data['lookback_days'] ?? 7),
                'min_total_minutes' => (int) ($data['min_total_minutes'] ?? 0),
                'discount_percent' => (int) ($data['discount_percent'] ?? 0),
            ],
            default => [],
        };
    }
}
