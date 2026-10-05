<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#4b0876">
    <title>Đăng nhập · UTM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
</head>
<body class="auth-page">
    <main class="auth-layout">
        <section class="auth-panel">
            <a class="auth-brand" href="{{ route('login') }}">
                <img src="{{ asset('images/utm-logo.png') }}" alt="UTM - Trường Đại học Công nghệ và Quản lý Hữu Nghị">
                <span>HỆ THỐNG QUẢN LÝ CHUYÊN CẦN</span>
            </a>
            <div class="auth-heading">
                <p class="eyebrow">CỔNG ĐIỀU HÀNH · UTM</p>
                <h1>Đăng nhập</h1>
                <p>Đăng nhập bằng tài khoản được Nhà trường cấp.</p>
            </div>
            <form class="auth-form" action="{{ route('login.store') }}" method="POST">
                @csrf
                <label class="lecturer-field">
                    <span>Email</span>
                    <input type="email" name="email" value="{{ old('email') }}" required autocomplete="username" autofocus>
                    @error('email')<small class="field-error">{{ $message }}</small>@enderror
                </label>
                <label class="lecturer-field">
                    <span>Mật khẩu</span>
                    <input type="password" name="password" required autocomplete="current-password">
                    @error('password')<small class="field-error">{{ $message }}</small>@enderror
                </label>
                <label class="auth-remember"><input type="checkbox" name="remember" value="1"> <span>Ghi nhớ đăng nhập</span></label>
                <button class="button button-primary" type="submit">Đăng nhập</button>
                @if (app()->environment('local'))
                    <a class="auth-back-link" href="{{ route('admin.password.edit') }}">Đặt lại mật khẩu quản trị trên máy này</a>
                @endif
            </form>
        </section>
        <aside class="auth-aside">
            <span class="auth-aside-kicker">TRƯỜNG ĐẠI HỌC CÔNG NGHỆ VÀ QUẢN LÝ HỮU NGHỊ</span>
            <p>Hệ thống quản lý chuyên cần</p>
            <span>Đăng nhập để tiếp tục công việc của bạn.</span>
        </aside>
    </main>
</body>
</html>