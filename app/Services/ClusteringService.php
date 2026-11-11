<?php

namespace App\Services;

use App\Models\CerMap;
use App\Models\Score;
use App\Models\StudentTestScore;
use App\Models\User;
use App\Models\UserLog;
use App\Models\UserSession;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ClusteringService
{
    /**
     * Feature keys used in 7-dimensional clustering
     */
    public const FEATURE_KEYS = [
        'open_material_count',
        'total_reading_duration',
        'connect_count',
        'disconnect_count',
        'move_count',
        'completion_duration',
        'total_actions',
    ];

    /**
     * Run K-Means Clustering on a specific CER Map.
     *
     * @param int $mapId
     * @param int $k
     * @return array
     */
    public function cluster(int $mapId, int $k = 3): array
    {
        $map = CerMap::find($mapId);
        if (!$map) {
            return [
                'success'       => false,
                'message'       => 'Materi CER Map tidak ditemukan.',
                'map'           => null,
                'clusters'      => [],
                'students'      => [],
                'feature_stats' => [],
                'k'             => $k,
            ];
        }

        // 1. Extract raw feature vectors per student
        $rawStudents = $this->extractStudentVectors($mapId);

        if ($rawStudents->isEmpty()) {
            return [
                'success'       => true,
                'message'       => 'Belum ada data pengerjaan mahasiswa untuk materi ini.',
                'map'           => $map,
                'clusters'      => [],
                'students'      => [],
                'feature_stats' => [],
                'k'             => $k,
            ];
        }

        // 2. Standardize features using Z-Score
        $standardization = $this->standardizeFeatures($rawStudents->toArray());
        $studentsWithZ = $standardization['students'];
        $featureStats = $standardization['stats'];

        $totalStudents = count($studentsWithZ);
        $actualK = min($k, $totalStudents);

        // 3. Run K-Means algorithm
        $rawClusters = $this->runKMeans($studentsWithZ, $actualK);

        // 4. Compute cluster evaluation & statistics
        $evaluatedClusters = $this->evaluateClusters($rawClusters, $studentsWithZ);

        // 5. Rank and rename clusters by avg_map_score descending
        usort($evaluatedClusters, function ($a, $b) {
            return ($b['avg_map_score'] <=> $a['avg_map_score']);
        });

        // Remap cluster identifiers to Cluster 1 (Highest), Cluster 2, Cluster 3
        $finalClusters = [];
        $studentClusterMap = [];

        foreach ($evaluatedClusters as $index => $c) {
            $clusterNumber = $index + 1;
            $clusterLabel = "Cluster {$clusterNumber}";
            
            $tierName = match ($clusterNumber) {
                1 => 'Tinggi / Efektif',
                2 => 'Sedang',
                3 => 'Rendah / Butuh Bimbingan',
                default => "Kelompok {$clusterNumber}",
            };

            $c['cluster_id'] = $clusterNumber;
            $c['cluster_label'] = $clusterLabel;
            $c['tier_name'] = $tierName;

            foreach ($c['member_user_ids'] as $uid) {
                $studentClusterMap[$uid] = [
                    'cluster_id'    => $clusterNumber,
                    'cluster_label' => $clusterLabel,
                    'tier_name'     => $tierName,
                ];
            }

            $finalClusters[] = $c;
        }

        // Update student items with new cluster label and rank
        $finalStudents = array_map(function ($s) use ($studentClusterMap) {
            $uid = $s['user_id'];
            if (isset($studentClusterMap[$uid])) {
                $s['cluster_id']    = $studentClusterMap[$uid]['cluster_id'];
                $s['cluster_label'] = $studentClusterMap[$uid]['cluster_label'];
                $s['tier_name']     = $studentClusterMap[$uid]['tier_name'];
            }
            return $s;
        }, $studentsWithZ);

        // Sort students primarily by cluster_id, then by map_score descending
        usort($finalStudents, function ($a, $b) {
            if ($a['cluster_id'] !== $b['cluster_id']) {
                return $a['cluster_id'] <=> $b['cluster_id'];
            }
            return ($b['map_score'] ?? 0) <=> ($a['map_score'] ?? 0);
        });

        // Generate research insights
        $insights = $this->generateInsights($finalClusters);

        return [
            'success'       => true,
            'message'       => 'Clustering K-Means berhasil diproses.',
            'map'           => $map,
            'clusters'      => $finalClusters,
            'students'      => $finalStudents,
            'feature_stats' => $featureStats,
            'insights'      => $insights,
            'k'             => $actualK,
            'total_students'=> $totalStudents,
        ];
    }

    /**
     * Extract 7 numerical feature vectors + academic test scores per student for a given map.
     */
    public function extractStudentVectors(int $mapId): Collection
    {
        // Find all student users who interacted with this map:
        // Either they have a Score, a UserSession, or a StudentTestScore on this map.
        $userIdsFromScores = Score::where('map_id', $mapId)->pluck('user_id');
        $userIdsFromSessions = UserSession::where('map_id', $mapId)->pluck('user_id');
        $userIdsFromTests = StudentTestScore::where('cer_map_id', $mapId)->pluck('user_id');

        $allUserIds = $userIdsFromScores->merge($userIdsFromSessions)
            ->merge($userIdsFromTests)
            ->filter()
            ->unique()
            ->values();

        if ($allUserIds->isEmpty()) {
            return collect();
        }

        $students = User::whereIn('id', $allUserIds)
            ->where('role', 'siswa')
            ->get();

        // Preload latest score, test score, and sessions with logs
        $testScores = StudentTestScore::where('cer_map_id', $mapId)
            ->whereIn('user_id', $allUserIds)
            ->get()
            ->keyBy('user_id');

        $scores = Score::where('map_id', $mapId)
            ->whereIn('user_id', $allUserIds)
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy('user_id');

        $sessions = UserSession::with(['logs' => fn ($q) => $q->orderBy('created_at', 'asc')->orderBy('id', 'asc'), 'score'])
            ->where('map_id', $mapId)
            ->whereIn('user_id', $allUserIds)
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy('user_id');

        $vectors = collect();

        foreach ($students as $student) {
            $uid = $student->id;
            $userSessions = $sessions->get($uid, collect());

            // Choose primary session: prefer latest submitted session, or latest session
            $primarySession = $userSessions->firstWhere('is_submitted', true) ?? $userSessions->first();

            // Extract activity logs from primary session (or all sessions if primary not found)
            $logs = $primarySession ? $primarySession->logs : collect();

            $openMaterialCount = 0;
            $totalReadingDuration = 0;
            $connectCount = 0;
            $disconnectCount = 0;
            $moveCount = 0;
            $totalActions = $logs->count();

            $currentOpenTimestamp = null;

            foreach ($logs as $log) {
                $type = $log->action_type;
                $data = is_array($log->action_data) ? $log->action_data : (array) $log->action_data;
                $ts = $log->created_at ?? now();

                switch ($type) {
                    case 'open_material':
                    case 'view_reading':
                        $openMaterialCount++;
                        $currentOpenTimestamp = $ts;
                        break;

                    case 'close_material':
                    case 'reading_close':
                        $duration = isset($data['reading_duration_seconds']) ? (int) $data['reading_duration_seconds'] : null;
                        if ($duration === null && $currentOpenTimestamp !== null) {
                            $duration = abs((int) Carbon::parse($ts)->diffInSeconds(Carbon::parse($currentOpenTimestamp)));
                        }
                        if ($duration !== null) {
                            $totalReadingDuration += $duration;
                            $currentOpenTimestamp = null;
                        }
                        break;

                    case 'connect':
                        $connectCount++;
                        break;

                    case 'disconnect':
                        $disconnectCount++;
                        break;

                    case 'move':
                        $moveCount++;
                        break;
                }
            }

            // Completion duration in seconds
            $completionDuration = 0;
            if ($primarySession) {
                if ($primarySession->is_submitted && $primarySession->score && $primarySession->start_time) {
                    $start = Carbon::parse($primarySession->start_time);
                    $sub = Carbon::parse($primarySession->score->submitted_at);
                    $completionDuration = abs((int) $sub->diffInSeconds($start));
                } elseif ($logs->isNotEmpty() && $primarySession->start_time) {
                    $start = Carbon::parse($primarySession->start_time);
                    $last = Carbon::parse($logs->last()->created_at);
                    $completionDuration = abs((int) $last->diffInSeconds($start));
                }
            }

            // Academic scores
            $userScoreRecord = $scores->get($uid)?->first();
            $mapScore = $userScoreRecord ? (float) $userScoreRecord->score : null;

            $testScoreRecord = $testScores->get($uid);
            $preTest = $testScoreRecord?->pre_test;
            $postTest = $testScoreRecord?->post_test;
            $delayedTest = $testScoreRecord?->delayed_test;
            $scoreDiff = ($postTest !== null && $preTest !== null) ? round($postTest - $preTest, 2) : null;

            $rawFeatures = [
                'open_material_count'    => (float) $openMaterialCount,
                'total_reading_duration' => (float) $totalReadingDuration,
                'connect_count'          => (float) $connectCount,
                'disconnect_count'       => (float) $disconnectCount,
                'move_count'             => (float) $moveCount,
                'completion_duration'    => (float) $completionDuration,
                'total_actions'          => (float) $totalActions,
            ];

            $vectors->push([
                'user_id'      => $student->id,
                'username'     => $student->username,
                'student_name' => $student->namalengkap,
                'map_id'       => $mapId,
                'session_id'   => $primarySession?->id,
                'map_score'    => $mapScore,
                'pre_test'     => $preTest,
                'post_test'    => $postTest,
                'delayed_test' => $delayedTest,
                'score_diff'   => $scoreDiff,
                'raw_features' => $rawFeatures,
            ]);
        }

        return $vectors;
    }

    /**
     * Standardize 7 numerical features using Z-score standardization.
     * z = (x - mean) / std_dev.
     * If std_dev == 0, z = 0.
     */
    public function standardizeFeatures(array $students): array
    {
        $n = count($students);
        if ($n === 0) {
            return ['students' => [], 'stats' => []];
        }

        $stats = [];

        // 1. Calculate Mean and Standard Deviation for each feature
        foreach (self::FEATURE_KEYS as $key) {
            $values = array_map(fn ($s) => (float) ($s['raw_features'][$key] ?? 0), $students);
            $mean = array_sum($values) / $n;

            $varianceSum = 0.0;
            foreach ($values as $v) {
                $varianceSum += pow($v - $mean, 2);
            }
            // Population standard deviation (or sample if n > 1)
            $stdDev = ($n > 1) ? sqrt($varianceSum / ($n - 1)) : 0.0;

            $stats[$key] = [
                'mean'    => round($mean, 4),
                'std_dev' => round($stdDev, 4),
                'min'     => min($values),
                'max'     => max($values),
            ];
        }

        // 2. Compute Z-Scores for each student
        $standardizedStudents = [];
        foreach ($students as $student) {
            $zFeatures = [];
            foreach (self::FEATURE_KEYS as $key) {
                $rawVal = (float) ($student['raw_features'][$key] ?? 0);
                $mean = $stats[$key]['mean'];
                $std = $stats[$key]['std_dev'];

                if ($std > 1e-6) {
                    $zFeatures[$key] = round(($rawVal - $mean) / $std, 4);
                } else {
                    $zFeatures[$key] = 0.0;
                }
            }

            $student['z_features'] = $zFeatures;
            $standardizedStudents[] = $student;
        }

        return [
            'students' => $standardizedStudents,
            'stats'    => $stats,
        ];
    }

    /**
     * Deterministic K-Means clustering algorithm ($K=3$).
     *
     * @param array $students
     * @param int $k
     * @param int $maxIterations
     * @return array Array of clusters containing member indices
     */
    public function runKMeans(array $students, int $k = 3, int $maxIterations = 100): array
    {
        $n = count($students);
        if ($n === 0) return [];
        if ($k > $n) $k = $n;

        // Initialize centroids deterministically using K-Means++ deterministic furthest-point
        $centroids = $this->initializeDeterministicCentroids($students, $k);

        $assignments = array_fill(0, $n, 0);

        for ($iter = 0; $iter < $maxIterations; $iter++) {
            $changed = false;

            // 1. Assign each student to nearest centroid
            for ($i = 0; $i < $n; $i++) {
                $minDist = INF;
                $closestCentroid = 0;

                for ($c = 0; $c < $k; $c++) {
                    $dist = $this->euclideanDistance($students[$i]['z_features'], $centroids[$c]);
                    if ($dist < $minDist) {
                        $minDist = $dist;
                        $closestCentroid = $c;
                    }
                }

                if ($assignments[$i] !== $closestCentroid) {
                    $assignments[$i] = $closestCentroid;
                    $changed = true;
                }
            }

            // 2. Recompute centroids
            $newCentroids = [];
            $clusterCounts = array_fill(0, $k, 0);

            for ($c = 0; $c < $k; $c++) {
                $sum = array_fill_keys(self::FEATURE_KEYS, 0.0);
                $count = 0;

                for ($i = 0; $i < $n; $i++) {
                    if ($assignments[$i] === $c) {
                        $count++;
                        foreach (self::FEATURE_KEYS as $fKey) {
                            $sum[$fKey] += $students[$i]['z_features'][$fKey];
                        }
                    }
                }

                $clusterCounts[$c] = $count;

                if ($count > 0) {
                    foreach (self::FEATURE_KEYS as $fKey) {
                        $sum[$fKey] = round($sum[$fKey] / $count, 4);
                    }
                    $newCentroids[$c] = $sum;
                } else {
                    // Empty cluster fallback: assign the student furthest from its current centroid
                    $furthestIndex = 0;
                    $maxDistFromOwn = -1.0;
                    for ($i = 0; $i < $n; $i++) {
                        $ownCentroid = $centroids[$assignments[$i]];
                        $d = $this->euclideanDistance($students[$i]['z_features'], $ownCentroid);
                        if ($d > $maxDistFromOwn) {
                            $maxDistFromOwn = $d;
                            $furthestIndex = $i;
                        }
                    }
                    $assignments[$furthestIndex] = $c;
                    $newCentroids[$c] = $students[$furthestIndex]['z_features'];
                }
            }

            // Check centroid convergence
            $shift = 0.0;
            for ($c = 0; $c < $k; $c++) {
                $shift += $this->euclideanDistance($centroids[$c], $newCentroids[$c]);
            }

            $centroids = $newCentroids;

            if (!$changed || $shift < 1e-4) {
                break;
            }
        }

        // Group into cluster arrays
        $clusters = [];
        for ($c = 0; $c < $k; $c++) {
            $memberIndices = [];
            for ($i = 0; $i < $n; $i++) {
                if ($assignments[$i] === $c) {
                    $memberIndices[] = $i;
                }
            }
            $clusters[$c] = [
                'raw_cluster_id' => $c,
                'centroid'       => $centroids[$c],
                'member_indices' => $memberIndices,
            ];
        }

        return $clusters;
    }

    /**
     * Deterministic K-Means++ initial centroid selector.
     * Picks first point deterministically (highest composite activity / first sorted point),
     * then iteratively picks points with maximum squared distance to existing centroids.
     */
    protected function initializeDeterministicCentroids(array $students, int $k): array
    {
        $n = count($students);
        $centroids = [];

        // 1. First centroid: student index with highest sum of standardized features (or index 0)
        $firstIndex = 0;
        $maxSum = -INF;
        for ($i = 0; $i < $n; $i++) {
            $sum = array_sum($students[$i]['z_features']);
            if ($sum > $maxSum) {
                $maxSum = $sum;
                $firstIndex = $i;
            }
        }
        $centroids[] = $students[$firstIndex]['z_features'];

        // 2. Select subsequent centroids by furthest minimum squared distance
        while (count($centroids) < $k) {
            $maxMinDist = -1.0;
            $bestCandidate = 0;

            for ($i = 0; $i < $n; $i++) {
                $minDistToCentroids = INF;
                foreach ($centroids as $c) {
                    $d = $this->euclideanDistance($students[$i]['z_features'], $c);
                    if ($d < $minDistToCentroids) {
                        $minDistToCentroids = $d;
                    }
                }

                if ($minDistToCentroids > $maxMinDist) {
                    $maxMinDist = $minDistToCentroids;
                    $bestCandidate = $i;
                }
            }

            $centroids[] = $students[$bestCandidate]['z_features'];
        }

        return $centroids;
    }

    /**
     * Calculate Euclidean distance between two 7-dimensional vectors.
     */
    public function euclideanDistance(array $v1, array $v2): float
    {
        $sum = 0.0;
        foreach (self::FEATURE_KEYS as $key) {
            $x = (float) ($v1[$key] ?? 0);
            $y = (float) ($v2[$key] ?? 0);
            $sum += pow($x - $y, 2);
        }
        return sqrt($sum);
    }

    /**
     * Compute cluster averages for raw features and academic scores.
     */
    protected function evaluateClusters(array $rawClusters, array $students): array
    {
        $evaluated = [];

        foreach ($rawClusters as $cluster) {
            $indices = $cluster['member_indices'];
            $memberCount = count($indices);

            $memberUserIds = [];
            $rawFeatureSums = array_fill_keys(self::FEATURE_KEYS, 0.0);

            $mapScores = [];
            $preTests = [];
            $postTests = [];
            $scoreDiffs = [];

            foreach ($indices as $idx) {
                $s = $students[$idx];
                $memberUserIds[] = $s['user_id'];

                foreach (self::FEATURE_KEYS as $fKey) {
                    $rawFeatureSums[$fKey] += (float) ($s['raw_features'][$fKey] ?? 0);
                }

                if ($s['map_score'] !== null) {
                    $mapScores[] = (float) $s['map_score'];
                }
                if ($s['pre_test'] !== null) {
                    $preTests[] = (float) $s['pre_test'];
                }
                if ($s['post_test'] !== null) {
                    $postTests[] = (float) $s['post_test'];
                }
                if ($s['score_diff'] !== null) {
                    $scoreDiffs[] = (float) $s['score_diff'];
                }
            }

            // Raw feature means
            $avgRawFeatures = [];
            foreach (self::FEATURE_KEYS as $fKey) {
                $avgRawFeatures[$fKey] = $memberCount > 0 ? round($rawFeatureSums[$fKey] / $memberCount, 2) : 0.0;
            }

            // Academic metrics
            $avgMapScore = count($mapScores) > 0 ? round(array_sum($mapScores) / count($mapScores), 2) : 0.0;
            $avgPreTest = count($preTests) > 0 ? round(array_sum($preTests) / count($preTests), 2) : null;
            $avgPostTest = count($postTests) > 0 ? round(array_sum($postTests) / count($postTests), 2) : null;
            $avgScoreDiff = count($scoreDiffs) > 0 ? round(array_sum($scoreDiffs) / count($scoreDiffs), 2) : ($avgPostTest !== null && $avgPreTest !== null ? round($avgPostTest - $avgPreTest, 2) : null);

            $evaluated[] = [
                'raw_cluster_id'        => $cluster['raw_cluster_id'],
                'member_count'          => $memberCount,
                'member_user_ids'       => $memberUserIds,
                'centroid'              => $cluster['centroid'],
                'avg_raw_features'      => $avgRawFeatures,
                'avg_open_material_count' => $avgRawFeatures['open_material_count'],
                'avg_total_reading_duration' => $avgRawFeatures['total_reading_duration'],
                'avg_connect_count'     => $avgRawFeatures['connect_count'],
                'avg_disconnect_count'  => $avgRawFeatures['disconnect_count'],
                'avg_move_count'        => $avgRawFeatures['move_count'],
                'avg_completion_duration' => $avgRawFeatures['completion_duration'],
                'avg_total_actions'     => $avgRawFeatures['total_actions'],
                'avg_map_score'         => $avgMapScore,
                'avg_pre_test'          => $avgPreTest,
                'avg_post_test'         => $avgPostTest,
                'avg_score_diff'        => $avgScoreDiff,
            ];
        }

        return $evaluated;
    }

    /**
     * Generate research correlation insights across the sorted clusters.
     */
    protected function generateInsights(array $clusters): array
    {
        if (count($clusters) < 2) {
            return [
                'summary' => 'Data belum mencukupi untuk melakukan analisis perbandingan korelasi antar kelompok.',
                'correlation_confirmed' => false,
            ];
        }

        $c1 = $clusters[0]; // Highest Map Score
        $cLast = end($clusters); // Lowest Map Score

        $mapDiff = round($c1['avg_map_score'] - $cLast['avg_map_score'], 2);
        
        $hasPostTest = $c1['avg_post_test'] !== null && $cLast['avg_post_test'] !== null;
        $postDiff = $hasPostTest ? round($c1['avg_post_test'] - $cLast['avg_post_test'], 2) : null;

        $hasGain = $c1['avg_score_diff'] !== null && $cLast['avg_score_diff'] !== null;
        $gainDiff = $hasGain ? round($c1['avg_score_diff'] - $cLast['avg_score_diff'], 2) : null;

        $readingDiff = round($c1['avg_total_reading_duration'] - $cLast['avg_total_reading_duration'], 2);
        $actionDiff = round($c1['avg_total_actions'] - $cLast['avg_total_actions'], 2);

        $summary = "Kelompok 1 ({$c1['tier_name']}) memiliki rata-rata Nilai Map CER tertinggi sebesar {$c1['avg_map_score']}%. ";

        if ($hasPostTest && $postDiff > 0) {
            $summary .= "Hal ini berkolerasi positif dengan rata-rata Nilai Post-test ({$c1['avg_post_test']}) yang lebih tinggi (+{$postDiff} poin) dibanding Kelompok 3 ({$cLast['avg_post_test']}). ";
        }

        if ($hasGain && $gainDiff > 0) {
            $summary .= "Peningkatan pemahaman (Gain Score) kelompok ini mencapai +{$c1['avg_score_diff']} poin. ";
        }

        if ($readingDiff > 0) {
            $summary .= "Secara perilaku, kelompok ini mengalokasikan waktu membaca materi lebih intensif ({$c1['avg_total_reading_duration']} detik vs {$cLast['avg_total_reading_duration']} detik).";
        }

        return [
            'summary'               => $summary,
            'map_diff'              => $mapDiff,
            'post_test_diff'        => $postDiff,
            'gain_diff'             => $gainDiff,
            'reading_duration_diff' => $readingDiff,
            'action_diff'           => $actionDiff,
            'correlation_confirmed' => ($mapDiff > 0 && ($postDiff === null || $postDiff >= 0)),
        ];
    }
}

