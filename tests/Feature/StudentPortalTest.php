<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\CourseSection;
use App\Models\Department;
use App\Models\Learner;
use App\Models\User;
use Database\Seeders\AttendanceDemoSeeder;
use Database\Seeders\CourseSeeder;
use Database\Seeders\CourseSectionSeeder;
use Database\Seeders\LearnerSeeder;
use Database\Seeders\LecturerSeeder;
use Database\Seeders\StudentDemoAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_sees_only_own_attendance_and_can_submit_a_correction_request(): void
    {
        $this->seed([CourseSeeder::class, LecturerSeeder::class, LearnerSeeder::class, CourseSectionSeeder::class, AttendanceDemoSeeder::class]);
        $learner = Learner::where('student_code', 'SV-MAU-001')->firstOrFail();
        $otherLearner = Learner::where('student_code', 'SV-MAU-008')->firstOrFail();
        $user = User::factory()->create(['role' => 'learner', 'learner_id' => $learner->id]);
        $this->actingAs($user);

        $response = $this->get('/student/attendance')
            ->assertOk()
            ->assertSee('SV-MAU-001')
            ->assertSee('IT201-01')
            ->assertDontSee('SV-MAU-008');

        $ownRecord = AttendanceRecord::whereHas('learner', fn ($query) => $query->whereKey($learner->id))->firstOrFail();
        $otherRecord = AttendanceRecord::whereHas('learner', fn ($query) => $query->whereKey($otherLearner->id))->firstOrFail();

        $this->post("/student/attendance-records/{$ownRecord->id}/corrections", [
            'requested_status' => 'excused',
            'reason' => 'Có giấy xác nhận của đơn vị.',
        ])->assertRedirect();

        $this->post("/student/attendance-records/{$otherRecord->id}/corrections", [
            'requested_status' => 'present',
            'reason' => 'Yêu cầu trái quyền.',
        ])->assertNotFound();
    }

    public function test_student_correction_is_applied_only_after_staff_approval_and_is_audited(): void
    {
        $this->seed([CourseSeeder::class, LecturerSeeder::class, LearnerSeeder::class, CourseSectionSeeder::class, AttendanceDemoSeeder::class]);
        $learner = Learner::where('student_code', 'SV-MAU-008')->firstOrFail();
        $record = AttendanceRecord::whereHas('learner', fn ($query) => $query->whereKey($learner->id))
            ->whereHas('attendanceSession', fn ($query) => $query->where('status', 'confirmed'))
            ->firstOrFail();
        $this->actingAs(User::factory()->create(['role' => 'learner', 'learner_id' => $learner->id]));

        $this->post("/student/attendance-records/{$record->id}/corrections", [
            'requested_status' => 'present',
            'reason' => 'Đã tham gia đầy đủ buổi học.',
        ])->assertRedirect();

        $correctionId = \App\Models\StudentCorrectionRequest::firstOrFail()->id;
        $this->assertSame('late', $record->fresh()->status);

        $reviewer = User::factory()->create([
            'role' => 'student_affairs',
            'department_id' => Department::where('name', 'Khoa Công nghệ thông tin')->value('id'),
        ]);
        $this->actingAs($reviewer);
        $this->get('/work/correction-requests')->assertOk();
        $this->post("/work/correction-requests/{$correctionId}/review", [
            'decision' => 'approved',
            'review_note' => 'Đã xác minh.',
        ])->assertRedirect();

        $this->assertSame('present', $record->fresh()->status);
        $this->assertSame('approved', \App\Models\StudentCorrectionRequest::findOrFail($correctionId)->status);
        $this->assertSame('late', $record->changes()->firstOrFail()->before_status);
        $this->assertSame('present', $record->changes()->firstOrFail()->after_status);
    }

    public function test_demo_student_login_is_linked_and_redirects_to_private_portal(): void
    {
        $this->seed([LearnerSeeder::class, StudentDemoAccountSeeder::class]);
        $user = User::where('email', 'student.demo@example.test')->firstOrFail();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->get('/')->assertRedirect('/student/attendance');
        $this->get('/student/attendance')->assertOk()->assertSee('SV-MAU-001');
    }
}
