<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\CourseSection;
use App\Models\User;
use Illuminate\Database\Seeder;

class AttendanceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $section = CourseSection::where('section_code', 'IT201-01')
            ->where('academic_year', '2026-2027')
            ->where('semester', 1)
            ->first();

        if (! $section) {
            $this->command?->warn('Bỏ qua dữ liệu điểm danh mẫu: chưa có lớp IT201-01.');

            return;
        }

        $actorId = User::where('email', 'training.office@example.test')->value('id');
        $samples = [
            [
                'number' => 1,
                'date' => '2026-10-07',
                'status' => AttendanceSession::STATUS_CONFIRMED,
                'confirmed' => true,
                'records' => ['SV-MAU-001' => 'present', 'SV-MAU-008' => 'late'],
            ],
            [
                'number' => 2,
                'date' => '2026-10-14',
                'status' => AttendanceSession::STATUS_OPEN,
                'confirmed' => false,
                'records' => [],
            ],
        ];

        foreach ($samples as $sample) {
            $session = AttendanceSession::firstOrCreate(
                [
                    'course_section_id' => $section->id,
                    'session_number' => $sample['number'],
                ],
                [
                    'session_date' => $sample['date'],
                    'start_time' => $section->start_time,
                    'end_time' => $section->end_time,
                    'room' => $section->room,
                    'status' => $sample['status'],
                    'opened_by' => $actorId,
                    'opened_at' => now(),
                    'confirmed_by' => $sample['confirmed'] ? $actorId : null,
                    'confirmed_at' => $sample['confirmed'] ? now() : null,
                ],
            );

            $learners = $section->learners()
                ->where('learners.study_status', 'active')
                ->where(fn ($query) => $query
                    ->whereNull('course_section_learner.effective_from')
                    ->orWhereDate('course_section_learner.effective_from', '<=', $sample['date']))
                ->get(['learners.id', 'learners.student_code']);

            foreach ($learners as $learner) {
                $status = $sample['records'][$learner->student_code] ?? 'unconfirmed';
                $record = AttendanceRecord::firstOrCreate(
                    ['attendance_session_id' => $session->id, 'learner_id' => $learner->id],
                    [
                        'status' => $status,
                        'recorded_by' => $status === 'unconfirmed' ? null : $actorId,
                        'marked_at' => $status === 'unconfirmed' ? null : now(),
                    ],
                );

                if ($record->wasRecentlyCreated && $status === 'late') {
                    $record->changes()->create([
                        'actor_id' => $actorId,
                        'before_status' => 'unconfirmed',
                        'after_status' => 'late',
                        'reason' => 'Dữ liệu điểm danh minh họa.',
                    ]);
                }
            }
        }

        $this->command?->info('Đã thêm hoặc xác nhận lại dữ liệu điểm danh mẫu cho lớp IT201-01.');
    }
}
