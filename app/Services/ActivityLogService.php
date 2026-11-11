<?php

namespace App\Services;

use App\Models\UserLog;
use App\Models\UserSession;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class ActivityLogService
{
    /**
     * Allowed action types supported in Phase 3A (matching webCerflow existing actions).
     */
    public const ALLOWED_ACTIONS = [
        'connect',
        'disconnect',
        'move',
        'auto_arrange',
        'feedback',
        'view_reading',
        'reading_close',
        'open_material',
        'close_material',
    ];

    /**
     * Record a student activity log within a valid session.
     *
     * @param  int  $userId
     * @param  int  $sessionId
     * @param  string  $actionType
     * @param  mixed  $actionData
     * @return UserLog
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function log(int $userId, int $sessionId, string $actionType, mixed $actionData = null): UserLog
    {
        $session = UserSession::find($sessionId);

        if (! $session) {
            throw ValidationException::withMessages([
                'session_id' => 'Sesi belajar tidak ditemukan.',
            ]);
        }

        // Strict ownership check: session must belong to the authenticated user
        if ($session->user_id !== $userId) {
            throw new AuthorizationException('Anda tidak memiliki izin untuk merekam log pada sesi pengguna lain.');
        }

        // Cannot add logs to already submitted sessions
        if ($session->is_submitted) {
            throw ValidationException::withMessages([
                'session_id' => 'Sesi belajar ini sudah dikumpulkan dan ditutup.',
            ]);
        }

        // Validate action type
        if (! in_array($actionType, self::ALLOWED_ACTIONS, true)) {
            throw ValidationException::withMessages([
                'action_type' => "Tipe aksi '{$actionType}' tidak valid. Pilihan: " . implode(', ', self::ALLOWED_ACTIONS),
            ]);
        }

        // Normalize action data (array or decoded JSON)
        if (is_string($actionData)) {
            $decoded = json_decode($actionData, true);
            $parsedData = (json_last_error() === JSON_ERROR_NONE) ? $decoded : ['raw' => $actionData];
        } elseif (is_array($actionData)) {
            $parsedData = $actionData;
        } else {
            $parsedData = $actionData ? (array) $actionData : [];
        }

        // Research Logging Rule: move event only recorded if from != to
        if ($actionType === 'move') {
            if (isset($parsedData['from'], $parsedData['to']) && $parsedData['from'] == $parsedData['to']) {
                throw ValidationException::withMessages([
                    'action_data' => 'Event move tidak dicatat karena posisi kartu tidak berpindah (from == to).',
                ]);
            }

            // Infer card_type if missing
            if (empty($parsedData['card_type']) && ! empty($parsedData['card'])) {
                $cardId = (string) $parsedData['card'];
                if (str_starts_with($cardId, 'c-')) {
                    $parsedData['card_type'] = 'claim';
                } elseif (str_starts_with($cardId, 'e-')) {
                    $parsedData['card_type'] = 'evidence';
                } elseif (str_starts_with($cardId, 'r-')) {
                    $parsedData['card_type'] = 'reasoning';
                }
            }
        }

        return UserLog::create([
            'session_id'  => $session->id,
            'action_type' => $actionType,
            'action_data' => $parsedData,
        ]);
    }
}
