<?php

return [
    'groups' => [
        [
            'label' => 'Điều hành',
            'items' => [
                [
                    'key' => 'overview',
                    'label' => 'Tổng quan',
                    'owner' => 'Tất cả bộ phận',
                    'description' => 'Tổng hợp các đầu việc và dữ liệu chuyên cần.',
                ],
            ],
        ],
        [
            'label' => 'Danh mục đào tạo',
            'items' => [
                ['key' => 'departments', 'label' => 'Khoa / đơn vị', 'owner' => 'Phòng đào tạo', 'description' => 'Quản lý các đơn vị đào tạo trong hệ thống.'],
                ['key' => 'courses', 'label' => 'Học phần', 'owner' => 'Phòng đào tạo', 'description' => 'Khai báo môn học, số tín chỉ và khoa phụ trách.'],
                ['key' => 'classes', 'label' => 'Lớp học phần', 'owner' => 'Phòng đào tạo', 'description' => 'Mở lớp theo học kỳ, phân công giảng viên và dự kiến sĩ số.'],
                ['key' => 'learners', 'label' => 'Người học', 'owner' => 'Phòng đào tạo', 'description' => 'Quản lý hồ sơ và tình trạng người học.'],
                ['key' => 'lecturers', 'label' => 'Giảng viên', 'owner' => 'Quản trị hệ thống', 'description' => 'Quản lý hồ sơ giảng viên và đơn vị công tác.'],
            ],
        ],
        [
            'label' => 'Điểm danh',
            'items' => [
                ['key' => 'schedules', 'label' => 'Lịch giảng dạy', 'owner' => 'Phòng đào tạo', 'description' => 'Theo dõi lịch học theo lớp và giảng viên.'],
                ['key' => 'attendance-sessions', 'label' => 'Phiên điểm danh', 'owner' => 'Giảng viên', 'description' => 'Mở buổi học và ghi nhận tình trạng tham dự.'],
                ['key' => 'absence-requests', 'label' => 'Giải trình vắng', 'owner' => 'Người học / Cố vấn', 'description' => 'Tiếp nhận và xử lý giải trình vắng học.'],
                ['key' => 'student-portal', 'label' => 'Chuyên cần của tôi', 'owner' => 'Học viên / Sinh viên', 'description' => 'Tra cứu chuyên cần cá nhân và gửi đề nghị kiểm tra sai sót.'],
            ],
        ],
        [
            'label' => 'Theo dõi',
            'items' => [
                ['key' => 'attendance-reports', 'label' => 'Báo cáo chuyên cần', 'owner' => 'Phòng đào tạo', 'description' => 'Tổng hợp tỷ lệ tham dự theo lớp và học phần.'],
                ['key' => 'assessment-reports', 'label' => 'Báo cáo điểm kiểm tra', 'owner' => 'Phòng đào tạo', 'description' => 'Tổng hợp điểm kiểm tra, trạng thái vắng và tiến độ nhập điểm.'],
                ['key' => 'absence-alerts', 'label' => 'Cảnh báo đào tạo', 'owner' => 'Phòng đào tạo / Cán bộ quản lý', 'description' => 'Theo dõi vắng vượt ngưỡng, lớp thiếu dữ liệu và buổi chưa xác nhận.'],
            ],
        ],
        [
            'label' => 'Hệ thống',
            'items' => [
                ['key' => 'accounts', 'label' => 'Tài khoản & vai trò', 'owner' => 'Quản trị hệ thống', 'description' => 'Quản lý tài khoản và vai trò sử dụng hệ thống.'],
            ],
        ],
        [
            'label' => 'Cấu hình',
            'items' => [
                ['key' => 'configuration', 'label' => 'Khoa, học hàm & học vị', 'owner' => 'Quản trị hệ thống', 'description' => 'Quản lý danh mục dùng chung cho hồ sơ giảng viên.'],
            ],
        ],
    ],
];
