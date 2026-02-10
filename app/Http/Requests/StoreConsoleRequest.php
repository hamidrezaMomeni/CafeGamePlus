<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsoleRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'type' => 'required|in:PS4,PS5,XBOX,PC,Other',
            'hourly_rate_single' => 'required|numeric|min:0',
            'hourly_rate_double' => 'required|numeric|min:0',
            'hourly_rate_triple' => 'required|numeric|min:0',
            'hourly_rate_quadruple' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام کنسول الزامی است.',
            'type.required' => 'نوع کنسول الزامی است.',
            'type.in' => 'نوع کنسول معتبر نیست.',
        ];
    }
}
