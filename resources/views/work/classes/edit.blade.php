@extends('layouts.app')

@section('title', 'Sửa lớp học phần')
@section('page_heading', 'Sửa lớp học phần')

@section('content')
    <div class="page-heading">
        <div><p class="eyebrow">TỔ CHỨC GIẢNG DẠY / LỚP HỌC PHẦN</p><h1>{{ $section->section_code }}</h1><p class="page-lede">{{ $section->course->code }} · {{ $section->course->name }}</p></div>
        <a class="button button-secondary" href="{{ route('classes.index') }}">← Lớp học phần</a>
    </div>
    @if ($errors->any())<div class="lecturer-flash config-error" role="alert">{{ $errors->first() }}</div>@endif
    <section class="panel lecturer-form-panel department-edit-panel">
        <form action="{{ route('classes.update', $section) }}" method="POST">
            @csrf @method('PUT')
            <div class="lecturer-form-grid">
                <label class="lecturer-field"><span>Học phần</span><select name="course_id" required>@foreach ($courses as $course)<option value="{{ $course->id }}" @selected(old('course_id', $section->course_id) == $course->id)>{{ $course->code }} · {{ $course->name }}</option>@endforeach</select>@error('course_id')<small class="field-error">{{ $message }}</small>@enderror</label>
                <label class="lecturer-field"><span>Mã lớp</span><input name="section_code" value="{{ old('section_code', $section->section_code) }}" maxlength="40" required>@error('section_code')<small class="field-error">{{ $message }}</small>@enderror</label>
                <label class="lecturer-field"><span>Học kỳ</span><select name="semester" required>@foreach ([1, 2, 3] as $semester)<option value="{{ $semester }}" @selected(old('semester', $section->semester) == $semester)>Học kỳ {{ $semester }}</option>@endforeach</select>@error('semester')<small class="field-error">{{ $message }}</small>@enderror</label>
                <label class="lecturer-field"><span>Năm học</span><input name="academic_year" value="{{ old('academic_year', $section->academic_year) }}" maxlength="9" required>@error('academic_year')<small class="field-error">{{ $message }}</small>@enderror</label>
                <label class="lecturer-field"><span>Giảng viên</span><select name="lecturer_id"><option value="">Chưa phân công</option>@foreach ($lecturers as $lecturer)<option value="{{ $lecturer->id }}" @selected(old('lecturer_id', $section->lecturer_id) == $lecturer->id)>{{ $lecturer->lecturer_code }} · {{ $lecturer->name }}</option>@endforeach</select>@error('lecturer_id')<small class="field-error">{{ $message }}</small>@enderror</label>
                <label class="lecturer-field"><span>Sĩ số dự kiến</span><input type="number" name="capacity" value="{{ old('capacity', $section->capacity) }}" min="1" max="500" required>@error('capacity')<small class="field-error">{{ $message }}</small>@enderror</label>
                @include('work.classes._schedule-fields', ['section' => $section])
            </div>
            <div class="lecturer-form-actions"><a class="button button-secondary" href="{{ route('classes.index') }}">Hủy</a><button class="button button-primary" type="submit">Lưu thay đổi</button></div>
        </form>
    </section>
@endsection
