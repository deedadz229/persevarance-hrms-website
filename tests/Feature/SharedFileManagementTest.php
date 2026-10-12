<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SharedFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SharedFileManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function userForRole(string $roleName): User
    {
        $role = Role::where('name', $roleName)->firstOrFail();

        return User::where('role_id', $role->id)->firstOrFail();
    }

    public function test_super_admin_can_view_shared_files_index(): void
    {
        $admin = $this->userForRole('super_admin');

        $response = $this->actingAs($admin)->get(route('shared-files.index'));

        $response->assertOk();
        $response->assertSee('Aktivitas File dan Dokumen');
        $response->assertSee('Unggah File Baru');
    }

    public function test_super_admin_can_upload_new_file_and_it_is_pending_by_default(): void
    {
        Storage::fake('local');
        $admin = $this->userForRole('super_admin');

        $file = UploadedFile::fake()->create('contract.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($admin)->post(route('shared-files.store'), [
            'file' => $file,
            'name' => 'Kontrak Kerja Bersama',
            'category' => 'Regulasi & Legal',
            'description' => 'Dokumen SOP dan regulasi ketenagakerjaan.',
        ]);

        $response->assertRedirect(route('shared-files.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('shared_files', [
            'name' => 'Kontrak Kerja Bersama.pdf',
            'category' => 'Regulasi & Legal',
            'sync_status' => 'pending',
            'uploaded_by' => $admin->id,
        ]);

        $sharedFile = SharedFile::where('category', 'Regulasi & Legal')->first();
        $this->assertNotNull($sharedFile);
        $this->assertTrue(Storage::disk('local')->exists($sharedFile->storage_path));
        $this->assertEquals(0, $sharedFile->recipients()->count());
    }

    public function test_super_admin_can_upload_and_immediately_share_with_taiwan_account(): void
    {
        Storage::fake('local');
        $admin = $this->userForRole('super_admin');
        $taiwanUser = $this->userForRole('taiwan_account');

        $file = UploadedFile::fake()->create('report-taiwan.xlsx', 500);

        $response = $this->actingAs($admin)->post(route('shared-files.store'), [
            'file' => $file,
            'name' => 'Laporan Bulanan Taiwan',
            'category' => 'Laporan Manajerial',
            'recipient_ids' => [$taiwanUser->id],
        ]);

        $response->assertRedirect(route('shared-files.index'));

        $sharedFile = SharedFile::where('name', 'Laporan Bulanan Taiwan.xlsx')->first();
        $this->assertNotNull($sharedFile);
        $this->assertEquals('synced', $sharedFile->sync_status);
        $this->assertTrue($sharedFile->recipients->contains($taiwanUser->id));

        $this->assertDatabaseHas('sync_logs', [
            'entity_type' => SharedFile::class,
            'entity_id' => $sharedFile->id,
            'status' => 'success',
        ]);
    }

    public function test_taiwan_account_can_only_see_files_explicitly_shared_with_them(): void
    {
        Storage::fake('local');
        $admin = $this->userForRole('super_admin');
        $taiwanUser = $this->userForRole('taiwan_account');

        // File 1: Unshared (pending)
        $unshared = SharedFile::create([
            'name' => 'Dokumen Internal Rahasia.pdf',
            'category' => 'Internal',
            'size' => 1024,
            'storage_path' => 'shared_files/internal.pdf',
            'owner_name' => 'Admin',
            'sync_status' => 'pending',
            'uploaded_by' => $admin->id,
        ]);

        // File 2: Shared with Taiwan Account
        $shared = SharedFile::create([
            'name' => 'Laporan Khusus Taiwan.pdf',
            'category' => 'Publik',
            'size' => 2048,
            'storage_path' => 'shared_files/taiwan.pdf',
            'owner_name' => 'Admin',
            'sync_status' => 'synced',
            'uploaded_by' => $admin->id,
        ]);
        $shared->recipients()->attach($taiwanUser->id, ['shared_at' => now()]);

        // Taiwan Account visits index
        $response = $this->actingAs($taiwanUser)->get(route('shared-files.index'));

        $response->assertOk();
        $response->assertSee('Laporan Khusus Taiwan.pdf');
        $response->assertDontSee('Dokumen Internal Rahasia.pdf');
        $response->assertDontSee('Unggah File Baru');
    }

    public function test_taiwan_account_cannot_upload_or_modify_or_delete_files(): void
    {
        Storage::fake('local');
        $taiwanUser = $this->userForRole('taiwan_account');
        $admin = $this->userForRole('super_admin');

        $file = UploadedFile::fake()->create('hacked.pdf', 100);

        // Upload blocked
        $this->actingAs($taiwanUser)
            ->post(route('shared-files.store'), ['file' => $file])
            ->assertForbidden();

        $sharedFile = SharedFile::create([
            'name' => 'Test File.pdf',
            'size' => 100,
            'storage_path' => 'shared_files/test.pdf',
            'owner_name' => 'Admin',
            'sync_status' => 'synced',
            'uploaded_by' => $admin->id,
        ]);

        // Update blocked
        $this->actingAs($taiwanUser)
            ->put(route('shared-files.update', $sharedFile), ['name' => 'Ganti Nama'])
            ->assertForbidden();

        // Share/Revoke blocked
        $this->actingAs($taiwanUser)
            ->post(route('shared-files.share', $sharedFile), ['recipient_ids' => [$taiwanUser->id]])
            ->assertForbidden();

        $this->actingAs($taiwanUser)
            ->post(route('shared-files.revoke', $sharedFile))
            ->assertForbidden();

        // Delete blocked
        $this->actingAs($taiwanUser)
            ->delete(route('shared-files.destroy', $sharedFile))
            ->assertForbidden();
    }

    public function test_taiwan_account_can_download_shared_file_but_not_unshared_file(): void
    {
        Storage::fake('local');
        $admin = $this->userForRole('super_admin');
        $taiwanUser = $this->userForRole('taiwan_account');

        Storage::disk('local')->put('shared_files/unshared.pdf', 'SECRET CONTENT');
        $unshared = SharedFile::create([
            'name' => 'unshared.pdf',
            'size' => 14,
            'storage_path' => 'shared_files/unshared.pdf',
            'owner_name' => 'Admin',
            'sync_status' => 'pending',
            'uploaded_by' => $admin->id,
        ]);

        Storage::disk('local')->put('shared_files/allowed.pdf', 'ALLOWED CONTENT');
        $shared = SharedFile::create([
            'name' => 'allowed.pdf',
            'size' => 15,
            'storage_path' => 'shared_files/allowed.pdf',
            'owner_name' => 'Admin',
            'sync_status' => 'synced',
            'uploaded_by' => $admin->id,
        ]);
        $shared->recipients()->attach($taiwanUser->id, ['shared_at' => now()]);

        // Attempt downloading unshared file -> 403 Forbidden
        $this->actingAs($taiwanUser)
            ->get(route('shared-files.download', $unshared))
            ->assertForbidden();

        // Attempt downloading allowed file -> OK
        $response = $this->actingAs($taiwanUser)
            ->get(route('shared-files.download', $shared));

        $response->assertOk();
    }

    public function test_admin_can_revoke_recipient_access(): void
    {
        Storage::fake('local');
        $admin = $this->userForRole('super_admin');
        $taiwanUser = $this->userForRole('taiwan_account');

        $shared = SharedFile::create([
            'name' => 'to-revoke.pdf',
            'size' => 100,
            'storage_path' => 'shared_files/to-revoke.pdf',
            'owner_name' => 'Admin',
            'sync_status' => 'synced',
            'uploaded_by' => $admin->id,
        ]);
        $shared->recipients()->attach($taiwanUser->id, ['shared_at' => now()]);

        $this->assertEquals(1, $shared->recipients()->count());

        $response = $this->actingAs($admin)
            ->post(route('shared-files.revoke', $shared), [
                'user_id' => $taiwanUser->id,
            ]);

        $response->assertSessionHas('success');
        $this->assertEquals(0, $shared->fresh()->recipients()->count());
        $this->assertEquals('pending', $shared->fresh()->sync_status);

        $this->assertDatabaseHas('sync_logs', [
            'entity_type' => SharedFile::class,
            'entity_id' => $shared->id,
            'status' => 'revoked',
        ]);
    }
}
