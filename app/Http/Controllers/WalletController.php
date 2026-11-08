<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChargeWalletRequest;
use App\Http\Requests\UpdateDefaultCreditLimitRequest;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function __construct(protected WalletService $walletService)
    {
    }

    public function show(Customer $customer): View
    {
        $transactions = $customer->walletTransactions()
            ->with(['invoice', 'user'])
            ->latest('id')
            ->paginate(20);

        return view('customers.wallet', [
            'customer' => $customer,
            'transactions' => $transactions,
            'typeLabels' => WalletTransaction::typeLabels(),
            'defaultCreditLimit' => Setting::defaultCreditLimit(),
        ]);
    }

    public function charge(ChargeWalletRequest $request, Customer $customer): RedirectResponse
    {
        $transaction = $this->walletService->charge(
            $customer,
            (float) $request->validated('amount'),
            $request->validated('description'),
            $request->user()?->id
        );

        return back()->with(
            'success',
            sprintf(
                'کیف پول %s به مبلغ %s تومان شارژ شد. موجودی جدید: %s تومان',
                $customer->name,
                number_format($transaction->amount),
                $this->formatBalance($transaction->balance_after)
            )
        );
    }

    public function updateDefaultCreditLimit(UpdateDefaultCreditLimitRequest $request): RedirectResponse
    {
        Setting::set(Setting::DEFAULT_CREDIT_LIMIT, (int) $request->validated('default_credit_limit'));

        return redirect()->route('dashboard')->with('success', 'سقف اعتبار پیش‌فرض مشتریان ذخیره شد.');
    }

    protected function formatBalance(float $balance): string
    {
        return $balance < 0
            ? number_format(abs($balance)) . '- (بدهکار)'
            : number_format($balance);
    }
}
