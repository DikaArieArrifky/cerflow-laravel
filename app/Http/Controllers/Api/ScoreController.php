<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreScoreRequest;
use App\Services\ScoringService;
use Illuminate\Http\JsonResponse;

class ScoreController extends Controller
{
    protected ScoringService $scoringService;

    public function __construct(ScoringService $scoringService)
    {
        $this->scoringService = $scoringService;
    }

    /**
     * POST /api/scores
     * Submit and record student score and finalize user session (Role: siswa).
     */
    public function store(StoreScoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $request->user()->id;

        $score = $this->scoringService->submitScore(
            userId: $userId,
            sessionId: (int) $validated['session_id'],
            mapId: (int) $validated['map_id'],
            clientScore: isset($validated['score']) ? (float) $validated['score'] : null,
            mapData: $validated['map_data'] ?? null
        );

        return response()->json([
            'success' => true,
            'status'  => 'success', // Preserved for webCerflow siswa.js compatibility
            'message' => 'Nilai berhasil disimpan.',
            'data'    => [
                'score_id'     => $score->id,
                'score'        => $score->score,
                'map_id'       => $score->map_id,
                'session_id'   => $score->session_id,
                'submitted_at' => $score->submitted_at,
            ],
        ], 201);
    }
}
