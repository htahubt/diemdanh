<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\CourseSection;
use App\Models\Lecturer;
use App\Models\Learner;
use App\Models\User;
use Database\Seeders\CourseSeeder;
use Database\Seeders\CourseSectionSeeder;
use Database\Seeders\LearnerSeeder;
use Database\Seeders\LecturerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_student_account_linked_to_one_learner(): void
    {
        $learner = Learner::create([
            'student_code' => 'SV-ACCOUNT-01',
            'full_name' => 'Nguyễn Thị Mai',
            'study_status' => 'active',
        ]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->post('/work/accounts', [
            'name' => $learner->full_name,
            'email' => 'sv.account@example.test',
            'password' => 'StrongPass!2026',
            'role' => 'learner',
            'learner_id' => $learner->id,
        ])->assertRedirect('/work/accounts');

        $this->assertDatabaseHas('users', [
            'email' => 'sv.account@example.test',
            'role' => 'learner',
            'learner_id' => $learner->id,
        ]);
    }

    public function test_admin_can_scope_a_unit_leader_account_and_non_admin_cannot_manage_accounts(): void
    {
        $department = Department::create(['code' => 'ACCOUNT-DT', 'name' => 'Khoa tài khoản']);
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->post('/work/accounts', [
            'name' => 'Lãnh đạo khoa',
            'email' => 'unit.leader@example.test',
            'password' => 'StrongPass!2026',
            'role' => 'unit_leader',
            'department_id' => $department->id,
        ])->assertRedirect('/work/accounts');

        $this->assertDatabaseHas('users', [
            'email' => 'unit.leader@example.test',
            'role' => 'unit_leader',
            'department_id' => $department->id,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'training_office']))
            ->get('/work/accounts')
            ->assertForbidden();
    }

    public function test_admin_links_lecturer_account_to_a_profile(): void
    {
        $lecturer = Lecturer::create([
            'lecturer_code' => 'GV-ACCOUNT-LINK',
            'name' => 'Giảng viên liên kết',
            'email' => 'profile@example.test',
        ]);
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post('/work/accounts', [
                'name' => 'Tài khoản giảng viên',
                'email' => 'lecturer.login@example.test',
                'password' => 'StrongPass!2026',
                'role' => 'lecturer',
                'lecturer_id' => $lecturer->id,
            ])
            ->assertRedirect('/work/accounts');

        $account = User::where('email', 'lecturer.login@example.test')->firstOrFail();
        $this->assertDatabaseHas('users', [
            'email' => 'lecturer.login@example.test',
            'role' => 'lecturer',
            'lecturer_id' => $lecturer->id,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/work/accounts')
            ->assertOk()
            ->assertSee('GV-ACCOUNT-LINK')
            ->assertSee('data-lecturer-id="' . $lecturer->id . '"', false);

        $this->actingAs($account)
            ->get("/work/lecturers/{$lecturer->id}")
            ->assertOk();
    }

    public function test_account_screen_renders_and_admin_can_delete_another_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $account = User::factory()->create(['role' => 'training_office']);

        $this->actingAs($admin)
            ->get('/work/accounts')
            ->assertOk()
            ->assertSee('Tài khoản & vai trò')
            ->assertSee('Tạo tài khoản')
            ->assertSee($account->email)
            ->assertSee('Xóa tài khoản');

        $this->delete("/work/accounts/{$account->id}")->assertRedirect('/work/accounts');
        $this->assertDatabaseMissing('users', ['id' => $account->id]);
    }

    public function test_unit_leader_can_only_read_sections_and_rosters_in_assigned_department(): void
    {
        $this->seed([CourseSeeder::class, LecturerSeeder::class, LearnerSeeder::class, CourseSectionSeeder::class]);
        $department = Department::where('name', 'Khoa Công nghệ thông tin')->firstOrFail();
        $otherDepartment = Department::create(['code' => 'UNIT-OTHER', 'name' => 'Khoa khác']);
        $otherSection = CourseSection::where('section_code', 'IT401-01')->firstOrFail();
        $otherSection->update(['department_id' => $otherDepartment->id]);
        $user = User::factory()->create(['role' => 'unit_leader', 'department_id' => $department->id]);
        $this->actingAs($user);

        $this->get('/work/classes')->assertOk()->assertSee('IT201-01')->assertDontSee('IT401-01');
        $this->get("/work/classes/{$otherSection->id}/learners")->assertForbidden();
        $this->get('/work/attendance-reports')->assertOk()->assertDontSee('IT401-01');
    }
}
