<?php

namespace App\Http\Requests;

use App\Support\JalaliDate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreConsoleSessionRequest extends FormRequest
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
        return [
            'console_id' => 'required|exists:consoles,id',
            'customer_id' => 'nullable|exists:customers,id',
            'controller_count' => 'required|in:1,2,3,4',
            'start_time' => 'nullable|string',
            'planned_duration_minutes' => 'nullable|integer|min:1|max:1440',
            'discount_percent' => 'nullable|integer|min:0|max:100',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $startTime = $this->input('start_time');
            if ($startTime && ! JalaliDate::canParse($startTime, true)) {
                $validator->errors()->add('start_time', 'فرمت زمان شروع شمسی معتبر نیست. نمونه: 1403-01-15 18:30 یا 18:30');
            }
        });
    }
}
