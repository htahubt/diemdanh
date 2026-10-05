<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\AttendanceDemoSeeder;
use Database\Seeders\CourseSeeder;
use Database\Seeders\CourseSectionSeeder;
use Database\Seeders\LearnerSeeder;
use Database\Seeders\LecturerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/');

        $response->assertOk()
            ->assertSee('Tổng quan chuyên cần')
            ->assertSee('Tài khoản & vai trò')
            ->assertSee('Lớp theo lịch hôm nay');
    }

    public function test_admin_navigation_includes_full_training_access(): void
    {
        $response = $this->actingAs(User::factory()->create([
            'role' => 'admin',
            'can_view_sensitive_profiles' => false,
        ]))->get('/');

        $response->assertOk()
            ->assertSee('Tài khoản &amp; vai trò', false)
            ->assertSee('Cấu hình')
            ->assertSee('aria-controls="nav-group-1"', false)
            ->assertSee('aria-controls="nav-group-2"', false)
            ->assertSee('aria-controls="nav-group-3"', false)
            ->assertSee('Người học');
    }

    public function test_a_work_menu_opens_its_setup_page(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'training_office']));

        $this->get('/work/departments')
            ->assertOk()
            ->assertSee('Khoa / Đơn vị')
            ->assertSee('Danh sách khoa / đơn vị')
            ->assertSee('Thêm khoa / đơn vị');

        $this->get('/work/learners')
            ->assertOk()
            ->assertSee('Người học')
            ->assertSee('Phòng đào tạo')
            ->assertSee('Danh sách người học')
            ->assertSee('Nhập danh sách từ Excel');

        $this->get('/work/not-a-menu')->assertNotFound();
    }

    public function test_dashboard_shows_live_daily_attendance_counts(): void
    {
        $this->travelTo(Carbon::parse('2026-10-07 12:00:00'));
        $this->seed([CourseSeeder::class, LecturerSeeder::class, LearnerSeeder::class, CourseSectionSeeder::class, AttendanceDemoSeeder::class]);
        $this->actingAs(User::factory()->create(['role' => 'training_office']));

        $this->get('/')
            ->assertOk()
            ->assertSee('Lớp theo lịch hôm nay')
            ->assertSee('3')
            ->assertSee('Chưa điểm danh');
    }
}
