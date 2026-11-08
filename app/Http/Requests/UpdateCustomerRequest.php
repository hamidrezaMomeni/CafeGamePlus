<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],

            'phone' => [
                'required',
                'string',
                Rule::unique('customers', 'phone')
                    ->ignore($this->route('customer')->id),
            ],

            'national_id' => [
                'required',
                'digits:10',
                Rule::unique('customers', 'national_id')
                    ->ignore($this->route('customer')->id),
            ],

            'email' => ['nullable', 'email'],

            // خالی = استفاده از سقف اعتبار پیش‌فرض سیستم
            'credit_limit' => ['nullable', 'integer', 'min:0', 'max:100000000000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'credit_limit' => 'سقف اعتبار',
        ];
    }

    public function messages(): array
    {
        return [
            'credit_limit.integer' => 'سقف اعتبار باید یک عدد صحیح (به تومان) باشد.',
            'credit_limit.min' => 'سقف اعتبار نمی‌تواند منفی باشد.',
        ];
    }
}
