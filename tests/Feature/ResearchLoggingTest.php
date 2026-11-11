<?php

namespace Tests\Feature;

use App\Models\CerMap;
use App\Models\User;
use App\Models\UserLog;
use App\Models\UserSession;
use Tests\TestCase;

class ResearchLoggingTest extends TestCase
{
    protected User $siswa1;
    protected User $siswa2;
    protected CerMap $map;

    protected function setUp(): void
    {
        parent::setUp();

        $this->siswa1 = User::firstOrCreate(
            ['username' => 'siswa1_research_test'],
            [
                'namalengkap' => 'Siswa Satu Research',
                'password'    => 'secret123',
                'role'        => 'siswa',
            ]
        );

        $this->siswa2 = User::firstOrCreate(
            ['username' => 'siswa2_research_test'],
            [
                'namalengkap' => 'Siswa Dua Research',
                'password'    => 'secret123',
                'role'        => 'siswa',
            ]
        );

        $this->map = CerMap::firstOrCreate(
            ['title' => 'Materi Research Logging 5'],
            [
                'allow_feedback' => true,
                'allow_reading'  => true,
            ]
        );
    }

    public function test_move_logged_with_card_type_and_inferred(): void
    {
        $session = UserSession::create([
            'user_id'    => $this->siswa1->id,
            'map_id'     => $this->map->id,
            'start_time' => now(),
        ]);

        // Explicit card_type
        $res1 = $this->actingAs($this->siswa1)->postJson('/api/logs', [
            'session_id'  => $session->id,
            'action_type' => 'move',
            'action_data' => [
                'card'      => 'e-10',
                'card_type' => 'evidence',
                'from'      => 0,
                'to'        => 2,
            ],
        ]);
        $res1->assertStatus(201);
        $log1 = UserLog::find($res1->json('data.id'));
        $this->assertEquals('evidence', $log1->action_data['card_type']);
        $this->assertEquals(0, $log1->action_data['from']);
        $this->assertEquals(2, $log1->action_data['to']);

        // Inferred card_type from prefix c-
        $res2 = $this->actingAs($this->siswa1)->postJson('/api/logs', [
            'session_id'  => $session->id,
            'action_type' => 'move',
            'action_data' => [
                'card' => 'c-15',
                'from' => 3,
                'to'   => 1,
            ],
        ]);
        $res2->assertStatus(201);
        $log2 = UserLog::find($res2->json('data.id'));
        $this->assertEquals('claim', $log2->action_data['card_type']);

        // Inferred card_type from prefix r-
        $res3 = $this->actingAs($this->siswa1)->postJson('/api/logs', [
            'session_id'  => $session->id,
            'action_type' => 'move',
            'action_data' => [
                'card' => 'r-20',
                'from' => 1,
                'to'   => 0,
            ],
        ]);
        $res3->assertStatus(201);
        $log3 = UserLog::find($res3->json('data.id'));
        $this->assertEquals('reasoning', $log3->action_data['card_type']);

        // Cleanup
        $session->logs()->delete();
        $session->delete();
    }

    public function test_move_from_equals_to_is_not_recorded(): void
    {
        $session = UserSession::create([
            'user_id'    => $this->siswa1->id,
            'map_id'     => $this->map->id,
            'start_time' => now(),
        ]);

        $res = $this->actingAs($this->siswa1)->postJson('/api/logs', [
            'session_id'  => $session->id,
            'action_type' => 'move',
            'action_data' => [
                'card' => 'e-10',
                'from' => 2,
                'to'   => 2, // No move!
            ],
        ]);

        $res->assertStatus(422);
        $this->assertEquals(0, $session->logs()->where('action_type', 'move')->count());

        // Cleanup
        $session->delete();
    }

    public function test_connect_and_disconnect_and_replacement(): void
    {
        $session = UserSession::create([
            'user_id'    => $this->siswa1->id,
            'map_id'     => $this->map->id,
            'start_time' => now(),
        ]);

        // Connect 1
        $resConn1 = $this->actingAs($this->siswa1)->postJson('/api/logs', [
            'session_id'  => $session->id,
            'action_type' => 'connect',
            'action_data' => ['from' => 'c-1', 'to' => 'e-1'],
        ]);
        $resConn1->assertStatus(201);

        // Replacement: Disconnect old, connect new
        $resDisconn = $this->actingAs($this->siswa1)->postJson('/api/logs', [
            'session_id'  => $session->id,
            'action_type' => 'disconnect',
            'action_data' => ['from' => 'c-1', 'to' => 'e-1'],
        ]);
        $resDisconn->assertStatus(201);

        $resConn2 = $this->actingAs($this->siswa1)->postJson('/api/logs', [
            'session_id'  => $session->id,
            'action_type' => 'connect',
            'action_data' => ['from' => 'c-1', 'to' => 'e-2'],
        ]);
        $resConn2->assertStatus(201);

        $this->assertEquals(2, $session->logs()->where('action_type', 'connect')->count());
        $this->assertEquals(1, $session->logs()->where('action_type', 'disconnect')->count());

        // Cleanup
        $session->logs()->delete();
        $session->delete();
    }

    public function test_view_reading_and_reading_close_logged(): void
    {
        $session = UserSession::create([
            'user_id'    => $this->siswa1->id,
            'map_id'     => $this->map->id,
            'start_time' => now(),
        ]);

        $resOpen = $this->actingAs($this->siswa1)->postJson('/api/logs', [
            'session_id'  => $session->id,
            'action_type' => 'view_reading',
            'action_data' => ['map_id' => $this->map->id],
        ]);
        $resOpen->assertStatus(201);

        $resClose = $this->actingAs($this->siswa1)->postJson('/api/logs', [
            'session_id'  => $session->id,
            'action_type' => 'reading_close',
            'action_data' => ['map_id' => $this->map->id],
        ]);
        $resClose->assertStatus(201);

        $this->assertEquals(1, $session->logs()->where('action_type', 'view_reading')->count());
        $this->assertEquals(1, $session->logs()->where('action_type', 'reading_close')->count());

        // Cleanup
        $session->logs()->delete();
        $session->delete();
    }

    public function test_feedback_toggle_logged(): void
    {
        $session = UserSession::create([
            'user_id'    => $this->siswa1->id,
            'map_id'     => $this->map->id,
            'start_time' => now(),
        ]);

        $res = $this->actingAs($this->siswa1)->postJson('/api/logs', [
            'session_id'  => $session->id,
            'action_type' => 'feedback',
            'action_data' => ['active' => true],
        ]);
        $res->assertStatus(201);

        $log = UserLog::find($res->json('data.id'));
        $this->assertTrue($log->action_data['active']);

        // Cleanup
        $session->logs()->delete();
        $session->delete();
    }

    public function test_submitted_session_cannot_receive_logs(): void
    {
        $session = UserSession::create([
            'user_id'      => $this->siswa1->id,
            'map_id'       => $this->map->id,
            'start_time'   => now(),
            'is_submitted' => true,
        ]);

        $res = $this->actingAs($this->siswa1)->postJson('/api/logs', [
            'session_id'  => $session->id,
            'action_type' => 'view_reading',
        ]);

        $res->assertStatus(422);

        // Cleanup
        $session->delete();
    }

    public function test_siswa_cannot_log_on_another_users_session(): void
    {
        $session = UserSession::create([
            'user_id'      => $this->siswa1->id,
            'map_id'       => $this->map->id,
            'start_time'   => now(),
            'is_submitted' => false,
        ]);

        $res = $this->actingAs($this->siswa2)->postJson('/api/logs', [
            'session_id'  => $session->id,
            'action_type' => 'view_reading',
        ]);

        $res->assertStatus(403);

        // Cleanup
        $session->delete();
    }

    /**
     * Exact Research Event Verification as required by Redesign Spec:
     * - open reading: 1
     * - close reading: 1
     * - move: 2
     * - connect: 2
     * - disconnect: 1
     * - feedback: 1
     * - auto_arrange: 1
     * - submit: 1
     */
    public function test_exact_redesign_research_event_verification_sequence(): void
    {
        // Start Session
        $session = UserSession::create([
            'user_id'      => $this->siswa1->id,
            'map_id'       => $this->map->id,
            'start_time'   => now(),
            'is_submitted' => false,
        ]);

        // 1. open reading 1 kali
        $this->actingAs($this->siswa1)->postJson('/api/logs', [
            'session_id'  => $session->id,
            'action_type' => 'view_reading',
            'action_data' => ['map_id' => $this->map->id],
        ])->assertStatus(201);

        // 2. close reading 1 kali
        $this->actingAs($this->siswa1)->postJson('/api/logs', [
            'session_id'  => $session->id,
            'action_type' => 'reading_close',
            'action_data' => ['map_id' => $this->map->id],
        ])->assertStatus(201);

        // 3. move 2 kali
        $this->actingAs($this->siswa1)->postJson('/api/logs', [
            'session_id'  => $session->id,
            'action_type' => 'move',
            'action_data' => ['card' => 'c-1', 'from' => 0, 'to' => 1],
        ])->assertStatus(201);

        $this->actingAs($this->siswa1)->postJson('/api/logs', [
            'session_id'  => $session->id,
            'action_type' => 'move',
            'action_data' => ['card' => 'e-1', 'from' => 1, 'to' => 0],
        ])->assertStatus(201);

        // 4. connect 2 kali
        $this->actingAs($this->siswa1)->postJson('/api/logs', [
            'session_id'  => $session->id,
            'action_type' => 'connect',
            'action_data' => ['from' => 'c-1', 'to' => 'e-1'],
        ])->assertStatus(201);

        $this->actingAs($this->siswa1)->postJson('/api/logs', [
            'session_id'  => $session->id,
            'action_type' => 'connect',
            'action_data' => ['from' => 'e-1', 'to' => 'r-1'],
        ])->assertStatus(201);

        // 5. disconnect 1 kali
        $this->actingAs($this->siswa1)->postJson('/api/logs', [
            'session_id'  => $session->id,
            'action_type' => 'disconnect',
            'action_data' => ['from' => 'e-1', 'to' => 'r-1'],
        ])->assertStatus(201);

        // 6. feedback 1 kali
        $this->actingAs($this->siswa1)->postJson('/api/logs', [
            'session_id'  => $session->id,
            'action_type' => 'feedback',
            'action_data' => ['active' => true],
        ])->assertStatus(201);

        // 7. auto arrange 1 kali
        $this->actingAs($this->siswa1)->postJson('/api/logs', [
            'session_id'  => $session->id,
            'action_type' => 'auto_arrange',
        ])->assertStatus(201);

        // 8. submit 1 kali
        $submitRes = $this->actingAs($this->siswa1)->postJson('/api/scores', [
            'session_id' => $session->id,
            'map_id'     => $this->map->id,
            'map_data'   => [
                'triplets'    => [['id' => 1, 'claim' => 'C1', 'evidence' => 'E1', 'reasoning' => 'R1']],
                'connections' => [['from' => 'c-1', 'to' => 'e-1']],
            ],
        ]);
        $submitRes->assertStatus(201);

        // Verify Database Counts Match Specification Exactly
        $this->assertEquals(1, $session->logs()->where('action_type', 'view_reading')->count());
        $this->assertEquals(1, $session->logs()->where('action_type', 'reading_close')->count());
        $this->assertEquals(2, $session->logs()->where('action_type', 'move')->count());
        $this->assertEquals(2, $session->logs()->where('action_type', 'connect')->count());
        $this->assertEquals(1, $session->logs()->where('action_type', 'disconnect')->count());
        $this->assertEquals(1, $session->logs()->where('action_type', 'feedback')->count());
        $this->assertEquals(1, $session->logs()->where('action_type', 'auto_arrange')->count());

        // Session status is submitted
        $this->assertTrue((bool) $session->fresh()->is_submitted);

        // Verify Log UI displays this session
        $guru = User::where('role', 'guru')->first();
        $uiResponse = $this->actingAs($guru)->get('/admin/logs');
        $uiResponse->assertStatus(200);
        $uiResponse->assertSee("#S-{$session->id}");

        // Detail UI displays the event summary
        $detailResponse = $this->actingAs($guru)->get("/admin/logs/{$session->id}");
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee("#S-{$session->id}");

        // Cleanup
        $session->logs()->delete();
        \App\Models\Score::where('session_id', $session->id)->delete();
        $session->delete();
    }
}
