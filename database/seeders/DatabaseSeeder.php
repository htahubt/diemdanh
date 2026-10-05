<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'training.office@example.test'],
            [
                'name' => 'Phòng đào tạo mẫu',
                'password' => Hash::make('password'),
                'role' => 'training_office',
            ],
        );

        $this->call([
            CourseSeeder::class,
            LecturerSeeder::class,
            LearnerSeeder::class,
            StudentDemoAccountSeeder::class,
            CourseSectionSeeder::class,
            AttendanceDemoSeeder::class,
            AssessmentDemoSeeder::class,
            AttendanceAlertSettingSeeder::class,
        ]);
    }
}
