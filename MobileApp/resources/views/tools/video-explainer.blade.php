@extends('layouts.mobile', ['title' => 'Video Explainer'])

@section('content')
<div x-data="{
        topic: '', style: 'Modern', slideCount: 5, language: 'ar',
        enableCaptions: true, instructions: '',
        submitting: false, error: '',

        async submit() {
            this.error = '';
            if (!this.topic.trim()) { this.error = 'Topic is required.'; return; }
            this.submitting = true;
            try {
                const sessionId = await Api.createSession('Video: ' + this.topic.slice(0,50));
                const res = await Api.post('/tools/video-explainer', {
                    session_id: sessionId,
                    topic: this.topic, style: this.style,
                    slide_count: Number.parseInt(this.slideCount, 10) || 5, language: this.language,
                    enable_captions: this.enableCaptions, instructions: this.instructions,
                });
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
        <h1>🎬 Video Explainer</h1>
    </div>

    <div class="p-4 md:p-8 flex flex-col gap-5 max-w-2xl mx-auto w-full">
        <div class="error-message" x-show="error" x-text="error" style="display:none;"></div>

        <div class="flex flex-col gap-1.5">
            <label>Topic *</label>
            <textarea placeholder="e.g. How does photosynthesis work?, The life cycle of stars…"
                      x-model="topic" rows="3"></textarea>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div class="flex flex-col gap-1.5">
                <label>Style</label>
                <select x-model="style">
                    <option>Modern</option>
                    <option>Classic</option>
                    <option>Minimal</option>
                    <option>Animated</option>
                </select>
            </div>
            <div class="flex flex-col gap-1.5">
                <label>Slides</label>
                <select x-model="slideCount">
                    <option value="3">3</option>
                    <option value="5" selected>5</option>
                    <option value="8">8</option>
                    <option value="10">10</option>
                </select>
            </div>
        </div>

        <div class="flex flex-col gap-1.5">
            <label>Narration Language</label>
            <select x-model="language">
                <option value="ar">🇸🇦 Arabic (العربية)</option>
                <option value="en">🇺🇸 English</option>
                <option value="fr">🇫🇷 French</option>
                <option value="de">🇩🇪 German</option>
                <option value="es">🇪🇸 Spanish</option>
            </select>
        </div>

        {{-- Caption toggle --}}
        <div class="flex items-center justify-between px-4 py-3.5 bg-dark-50 border border-white/[0.07] rounded-xl">
            <div>
                <p class="text-sm font-semibold text-white">Enable Captions</p>
                <p class="text-xs text-white/40 mt-0.5">Add subtitles to the video</p>
            </div>
            <label class="toggle">
                <input type="checkbox" x-model="enableCaptions">
                <div class="toggle-track"></div>
            </label>
        </div>

        <div class="flex flex-col gap-1.5">
            <label>Extra Instructions <span class="text-white/25">(optional)</span></label>
            <textarea placeholder="e.g. Keep it simple for elementary school, include examples…"
                      x-model="instructions" rows="2"></textarea>
        </div>

        <button class="btn btn-primary btn-full" @click="submit()" :disabled="submitting || !topic.trim()">
            <div class="spinner spinner-sm" x-show="submitting" style="display:none;"></div>
            <span x-text="submitting ? 'Generating video…' : '🎬 Generate Video'"></span>
        </button>

        <div class="flex items-start gap-3 px-4 py-3 rounded-2xl"
             style="background:rgba(236,72,153,0.08);border:1px solid rgba(236,72,153,0.2);">
            <span class="text-lg">⏱️</span>
            <p class="text-sm text-white/50">Video generation takes 2–5 minutes. Download the MP4 when ready.</p>
        </div>
    </div>
</div>
@endsection
