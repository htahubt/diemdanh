@php
    $statusLabels = ['open' => 'Đang mở', 'confirmed' => 'Đã xác nhận'];
@endphp

@extends('layouts.app')

@section('title', 'Phiên điểm danh')
@section('page_heading', 'Phiên điểm danh')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">ĐIỂM DANH / THEO DÕI BUỔI HỌC</p>
            <h1>Phiên điểm danh</h1>
            <p class="page-lede">Buổi học được tạo theo ngày diễn ra thực tế của từng lớp học phần.</p>
        </div>
    </div>

    <section class="panel config-departments-panel">
        <div class="config-table-wrap">
            <table class="config-table session-table">
                <thead><tr><th>Ngày / Buổi</th><th>Lớp học phần</th><th>Học phần</th><th>Giảng viên</th><th>Sĩ số điểm danh</th><th>Trạng thái</th><th></th></tr></thead>
                <tbody>
                    @forelse ($sessions as $session)
                        <tr>
                            <td>{{ $session->session_date->format('d/m/Y') }}<small class="course-description">Buổi {{ $session->session_number }} · {{ substr($session->start_time, 0, 5) }}–{{ substr($session->end_time, 0, 5) }}</small></td>
                            <td class="lecturer-code">{{ $session->courseSection->section_code }}</td>
                            <td><strong>{{ $session->courseSection->course->code }}</strong><small class="course-description">{{ $session->courseSection->course->name }}</small></td>
                            <td>{{ $session->courseSection->lecturer?->name ?? 'Chưa phân công' }}</td>
                            <td>{{ $session->attendance_records_count }}</td>
                            <td><span class="lecturer-status {{ $session->status === 'confirmed' ? 'is-active' : 'profile-submitted' }}">{{ $statusLabels[$session->status] ?? $session->status }}</span></td>
                            <td><a class="button button-secondary session-open-link" href="{{ route('attendance-sessions.show', $session) }}">{{ $session->status === 'confirmed' ? 'Xem kết quả' : 'Mở điểm danh' }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="lecturer-empty">Chưa có buổi học nào được tạo từ lịch lớp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="course-pagination">{{ $sessions->links() }}</div>
    </section>
@endsection
