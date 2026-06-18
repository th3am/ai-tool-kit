<?php

use App\Http\Controllers\MobileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile App Web Routes
|--------------------------------------------------------------------------
| All routes render Blade views. Auth is client-side (Alpine + Bearer token).
| The MobileController simply returns views with shared config.
*/

// Root redirect
Route::get('/', function () {
    return redirect('/dashboard');
});

// Auth pages (no server-side auth guard – client handles it)
Route::get('/login', [MobileController::class, 'login'])->name('login');
Route::get('/register', [MobileController::class, 'register'])->name('register');
Route::get('/otp', [MobileController::class, 'otp'])->name('otp');

// Main pages
Route::get('/dashboard', [MobileController::class, 'dashboard'])->name('dashboard');

// Tools
Route::get('/tools', [MobileController::class, 'tools'])->name('tools');
Route::get('/tools/quiz', [MobileController::class, 'toolQuiz'])->name('tools.quiz');
Route::get('/tools/presentation', [MobileController::class, 'toolPresentation'])->name('tools.presentation');
Route::get('/tools/mindmap', [MobileController::class, 'toolMindmap'])->name('tools.mindmap');
Route::get('/tools/audio', [MobileController::class, 'toolAudio'])->name('tools.audio');
Route::get('/tools/animation', [MobileController::class, 'toolAnimation'])->name('tools.animation');
Route::get('/tools/video-explainer', [MobileController::class, 'toolVideoExplainer'])->name('tools.video-explainer');

// Jobs
Route::get('/jobs', [MobileController::class, 'jobs'])->name('jobs');
Route::get('/jobs/{id}', [MobileController::class, 'jobShow'])->name('jobs.show');

// Sessions / Chat History
Route::get('/sessions', [MobileController::class, 'sessions'])->name('sessions');
Route::get('/sessions/{id}', [MobileController::class, 'sessionShow'])->name('sessions.show');

// Quiz Player
Route::get('/quiz/{id}/play', [MobileController::class, 'quizPlay'])->name('quiz.play');

// Profile
Route::get('/profile', [MobileController::class, 'profile'])->name('profile');
