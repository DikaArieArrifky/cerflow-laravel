<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CerMap;
use App\Models\Score;
use App\Models\User;
use App\Models\UserLog;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the main academic admin dashboard.
     * Delegates to StudioController when edit_id query parameter is present.
     */
    public function index(Request $request): View
    {
        if ($request->has('edit_id')) {
            return app(StudioController::class)->index($request);
        }

        $stats = [
            'total_maps'        => CerMap::count(),
            'total_students'    => User::where('role', 'siswa')->count(),
            'total_sessions'    => UserSession::count(),
            'total_submissions' => UserSession::where('is_submitted', true)->count(),
        ];

        $recentActivities = UserLog::with(['session.user', 'session.cerMap'])
            ->orderBy('id', 'desc')
            ->take(6)
            ->get();

        $recentSubmissions = Score::with(['user', 'cerMap'])
            ->orderBy('submitted_at', 'desc')
            ->take(6)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentActivities', 'recentSubmissions'));
    }
}
