<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CerMap;
use App\Models\Score;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LearningController extends Controller
{
    /**
     * Display the student learning workspace / module selector.
     */
    public function index(Request $request): View
    {
        $userId = $request->user()->id;
        $maps = CerMap::with('triplets')->get();

        $userScores = Score::where('user_id', $userId)
            ->latest('submitted_at')
            ->get()
            ->keyBy('map_id');

        return view('student.learning', compact('maps', 'userScores'));
    }
}
