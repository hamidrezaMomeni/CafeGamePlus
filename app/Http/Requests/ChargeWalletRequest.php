<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChargeWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1', 'max:100000000000'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'amount' => 'مبلغ شارژ',
            'description' => 'توضیحات',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'مبلغ شارژ را وارد کنید.',
            'amount.integer' => 'مبلغ شارژ باید یک عدد صحیح (به تومان) باشد.',
            'amount.min' => 'مبلغ شارژ باید بیشتر از صفر باشد.',
        ];
    }
}
