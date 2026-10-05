@extends('layouts.app')

@section('title', 'Hồ sơ giảng viên')
@section('page_heading', 'Hồ sơ giảng viên')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">DANH MỤC ĐÀO TẠO / NHÂN SỰ</p>
            <h1>Hồ sơ giảng viên</h1>
            <p class="page-lede">Quản lý thông tin giảng dạy và đơn vị công tác.</p>
        </div>
        <div class="lecturer-page-actions">
            @if ($canManageAll)
                <a class="button button-secondary" href="{{ route('lecturers.export') }}">Xuất hồ sơ đã duyệt</a>
            @endif
            @if ($canCreateProfile)
                <a class="button button-primary" href="{{ route('lecturers.create') }}"><span aria-hidden="true">+</span> Thêm giảng viên</a>
            @endif
        </div>
    </div>

    @if (session('status'))
        <div class="lecturer-flash" role="status">{{ session('status') }}</div>
    @endif

    <section class="lecturer-stats" aria-label="Thống kê giảng viên">
        <div><span>Tổng hồ sơ</span><strong>{{ $totalCount }}</strong></div>
        <div><span>Chờ kiểm tra</span><strong>{{ $pendingCount }}</strong></div>
        <div><span>Đã xác nhận</span><strong>{{ $approvedCount }}</strong></div>
    </section>

    <section class="panel lecturer-panel" aria-label="Danh sách giảng viên">
        <form class="lecturer-filters" action="{{ route('lecturers.index') }}" method="GET">
            <label class="lecturer-search">
                <span class="visually-hidden">Tìm giảng viên</span>
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Tìm theo mã, họ tên, khoa hoặc email">
            </label>
            <label class="lecturer-status-filter">
                <span class="visually-hidden">Lọc tình trạng công tác</span>
                <select name="status">
                    <option value="">Tất cả tình trạng công tác</option>
                    <option value="active" @selected(request('status') === 'active')>Đang công tác</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Ngừng công tác</option>
                </select>
            </label>
            <label class="lecturer-review-filter">
                <span class="visually-hidden">Lọc trạng thái hồ sơ</span>
                <select name="profile_status">
                    <option value="">Tất cả hồ sơ</option>
                    <option value="submitted" @selected(request('profile_status') === 'submitted')>Chờ kiểm tra</option>
                    <option value="needs_revision" @selected(request('profile_status') === 'needs_revision')>Cần bổ sung</option>
                    <option value="approved" @selected(request('profile_status') === 'approved')>Đã xác nhận</option>
                </select>
            </label>
            <button class="button button-secondary" type="submit">Lọc danh sách</button>
            @if (request()->filled('search') || request()->filled('status') || request()->filled('profile_status'))
                <a class="filter-reset" href="{{ route('lecturers.index') }}">Xóa lọc</a>
            @endif
        </form>

        <div class="lecturer-table-wrap">
            <table class="lecturer-table">
                <thead>
                    <tr>
                        <th scope="col">Mã giảng viên</th>
                        <th scope="col">Họ tên</th>
                        <th scope="col">Khoa / đơn vị</th>
                        <th scope="col">Liên hệ</th>
                        <th scope="col">Duyệt hồ sơ</th>
                        <th scope="col">Công tác</th>
                        <th scope="col"><span class="visually-hidden">Thao tác</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lecturers as $lecturer)
                        <tr>
                            <td><span class="lecturer-code">{{ $lecturer->lecturer_code }}</span></td>
                            <td>
                                <strong class="lecturer-name">{{ $lecturer->name }}</strong>
                                <span class="lecturer-title">{{ $lecturer->academicDegree?->name ?? $lecturer->academic_title ?? 'Chưa cập nhật học vị' }}</span>
                            </td>
                            <td>{{ $lecturer->departmentUnit?->name ?? $lecturer->department ?? '—' }}</td>
                            <td class="lecturer-contact">
                                @if ($lecturer->email)<a href="mailto:{{ $lecturer->email }}">{{ $lecturer->email }}</a>@endif
                                @if ($lecturer->phone)<a href="tel:{{ $lecturer->phone }}">{{ $lecturer->phone }}</a>@endif
                                @if (! $lecturer->email && ! $lecturer->phone)—@endif
                            </td>
                            <td><span class="lecturer-status profile-{{ $lecturer->profile_status }}">{{ match ($lecturer->profile_status) { 'approved' => 'Đã xác nhận', 'needs_revision' => 'Cần bổ sung', 'submitted' => 'Chờ kiểm tra', default => 'Bản nháp' } }}</span></td>
                            <td><span class="lecturer-status {{ $lecturer->status === 'active' ? 'is-active' : 'is-inactive' }}">{{ $lecturer->status === 'active' ? 'Đang công tác' : 'Ngừng công tác' }}</span></td>
                            <td>
                                <div class="lecturer-actions">
                                    <a href="{{ route('lecturers.show', $lecturer) }}">Xem hồ sơ</a>
                                    @if (auth()->user()->role === 'admin')
                                        <form action="{{ route('lecturers.destroy', $lecturer) }}" method="POST" onsubmit="return confirm('Xóa hồ sơ {{ $lecturer->name }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit">Xóa</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="lecturer-empty" colspan="7">
                                <strong>{{ request()->filled('search') || request()->filled('status') || request()->filled('profile_status') ? 'Không tìm thấy hồ sơ phù hợp' : 'Chưa có hồ sơ giảng viên' }}</strong>
                                <span>{{ request()->filled('search') || request()->filled('status') || request()->filled('profile_status') ? 'Thử thay đổi từ khóa hoặc bộ lọc.' : 'Thêm hồ sơ đầu tiên để bắt đầu quản lý.' }}</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($lecturers->hasPages())
            <div class="lecturer-pagination">{{ $lecturers->links() }}</div>
        @endif
    </section>
@endsection