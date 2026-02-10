<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'پنل مدیریت') | {{ config('app.name', 'CafeGamePlus') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-body">
    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <div class="brand">
                <div class="brand__logo">CG+</div>
                <div>
                    <div class="brand__title">CafeGame+</div>
                    <div class="brand__subtitle">پنل مدیریت</div>
                </div>
            </div>

            <nav class="nav">
                @yield('sidebar')
            </nav>

            <div class="sidebar__footer">
                <div class="user-chip">
                    <span class="user-chip__avatar">{{ mb_substr(auth()->user()->name ?? 'U', 0, 1) }}</span>
                    <div class="user-chip__meta">
                        <span class="user-chip__name">{{ auth()->user()->name ?? 'کاربر' }}</span>
                        <span class="user-chip__role">{{ auth()->user()?->isSuperAdmin() ? 'سوپرادمین' : 'پرسنل' }}</span>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn--ghost">خروج از حساب</button>
                </form>
            </div>
        </aside>

        <div class="sidebar-backdrop" data-sidebar-close></div>
        <main class="main">
            <header class="topbar">
                <div>
                    <div class="topbar__eyebrow">داشبورد یکپارچه</div>
                    <h1 class="topbar__title">@yield('page_title', 'مدیریت گیم سنتر')</h1>
                </div>
                <div class="topbar__meta">
                    <button type="button" class="btn btn--ghost btn--sm sidebar-toggle" data-sidebar-toggle aria-controls="sidebar" aria-expanded="false">
                        <span class="sidebar-toggle__bars" aria-hidden="true">
                            <span></span>
                            <span></span>
                            <span></span>
                        </span>
                        <span class="sidebar-toggle__label">منو</span>
                    </button>
                    <div class="topbar__badge">@jdate(now(), 'Y/m/d')</div>
                    <div class="topbar__badge">@jdate(now(), 'H:i')</div>
                    <button type="button" class="btn btn--ghost btn--sm theme-toggle" data-theme-toggle aria-pressed="false">
                        <span data-theme-label>حالت شب</span>
                    </button>
                </div>
            </header>

            @if (session('success'))
                <div class="alert alert--success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert--danger">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert--warning">
                    <div class="alert__title">خطا در ثبت اطلاعات</div>
                    <ul class="alert__list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
