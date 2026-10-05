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

@section('title', 'Người học')
@section('page_heading', 'Người học')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">DANH MỤC ĐÀO TẠO / NGƯỜI HỌC</p>
            <h1>Người học</h1>
            <p class="page-lede">Quản lý hồ sơ, trạng thái học tập và danh sách lớp học phần đang theo học.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="lecturer-flash" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="lecturer-flash" role="alert">{{ $errors->first() }}</div>
    @endif
    @if (session('import_errors'))
        <div class="learner-import-errors" role="alert">
            @foreach (session('import_errors') as $importError)
                <p>{{ $importError }}</p>
            @endforeach
        </div>
    @endif

    <section class="panel learner-import-panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">NHẬP DỮ LIỆU</p>
                <h2>Nhập danh sách từ Excel</h2>
            </div>
            <a class="button button-secondary" href="{{ route('learners.template') }}" aria-label="Tải mẫu CSV cho Excel">Tải mẫu CSV</a>
        </div>
        <form class="learner-import-form" action="{{ route('learners.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <label class="lecturer-field"><span>Tệp CSV UTF-8</span><input type="file" name="file" accept=".csv,.txt,text/csv" required><small class="course-description">Ngày sinh: YYYY-MM-DD. Trạng thái: active, paused, inactive, graduated hoặc dropped.</small></label>
            <button class="button button-primary" type="submit">Nhập hồ sơ</button>
        </form>
    </section>

    <section class="panel config-departments-panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">HỒ SƠ NGƯỜI HỌC</p>
                <h2>Quản lý hồ sơ người học</h2>
            </div>
            <span class="panel-count">{{ $activeCount }} ĐANG HỌC · {{ $inactiveCount }} KHÁC · {{ $warningCount }} CẢNH BÁO TRÊN TRANG</span>
        </div>

        <form class="config-add-form course-add-form" action="{{ route('learners.store') }}" method="POST">
            @csrf
            <label class="lecturer-field"><span>Mã học viên</span><input name="student_code" value="{{ old('student_code') }}" maxlength="40" required placeholder="SV-2025-001"></label>
            <label class="lecturer-field"><span>Họ tên</span><input name="full_name" value="{{ old('full_name') }}" maxlength="160" required placeholder="Nguyễn Văn A"></label>
            <label class="lecturer-field"><span>Ngày sinh</span><input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}"></label>
            <label class="lecturer-field"><span>Khóa</span><input name="cohort" value="{{ old('cohort') }}" maxlength="20" placeholder="2025"></label>
            <label class="lecturer-field"><span>Ngành / chuyên ngành</span><input name="major" value="{{ old('major') }}" maxlength="160" placeholder="Kỹ thuật phần mềm"></label>
            <label class="lecturer-field"><span>Lớp quản lý</span><input name="class_name" value="{{ old('class_name') }}" maxlength="80" placeholder="KTPM01"></label>
            <label class="lecturer-field"><span>Trạng thái</span>
                <select name="study_status">
                    <option value="active" @selected(old('study_status', 'active') === 'active')>Đang học</option>
                    <option value="paused" @selected(old('study_status') === 'paused')>Tạm dừng</option>
                    <option value="inactive" @selected(old('study_status') === 'inactive')>Không hoạt động</option>
                    <option value="graduated" @selected(old('study_status') === 'graduated')>Tốt nghiệp</option>
                    <option value="dropped" @selected(old('study_status') === 'dropped')>Bỏ học</option>
                </select>
            </label>
            <label class="lecturer-field"><span>Điện thoại</span><input name="phone" value="{{ old('phone') }}" maxlength="30" placeholder="0901234567"></label>
            <label class="lecturer-field"><span>Email</span><input type="email" name="email" value="{{ old('email') }}" maxlength="120" placeholder="example@domain.com"></label>
            <label class="lecturer-field"><span>Khoa / đơn vị quản lý</span>
                <select name="department_id">
                    <option value="">Chưa phân công</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </label>
            <button class="button button-primary" type="submit">Thêm người học</button>
        </form>

        <h2 class="learner-list-heading">Danh sách người học</h2>
        <form class="course-filters" action="{{ route('learners.index') }}" method="GET">
            <label class="lecturer-search"><span class="sr-only">Tìm người học</span><input name="search" value="{{ request('search') }}" placeholder="Tìm theo mã, tên, ngành, lớp..."></label>
            <label class="lecturer-status-filter"><span class="sr-only">Trạng thái</span>
                <select name="status">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active" @selected(request('status') === 'active')>Đang học</option>
                    <option value="paused" @selected(request('status') === 'paused')>Tạm dừng</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Không hoạt động</option>
                    <option value="graduated" @selected(request('status') === 'graduated')>Tốt nghiệp</option>
                    <option value="dropped" @selected(request('status') === 'dropped')>Bỏ học</option>
                </select>
            </label>
            <button class="button button-secondary" type="submit">Lọc</button>
            @if (request()->hasAny(['search', 'status']))
                <a class="config-toggle" href="{{ route('learners.index') }}">Xóa lọc</a>
            @endif
        </form>

        <div class="config-table-wrap">
            <table class="config-table course-table learner-table">
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Họ tên</th>
                        <th>Khóa / Ngành</th>
                        <th>Lớp / Liên hệ</th>
                        <th>Trạng thái</th>
                        <th>Dữ liệu</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($learners as $learner)
                        <tr>
                            <td class="lecturer-code">{{ $learner->student_code }}</td>
                            <td>
                                <strong>{{ $learner->full_name }}</strong>
                                @if ($learner->date_of_birth)
                                    <small class="course-description">Sinh: {{ $learner->date_of_birth->format('d/m/Y') }}</small>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $learner->cohort ?: '—' }}</strong>
                                <small class="course-description">{{ $learner->major ?: 'Chưa cập nhật' }}</small>
                            </td>
                            <td>
                                <strong>{{ $learner->class_name ?: '—' }}</strong>
                                <small class="course-description">{{ $learner->department?->name ?: 'Chưa phân công khoa' }}</small>
                                <small class="course-description">HP: {{ $learner->courseSections->pluck('section_code')->implode(', ') ?: 'Chưa ghi danh' }}</small>
                            </td>
                            <td>
                                <span class="lecturer-status {{ $learner->study_status === 'active' ? 'is-active' : 'is-inactive' }}">
                                    {{ $studyStatusMap[$learner->study_status] ?? $learner->study_status }}
                                </span>
                            </td>
                            <td>
                                @if ($learner->dataWarnings())
                                    <span class="learner-warning" title="{{ implode('; ', $learner->dataWarnings()) }}">{{ count($learner->dataWarnings()) }} mục cần rà soát</span>
                                    <small class="course-description">{{ implode(' · ', array_slice($learner->dataWarnings(), 0, 2)) }}</small>
                                @else
                                    <span class="learner-data-ok">Đầy đủ</span>
                                @endif
                            </td>
                            <td>
                                <div class="config-row-actions course-actions">
                                    <a class="icon-action" href="{{ route('learners.edit', $learner) }}" aria-label="Sửa người học {{ $learner->student_code }}" title="Sửa hồ sơ">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
                                    </a>
                                    <form action="{{ route('learners.destroy', $learner) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa hồ sơ này?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="icon-action is-danger" type="submit" aria-label="Xóa người học {{ $learner->student_code }}" title="Xóa hồ sơ">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="lecturer-empty">Chưa có hồ sơ người học phù hợp.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="course-pagination">{{ $learners->links() }}</div>
    </section>
@endsection
