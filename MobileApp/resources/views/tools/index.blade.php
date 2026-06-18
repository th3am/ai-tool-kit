@extends('layouts.mobile', ['title' => 'AI Tools'])

@section('content')

<div class="page-header">
    <h1>AI Tools</h1>
    <span class="badge badge-purple">6 tools</span>
</div>

{{-- Hint card --}}
<div class="mx-4 mt-4 mb-2 px-4 py-3 rounded-2xl flex items-center gap-3"
     style="background:linear-gradient(135deg,rgba(124,58,237,0.12),rgba(168,85,247,0.07));border:1px solid rgba(124,58,237,0.25);">
    <span class="text-xl">✨</span>
    <p class="text-xs text-white/60 leading-relaxed">Pick a tool and let AI generate content in seconds. Results appear in Jobs.</p>
</div>

<div class="grid grid-cols-2 gap-3 p-4 pt-3">

    <a href="/tools/quiz" class="tool-card col-span-2"
       style="flex-direction:row;align-items:center;gap:16px;padding:16px 18px;">
        <div class="tool-icon" style="background:rgba(124,58,237,0.15);width:52px;height:52px;border-radius:16px;font-size:26px;">🧠</div>
        <div class="flex-1 min-w-0">
            <h3 class="text-[15px] font-bold mb-1">Quiz Generator</h3>
            <p class="text-xs">Generate interactive quizzes from any topic or text</p>
        </div>
        <svg class="w-5 h-5 text-white/20 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

    <a href="/tools/presentation" class="tool-card">
        <div class="tool-icon" style="background:rgba(14,165,233,0.15);">📊</div>
        <h3>Presentation</h3>
        <p>Beautiful slide decks in minutes</p>
    </a>

    <a href="/tools/mindmap" class="tool-card">
        <div class="tool-icon" style="background:rgba(16,185,129,0.15);">🗺️</div>
        <h3>Mind Map</h3>
        <p>Visual knowledge structures</p>
    </a>

    <a href="/tools/audio" class="tool-card">
        <div class="tool-icon" style="background:rgba(245,158,11,0.15);">🎙️</div>
        <h3>Audio Narration</h3>
        <p>Text to professional voice</p>
    </a>

    <a href="/tools/animation" class="tool-card">
        <div class="tool-icon" style="background:rgba(239,68,68,0.15);">🎨</div>
        <h3>2D Animation</h3>
        <p>Animated SVG illustration</p>
    </a>

    <a href="/tools/video-explainer" class="tool-card col-span-2"
       style="flex-direction:row;align-items:center;gap:16px;padding:16px 18px;">
        <div class="tool-icon" style="background:rgba(236,72,153,0.15);width:52px;height:52px;border-radius:16px;font-size:26px;">🎬</div>
        <div class="flex-1 min-w-0">
            <h3 class="text-[15px] font-bold mb-1">Video Explainer</h3>
            <p class="text-xs">AI video with narration, slides & captions</p>
        </div>
        <svg class="w-5 h-5 text-white/20 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

</div>

@endsection
