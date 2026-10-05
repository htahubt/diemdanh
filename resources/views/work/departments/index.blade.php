@extends('layouts.app')

@section('title', 'Khoa / Đơn vị')
@section('page_heading', 'Khoa / Đơn vị')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">DANH MỤC ĐÀO TẠO / CƠ CẤU TỔ CHỨC</p>
            <h1>Khoa / Đơn vị</h1>
            <p class="page-lede">Danh mục đơn vị công tác dùng chung cho hồ sơ giảng viên.</p>
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
            <div><p class="eyebrow">DỮ LIỆU THAM CHIẾU</p><h2>Danh sách khoa / đơn vị</h2></div>
            <span class="panel-count">{{ $departments->count() }} ĐƠN VỊ</span>
        </div>
        <form class="config-add-form config-department-form" action="{{ route('departments.store') }}" method="POST">
            @csrf
            <label class="lecturer-field"><span>Mã đơn vị</span><input name="code" value="{{ old('code') }}" maxlength="60" required placeholder="KHOA-CNTT"></label>
            <label class="lecturer-field"><span>Tên khoa / đơn vị</span><input name="name" value="{{ old('name') }}" maxlength="160" required placeholder="Khoa Công nghệ thông tin"></label>
            <button class="button button-primary" type="submit">Thêm khoa / đơn vị</button>
        </form>
        <div class="config-table-wrap">
            <table class="config-table">
                <thead><tr><th>Mã</th><th>Tên khoa / đơn vị</th><th>Hồ sơ đang tham chiếu</th><th>Trạng thái</th><th></th></tr></thead>
                <tbody>
                    @forelse ($departments as $department)
                        <tr>
                            <td class="lecturer-code">{{ $department->code }}</td>
                            <td><strong>{{ $department->name }}</strong></td>
                            <td>{{ $department->lecturers_count }}</td>
                            <td><span class="lecturer-status {{ $department->is_active ? 'is-active' : 'is-inactive' }}">{{ $department->is_active ? 'Đang dùng' : 'Ngừng dùng' }}</span></td>
                            <td>
                                <div class="config-row-actions">
                                    <a class="config-toggle" href="{{ route('departments.edit', $department) }}">Sửa</a>
                                @if (! $department->lecturers_count && ! $department->courses_count)
                                    <form action="{{ route('departments.toggle', $department) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button class="config-toggle" type="submit">{{ $department->is_active ? 'Ngừng dùng' : 'Kích hoạt' }}</button>
                                    </form>
                                    <form action="{{ route('departments.destroy', $department) }}" method="POST" onsubmit="return confirm('Xóa khoa / đơn vị {{ $department->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="config-toggle config-delete" type="submit">Xóa</button>
                                    </form>
                                @else
                                    <span class="config-locked">Đang được tham chiếu</span>
                                @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="lecturer-empty">Chưa có khoa / đơn vị.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
