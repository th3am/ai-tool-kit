@extends('layouts.mobile', ['title' => 'Mind Map'])

@section('content')
<div x-data="{
        topic: '', submitting: false, error: '', result: null,

        async submit() {
            this.error = ''; this.result = null;
            if (!this.topic.trim()) { this.error = 'Topic is required.'; return; }
            this.submitting = true;
            try {
                const sessionId = await Api.createSession('Mind Map: ' + this.topic.slice(0,50));
                const res = await Api.post('/tools/mindmap', { session_id: sessionId, topic: this.topic });
                if (res.status === 'succeeded' && res.result) {
                    this.result = res;
                } else {
                    navigateTo('/jobs/' + res.job_id);
                }
            } catch(e) {
                this.error = e.message || 'Generation failed.';
            } finally {
                this.submitting = false;
            }
        }
     }">

    <div class="page-header">
        <a href="/tools" class="back-btn">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1>🗺️ Mind Map</h1>
    </div>

    <div class="p-4 md:p-8 flex flex-col gap-5 max-w-2xl mx-auto w-full">
        <div class="error-message" x-show="error" x-text="error" style="display:none;"></div>

        {{-- Form --}}
        <div x-show="!result" class="flex flex-col gap-4">
            <div class="flex flex-col gap-1.5">
                <label>Topic *</label>
                <textarea placeholder="e.g. Machine Learning, World War II, Organic Chemistry…"
                          x-model="topic" rows="4"></textarea>
            </div>

            <button class="btn btn-primary btn-full" @click="submit()" :disabled="submitting || !topic.trim()">
                <div class="spinner spinner-sm" x-show="submitting" style="display:none;"></div>
                <span x-text="submitting ? 'Generating mind map…' : '✨ Generate Mind Map'"></span>
            </button>

            <div class="flex items-start gap-3 px-4 py-3 rounded-2xl"
                 style="background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.2);">
                <span class="text-lg">🗺️</span>
                <p class="text-sm text-white/50">Creates a structured mind map as text. Visual export coming soon.</p>
            </div>
        </div>

        {{-- Result --}}
        <div x-show="result" class="fade-in flex flex-col gap-4" style="display:none;">
            <div class="flex items-center justify-between">
                <span class="badge badge-green">✅ Generated</span>
                <button class="btn btn-sm btn-ghost" @click="result=null;topic=''">New Map</button>
            </div>
            <div class="bg-dark-100 border border-white/[0.07] rounded-2xl p-4 overflow-auto max-h-[55vh]">
                <pre class="text-sm text-white/80 font-mono leading-relaxed whitespace-pre-wrap" x-text="result?.result || ''"></pre>
            </div>
            <button class="btn btn-ghost btn-full" @click="() => {
                const b = new Blob([result.result],{type:'text/plain'});
                const a = document.createElement('a');
                a.href = URL.createObjectURL(b);
                a.download = 'mindmap.md'; a.click();
            }">⬇️ Download Markdown</button>
        </div>
    </div>
</div>
@endsection
