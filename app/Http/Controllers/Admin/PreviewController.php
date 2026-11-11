<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CerMap;
use Illuminate\View\View;

class PreviewController extends Controller
{
    /**
     * Display answer-key preview for a CER map.
     */
    public function show(CerMap $map): View
    {
        $map->load('triplets');

        $triplets = $map->triplets;

        // Generate synthetic "perfect" connections for preview
        $connections = [];
        foreach ($triplets as $t) {
            $tripletId = (int) $t->id;
            $connections[] = ['from' => "c-{$tripletId}", 'to' => "e-{$tripletId}"];
            $connections[] = ['from' => "e-{$tripletId}", 'to' => "r-{$tripletId}"];
        }

        $map_data = [
            'triplets'    => $triplets,
            'connections' => $connections,
            'colOrder'    => null,
        ];

        return view('admin.preview-map', compact('map', 'map_data'));
    }
}
