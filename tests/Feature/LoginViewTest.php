<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginViewTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_login_page_renders_with_background_video_and_modern_ui(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('login-background.mp4');
        $response->assertSee('video-background');
        $response->assertSee('video-overlay');
        $response->assertSee('PERSEVARANCE HRMS');
        $response->assertSee('Human Resource Management System');
        $response->assertSee('PT Persevarance');
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('togglePassword');
        $response->assertSee('Login');
    }

    public function test_login_with_empty_credentials_fails_validation(): void
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['email', 'password']);
    }

    public function test_login_with_invalid_credentials_shows_error_message(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'admin@aranus.local',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email' => 'Email atau password salah.']);
        $this->assertGuest();

        $followResponse = $this->get('/login');
        $followResponse->assertOk();
    }

    public function test_login_with_valid_credentials_redirects_to_dashboard(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@aranus.local',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_logout_redirects_to_login_page(): void
    {
        $user = User::where('email', 'admin@aranus.local')->first();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
