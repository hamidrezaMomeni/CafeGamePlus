@extends('layouts.app')

@section('title', 'کیف پول ' . $customer->name)
@section('page_title', 'کیف پول مشتری')

@section('sidebar')
    <a href="{{ route('dashboard') }}" class="nav__link">بازگشت به داشبورد</a>
    <a href="{{ route('dashboard') }}#customers" class="nav__link">مشتری‌ها</a>
@endsection

@section('content')
<section class="section">
    <div class="section__header">
        <div>
            <h2>{{ $customer->name }}</h2>
            <p>{{ $customer->phone }}</p>
        </div>
        <div class="section__actions">
            <a class="btn btn--ghost" href="{{ route('dashboard') }}#customers">بازگشت به مشتری‌ها</a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card {{ $customer->wallet_balance < 0 ? '' : 'stat-card--accent' }}">
            <div class="stat-card__label">{{ $customer->wallet_balance < 0 ? 'بدهی مشتری' : 'موجودی کیف پول' }}</div>
            <div class="stat-card__value">{{ number_format(abs($customer->wallet_balance)) }}</div>
            <div class="stat-card__meta">تومان</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__label">سقف اعتبار</div>
            <div class="stat-card__value">{{ number_format($customer->effective_credit_limit) }}</div>
            <div class="stat-card__meta">
                {{ $customer->has_custom_credit_limit ? 'سقف اختصاصی مشتری' : 'پیش‌فرض سیستم' }}
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card__label">حداکثر مبلغ قابل پرداخت از کیف پول</div>
            <div class="stat-card__value">{{ number_format($customer->available_credit) }}</div>
            <div class="stat-card__meta">موجودی + اعتبار مجاز • تومان</div>
        </div>
    </div>
</section>

<section class="section">
    <div class="section__header">
        <div>
            <h2>شارژ دستی کیف پول</h2>
            <p>مبلغ دریافتی (نقدی یا کارت‌به‌کارت) را اینجا ثبت کنید. اگر مشتری بدهکار باشد، ابتدا بدهی او تسویه می‌شود.</p>
        </div>
    </div>
    <form method="POST" action="{{ route('customers.wallet.charge', $customer) }}">
        @csrf
        <div class="form-grid">
            <label class="field">
                <span>مبلغ شارژ (تومان)</span>
                <input type="number" name="amount" min="1" step="1" inputmode="numeric" value="{{ old('amount') }}" required>
            </label>
            <label class="field">
                <span>توضیحات (اختیاری)</span>
                <input type="text" name="description" maxlength="255" value="{{ old('description') }}" placeholder="مثلاً: واریز نقدی / کارت‌به‌کارت">
            </label>
        </div>
        <div class="modal__footer">
            <button type="submit" class="btn btn--primary">شارژ کیف پول</button>
        </div>
    </form>
</section>

<section class="section">
    <div class="section__header">
        <div>
            <h2>تاریخچه تراکنش‌ها</h2>
            <p>همهٔ شارژها، پرداخت‌ها و بازگشت وجه‌های این کیف پول.</p>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>تاریخ</th>
                    <th>نوع</th>
                    <th>مبلغ (تومان)</th>
                    <th>موجودی پس از تراکنش</th>
                    <th>فاکتور</th>
                    <th>توضیحات</th>
                    <th>ثبت‌کننده</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($transactions as $tx)
                <tr>
                    <td data-label="تاریخ">@jdate($tx->created_at, 'Y/m/d H:i')</td>
                    <td data-label="نوع">{{ $typeLabels[$tx->type] ?? $tx->type }}</td>
                    <td data-label="مبلغ (تومان)">
                        <span class="badge badge--{{ $tx->amount >= 0 ? 'success' : 'warning' }}">
                            {{ $tx->amount >= 0 ? '+' : '-' }}{{ number_format(abs($tx->amount)) }}
                        </span>
                    </td>
                    <td data-label="موجودی پس از تراکنش">
                        @if ($tx->balance_after < 0)
                            <span class="muted">بدهکار: {{ number_format(abs($tx->balance_after)) }}</span>
                        @else
                            {{ number_format($tx->balance_after) }}
                        @endif
                    </td>
                    <td data-label="فاکتور">{{ $tx->invoice?->invoice_number ?? '—' }}</td>
                    <td data-label="توضیحات">{{ $tx->description ?? '—' }}</td>
                    <td data-label="ثبت‌کننده">{{ $tx->user?->name ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">هنوز تراکنشی ثبت نشده است.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">
        {{ $transactions->links() }}
    </div>
</section>
@endsection