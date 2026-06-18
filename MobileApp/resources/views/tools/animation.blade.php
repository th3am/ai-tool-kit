@extends('layouts.mobile', ['title' => '2D Animation'])

@section('content')
<div x-data="{
        prompt: '', submitting: false, error: '',

        async submit() {
            this.error = '';
            if (!this.prompt.trim()) { this.error = 'Please enter an animation prompt.'; return; }
            this.submitting = true;
            try {
                const sessionId = await Api.createSession('Animation: ' + this.prompt.slice(0,50));
                const res = await Api.post('/tools/animation', { session_id: sessionId, prompt: this.prompt });
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
        <h1>🎨 2D Animation</h1>
    </div>

    <div class="p-4 md:p-8 flex flex-col gap-5 max-w-2xl mx-auto w-full">
        <div class="error-message" x-show="error" x-text="error" style="display:none;"></div>

        <div class="flex flex-col gap-1.5">
            <label>Animation Prompt *</label>
            <textarea placeholder="e.g. A rocket launching from Earth into space with exploding stars in the background…"
                      x-model="prompt" rows="5"></textarea>
        </div>

        {{-- Example prompts --}}
        <div class="flex flex-wrap gap-2">
            <button type="button"
                    class="px-3 py-1.5 rounded-xl text-xs font-medium text-white/50 border border-white/10 transition-colors hover:border-brand-600/50 hover:text-brand-400"
                    @click="prompt='A rocket launching into space with stars'">🚀 Rocket</button>
            <button type="button"
                    class="px-3 py-1.5 rounded-xl text-xs font-medium text-white/50 border border-white/10 transition-colors hover:border-brand-600/50 hover:text-brand-400"
                    @click="prompt='A tree growing from seed to full tree with seasons'">🌳 Tree Growth</button>
            <button type="button"
                    class="px-3 py-1.5 rounded-xl text-xs font-medium text-white/50 border border-white/10 transition-colors hover:border-brand-600/50 hover:text-brand-400"
                    @click="prompt='Water molecules forming and flowing'">💧 Water</button>
        </div>

        <button class="btn btn-primary btn-full" @click="submit()" :disabled="submitting || !prompt.trim()">
            <div class="spinner spinner-sm" x-show="submitting" style="display:none;"></div>
            <span x-text="submitting ? 'Generating animation…' : '✨ Generate Animation'"></span>
        </button>

        <div class="flex items-start gap-3 px-4 py-3 rounded-2xl"
             style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);">
            <span class="text-lg">🎨</span>
            <p class="text-sm text-white/50">Creates an animated SVG from your description. Takes ~1–2 minutes.</p>
        </div>
    </div>
</div>
@endsection
