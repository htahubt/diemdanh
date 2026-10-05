<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeTrainingOffice();
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $courses = Course::query()
            ->with('department')
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(fn (Builder $query) => $query
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhereHas('department', fn (Builder $department) => $department->where('name', 'like', "%{$search}%")));
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('is_active', $status === 'active'))
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        return view('work.courses.index', [
            'courses' => $courses,
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'activeCount' => Course::where('is_active', true)->count(),
            'inactiveCount' => Course::where('is_active', false)->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeTrainingOffice();
        $validated = $request->validate($this->rules());
        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['name'] = trim($validated['name']);
        $validated['is_active'] = true;

        Course::create($validated);

        return redirect()->route('courses.index')->with('status', 'Đã thêm học phần.');
    }

    public function edit(Course $course): View
    {
        $this->authorizeTrainingOffice();

        return view('work.courses.edit', [
            'course' => $course->load('department'),
            'departments' => Department::where('is_active', true)
                ->orWhere('id', $course->department_id)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeTrainingOffice();
        $validated = $request->validate($this->rules($course));
        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['name'] = trim($validated['name']);

        $course->update($validated);

        return redirect()->route('courses.index')->with('status', 'Đã cập nhật học phần.');
    }

    public function toggle(Course $course): RedirectResponse
    {
        $this->authorizeTrainingOffice();
        $course->update(['is_active' => ! $course->is_active]);

        return back()->with('status', 'Đã cập nhật trạng thái học phần.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $this->authorizeTrainingOffice();
        abort_if($course->sections()->exists(), 409, 'Không thể xóa học phần đang có lớp học phần.');
        $course->delete();

        return redirect()->route('courses.index')->with('status', 'Đã xóa học phần.');
    }

    private function rules(?Course $course = null): array
    {
        return [
            'code' => [
                'required', 'string', 'max:30', 'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('courses', 'code')->ignore($course?->id),
            ],
            'name' => ['required', 'string', 'max:160'],
            'credits' => ['required', 'integer', 'min:1', 'max:30'],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where(function ($query) use ($course): void {
                    $query->where('is_active', true);
                    if ($course?->department_id) {
                        $query->orWhere('id', $course->department_id);
                    }
                }),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    private function authorizeTrainingOffice(): void
    {
        abort_unless(auth()->user()->canManageTraining(), 403);
    }
}
