<?php

namespace Tests\Feature;

use App\Models\CerMap;
use App\Models\Score;
use App\Models\StudentTestScore;
use App\Models\Triplet;
use App\Models\User;
use App\Models\UserLog;
use App\Models\UserSession;
use App\Services\ActivityAnalyticsService;
use App\Services\ClusteringService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ClusteringTest extends TestCase
{
    protected User $guru;
    protected User $siswa1;
    protected User $siswa2;
    protected User $siswa3;
    protected CerMap $map;
    protected ClusteringService $clusteringService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clusteringService = new ClusteringService();

        $this->guru = User::firstOrCreate(
            ['username' => 'guru_clustering_test'],
            [
                'namalengkap' => 'Guru Clustering Test',
                'password'    => 'secret123',
                'role'        => 'guru',
            ]
        );

        $this->siswa1 = User::firstOrCreate(
            ['username' => 'mhs_andi_cluster'],
            [
                'namalengkap' => 'Andi Wijaya',
                'password'    => 'secret123',
                'role'        => 'siswa',
            ]
        );

        $this->siswa2 = User::firstOrCreate(
            ['username' => 'mhs_budi_cluster'],
            [
                'namalengkap' => 'Budi Santoso',
                'password'    => 'secret123',
                'role'        => 'siswa',
            ]
        );

        $this->siswa3 = User::firstOrCreate(
            ['username' => 'mhs_citra_cluster'],
            [
                'namalengkap' => 'Citra Lestari',
                'password'    => 'secret123',
                'role'        => 'siswa',
            ]
        );

        $this->map = CerMap::firstOrCreate(
            ['title' => 'Materi Biologi Sel Clustering'],
            [
                'allow_feedback' => true,
                'allow_reading'  => true,
                'reading_text'   => 'Teks materi biologi sel...',
            ]
        );

        // Ensure at least 2 triplets exist
        if ($this->map->triplets()->count() === 0) {
            Triplet::create([
                'map_id'    => $this->map->id,
                'claim'     => 'Klaim 1',
                'evidence'  => 'Bukti 1',
                'reasoning' => 'Alasan 1',
            ]);
            Triplet::create([
                'map_id'    => $this->map->id,
                'claim'     => 'Klaim 2',
                'evidence'  => 'Bukti 2',
                'reasoning' => 'Alasan 2',
            ]);
        }
    }

    public function test_open_material_and_close_material_logging_with_duration(): void
    {
        $session = UserSession::create([
            'user_id'    => $this->siswa1->id,
            'map_id'     => $this->map->id,
            'start_time' => now(),
        ]);

        // Student opens reading modal
        $resOpen = $this->actingAs($this->siswa1)->postJson('/api/logs', [
            'session_id'  => $session->id,
            'action_type' => 'open_material',
            'action_data' => [
                'map_id'     => $this->map->id,
                'start_time' => now()->toIso8601String(),
            ],
        ]);
        $resOpen->assertStatus(201);

        // Student closes reading modal after reading 45 seconds
        $resClose = $this->actingAs($this->siswa1)->postJson('/api/logs', [
            'session_id'  => $session->id,
            'action_type' => 'close_material',
            'action_data' => [
                'map_id'                   => $this->map->id,
                'reading_duration_seconds' => 45,
            ],
        ]);
        $resClose->assertStatus(201);

        // Verify analytics computes the duration correctly
        $analyticsService = new ActivityAnalyticsService();
        $analytics = $analyticsService->calculateSessionAnalytics($session);

        $this->assertEquals(1, $analytics['reading_count']);
        $this->assertEquals(45, $analytics['reading_duration_seconds']);

        // Cleanup
        $session->logs()->delete();
        $session->delete();
    }

    public function test_student_test_scores_model_and_gain_diff(): void
    {
        $testScore = StudentTestScore::updateOrCreate(
            [
                'user_id'    => $this->siswa1->id,
                'cer_map_id' => $this->map->id,
            ],
            [
                'pre_test'     => 60.00,
                'post_test'    => 85.50,
                'delayed_test' => 80.00,
            ]
        );

        $this->assertEquals(60.00, $testScore->pre_test);
        $this->assertEquals(85.50, $testScore->post_test);
        $this->assertEquals(25.50, $testScore->score_diff); // 85.50 - 60.00 = 25.50
    }

    public function test_clustering_service_aggregation_and_kmeans_3_clusters(): void
    {
        // Setup 3 students with distinct activity profiles:
        // Student 1 (High performer): high reading, high map score, high post test
        $s1 = UserSession::create(['user_id' => $this->siswa1->id, 'map_id' => $this->map->id, 'start_time' => now()->subMinutes(20), 'is_submitted' => true]);
        Score::create(['user_id' => $this->siswa1->id, 'map_id' => $this->map->id, 'session_id' => $s1->id, 'score' => 95.00, 'submitted_at' => now()->subMinutes(5)]);
        UserLog::create(['session_id' => $s1->id, 'action_type' => 'open_material', 'action_data' => ['map_id' => $this->map->id]]);
        UserLog::create(['session_id' => $s1->id, 'action_type' => 'close_material', 'action_data' => ['reading_duration_seconds' => 180]]);
        UserLog::create(['session_id' => $s1->id, 'action_type' => 'connect', 'action_data' => ['from' => 'c-1', 'to' => 'e-1']]);
        UserLog::create(['session_id' => $s1->id, 'action_type' => 'connect', 'action_data' => ['from' => 'e-1', 'to' => 'r-1']]);
        StudentTestScore::updateOrCreate(['user_id' => $this->siswa1->id, 'cer_map_id' => $this->map->id], ['pre_test' => 60, 'post_test' => 90]);

        // Student 2 (Medium performer): medium reading, medium map score
        $s2 = UserSession::create(['user_id' => $this->siswa2->id, 'map_id' => $this->map->id, 'start_time' => now()->subMinutes(15), 'is_submitted' => true]);
        Score::create(['user_id' => $this->siswa2->id, 'map_id' => $this->map->id, 'session_id' => $s2->id, 'score' => 70.00, 'submitted_at' => now()->subMinutes(5)]);
        UserLog::create(['session_id' => $s2->id, 'action_type' => 'open_material', 'action_data' => ['map_id' => $this->map->id]]);
        UserLog::create(['session_id' => $s2->id, 'action_type' => 'close_material', 'action_data' => ['reading_duration_seconds' => 60]]);
        UserLog::create(['session_id' => $s2->id, 'action_type' => 'connect', 'action_data' => ['from' => 'c-1', 'to' => 'e-1']]);
        UserLog::create(['session_id' => $s2->id, 'action_type' => 'disconnect', 'action_data' => ['from' => 'c-1', 'to' => 'e-1']]);
        StudentTestScore::updateOrCreate(['user_id' => $this->siswa2->id, 'cer_map_id' => $this->map->id], ['pre_test' => 55, 'post_test' => 75]);

        // Student 3 (Low performer): low reading, low map score
        $s3 = UserSession::create(['user_id' => $this->siswa3->id, 'map_id' => $this->map->id, 'start_time' => now()->subMinutes(10), 'is_submitted' => true]);
        Score::create(['user_id' => $this->siswa3->id, 'map_id' => $this->map->id, 'session_id' => $s3->id, 'score' => 40.00, 'submitted_at' => now()->subMinutes(5)]);
        UserLog::create(['session_id' => $s3->id, 'action_type' => 'move', 'action_data' => ['card' => 'c-1', 'from' => 0, 'to' => 1]]);
        StudentTestScore::updateOrCreate(['user_id' => $this->siswa3->id, 'cer_map_id' => $this->map->id], ['pre_test' => 50, 'post_test' => 55]);

        // Run Clustering
        $result = $this->clusteringService->cluster($this->map->id, 3);

        $this->assertTrue($result['success']);
        $this->assertCount(3, $result['clusters']);
        $this->assertCount(3, $result['students']);

        // Check cluster ranking by avg_map_score descending
        $c1 = $result['clusters'][0];
        $c2 = $result['clusters'][1];
        $c3 = $result['clusters'][2];

        $this->assertEquals('Cluster 1', $c1['cluster_label']);
        $this->assertEquals('Cluster 2', $c2['cluster_label']);
        $this->assertEquals('Cluster 3', $c3['cluster_label']);

        $this->assertGreaterThanOrEqual($c2['avg_map_score'], $c1['avg_map_score']);
        $this->assertGreaterThanOrEqual($c3['avg_map_score'], $c2['avg_map_score']);

        // Check insight summary generated
        $this->assertNotEmpty($result['insights']['summary']);

        // Cleanup
        $s1->logs()->delete();
        $s2->logs()->delete();
        $s3->logs()->delete();
        Score::whereIn('session_id', [$s1->id, $s2->id, $s3->id])->delete();
        $s1->delete();
        $s2->delete();
        $s3->delete();
    }

    public function test_guru_can_access_clustering_page(): void
    {
        $response = $this->actingAs($this->guru)->get(route('admin.clustering', ['map_id' => $this->map->id]));
        $response->assertStatus(200);
        $response->assertSee('Analisis Clustering K-Means');
        $response->assertSee('Kelola Nilai Pre/Post-Test');
    }

    public function test_siswa_cannot_access_clustering_page(): void
    {
        $response = $this->actingAs($this->siswa1)->get(route('admin.clustering'));
        $response->assertStatus(403);
    }

    public function test_guru_can_update_test_score_via_json(): void
    {
        $res = $this->actingAs($this->guru)->postJson(route('admin.clustering.test-scores.update'), [
            'user_id'      => $this->siswa1->id,
            'cer_map_id'   => $this->map->id,
            'pre_test'     => 65,
            'post_test'    => 88,
            'delayed_test' => 82,
        ]);

        $res->assertStatus(200);
        $res->assertJsonPath('success', true);
        $res->assertJsonPath('data.score_diff', 23);

        $this->assertDatabaseHas('student_test_scores', [
            'user_id'    => $this->siswa1->id,
            'cer_map_id' => $this->map->id,
            'pre_test'   => 65.00,
            'post_test'  => 88.00,
        ]);
    }

    public function test_guru_can_export_clustering_dataset_csv(): void
    {
        $res = $this->actingAs($this->guru)->get(route('admin.clustering.export', ['map_id' => $this->map->id]));
        $res->assertStatus(200);
        $res->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_guru_can_download_and_import_test_scores_csv(): void
    {
        // Download template
        $templateRes = $this->actingAs($this->guru)->get(route('admin.clustering.test-scores.template'));
        $templateRes->assertStatus(200);
        $templateRes->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // Prepare CSV import
        $csvContent = "username,pre_test,post_test,delayed_test\n" .
                      "{$this->siswa1->username},62.50,89.00,85.00\n" .
                      "{$this->siswa2->username},71.00,84.50,80.00\n";

        $file = UploadedFile::fake()->createWithContent('scores.csv', $csvContent);

        $importRes = $this->actingAs($this->guru)->post(route('admin.clustering.test-scores.import'), [
            'cer_map_id' => $this->map->id,
            'csv_file'   => $file,
        ]);

        $importRes->assertRedirect(route('admin.clustering', ['map_id' => $this->map->id]));

        $this->assertDatabaseHas('student_test_scores', [
            'user_id'    => $this->siswa1->id,
            'cer_map_id' => $this->map->id,
            'pre_test'   => 62.50,
            'post_test'  => 89.00,
        ]);

        $this->assertDatabaseHas('student_test_scores', [
            'user_id'    => $this->siswa2->id,
            'cer_map_id' => $this->map->id,
            'pre_test'   => 71.00,
            'post_test'  => 84.50,
        ]);
    }
}

