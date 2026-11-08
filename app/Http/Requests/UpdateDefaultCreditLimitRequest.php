<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDefaultCreditLimitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'default_credit_limit' => ['required', 'integer', 'min:0', 'max:100000000000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'default_credit_limit' => 'سقف اعتبار پیش‌فرض',
        ];
    }

    public function messages(): array
    {
        return [
            'default_credit_limit.required' => 'سقف اعتبار پیش‌فرض را وارد کنید (برای غیرفعال کردن اعتبار عدد ۰ را وارد کنید).',
            'default_credit_limit.integer' => 'سقف اعتبار باید یک عدد صحیح (به تومان) باشد.',
            'default_credit_limit.min' => 'سقف اعتبار نمی‌تواند منفی باشد.',
        ];
    }
}
