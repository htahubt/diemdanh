<?php

namespace App\Http\Controllers;

use App\Models\AssessmentResult;
use App\Models\CourseSection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssessmentReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $query = $this->resultsQuery($request, $filters);

        return view('work.assessment-reports.index', [
            'results' => (clone $query)->with(['assessment.courseSection.course', 'learner', 'enteredBy'])->orderBy('learner_id')->paginate(50)->withQueryString(),
            'sections' => $this->availableSections($request),
            'filters' => $filters,
            'gradedCount' => (clone $query)->where('status', 'graded')->count(),
            'absentCount' => (clone $query)->where('status', 'absent')->count(),
            'notEnteredCount' => (clone $query)->where('status', 'not_entered')->count(),
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->filters($request);
        $results = $this->resultsQuery($request, $filters)
            ->with(['assessment.courseSection.course', 'learner', 'enteredBy'])
            ->orderBy('learner_id')
            ->get();
        $statusLabels = ['graded' => 'Đã chấm', 'absent' => 'Vắng kiểm tra', 'not_entered' => 'Chưa có điểm'];

        return Response::streamDownload(function () use ($results, $statusLabels): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Năm học', 'Học kỳ', 'Mã lớp', 'Học phần', 'Cột điểm', 'Loại', 'Thang điểm', 'Trọng số', 'Mã người học', 'Họ tên', 'Trạng thái', 'Điểm', 'Người nhập', 'Thời điểm nhập']);
            foreach ($results as $result) {
                fputcsv($output, [
                    $result->assessment->courseSection->academic_year,
                    $result->assessment->courseSection->semester,
                    $result->assessment->courseSection->section_code,
                    $result->assessment->courseSection->course->code,
                    $result->assessment->title,
                    $result->assessment->kind,
                    $result->assessment->max_score,
                    $result->assessment->weight,
                    $result->learner->student_code,
                    $result->learner->full_name,
                    $statusLabels[$result->status] ?? $result->status,
                    $result->score,
                    $result->enteredBy?->name,
                    $result->entered_at?->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($output);
        }, 'bao-cao-diem-kiem-tra.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'course_section_id' => ['nullable', 'integer', Rule::exists('course_sections', 'id')],
            'academic_year' => ['nullable', 'regex:/^20\d{2}-20\d{2}$/'],
            'semester' => ['nullable', Rule::in(['1', '2', '3'])],
            'status' => ['nullable', Rule::in(['graded', 'absent', 'not_entered'])],
        ]);
    }

    private function resultsQuery(Request $request, array $filters): Builder
    {
        $user = $request->user();
        $lecturerId = $user->lecturerProfileId() ?? 0;
        abort_unless($user->canManageStudentCases() || $user->role === 'lecturer', 403);

        return AssessmentResult::query()
            ->whereHas('assessment.courseSection', function (Builder $query) use ($filters, $user, $lecturerId): void {
                $query->when($filters['course_section_id'] ?? null, fn (Builder $query, int $sectionId) => $query->whereKey($sectionId))
                    ->when($filters['academic_year'] ?? null, fn (Builder $query, string $year) => $query->where('academic_year', $year))
                    ->when($filters['semester'] ?? null, fn (Builder $query, string $semester) => $query->where('semester', $semester));

                if ($user->role === 'lecturer') {
                    $query->where('lecturer_id', $lecturerId);
                } elseif (in_array($user->role, ['unit_leader', 'student_affairs'], true)) {
                    $query->where('department_id', $user->department_id);
                }
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status));
    }

    private function availableSections(Request $request)
    {
        $lecturerId = $request->user()->lecturerProfileId() ?? 0;

        return CourseSection::query()
            ->with('course')
            ->when($request->user()->role === 'lecturer', fn (Builder $query) => $query->where('lecturer_id', $lecturerId))
            ->when(in_array($request->user()->role, ['unit_leader', 'student_affairs'], true), fn (Builder $query) => $query->where('department_id', $request->user()->department_id))
            ->orderBy('academic_year')
            ->orderBy('section_code')
            ->get();
    }
}
