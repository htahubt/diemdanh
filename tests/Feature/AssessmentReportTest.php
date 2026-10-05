<?php

namespace Tests\Feature;

use App\Models\CourseSection;
use App\Models\User;
use Database\Seeders\AssessmentDemoSeeder;
use Database\Seeders\AttendanceDemoSeeder;
use Database\Seeders\CourseSeeder;
use Database\Seeders\CourseSectionSeeder;
use Database\Seeders\LearnerSeeder;
use Database\Seeders\LecturerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CourseSeeder::class, LecturerSeeder::class, LearnerSeeder::class, CourseSectionSeeder::class, AttendanceDemoSeeder::class, AssessmentDemoSeeder::class]);
        $this->actingAs(User::factory()->create(['role' => 'training_office']));
    }

    public function test_training_office_can_filter_and_export_assessment_results(): void
    {
        $section = CourseSection::where('section_code', 'IT201-01')->firstOrFail();

        $this->get("/work/assessment-reports?course_section_id={$section->id}&academic_year=2026-2027")
            ->assertOk()
            ->assertSee('Báo cáo điểm kiểm tra')
            ->assertSee('Kiểm tra giữa kỳ')
            ->assertSee('SV-MAU-001')
            ->assertSee('Vắng kiểm tra');

        $response = $this->get("/work/assessment-reports/export?course_section_id={$section->id}")->assertOk();
        ob_start();
        $response->baseResponse->sendContent();
        $csv = ob_get_clean();
        $this->assertStringContainsString('SV-MAU-001', $csv);
        $this->assertStringContainsString('Kiểm tra giữa kỳ', $csv);
    }
}
