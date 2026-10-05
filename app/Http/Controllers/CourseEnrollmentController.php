<?php

namespace App\Http\Controllers;

use App\Models\CourseSection;
use App\Models\Learner;
use App\Models\RosterChangeLog;
use App\Models\AssessmentResult;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CourseEnrollmentController extends Controller
{
    public function index(Request $request, CourseSection $courseSection): View
    {
        $user = $request->user();
        $canManageRoster = $user->canManageTraining();
        $canViewAssignedSection = $user->isAssignedToLecturer($courseSection->lecturer_id);
        abort_unless($canManageRoster || $user->canAccessTrainingDepartment($courseSection->department_id) || $canViewAssignedSection, 403);
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100']]);

        $courseSection->load([
            'course',
            'lecturer',
            'learners' => fn ($query) => $query
                ->where(fn ($query) => $query->whereNull('course_section_learner.effective_from')->orWhereDate('course_section_learner.effective_from', '<=', now()->toDateString()))
                ->where(fn ($query) => $query->whereNull('course_section_learner.effective_until')->orWhereDate('course_section_learner.effective_until', '>=', now()->toDateString()))
                ->orderBy('full_name'),
            'rosterChangeLogs.actor',
        ]);
        $today = now()->toDateString();

        $availableLearners = Learner::query()
            ->where('study_status', 'active')
            ->whereDoesntHave('courseSections', fn (Builder $query) => $query
                ->where('course_sections.id', $courseSection->id)
                ->where(fn (Builder $query) => $query
                    ->whereNull('course_section_learner.effective_from')
                    ->orWhereDate('course_section_learner.effective_from', '<=', $today))
                ->where(fn (Builder $query) => $query
                    ->whereNull('course_section_learner.effective_until')
                    ->orWhereDate('course_section_learner.effective_until', '>=', $today)))
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query
                ->where(fn (Builder $query) => $query
                    ->where('student_code', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")))
            ->when(! $canManageRoster, fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->orderBy('student_code')
            ->paginate(20)
            ->withQueryString();

        return view('work.classes.learners', compact('courseSection', 'availableLearners', 'canManageRoster'));
    }

    public function store(Request $request, CourseSection $courseSection): RedirectResponse
    {
        $this->authorizeTrainingOffice();
        $request->merge(['effective_from' => $request->input('effective_from', now()->toDateString())]);
        $validated = $request->validate([
            'learner_ids' => ['required', 'array', 'min:1'],
            'learner_ids.*' => [
                'required', 'integer', 'distinct',
                Rule::exists('learners', 'id')->where(fn ($query) => $query->where('study_status', 'active')),
            ],
            'effective_from' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($courseSection, $validated): void {
            $lockedSection = CourseSection::whereKey($courseSection->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedSection->is_active, 409, 'Không thể ghi danh vào lớp đã ngừng mở.');

            $learnerIds = collect($validated['learner_ids'])->unique()->values();
            $existingRows = DB::table('course_section_learner')
                ->where('course_section_id', $lockedSection->id)
                ->whereIn('learner_id', $learnerIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('learner_id');
            $activeIds = DB::table('course_section_learner')
                ->where('course_section_id', $lockedSection->id)
                ->where(fn ($query) => $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', $validated['effective_from']))
                ->where(fn ($query) => $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', $validated['effective_from']))
                ->pluck('learner_id');
            $newLearnerIds = $learnerIds->diff($activeIds)->values();
            $activeCount = DB::table('course_section_learner')
                ->where('course_section_id', $lockedSection->id)
                ->where(fn ($query) => $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', $validated['effective_from']))
                ->where(fn ($query) => $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', $validated['effective_from']))
                ->count();

            if ($activeCount + $newLearnerIds->count() > $lockedSection->capacity) {
                throw ValidationException::withMessages(['learner_ids' => 'Số lượng ghi danh vượt quá sĩ số tối đa của lớp.']);
            }

            foreach ($newLearnerIds as $learnerId) {
                $existing = $existingRows->get($learnerId);
                $before = $existing ? [
                    'effective_from' => $existing->effective_from,
                    'effective_until' => $existing->effective_until,
                ] : null;
                $after = ['effective_from' => $validated['effective_from'], 'effective_until' => null];

                if ($existing) {
                    DB::table('course_section_learner')
                        ->where('course_section_id', $lockedSection->id)
                        ->where('learner_id', $learnerId)
                        ->update([...$after, 'is_new' => true, 'updated_at' => now()]);
                } else {
                    $lockedSection->learners()->attach($learnerId, [...$after, 'is_new' => true]);
                }

                $learner = Learner::findOrFail($learnerId);
                RosterChangeLog::create([
                    'course_section_id' => $lockedSection->id,
                    'section_code' => $lockedSection->section_code,
                    'learner_id' => $learner->id,
                    'learner_code' => $learner->student_code,
                    'action' => $existing ? 're_enrolled' : 'enrolled',
                    'effective_from' => $validated['effective_from'],
                    'before_values' => $before,
                    'after_values' => $after,
                    'actor_id' => auth()->id(),
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                    'reason' => $validated['reason'] ?? null,
                ]);

                foreach ($lockedSection->assessments()->get() as $assessment) {
                    AssessmentResult::firstOrCreate([
                        'assessment_id' => $assessment->id,
                        'learner_id' => $learner->id,
                    ], ['status' => 'not_entered']);
                }
            }
        });

        return back()->with('status', 'Đã cập nhật danh sách người học của lớp.');
    }

    public function destroy(Request $request, CourseSection $courseSection, Learner $learner): RedirectResponse
    {
        $this->authorizeTrainingOffice();
        $validated = $request->validate([
            'effective_until' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($courseSection, $learner, $validated): void {
            $lockedSection = CourseSection::whereKey($courseSection->id)->lockForUpdate()->firstOrFail();
            $registration = DB::table('course_section_learner')
                ->where('course_section_id', $lockedSection->id)
                ->where('learner_id', $learner->id)
                ->lockForUpdate()
                ->first();
            abort_unless($registration, 404);
            abort_if($registration->effective_until, 409, 'Ghi danh này đã có ngày kết thúc hiệu lực.');
            if ($registration->effective_from && $validated['effective_until'] < $registration->effective_from) {
                throw ValidationException::withMessages(['effective_until' => 'Ngày kết thúc không được trước ngày bắt đầu hiệu lực.']);
            }

            $before = ['effective_from' => $registration->effective_from, 'effective_until' => null];
            $after = ['effective_from' => $registration->effective_from, 'effective_until' => $validated['effective_until']];
            DB::table('course_section_learner')
                ->where('course_section_id', $lockedSection->id)
                ->where('learner_id', $learner->id)
                ->update(['effective_until' => $validated['effective_until'], 'is_new' => false, 'updated_at' => now()]);

            RosterChangeLog::create([
                'course_section_id' => $lockedSection->id,
                'section_code' => $lockedSection->section_code,
                'learner_id' => $learner->id,
                'learner_code' => $learner->student_code,
                'action' => 'withdrawn',
                'effective_from' => $registration->effective_from,
                'effective_until' => $validated['effective_until'],
                'before_values' => $before,
                'after_values' => $after,
                'actor_id' => auth()->id(),
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'reason' => $validated['reason'] ?? null,
            ]);
        });

        return back()->with('status', 'Đã kết thúc hiệu lực ghi danh; lịch sử roster và hồ sơ người học được giữ nguyên.');
    }

    private function authorizeTrainingOffice(): void
    {
        abort_unless(auth()->user()->canManageTraining(), 403);
    }
}
