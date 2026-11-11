<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure test users exist
        User::firstOrCreate(
            ['username' => 'test_guru'],
            [
                'namalengkap' => 'Guru Penguji',
                'password' => 'guru123',
                'role' => 'guru',
            ]
        );

        User::firstOrCreate(
            ['username' => 'test_siswa'],
            [
                'namalengkap' => 'Siswa Penguji',
                'password' => 'siswa123',
                'role' => 'siswa',
            ]
        );
    }

    /**
     * Test login view can be rendered.
     */
    public function test_login_page_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('CER');
        $response->assertSee('Flow');
        $response->assertSee('Username');
        $response->assertSee('Password');
    }

    /**
     * Test 4: Test login guru.
     * Guru should authenticate and be redirected to admin dashboard.
     */
    public function test_login_guru_success_and_redirects_to_admin_dashboard(): void
    {
        $response = $this->post('/login', [
            'username' => 'test_guru',
            'password' => 'guru123',
        ]);

        $this->assertAuthenticated();
        $this->assertEquals('guru', auth()->user()->role);
        $response->assertRedirect(route('admin.dashboard'));
    }

    /**
     * Test 5: Test login siswa.
     * Siswa should authenticate and be redirected to student page.
     */
    public function test_login_siswa_success_and_redirects_to_student_page(): void
    {
        $response = $this->post('/login', [
            'username' => 'test_siswa',
            'password' => 'siswa123',
        ]);

        $this->assertAuthenticated();
        $this->assertEquals('siswa', auth()->user()->role);
        $response->assertRedirect(route('student.index'));
    }

    /**
     * Test 6: Test login password salah.
     * Authentication should fail, remain guest, and display error.
     */
    public function test_login_with_wrong_password_fails(): void
    {
        $response = $this->post('/login', [
            'username' => 'test_guru',
            'password' => 'wrongpassword',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('username');
    }

    /**
     * Test 7: Test akses route guru oleh siswa.
     * Siswa should receive 403 Forbidden when accessing admin route.
     */
    public function test_siswa_cannot_access_guru_route(): void
    {
        $siswa = User::where('username', 'test_siswa')->first();

        $response = $this->actingAs($siswa)->get('/admin/dashboard');

        $response->assertStatus(403);
    }

    /**
     * Test 8: Test akses route siswa oleh guru.
     * Guru should receive 403 Forbidden when accessing student route.
     */
    public function test_guru_cannot_access_siswa_route(): void
    {
        $guru = User::where('username', 'test_guru')->first();

        $response = $this->actingAs($guru)->get('/student');

        $response->assertStatus(403);
    }

    /**
     * Test 9: Test logout.
     * User should be logged out and redirected to login page.
     */
    public function test_authenticated_user_can_logout(): void
    {
        $guru = User::where('username', 'test_guru')->first();

        $response = $this->actingAs($guru)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    /**
     * Test unauthenticated guest accessing protected routes is redirected to login.
     */
    public function test_guest_is_redirected_to_login_for_protected_routes(): void
    {
        $responseAdmin = $this->get('/admin/dashboard');
        $responseAdmin->assertRedirect(route('login'));

        $responseStudent = $this->get('/student');
        $responseStudent->assertRedirect(route('login'));
    }

    /**
     * Test compatibility with existing bcrypt hashes (e.g. $2y$10 from webCerflow).
     */
    public function test_existing_bcrypt_hash_compatibility(): void
    {
        // Existing hash for 'admin123' generated with PHP native password_hash($2y$10$)
        $existingHash = password_hash('existingpass123', PASSWORD_BCRYPT, ['cost' => 10]);

        $user = User::updateOrCreate(
            ['username' => 'native_php_user'],
            [
                'namalengkap' => 'Native PHP Migrated User',
                'password' => $existingHash,
                'role' => 'guru',
            ]
        );

        // Manually set raw hash to avoid double hashing if mutator/cast intervened
        \Illuminate\Support\Facades\DB::table('users')
            ->where('id', $user->id)
            ->update(['password' => $existingHash]);

        $response = $this->post('/login', [
            'username' => 'native_php_user',
            'password' => 'existingpass123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('admin.dashboard'));
    }
}
