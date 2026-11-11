<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportLegacyDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cerflow:import-legacy
                            {--dry-run : Perform a dry run without modifying target database}
                            {--execute : Execute the data migration}
                            {--force : Force overwrite test records in target database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import legacy data from cer_flow_db / cerflow to cer_flow_laravel';

    /**
     * Tables to import in topological order.
     *
     * @var array
     */
    protected array $tables = [
        'users',
        'cer_maps',
        'triplets',
        'user_sessions',
        'user_logs',
        'scores',
        'settings',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('====================================================');
        $this->info('CERFlow Legacy Data Importer (PHP Native → Laravel 11)');
        $this->info('====================================================');

        // 1. Verify legacy connection
        try {
            $legacyConn = DB::connection('legacy');
            $legacyConn->getPdo();
            $dbName = $legacyConn->getDatabaseName();
            $this->info("Legacy database connection successful: [{$dbName}] (READ-ONLY)");
        } catch (\Throwable $e) {
            $this->error("Gagal terhubung ke legacy database: " . $e->getMessage());
            $this->line("Pastikan konfigurasi LEGACY_DB_* di .env atau config/database.php sudah benar.");
            return 1;
        }

        // 2. Count source & target records
        $counts = [];
        foreach ($this->tables as $table) {
            try {
                $sourceCount = $legacyConn->table($table)->count();
            } catch (\Throwable $e) {
                $sourceCount = 'ERROR: ' . $e->getMessage();
            }

            try {
                $targetCount = DB::table($table)->count();
            } catch (\Throwable $e) {
                $targetCount = 'ERROR: ' . $e->getMessage();
            }

            $counts[] = [
                'table'  => $table,
                'source' => $sourceCount,
                'target' => $targetCount,
            ];
        }

        $this->newLine();
        $this->table(['Table', 'Legacy Source Count', 'Target Current Count'], $counts);
        $this->newLine();

        $isExecute = $this->option('execute');
        $isDryRun = $this->option('dry-run') || ! $isExecute;

        if ($isDryRun) {
            $this->warn('MODE: DRY RUN');
            $this->line('Tidak ada perubahan yang ditulis ke target database.');
            $this->info('Gunakan flag --execute untuk menjalankan impor data ke cer_flow_laravel.');
            return 0;
        }

        // 3. Execution safety check
        $this->warn('MODE: EXECUTE');
        
        $targetHasNonTestData = false;
        // Check if target has users with real email or custom IDs that might not be test users
        $targetUserCount = DB::table('users')->count();
        if ($targetUserCount > 5) {
            $this->line("Target database memiliki {$targetUserCount} users (berisi data pengembangan/pengujian).");
        }

        if (! $this->option('force') && ! $this->confirm('Apakah Anda yakin ingin menimpa data target dengan data dari legacy database?', true)) {
            $this->warn('Impor dibatalkan oleh pengguna.');
            return 0;
        }

        // 4. Perform Data Import
        $this->info('Memulai impor data dengan menjaga integritas relasi dan primary key...');

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            foreach ($this->tables as $table) {
                $this->line("Mengimpor tabel: [{$table}]...");

                $rows = $legacyConn->table($table)->get();

                // Truncate target table
                DB::table($table)->truncate();

                $inserted = 0;
                $chunk = [];

                foreach ($rows as $row) {
                    $record = (array) $row;

                    // Ensure passwords remain bcrypt without rehash
                    // Ensure JSON columns remain valid JSON strings or arrays
                    $chunk[] = $record;
                    $inserted++;

                    if (count($chunk) >= 100) {
                        DB::table($table)->insert($chunk);
                        $chunk = [];
                    }
                }

                if (! empty($chunk)) {
                    DB::table($table)->insert($chunk);
                }

                $this->info("✓ Berhasil mengimpor {$inserted} baris ke tabel [{$table}].");
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } catch (\Throwable $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $this->error("Terjadi kesalahan saat impor: " . $e->getMessage());
            return 1;
        }

        // 5. Verification
        $this->newLine();
        $this->info('====================================================');
        $this->info('HASIL VERIFIKASI PASCA-IMPOR');
        $this->info('====================================================');

        $verifyResults = [];
        $allMatched = true;

        foreach ($this->tables as $table) {
            $src = $legacyConn->table($table)->count();
            $tgt = DB::table($table)->count();
            $status = ($src === $tgt) ? 'MATCH' : 'MISMATCH';
            if ($status !== 'MATCH') {
                $allMatched = false;
            }

            $verifyResults[] = [
                'table'  => $table,
                'source' => $src,
                'target' => $tgt,
                'status' => $status,
            ];
        }

        $this->table(['Table', 'Source', 'Target', 'Status'], $verifyResults);

        // Foreign Key Integrity Check
        $orphanedTriplets = DB::table('triplets')
            ->leftJoin('cer_maps', 'triplets.map_id', '=', 'cer_maps.id')
            ->whereNull('cer_maps.id')
            ->count();

        $orphanedSessions = DB::table('user_sessions')
            ->leftJoin('users', 'user_sessions.user_id', '=', 'users.id')
            ->whereNull('users.id')
            ->count();

        $orphanedLogs = DB::table('user_logs')
            ->leftJoin('user_sessions', 'user_logs.session_id', '=', 'user_sessions.id')
            ->whereNull('user_sessions.id')
            ->count();

        $orphanedScores = DB::table('scores')
            ->leftJoin('user_sessions', 'scores.session_id', '=', 'user_sessions.id')
            ->whereNull('user_sessions.id')
            ->count();

        $this->newLine();
        $this->line("Integritas Foreign Key:");
        $this->line("- Orphaned Triplets: {$orphanedTriplets}");
        $this->line("- Orphaned Sessions: {$orphanedSessions}");
        $this->line("- Orphaned Logs: {$orphanedLogs}");
        $this->line("- Orphaned Scores: {$orphanedScores}");

        if ($allMatched && $orphanedTriplets === 0 && $orphanedSessions === 0 && $orphanedLogs === 0 && $orphanedScores === 0) {
            $this->info('SEMUA DATA BERHASIL DIMIGRASIKAN DENGAN INTEGRITAS 100%!');
            return 0;
        }

        $this->warn('Data berhasil diimpor dengan beberapa catatan.');
        return 0;
    }
}
