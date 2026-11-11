<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreMapRequest;
use App\Models\CerMap;
use App\Services\CerMapService;
use Illuminate\Http\JsonResponse;

class MapController extends Controller
{
    protected CerMapService $mapService;

    public function __construct(CerMapService $mapService)
    {
        $this->mapService = $mapService;
    }

    /**
     * GET /api/maps
     * Retrieve list of all CER Maps.
     */
    public function index(): JsonResponse
    {
        $maps = $this->mapService->getAllMaps();

        return response()->json([
            'success' => true,
            'status'  => 'success',
            'data'    => $maps,
        ]);
    }

    /**
     * POST /api/maps
     * Create or update a CER Map with triplets (Role: guru).
     */
    public function store(StoreMapRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $mapId = $validated['map_id'] ?? null;

        $map = $this->mapService->saveMap($validated, $mapId);

        return response()->json([
            'success' => true,
            'status'  => 'ok', // Preserved for webCerflow admin.js compatibility
            'id'      => $map->id,
            'message' => $mapId ? 'Map berhasil diperbarui.' : 'Map baru berhasil disimpan.',
            'data'    => $map,
        ], $mapId ? 200 : 201);
    }

    /**
     * GET /api/maps/{map}
     * Retrieve a single map with its triplets (Role: guru).
     */
    public function show(CerMap $map): JsonResponse
    {
        $mapWithTriplets = $this->mapService->getMapWithTriplets($map);

        return response()->json([
            'success' => true,
            'status'  => 'success', // Preserved for webCerflow admin.js compatibility
            'data'    => $mapWithTriplets,
        ]);
    }

    /**
     * GET /api/maps/{map}/triplets
     * Retrieve triplets belonging to a specific map.
     */
    public function triplets(CerMap $map): JsonResponse
    {
        return response()->json([
            'success' => true,
            'status'  => 'success',
            'data'    => $map->triplets,
        ]);
    }

    /**
     * DELETE /api/maps/{map}
     * Delete a map and its dependent triplets/scores (Role: guru).
     */
    public function destroy(CerMap $map): JsonResponse
    {
        $this->mapService->deleteMap($map);

        return response()->json([
            'success' => true,
            'status'  => 'success', // Preserved for webCerflow admin.js compatibility
            'message' => 'Map berhasil dihapus.',
        ]);
    }
}
