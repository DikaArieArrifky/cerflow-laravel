<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    protected User $guru;
    protected User $siswa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cleanupTestUsers();

        $this->guru = User::firstOrCreate(
            ['username' => 'guru_user_mgmt'],
            [
                'namalengkap' => 'Guru Pengelola User',
                'password'    => 'secret123',
                'role'        => 'guru',
            ]
        );

        $this->siswa = User::firstOrCreate(
            ['username' => 'siswa_user_mgmt'],
            [
                'namalengkap' => 'Siswa Pengelola User',
                'password'    => 'secret123',
                'role'        => 'siswa',
            ]
        );
    }

    protected function tearDown(): void
    {
        $this->cleanupTestUsers();
        parent::tearDown();
    }

    protected function cleanupTestUsers(): void
    {
        User::whereIn('username', [
            'new_guru',
            'new_siswa',
            'user_to_edit',
            'user_edited',
            'user_keep_pwd',
            'user_change_pwd',
            'user_to_delete',
            'siswa_csv1',
            'guru_csv1',
            'hacked_user',
        ])->delete();
    }

    public function test_guru_can_view_users_page_and_json(): void
    {
        // View page
        $response = $this->actingAs($this->guru)->get('/admin/users');
        $response->assertStatus(200);
        $response->assertSee('User Management');
        $response->assertSee('guru_user_mgmt');

        // JSON endpoint
        $jsonResponse = $this->actingAs($this->guru)->getJson('/admin/users');
        $jsonResponse->assertStatus(200);
        $jsonResponse->assertJson(['success' => true]);
        $jsonResponse->assertJsonFragment(['username' => 'guru_user_mgmt']);
    }

    public function test_siswa_cannot_view_users_page(): void
    {
        $response = $this->actingAs($this->siswa)->get('/admin/users');
        $response->assertStatus(403);
    }

    public function test_guru_can_create_guru(): void
    {
        $response = $this->actingAs($this->guru)->postJson('/admin/users', [
            'username'       => 'new_guru',
            'namalengkap'    => 'Guru Baru Terdaftar',
            'password'       => 'guru456',
            'role'           => 'guru',
            'gemini_api_key' => 'AIzaSyNewGuruKey',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'status'  => 'success',
        ]);

        $newUser = User::where('username', 'new_guru')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('guru', $newUser->role);
        $this->assertEquals('AIzaSyNewGuruKey', $newUser->gemini_api_key);
        $this->assertTrue(Hash::check('guru456', $newUser->password));
    }

    public function test_guru_can_create_siswa(): void
    {
        $response = $this->actingAs($this->guru)->postJson('/admin/users', [
            'username'    => 'new_siswa',
            'namalengkap' => 'Siswa Baru Terdaftar',
            'password'    => 'siswa789',
            'role'        => 'siswa',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'status'  => 'success',
        ]);

        $newUser = User::where('username', 'new_siswa')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('siswa', $newUser->role);
        $this->assertTrue(Hash::check('siswa789', $newUser->password));
    }

    public function test_duplicate_username_is_rejected(): void
    {
        $response = $this->actingAs($this->guru)->postJson('/admin/users', [
            'username'    => $this->guru->username, // already exists
            'namalengkap' => 'Nama Duplikat',
            'password'    => 'pass123',
            'role'        => 'guru',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['username']);
    }

    public function test_guru_can_edit_user(): void
    {
        $targetUser = User::create([
            'username'    => 'user_to_edit',
            'namalengkap' => 'Nama Awal',
            'password'    => 'password_awal',
            'role'        => 'siswa',
        ]);

        $response = $this->actingAs($this->guru)->postJson('/admin/users', [
            'id'          => $targetUser->id,
            'username'    => 'user_edited',
            'namalengkap' => 'Nama Diperbarui',
            'role'        => 'guru',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id'          => $targetUser->id,
            'username'    => 'user_edited',
            'namalengkap' => 'Nama Diperbarui',
            'role'        => 'guru',
        ]);
    }

    public function test_edit_without_password_preserves_old_hash(): void
    {
        $targetUser = User::create([
            'username'    => 'user_keep_pwd',
            'namalengkap' => 'Tetap Password',
            'password'    => 'password_tetap_123',
            'role'        => 'siswa',
        ]);

        $oldHash = $targetUser->password;

        $response = $this->actingAs($this->guru)->postJson('/admin/users', [
            'id'          => $targetUser->id,
            'username'    => 'user_keep_pwd',
            'namalengkap' => 'Nama Berubah Password Sama',
            'role'        => 'siswa',
            'password'    => '', // Empty password
        ]);

        $response->assertStatus(200);
        $freshUser = $targetUser->fresh();
        $this->assertEquals($oldHash, $freshUser->password);
        $this->assertTrue(Hash::check('password_tetap_123', $freshUser->password));
    }

    public function test_edit_with_password_changes_password(): void
    {
        $targetUser = User::create([
            'username'    => 'user_change_pwd',
            'namalengkap' => 'Ganti Password',
            'password'    => 'old_pass',
            'role'        => 'siswa',
        ]);

        $response = $this->actingAs($this->guru)->postJson('/admin/users', [
            'id'          => $targetUser->id,
            'username'    => 'user_change_pwd',
            'namalengkap' => 'Ganti Password',
            'role'        => 'siswa',
            'password'    => 'new_secret_pass',
        ]);

        $response->assertStatus(200);
        $freshUser = $targetUser->fresh();
        $this->assertTrue(Hash::check('new_secret_pass', $freshUser->password));
        $this->assertFalse(Hash::check('old_pass', $freshUser->password));
    }

    public function test_delete_user_works(): void
    {
        $targetUser = User::create([
            'username'    => 'user_to_delete',
            'namalengkap' => 'Akan Dihapus',
            'password'    => 'secret',
            'role'        => 'siswa',
        ]);

        $response = $this->actingAs($this->guru)->deleteJson("/admin/users/{$targetUser->id}");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('users', ['id' => $targetUser->id]);
    }

    public function test_user_cannot_delete_themselves(): void
    {
        $response = $this->actingAs($this->guru)->deleteJson("/admin/users/{$this->guru->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $this->guru->id]);
    }

    public function test_download_template_csv(): void
    {
        $response = $this->actingAs($this->guru)->get('/admin/users/template');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
    }

    public function test_import_csv_works(): void
    {
        $csvContent = "username,namalengkap,password,role\n" .
                      "siswa_csv1,Siswa CSV Pertama,pass123,siswa\n" .
                      "guru_csv1,Guru CSV Pertama,pass456,guru\n";

        $file = UploadedFile::fake()->createWithContent('users.csv', $csvContent);

        $response = $this->actingAs($this->guru)->postJson('/admin/users/import', [
            'csv_file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status'  => 'success',
        ]);

        $this->assertDatabaseHas('users', ['username' => 'siswa_csv1', 'role' => 'siswa']);
        $this->assertDatabaseHas('users', ['username' => 'guru_csv1', 'role' => 'guru']);
    }

    public function test_invalid_csv_rejected(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->guru)->postJson('/admin/users/import', [
            'csv_file' => $file,
        ]);

        $response->assertStatus(422);
    }

    public function test_siswa_cannot_perform_crud_on_users(): void
    {
        // Siswa cannot create
        $resCreate = $this->actingAs($this->siswa)->postJson('/admin/users', [
            'username'    => 'hacked_user',
            'namalengkap' => 'Hacked',
            'password'    => 'hack123',
            'role'        => 'guru',
        ]);
        $resCreate->assertStatus(403);

        // Siswa cannot delete
        $resDelete = $this->actingAs($this->siswa)->deleteJson("/admin/users/{$this->guru->id}");
        $resDelete->assertStatus(403);

        // Siswa cannot import
        $resImport = $this->actingAs($this->siswa)->postJson('/admin/users/import', []);
        $resImport->assertStatus(403);
    }
}
