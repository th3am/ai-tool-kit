@extends('layouts.mobile', ['title' => 'Chat History'])

@section('content')
<div x-data="sessionsPage()" x-init="init()">

    <div class="page-header">
        <h1>Chat History</h1>
    </div>

    {{-- Skeleton --}}
    <div x-show="loading" class="p-4 flex flex-col gap-3" style="display:none;">
        <template x-for="i in [1,2,3,4,5]" :key="i">
            <div class="skeleton h-[68px] rounded-[14px]"></div>
        </template>
    </div>

    {{-- Error --}}
    <div class="error-state" x-show="error && !loading" style="display:none;">
        <span class="text-5xl">⚠️</span>
        <h3>Failed to load</h3>
        <p x-text="error"></p>
        <button class="btn btn-ghost btn-sm mt-2" @click="init()">Retry</button>
    </div>

    {{-- Empty --}}
    <div class="empty-state" x-show="!loading && !error && sessions.length === 0" style="display:none;">
        <span class="text-5xl">💬</span>
        <h3>No sessions yet</h3>
        <p>Your AI tool sessions will appear here once you start generating.</p>
        <a href="/tools" class="btn btn-primary btn-sm mt-2">Start Generating</a>
    </div>

    {{-- Sessions list --}}
    <div x-show="!loading && !error && sessions.length > 0" style="display:none;" class="fade-in">
        <template x-for="session in sessions" :key="session.id">
            <a :href="'/sessions/' + session.id" class="list-item">
                <div class="w-10 h-10 rounded-xl bg-dark-50 flex items-center justify-center text-lg flex-shrink-0">💬</div>
                <div class="flex flex-col flex-1 min-w-0">
                    <span class="text-sm font-semibold text-white truncate" x-text="session.title || 'Untitled Session'"></span>
                    <span class="text-xs text-white/40" x-text="(session.messages_count || 0) + ' messages · ' + (session.jobs_count || 0) + ' jobs'"></span>
                </div>
                <span class="text-[10px] text-white/30 flex-shrink-0 pl-2" x-text="timeAgo(session.updated_at)"></span>
            </a>
        </template>
    </div>

</div>

@push('scripts')
<script>
function sessionsPage() {
    return {
        loading: true, error: '', sessions: [],

        async init() {
            this.loading = true; this.error = '';
            try {
                const res = await Api.get('/sessions');
                const payload = res.data || res.sessions || res;
                this.sessions = Array.isArray(payload) ? payload : [];
            } catch(e) {
                if (e.status !== 401) this.error = e.message || 'Failed to load sessions.';
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endpush
@endsection
