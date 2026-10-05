<?php

namespace Tests\Feature;

use App\Models\CourseSection;
use App\Models\Learner;
use App\Models\RosterChangeLog;
use App\Models\User;
use Database\Seeders\CourseSeeder;
use Database\Seeders\CourseSectionSeeder;
use Database\Seeders\LecturerSeeder;
use Database\Seeders\LearnerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseSectionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_section_seeder_creates_related_sample_classes_idempotently(): void
    {
        $this->seed([CourseSeeder::class, LecturerSeeder::class, LearnerSeeder::class, CourseSectionSeeder::class]);

        $sections = CourseSection::with(['course', 'lecturer'])
            ->where('academic_year', '2026-2027')
            ->where('semester', 1)
            ->get();

        $this->assertCount(12, $sections);
        $this->assertTrue($sections->every(fn (CourseSection $section): bool => $section->course !== null && $section->lecturer !== null));
        $this->assertTrue($sections->every(fn (CourseSection $section): bool => $section->department !== null && $section->expected_sessions === 15));
        $this->assertSame('IT101', $sections->firstWhere('section_code', 'IT101-01')->course->code);
        $this->assertTrue($sections->firstWhere('section_code', 'IT401-01')->learners()->where('student_code', 'SV-MAU-005')->exists());

        $this->seed(CourseSectionSeeder::class);

        $this->assertSame(12, CourseSection::where('academic_year', '2026-2027')->where('semester', 1)->count());
    }

    public function test_training_office_can_enroll_and_remove_learners_without_deleting_profiles(): void
    {
        $this->seed([CourseSeeder::class, LecturerSeeder::class, LearnerSeeder::class, CourseSectionSeeder::class]);
        $this->actingAs(User::factory()->create(['role' => 'training_office']));

        $section = CourseSection::where('section_code', 'IT303-01')->firstOrFail();
        $section->update(['capacity' => 1]);
        $learner = Learner::where('student_code', 'SV-MAU-001')->firstOrFail();
        $secondLearner = Learner::where('student_code', 'SV-MAU-002')->firstOrFail();

        $this->get("/work/classes/{$section->id}/learners")
            ->assertOk()
            ->assertSee('Danh sách người học lớp IT303-01');

        $this->post("/work/classes/{$section->id}/learners", ['learner_ids' => [$learner->id]])
            ->assertRedirect();
        $this->assertTrue($section->learners()->whereKey($learner->id)->exists());

        $this->post("/work/classes/{$section->id}/learners", ['learner_ids' => [$secondLearner->id]])
            ->assertSessionHasErrors('learner_ids');
        $this->assertFalse($section->learners()->whereKey($secondLearner->id)->exists());

        $this->delete("/work/classes/{$section->id}/learners/{$learner->id}", ['effective_until' => '2026-10-08'])
            ->assertRedirect();
        $this->assertDatabaseHas('learners', ['id' => $learner->id]);
        $this->assertDatabaseHas('course_section_learner', [
            'course_section_id' => $section->id,
            'learner_id' => $learner->id,
            'effective_until' => '2026-10-08',
        ]);
        $rosterChange = RosterChangeLog::where('course_section_id', $section->id)
            ->where('learner_code', $learner->student_code)
            ->where('action', 'withdrawn')
            ->firstOrFail();
        $this->assertSame('2026-10-08', $rosterChange->effective_until->format('Y-m-d'));
        $this->assertSame(auth()->id(), $rosterChange->approved_by);
    }

    public function test_lecturers_cannot_manage_class_enrollments(): void
    {
        $this->seed([CourseSeeder::class, LecturerSeeder::class, LearnerSeeder::class, CourseSectionSeeder::class]);
        $section = CourseSection::firstOrFail();
        $learner = Learner::firstOrFail();
        $this->actingAs(User::factory()->create(['role' => 'lecturer']));

        $this->get("/work/classes/{$section->id}/learners")->assertForbidden();
        $this->post("/work/classes/{$section->id}/learners", ['learner_ids' => [$learner->id]])->assertForbidden();
    }

    public function test_lecturer_sees_only_assigned_sections_in_read_only_class_list(): void
    {
        $this->seed([CourseSeeder::class, LecturerSeeder::class, LearnerSeeder::class, CourseSectionSeeder::class]);
        $section = CourseSection::where('section_code', 'IT201-01')->firstOrFail();
        $this->actingAs(User::factory()->create([
            'role' => 'lecturer',
            'email' => 'lecturer.login@example.test',
            'lecturer_id' => $section->lecturer_id,
        ]));

        $this->get('/work/classes')
            ->assertOk()
            ->assertSee('IT201-01')
            ->assertDontSee('IT201-02')
            ->assertDontSee('Mở lớp mới');
    }
}
