<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\AssessmentCorrectionRequest;
use App\Models\AssessmentResult;
use App\Models\StudentCorrectionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentPortalController extends Controller
{
    public function index(Request $request): View
    {
        $learner = $this->signedInLearner($request);
        $attendanceRecords = AttendanceRecord::query()
            ->with(['attendanceSession.courseSection.course', 'attendanceSession.courseSection.lecturer'])
            ->where('learner_id', $learner->id)
            ->join('attendance_sessions', 'attendance_records.attendance_session_id', '=', 'attendance_sessions.id')
            ->orderByDesc('attendance_sessions.session_date')
            ->select('attendance_records.*')
            ->get();
        $correctionRequests = StudentCorrectionRequest::where('learner_id', $learner->id)
            ->with('attendanceRecord.attendanceSession.courseSection.course')
            ->orderByDesc('created_at')
            ->get();
        $assessmentResults = AssessmentResult::where('learner_id', $learner->id)
            ->with(['assessment.courseSection.course', 'correctionRequests'])
            ->orderByDesc('assessment_id')
            ->get();
        $assessmentCorrectionRequests = AssessmentCorrectionRequest::where('learner_id', $learner->id)->get()->keyBy('assessment_result_id');

        return view('student.attendance', compact('learner', 'attendanceRecords', 'correctionRequests', 'assessmentResults', 'assessmentCorrectionRequests'));
    }

    public function requestCorrection(Request $request, AttendanceRecord $attendanceRecord): RedirectResponse
    {
        $learner = $this->signedInLearner($request);
        abort_unless($attendanceRecord->learner_id === $learner->id, 404);
        abort_unless($attendanceRecord->attendanceSession->status === 'confirmed', 409, 'Chỉ có thể đề nghị chỉnh sửa sau khi buổi học đã được xác nhận.');

        $validated = $request->validate([
            'requested_status' => ['required', Rule::in(['present', 'late', 'excused', 'unexcused', 'other'])],
            'reason' => ['required', 'string', 'min:8', 'max:1000'],
        ]);

        abort_if(StudentCorrectionRequest::where('attendance_record_id', $attendanceRecord->id)->where('status', 'pending')->exists(), 409, 'Đã có đề nghị đang chờ xử lý cho bản ghi này.');

        StudentCorrectionRequest::create([
            'learner_id' => $learner->id,
            'attendance_record_id' => $attendanceRecord->id,
            'requested_by' => $request->user()->id,
            'requester_type' => 'learner',
            'original_status' => $attendanceRecord->status,
            'requested_status' => $validated['requested_status'],
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return back()->with('status', 'Đã gửi đề nghị kiểm tra; trạng thái chuyên cần chỉ đổi sau khi được duyệt.');
    }

    private function signedInLearner(Request $request): \App\Models\Learner
    {
        abort_unless($request->user()->role === 'learner' && $request->user()->learner_id, 403);

        return $request->user()->learner()->firstOrFail();
    }
}
