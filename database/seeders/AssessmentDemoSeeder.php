<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\CourseSection;
use App\Models\Learner;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class AssessmentDemoSeeder extends Seeder
{
    public function run(): void
    {
        $section = CourseSection::where('section_code', 'IT201-01')
            ->where('academic_year', '2026-2027')
            ->where('semester', 1)
            ->first();
        if (! $section) {
            throw new RuntimeException('Hãy chạy CourseSectionSeeder trước AssessmentDemoSeeder.');
        }

        $lecturerId = User::where('email', $section->lecturer?->email)->value('id');
        $assessment = Assessment::firstOrCreate(
            ['course_section_id' => $section->id, 'title' => 'Kiểm tra giữa kỳ'],
            [
                'kind' => 'midterm',
                'max_score' => 10,
                'weight' => 40,
                'edit_until' => '2026-11-15',
                'created_by' => null,
            ],
        );

        $sampleScores = [
            'SV-MAU-001' => ['status' => 'graded', 'score' => 8.50],
            'SV-MAU-008' => ['status' => 'absent', 'score' => null],
        ];

        foreach ($sampleScores as $studentCode => $sample) {
            $learner = Learner::where('student_code', $studentCode)->first();
            if (! $learner || ! $section->learners()->whereKey($learner->id)->exists()) {
                continue;
            }

            $result = AssessmentResult::firstOrCreate(
                ['assessment_id' => $assessment->id, 'learner_id' => $learner->id],
                [
                    'status' => $sample['status'],
                    'score' => $sample['score'],
                    'entered_by' => $lecturerId,
                    'entered_at' => now(),
                ],
            );
            if ($result->wasRecentlyCreated) {
                $result->changes()->create([
                    'actor_id' => $lecturerId,
                    'before_status' => 'not_entered',
                    'before_score' => null,
                    'after_status' => $sample['status'],
                    'after_score' => $sample['score'],
                    'reason' => 'Dữ liệu điểm kiểm tra minh họa.',
                ]);
            }
        }

        $this->command?->info('Đã thêm hoặc xác nhận dữ liệu điểm kiểm tra mẫu cho lớp IT201-01.');
    }
}
