<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Score;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Display performance analytics / score reports.
     */
    public function index(Request $request): View
    {
        $reports = Score::with(['user', 'cerMap', 'session'])
            ->orderBy('submitted_at', 'desc')
            ->get();

        $uniqueModules = $reports->pluck('cerMap.title')->filter()->unique()->values();

        return view('admin.report', compact('reports', 'uniqueModules'));
    }
}
