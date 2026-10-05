@extends('layouts.app')

@section('title', 'Thêm giảng viên')
@section('page_heading', 'Thêm giảng viên')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">HỒ SƠ GIẢNG VIÊN / TẠO MỚI</p>
            <h1>Thêm giảng viên</h1>
            <p class="page-lede">Nhập thông tin nhân sự giảng dạy.</p>
        </div>
        <a class="button button-secondary" href="{{ route('lecturers.index') }}">← Danh sách</a>
    </div>

    <section class="panel lecturer-form-panel">
        <form action="{{ route('lecturers.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @include('work.lecturers._form', ['submitLabel' => 'Gửi hồ sơ xác nhận'])
        </form>
    </section>
@endsection