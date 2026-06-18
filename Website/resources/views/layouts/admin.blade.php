<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — EduAI Admin</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        admin: {
                            bg:      '#0a0f1e',
                            sidebar: '#0d1424',
                            card:    '#111827',
                            border:  '#1f2937',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0a0f1e; }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #374151; border-radius: 3px; }

        /* Sidebar gradient */
        .sidebar-gradient {
            background: linear-gradient(180deg, #0d1424 0%, #0f172a 100%);
            border-right: 1px solid rgba(99, 102, 241, 0.15);
        }

        /* Glow effects */
        .glow-indigo { box-shadow: 0 0 20px rgba(99, 102, 241, 0.3); }
        .glow-purple { box-shadow: 0 0 20px rgba(168, 85, 247, 0.3); }

        /* Active sidebar link */
        .nav-link-active {
            background: linear-gradient(135deg, rgba(99,102,241,0.2), rgba(168,85,247,0.1));
            border-left: 3px solid #6366f1;
            color: #a5b4fc;
        }
        .nav-link-active .nav-icon { color: #818cf8; }

        /* Card style */
        .admin-card {
            background: #111827;
            border: 1px solid #1f2937;
            border-radius: 12px;
        }

        /* Stat card gradient backgrounds */
        .stat-indigo  { background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); border: 1px solid rgba(99,102,241,0.3); }
        .stat-purple  { background: linear-gradient(135deg, #2e1065 0%, #4c1d95 100%); border: 1px solid rgba(168,85,247,0.3); }
        .stat-emerald { background: linear-gradient(135deg, #064e3b 0%, #065f46 100%); border: 1px solid rgba(16,185,129,0.3); }
        .stat-rose    { background: linear-gradient(135deg, #4c0519 0%, #881337 100%); border: 1px solid rgba(244,63,94,0.3); }
        .stat-blue    { background: linear-gradient(135deg, #1e3a5f 0%, #1e40af 100%); border: 1px solid rgba(59,130,246,0.3); }

        /* Table rows */
        .admin-table tr:hover { background: rgba(99,102,241,0.05); }

        /* Badge */
        .badge { display: inline-flex; align-items: center; padding: 2px 10px; border-radius: 9999px; font-size: 11px; font-weight: 600; letter-spacing: .03em; }
        .badge-green  { background: rgba(16,185,129,.15); color: #34d399; border:1px solid rgba(16,185,129,.2); }
        .badge-red    { background: rgba(239,68,68,.15); color: #f87171; border:1px solid rgba(239,68,68,.2); }
        .badge-yellow { background: rgba(245,158,11,.15); color: #fbbf24; border:1px solid rgba(245,158,11,.2); }
        .badge-blue   { background: rgba(59,130,246,.15); color: #60a5fa; border:1px solid rgba(59,130,246,.2); }
        .badge-slate  { background: rgba(100,116,139,.15); color: #94a3b8; border:1px solid rgba(100,116,139,.2); }
        .badge-purple { background: rgba(168,85,247,.15); color: #c084fc; border:1px solid rgba(168,85,247,.2); }
        .badge-indigo { background: rgba(99,102,241,.15); color: #818cf8; border:1px solid rgba(99,102,241,.2); }

        /* Animations */
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in { animation: fadeInUp .4s ease both; }
        .delay-100 { animation-delay: .1s; }
        .delay-200 { animation-delay: .2s; }
        .delay-300 { animation-delay: .3s; }

        /* Sidebar pulse dot */
        @keyframes pulse-dot { 0%,100%{opacity:1;} 50%{opacity:0.4;} }
        .pulse-dot { animation: pulse-dot 2s infinite; }
    </style>
</head>
<body class="text-gray-100 overflow-x-hidden" x-data="{ sidebarOpen: false }">

<!-- Mobile Overlay -->
<div x-show="sidebarOpen"
     x-transition:enter="transition-opacity ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="sidebarOpen = false"
     class="fixed inset-0 bg-black/60 z-30 lg:hidden">
</div>

<!-- ══════════════════════════════════════════ SIDEBAR ════════════════════════════════════ -->
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
       class="fixed inset-y-0 left-0 w-64 sidebar-gradient z-40 flex flex-col transition-transform duration-300 ease-out">

    <!-- Logo -->
    <div class="flex items-center gap-3 px-6 py-5 border-b border-white/5">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center glow-indigo">
            <i class="fas fa-brain text-white text-sm"></i>
        </div>
        <div>
            <span class="text-white font-bold text-base tracking-tight">EduAI</span>
            <span class="block text-xs text-indigo-400 font-medium">Admin Panel</span>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">

        <p class="text-xs text-gray-600 font-semibold uppercase tracking-wider px-3 mb-2 mt-1">Main</p>

        <a href="{{ route('admin.dashboard') }}"
           class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-400 hover:text-white hover:bg-white/5 transition-all duration-150 {{ request()->routeIs('admin.dashboard') ? 'nav-link-active' : '' }}">
            <i class="nav-icon fas fa-chart-pie w-4 text-center text-gray-500"></i>
            <span class="text-sm font-medium">Dashboard</span>
        </a>

        <p class="text-xs text-gray-600 font-semibold uppercase tracking-wider px-3 mb-2 mt-4">Management</p>

        <a href="{{ route('admin.users.index') }}"
           class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-400 hover:text-white hover:bg-white/5 transition-all duration-150 {{ request()->routeIs('admin.users.*') ? 'nav-link-active' : '' }}">
            <i class="nav-icon fas fa-users w-4 text-center text-gray-500"></i>
            <span class="text-sm font-medium">Users</span>
            <span class="ml-auto bg-indigo-900/50 text-indigo-400 text-xs px-2 py-0.5 rounded-full">
                {{ \App\Models\User::where('role','!=','admin')->count() }}
            </span>
        </a>

        <a href="{{ route('admin.plans.index') }}"
           class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-400 hover:text-white hover:bg-white/5 transition-all duration-150 {{ request()->routeIs('admin.plans.*') ? 'nav-link-active' : '' }}">
            <i class="nav-icon fas fa-tags w-4 text-center text-gray-500"></i>
            <span class="text-sm font-medium">Plans</span>
        </a>

        <a href="{{ route('admin.jobs.index') }}"
           class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-400 hover:text-white hover:bg-white/5 transition-all duration-150 {{ request()->routeIs('admin.jobs.*') ? 'nav-link-active' : '' }}">
            <i class="nav-icon fas fa-bolt w-4 text-center text-gray-500"></i>
            <span class="text-sm font-medium">Tool Jobs</span>
        </a>

        <p class="text-xs text-gray-600 font-semibold uppercase tracking-wider px-3 mb-2 mt-4">App</p>

        <a href="{{ route('dashboard') }}"
           class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-400 hover:text-white hover:bg-white/5 transition-all duration-150">
            <i class="nav-icon fas fa-arrow-left w-4 text-center text-gray-500"></i>
            <span class="text-sm font-medium">Back to App</span>
        </a>
    </nav>

    <!-- User Info -->
    <div class="px-4 py-4 border-t border-white/5">
        <div class="flex items-center gap-3 bg-white/5 rounded-xl p-3">
            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-sm font-bold text-white flex-shrink-0">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                <p class="text-xs text-indigo-400 font-medium">Administrator</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-gray-500 hover:text-red-400 transition-colors" title="Logout">
                    <i class="fas fa-sign-out-alt text-sm"></i>
                </button>
            </form>
        </div>
    </div>
</aside>

<!-- ══════════════════════════════════════════ MAIN ════════════════════════════════════ -->
<div class="lg:pl-64 min-h-screen flex flex-col">

    <!-- Topbar -->
    <header class="sticky top-0 z-20 flex items-center justify-between px-4 md:px-6 h-16"
            style="background: rgba(10,15,30,0.85); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(99,102,241,0.1);">

        <!-- Mobile menu button -->
        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 rounded-lg text-gray-400 hover:text-white hover:bg-white/5 transition">
            <i class="fas fa-bars text-lg"></i>
        </button>

        <!-- Page title -->
        <div class="flex items-center gap-2 text-sm text-gray-400">
            <i class="fas fa-shield-alt text-indigo-500 text-xs"></i>
            <span>Admin</span>
            <i class="fas fa-chevron-right text-xs text-gray-600"></i>
            <span class="text-white font-medium">@yield('breadcrumb', 'Dashboard')</span>
        </div>

        <!-- Right side -->
        <div class="flex items-center gap-3">
            <!-- Live indicator -->
            <div class="hidden md:flex items-center gap-2 px-3 py-1.5 bg-emerald-500/10 border border-emerald-500/20 rounded-full">
                <span class="w-2 h-2 bg-emerald-500 rounded-full pulse-dot"></span>
                <span class="text-xs text-emerald-400 font-medium">Live</span>
            </div>

            <!-- Admin badge -->
            <div class="flex items-center gap-2 bg-indigo-600/20 border border-indigo-500/30 px-3 py-1.5 rounded-full">
                <i class="fas fa-user-shield text-indigo-400 text-xs"></i>
                <span class="text-xs text-indigo-300 font-semibold">{{ auth()->user()->name }}</span>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-end="opacity-0 scale-95"
         class="mx-4 md:mx-6 mt-4 flex items-center gap-3 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-emerald-400 text-sm">
        <i class="fas fa-check-circle text-base flex-shrink-0"></i>
        <span>{{ session('success') }}</span>
        <button @click="show = false" class="ml-auto text-emerald-500/60 hover:text-emerald-400"><i class="fas fa-times"></i></button>
    </div>
    @endif

    @if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-end="opacity-0 scale-95"
         class="mx-4 md:mx-6 mt-4 flex items-center gap-3 p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-red-400 text-sm">
        <i class="fas fa-exclamation-circle text-base flex-shrink-0"></i>
        <span>{{ session('error') }}</span>
        <button @click="show = false" class="ml-auto text-red-500/60 hover:text-red-400"><i class="fas fa-times"></i></button>
    </div>
    @endif

    <!-- Page Content -->
    <main class="flex-1 px-4 md:px-6 py-6">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="px-6 py-4 border-t border-white/5 text-center text-xs text-gray-600">
        EduAI Admin Panel &copy; {{ date('Y') }} — All rights reserved
    </footer>
</div>

</body>
</html>
