<?php

namespace Tests\Feature;

use App\Models\AcademicOption;
use App\Models\Lecturer;
use App\Models\User;
use Database\Seeders\LecturerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    public function test_admin_can_manage_reference_options(): void
    {
        $this->post('/work/configuration/academic-options', [
            'type' => 'rank',
            'name' => 'Giáo sư danh dự',
        ])->assertRedirect();

        $this->post('/work/configuration/academic-options', [
            'type' => 'degree',
            'name' => 'Tiến sĩ khoa học',
        ])->assertRedirect();

        $this->get('/work/configuration')
            ->assertOk()
            ->assertSee('Giáo sư danh dự')
            ->assertSee('Tiến sĩ khoa học');
    }

    public function test_reference_items_in_use_cannot_be_deactivated(): void
    {
        $rank = AcademicOption::create(['type' => 'rank', 'name' => 'Học hàm đang dùng', 'is_active' => true]);
        $degree = AcademicOption::create(['type' => 'degree', 'name' => 'Học vị đang dùng', 'is_active' => true]);
        Lecturer::create([
            'lecturer_code' => 'GV-REF',
            'name' => 'Lecturer Reference',
            'academic_rank_id' => $rank->id,
            'academic_degree_id' => $degree->id,
        ]);

        $this->patch("/work/configuration/academic-options/{$rank->id}")
            ->assertSessionHasErrors('academic_option');
        $this->patch("/work/configuration/academic-options/{$degree->id}")
            ->assertSessionHasErrors('academic_option');

        $this->assertTrue($rank->fresh()->is_active);
        $this->assertTrue($degree->fresh()->is_active);
    }

    public function test_lecturer_form_uses_reference_selects_and_demo_profiles_are_mapped(): void
    {
        $this->get('/work/lecturers/create')
            ->assertOk()
            ->assertSee('<select name="department_id"', false)
            ->assertSee('<select name="academic_rank_id"', false)
            ->assertSee('<select name="academic_degree_id"', false);

        $this->seed(LecturerSeeder::class);
        $lecturers = Lecturer::with(['departmentUnit', 'academicRank', 'academicDegree'])->get();

        $this->assertCount(5, $lecturers);
        $this->assertTrue($lecturers->every(fn (Lecturer $lecturer): bool =>
            $lecturer->department_id !== null
            && $lecturer->academic_rank_id !== null
            && $lecturer->academic_degree_id !== null
            && $lecturer->departmentUnit !== null
            && $lecturer->academicRank !== null
            && $lecturer->academicDegree !== null
        ));
    }

    public function test_non_admin_cannot_open_configuration(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'lecturer']))
            ->get('/work/configuration')
            ->assertForbidden();
    }
}