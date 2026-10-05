@php
    $statusLabels = ['present' => 'Có mặt', 'late' => 'Đi muộn', 'excused' => 'Vắng có phép', 'unexcused' => 'Vắng không phép', 'other' => 'Khác', 'unconfirmed' => 'Chưa xác định'];
    $currentFilters = array_filter($filters, fn ($value) => $value !== null && $value !== '');
@endphp

@extends('layouts.app')

@section('title', 'Báo cáo chuyên cần')
@section('page_heading', 'Báo cáo chuyên cần')

@section('content')
    <div class="page-heading">
        <div><p class="eyebrow">THEO DÕI / CHUYÊN CẦN</p><h1>Báo cáo chuyên cần</h1><p class="page-lede">Chi tiết theo buổi, lớp và người học; phạm vi giảng viên giới hạn theo phân công.</p></div>
        <a class="button button-primary" href="{{ route('attendance-reports.export', $currentFilters) }}">Xuất CSV</a>
    </div>

    <section class="metric-grid report-metrics">
        @foreach ([['Tổng lượt', $totalCount, 'blue'], ['Có mặt', $presentCount, 'mint'], ['Đi muộn', $lateCount, 'yellow'], ['Vắng không phép', $unexcusedCount, 'coral']] as [$label, $count, $tone])
            <article class="metric-card"><div class="metric-top"><span class="metric-dot dot-{{ $tone }}"></span></div><strong class="metric-value">{{ $count }}</strong><span class="metric-label">{{ $label }}</span></article>
        @endforeach
    </section>

    <section class="panel config-departments-panel">
        <form class="report-filters" action="{{ route('attendance-reports.index') }}" method="GET">
            <label class="lecturer-field"><span>Từ ngày</span><input type="date" name="from" value="{{ $filters['from'] ?? '' }}"></label>
            <label class="lecturer-field"><span>Đến ngày</span><input type="date" name="to" value="{{ $filters['to'] ?? '' }}"></label>
            <label class="lecturer-field"><span>Đơn vị</span><select name="department_id"><option value="">Tất cả đơn vị</option>@foreach ($departments as $department)<option value="{{ $department->id }}" @selected(($filters['department_id'] ?? '') == $department->id)>{{ $department->name }}</option>@endforeach</select></label>
            <label class="lecturer-field"><span>Năm học</span><input name="academic_year" value="{{ $filters['academic_year'] ?? '' }}" placeholder="2026-2027"></label>
            <label class="lecturer-field"><span>Học kỳ</span><select name="semester"><option value="">Tất cả</option>@foreach ([1, 2, 3] as $term)<option value="{{ $term }}" @selected(($filters['semester'] ?? '') == $term)>Học kỳ {{ $term }}</option>@endforeach</select></label>
            <label class="lecturer-field"><span>Lớp học phần</span><select name="course_section_id"><option value="">Tất cả lớp</option>@foreach ($sections as $section)<option value="{{ $section->id }}" @selected(($filters['course_section_id'] ?? '') == $section->id)>{{ $section->section_code }} · {{ $section->course->code }}</option>@endforeach</select></label>
            <label class="lecturer-field"><span>Người học</span><select name="learner_id"><option value="">Tất cả người học</option>@foreach ($learners as $learner)<option value="{{ $learner->id }}" @selected(($filters['learner_id'] ?? '') == $learner->id)>{{ $learner->student_code }} · {{ $learner->full_name }}</option>@endforeach</select></label>
            <label class="lecturer-field"><span>Trạng thái</span><select name="status"><option value="">Tất cả</option>@foreach ($statusLabels as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></label>
            <button class="button button-secondary" type="submit">Lọc</button>
            @if ($currentFilters)<a class="config-toggle" href="{{ route('attendance-reports.index') }}">Xóa lọc</a>@endif
        </form>
        <div class="config-table-wrap">
            <table class="config-table report-table">
                <thead><tr><th>Ngày / Buổi</th><th>Lớp / Học phần</th><th>Mã người học</th><th>Họ tên</th><th>Trạng thái</th><th>Người ghi nhận</th><th>Xác nhận</th></tr></thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            <td>{{ $record->attendanceSession->session_date->format('d/m/Y') }}<small class="course-description">Buổi {{ $record->attendanceSession->session_number }}</small></td>
                            <td>{{ $record->attendanceSession->courseSection->section_code }}<small class="course-description">{{ $record->attendanceSession->courseSection->course->code }}</small></td>
                            <td class="lecturer-code">{{ $record->learner->student_code }}</td>
                            <td>{{ $record->learner->full_name }}</td>
                            <td>{{ $statusLabels[$record->status] ?? $record->status }}</td>
                            <td>{{ $record->recordedBy?->name ?? '—' }}</td>
                            <td>{{ $record->attendanceSession->confirmed_at?->format('d/m/Y H:i') ?? 'Chưa xác nhận' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="lecturer-empty">Không có dữ liệu trong điều kiện lọc.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="course-pagination">{{ $records->links() }}</div>
    </section>
@endsection
