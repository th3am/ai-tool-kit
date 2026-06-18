<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Swagger / Legacy API Auth Routes
Route::post('/auth/login', [App\Http\Controllers\Api\AuthApiController::class, 'login']);
Route::post('/auth/register', [App\Http\Controllers\Api\AuthApiController::class, 'register']);
Route::middleware('auth:sanctum')->post('/auth/logout', [App\Http\Controllers\Api\AuthApiController::class, 'logout']);

// V1 API Routes (Session/Cookie based)
Route::prefix('v1')->group(function () {
    
    // Auth
    Route::post('/login', [App\Http\Controllers\Api\V1\AuthController::class, 'login']);
    Route::post('/register', [App\Http\Controllers\Api\V1\AuthController::class, 'register']);
    Route::post('/otp/request', [App\Http\Controllers\Api\V1\AuthController::class, 'requestOtp']);
    Route::post('/otp/resend', [App\Http\Controllers\Api\V1\AuthController::class, 'resendOtp']);
    Route::post('/otp/verify', [App\Http\Controllers\Api\V1\AuthController::class, 'verifyOtp']);
    Route::middleware('auth:sanctum')->post('/logout', [App\Http\Controllers\Api\V1\AuthController::class, 'logout']);

    Route::match(['get', 'post'], '/whatsapp/metaphilia/webhook', [App\Http\Controllers\Api\V1\WhatsappWebhookController::class, 'handle'])
        ->name('api.whatsapp.metaphilia.webhook');

    // Downloads accept either Authorization: Bearer TOKEN or ?token=TOKEN so NativePHP/WebView can open files directly.
    Route::get('/downloads/presentation/{presentation}/pdf', [App\Http\Controllers\DownloadController::class, 'downloadPresentation'])
        ->name('api.download.presentation.pdf');
    Route::get('/downloads/presentation/{presentation}/ppt', [App\Http\Controllers\DownloadController::class, 'downloadPowerPoint'])
        ->name('api.download.presentation.ppt');
    Route::get('/downloads/mindmap/{job}/png', [App\Http\Controllers\DownloadController::class, 'downloadMindMapPng'])
        ->name('api.download.mindmap.png');
    Route::get('/downloads/mindmap/{job}/svg', [App\Http\Controllers\DownloadController::class, 'downloadMindMapSvg'])
        ->name('api.download.mindmap.svg');
    Route::get('/downloads/audio/{job}', [App\Http\Controllers\DownloadController::class, 'downloadAudio'])
        ->name('api.download.audio');
    Route::get('/downloads/animation/{job}/{format}', [App\Http\Controllers\DownloadController::class, 'downloadAnimation'])
        ->whereIn('format', ['svg', 'mp4', 'gif'])
        ->name('api.download.animation');
    Route::get('/downloads/video-explainer/{job}', [App\Http\Controllers\DownloadController::class, 'downloadVideoExplainer'])
        ->name('api.download.video-explainer');

    // Public Quiz Routes (no auth - for friends taking shared quizzes)
    Route::get('/public/quiz/{uuid}', [App\Http\Controllers\Api\V1\PublicQuizApiController::class, 'show']);
    Route::post('/public/quiz/{uuid}/attempt', [App\Http\Controllers\Api\V1\PublicQuizApiController::class, 'attempt']);

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        // User & Settings
        Route::get('/user', [App\Http\Controllers\Api\V1\UserController::class, 'show']);
        Route::match(['put', 'post'], '/user', [App\Http\Controllers\Api\V1\UserController::class, 'update']);
        Route::get('/plans', [App\Http\Controllers\Api\V1\UserController::class, 'plans']);

        // Chat Sessions (Required context for tools)
        Route::get('/sessions', [App\Http\Controllers\Api\V1\ChatSessionController::class, 'index']);
        Route::post('/sessions', [App\Http\Controllers\Api\V1\ChatSessionController::class, 'store']);
        Route::get('/sessions/{session}', [App\Http\Controllers\Api\V1\ChatSessionController::class, 'show']);

        // Quiz (Android API)
        Route::get('/quiz', [App\Http\Controllers\Api\V1\QuizController::class, 'index']);
        Route::post('/quiz', [App\Http\Controllers\Api\V1\QuizController::class, 'store']);
        Route::get('/quiz/{quiz}', [App\Http\Controllers\Api\V1\QuizController::class, 'show']);
        Route::get('/quiz/{quiz}/status', [App\Http\Controllers\Api\V1\QuizController::class, 'status']);
        Route::post('/quiz/{quiz}/toggle-share', [App\Http\Controllers\Api\V1\QuizController::class, 'toggleShare']);
        Route::post('/quiz/{quiz}/attempt', [App\Http\Controllers\Api\V1\QuizController::class, 'attempt']);

        // Tools
        Route::post('/tools/presentation', [App\Http\Controllers\Api\V1\PresentationController::class, 'store']);
        Route::post('/tools/mindmap', [App\Http\Controllers\Api\V1\MindMapController::class, 'store']);
        Route::post('/tools/animation', [App\Http\Controllers\Api\V1\AnimationController::class, 'store']);
        Route::post('/tools/audio', [App\Http\Controllers\Api\V1\AudioController::class, 'store']);
        Route::post('/tools/video-explainer', [App\Http\Controllers\Api\V1\VideoExplainerController::class, 'store']);

        // Job Status
        Route::get('/jobs', [App\Http\Controllers\Api\V1\ToolJobController::class, 'index']);
        Route::get('/jobs/{id}', [App\Http\Controllers\Api\V1\ToolJobController::class, 'show']);

    });

    /*
    |--------------------------------------------------------------------------
    | Guest Public API â€” No Authentication Required
    |--------------------------------------------------------------------------
    |
    | These routes allow unauthenticated users to try all AI tools with
    | strict per-IP daily rate limits. Register for unlimited access.
    |
    */
    Route::prefix('guest')->name('guest.')->group(function () {

        // Quiz â€” 3/day per IP, max 3 questions
        Route::post('/tools/quiz',
            [App\Http\Controllers\Api\V1\Guest\GuestQuizController::class, 'store']
        )->middleware('throttle:guest-quiz')->name('tools.quiz');

        // Presentation â€” 2/day per IP, max 3 slides, async
        Route::post('/tools/presentation',
            [App\Http\Controllers\Api\V1\Guest\GuestPresentationController::class, 'store']
        )->middleware('throttle:guest-presentation')->name('tools.presentation');

        // Mind Map â€” 3/day per IP, synchronous
        Route::post('/tools/mindmap',
            [App\Http\Controllers\Api\V1\Guest\GuestMindMapController::class, 'store']
        )->middleware('throttle:guest-mindmap')->name('tools.mindmap');

        // Animation â€” 2/day per IP, synchronous SVG
        Route::post('/tools/animation',
            [App\Http\Controllers\Api\V1\Guest\GuestAnimationController::class, 'store']
        )->middleware('throttle:guest-animation')->name('tools.animation');

        // Audio â€” 2/day per IP, max 500 chars, async
        Route::post('/tools/audio',
            [App\Http\Controllers\Api\V1\Guest\GuestAudioController::class, 'store']
        )->middleware('throttle:guest-audio')->name('tools.audio');

        // Job Status Polling â€” no limit (guests poll their own null-user jobs)
        Route::get('/jobs/{id}',
            [App\Http\Controllers\Api\V1\Guest\GuestJobController::class, 'show']
        )->name('jobs.show');
    });
});
