<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Score;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    /**
     * Display student history page.
     * Siswa only sees their own scores.
     */
    public function index(Request $request): View
    {
        $userId = $request->user()->id;

        $scores = Score::with('cerMap')
            ->where('user_id', $userId)
            ->orderBy('submitted_at', 'desc')
            ->get();

        return view('student.history', compact('scores'));
    }
}
