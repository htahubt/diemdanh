@php
    $statusLabels = ['open' => 'Đang mở', 'confirmed' => 'Đã xác nhận'];
    $nextSessionNumber = $sessions->max('session_number') + 1;
@endphp

@extends('layouts.app')

@section('title', 'Buổi học lớp ' . $courseSection->section_code)
@section('page_heading', 'Buổi học')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">{{ $courseSection->academic_year }} / HỌC KỲ {{ $courseSection->semester }}</p>
            <h1>Buổi học · {{ $courseSection->section_code }}</h1>
            <p class="page-lede">{{ $courseSection->course->code }} · {{ $courseSection->course->name }} · {{ $courseSection->room }} · {{ $courseSection->expected_sessions }} buổi dự kiến</p>
        </div>
        <a class="button button-secondary" href="{{ route('classes.index') }}">Quay lại lớp học phần</a>
    </div>

    @if (session('status'))
        <div class="lecturer-flash" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="lecturer-flash config-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="panel config-departments-panel">
        <div class="panel-heading">
            <div><p class="eyebrow">THỜI KHÓA BIỂU</p><h2>Tạo buổi theo ngày học thực tế</h2></div>
            <span class="panel-count">{{ $sessions->count() }} / {{ $courseSection->expected_sessions }} BUỔI</span>
        </div>
        <dl class="session-schedule-summary">
            <div><dt>Thứ</dt><dd>{{ ['Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy', 'Chủ nhật'][$courseSection->weekday - 1] }}</dd></div>
            <div><dt>Giờ</dt><dd>{{ substr($courseSection->start_time, 0, 5) }}–{{ substr($courseSection->end_time, 0, 5) }}</dd></div>
            <div><dt>Phòng</dt><dd>{{ $courseSection->room }}</dd></div>
            <div><dt>Khoảng thời gian</dt><dd>{{ $courseSection->starts_on->format('d/m/Y') }}–{{ $courseSection->ends_on->format('d/m/Y') }}</dd></div>
        </dl>
        @if ($canCreateSession)
            <form class="session-create-form" action="{{ route('classes.sessions.store', $courseSection) }}" method="POST">
                @csrf
                <label class="lecturer-field"><span>Ngày diễn ra buổi {{ $nextSessionNumber }}</span><input type="date" name="session_date" min="{{ $courseSection->starts_on->format('Y-m-d') }}" max="{{ $courseSection->ends_on->format('Y-m-d') }}" required></label>
                <button class="button button-primary" type="submit" @disabled(! $courseSection->is_active || $sessions->count() >= $courseSection->expected_sessions)>Tạo buổi và mở điểm danh</button>
            </form>
        @else
            <p class="course-empty-note">Chỉ giảng viên được phân công mới mở buổi học.</p>
        @endif
    </section>

    <section class="panel config-departments-panel">
        <div class="panel-heading"><div><p class="eyebrow">CÁC BUỔI ĐÃ TẠO</p><h2>Danh sách buổi học</h2></div></div>
        <div class="config-table-wrap">
            <table class="config-table session-table">
                <thead><tr><th>Buổi</th><th>Ngày</th><th>Thời gian</th><th>Phòng</th><th>Người học</th><th>Trạng thái</th><th></th></tr></thead>
                <tbody>
                    @forelse ($sessions as $session)
                        <tr>
                            <td class="lecturer-code">Buổi {{ $session->session_number }}</td>
                            <td>{{ $session->session_date->format('d/m/Y') }}</td>
                            <td>{{ substr($session->start_time, 0, 5) }}–{{ substr($session->end_time, 0, 5) }}</td>
                            <td>{{ $session->room ?: '—' }}</td>
                            <td>{{ $session->attendance_records_count }}</td>
                            <td><span class="lecturer-status {{ $session->status === 'confirmed' ? 'is-active' : 'profile-submitted' }}">{{ $statusLabels[$session->status] ?? $session->status }}</span></td>
                            <td><a class="button button-secondary session-open-link" href="{{ route('attendance-sessions.show', $session) }}">Mở điểm danh</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="lecturer-empty">Chưa tạo buổi học nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
