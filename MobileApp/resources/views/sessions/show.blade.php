@extends('layouts.mobile', ['title' => 'Session'])

@section('content')
<div x-data="sessionDetailPage(@js($sessionId))" x-init="init()">

    <div class="page-header">
        <a href="/sessions" class="back-btn">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="truncate" x-text="session ? session.title : 'Session'">Session</h1>
    </div>

    {{-- Skeleton --}}
    <div class="p-4 flex flex-col gap-3" x-show="loading" style="display:none;">
        <div class="skeleton h-10 w-[60%] rounded-lg"></div>
        <div class="skeleton h-16 rounded-[14px]"></div>
        <div class="skeleton h-16 rounded-[14px]"></div>
    </div>

    {{-- Error --}}
    <div class="error-state" x-show="error && !loading" style="display:none;">
        <span class="text-5xl">⚠️</span>
        <h3>Failed to load</h3>
        <p x-text="error"></p>
        <button class="btn btn-ghost btn-sm mt-2" @click="init()">Retry</button>
    </div>

    {{-- Content --}}
    <div x-show="!loading && !error" style="display:none;" class="fade-in">

        {{-- Session info --}}
        <div class="p-4 pb-0">
            <div class="card card-accent">
                <p class="text-[10px] text-brand-400/80 font-bold uppercase tracking-widest mb-1.5">Session Overview</p>
                <p class="font-bold text-[15px] text-white" x-text="session?.title"></p>
                <p class="text-xs text-white/50 mt-1" x-text="(session?.messages_count || 0) + ' msgs · ' + (session?.jobs_count || 0) + ' jobs · ' + timeAgo(session?.created_at)"></p>
            </div>
        </div>

        {{-- Jobs --}}
        <div x-show="jobs.length > 0" style="display:none;">
            <p class="section-label mt-4">Jobs in this session</p>
            <div class="border-y border-white/[0.07]">
                <template x-for="job in jobs" :key="job.id">
                    <a :href="'/jobs/' + job.id" class="list-item">
                        <div class="w-9 h-9 rounded-xl bg-dark-50 flex items-center justify-center text-base flex-shrink-0"
                             x-text="toolEmoji(job.tool_type)"></div>
                        <div class="flex flex-col flex-1 min-w-0">
                            <span class="text-[13px] font-semibold text-white" x-text="toolLabel(job.tool_type)"></span>
                            <span class="text-[11px] text-white/40 truncate" x-text="job.params?.topic || job.params?.prompt || job.params?.text || '—'"></span>
                        </div>
                        <span class="badge flex-shrink-0" :class="statusBadge(job.status)" x-text="job.status"></span>
                    </a>
                </template>
            </div>
        </div>

        {{-- Messages --}}
        <p class="section-label" :class="jobs.length > 0 ? 'mt-4' : 'mt-2'">Messages</p>

        <div x-show="messages.length === 0 && jobs.length === 0" class="empty-state">
            <h3>No activity yet</h3>
            <p>This session has no messages or jobs saved yet.</p>
        </div>

        <div class="flex flex-col px-4 pb-6 gap-4">
            <template x-for="msg in messages" :key="msg.id">
                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div :class="msg.role === 'user'
                            ? 'bg-brand-600 text-white shadow-glow-sm rounded-tl-2xl rounded-tr-2xl rounded-bl-2xl rounded-br-sm'
                            : 'bg-dark-100 border border-white/10 text-white/90 rounded-tl-2xl rounded-tr-2xl rounded-bl-sm rounded-br-2xl'"
                         class="max-w-[85%] px-4 py-3 text-[14px] leading-relaxed break-words"
                         x-text="messageText(msg)"></div>
                </div>
            </template>
        </div>

    </div>

</div>

@push('scripts')
<script>
function sessionDetailPage(sessionId) {
    return {
        sessionId, session: null, messages: [], jobs: [],
        loading: true, error: '',

        async init() {
            this.loading = true; this.error = '';
            try {
                const res = await Api.get('/sessions/' + sessionId);
                const payload = res.data || res;
                this.session = payload;
                this.messages = payload.messages || payload.data?.messages || [];
                this.jobs = payload.jobs || payload.tool_jobs || payload.data?.jobs || [];
            } catch(e) {
                if (e.status !== 401) this.error = e.message || 'Failed to load session.';
            } finally {
                this.loading = false;
            }
        },

        toolEmoji(type) {
            const map = { 'quiz':'🧠','presentation':'📊','mindmap':'🗺️','audio':'🎙️','video-animation':'🎨','video-explainer':'🎬','lecture':'🎬' };
            return map[type] || '⚙️';
        },

        messageText(msg) {
            if (msg?.content) return msg.content;
            if (msg?.tool_job?.tool_type) {
                return toolLabel(msg.tool_job.tool_type) + ' - ' + (msg.tool_job.status || 'created');
            }
            if (msg?.meta_data?.summary) return msg.meta_data.summary;
            return 'No message content saved.';
        },
    };
}
</script>
@endpush
@endsection
