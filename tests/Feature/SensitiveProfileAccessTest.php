<?php

namespace Tests\Feature;

use App\Models\Lecturer;
use App\Models\LecturerDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SensitiveProfileAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_lecturer_can_view_only_their_own_sensitive_profile(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.edu.vn']);
        $other = $this->lecturer('GV-OTHER', 'other@example.edu.vn');

        $this->actingAs($owner)
            ->get('/work/lecturers')
            ->assertOk()
            ->assertSee('Chưa có hồ sơ giảng viên')
            ->assertDontSee($other->name);

        $this->get("/work/lecturers/{$other->id}/edit")->assertForbidden();
    }

    public function test_system_admin_has_full_sensitive_profile_access(): void
    {
        $lecturer = $this->lecturer('GV-PRIVATE', 'lecturer@example.edu.vn');
        $admin = User::factory()->create([
            'role' => 'admin',
            'can_view_sensitive_profiles' => false,
        ]);

        $this->actingAs($admin)
            ->get("/work/lecturers/{$lecturer->id}/edit")
            ->assertOk()
            ->assertSee('012345678901')
            ->assertSee('001234567890');

        $granted = User::factory()->create([
            'role' => 'staff',
            'can_view_sensitive_profiles' => true,
        ]);

        $this->actingAs($granted)
            ->get("/work/lecturers/{$lecturer->id}/edit")
            ->assertOk()
            ->assertSee('012345678901')
            ->assertSee('001234567890');
    }

    public function test_unprivileged_user_cannot_view_another_persons_sensitive_data_or_documents(): void
    {
        Storage::fake('local');
        $lecturer = $this->lecturer('GV-PRIVATE', 'lecturer@example.edu.vn');
        $path = 'lecturer-documents/' . $lecturer->id . '/id.pdf';
        Storage::disk('local')->put($path, 'private document');
        $document = $lecturer->documents()->create([
            'document_type' => 'identity_documents',
            'original_name' => 'id.pdf',
            'storage_path' => $path,
            'mime_type' => 'application/pdf',
            'file_size' => 16,
        ]);
        $user = User::factory()->create(['email' => 'unprivileged@example.edu.vn']);

        $this->actingAs($user)
            ->get("/work/lecturers/{$lecturer->id}/edit")
            ->assertForbidden();
        $this->get("/work/lecturers/{$lecturer->id}/documents/{$document->id}")->assertForbidden();

        $granted = User::factory()->create(['can_view_sensitive_profiles' => true]);
        $this->actingAs($granted)
            ->get("/work/lecturers/{$lecturer->id}/documents/{$document->id}")
            ->assertDownload('id.pdf');
    }

    public function test_owner_can_view_their_own_cccd(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.edu.vn']);
        $lecturer = $this->lecturer('GV-OWNER', $owner->email);

        $this->actingAs($owner)
            ->get("/work/lecturers/{$lecturer->id}/edit")
            ->assertOk()
            ->assertSee('012345678901')
            ->assertSee('001234567890');
    }

    private function lecturer(string $code, string $email): Lecturer
    {
        return Lecturer::create([
            'lecturer_code' => $code,
            'name' => 'Private Lecturer',
            'email' => $email,
            'national_id' => '012345678901',
            'national_id_hash' => hash_hmac('sha256', '012345678901', config('app.key')),
            'bank_account_number' => '001234567890',
            'profile_status' => 'submitted',
        ]);
    }
}