<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $courses = [
            ['code' => 'IT101', 'name' => 'Tin học đại cương', 'credits' => 3, 'description' => 'Kiến thức nền tảng về máy tính, hệ điều hành và công cụ văn phòng.'],
            ['code' => 'IT102', 'name' => 'Lập trình cơ bản', 'credits' => 3, 'description' => 'Tư duy thuật toán, biến, kiểu dữ liệu, điều kiện, vòng lặp và hàm.'],
            ['code' => 'IT201', 'name' => 'Cấu trúc dữ liệu và giải thuật', 'credits' => 4, 'description' => 'Các cấu trúc dữ liệu và giải thuật cơ bản phục vụ giải quyết bài toán.'],
            ['code' => 'IT202', 'name' => 'Cơ sở dữ liệu', 'credits' => 3, 'description' => 'Mô hình dữ liệu quan hệ, SQL, thiết kế và chuẩn hóa cơ sở dữ liệu.'],
            ['code' => 'IT203', 'name' => 'Mạng máy tính', 'credits' => 3, 'description' => 'Kiến trúc mạng, giao thức truyền thông và các dịch vụ mạng cơ bản.'],
            ['code' => 'IT204', 'name' => 'Hệ điều hành', 'credits' => 3, 'description' => 'Tiến trình, luồng, quản lý bộ nhớ, hệ thống tệp và đồng bộ.'],
            ['code' => 'IT301', 'name' => 'Lập trình Web', 'credits' => 3, 'description' => 'Phát triển ứng dụng web với giao diện, máy chủ và cơ sở dữ liệu.'],
            ['code' => 'IT302', 'name' => 'Phân tích và thiết kế hệ thống', 'credits' => 3, 'description' => 'Khảo sát yêu cầu và mô hình hóa hệ thống thông tin.'],
            ['code' => 'IT303', 'name' => 'Công nghệ phần mềm', 'credits' => 3, 'description' => 'Quy trình phát triển, kiểm thử, quản lý cấu hình và bảo trì phần mềm.'],
            ['code' => 'IT401', 'name' => 'Trí tuệ nhân tạo', 'credits' => 3, 'description' => 'Tìm kiếm, biểu diễn tri thức và các phương pháp học máy nhập môn.'],
        ];

        foreach ($courses as $course) {
            Course::firstOrCreate(
                ['code' => $course['code']],
                [...$course, 'department_id' => null, 'is_active' => true],
            );
        }

        $this->command?->info('Đã thêm hoặc cập nhật 10 học phần mẫu (mã IT101–IT401).');
    }
}
