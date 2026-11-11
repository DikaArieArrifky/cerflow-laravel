<?php

namespace Tests\Feature;

use App\Models\CerMap;
use App\Models\Score;
use App\Models\User;
use App\Models\UserLog;
use App\Models\UserSession;
use Tests\TestCase;

class InteractionLogTest extends TestCase
{
    protected User $guru;
    protected User $siswa;
    protected CerMap $map;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guru = User::firstOrCreate(
            ['username' => 'guru_log_test'],
            [
                'namalengkap' => 'Guru Log Test',
                'password'    => 'secret123',
                'role'        => 'guru',
            ]
        );

        $this->siswa = User::firstOrCreate(
            ['username' => 'siswa_log_test'],
            [
                'namalengkap' => 'Siswa Log Test',
                'password'    => 'secret123',
                'role'        => 'siswa',
            ]
        );

        $this->map = CerMap::firstOrCreate(
            ['title' => 'Materi Log Interaksi Test 3F'],
            [
                'allow_feedback' => true,
                'allow_reading'  => true,
            ]
        );
    }

    public function test_guru_can_access_interaction_logs_page(): void
    {
        $response = $this->actingAs($this->guru)->get('/admin/logs');

        $response->assertStatus(200);
        $response->assertSee('Interaction');
        $response->assertSee('Log Analytics');
        $response->assertSee('Filter Materi');
        $response->assertSee('Filter Siswa');
    }

    public function test_siswa_cannot_access_interaction_logs_page(): void
    {
        $response = $this->actingAs($this->siswa)->get('/admin/logs');

        $response->assertStatus(403);
    }

    public function test_completed_sessions_and_action_counts_are_displayed(): void
    {
        $session = UserSession::create([
            'user_id'      => $this->siswa->id,
            'map_id'       => $this->map->id,
            'start_time'   => now()->subMinutes(10),
            'is_submitted' => true,
        ]);

        // Insert various action logs
        UserLog::create(['session_id' => $session->id, 'action_type' => 'view_reading']);
        UserLog::create(['session_id' => $session->id, 'action_type' => 'connect', 'action_data' => ['from' => 'c-1', 'to' => 'e-1']]);
        UserLog::create(['session_id' => $session->id, 'action_type' => 'disconnect', 'action_data' => ['from' => 'c-1', 'to' => 'e-1']]);
        UserLog::create(['session_id' => $session->id, 'action_type' => 'move', 'action_data' => ['card' => 'c-1', 'from' => 0, 'to' => 2]]);
        UserLog::create(['session_id' => $session->id, 'action_type' => 'auto_arrange']);
        UserLog::create(['session_id' => $session->id, 'action_type' => 'feedback', 'action_data' => ['active' => true]]);

        $score = Score::create([
            'user_id'      => $this->siswa->id,
            'map_id'       => $this->map->id,
            'session_id'   => $session->id,
            'score'        => 100.0,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->guru)->get('/admin/logs');

        $response->assertStatus(200);
        $response->assertSee("#S-{$session->id}");
        $response->assertSee('Materi Log Interaksi Test 3F');
        $response->assertSee('Siswa Log Test');
        $response->assertSee('100%');

        // Test filtering by siswa
        $filterResponse = $this->actingAs($this->guru)->get("/admin/logs?siswa=siswa_log_test");
        $filterResponse->assertStatus(200);
        $filterResponse->assertSee("#S-{$session->id}");

        // Test filtering by non-matching siswa
        $noMatchResponse = $this->actingAs($this->guru)->get("/admin/logs?siswa=non_existing_student_xyz");
        $noMatchResponse->assertStatus(200);
        $noMatchResponse->assertDontSee("#S-{$session->id}");

        // Cleanup
        $session->logs()->delete();
        $score->delete();
        $session->delete();
    }
}
