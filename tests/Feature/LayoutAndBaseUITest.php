<?php

namespace Tests\Feature;

use App\Models\CerMap;
use App\Models\User;
use Tests\TestCase;

class LayoutAndBaseUITest extends TestCase
{
    protected User $guru;
    protected User $siswa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guru = User::firstOrCreate(
            ['username' => 'guru_ui_test'],
            [
                'namalengkap' => 'Guru UI Test',
                'password' => 'secret123',
                'role' => 'guru',
            ]
        );

        $this->siswa = User::firstOrCreate(
            ['username' => 'siswa_ui_test'],
            [
                'namalengkap' => 'Siswa UI Test',
                'password' => 'secret123',
                'role' => 'siswa',
            ]
        );

        CerMap::firstOrCreate(
            ['title' => 'Topik Uji Tata Letak UI'],
            [
                'allow_feedback' => true,
                'allow_reading' => true,
                'reading_text' => 'Teks bacaan uji coba',
            ]
        );
    }

    /**
     * 1. Guest membuka /admin/dashboard -> redirect login.
     */
    public function test_guest_visiting_admin_dashboard_redirects_to_login(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect(route('login'));
    }

    /**
     * 2. Guest membuka /student -> redirect login.
     */
    public function test_guest_visiting_student_page_redirects_to_login(): void
    {
        $response = $this->get('/student');

        $response->assertRedirect(route('login'));
    }

    /**
     * 3. Guru login -> admin dashboard dengan layout dan navbar guru.
     * 5. Navbar tidak menampilkan menu role lain.
     */
    public function test_guru_dashboard_renders_with_proper_layout_and_guru_navbar(): void
    {
        $response = $this->actingAs($this->guru)->get('/admin/dashboard');

        $response->assertStatus(200);

        // Layout Base Elements
        $response->assertSee('Curriculum Studio - CER Flow');
        $response->assertSee('css/style.css');
        $response->assertSee('js/dialog-utils.js');
        $response->assertSee('Login as:');
        $response->assertSee('@guru_ui_test');
        $response->assertSee('Guru UI Test');
        $response->assertSee('Version 2.0 (Laravel 11)');
        $response->assertSee('id="global-confirm-modal"', false);
        $response->assertSee('id="global-alert-modal"', false);

        // Guru Navbar Elements
        $response->assertSee('📚 Materi');
        $response->assertSee('👥 Users');
        $response->assertSee('📝 Log Aktivitas');
        $response->assertSee('📊 Laporan Nilai');
        $response->assertSee('⚙️ Settings');
        $response->assertSee('LOGOUT');

        // Studio Layout Body
        $response->assertSee('CER Map');
        $response->assertSee('Studio');
        $response->assertSee('Create and publish learning modules');

        // Navbar Isolation (MUST NOT contain student menus)
        $response->assertDontSee('📖 Pembelajaran');
        $response->assertDontSee('📊 Riwayat');
    }

    /**
     * 4. Siswa login -> student page dengan layout dan navbar siswa.
     * 5. Navbar tidak menampilkan menu role lain.
     */
    public function test_siswa_page_renders_with_proper_layout_and_siswa_navbar(): void
    {
        $response = $this->actingAs($this->siswa)->get('/student');

        $response->assertStatus(200);

        // Layout Base Elements
        $response->assertSee('Learning Workspace - CER Flow');
        $response->assertSee('css/style.css');
        $response->assertSee('js/dialog-utils.js');
        $response->assertSee('Login as:');
        $response->assertSee('@siswa_ui_test');
        $response->assertSee('Siswa UI Test');
        $response->assertSee('Version 2.0 (Laravel 11)');
        $response->assertSee('id="global-confirm-modal"', false);
        $response->assertSee('id="global-alert-modal"', false);

        // Siswa Navbar Elements
        $response->assertSee('📖 Pembelajaran');
        $response->assertSee('📊 Riwayat');
        $response->assertSee('LOGOUT');

        // Student Topic Discovery View Elements
        $response->assertSee('Curriculum Discovery');
        $response->assertSee('Select Topic');
        $response->assertSee('Topik Uji Tata Letak UI');
        $response->assertSee('Mulai Sekarang');

        // Navbar Isolation (MUST NOT contain guru menus)
        $response->assertDontSee('📚 Materi');
        $response->assertDontSee('👥 Users');
        $response->assertDontSee('📝 Log Aktivitas');
        $response->assertDontSee('⚙️ Settings');
    }

    /**
     * 6. Logout tetap bekerja.
     */
    public function test_logout_works_from_navbar_form(): void
    {
        $response = $this->actingAs($this->guru)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    /**
     * 7. Halaman login tetap bekerja dengan layout baru & asset css.
     */
    public function test_login_page_renders_and_includes_style_css(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('css/style.css');
        $response->assertSee('CER');
        $response->assertSee('Flow');
        $response->assertSee('Portal');
        $response->assertSee('Username');
        $response->assertSee('Password');
        $response->assertSee('MASUK KE DASHBOARD');
    }

    /**
     * 8. Base Settings Page (Requirement 9)
     */
    public function test_guru_can_access_and_update_settings(): void
    {
        $response = $this->actingAs($this->guru)->get('/admin/settings');

        $response->assertStatus(200);
        $response->assertSee('Pengaturan');
        $response->assertSee('Aplikasi');
        $response->assertSee('Your Gemini API Key');
        $response->assertSee('Simpan Perubahan');

        // Update API Key
        $postResponse = $this->actingAs($this->guru)->post('/admin/settings', [
            'gemini_api_key' => 'AIzaSyTestKeyUpdated12345',
        ]);

        $postResponse->assertSessionHas('status', 'Pengaturan berhasil diperbarui.');
        $this->assertEquals('AIzaSyTestKeyUpdated12345', $this->guru->fresh()->gemini_api_key);
    }

    /**
     * Student cannot access admin settings page.
     */
    public function test_siswa_cannot_access_admin_settings(): void
    {
        $response = $this->actingAs($this->siswa)->get('/admin/settings');

        $response->assertStatus(403);
    }

    /**
     * Placeholder routes work without broken links.
     */
    public function test_placeholder_routes_return_200_without_broken_links(): void
    {
        $guruMaps = $this->actingAs($this->guru)->get('/admin/maps');
        $guruMaps->assertStatus(200);
        $guruMaps->assertSee('Content');

        $guruUsers = $this->actingAs($this->guru)->get('/admin/users');
        $guruUsers->assertStatus(200);
        $guruUsers->assertSee('User Management');

        $siswaHistory = $this->actingAs($this->siswa)->get('/student/history');
        $siswaHistory->assertStatus(200);
        $siswaHistory->assertSee('Riwayat Pembelajaran');
    }
}
