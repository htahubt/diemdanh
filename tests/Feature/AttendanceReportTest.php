<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AttendanceDemoSeeder;
use Database\Seeders\CourseSeeder;
use Database\Seeders\CourseSectionSeeder;
use Database\Seeders\LearnerSeeder;
use Database\Seeders\LecturerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CourseSeeder::class, LecturerSeeder::class, LearnerSeeder::class, CourseSectionSeeder::class, AttendanceDemoSeeder::class]);
        $this->actingAs(User::factory()->create(['role' => 'training_office']));
    }

    public function test_training_office_can_filter_and_export_attendance_details(): void
    {
        $this->get('/work/attendance-reports?from=2026-10-07&to=2026-10-07')
            ->assertOk()
            ->assertSee('Báo cáo chuyên cần')
            ->assertSee('IT201-01')
            ->assertSee('SV-MAU-001');

        $response = $this->get('/work/attendance-reports/export?from=2026-10-07&to=2026-10-07');
        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        ob_start();
        $response->baseResponse->sendContent();
        $csv = ob_get_clean();
        $this->assertStringContainsString('SV-MAU-001', $csv);
        $this->assertStringContainsString('2026-10-07', $csv);
    }

    public function test_lecturer_report_is_limited_to_assigned_sections(): void
    {
        $lecturer = \App\Models\CourseSection::where('section_code', 'IT201-01')->firstOrFail()->lecturer;
        $this->actingAs(User::factory()->create(['role' => 'lecturer', 'email' => $lecturer->email]));

        $this->get('/work/attendance-reports')
            ->assertOk()
            ->assertSee('IT201-01')
            ->assertDontSee('IT401-01');
    }
}
