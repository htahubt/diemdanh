@extends('layouts.app')

@section('title', 'Sửa học phần')
@section('page_heading', 'Sửa học phần')

@section('content')
    <div class="page-heading">
        <div><p class="eyebrow">DANH MỤC ĐÀO TẠO / CHƯƠNG TRÌNH</p><h1>{{ $course->name }}</h1><p class="page-lede">Cập nhật thông tin học phần.</p></div>
        <a class="button button-secondary" href="{{ route('courses.index') }}">← Học phần</a>
    </div>

    @if ($errors->any())
        <div class="lecturer-flash config-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="panel lecturer-form-panel department-edit-panel">
        <form action="{{ route('courses.update', $course) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="lecturer-form-grid">
                <label class="lecturer-field"><span>Mã học phần</span><input name="code" value="{{ old('code', $course->code) }}" maxlength="30" required>@error('code')<small class="field-error">{{ $message }}</small>@enderror</label>
                <label class="lecturer-field"><span>Tên học phần</span><input name="name" value="{{ old('name', $course->name) }}" maxlength="160" required>@error('name')<small class="field-error">{{ $message }}</small>@enderror</label>
                <label class="lecturer-field"><span>Số tín chỉ</span><input type="number" name="credits" value="{{ old('credits', $course->credits) }}" min="1" max="30" required>@error('credits')<small class="field-error">{{ $message }}</small>@enderror</label>
                <label class="lecturer-field"><span>Khoa phụ trách</span><select name="department_id"><option value="">Chưa phân công</option>@foreach ($departments as $department)<option value="{{ $department->id }}" @selected(old('department_id', $course->department_id) == $department->id)>{{ $department->name }}</option>@endforeach</select>@error('department_id')<small class="field-error">{{ $message }}</small>@enderror</label>
                <label class="lecturer-field course-edit-description"><span>Mô tả</span><textarea name="description" rows="4" maxlength="2000">{{ old('description', $course->description) }}</textarea>@error('description')<small class="field-error">{{ $message }}</small>@enderror</label>
            </div>
            <div class="lecturer-form-actions"><a class="button button-secondary" href="{{ route('courses.index') }}">Hủy</a><button class="button button-primary" type="submit">Lưu thay đổi</button></div>
        </form>
    </section>
@endsection
