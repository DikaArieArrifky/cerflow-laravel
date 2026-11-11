<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreActivityLogRequest;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;

class LogController extends Controller
{
    protected ActivityLogService $logService;

    public function __construct(ActivityLogService $logService)
    {
        $this->logService = $logService;
    }

    /**
     * POST /api/logs
     * Store student interaction activity log (Role: siswa).
     */
    public function store(StoreActivityLogRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $request->user()->id;

        $log = $this->logService->log(
            userId: $userId,
            sessionId: (int) $validated['session_id'],
            actionType: (string) $validated['action_type'],
            actionData: $validated['action_data'] ?? null
        );

        return response()->json([
            'success' => true,
            'status'  => 'success', // Preserved for webCerflow siswa.js compatibility
            'message' => 'Aktivitas berhasil dicatat.',
            'data'    => $log,
        ], 201);
    }
}
