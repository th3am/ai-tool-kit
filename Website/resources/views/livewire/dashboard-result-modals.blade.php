@php
    $hasDashboardResult = $generatedAnimation
        || $generatedVideoPath
        || $generatedMindMap
        || $generatedAudio
        || $generatedQuizId
        || ! empty($generatedSlides);
@endphp

@if($hasDashboardResult)
    <div wire:key="dashboard-result-shell-{{ $lastResultJobId ?? 'latest' }}">
        @if($generatedMindMap)
            <div
                wire:key="dashboard-mindmap-result-{{ $generatedMindMapJobId ?? $lastResultJobId ?? 'latest' }}"
                x-data="{
                    rawMarkdown: @js($generatedMindMap),
                    loading: true,
                    error: null,
                    async init() {
                        await this.render();
                    },
                    async loadScript(src) {
                        if (document.querySelector(`script[src='${src}']`)) {
                            return;
                        }

                        await new Promise((resolve, reject) => {
                            const script = document.createElement('script');
                            script.src = src;
                            script.onload = resolve;
                            script.onerror = reject;
                            document.head.appendChild(script);
                        });
                    },
                    async ensureMarkmap() {
                        if (window.markmap && window.markmap.Markmap && window.markmap.Transformer) {
                            return;
                        }

                        await this.loadScript('https://cdn.jsdelivr.net/npm/d3@7');
                        await this.loadScript('https://cdn.jsdelivr.net/npm/markmap-lib@0.15.4/dist/browser/index.min.js');
                        await this.loadScript('https://cdn.jsdelivr.net/npm/markmap-view@0.15.4/dist/browser/index.min.js');
                    },
                    normalizeMarkdown(source) {
                        let text = String(source || '').trim();
                        const markerMatch = text.match(/MINDMAP_JSON_START\s*([\s\S]*?)\s*MINDMAP_JSON_END/i);
                        if (markerMatch) {
                            text = markerMatch[1].trim();
                        }

                        const thoughtMatch = text.match(/Thought\s+for\s+\d+s\s*([\s\S]*)$/i);
                        if (thoughtMatch) {
                            text = thoughtMatch[1].trim();
                        }

                        try {
                            const start = text.indexOf('{');
                            const end = text.lastIndexOf('}');
                            const jsonText = start >= 0 && end > start ? text.slice(start, end + 1) : text;
                            const parsed = JSON.parse(jsonText);
                            if (parsed && Array.isArray(parsed.branches)) {
                                const out = [`# ${parsed.title || 'Mind Map'}`];
                                const append = (item, depth = 0) => {
                                    const indent = '  '.repeat(depth);
                                    if (typeof item === 'string') {
                                        if (item.trim()) out.push(`${indent}- ${item.trim()}`);
                                        return;
                                    }
                                    if (!item || typeof item !== 'object') return;
                                    const label = String(item.label || item.title || item.name || '').trim();
                                    if (label) out.push(`${indent}- ${label}`);
                                    const children = Array.isArray(item.children) ? item.children : [];
                                    children.forEach(child => append(child, depth + 1));
                                };
                                parsed.branches.forEach(branch => append(branch, 0));
                                return out.join('\n');
                            }
                        } catch (_) {}

                        const lines = text
                            .replace(/^```(?:markdown|md)?/i, '')
                            .replace(/```$/i, '')
                            .split(/\r?\n/)
                            .map(line => line.replace(/\t/g, '  ').replace(/\s+$/g, ''))
                            .filter(line => line.trim() !== '');

                        if (!lines.length) {
                            return '# Mind Map\n- No content generated';
                        }

                        const structured = lines.filter(line => /^\s*(#{1,6}\s+|[-*+]\s+|\d+\.\s+)/.test(line)).length;
                        if (structured >= Math.max(2, Math.ceil(lines.length * 0.35))) {
                            return lines.join('\n');
                        }

                        const genericHeading = /^#\s*(mind\s*map|result)$/i.test(lines[0].trim());
                        const title = genericHeading && lines[1] ? lines[1].trim().replace(/^#+\s*/, '') : lines[0].trim().replace(/^#+\s*/, '');
                        const body = lines.slice(genericHeading && lines[1] ? 2 : 1);
                        const branchLike = line => {
                            const trimmed = line.trim();
                            const words = trimmed.split(/\s+/).filter(Boolean);
                            return words.length <= 4 && /^[A-Z0-9][A-Za-z0-9 &/()+-]{2,55}$/.test(trimmed);
                        };

                        const output = [`# ${title || 'Mind Map'}`];
                        let hasBranch = false;
                        let childCount = 0;
                        const sectionWords = ['definition', 'overview', 'how', 'works', 'process', 'steps', 'example', 'speed', 'performance', 'complexity', 'conditions', 'uses', 'applications', 'advantages', 'benefits', 'disadvantages', 'limitations', 'features', 'types', 'components', 'summary', 'key concepts'];
                        const sectionLike = (line, nextLine) => {
                            if (childCount < 2) return false;
                            const normalized = line.trim().toLowerCase();
                            if (sectionWords.some(word => normalized.includes(word))) return true;
                            return branchLike(line) && String(nextLine || '').trim() !== '';
                        };

                        body.forEach((line, index) => {
                            const trimmed = line.trim();
                            if (!hasBranch || sectionLike(line, body[index + 1])) {
                                output.push(`- ${trimmed}`);
                                hasBranch = true;
                                childCount = 0;
                            } else {
                                output.push(`  - ${trimmed}`);
                                childCount++;
                            }
                        });

                        return output.join('\n');
                    },
                    renderFallback(markdown) {
                        const container = this.$refs.fallback;
                        if (!container) return;

                        const lines = markdown.split(/\r?\n/).filter(Boolean);
                        container.innerHTML = '';
                        lines.forEach(line => {
                            const item = document.createElement('div');
                            const level = Math.max(0, Math.floor((line.match(/^\s*/)?.[0]?.length || 0) / 2));
                            item.textContent = line.replace(/^#{1,6}\s+/, '').replace(/^[-*+]\s+/, '');
                            item.style.marginLeft = `${level * 18}px`;
                            item.className = level === 0 ? 'font-bold text-gray-900 dark:text-white mb-2' : 'text-gray-700 dark:text-gray-200 py-1';
                            container.appendChild(item);
                        });
                    },
                    async render() {
                        this.loading = true;
                        this.error = null;

                        try {
                            await this.ensureMarkmap();

                            const svg = this.$refs.svg;
                            svg.innerHTML = '';

                            const transformer = new window.markmap.Transformer();
                            const markdown = this.normalizeMarkdown(this.rawMarkdown || '# Result');
                            this.renderFallback(markdown);
                            const result = transformer.transform(markdown);
                            const { root } = result;
                            const mm = window.markmap.Markmap.create(svg, {
                                autoFit: true,
                                colorFreezeLevel: 3,
                                duration: 400,
                                initialExpandLevel: 4,
                                maxWidth: 360,
                                paddingX: 16,
                            }, root);

                            setTimeout(() => mm.fit(), 120);
                        } catch (e) {
                            const markdown = this.normalizeMarkdown(this.rawMarkdown || '# Result');
                            this.renderFallback(markdown);
                            this.error = e?.message || 'Unable to render the interactive mind map. Showing readable preview instead.';
                        } finally {
                            this.loading = false;
                        }
                    },
                    downloadSvg() {
                        const svg = this.$refs.svg?.cloneNode(true);
                        if (! svg) return;

                        svg.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
                        const blob = new Blob([new XMLSerializer().serializeToString(svg)], { type: 'image/svg+xml;charset=utf-8' });
                        const link = document.createElement('a');
                        link.href = URL.createObjectURL(blob);
                        link.download = 'mindmap.svg';
                        link.click();
                        URL.revokeObjectURL(link.href);
                    },
                    downloadPng() {
                        const svg = this.$refs.svg?.cloneNode(true);
                        if (! svg) return;

                        const serializer = new XMLSerializer();
                        const svgText = serializer.serializeToString(svg);
                        const canvas = document.createElement('canvas');
                        const box = this.$refs.preview.getBoundingClientRect();
                        canvas.width = Math.max(1200, Math.round(box.width * 2));
                        canvas.height = Math.max(800, Math.round(box.height * 2));
                        const ctx = canvas.getContext('2d');
                        ctx.fillStyle = '#ffffff';
                        ctx.fillRect(0, 0, canvas.width, canvas.height);

                        const image = new Image();
                        const blob = new Blob([svgText], { type: 'image/svg+xml;charset=utf-8' });
                        const url = URL.createObjectURL(blob);
                        image.onload = () => {
                            ctx.drawImage(image, 0, 0, canvas.width, canvas.height);
                            URL.revokeObjectURL(url);
                            const link = document.createElement('a');
                            link.href = canvas.toDataURL('image/png');
                            link.download = 'mindmap.png';
                            link.click();
                        };
                        image.src = url;
                    },
                    close() {
                        $wire.continueToChat();
                    },
                }"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
            >
                <div class="bg-white dark:bg-gray-950 w-full max-w-6xl h-[88vh] rounded-2xl shadow-2xl flex flex-col overflow-hidden border border-gray-200 dark:border-white/10">
                    <div class="flex items-center justify-between gap-4 p-4 border-b border-gray-200 dark:border-white/10">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Mind Map Preview</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Interactive Markmap preview</p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($generatedMindMapJobId)
                                <a href="{{ route('mindmap.download.png', $generatedMindMapJobId) }}" target="_blank" class="px-3 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">Server PNG</a>
                            @endif
                            <button type="button" @click="downloadSvg()" class="px-3 py-2 rounded-lg bg-gray-100 dark:bg-white/10 text-gray-800 dark:text-gray-100 text-sm font-semibold hover:bg-gray-200 dark:hover:bg-white/15">SVG</button>
                            <button type="button" @click="downloadPng()" class="px-3 py-2 rounded-lg bg-gray-100 dark:bg-white/10 text-gray-800 dark:text-gray-100 text-sm font-semibold hover:bg-gray-200 dark:hover:bg-white/15">PNG</button>
                            <button type="button" @click="close()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-white/10 text-gray-500">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 grid lg:grid-cols-[1fr_320px] min-h-0">
                        <div x-ref="preview" class="relative min-h-0 bg-gray-50 dark:bg-black overflow-hidden">
                            <div x-show="loading" class="absolute inset-0 flex items-center justify-center text-gray-500 dark:text-gray-400">Rendering preview...</div>
                            <div x-show="error" x-text="error" class="absolute inset-x-4 top-4 z-10 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-700"></div>
                            <svg x-ref="svg" class="w-full h-full markmap"></svg>
                            <div x-ref="fallback" x-show="error" class="absolute inset-x-4 top-20 bottom-4 overflow-auto rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-950"></div>
                        </div>
                        <div class="border-t lg:border-t-0 lg:border-l border-gray-200 dark:border-white/10 bg-white dark:bg-gray-950 p-4 overflow-y-auto">
                            <h4 class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">Markdown</h4>
                            <pre class="whitespace-pre-wrap text-sm leading-6 text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-white/5 rounded-xl p-4 border border-gray-200 dark:border-white/10">{{ $generatedMindMap }}</pre>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if(! empty($generatedSlides))
            <div
                wire:key="dashboard-presentation-result-{{ $generatedPresentationId ?? $lastResultJobId ?? 'latest' }}"
                x-data="{
                    slides: @js($generatedSlides),
                    index: 0,
                    close() { $wire.continueToChat(); },
                    slideHtml() {
                        return `<!doctype html><html><head><meta charset='utf-8'><script src='https://cdn.tailwindcss.com'><\/script><style>html,body{margin:0;width:100%;height:100%;overflow:hidden}body{display:flex}.slide{width:100%;height:100%;}</style></head><body>${this.slides[this.index] || ''}</body></html>`;
                    },
                }"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
            >
                <div class="bg-white dark:bg-gray-950 w-full max-w-7xl h-[90vh] rounded-2xl shadow-2xl flex flex-col overflow-hidden border border-gray-200 dark:border-white/10">
                    <div class="flex items-center justify-between gap-4 p-4 border-b border-gray-200 dark:border-white/10">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Presentation Preview</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Slide <span x-text="index + 1"></span> of <span x-text="slides.length"></span></p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($generatedPresentationId)
                                <a href="{{ route('presentation.download', $generatedPresentationId) }}" target="_blank" class="px-3 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">PDF</a>
                                <a href="{{ route('presentation.download.ppt', $generatedPresentationId) }}" target="_blank" class="px-3 py-2 rounded-lg bg-purple-600 text-white text-sm font-semibold hover:bg-purple-700">PowerPoint</a>
                            @endif
                            <button type="button" @click="close()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-white/10 text-gray-500">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 grid lg:grid-cols-[1fr_260px] min-h-0">
                        <div class="bg-gray-100 dark:bg-black p-4 md:p-8 flex items-center justify-center min-h-0 overflow-hidden">
                            <div class="aspect-video w-full max-w-5xl bg-white shadow-2xl">
                                <iframe class="w-full h-full border-0" :srcdoc="slideHtml()"></iframe>
                            </div>
                        </div>
                        <div class="border-t lg:border-t-0 lg:border-l border-gray-200 dark:border-white/10 bg-white dark:bg-gray-950 p-4 overflow-y-auto">
                            <div class="flex gap-2 mb-4">
                                <button type="button" @click="index = Math.max(0, index - 1)" :disabled="index === 0" class="flex-1 px-3 py-2 rounded-lg bg-gray-100 dark:bg-white/10 text-sm font-semibold disabled:opacity-40">Prev</button>
                                <button type="button" @click="index = Math.min(slides.length - 1, index + 1)" :disabled="index === slides.length - 1" class="flex-1 px-3 py-2 rounded-lg bg-gray-100 dark:bg-white/10 text-sm font-semibold disabled:opacity-40">Next</button>
                            </div>
                            <div class="space-y-3">
                                <template x-for="(slide, slideIndex) in slides" :key="slideIndex">
                                    <button type="button" @click="index = slideIndex" class="w-full text-left rounded-xl border p-3 text-sm transition" :class="index === slideIndex ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-200' : 'border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300'">
                                        Slide <span x-text="slideIndex + 1"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($generatedAnimation)
            <div
                wire:key="dashboard-animation-result-{{ $generatedAnimationJobId ?? $lastResultJobId ?? 'latest' }}"
                x-data="{ frameLoading: true, close() { $wire.continueToChat(); } }"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
            >
                <div class="bg-white dark:bg-gray-950 w-full max-w-5xl h-[84vh] rounded-2xl shadow-2xl flex flex-col overflow-hidden border border-gray-200 dark:border-white/10">
                    <div class="flex items-center justify-between gap-4 p-4 border-b border-gray-200 dark:border-white/10">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Animation Preview</h3>
                        <div class="flex items-center gap-2">
                            @if($generatedAnimationJobId)
                                <a href="{{ route('animation.download', ['job' => $generatedAnimationJobId, 'format' => 'svg']) }}" target="_blank" class="px-3 py-2 rounded-lg bg-gray-100 dark:bg-white/10 text-sm font-semibold">SVG</a>
                                <a href="{{ route('animation.download', ['job' => $generatedAnimationJobId, 'format' => 'mp4']) }}" target="_blank" class="px-3 py-2 rounded-lg bg-orange-600 text-white text-sm font-semibold">MP4</a>
                                <a href="{{ route('animation.download', ['job' => $generatedAnimationJobId, 'format' => 'gif']) }}" target="_blank" class="px-3 py-2 rounded-lg bg-pink-600 text-white text-sm font-semibold">GIF</a>
                            @endif
                            <button type="button" @click="close()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-white/10 text-gray-500">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="flex-1 bg-gray-100 dark:bg-black p-4 overflow-hidden flex items-center justify-center">
                        <div class="relative w-full h-full bg-white rounded-xl shadow-xl overflow-hidden border border-gray-200 dark:border-white/10">
                            @if($generatedAnimationPath)
                                <div x-show="frameLoading" class="absolute inset-0 flex items-center justify-center text-sm text-gray-500 bg-white">
                                    Loading animation preview...
                                </div>
                                <iframe
                                    src="{{ asset('storage/' . $generatedAnimationPath) }}"
                                    title="Animation SVG Preview"
                                    class="w-full h-full border-0 bg-white"
                                    loading="eager"
                                    @load="frameLoading = false"
                                ></iframe>
                            @else
                                <div class="w-full h-full overflow-auto flex items-center justify-center p-4 bg-white">
                                    {!! $generatedAnimation !!}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($generatedVideoPath)
            <div
                wire:key="dashboard-video-result-{{ $generatedVideoJobId ?? $lastResultJobId ?? 'latest' }}"
                x-data="{ close() { $wire.continueToChat(); } }"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
            >
                <div class="bg-white dark:bg-gray-950 w-full max-w-5xl rounded-2xl shadow-2xl overflow-hidden border border-gray-200 dark:border-white/10">
                    <div class="flex items-center justify-between gap-4 p-4 border-b border-gray-200 dark:border-white/10">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Video Preview</h3>
                        <div class="flex items-center gap-2">
                            @if($generatedVideoJobId)
                                <a href="{{ route('video.explainer.download', $generatedVideoJobId) }}" target="_blank" class="px-3 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">Download MP4</a>
                            @endif
                            <button type="button" @click="close()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-white/10 text-gray-500">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="bg-black">
                        <video controls autoplay class="w-full max-h-[72vh]">
                            <source src="{{ asset('storage/' . $generatedVideoPath) }}" type="video/mp4">
                        </video>
                    </div>
                </div>
            </div>
        @endif

        @if($generatedAudio)
            <div
                wire:key="dashboard-audio-result-{{ $lastResultJobId ?? 'latest' }}"
                x-data="{ close() { $wire.continueToChat(); } }"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
            >
                <div class="bg-white dark:bg-gray-950 w-full max-w-xl rounded-2xl shadow-2xl overflow-hidden border border-gray-200 dark:border-white/10">
                    <div class="flex items-center justify-between gap-4 p-4 border-b border-gray-200 dark:border-white/10">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Audio Preview</h3>
                        <button type="button" @click="close()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-white/10 text-gray-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="p-6 space-y-4">
                        <audio controls autoplay class="w-full">
                            <source src="{{ asset('storage/' . $generatedAudio) }}">
                        </audio>
                        <a href="{{ asset('storage/' . $generatedAudio) }}" download class="inline-flex px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">Download Audio</a>
                    </div>
                </div>
            </div>
        @endif

        @if($generatedQuizId)
            <div
                wire:key="dashboard-quiz-result-{{ $generatedQuizId }}"
                x-data="{ close() { $wire.continueToChat(); } }"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
            >
                <div class="bg-white dark:bg-gray-950 w-full max-w-xl rounded-2xl shadow-2xl overflow-hidden border border-gray-200 dark:border-white/10">
                    <div class="p-8 text-center space-y-5">
                        <div class="mx-auto w-16 h-16 rounded-2xl bg-pink-500/15 text-pink-500 flex items-center justify-center">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Quiz Ready</h3>
                            <p class="text-gray-500 dark:text-gray-400 mt-1">Open the generated quiz or continue to the chat session.</p>
                        </div>
                        <div class="grid sm:grid-cols-2 gap-3">
                            <a href="{{ route('quiz.show', $generatedQuizId) }}" class="px-4 py-3 rounded-xl bg-pink-600 text-white font-bold hover:bg-pink-700">Open Quiz</a>
                            <button type="button" @click="close()" class="px-4 py-3 rounded-xl bg-gray-100 dark:bg-white/10 text-gray-800 dark:text-gray-100 font-bold hover:bg-gray-200 dark:hover:bg-white/15">Go to Chat</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endif
