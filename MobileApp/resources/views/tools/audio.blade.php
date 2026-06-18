@extends('layouts.mobile', ['title' => 'Audio Narration'])

@section('content')
<div x-data="{
        text: '', submitting: false, error: '',

        async submit() {
            this.error = '';
            if (!this.text.trim()) { this.error = 'Please enter text to narrate.'; return; }
            if (this.text.length > 5000) { this.error = 'Text exceeds 5,000 character limit.'; return; }
            this.submitting = true;
            try {
                const sessionId = await Api.createSession('Audio: ' + this.text.slice(0,50));
                const res = await Api.post('/tools/audio', { session_id: sessionId, text: this.text });
                navigateTo('/jobs/' + res.job_id);
            } catch(e) {
                this.error = e.message || 'Failed to start generation.';
                this.submitting = false;
            }
        }
     }">

    <div class="page-header">
        <a href="/tools" class="back-btn">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1>🎙️ Audio Narration</h1>
    </div>

    <div class="p-4 md:p-8 flex flex-col gap-5 max-w-2xl mx-auto w-full">
        <div class="error-message" x-show="error" x-text="error" style="display:none;"></div>

        <div class="flex flex-col gap-1.5">
            <div class="flex items-center justify-between">
                <label>Text to Narrate *</label>
                <span class="text-[11px] font-medium"
                      :class="text.length > 4500 ? 'text-amber-400' : 'text-white/25'"
                      x-text="text.length + ' / 5,000'"></span>
            </div>
            <textarea placeholder="Paste or type the text you want narrated in a professional voice…"
                      x-model="text" rows="9" style="min-height:200px;"></textarea>
        </div>

        <button class="btn btn-primary btn-full" @click="submit()"
                :disabled="submitting || !text.trim() || text.length > 5000">
            <div class="spinner spinner-sm" x-show="submitting" style="display:none;"></div>
            <span x-text="submitting ? 'Generating audio…' : '🎙️ Generate Audio'"></span>
        </button>

        <div class="flex items-start gap-3 px-4 py-3 rounded-2xl"
             style="background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.2);">
            <span class="text-lg">⏱️</span>
            <p class="text-sm text-white/50">Audio generation takes 30–90 seconds. You'll be redirected to listen and download when ready.</p>
        </div>
    </div>
</div>
@endsection
