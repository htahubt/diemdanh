@php
    $studyStatusMap = [
        'active' => 'Đang học',
        'paused' => 'Tạm dừng',
        'inactive' => 'Không hoạt động',
        'graduated' => 'Tốt nghiệp',
        'dropped' => 'Bỏ học',
    ];
@endphp

@extends('layouts.app')

@section('title', 'Chỉnh sửa người học')
@section('page_heading', 'Chỉnh sửa người học')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">DANH MỤC ĐÀO TẠO / NGƯỜI HỌC</p>
            <h1>Chỉnh sửa hồ sơ</h1>
            <p class="page-lede">Cập nhật thông tin và trạng thái học tập của {{ $learner->full_name }}.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="lecturer-flash" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="panel lecturer-form-panel">
        <form action="{{ route('learners.update', $learner) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="lecturer-form-section">
                <h2>Thông tin cá nhân</h2>
                <div class="lecturer-form-grid">
                    <label class="lecturer-field"><span>Mã học viên <b>*</b></span><input name="student_code" value="{{ old('student_code', $learner->student_code) }}" maxlength="40" required></label>
                    <label class="lecturer-field"><span>Họ tên <b>*</b></span><input name="full_name" value="{{ old('full_name', $learner->full_name) }}" maxlength="160" required></label>
                    <label class="lecturer-field"><span>Ngày sinh</span><input type="date" name="date_of_birth" value="{{ old('date_of_birth', $learner->date_of_birth?->format('Y-m-d')) }}"></label>
                    <label class="lecturer-field"><span>Khóa</span><input name="cohort" value="{{ old('cohort', $learner->cohort) }}" maxlength="20"></label>
                    <label class="lecturer-field"><span>Ngành / chuyên ngành</span><input name="major" value="{{ old('major', $learner->major) }}" maxlength="160"></label>
                    <label class="lecturer-field"><span>Lớp quản lý</span><input name="class_name" value="{{ old('class_name', $learner->class_name) }}" maxlength="80"></label>
                    <label class="lecturer-field"><span>Trạng thái học tập <b>*</b></span>
                        <select name="study_status" required>
                            @foreach ($studyStatusMap as $value => $label)
                                <option value="{{ $value }}" @selected(old('study_status', $learner->study_status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="lecturer-field"><span>Khoa / đơn vị</span>
                        <select name="department_id">
                            <option value="">Chưa phân công</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}" @selected(old('department_id', $learner->department_id) == $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="lecturer-field"><span>Điện thoại</span><input name="phone" value="{{ old('phone', $learner->phone) }}" maxlength="30"></label>
                    <label class="lecturer-field"><span>Email</span><input type="email" name="email" value="{{ old('email', $learner->email) }}" maxlength="120"></label>
                </div>
            </div>

            <div class="lecturer-form-actions">
                <a class="button button-secondary" href="{{ route('learners.index') }}">Hủy</a>
                <button class="button button-primary" type="submit">Lưu thay đổi</button>
            </div>
        </form>
    </section>
@endsection
