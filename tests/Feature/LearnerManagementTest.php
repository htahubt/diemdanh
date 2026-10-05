<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Learner;
use Database\Seeders\LearnerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class LearnerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_training_office_can_create_and_list_learners(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'training_office']));

        $this->post('/work/learners', [
            'student_code' => 'SV-2025-001',
            'full_name' => 'Nguyễn Văn A',
            'date_of_birth' => '2005-05-10',
            'cohort' => '2025',
            'major' => 'Kỹ thuật phần mềm',
            'class_name' => 'KTPM01',
            'study_status' => 'active',
            'phone' => '0901234567',
            'email' => 'nguyenvana@example.com',
            'enrolled_courses' => 'MATH101, CS101',
            'department_id' => null,
        ])->assertRedirect('/work/learners');

        $this->assertDatabaseHas('learners', [
            'student_code' => 'SV-2025-001',
            'full_name' => 'Nguyễn Văn A',
        ]);

        $this->post('/work/learners', [
            'student_code' => 'sv-2025-001',
            'full_name' => 'Người học trùng mã',
            'study_status' => 'active',
        ])->assertSessionHasErrors('student_code');

        $this->get('/work/learners')
            ->assertOk()
            ->assertSee('Danh sách người học')
            ->assertSee('SV-2025-001');
    }

    public function test_training_office_can_import_csv_but_duplicate_rows_are_rejected_atomically(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'training_office']));

        $this->post('/work/learners/import', [
            'file' => UploadedFile::fake()->createWithContent(
                'learners.csv',
                "student_code,full_name,date_of_birth,cohort,major,class_name,study_status,phone,email,department_code\nSV-CSV-001,Nguyen Van A,2005-05-10,2025,Software,KTPM01,active,0901234567,a@example.test,\nSV-CSV-001,Nguyen Van B,2005-06-10,2025,Software,KTPM01,active,0901234568,b@example.test,\n",
            ),
        ])->assertRedirect()
            ->assertSessionHasErrors('import');

        $this->assertDatabaseCount('learners', 0);
    }

    public function test_training_office_can_import_a_valid_csv(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'training_office']));

        $this->post('/work/learners/import', [
            'file' => UploadedFile::fake()->createWithContent(
                'learners.csv',
                "student_code,full_name,date_of_birth,cohort,major,class_name,study_status,phone,email,department_code\nSV-CSV-002,Nguyen Van B,2005-05-10,2025,Software,KTPM01,active,0901234567,b@example.test,\n",
            ),
        ])->assertRedirect('/work/learners')
            ->assertSessionHas('status', 'Đã nhập 1 hồ sơ người học.');

        $this->assertDatabaseHas('learners', ['student_code' => 'SV-CSV-002']);
    }

    public function test_lecturers_cannot_manage_learners(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'lecturer']));

        $this->get('/work/learners')->assertForbidden();
        $this->post('/work/learners', [])->assertForbidden();
        $this->post('/work/learners/import', [])->assertForbidden();
    }

    public function test_learner_sample_data_is_idempotent_and_includes_warning_cases(): void
    {
        $this->seed(LearnerSeeder::class);
        $this->seed(LearnerSeeder::class);

        $this->assertSame(10, Learner::count());
        $this->assertNotEmpty(Learner::where('student_code', 'SV-MAU-008')->firstOrFail()->dataWarnings());
    }
}
