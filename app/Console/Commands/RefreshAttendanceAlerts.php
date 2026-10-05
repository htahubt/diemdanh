<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AttendanceAlertService;
use Illuminate\Console\Command;

class RefreshAttendanceAlerts extends Command
{
    protected $signature = 'attendance:refresh-alerts';

    protected $description = 'Recalculate configurable attendance and enrollment alerts';

    public function handle(AttendanceAlertService $service): int
    {
        $actor = User::whereIn('role', ['training_office', 'student_affairs'])->orderBy('id')->first();
        if (! $actor) {
            $this->error('Cần có tài khoản Phòng đào tạo hoặc cán bộ quản lý để ghi nhật ký quét cảnh báo.');

            return self::FAILURE;
        }

        $count = $service->refresh($actor);
        $this->info("Đã cập nhật {$count} cảnh báo đang thỏa điều kiện.");

        return self::SUCCESS;
    }
}
