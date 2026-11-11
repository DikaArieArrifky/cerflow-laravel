<?php

namespace Tests\Feature;

use App\Models\CerMap;
use App\Models\Score;
use App\Models\Setting;
use App\Models\Triplet;
use App\Models\User;
use App\Models\UserLog;
use App\Models\UserSession;
use Tests\TestCase;

class ModelRelationshipTest extends TestCase
{
    public function test_all_models_relationships_and_casts(): void
    {
        // 1. User
        $user = User::firstOrCreate(
            ['username' => 'model_test_user'],
            [
                'namalengkap' => 'Model Test User',
                'password' => 'secret123',
                'role' => 'siswa',
            ]
        );
        $this->assertTrue($user->isSiswa());
        $this->assertFalse($user->isGuru());

        // 2. CerMap
        $map = CerMap::create([
            'title' => 'Pemanasan Global',
            'allow_feedback' => true,
            'reading_text' => 'Teks bacaan materi efek rumah kaca...',
            'allow_reading' => true,
        ]);
        $this->assertNotNull($map->id);
        $this->assertTrue($map->allow_feedback);
        $this->assertTrue($map->allow_reading);

        // 3. Triplet
        $triplet = Triplet::create([
            'map_id' => $map->id,
            'claim' => 'Emisi CO2 meningkatkan suhu bumi.',
            'evidence' => 'Data NOAA menunjukkan peningkatan CO2 50% sejak 1850.',
            'reasoning' => 'Gas rumah kaca memerangkap radiasi inframerah.',
        ]);
        $this->assertEquals($map->id, $triplet->cerMap->id);
        $this->assertCount(1, $map->triplets);

        // 4. UserSession
        $session = UserSession::create([
            'user_id' => $user->id,
            'map_id' => $map->id,
            'is_submitted' => false,
        ]);
        $this->assertEquals($user->id, $session->user->id);
        $this->assertEquals($map->id, $session->cerMap->id);
        $this->assertFalse($session->is_submitted);

        // 5. UserLog (action_data cast to array)
        $logData = ['source' => 'node_claim_1', 'target' => 'node_evidence_1', 'type' => 'connect'];
        $log = UserLog::create([
            'session_id' => $session->id,
            'action_type' => 'connect_node',
            'action_data' => $logData,
        ]);
        $this->assertEquals($session->id, $log->session->id);
        $this->assertIsArray($log->action_data);
        $this->assertEquals('connect_node', $log->action_type);
        $this->assertEquals('node_claim_1', $log->action_data['source']);

        // 6. Score (map_data cast to array, score cast to decimal)
        $mapData = [
            'nodes' => [
                ['id' => 'c1', 'type' => 'claim', 'text' => 'Claim text'],
                ['id' => 'e1', 'type' => 'evidence', 'text' => 'Evidence text'],
            ],
            'connections' => [
                ['from' => 'c1', 'to' => 'e1'],
            ],
        ];
        $score = Score::create([
            'user_id' => $user->id,
            'map_id' => $map->id,
            'session_id' => $session->id,
            'score' => 95.50,
            'map_data' => $mapData,
        ]);
        $this->assertEquals($user->id, $score->user->id);
        $this->assertEquals($map->id, $score->cerMap->id);
        $this->assertEquals($session->id, $score->session->id);
        $this->assertIsArray($score->map_data);
        $this->assertEquals('c1', $score->map_data['nodes'][0]['id']);
        $this->assertEquals('95.50', (string) $score->score);

        // 7. Setting
        Setting::set('gemini_api_key', 'AIzaSyTestApiKey123');
        $this->assertEquals('AIzaSyTestApiKey123', Setting::get('gemini_api_key'));
        $this->assertEquals('default_val', Setting::get('non_existent_key', 'default_val'));

        // Verify relationships from User and CerMap sides
        $this->assertTrue($user->sessions->contains($session));
        $this->assertTrue($user->scores->contains($score));
        $this->assertTrue($map->sessions->contains($session));
        $this->assertTrue($map->scores->contains($score));
        $this->assertTrue($session->logs->contains($log));
        $this->assertEquals($score->id, $session->score->id);
    }
}
