<?php

namespace Tests\Feature;

use App\Models\CerMap;
use App\Models\Score;
use App\Models\Triplet;
use App\Models\User;
use App\Models\UserLog;
use App\Models\UserSession;
use Tests\TestCase;

class CoreApiTest extends TestCase
{
    protected User $guru;
    protected User $siswa1;
    protected User $siswa2;
    protected CerMap $sampleMap;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guru = User::firstOrCreate(
            ['username' => 'api_test_guru'],
            [
                'namalengkap' => 'Guru API Tester',
                'password'    => 'secret123',
                'role'        => 'guru',
            ]
        );

        $this->siswa1 = User::firstOrCreate(
            ['username' => 'api_test_siswa1'],
            [
                'namalengkap' => 'Siswa API Tester 1',
                'password'    => 'secret123',
                'role'        => 'siswa',
            ]
        );

        $this->siswa2 = User::firstOrCreate(
            ['username' => 'api_test_siswa2'],
            [
                'namalengkap' => 'Siswa API Tester 2',
                'password'    => 'secret123',
                'role'        => 'siswa',
            ]
        );

        $this->sampleMap = CerMap::firstOrCreate(
            ['title' => 'Materi Siklus Air'],
            [
                'allow_feedback' => true,
                'allow_reading'  => true,
                'reading_text'   => 'Siklus air melibatkan evaporasi, kondensasi, dan presipitasi.',
            ]
        );

        // Ensure sample triplets exist
        if ($this->sampleMap->triplets()->count() === 0) {
            $this->sampleMap->triplets()->create([
                'claim'     => 'Air di bumi tidak pernah habis.',
                'evidence'  => 'Matahari menguapkan air membentuk awan hujan.',
                'reasoning' => 'Siklus hidrologi mendaur ulang air secara konstan.',
            ]);
            $this->sampleMap->triplets()->create([
                'claim'     => 'Hutan menjaga ketersediaan air tanah.',
                'evidence'  => 'Akar pohon menyerap dan menahan air hujan.',
                'reasoning' => 'Infiltrasi air meningkat dengan tutupan vegetasi.',
            ]);
        }
    }

    // ==========================================
    // CER MAP TESTS
    // ==========================================

    public function test_guru_can_get_maps(): void
    {
        $response = $this->actingAs($this->guru)->getJson('/api/maps');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonFragment([
            'title' => 'Materi Siklus Air',
        ]);
    }

    public function test_siswa_can_read_triplets(): void
    {
        $response = $this->actingAs($this->siswa1)->getJson("/api/maps/{$this->sampleMap->id}/triplets");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonFragment([
            'claim' => 'Air di bumi tidak pernah habis.',
        ]);
    }

    public function test_siswa_cannot_create_map(): void
    {
        $response = $this->actingAs($this->siswa1)->postJson('/api/maps', [
            'title'    => 'Map Ilegal oleh Siswa',
            'triplets' => [
                ['claim' => 'C', 'evidence' => 'E', 'reasoning' => 'R'],
            ],
        ]);

        $response->assertStatus(403);
    }

    public function test_siswa_cannot_delete_map(): void
    {
        $response = $this->actingAs($this->siswa1)->deleteJson("/api/maps/{$this->sampleMap->id}");

        $response->assertStatus(403);
    }

    public function test_guru_can_create_map_with_triplets(): void
    {
        $payload = [
            'title'          => 'Fotosintesis Tumbuhan',
            'allow_feedback' => true,
            'allow_reading'  => true,
            'reading_text'   => 'Klorofil menyerap cahaya untuk sintesis glukosa.',
            'triplets'       => [
                [
                    'claim'     => 'Tumbuhan menghasilkan oksigen pada siang hari.',
                    'evidence'  => 'Eksperimen Ingenhousz menghasilkan gelembung udara.',
                    'reasoning' => 'Reaksi terang fotosintesis memecah molekul air.',
                ],
            ],
        ];

        $response = $this->actingAs($this->guru)->postJson('/api/maps', $payload);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'status'  => 'ok',
        ]);

        $createdMapId = $response->json('id');
        $this->assertNotNull($createdMapId);

        $this->assertDatabaseHas('cer_maps', [
            'id'    => $createdMapId,
            'title' => 'Fotosintesis Tumbuhan',
        ]);

        $this->assertDatabaseHas('triplets', [
            'map_id' => $createdMapId,
            'claim'  => 'Tumbuhan menghasilkan oksigen pada siang hari.',
        ]);
    }

    public function test_guru_can_update_map_and_triplets(): void
    {
        $map = CerMap::create([
            'title' => 'Map Sebelum Update',
        ]);
        $t1 = $map->triplets()->create([
            'claim' => 'Claim 1 Lama',
            'evidence' => 'Evidence 1 Lama',
            'reasoning' => 'Reasoning 1 Lama',
        ]);

        $updatePayload = [
            'map_id'         => $map->id,
            'title'          => 'Map Setelah Update',
            'allow_feedback' => false,
            'allow_reading'  => true,
            'reading_text'   => 'Teks baru',
            'triplets'       => [
                [
                    'id'        => $t1->id,
                    'claim'     => 'Claim 1 Diperbarui',
                    'evidence'  => 'Evidence 1 Diperbarui',
                    'reasoning' => 'Reasoning 1 Diperbarui',
                ],
                [
                    'claim'     => 'Claim 2 Baru',
                    'evidence'  => 'Evidence 2 Baru',
                    'reasoning' => 'Reasoning 2 Baru',
                ],
            ],
        ];

        $response = $this->actingAs($this->guru)->postJson('/api/maps', $updatePayload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status'  => 'ok',
            'id'      => $map->id,
        ]);

        $this->assertDatabaseHas('cer_maps', [
            'id'    => $map->id,
            'title' => 'Map Setelah Update',
        ]);

        $this->assertDatabaseHas('triplets', [
            'id'    => $t1->id,
            'claim' => 'Claim 1 Diperbarui',
        ]);

        $this->assertDatabaseHas('triplets', [
            'map_id' => $map->id,
            'claim'  => 'Claim 2 Baru',
        ]);
    }

    public function test_guru_can_delete_map(): void
    {
        $map = CerMap::create([
            'title' => 'Map Akan Dihapus',
        ]);
        $map->triplets()->create([
            'claim' => 'C', 'evidence' => 'E', 'reasoning' => 'R',
        ]);

        $response = $this->actingAs($this->guru)->deleteJson("/api/maps/{$map->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status'  => 'success',
        ]);

        $this->assertDatabaseMissing('cer_maps', ['id' => $map->id]);
    }

    // ==========================================
    // LEARNING SESSION TESTS
    // ==========================================

    public function test_siswa_can_start_session(): void
    {
        $response = $this->actingAs($this->siswa1)->postJson('/api/sessions', [
            'map_id' => $this->sampleMap->id,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'status'  => 'success',
        ]);

        $sessionId = $response->json('session_id');
        $this->assertNotNull($sessionId);

        $this->assertDatabaseHas('user_sessions', [
            'id'           => $sessionId,
            'user_id'      => $this->siswa1->id,
            'map_id'       => $this->sampleMap->id,
            'is_submitted' => 0,
        ]);
    }

    public function test_guru_cannot_start_student_session(): void
    {
        $response = $this->actingAs($this->guru)->postJson('/api/sessions', [
            'map_id' => $this->sampleMap->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_guest_is_rejected_from_api(): void
    {
        $response = $this->postJson('/api/sessions', [
            'map_id' => $this->sampleMap->id,
        ]);

        // Unauthenticated guests should receive 401 or redirect to login
        $this->assertTrue(in_array($response->status(), [401, 302]));
    }

    public function test_invalid_map_id_rejected_on_start_session(): void
    {
        $response = $this->actingAs($this->siswa1)->postJson('/api/sessions', [
            'map_id' => 999999, // Non-existent map ID
        ]);

        $response->assertStatus(422);
    }

    // ==========================================
    // ACTIVITY LOG TESTS
    // ==========================================

    public function test_siswa_can_log_connect(): void
    {
        $session = UserSession::create([
            'user_id'      => $this->siswa1->id,
            'map_id'       => $this->sampleMap->id,
            'is_submitted' => false,
        ]);

        $response = $this->actingAs($this->siswa1)->postJson('/api/logs', [
            'session_id'  => $session->id,
            'action_type' => 'connect',
            'action_data' => ['from' => 'c-1', 'to' => 'e-1'],
        ]);

        $response->assertStatus(201);
        $response->assertJson(['success' => true, 'status' => 'success']);

        $this->assertDatabaseHas('user_logs', [
            'session_id'  => $session->id,
            'action_type' => 'connect',
        ]);
    }

    public function test_siswa_can_log_disconnect(): void
    {
        $session = UserSession::create([
            'user_id'      => $this->siswa1->id,
            'map_id'       => $this->sampleMap->id,
            'is_submitted' => false,
        ]);

        $response = $this->actingAs($this->siswa1)->postJson('/api/logs', [
            'session_id'  => $session->id,
            'action_type' => 'disconnect',
            'action_data' => ['from' => 'c-1', 'to' => 'e-1'],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('user_logs', [
            'session_id'  => $session->id,
            'action_type' => 'disconnect',
        ]);
    }

    public function test_siswa_can_log_move(): void
    {
        $session = UserSession::create([
            'user_id'      => $this->siswa1->id,
            'map_id'       => $this->sampleMap->id,
            'is_submitted' => false,
        ]);

        $response = $this->actingAs($this->siswa1)->postJson('/api/logs', [
            'session_id'  => $session->id,
            'action_type' => 'move',
            'action_data' => ['card' => 'r-1', 'from' => 0, 'to' => 2],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('user_logs', [
            'session_id'  => $session->id,
            'action_type' => 'move',
        ]);
    }

    public function test_action_data_saved_and_cast_as_array(): void
    {
        $session = UserSession::create([
            'user_id'      => $this->siswa1->id,
            'map_id'       => $this->sampleMap->id,
            'is_submitted' => false,
        ]);

        $payloadData = ['active' => true, 'hint' => 'test hint'];
        $response = $this->actingAs($this->siswa1)->postJson('/api/logs', [
            'session_id'  => $session->id,
            'action_type' => 'feedback',
            'action_data' => $payloadData,
        ]);

        $response->assertStatus(201);

        $log = UserLog::where('session_id', $session->id)->where('action_type', 'feedback')->first();
        $this->assertNotNull($log);
        $this->assertIsArray($log->action_data);
        $this->assertTrue($log->action_data['active']);
        $this->assertEquals('test hint', $log->action_data['hint']);
    }

    public function test_invalid_action_type_rejected(): void
    {
        $session = UserSession::create([
            'user_id'      => $this->siswa1->id,
            'map_id'       => $this->sampleMap->id,
            'is_submitted' => false,
        ]);

        $response = $this->actingAs($this->siswa1)->postJson('/api/logs', [
            'session_id'  => $session->id,
            'action_type' => 'unsupported_hack_type',
        ]);

        $response->assertStatus(422);
    }

    public function test_siswa_cannot_write_log_to_another_users_session(): void
    {
        // Session created by Siswa 2
        $session2 = UserSession::create([
            'user_id'      => $this->siswa2->id,
            'map_id'       => $this->sampleMap->id,
            'is_submitted' => false,
        ]);

        // Siswa 1 attempts to log into Siswa 2's session
        $response = $this->actingAs($this->siswa1)->postJson('/api/logs', [
            'session_id'  => $session2->id,
            'action_type' => 'connect',
            'action_data' => ['from' => 'c-1', 'to' => 'e-1'],
        ]);

        // Must be rejected with 403 Forbidden
        $response->assertStatus(403);
    }

    // ==========================================
    // SCORE / SUBMISSION TESTS
    // ==========================================

    public function test_siswa_can_submit_score_and_it_calculates_correctly(): void
    {
        $triplets = $this->sampleMap->triplets;
        $t1 = $triplets[0];
        $t2 = $triplets[1];

        $session = UserSession::create([
            'user_id'      => $this->siswa1->id,
            'map_id'       => $this->sampleMap->id,
            'is_submitted' => false,
        ]);

        // Map has 2 triplets -> 4 connections total (100%)
        // We provide 2 correct connections (c-t1 -> e-t1, e-t1 -> r-t1) out of 4 -> Expected score = 50%
        $mapData = [
            'connections' => [
                ['from' => "c-{$t1->id}", 'to' => "e-{$t1->id}"], // Correct
                ['from' => "e-{$t1->id}", 'to' => "r-{$t1->id}"], // Correct
                ['from' => "c-{$t2->id}", 'to' => "e-{$t1->id}"], // Incorrect (mismatched)
            ],
            'colOrder' => [
                'claim'     => ["c-{$t1->id}", "c-{$t2->id}"],
                'evidence'  => ["e-{$t1->id}", "e-{$t2->id}"],
                'reasoning' => ["r-{$t1->id}", "r-{$t2->id}"],
            ],
        ];

        $response = $this->actingAs($this->siswa1)->postJson('/api/scores', [
            'session_id' => $session->id,
            'map_id'     => $this->sampleMap->id,
            'map_data'   => $mapData,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'status'  => 'success',
            'data'    => [
                'score' => '50.00',
            ],
        ]);

        // Verify database records
        $score = Score::where('session_id', $session->id)->first();
        $this->assertNotNull($score);
        $this->assertEquals($this->siswa1->id, $score->user_id);
        $this->assertEquals($this->sampleMap->id, $score->map_id);
        $this->assertEquals($session->id, $score->session_id);
        $this->assertEquals('50.00', (string) $score->score);

        // Verify session is_submitted updated to true
        $this->assertTrue((bool) $session->fresh()->is_submitted);
    }

    public function test_score_is_linked_to_user_map_and_session(): void
    {
        $session = UserSession::create([
            'user_id'      => $this->siswa1->id,
            'map_id'       => $this->sampleMap->id,
            'is_submitted' => false,
        ]);

        $response = $this->actingAs($this->siswa1)->postJson('/api/scores', [
            'session_id' => $session->id,
            'map_id'     => $this->sampleMap->id,
            'score'      => 85.00,
            'map_data'   => ['note' => 'direct submission'],
        ]);

        $response->assertStatus(201);

        $score = Score::where('session_id', $session->id)->first();
        $this->assertNotNull($score);
        $this->assertEquals($this->siswa1->id, $score->user->id);
        $this->assertEquals($this->sampleMap->id, $score->cerMap->id);
        $this->assertEquals($session->id, $score->session->id);
    }

    public function test_session_changes_to_is_submitted_true_after_submit(): void
    {
        $session = UserSession::create([
            'user_id'      => $this->siswa1->id,
            'map_id'       => $this->sampleMap->id,
            'is_submitted' => false,
        ]);

        $this->assertFalse((bool) $session->is_submitted);

        $this->actingAs($this->siswa1)->postJson('/api/scores', [
            'session_id' => $session->id,
            'map_id'     => $this->sampleMap->id,
            'score'      => 100.0,
        ]);

        $this->assertTrue((bool) $session->fresh()->is_submitted);
    }

    public function test_siswa_cannot_submit_on_another_users_session(): void
    {
        // Session belongs to Siswa 2
        $session2 = UserSession::create([
            'user_id'      => $this->siswa2->id,
            'map_id'       => $this->sampleMap->id,
            'is_submitted' => false,
        ]);

        // Siswa 1 attempts to submit
        $response = $this->actingAs($this->siswa1)->postJson('/api/scores', [
            'session_id' => $session2->id,
            'map_id'     => $this->sampleMap->id,
            'score'      => 100.0,
        ]);

        $response->assertStatus(403);
    }

    public function test_cannot_submit_twice_on_already_submitted_session(): void
    {
        $session = UserSession::create([
            'user_id'      => $this->siswa1->id,
            'map_id'       => $this->sampleMap->id,
            'is_submitted' => true, // Already submitted
        ]);

        $response = $this->actingAs($this->siswa1)->postJson('/api/scores', [
            'session_id' => $session->id,
            'map_id'     => $this->sampleMap->id,
            'score'      => 90.0,
        ]);

        $response->assertStatus(422);
    }
}
