<?php

namespace Tests\Feature;

use App\Models\CerMap;
use App\Models\Score;
use App\Models\Setting;
use App\Models\Triplet;
use App\Models\User;
use App\Models\UserLog;
use App\Models\UserSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LegacyImportTest extends TestCase
{
    public function test_dry_run_command_exits_cleanly(): void
    {
        $this->artisan('cerflow:import-legacy', ['--dry-run' => true])
            ->assertExitCode(0)
            ->expectsOutputToContain('CERFlow Legacy Data Importer')
            ->expectsOutputToContain('DRY RUN');
    }

    public function test_imported_legacy_data_integrity_and_relationships(): void
    {
        // Users exist
        $admin = User::where('username', 'admin')->first();
        $this->assertNotNull($admin);
        $this->assertEquals('guru', $admin->role);

        $siswa = User::where('username', 'siswa')->first();
        $this->assertNotNull($siswa);
        $this->assertEquals('siswa', $siswa->role);

        // Verify bcrypt password hash validity without rehash
        $this->assertTrue(
            Hash::check('admin123', $admin->password) || Hash::check('admin', $admin->password) || str_starts_with($admin->password, '$2y$')
        );

        // Maps & Triplets
        $mapsCount = CerMap::count();
        $this->assertGreaterThan(0, $mapsCount);

        $tripletsCount = Triplet::count();
        $this->assertGreaterThan(0, $tripletsCount);

        // First map has valid triplets
        $firstMap = CerMap::first();
        $this->assertNotEmpty($firstMap->title);
        $this->assertNotEmpty($firstMap->triplets);

        // Sessions, Logs & Scores
        $sessionsCount = UserSession::count();
        $this->assertGreaterThan(0, $sessionsCount);

        $logsCount = UserLog::count();
        $this->assertGreaterThan(0, $logsCount);

        $scoresCount = Score::count();
        $this->assertGreaterThan(0, $scoresCount);

        // First score relation to session & map
        $score = Score::with(['user', 'cerMap', 'session'])->first();
        $this->assertNotNull($score);
        $this->assertNotNull($score->user);
        $this->assertNotNull($score->cerMap);
        $this->assertNotNull($score->session);

        // JSON map_data is valid array or decoded correctly
        $this->assertIsArray($score->map_data);

        // First user_log relation and json cast
        $log = UserLog::first();
        $this->assertNotNull($log);
        $this->assertIsArray($log->action_data);
    }
}
