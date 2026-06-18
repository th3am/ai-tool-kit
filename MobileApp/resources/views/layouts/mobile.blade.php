<!DOCTYPE html>
<html lang="en" x-data>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="api-base-url" content="{{ $apiBaseUrl ?? env('API_BASE_URL', 'http://127.0.0.1:8000/api/v1') }}">
    <meta name="theme-color" content="#0a0a12">
    <title>{{ $title ?? 'EduAI' }} | EduAI Mobile</title>

    @vite(['resources/css/app.css', 'resources/js/api.js', 'resources/js/app.js'])

    {{-- Alpine must defer AFTER vite scripts so store registers on alpine:init --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @stack('head')
</head>
<body class="bg-dark-400 text-white font-sans overflow-hidden h-full">

{{-- Global Progress Bar --}}
<div id="progress-bar"></div>

{{-- Global Loading Overlay --}}
<div class="page-overlay" x-show="$store.app.loading" x-transition style="display:none;">
    <div class="spinner spinner-lg spinner-accent"></div>
    <p class="text-sm text-white/50 font-medium">Loading…</p>
</div>

{{-- App Shell --}}
<div id="app">

    {{-- Sidebar (Desktop) --}}
    @if(!isset($hideNav) || !$hideNav)
    @php $seg = request()->segment(1); @endphp
    <nav class="sidebar">
        <div class="px-2 mb-8 mt-2 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-accent-gradient flex items-center justify-center font-bold text-white shadow-glow-sm">E</div>
            <span class="font-bold text-xl tracking-tight text-white">EduAI</span>
        </div>

        <div class="flex flex-col gap-2 flex-1">
            <a href="/dashboard" class="nav-item {{ $seg === 'dashboard' ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>Dashboard</span>
            </a>
            <a href="/tools" class="nav-item {{ $seg === 'tools' ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                <span>Tools Directory</span>
            </a>
            <a href="/sessions" class="nav-item {{ $seg === 'sessions' ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                <span>Chat History</span>
            </a>
            <a href="/jobs" class="nav-item {{ $seg === 'jobs' ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <span>Generated Jobs</span>
            </a>
        </div>

        <div class="mt-auto pt-4 border-t border-white/10">
            <a href="/profile" class="nav-item {{ $seg === 'profile' ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span>Settings & Profile</span>
            </a>
        </div>
    </nav>
    @endif

    {{-- Page Content --}}
    <div class="page-content">
        <div class="max-w-6xl mx-auto w-full flex-1">
            @yield('content')
        </div>
    </div>

    {{-- Bottom Navigation (Mobile) --}}
    @if(!isset($hideNav) || !$hideNav)
    <nav class="bottom-nav">
        <a href="/dashboard" class="nav-item {{ $seg === 'dashboard' ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <span>Home</span>
        </a>
        <a href="/tools" class="nav-item {{ $seg === 'tools' ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            <span>Tools</span>
        </a>
        <a href="/sessions" class="nav-item {{ $seg === 'sessions' ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            <span>History</span>
        </a>
        <a href="/jobs" class="nav-item {{ $seg === 'jobs' ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <span>Jobs</span>
        </a>
        <a href="/profile" class="nav-item {{ $seg === 'profile' ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span>Profile</span>
        </a>
    </nav>
    @endif

</div>{{-- /#app --}}

@stack('scripts')
</body>
</html>
