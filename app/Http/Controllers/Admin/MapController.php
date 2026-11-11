<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CerMap;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MapController extends Controller
{
    /**
     * Display the CER Maps Content Hub.
     */
    public function index(Request $request): View
    {
        $maps = CerMap::withCount('triplets')->orderBy('id', 'desc')->get();

        $stats = [
            'total'    => $maps->count(),
            'active'   => $maps->where('allow_feedback', true)->count(),
            'triplets' => $maps->sum('triplets_count'),
        ];

        return view('admin.maps', compact('maps', 'stats'));
    }
}
