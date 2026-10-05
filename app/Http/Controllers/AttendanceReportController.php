<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\CourseSection;
use App\Models\Department;
use App\Models\Learner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AttendanceReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $query = $this->recordsQuery($request, $filters);
        return view('work.attendance-reports.index', [
            'records' => (clone $query)->with(['learner', 'attendanceSession.courseSection.course', 'attendanceSession.courseSection.lecturer'])->orderByDesc('attendance_sessions.session_date')->paginate(50)->withQueryString(),
            'sections' => $this->availableSections($request),
            'departments' => Department::orderBy('name')->when(in_array($request->user()->role, ['unit_leader', 'student_affairs'], true), fn ($query) => $query->whereKey($request->user()->department_id))->get(),
            'learners' => Learner::query()->orderBy('student_code')->when(in_array($request->user()->role, ['unit_leader', 'student_affairs'], true), fn (Builder $query) => $query->where('department_id', $request->user()->department_id))->get(),
            'filters' => $filters,
            'totalCount' => (clone $query)->count(),
            'presentCount' => (clone $query)->where('attendance_records.status', 'present')->count(),
            'lateCount' => (clone $query)->where('attendance_records.status', 'late')->count(),
            'excusedCount' => (clone $query)->where('attendance_records.status', 'excused')->count(),
            'unexcusedCount' => (clone $query)->where('attendance_records.status', 'unexcused')->count(),
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->filters($request);
        $records = $this->recordsQuery($request, $filters)
            ->with(['learner', 'attendanceSession.courseSection.course', 'attendanceSession.courseSection.lecturer'])
            ->orderBy('attendance_sessions.session_date')
            ->orderBy('attendance_sessions.session_number')
            ->get();
        $statusLabels = ['present' => 'Có mặt', 'late' => 'Đi muộn', 'excused' => 'Vắng có phép', 'unexcused' => 'Vắng không phép', 'other' => 'Khác', 'unconfirmed' => 'Chưa xác định'];

        return Response::streamDownload(function () use ($records, $statusLabels): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Ngày', 'Buổi', 'Mã lớp học phần', 'Học phần', 'Mã người học', 'Họ tên', 'Trạng thái', 'Giảng viên', 'Người ghi nhận', 'Thời điểm ghi nhận', 'Thời điểm xác nhận']);

            foreach ($records as $record) {
                $session = $record->attendanceSession;
                fputcsv($output, [
                    $session->session_date->format('Y-m-d'),
                    $session->session_number,
                    $session->courseSection->section_code,
                    $session->courseSection->course->code,
                    $record->learner->student_code,
                    $record->learner->full_name,
                    $statusLabels[$record->status] ?? $record->status,
                    $session->courseSection->lecturer?->name,
                    $record->recordedBy?->name,
                    $record->marked_at?->format('Y-m-d H:i:s'),
                    $session->confirmed_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($output);
        }, 'bao-cao-chuyen-can.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'course_section_id' => ['nullable', 'integer', Rule::exists('course_sections', 'id')],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'learner_id' => ['nullable', 'integer', Rule::exists('learners', 'id')],
            'status' => ['nullable', Rule::in(AttendanceRecord::STATUSES)],
            'academic_year' => ['nullable', 'regex:/^20\d{2}-20\d{2}$/'],
            'semester' => ['nullable', Rule::in(['1', '2', '3'])],
        ]);
    }

    private function recordsQuery(Request $request, array $filters): Builder
    {
        $user = $request->user();
        $lecturerId = $user->lecturerProfileId() ?? 0;
        abort_unless($user->canManageStudentCases() || $user->role === 'lecturer', 403);

        return AttendanceRecord::query()
            ->join('attendance_sessions', 'attendance_records.attendance_session_id', '=', 'attendance_sessions.id')
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->whereDate('attendance_sessions.session_date', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->whereDate('attendance_sessions.session_date', '<=', $to))
            ->when($filters['course_section_id'] ?? null, fn (Builder $query, int $sectionId) => $query->where('attendance_sessions.course_section_id', $sectionId))
            ->when($filters['learner_id'] ?? null, fn (Builder $query, int $learnerId) => $query->where('attendance_records.learner_id', $learnerId))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('attendance_records.status', $status))
            ->whereHas('attendanceSession.courseSection', function (Builder $section) use ($filters, $user, $lecturerId): void {
                $section->when($filters['department_id'] ?? null, fn (Builder $query, int $departmentId) => $query->where('department_id', $departmentId))
                    ->when($filters['academic_year'] ?? null, fn (Builder $query, string $year) => $query->where('academic_year', $year))
                    ->when($filters['semester'] ?? null, fn (Builder $query, string $semester) => $query->where('semester', $semester));
                if (in_array($user->role, ['unit_leader', 'student_affairs'], true)) {
                    $section->where('department_id', $user->department_id);
                }
            })
            ->when($user->role === 'lecturer', fn (Builder $query) => $query->whereHas(
                'attendanceSession.courseSection',
                fn (Builder $section) => $section->where('lecturer_id', $lecturerId),
            ))
            ->select('attendance_records.*');
    }

    private function availableSections(Request $request)
    {
        $lecturerId = $request->user()->lecturerProfileId() ?? 0;

        return CourseSection::query()
            ->with('course')
            ->when($request->user()->role === 'lecturer', fn (Builder $query) => $query->where('lecturer_id', $lecturerId))
            ->when(in_array($request->user()->role, ['unit_leader', 'student_affairs'], true), fn (Builder $query) => $query->where('department_id', $request->user()->department_id))
            ->orderBy('section_code')
            ->get();
    }
}
