<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\CourseSection;
use App\Models\Department;
use App\Models\Learner;
use App\Models\User;
use Database\Seeders\CourseSeeder;
use Database\Seeders\CourseSectionSeeder;
use Database\Seeders\LearnerSeeder;
use Database\Seeders\LecturerSeeder;
use Database\Seeders\AttendanceDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private CourseSection $section;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CourseSeeder::class, LecturerSeeder::class, LearnerSeeder::class, CourseSectionSeeder::class]);
        $this->section = CourseSection::where('section_code', 'IT201-01')->firstOrFail();
        $this->actingAs(User::factory()->create(['role' => 'training_office']));
    }

    public function test_training_office_can_define_a_real_timetable_and_open_a_session_from_it(): void
    {
        $laterAddedLearner = Learner::where('student_code', 'SV-MAU-008')->firstOrFail();
        $this->section->learners()->updateExistingPivot($laterAddedLearner->id, ['effective_from' => '2026-10-08']);

        $this->put("/work/classes/{$this->section->id}", $this->sectionData([
            'weekday' => 3,
            'start_time' => '15:00',
            'end_time' => '17:00',
            'room' => 'A-201',
            'expected_sessions' => 15,
            'starts_on' => '2026-10-01',
            'ends_on' => '2027-01-31',
        ]))->assertRedirect('/work/classes');

        $this->assertDatabaseHas('course_sections', [
            'id' => $this->section->id,
            'weekday' => 3,
            'room' => 'A-201',
            'expected_sessions' => 15,
        ]);

        $this->post("/work/classes/{$this->section->id}/sessions", ['session_date' => '2026-10-07'])
            ->assertRedirect();

        $session = AttendanceSession::where('course_section_id', $this->section->id)->firstOrFail();
        $this->assertSame(1, $session->session_number);
        $this->assertSame('open', $session->status);
        $this->assertSame(1, $session->attendanceRecords()->count());
        $this->assertDatabaseMissing('attendance_records', [
            'attendance_session_id' => $session->id,
            'learner_id' => $laterAddedLearner->id,
        ]);

        $withdrawnLearner = Learner::where('student_code', 'SV-MAU-001')->firstOrFail();
        $this->section->learners()->updateExistingPivot($withdrawnLearner->id, ['effective_until' => '2026-10-08']);
        $this->post("/work/classes/{$this->section->id}/sessions", ['session_date' => '2026-10-14'])->assertRedirect();
        $laterSession = AttendanceSession::where('course_section_id', $this->section->id)->where('session_number', 2)->firstOrFail();
        $this->assertDatabaseMissing('attendance_records', [
            'attendance_session_id' => $laterSession->id,
            'learner_id' => $withdrawnLearner->id,
        ]);
        $this->get("/work/attendance-sessions/{$session->id}")
            ->assertOk()
            ->assertSee('value="unconfirmed" selected', false);
        $pendingRecord = $session->attendanceRecords()->firstOrFail();
        $this->patch("/work/attendance-sessions/{$session->id}/records", [
            'records' => [$pendingRecord->learner_id => 'unconfirmed'],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('unconfirmed', $pendingRecord->fresh()->status);
        $this->get('/work/attendance-sessions')->assertOk()->assertSee('IT201-01');
        $this->delete('/work/learners/' . Learner::where('student_code', 'SV-MAU-001')->value('id'))
            ->assertStatus(409);
    }

    public function test_overlapping_room_or_lecturer_timetables_are_rejected(): void
    {
        $this->put("/work/classes/{$this->section->id}", $this->sectionData([
            'weekday' => 3,
            'start_time' => '15:00',
            'end_time' => '17:00',
            'room' => 'A-201',
            'expected_sessions' => 15,
            'starts_on' => '2026-10-01',
            'ends_on' => '2027-01-31',
        ]))->assertRedirect('/work/classes');

        $this->post('/work/classes', [
            ...$this->sectionData([
                'weekday' => 3,
                'start_time' => '16:00',
                'end_time' => '18:00',
                'room' => 'B-101',
                'expected_sessions' => 15,
                'starts_on' => '2026-10-01',
                'ends_on' => '2027-01-31',
            ]),
            'section_code' => 'IT201-03',
            'lecturer_id' => $this->section->lecturer_id,
        ])->assertSessionHasErrors('schedule');
    }

    public function test_lecturer_can_mark_all_present_change_exceptions_and_confirm_with_audit_history(): void
    {
        $this->put("/work/classes/{$this->section->id}", $this->sectionData([
            'weekday' => 3,
            'start_time' => '15:00',
            'end_time' => '17:00',
            'room' => 'A-201',
            'expected_sessions' => 15,
            'starts_on' => '2026-10-01',
            'ends_on' => '2027-01-31',
        ]))->assertRedirect('/work/classes');
        $this->post("/work/classes/{$this->section->id}/sessions", ['session_date' => '2026-10-07'])->assertRedirect();

        $session = AttendanceSession::where('course_section_id', $this->section->id)->firstOrFail();
        $learner = $session->attendanceRecords()->firstOrFail()->learner;
        $lecturer = $this->section->lecturer;
        $this->actingAs(User::factory()->create([
            'role' => 'lecturer',
            'email' => 'lecturer.login@example.test',
            'lecturer_id' => $lecturer->id,
        ]));

        $this->post("/work/attendance-sessions/{$session->id}/mark-all-present")->assertRedirect();
        $this->patch("/work/attendance-sessions/{$session->id}/records", [
            'records' => [$learner->id => 'late'],
            'reason' => 'Đến muộn theo xác nhận tại lớp',
        ])->assertRedirect();

        $record = AttendanceRecord::where('attendance_session_id', $session->id)->where('learner_id', $learner->id)->firstOrFail();
        $this->assertSame('late', $record->status);
        $this->assertSame('present', $record->changes()->firstOrFail()->before_status);
        $this->assertSame('late', $record->changes()->firstOrFail()->after_status);

        $this->post("/work/attendance-sessions/{$session->id}/confirm")->assertRedirect();
        $this->assertNotNull($session->fresh()->confirmed_at);
        $this->patch("/work/attendance-sessions/{$session->id}/records", ['records' => [$learner->id => 'present']])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('present', $record->fresh()->status);

        $this->travelTo($session->fresh()->editable_until->addSecond());
        $this->patch("/work/attendance-sessions/{$session->id}/records", [
            'records' => [$learner->id => 'late'],
            'reason' => 'Giảng viên phát hiện nhầm trạng thái sau hạn.',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('present', $record->fresh()->status);
        $this->assertDatabaseHas('student_correction_requests', [
            'attendance_record_id' => $record->id,
            'requester_type' => 'lecturer',
            'requested_status' => 'late',
            'status' => 'pending',
        ]);
    }

    public function test_attendance_demo_seeder_adds_open_and_confirmed_sessions_idempotently(): void
    {
        $this->seed(AttendanceDemoSeeder::class);
        $this->seed(AttendanceDemoSeeder::class);

        $this->assertSame(2, AttendanceSession::count());
        $confirmed = AttendanceSession::where('status', 'confirmed')->firstOrFail();
        $open = AttendanceSession::where('status', 'open')->firstOrFail();
        $this->assertSame(2, $confirmed->attendanceRecords()->count());
        $this->assertTrue($confirmed->attendanceRecords()->where('status', 'late')->firstOrFail()->changes()->exists());
        $this->assertSame(2, $open->attendanceRecords()->where('status', 'unconfirmed')->count());
    }

    private function sectionData(array $schedule): array
    {
        return [
            'course_id' => $this->section->course_id,
            'section_code' => $this->section->section_code,
            'semester' => $this->section->semester,
            'academic_year' => $this->section->academic_year,
            'lecturer_id' => $this->section->lecturer_id,
            'capacity' => $this->section->capacity,
            'department_id' => $this->section->department_id ?? Department::value('id'),
            'training_format' => 'Chính quy',
            ...$schedule,
        ];
    }
}
