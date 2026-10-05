<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Department;
use App\Models\Lecturer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CourseSectionController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $canManageTraining = $user->canManageTraining();
        $isLecturer = $user->role === 'lecturer';
        abort_unless($canManageTraining || $user->canReadTrainingData() || $isLecturer, 403);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'academic_year' => ['nullable', 'regex:/^20\d{2}-20\d{2}$/'],
            'semester' => ['nullable', Rule::in(['1', '2', '3'])],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);
        $lecturerId = $isLecturer ? ($user->lecturerProfileId() ?? 0) : null;
        $countQuery = CourseSection::query()
            ->when($isLecturer, fn (Builder $query) => $query->where('lecturer_id', $lecturerId))
            ->when(in_array($user->role, ['unit_leader', 'student_affairs'], true), fn (Builder $query) => $query->where('department_id', $user->department_id));

        $sections = (clone $countQuery)
            ->with(['course.department', 'department', 'lecturer'])
            ->withCount(['learners', 'attendanceSessions'])
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('section_code', 'like', "%{$search}%")
                        ->orWhereHas('course', fn (Builder $course) => $course->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"))
                        ->orWhereHas('lecturer', fn (Builder $lecturer) => $lecturer->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['academic_year'] ?? null, fn (Builder $query, string $year) => $query->where('academic_year', $year))
            ->when($filters['semester'] ?? null, fn (Builder $query, string $semester) => $query->where('semester', $semester))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('is_active', $status === 'active'))
            ->orderByDesc('academic_year')
            ->orderByDesc('semester')
            ->orderBy('section_code')
            ->paginate(15)
            ->withQueryString();

        return view('work.classes.index', [
            'sections' => $sections,
            'courses' => Course::where('is_active', true)->orderBy('code')->get(),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'lecturers' => Lecturer::where('status', 'active')->where('profile_status', 'approved')->orderBy('name')->get(),
            'activeCount' => (clone $countQuery)->where('is_active', true)->count(),
            'inactiveCount' => (clone $countQuery)->where('is_active', false)->count(),
            'canManageTraining' => $canManageTraining,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeTrainingOffice();
        $validated = $request->validate($this->rules());
        if ($this->hasScheduleConflict($validated)) {
            return back()->withErrors(['schedule' => 'Lịch học bị trùng giờ với lớp khác cùng giảng viên hoặc phòng học.'])->withInput();
        }
        $validated['section_code'] = strtoupper(trim($validated['section_code']));
        $validated['is_active'] = true;

        CourseSection::create($validated);

        return redirect()->route('classes.index')->with('status', 'Đã mở lớp học phần.');
    }

    public function edit(CourseSection $courseSection): View
    {
        $this->authorizeTrainingOffice();

        return view('work.classes.edit', [
            'section' => $courseSection->load(['course', 'department', 'lecturer']),
            'courses' => Course::where('is_active', true)->orWhere('id', $courseSection->course_id)->orderBy('code')->get(),
            'departments' => Department::where('is_active', true)->orWhere('id', $courseSection->department_id)->orderBy('name')->get(),
            'lecturers' => Lecturer::where(function (Builder $query) use ($courseSection): void {
                $query->where(fn (Builder $query) => $query->where('status', 'active')->where('profile_status', 'approved'));
                if ($courseSection->lecturer_id) {
                    $query->orWhere('id', $courseSection->lecturer_id);
                }
            })->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, CourseSection $courseSection): RedirectResponse
    {
        $this->authorizeTrainingOffice();
        $validated = $request->validate($this->rules($courseSection));
        if ($this->hasScheduleConflict($validated, $courseSection)) {
            return back()->withErrors(['schedule' => 'Lịch học bị trùng giờ với lớp khác cùng giảng viên hoặc phòng học.'])->withInput();
        }
        $validated['section_code'] = strtoupper(trim($validated['section_code']));
        $courseSection->update($validated);

        return redirect()->route('classes.index')->with('status', 'Đã cập nhật lớp học phần.');
    }

    public function toggle(CourseSection $courseSection): RedirectResponse
    {
        $this->authorizeTrainingOffice();
        $courseSection->update(['is_active' => ! $courseSection->is_active]);

        return back()->with('status', 'Đã cập nhật trạng thái lớp học phần.');
    }

    public function destroy(CourseSection $courseSection): RedirectResponse
    {
        $this->authorizeTrainingOffice();
        abort_if($courseSection->attendanceSessions()->exists(), 409, 'Không thể xóa lớp đã có buổi học; hãy ngừng mở lớp thay vì xóa.');
        $courseSection->delete();

        return redirect()->route('classes.index')->with('status', 'Đã xóa lớp học phần.');
    }

    private function rules(?CourseSection $section = null): array
    {
        return [
            'course_id' => [
                'required', 'integer',
                Rule::exists('courses', 'id')->where(function ($query) use ($section): void {
                    $query->where('is_active', true);
                    if ($section) {
                        $query->orWhere('id', $section->course_id);
                    }
                }),
            ],
            'section_code' => [
                'required', 'string', 'max:40', 'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('course_sections', 'section_code')
                    ->where('course_id', request('course_id'))
                    ->where('academic_year', request('academic_year'))
                    ->where('semester', request('semester'))
                    ->ignore($section?->id),
            ],
            'semester' => ['required', Rule::in([1, 2, 3])],
            'academic_year' => ['required', 'regex:/^20\d{2}-20\d{2}$/'],
            'lecturer_id' => [
                'nullable', 'integer',
                Rule::exists('lecturers', 'id')->where(function ($query) use ($section): void {
                    $query->where('status', 'active')->where('profile_status', 'approved');
                    if ($section?->lecturer_id) {
                        $query->orWhere('id', $section->lecturer_id);
                    }
                }),
            ],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->where('is_active', true)],
            'training_format' => ['required', 'string', 'max:80'],
            'weekday' => ['required', Rule::in([1, 2, 3, 4, 5, 6, 7])],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'room' => ['required', 'string', 'max:100'],
            'expected_sessions' => ['required', 'integer', 'min:1', 'max:100'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'capacity' => ['required', 'integer', 'min:1', 'max:500'],
        ];
    }

    private function hasScheduleConflict(array $schedule, ?CourseSection $except = null): bool
    {
        return CourseSection::query()
            ->where('is_active', true)
            ->where('weekday', $schedule['weekday'])
            ->whereDate('starts_on', '<=', $schedule['ends_on'])
            ->whereDate('ends_on', '>=', $schedule['starts_on'])
            ->where('start_time', '<', $schedule['end_time'])
            ->where('end_time', '>', $schedule['start_time'])
            ->where(function (Builder $query) use ($schedule): void {
                $query->where('room', $schedule['room']);
                if ($schedule['lecturer_id'] ?? null) {
                    $query->orWhere('lecturer_id', $schedule['lecturer_id']);
                }
            })
            ->when($except, fn (Builder $query) => $query->where('course_sections.id', '!=', $except->id))
            ->exists();
    }

    private function authorizeTrainingOffice(): void
    {
        abort_unless(auth()->user()->canManageTraining(), 403);
    }
}
