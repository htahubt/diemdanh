@extends('layouts.app')

@section('title', 'Lớp học phần')
@section('page_heading', 'Lớp học phần')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">DANH MỤC ĐÀO TẠO / TỔ CHỨC GIẢNG DẠY</p>
            <h1>Lớp học phần</h1>
            <p class="page-lede">Mở học phần theo học kỳ và năm học, phân công giảng viên và đặt sĩ số dự kiến.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="lecturer-flash" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="lecturer-flash config-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="panel config-departments-panel">
        <div class="panel-heading">
            <div><p class="eyebrow">MỞ LỚP MỚI</p><h2>Tạo lớp học phần</h2></div>
            <span class="panel-count">{{ $activeCount }} ĐANG MỞ · {{ $inactiveCount }} ĐÃ NGỪNG</span>
        </div>
        @if (! $canManageTraining)
            <p class="course-empty-note">Chỉ xem các lớp thuộc đơn vị được phân công.</p>
        @elseif ($courses->isEmpty())
            <p class="course-empty-note">Cần có ít nhất một học phần đang dùng trước khi mở lớp.</p>
        @else
            <form class="config-add-form section-add-form" action="{{ route('classes.store') }}" method="POST">
                @csrf
                <label class="lecturer-field"><span>Học phần</span><select name="course_id" required><option value="">Chọn học phần</option>@foreach ($courses as $course)<option value="{{ $course->id }}" @selected(old('course_id') == $course->id)>{{ $course->code }} · {{ $course->name }}</option>@endforeach</select></label>
                <label class="lecturer-field"><span>Mã lớp</span><input name="section_code" value="{{ old('section_code') }}" maxlength="40" required placeholder="IT101-01"></label>
                <label class="lecturer-field"><span>Học kỳ</span><select name="semester" required><option value="">Chọn học kỳ</option>@foreach ([1, 2, 3] as $semester)<option value="{{ $semester }}" @selected(old('semester') == $semester)>Học kỳ {{ $semester }}</option>@endforeach</select></label>
                <label class="lecturer-field"><span>Năm học</span><input name="academic_year" value="{{ old('academic_year', now()->year . '-' . (now()->year + 1)) }}" maxlength="9" required placeholder="2026-2027"></label>
                <label class="lecturer-field"><span>Giảng viên</span><select name="lecturer_id"><option value="">Chưa phân công</option>@foreach ($lecturers as $lecturer)<option value="{{ $lecturer->id }}" @selected(old('lecturer_id') == $lecturer->id)>{{ $lecturer->lecturer_code }} · {{ $lecturer->name }}</option>@endforeach</select></label>
                <label class="lecturer-field"><span>Sĩ số dự kiến</span><input type="number" name="capacity" value="{{ old('capacity', 40) }}" min="1" max="500" required></label>
                @include('work.classes._schedule-fields', ['section' => null])
                <button class="button button-primary" type="submit">Mở lớp</button>
            </form>
        @endif

        <form class="course-filters section-filters" action="{{ route('classes.index') }}" method="GET">
            <label class="lecturer-search"><span class="sr-only">Tìm lớp học phần</span><input name="search" value="{{ request('search') }}" placeholder="Tìm mã lớp, học phần hoặc giảng viên..."></label>
            <label class="lecturer-status-filter"><span class="sr-only">Năm học</span><input name="academic_year" value="{{ request('academic_year') }}" placeholder="Năm học, ví dụ 2026-2027"></label>
            <label class="lecturer-status-filter"><span class="sr-only">Học kỳ</span><select name="semester"><option value="">Tất cả học kỳ</option>@foreach ([1, 2, 3] as $semester)<option value="{{ $semester }}" @selected(request('semester') === (string) $semester)>Học kỳ {{ $semester }}</option>@endforeach</select></label>
            <label class="lecturer-status-filter"><span class="sr-only">Trạng thái</span><select name="status"><option value="">Tất cả trạng thái</option><option value="active" @selected(request('status') === 'active')>Đang mở</option><option value="inactive" @selected(request('status') === 'inactive')>Đã ngừng</option></select></label>
            <button class="button button-secondary" type="submit">Lọc</button>
            @if (request()->hasAny(['search', 'academic_year', 'semester', 'status']))<a class="config-toggle" href="{{ route('classes.index') }}">Xóa lọc</a>@endif
        </form>

        <div class="config-table-wrap">
            <table class="config-table section-table">
                <thead><tr><th>Mã lớp</th><th>Học phần</th><th>Học kỳ</th><th>Lịch / phòng</th><th>Giảng viên</th><th>Sĩ số</th><th>Buổi</th><th>Trạng thái</th><th></th></tr></thead>
                <tbody>
                    @forelse ($sections as $section)
                        <tr>
                            <td class="lecturer-code">{{ $section->section_code }}</td>
                            <td><strong>{{ $section->course->code }} · {{ $section->course->name }}</strong><small class="course-description">{{ $section->academic_year }} · {{ $section->course->credits }} tín chỉ</small></td>
                            <td>Học kỳ {{ $section->semester }}</td>
                            <td>{{ $section->weekday ? ['Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy', 'Chủ nhật'][$section->weekday - 1] : 'Chưa xếp lịch' }}<small class="course-description">{{ $section->start_time ? substr($section->start_time, 0, 5) . '–' . substr($section->end_time, 0, 5) : '' }} · {{ $section->room }}</small></td>
                            <td>{{ $section->lecturer?->name ?? 'Chưa phân công' }}</td>
                            <td>{{ $section->capacity }}</td>
                            <td>{{ $section->attendance_sessions_count }} / {{ $section->expected_sessions ?? '—' }}<small class="course-description">đã tạo / dự kiến</small></td>
                            <td><span class="lecturer-status {{ $section->is_active ? 'is-active' : 'is-inactive' }}">{{ $section->is_active ? 'Đang mở' : 'Đã ngừng' }}</span></td>
                            <td><div class="config-row-actions course-actions">
                                        <a class="config-toggle" href="{{ route('classes.learners.index', $section) }}">Người học</a>
                                <a class="config-toggle" href="{{ route('classes.sessions.index', $section) }}">Buổi học</a>
                                        <a class="config-toggle" href="{{ route('classes.assessments.index', $section) }}">Điểm kiểm tra</a>
                                @if ($canManageTraining)
                                <a class="icon-action" href="{{ route('classes.edit', $section) }}" aria-label="Sửa lớp {{ $section->section_code }}" title="Sửa lớp"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg></a>
                                <form action="{{ route('classes.toggle', $section) }}" method="POST">@csrf @method('PATCH')<button class="icon-action {{ $section->is_active ? 'is-on' : '' }}" type="submit" aria-label="{{ $section->is_active ? 'Ngừng lớp' : 'Mở lại lớp' }} {{ $section->section_code }}" title="{{ $section->is_active ? 'Ngừng lớp' : 'Mở lại lớp' }}"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="7"/><circle cx="{{ $section->is_active ? 15 : 9 }}" cy="12" r="3"/></svg></button></form>
                                <form action="{{ route('classes.destroy', $section) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa lớp học phần này?')">@csrf @method('DELETE')<button class="icon-action is-danger" type="submit" aria-label="Xóa lớp {{ $section->section_code }}" title="Xóa lớp"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg></button></form>
                                @endif
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="lecturer-empty">Chưa có lớp học phần phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="course-pagination">{{ $sections->links() }}</div>
    </section>
@endsection
