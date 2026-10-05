<?php

namespace Tests\Feature;

use App\Models\Lecturer;
use App\Models\AcademicOption;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LecturerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create([
            'role' => 'admin',
            'can_view_sensitive_profiles' => true,
        ]));

        Department::firstOrCreate(['code' => 'TEST-CNTT'], ['name' => 'Khoa Công nghệ', 'is_active' => true]);
        AcademicOption::firstOrCreate(['type' => 'rank', 'name' => 'Phó Giáo sư'], ['is_active' => true]);
        AcademicOption::firstOrCreate(['type' => 'degree', 'name' => 'Tiến sĩ'], ['is_active' => true]);
    }

    public function test_lecturer_list_can_be_searched_and_filtered(): void
    {
        $this->get('/work/lecturers')
            ->assertOk()
            ->assertSee('aria-expanded="true" aria-controls="nav-group-1"', false)
            ->assertSee('<span>Giảng viên</span>', false);

        Lecturer::create([
            'lecturer_code' => 'GV001',
            'name' => 'Nguyen Van An',
            'department' => 'Khoa Công nghệ',
            'status' => 'active',
        ]);
        Lecturer::create([
            'lecturer_code' => 'GV002',
            'name' => 'Tran Thi Binh',
            'department' => 'Khoa Kinh tế',
            'status' => 'inactive',
        ]);

        $this->get('/work/lecturers?search=GV001&status=active')
            ->assertOk()
            ->assertSee('Nguyen Van An')
            ->assertDontSee('Tran Thi Binh');
    }

    public function test_lecturer_can_be_created_updated_and_deleted(): void
    {
        $this->get('/work/lecturers/create')
            ->assertOk()
            ->assertSee('Tài khoản đăng nhập')
            ->assertSee('name="account_password"', false);

        $this->post('/work/lecturers', $this->lecturerData())->assertRedirect('/work/lecturers');

        $lecturer = Lecturer::where('lecturer_code', 'GV003')->firstOrFail();
        $this->assertDatabaseHas('lecturers', ['id' => $lecturer->id, 'name' => 'Le Minh Chau']);
        $this->assertSame(Department::where('code', 'TEST-CNTT')->value('id'), $lecturer->department_id);
        $this->assertSame(AcademicOption::where('type', 'rank')->where('name', 'Phó Giáo sư')->value('id'), $lecturer->academic_rank_id);
        $this->assertSame(AcademicOption::where('type', 'degree')->where('name', 'Tiến sĩ')->value('id'), $lecturer->academic_degree_id);
        $this->assertSame('Khoa Công nghệ', $lecturer->departmentUnit->name);
        $this->assertSame('Phó Giáo sư', $lecturer->academicRank->name);
        $this->assertSame('Tiến sĩ', $lecturer->academicDegree->name);
        $this->assertSame('012345678901', $lecturer->national_id);
        $this->assertSame('001234567890', $lecturer->bank_account_number);
        $this->assertSame(hash_hmac('sha256', '012345678901', config('app.key')), $lecturer->national_id_hash);
        $this->assertNotSame('012345678901', $lecturer->getRawOriginal('national_id'));
        $this->assertNotSame('001234567890', $lecturer->getRawOriginal('bank_account_number'));
        $this->assertSame('submitted', $lecturer->profile_status);
        $this->get("/work/lecturers/{$lecturer->id}/edit")
            ->assertOk()
            ->assertSee('name="account_password_confirmation"', false);
        $this->assertDatabaseHas('users', [
            'email' => $lecturer->email,
            'role' => 'lecturer',
            'lecturer_id' => $lecturer->id,
        ]);
        $this->assertNotSame('012345678901', $lecturer->history()->firstOrFail()->getRawOriginal('changes'));

        $this->put("/work/lecturers/{$lecturer->id}", $this->lecturerData([
            'name' => 'Le Minh Chau Nguyen',
            'email' => 'chau.updated@example.edu.vn',
            'account_password' => 'NewStrongPass!2026',
            'account_password_confirmation' => 'NewStrongPass!2026',
            'status' => 'inactive',
        ]))->assertRedirect('/work/lecturers');

        $this->assertDatabaseHas('lecturers', ['id' => $lecturer->id, 'name' => 'Le Minh Chau Nguyen', 'status' => 'inactive']);
        $linkedAccount = User::where('lecturer_id', $lecturer->id)->firstOrFail();
        $this->assertSame('chau.updated@example.edu.vn', $linkedAccount->email);
        $this->assertSame('Le Minh Chau Nguyen', $linkedAccount->name);
        $this->assertTrue(Hash::check('NewStrongPass!2026', $linkedAccount->password));

        $linkedAccountId = $linkedAccount->id;
        $this->delete("/work/lecturers/{$lecturer->id}")->assertRedirect('/work/lecturers');
        $this->assertDatabaseMissing('lecturers', ['id' => $lecturer->id]);
        $this->assertDatabaseMissing('users', ['id' => $linkedAccountId]);
    }

    public function test_lecturer_self_service_links_profile_to_current_account(): void
    {
        $user = User::factory()->create(['role' => 'lecturer', 'email' => 'self.service@example.edu.vn']);

        $this->actingAs($user)
            ->post('/work/lecturers', $this->lecturerData([
                'email' => $user->email,
                'account_password' => '',
                'account_password_confirmation' => '',
            ]))
            ->assertRedirect('/work/lecturers');

        $lecturer = Lecturer::where('email', $user->email)->firstOrFail();
        $this->assertSame($lecturer->id, $user->fresh()->lecturer_id);
        $this->assertSame($lecturer->name, $user->fresh()->name);
        $this->assertSame(1, User::where('email', $user->email)->count());
    }

    public function test_approval_requires_identity_and_bank_verification(): void
    {
        $this->post('/work/lecturers', $this->lecturerData())->assertRedirect('/work/lecturers');
        $lecturer = Lecturer::where('lecturer_code', 'GV003')->firstOrFail();

        $this->post("/work/lecturers/{$lecturer->id}/review", [
            'action' => 'approve',
            'identity_verified' => '1',
        ])->assertSessionHasErrors('bank_verified');

        $this->post("/work/lecturers/{$lecturer->id}/review", [
            'action' => 'approve',
            'identity_verified' => '1',
            'bank_verified' => '1',
            'review_note' => 'Đã đối chiếu giấy tờ bản gốc.',
        ])->assertRedirect("/work/lecturers/{$lecturer->id}/edit");

        $lecturer->refresh();
        $this->assertSame('approved', $lecturer->profile_status);
        $this->assertNotNull($lecturer->identity_verified_at);
        $this->assertNotNull($lecturer->bank_verified_at);
        $this->assertSame('approved', $lecturer->history()->firstOrFail()->action);
    }

    public function test_private_documents_are_saved_outside_public_storage(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $this->post('/work/lecturers', $this->lecturerData([
            'identity_documents' => [UploadedFile::fake()->create('cccd.pdf', 20, 'application/pdf')],
        ]))->assertRedirect('/work/lecturers');

        $document = Lecturer::where('lecturer_code', 'GV003')->firstOrFail()->documents()->firstOrFail();
        Storage::disk('local')->assertExists($document->storage_path);
        Storage::disk('public')->assertMissing($document->storage_path);
        $history = $document->lecturer->history()->firstOrFail();
        $this->assertArrayHasKey('documents_added', $history->changes);
    }

    public function test_reviewer_can_request_changes_and_approved_profiles_cannot_be_deleted(): void
    {
        $this->post('/work/lecturers', $this->lecturerData())->assertRedirect('/work/lecturers');
        $lecturer = Lecturer::where('lecturer_code', 'GV003')->firstOrFail();

        $this->post("/work/lecturers/{$lecturer->id}/review", [
            'action' => 'needs_revision',
            'review_note' => 'Vui lòng bổ sung bản chụp văn bằng.',
        ])->assertRedirect("/work/lecturers/{$lecturer->id}/edit");

        $lecturer->refresh();
        $this->assertSame('needs_revision', $lecturer->profile_status);
        $this->assertSame('Vui lòng bổ sung bản chụp văn bằng.', $lecturer->review_note);
        $this->assertSame('revision_requested', $lecturer->history()->firstOrFail()->action);

        $this->put("/work/lecturers/{$lecturer->id}", $this->lecturerData())
            ->assertRedirect('/work/lecturers');
        $this->assertSame('submitted', $lecturer->fresh()->profile_status);

        $this->post("/work/lecturers/{$lecturer->id}/review", [
            'action' => 'approve',
            'identity_verified' => '1',
            'bank_verified' => '1',
        ])->assertRedirect("/work/lecturers/{$lecturer->id}/edit");

        $this->delete("/work/lecturers/{$lecturer->id}")->assertStatus(409);
        $this->assertDatabaseHas('lecturers', ['id' => $lecturer->id, 'profile_status' => 'approved']);
    }

    public function test_changing_identity_or_bank_data_requires_new_verification(): void
    {
        $this->post('/work/lecturers', $this->lecturerData())->assertRedirect('/work/lecturers');
        $lecturer = Lecturer::where('lecturer_code', 'GV003')->firstOrFail();
        $lecturer->update([
            'profile_status' => 'approved',
            'identity_verified_at' => now(),
            'bank_verified_at' => now(),
        ]);

        $this->put("/work/lecturers/{$lecturer->id}", $this->lecturerData([
            'national_id' => '109876543210',
            'bank_account_number' => '009876543210',
        ]))->assertRedirect('/work/lecturers');

        $lecturer->refresh();
        $this->assertSame('submitted', $lecturer->profile_status);
        $this->assertNull($lecturer->identity_verified_at);
        $this->assertNull($lecturer->bank_verified_at);
        $this->assertSame('109876543210', $lecturer->national_id);
    }

    public function test_admin_edit_form_shows_sensitive_numbers_and_blank_keeps_them(): void
    {
        $this->post('/work/lecturers', $this->lecturerData())->assertRedirect('/work/lecturers');
        $lecturer = Lecturer::where('lecturer_code', 'GV003')->firstOrFail();

        $this->get("/work/lecturers/{$lecturer->id}/edit")
            ->assertOk()
            ->assertSee('012345678901')
            ->assertSee('001234567890');

        $this->put("/work/lecturers/{$lecturer->id}", $this->lecturerData([
            'national_id' => '',
            'bank_account_number' => '',
        ]))->assertRedirect('/work/lecturers');

        $lecturer->refresh();
        $this->assertSame('012345678901', $lecturer->national_id);
        $this->assertSame('001234567890', $lecturer->bank_account_number);
    }

    public function test_profile_opens_read_only_and_edit_requires_a_separate_action(): void
    {
        $this->post('/work/lecturers', $this->lecturerData())->assertRedirect('/work/lecturers');
        $lecturer = Lecturer::where('lecturer_code', 'GV003')->firstOrFail();

        $this->get("/work/lecturers/{$lecturer->id}")
            ->assertOk()
            ->assertSee('CHẾ ĐỘ XEM')
            ->assertSee('Chỉnh sửa hồ sơ')
            ->assertSee('Le Minh Chau')
            ->assertDontSee('name="specialization"', false);

        $this->get("/work/lecturers/{$lecturer->id}/edit")
            ->assertOk()
            ->assertSee('name="specialization"', false);
    }

    public function test_export_only_contains_approved_non_sensitive_fields(): void
    {
        $this->post('/work/lecturers', $this->lecturerData())->assertRedirect('/work/lecturers');
        Lecturer::where('lecturer_code', 'GV003')->update(['profile_status' => 'approved']);
        $this->post('/work/lecturers', $this->lecturerData([
            'lecturer_code' => 'GV004',
            'name' => 'Pham Thi Dung',
            'national_id' => '109876543210',
            'bank_account_number' => '009876543210',
            'email' => 'dung@example.edu.vn',
        ]))->assertRedirect('/work/lecturers');

        $response = $this->get('/work/lecturers/export')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Le Minh Chau', $csv);
        $this->assertStringNotContainsString('Pham Thi Dung', $csv);
        $this->assertStringNotContainsString('012345678901', $csv);
        $this->assertStringNotContainsString('001234567890', $csv);
    }

    public function test_duplicate_national_id_is_rejected(): void
    {
        $this->post('/work/lecturers', $this->lecturerData())->assertRedirect('/work/lecturers');

        $this->from('/work/lecturers/create')
            ->post('/work/lecturers', $this->lecturerData([
                'lecturer_code' => 'GV004',
                'email' => 'another@example.edu.vn',
            ]))
            ->assertRedirect('/work/lecturers/create')
            ->assertSessionHasErrors('national_id');
    }

    private function lecturerData(array $overrides = []): array
    {
        return array_merge([
            'lecturer_code' => 'GV003',
            'name' => 'Le Minh Chau',
            'date_of_birth' => '1990-04-12',
            'academic_rank_id' => AcademicOption::where('type', 'rank')->where('name', 'Phó Giáo sư')->value('id'),
            'academic_degree_id' => AcademicOption::where('type', 'degree')->where('name', 'Tiến sĩ')->value('id'),
            'specialization' => 'Công nghệ thông tin',
            'department_id' => Department::where('code', 'TEST-CNTT')->value('id'),
            'email' => 'chau@example.edu.vn',
            'account_password' => 'StrongPass!2026',
            'account_password_confirmation' => 'StrongPass!2026',
            'phone' => '0901234567',
            'national_id' => '012345678901',
            'id_issued_at' => '2020-05-12',
            'id_issued_place' => 'Cục Cảnh sát QLHC về TTXH',
            'permanent_address' => 'Hoàng Mai, Hà Nội',
            'bank_account_number' => '001234567890',
            'bank_name' => 'Ngân hàng A',
            'bank_account_name' => 'LE MINH CHAU',
            'status' => 'active',
            'declaration' => '1',
        ], $overrides);
    }

    public function test_lecturer_code_and_name_are_required(): void
    {
        $this->from('/work/lecturers/create')
            ->post('/work/lecturers', ['status' => 'active'])
            ->assertRedirect('/work/lecturers/create')
            ->assertSessionHasErrors(['lecturer_code', 'name']);
    }

    public function test_academic_rank_is_required_and_phone_format_is_validated(): void
    {
        $this->from('/work/lecturers/create')
            ->post('/work/lecturers', $this->lecturerData([
                'academic_rank_id' => '',
                'phone' => '12345678',
            ]))
            ->assertRedirect('/work/lecturers/create')
            ->assertSessionHasErrors(['academic_rank_id', 'phone']);

        $this->post('/work/lecturers', $this->lecturerData(['phone' => '+84 (90) 123-45-67']))
            ->assertRedirect('/work/lecturers');

        $lecturer = Lecturer::where('lecturer_code', 'GV003')->firstOrFail();
        $this->assertSame('+84901234567', $lecturer->phone);
    }
}