<?php

namespace App\Http\Requests;

use App\Models\BoardGameSession;
use App\Models\ConsoleSession;
use App\Models\TableSession;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
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
            'customer_id' => 'nullable|exists:customers,id',
            'table_id' => 'nullable|exists:tables,id',
            'session_ref' => 'nullable|string',
            'items' => 'required|array',
            'items.*.cafe_item_id' => 'required|exists:cafe_items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $sessionRef = $this->input('session_ref');
            if (! $sessionRef) {
                return;
            }

            if (! preg_match('/^(console|table|board_game):(\d+)$/', $sessionRef, $matches)) {
                $validator->errors()->add('session_ref', 'فرمت انتخاب سشن معتبر نیست.');
                return;
            }

            [$type, $id] = [$matches[1], (int) $matches[2]];

            $exists = match ($type) {
                'console' => ConsoleSession::query()->where('status', 'active')->whereKey($id)->exists(),
                'table' => TableSession::query()->where('status', 'active')->whereKey($id)->exists(),
                'board_game' => BoardGameSession::query()->where('status', 'active')->whereKey($id)->exists(),
                default => false,
            };

            if (! $exists) {
                $validator->errors()->add('session_ref', 'سشن فعال انتخاب شده یافت نشد.');
            }
        });
    }
}
