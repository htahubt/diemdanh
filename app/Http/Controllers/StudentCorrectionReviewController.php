<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use App\Models\AssessmentCorrectionRequest;
use App\Models\StudentCorrectionRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudentCorrectionReviewController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->canManageStudentCases(), 403);

        $correctionRequests = StudentCorrectionRequest::query()
            ->with(['learner', 'attendanceRecord.attendanceSession.courseSection.course'])
            ->when(! $request->user()->canManageTraining(), fn (Builder $query) => $query->whereHas('learner', fn (Builder $learner) => $learner->where('department_id', $request->user()->department_id)))
            ->when(request('status'), fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $assessmentCorrectionRequests = AssessmentCorrectionRequest::query()
            ->with(['learner', 'assessmentResult.assessment.courseSection.course', 'requestedBy', 'reviewedBy'])
            ->when(! $request->user()->canManageTraining(), fn (Builder $query) => $query->whereHas('learner', fn (Builder $learner) => $learner->where('department_id', $request->user()->department_id)))
            ->when(request('status'), fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->paginate(25, ['*'], 'assessment_page')
            ->withQueryString();

        return view('work.correction-requests.index', compact('correctionRequests', 'assessmentCorrectionRequests'));
    }

    public function review(Request $request, StudentCorrectionRequest $correctionRequest): RedirectResponse
    {
        abort_unless($request->user()->canManageStudentCases(), 403);
        abort_unless($request->user()->canAccessTrainingDepartment($correctionRequest->learner()->value('department_id')), 404);
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'review_note' => ['required', 'string', 'min:3', 'max:1000'],
        ]);
        DB::transaction(function () use ($request, $correctionRequest, $validated): void {
            $lockedRequest = StudentCorrectionRequest::whereKey($correctionRequest->id)->lockForUpdate()->firstOrFail();
            abort_if($lockedRequest->status !== 'pending', 409, 'Đề nghị đã được xử lý.');

            if ($validated['decision'] === 'approved') {
                $record = $lockedRequest->attendanceRecord()->lockForUpdate()->firstOrFail();
                AttendanceSession::whereKey($record->attendance_session_id)->lockForUpdate()->firstOrFail();
                $record->changes()->create([
                    'actor_id' => $request->user()->id,
                    'before_status' => $record->status,
                    'after_status' => $lockedRequest->requested_status,
                    'reason' => 'Duyệt đề nghị sinh viên: ' . $validated['review_note'],
                ]);
                $record->update([
                    'status' => $lockedRequest->requested_status,
                    'recorded_by' => $request->user()->id,
                    'marked_at' => now(),
                ]);
            }

            $lockedRequest->update([
                'status' => $validated['decision'],
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'review_note' => $validated['review_note'],
            ]);
        });

        return back()->with('status', 'Đã xử lý đề nghị điều chỉnh chuyên cần.');
    }

    public function reviewAssessment(Request $request, AssessmentCorrectionRequest $correctionRequest): RedirectResponse
    {
        abort_unless($request->user()->canManageStudentCases(), 403);
        abort_unless($request->user()->canAccessTrainingDepartment($correctionRequest->learner()->value('department_id')), 404);
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'review_note' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $correctionRequest, $validated): void {
            $lockedRequest = AssessmentCorrectionRequest::whereKey($correctionRequest->id)->lockForUpdate()->firstOrFail();
            abort_if($lockedRequest->status !== 'pending', 409, 'Đề nghị đã được xử lý.');

            if ($validated['decision'] === 'approved') {
                $result = $lockedRequest->assessmentResult()->lockForUpdate()->firstOrFail();
                if ($result->status !== $lockedRequest->original_status || (string) $result->score !== (string) $lockedRequest->original_score) {
                    throw ValidationException::withMessages(['decision' => 'Kết quả đã thay đổi sau khi gửi yêu cầu; cần kiểm tra lại trước khi duyệt.']);
                }

                $result->changes()->create([
                    'actor_id' => $request->user()->id,
                    'before_status' => $result->status,
                    'before_score' => $result->score,
                    'after_status' => $lockedRequest->requested_status,
                    'after_score' => $lockedRequest->requested_score,
                    'reason' => 'Duyệt đề nghị: ' . $validated['review_note'],
                ]);
                $result->update([
                    'status' => $lockedRequest->requested_status,
                    'score' => $lockedRequest->requested_score,
                    'entered_by' => $request->user()->id,
                    'entered_at' => now(),
                ]);
            }

            $lockedRequest->update([
                'status' => $validated['decision'],
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'review_note' => $validated['review_note'],
            ]);
        });

        return back()->with('status', 'Đã xử lý đề nghị điều chỉnh điểm kiểm tra.');
    }
}
