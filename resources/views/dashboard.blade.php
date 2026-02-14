@extends('layouts.app')

@section('title', 'داشبورد')
@section('page_title', 'مدیریت گیم سنتر')

@section('sidebar')
    <a href="#overview" class="nav__link">نمای کلی</a>
    @if (auth()->user()?->hasPermission('console_sessions.manage') || auth()->user()?->hasPermission('table_sessions.manage') || auth()->user()?->hasPermission('board_game_sessions.manage'))
        <a href="#sessions" class="nav__link">سشن‌ها</a>
    @endif
    @if (auth()->user()?->hasPermission('consoles.manage'))
        <a href="#consoles" class="nav__link">کنسول‌ها</a>
    @endif
    @if (auth()->user()?->hasPermission('tables.manage'))
        <a href="#tables" class="nav__link">میزها</a>
    @endif
    @if (auth()->user()?->hasPermission('board_games.manage'))
        <a href="#board-games" class="nav__link">بردگیم‌ها</a>
    @endif
    @if (auth()->user()?->hasPermission('cafe_items.manage'))
        <a href="#cafe" class="nav__link">منوی کافه</a>
    @endif
    @if (auth()->user()?->hasPermission('customers.manage'))
        <a href="#customers" class="nav__link">مشتری‌ها</a>
    @endif
    @if (auth()->user()?->hasPermission('orders.manage'))
        <a href="#orders" class="nav__link">سفارش‌ها</a>
    @endif
    @if (auth()->user()?->hasPermission('invoices.manage'))
        <a href="#invoices" class="nav__link">فاکتورها</a>
        <a href="{{ route('accounting-stats.index') }}" class="nav__link">آمار حسابداری</a>
    @endif
    @if (auth()->user()?->hasPermission('pricing_plans.manage'))
        <a href="#pricing-plans" class="nav__link">طرح‌های قیمتی</a>
    @endif
    @if (auth()->user()?->isSuperAdmin())
        <a href="#users" class="nav__link">کاربران</a>
    @endif
@endsection

@section('content')
@php
    $user = auth()->user();
    $consoleStatusLabels = ['available' => 'آزاد', 'busy' => 'مشغول', 'maintenance' => 'تعمیر'];
    $tableTypes = ['billiard' => 'بیلیارد', 'snooker' => 'اسنوکر'];
    $consoleTypes = ['PS4' => 'PS4', 'PS5' => 'PS5', 'XBOX' => 'Xbox', 'PC' => 'PC', 'Other' => 'سایر'];
    $sessionStatusLabels = ['active' => 'فعال', 'completed' => 'پایان یافته'];
    $invoiceStatusLabels = ['pending' => 'در انتظار پرداخت', 'paid' => 'پرداخت شده'];
    $resolveSessionStart = function ($startTime) {
        if (! $startTime) {
            return null;
        }

        $now = now($startTime->getTimezone());

        if ($startTime->greaterThan($now)) {
            $candidate = $now->copy()->setTimeFromTimeString($startTime->format('H:i:s'));
            if ($candidate->greaterThan($now)) {
                $candidate->subDay();
            }
            return $candidate;
        }

        return $startTime;
    };
@endphp

<section id="overview" class="section">
    <div class="section__header">
        <div>
            <h2>نمای کلی</h2>
            <p>وضعیت لحظه‌ای سالن و درآمد امروز.</p>
        </div>
        <div class="section__actions">
            @if ($user?->hasPermission('console_sessions.manage'))
                <button class="btn btn--primary" data-modal-open="modal-console-session">شروع سشن کنسول</button>
            @endif
            @if ($user?->hasPermission('table_sessions.manage'))
                <button class="btn btn--primary" data-modal-open="modal-table-session">شروع سشن میز</button>
            @endif
            @if ($user?->hasPermission('board_game_sessions.manage'))
                <button class="btn btn--primary" data-modal-open="modal-board-game-session">شروع سشن بردگیم</button>
            @endif
            @if ($user?->hasPermission('orders.manage'))
                <button class="btn btn--ghost" data-modal-open="modal-order">ثبت سفارش کافه</button>
            @endif
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-card__label">کنسول‌ها</div>
            <div class="stat-card__value">{{ $stats['total_consoles'] }}</div>
            <div class="stat-card__meta">آزاد: {{ $stats['available_consoles'] }} | مشغول: {{ $stats['busy_consoles'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__label">میزها</div>
            <div class="stat-card__value">{{ $stats['total_tables'] }}</div>
            <div class="stat-card__meta">آزاد: {{ $stats['available_tables'] }} | مشغول: {{ $stats['busy_tables'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__label">بردگیم‌ها</div>
            <div class="stat-card__value">{{ $stats['total_board_games'] }}</div>
            <div class="stat-card__meta">آزاد: {{ $stats['available_board_games'] }} | مشغول: {{ $stats['busy_board_games'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__label">مشتری‌ها</div>
            <div class="stat-card__value">{{ $stats['total_customers'] }}</div>
            <div class="stat-card__meta">سشن فعال: {{ $stats['active_console_sessions'] + $stats['active_table_sessions'] + $stats['active_board_game_sessions'] }}</div>
        </div>
        <div class="stat-card stat-card--accent stat-card--sensitive" data-sensitive-card data-sensitive-show-text="تومان • برای نمایش کلیک کنید" data-sensitive-hide-text="تومان • برای مخفی‌سازی کلیک کنید" role="button" tabindex="0" aria-pressed="false">
            <div class="stat-card__label">درآمد امروز</div>
            <div class="stat-card__value">{{ number_format($stats['today_revenue']) }}</div>
            <div class="stat-card__meta">تومان • برای نمایش کلیک کنید</div>
            <div class="stat-card__overlay" aria-hidden="true">برای نمایش مبلغ کلیک کنید</div>
        </div>
    </div>
</section>

@if ($user?->hasPermission('console_sessions.manage') || $user?->hasPermission('table_sessions.manage') || $user?->hasPermission('board_game_sessions.manage'))
<section id="sessions" class="section" data-server-now-ts="{{ now()->getTimestamp() }}">
    <div class="section__header">
        <div>
            <h2>سشن‌ها</h2>
            <p>مدیریت شروع و پایان سشن‌ها بدون خروج از داشبورد.</p>
        </div>
        <div class="section__actions">
            <form method="POST" action="{{ route('system.tick') }}" class="inline-form" data-auto-submit>
                @csrf
                <button type="submit" class="btn btn--ghost">همگام‌سازی خودکار</button>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel__header">
            <h3>سشن‌های کنسول</h3>
            @if ($user?->hasPermission('console_sessions.manage'))
                <button class="btn btn--sm" data-modal-open="modal-console-session">شروع سشن</button>
            @endif
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>کنسول</th>
                        <th>مشتری</th>
                        <th>کنترلر</th>
                        <th>شروع</th>
                        <th>پایان برنامه‌ریزی</th>
                        <th>گذشته</th>
                        <th>وضعیت</th>
                        <th>اقدام</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($consoleSessions as $session)
                    @php $effectiveStart = $resolveSessionStart($session->start_time); @endphp
                    <tr>
                        <td data-label="کنسول">{{ $session->console?->name ?? '—' }}</td>
                        <td data-label="مشتری">{{ $session->customer?->name ?? 'مهمان' }}</td>
                        <td data-label="کنترلر">{{ $session->controller_count }}</td>
                        <td data-label="شروع">{{ optional($effectiveStart)->format('H:i') }}</td>
                        <td data-label="پایان برنامه‌ریزی">{{ optional($session->planned_end_time)->format('H:i') ?? '—' }}</td>
                        <td data-label="گذشته">
                            @if ($session->status === 'active' && $effectiveStart)
                                <span class="live-timer" data-elapsed-start-ts="{{ $effectiveStart->getTimestamp() }}" data-elapsed-start="{{ $effectiveStart->toIso8601String() }}">00:00:00</span>
                            @elseif ($session->duration_minutes)
                                {{ $session->duration_minutes }} دقیقه
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td data-label="وضعیت"><span class="badge badge--{{ $session->status === 'active' ? 'success' : 'muted' }}">{{ $sessionStatusLabels[$session->status] ?? $session->status }}</span></td>
                        <td data-label="اقدام">
                            @if ($session->status === 'active' && $user?->hasPermission('console_sessions.manage'))
                                <form method="POST" action="{{ route('console-sessions.end', $session) }}" class="inline-form" data-confirm="پایان دادن سشن کنسول؟">
                                    @csrf
                                    <button type="submit" class="btn btn--danger btn--sm">پایان</button>
                                </form>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">سشنی ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">
            {{ $consoleSessions->appends(request()->except('console_sessions_page'))->fragment('sessions')->links() }}
        </div>
    </div>

    <div class="panel panel--spaced">
        <div class="panel__header">
            <h3>سشن‌های میز</h3>
            @if ($user?->hasPermission('table_sessions.manage'))
                <button class="btn btn--sm" data-modal-open="modal-table-session">شروع سشن</button>
            @endif
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>میز</th>
                        <th>مشتری</th>
                        <th>شروع</th>
                        <th>پایان برنامه‌ریزی</th>
                        <th>فاکتور کافه</th>
                        <th>گذشته</th>
                        <th>وضعیت</th>
                        <th>اقدام</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($tableSessions as $session)
                    @php $effectiveStart = $resolveSessionStart($session->start_time); @endphp
                    <tr>
                        <td data-label="میز">{{ $session->table?->name ?? '—' }}</td>
                        <td data-label="مشتری">{{ $session->customer?->name ?? 'مهمان' }}</td>
                        <td data-label="شروع">{{ optional($effectiveStart)->format('H:i') }}</td>
                        <td data-label="پایان برنامه‌ریزی">{{ optional($session->planned_end_time)->format('H:i') ?? '—' }}</td>
                        <td data-label="فاکتور کافه">
                            @php
                                $pendingCafeOrders = $pendingOrdersByTable->get($session->table_id, collect());
                            @endphp
                            @if ($pendingCafeOrders->count())
                                <div class="badge badge--warning">
                                    {{ $pendingCafeOrders->map(fn ($order) => $order->invoice?->invoice_number ?? ('#' . $order->id))->implode('، ') }}
                                </div>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td data-label="گذشته">
                            @if ($session->status === 'active' && $effectiveStart)
                                <span class="live-timer" data-elapsed-start-ts="{{ $effectiveStart->getTimestamp() }}" data-elapsed-start="{{ $effectiveStart->toIso8601String() }}">00:00:00</span>
                            @elseif ($session->duration_minutes)
                                {{ $session->duration_minutes }} دقیقه
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td data-label="وضعیت"><span class="badge badge--{{ $session->status === 'active' ? 'success' : 'muted' }}">{{ $sessionStatusLabels[$session->status] ?? $session->status }}</span></td>
                        <td data-label="اقدام">
                            @if ($session->status === 'active' && $user?->hasPermission('table_sessions.manage'))
                                <form method="POST" action="{{ route('table-sessions.end', $session) }}" class="inline-form" data-confirm="پایان دادن سشن میز؟">
                                    @csrf
                                    <button type="submit" class="btn btn--danger btn--sm">پایان</button>
                                </form>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">سشنی ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">
            {{ $tableSessions->appends(request()->except('table_sessions_page'))->fragment('sessions')->links() }}
        </div>
    </div>

    <div class="panel panel--spaced">
        <div class="panel__header">
            <h3>سشن‌های بردگیم</h3>
            @if ($user?->hasPermission('board_game_sessions.manage'))
                <button class="btn btn--sm" data-modal-open="modal-board-game-session">شروع سشن</button>
            @endif
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>بردگیم</th>
                        <th>مشتری</th>
                        <th>شروع</th>
                        <th>پایان برنامه‌ریزی</th>
                        <th>گذشته</th>
                        <th>وضعیت</th>
                        <th>اقدام</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($boardGameSessions as $session)
                    @php $effectiveStart = $resolveSessionStart($session->start_time); @endphp
                    <tr>
                        <td data-label="بردگیم">{{ $session->boardGame?->name ?? '—' }}</td>
                        <td data-label="مشتری">{{ $session->customer?->name ?? 'مهمان' }}</td>
                        <td data-label="شروع">{{ optional($effectiveStart)->format('H:i') }}</td>
                        <td data-label="پایان برنامه‌ریزی">{{ optional($session->planned_end_time)->format('H:i') ?? '—' }}</td>
                        <td data-label="گذشته">
                            @if ($session->status === 'active' && $effectiveStart)
                                <span class="live-timer" data-elapsed-start-ts="{{ $effectiveStart->getTimestamp() }}" data-elapsed-start="{{ $effectiveStart->toIso8601String() }}">00:00:00</span>
                            @elseif ($session->duration_minutes)
                                {{ $session->duration_minutes }} دقیقه
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td data-label="وضعیت"><span class="badge badge--{{ $session->status === 'active' ? 'success' : 'muted' }}">{{ $sessionStatusLabels[$session->status] ?? $session->status }}</span></td>
                        <td data-label="اقدام">
                            @if ($session->status === 'active' && $user?->hasPermission('board_game_sessions.manage'))
                                <form method="POST" action="{{ route('board-game-sessions.end', $session) }}" class="inline-form" data-confirm="پایان دادن سشن بردگیم؟">
                                    @csrf
                                    <button type="submit" class="btn btn--danger btn--sm">پایان</button>
                                </form>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">سشنی ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">
            {{ $boardGameSessions->appends(request()->except('board_game_sessions_page'))->fragment('sessions')->links() }}
        </div>
    </div>
</section>
@endif

@if ($user?->hasPermission('consoles.manage'))
<section id="consoles" class="section">
    <div class="section__header">
        <div>
            <h2>کنسول‌ها</h2>
            <p>تنظیم نرخ‌های ساعتی و وضعیت کنسول‌ها.</p>
        </div>
        <div class="section__actions">
            <button class="btn btn--primary" data-modal-open="modal-console">ثبت کنسول جدید</button>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>نام</th>
                    <th>نوع</th>
                    <th>وضعیت</th>
                    <th>نرخ 1 دسته</th>
                    <th>نرخ 2 دسته</th>
                    <th>نرخ 3 دسته</th>
                    <th>نرخ 4 دسته</th>
                    <th>اقدام</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($consoles as $console)
                <tr>
                    <td data-label="نام">{{ $console->name }}</td>
                    <td data-label="نوع">{{ $consoleTypes[$console->type] ?? $console->type }}</td>
                    <td data-label="وضعیت"><span class="badge badge--{{ $console->status === 'available' ? 'success' : ($console->status === 'busy' ? 'warning' : 'muted') }}">{{ $consoleStatusLabels[$console->status] ?? $console->status }}</span></td>
                    <td data-label="نرخ 1 دسته">{{ number_format($console->hourly_rate_single) }}</td>
                    <td data-label="نرخ 2 دسته">{{ number_format($console->hourly_rate_double) }}</td>
                    <td data-label="نرخ 3 دسته">{{ number_format($console->hourly_rate_triple) }}</td>
                    <td data-label="نرخ 4 دسته">{{ number_format($console->hourly_rate_quadruple) }}</td>
                    <td data-label="اقدام">
                        <button class="btn btn--sm" data-modal-open="modal-console" data-edit="1" data-id="{{ $console->id }}" data-fields='{{ json_encode([
                            'name' => $console->name,
                            'type' => $console->type,
                            'status' => $console->status,
                            'hourly_rate_single' => $console->hourly_rate_single,
                            'hourly_rate_double' => $console->hourly_rate_double,
                            'hourly_rate_triple' => $console->hourly_rate_triple,
                            'hourly_rate_quadruple' => $console->hourly_rate_quadruple
                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}'>ویرایش</button>
                        <form method="POST" action="{{ route('consoles.destroy', $console) }}" class="inline-form" data-confirm="حذف کنسول انجام شود؟">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn--danger btn--sm">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">کنسولی ثبت نشده است.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">
        {{ $consoles->appends(request()->except('consoles_page'))->fragment('consoles')->links() }}
    </div>
</section>
@endif

@if ($user?->hasPermission('tables.manage'))
<section id="tables" class="section">
    <div class="section__header">
        <div>
            <h2>میزها</h2>
            <p>تعریف نوع میز و نرخ ساعتی.</p>
        </div>
    <div class="section__actions">
        <button class="btn btn--primary" data-modal-open="modal-table">ثبت میز جدید</button>
    </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>نام</th>
                    <th>نوع</th>
                    <th>وضعیت</th>
                    <th>نرخ ساعتی</th>
                    <th>اقدام</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($tables as $table)
                <tr>
                    <td data-label="نام">{{ $table->name }}</td>
                    <td data-label="نوع">{{ $tableTypes[$table->type] ?? $table->type }}</td>
                    <td data-label="وضعیت"><span class="badge badge--{{ $table->status === 'available' ? 'success' : ($table->status === 'busy' ? 'warning' : 'muted') }}">{{ $consoleStatusLabels[$table->status] ?? $table->status }}</span></td>
                    <td data-label="نرخ ساعتی">{{ number_format($table->hourly_rate) }}</td>
                    <td data-label="اقدام">
                        <button class="btn btn--sm" data-modal-open="modal-table" data-edit="1" data-id="{{ $table->id }}" data-fields='{{ json_encode([
                            'name' => $table->name,
                            'type' => $table->type,
                            'status' => $table->status,
                            'hourly_rate' => $table->hourly_rate,
                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}'>ویرایش</button>
                        <form method="POST" action="{{ route('tables.destroy', $table) }}" class="inline-form" data-confirm="حذف میز انجام شود؟">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn--danger btn--sm">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">میزی ثبت نشده است.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">
        {{ $tables->appends(request()->except('tables_page'))->fragment('tables')->links() }}
    </div>
</section>
@endif

@if ($user?->hasPermission('board_games.manage'))
<section id="board-games" class="section">
    <div class="section__header">
        <div>
            <h2>بردگیم‌ها</h2>
            <p>کنترل موجودی بردگیم و نرخ ساعتی.</p>
        </div>
    <div class="section__actions">
        <button class="btn btn--primary" data-modal-open="modal-board-game">ثبت بردگیم</button>
    </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>نام</th>
                    <th>وضعیت</th>
                    <th>نرخ ساعتی</th>
                    <th>اقدام</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($boardGames as $boardGame)
                <tr>
                    <td data-label="نام">{{ $boardGame->name }}</td>
                    <td data-label="وضعیت"><span class="badge badge--{{ $boardGame->status === 'available' ? 'success' : ($boardGame->status === 'busy' ? 'warning' : 'muted') }}">{{ $consoleStatusLabels[$boardGame->status] ?? $boardGame->status }}</span></td>
                    <td data-label="نرخ ساعتی">{{ number_format($boardGame->hourly_rate) }}</td>
                    <td data-label="اقدام">
                        <button class="btn btn--sm" data-modal-open="modal-board-game" data-edit="1" data-id="{{ $boardGame->id }}" data-fields='{{ json_encode([
                            'name' => $boardGame->name,
                            'status' => $boardGame->status,
                            'hourly_rate' => $boardGame->hourly_rate,
                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}'>ویرایش</button>
                        <form method="POST" action="{{ route('board-games.destroy', $boardGame) }}" class="inline-form" data-confirm="حذف بردگیم انجام شود؟">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn--danger btn--sm">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">بردگیمی ثبت نشده است.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">
        {{ $boardGames->appends(request()->except('board_games_page'))->fragment('board-games')->links() }}
    </div>
</section>
@endif

@if ($user?->hasPermission('cafe_items.manage'))
<section id="cafe" class="section">
    <div class="section__header">
        <div>
            <h2>منوی کافه</h2>
            <p>مدیریت آیتم‌های قابل سفارش.</p>
        </div>
    <div class="section__actions">
        <button class="btn btn--primary" data-modal-open="modal-cafe-item">افزودن آیتم</button>
    </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>نام آیتم</th>
                    <th>دسته‌بندی</th>
                    <th>قیمت</th>
                    <th>موجودی</th>
                    <th>وضعیت</th>
                    <th>اقدام</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($cafeItems as $item)
                <tr>
                    <td data-label="نام آیتم">{{ $item->name }}</td>
                    <td data-label="دسته‌بندی">{{ $item->category }}</td>
                    <td data-label="قیمت">{{ number_format($item->price) }}</td>
                    <td data-label="موجودی">
                        @if ($item->stock_quantity <= 0)
                            <span class="badge badge--warning">ناموجود</span>
                        @elseif ($item->stock_quantity <= 5)
                            <span class="badge badge--warning">{{ number_format($item->stock_quantity) }} عدد (کم)</span>
                        @else
                            <span class="badge badge--success">{{ number_format($item->stock_quantity) }} عدد</span>
                        @endif
                    </td>
                    <td data-label="وضعیت"><span class="badge badge--{{ $item->is_available ? 'success' : 'muted' }}">{{ $item->is_available ? 'فعال' : 'غیرفعال' }}</span></td>
                    <td data-label="اقدام">
                        <button class="btn btn--sm" data-modal-open="modal-cafe-item" data-edit="1" data-id="{{ $item->id }}" data-fields='{{ json_encode([
                            'name' => $item->name,
                            'category' => $item->category,
                            'price' => $item->price,
                            'stock_quantity' => $item->stock_quantity,
                            'is_available' => (bool) $item->is_available,
                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}'>ویرایش</button>
                        <form method="POST" action="{{ route('cafe-items.destroy', $item) }}" class="inline-form" data-confirm="حذف آیتم انجام شود؟">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn--danger btn--sm">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">آیتمی ثبت نشده است.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">
        {{ $cafeItems->appends(request()->except('cafe_items_page'))->fragment('cafe')->links() }}
    </div>
</section>
@endif

@if ($user?->hasPermission('customers.manage'))
<section id="customers" class="section">
    <div class="section__header">
        <div>
            <h2>مشتری‌ها</h2>
            <p>پروفایل مشتریان و میزان خرید.</p>
        </div>
    <div class="section__actions">
        <button class="btn btn--primary" data-modal-open="modal-customer">ثبت مشتری</button>
    </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>نام</th>
                    <th>شماره تماس</th>
                    <th>کد ملی</th>
                    <th>ایمیل</th>
                    <th>مبلغ کل</th>
                    <th>تعداد مراجعه</th>
                    <th>فاکتور</th>
                    <th>اقدام</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($customers as $customer)
                <tr>
                    <td data-label="نام">{{ $customer->name }}</td>
                    <td data-label="شماره تماس">{{ $customer->phone }}</td>
                    <td data-label="کد ملی">{{ $customer->national_id }}</td>
                    <td data-label="ایمیل">{{ $customer->email ?? '—' }}</td>
                    <td data-label="مبلغ کل">{{ number_format($customer->total_spend) }}</td>
                    <td data-label="تعداد مراجعه">{{ $customer->visit_count }}</td>
                    <td data-label="فاکتور">{{ $customer->invoices_count }}</td>
                    <td data-label="اقدام">
                        <button class="btn btn--sm" data-modal-open="modal-customer" data-edit="1" data-id="{{ $customer->id }}" data-fields='{{ json_encode([
                            'name' => $customer->name,
                            'phone' => $customer->phone,
                            'national_id' => $customer->national_id,
                            'email' => $customer->email,
                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}'>ویرایش</button>
                        <form method="POST" action="{{ route('customers.destroy', $customer) }}" class="inline-form" data-confirm="حذف مشتری انجام شود؟">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn--danger btn--sm">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">مشتری ثبت نشده است.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">
        {{ $customers->appends(request()->except('customers_page'))->fragment('customers')->links() }}
    </div>
</section>
@endif

@if ($user?->hasPermission('orders.manage'))
<section id="orders" class="section">
    <div class="section__header">
        <div>
            <h2>سفارش‌های کافه</h2>
            <p>ثبت سریع سفارش و مشاهده آیتم‌ها.</p>
        </div>
    <div class="section__actions">
        <button class="btn btn--primary" data-modal-open="modal-order">ثبت سفارش</button>
    </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>کد سفارش</th>
                    <th>مشتری</th>
                    <th>میز</th>
                    <th>مبلغ</th>
                    <th>وضعیت</th>
                    <th>آیتم‌ها</th>
                    <th>فاکتور</th>
                    <th>ثبت</th>
                    <th>جزئیات</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td data-label="کد سفارش">#{{ $order->id }}</td>
                    <td data-label="مشتری">{{ $order->customer?->name ?? 'مهمان' }}</td>
                    <td data-label="میز">{{ $order->table?->name ?? '—' }}</td>
                    <td data-label="مبلغ">{{ number_format($order->total_price) }}</td>
                    <td data-label="وضعیت"><span class="badge badge--{{ $order->status === 'pending' ? 'warning' : 'success' }}">{{ $order->status === 'pending' ? 'در انتظار' : $order->status }}</span></td>
                    <td data-label="آیتم‌ها">{{ $order->items->count() }}</td>
                    <td data-label="فاکتور">{{ $order->invoice?->invoice_number ?? $order->invoice_id ?? '—' }}</td>
                    <td data-label="ثبت">@jdate($order->created_at, 'Y/m/d H:i')</td>
                    <td data-label="جزئیات">
                        <button class="btn btn--sm" data-modal-open="modal-order-detail" data-order='{{ json_encode([
                            'id' => $order->id,
                            'customer' => $order->customer?->name ?? 'مهمان',
                            'table' => $order->table?->name ?? null,
                            'total' => $order->total_price,
                            'created_at' => \App\Support\JalaliDate::format($order->created_at, 'Y/m/d H:i'),
                            'items' => $order->items->map(fn ($item) => [
                                'name' => $item->cafeItem?->name ?? '—',
                                'quantity' => $item->quantity,
                                'price' => $item->price,
                                'total_price' => $item->total_price,
                            ]),
                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}'>نمایش</button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="muted">سفارشی ثبت نشده است.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">
        {{ $orders->appends(request()->except('orders_page'))->fragment('orders')->links() }}
    </div>
</section>
@endif

@if ($user?->hasPermission('invoices.manage'))
<section id="invoices" class="section">
    <div class="section__header">
        <div>
            <h2>فاکتورها</h2>
            <p>پیگیری وضعیت پرداخت و جزئیات آیتم‌ها.</p>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>شماره فاکتور</th>
                    <th>مشتری</th>
                    <th>مبلغ کل</th>
                    <th>وضعیت</th>
                    <th>آیتم‌ها</th>
                    <th>ثبت</th>
                    <th>اقدام</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($invoices as $invoice)
                <tr>
                    <td data-label="شماره فاکتور">{{ $invoice->invoice_number }}</td>
                    <td data-label="مشتری">{{ $invoice->customer?->name ?? 'مهمان' }}</td>
                    <td data-label="مبلغ کل">{{ number_format($invoice->total_amount) }}</td>
                    <td data-label="وضعیت"><span class="badge badge--{{ $invoice->status === 'paid' ? 'success' : 'warning' }}">{{ $invoiceStatusLabels[$invoice->status] ?? $invoice->status }}</span></td>
                    <td data-label="آیتم‌ها">{{ $invoice->consoleSessions->count() + $invoice->tableSessions->count() + $invoice->boardGameSessions->count() + $invoice->orders->count() }}</td>
                    <td data-label="ثبت">@jdate($invoice->created_at, 'Y/m/d H:i')</td>
                    <td data-label="اقدام">
                        <button class="btn btn--sm" data-modal-open="modal-invoice" data-invoice-id="{{ $invoice->id }}" data-invoice='{{ json_encode([
                            'id' => $invoice->id,
                            'number' => $invoice->invoice_number,
                            'customer' => $invoice->customer?->name ?? 'مهمان',
                            'total' => $invoice->total_amount,
                            'status' => $invoiceStatusLabels[$invoice->status] ?? $invoice->status,
                            'created_at' => \App\Support\JalaliDate::format($invoice->created_at, 'Y/m/d H:i'),
                            'console_sessions' => $invoice->consoleSessions->map(fn ($session) => [
                                'name' => $session->console?->name ?? '—',
                                'duration' => $session->duration_minutes,
                                'total' => $session->total_price,
                                'start' => optional($session->start_time)->format('H:i'),
                                'end' => optional($session->end_time)->format('H:i'),
                            ]),
                            'table_sessions' => $invoice->tableSessions->map(fn ($session) => [
                                'name' => $session->table?->name ?? '—',
                                'duration' => $session->duration_minutes,
                                'total' => $session->total_price,
                                'start' => optional($session->start_time)->format('H:i'),
                                'end' => optional($session->end_time)->format('H:i'),
                            ]),
                            'board_game_sessions' => $invoice->boardGameSessions->map(fn ($session) => [
                                'name' => $session->boardGame?->name ?? '—',
                                'duration' => $session->duration_minutes,
                                'total' => $session->total_price,
                                'start' => optional($session->start_time)->format('H:i'),
                                'end' => optional($session->end_time)->format('H:i'),
                            ]),
                            'orders' => $invoice->orders->map(fn ($order) => [
                                'id' => $order->id,
                                'total' => $order->total_price,
                                'table' => $order->table?->name ?? null,
                                'items' => $order->items->map(fn ($item) => [
                                    'name' => $item->cafeItem?->name ?? '—',
                                    'quantity' => $item->quantity,
                                    'total' => $item->total_price,
                                ]),
                            ]),
                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}'>جزئیات</button>
                        @if ($invoice->status !== 'paid')
                            <form method="POST" action="{{ route('invoices.mark-as-paid', $invoice) }}" class="inline-form" data-confirm="فاکتور پرداخت شد؟">
                                @csrf
                                <button type="submit" class="btn btn--success btn--sm">پرداخت شد</button>
                            </form>
                        @endif
                        @if ($user?->hasPermission('invoices.delete'))
                            <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" class="inline-form" data-confirm="حذف فاکتور انجام شود؟">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn--danger btn--sm">حذف</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">فاکتوری ثبت نشده است.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">
        {{ $invoices->appends(request()->except('invoices_page'))->fragment('invoices')->links() }}
    </div>
</section>
@endif

@if ($user?->hasPermission('pricing_plans.manage'))
<section id="pricing-plans" class="section">
    <div class="section__header">
        <div>
            <h2>طرح‌های قیمتی</h2>
            <p>قوانین تخفیف و زمان هدیه را تعریف کنید.</p>
        </div>
    <div class="section__actions">
        <button class="btn btn--primary" data-modal-open="modal-pricing-plan">ثبت طرح جدید</button>
    </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>نام</th>
                    <th>نوع</th>
                    <th>اعمال روی</th>
                    <th>فعال</th>
                    <th>اولویت</th>
                    <th>بازه</th>
                    <th>خلاصه</th>
                    <th>اقدام</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($pricingPlans as $plan)
                <tr>
                    <td data-label="نام">{{ $plan->name }}</td>
                    <td data-label="نوع">{{ $pricingPlanTypes[$plan->type] ?? $plan->type }}</td>
                    <td data-label="اعمال روی">{{ $pricingPlanAppliesTo[$plan->applies_to] ?? $plan->applies_to }}</td>
                    <td data-label="فعال"><span class="badge badge--{{ $plan->is_active ? 'success' : 'muted' }}">{{ $plan->is_active ? 'فعال' : 'غیرفعال' }}</span></td>
                    <td data-label="اولویت">{{ $plan->priority }}</td>
                    <td data-label="بازه">{{ $plan->starts_at ? \App\Support\JalaliDate::format($plan->starts_at, 'Y/m/d') : '—' }} تا {{ $plan->ends_at ? \App\Support\JalaliDate::format($plan->ends_at, 'Y/m/d') : '—' }}</td>
                    <td data-label="خلاصه">{{ $plan->summary() }}</td>
                    <td data-label="اقدام">
                        <button class="btn btn--sm" data-modal-open="modal-pricing-plan" data-edit="1" data-id="{{ $plan->id }}" data-fields='{{ json_encode([
                            'name' => $plan->name,
                            'type' => $plan->type,
                            'applies_to' => $plan->applies_to,
                            'priority' => $plan->priority,
                            'is_active' => (bool) $plan->is_active,
                            'starts_at' => $plan->starts_at ? \App\Support\JalaliDate::format($plan->starts_at, 'Y-m-d') : null,
                            'ends_at' => $plan->ends_at ? \App\Support\JalaliDate::format($plan->ends_at, 'Y-m-d') : null,
                            'threshold_minutes' => $plan->config['threshold_minutes'] ?? null,
                            'bonus_minutes' => $plan->config['bonus_minutes'] ?? null,
                            'min_minutes' => $plan->config['min_minutes'] ?? null,
                            'discount_percent' => $plan->config['discount_percent'] ?? null,
                            'start_time' => $plan->config['start_time'] ?? null,
                            'end_time' => $plan->config['end_time'] ?? null,
                            'days_of_week' => $plan->config['days_of_week'] ?? [],
                            'lookback_days' => $plan->config['lookback_days'] ?? null,
                            'min_total_minutes' => $plan->config['min_total_minutes'] ?? null,
                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}'>ویرایش</button>
                        <form method="POST" action="{{ route('pricing-plans.destroy', $plan) }}" class="inline-form" data-confirm="حذف طرح قیمتی انجام شود؟">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn--danger btn--sm">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">طرحی ثبت نشده است.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">
        {{ $pricingPlans->appends(request()->except('pricing_plans_page'))->fragment('pricing-plans')->links() }}
    </div>
</section>
@endif

@if ($user?->isSuperAdmin())
<section id="users" class="section">
    <div class="section__header">
        <div>
            <h2>کاربران</h2>
            <p>مدیریت دسترسی‌های پرسنل.</p>
        </div>
    <div class="section__actions">
        <button class="btn btn--primary" data-modal-open="modal-user">ایجاد کاربر</button>
    </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>نام</th>
                    <th>نام کاربری</th>
                    <th>ایمیل</th>
                    <th>نقش</th>
                    <th>دسترسی‌ها</th>
                    <th>اقدام</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($users as $row)
                @php
                    $permLabels = [];
                    $permissionsArray = $row->permissions ?? [];
                    foreach ($permissions as $permKey => $permLabel) {
                        if (!empty($permissionsArray[$permKey])) {
                            $permLabels[] = $permLabel;
                        }
                    }
                @endphp
                <tr>
                    <td data-label="نام">{{ $row->name }}</td>
                    <td data-label="نام کاربری">{{ $row->username }}</td>
                    <td data-label="ایمیل">{{ $row->email }}</td>
                    <td data-label="نقش">{{ $row->isSuperAdmin() ? 'سوپرادمین' : 'پرسنل' }}</td>
                    <td data-label="دسترسی‌ها">{{ $row->isSuperAdmin() ? 'همه دسترسی‌ها' : (count($permLabels) ? implode('، ', $permLabels) : '—') }}</td>
                    <td data-label="اقدام">
                        <button class="btn btn--sm" data-modal-open="modal-user" data-edit="1" data-id="{{ $row->id }}" data-fields='{{ json_encode([
                            'name' => $row->name,
                            'username' => $row->username,
                            'email' => $row->email,
                            'permissions' => array_keys($permissionsArray ?? []),
                            'is_super_admin' => $row->isSuperAdmin(),
                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}'>ویرایش</button>
                        @if (! $row->isSuperAdmin() && auth()->id() !== $row->id)
                            <form method="POST" action="{{ route('users.destroy', $row) }}" class="inline-form" data-confirm="حذف کاربر انجام شود؟">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn--danger btn--sm">حذف</button>
                            </form>
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">کاربری ثبت نشده است.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">
        {{ $users->appends(request()->except('users_page'))->fragment('users')->links() }}
    </div>
</section>
@endif

{{-- Modals --}}
<div class="modal" id="modal-console" aria-hidden="true">
    <div class="modal__backdrop" data-modal-close></div>
    <div class="modal__dialog">
        <div class="modal__header">
            <h3>ثبت / ویرایش کنسول</h3>
            <button class="modal__close" type="button" data-modal-close>×</button>
        </div>
        <form method="POST" action="{{ route('consoles.store') }}" data-form="console" data-create-action="{{ route('consoles.store') }}" data-update-template="{{ route('consoles.update', ['console' => '__ID__']) }}">
            @csrf
            <input type="hidden" name="form_key" value="console">
            <input type="hidden" name="form_mode" value="create" data-form-mode>
            <input type="hidden" name="entity_id" value="" data-entity-id>
            <input type="hidden" name="_method" value="PUT" data-method-field disabled>

            <div class="form-grid">
                <label class="field">
                    <span>نام کنسول</span>
                    <input type="text" name="name" required>
                </label>
                <label class="field">
                    <span>نوع کنسول</span>
                    <select name="type" required>
                        @foreach ($consoleTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field" data-edit-only>
                    <span>وضعیت</span>
                    <select name="status">
                        @foreach ($consoleStatusLabels as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>نرخ 1 دسته</span>
                    <input type="number" name="hourly_rate_single" min="0" required>
                </label>
                <label class="field">
                    <span>نرخ 2 دسته</span>
                    <input type="number" name="hourly_rate_double" min="0" required>
                </label>
                <label class="field">
                    <span>نرخ 3 دسته</span>
                    <input type="number" name="hourly_rate_triple" min="0" required>
                </label>
                <label class="field">
                    <span>نرخ 4 دسته</span>
                    <input type="number" name="hourly_rate_quadruple" min="0" required>
                </label>
            </div>

            <div class="modal__footer">
                <button type="submit" class="btn btn--primary">ذخیره</button>
                <button type="button" class="btn btn--ghost" data-modal-close>انصراف</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="modal-table" aria-hidden="true">
    <div class="modal__backdrop" data-modal-close></div>
    <div class="modal__dialog">
        <div class="modal__header">
            <h3>ثبت / ویرایش میز</h3>
            <button class="modal__close" type="button" data-modal-close>×</button>
        </div>
        <form method="POST" action="{{ route('tables.store') }}" data-form="table" data-create-action="{{ route('tables.store') }}" data-update-template="{{ route('tables.update', ['table' => '__ID__']) }}">
            @csrf
            <input type="hidden" name="form_key" value="table">
            <input type="hidden" name="form_mode" value="create" data-form-mode>
            <input type="hidden" name="entity_id" value="" data-entity-id>
            <input type="hidden" name="_method" value="PUT" data-method-field disabled>

            <div class="form-grid">
                <label class="field">
                    <span>نام میز</span>
                    <input type="text" name="name" required>
                </label>
                <label class="field">
                    <span>نوع میز</span>
                    <select name="type" required>
                        @foreach ($tableTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field" data-edit-only>
                    <span>وضعیت</span>
                    <select name="status">
                        @foreach ($consoleStatusLabels as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>نرخ ساعتی</span>
                    <input type="number" name="hourly_rate" min="0" required>
                </label>
            </div>

            <div class="modal__footer">
                <button type="submit" class="btn btn--primary">ذخیره</button>
                <button type="button" class="btn btn--ghost" data-modal-close>انصراف</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="modal-board-game" aria-hidden="true">
    <div class="modal__backdrop" data-modal-close></div>
    <div class="modal__dialog">
        <div class="modal__header">
            <h3>ثبت / ویرایش بردگیم</h3>
            <button class="modal__close" type="button" data-modal-close>×</button>
        </div>
        <form method="POST" action="{{ route('board-games.store') }}" data-form="board-game" data-create-action="{{ route('board-games.store') }}" data-update-template="{{ route('board-games.update', ['board_game' => '__ID__']) }}">
            @csrf
            <input type="hidden" name="form_key" value="board-game">
            <input type="hidden" name="form_mode" value="create" data-form-mode>
            <input type="hidden" name="entity_id" value="" data-entity-id>
            <input type="hidden" name="_method" value="PUT" data-method-field disabled>

            <div class="form-grid">
                <label class="field">
                    <span>نام بردگیم</span>
                    <input type="text" name="name" required>
                </label>
                <label class="field" data-edit-only>
                    <span>وضعیت</span>
                    <select name="status">
                        @foreach ($consoleStatusLabels as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>نرخ ساعتی</span>
                    <input type="number" name="hourly_rate" min="0" required>
                </label>
            </div>

            <div class="modal__footer">
                <button type="submit" class="btn btn--primary">ذخیره</button>
                <button type="button" class="btn btn--ghost" data-modal-close>انصراف</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="modal-cafe-item" aria-hidden="true">
    <div class="modal__backdrop" data-modal-close></div>
    <div class="modal__dialog">
        <div class="modal__header">
            <h3>ثبت / ویرایش آیتم کافه</h3>
            <button class="modal__close" type="button" data-modal-close>×</button>
        </div>
        <form method="POST" action="{{ route('cafe-items.store') }}" data-form="cafe-item" data-create-action="{{ route('cafe-items.store') }}" data-update-template="{{ route('cafe-items.update', ['cafe_item' => '__ID__']) }}">
            @csrf
            <input type="hidden" name="form_key" value="cafe-item">
            <input type="hidden" name="form_mode" value="create" data-form-mode>
            <input type="hidden" name="entity_id" value="" data-entity-id>
            <input type="hidden" name="_method" value="PUT" data-method-field disabled>

            <div class="form-grid">
                <label class="field">
                    <span>نام آیتم</span>
                    <input type="text" name="name" required>
                </label>
                <label class="field">
                    <span>دسته‌بندی</span>
                    <input type="text" name="category" required>
                </label>
                <label class="field">
                    <span>قیمت</span>
                    <input type="number" name="price" min="0" required>
                </label>
                <label class="field">
                    <span>موجودی</span>
                    <input type="number" name="stock_quantity" min="0" required>
                </label>
                <label class="field field--toggle">
                    <span>فعال است؟</span>
                    <input type="checkbox" name="is_available" value="1">
                </label>
            </div>

            <div class="modal__footer">
                <button type="submit" class="btn btn--primary">ذخیره</button>
                <button type="button" class="btn btn--ghost" data-modal-close>انصراف</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="modal-customer" aria-hidden="true">
    <div class="modal__backdrop" data-modal-close></div>
    <div class="modal__dialog">
        <div class="modal__header">
            <h3>ثبت / ویرایش مشتری</h3>
            <button class="modal__close" type="button" data-modal-close>×</button>
        </div>
        <form method="POST" action="{{ route('customers.store') }}" data-form="customer" data-create-action="{{ route('customers.store') }}" data-update-template="{{ route('customers.update', ['customer' => '__ID__']) }}">
            @csrf
            <input type="hidden" name="form_key" value="customer">
            <input type="hidden" name="form_mode" value="create" data-form-mode>
            <input type="hidden" name="entity_id" value="" data-entity-id>
            <input type="hidden" name="_method" value="PUT" data-method-field disabled>

            <div class="form-grid">
                <label class="field">
                    <span>نام مشتری</span>
                    <input type="text" name="name" required>
                </label>
                <label class="field">
                    <span>شماره تماس</span>
                    <input type="text" name="phone" required>
                </label>
                <label class="field">
                    <span>کد ملی</span>
                    <input type="text" name="national_id" required>
                </label>
                <label class="field">
                    <span>ایمیل (اختیاری)</span>
                    <input type="email" name="email">
                </label>
            </div>

            <div class="modal__footer">
                <button type="submit" class="btn btn--primary">ذخیره</button>
                <button type="button" class="btn btn--ghost" data-modal-close>انصراف</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="modal-console-session" aria-hidden="true">
    <div class="modal__backdrop" data-modal-close></div>
    <div class="modal__dialog">
        <div class="modal__header">
            <h3>شروع سشن کنسول</h3>
            <button class="modal__close" type="button" data-modal-close>×</button>
        </div>
        <form method="POST" action="{{ route('console-sessions.store') }}" data-form="console-session" data-create-action="{{ route('console-sessions.store') }}">
            @csrf
            <input type="hidden" name="form_key" value="console-session">
            <div class="form-grid">
                <label class="field">
                    <span>کنسول</span>
                    <select name="console_id" required>
                        @foreach ($consolesAll->where('status', 'available') as $console)
                            <option value="{{ $console->id }}">{{ $console->name }} ({{ $console->type }})</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>مشتری</span>
                    <select name="customer_id">
                        <option value="">مهمان</option>
                        @foreach ($customersAll as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }} - {{ $customer->phone }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>تعداد دسته</span>
                    <select name="controller_count" required>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                    </select>
                </label>
                <label class="field">
                    <span>زمان شروع (اختیاری)</span>
                    <input type="text" name="start_time" placeholder="1403-01-15 18:30 یا 18:30" data-jalali-picker="datetime" autocomplete="off">
                </label>
                <label class="field">
                    <span>مدت برنامه‌ریزی (دقیقه)</span>
                    <input type="number" name="planned_duration_minutes" min="1" max="1440">
                </label>
                <label class="field">
                    <span>تخفیف دستی (%)</span>
                    <input type="number" name="discount_percent" min="0" max="100">
                </label>
            </div>
            <div class="modal__footer">
                <button type="submit" class="btn btn--primary">شروع سشن</button>
                <button type="button" class="btn btn--ghost" data-modal-close>انصراف</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="modal-table-session" aria-hidden="true">
    <div class="modal__backdrop" data-modal-close></div>
    <div class="modal__dialog">
        <div class="modal__header">
            <h3>شروع سشن میز</h3>
            <button class="modal__close" type="button" data-modal-close>×</button>
        </div>
        <form method="POST" action="{{ route('table-sessions.store') }}" data-form="table-session" data-create-action="{{ route('table-sessions.store') }}">
            @csrf
            <input type="hidden" name="form_key" value="table-session">
            <div class="form-grid">
                <label class="field">
                    <span>میز</span>
                    <select name="table_id" required>
                        @foreach ($tablesAll->where('status', 'available') as $table)
                            <option value="{{ $table->id }}">{{ $table->name }} ({{ $tableTypes[$table->type] ?? $table->type }})</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>مشتری</span>
                    <select name="customer_id">
                        <option value="">مهمان</option>
                        @foreach ($customersAll as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }} - {{ $customer->phone }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>زمان شروع (اختیاری)</span>
                    <input type="text" name="start_time" placeholder="1403-01-15 18:30 یا 18:30" data-jalali-picker="datetime" autocomplete="off">
                </label>
                <label class="field">
                    <span>مدت برنامه‌ریزی (دقیقه)</span>
                    <input type="number" name="planned_duration_minutes" min="1" max="1440">
                </label>
                <label class="field">
                    <span>تخفیف دستی (%)</span>
                    <input type="number" name="discount_percent" min="0" max="100">
                </label>
            </div>
            <div class="modal__footer">
                <button type="submit" class="btn btn--primary">شروع سشن</button>
                <button type="button" class="btn btn--ghost" data-modal-close>انصراف</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="modal-board-game-session" aria-hidden="true">
    <div class="modal__backdrop" data-modal-close></div>
    <div class="modal__dialog">
        <div class="modal__header">
            <h3>شروع سشن بردگیم</h3>
            <button class="modal__close" type="button" data-modal-close>×</button>
        </div>
        <form method="POST" action="{{ route('board-game-sessions.store') }}" data-form="board-game-session" data-create-action="{{ route('board-game-sessions.store') }}">
            @csrf
            <input type="hidden" name="form_key" value="board-game-session">
            <div class="form-grid">
                <label class="field">
                    <span>بردگیم</span>
                    <select name="board_game_id" required>
                        @foreach ($boardGamesAll->where('status', 'available') as $boardGame)
                            <option value="{{ $boardGame->id }}">{{ $boardGame->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>مشتری</span>
                    <select name="customer_id">
                        <option value="">مهمان</option>
                        @foreach ($customersAll as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }} - {{ $customer->phone }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>زمان شروع (اختیاری)</span>
                    <input type="text" name="start_time" placeholder="1403-01-15 18:30 یا 18:30" data-jalali-picker="datetime" autocomplete="off">
                </label>
                <label class="field">
                    <span>مدت برنامه‌ریزی (دقیقه)</span>
                    <input type="number" name="planned_duration_minutes" min="1" max="1440">
                </label>
                <label class="field">
                    <span>تخفیف دستی (%)</span>
                    <input type="number" name="discount_percent" min="0" max="100">
                </label>
            </div>
            <div class="modal__footer">
                <button type="submit" class="btn btn--primary">شروع سشن</button>
                <button type="button" class="btn btn--ghost" data-modal-close>انصراف</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="modal-order" aria-hidden="true">
    <div class="modal__backdrop" data-modal-close></div>
    <div class="modal__dialog modal__dialog--wide">
        <div class="modal__header">
            <h3>ثبت سفارش کافه</h3>
            <button class="modal__close" type="button" data-modal-close>×</button>
        </div>
        <form method="POST" action="{{ route('orders.store') }}" data-form="order" data-create-action="{{ route('orders.store') }}">
            @csrf
            <input type="hidden" name="form_key" value="order">
            <div class="form-grid">
                <label class="field">
                    <span>اتصال به سشن فعال</span>
                    <select name="session_ref" data-session-ref>
                        <option value="">بدون سشن (فاکتور جدا)</option>
                        @if ($activeSessions->count())
                            <optgroup label="سشن‌های کنسول">
                                @foreach ($activeSessions as $session)
                                    @php $effectiveStart = $resolveSessionStart($session->start_time); @endphp
                                    <option value="console:{{ $session->id }}" data-customer-id="{{ $session->customer_id ?? '' }}">
                                        کنسول {{ $session->console?->name ?? '—' }} — {{ $session->customer?->name ?? 'مهمان' }} — شروع {{ optional($effectiveStart)->format('H:i') }}
                                        @if ($session->invoice?->invoice_number)
                                            — فاکتور {{ $session->invoice->invoice_number }}
                                        @endif
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                        @if ($activeTableSessions->count())
                            <optgroup label="سشن‌های میز">
                                @foreach ($activeTableSessions as $session)
                                    @php $effectiveStart = $resolveSessionStart($session->start_time); @endphp
                                    <option value="table:{{ $session->id }}" data-customer-id="{{ $session->customer_id ?? '' }}" data-table-id="{{ $session->table_id }}">
                                        میز {{ $session->table?->name ?? '—' }} — {{ $session->customer?->name ?? 'مهمان' }} — شروع {{ optional($effectiveStart)->format('H:i') }}
                                        @if ($session->invoice?->invoice_number)
                                            — فاکتور {{ $session->invoice->invoice_number }}
                                        @endif
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                        @if ($activeBoardGameSessions->count())
                            <optgroup label="سشن‌های بردگیم">
                                @foreach ($activeBoardGameSessions as $session)
                                    @php $effectiveStart = $resolveSessionStart($session->start_time); @endphp
                                    <option value="board_game:{{ $session->id }}" data-customer-id="{{ $session->customer_id ?? '' }}">
                                        بردگیم {{ $session->boardGame?->name ?? '—' }} — {{ $session->customer?->name ?? 'مهمان' }} — شروع {{ optional($effectiveStart)->format('H:i') }}
                                        @if ($session->invoice?->invoice_number)
                                            — فاکتور {{ $session->invoice->invoice_number }}
                                        @endif
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                    <div class="muted">اگر سشن انتخاب شود، سفارش به همان فاکتور اضافه می‌شود.</div>
                </label>
                <label class="field">
                    <span>مشتری</span>
                    <select name="customer_id">
                        <option value="">مهمان</option>
                        @foreach ($customersAll as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }} - {{ $customer->phone }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>میز (اختیاری)</span>
                    <select name="table_id">
                        <option value="">انتخاب میز</option>
                        @foreach ($tablesAll as $table)
                            <option value="{{ $table->id }}">{{ $table->name }} ({{ $tableTypes[$table->type] ?? $table->type }})</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="order-items">
                <div class="order-items__header">
                    <h4>آیتم‌های سفارش</h4>
                    <button type="button" class="btn btn--ghost btn--sm" data-add-order-item>افزودن آیتم</button>
                </div>
                <div class="order-items__list" data-order-items></div>
                <div class="order-items__summary">
                    <span>جمع کل:</span>
                    <strong data-order-total>0</strong>
                    <span>تومان</span>
                </div>
            </div>

            <div class="modal__footer">
                <button type="submit" class="btn btn--primary">ثبت سفارش</button>
                <button type="button" class="btn btn--ghost" data-modal-close>انصراف</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="modal-order-detail" aria-hidden="true">
    <div class="modal__backdrop" data-modal-close></div>
    <div class="modal__dialog">
        <div class="modal__header">
            <h3>جزئیات سفارش</h3>
            <button class="modal__close" type="button" data-modal-close>×</button>
        </div>
        <div class="detail" data-order-detail></div>
        <div class="modal__footer">
            <button type="button" class="btn btn--ghost" data-modal-close>بستن</button>
        </div>
    </div>
</div>

<div class="modal" id="modal-invoice" aria-hidden="true">
    <div class="modal__backdrop" data-modal-close></div>
    <div class="modal__dialog modal__dialog--wide">
        <div class="modal__header">
            <h3>جزئیات فاکتور</h3>
            <button class="modal__close" type="button" data-modal-close>×</button>
        </div>
        <div class="detail" data-invoice-detail></div>
        <div class="modal__footer">
            <button type="button" class="btn btn--ghost" data-modal-close>بستن</button>
        </div>
    </div>
</div>

<div class="modal" id="modal-pricing-plan" aria-hidden="true">
    <div class="modal__backdrop" data-modal-close></div>
    <div class="modal__dialog modal__dialog--wide">
        <div class="modal__header">
            <h3>ثبت / ویرایش طرح قیمتی</h3>
            <button class="modal__close" type="button" data-modal-close>×</button>
        </div>
        <form method="POST" action="{{ route('pricing-plans.store') }}" data-form="pricing-plan" data-create-action="{{ route('pricing-plans.store') }}" data-update-template="{{ route('pricing-plans.update', ['pricing_plan' => '__ID__']) }}">
            @csrf
            <input type="hidden" name="form_key" value="pricing-plan">
            <input type="hidden" name="form_mode" value="create" data-form-mode>
            <input type="hidden" name="entity_id" value="" data-entity-id>
            <input type="hidden" name="_method" value="PUT" data-method-field disabled>

            <div class="form-grid">
                <label class="field">
                    <span>نام طرح</span>
                    <input type="text" name="name" required>
                </label>
                <label class="field">
                    <span>نوع طرح</span>
                    <select name="type" required data-plan-type>
                        @foreach ($pricingPlanTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>اعمال روی</span>
                    <select name="applies_to" required>
                        @foreach ($pricingPlanAppliesTo as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>اولویت</span>
                    <input type="number" name="priority" min="0" max="1000">
                </label>
                <label class="field field--toggle">
                    <span>فعال است؟</span>
                    <input type="checkbox" name="is_active" value="1">
                </label>
                <label class="field">
                    <span>تاریخ شروع</span>
                    <input type="text" name="starts_at" placeholder="1403-01-15" data-jalali-picker="date">
                </label>
                <label class="field">
                    <span>تاریخ پایان</span>
                    <input type="text" name="ends_at" placeholder="1403-01-15" data-jalali-picker="date">
                </label>
            </div>

            <div class="plan-grid">
                <div class="plan-card" data-plan-section="bonus_time">
                    <h4>زمان هدیه</h4>
                    <label class="field">
                        <span>حد آستانه (دقیقه)</span>
                        <input type="number" name="threshold_minutes" min="1">
                    </label>
                    <label class="field">
                        <span>دقیقه هدیه</span>
                        <input type="number" name="bonus_minutes" min="1">
                    </label>
                </div>
                <div class="plan-card" data-plan-section="duration_discount">
                    <h4>تخفیف بر اساس مدت</h4>
                    <label class="field">
                        <span>حداقل دقیقه</span>
                        <input type="number" name="min_minutes" min="1">
                    </label>
                    <label class="field">
                        <span>درصد تخفیف</span>
                        <input type="number" name="discount_percent" min="0" max="100">
                    </label>
                </div>
                <div class="plan-card" data-plan-section="happy_hour">
                    <h4>هپی آور</h4>
                    <label class="field">
                        <span>درصد تخفیف</span>
                        <input type="number" name="discount_percent" min="0" max="100">
                    </label>
                    <label class="field">
                        <span>ساعت شروع</span>
                        <input type="time" name="start_time">
                    </label>
                    <label class="field">
                        <span>ساعت پایان</span>
                        <input type="time" name="end_time">
                    </label>
                    <div class="field">
                        <span>روزهای هفته</span>
                        <div class="checkbox-grid">
                            @foreach (['0' => 'یکشنبه', '1' => 'دوشنبه', '2' => 'سه‌شنبه', '3' => 'چهارشنبه', '4' => 'پنجشنبه', '5' => 'جمعه', '6' => 'شنبه'] as $key => $label)
                                <label class="checkbox">
                                    <input type="checkbox" name="days_of_week[]" value="{{ $key }}">
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="plan-card" data-plan-section="weekly_volume_discount">
                    <h4>تخفیف حجمی هفتگی</h4>
                    <label class="field">
                        <span>بازه بازگشت (روز)</span>
                        <input type="number" name="lookback_days" min="1" max="365">
                    </label>
                    <label class="field">
                        <span>حداقل دقیقه کل</span>
                        <input type="number" name="min_total_minutes" min="1">
                    </label>
                    <label class="field">
                        <span>درصد تخفیف</span>
                        <input type="number" name="discount_percent" min="0" max="100">
                    </label>
                </div>
            </div>

            <div class="modal__footer">
                <button type="submit" class="btn btn--primary">ذخیره</button>
                <button type="button" class="btn btn--ghost" data-modal-close>انصراف</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="modal-user" aria-hidden="true">
    <div class="modal__backdrop" data-modal-close></div>
    <div class="modal__dialog modal__dialog--wide">
        <div class="modal__header">
            <h3>ایجاد / ویرایش کاربر</h3>
            <button class="modal__close" type="button" data-modal-close>×</button>
        </div>
        <form method="POST" action="{{ route('users.store') }}" data-form="user" data-create-action="{{ route('users.store') }}" data-update-template="{{ route('users.update', ['user' => '__ID__']) }}">
            @csrf
            <input type="hidden" name="form_key" value="user">
            <input type="hidden" name="form_mode" value="create" data-form-mode>
            <input type="hidden" name="entity_id" value="" data-entity-id>
            <input type="hidden" name="_method" value="PUT" data-method-field disabled>

            <div class="form-grid">
                <label class="field">
                    <span>نام</span>
                    <input type="text" name="name" required>
                </label>
                <label class="field">
                    <span>نام کاربری</span>
                    <input type="text" name="username" required>
                </label>
                <label class="field">
                    <span>ایمیل</span>
                    <input type="email" name="email" required>
                </label>
                <label class="field">
                    <span>رمز عبور</span>
                    <input type="password" name="password">
                </label>
                <label class="field">
                    <span>تکرار رمز عبور</span>
                    <input type="password" name="password_confirmation">
                </label>
            </div>

            <div class="permissions" data-permissions>
                <h4>دسترسی‌ها</h4>
                <div class="checkbox-grid">
                    @foreach ($permissions as $key => $label)
                        <label class="checkbox">
                            <input type="checkbox" name="permissions[]" value="{{ $key }}">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="muted">در صورت سوپرادمین بودن، دسترسی‌ها قابل تغییر نیست.</div>
            </div>

            <div class="modal__footer">
                <button type="submit" class="btn btn--primary">ذخیره</button>
                <button type="button" class="btn btn--ghost" data-modal-close>انصراف</button>
            </div>
        </form>
    </div>
</div>

<template id="order-item-template">
    <div class="order-item" data-order-item>
        <label class="field">
            <span>آیتم</span>
            <select data-order-item-select>
                <option value="">انتخاب آیتم</option>
                @foreach ($cafeItemsAll as $item)
                    <option value="{{ $item->id }}" data-price="{{ $item->price }}" data-stock="{{ $item->stock_quantity }}" @disabled(! $item->is_available || $item->stock_quantity <= 0)>
                        {{ $item->name }} ({{ number_format($item->price) }}) - موجودی: {{ number_format($item->stock_quantity) }}
                        @if (! $item->is_available || $item->stock_quantity <= 0)
                            - ناموجود
                        @endif
                    </option>
                @endforeach
            </select>
            <div class="muted" data-order-item-stock></div>
        </label>
        <label class="field">
            <span>تعداد</span>
            <input type="number" min="1" value="1" data-order-item-qty>
        </label>
        <button type="button" class="btn btn--danger btn--sm" data-remove-order-item>حذف</button>
    </div>
</template>

<script>
    window.dashboardState = {
        openModal: @json(old('form_key')),
        oldFormMode: @json(old('form_mode')),
        oldEntityId: @json(old('entity_id')),
        oldInput: @json(old()),
        openInvoiceId: @json(session('open_invoice_id')),
        cafeItems: @json($cafeItemsAll->map(fn ($item) => ['id' => $item->id, 'name' => $item->name, 'price' => $item->price])),
        currentUserId: @json(auth()->id()),
    };
</script>
@endsection
