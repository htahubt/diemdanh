<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Department;
use App\Models\Learner;
use App\Models\Lecturer;
use Illuminate\Database\Seeder;
use RuntimeException;

class CourseSectionSeeder extends Seeder
{
    public function run(): void
    {
        $lecturers = Lecturer::query()
            ->where('status', 'active')
            ->where('profile_status', 'approved')
            ->orderBy('lecturer_code')
            ->get()
            ->keyBy('lecturer_code');
        $department = Department::where('name', 'Khoa Công nghệ thông tin')->first();

        if (! $department) {
            throw new RuntimeException('Hãy chạy LecturerSeeder trước CourseSectionSeeder.');
        }

        $samples = [
            ['course' => 'IT101', 'section' => 'IT101-01', 'lecturer' => 'GV-MAU-001', 'capacity' => 40, 'weekday' => 3, 'start' => '08:00', 'end' => '10:00'],
            ['course' => 'IT101', 'section' => 'IT101-02', 'lecturer' => 'GV-MAU-002', 'capacity' => 40, 'weekday' => 3, 'start' => '10:00', 'end' => '12:00'],
            ['course' => 'IT102', 'section' => 'IT102-01', 'lecturer' => 'GV-MAU-001', 'capacity' => 45, 'weekday' => 1, 'start' => '08:00', 'end' => '10:00'],
            ['course' => 'IT201', 'section' => 'IT201-01', 'lecturer' => 'GV-MAU-002', 'capacity' => 35, 'weekday' => 3, 'start' => '13:00', 'end' => '15:00'],
            ['course' => 'IT201', 'section' => 'IT201-02', 'lecturer' => 'GV-MAU-001', 'capacity' => 35, 'weekday' => 4, 'start' => '08:00', 'end' => '10:00'],
            ['course' => 'IT202', 'section' => 'IT202-01', 'lecturer' => 'GV-MAU-001', 'capacity' => 40, 'weekday' => 4, 'start' => '10:00', 'end' => '12:00'],
            ['course' => 'IT203', 'section' => 'IT203-01', 'lecturer' => 'GV-MAU-002', 'capacity' => 40, 'weekday' => 2, 'start' => '08:00', 'end' => '10:00'],
            ['course' => 'IT204', 'section' => 'IT204-01', 'lecturer' => 'GV-MAU-001', 'capacity' => 35, 'weekday' => 2, 'start' => '10:00', 'end' => '12:00'],
            ['course' => 'IT301', 'section' => 'IT301-01', 'lecturer' => 'GV-MAU-002', 'capacity' => 35, 'weekday' => 4, 'start' => '13:00', 'end' => '15:00'],
            ['course' => 'IT302', 'section' => 'IT302-01', 'lecturer' => 'GV-MAU-001', 'capacity' => 35, 'weekday' => 5, 'start' => '08:00', 'end' => '10:00'],
            ['course' => 'IT303', 'section' => 'IT303-01', 'lecturer' => 'GV-MAU-002', 'capacity' => 35, 'weekday' => 5, 'start' => '10:00', 'end' => '12:00'],
            ['course' => 'IT401', 'section' => 'IT401-01', 'lecturer' => 'GV-MAU-001', 'capacity' => 30, 'weekday' => 5, 'start' => '13:00', 'end' => '15:00'],
        ];

        foreach ($samples as $sample) {
            $course = Course::where('code', $sample['course'])->first();
            $lecturer = $lecturers->get($sample['lecturer']);

            if (! $course || ! $lecturer) {
                throw new RuntimeException('Hãy chạy CourseSeeder và LecturerSeeder trước CourseSectionSeeder.');
            }

            CourseSection::updateOrCreate(
                [
                    'course_id' => $course->id,
                    'section_code' => $sample['section'],
                    'academic_year' => '2026-2027',
                    'semester' => 1,
                ],
                [
                    'lecturer_id' => $lecturer->id,
                    'department_id' => $department->id,
                    'training_format' => 'Chính quy',
                    'weekday' => $sample['weekday'],
                    'start_time' => $sample['start'],
                    'end_time' => $sample['end'],
                    'room' => 'A-201',
                    'expected_sessions' => 15,
                    'starts_on' => '2026-10-05',
                    'ends_on' => '2027-01-31',
                    'capacity' => $sample['capacity'],
                    'is_active' => true,
                ],
            );
        }

        $this->command?->info('Đã thêm hoặc cập nhật 12 lớp học phần mẫu cho học kỳ 1 năm học 2026-2027.');

        $sampleEnrollments = [
            'SV-MAU-001' => ['IT201-01', 'IT202-01', 'IT301-01'],
            'SV-MAU-002' => ['IT202-01', 'IT203-01', 'IT302-01'],
            'SV-MAU-003' => ['IT101-01', 'IT201-02', 'IT204-01'],
            'SV-MAU-005' => ['IT202-01', 'IT301-01', 'IT401-01'],
            'SV-MAU-007' => ['IT101-02', 'IT102-01'],
            'SV-MAU-008' => ['IT201-01', 'IT202-01'],
            'SV-MAU-009' => ['IT203-01', 'IT204-01', 'IT302-01'],
        ];

        foreach ($sampleEnrollments as $studentCode => $sectionCodes) {
            $learner = Learner::where('student_code', $studentCode)->first();
            if (! $learner) {
                continue;
            }

            $sectionIds = CourseSection::whereIn('section_code', $sectionCodes)
                ->where('academic_year', '2026-2027')
                ->where('semester', 1)
                ->pluck('id');
            $pivot = $sectionIds->mapWithKeys(fn (int $sectionId): array => [
                $sectionId => ['effective_from' => '2026-10-05', 'is_new' => $studentCode === 'SV-MAU-008'],
            ])->all();
            $learner->courseSections()->syncWithoutDetaching($pivot);
        }
    }
}
