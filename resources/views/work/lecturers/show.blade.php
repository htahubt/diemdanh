@extends('layouts.app')

@section('title', 'Hồ sơ giảng viên')
@section('page_heading', 'Hồ sơ giảng viên')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">HỒ SƠ GIẢNG VIÊN / CHẾ ĐỘ XEM</p>
            <h1>{{ $lecturer->name }}</h1>
            <p class="page-lede">{{ $lecturer->lecturer_code }} · {{ $lecturer->departmentUnit?->name ?? $lecturer->department }}</p>
        </div>
        <div class="lecturer-page-actions">
            <a class="button button-secondary" href="{{ route('lecturers.index') }}">← Danh sách</a>
            <a class="button button-primary" href="{{ route('lecturers.edit', $lecturer) }}">Chỉnh sửa hồ sơ</a>
        </div>
    </div>

    @if (session('status'))
        <div class="lecturer-flash" role="status">{{ session('status') }}</div>
    @endif

    <div class="lecturer-read-grid">
        <section class="panel lecturer-read-panel">
            <div class="panel-heading"><div><p class="eyebrow">THÔNG TIN CÁ NHÂN</p><h2>Liên hệ</h2></div></div>
            <dl class="lecturer-detail-list">
                <div><dt>Họ tên</dt><dd>{{ $lecturer->name }}</dd></div>
                <div><dt>Mã giảng viên</dt><dd>{{ $lecturer->lecturer_code }}</dd></div>
                <div><dt>Ngày sinh</dt><dd>{{ $lecturer->date_of_birth?->format('d/m/Y') ?? '—' }}</dd></div>
                <div><dt>Giới tính</dt><dd>{{ match ($lecturer->gender) { 'female' => 'Nữ', 'male' => 'Nam', 'other' => 'Khác', default => '—' } }}</dd></div>
                <div><dt>Số điện thoại</dt><dd>{{ $lecturer->phone ?: '—' }}</dd></div>
                <div><dt>Email</dt><dd>{{ $lecturer->email ?: '—' }}</dd></div>
            </dl>
        </section>

        <section class="panel lecturer-read-panel">
            <div class="panel-heading"><div><p class="eyebrow">DỮ LIỆU THAM CHIẾU</p><h2>Chuyên môn</h2></div></div>
            <dl class="lecturer-detail-list">
                <div><dt>Học hàm</dt><dd>{{ $lecturer->academicRank?->name ?? $lecturer->academic_rank ?? '—' }}</dd></div>
                <div><dt>Học vị</dt><dd>{{ $lecturer->academicDegree?->name ?? $lecturer->academic_title ?? '—' }}</dd></div>
                <div><dt>Chuyên ngành</dt><dd>{{ $lecturer->specialization ?: '—' }}</dd></div>
                <div><dt>Khoa / đơn vị</dt><dd>{{ $lecturer->departmentUnit?->name ?? $lecturer->department ?? '—' }}</dd></div>
                <div><dt>Chức vụ</dt><dd>{{ $lecturer->position ?: '—' }}</dd></div>
            </dl>
        </section>

        <section class="panel lecturer-read-panel">
            <div class="panel-heading"><div><p class="eyebrow">THÔNG TIN ĐỊNH DANH</p><h2>CCCD và địa chỉ</h2></div></div>
            <dl class="lecturer-detail-list">
                <div><dt>Số CCCD</dt><dd>{{ $canViewSensitive ? $lecturer->national_id : 'Đã ẩn' }}</dd></div>
                <div><dt>Ngày cấp</dt><dd>{{ $lecturer->id_issued_at?->format('d/m/Y') ?? '—' }}</dd></div>
                <div><dt>Nơi cấp</dt><dd>{{ $lecturer->id_issued_place ?: '—' }}</dd></div>
                <div><dt>Thường trú</dt><dd>{{ $lecturer->permanent_address ?: '—' }}</dd></div>
                <div><dt>Địa chỉ liên hệ</dt><dd>{{ $lecturer->contact_address ?: '—' }}</dd></div>
            </dl>
        </section>

        <section class="panel lecturer-read-panel">
            <div class="panel-heading"><div><p class="eyebrow">THANH TOÁN</p><h2>Tài khoản ngân hàng</h2></div></div>
            <dl class="lecturer-detail-list">
                <div><dt>Số tài khoản</dt><dd>{{ $canViewSensitive ? $lecturer->bank_account_number : 'Đã ẩn' }}</dd></div>
                <div><dt>Ngân hàng</dt><dd>{{ $lecturer->bank_name ?: '—' }}</dd></div>
                <div><dt>Chi nhánh</dt><dd>{{ $lecturer->bank_branch ?: '—' }}</dd></div>
                <div><dt>Chủ tài khoản</dt><dd>{{ $lecturer->bank_account_name ?: '—' }}</dd></div>
            </dl>
        </section>

        <section class="panel lecturer-read-panel">
            <div class="panel-heading"><div><p class="eyebrow">TRẠNG THÁI</p><h2>Hồ sơ và công tác</h2></div></div>
            <dl class="lecturer-detail-list">
                <div><dt>Duyệt hồ sơ</dt><dd>{{ match ($lecturer->profile_status) { 'approved' => 'Đã xác nhận', 'needs_revision' => 'Cần bổ sung', 'submitted' => 'Chờ kiểm tra', default => 'Bản nháp' } }}</dd></div>
                <div><dt>Tình trạng công tác</dt><dd>{{ $lecturer->status === 'active' ? 'Đang công tác' : 'Ngừng công tác' }}</dd></div>
                <div><dt>Ghi chú đơn vị</dt><dd>{{ $lecturer->review_note ?: '—' }}</dd></div>
            </dl>
        </section>

        @if ($canViewSensitive)
            <section class="panel lecturer-read-panel">
                <div class="panel-heading"><div><p class="eyebrow">TÀI LIỆU RIÊNG TƯ</p><h2>Hồ sơ kèm theo</h2></div></div>
                @forelse ($lecturer->documents as $document)
                    <a class="lecturer-private-document" href="{{ route('lecturers.documents.download', [$lecturer, $document]) }}">{{ $document->original_name }}</a>
                @empty
                    <p class="lecturer-history-empty">Chưa có tệp đính kèm.</p>
                @endforelse
            </section>
        @endif
    </div>

    <section class="panel lecturer-history-panel">
        <div class="lecturer-section-heading"><div><p class="eyebrow">THEO DÕI THAY ĐỔI</p><h2>Lịch sử hồ sơ</h2></div></div>
        @if ($lecturer->history->isEmpty())
            <p class="lecturer-history-empty">Chưa có lịch sử thay đổi.</p>
        @else
            <ol class="lecturer-history-list">
                @foreach ($lecturer->history as $entry)
                    <li>
                        <div class="lecturer-history-meta">
                            <strong>{{ match ($entry->action) { 'approved' => 'Đã xác nhận hồ sơ', 'revision_requested' => 'Yêu cầu bổ sung', 'submitted' => 'Gửi hồ sơ kiểm tra', default => 'Cập nhật hồ sơ' } }}</strong>
                            <time datetime="{{ $entry->created_at->toIso8601String() }}">{{ $entry->created_at->format('d/m/Y H:i') }}</time>
                        </div>
                        @if ($entry->note)<p>{{ $entry->note }}</p>@endif
                    </li>
                @endforeach
            </ol>
        @endif
    </section>
@endsection