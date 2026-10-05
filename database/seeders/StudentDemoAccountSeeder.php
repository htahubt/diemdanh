<?php

namespace Database\Seeders;

use App\Models\Learner;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class StudentDemoAccountSeeder extends Seeder
{
    public function run(): void
    {
        $learner = Learner::where('student_code', 'SV-MAU-001')->first();
        if (! $learner) {
            throw new RuntimeException('Hãy chạy LearnerSeeder trước StudentDemoAccountSeeder.');
        }

        User::updateOrCreate(
            ['email' => 'student.demo@example.test'],
            [
                'name' => $learner->full_name,
                'password' => Hash::make('password'),
                'role' => 'learner',
                'learner_id' => $learner->id,
            ],
        );

        $this->command?->info('Đã tạo hoặc cập nhật tài khoản người học mẫu.');
    }
}
