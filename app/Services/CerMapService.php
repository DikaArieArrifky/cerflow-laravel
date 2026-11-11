<?php

namespace App\Services;

use App\Models\CerMap;
use App\Models\Triplet;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CerMapService
{
    /**
     * Get all maps ordered by ID descending.
     */
    public function getAllMaps(): Collection
    {
        return CerMap::withCount('triplets')->orderBy('id', 'desc')->get();
    }

    /**
     * Get a single map with its triplets.
     */
    public function getMapWithTriplets(int|CerMap $map): ?CerMap
    {
        if (is_int($map)) {
            $map = CerMap::find($map);
        }

        if (! $map) {
            return null;
        }

        $map->load('triplets');
        return $map;
    }

    /**
     * Save or update a CER Map and its triplets within a DB transaction.
     *
     * @param  array  $data
     * @param  int|null  $mapId
     * @return CerMap
     */
    public function saveMap(array $data, ?int $mapId = null): CerMap
    {
        return DB::transaction(function () use ($data, $mapId) {
            $mapAttributes = [
                'title'          => $data['title'],
                'allow_feedback' => isset($data['allow_feedback']) ? (bool) $data['allow_feedback'] : true,
                'allow_reading'  => isset($data['allow_reading']) ? (bool) $data['allow_reading'] : true,
                'reading_text'   => $data['reading_text'] ?? null,
            ];

            if ($mapId) {
                $map = CerMap::findOrFail($mapId);
                $map->update($mapAttributes);
            } else {
                $map = CerMap::create($mapAttributes);
            }

            // Synchronize triplets
            $tripletsData = $data['triplets'] ?? [];
            $existingTripletIds = $map->triplets()->pluck('id')->toArray();
            $keptTripletIds = [];

            foreach ($tripletsData as $tData) {
                $tripletId = ! empty($tData['id']) ? (int) $tData['id'] : null;

                $tripletPayload = [
                    'claim'     => $tData['claim'] ?? '',
                    'evidence'  => $tData['evidence'] ?? '',
                    'reasoning' => $tData['reasoning'] ?? '',
                ];

                if ($tripletId && in_array($tripletId, $existingTripletIds, true)) {
                    // Update existing triplet
                    $map->triplets()->where('id', $tripletId)->update($tripletPayload);
                    $keptTripletIds[] = $tripletId;
                } else {
                    // Create new triplet
                    $newTriplet = $map->triplets()->create($tripletPayload);
                    $keptTripletIds[] = $newTriplet->id;
                }
            }

            // Remove triplets that are no longer in the list (matching webCerflow behavior)
            $toDelete = array_diff($existingTripletIds, $keptTripletIds);
            if (! empty($toDelete)) {
                $map->triplets()->whereIn('id', $toDelete)->delete();
            }

            return $map->fresh(['triplets']);
        });
    }

    /**
     * Delete a map and its dependent triplets/scores.
     */
    public function deleteMap(CerMap|int $map): bool
    {
        return DB::transaction(function () use ($map) {
            if (is_int($map)) {
                $map = CerMap::findOrFail($map);
            }

            return (bool) $map->delete();
        });
    }
}
