@extends('layouts.mobile', ['title' => 'Dashboard'])

@section('content')
<div x-data="dashboardPage()" x-init="init()" class="pb-10">

    <div class="page-header">
        <h1>Dashboard</h1>
    </div>

    {{-- Error --}}
    <div class="error-message mx-4 mt-4" x-show="error" style="display:none;">
        <span x-text="error"></span>
        <button class="ml-2 underline text-white/80 hover:text-white" @click="init()">Retry</button>
    </div>

    <div class="p-4 md:p-8 flex flex-col gap-6 md:gap-8">
        {{-- Welcome Card --}}
        <div class="credits-card flex flex-col md:flex-row md:items-center gap-4">
            <div class="flex-1">
                <p class="text-sm font-medium text-white/70 mb-1">Welcome back,</p>
                <div class="text-2xl md:text-3xl font-extrabold text-white" x-text="user?.name || 'Loading…'">Loading…</div>
            </div>
            <div class="flex items-center gap-6">
                <div>
                    <p class="text-[10px] md:text-xs font-bold uppercase tracking-widest text-brand-400/80 mb-1.5">Credits Balance</p>
                    <div class="text-[32px] md:text-[40px] font-extrabold leading-none text-white" x-text="user?.credits || 0">0</div>
                </div>
                <a href="/profile" class="btn btn-primary text-sm shadow-glow-sm">Upgrade</a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 md:gap-8">
            
            {{-- Tools Grid --}}
            <div class="lg:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-semibold uppercase tracking-widest text-white/50">Quick Tools</p>
                    <a href="/tools" class="text-[13px] text-brand-400 font-medium hover:text-brand-300">View All →</a>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3 md:gap-4">
                    <a href="/tools/video-explainer" class="tool-card">
                        <div class="tool-icon bg-pink-500/10 text-pink-400">🎬</div>
                        <h3>Video Explainer</h3>
                    </a>
                    <a href="/tools/quiz" class="tool-card">
                        <div class="tool-icon bg-emerald-500/10 text-emerald-400">🧠</div>
                        <h3>Smart Quiz</h3>
                    </a>
                    <a href="/tools/presentation" class="tool-card">
                        <div class="tool-icon bg-cyan-500/10 text-cyan-400">📊</div>
                        <h3>Presentations</h3>
                    </a>
                    <a href="/tools/mindmap" class="tool-card">
                        <div class="tool-icon bg-brand-600/10 text-brand-400">🗺️</div>
                        <h3>Mind Map</h3>
                    </a>
                    <a href="/tools/audio" class="tool-card">
                        <div class="tool-icon bg-amber-500/10 text-amber-400">🎙️</div>
                        <h3>Audio Voiceover</h3>
                    </a>
                    <a href="/tools/animation" class="tool-card">
                        <div class="tool-icon bg-red-500/10 text-red-400">🎨</div>
                        <h3>2D Animation</h3>
                    </a>
                </div>
            </div>

            {{-- Recent Jobs Sidebar --}}
            <div class="lg:col-span-1">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-semibold uppercase tracking-widest text-white/50">Recent Jobs</p>
                    <a href="/jobs" class="text-[13px] text-brand-400 font-medium hover:text-brand-300">All Jobs</a>
                </div>

                <div class="bg-dark-100 border border-white/[0.07] rounded-2xl overflow-hidden shadow-glow">
                    <div x-show="loading" class="p-4 flex flex-col gap-3" style="display:none;">
                        <div class="skeleton h-12 rounded-xl"></div>
                        <div class="skeleton h-12 rounded-xl"></div>
                        <div class="skeleton h-12 rounded-xl"></div>
                    </div>

                    <div class="empty-state py-10" x-show="!loading && jobs.length === 0" style="display:none;">
                        <span class="text-4xl mb-2">📋</span>
                        <p>No recent jobs found.</p>
                    </div>

                    <div class="flex flex-col" x-show="!loading && jobs.length > 0" style="display:none;">
                        <template x-for="job in jobs" :key="job.id">
                            <a :href="'/jobs/' + job.id" class="flex items-center gap-3 p-3.5 border-b border-white/[0.07] hover:bg-dark-50 transition-colors">
                                <div class="w-10 h-10 rounded-xl bg-dark-50 flex items-center justify-center text-lg flex-shrink-0" x-text="toolEmoji(job.tool_type)"></div>
                                <div class="flex flex-col flex-1 min-w-0">
                                    <span class="text-[13px] font-semibold text-white truncate" x-text="toolLabel(job.tool_type)"></span>
                                    <span class="text-[11px] text-white/40 truncate" x-text="job.params?.topic || job.params?.prompt || job.params?.text?.slice(0,30) || '—'"></span>
                                </div>
                                <div class="flex flex-col items-end flex-shrink-0">
                                    <span class="badge" :class="statusBadge(job.status)" x-text="job.status"></span>
                                    <span class="text-[10px] text-white/30 mt-1" x-text="timeAgo(job.updated_at)"></span>
                                </div>
                            </a>
                        </template>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
function dashboardPage() {
    return {
        loading: true, error: '', user: null, jobs: [],

        async init() {
            this.loading = true; this.error = '';
            try {
                // Fetch user and recent jobs in parallel
                const [uRes, jRes] = await Promise.all([
                    Api.get('/user'),
                    Api.get('/jobs?per_page=5')
                ]);
                this.user = uRes;
                this.jobs = jRes.data || [];
            } catch(e) {
                if (e.status !== 401) this.error = e.message || 'Failed to load dashboard data.';
            } finally {
                this.loading = false;
            }
        },

        toolEmoji(type) {
            const map = { 'quiz':'🧠','presentation':'📊','mindmap':'🗺️','audio':'🎙️','video-animation':'🎨','video-explainer':'🎬','lecture':'🎬' };
            return map[type] || '⚙️';
        },
    };
}
</script>
@endpush
@endsection
