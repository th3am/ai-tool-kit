<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VideoController;
// use App\Http\Controllers\FolderController;
use App\Http\Controllers\PresentationController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\PresentationStreamController;
use App\Models\Presentation;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Redirects
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', function () {
    return redirect()->route('login');
});

Route::get('/register', function () {
    return redirect()->route('register.view');
});

// Auth Routes Group
Route::prefix('auth')->middleware('guest')->group(function () {
    // Views - Legacy Ajax Auth
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register.view');

    // Actions (POST)
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('otp.verify.post');
    Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->name('otp.resend');
    Route::post('/check-number', [AuthController::class, 'checkWhatsApp'])->name('whatsapp.check');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::middleware(['web'])->group(function () {
    Route::get('/auth/otp-verify', [AuthController::class, 'showOtpForm'])->name('otp.verify');
});

// Public Quiz Routes (no auth required - for friends)
Route::get('/quiz/public/{uuid}', [App\Http\Controllers\PublicQuizController::class, 'show'])->name('quiz.public.show');
Route::post('/quiz/public/{uuid}/attempt', [App\Http\Controllers\PublicQuizController::class, 'attempt'])->name('quiz.public.attempt');

// Protected Application Routes
Route::middleware(['auth', 'otp.verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', \App\Livewire\Dashboard::class)->name('dashboard');
    Route::get('/settings', \App\Livewire\Settings::class)->name('settings');
    Route::get('/profile', \App\Livewire\Profile::class)->name('profile');

    // Chat Session
    Route::get('/chat/{sessionId}', \App\Livewire\ChatSession::class)->name('chat.session');

    // Quiz Routes
    Route::get('/quiz', [App\Http\Controllers\QuizWebController::class, 'index'])->name('quiz.index');
    Route::get('/quiz/create', [App\Http\Controllers\QuizWebController::class, 'create'])->name('quiz.create');
    Route::post('/quiz', [App\Http\Controllers\QuizWebController::class, 'store'])->name('quiz.store');
    Route::get('/quiz/{quiz}', [App\Http\Controllers\QuizWebController::class, 'show'])->name('quiz.show');
    Route::get('/quiz/{quiz}/status', [App\Http\Controllers\QuizWebController::class, 'statusCheck'])->name('quiz.status');
    Route::post('/quiz/{quiz}/toggle-share', [App\Http\Controllers\QuizWebController::class, 'toggleShare'])->name('quiz.toggle-share');
    Route::delete('/quiz/{quiz}', [App\Http\Controllers\QuizWebController::class, 'destroy'])->name('quiz.destroy');

    // Presentation Routes
    Route::get('/presentation/{presentation}/download', [DownloadController::class, 'downloadPresentation'])->name('presentation.download');
    Route::get('/presentation/{presentation}/download-ppt', [DownloadController::class, 'downloadPowerPoint'])->name('presentation.download.ppt');
    
    // Mind Map Routes
    Route::get('/mindmap/{job}/download-png', [DownloadController::class, 'downloadMindMapPng'])->name('mindmap.download.png');
    
    // Animation Routes
    Route::get('/animation/{job}/download/{format}', [DownloadController::class, 'downloadAnimation'])->name('animation.download');

    // Video Explainer Routes
    Route::get('/video-explainer/{job}/download', [DownloadController::class, 'downloadVideoExplainer'])->name('video.explainer.download');

    // Video/Course Routes
    // Route::get('/courses/{folder}', [FolderController::class, 'show'])->name('folder.show');
    
    // Protected Media Routes
    Route::get('/videos/{filename}', [VideoController::class, 'streamVideo'])->name('video.stream');
    Route::get('/videos/{folder}/{filename}', [VideoController::class, 'streamSubfolderVideo'])->name('video.subfolder.stream');
    Route::get('/hls/{folder}/{filename}', [VideoController::class, 'streamHls'])->name('video.hls');
    Route::post('/videos/key', [VideoController::class, 'getKey'])->name('video.key');
});

// Fallback for asset routes if needed
Route::get('/storage/{path}', function ($path) {
    return response()->file(storage_path('app/public/' . $path));
})->where('path', '.*');

// ──────────────────────────────────────────────────────────────────────────────
// Admin Panel Routes
// ──────────────────────────────────────────────────────────────────────────────
Route::prefix('admin')
    ->middleware(['auth', 'admin'])
    ->name('admin.')
    ->group(function () {

        // Dashboard
        Route::get('/', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'index'])->name('dashboard');
        Route::post('/ai-settings', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'updateAiSettings'])->name('ai-settings.update');

        // Users
        Route::get('/users', [\App\Http\Controllers\Admin\AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [\App\Http\Controllers\Admin\AdminUserController::class, 'show'])->name('users.show');
        Route::put('/users/{user}', [\App\Http\Controllers\Admin\AdminUserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [\App\Http\Controllers\Admin\AdminUserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{user}/add-credits', [\App\Http\Controllers\Admin\AdminUserController::class, 'addCredits'])->name('users.add-credits');

        // Subscription Plans
        Route::get('/plans', [\App\Http\Controllers\Admin\AdminPlanController::class, 'index'])->name('plans.index');
        Route::get('/plans/create', [\App\Http\Controllers\Admin\AdminPlanController::class, 'create'])->name('plans.create');
        Route::post('/plans', [\App\Http\Controllers\Admin\AdminPlanController::class, 'store'])->name('plans.store');
        Route::get('/plans/{plan}/edit', [\App\Http\Controllers\Admin\AdminPlanController::class, 'edit'])->name('plans.edit');
        Route::put('/plans/{plan}', [\App\Http\Controllers\Admin\AdminPlanController::class, 'update'])->name('plans.update');
        Route::delete('/plans/{plan}', [\App\Http\Controllers\Admin\AdminPlanController::class, 'destroy'])->name('plans.destroy');

        // Tool Jobs Log
        Route::get('/jobs', [\App\Http\Controllers\Admin\AdminJobController::class, 'index'])->name('jobs.index');
    });
