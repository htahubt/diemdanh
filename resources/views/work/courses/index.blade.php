@extends('layouts.app')

@section('title', 'Học phần')
@section('page_heading', 'Học phần')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">DANH MỤC ĐÀO TẠO / CHƯƠNG TRÌNH</p>
            <h1>Học phần</h1>
            <p class="page-lede">Quản lý môn học trong chương trình đào tạo. Lớp học phần sẽ được mở từ danh mục này theo từng học kỳ.</p>
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
            <div><p class="eyebrow">DANH MỤC MÔN HỌC</p><h2>Thêm học phần</h2></div>
            <span class="panel-count">{{ $activeCount }} ĐANG DẠY · {{ $inactiveCount }} NGỪNG</span>
        </div>
        <form class="config-add-form course-add-form" action="{{ route('courses.store') }}" method="POST">
            @csrf
            <label class="lecturer-field"><span>Mã học phần</span><input name="code" value="{{ old('code') }}" maxlength="30" required placeholder="IT101"></label>
            <label class="lecturer-field"><span>Tên học phần</span><input name="name" value="{{ old('name') }}" maxlength="160" required placeholder="Nhập môn Công nghệ thông tin"></label>
            <label class="lecturer-field"><span>Số tín chỉ</span><input type="number" name="credits" value="{{ old('credits', 3) }}" min="1" max="30" required></label>
            <label class="lecturer-field"><span>Khoa phụ trách</span>
                <select name="department_id">
                    <option value="">Chưa phân công</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="lecturer-field course-description-field"><span>Mô tả (không bắt buộc)</span><input name="description" value="{{ old('description') }}" maxlength="2000" placeholder="Nội dung khái quát của học phần"></label>
            <button class="button button-primary" type="submit">Thêm học phần</button>
        </form>

        <form class="course-filters" action="{{ route('courses.index') }}" method="GET">
            <label class="lecturer-search"><span class="sr-only">Tìm học phần</span><input name="search" value="{{ request('search') }}" placeholder="Tìm theo mã, tên hoặc khoa..."></label>
            <label class="lecturer-status-filter"><span class="sr-only">Trạng thái</span><select name="status"><option value="">Tất cả trạng thái</option><option value="active" @selected(request('status') === 'active')>Đang dùng</option><option value="inactive" @selected(request('status') === 'inactive')>Ngừng dùng</option></select></label>
            <button class="button button-secondary" type="submit">Lọc</button>
            @if (request()->hasAny(['search', 'status']))<a class="config-toggle" href="{{ route('courses.index') }}">Xóa lọc</a>@endif
        </form>

        <div class="config-table-wrap">
            <table class="config-table course-table">
                <thead><tr><th>Mã</th><th>Tên học phần</th><th>Tín chỉ</th><th>Khoa phụ trách</th><th>Trạng thái</th><th></th></tr></thead>
                <tbody>
                    @forelse ($courses as $course)
                        <tr>
                            <td class="lecturer-code">{{ $course->code }}</td>
                            <td><strong>{{ $course->name }}</strong>@if ($course->description)<small class="course-description">{{ $course->description }}</small>@endif</td>
                            <td>{{ $course->credits }}</td>
                            <td>{{ $course->department?->name ?? 'Chưa phân công' }}</td>
                            <td><span class="lecturer-status {{ $course->is_active ? 'is-active' : 'is-inactive' }}">{{ $course->is_active ? 'Đang dùng' : 'Ngừng dùng' }}</span></td>
                            <td><div class="config-row-actions course-actions">
                            <a class="icon-action" href="{{ route('courses.edit', $course) }}" aria-label="Sửa học phần {{ $course->code }}" title="Sửa học phần">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
                            </a>
                            <form action="{{ route('courses.toggle', $course) }}" method="POST">@csrf @method('PATCH')
                            <button class="icon-action {{ $course->is_active ? 'is-on' : '' }}" type="submit" aria-label="{{ $course->is_active ? 'Ngừng kích hoạt' : 'Kích hoạt' }} {{ $course->code }}" title="{{ $course->is_active ? 'Ngừng kích hoạt' : 'Kích hoạt' }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="7"/><circle cx="{{ $course->is_active ? 15 : 9 }}" cy="12" r="3"/></svg>
                            </button>
                            </form>
                            <form action="{{ route('courses.destroy', $course) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa học phần này?')">@csrf @method('DELETE')
                            <button class="icon-action is-danger" type="submit" aria-label="Xóa học phần {{ $course->code }}" title="Xóa học phần">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>
                            </button>
                            </form>
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="lecturer-empty">Chưa có học phần phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="course-pagination">{{ $courses->links() }}</div>
    </section>
@endsection
