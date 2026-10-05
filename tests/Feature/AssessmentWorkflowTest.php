<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\AssessmentCorrectionRequest;
use App\Models\CourseSection;
use App\Models\Learner;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\CourseSeeder;
use Database\Seeders\CourseSectionSeeder;
use Database\Seeders\LearnerSeeder;
use Database\Seeders\LecturerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private CourseSection $section;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-15 12:00:00'));
        $this->seed([CourseSeeder::class, LecturerSeeder::class, LearnerSeeder::class, CourseSectionSeeder::class]);
        $this->section = CourseSection::where('section_code', 'IT201-01')->firstOrFail();
        $this->actingAs(User::factory()->create(['role' => 'training_office']));
    }

    public function test_training_office_configures_multiple_weighted_assessments(): void
    {
        $this->post("/work/classes/{$this->section->id}/assessments", [
            'title' => 'Kiểm tra giữa kỳ',
            'kind' => 'midterm',
            'max_score' => 10,
            'weight' => 40,
            'edit_until' => '2026-12-15',
        ])->assertRedirect();

        $assessment = Assessment::where('course_section_id', $this->section->id)->firstOrFail();
        $this->assertSame('40.00', $assessment->weight);
        $this->assertSame(
            $this->section->learners()->where('learners.study_status', 'active')->count(),
            $assessment->results()->count(),
        );
        $this->assertSame(0, $assessment->results()->where('status', 'not_entered')->whereNotNull('score')->count());

        $this->post("/work/classes/{$this->section->id}/assessments", [
            'title' => 'Kiểm tra cuối kỳ',
            'kind' => 'final',
            'max_score' => 10,
            'weight' => 70,
            'edit_until' => '2026-12-20',
        ])->assertSessionHasErrors('weight');

        $this->assertSame(1, Assessment::where('course_section_id', $this->section->id)->count());
    }

    public function test_assigned_lecturer_can_record_scores_and_absence_is_not_zero(): void
    {
        $this->post("/work/classes/{$this->section->id}/assessments", [
            'title' => 'Kiểm tra thường xuyên',
            'kind' => 'quiz',
            'max_score' => 20,
            'weight' => 100,
            'edit_until' => '2026-12-20',
        ])->assertRedirect();
        $assessment = Assessment::where('course_section_id', $this->section->id)->firstOrFail();

        $learners = $this->section->learners()->get();
        $presentLearner = $learners->first();
        $absentLearner = $learners->last();
        $lecturer = User::factory()->create([
            'role' => 'lecturer',
            'email' => 'lecturer.login@example.test',
            'lecturer_id' => $this->section->lecturer_id,
        ]);
        $this->actingAs($lecturer);

        $this->get("/work/assessments/{$assessment->id}")->assertOk()->assertSee('Kiểm tra thường xuyên');
        $this->patch("/work/assessments/{$assessment->id}/results", [
            'results' => [
                $presentLearner->id => ['status' => 'graded', 'score' => '17.50'],
                $absentLearner->id => ['status' => 'absent', 'score' => ''],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $graded = AssessmentResult::where('assessment_id', $assessment->id)->where('learner_id', $presentLearner->id)->firstOrFail();
        $absent = AssessmentResult::where('assessment_id', $assessment->id)->where('learner_id', $absentLearner->id)->firstOrFail();
        $this->assertSame('17.50', $graded->score);
        $this->assertSame('absent', $absent->status);
        $this->assertNull($absent->score);
    }

    public function test_score_changes_after_deadline_require_approval_and_keep_before_after_history(): void
    {
        $this->post("/work/classes/{$this->section->id}/assessments", [
            'title' => 'Kiểm tra đã hết hạn',
            'kind' => 'quiz',
            'max_score' => 20,
            'weight' => 100,
            'edit_until' => '2026-10-10',
        ])->assertRedirect();
        $assessment = Assessment::where('title', 'Kiểm tra đã hết hạn')->firstOrFail();
        $learner = $this->section->learners()->firstOrFail();
        $record = AssessmentResult::where('assessment_id', $assessment->id)
            ->where('learner_id', $learner->id)
            ->firstOrFail();
        $record->update(['status' => 'graded', 'score' => 12]);
        $lecturer = User::factory()->create([
            'role' => 'lecturer',
            'email' => 'lecturer.login@example.test',
            'lecturer_id' => $this->section->lecturer_id,
        ]);
        $this->actingAs($lecturer);

        $this->patch("/work/assessments/{$assessment->id}/results", [
            'results' => [$learner->id => ['status' => 'graded', 'score' => 18]],
        ])->assertStatus(409);

        $this->post("/work/assessment-results/{$record->id}/corrections", [
            'requested_status' => 'graded',
            'requested_score' => 18,
            'reason' => 'Nhập nhầm điểm từ phiếu chấm đã đối chiếu.',
        ])->assertRedirect();
        $request = AssessmentCorrectionRequest::firstOrFail();
        $this->assertSame('12.00', $record->fresh()->score);

        $this->actingAs(User::factory()->create(['role' => 'training_office']));
        $this->get('/work/correction-requests')->assertOk()->assertSee('Nhập nhầm điểm');
        $this->post("/work/assessment-correction-requests/{$request->id}/review", [
            'decision' => 'approved',
            'review_note' => 'Đã đối chiếu phiếu gốc.',
        ])->assertRedirect();

        $this->assertSame('18.00', $record->fresh()->score);
        $this->assertSame('12.00', $record->changes()->firstOrFail()->before_score);
        $this->assertSame('18.00', $record->changes()->firstOrFail()->after_score);
    }
}
