<?php

namespace App\Http\Controllers;

use App\Models\AttendanceAlert;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\CourseSection;
use App\Models\Learner;
use App\Services\AttendanceAlertService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, AttendanceAlertService $alertService): View|RedirectResponse
    {
        $user = $request->user();
        if ($user->role === 'learner') {
            return redirect()->route('student.attendance.index');
        }
        if ($user->canManageTraining()) {
            $alertService->refresh($user);
        }
        $today = now()->toDateString();
        $lecturerId = $user->lecturerProfileId() ?? 0;
        $sectionsToday = CourseSection::query()
            ->with(['course', 'lecturer'])
            ->where('is_active', true)
            ->where('weekday', now()->isoWeekday())
            ->whereDate('starts_on', '<=', $today)
            ->whereDate('ends_on', '>=', $today)
            ->when($user->role === 'lecturer', fn (Builder $query) => $query->where('lecturer_id', $lecturerId))
            ->when(in_array($user->role, ['unit_leader', 'student_affairs'], true), fn (Builder $query) => $query->where('department_id', $user->department_id))
            ->orderBy('start_time')
            ->get();
        $sectionIds = $sectionsToday->modelKeys();
        $todaySessions = AttendanceSession::query()
            ->with('courseSection')
            ->withCount('attendanceRecords')
            ->whereDate('session_date', $today)
            ->whereIn('course_section_id', $sectionIds)
            ->orderBy('start_time')
            ->get();
        $sessionIds = $todaySessions->modelKeys();
        $todayRecords = AttendanceRecord::whereIn('attendance_session_id', $sessionIds);
        $startedSectionIds = $todaySessions->pluck('course_section_id')->unique();

        $activeLearners = $user->role === 'lecturer'
            ? $sectionsToday->flatMap(fn (CourseSection $section) => $section->learners()->where('study_status', 'active')->pluck('learners.id'))->unique()->count()
            : Learner::where('study_status', 'active')->when(in_array($user->role, ['unit_leader', 'student_affairs'], true), fn (Builder $query) => $query->where('department_id', $user->department_id))->count();

        $alertQuery = AttendanceAlert::whereIn('status', ['open', 'in_progress']);
        if ($user->role === 'lecturer') {
            $alertQuery->whereHas('courseSection', fn (Builder $query) => $query->where('lecturer_id', $lecturerId));
        } elseif (in_array($user->role, ['unit_leader', 'student_affairs'], true)) {
            $alertQuery->where(function (Builder $query) use ($user): void {
                $query->whereHas('learner', fn (Builder $learner) => $learner->where('department_id', $user->department_id))
                    ->orWhereHas('courseSection', fn (Builder $section) => $section->where('department_id', $user->department_id));
            });
        }

        $alerts = $alertQuery->with(['learner', 'courseSection'])->orderByDesc('severity')->orderByDesc('last_detected_at')->limit(5)->get();
        $unmarkedSessions = AttendanceSession::where('status', AttendanceSession::STATUS_OPEN)
            ->whereDate('session_date', '<', $today)
            ->when($user->role === 'lecturer', fn (Builder $query) => $query->whereHas('courseSection', fn (Builder $section) => $section->where('lecturer_id', $lecturerId)))
            ->when(in_array($user->role, ['unit_leader', 'student_affairs'], true), fn (Builder $query) => $query->whereHas('courseSection', fn (Builder $section) => $section->where('department_id', $user->department_id)))
            ->count();

        return view('dashboard', [
            'today' => now(),
            'activeLearnerCount' => $activeLearners,
            'scheduledSectionCount' => $sectionsToday->count(),
            'startedSectionCount' => $startedSectionIds->count(),
            'unopenedSectionCount' => max(0, $sectionsToday->count() - $startedSectionIds->count()),
            'presentCount' => (clone $todayRecords)->where('status', 'present')->count(),
            'lateCount' => (clone $todayRecords)->where('status', 'late')->count(),
            'excusedCount' => (clone $todayRecords)->where('status', 'excused')->count(),
            'unexcusedCount' => (clone $todayRecords)->where('status', 'unexcused')->count(),
            'otherCount' => (clone $todayRecords)->where('status', 'other')->count(),
            'openAlertCount' => (clone $alertQuery)->count(),
            'unmarkedSessions' => $unmarkedSessions,
            'todaySections' => $sectionsToday,
            'todaySessions' => $todaySessions->keyBy('course_section_id'),
            'recentAlerts' => $alerts,
        ]);
    }
}
