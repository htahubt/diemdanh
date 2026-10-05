<?php

namespace App\Services;

use App\Models\AttendanceAlert;
use App\Models\AttendanceAlertHistory;
use App\Models\AttendanceAlertSetting;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\CourseSection;
use App\Models\Learner;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AttendanceAlertService
{
    public function refresh(User $actor): int
    {
        $this->ensureDefaultSettings();
        $settings = AttendanceAlertSetting::where('is_enabled', true)->get()->keyBy('rule_key');
        $now = now();
        $currentFingerprints = [];

        $this->detectUnassignedLearners($now, $currentFingerprints);
        $this->detectMissingClassSetup($now, $currentFingerprints);
        $this->detectOverdueSessions($now, $settings, $currentFingerprints);
        $this->detectAbsences($now, $settings, $currentFingerprints);

        $staleAlerts = AttendanceAlert::whereIn('status', ['open', 'in_progress']);
        if ($currentFingerprints) {
            $staleAlerts->whereNotIn('fingerprint', array_keys($currentFingerprints));
        }

        foreach ($staleAlerts->get() as $alert) {
            $beforeStatus = $alert->status;
            $alert->update([
                'status' => 'resolved',
                'resolved_by' => $actor->id,
                'resolved_at' => $now,
                'resolution_note' => 'Tự đóng: điều kiện cảnh báo không còn tồn tại.',
            ]);
            $alert->histories()->create([
                'actor_id' => $actor->id,
                'before_status' => $beforeStatus,
                'after_status' => 'resolved',
                'action_note' => $alert->resolution_note,
            ]);
        }

        return count($currentFingerprints);
    }

    private function detectUnassignedLearners($now, array &$fingerprints): void
    {
        $academicYear = $now->year . '-' . ($now->year + 1);
        $today = $now->toDateString();
        $learners = Learner::query()
            ->where('study_status', 'active')
            ->whereDoesntHave('courseSections', fn ($query) => $query
                ->where('academic_year', $academicYear)
                ->where('course_sections.is_active', true)
                ->where(fn ($query) => $query->whereNull('course_section_learner.effective_from')->orWhereDate('course_section_learner.effective_from', '<=', $today))
                ->where(fn ($query) => $query->whereNull('course_section_learner.effective_until')->orWhereDate('course_section_learner.effective_until', '>=', $today)))
            ->get();

        foreach ($learners as $learner) {
            $fingerprint = "unassigned-learner:{$academicYear}:{$learner->id}";
            $fingerprints[$fingerprint] = true;
            $this->touchAlert($fingerprint, [
                'alert_type' => 'active_learner_unassigned',
                'severity' => 'warning',
                'title' => 'Người học chưa được xếp lớp học phần',
                'description' => "{$learner->full_name} ({$learner->student_code}) đang học nhưng chưa có ghi danh trong năm học {$academicYear}.",
                'learner_id' => $learner->id,
                'learner_code' => $learner->student_code,
                'details' => ['academic_year' => $academicYear],
            ], $now);
        }
    }

    private function detectMissingClassSetup($now, array &$fingerprints): void
    {
        $sections = CourseSection::query()
            ->with('lecturer')
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('lecturer_id')
                    ->orWhereNull('department_id')
                    ->orWhereNull('weekday')
                    ->orWhereNull('start_time')
                    ->orWhereNull('room')
                    ->orWhereNull('starts_on')
                    ->orWhereNull('ends_on');
            })
            ->get();

        foreach ($sections as $section) {
            $missing = [];
            foreach (['lecturer_id' => 'giảng viên', 'department_id' => 'đơn vị quản lý', 'weekday' => 'thứ học', 'start_time' => 'giờ học', 'room' => 'phòng học', 'starts_on' => 'ngày bắt đầu', 'ends_on' => 'ngày kết thúc'] as $field => $label) {
                if (! $section->{$field}) {
                    $missing[] = $label;
                }
            }
            $fingerprint = "incomplete-section:{$section->id}";
            $fingerprints[$fingerprint] = true;
            $this->touchAlert($fingerprint, [
                'alert_type' => 'incomplete_course_section',
                'severity' => 'critical',
                'title' => 'Lớp học phần thiếu thông tin tổ chức',
                'description' => "Lớp {$section->section_code} thiếu: " . implode(', ', $missing) . '.',
                'course_section_id' => $section->id,
                'section_code' => $section->section_code,
                'details' => ['missing' => $missing],
            ], $now);
        }
    }

    private function detectOverdueSessions($now, $settings, array &$fingerprints): void
    {
        $setting = $settings->get('unconfirmed_session_hours');
        if (! $setting) {
            return;
        }
        $hours = (float) $setting->threshold;
        $sessions = AttendanceSession::query()
            ->with('courseSection')
            ->where('status', AttendanceSession::STATUS_OPEN)
            ->get()
            ->filter(fn (AttendanceSession $session): bool => $session->session_date->copy()->setTimeFromTimeString($session->end_time)->diffInHours($now, false) >= $hours);

        foreach ($sessions as $session) {
            $fingerprint = "unconfirmed-session:{$session->id}";
            $fingerprints[$fingerprint] = true;
            $this->touchAlert($fingerprint, [
                'alert_type' => 'lecturer_unconfirmed_session',
                'severity' => 'warning',
                'title' => 'Buổi học chưa được xác nhận',
                'description' => "Lớp {$session->courseSection->section_code}, buổi {$session->session_number} chưa xác nhận sau khi kết thúc.",
                'course_section_id' => $session->course_section_id,
                'section_code' => $session->courseSection->section_code,
                'attendance_session_id' => $session->id,
                'details' => ['session_date' => $session->session_date->toDateString()],
            ], $now);
        }
    }

    private function detectAbsences($now, $settings, array &$fingerprints): void
    {
        $countSetting = $settings->get('unexcused_absence_count');
        $rateSetting = $settings->get('unexcused_absence_rate');
        if (! $countSetting && ! $rateSetting) {
            return;
        }
        $minCount = $countSetting ? (int) $countSetting->threshold : PHP_INT_MAX;
        $minRate = $rateSetting ? (float) $rateSetting->threshold : 101;
        $groups = AttendanceRecord::query()
            ->join('attendance_sessions', 'attendance_records.attendance_session_id', '=', 'attendance_sessions.id')
            ->where('attendance_sessions.status', AttendanceSession::STATUS_CONFIRMED)
            ->select('attendance_sessions.course_section_id', 'attendance_records.learner_id')
            ->selectRaw('COUNT(*) as total_sessions')
            ->selectRaw("SUM(CASE WHEN attendance_records.status = 'unexcused' THEN 1 ELSE 0 END) as absent_count")
            ->groupBy('attendance_sessions.course_section_id', 'attendance_records.learner_id')
            ->get();

        foreach ($groups as $group) {
            $count = (int) $group->absent_count;
            $total = (int) $group->total_sessions;
            $rate = $total ? round($count * 100 / $total, 2) : 0;
            if ($count < $minCount && ($count === 0 || $rate < $minRate)) {
                continue;
            }

            $learner = Learner::find($group->learner_id);
            $section = CourseSection::find($group->course_section_id);
            if (! $learner || ! $section) {
                continue;
            }
            $fingerprint = "unexcused-absence:{$section->id}:{$learner->id}";
            $fingerprints[$fingerprint] = true;
            $this->touchAlert($fingerprint, [
                'alert_type' => 'unexcused_absence_threshold',
                'severity' => 'critical',
                'title' => 'Người học vắng không phép vượt ngưỡng',
                'description' => "{$learner->full_name} ({$learner->student_code}) vắng không phép {$count}/{$total} buổi ({$rate}%).",
                'learner_id' => $learner->id,
                'learner_code' => $learner->student_code,
                'course_section_id' => $section->id,
                'section_code' => $section->section_code,
                'details' => ['absence_count' => $count, 'total_sessions' => $total, 'absence_rate' => $rate, 'count_threshold' => $minCount, 'rate_threshold' => $minRate],
            ], $now);
        }
    }

    private function touchAlert(string $fingerprint, array $attributes, $now): void
    {
        DB::transaction(function () use ($fingerprint, $attributes, $now): void {
            $alert = AttendanceAlert::where('fingerprint', $fingerprint)->lockForUpdate()->first();
            if (! $alert) {
                AttendanceAlert::create($attributes + [
                    'fingerprint' => $fingerprint,
                    'status' => 'open',
                    'first_detected_at' => $now,
                    'last_detected_at' => $now,
                ]);

                return;
            }

            $beforeStatus = $alert->status;
            $wasResolved = $beforeStatus === 'resolved';
            $alert->fill($attributes);
            $alert->last_detected_at = $now;
            if ($wasResolved) {
                $alert->status = 'open';
                $alert->resolved_by = null;
                $alert->resolved_at = null;
                $alert->resolution_note = null;
            }
            $alert->save();

            if ($wasResolved) {
                $alert->histories()->create([
                    'before_status' => 'resolved',
                    'after_status' => 'open',
                    'action_note' => 'Tự mở lại: điều kiện cảnh báo phát sinh trở lại.',
                ]);
            }
        });
    }

    private function ensureDefaultSettings(): void
    {
        foreach ([
            ['rule_key' => 'unexcused_absence_count', 'label' => 'Số buổi vắng không phép', 'threshold' => 3, 'unit' => 'buổi'],
            ['rule_key' => 'unexcused_absence_rate', 'label' => 'Tỷ lệ vắng không phép', 'threshold' => 25, 'unit' => '%'],
            ['rule_key' => 'unconfirmed_session_hours', 'label' => 'Thời gian nhắc xác nhận buổi', 'threshold' => 24, 'unit' => 'giờ'],
            ['rule_key' => 'attendance_edit_hours', 'label' => 'Thời hạn tự sửa điểm danh', 'threshold' => 24, 'unit' => 'giờ'],
        ] as $setting) {
            AttendanceAlertSetting::firstOrCreate(['rule_key' => $setting['rule_key']], $setting + ['is_enabled' => true]);
        }
    }
}
