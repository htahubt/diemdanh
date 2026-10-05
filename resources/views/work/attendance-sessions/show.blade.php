@php
    $statusLabels = [
        'unconfirmed' => 'Chưa xác định',
        'present' => 'Có mặt',
        'late' => 'Đi muộn',
        'excused' => 'Vắng có phép',
        'unexcused' => 'Vắng không phép',
        'other' => 'Khác',
    ];
@endphp

@extends('layouts.app')

@section('title', 'Điểm danh buổi ' . $attendanceSession->session_number)
@section('page_heading', 'Điểm danh')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">{{ $attendanceSession->courseSection->academic_year }} / HỌC KỲ {{ $attendanceSession->courseSection->semester }} / {{ $attendanceSession->session_date->format('d/m/Y') }}</p>
            <h1>{{ $attendanceSession->courseSection->section_code }} · Buổi {{ $attendanceSession->session_number }}</h1>
            <p class="page-lede">{{ $attendanceSession->courseSection->course->code }} · {{ $attendanceSession->courseSection->course->name }} · {{ substr($attendanceSession->start_time, 0, 5) }}–{{ substr($attendanceSession->end_time, 0, 5) }} · {{ $attendanceSession->room ?: 'Chưa có phòng' }}</p>
        </div>
        <a class="button button-secondary" href="{{ route('classes.sessions.index', $attendanceSession->courseSection) }}">Danh sách buổi</a>
    </div>

    @if (session('status'))
        <div class="lecturer-flash" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="lecturer-flash config-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="panel config-departments-panel">
        <div class="panel-heading">
            <div><p class="eyebrow">{{ $attendanceSession->status === 'confirmed' ? 'ĐÃ XÁC NHẬN' : 'ĐANG GHI NHẬN' }}</p><h2>Danh sách điểm danh</h2></div>
            @if ($attendanceSession->confirmed_at)
                <span class="panel-count">XÁC NHẬN {{ $attendanceSession->confirmed_at->format('d/m/Y H:i') }}</span>
            @endif
        </div>

        @if ($attendanceSession->status === 'open' && $canEditAttendance)
            <div class="attendance-toolbar">
                <form action="{{ route('attendance-sessions.mark-all-present', $attendanceSession) }}" method="POST" onsubmit="return confirm('Đánh dấu các trạng thái chưa xác định là có mặt?')">
                    @csrf
                    <button class="button button-secondary" type="submit">Đánh dấu chưa xác định là có mặt</button>
                </form>
                <form action="{{ route('attendance-sessions.confirm', $attendanceSession) }}" method="POST" onsubmit="return confirm('Xác nhận buổi điểm danh? Sau khi xác nhận sẽ khóa chỉnh sửa trực tiếp.')">
                    @csrf
                    <button class="button button-primary" type="submit">Xác nhận buổi học</button>
                </form>
            </div>
        @endif
        @if ($attendanceSession->status === 'confirmed' && $attendanceSession->editable_until)
            <p class="course-empty-note">Hạn giảng viên tự sửa: {{ $attendanceSession->editable_until->format('d/m/Y H:i') }}. @if ($canRequestAttendanceCorrection)Đã quá hạn; yêu cầu sửa cần được duyệt.@endif</p>
        @endif

        <form action="{{ route('attendance-sessions.records.update', $attendanceSession) }}" method="POST">
            @csrf
            @method('PATCH')
            <div class="config-table-wrap">
                <table class="config-table attendance-table">
                    <thead><tr><th>Mã</th><th>Họ tên</th><th>Lớp quản lý</th><th>Trạng thái</th><th>Lịch sử thay đổi</th></tr></thead>
                    <tbody>
                        @forelse ($attendanceSession->attendanceRecords as $record)
                            <tr>
                                <td class="lecturer-code">{{ $record->learner->student_code }}</td>
                                <td>{{ $record->learner->full_name }}</td>
                                <td>{{ $record->learner->class_name ?: '—' }}</td>
                                <td>
                                    @if ($canEditAttendance || $canRequestAttendanceCorrection)
                                        <label class="sr-only" for="record-{{ $record->learner_id }}">Trạng thái {{ $record->learner->full_name }}</label>
                                        <select id="record-{{ $record->learner_id }}" name="records[{{ $record->learner_id }}]">
                                            @foreach ($statusLabels as $value => $label)
                                                <option value="{{ $value }}" @selected($record->status === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <span class="lecturer-status {{ $record->status === 'present' ? 'is-active' : 'is-inactive' }}">{{ $statusLabels[$record->status] ?? $record->status }}</span>
                                    @endif
                                    @if ($record->recordedBy)
                                        <small class="course-description">{{ $record->recordedBy->name }} · {{ $record->marked_at?->format('H:i') }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if ($record->changes->isNotEmpty())
                                        <details class="attendance-history">
                                            <summary>{{ $record->changes->count() }} lần sửa</summary>
                                            @foreach ($record->changes as $change)
                                                <small>{{ $statusLabels[$change->before_status] ?? $change->before_status }} → {{ $statusLabels[$change->after_status] ?? $change->after_status }} · {{ $change->actor?->name ?? 'Tài khoản đã xóa' }} · {{ $change->created_at->format('d/m/Y H:i') }}@if ($change->reason) · {{ $change->reason }}@endif</small>
                                            @endforeach
                                        </details>
                                    @else
                                        <span class="course-description">Chưa có thay đổi</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="lecturer-empty">Không có người học có hiệu lực trong roster vào ngày này.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if (($canEditAttendance || $canRequestAttendanceCorrection) && $attendanceSession->attendanceRecords->isNotEmpty())
                <div class="attendance-save-row">
                    <label class="lecturer-field"><span>Lý do điều chỉnh {{ $canRequestAttendanceCorrection ? '(bắt buộc)' : '(nếu có)' }}</span><input name="reason" maxlength="1000" @required($canRequestAttendanceCorrection) placeholder="Ghi chú khi đổi trạng thái"></label>
                    <button class="button button-primary" type="submit">{{ $canRequestAttendanceCorrection ? 'Gửi đề nghị duyệt sửa' : 'Lưu điểm danh' }}</button>
                </div>
            @endif
        </form>
    </section>
@endsection
