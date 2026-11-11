<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CerMap;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudioController extends Controller
{
    /**
     * Display the CER Map Studio.
     */
    public function index(Request $request): View
    {
        $initialEditMapId = (int) $request->query('edit_id', 0);
        $initialEditMap = null;

        if ($initialEditMapId > 0) {
            $initialEditMap = CerMap::with('triplets')->find($initialEditMapId);
        }

        return view('admin.studio', compact('initialEditMapId', 'initialEditMap'));
    }
}
