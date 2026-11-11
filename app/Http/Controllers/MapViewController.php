<?php

namespace App\Http\Controllers;

use App\Models\Score;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MapViewController extends Controller
{
    /**
     * Display a submitted student map.
     * Guru can view any student submission.
     * Siswa can only view their own submission.
     */
    public function show(Score $score): View
    {
        $user = auth()->user();

        if ($user->role === 'siswa' && $score->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke map ini.');
        }

        $score->load(['user', 'cerMap']);

        $map_data = $score->map_data;
        if (is_string($map_data)) {
            $map_data = json_decode($map_data, true);
        }

        if (empty($map_data)) {
            abort(404, 'Data map tidak ditemukan atau tidak valid.');
        }

        return view('shared.view-map', compact('score', 'map_data'));
    }
}
