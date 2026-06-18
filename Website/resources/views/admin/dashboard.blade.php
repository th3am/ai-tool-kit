@extends('layouts.admin')

@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')

{{-- ── Stat Cards ──────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    {{-- Total Users --}}
    <div class="stat-indigo rounded-2xl p-5 animate-fade-in">
        <div class="flex items-start justify-between mb-4">
            <div class="w-11 h-11 rounded-xl bg-indigo-500/20 flex items-center justify-center">
                <i class="fas fa-users text-indigo-400 text-lg"></i>
            </div>
            <span class="text-xs text-indigo-400 font-medium bg-indigo-500/10 px-2 py-1 rounded-full">Total</span>
        </div>
        <p class="text-3xl font-bold text-white">{{ number_format($totalUsers) }}</p>
        <p class="text-sm text-indigo-300 mt-1">Registered Users</p>
    </div>

    {{-- Active Users --}}
    <div class="stat-emerald rounded-2xl p-5 animate-fade-in delay-100">
        <div class="flex items-start justify-between mb-4">
            <div class="w-11 h-11 rounded-xl bg-emerald-500/20 flex items-center justify-center">
                <i class="fas fa-user-check text-emerald-400 text-lg"></i>
            </div>
            <span class="text-xs text-emerald-400 font-medium bg-emerald-500/10 px-2 py-1 rounded-full">Verified</span>
        </div>
        <p class="text-3xl font-bold text-white">{{ number_format($activeUsers) }}</p>
        <p class="text-sm text-emerald-300 mt-1">Verified Users</p>
    </div>

    {{-- Total Jobs --}}
    <div class="stat-purple rounded-2xl p-5 animate-fade-in delay-200">
        <div class="flex items-start justify-between mb-4">
            <div class="w-11 h-11 rounded-xl bg-purple-500/20 flex items-center justify-center">
                <i class="fas fa-bolt text-purple-400 text-lg"></i>
            </div>
            <span class="text-xs text-purple-400 font-medium bg-purple-500/10 px-2 py-1 rounded-full">{{ $jobsThisMonth }} this month</span>
        </div>
        <p class="text-3xl font-bold text-white">{{ number_format($totalJobs) }}</p>
        <p class="text-sm text-purple-300 mt-1">AI Tool Jobs</p>
    </div>

    {{-- Credits Used --}}
    <div class="stat-rose rounded-2xl p-5 animate-fade-in delay-300">
        <div class="flex items-start justify-between mb-4">
            <div class="w-11 h-11 rounded-xl bg-rose-500/20 flex items-center justify-center">
                <i class="fas fa-coins text-rose-400 text-lg"></i>
            </div>
            <span class="text-xs text-rose-400 font-medium bg-rose-500/10 px-2 py-1 rounded-full">Consumed</span>
        </div>
        <p class="text-3xl font-bold text-white">{{ number_format($totalCreditsUsed) }}</p>
        <p class="text-sm text-rose-300 mt-1">Credits Used</p>
    </div>
</div>

{{-- AI Runtime Settings --}}
<div class="admin-card p-5 mb-6 animate-fade-in">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-5">
        <div>
            <h3 class="text-base font-semibold text-white">AI Runtime Settings</h3>
            <p class="text-xs text-gray-500 mt-0.5">Choose which engines are used by quiz generation and text-to-speech.</p>
        </div>
        @if(session('success'))
            <span class="badge badge-green">{{ session('success') }}</span>
        @endif
    </div>

    <form method="POST" action="{{ route('admin.ai-settings.update') }}" class="grid md:grid-cols-4 gap-4 items-end">
        @csrf
        <div>
            <label class="block text-xs font-semibold text-gray-400 mb-2">TTS Provider</label>
            <select name="tts_provider" class="w-full rounded-xl bg-gray-950 border border-gray-800 px-4 py-3 text-sm text-white focus:border-indigo-500 focus:outline-none">
                <option value="edge" @selected(($aiSettings['tts_provider'] ?? 'edge') === 'edge')>Edge TTS</option>
                <option value="gemini" @selected(($aiSettings['tts_provider'] ?? 'edge') === 'gemini')>Gemini TTS</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-400 mb-2">Quiz Question Engine</label>
            <select name="quiz_ai_provider" class="w-full rounded-xl bg-gray-950 border border-gray-800 px-4 py-3 text-sm text-white focus:border-indigo-500 focus:outline-none">
                <option value="questgen" @selected(($aiSettings['quiz_ai_provider'] ?? 'questgen') === 'questgen')>Questgen API</option>
                <option value="gemini" @selected(($aiSettings['quiz_ai_provider'] ?? 'questgen') === 'gemini')>Gemini</option>
                <option value="chatgpt" @selected(($aiSettings['quiz_ai_provider'] ?? 'questgen') === 'chatgpt')>ChatGPT</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-400 mb-2">Quiz AI Model</label>
            <input
                name="quiz_ai_model"
                value="{{ old('quiz_ai_model', $aiSettings['quiz_ai_model'] ?? 'gemini-2.5-flash') }}"
                placeholder="gemini-2.5-flash or gpt-4o-mini"
                class="w-full rounded-xl bg-gray-950 border border-gray-800 px-4 py-3 text-sm text-white placeholder-gray-600 focus:border-indigo-500 focus:outline-none">
        </div>
        <button class="rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-3 text-sm font-semibold text-white transition">
            Save AI Settings
        </button>
    </form>

    @if($errors->any())
        <div class="mt-4 rounded-xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-sm text-red-300">
            {{ $errors->first() }}
        </div>
    @endif
</div>

{{-- ── Charts Row ─────────────────────────────────────────────────────── --}}
<div class="grid lg:grid-cols-3 gap-4 mb-6">

    {{-- Registration Chart --}}
    <div class="lg:col-span-2 admin-card p-5 animate-fade-in">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="text-base font-semibold text-white">User Registrations</h3>
                <p class="text-xs text-gray-500 mt-0.5">Last 7 days</p>
            </div>
            <div class="flex items-center gap-1.5 text-xs text-emerald-400">
                <i class="fas fa-arrow-trend-up"></i> Growth
            </div>
        </div>
        <div class="h-48">
            <canvas id="regChart"></canvas>
        </div>
    </div>

    {{-- Jobs by Tool --}}
    <div class="admin-card p-5 animate-fade-in delay-100">
        <div class="mb-5">
            <h3 class="text-base font-semibold text-white">Jobs by Tool</h3>
            <p class="text-xs text-gray-500 mt-0.5">All time</p>
        </div>
        <div class="h-48 flex items-center justify-center">
            <canvas id="toolChart"></canvas>
        </div>
    </div>
</div>

{{-- ── Bottom Row ─────────────────────────────────────────────────────── --}}
<div class="grid lg:grid-cols-5 gap-4">

    {{-- Recent Users --}}
    <div class="lg:col-span-3 admin-card overflow-hidden animate-fade-in">
        <div class="flex items-center justify-between p-5 border-b border-gray-800">
            <h3 class="text-base font-semibold text-white">Recent Users</h3>
            <a href="{{ route('admin.users.index') }}" class="text-xs text-indigo-400 hover:text-indigo-300 flex items-center gap-1">
                View all <i class="fas fa-arrow-right text-xs"></i>
            </a>
        </div>
        <div class="divide-y divide-gray-800/70">
            @forelse($recentUsers as $user)
            <div class="flex items-center gap-4 px-5 py-3 hover:bg-white/[0.02] transition">
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center text-sm font-bold text-white flex-shrink-0">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate">{{ $user->name }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ $user->email ?? $user->whatsapp_number }}</p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    @if($user->plan)
                        <span class="badge badge-indigo">{{ $user->plan->name }}</span>
                    @else
                        <span class="badge badge-slate">Free</span>
                    @endif
                    <span class="text-xs text-gray-600">{{ $user->created_at->diffForHumans() }}</span>
                </div>
            </div>
            @empty
            <div class="px-5 py-8 text-center text-gray-600 text-sm">No users yet</div>
            @endforelse
        </div>
    </div>

    {{-- Plans & Job Status --}}
    <div class="lg:col-span-2 flex flex-col gap-4">

        {{-- Users by Plan --}}
        <div class="admin-card p-5 animate-fade-in delay-200">
            <h3 class="text-base font-semibold text-white mb-4">Users by Plan</h3>
            <div class="space-y-3">
                @foreach($usersByPlan as $plan)
                @php
                    $maxUsers = $usersByPlan->max('users_count') ?: 1;
                    $pct = $plan->users_count > 0 ? round(($plan->users_count / $maxUsers) * 100) : 0;
                    $colorClass = match($plan->color) {
                        'indigo' => 'bg-indigo-500',
                        'purple' => 'bg-purple-500',
                        'emerald'=> 'bg-emerald-500',
                        default  => 'bg-slate-500',
                    };
                @endphp
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-gray-300 font-medium">{{ $plan->name }}</span>
                        <span class="text-gray-500">{{ $plan->users_count }} users</span>
                    </div>
                    <div class="h-1.5 bg-gray-800 rounded-full overflow-hidden">
                        <div class="{{ $colorClass }} h-full rounded-full transition-all duration-700" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Job Status Breakdown --}}
        <div class="admin-card p-5 animate-fade-in delay-300">
            <h3 class="text-base font-semibold text-white mb-4">Job Status</h3>
            <div class="space-y-2">
                @php
                    $statusConfig = [
                        'succeeded' => ['badge-green', 'fa-check-circle'],
                        'failed'    => ['badge-red', 'fa-times-circle'],
                        'queued'    => ['badge-yellow', 'fa-clock'],
                        'running'   => ['badge-blue', 'fa-spinner'],
                        'cancelled' => ['badge-slate', 'fa-ban'],
                    ];
                @endphp
                @foreach($statusConfig as $status => [$badgeClass, $icon])
                @if(isset($jobStatuses[$status]))
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas {{ $icon }} text-xs {{ str_replace(['badge-', 'slate'], ['text-', 'gray-400'], $badgeClass) }}"></i>
                        <span class="text-sm text-gray-400 capitalize">{{ $status }}</span>
                    </div>
                    <span class="badge {{ $badgeClass }}">{{ number_format($jobStatuses[$status]) }}</span>
                </div>
                @endif
                @endforeach
                @if($jobStatuses->isEmpty())
                <p class="text-sm text-gray-600 text-center py-2">No jobs yet</p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Recent Jobs --}}
<div class="admin-card overflow-hidden mt-6 animate-fade-in">
    <div class="flex items-center justify-between p-5 border-b border-gray-800">
        <div>
            <h3 class="text-base font-semibold text-white">Recent Jobs</h3>
            <p class="text-xs text-gray-500 mt-0.5">Latest AI jobs across users</p>
        </div>
        <a href="{{ route('admin.jobs.index') }}" class="text-xs text-indigo-400 hover:text-indigo-300 flex items-center gap-1">
            View all <i class="fas fa-arrow-right text-xs"></i>
        </a>
    </div>
    <div class="divide-y divide-gray-800/70">
        @forelse($recentJobs as $job)
            @php
                $badgeClass = match($job->status) {
                    'succeeded' => 'badge-green',
                    'failed' => 'badge-red',
                    'queued' => 'badge-yellow',
                    'running' => 'badge-blue',
                    'cancelled' => 'badge-slate',
                    default => 'badge-slate',
                };
            @endphp
            <div class="grid md:grid-cols-[1fr_auto_auto] gap-3 px-5 py-3 hover:bg-white/[0.02] transition">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-white truncate">{{ ucwords(str_replace('-', ' ', $job->tool_type)) }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ $job->user?->name ?? 'Deleted user' }} · {{ $job->params['topic'] ?? $job->params['prompt'] ?? 'No topic' }}</p>
                </div>
                <span class="badge {{ $badgeClass }} self-center capitalize">{{ $job->status }}</span>
                <span class="text-xs text-gray-600 self-center">{{ $job->created_at->diffForHumans() }}</span>
            </div>
        @empty
            <div class="px-5 py-8 text-center text-gray-600 text-sm">No jobs yet</div>
        @endforelse
    </div>
</div>

{{-- ── Chart.js Scripts ─────────────────────────────────────────────── --}}
<script>
    const chartDefaults = {
        color: '#9ca3af',
        borderColor: 'rgba(99,102,241,0.2)',
    };
    Chart.defaults.color = '#9ca3af';

    // Registration Chart
    const regCtx = document.getElementById('regChart').getContext('2d');
    const regChart = new Chart(regCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($chartDates->pluck('date')) !!},
            datasets: [{
                label: 'Registrations',
                data: {!! json_encode($chartDates->pluck('total')) !!},
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99,102,241,0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#6366f1',
                pointRadius: 4,
                pointHoverRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { backgroundColor: '#1f2937', borderColor: 'rgba(99,102,241,0.3)', borderWidth: 1 } },
            scales: {
                x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#6b7280', font: { size: 11 } } },
                y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#6b7280', font: { size: 11 }, precision: 0 }, beginAtZero: true },
            }
        }
    });

    // Tool Distribution Doughnut
    const toolCtx = document.getElementById('toolChart').getContext('2d');
    const toolColors = ['#6366f1','#a855f7','#10b981','#f59e0b','#ef4444','#06b6d4','#f97316'];
    new Chart(toolCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($jobsByTool->pluck('tool_type')->map(fn($t) => ucwords(str_replace('-', ' ', $t)))) !!},
            datasets: [{
                data: {!! json_encode($jobsByTool->pluck('total')) !!},
                backgroundColor: toolColors,
                borderColor: '#111827',
                borderWidth: 2,
                hoverOffset: 8,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { position: 'bottom', labels: { color: '#9ca3af', font: { size: 10 }, boxWidth: 10, padding: 8 } },
                tooltip: { backgroundColor: '#1f2937', borderColor: 'rgba(99,102,241,0.3)', borderWidth: 1 }
            }
        }
    });
</script>
@endsection
