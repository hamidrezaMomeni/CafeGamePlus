@extends('layouts.app')

@section('title', 'آمار حسابداری')
@section('page_title', 'گزارش آمار حسابداری')

@section('sidebar')
    <a href="{{ route('dashboard') }}" class="nav__link">بازگشت به داشبورد</a>
    <a href="{{ route('accounting-stats.index', ['range' => $selectedRange]) }}" class="nav__link nav__link--active">آمار حسابداری</a>
    <a href="#overview" class="nav__link">نمای کلی</a>
    <a href="#trends" class="nav__link">نمودارها</a>
    <a href="#breakdown" class="nav__link">تفکیک درآمد</a>
    <a href="#details" class="nav__link">جزئیات</a>
@endsection

@section('content')
@php
    $totalRevenue = (float) ($summary['total_revenue'] ?? 0);
    $trendMaxTotal = max(1, (float) (collect($trendSeries)->max('total') ?? 0));
    $trendMaxSegment = max(1, (float) (collect($trendSeries)->flatMap(fn ($row) => [
        $row['console'] ?? 0,
        $row['table'] ?? 0,
        $row['cafe'] ?? 0,
        $row['board_game'] ?? 0,
    ])->max() ?? 0));
@endphp

<section id="overview" class="section">
    <div class="section__header">
        <div>
            <h2>نمای کلی حسابداری</h2>
            <p>داده‌ها بر اساس فاکتورهای پرداخت‌شده در بازه {{ $dateWindow }} محاسبه شده‌اند.</p>
        </div>
        <div class="range-switch">
            @foreach ($rangeOptions as $key => $range)
                <a href="{{ route('accounting-stats.index', ['range' => $key]) }}" class="btn btn--sm {{ $selectedRange === $key ? 'btn--primary' : 'btn--ghost' }}">
                    {{ $range['label'] }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card stat-card--accent">
            <div class="stat-card__label">درآمد کل بازه</div>
            <div class="stat-card__value">{{ number_format($summary['total_revenue']) }}</div>
            <div class="stat-card__meta">تومان</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__label">فاکتورهای پرداخت‌شده</div>
            <div class="stat-card__value">{{ number_format($summary['paid_invoice_count']) }}</div>
            <div class="stat-card__meta">فاکتور</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__label">میانگین فاکتور</div>
            <div class="stat-card__value">{{ number_format($summary['average_invoice']) }}</div>
            <div class="stat-card__meta">تومان</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__label">درآمد کنسول‌ها</div>
            <div class="stat-card__value">{{ number_format($summary['console_revenue']) }}</div>
            <div class="stat-card__meta">تومان</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__label">درآمد میزها</div>
            <div class="stat-card__value">{{ number_format($summary['table_revenue']) }}</div>
            <div class="stat-card__meta">تومان</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__label">درآمد کافه</div>
            <div class="stat-card__value">{{ number_format($summary['cafe_revenue']) }}</div>
            <div class="stat-card__meta">تومان</div>
        </div>
    </div>

    <div class="quick-range-grid">
        @foreach ($quickSummaries as $key => $item)
            <a href="{{ route('accounting-stats.index', ['range' => $key]) }}" class="quick-range-card {{ $selectedRange === $key ? 'quick-range-card--active' : '' }}">
                <div class="quick-range-card__title">مرور {{ $item['label'] }}</div>
                <div class="quick-range-card__value">{{ number_format($item['revenue']) }}</div>
                <div class="quick-range-card__meta">{{ number_format($item['invoice_count']) }} فاکتور</div>
            </a>
        @endforeach
    </div>
</section>

<section id="trends" class="section">
    <div class="section__header">
        <div>
            <h2>نمودارهای درآمد</h2>
            <p>روند کل درآمد و روند تفکیکی بر اساس کنسول، میز، کافه و بردگیم.</p>
        </div>
    </div>

    <div class="grid-2">
        <div class="panel">
            <div class="panel__header">
                <h3>روند درآمد کل</h3>
            </div>
            <div class="trend-chart">
                @forelse ($trendSeries as $point)
                    @php
                        $height = $point['total'] > 0 ? max(6, round(($point['total'] / $trendMaxTotal) * 100, 2)) : 0;
                    @endphp
                    <div class="trend-chart__item" title="{{ $point['label'] }}: {{ number_format($point['total']) }} تومان">
                        <div class="trend-chart__track">
                            <div class="trend-chart__bar" style="height: {{ $height }}%;"></div>
                        </div>
                        <div class="trend-chart__label">{{ $point['label'] }}</div>
                    </div>
                @empty
                    <div class="muted">داده‌ای برای نمودار وجود ندارد.</div>
                @endforelse
            </div>
        </div>

        <div class="panel">
            <div class="panel__header">
                <h3>روند تفکیکی بخش‌ها</h3>
            </div>
            <div class="segment-legend">
                <span><i class="segment-dot segment-dot--console"></i>کنسول</span>
                <span><i class="segment-dot segment-dot--table"></i>میز</span>
                <span><i class="segment-dot segment-dot--cafe"></i>کافه</span>
                <span><i class="segment-dot segment-dot--board-game"></i>بردگیم</span>
            </div>
            <div class="segment-chart">
                @forelse ($trendSeries as $point)
                    @php
                        $consoleHeight = $point['console'] > 0 ? max(4, round(($point['console'] / $trendMaxSegment) * 100, 2)) : 0;
                        $tableHeight = $point['table'] > 0 ? max(4, round(($point['table'] / $trendMaxSegment) * 100, 2)) : 0;
                        $cafeHeight = $point['cafe'] > 0 ? max(4, round(($point['cafe'] / $trendMaxSegment) * 100, 2)) : 0;
                        $boardGameHeight = $point['board_game'] > 0 ? max(4, round(($point['board_game'] / $trendMaxSegment) * 100, 2)) : 0;
                    @endphp
                    <div class="segment-chart__item" title="{{ $point['label'] }}">
                        <div class="segment-chart__bars">
                            <span class="segment-bar segment-bar--console" style="height: {{ $consoleHeight }}%;"></span>
                            <span class="segment-bar segment-bar--table" style="height: {{ $tableHeight }}%;"></span>
                            <span class="segment-bar segment-bar--cafe" style="height: {{ $cafeHeight }}%;"></span>
                            <span class="segment-bar segment-bar--board-game" style="height: {{ $boardGameHeight }}%;"></span>
                        </div>
                        <div class="segment-chart__label">{{ $point['label'] }}</div>
                    </div>
                @empty
                    <div class="muted">داده‌ای برای نمودار وجود ندارد.</div>
                @endforelse
            </div>
        </div>
    </div>
</section>

<section id="breakdown" class="section">
    <div class="section__header">
        <div>
            <h2>تفکیک درآمد</h2>
            <p>نمای کلی سهم هر بخش از درآمد بازه انتخابی.</p>
        </div>
    </div>

    <div class="panel">
        @forelse ($categoryRows as $row)
            @php
                $share = $totalRevenue > 0 ? round(($row['revenue'] / $totalRevenue) * 100, 2) : 0;
            @endphp
            <div class="category-progress">
                <div class="category-progress__header">
                    <strong>{{ $row['label'] }}</strong>
                    <span>{{ number_format($row['revenue']) }} تومان</span>
                </div>
                <div class="category-progress__track">
                    <div class="category-progress__fill category-progress__fill--{{ $row['key'] }}" style="width: {{ $share }}%;"></div>
                </div>
                <div class="category-progress__meta">
                    <span>{{ number_format($row['count']) }} آیتم</span>
                    <span>{{ number_format($share, 1) }}٪</span>
                </div>
            </div>
        @empty
            <div class="muted">هیچ داده‌ای برای تفکیک درآمد ثبت نشده است.</div>
        @endforelse
    </div>

    <div class="panel panel--spaced">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>بخش</th>
                        <th>تعداد</th>
                        <th>درآمد</th>
                        <th>سهم از کل</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($categoryRows as $row)
                    @php
                        $share = $totalRevenue > 0 ? ($row['revenue'] / $totalRevenue) * 100 : 0;
                    @endphp
                    <tr>
                        <td data-label="بخش">{{ $row['label'] }}</td>
                        <td data-label="تعداد">{{ number_format($row['count']) }}</td>
                        <td data-label="درآمد">{{ number_format($row['revenue']) }} تومان</td>
                        <td data-label="سهم">{{ number_format($share, 1) }}٪</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">رکوردی برای نمایش وجود ندارد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

<section id="details" class="section">
    <div class="section__header">
        <div>
            <h2>جزئیات کنسول، میز و کافه</h2>
            <p>نمایش مجزای کارکرد و درآمد بر اساس هر بخش.</p>
        </div>
    </div>

    <div class="grid-2">
        <div class="panel">
            <div class="panel__header">
                <h3>کنسول‌ها (بیشترین درآمد)</h3>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>کنسول</th>
                            <th>تعداد سشن</th>
                            <th>مجموع دقیقه</th>
                            <th>درآمد</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($topConsoles as $row)
                        <tr>
                            <td data-label="کنسول">{{ $row['name'] }}</td>
                            <td data-label="تعداد سشن">{{ number_format($row['sessions_count']) }}</td>
                            <td data-label="مجموع دقیقه">{{ number_format($row['total_minutes']) }}</td>
                            <td data-label="درآمد">{{ number_format($row['revenue']) }} تومان</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted">داده‌ای برای کنسول‌ها ثبت نشده است.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <div class="panel__header">
                <h3>میزها (بیشترین درآمد)</h3>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>میز</th>
                            <th>تعداد سشن</th>
                            <th>مجموع دقیقه</th>
                            <th>درآمد</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($topTables as $row)
                        <tr>
                            <td data-label="میز">{{ $row['name'] }}</td>
                            <td data-label="تعداد سشن">{{ number_format($row['sessions_count']) }}</td>
                            <td data-label="مجموع دقیقه">{{ number_format($row['total_minutes']) }}</td>
                            <td data-label="درآمد">{{ number_format($row['revenue']) }} تومان</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted">داده‌ای برای میزها ثبت نشده است.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="panel panel--spaced">
        <div class="panel__header">
            <h3>آیتم‌های کافه (پرفروش‌ترین‌ها)</h3>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>آیتم</th>
                        <th>تعداد فروش</th>
                        <th>تعداد سفارش</th>
                        <th>درآمد</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($topCafeItems as $row)
                    <tr>
                        <td data-label="آیتم">{{ $row['name'] }}</td>
                        <td data-label="تعداد فروش">{{ number_format($row['quantity_sold']) }}</td>
                        <td data-label="تعداد سفارش">{{ number_format($row['orders_count']) }}</td>
                        <td data-label="درآمد">{{ number_format($row['revenue']) }} تومان</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">داده‌ای برای آیتم‌های کافه ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
