<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#4b0876">
    <title>@yield('title', 'Tổng quan') · {{ config('app.name', 'DiemDanh') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
</head>
<body>
    <div class="app-shell">
        <button class="sidebar-scrim" id="sidebarScrim" type="button" aria-label="Đóng menu"></button>
        <aside class="sidebar" id="sidebar">
            <a class="brand" href="{{ route('dashboard') }}">
                <img class="brand-logo" src="{{ asset('images/utm-logo.png') }}" alt="UTM - Trường Đại học Công nghệ và Quản lý Hữu Nghị">
                <span class="brand-copy"><strong>Điểm danh</strong><small>QUẢN LÝ CHUYÊN CẦN</small></span>
            </a>

            <div class="workspace-label"><span class="status-dot"></span> CỔNG ĐIỀU HÀNH</div>

            <nav class="side-nav" aria-label="Điều hướng chính">
                @php
                    $currentKey = match (true) {
                        request()->routeIs('dashboard') => 'overview',
                        request()->routeIs('lecturers.*') => 'lecturers',
                        request()->routeIs('departments.*') => 'departments',
                        request()->routeIs('courses.*') => 'courses',
                        request()->routeIs('classes.*') => 'classes',
                        request()->routeIs('attendance-sessions.*') => 'attendance-sessions',
                        request()->routeIs('attendance-alerts.*', 'attendance-alert-settings.*') => 'absence-alerts',
                        request()->routeIs('attendance-reports.*') => 'attendance-reports',
                        request()->routeIs('assessment-reports.*') => 'assessment-reports',
                        request()->routeIs('student.attendance.*') => 'student-portal',
                        request()->routeIs('correction-requests.*') => 'absence-requests',
                        request()->routeIs('learners.*') => 'learners',
                        request()->routeIs('configuration.*') => 'configuration',
                        default => request()->route('module'),
                    };
                @endphp
                @foreach (config('attendance.groups') as $group)
                    @php
                        $user = auth()->user();
                        $visibleItems = collect($group['items'])->filter(function (array $item) use ($user): bool {
                            return match ($user->role) {
                                'learner' => in_array($item['key'], ['overview', 'student-portal'], true),
                                'admin' => $item['key'] !== 'student-portal',
                                'unit_leader', 'student_affairs' => in_array($item['key'], ['overview', 'classes', 'attendance-sessions', 'absence-requests', 'attendance-reports', 'assessment-reports', 'absence-alerts'], true),
                                'lecturer' => in_array($item['key'], ['overview', 'lecturers', 'classes', 'attendance-sessions', 'attendance-reports', 'assessment-reports'], true),
                                default => $item['key'] !== 'student-portal' && $item['key'] !== 'accounts' && $item['key'] !== 'configuration',
                            };
                        })->values();
                        $groupActive = $visibleItems->contains('key', $currentKey);
                        $groupId = 'nav-group-' . $loop->index;
                    @endphp
                    @if ($visibleItems->isNotEmpty())
                    <div class="nav-group">
                        <button class="nav-heading" type="button" aria-expanded="{{ $groupActive ? 'true' : 'false' }}" aria-controls="{{ $groupId }}">
                            {{ $group['label'] }}
                            <span class="nav-caret" aria-hidden="true"></span>
                        </button>
                        <div class="nav-items" id="{{ $groupId }}" @if (! $groupActive) hidden @endif>
                            @foreach ($visibleItems as $item)
                                @php
                                    $active = $item['key'] === $currentKey;
                                    $href = match ($item['key']) {
                                        'overview' => route('dashboard'),
                                        'lecturers' => route('lecturers.index'),
                                        'departments' => route('departments.index'),
                                        'courses' => route('courses.index'),
                                        'classes' => route('classes.index'),
                                        'attendance-sessions' => route('attendance-sessions.index'),
                                        'absence-alerts' => route('attendance-alerts.index'),
                                        'attendance-reports' => route('attendance-reports.index'),
                                        'assessment-reports' => route('assessment-reports.index'),
                                        'accounts' => route('accounts.index'),
                                        'student-portal' => route('student.attendance.index'),
                                        'absence-requests' => route('correction-requests.index'),
                                        'learners' => route('learners.index'),
                                        'configuration' => route('configuration.index'),
                                        default => route('work.show', $item['key']),
                                    };
                                @endphp
                                <a class="nav-link {{ $active ? 'is-active' : '' }}" href="{{ $href }}" @if ($active) aria-current="page" @endif>
                                    <span>{{ $item['label'] }}</span>
                                    <small>{{ $item['owner'] }}</small>
                                </a>
                            @endforeach
                        </div>
                    </div>
                    @endif
                @endforeach
            </nav>

            <div class="sidebar-footer">
                <span class="footer-mark">LOCAL</span>
                <span><strong>Môi trường phát triển</strong><small>{{ config('app.name') }}</small></span>
            </div>
        </aside>

        <main class="main-panel">
            <header class="topbar">
                <button class="menu-toggle" id="menuToggle" type="button" aria-controls="sidebar" aria-expanded="false">Menu</button>
                <div class="breadcrumb"><span>Hệ thống</span><b>/</b><strong>@yield('page_heading', 'Tổng quan')</strong></div>
                <div class="topbar-meta">
                    <span class="topbar-date">{{ now()->format('d/m/Y') }}</span>
                    <span class="topbar-user">{{ auth()->user()->name }}</span>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="logout-button" type="submit">Đăng xuất</button>
                    </form>
                </div>
            </header>

            <div class="page-content">
                @yield('content')
            </div>

            <footer class="institution-footer">
                <div class="institution-copy">
                    <strong>Trường Đại học Công nghệ và Quản lý Hữu Nghị (UTM)</strong>
                    <span>Hệ thống quản lý chuyên cần</span>
                </div>
                <a href="https://maps.google.com/?q=431+Tam+Trinh,+Hoang+Mai,+Ha+Noi" target="_blank" rel="noreferrer">Lô 1 - 04, số 431 Tam Trinh, P. Hoàng Mai, Hà Nội</a>
                <a href="tel:+842436320743">(024) 36 320743 · (024) 37 632 890</a>
                <a href="mailto:contact@utm.edu.vn">contact@utm.edu.vn</a>
            </footer>
        </main>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const menuToggle = document.getElementById('menuToggle');
        const sidebarScrim = document.getElementById('sidebarScrim');
        const navToggles = [...document.querySelectorAll('.nav-heading')];

        function setMenuOpen(isOpen) {
            sidebar.classList.toggle('is-open', isOpen);
            sidebarScrim.classList.toggle('is-visible', isOpen);
            menuToggle.setAttribute('aria-expanded', String(isOpen));
            document.body.classList.toggle('menu-open', isOpen);
        }

        menuToggle.addEventListener('click', () => setMenuOpen(!sidebar.classList.contains('is-open')));
        sidebarScrim.addEventListener('click', () => setMenuOpen(false));
        navToggles.forEach((toggle) => {
            toggle.addEventListener('click', () => {
                const willExpand = toggle.getAttribute('aria-expanded') !== 'true';

                navToggles.forEach((other) => {
                    const isExpanded = other === toggle && willExpand;
                    other.setAttribute('aria-expanded', String(isExpanded));
                    document.getElementById(other.getAttribute('aria-controls')).hidden = !isExpanded;
                });
            });
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') setMenuOpen(false);
        });
    </script>
</body>
</html>
