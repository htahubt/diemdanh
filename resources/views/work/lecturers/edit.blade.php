@extends('layouts.app')

@section('title', 'Sửa hồ sơ giảng viên')
@section('page_heading', 'Sửa hồ sơ giảng viên')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">HỒ SƠ GIẢNG VIÊN / CHỈNH SỬA</p>
            <h1>{{ $lecturer->name }}</h1>
            <p class="page-lede">Cập nhật mã {{ $lecturer->lecturer_code }} và thông tin công tác.</p>
        </div>
        <a class="button button-secondary" href="{{ route('lecturers.show', $lecturer) }}">← Xem hồ sơ</a>
    </div>

    <section class="panel lecturer-form-panel">
        <form action="{{ route('lecturers.update', $lecturer) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('work.lecturers._form', ['submitLabel' => 'Gửi lại để kiểm tra'])
        </form>
    </section>

    @if (session('status'))
        <div class="lecturer-flash" role="status">{{ session('status') }}</div>
    @endif

    @if (auth()->user()->canViewSensitiveProfiles())
    <section class="panel lecturer-review-panel">
        <div class="lecturer-section-heading">
            <div>
                <p class="eyebrow">ĐƠN VỊ ĐÀO TẠO</p>
                <h2>Kiểm tra hồ sơ</h2>
            </div>
            <span class="lecturer-status profile-{{ $lecturer->profile_status }}">{{ match ($lecturer->profile_status) { 'approved' => 'Đã xác nhận', 'needs_revision' => 'Cần bổ sung', 'submitted' => 'Chờ kiểm tra', default => 'Bản nháp' } }}</span>
        </div>
        @if ($lecturer->review_note)
            <p class="lecturer-review-note"><strong>Ghi chú:</strong> {{ $lecturer->review_note }}</p>
        @endif
        @error('identity_verified')<p class="field-error">{{ $message }}</p>@enderror
        @error('bank_verified')<p class="field-error">{{ $message }}</p>@enderror
        @error('review_note')<p class="field-error">{{ $message }}</p>@enderror
        @if ($lecturer->profile_status === 'submitted')
            <div class="lecturer-review-actions">
                <form action="{{ route('lecturers.review', $lecturer) }}" method="POST" class="lecturer-approve-form">
                    @csrf
                    <input type="hidden" name="action" value="approve">
                    <label><input type="checkbox" name="identity_verified" value="1" required> Đã đối chiếu CCCD</label>
                    <label><input type="checkbox" name="bank_verified" value="1" required> Đã đối chiếu tài khoản ngân hàng</label>
                    <label class="lecturer-review-comment">Ghi chú kiểm tra <textarea name="review_note" rows="2" maxlength="2000"></textarea></label>
                    <button class="button button-primary" type="submit">Xác nhận hồ sơ</button>
                </form>
                <form action="{{ route('lecturers.review', $lecturer) }}" method="POST" class="lecturer-revision-form">
                    @csrf
                    <input type="hidden" name="action" value="needs_revision">
                    <label class="lecturer-review-comment">Yêu cầu bổ sung / chỉnh sửa <textarea name="review_note" rows="3" maxlength="2000" required></textarea></label>
                    <button class="button button-secondary" type="submit">Trả hồ sơ để bổ sung</button>
                </form>
            </div>
        @elseif ($lecturer->profile_status === 'approved')
            <p class="lecturer-verified-summary">
                Đã đối chiếu CCCD {{ $lecturer->identity_verified_at?->format('d/m/Y') }}
                · Tài khoản ngân hàng {{ $lecturer->bank_verified_at?->format('d/m/Y') }}
            </p>
        @endif
    </section>
    @endif

    <section class="panel lecturer-history-panel">
        <div class="lecturer-section-heading">
            <div>
                <p class="eyebrow">THEO DÕI THAY ĐỔI</p>
                <h2>Lịch sử hồ sơ</h2>
            </div>
        </div>
        @if ($lecturer->history->isEmpty())
            <p class="lecturer-history-empty">Chưa có lịch sử thay đổi.</p>
        @else
            @php
                $fieldLabels = [
                    'name' => 'Họ tên', 'date_of_birth' => 'Ngày sinh', 'gender' => 'Giới tính',
                    'academic_rank' => 'Học hàm', 'academic_title' => 'Học vị', 'specialization' => 'Chuyên ngành',
                    'department' => 'Đơn vị', 'position' => 'Chức vụ', 'email' => 'Email', 'phone' => 'Điện thoại',
                    'national_id' => 'CCCD', 'id_issued_at' => 'Ngày cấp CCCD', 'id_issued_place' => 'Nơi cấp CCCD',
                    'permanent_address' => 'Địa chỉ thường trú', 'contact_address' => 'Địa chỉ liên hệ',
                    'bank_account_number' => 'Tài khoản ngân hàng', 'bank_name' => 'Ngân hàng',
                    'bank_branch' => 'Chi nhánh', 'bank_account_name' => 'Chủ tài khoản', 'status' => 'Tình trạng công tác',
                    'documents_added' => 'Hồ sơ đính kèm',
                ];
            @endphp
            <ol class="lecturer-history-list">
                @foreach ($lecturer->history as $entry)
                    <li>
                        <div class="lecturer-history-meta">
                            <strong>{{ match ($entry->action) { 'approved' => 'Đã xác nhận hồ sơ', 'revision_requested' => 'Yêu cầu bổ sung', 'submitted' => 'Gửi hồ sơ kiểm tra', default => 'Cập nhật hồ sơ' } }}</strong>
                            <time datetime="{{ $entry->created_at->toIso8601String() }}">{{ $entry->created_at->format('d/m/Y H:i') }}</time>
                        </div>
                        @if ($entry->note)<p>{{ $entry->note }}</p>@endif
                        @if ($entry->changes)
                            <div class="lecturer-change-tags">
                                @foreach (array_keys($entry->changes) as $field)
                                    <span>{{ $fieldLabels[$field] ?? 'Trạng thái hồ sơ' }}</span>
                                @endforeach
                            </div>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif
    </section>
@endsection