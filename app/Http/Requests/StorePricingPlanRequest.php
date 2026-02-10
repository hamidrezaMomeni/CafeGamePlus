<?php

namespace App\Http\Requests;

use App\Models\PricingPlan;
use App\Support\JalaliDate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePricingPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $types = array_keys(PricingPlan::availableTypes());
        $appliesTo = array_keys(PricingPlan::availableAppliesTo());

        return [
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in($types)],
            'applies_to' => ['required', Rule::in($appliesTo)],
            'priority' => 'nullable|integer|min:0|max:1000',
            'is_active' => 'nullable|boolean',
            'starts_at' => 'nullable|string',
            'ends_at' => 'nullable|string',

            // bonus_time
            'threshold_minutes' => 'nullable|required_if:type,bonus_time|integer|min:1|max:100000',
            'bonus_minutes' => 'nullable|required_if:type,bonus_time|integer|min:1|max:100000',

            // duration_discount
            'min_minutes' => 'nullable|required_if:type,duration_discount|integer|min:1|max:100000',
            'discount_percent' => 'nullable|required_if:type,duration_discount,happy_hour,weekly_volume_discount|integer|min:0|max:100',

            // happy_hour
            'start_time' => 'nullable|required_if:type,happy_hour|date_format:H:i',
            'end_time' => 'nullable|required_if:type,happy_hour|date_format:H:i',
            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'integer|min:0|max:6',

            // weekly_volume_discount
            'lookback_days' => 'nullable|required_if:type,weekly_volume_discount|integer|min:1|max:365',
            'min_total_minutes' => 'nullable|required_if:type,weekly_volume_discount|integer|min:1|max:1000000',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $startsAt = $this->input('starts_at');
            $endsAt = $this->input('ends_at');

            if ($startsAt && ! JalaliDate::canParse($startsAt)) {
                $validator->errors()->add('starts_at', 'فرمت تاریخ شروع شمسی معتبر نیست. نمونه: 1403-01-15');
            }

            if ($endsAt && ! JalaliDate::canParse($endsAt)) {
                $validator->errors()->add('ends_at', 'فرمت تاریخ پایان شمسی معتبر نیست. نمونه: 1403-01-15');
            }

            if ($startsAt && $endsAt) {
                $start = JalaliDate::parse($startsAt);
                $end = JalaliDate::parse($endsAt);

                if ($start && $end && $end->lessThan($start)) {
                    $validator->errors()->add('ends_at', 'تاریخ پایان باید بعد از تاریخ شروع باشد.');
                }
            }
        });
    }
}
