<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MobileController extends Controller
{
    /**
     * Shared data passed to every mobile view.
     */
    private function sharedData(): array
    {
        return [
            'apiBaseUrl' => env('API_BASE_URL', 'http://127.0.0.1:8000/api/v1'),
        ];
    }

    // ─── Auth ────────────────────────────────────────────────────────────────

    public function login()
    {
        return view('auth.login', $this->sharedData());
    }

    public function register()
    {
        return view('auth.register', $this->sharedData());
    }

    public function otp()
    {
        return view('auth.otp', $this->sharedData());
    }

    // ─── Dashboard ───────────────────────────────────────────────────────────

    public function dashboard()
    {
        return view('dashboard', $this->sharedData());
    }

    // ─── Tools ───────────────────────────────────────────────────────────────

    public function tools()
    {
        return view('tools.index', $this->sharedData());
    }

    public function toolQuiz()
    {
        return view('tools.quiz', $this->sharedData());
    }

    public function toolPresentation()
    {
        return view('tools.presentation', $this->sharedData());
    }

    public function toolMindmap()
    {
        return view('tools.mindmap', $this->sharedData());
    }

    public function toolAudio()
    {
        return view('tools.audio', $this->sharedData());
    }

    public function toolAnimation()
    {
        return view('tools.animation', $this->sharedData());
    }

    public function toolVideoExplainer()
    {
        return view('tools.video-explainer', $this->sharedData());
    }

    // ─── Jobs ────────────────────────────────────────────────────────────────

    public function jobs()
    {
        return view('jobs.index', $this->sharedData());
    }

    public function jobShow($id)
    {
        return view('jobs.show', array_merge($this->sharedData(), ['jobId' => $id]));
    }

    // ─── Sessions ────────────────────────────────────────────────────────────

    public function sessions()
    {
        return view('sessions.index', $this->sharedData());
    }

    public function sessionShow($id)
    {
        return view('sessions.show', array_merge($this->sharedData(), ['sessionId' => $id]));
    }

    // ─── Quiz Player ─────────────────────────────────────────────────────────

    public function quizPlay($id)
    {
        return view('quiz.play', array_merge($this->sharedData(), ['quizId' => $id]));
    }

    // ─── Profile ─────────────────────────────────────────────────────────────

    public function profile()
    {
        return view('profile', $this->sharedData());
    }
}
