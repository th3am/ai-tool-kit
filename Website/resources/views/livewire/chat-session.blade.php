<div class="min-h-screen bg-gray-100 dark:bg-[#060b21] flex flex-col transition-colors duration-500" @if(!$viewingMindMap) wire:poll.5s="loadSession" @endif>
    <!-- Header -->
    <header class="backdrop-blur-xl bg-white/60 dark:bg-[#060b21]/60 border-b border-gray-300 dark:border-white/10 px-6 py-4 flex items-center justify-between sticky top-0 z-10 transition-all duration-300">
        <div class="flex items-center gap-4">
            <a href="{{ route('dashboard') }}" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ $session->title ?? 'Untitled Session' }}</h1>
                <p class="text-xs text-gray-500 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    Active Workspace
                </p>
            </div>
        </div>
        <div class="flex items-center gap-3" x-data="{ open: false }">
            <!-- Tool Quick Actions -->
            <div class="relative">
                <button @click="open = !open" @click.away="open = false" class="px-3 py-1.5 bg-[#DBDDE9] dark:bg-white/5 text-gray-600 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-white/15 rounded-lg text-sm font-medium transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    New Tool
                </button>
                <div x-show="open" class="absolute right-0 mt-2 w-48 backdrop-blur-3xl bg-white/50 dark:bg-white/5 rounded-xl shadow-lg border border-gray-300 dark:border-white/10 py-2 z-20" style="display: none;">
                    <button wire:click="startTool('mindmap')" @click="open = false" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2">
                        <span class="text-purple-500">🧠</span> Mind Map
                    </button>
                    <button wire:click="startTool('presentation')" @click="open = false" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2">
                        <span class="text-orange-500">📊</span> Presentation
                    </button>
                    <button wire:click="startTool('audio')" @click="open = false" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2">
                        <span class="text-blue-500">🎙️</span> Audio Narration
                    </button>
                    <button wire:click="startTool('quiz')" @click="open = false" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2">
                        <span class="text-green-500">📝</span> Quiz
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Chat Timeline -->
    <main class="flex-1 overflow-y-auto p-4 md:p-8 space-y-6" id="chat-timeline">
        @foreach($chatMessages as $msg)
            <div class="flex {{ $msg->role === 'user' ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-2xl w-full flex {{ $msg->role === 'user' ? 'flex-row-reverse' : 'flex-row' }} gap-4">
                    <!-- Avatar -->
                    <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center {{ $msg->role === 'user' ? 'bg-indigo-600' : 'bg-green-600' }} text-white text-xs font-bold">
                        {{ $msg->role === 'user' ? 'ME' : 'AI' }}
                    </div>

                    <!-- Content Bubble -->
                    <div class="flex flex-col {{ $msg->role === 'user' ? 'items-end' : 'items-start' }}">
                        <div class="px-5 py-3 rounded-2xl shadow-md border {{ $msg->role === 'user' ? 'bg-gradient-to-r from-[#3b82f6] to-[#6366f1] text-white rounded-tr-none border-none' : 'backdrop-blur-3xl bg-white/50 dark:bg-white/5 text-gray-800 dark:text-white rounded-tl-none border-gray-200 dark:border-white/10' }}">
                            
                            @if($msg->tool_job_id && $msg->toolJob)
                                <!-- Tool Result Card -->
                                <div class="mb-2 pb-2 border-b border-white/20 dark:border-gray-700">
                                    <span class="text-xs font-bold uppercase tracking-wider opacity-75">Used Tool: {{ $msg->toolJob->tool_type }}</span>
                                </div>
                                <div class="prose dark:prose-invert text-sm">
                                    {!! Str::markdown($msg->content) !!}
                                </div>
                                @if($msg->toolJob->tool_type === 'mindmap' && isset($msg->toolJob->results['raw_markdown']))
                                    <button 
                                        wire:click="viewToolResult({{ $msg->toolJob->id }})"
                                        wire:loading.attr="disabled"
                                        class="mt-3 w-full py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-medium transition flex items-center justify-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        View Result
                                        <span wire:loading wire:target="viewToolResult({{ $msg->toolJob->id }})" class="ml-2 animate-pulse">...</span>
                                    </button>
                                @elseif($msg->toolJob->tool_type === 'presentation' && isset($msg->toolJob->results['presentation_id']))
                                    <div class="mt-3 flex flex-col gap-2">
                                        <a href="{{ route('presentation.download', $msg->toolJob->results['presentation_id']) }}" target="_blank" class="w-full py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-medium transition flex items-center justify-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            Download PDF
                                        </a>
                                        <a href="{{ route('presentation.download.ppt', $msg->toolJob->results['presentation_id']) }}" class="w-full py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-medium transition flex items-center justify-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                            Download PPT
                                        </a>

                                        <!-- WhatsApp Share -->
                                        <div class="mt-1">
                                            @if($activeShareJobId === $msg->toolJob->id)
                                                <div class="flex flex-col gap-2 p-3 bg-white/5 rounded-lg border border-white/10 animate-fadeIn transition-all">
                                                    <label class="text-xs text-white/70 font-medium">Recipient Number:</label>
                                                    <div class="flex gap-2">
                                                        <input type="text" wire:model.defer="whatsappNumber" placeholder="Ex: 62888xxxx" class="flex-1 bg-black/20 border border-white/20 rounded px-2 py-1.5 text-xs text-white placeholder-white/30 focus:outline-none focus:border-green-400/50 focus:ring-1 focus:ring-green-400/50">
                                                        <button wire:click="sendToWhatsApp({{ $msg->toolJob->id }})" wire:loading.attr="disabled" class="px-3 py-1.5 bg-green-500 hover:bg-green-600 rounded text-xs font-bold text-white shadow-sm flex items-center gap-1 disabled:opacity-50">
                                                            <span wire:loading.remove wire:target="sendToWhatsApp({{ $msg->toolJob->id }})">Send</span>
                                                            <span wire:loading wire:target="sendToWhatsApp({{ $msg->toolJob->id }})">...</span>
                                                        </button>
                                                    </div>
                                                    @if(session('whatsapp_success_' . $msg->toolJob->id))
                                                        <span class="text-green-400 text-xs font-bold flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Sent successfully!</span>
                                                    @endif
                                                    @error('whatsapp_' . $msg->toolJob->id)
                                                        <span class="text-red-300 text-xs">{{ $message }}</span>
                                                    @enderror
                                                    <button wire:click="toggleShare({{ $msg->toolJob->id }})" class="text-xs text-white/40 hover:text-white text-left mt-1">Cancel</button>
                                                </div>
                                            @else
                                                <button wire:click="toggleShare({{ $msg->toolJob->id }})" class="w-full py-2 bg-green-500/10 hover:bg-green-500/20 text-green-200/90 rounded-lg text-sm font-medium transition flex items-center justify-center gap-2 border border-green-500/20">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                                    Share WhatsApp
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @elseif($msg->toolJob->tool_type === 'audio' && isset($msg->toolJob->results['audio_path']))
                                    <div class="mt-3 bg-white/5 rounded-lg p-3">
                                        <div class="flex items-center gap-3 mb-2">
                                            <div class="w-8 h-8 rounded-full bg-indigo-500/20 flex items-center justify-center">
                                                <span class="text-sm">🎵</span>
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold opacity-75">Audio Narration</p>
                                                <p class="text-xs opacity-50">Generated by Gemini TTS</p>
                                            </div>
                                        </div>
                                        <audio controls class="w-full h-8 rounded accent-indigo-500">
                                            @php
                                                $audioPath = $msg->toolJob->results['audio_path'];
                                                $mime = Str::endsWith($audioPath, '.wav') ? 'audio/wav' : 'audio/mpeg';
                                            @endphp
                                            <source src="{{ asset('storage/' . $audioPath) }}" type="{{ $mime }}">
                                            Your browser does not support the audio element.
                                        </audio>
                                        
                                        @if(isset($msg->toolJob->results['script']))
                                            <div class="mt-2 pt-2 border-t border-white/10">
                                                <details class="text-xs">
                                                    <summary class="cursor-pointer opacity-70 hover:opacity-100">View Script</summary>
                                                    <p class="mt-1 opacity-80 leading-relaxed italic">"{{ Str::limit($msg->toolJob->results['script'], 200) }}..."</p>
                                                </details>
                                            </div>
                                        @endif
                                    </div>
                                @elseif($msg->toolJob->tool_type === 'video-animation' && isset($msg->toolJob->results['svg_path']))
                                    <div class="mt-3 bg-white/5 rounded-lg p-3">
                                        <div class="flex items-center gap-3 mb-2">
                                            <div class="w-8 h-8 rounded-full bg-orange-500/20 flex items-center justify-center">
                                                <span class="text-sm">🎬</span>
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold opacity-75">2D Animation</p>
                                                <p class="text-xs opacity-50">Generated by Gemini</p>
                                            </div>
                                        </div>
                                        
                                        <div class="w-full aspect-video bg-white dark:bg-black/50 rounded-lg overflow-hidden flex items-center justify-center border border-white/10 mb-3 relative group">
                                             <img src="{{ asset('storage/' . $msg->toolJob->results['svg_path']) }}" class="max-h-full max-w-full block" alt="Animation Preview">
                                             <div class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                                 <a href="{{ asset('storage/' . $msg->toolJob->results['svg_path']) }}" target="_blank" class="px-3 py-1 bg-white text-black rounded font-medium text-xs hover:bg-gray-100">Open Full Screen</a>
                                             </div>
                                        </div>

                                        <div class="flex flex-wrap gap-2">
                                             <a href="{{ route('animation.download', ['job' => $msg->toolJob->id, 'format' => 'svg']) }}" target="_blank" class="flex-1 py-1.5 bg-white/10 hover:bg-white/20 rounded text-xs font-medium text-center">
                                                SVG
                                             </a>
                                             <a href="{{ route('animation.download', ['job' => $msg->toolJob->id, 'format' => 'mp4']) }}" target="_blank" class="flex-1 py-1.5 bg-orange-500/80 hover:bg-orange-600 rounded text-xs font-medium text-center text-white">
                                                Download MP4
                                             </a>
                                             <a href="{{ route('animation.download', ['job' => $msg->toolJob->id, 'format' => 'gif']) }}" target="_blank" class="flex-1 py-1.5 bg-pink-500/80 hover:bg-pink-600 rounded text-xs font-medium text-center text-white">
                                                Download GIF
                                             </a>
                                        </div>
                                    </div>
                                @elseif($msg->toolJob->tool_type === 'quiz' && isset($msg->toolJob->results['quiz_id']))
                                    <div class="mt-3 bg-white/5 rounded-lg p-3">
                                        <div class="flex items-center gap-3 mb-3">
                                            <div class="w-8 h-8 rounded-full bg-pink-500/20 flex items-center justify-center">
                                                <span class="text-sm">Q</span>
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold opacity-75">Quiz Ready</p>
                                                <p class="text-xs opacity-50">Generated MCQ quiz</p>
                                            </div>
                                        </div>
                                        <a href="{{ route('quiz.show', $msg->toolJob->results['quiz_id']) }}" class="w-full py-2 bg-pink-500/80 hover:bg-pink-600 rounded-lg text-sm font-medium transition flex items-center justify-center gap-2 text-white">
                                            View & Play Quiz
                                        </a>
                                    </div>
                                @elseif(in_array($msg->toolJob->tool_type, ['video-explainer', 'lecture'], true) && isset($msg->toolJob->results['video_path']))
                                    <div class="mt-3 bg-white/5 rounded-lg p-3">
                                        <div class="flex items-center gap-3 mb-3">
                                            <div class="w-8 h-8 rounded-full bg-teal-500/20 flex items-center justify-center">
                                                <span class="text-sm">▶</span>
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold opacity-75">Video Explainer</p>
                                                <p class="text-xs opacity-50">Generated MP4 video</p>
                                            </div>
                                        </div>
                                        <video controls class="w-full rounded-lg bg-black mb-3" src="{{ asset('storage/' . $msg->toolJob->results['video_path']) }}"></video>
                                        <a href="{{ route('video.explainer.download', $msg->toolJob->id) }}" class="w-full py-2 bg-teal-500/80 hover:bg-teal-600 rounded-lg text-sm font-medium transition flex items-center justify-center gap-2 text-white">
                                            Download MP4
                                        </a>
                                    </div>
                                @endif
                                
                            @else
                                <!-- Standard Message -->
                                <div wire:key="msg-{{ $msg->id }}" class="prose {{ $msg->role === 'user' ? 'prose-invert' : 'dark:prose-invert' }} max-w-none">
                                    @if($loop->last && $msg->role === 'assistant' && $msg->created_at->gt(now()->subSeconds(30)))
                                        <!-- Typed.js for latest AI response -->
                                        <div x-data="{
                                            init() {
                                                new Typed(this.$refs.typedOutput, {
                                                    strings: [this.$refs.source.innerHTML],
                                                    typeSpeed: 10,
                                                    showCursor: false,
                                                    contentType: 'html'
                                                });
                                            }
                                        }">
                                            <div x-ref="source" class="hidden">{!! Str::markdown($msg->content) !!}</div>
                                            <div x-ref="typedOutput"></div>
                                        </div>
                                    @else
                                        {!! Str::markdown($msg->content) !!}
                                    @endif
                                </div>
                            @endif
                        </div>
                        <span class="text-xs text-gray-400 mt-1">{{ $msg->created_at->format('h:i A') }}</span>
                    </div>
                </div>
            </div>
        @endforeach
        
        <!-- Script for Typed.js -->
        <script src="https://unpkg.com/typed.js@2.1.0/dist/typed.umd.js"></script>
        <!-- Loading Indicator -->
        <div wire:loading.flex wire:target="sendMessage" class="justify-start animate-fadeIn">
            <div class="flex gap-4">
                 <div class="flex-shrink-0 w-8 h-8 rounded-full bg-green-600 flex items-center justify-center text-white text-xs font-bold animate-pulse">
                     AI
                 </div>
                 <div class="flex flex-col items-start w-full">
                     <div class="px-5 py-3 rounded-2xl rounded-tl-none backdrop-blur-3xl bg-white/50 dark:bg-white/5 border border-gray-200 dark:border-white/10 shadow-md">
                         <div class="flex space-x-2 items-center h-6">
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
                         </div>
                     </div>
                     <span class="text-xs text-gray-400 mt-1">Generating response...</span>
                 </div>
            </div>
        </div>
    </main>

    <!-- Input Area -->
    <div class="backdrop-blur-xl bg-white/60 dark:bg-[#060b21]/60 border-t border-gray-300 dark:border-white/10 p-4 transition-all duration-300">
        <div class="max-w-4xl mx-auto relative">
            <form wire:submit.prevent="sendMessage">
                <input 
                    wire:model="newMessage" 
                    type="text" 
                    wire:loading.attr="disabled"
                    wire:target="sendMessage"
                    placeholder="Ask follow-up questions or run a command..." 
                    class="w-full pl-4 pr-12 py-4 bg-gray-200 dark:bg-[#060b21] border border-gray-300 dark:border-white/10 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#6366f1] focus:border-transparent transition-all shadow-sm disabled:opacity-50 disabled:cursor-wait text-gray-900 dark:text-white"
                >
                <button 
                    type="submit" 
                    wire:loading.attr="disabled"
                    wire:target="sendMessage"
                    class="absolute right-2 top-2 p-2 bg-gradient-to-r from-[#3b82f6] to-[#6366f1] hover:from-[#2563eb] hover:to-[#4f46e5] text-white rounded-lg transition shadow-md disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <svg wire:loading.remove wire:target="sendMessage" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                    <svg wire:loading wire:target="sendMessage" class="animate-spin w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </form>
        </div>
    </div>

    <!-- Mark Map Result Modal (Reused Logic) -->
    @if($viewingMindMap)
    <div 
        wire:ignore
        x-data="{ 
            rawMarkdown: @js($viewingMindMap),
            mm: null,
            init() {
                this.$nextTick(() => {
                    this.waitForMarkmap();
                });
            },
            waitForMarkmap(attempts = 0) {
                if (window.markmap && window.markmap.Markmap && window.markmap.Transformer) {
                    this.renderMap();
                } else {
                    if (attempts > 50) {
                        console.error('Markmap missing');
                        alert('Failed to load libraries.');
                        return;
                    }
                    setTimeout(() => this.waitForMarkmap(attempts + 1), 100);
                }
            },
            renderMap() {
                try {
                    const { Markmap, Transformer } = window.markmap;
                    const svg = this.$refs.mmSvg;
                    svg.innerHTML = '';
                    
                    if (!this.rawMarkdown) return;

                    const transformer = new Transformer();
                    const { root, features } = transformer.transform(this.rawMarkdown);
                    
                    this.mm = Markmap.create(svg, {
                        height: '100%',
                        duration: 250,
                        autoFit: false,
                    }, root);
                    
                    setTimeout(() => this.mm.fit(), 500);
                } catch (e) {
                    console.error('Mindmap error:', e);
                }
            },
            downloadSVG() {
                const svg = this.$refs.mmSvg;
                const svgData = new XMLSerializer().serializeToString(svg);
                const blob = new Blob([svgData], { type: 'image/svg+xml;charset=utf-8' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = 'mindmap.svg';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            },
            downloadPNG() {
                 const svg = this.$refs.mmSvg;
                 const canvas = document.createElement('canvas');
                 const ctx = canvas.getContext('2d');
                 
                 const clonedSvg = svg.cloneNode(true);
                 clonedSvg.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
                 
                 const style = document.createElement('style');
                 style.textContent = `
                    .markmap-container { display: flex; justify-content: center; align-items: center; }
                    .markmap text { font-family: 'Tajawal', sans-serif; }
                 `;
                 
                 if (document.documentElement.classList.contains('dark')) {
                     style.textContent += `
                        .markmap text { fill: #f9fafb !important; }
                        .markmap div { color: #f9fafb !important; }
                        .markmap path { stroke: #9ca3af !important; }
                        .markmap circle { stroke: #9ca3af !important; fill: #1f2937 !important; }
                     `;
                 } else {
                     style.textContent += `
                        .markmap text { fill: #1f2937; }
                        .markmap path { stroke: #9ca3af; }
                        .markmap circle { stroke: #9ca3af; fill: #fff; }
                     `;
                 }
                 clonedSvg.prepend(style);

                 const svgData = new XMLSerializer().serializeToString(clonedSvg);
                 const img = new Image();
                 
                 img.onload = () => {
                     try {
                         const bbox = svg.getBBox();
                         const padding = 20;
                         canvas.width = (bbox.width + padding * 2) * 2; 
                         canvas.height = (bbox.height + padding * 2) * 2;
                         
                         ctx.fillStyle = document.documentElement.classList.contains('dark') ? '#111827' : '#ffffff';
                         ctx.fillRect(0, 0, canvas.width, canvas.height);
                         
                         const x = (canvas.width - bbox.width * 2) / 2;
                         const y = (canvas.height - bbox.height * 2) / 2;
                         
                         ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                         
                         const pngUrl = canvas.toDataURL('image/png');
                         const link = document.createElement('a');
                         link.href = pngUrl;
                         link.download = 'mindmap.png';
                         document.body.appendChild(link);
                         link.click();
                         document.body.removeChild(link);
                         URL.revokeObjectURL(url);
                     } catch (err) {
                         console.error('PNG conversion failed:', err);
                         alert('Failed to generate PNG. Please try SVG download.');
                     }
                 };
                 
                 img.onerror = (e) => {
                     console.error('Image load failed:', e);
                     alert('Failed to load SVG for conversion.');
                 };

                 const svgBlob = new Blob([svgData], {type: 'image/svg+xml;charset=utf-8'});
                 const url = URL.createObjectURL(svgBlob);
                 img.src = url;
            },
            close() {
                $wire.closeToolResult();
            }
        }"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
    >
        <!-- Styles -->
        <style>
            svg.markmap { width: 100% !important; height: 100% !important; }
            .dark .markmap text { fill: #f9fafb !important; }
            .dark .markmap div { color: #f9fafb !important; }
            .dark .markmap path { stroke: #9ca3af !important; }
            .dark .markmap circle { stroke: #9ca3af !important; fill: #1f2937 !important; }
        </style>

        <div class="bg-white dark:bg-[#111827] w-full max-w-7xl h-[90vh] rounded-2xl shadow-2xl flex flex-col relative overflow-hidden">
             <!-- Header -->
             <div class="flex justify-between items-center px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-white/10">
                <h3 class="text-xl sm:text-2xl font-bold text-gray-800 dark:text-white">Mind Map Viewer</h3>
                <div class="flex gap-2">
                     <button @click="downloadSVG()" class="px-3 py-1 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded text-sm font-medium text-gray-700 dark:text-gray-300">SVG</button>
                     <a href="{{ route('mindmap.download.png', $viewingMindMapJobId) }}" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-sm font-medium flex items-center justify-center">
                        PNG
                     </a>
                     <button @click="close()" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-full text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                     </button>
                </div>
            </div>
            
            <!-- Canvas -->
            <div class="flex-1 bg-gray-50 dark:bg-[#0b1020] p-3 sm:p-6 overflow-hidden relative">
                 <svg x-ref="mmSvg" class="w-full h-full block"></svg>
            </div>
        </div>
    </div>
    @endif

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/d3@7"></script>
    <script src="https://cdn.jsdelivr.net/npm/markmap-lib@0.15.4/dist/browser/index.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/markmap-view@0.15.4/dist/browser/index.min.js"></script>
</div>
