<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CerMap;
use App\Models\UserSession;
use App\Services\ActivityAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogController extends Controller
{
    protected ActivityAnalyticsService $analyticsService;

    public function __construct(ActivityAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Display student interaction log page with research analytics.
     */
    public function index(Request $request): View
    {
        $filters = [
            'materi' => $request->query('materi', ''),
            'siswa'  => $request->query('siswa', ''),
            'start'  => $request->query('start', ''),
            'end'    => $request->query('end', ''),
        ];

        $query = UserSession::with([
            'user',
            'cerMap',
            'score',
            'logs' => fn ($q) => $q->orderBy('created_at', 'asc')->orderBy('id', 'asc'),
        ])->where('is_submitted', true);

        if (! empty($filters['materi'])) {
            $query->whereHas('cerMap', function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['materi'] . '%');
            });
        }

        if (! empty($filters['siswa'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where(function ($sq) use ($filters) {
                    $sq->where('username', 'like', '%' . $filters['siswa'] . '%')
                       ->orWhere('namalengkap', 'like', '%' . $filters['siswa'] . '%');
                });
            });
        }

        if (! empty($filters['start'])) {
            $query->where('start_time', '>=', $filters['start']);
        }

        if (! empty($filters['end'])) {
            $query->where('start_time', '<=', $filters['end']);
        }

        $sessions = $query->orderBy('start_time', 'desc')->get();

        $sessionAnalytics = $sessions->map(function ($session) {
            return [
                'session'   => $session,
                'analytics' => $this->analyticsService->calculateSessionAnalytics($session),
            ];
        });

        $loggedMaps = CerMap::whereHas('sessions', fn ($q) => $q->where('is_submitted', true))
            ->orderBy('title')
            ->get();

        return view('admin.logs', compact('sessionAnalytics', 'loggedMaps', 'filters'));
    }

    /**
     * Display detailed session timeline, movement analysis, and metrics.
     */
    public function show(UserSession $session): View
    {
        $analytics = $this->analyticsService->calculateSessionAnalytics($session);

        return view('admin.log-detail', compact('session', 'analytics'));
    }
}
