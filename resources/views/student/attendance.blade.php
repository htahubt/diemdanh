@php
    $statusLabels = [
        'present' => 'Có mặt',
        'late' => 'Đi muộn',
        'excused' => 'Vắng có phép',
        'unexcused' => 'Vắng không phép',
        'other' => 'Khác',
        'unconfirmed' => 'Chưa xác định',
    ];
    $requestStatusLabels = ['pending' => 'Chờ xử lý', 'approved' => 'Đã duyệt', 'rejected' => 'Từ chối'];
@endphp

@extends('layouts.app')

@section('title', 'Chuyên cần của tôi')
@section('page_heading', 'Chuyên cần của tôi')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">CỔNG NGƯỜI HỌC / DỮ LIỆU CÁ NHÂN</p>
            <h1>Chuyên cần của tôi</h1>
            <p class="page-lede">{{ $learner->full_name }} · {{ $learner->student_code }}</p>
        </div>
    </div>

    @if (session('status'))
        <div class="lecturer-flash" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="lecturer-flash config-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="panel config-departments-panel">
        <div class="panel-heading"><div><p class="eyebrow">THEO TỪNG BUỔI HỌC</p><h2>Kết quả chuyên cần</h2></div><span class="panel-count">{{ $attendanceRecords->count() }} BUỔI</span></div>
        <div class="config-table-wrap">
            <table class="config-table student-attendance-table">
                <thead><tr><th>Ngày / Buổi</th><th>Lớp học phần</th><th>Giảng viên</th><th>Trạng thái</th><th>Đề nghị</th></tr></thead>
                <tbody>
                    @forelse ($attendanceRecords as $record)
                        @php $correction = $correctionRequests->firstWhere('attendance_record_id', $record->id); @endphp
                        <tr>
                            <td>{{ $record->attendanceSession->session_date->format('d/m/Y') }}<small class="course-description">Buổi {{ $record->attendanceSession->session_number }}</small></td>
                            <td><strong>{{ $record->attendanceSession->courseSection->section_code }}</strong><small class="course-description">{{ $record->attendanceSession->courseSection->course->code }} · {{ $record->attendanceSession->courseSection->course->name }}</small></td>
                            <td>{{ $record->attendanceSession->courseSection->lecturer?->name ?? '—' }}</td>
                            <td><span class="lecturer-status {{ $record->status === 'present' ? 'is-active' : 'is-inactive' }}">{{ $statusLabels[$record->status] ?? $record->status }}</span></td>
                            <td>
                                @if ($correction)
                                    <span class="lecturer-status {{ $correction->status === 'approved' ? 'is-active' : ($correction->status === 'pending' ? 'profile-submitted' : 'is-inactive') }}">{{ $requestStatusLabels[$correction->status] ?? $correction->status }}</span>
                                    <small class="course-description">{{ $correction->reason }}@if ($correction->review_note)<br>{{ $correction->review_note }}@endif</small>
                                @elseif ($record->attendanceSession->status === 'confirmed')
                                    <form class="student-correction-form" action="{{ route('student.attendance.corrections.store', $record) }}" method="POST">
                                        @csrf
                                        <select name="requested_status" required aria-label="Tình trạng đề nghị">
                                            @foreach ($statusLabels as $value => $label)
                                                @continue($value === 'unconfirmed')
                                                <option value="{{ $value }}" @selected($record->status === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <input name="reason" maxlength="1000" required placeholder="Lý do đề nghị" aria-label="Lý do đề nghị">
                                        <button class="button button-secondary student-correction-submit" type="submit">Gửi đề nghị</button>
                                    </form>
                                @else
                                    <span class="course-description">Buổi chưa xác nhận</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="lecturer-empty">Chưa có dữ liệu điểm danh gắn với tài khoản này.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel config-departments-panel">
        <div class="panel-heading"><div><p class="eyebrow">KẾT QUẢ KIỂM TRA</p><h2>Điểm thành phần</h2></div><span class="panel-count">{{ $assessmentResults->count() }} KẾT QUẢ</span></div>
        <div class="config-table-wrap">
            <table class="config-table student-attendance-table">
                <thead><tr><th>Học phần / lớp</th><th>Bài kiểm tra</th><th>Trọng số</th><th>Kết quả</th><th>Đề nghị</th></tr></thead>
                <tbody>
                    @forelse ($assessmentResults as $result)
                        @php $gradeRequest = $assessmentCorrectionRequests->get($result->id); @endphp
                        <tr>
                            <td>{{ $result->assessment->courseSection->course->code }}<small class="course-description">{{ $result->assessment->courseSection->section_code }}</small></td>
                            <td>{{ $result->assessment->title }}<small class="course-description">{{ $result->assessment->kind }}</small></td>
                            <td>{{ $result->assessment->weight }}%</td>
                            <td>{{ $result->score ?? '—' }} / {{ $result->assessment->max_score }}<small class="course-description">{{ ['graded' => 'Đã chấm', 'absent' => 'Vắng kiểm tra', 'not_entered' => 'Chưa có điểm'][$result->status] ?? $result->status }}</small></td>
                            <td>
                                @if ($gradeRequest)
                                    <span class="lecturer-status {{ $gradeRequest->status === 'approved' ? 'is-active' : ($gradeRequest->status === 'pending' ? 'profile-submitted' : 'is-inactive') }}">{{ $requestStatusLabels[$gradeRequest->status] ?? $gradeRequest->status }}</span>
                                    <small class="course-description">{{ $gradeRequest->reason }} @if ($gradeRequest->review_note)<br>{{ $gradeRequest->review_note }}@endif</small>
                                @elseif ($result->assessment->edit_until && now()->toDateString() > $result->assessment->edit_until->toDateString())
                                    <form class="student-correction-form" action="{{ route('assessment-results.corrections.store', $result) }}" method="POST">
                                        @csrf
                                        <select name="requested_status" required aria-label="Trạng thái điểm đề nghị"><option value="graded">Đã chấm</option><option value="absent">Vắng kiểm tra</option><option value="not_entered">Chưa có điểm</option></select>
                                        <input type="number" name="requested_score" min="0" max="{{ $result->assessment->max_score }}" step="0.01" placeholder="Điểm đề nghị" aria-label="Điểm đề nghị">
                                        <input name="reason" minlength="8" maxlength="1000" required placeholder="Lý do đề nghị" aria-label="Lý do đề nghị">
                                        <button class="button button-secondary student-correction-submit" type="submit">Gửi đề nghị</button>
                                    </form>
                                @else
                                    <span class="course-description">
                                        @if ($result->assessment->edit_until)
                                            Có thể yêu cầu sau {{ $result->assessment->edit_until->format('d/m/Y') }}
                                        @else
                                            Chưa mở hạn điều chỉnh
                                        @endif
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="lecturer-empty">Chưa có kết quả kiểm tra được nhập cho tài khoản này.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
