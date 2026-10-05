@extends('layouts.app')

@section('title', 'Sửa khoa / đơn vị')
@section('page_heading', 'Sửa khoa / đơn vị')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">DANH MỤC ĐÀO TẠO / CƠ CẤU TỔ CHỨC</p>
            <h1>{{ $department->name }}</h1>
            <p class="page-lede">Đang được {{ $department->lecturers_count ?? $department->lecturers()->count() }} hồ sơ giảng viên tham chiếu.</p>
        </div>
        <a class="button button-secondary" href="{{ route('departments.index') }}">← Khoa / Đơn vị</a>
    </div>

    @if ($errors->any())
        <div class="lecturer-flash config-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="panel lecturer-form-panel department-edit-panel">
        <form action="{{ route('departments.update', $department) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="lecturer-form-grid">
                <label class="lecturer-field">
                    <span>Mã đơn vị</span>
                    <input name="code" value="{{ old('code', $department->code) }}" maxlength="60" required>
                    @error('code')<small class="field-error">{{ $message }}</small>@enderror
                </label>
                <label class="lecturer-field">
                    <span>Tên khoa / đơn vị</span>
                    <input name="name" value="{{ old('name', $department->name) }}" maxlength="160" required>
                    @error('name')<small class="field-error">{{ $message }}</small>@enderror
                </label>
            </div>
            <div class="lecturer-form-actions">
                <a class="button button-secondary" href="{{ route('departments.index') }}">Hủy</a>
                <button class="button button-primary" type="submit">Lưu thay đổi</button>
            </div>
        </form>
    </section>
@endsection