<?php

namespace Tests\Feature;

use App\Models\AttendanceAlert;
use App\Models\Learner;
use App\Models\User;
use Database\Seeders\AttendanceDemoSeeder;
use Database\Seeders\CourseSeeder;
use Database\Seeders\CourseSectionSeeder;
use Database\Seeders\LearnerSeeder;
use Database\Seeders\LecturerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CourseSeeder::class, LecturerSeeder::class, LearnerSeeder::class, CourseSectionSeeder::class, AttendanceDemoSeeder::class]);
    }

    public function test_training_office_refreshes_configurable_alerts_and_records_follow_up(): void
    {
        $learner = Learner::where('student_code', 'SV-MAU-010')->firstOrFail();
        $learner->update(['study_status' => 'active']);
        $this->actingAs(User::factory()->create(['role' => 'training_office']));

        $this->get('/work/attendance-alerts')->assertOk()->assertSee('Cảnh báo chuyên cần');
        $this->post('/work/attendance-alerts/refresh')->assertRedirect();
        $alert = AttendanceAlert::where('learner_id', $learner->id)->where('alert_type', 'active_learner_unassigned')->firstOrFail();

        $this->patch("/work/attendance-alerts/{$alert->id}", [
            'status' => 'resolved',
            'action_note' => 'Đã liên hệ Phòng đào tạo để xếp lớp.',
        ])->assertRedirect();

        $this->assertSame('resolved', $alert->fresh()->status);
        $this->assertDatabaseHas('attendance_alert_histories', [
            'attendance_alert_id' => $alert->id,
            'after_status' => 'resolved',
            'action_note' => 'Đã liên hệ Phòng đào tạo để xếp lớp.',
        ]);
    }

    public function test_lecturer_cannot_access_the_central_alert_inbox(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'lecturer']));
        $this->get('/work/attendance-alerts')->assertForbidden();
        $this->post('/work/attendance-alerts/refresh')->assertForbidden();
    }
}
