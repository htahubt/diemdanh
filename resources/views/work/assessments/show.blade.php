@php
    $resultStatusLabels = ['graded' => 'Đã chấm', 'absent' => 'Vắng kiểm tra', 'not_entered' => 'Chưa có điểm'];
    $isLocked = $assessment->edit_until && now()->toDateString() > $assessment->edit_until->toDateString();
@endphp

@extends('layouts.app')

@section('title', $assessment->title . ' · ' . $assessment->courseSection->section_code)
@section('page_heading', 'Bảng điểm kiểm tra')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">{{ $assessment->courseSection->academic_year }} / HỌC KỲ {{ $assessment->courseSection->semester }}</p>
            <h1>{{ $assessment->title }}</h1>
            <p class="page-lede">{{ $assessment->courseSection->section_code }} · {{ $assessment->courseSection->course->code }} · Thang {{ $assessment->max_score }} · Trọng số {{ $assessment->weight }}%</p>
        </div>
        <a class="button button-secondary" href="{{ route('classes.assessments.index', $assessment->courseSection) }}">Danh sách cột điểm</a>
    </div>

    @if (session('status'))<div class="lecturer-flash" role="status">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="lecturer-flash config-error" role="alert">{{ $errors->first() }}</div>@endif
    @if ($isLocked)<div class="lecturer-flash config-error" role="status">Đã quá hạn sửa trực tiếp. Luồng đề nghị điều chỉnh sau hạn đang được thiết lập.</div>@endif

    <section class="panel config-departments-panel">
        <div class="panel-heading"><div><p class="eyebrow">DANH SÁCH CHÍNH THỨC</p><h2>Điểm kiểm tra</h2></div><span class="panel-count">{{ $learners->count() }} NGƯỜI HỌC</span></div>
        <form action="{{ route('assessments.results.update', $assessment) }}" method="POST">
            @csrf
            @method('PATCH')
            <div class="config-table-wrap">
                <table class="config-table assessment-results-table">
                    <thead><tr><th>Mã người học</th><th>Họ tên</th><th>Điểm / trạng thái</th><th>Người nhập</th><th>Lịch sử</th></tr></thead>
                    <tbody>
                        @forelse ($learners as $learner)
                            @php $result = $resultsByLearner->get($learner->id); @endphp
                            <tr>
                                <td class="lecturer-code">{{ $learner->student_code }}</td>
                                <td>{{ $learner->full_name }}</td>
                                <td>
                                    <div class="assessment-result-fields">
                                        <label class="sr-only" for="score-status-{{ $learner->id }}">Trạng thái điểm {{ $learner->full_name }}</label>
                                        <select id="score-status-{{ $learner->id }}" name="results[{{ $learner->id }}][status]">
                                            @foreach ($resultStatusLabels as $value => $label)
                                                <option value="{{ $value }}" @selected(($result?->status ?? 'not_entered') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <label class="sr-only" for="score-{{ $learner->id }}">Điểm {{ $learner->full_name }}</label>
                                        <input id="score-{{ $learner->id }}" type="number" name="results[{{ $learner->id }}][score]" value="{{ $result?->score }}" min="0" max="{{ $assessment->max_score }}" step="0.01" placeholder="—" @disabled($isLocked)>
                                    </div>
                                </td>
                                <td>{{ $result?->enteredBy?->name ?? '—' }}<small class="course-description">{{ $result?->entered_at?->format('d/m/Y H:i') }}</small></td>
                                <td>@if ($result?->changes->isNotEmpty())<details><summary>{{ $result->changes->count() }} lần sửa</summary>@foreach ($result->changes as $change)<small class="course-description">{{ $change->before_score ?? '—' }} → {{ $change->after_score ?? '—' }} · {{ $change->actor?->name }} · {{ $change->created_at->format('d/m/Y H:i') }}@if ($change->reason) · {{ $change->reason }}@endif</small>@endforeach</details>@else<span class="course-description">—</span>@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="lecturer-empty">Chưa có người học có hiệu lực trong roster.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if (! $isLocked && $learners->isNotEmpty())
                <div class="assessment-save-row">
                    <label class="lecturer-field"><span>Lý do chỉnh sửa (nếu có)</span><input name="reason" maxlength="1000"></label>
                    <button class="button button-primary" type="submit">Lưu điểm</button>
                </div>
            @endif
        </form>
    </section>
@endsection
