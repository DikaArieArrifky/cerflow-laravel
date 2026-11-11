<?php

namespace Tests\Feature;

use App\Models\CerMap;
use App\Models\Score;
use App\Models\Triplet;
use App\Models\User;
use App\Models\UserSession;
use Tests\TestCase;

class StudentInteractiveCerTest extends TestCase
{
    protected User $guru;
    protected User $siswa1;
    protected User $siswa2;
    protected CerMap $map;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guru = User::firstOrCreate(
            ['username' => 'guru_student_test'],
            [
                'namalengkap' => 'Guru Student Test',
                'password'    => 'secret123',
                'role'        => 'guru',
            ]
        );

        $this->siswa1 = User::firstOrCreate(
            ['username' => 'siswa1_cer_test'],
            [
                'namalengkap' => 'Siswa Satu CER',
                'password'    => 'secret123',
                'role'        => 'siswa',
            ]
        );

        $this->siswa2 = User::firstOrCreate(
            ['username' => 'siswa2_cer_test'],
            [
                'namalengkap' => 'Siswa Dua CER',
                'password'    => 'secret123',
                'role'        => 'siswa',
            ]
        );

        $this->map = CerMap::firstOrCreate(
            ['title' => 'Materi Fotosintesis Student Test 4'],
            [
                'allow_feedback' => true,
                'allow_reading'  => true,
                'reading_text'   => 'Fotosintesis menghasilkan oksigen dan glukosa.',
            ]
        );

        if ($this->map->triplets()->count() === 0) {
            Triplet::create([
                'map_id'    => $this->map->id,
                'claim'     => 'Klaim Oksigen',
                'evidence'  => 'Bukti Gelembung Air',
                'reasoning' => 'Reaksi Terang Memecah H2O',
            ]);
            Triplet::create([
                'map_id'    => $this->map->id,
                'claim'     => 'Klaim Glukosa',
                'evidence'  => 'Uji Amilum Lugol',
                'reasoning' => 'Siklus Calvin Membentuk Gula',
            ]);
        }
    }

    public function test_siswa_can_access_student_workspace(): void
    {
        $response = $this->actingAs($this->siswa1)->get('/student');

        $response->assertStatus(200);
        $response->assertSee('Curriculum Discovery');
        $response->assertSee('Select Topic');
        $response->assertSee('Materi Fotosintesis Student Test 4');
        $response->assertSee('siswa.js');
        $response->assertSee('api-client.js');
    }

    public function test_guru_cannot_access_student_workspace(): void
    {
        $response = $this->actingAs($this->guru)->get('/student');

        $response->assertStatus(403);
    }

    public function test_student_complete_flow_start_session_log_actions_and_submit(): void
    {
        // 1. Start Session
        $startRes = $this->actingAs($this->siswa1)->postJson('/api/sessions', [
            'map_id' => $this->map->id,
        ]);

        $startRes->assertStatus(201);
        $sessionId = $startRes->json('session_id');
        $this->assertNotNull($sessionId);

        // 2. Log view_reading
        $logReading = $this->actingAs($this->siswa1)->postJson('/api/logs', [
            'session_id'  => $sessionId,
            'action_type' => 'view_reading',
            'action_data' => ['map_id' => $this->map->id],
        ]);
        $logReading->assertStatus(201);

        // 3. Log move
        $triplets = $this->map->triplets;
        $t1 = $triplets[0];
        $t2 = $triplets[1];

        $logMove = $this->actingAs($this->siswa1)->postJson('/api/logs', [
            'session_id'  => $sessionId,
            'action_type' => 'move',
            'action_data' => [
                'card'      => "e-{$t1->id}",
                'card_type' => 'evidence',
                'from'      => 1,
                'to'        => 0,
            ],
        ]);
        $logMove->assertStatus(201);

        // 4. Log connect
        $logConn = $this->actingAs($this->siswa1)->postJson('/api/logs', [
            'session_id'  => $sessionId,
            'action_type' => 'connect',
            'action_data' => [
                'from' => "c-{$t1->id}",
                'to'   => "e-{$t1->id}",
            ],
        ]);
        $logConn->assertStatus(201);

        // 5. Submit Score (Server-side score calculation)
        // With 4 total required connections (2 triplets * 2), providing 2 correct connections = 50%
        $connections = [
            ['from' => "c-{$t1->id}", 'to' => "e-{$t1->id}"],
            ['from' => "e-{$t1->id}", 'to' => "r-{$t1->id}"],
        ];

        $submitRes = $this->actingAs($this->siswa1)->postJson('/api/scores', [
            'session_id' => $sessionId,
            'map_id'     => $this->map->id,
            'map_data'   => [
                'triplets'    => $triplets->toArray(),
                'connections' => $connections,
            ],
        ]);

        $submitRes->assertStatus(201);
        $scoreId = $submitRes->json('data.score_id');
        $scoreVal = $submitRes->json('data.score');
        $this->assertEquals(50.0, (float) $scoreVal);

        // 6. Verify session is now is_submitted = true
        $session = UserSession::find($sessionId);
        $this->assertTrue((bool) $session->is_submitted);

        // 7. Verify student can view history and see this score
        $historyRes = $this->actingAs($this->siswa1)->get('/student/history');
        $historyRes->assertStatus(200);
        $historyRes->assertSee('Hasil Sebelumnya');
        $historyRes->assertSee("#S-{$sessionId}");
        $historyRes->assertSee('50%');

        // 8. Verify student can view own submitted map
        $viewMapRes = $this->actingAs($this->siswa1)->get("/view-map/{$scoreId}");
        $viewMapRes->assertStatus(200);
        $viewMapRes->assertSee('Hasil Map:');
        $viewMapRes->assertSee('Siswa Satu CER');

        // 9. Verify another student cannot access this submission (IDOR protection)
        $idorRes = $this->actingAs($this->siswa2)->get("/view-map/{$scoreId}");
        $idorRes->assertStatus(403);
    }
}
