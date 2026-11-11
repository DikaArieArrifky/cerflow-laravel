<?php

namespace Tests\Feature;

use App\Models\CerMap;
use App\Models\Score;
use App\Models\User;
use App\Models\UserSession;
use Tests\TestCase;

class ReportTest extends TestCase
{
    protected User $guru;
    protected User $siswa1;
    protected User $siswa2;
    protected CerMap $map;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guru = User::firstOrCreate(
            ['username' => 'guru_report_test'],
            [
                'namalengkap' => 'Guru Report Test',
                'password'    => 'secret123',
                'role'        => 'guru',
            ]
        );

        $this->siswa1 = User::firstOrCreate(
            ['username' => 'siswa1_report_test'],
            [
                'namalengkap' => 'Siswa Satu Report',
                'password'    => 'secret123',
                'role'        => 'siswa',
            ]
        );

        $this->siswa2 = User::firstOrCreate(
            ['username' => 'siswa2_report_test'],
            [
                'namalengkap' => 'Siswa Dua Report',
                'password'    => 'secret123',
                'role'        => 'siswa',
            ]
        );

        $this->map = CerMap::firstOrCreate(
            ['title' => 'Materi Biologi Ekosistem Report'],
            [
                'allow_feedback' => true,
                'allow_reading'  => true,
            ]
        );
    }

    public function test_guru_can_access_report_page(): void
    {
        $response = $this->actingAs($this->guru)->get('/admin/report');

        $response->assertStatus(200);
        $response->assertSee('Performance Analytics');
        $response->assertSee('Filter Nama Siswa');
        $response->assertSee('Export to Excel');
    }

    public function test_siswa_cannot_access_report_page(): void
    {
        $response = $this->actingAs($this->siswa1)->get('/admin/report');

        $response->assertStatus(403);
    }

    public function test_scores_are_displayed_with_student_and_map_details(): void
    {
        $session = UserSession::create([
            'user_id'      => $this->siswa1->id,
            'map_id'       => $this->map->id,
            'start_time'   => now(),
            'is_submitted' => true,
        ]);

        $score = Score::create([
            'user_id'      => $this->siswa1->id,
            'map_id'       => $this->map->id,
            'session_id'   => $session->id,
            'score'        => 85.5,
            'map_data'     => [
                'triplets'    => [
                    ['id' => 1, 'claim' => 'C1', 'evidence' => 'E1', 'reasoning' => 'R1']
                ],
                'connections' => [
                    ['from' => 'c-1', 'to' => 'e-1']
                ],
            ],
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->guru)->get('/admin/report');

        $response->assertStatus(200);
        $response->assertSee('Siswa Satu Report');
        $response->assertSee('siswa1_report_test');
        $response->assertSee('Materi Biologi Ekosistem Report');
        $response->assertSee('85.5%');
        $response->assertSee('Lihat Map');

        // Cleanup
        $score->delete();
        $session->delete();
    }

    public function test_guru_can_view_student_submitted_map(): void
    {
        $score = Score::create([
            'user_id'      => $this->siswa1->id,
            'map_id'       => $this->map->id,
            'score'        => 90.0,
            'map_data'     => [
                'triplets'    => [
                    ['id' => 10, 'claim' => 'Klaim Ekosistem', 'evidence' => 'Bukti Lapangan', 'reasoning' => 'Logika Teori']
                ],
                'connections' => [
                    ['from' => 'c-10', 'to' => 'e-10']
                ],
            ],
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->guru)->get("/view-map/{$score->id}");

        $response->assertStatus(200);
        $response->assertSee('Hasil Map:');
        $response->assertSee('Siswa Satu Report');
        $response->assertSee('Klaim Ekosistem');

        // Siswa 1 (owner) can also view their own map
        $ownerResponse = $this->actingAs($this->siswa1)->get("/view-map/{$score->id}");
        $ownerResponse->assertStatus(200);
        $ownerResponse->assertSee('Hasil Map:');

        // Siswa 2 (other student) is REJECTED with 403 (No IDOR allowed)
        $idorResponse = $this->actingAs($this->siswa2)->get("/view-map/{$score->id}");
        $idorResponse->assertStatus(403);

        // Cleanup
        $score->delete();
    }
}
