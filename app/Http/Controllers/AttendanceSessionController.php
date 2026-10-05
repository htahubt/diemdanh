<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\AttendanceAlertSetting;
use App\Models\AttendanceSession;
use App\Models\CourseSection;
use App\Models\StudentCorrectionRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AttendanceSessionController extends Controller
{
    public function overview(Request $request): View
    {
        $user = $request->user();
        $lecturerId = $user->lecturerProfileId() ?? 0;
        $sessions = AttendanceSession::query()
            ->with(['courseSection.course', 'courseSection.lecturer'])
            ->withCount('attendanceRecords')
            ->when($user->role === 'lecturer', fn (Builder $query) => $query->whereHas('courseSection', fn (Builder $section) => $section->where('lecturer_id', $lecturerId)))
            ->when(in_array($user->role, ['unit_leader', 'student_affairs'], true), fn (Builder $query) => $query->whereHas('courseSection', fn (Builder $section) => $section->where('department_id', $user->department_id)))
            ->orderByDesc('session_date')
            ->orderByDesc('session_number')
            ->paginate(20);

        return view('work.attendance-sessions.overview', compact('sessions'));
    }

    public function index(Request $request, CourseSection $courseSection): View
    {
        $this->authorizeSection($courseSection, false);

        return view('work.attendance-sessions.index', [
            'courseSection' => $courseSection->load(['course', 'lecturer', 'department']),
            'sessions' => $courseSection->attendanceSessions()->withCount('attendanceRecords')->get(),
            'canCreateSession' => $request->user()->canManageTraining()
                || $request->user()->isAssignedToLecturer($courseSection->lecturer_id),
        ]);
    }

    public function store(Request $request, CourseSection $courseSection): RedirectResponse
    {
        $this->authorizeSection($courseSection, true);
        abort_unless($courseSection->is_active, 409, 'Lớp đã ngừng mở.');
        abort_unless($courseSection->weekday && $courseSection->start_time && $courseSection->end_time && $courseSection->starts_on && $courseSection->ends_on, 409, 'Cần hoàn thiện thời khóa biểu trước khi tạo buổi học.');

        $validated = $request->validate(['session_date' => ['required', 'date']]);
        $sessionDate = Carbon::parse($validated['session_date']);

        if ($sessionDate->lt($courseSection->starts_on) || $sessionDate->gt($courseSection->ends_on)) {
            throw ValidationException::withMessages(['session_date' => 'Ngày học nằm ngoài thời gian tổ chức của lớp.']);
        }
        if ($sessionDate->isoWeekday() !== $courseSection->weekday) {
            throw ValidationException::withMessages(['session_date' => 'Ngày đã chọn không đúng thứ theo thời khóa biểu của lớp.']);
        }

        $session = DB::transaction(function () use ($courseSection, $sessionDate): AttendanceSession {
            $lockedSection = CourseSection::whereKey($courseSection->id)->lockForUpdate()->firstOrFail();
            $sessionNumber = (int) $lockedSection->attendanceSessions()->max('session_number') + 1;

            if ($lockedSection->expected_sessions && $sessionNumber > $lockedSection->expected_sessions) {
                throw ValidationException::withMessages(['session_date' => 'Đã tạo đủ số buổi dự kiến; hãy cập nhật số buổi lớp trước khi mở thêm.']);
            }
            if ($lockedSection->attendanceSessions()->whereDate('session_date', $sessionDate)->exists()) {
                throw ValidationException::withMessages(['session_date' => 'Lớp đã có buổi học vào ngày này.']);
            }

            $session = $lockedSection->attendanceSessions()->create([
                'session_number' => $sessionNumber,
                'session_date' => $sessionDate->toDateString(),
                'start_time' => $lockedSection->start_time,
                'end_time' => $lockedSection->end_time,
                'room' => $lockedSection->room,
                'status' => AttendanceSession::STATUS_OPEN,
                'opened_by' => auth()->id(),
                'opened_at' => now(),
            ]);

            $learners = $lockedSection->learners()
                ->where('learners.study_status', 'active')
                ->where(fn (Builder $query) => $query
                    ->whereNull('course_section_learner.effective_from')
                    ->orWhereDate('course_section_learner.effective_from', '<=', $sessionDate->toDateString()))
                ->where(fn (Builder $query) => $query
                    ->whereNull('course_section_learner.effective_until')
                    ->orWhereDate('course_section_learner.effective_until', '>=', $sessionDate->toDateString()))
                ->get(['learners.id']);

            foreach ($learners as $learner) {
                $session->attendanceRecords()->create(['learner_id' => $learner->id]);
            }

            return $session;
        });

        return redirect()->route('attendance-sessions.show', $session)->with('status', 'Đã mở buổi học và tạo danh sách điểm danh theo roster có hiệu lực.');
    }

    public function show(AttendanceSession $attendanceSession): View
    {
        $this->authorizeSection($attendanceSession->courseSection, false);
        $user = auth()->user();
        $attendanceSession->load([
            'courseSection.course',
            'courseSection.lecturer',
            'attendanceRecords.learner',
            'attendanceRecords.recordedBy',
            'attendanceRecords.changes.actor',
        ]);

        $canWriteAttendance = $user->canManageTraining()
            || $user->isAssignedToLecturer($attendanceSession->courseSection->lecturer_id);
        $canEditAttendance = $canWriteAttendance && ($attendanceSession->status === AttendanceSession::STATUS_OPEN
            || ($attendanceSession->editable_until && now()->lte($attendanceSession->editable_until)));
        $canRequestAttendanceCorrection = $canWriteAttendance
            && $attendanceSession->status === AttendanceSession::STATUS_CONFIRMED
            && ! $canEditAttendance;

        return view('work.attendance-sessions.show', compact('attendanceSession', 'canEditAttendance', 'canRequestAttendanceCorrection'));
    }

    public function markAllPresent(AttendanceSession $attendanceSession): RedirectResponse
    {
        $this->authorizeSection($attendanceSession->courseSection, true);
        abort_if($attendanceSession->status !== AttendanceSession::STATUS_OPEN, 409, 'Buổi học đã xác nhận và không nhận chỉnh sửa trực tiếp.');

        $pendingLearners = $attendanceSession->attendanceRecords()
            ->where('status', 'unconfirmed')
            ->pluck('learner_id');
        $this->applyStatuses($attendanceSession, $pendingLearners->mapWithKeys(fn ($id): array => [$id => 'present'])->all(), 'Đánh dấu toàn bộ người học có mặt.');

        return back()->with('status', 'Đã đánh dấu có mặt cho các trường hợp chưa xác nhận.');
    }

    public function updateRecords(Request $request, AttendanceSession $attendanceSession): RedirectResponse
    {
        $this->authorizeSection($attendanceSession->courseSection, true);
        $canEditDirectly = $attendanceSession->status === AttendanceSession::STATUS_OPEN
            || ($attendanceSession->status === AttendanceSession::STATUS_CONFIRMED
                && $attendanceSession->editable_until
                && now()->lte($attendanceSession->editable_until));

        $validated = $request->validate([
            'records' => ['required', 'array', 'min:1'],
            'records.*' => ['required', Rule::in(AttendanceRecord::STATUSES)],
            'reason' => [$canEditDirectly ? 'nullable' : 'required', 'string', 'max:1000'],
        ]);

        if (! $canEditDirectly) {
            $this->submitCorrectionRequests($request, $attendanceSession, $validated);

            return back()->with('status', 'Đã gửi đề nghị điều chỉnh sau hạn để người có thẩm quyền phê duyệt.');
        }

        $this->applyStatuses($attendanceSession, $validated['records'], $validated['reason'] ?? null);

        return back()->with('status', 'Đã lưu tình trạng điểm danh và lịch sử thay đổi.');
    }

    public function confirm(AttendanceSession $attendanceSession): RedirectResponse
    {
        $this->authorizeSection($attendanceSession->courseSection, true);

        DB::transaction(function () use ($attendanceSession): void {
            $lockedSession = AttendanceSession::whereKey($attendanceSession->id)->lockForUpdate()->firstOrFail();
            abort_if($lockedSession->status !== AttendanceSession::STATUS_OPEN, 409, 'Buổi học đã được xác nhận.');
            abort_if(! $lockedSession->attendanceRecords()->exists(), 409, 'Không có người học trong roster tại ngày diễn ra buổi học.');
            abort_if($lockedSession->attendanceRecords()->where('status', 'unconfirmed')->exists(), 409, 'Hãy xác định trạng thái của tất cả người học trước khi xác nhận.');

            $lockedSession->update([
                'status' => AttendanceSession::STATUS_CONFIRMED,
                'confirmed_by' => auth()->id(),
                'confirmed_at' => now(),
                'editable_until' => now()->addHours((float) (AttendanceAlertSetting::where('rule_key', 'attendance_edit_hours')->where('is_enabled', true)->value('threshold') ?? 24)),
            ]);
        });

        return back()->with('status', 'Đã xác nhận buổi học và chuyển dữ liệu cho đơn vị đào tạo.');
    }

    private function applyStatuses(AttendanceSession $session, array $statuses, ?string $reason): void
    {
        DB::transaction(function () use ($session, $statuses, $reason): void {
            $lockedSession = AttendanceSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            $canEditDirectly = $lockedSession->status === AttendanceSession::STATUS_OPEN
                || ($lockedSession->status === AttendanceSession::STATUS_CONFIRMED
                    && $lockedSession->editable_until
                    && now()->lte($lockedSession->editable_until));
            abort_unless($canEditDirectly, 409, 'Đã quá hạn sửa trực tiếp; hãy gửi đề nghị điều chỉnh để được phê duyệt.');

            $records = $lockedSession->attendanceRecords()
                ->whereIn('learner_id', array_keys($statuses))
                ->lockForUpdate()
                ->get()
                ->keyBy('learner_id');

            if ($records->count() !== count($statuses)) {
                throw ValidationException::withMessages(['records' => 'Danh sách có người học không thuộc roster của buổi học này.']);
            }

            foreach ($statuses as $learnerId => $status) {
                $record = $records->get($learnerId);
                if ($record->status === $status) {
                    continue;
                }

                $record->changes()->create([
                    'actor_id' => auth()->id(),
                    'before_status' => $record->status,
                    'after_status' => $status,
                    'reason' => $reason,
                ]);
                $record->update([
                    'status' => $status,
                    'recorded_by' => auth()->id(),
                    'marked_at' => now(),
                ]);
            }
        });
    }

    private function submitCorrectionRequests(Request $request, AttendanceSession $session, array $validated): void
    {
        abort_unless($session->status === AttendanceSession::STATUS_CONFIRMED, 409, 'Chỉ buổi đã xác nhận mới nhận đề nghị điều chỉnh sau hạn.');
        DB::transaction(function () use ($request, $session, $validated): void {
            $lockedSession = AttendanceSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            abort_if($lockedSession->status !== AttendanceSession::STATUS_CONFIRMED, 409, 'Buổi học chưa được xác nhận.');

            $records = $lockedSession->attendanceRecords()
                ->whereIn('learner_id', array_keys($validated['records']))
                ->lockForUpdate()
                ->get()
                ->keyBy('learner_id');
            if ($records->count() !== count($validated['records'])) {
                throw ValidationException::withMessages(['records' => 'Danh sách có người học không thuộc roster của buổi học này.']);
            }

            foreach ($validated['records'] as $learnerId => $requestedStatus) {
                $record = $records->get($learnerId);
                if ($record->status === $requestedStatus) {
                    continue;
                }
                abort_if(StudentCorrectionRequest::where('attendance_record_id', $record->id)->where('status', 'pending')->exists(), 409, 'Đã có yêu cầu đang chờ xử lý cho người học này.');
                StudentCorrectionRequest::create([
                    'learner_id' => $record->learner_id,
                    'attendance_record_id' => $record->id,
                    'requested_by' => $request->user()->id,
                    'requester_type' => $request->user()->role,
                    'original_status' => $record->status,
                    'requested_status' => $requestedStatus,
                    'reason' => $validated['reason'],
                    'status' => 'pending',
                ]);
            }
        });
    }

    private function authorizeSection(CourseSection $courseSection, bool $write): void
    {
        $user = auth()->user();
        $isAssignedLecturer = $user->role === 'lecturer'
            && $user->isAssignedToLecturer($courseSection->lecturer_id);
        $canReadUnit = ! $write && $user->canAccessTrainingDepartment($courseSection->department_id);

        abort_unless($user->canManageTraining() || $isAssignedLecturer || $canReadUnit, 403);
    }
}
