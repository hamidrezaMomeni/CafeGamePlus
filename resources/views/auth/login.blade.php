@extends('layouts.auth')

@section('title', 'ورود به پنل')

@section('content')
<div class="auth-layout">
    <div class="auth-hero">
        <div class="auth-hero__badge">CafeGame+</div>
        <h1>مدیریت یکپارچه<br>برای گیم سنتر</h1>
        <p>سشن‌ها، منوی کافه، مشتری‌ها و فاکتورها را در یک پنل ساده و سریع مدیریت کنید.</p>
        <div class="auth-hero__grid">
            <div>
                <span>سشن‌ها</span>
                <strong>کنسول، میز، بردگیم</strong>
            </div>
            <div>
                <span>کافه</span>
                <strong>ثبت سفارش سریع</strong>
            </div>
            <div>
                <span>گزارش</span>
                <strong>فاکتور و درآمد</strong>
            </div>
        </div>
    </div>

    <div class="auth-card">
        <div class="auth-card__header">
            <h2>ورود به پنل</h2>
            <p>نام کاربری و رمز عبور خود را وارد کنید.</p>
        </div>

        @if ($errors->any())
            <div class="alert alert--warning">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="form">
            @csrf
            <label class="field">
                <span>نام کاربری</span>
                <input type="text" name="username" value="{{ old('username') }}" required autofocus>
            </label>

            <label class="field">
                <span>رمز عبور</span>
                <input type="password" name="password" required>
            </label>

            <button type="submit" class="btn btn--primary btn--full">ورود</button>
        </form>
    </div>
</div>
@endsection
