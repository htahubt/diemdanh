@php
    $statusLabels = ['graded' => 'Đã chấm', 'absent' => 'Vắng kiểm tra', 'not_entered' => 'Chưa có điểm'];
    $currentFilters = array_filter($filters, fn ($value) => $value !== null && $value !== '');
@endphp

@extends('layouts.app')

@section('title', 'Báo cáo điểm kiểm tra')
@section('page_heading', 'Báo cáo điểm kiểm tra')

@section('content')
    <div class="page-heading">
        <div><p class="eyebrow">THEO DÕI / KẾT QUẢ KIỂM TRA</p><h1>Báo cáo điểm kiểm tra</h1><p class="page-lede">Điểm thành phần, thang điểm, trọng số và trạng thái nhập điểm theo roster.</p></div>
        <a class="button button-primary" href="{{ route('assessment-reports.export', $currentFilters) }}">Xuất CSV</a>
    </div>

    <section class="lecturer-stats">
        <div><span>Đã chấm</span><strong>{{ $gradedCount }}</strong></div>
        <div><span>Vắng kiểm tra</span><strong>{{ $absentCount }}</strong></div>
        <div><span>Chưa có điểm</span><strong>{{ $notEnteredCount }}</strong></div>
    </section>

    <section class="panel config-departments-panel">
        <form class="report-filters" action="{{ route('assessment-reports.index') }}" method="GET">
            <label class="lecturer-field"><span>Năm học</span><input name="academic_year" value="{{ $filters['academic_year'] ?? '' }}" placeholder="2026-2027"></label>
            <label class="lecturer-field"><span>Học kỳ</span><select name="semester"><option value="">Tất cả</option>@foreach ([1, 2, 3] as $term)<option value="{{ $term }}" @selected(($filters['semester'] ?? '') == $term)>Học kỳ {{ $term }}</option>@endforeach</select></label>
            <label class="lecturer-field"><span>Lớp học phần</span><select name="course_section_id"><option value="">Tất cả lớp</option>@foreach ($sections as $section)<option value="{{ $section->id }}" @selected(($filters['course_section_id'] ?? '') == $section->id)>{{ $section->section_code }} · {{ $section->course->code }}</option>@endforeach</select></label>
            <label class="lecturer-field"><span>Trạng thái</span><select name="status"><option value="">Tất cả</option>@foreach ($statusLabels as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></label>
            <button class="button button-secondary" type="submit">Lọc</button>
            @if ($currentFilters)<a class="config-toggle" href="{{ route('assessment-reports.index') }}">Xóa lọc</a>@endif
        </form>
        <div class="config-table-wrap">
            <table class="config-table assessment-report-table">
                <thead><tr><th>Lớp / Học phần</th><th>Cột điểm</th><th>Mã người học</th><th>Họ tên</th><th>Điểm / Thang</th><th>Trọng số</th><th>Trạng thái</th><th>Người nhập</th></tr></thead>
                <tbody>
                    @forelse ($results as $result)
                        <tr>
                            <td>{{ $result->assessment->courseSection->section_code }}<small class="course-description">{{ $result->assessment->courseSection->course->code }}</small></td>
                            <td>{{ $result->assessment->title }}<small class="course-description">{{ $result->assessment->kind }}</small></td>
                            <td class="lecturer-code">{{ $result->learner->student_code }}</td>
                            <td>{{ $result->learner->full_name }}</td>
                            <td>{{ $result->score ?? '—' }} / {{ $result->assessment->max_score }}</td>
                            <td>{{ $result->assessment->weight }}%</td>
                            <td>{{ $statusLabels[$result->status] ?? $result->status }}</td>
                            <td>{{ $result->enteredBy?->name ?? '—' }}<small class="course-description">{{ $result->entered_at?->format('d/m/Y H:i') }}</small></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="lecturer-empty">Không có kết quả kiểm tra theo điều kiện lọc.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="course-pagination">{{ $results->links() }}</div>
    </section>
@endsection
