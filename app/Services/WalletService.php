<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletService
{
    /**
     * شارژ دستی کیف پول. اگر مشتری بدهکار باشد، ابتدا بدهی او تسویه می‌شود
     * (چون بدهی همان موجودی منفی است).
     */
    public function charge(Customer $customer, float $amount, ?string $description = null, ?int $userId = null): WalletTransaction
    {
        return DB::transaction(function () use ($customer, $amount, $description, $userId) {
            $locked = Customer::query()->lockForUpdate()->findOrFail($customer->id);

            $newBalance = round((float) $locked->wallet_balance + $amount, 2);
            $locked->wallet_balance = $newBalance;
            $locked->save();

            return $locked->walletTransactions()->create([
                'user_id' => $userId,
                'type' => WalletTransaction::TYPE_CHARGE,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'description' => $description,
            ]);
        });
    }

    /**
     * پرداخت فاکتور از کیف پول. باید داخل تراکنش دیتابیسِ پرداخت فاکتور صدا زده شود.
     * اگر موجودی + سقف اعتبار مجاز کافی نباشد ValidationException پرتاب می‌شود.
     */
    public function payInvoice(Invoice $invoice, ?int $userId = null): ?WalletTransaction
    {
        if (! $invoice->customer_id) {
            throw ValidationException::withMessages([
                'wallet' => 'پرداخت از کیف پول فقط برای فاکتورهای دارای مشتری امکان‌پذیر است (فاکتور مهمان کیف پول ندارد).',
            ]);
        }

        $customer = Customer::query()->lockForUpdate()->find($invoice->customer_id);

        if (! $customer) {
            throw ValidationException::withMessages([
                'wallet' => 'مشتری این فاکتور یافت نشد.',
            ]);
        }

        $amount = round((float) $invoice->total_amount, 2);
        $balance = round((float) $customer->wallet_balance, 2);
        $limit = round((float) $customer->effective_credit_limit, 2);
        $maxPayable = max(0, round($balance + $limit, 2));

        if ($amount > $maxPayable) {
            throw ValidationException::withMessages([
                'wallet' => sprintf(
                    'موجودی کیف پول و اعتبار مجاز برای پرداخت این فاکتور کافی نیست. '
                    . 'مبلغ فاکتور: %s تومان | موجودی کیف پول: %s تومان | سقف اعتبار: %s تومان | حداکثر مبلغ قابل پرداخت: %s تومان',
                    number_format($amount),
                    $this->formatSigned($balance),
                    number_format($limit),
                    number_format($maxPayable)
                ),
            ]);
        }

        if ($amount <= 0) {
            return null;
        }

        $newBalance = round($balance - $amount, 2);
        $customer->wallet_balance = $newBalance;
        $customer->save();

        return $customer->walletTransactions()->create([
            'invoice_id' => $invoice->id,
            'user_id' => $userId,
            'type' => WalletTransaction::TYPE_INVOICE_PAYMENT,
            'amount' => -$amount,
            'balance_after' => $newBalance,
            'description' => 'پرداخت فاکتور ' . $invoice->invoice_number,
        ]);
    }

    /**
     * بازگرداندن مبلغ پرداخت‌شده از کیف پول برای یک فاکتور (مثلاً هنگام حذف فاکتور).
     */
    public function refundInvoice(Invoice $invoice, ?int $userId = null): ?WalletTransaction
    {
        if (! $invoice->customer_id) {
            return null;
        }

        return DB::transaction(function () use ($invoice, $userId) {
            $customer = Customer::query()->lockForUpdate()->find($invoice->customer_id);

            if (! $customer) {
                return null;
            }

            // جمع جبری تراکنش‌های این فاکتور (پرداخت‌ها منفی، بازگشت‌ها مثبت)
            $net = (float) WalletTransaction::query()
                ->where('invoice_id', $invoice->id)
                ->where('customer_id', $customer->id)
                ->sum('amount');

            $refundable = round(-$net, 2);

            if ($refundable <= 0) {
                return null;
            }

            $newBalance = round((float) $customer->wallet_balance + $refundable, 2);
            $customer->wallet_balance = $newBalance;
            $customer->save();

            return $customer->walletTransactions()->create([
                'invoice_id' => $invoice->id,
                'user_id' => $userId,
                'type' => WalletTransaction::TYPE_REFUND,
                'amount' => $refundable,
                'balance_after' => $newBalance,
                'description' => 'بازگشت وجه بابت حذف فاکتور ' . $invoice->invoice_number,
            ]);
        });
    }

    protected function formatSigned(float $value): string
    {
        return $value < 0
            ? number_format(abs($value)) . '- (بدهکار)'
            : number_format($value);
    }
}