<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#4b0876">
    <title>Đặt lại mật khẩu quản trị · UTM</title>
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
                <p class="eyebrow">THIẾT LẬP CỤC BỘ</p>
                <h1>Đặt lại mật khẩu</h1>
                <p>Tài khoản quản trị: {{ $admin->email }}</p>
            </div>
            <form class="auth-form" action="{{ route('admin.password.update') }}" method="POST">
                @csrf
                <input type="hidden" name="nonce" value="{{ $nonce }}">
                <label class="lecturer-field">
                    <span>Mật khẩu mới</span>
                    <input type="password" name="password" required minlength="12" autocomplete="new-password" autofocus>
                    @error('password')<small class="field-error">{{ $message }}</small>@enderror
                </label>
                <label class="lecturer-field">
                    <span>Nhập lại mật khẩu</span>
                    <input type="password" name="password_confirmation" required minlength="12" autocomplete="new-password">
                </label>
                <button class="button button-primary" type="submit">Lưu mật khẩu mới</button>
                <a class="auth-back-link" href="{{ route('login') }}">Quay lại đăng nhập</a>
            </form>
        </section>
        <aside class="auth-aside">
            <span class="auth-aside-kicker">CHỈ TRÊN MÁY LOCAL</span>
            <p>Đặt lại quyền truy cập quản trị</p>
            <span>Liên kết chỉ hoạt động trên máy chủ local và hết hạn sau 10 phút.</span>
        </aside>
    </main>
</body>
</html>