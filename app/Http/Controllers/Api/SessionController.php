<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StartSessionRequest;
use App\Models\UserSession;
use Illuminate\Http\JsonResponse;

class SessionController extends Controller
{
    /**
     * POST /api/sessions
     * Start a new learning session for the authenticated student (Role: siswa).
     */
    public function store(StartSessionRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $mapId = (int) $request->validated()['map_id'];

        $session = UserSession::create([
            'user_id'      => $userId,
            'map_id'       => $mapId,
            'is_submitted' => false,
        ]);

        return response()->json([
            'success'    => true,
            'status'     => 'success', // Preserved for webCerflow siswa.js compatibility
            'session_id' => $session->id, // Direct top-level access for siswa.js
            'data'       => [
                'session_id' => $session->id,
                'map_id'     => $session->map_id,
                'start_time' => $session->start_time ?? now()->toIso8601String(),
            ],
        ], 201);
    }
}
