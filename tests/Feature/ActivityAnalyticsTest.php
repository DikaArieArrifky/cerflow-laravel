<?php

namespace Tests\Feature;

use App\Models\CerMap;
use App\Models\Score;
use App\Models\User;
use App\Models\UserLog;
use App\Models\UserSession;
use App\Services\ActivityAnalyticsService;
use Carbon\Carbon;
use Tests\TestCase;

class ActivityAnalyticsTest extends TestCase
{
    protected User $guru;
    protected User $siswa;
    protected CerMap $map;
    protected ActivityAnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->analyticsService = new ActivityAnalyticsService();

        $this->guru = User::firstOrCreate(
            ['username' => 'guru_analytics_test'],
            [
                'namalengkap' => 'Guru Analytics Test',
                'password'    => 'secret123',
                'role'        => 'guru',
            ]
        );

        $this->siswa = User::firstOrCreate(
            ['username' => 'siswa_analytics_test'],
            [
                'namalengkap' => 'Siswa Analytics Test',
                'password'    => 'secret123',
                'role'        => 'siswa',
            ]
        );

        $this->map = CerMap::firstOrCreate(
            ['title' => 'Materi Analisis Riset 6'],
            [
                'allow_feedback' => true,
                'allow_reading'  => true,
            ]
        );
    }

    public function test_movement_metrics_count_distance_and_unique_cards(): void
    {
        $session = UserSession::create([
            'user_id'    => $this->siswa->id,
            'map_id'     => $this->map->id,
            'start_time' => now(),
        ]);

        // Moves specified in specification:
        // 0 -> 2 = 2 (card: c-1, claim)
        // 4 -> 1 = 3 (card: e-2, evidence)
        // 1 -> 2 = 1 (card: c-1, claim) -> repeat card
        UserLog::create([
            'session_id'  => $session->id,
            'action_type' => 'move',
            'action_data' => ['card' => 'c-1', 'card_type' => 'claim', 'from' => 0, 'to' => 2],
        ]);
        UserLog::create([
            'session_id'  => $session->id,
            'action_type' => 'move',
            'action_data' => ['card' => 'e-2', 'card_type' => 'evidence', 'from' => 4, 'to' => 1],
        ]);
        UserLog::create([
            'session_id'  => $session->id,
            'action_type' => 'move',
            'action_data' => ['card' => 'c-1', 'card_type' => 'claim', 'from' => 1, 'to' => 2],
        ]);

        $analytics = $this->analyticsService->calculateSessionAnalytics($session);

        $this->assertEquals(3, $analytics['move_count']);
        $this->assertEquals(6, $analytics['total_move_distance']); // 2 + 3 + 1 = 6
        $this->assertEquals(2, $analytics['unique_moved_cards']);  // c-1 and e-2
        $this->assertEquals(2, $analytics['claim_move_count']);
        $this->assertEquals(1, $analytics['evidence_move_count']);
        $this->assertEquals(0, $analytics['reasoning_move_count']);

        // Cleanup
        $session->logs()->delete();
        $session->delete();
    }

    public function test_card_type_inference_from_prefix(): void
    {
        $session = UserSession::create([
            'user_id'    => $this->siswa->id,
            'map_id'     => $this->map->id,
            'start_time' => now(),
        ]);

        // Move without explicit card_type
        UserLog::create([
            'session_id'  => $session->id,
            'action_type' => 'move',
            'action_data' => ['card' => 'r-99', 'from' => 0, 'to' => 3],
        ]);

        $analytics = $this->analyticsService->calculateSessionAnalytics($session);

        $this->assertEquals(1, $analytics['reasoning_move_count']);
        $this->assertEquals(3, $analytics['total_move_distance']);

        // Cleanup
        $session->logs()->delete();
        $session->delete();
    }

    public function test_reading_duration_matching_and_incomplete_reading(): void
    {
        $session = UserSession::create([
            'user_id'    => $this->siswa->id,
            'map_id'     => $this->map->id,
            'start_time' => now()->subMinutes(10),
        ]);

        $t0 = Carbon::now()->subMinutes(8);
        $t1 = Carbon::now()->subMinutes(6); // 2 minutes = 120 seconds

        // Pair 1: view_reading at t0, reading_close at t1
        UserLog::create([
            'session_id'  => $session->id,
            'action_type' => 'view_reading',
            'created_at'  => $t0,
        ]);
        UserLog::create([
            'session_id'  => $session->id,
            'action_type' => 'reading_close',
            'created_at'  => $t1,
        ]);

        // Pair 2: incomplete reading (opened at t2, never closed before submit)
        $t2 = Carbon::now()->subMinutes(2);
        UserLog::create([
            'session_id'  => $session->id,
            'action_type' => 'view_reading',
            'created_at'  => $t2,
        ]);

        $analytics = $this->analyticsService->calculateSessionAnalytics($session);

        $this->assertEquals(2, $analytics['reading_count']);
        $this->assertEquals(120, $analytics['reading_duration_seconds']);
        $this->assertTrue($analytics['has_incomplete_reading']);

        // Cleanup
        $session->logs()->delete();
        $session->delete();
    }

    public function test_completion_time_calculation(): void
    {
        $startTime = Carbon::now()->subMinutes(15);
        $submitTime = Carbon::now()->subMinutes(5); // 10 minutes = 600 seconds

        $session = UserSession::create([
            'user_id'      => $this->siswa->id,
            'map_id'       => $this->map->id,
            'start_time'   => $startTime,
            'is_submitted' => true,
        ]);

        $score = Score::create([
            'user_id'      => $this->siswa->id,
            'map_id'       => $this->map->id,
            'session_id'   => $session->id,
            'score'        => 95.0,
            'submitted_at' => $submitTime,
        ]);

        $analytics = $this->analyticsService->calculateSessionAnalytics($session);

        $this->assertEquals(600, $analytics['completion_time_seconds']);
        $this->assertEquals(95.0, $analytics['score']);

        // Cleanup
        $score->delete();
        $session->delete();
    }

    public function test_session_detail_page_renders_complete_analytics_and_timeline(): void
    {
        $session = UserSession::create([
            'user_id'      => $this->siswa->id,
            'map_id'       => $this->map->id,
            'start_time'   => now()->subMinutes(5),
            'is_submitted' => true,
        ]);

        UserLog::create([
            'session_id'  => $session->id,
            'action_type' => 'view_reading',
            'created_at'  => now()->subMinutes(4),
        ]);
        UserLog::create([
            'session_id'  => $session->id,
            'action_type' => 'reading_close',
            'created_at'  => now()->subMinutes(3),
        ]);
        UserLog::create([
            'session_id'  => $session->id,
            'action_type' => 'move',
            'action_data' => ['card' => 'e-1', 'card_type' => 'evidence', 'from' => 0, 'to' => 2],
            'created_at'  => now()->subMinutes(2),
        ]);
        UserLog::create([
            'session_id'  => $session->id,
            'action_type' => 'connect',
            'action_data' => ['from' => 'c-1', 'to' => 'e-1'],
            'created_at'  => now()->subMinute(),
        ]);

        $score = Score::create([
            'user_id'      => $this->siswa->id,
            'map_id'       => $this->map->id,
            'session_id'   => $session->id,
            'score'        => 100.0,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->guru)->get("/admin/logs/{$session->id}");

        $response->assertStatus(200);
        $response->assertSee('Session');
        $response->assertSee('Analytics Detail');
        $response->assertSee('Total Events');
        $response->assertSee('Movement Analysis');
        $response->assertSee('Timeline Kronologis');
        $response->assertSee('e-1');
        $response->assertSee('c-1 → e-1');

        // Siswa cannot view admin session detail
        $siswaRes = $this->actingAs($this->siswa)->get("/admin/logs/{$session->id}");
        $siswaRes->assertStatus(403);

        // Cleanup
        $session->logs()->delete();
        $score->delete();
        $session->delete();
    }
}
