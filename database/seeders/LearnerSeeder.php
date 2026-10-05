<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Learner;
use Illuminate\Database\Seeder;

class LearnerSeeder extends Seeder
{
    public function run(): void
    {
        $department = Department::firstOrCreate(
            ['name' => 'Khoa Công nghệ thông tin'],
            ['code' => 'DV-DEMO-CNTT', 'is_active' => true],
        );

        $learners = [
            ['student_code' => 'SV-MAU-001', 'full_name' => 'Nguyễn Minh Khôi', 'date_of_birth' => '2005-03-14', 'cohort' => '2023', 'major' => 'Công nghệ thông tin', 'class_name' => 'CNTT-K23A', 'study_status' => 'active', 'phone' => '0900001001', 'email' => 'minh.khoi.demo@example.test'],
            ['student_code' => 'SV-MAU-002', 'full_name' => 'Trần Ngọc Mai', 'date_of_birth' => '2004-11-02', 'cohort' => '2022', 'major' => 'Kỹ thuật phần mềm', 'class_name' => 'KTPM-K22A', 'study_status' => 'active', 'phone' => '0900001002', 'email' => 'ngoc.mai.demo@example.test'],
            ['student_code' => 'SV-MAU-003', 'full_name' => 'Lê Hoàng Nam', 'date_of_birth' => '2005-07-21', 'cohort' => '2023', 'major' => 'Công nghệ thông tin', 'class_name' => 'CNTT-K23B', 'study_status' => 'active', 'phone' => '0900001003', 'email' => 'hoang.nam.demo@example.test'],
            ['student_code' => 'SV-MAU-004', 'full_name' => 'Phạm Thu Trang', 'date_of_birth' => '2003-01-09', 'cohort' => '2021', 'major' => 'Hệ thống thông tin', 'class_name' => 'HTTT-K21A', 'study_status' => 'paused', 'phone' => '0900001004', 'email' => 'thu.trang.demo@example.test'],
            ['student_code' => 'SV-MAU-005', 'full_name' => 'Đỗ Đức Anh', 'date_of_birth' => '2004-08-30', 'cohort' => '2022', 'major' => 'Kỹ thuật phần mềm', 'class_name' => 'KTPM-K22B', 'study_status' => 'active', 'phone' => '0900001005', 'email' => 'duc.anh.demo@example.test'],
            ['student_code' => 'SV-MAU-006', 'full_name' => 'Vũ Khánh Linh', 'date_of_birth' => '2002-05-18', 'cohort' => '2020', 'major' => 'Công nghệ thông tin', 'class_name' => 'CNTT-K20A', 'study_status' => 'graduated', 'phone' => '0900001006', 'email' => 'khanh.linh.demo@example.test'],
            ['student_code' => 'SV-MAU-007', 'full_name' => 'Bùi Gia Huy', 'date_of_birth' => '2006-12-07', 'cohort' => '2024', 'major' => 'An toàn thông tin', 'class_name' => 'ATTT-K24A', 'study_status' => 'active', 'phone' => null, 'email' => 'gia.huy.demo@example.test'],
            ['student_code' => 'SV-MAU-008', 'full_name' => 'Hoàng Bảo Ngọc', 'date_of_birth' => null, 'cohort' => '2023', 'major' => 'Công nghệ thông tin', 'class_name' => 'CNTT-K23A', 'study_status' => 'active', 'phone' => '0900001008', 'email' => null],
            ['student_code' => 'SV-MAU-009', 'full_name' => 'Đặng Quang Hưng', 'date_of_birth' => '2005-10-11', 'cohort' => '2023', 'major' => 'Kỹ thuật phần mềm', 'class_name' => 'KTPM-K23A', 'study_status' => 'active', 'phone' => '0900001009', 'email' => 'quang.hung.demo@example.test'],
            ['student_code' => 'SV-MAU-010', 'full_name' => 'Nguyễn Hải Yến', 'date_of_birth' => '2004-02-26', 'cohort' => '2022', 'major' => 'Hệ thống thông tin', 'class_name' => 'HTTT-K22A', 'study_status' => 'inactive', 'phone' => '0900001010', 'email' => 'hai.yen.demo@example.test'],
        ];

        foreach ($learners as $learner) {
            Learner::updateOrCreate(
                ['student_code' => $learner['student_code']],
                $learner + ['department_id' => $department->id, 'is_active' => true],
            );
        }

        $this->command?->info('Đã thêm hoặc cập nhật 10 hồ sơ người học mẫu.');
    }
}
