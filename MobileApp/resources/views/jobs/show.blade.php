@extends('layouts.mobile', ['title' => 'Job Detail'])

@section('content')
<div x-data="jobDetailPage(@js($jobId))" x-init="init()" class="pb-10">
    <div class="page-header">
        <a href="/jobs" class="back-btn">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 x-text="job ? toolLabel(job.tool_type) : 'Job'">Job</h1>
        <span class="badge" :class="job ? statusBadge(job.status) : 'badge-gray'" x-text="job ? job.status : '...'"></span>
    </div>

    <div class="p-4 md:p-8 grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <aside class="lg:col-span-4 flex flex-col gap-4">
            <div class="flex flex-col gap-4" x-show="loading && !job" style="display:none;">
                <div class="skeleton h-24 rounded-[14px]"></div>
                <div class="skeleton h-32 rounded-[14px]"></div>
            </div>

            <div class="card card-accent shadow-glow-sm" x-show="job" style="display:none;">
                <p class="text-[10px] text-brand-400/80 font-bold uppercase tracking-widest mb-1.5">Tool</p>
                <p class="font-bold text-[16px] text-white" x-text="toolLabel(job?.tool_type)"></p>
                <p class="text-xs text-white/40 mt-1" x-text="job?.created_at ? timeAgo(job.created_at) : ''"></p>

                <div class="divider my-4"></div>

                <p class="text-[10px] text-brand-400/80 font-bold uppercase tracking-widest mb-2">Parameters</p>
                <div class="flex flex-col gap-2" x-show="paramEntries().length > 0">
                    <template x-for="item in paramEntries()" :key="item.key">
                        <div class="rounded-xl bg-dark-50 border border-white/[0.07] p-3">
                            <p class="text-[10px] uppercase tracking-widest text-white/35" x-text="item.key"></p>
                            <p class="text-sm font-medium text-white/90 leading-relaxed mt-1 break-words" x-text="item.value"></p>
                        </div>
                    </template>
                </div>
                <p class="text-sm text-white/45" x-show="paramEntries().length === 0">No parameters were saved for this job.</p>
            </div>

            <div class="card text-center py-10 shadow-glow" x-show="job && isWorking()" style="display:none;">
                <div class="flex items-center justify-center gap-3 mb-4">
                    <div class="poll-dot"></div>
                    <p class="font-semibold text-white" x-text="job?.status === 'running' ? 'Generating...' : 'Queued - waiting for worker'"></p>
                </div>
                <p class="text-sm text-white/40 max-w-[240px] mx-auto">This page refreshes automatically while the job is processing.</p>
                <div class="mt-8 progress-track mx-auto w-48">
                    <div class="progress-fill" style="width:60%;animation:none;"></div>
                </div>
            </div>
        </aside>

        <main class="lg:col-span-8 flex flex-col gap-4">
            <div class="error-state card border-red-500/30 bg-red-500/5 shadow-glow" x-show="error && !loading" style="display:none;">
                <span class="text-4xl">!</span>
                <h3>Could not load job</h3>
                <p x-text="error"></p>
                <button class="btn btn-danger btn-sm mt-4" @click="fetchJob()">Retry</button>
            </div>

            <div class="fade-in" x-show="job?.status === 'succeeded'" style="display:none;">
                <template x-if="job?.tool_type === 'presentation'">
                    <section class="flex flex-col gap-4" x-data="{ currentSlide: 0 }" x-effect="currentSlide = Math.min(currentSlide, Math.max(slides().length - 1, 0))">
                        <div class="flex items-center justify-between gap-3 px-1">
                            <div>
                                <p class="text-sm font-semibold text-white">Presentation Preview</p>
                                <p class="text-xs text-white/45" x-text="slides().length + ' slides generated'"></p>
                            </div>
                            <div class="flex gap-2" x-show="slides().length > 1">
                                <button class="btn btn-ghost btn-sm" @click="currentSlide = Math.max(0, currentSlide - 1)" :disabled="currentSlide === 0">Prev</button>
                                <button class="btn btn-ghost btn-sm" @click="currentSlide = Math.min(slides().length - 1, currentSlide + 1)" :disabled="currentSlide >= slides().length - 1">Next</button>
                            </div>
                        </div>

                        <template x-if="slides().length > 0">
                            <div class="relative w-full aspect-video bg-white rounded-2xl overflow-hidden border border-white/10 shadow-glow">
                                <iframe class="absolute inset-0 w-full h-full border-0 bg-white"
                                        sandbox="allow-scripts"
                                        :srcdoc="slideDocument(slides()[currentSlide])"></iframe>
                                <div class="absolute bottom-3 left-1/2 -translate-x-1/2 bg-black/70 text-white text-xs font-semibold px-3 py-1.5 rounded-full pointer-events-none">
                                    <span x-text="(currentSlide + 1) + ' / ' + slides().length"></span>
                                </div>
                            </div>
                        </template>

                        <div class="empty-state card" x-show="slides().length === 0">
                            <h3>No preview available</h3>
                            <p>The presentation finished, but the API did not return slide HTML.</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3" x-show="job?.results?.presentation_id">
                            <button class="btn btn-primary text-[13px]" @click="downloadFile(job.results.pdf_url || ('/downloads/presentation/' + job.results.presentation_id + '/pdf'), 'presentation.pdf')">
                                Download PDF
                            </button>
                            <button class="btn btn-ghost text-[13px]" @click="downloadFile(job.results.ppt_url || ('/downloads/presentation/' + job.results.presentation_id + '/ppt'), 'presentation.pptx')">
                                Download PPTX
                            </button>
                        </div>
                    </section>
                </template>

                <template x-if="job?.tool_type === 'mindmap'">
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center justify-between gap-3 px-1">
                            <div>
                                <p class="text-sm font-semibold text-white">Interactive Mind Map</p>
                                <p class="text-xs text-white/45">Drag, zoom, and expand nodes.</p>
                            </div>
                            <button class="btn btn-ghost btn-sm" @click="fitMindmap()" x-show="mmInstance">Fit</button>
                        </div>

                        <div class="card p-0 overflow-hidden bg-white rounded-2xl border border-white/10 shadow-glow relative h-[460px] md:h-[560px] w-full">
                            <div class="absolute inset-0 flex items-center justify-center text-dark-400" x-show="mindmapLoading">
                                <div class="spinner spinner-md spinner-accent"></div>
                            </div>
                            <svg x-ref="mindmapSvg" class="w-full h-full block"></svg>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <button class="btn btn-primary text-[13px]" @click="downloadFile(job.results.png_url || ('/downloads/mindmap/' + job.id + '/png'), 'mindmap.png')">
                                Download PNG
                            </button>
                            <button class="btn btn-ghost text-[13px]" @click="downloadFile(job.results.svg_url || ('/downloads/mindmap/' + job.id + '/svg'), 'mindmap.svg')">
                                Download SVG
                            </button>
                        </div>
                    </section>
                </template>

                <template x-if="job?.tool_type === 'audio'">
                    <section class="flex flex-col gap-4">
                        <div class="card p-6 shadow-glow" x-show="job?.results?.audio_url">
                            <p class="text-[10px] text-brand-400/80 font-bold uppercase tracking-widest mb-4">Audio Track</p>
                            <audio class="w-full" controls :src="job.results.audio_url"></audio>
                        </div>
                        <button class="btn btn-primary w-fit" x-show="job?.results?.audio_url || job?.id" @click="downloadFile('/downloads/audio/' + job.id, 'audio.mp3')">Download Audio</button>
                    </section>
                </template>

                <template x-if="job?.tool_type === 'video-explainer' || job?.tool_type === 'lecture'">
                    <section class="flex flex-col gap-4">
                        <div class="rounded-2xl overflow-hidden border border-white/10 bg-dark-50 aspect-video shadow-glow" x-show="job?.results?.video_url">
                            <video class="w-full h-full object-cover" controls :src="job.results.video_url"></video>
                        </div>
                        <button class="btn btn-primary" x-show="job?.results?.download_url || job?.id" @click="downloadFile(job.results.download_url || ('/downloads/video-explainer/' + job.id), 'video-explainer.mp4')">Download MP4</button>
                    </section>
                </template>

                <template x-if="job?.tool_type === 'video-animation'">
                    <section class="flex flex-col gap-4">
                        <div class="card flex items-center justify-center p-8 bg-dark-50 shadow-glow" x-show="job?.results?.svg" x-html="job.results.svg"></div>
                        <button class="btn btn-primary w-fit" x-show="job?.results?.svg_url || job?.id" @click="downloadFile('/downloads/animation/' + job.id + '/svg', 'animation.svg')">Download SVG</button>
                    </section>
                </template>

                <template x-if="job?.tool_type === 'quiz' && job?.results?.quiz_id">
                    <section class="card card-accent text-center py-12 shadow-glow">
                        <p class="font-bold text-2xl text-white">Quiz Ready</p>
                        <p class="text-[15px] text-brand-400 mt-2">Your interactive quiz has been generated.</p>
                        <a :href="'/quiz/' + job.results.quiz_id + '/play'" class="btn btn-primary mt-6 px-8 shadow-glow-sm">Take the Quiz</a>
                    </section>
                </template>
            </div>

            <div class="error-state card border-red-500/30 bg-red-500/5 shadow-glow" x-show="job?.status === 'failed'" style="display:none;">
                <span class="text-4xl">!</span>
                <h3>Generation Failed</h3>
                <p x-text="job?.error_message || 'An unknown error occurred.'"></p>
                <a href="/tools" class="btn btn-danger btn-sm mt-4">Try Again</a>
            </div>

            <div class="empty-state card" x-show="job?.status === 'cancelled'" style="display:none;">
                <h3>Job Cancelled</h3>
                <p>This job was cancelled before it could complete.</p>
                <a href="/tools" class="btn btn-ghost btn-sm mt-4">New Job</a>
            </div>
        </main>
    </div>
</div>

@push('head')
<style>
    .markmap text { font-family: Inter, Arial, sans-serif; fill: #111827; }
    .markmap path { stroke: #64748b; }
    .markmap circle { stroke: #64748b; fill: #ffffff; }
</style>
@endpush

@push('scripts')
<script>
function jobDetailPage(jobId) {
    return {
        jobId,
        job: null,
        loading: true,
        error: '',
        pollTimer: null,
        mmInstance: null,
        mindmapLoading: false,

        async init() {
            await this.fetchJob();
            this.maybeStartPolling();
        },

        async fetchJob() {
            try {
                const res = await Api.get('/jobs/' + this.jobId);
                this.job = res.data || res;
                this.error = '';

                if (this.job?.tool_type === 'mindmap' && this.job?.status === 'succeeded') {
                    this.$nextTick(() => this.renderMindmap());
                }
            } catch (e) {
                if (e.status !== 401) this.error = e.message || 'Failed to load job.';
            } finally {
                this.loading = false;
            }
        },

        isWorking() {
            return this.job?.status === 'queued' || this.job?.status === 'running' || this.job?.status === 'pending';
        },

        maybeStartPolling() {
            if (this.isWorking()) {
                clearTimeout(this.pollTimer);
                this.pollTimer = setTimeout(() => this.poll(), 3000);
            }
        },

        async poll() {
            await this.fetchJob();
            this.maybeStartPolling();
        },

        paramEntries() {
            const params = this.job?.params || {};
            return Object.entries(params)
                .filter(([key, value]) => value !== null && value !== undefined && value !== '')
                .map(([key, value]) => ({
                    key: key.replaceAll('_', ' '),
                    value: typeof value === 'object' ? JSON.stringify(value) : String(value),
                }));
        },

        slides() {
            const html = this.job?.results?.html;
            if (Array.isArray(html)) return html;
            if (typeof html === 'string' && html.trim() !== '') return [html];
            return [];
        },

        slideDocument(slideHtml) {
            return `<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<script src="https://cdn.tailwindcss.com"><\/script>
<style>
html,body{margin:0;width:100%;height:100%;overflow:hidden;background:#fff;}
body{font-family:Inter,Arial,sans-serif;}
.slide,.slide-container,section{width:100%;min-height:100%;}
</style>
</head>
<body>${slideHtml || ''}</body>
</html>`;
        },

        mindmapMarkdown() {
            return this.job?.results?.raw_markdown
                || this.job?.results?.markdown
                || this.job?.results?.result
                || '';
        },

        waitForMarkmap(attempts = 0) {
            if (window.markmap?.Markmap && window.markmap?.Transformer) return Promise.resolve(true);
            if (attempts > 50) return Promise.resolve(false);
            return new Promise(resolve => {
                setTimeout(() => resolve(this.waitForMarkmap(attempts + 1)), 100);
            });
        },

        async renderMindmap() {
            const markdown = this.mindmapMarkdown();
            if (!markdown || !this.$refs.mindmapSvg) return;

            this.mindmapLoading = true;
            const ready = await this.waitForMarkmap();
            if (!ready) {
                this.error = 'Markmap could not be loaded.';
                this.mindmapLoading = false;
                return;
            }

            try {
                const { Markmap, Transformer } = window.markmap;
                const svg = this.$refs.mindmapSvg;
                svg.innerHTML = '';

                const transformer = new Transformer();
                const { root } = transformer.transform(markdown);

                this.mmInstance = Markmap.create(svg, {
                    autoFit: true,
                    duration: 250,
                    initialExpandLevel: 3,
                    spacingHorizontal: 100,
                    spacingVertical: 8,
                    paddingX: 24,
                }, root);

                setTimeout(() => this.fitMindmap(), 250);
            } catch (e) {
                console.error('Markmap render error:', e);
                this.error = 'Failed to render mind map preview.';
            } finally {
                this.mindmapLoading = false;
            }
        },

        fitMindmap() {
            try {
                if (this.mmInstance?.fit) this.mmInstance.fit();
            } catch (e) {
                console.error('Markmap fit error:', e);
            }
        },

        async downloadFile(urlOrEndpoint, filename) {
            if (!urlOrEndpoint) return;

            try {
                Alpine.store('app').loading = true;
                await Api.download(urlOrEndpoint, filename);
            } catch (e) {
                alert(e.message || 'Download failed.');
            } finally {
                setTimeout(() => Alpine.store('app').loading = false, 800);
            }
        },
    };
}
</script>
<script src="https://cdn.jsdelivr.net/npm/d3@7"></script>
<script src="https://cdn.jsdelivr.net/npm/markmap-lib@0.17.0/dist/browser/index.iife.js"></script>
<script src="https://cdn.jsdelivr.net/npm/markmap-view@0.17.0/dist/browser/index.js"></script>
@endpush
@endsection
