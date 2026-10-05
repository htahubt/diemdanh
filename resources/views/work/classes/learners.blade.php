@extends('layouts.app')

@section('title', 'Danh sách người học lớp ' . $courseSection->section_code)
@section('page_heading', 'Danh sách lớp học phần')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">LỚP HỌC PHẦN / {{ $courseSection->academic_year }} · HỌC KỲ {{ $courseSection->semester }}</p>
            <h1>Danh sách người học lớp {{ $courseSection->section_code }}</h1>
            <p class="page-lede">{{ $courseSection->course->code }} · {{ $courseSection->course->name }} · {{ $courseSection->learners->count() }}/{{ $courseSection->capacity }} người học</p>
        </div>
        <a class="button button-secondary" href="{{ route('classes.index') }}">Quay lại lớp học phần</a>
    </div>

    @if (session('status'))
        <div class="lecturer-flash" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="lecturer-flash config-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="panel config-departments-panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">DANH SÁCH CHÍNH THỨC</p>
                <h2>Người học đã ghi danh</h2>
            </div>
            <span class="panel-count">{{ $courseSection->learners->count() }} / {{ $courseSection->capacity }}</span>
        </div>
        <div class="config-table-wrap">
            <table class="config-table roster-table">
                <thead><tr><th>Mã người học</th><th>Họ tên</th><th>Lớp quản lý</th><th>Thời gian ghi danh</th><th></th></tr></thead>
                <tbody>
                    @forelse ($courseSection->learners as $learner)
                        <tr>
                            <td class="lecturer-code">{{ $learner->student_code }}</td>
                            <td>{{ $learner->full_name }}</td>
                            <td>{{ $learner->class_name ?: 'Chưa cập nhật' }}</td>
                            <td>
                                {{ $learner->pivot->effective_from ? \Illuminate\Support\Carbon::parse($learner->pivot->effective_from)->format('d/m/Y') : 'Chưa xác định' }}
                                @if ($learner->pivot->is_new)<span class="learner-warning">Mới bổ sung</span>@endif
                                @if ($learner->pivot->effective_until)<small class="course-description">Đến {{ \Illuminate\Support\Carbon::parse($learner->pivot->effective_until)->format('d/m/Y') }}</small>@endif
                            </td>
                            <td>
                                @if ($canManageRoster && ! $learner->pivot->effective_until)
                                    <form action="{{ route('classes.learners.destroy', [$courseSection, $learner]) }}" method="POST" onsubmit="return confirm('Kết thúc hiệu lực ghi danh từ ngày đã chọn? Lịch sử và hồ sơ vẫn được giữ.')">
                                        @csrf
                                        @method('DELETE')
                                        <input class="roster-end-date" type="date" name="effective_until" value="{{ now()->toDateString() }}" required aria-label="Ngày kết thúc ghi danh {{ $learner->full_name }}">
                                        <input type="hidden" name="reason" value="Điều chỉnh roster bởi Phòng đào tạo">
                                        <button class="button button-secondary roster-remove" type="submit">Kết thúc ghi danh</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="lecturer-empty">Lớp chưa có người học ghi danh.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel config-departments-panel">
        <div class="panel-heading"><div><p class="eyebrow">NHẬT KÝ ĐIỀU CHỈNH</p><h2>Lịch sử roster</h2></div></div>
        <div class="config-table-wrap">
            <table class="config-table roster-table">
                <thead><tr><th>Thời điểm</th><th>Người học</th><th>Thay đổi</th><th>Hiệu lực</th><th>Người thực hiện / duyệt</th><th>Lý do</th></tr></thead>
                <tbody>
                    @forelse ($courseSection->rosterChangeLogs as $change)
                        <tr>
                            <td>{{ $change->created_at->format('d/m/Y H:i') }}</td>
                            <td class="lecturer-code">{{ $change->learner_code }}</td>
                            <td>{{ ['enrolled' => 'Ghi danh', 're_enrolled' => 'Ghi danh lại', 'withdrawn' => 'Kết thúc ghi danh'][$change->action] ?? $change->action }}</td>
                            <td>{{ $change->effective_from?->format('d/m/Y') ?? '—' }}@if ($change->effective_until) đến {{ $change->effective_until->format('d/m/Y') }}@endif</td>
                            <td>{{ $change->actor?->name ?? 'Tài khoản đã xóa' }}</td>
                            <td>{{ $change->reason ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="lecturer-empty">Chưa có thay đổi roster.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($courseSection->is_active && $canManageRoster)
        <section class="panel config-departments-panel">
            <div class="panel-heading">
                <div>
                    <p class="eyebrow">GHI DANH</p>
                    <h2>Thêm người học đang học</h2>
                </div>
            </div>
            <form class="course-filters roster-search" action="{{ route('classes.learners.index', $courseSection) }}" method="GET">
                <label class="lecturer-search"><span class="sr-only">Tìm người học</span><input name="search" value="{{ request('search') }}" placeholder="Tìm theo mã hoặc họ tên..."></label>
                <button class="button button-secondary" type="submit">Tìm</button>
                @if (request('search'))<a class="config-toggle" href="{{ route('classes.learners.index', $courseSection) }}">Xóa tìm kiếm</a>@endif
            </form>
            <form action="{{ route('classes.learners.store', $courseSection) }}" method="POST">
                @csrf
                <label class="roster-effective-date lecturer-field"><span>Ngày có hiệu lực</span><input type="date" name="effective_from" value="{{ old('effective_from', now()->toDateString()) }}" required></label>
                <div class="config-table-wrap">
                    <table class="config-table roster-table">
                        <thead><tr><th></th><th>Mã người học</th><th>Họ tên</th><th>Lớp quản lý</th></tr></thead>
                        <tbody>
                            @forelse ($availableLearners as $learner)
                                <tr>
                                    <td><input class="roster-checkbox" type="checkbox" name="learner_ids[]" value="{{ $learner->id }}" aria-label="Chọn {{ $learner->full_name }}"></td>
                                    <td class="lecturer-code">{{ $learner->student_code }}</td>
                                    <td>{{ $learner->full_name }}</td>
                                    <td>{{ $learner->class_name ?: 'Chưa cập nhật' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="lecturer-empty">Không có người học đang học phù hợp để ghi danh.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="roster-footer">
                    <div class="course-pagination">{{ $availableLearners->links() }}</div>
                    <button class="button button-primary" type="submit" @disabled($availableLearners->isEmpty())>Ghi danh đã chọn</button>
                </div>
            </form>
        </section>
    @endif
@endsection
