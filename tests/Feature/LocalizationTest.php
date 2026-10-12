<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function userForRole(string $roleName): User
    {
        $role = Role::where('name', $roleName)->firstOrFail();

        return User::where('role_id', $role->id)->firstOrFail();
    }

    public function test_default_locale_is_indonesian(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $this->assertEquals('id', app()->getLocale());
    }

    public function test_switching_locale_to_english_updates_session(): void
    {
        $response = $this->get(route('locale.switch', 'en'));

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'en');

        // Follow request with session to verify English strings render
        $loginResponse = $this->withSession(['locale' => 'en'])->get('/login');
        $loginResponse->assertOk();
        $this->assertEquals('en', app()->getLocale());
    }

    public function test_switching_locale_with_invalid_key_fails_validation(): void
    {
        $response = $this->get('/locale/invalid_lang');

        $response->assertStatus(400);
    }

    public function test_navigation_renders_in_english_when_locale_is_en(): void
    {
        $admin = $this->userForRole('super_admin');

        $response = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'));

        $response->assertOk();
        $this->assertEquals('en', app()->getLocale());
        $response->assertSee('Employees');
        $response->assertSee('Attendance');
        $response->assertSee('Settings');
        $response->assertSee('File Activity');
    }

    public function test_navigation_renders_in_indonesian_when_locale_is_id(): void
    {
        $admin = $this->userForRole('super_admin');

        $response = $this->actingAs($admin)
            ->withSession(['locale' => 'id'])
            ->get(route('dashboard'));

        $response->assertOk();
        $this->assertEquals('id', app()->getLocale());
        $response->assertSee('Karyawan');
        $response->assertSee('Absensi');
        $response->assertSee('Pengaturan');
        $response->assertSee('Aktivitas File');
    }

    public function test_settings_page_displays_language_preference_and_switches_properly(): void
    {
        $admin = $this->userForRole('super_admin');

        $responseId = $this->actingAs($admin)
            ->withSession(['locale' => 'id'])
            ->get(route('settings.index'));

        $responseId->assertOk();
        $responseId->assertSee('Preferensi Bahasa Sistem');
        $responseId->assertSee('Informasi Aplikasi &amp; Sistem', false);

        $responseEn = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('settings.index'));

        $responseEn->assertOk();
        $responseEn->assertSee('System Language Preference');
        $responseEn->assertSee('Application &amp; System Information', false);
    }

    public function test_shared_files_page_renders_localized_headers(): void
    {
        $admin = $this->userForRole('super_admin');

        $responseEn = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('shared-files.index'));

        $responseEn->assertOk();
        $responseEn->assertSee('File Activity &amp; Documents', false);
        $responseEn->assertSee('Upload New File');

        $responseId = $this->actingAs($admin)
            ->withSession(['locale' => 'id'])
            ->get(route('shared-files.index'));

        $responseId->assertOk();
        $responseId->assertSee('Aktivitas File dan Dokumen');
        $responseId->assertSee('Unggah File Baru');
    }
}
