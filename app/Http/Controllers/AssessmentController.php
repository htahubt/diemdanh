<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\AssessmentCorrectionRequest;
use App\Models\CourseSection;
use App\Models\Learner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function index(Request $request, CourseSection $courseSection): View
    {
        $this->authorizeSection($request, $courseSection);
        $courseSection->loadCount('learners');
        $assessments = $courseSection->assessments()
            ->withCount(['results as graded_count' => fn (Builder $query) => $query->where('status', 'graded')])
            ->orderBy('id')
            ->get();

        return view('work.assessments.index', compact('courseSection', 'assessments'));
    }

    public function store(Request $request, CourseSection $courseSection): RedirectResponse
    {
        abort_unless($request->user()->canManageTraining(), 403);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'kind' => ['required', 'string', 'max:40'],
            'max_score' => ['required', 'numeric', 'gt:0', 'max:1000'],
            'weight' => ['required', 'numeric', 'gt:0', 'max:100'],
            'edit_until' => ['nullable', 'date'],
        ]);

        $totalWeight = (float) $courseSection->assessments()->sum('weight') + (float) $validated['weight'];
        if ($totalWeight > 100.0001) {
            throw ValidationException::withMessages(['weight' => 'Tổng trọng số các cột điểm không được vượt quá 100%.']);
        }

        $assessment = $courseSection->assessments()->create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        $learnerIds = $courseSection->learners()
            ->where('learners.study_status', 'active')
            ->where(fn (Builder $query) => $query->whereNull('course_section_learner.effective_from')->orWhereDate('course_section_learner.effective_from', '<=', now()->toDateString()))
            ->where(fn (Builder $query) => $query->whereNull('course_section_learner.effective_until')->orWhereDate('course_section_learner.effective_until', '>=', now()->toDateString()))
            ->pluck('learners.id');
        foreach ($learnerIds as $learnerId) {
            AssessmentResult::firstOrCreate([
                'assessment_id' => $assessment->id,
                'learner_id' => $learnerId,
            ], ['status' => 'not_entered']);
        }

        return redirect()->route('assessments.show', $assessment)->with('status', 'Đã tạo cột điểm kiểm tra.');
    }

    public function show(Request $request, Assessment $assessment): View
    {
        $this->authorizeSection($request, $assessment->courseSection);
        $assessment->load(['courseSection.course', 'results.learner', 'results.enteredBy', 'results.changes.actor']);
        $learners = $assessment->courseSection->learners()
            ->where('learners.study_status', 'active')
            ->where(fn (Builder $query) => $query
                ->whereNull('course_section_learner.effective_from')
                ->orWhereDate('course_section_learner.effective_from', '<=', now()->toDateString()))
            ->where(fn (Builder $query) => $query
                ->whereNull('course_section_learner.effective_until')
                ->orWhereDate('course_section_learner.effective_until', '>=', now()->toDateString()))
            ->orderBy('student_code')
            ->get();
        $resultsByLearner = $assessment->results->keyBy('learner_id');

        return view('work.assessments.show', compact('assessment', 'learners', 'resultsByLearner'));
    }

    public function updateResults(Request $request, Assessment $assessment): RedirectResponse
    {
        $this->authorizeSection($request, $assessment->courseSection);
        abort_if($assessment->edit_until && now()->toDateString() > $assessment->edit_until->toDateString(), 409, 'Đã quá hạn nhập/sửa điểm; hãy gửi đề nghị điều chỉnh để được phê duyệt.');

        $validated = Validator::make($request->all(), [
            'results' => ['required', 'array', 'min:1'],
            'results.*.status' => ['required', Rule::in(AssessmentResult::STATUSES)],
            'results.*.score' => ['nullable', 'numeric', 'min:0', 'max:' . $assessment->max_score],
            'reason' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        foreach ($validated['results'] as $learnerId => $result) {
            if ($result['status'] === 'graded' && ($result['score'] ?? null) === null) {
                throw ValidationException::withMessages(["results.{$learnerId}.score" => 'Cần nhập điểm cho trạng thái đã chấm.']);
            }
            if ($result['status'] !== 'graded') {
                $validated['results'][$learnerId]['score'] = null;
            }
        }

        $eligibleIds = $assessment->courseSection->learners()
            ->where('learners.study_status', 'active')
            ->where(fn (Builder $query) => $query
                ->whereNull('course_section_learner.effective_from')
                ->orWhereDate('course_section_learner.effective_from', '<=', now()->toDateString()))
            ->where(fn (Builder $query) => $query
                ->whereNull('course_section_learner.effective_until')
                ->orWhereDate('course_section_learner.effective_until', '>=', now()->toDateString()))
            ->pluck('learners.id')
            ->map(fn ($id): string => (string) $id)
            ->all();
        if (array_diff(array_map('strval', array_keys($validated['results'])), $eligibleIds)) {
            throw ValidationException::withMessages(['results' => 'Có người học không thuộc roster hiện hành của lớp.']);
        }

        DB::transaction(function () use ($request, $assessment, $validated): void {
            foreach ($validated['results'] as $learnerId => $resultData) {
                $result = AssessmentResult::firstOrNew([
                    'assessment_id' => $assessment->id,
                    'learner_id' => $learnerId,
                ]);
                $beforeStatus = $result->exists ? $result->status : 'not_entered';
                $beforeScore = $result->exists ? $result->score : null;
                if ($beforeStatus === $resultData['status'] && (string) $beforeScore === (string) ($resultData['score'] ?? null)) {
                    continue;
                }

                $result->fill([
                    'status' => $resultData['status'],
                    'score' => $resultData['score'] ?? null,
                    'entered_by' => $request->user()->id,
                    'entered_at' => now(),
                ]);
                $result->save();
                $result->changes()->create([
                    'actor_id' => $request->user()->id,
                    'before_status' => $beforeStatus,
                    'before_score' => $beforeScore,
                    'after_status' => $resultData['status'],
                    'after_score' => $resultData['score'] ?? null,
                    'reason' => $validated['reason'] ?? null,
                ]);
            }
        });

        return back()->with('status', 'Đã lưu điểm kiểm tra và lịch sử thay đổi.');
    }

    public function requestCorrection(Request $request, AssessmentResult $assessmentResult): RedirectResponse
    {
        $assessmentResult->load('assessment.courseSection');
        $user = $request->user();
        $section = $assessmentResult->assessment->courseSection;
        $isOwner = $user->role === 'learner' && (int) $user->learner_id === $assessmentResult->learner_id;
        $isAssignedLecturer = $user->isAssignedToLecturer($section->lecturer_id);
        abort_unless($isOwner || $isAssignedLecturer, 404);
        abort_unless($assessmentResult->assessment->edit_until && now()->toDateString() > $assessmentResult->assessment->edit_until->toDateString(), 409, 'Trong thời hạn cho phép, hãy sửa trực tiếp trên bảng điểm.');

        $validated = $request->validate([
            'requested_status' => ['required', Rule::in(AssessmentResult::STATUSES)],
            'requested_score' => ['nullable', 'numeric', 'min:0', 'max:' . $assessmentResult->assessment->max_score],
            'reason' => ['required', 'string', 'min:8', 'max:1000'],
        ]);
        if ($validated['requested_status'] === 'graded' && ($validated['requested_score'] ?? null) === null) {
            throw ValidationException::withMessages(['requested_score' => 'Cần nhập điểm đề nghị khi trạng thái là đã chấm.']);
        }
        if ($validated['requested_status'] !== 'graded') {
            $validated['requested_score'] = null;
        }
        abort_if(AssessmentCorrectionRequest::where('assessment_result_id', $assessmentResult->id)->where('status', 'pending')->exists(), 409, 'Đã có yêu cầu đang chờ duyệt cho kết quả này.');

        AssessmentCorrectionRequest::create([
            'assessment_result_id' => $assessmentResult->id,
            'learner_id' => $assessmentResult->learner_id,
            'requested_by' => $user->id,
            'requester_type' => $user->role,
            'original_status' => $assessmentResult->status,
            'original_score' => $assessmentResult->score,
            'requested_status' => $validated['requested_status'],
            'requested_score' => $validated['requested_score'] ?? null,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return back()->with('status', 'Đã gửi yêu cầu điều chỉnh điểm để đơn vị đào tạo phê duyệt.');
    }

    private function authorizeSection(Request $request, CourseSection $courseSection): void
    {
        $user = $request->user();
        $isAssignedLecturer = $user->isAssignedToLecturer($courseSection->lecturer_id);
        $canReadUnit = in_array($user->role, ['unit_leader', 'student_affairs'], true)
            && (int) $user->department_id === (int) $courseSection->department_id;
        abort_unless($user->canManageTraining() || $isAssignedLecturer || $canReadUnit, 403);
    }
}
