<?php

namespace App\Http\Requests;

use App\Support\JalaliDate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBoardGameSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'board_game_id' => 'required|exists:board_games,id',
            'customer_id' => 'nullable|exists:customers,id',
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
