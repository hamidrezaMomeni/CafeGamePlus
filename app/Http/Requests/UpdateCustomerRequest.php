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
        ];
    }
}
