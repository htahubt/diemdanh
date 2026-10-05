<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Lecturer;
use App\Models\User;
use Database\Seeders\LecturerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'training_office']));
    }

    public function test_existing_department_menu_manages_departments_and_sample_mapping(): void
    {
        $this->seed(LecturerSeeder::class);

        $this->get('/work/departments')
            ->assertOk()
            ->assertSee('Khoa / Đơn vị')
            ->assertSee('Khoa Công nghệ thông tin')
            ->assertSee('Hồ sơ đang tham chiếu');

        $lecturers = Lecturer::with('departmentUnit')->get();
        $this->assertCount(5, $lecturers);
        $this->assertTrue($lecturers->every(fn (Lecturer $lecturer): bool => $lecturer->departmentUnit !== null));
    }

    public function test_admin_can_create_update_and_delete_departments_safely(): void
    {
        $this->post('/work/departments', [
            'code' => 'KHOA-TEST',
            'name' => 'Khoa thử nghiệm',
        ])->assertRedirect();

        $department = Department::where('code', 'KHOA-TEST')->firstOrFail();
        $this->assertTrue($department->is_active);
        $this->get("/work/departments/{$department->id}/edit")
            ->assertOk()
            ->assertSee('Khoa thử nghiệm');

        $this->put("/work/departments/{$department->id}", [
            'code' => 'KHOA-DOI-TEN',
            'name' => 'Khoa đã đổi tên',
        ])->assertRedirect('/work/departments');

        $department->refresh();
        $this->assertSame('Khoa đã đổi tên', $department->name);

        $lecturer = Lecturer::create([
            'lecturer_code' => 'GV-DEPT-TEST',
            'name' => 'Lecturer Department Test',
            'department_id' => $department->id,
            'department' => $department->name,
        ]);

        $this->put("/work/departments/{$department->id}", [
            'code' => 'KHOA-DOI-TEN',
            'name' => 'Khoa cập nhật lần hai',
        ])->assertRedirect('/work/departments');

        $this->assertSame('Khoa cập nhật lần hai', $lecturer->fresh()->department);
        $this->assertSame('department_renamed', $lecturer->history()->firstOrFail()->action);

        $this->patch("/work/departments/{$department->id}")
            ->assertSessionHasErrors('department');
        $this->assertTrue($department->fresh()->is_active);
        $this->delete("/work/departments/{$department->id}")->assertStatus(409);

        $unused = Department::create(['code' => 'KHOA-UNUSED', 'name' => 'Khoa chưa dùng', 'is_active' => true]);
        $this->delete("/work/departments/{$unused->id}")->assertRedirect('/work/departments');
        $this->assertDatabaseMissing('departments', ['id' => $unused->id]);
    }

    public function test_non_admin_cannot_manage_departments(): void
    {
        $user = User::factory()->create(['role' => 'lecturer', 'can_view_sensitive_profiles' => false]);

        $this->actingAs($user)->get('/work/departments')->assertForbidden();
        $this->post('/work/departments', ['code' => 'BLOCKED', 'name' => 'Không được tạo'])
            ->assertForbidden();
    }

    public function test_training_office_can_manage_training_catalogs_and_class_sections(): void
    {
        $user = User::factory()->create(['role' => 'training_office']);
        $this->actingAs($user);

        $this->get('/work/departments')->assertOk();
        $this->post('/work/departments', ['code' => 'DT-TEST', 'name' => 'Đơn vị đào tạo test'])
            ->assertRedirect();

        $this->get('/work/courses')->assertOk();
        $this->post('/work/courses', [
            'code' => 'HP-TEST',
            'name' => 'Học phần test',
            'credits' => 3,
        ])->assertRedirect('/work/courses');

        $course = \App\Models\Course::where('code', 'HP-TEST')->firstOrFail();
        $department = Department::where('code', 'DT-TEST')->firstOrFail();
        $this->post('/work/classes', [
            'course_id' => $course->id,
            'section_code' => 'HP-TEST-01',
            'semester' => 1,
            'academic_year' => '2026-2027',
            'capacity' => 30,
            'department_id' => $department->id,
            'training_format' => 'Chính quy',
            'weekday' => 1,
            'start_time' => '08:00',
            'end_time' => '10:00',
            'room' => 'A-TEST',
            'expected_sessions' => 15,
            'starts_on' => '2026-10-05',
            'ends_on' => '2027-01-31',
        ])->assertRedirect('/work/classes');
    }

    public function test_system_admin_can_manage_training_data(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->get('/work/configuration')->assertOk();
        $this->get('/work/departments')->assertOk();
        $this->get('/work/courses')->assertOk();
        $this->get('/work/classes')->assertOk();
        $this->get('/work/learners')->assertOk();
        $this->from('/work/departments')
            ->post('/work/departments', ['code' => 'ADMIN-TEST', 'name' => 'Đơn vị quản trị'])
            ->assertRedirect('/work/departments');
    }
}