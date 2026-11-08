@forelse ($customers as $customer)
    <tr>
        <td data-label="نام">{{ $customer->name }}</td>
        <td data-label="شماره تماس">{{ $customer->phone }}</td>
        <td data-label="کد ملی">{{ $customer->national_id }}</td>
        <td data-label="ایمیل">{{ $customer->email ?? '—' }}</td>
        <td data-label="مبلغ کل">{{ number_format($customer->total_spend) }}</td>
        <td data-label="تعداد مراجعه">{{ $customer->visit_count }}</td>
        <td data-label="فاکتور">{{ $customer->invoices_count }}</td>
        <td data-label="کیف پول (تومان)">
            @if ($customer->wallet_balance < 0)
                <span class="badge badge--warning">بدهکار: {{ number_format(abs($customer->wallet_balance)) }}</span>
            @else
                {{ number_format($customer->wallet_balance) }}
            @endif
        </td>
        <td data-label="سقف اعتبار (تومان)">
            {{ number_format($customer->effective_credit_limit) }}
            @unless ($customer->has_custom_credit_limit)
                <span class="muted">(پیش‌فرض)</span>
            @endunless
        </td>
        <td data-label="اقدام">
            <button class="btn btn--sm btn--success" data-modal-open="modal-wallet-charge" data-edit="1" data-id="{{ $customer->id }}" data-fields='{{ json_encode([
                'customer_name' => $customer->name,
            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}'>شارژ کیف پول</button>
            <a class="btn btn--sm btn--ghost" href="{{ route('customers.wallet', $customer) }}">تراکنش‌ها</a>
            <button class="btn btn--sm" data-modal-open="modal-customer" data-edit="1" data-id="{{ $customer->id }}" data-fields='{{ json_encode([
                'name' => $customer->name,
                'phone' => $customer->phone,
                'national_id' => $customer->national_id,
                'email' => $customer->email,
                'credit_limit' => $customer->credit_limit !== null ? (int) $customer->credit_limit : '',
            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}'>ویرایش</button>
            <form method="POST" action="{{ route('customers.destroy', $customer) }}" class="inline-form" data-confirm="حذف مشتری انجام شود؟">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn--danger btn--sm">حذف</button>
            </form>
        </td>
    </tr>
@empty
    <tr><td colspan="10" class="muted">{{ ($search ?? '') !== '' ? 'مشتری با این مشخصات یافت نشد.' : 'مشتری ثبت نشده است.' }}</td></tr>
@endforelse