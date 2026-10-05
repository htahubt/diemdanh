@php
    $statusLabels = ['pending' => 'Chờ xử lý', 'approved' => 'Đã duyệt', 'rejected' => 'Từ chối'];
    $attendanceStatusLabels = ['present' => 'Có mặt', 'late' => 'Đi muộn', 'excused' => 'Vắng có phép', 'unexcused' => 'Vắng không phép', 'other' => 'Khác'];
@endphp

@extends('layouts.app')

@section('title', 'Đề nghị kiểm tra chuyên cần')
@section('page_heading', 'Đề nghị kiểm tra chuyên cần')

@section('content')
    <div class="page-heading">
        <div><p class="eyebrow">QUẢN LÝ NGƯỜI HỌC / XỬ LÝ YÊU CẦU</p><h1>Đề nghị kiểm tra chuyên cần</h1><p class="page-lede">Chỉ yêu cầu được duyệt mới cập nhật bản ghi điểm danh.</p></div>
    </div>
    @if (session('status'))<div class="lecturer-flash" role="status">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="lecturer-flash config-error" role="alert">{{ $errors->first() }}</div>@endif

    <form class="course-filters" action="{{ route('correction-requests.index') }}" method="GET">
        <label class="lecturer-status-filter"><span class="sr-only">Trạng thái yêu cầu</span><select name="status"><option value="">Tất cả</option>@foreach ($statusLabels as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></label>
        <button class="button button-secondary" type="submit">Lọc</button>
    </form>

    <section class="panel config-departments-panel">
        <div class="config-table-wrap">
            <table class="config-table correction-table">
                <thead><tr><th>Người học</th><th>Buổi / Lớp</th><th>Hiện tại → Đề nghị</th><th>Lý do</th><th>Trạng thái / xử lý</th></tr></thead>
                <tbody>
                    @forelse ($correctionRequests as $correction)
                        <tr>
                            <td><strong>{{ $correction->learner->full_name }}</strong><small class="course-description">{{ $correction->learner->student_code }}</small></td>
                            <td>{{ $correction->attendanceRecord->attendanceSession->session_date->format('d/m/Y') }}<small class="course-description">{{ $correction->attendanceRecord->attendanceSession->courseSection->section_code }} · Buổi {{ $correction->attendanceRecord->attendanceSession->session_number }}</small></td>
                            <td>{{ $attendanceStatusLabels[$correction->original_status] ?? $correction->original_status }} → {{ $attendanceStatusLabels[$correction->requested_status] ?? $correction->requested_status }}</td>
                            <td>{{ $correction->reason }}</td>
                            <td>
                                @if ($correction->status === 'pending')
                                    <form class="correction-review-form" action="{{ route('correction-requests.review', $correction) }}" method="POST">
                                        @csrf
                                        <label class="sr-only" for="review-note-{{ $correction->id }}">Lý do xử lý</label>
                                        <input id="review-note-{{ $correction->id }}" name="review_note" required minlength="3" maxlength="1000" placeholder="Ghi chú xử lý">
                                        <div><button class="button button-primary" type="submit" name="decision" value="approved">Duyệt</button><button class="button button-secondary" type="submit" name="decision" value="rejected">Từ chối</button></div>
                                    </form>
                                @else
                                    <span class="lecturer-status {{ $correction->status === 'approved' ? 'is-active' : 'is-inactive' }}">{{ $statusLabels[$correction->status] }}</span>
                                    <small class="course-description">{{ $correction->reviewedBy?->name }} · {{ $correction->reviewed_at?->format('d/m/Y H:i') }} · {{ $correction->review_note }}</small>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="lecturer-empty">Không có đề nghị nào phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="course-pagination">{{ $correctionRequests->links() }}</div>
    </section>

    <section class="panel config-departments-panel">
        <div class="panel-heading"><div><p class="eyebrow">ĐIỂM KIỂM TRA</p><h2>Yêu cầu điều chỉnh điểm</h2></div><span class="panel-count">{{ $assessmentCorrectionRequests->total() }} YÊU CẦU</span></div>
        <div class="config-table-wrap">
            <table class="config-table correction-table">
                <thead><tr><th>Người học</th><th>Lớp / Bài kiểm tra</th><th>Điểm hiện tại → đề nghị</th><th>Lý do</th><th>Xử lý</th></tr></thead>
                <tbody>
                    @forelse ($assessmentCorrectionRequests as $correction)
                        <tr>
                            <td><strong>{{ $correction->learner->full_name }}</strong><small class="course-description">{{ $correction->learner->student_code }} · {{ $correction->requestedBy->name }}</small></td>
                            <td>{{ $correction->assessmentResult->assessment->courseSection->section_code }}<small class="course-description">{{ $correction->assessmentResult->assessment->title }}</small></td>
                            <td>{{ $correction->original_score ?? '—' }} ({{ $correction->original_status }}) → {{ $correction->requested_score ?? '—' }} ({{ $correction->requested_status }})</td>
                            <td>{{ $correction->reason }}</td>
                            <td>
                                @if ($correction->status === 'pending')
                                    <form class="correction-review-form" action="{{ route('assessment-correction-requests.review', $correction) }}" method="POST">
                                        @csrf
                                        <label class="sr-only" for="assessment-review-note-{{ $correction->id }}">Ghi chú duyệt điểm</label>
                                        <input id="assessment-review-note-{{ $correction->id }}" name="review_note" required minlength="3" maxlength="1000" placeholder="Lý do xử lý">
                                        <div><button class="button button-primary" type="submit" name="decision" value="approved">Duyệt</button><button class="button button-secondary" type="submit" name="decision" value="rejected">Từ chối</button></div>
                                    </form>
                                @else
                                    <span class="lecturer-status {{ $correction->status === 'approved' ? 'is-active' : 'is-inactive' }}">{{ $statusLabels[$correction->status] }}</span>
                                    <small class="course-description">{{ $correction->reviewedBy?->name }} · {{ $correction->reviewed_at?->format('d/m/Y H:i') }} · {{ $correction->review_note }}</small>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="lecturer-empty">Không có yêu cầu điều chỉnh điểm.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="course-pagination">{{ $assessmentCorrectionRequests->links() }}</div>
    </section>
@endsection
