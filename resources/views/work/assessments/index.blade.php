@extends('layouts.app')

@section('title', 'Điểm kiểm tra lớp ' . $courseSection->section_code)
@section('page_heading', 'Điểm kiểm tra')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">{{ $courseSection->academic_year }} / HỌC KỲ {{ $courseSection->semester }}</p>
            <h1>Điểm kiểm tra · {{ $courseSection->section_code }}</h1>
            <p class="page-lede">{{ $courseSection->course->code }} · {{ $courseSection->course->name }}</p>
        </div>
        <a class="button button-secondary" href="{{ route('classes.index') }}">Quay lại lớp học phần</a>
    </div>

    @if (session('status'))<div class="lecturer-flash" role="status">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="lecturer-flash config-error" role="alert">{{ $errors->first() }}</div>@endif

    @if (auth()->user()->canManageTraining())
        <section class="panel config-departments-panel">
            <div class="panel-heading"><div><p class="eyebrow">CẤU HÌNH ĐIỂM</p><h2>Thêm cột kiểm tra</h2></div><span class="panel-count">{{ number_format((float) $assessments->sum('weight'), 2) }}% TRỌNG SỐ ĐÃ DÙNG</span></div>
            <form class="assessment-create-form" action="{{ route('classes.assessments.store', $courseSection) }}" method="POST">
                @csrf
                <label class="lecturer-field"><span>Tên bài kiểm tra</span><input name="title" maxlength="160" required placeholder="Kiểm tra giữa kỳ"></label>
                <label class="lecturer-field"><span>Loại điểm</span><input name="kind" maxlength="40" required placeholder="quiz / midterm / final"></label>
                <label class="lecturer-field"><span>Thang điểm tối đa</span><input type="number" name="max_score" value="{{ old('max_score', 10) }}" min="0.01" max="1000" step="0.01" required></label>
                <label class="lecturer-field"><span>Trọng số (%)</span><input type="number" name="weight" value="{{ old('weight') }}" min="0.01" max="100" step="0.01" required></label>
                <label class="lecturer-field"><span>Hạn sửa trực tiếp</span><input type="date" name="edit_until" value="{{ old('edit_until') }}"></label>
                <button class="button button-primary" type="submit">Thêm cột điểm</button>
            </form>
        </section>
    @endif

    <section class="panel config-departments-panel">
        <div class="panel-heading"><div><p class="eyebrow">CÁC CỘT ĐIỂM</p><h2>Thành phần kiểm tra</h2></div><span class="panel-count">{{ $assessments->count() }} CỘT</span></div>
        <div class="config-table-wrap">
            <table class="config-table assessment-table">
                <thead><tr><th>Tên</th><th>Loại</th><th>Thang điểm</th><th>Trọng số</th><th>Đã chấm</th><th>Hạn sửa</th><th></th></tr></thead>
                <tbody>
                    @forelse ($assessments as $assessment)
                        <tr>
                            <td><strong>{{ $assessment->title }}</strong></td>
                            <td>{{ $assessment->kind }}</td>
                            <td>{{ $assessment->max_score }}</td>
                            <td>{{ $assessment->weight }}%</td>
                            <td>{{ $assessment->graded_count }} / {{ $courseSection->learners_count }}</td>
                            <td>{{ $assessment->edit_until?->format('d/m/Y') ?: 'Không đặt' }}</td>
                            <td><a class="button button-secondary assessment-open-link" href="{{ route('assessments.show', $assessment) }}">Mở bảng điểm</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="lecturer-empty">Chưa cấu hình cột điểm kiểm tra.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
