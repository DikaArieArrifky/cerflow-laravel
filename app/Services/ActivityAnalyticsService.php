<?php

namespace App\Services;

use App\Models\UserSession;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ActivityAnalyticsService
{
    /**
     * Compute comprehensive, non-destructive research analytics for a single learning session.
     *
     * @param  UserSession  $session
     * @return array
     */
    public function calculateSessionAnalytics(UserSession $session): array
    {
        // Ensure relationships are loaded
        if (! $session->relationLoaded('logs')) {
            $session->load(['logs' => fn ($q) => $q->orderBy('created_at', 'asc')->orderBy('id', 'asc')]);
        }
        if (! $session->relationLoaded('score')) {
            $session->load('score');
        }
        if (! $session->relationLoaded('user')) {
            $session->load('user');
        }
        if (! $session->relationLoaded('cerMap')) {
            $session->load('cerMap');
        }

        $logs = $session->logs;

        $totalEvents = $logs->count();
        $connectCount = 0;
        $disconnectCount = 0;
        $autoArrangeCount = 0;
        $feedbackCount = 0;
        $readingCount = 0;
        $readingCloseCount = 0;

        $moveCount = 0;
        $totalMoveDistance = 0;
        $movedCards = [];
        $claimMoveCount = 0;
        $evidenceMoveCount = 0;
        $reasoningMoveCount = 0;
        $movements = [];

        // Reading duration calculation variables
        $readingDurationSeconds = 0;
        $hasIncompleteReading = false;
        $currentReadingOpenTime = null;

        $timeline = [];

        foreach ($logs as $log) {
            $type = $log->action_type;
            $data = is_array($log->action_data) ? $log->action_data : (array) $log->action_data;
            $timestamp = $log->created_at ?? now();

            switch ($type) {
                case 'connect':
                    $connectCount++;
                    $from = $data['from'] ?? '?';
                    $to = $data['to'] ?? '?';
                    $timeline[] = [
                        'timestamp'   => $timestamp,
                        'action_type' => 'CONNECT',
                        'detail'      => "{$from} → {$to}",
                    ];
                    break;

                case 'disconnect':
                    $disconnectCount++;
                    $from = $data['from'] ?? '?';
                    $to = $data['to'] ?? '?';
                    $timeline[] = [
                        'timestamp'   => $timestamp,
                        'action_type' => 'DISCONNECT',
                        'detail'      => "{$from} → {$to}",
                    ];
                    break;

                case 'auto_arrange':
                    $autoArrangeCount++;
                    $timeline[] = [
                        'timestamp'   => $timestamp,
                        'action_type' => 'AUTO_ARRANGE',
                        'detail'      => 'Penataan otomatis posisi kartu',
                    ];
                    break;

                case 'feedback':
                    $feedbackCount++;
                    $active = ! empty($data['active']) ? 'Aktif' : 'Nonaktif';
                    $timeline[] = [
                        'timestamp'   => $timestamp,
                        'action_type' => 'FEEDBACK',
                        'detail'      => "Visual feedback: {$active}",
                    ];
                    break;

                case 'view_reading':
                case 'open_material':
                    $readingCount++;
                    $currentReadingOpenTime = $timestamp;
                    $timeline[] = [
                        'timestamp'   => $timestamp,
                        'action_type' => strtoupper($type),
                        'detail'      => 'Membuka dialog materi bacaan',
                    ];
                    break;

                case 'reading_close':
                case 'close_material':
                    $readingCloseCount++;
                    $duration = isset($data['reading_duration_seconds']) ? (int) $data['reading_duration_seconds'] : null;
                    if ($duration === null && $currentReadingOpenTime !== null) {
                        $duration = abs((int) Carbon::parse($timestamp)->diffInSeconds(Carbon::parse($currentReadingOpenTime)));
                    }
                    if ($duration !== null) {
                        $readingDurationSeconds += $duration;
                        $currentReadingOpenTime = null;
                        $timeline[] = [
                            'timestamp'   => $timestamp,
                            'action_type' => strtoupper($type),
                            'detail'      => "Menutup dialog bacaan (durasi: {$duration}s)",
                        ];
                    } else {
                        $timeline[] = [
                            'timestamp'   => $timestamp,
                            'action_type' => strtoupper($type),
                            'detail'      => 'Menutup dialog bacaan',
                        ];
                    }
                    break;

                case 'move':
                    $from = isset($data['from']) ? (int) $data['from'] : 0;
                    $to = isset($data['to']) ? (int) $data['to'] : 0;
                    $distance = abs($to - $from);

                    $card = $data['card'] ?? '';
                    $cardType = $data['card_type'] ?? $this->inferCardType($card);

                    $moveCount++;
                    $totalMoveDistance += $distance;
                    if (! empty($card)) {
                        $movedCards[$card] = true;
                    }

                    if ($cardType === 'claim') {
                        $claimMoveCount++;
                    } elseif ($cardType === 'evidence') {
                        $evidenceMoveCount++;
                    } elseif ($cardType === 'reasoning') {
                        $reasoningMoveCount++;
                    }

                    $movements[] = [
                        'timestamp' => $timestamp,
                        'card'      => $card,
                        'card_type' => $cardType,
                        'from'      => $from,
                        'to'        => $to,
                        'distance'  => $distance,
                    ];

                    $timeline[] = [
                        'timestamp'   => $timestamp,
                        'action_type' => 'MOVE',
                        'detail'      => "{$card} ({$cardType}) {$from} → {$to} (jarak: {$distance})",
                    ];
                    break;

                default:
                    $timeline[] = [
                        'timestamp'   => $timestamp,
                        'action_type' => strtoupper($type),
                        'detail'      => json_encode($data),
                    ];
                    break;
            }
        }

        // If view_reading was opened without reading_close before the end of logs:
        if ($currentReadingOpenTime !== null) {
            $hasIncompleteReading = true;
        }

        // Completion time: scores.submitted_at - user_sessions.start_time
        $completionTimeSeconds = null;
        if ($session->is_submitted && $session->score && $session->start_time) {
            $start = Carbon::parse($session->start_time);
            $submitted = Carbon::parse($session->score->submitted_at);
            $completionTimeSeconds = abs((int) $submitted->diffInSeconds($start));
        }

        $finalScore = $session->score ? (float) $session->score->score : null;

        return [
            'session_id'               => $session->id,
            'student_name'             => $session->user?->namalengkap,
            'username'                 => $session->user?->username,
            'map_title'                => $session->cerMap?->title,
            'start_time'               => $session->start_time,
            'submitted_at'             => $session->score?->submitted_at,
            'is_submitted'             => (bool) $session->is_submitted,
            'score'                    => $finalScore,
            'completion_time_seconds'  => $completionTimeSeconds,
            'total_events'             => $totalEvents,
            'connect_count'            => $connectCount,
            'disconnect_count'         => $disconnectCount,
            'move_count'               => $moveCount,
            'total_move_distance'      => $totalMoveDistance,
            'unique_moved_cards'       => count($movedCards),
            'claim_move_count'         => $claimMoveCount,
            'evidence_move_count'      => $evidenceMoveCount,
            'reasoning_move_count'     => $reasoningMoveCount,
            'auto_arrange_count'       => $autoArrangeCount,
            'feedback_count'           => $feedbackCount,
            'reading_count'            => $readingCount,
            'reading_duration_seconds' => $readingDurationSeconds,
            'has_incomplete_reading'   => $hasIncompleteReading,
            'movements'                => $movements,
            'timeline'                 => $timeline,
        ];
    }

    /**
     * Infer card type from its string identifier prefix.
     */
    public function inferCardType(?string $cardId): string
    {
        if (empty($cardId)) {
            return 'unknown';
        }

        if (str_starts_with($cardId, 'c-')) {
            return 'claim';
        }

        if (str_starts_with($cardId, 'e-')) {
            return 'evidence';
        }

        if (str_starts_with($cardId, 'r-')) {
            return 'reasoning';
        }

        return 'unknown';
    }
}
