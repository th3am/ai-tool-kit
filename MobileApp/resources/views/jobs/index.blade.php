@extends('layouts.mobile', ['title' => 'Recent Jobs'])

@section('content')
<div x-data="jobsPage()" x-init="init()">

    <div class="page-header">
        <h1>Jobs</h1>
    </div>

    {{-- Skeleton --}}
    <div x-show="loading" class="p-4 flex flex-col gap-3" style="display:none;">
        <template x-for="i in [1,2,3,4,5]" :key="i">
            <div class="skeleton h-[72px] rounded-[14px]"></div>
        </template>
    </div>

    {{-- Error --}}
    <div class="error-state" x-show="error && !loading" style="display:none;">
        <span class="text-5xl">⚠️</span>
        <h3>Failed to load</h3>
        <p x-text="error"></p>
        <button class="btn btn-ghost btn-sm" @click="init()">Retry</button>
    </div>

    {{-- Empty --}}
    <div class="empty-state" x-show="!loading && !error && jobs.length === 0" style="display:none;">
        <span class="text-5xl">📋</span>
        <h3>No jobs yet</h3>
        <p>Use a tool to generate content and jobs will appear here.</p>
        <a href="/tools" class="btn btn-primary btn-sm mt-2">Browse Tools</a>
    </div>

    {{-- Jobs list --}}
    <div x-show="!loading && !error && jobs.length > 0" style="display:none;" class="fade-in">
        <template x-for="job in jobs" :key="job.id">
            <a :href="'/jobs/' + job.id" class="list-item">
                <div class="w-10 h-10 rounded-xl bg-dark-50 flex items-center justify-center text-lg flex-shrink-0"
                     x-text="toolEmoji(job.tool_type)"></div>
                <div class="flex flex-col flex-1 min-w-0">
                    <span class="text-sm font-semibold text-white truncate" x-text="toolLabel(job.tool_type)"></span>
                    <span class="text-xs text-white/40 truncate" x-text="job.params?.topic || job.params?.prompt || job.params?.text?.slice(0,50) || '—'"></span>
                </div>
                <div class="flex flex-col items-end gap-1 flex-shrink-0 pl-2">
                    <span class="badge" :class="statusBadge(job.status)" x-text="job.status"></span>
                    <span class="text-[10px] text-white/30" x-text="timeAgo(job.updated_at)"></span>
                </div>
            </a>
        </template>

        {{-- Load more --}}
        <div x-show="hasMore" class="p-4" style="display:none;">
            <button class="btn btn-ghost btn-full" @click="loadMore()" :disabled="loadingMore">
                <div class="spinner spinner-sm" x-show="loadingMore" style="display:none;"></div>
                <span x-text="loadingMore ? 'Loading…' : 'Load more'"></span>
            </button>
        </div>
    </div>

</div>

@push('scripts')
<script>
function jobsPage() {
    return {
        loading: true, error: '', jobs: [],
        page: 1, hasMore: false, loadingMore: false,

        async init() {
            this.loading = true; this.error = '';
            try {
                const res = await Api.get('/jobs?per_page=20&page=1');
                this.jobs = res.data || [];
                this.hasMore = res.current_page < res.last_page;
            } catch(e) {
                if (e.status !== 401) this.error = e.message || 'Failed to load jobs.';
            } finally {
                this.loading = false;
            }
        },

        async loadMore() {
            this.loadingMore = true;
            this.page++;
            try {
                const res = await Api.get('/jobs?per_page=20&page=' + this.page);
                this.jobs = [...this.jobs, ...(res.data || [])];
                this.hasMore = res.current_page < res.last_page;
            } catch(e) { this.page--; }
            finally { this.loadingMore = false; }
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
