<?php

use App\Http\Controllers\AuthController;
use App\Models\CerMap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - CERFlow Modular v2.0 (Laravel 11)
|--------------------------------------------------------------------------
*/

// Root URL redirect
Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->isGuru()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('student.index');
    }

    return redirect()->route('login');
});

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// Authenticated Logout Route
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Guru / Admin Routes (Role: guru)
Route::middleware(['auth', 'role:guru'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.dashboard');
    });

    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/studio', [\App\Http\Controllers\Admin\StudioController::class, 'index'])->name('studio');

    // Settings
    Route::get('/settings', function () {
        return view('admin.settings');
    })->name('settings');

    Route::post('/settings', function (Request $request) {
        $validated = $request->validate([
            'gemini_api_key' => ['nullable', 'string', 'max:255'],
        ]);

        $user = auth()->user();
        $user->gemini_api_key = $validated['gemini_api_key'] ?? null;
        $user->save();

        return back()->with('status', 'Pengaturan berhasil diperbarui.');
    })->name('settings.update');

    // CER Map Content Hub & Preview (Phase 3C)
    Route::get('/maps', [\App\Http\Controllers\Admin\MapController::class, 'index'])->name('maps');
    Route::get('/preview/{map}', [\App\Http\Controllers\Admin\PreviewController::class, 'show'])->name('preview');

    // User Management (Phase 3B)
    Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users');
    Route::post('/users', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('users.store');
    Route::delete('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/users/template', [\App\Http\Controllers\Admin\UserController::class, 'downloadTemplate'])->name('users.template');
    Route::post('/users/import', [\App\Http\Controllers\Admin\UserController::class, 'import'])->name('users.import');

    Route::get('/logs', [\App\Http\Controllers\Admin\LogController::class, 'index'])->name('logs');
    Route::get('/logs/{session}', [\App\Http\Controllers\Admin\LogController::class, 'show'])->name('logs.show');

    Route::get('/report', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('report');

    // K-Means Clustering & Pre/Post Test Management
    Route::get('/clustering', [\App\Http\Controllers\Admin\ClusteringController::class, 'index'])->name('clustering');
    Route::get('/clustering/export', [\App\Http\Controllers\Admin\ClusteringController::class, 'exportCsv'])->name('clustering.export');
    Route::post('/clustering/test-scores', [\App\Http\Controllers\Admin\ClusteringController::class, 'updateTestScore'])->name('clustering.test-scores.update');
    Route::post('/clustering/test-scores/import', [\App\Http\Controllers\Admin\ClusteringController::class, 'importTestScores'])->name('clustering.test-scores.import');
    Route::get('/clustering/test-scores/template', [\App\Http\Controllers\Admin\ClusteringController::class, 'downloadTemplate'])->name('clustering.test-scores.template');
});

// Shared Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::get('/view-map/{score}', [\App\Http\Controllers\MapViewController::class, 'show'])->name('map.view');
});

// Siswa / Student Routes (Role: siswa)
Route::middleware(['auth', 'role:siswa'])->prefix('student')->name('student.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Student\LearningController::class, 'index'])->name('index');
    Route::get('/history', [\App\Http\Controllers\Student\HistoryController::class, 'index'])->name('history');
});

/*
|--------------------------------------------------------------------------
| Core REST-like API Routes (Phase 3A)
| Protected by web session authentication and CSRF protection
|--------------------------------------------------------------------------
*/
Route::prefix('api')->name('api.')->middleware('auth')->group(function () {
    // Maps list and triplets (authenticated users)
    Route::get('/maps', [App\Http\Controllers\Api\MapController::class, 'index'])->name('maps.index');
    Route::get('/maps/{map}/triplets', [App\Http\Controllers\Api\MapController::class, 'triplets'])->name('maps.triplets');

    // Guru-only CER Map management & AI
    Route::middleware('role:guru')->group(function () {
        Route::post('/maps', [App\Http\Controllers\Api\MapController::class, 'store'])->name('maps.store');
        Route::get('/maps/{map}', [App\Http\Controllers\Api\MapController::class, 'show'])->name('maps.show');
        Route::delete('/maps/{map}', [App\Http\Controllers\Api\MapController::class, 'destroy'])->name('maps.destroy');
        Route::post('/ai/extract', [\App\Http\Controllers\Api\AIController::class, 'extract'])->name('ai.extract');
        Route::post('/ai/test', [\App\Http\Controllers\Api\AIController::class, 'test'])->name('ai.test');
    });

    // Siswa-only learning actions
    Route::middleware('role:siswa')->group(function () {
        Route::post('/sessions', [App\Http\Controllers\Api\SessionController::class, 'store'])->name('sessions.store');
        Route::post('/logs', [App\Http\Controllers\Api\LogController::class, 'store'])->name('logs.store');
        Route::post('/scores', [App\Http\Controllers\Api\ScoreController::class, 'store'])->name('scores.store');
    });
});

