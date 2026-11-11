<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CerMap;
use App\Models\Score;
use App\Models\StudentTestScore;
use App\Models\User;
use App\Services\ClusteringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClusteringController extends Controller
{
    protected ClusteringService $clusteringService;

    public function __construct(ClusteringService $clusteringService)
    {
        $this->clusteringService = $clusteringService;
    }

    /**
     * Display the K-Means Clustering workspace & results.
     */
    public function index(Request $request): View
    {
        $maps = CerMap::orderBy('id', 'asc')->get();
        $selectedMapId = $request->query('map_id') ? (int) $request->query('map_id') : $maps->first()?->id;

        $clusteringResult = null;
        $studentListForModal = [];

        if ($selectedMapId) {
            $clusteringResult = $this->clusteringService->cluster($selectedMapId, 3);

            // Fetch all siswa users with their current map score & test score for the management modal
            $allStudents = User::where('role', 'siswa')->orderBy('namalengkap', 'asc')->get();
            $existingTestScores = StudentTestScore::where('cer_map_id', $selectedMapId)
                ->get()
                ->keyBy('user_id');

            $existingScores = Score::where('map_id', $selectedMapId)
                ->orderBy('id', 'desc')
                ->get()
                ->groupBy('user_id');

            foreach ($allStudents as $s) {
                $ts = $existingTestScores->get($s->id);
                $latestScore = $existingScores->get($s->id)?->first();

                $studentListForModal[] = [
                    'user_id'      => $s->id,
                    'username'     => $s->username,
                    'namalengkap'  => $s->namalengkap,
                    'map_score'    => $latestScore ? (float) $latestScore->score : null,
                    'pre_test'     => $ts?->pre_test,
                    'post_test'    => $ts?->post_test,
                    'delayed_test' => $ts?->delayed_test,
                    'score_diff'   => $ts?->score_diff,
                ];
            }
        }

        return view('admin.clustering', compact('maps', 'selectedMapId', 'clusteringResult', 'studentListForModal'));
    }

    /**
     * Export raw and Z-Score standardized clustering dataset to CSV.
     */
    public function exportCsv(Request $request): StreamedResponse|RedirectResponse
    {
        $mapId = (int) $request->query('map_id');
        $map = CerMap::find($mapId);

        if (!$map) {
            return redirect()->route('admin.clustering')->with('error', 'Materi tidak ditemukan.');
        }

        $result = $this->clusteringService->cluster($mapId, 3);
        $students = $result['students'] ?? [];

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"clustering_dataset_map_{$mapId}_" . date('Ymd_His') . ".csv\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($students) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // CSV Columns Header
            fputcsv($handle, [
                'user_id',
                'username',
                'nama_lengkap',
                'cluster_id',
                'cluster_label',
                'tier_name',
                'nilai_map_cer',
                'pre_test',
                'post_test',
                'delayed_test',
                'selisih_nilai_gain',
                'raw_open_material_count',
                'raw_total_reading_duration_sec',
                'raw_connect_count',
                'raw_disconnect_count',
                'raw_move_count',
                'raw_completion_duration_sec',
                'raw_total_actions',
                'z_open_material_count',
                'z_total_reading_duration',
                'z_connect_count',
                'z_disconnect_count',
                'z_move_count',
                'z_completion_duration',
                'z_total_actions',
            ]);

            foreach ($students as $s) {
                fputcsv($handle, [
                    $s['user_id'],
                    $s['username'],
                    $s['student_name'],
                    $s['cluster_id'] ?? '',
                    $s['cluster_label'] ?? '',
                    $s['tier_name'] ?? '',
                    $s['map_score'] ?? '',
                    $s['pre_test'] ?? '',
                    $s['post_test'] ?? '',
                    $s['delayed_test'] ?? '',
                    $s['score_diff'] ?? '',
                    $s['raw_features']['open_material_count'] ?? 0,
                    $s['raw_features']['total_reading_duration'] ?? 0,
                    $s['raw_features']['connect_count'] ?? 0,
                    $s['raw_features']['disconnect_count'] ?? 0,
                    $s['raw_features']['move_count'] ?? 0,
                    $s['raw_features']['completion_duration'] ?? 0,
                    $s['raw_features']['total_actions'] ?? 0,
                    $s['z_features']['open_material_count'] ?? 0,
                    $s['z_features']['total_reading_duration'] ?? 0,
                    $s['z_features']['connect_count'] ?? 0,
                    $s['z_features']['disconnect_count'] ?? 0,
                    $s['z_features']['move_count'] ?? 0,
                    $s['z_features']['completion_duration'] ?? 0,
                    $s['z_features']['total_actions'] ?? 0,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * AJAX / Form endpoint to update Pre-test and Post-test scores for a student.
     */
    public function updateTestScore(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'user_id'      => ['required', 'integer', 'exists:users,id'],
            'cer_map_id'   => ['required', 'integer', 'exists:cer_maps,id'],
            'pre_test'     => ['nullable', 'numeric', 'min:0', 'max:100'],
            'post_test'    => ['nullable', 'numeric', 'min:0', 'max:100'],
            'delayed_test' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $scoreRecord = StudentTestScore::updateOrCreate(
            [
                'user_id'    => $validated['user_id'],
                'cer_map_id' => $validated['cer_map_id'],
            ],
            [
                'pre_test'     => $validated['pre_test'] !== null ? round((float) $validated['pre_test'], 2) : null,
                'post_test'    => $validated['post_test'] !== null ? round((float) $validated['post_test'], 2) : null,
                'delayed_test' => $validated['delayed_test'] !== null ? round((float) $validated['delayed_test'], 2) : null,
            ]
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Nilai Pre/Post-Test berhasil disimpan.',
                'data'    => [
                    'id'           => $scoreRecord->id,
                    'user_id'      => $scoreRecord->user_id,
                    'cer_map_id'   => $scoreRecord->cer_map_id,
                    'pre_test'     => $scoreRecord->pre_test,
                    'post_test'    => $scoreRecord->post_test,
                    'delayed_test' => $scoreRecord->delayed_test,
                    'score_diff'   => $scoreRecord->score_diff,
                ],
            ]);
        }

        return redirect()->route('admin.clustering', ['map_id' => $validated['cer_map_id']])
            ->with('status', 'Nilai Pre/Post-Test berhasil diperbarui.');
    }

    /**
     * Import Pre-Test and Post-Test scores via CSV.
     */
    public function importTestScores(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cer_map_id' => ['required', 'integer', 'exists:cer_maps,id'],
            'csv_file'   => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $mapId = (int) $validated['cer_map_id'];
        $path = $request->file('csv_file')->getRealPath();
        $file = fopen($path, 'r');

        // Check and skip UTF-8 BOM
        $bom = fread($file, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($file);
        }

        $header = fgetcsv($file, 1000, ',');
        if (!$header) {
            fclose($file);
            return back()->with('error', 'File CSV kosong atau format tidak valid.');
        }

        // Normalize header row to lowercase
        $header = array_map(fn ($h) => strtolower(trim($h)), $header);
        $usernameIdx = array_search('username', $header);
        if ($usernameIdx === false) {
            $usernameIdx = array_search('user_id', $header);
        }
        $preIdx = array_search('pre_test', $header);
        $postIdx = array_search('post_test', $header);
        $delayedIdx = array_search('delayed_test', $header);

        if ($usernameIdx === false) {
            fclose($file);
            return back()->with('error', 'File CSV harus memiliki kolom header "username" atau "user_id".');
        }

        $imported = 0;
        while (($row = fgetcsv($file, 1000, ',')) !== false) {
            if (empty($row) || count($row) <= $usernameIdx) continue;

            $userIdent = trim($row[$usernameIdx]);
            if (empty($userIdent)) continue;

            $user = is_numeric($userIdent)
                ? User::find((int) $userIdent)
                : User::where('username', $userIdent)->first();

            if (!$user) continue;

            $preVal = ($preIdx !== false && isset($row[$preIdx]) && trim($row[$preIdx]) !== '') ? (float) trim($row[$preIdx]) : null;
            $postVal = ($postIdx !== false && isset($row[$postIdx]) && trim($row[$postIdx]) !== '') ? (float) trim($row[$postIdx]) : null;
            $delayedVal = ($delayedIdx !== false && isset($row[$delayedIdx]) && trim($row[$delayedIdx]) !== '') ? (float) trim($row[$delayedIdx]) : null;

            StudentTestScore::updateOrCreate(
                [
                    'user_id'    => $user->id,
                    'cer_map_id' => $mapId,
                ],
                [
                    'pre_test'     => $preVal,
                    'post_test'    => $postVal,
                    'delayed_test' => $delayedVal,
                ]
            );

            $imported++;
        }

        fclose($file);

        return redirect()->route('admin.clustering', ['map_id' => $mapId])
            ->with('status', "Berhasil mengimpor nilai untuk {$imported} mahasiswa.");
    }

    /**
     * Download sample CSV template for Pre/Post test scores.
     */
    public function downloadTemplate(Request $request): StreamedResponse
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_nilai_pre_post_test.csv"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['username', 'pre_test', 'post_test', 'delayed_test']);
            fputcsv($handle, ['mhs_andi', '65.00', '85.00', '80.00']);
            fputcsv($handle, ['mhs_budi', '70.00', '90.00', '85.00']);
            fputcsv($handle, ['mhs_citra', '55.00', '75.00', '70.00']);
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}

