<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'national_id',
        'email',
        'total_spend',
        'visit_count',
        'credit_limit',
    ];

    // wallet_balance عمداً در fillable نیست؛ فقط از طریق WalletService تغییر می‌کند.
    protected $casts = [
        'wallet_balance' => 'float',
        'credit_limit' => 'float',
    ];

    /**
     * جستجو در نام، شماره موبایل و کد ملی. عبارت به کلمات شکسته می‌شود و هر کلمه
     * باید در یکی از این فیلدها پیدا شود. ارقام فارسی/عربی و ی/ک عربی نرمال می‌شوند.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $digits = strtr($term, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        $tokens = preg_split('/\s+/u', $digits, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($tokens as $token) {
            $variants = array_unique([
                $token,
                strtr($token, ['ي' => 'ی', 'ك' => 'ک']),
                strtr($token, ['ی' => 'ي', 'ک' => 'ك']),
            ]);

            $query->where(function (Builder $q) use ($variants) {
                foreach ($variants as $variant) {
                    $like = '%' . addcslashes($variant, '\\%_') . '%';
                    $q->orWhere('name', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('national_id', 'like', $like);
                }
            });
        }

        return $query;
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * سقف اعتبار مؤثر: اگر برای مشتری سقف اختصاصی تعریف شده همان، وگرنه پیش‌فرض سیستم.
     * توجه: مقدار ۰ هم یک سقف اختصاصی معتبر است (یعنی بدون اعتبار).
     */
    protected function effectiveCreditLimit(): Attribute
    {
        return Attribute::get(fn () => $this->credit_limit !== null
            ? (float) $this->credit_limit
            : Setting::defaultCreditLimit());
    }

    protected function hasCustomCreditLimit(): Attribute
    {
        return Attribute::get(fn () => $this->credit_limit !== null);
    }

    /**
     * بیشترین مبلغی که مشتری می‌تواند از کیف پول خرج کند (موجودی + اعتبار مجاز).
     */
    protected function availableCredit(): Attribute
    {
        return Attribute::get(fn () => max(0, (float) $this->wallet_balance + $this->effective_credit_limit));
    }

    protected function debt(): Attribute
    {
        return Attribute::get(fn () => $this->wallet_balance < 0 ? abs((float) $this->wallet_balance) : 0.0);
    }
}