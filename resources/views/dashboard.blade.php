@extends('layouts.app')

@section('title', 'Tổng quan')
@section('page_heading', 'Tổng quan chuyên cần')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">ĐIỀU HÀNH / {{ $today->format('d.m.Y') }}</p>
            <h1>Tổng quan chuyên cần</h1>
            <p class="page-lede">Tình hình lớp học và chuyên cần trong ngày.</p>
        </div>
        <a class="button button-primary" href="{{ route('attendance-sessions.index') }}">Mở phiên điểm danh</a>
    </div>

    <section class="welcome-band" aria-label="Tình trạng khởi tạo">
        <div class="welcome-copy">
            <span class="band-kicker">ĐIỂM DANH HÔM NAY</span>
            <h2>{{ $scheduledSectionCount }} lớp theo lịch<br>{{ $unopenedSectionCount }} lớp chưa mở buổi.</h2>
            <p>{{ $startedSectionCount }} lớp đã có phiên điểm danh; kết quả được tổng hợp theo người học.</p>
        </div>
        <div class="band-note"><span class="note-number">{{ str_pad((string) $unmarkedSessions, 2, '0', STR_PAD_LEFT) }}</span><span>BUỔI QUÁ HẠN</span><i></i><strong>{{ $openAlertCount }} cảnh báo đang mở</strong></div>
    </section>

    <section class="metric-grid" aria-label="Chỉ số tổng quan">
        @foreach ([['Người học đang học', number_format($activeLearnerCount), 'Toàn phạm vi được xem', 'mint'], ['Lớp theo lịch hôm nay', $scheduledSectionCount, $unopenedSectionCount . ' chưa mở buổi', 'coral'], ['Lượt điểm danh hôm nay', $presentCount + $lateCount + $excusedCount + $unexcusedCount, $presentCount . ' có mặt · ' . $lateCount . ' đi muộn', 'yellow'], ['Cảnh báo cần xử lý', $openAlertCount, $unmarkedSessions . ' buổi chưa xác nhận', 'blue']] as [$label, $value, $caption, $tone])
            <article class="metric-card">
                <div class="metric-top"><span class="metric-dot dot-{{ $tone }}"></span><span class="metric-period">{{ $today->format('d/m/Y') }}</span></div>
                <strong class="metric-value">{{ $value }}</strong>
                <span class="metric-label">{{ $label }}</span>
                <span class="metric-caption">{{ $caption }}</span>
            </article>
        @endforeach
    </section>

    <section class="today-attendance-summary" aria-label="Thống kê trạng thái điểm danh hôm nay">
        <span><strong>{{ $presentCount }}</strong> Có mặt</span>
        <span><strong>{{ $lateCount }}</strong> Đi muộn</span>
        <span><strong>{{ $excusedCount }}</strong> Vắng có phép</span>
        <span><strong>{{ $unexcusedCount }}</strong> Vắng không phép</span>
        <span><strong>{{ $otherCount }}</strong> Khác</span>
    </section>

    <section class="panel today-schedule-panel">
        <div class="panel-heading"><div><p class="eyebrow">THỜI KHÓA BIỂU HÔM NAY</p><h2>Lớp và trạng thái điểm danh</h2></div><span class="panel-count">{{ $todaySections->count() }} LỚP</span></div>
        <div class="today-class-list">
            @forelse ($todaySections as $section)
                @php $session = $todaySessions->get($section->id); @endphp
                <div class="today-class-row">
                    <span class="today-class-time">{{ substr($section->start_time, 0, 5) }}<small>{{ substr($section->end_time, 0, 5) }}</small></span>
                    <span class="today-class-copy"><strong>{{ $section->section_code }} · {{ $section->course->code }} {{ $section->course->name }}</strong><small>{{ $section->room }} · {{ $section->lecturer?->name ?? 'Chưa phân công' }}</small></span>
                    @if ($session)
                        <a class="lecturer-status {{ $session->status === 'confirmed' ? 'is-active' : 'profile-submitted' }}" href="{{ route('attendance-sessions.show', $session) }}">{{ $session->status === 'confirmed' ? 'Đã xác nhận' : 'Đang điểm danh' }}</a>
                    @else
                        <span class="lecturer-status is-inactive">Chưa điểm danh</span>
                    @endif
                </div>
            @empty
                <p class="course-empty-note">Hôm nay không có lớp học phần theo lịch trong phạm vi được xem.</p>
            @endforelse
        </div>
    </section>

    <div class="dashboard-columns">
        <section class="panel work-panel">
            <div class="panel-heading">
                <div><p class="eyebrow">PHỐI HỢP LIÊN ĐƠN VỊ</p><h2>Phân công đầu việc</h2></div>
                <span class="panel-count">05 NHÓM</span>
            </div>
            <div class="responsibility-list">
                <a class="responsibility-row" href="{{ route('work.show', 'classes') }}"><span class="row-index">01</span><span class="row-copy"><strong>Danh mục đào tạo</strong><small>Khoa, học phần, lớp học phần và người học</small></span><span class="owner-tag owner-teal">Phòng đào tạo</span><span class="row-arrow">↗</span></a>
                <a class="responsibility-row" href="{{ route('work.show', 'attendance-sessions') }}"><span class="row-index">02</span><span class="row-copy"><strong>Ghi nhận điểm danh</strong><small>Mở phiên và cập nhật tình trạng tham dự</small></span><span class="owner-tag owner-coral">Giảng viên</span><span class="row-arrow">↗</span></a>
                <a class="responsibility-row" href="{{ route('work.show', 'absence-requests') }}"><span class="row-index">03</span><span class="row-copy"><strong>Giải trình vắng</strong><small>Tiếp nhận và phối hợp xử lý</small></span><span class="owner-tag owner-yellow">Người học / Cố vấn</span><span class="row-arrow">↗</span></a>
                <a class="responsibility-row" href="{{ route('work.show', 'attendance-reports') }}"><span class="row-index">04</span><span class="row-copy"><strong>Báo cáo chuyên cần</strong><small>Tổng hợp tình hình theo lớp và học phần</small></span><span class="owner-tag owner-blue">Phòng đào tạo</span><span class="row-arrow">↗</span></a>
                <a class="responsibility-row" href="{{ route('work.show', 'accounts') }}"><span class="row-index">05</span><span class="row-copy"><strong>Tài khoản và vai trò</strong><small>Quản lý danh tính và phạm vi công việc</small></span><span class="owner-tag owner-ink">Quản trị</span><span class="row-arrow">↗</span></a>
            </div>
            <p class="panel-footnote">Đầu mối phụ trách là phân công khởi tạo, có thể điều chỉnh theo quy trình thực tế.</p>
        </section>

        <aside class="panel setup-panel">
            <div class="panel-heading">
                <div><p class="eyebrow">BẮT ĐẦU</p><h2>Thiết lập ban đầu</h2></div>
                <span class="setup-badge">0 / 4</span>
            </div>
            <ol class="setup-list">
                <li><span class="step-number">1</span><span><strong>Khai báo đơn vị</strong><small>Khoa và bộ môn</small></span><a href="{{ route('work.show', 'departments') }}" aria-label="Mở khoa và đơn vị">↗</a></li>
                <li><span class="step-number">2</span><span><strong>Tạo học phần</strong><small>Danh mục môn học</small></span><a href="{{ route('work.show', 'courses') }}" aria-label="Mở học phần">↗</a></li>
                <li><span class="step-number">3</span><span><strong>Tổ chức lớp học</strong><small>Lớp và lịch giảng dạy</small></span><a href="{{ route('work.show', 'classes') }}" aria-label="Mở lớp học phần">↗</a></li>
                <li><span class="step-number">4</span><span><strong>Thêm người học</strong><small>Hồ sơ và danh sách lớp</small></span><a href="{{ route('work.show', 'learners') }}" aria-label="Mở người học">↗</a></li>
            </ol>
            <div class="setup-footer"><span class="status-dot status-dot-light"></span> Sẵn sàng cấu hình dữ liệu</div>
        </aside>
    </div>
@endsection