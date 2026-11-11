<?php

namespace App\Services;

use App\Models\CerMap;
use App\Models\Score;
use App\Models\UserSession;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScoringService
{
    /**
     * Extract triplet ID from card ID string (e.g. 'c-24' -> 24, 'e-24' -> 24).
     */
    public function getCardTripletId(string $cardId): ?int
    {
        $parts = explode('-', $cardId);
        if (count($parts) >= 2 && is_numeric($parts[1])) {
            return (int) $parts[1];
        }

        return null;
    }

    /**
     * Calculate CER score based on connections and valid triplets in the map.
     * Formula: (correct_connections / (triplet_count * 2)) * 100
     *
     * @param  CerMap  $map
     * @param  array  $connections  List of [from => ..., to => ...]
     * @return float
     */
    public function calculateScore(CerMap $map, array $connections): float
    {
        $triplets = $map->triplets;
        $tripletCount = $triplets->count();

        if ($tripletCount === 0 || empty($connections)) {
            return 0.0;
        }

        $validTripletIds = $triplets->pluck('id')->map(fn ($id) => (int) $id)->toArray();
        $totalShouldBe = $tripletCount * 2;
        $correct = 0;

        foreach ($connections as $conn) {
            $fromId = isset($conn['from']) ? $this->getCardTripletId((string) $conn['from']) : null;
            $toId = isset($conn['to']) ? $this->getCardTripletId((string) $conn['to']) : null;

            // Connection is correct if both endpoints point to the same triplet ID
            // and that triplet actually belongs to this CER Map.
            if ($fromId !== null && $toId !== null && $fromId === $toId && in_array($fromId, $validTripletIds, true)) {
                $correct++;
            }
        }

        $rawScore = ($correct / $totalShouldBe) * 100;

        // Clamp between 0.00 and 100.00
        return round(min(100.0, max(0.0, $rawScore)), 2);
    }

    /**
     * Submit and record student score and finalize user session within a DB transaction.
     *
     * @param  int  $userId
     * @param  int  $sessionId
     * @param  int  $mapId
     * @param  float|null  $clientScore
     * @param  mixed  $mapData
     * @return Score
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function submitScore(int $userId, int $sessionId, int $mapId, ?float $clientScore, mixed $mapData): Score
    {
        return DB::transaction(function () use ($userId, $sessionId, $mapId, $clientScore, $mapData) {
            $session = UserSession::find($sessionId);

            if (! $session) {
                throw ValidationException::withMessages([
                    'session_id' => 'Sesi belajar tidak ditemukan.',
                ]);
            }

            // Verify session belongs to authenticated user
            if ($session->user_id !== $userId) {
                throw new AuthorizationException('Anda tidak berhak mengumpulkan nilai untuk sesi pengguna lain.');
            }

            // Verify session belongs to the designated map
            if ((int) $session->map_id !== $mapId) {
                throw ValidationException::withMessages([
                    'map_id' => 'ID Map tidak cocok dengan sesi belajar yang aktif.',
                ]);
            }

            // Verify session is not already submitted
            if ($session->is_submitted) {
                throw ValidationException::withMessages([
                    'session_id' => 'Sesi belajar ini sudah pernah dikumpulkan sebelumnya.',
                ]);
            }

            $map = CerMap::with('triplets')->find($mapId);
            if (! $map) {
                throw ValidationException::withMessages([
                    'map_id' => 'Materi map tidak ditemukan.',
                ]);
            }

            // Normalize map_data
            $parsedMapData = [];
            if (is_string($mapData)) {
                $decoded = json_decode($mapData, true);
                $parsedMapData = (json_last_error() === JSON_ERROR_NONE) ? $decoded : ['raw' => $mapData];
            } elseif (is_array($mapData)) {
                $parsedMapData = $mapData;
            }

            // Determine final score:
            // If connections are present in map_data, calculate verified server-side score.
            // If not, fall back to validated client score or 0.00.
            if (! empty($parsedMapData['connections']) && is_array($parsedMapData['connections'])) {
                $finalScore = $this->calculateScore($map, $parsedMapData['connections']);
            } elseif ($clientScore !== null) {
                $finalScore = round(min(100.0, max(0.0, (float) $clientScore)), 2);
            } else {
                $finalScore = 0.0;
            }

            // Create Score entry
            $score = Score::create([
                'user_id'      => $userId,
                'map_id'       => $mapId,
                'session_id'   => $sessionId,
                'score'        => $finalScore,
                'map_data'     => $parsedMapData,
                'submitted_at' => now(),
            ]);

            // Finalize session
            $session->update(['is_submitted' => true]);

            return $score;
        });
    }
}
