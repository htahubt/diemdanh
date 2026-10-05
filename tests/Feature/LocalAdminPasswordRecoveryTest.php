<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LocalAdminPasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_recovery_resets_admin_password_with_bcrypt(): void
    {
        config(['app.env' => 'local']);
        $admin = User::factory()->create([
            'email' => 'ad@utm.edu.vn',
            'role' => 'admin',
        ]);

        $this->get('/local-admin-password')->assertOk();
        $nonce = session('local_admin_password_nonce');

        $this->post('/local-admin-password', [
            'nonce' => $nonce,
            'password' => 'NewDemoAdminPassword!2026',
            'password_confirmation' => 'NewDemoAdminPassword!2026',
        ])->assertRedirect('/login');

        $admin->refresh();
        $this->assertTrue(Hash::driver('bcrypt')->check('NewDemoAdminPassword!2026', $admin->password));
    }

    public function test_local_recovery_is_not_available_outside_local_environment(): void
    {
        config(['app.env' => 'production']);

        $this->get('/local-admin-password')->assertNotFound();
    }
}