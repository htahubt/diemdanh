<?php

namespace App\Http\Controllers;

use App\Models\AttendanceAlert;
use App\Models\AttendanceAlertSetting;
use App\Models\User;
use App\Services\AttendanceAlertService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AttendanceAlertController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeTrainingOffice($request);
        $canManageAll = $request->user()->canManageTraining();
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['open', 'in_progress', 'resolved'])],
            'severity' => ['nullable', Rule::in(['warning', 'critical'])],
        ]);

        $alertsQuery = AttendanceAlert::query()
                ->with(['learner', 'courseSection', 'attendanceSession', 'assignedTo', 'resolvedBy', 'histories.actor'])
                ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
                ->when($filters['severity'] ?? null, fn ($query, $severity) => $query->where('severity', $severity))
                ->when(! $canManageAll, fn (Builder $query) => $query->where(function (Builder $query) use ($request): void {
                    $query->whereHas('learner', fn (Builder $learner) => $learner->where('department_id', $request->user()->department_id))
                        ->orWhereHas('courseSection', fn (Builder $section) => $section->where('department_id', $request->user()->department_id));
                }));

        return view('work.attendance-alerts.index', [
            'alerts' => (clone $alertsQuery)
                ->orderByRaw("CASE WHEN status = 'open' THEN 0 WHEN status = 'in_progress' THEN 1 ELSE 2 END")
                ->orderByDesc('last_detected_at')
                ->paginate(25)
                ->withQueryString(),
            'settings' => $canManageAll ? AttendanceAlertSetting::orderBy('id')->get() : collect(),
            'canRefreshAlerts' => $canManageAll,
            'staff' => User::whereIn('role', ['admin', 'training_office', 'student_affairs'])->when(! $canManageAll, fn ($query) => $query->where('department_id', $request->user()->department_id))->orderBy('name')->get(),
            'openCount' => (clone $alertsQuery)->whereIn('status', ['open', 'in_progress'])->count(),
        ]);
    }

    public function refresh(Request $request, AttendanceAlertService $service): RedirectResponse
    {
        abort_unless($request->user()->canManageTraining(), 403);
        $count = $service->refresh($request->user());

        return back()->with('status', "Đã cập nhật {$count} cảnh báo đang thỏa điều kiện.");
    }

    public function updateSetting(Request $request, AttendanceAlertSetting $setting): RedirectResponse
    {
        abort_unless($request->user()->canManageTraining(), 403);
        $validated = $request->validate([
            'threshold' => ['required', 'numeric', 'min:0', 'max:10000'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);
        $setting->update([
            'threshold' => $validated['threshold'],
            'is_enabled' => $request->boolean('is_enabled'),
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Đã lưu ngưỡng cảnh báo.');
    }

    public function update(Request $request, AttendanceAlert $alert): RedirectResponse
    {
        $this->authorizeTrainingOffice($request);
        abort_unless($request->user()->canAccessTrainingDepartment($alert->course_section_id ? $alert->courseSection()->value('department_id') : $alert->learner()->value('department_id')), 404);
        $validated = $request->validate([
            'status' => ['required', Rule::in(['open', 'in_progress', 'resolved'])],
            'action_note' => ['required', 'string', 'min:5', 'max:2000'],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')->whereIn('role', ['admin', 'training_office', 'student_affairs'])],
        ]);

        $beforeStatus = $alert->status;
        $alert->update([
            'status' => $validated['status'],
            'assigned_to' => $validated['assigned_to'] ?? $alert->assigned_to,
            'resolved_by' => $validated['status'] === 'resolved' ? $request->user()->id : null,
            'resolved_at' => $validated['status'] === 'resolved' ? now() : null,
            'resolution_note' => $validated['status'] === 'resolved' ? $validated['action_note'] : $alert->resolution_note,
        ]);
        $alert->histories()->create([
            'actor_id' => $request->user()->id,
            'before_status' => $beforeStatus,
            'after_status' => $validated['status'],
            'action_note' => $validated['action_note'],
        ]);

        return back()->with('status', 'Đã cập nhật tình trạng xử lý cảnh báo.');
    }

    private function authorizeTrainingOffice(Request $request): void
    {
        abort_unless($request->user()->canManageStudentCases(), 403);
    }
}
