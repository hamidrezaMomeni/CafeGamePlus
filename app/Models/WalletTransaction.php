<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    public const TYPE_CHARGE = 'charge';
    public const TYPE_INVOICE_PAYMENT = 'invoice_payment';
    public const TYPE_REFUND = 'refund';

    protected $fillable = [
        'customer_id',
        'invoice_id',
        'user_id',
        'type',
        'amount',
        'balance_after',
        'description',
    ];

    protected $casts = [
        'amount' => 'float',
        'balance_after' => 'float',
    ];

    public static function typeLabels(): array
    {
        return [
            self::TYPE_CHARGE => 'شارژ کیف پول',
            self::TYPE_INVOICE_PAYMENT => 'پرداخت فاکتور',
            self::TYPE_REFUND => 'بازگشت وجه',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}