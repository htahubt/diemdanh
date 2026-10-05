<?php

namespace Database\Seeders;

use App\Models\AcademicOption;
use App\Models\Department;
use App\Models\Lecturer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LecturerSeeder extends Seeder
{
    public function run(): void
    {
        $profiles = [
            [
                'lecturer_code' => 'GV-MAU-001',
                'name' => 'Nguyễn Minh An',
                'date_of_birth' => '1987-04-12',
                'gender' => 'male',
                'academic_rank' => 'Phó Giáo sư',
                'academic_title' => 'Tiến sĩ',
                'specialization' => 'Công nghệ phần mềm',
                'department' => 'Khoa Công nghệ thông tin',
                'position' => 'Trưởng bộ môn',
                'email' => 'minh.an.demo@example.com',
                'phone' => '0900000001',
                'national_id' => '000000000001',
                'id_issued_at' => '2021-06-15',
                'id_issued_place' => 'Nơi cấp mẫu',
                'permanent_address' => 'Địa chỉ mẫu 01, Hà Nội',
                'contact_address' => 'Địa chỉ liên hệ mẫu 01, Hà Nội',
                'bank_account_number' => '000000000001',
                'bank_name' => 'Ngân hàng mẫu',
                'bank_branch' => 'Chi nhánh mẫu',
                'bank_account_name' => 'NGUYEN MINH AN',
                'status' => 'active',
                'profile_status' => 'approved',
                'review_note' => 'Hồ sơ mẫu đã được xác nhận.',
            ],
            [
                'lecturer_code' => 'GV-MAU-002',
                'name' => 'Trần Thu Hà',
                'date_of_birth' => '1990-09-23',
                'gender' => 'female',
                'academic_rank' => 'Không có học hàm',
                'academic_title' => 'Thạc sĩ',
                'specialization' => 'Quản trị kinh doanh',
                'department' => 'Khoa Kinh tế',
                'position' => null,
                'email' => 'thu.ha.demo@example.com',
                'phone' => '0900000002',
                'national_id' => '000000000002',
                'id_issued_at' => '2022-02-10',
                'id_issued_place' => 'Nơi cấp mẫu',
                'permanent_address' => 'Địa chỉ mẫu 02, Hà Nội',
                'contact_address' => null,
                'bank_account_number' => '000000000002',
                'bank_name' => 'Ngân hàng mẫu',
                'bank_branch' => null,
                'bank_account_name' => 'TRAN THU HA',
                'status' => 'active',
                'profile_status' => 'approved',
                'review_note' => 'Hồ sơ mẫu đã được xác nhận.',
            ],
            [
                'lecturer_code' => 'GV-MAU-003',
                'name' => 'Lê Quốc Bảo',
                'date_of_birth' => '1984-01-08',
                'gender' => 'male',
                'academic_rank' => 'Phó Giáo sư',
                'academic_title' => 'Tiến sĩ',
                'specialization' => 'Tài chính - ngân hàng',
                'department' => 'Khoa Tài chính',
                'position' => 'Giảng viên',
                'email' => 'quoc.bao.demo@example.com',
                'phone' => '0900000003',
                'national_id' => '000000000003',
                'id_issued_at' => '2020-11-20',
                'id_issued_place' => 'Nơi cấp mẫu',
                'permanent_address' => 'Địa chỉ mẫu 03, Hà Nội',
                'contact_address' => null,
                'bank_account_number' => '000000000003',
                'bank_name' => 'Ngân hàng mẫu',
                'bank_branch' => 'Chi nhánh mẫu',
                'bank_account_name' => 'LE QUOC BAO',
                'status' => 'active',
                'profile_status' => 'submitted',
                'review_note' => null,
            ],
            [
                'lecturer_code' => 'GV-MAU-004',
                'name' => 'Phạm Ngọc Linh',
                'date_of_birth' => '1992-07-17',
                'gender' => 'female',
                'academic_rank' => 'Không có học hàm',
                'academic_title' => 'Thạc sĩ',
                'specialization' => 'Ngôn ngữ Anh',
                'department' => 'Khoa Ngoại ngữ',
                'position' => null,
                'email' => 'ngoc.linh.demo@example.com',
                'phone' => '0900000004',
                'national_id' => '000000000004',
                'id_issued_at' => '2023-03-05',
                'id_issued_place' => 'Nơi cấp mẫu',
                'permanent_address' => 'Địa chỉ mẫu 04, Hà Nội',
                'contact_address' => 'Địa chỉ liên hệ mẫu 04, Hà Nội',
                'bank_account_number' => '000000000004',
                'bank_name' => 'Ngân hàng mẫu',
                'bank_branch' => null,
                'bank_account_name' => 'PHAM NGOC LINH',
                'status' => 'active',
                'profile_status' => 'submitted',
                'review_note' => null,
            ],
            [
                'lecturer_code' => 'GV-MAU-005',
                'name' => 'Đỗ Anh Tuấn',
                'date_of_birth' => '1979-12-02',
                'gender' => 'male',
                'academic_rank' => 'Giáo sư',
                'academic_title' => 'Tiến sĩ',
                'specialization' => 'Luật kinh tế',
                'department' => 'Khoa Luật',
                'position' => 'Cố vấn chuyên môn',
                'email' => 'anh.tuan.demo@example.com',
                'phone' => '0900000005',
                'national_id' => '000000000005',
                'id_issued_at' => '2019-08-14',
                'id_issued_place' => 'Nơi cấp mẫu',
                'permanent_address' => 'Địa chỉ mẫu 05, Hà Nội',
                'contact_address' => null,
                'bank_account_number' => '000000000005',
                'bank_name' => 'Ngân hàng mẫu',
                'bank_branch' => 'Chi nhánh mẫu',
                'bank_account_name' => 'DO ANH TUAN',
                'status' => 'inactive',
                'profile_status' => 'needs_revision',
                'review_note' => 'Vui lòng bổ sung bản chụp văn bằng và kiểm tra lại thông tin liên hệ.',
            ],
        ];

        foreach ($profiles as $profile) {
            $nationalId = $profile['national_id'];
            $profileStatus = $profile['profile_status'];
            $reviewNote = $profile['review_note'];
            $departmentName = $profile['department'];
            $rankName = $profile['academic_rank'];
            $degreeName = $profile['academic_title'];
            $department = Department::firstOrCreate(
                ['name' => $departmentName],
                ['code' => 'DV-' . Str::upper(Str::slug($departmentName)), 'is_active' => true],
            );
            $rank = AcademicOption::firstOrCreate(
                ['type' => 'rank', 'name' => $rankName],
                ['is_active' => true],
            );
            $degree = AcademicOption::firstOrCreate(
                ['type' => 'degree', 'name' => $degreeName],
                ['is_active' => true],
            );
            unset($profile['national_id']);

            $lecturer = Lecturer::updateOrCreate(
                ['lecturer_code' => $profile['lecturer_code']],
                array_merge($profile, [
                    'national_id' => $nationalId,
                    'national_id_hash' => hash_hmac('sha256', $nationalId, config('app.key')),
                    'department_id' => $department->id,
                    'academic_rank_id' => $rank->id,
                    'academic_degree_id' => $degree->id,
                    'submitted_at' => now()->subDays(5),
                    'approved_at' => $profileStatus === 'approved' ? now()->subDays(2) : null,
                    'identity_verified_at' => $profileStatus === 'approved' ? now()->subDays(2) : null,
                    'bank_verified_at' => $profileStatus === 'approved' ? now()->subDays(2) : null,
                    'review_note' => $reviewNote,
                ]),
            );

            $lecturer->history()->firstOrCreate(
                ['action' => 'submitted'],
                ['changes' => ['profile_status' => ['to' => 'submitted']], 'note' => 'Hồ sơ mẫu được gửi để kiểm tra.'],
            );

            if ($profileStatus === 'approved') {
                $lecturer->history()->firstOrCreate(
                    ['action' => 'approved'],
                    ['changes' => ['profile_status' => ['from' => 'submitted', 'to' => 'approved']], 'note' => 'Hồ sơ mẫu đã được xác nhận.'],
                );
            } elseif ($profileStatus === 'needs_revision') {
                $lecturer->history()->firstOrCreate(
                    ['action' => 'revision_requested'],
                    ['changes' => ['profile_status' => ['from' => 'submitted', 'to' => 'needs_revision']], 'note' => $reviewNote],
                );
            }
        }
    }
}