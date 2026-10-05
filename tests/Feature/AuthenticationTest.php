<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_for_school_pages(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/work/lecturers')->assertRedirect('/login');
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create(['email' => 'lecturer@example.edu.vn']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->get('/')->assertOk();

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }
}