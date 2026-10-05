<?php

namespace Database\Seeders;

use App\Models\AttendanceAlertSetting;
use Illuminate\Database\Seeder;

class AttendanceAlertSettingSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['rule_key' => 'unexcused_absence_count', 'label' => 'Ngưỡng số buổi vắng không phép', 'threshold' => 3, 'unit' => 'buổi'],
            ['rule_key' => 'unexcused_absence_rate', 'label' => 'Ngưỡng tỷ lệ vắng không phép', 'threshold' => 25, 'unit' => '%'],
            ['rule_key' => 'unconfirmed_session_hours', 'label' => 'Thời gian nhắc xác nhận sau buổi học', 'threshold' => 24, 'unit' => 'giờ'],
            ['rule_key' => 'attendance_edit_hours', 'label' => 'Thời hạn giảng viên tự sửa sau xác nhận', 'threshold' => 24, 'unit' => 'giờ'],
        ] as $setting) {
            AttendanceAlertSetting::firstOrCreate(['rule_key' => $setting['rule_key']], $setting + ['is_enabled' => true]);
        }
    }
}
